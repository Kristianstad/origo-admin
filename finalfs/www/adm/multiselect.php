<?php
/*
multiselect.php
 ├─ includeDirectory("./functions/common")
 ├─ dbh()                                    [common]
 ├─ tolkar $_GET['table'] i formatet "textareaId::tabell:aktuellaVärden"
 ├─ validerar tabell mot multiselectables.php, tableAliases.php och allowlistan
 ├─ allFromTable($dbh, $configSchema, $table)   [common] → hämtar tillåtna rader
 ├─ toSwedish($table)                        [common] → rubrik på svenska
 ├─ includeDirectory("./js-functions/multiselect")  → klistrar in modulens JS-filer inline i <script>
 ├─ renderUtilityHead($currentSkin, 'multiselect')   → skriver modul- och common.css samt gemensamma utility-JS
 └─ renderar HTML: <select> + knappar, med korta anrop till JS-funktionerna
*/

// Tell browsers to not cache response
header("Cache-Control: must-revalidate, max-age=0, s-maxage=0, no-cache, no-store");

// Expose specific functions
require_once("./functions/includeDirectory.php");

// Expose all functions in given folders
includeDirectory("./functions/common");

$dbh = dbh();
$tableParameter = $_GET['table'] ?? '';
if (!is_string($tableParameter)) {
	pg_close($dbh);
	http_response_code(400);
	exit('Invalid table');
}
$submitValue = explode('::', $tableParameter, 2);
$textareaId = $submitValue[0] ?? '';
$submitValue = explode(':', $submitValue[1] ?? '', 2);
$table = $submitValue[0] ?? '';

require("./constants/configSchema.php");
require("./constants/multiselectables.php");
require("./constants/tableAliases.php");
$allowedTables = array();
foreach ($multiselectables as $selectableTable) {
	$allowedTable = $tableAliases[$selectableTable] ?? $selectableTable;
	if (in_array($allowedTable, configTableNames($dbh), true)) {
		$allowedTables[] = $allowedTable;
	}
}
if (!in_array($table, $allowedTables, true)) {
	pg_close($dbh);
	http_response_code(400);
	exit('Invalid table');
}

if (empty($submitValue[1])) {
    $currentValue = '';
    $dataSortedValues = '';
} else {
    $currentValue = $submitValue[1];
    $dataSortedValues = $currentValue . ',';
}

$values = allFromTable($dbh, $configSchema, $table);
$currentSkin = currentSkin(allFromTable($dbh, $configSchema, 'skins'));
pg_close($dbh);

$idColumn = pkColumnOfTable($table);

$header = mbUcfirst(toSwedish($table));

// Escape for safe HTML output
$textareaIdEsc       = htmlspecialchars($textareaId, ENT_QUOTES, 'UTF-8');
$currentValueEsc     = htmlspecialchars($currentValue, ENT_QUOTES, 'UTF-8');
$dataSortedValuesEsc = htmlspecialchars($dataSortedValues, ENT_QUOTES, 'UTF-8');
$headerEsc           = htmlspecialchars($header, ENT_QUOTES, 'UTF-8');

// === Början av sidan ===
echo <<<HTML
<!DOCTYPE html>
<html>
<head>
	<script>
HTML;

includeDirectory("./js-functions/multiselect");
echo <<<HTML
	</script>
HTML;
renderUtilityHead($currentSkin, 'multiselect');
echo <<<HTML
</head>
<body>
<select id="selectbox" onChange="update(this);" data-sorted-values="{$dataSortedValuesEsc}" multiple>
HTML;

foreach (array_column($values, $idColumn) as $option) {
    $optionEsc = htmlspecialchars($option, ENT_QUOTES, 'UTF-8');
    echo "<option value='{$optionEsc}'>{$optionEsc}</option>";
}

echo <<<HTML
</select>
<h3>{$headerEsc}</h3>
<textarea readonly id="selection">{$currentValueEsc}</textarea>
HTML;

if (!empty($currentValue)) {
    echo '<button onClick="window.location.reload();">Återställ</button>&nbsp;';
}

$closeButton = renderCloseButton();
echo <<<HTML
<button type="button" onClick="clearSelection();">Töm</button>&nbsp;
<button type="button" onclick="sendSelectionAndClose('{$textareaIdEsc}');">Använd värde</button>&nbsp;
{$closeButton}
<script>selectOptionsByValues('selectbox', '{$dataSortedValuesEsc}');makeSelectToggleOnly('selectbox');</script>
</body>
</html>
HTML;