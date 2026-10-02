<?php
/*
multiselect.php
 ├─ includeDirectory("./functions/common")
 ├─ dbh()                                    [common]
 ├─ tolkar $_GET['table'] i formatet "textareaId::tabell:aktuellaVärden"
 ├─ validerar tabell mot multiselectables.php, tableAliases.php och allowlistan
 ├─ all_from_table($dbh, $configSchema, $table)   [common] → hämtar tillåtna rader
 ├─ toSwedish($table)                        [common] → rubrik på svenska
 ├─ includeDirectory("./js-functions/multiselect")  → klistrar in ALLA js-filer inline i <script>
 └─ renderar HTML: <select> + knappar, med inline onclick-anrop till JS-funktionerna
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

$values = all_from_table($dbh, $configSchema, $table);
$currentSkin = currentSkin(all_from_table($dbh, $configSchema, 'skins'));
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
	<style>
HTML;

printSkinVariables($currentSkin);
require("./styles/multiselect.css");

echo <<<HTML
	</style>
	<script>
		window.onload = function() {
			if (window.parent !== window) { // Make sure we are in an iframe
				window.parent.postMessage({ action: 'resize' }, window.location.origin);
			}
		};
	</script>
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

echo <<<HTML
<button onClick='document.querySelector("#selection").innerHTML=null;document.querySelector("#selection").value=null;document.querySelector("#selectbox").setAttribute("data-sorted-values", "");document.querySelector("#selectbox").value="";document.querySelector("#selectbox")?.querySelectorAll("option").forEach(o => o.removeAttribute("selected"));'>Töm</button>&nbsp;
<button type="button" onclick="sendSelectionAndClose('{$textareaIdEsc}');">Använd värde</button>&nbsp;
<button type="button" title="Stäng" aria-label="Stäng" onclick="closeTopFrame();"><span aria-hidden="true">&#x22A0;</span></button>
<script>selectOptionsByValues('selectbox', '{$dataSortedValuesEsc}');makeSelectToggleOnly('selectbox');</script>
</body>
</html>
HTML;