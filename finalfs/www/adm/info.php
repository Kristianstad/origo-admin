<?php
/*
info.php
 ├─ includeDirectory("./functions/common")
 ├─ includeDirectory("./functions/info")   → laddar printParents.php, printUniqueLogins.php
 ├─ dbh()                                          [common]
 ├─ toSwedish($childType)                          [common] → svensk översättning av typnamnet
 ├─ all_from_table($dbh, $configSchema, ...)        [common] → hämtar alla rader av given typ
 ├─ array_column_search(...)                        [common] → hittar EN rad baserat på kolumnvärde
 ├─ makeBasicTarget()/makeFullTarget()               [common] → bygger basic/full target för objektet
 ├─ targetTable()/targetIdColumn()                   [common] → läser targetens tabell och id-kolumn
 ├─ targetConfigParam()                              [common] → läser targetens fält
 ├─ (om $childType == 'source' och QGIS) läser .qgs-fil direkt från disk
 ├─ (om $childType == 'aduser') printUniqueLogins(...)  [info] → inloggningsstatistik
 ├─ findAllParents($dbh, $child)                     [common] → hittar alla objekt som refererar till detta target
 └─ printParents($allParents)                        [info] → skriver ut länkad lista av föräldrar
     └─ använder internt: assoc_array_values, toSwedish  [common]
*/

// Tell browsers to not cache response
header("Cache-Control: must-revalidate, max-age=0, s-maxage=0, no-cache, no-store");

// Expose specific functions
require_once("./functions/includeDirectory.php");

// Expose all functions in given folders
includeDirectory("./functions/common");
includeDirectory("./functions/info");

require("./constants/configSchema.php");

$dbh = dbh();

$childType   = $_GET['type'] ?? '';
$childId     = $_GET['id'] ?? '';
$childTypeSv = toSwedish($childType);
$currentSkin = currentSkin(all_from_table($dbh, $configSchema, 'skins'));

// === Början av sidan ===
echo <<<HTML
<!DOCTYPE html>
<html>
<head>
	<style>
HTML;

printSkinVariables($currentSkin);
require("./styles/info.css");

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
HTML;

// === Innehåll ===
if (!empty($childId)) {
	echo "<div>";
	echo "<h2>$childId</h2> ($childTypeSv)</br>";

	$child = makeBasicTarget($childType, $childId);
	$childId = targetId($child);
	$allOfChildType = all_from_table($dbh, $configSchema, targetTable($child));
	$childFullTarget = makeFullTarget($childType, array_column_search($childId, targetIdColumn($child), $allOfChildType));
	if (!empty(targetConfigParam($childFullTarget, 'name'))) {
		echo "<b>Namn: </b>" . targetConfigParam($childFullTarget, 'name') . "</br>";
	}
	if (!empty(targetConfigParam($childFullTarget, 'alias'))) {
		echo "<b>Alias: </b>" . targetConfigParam($childFullTarget, 'alias') . "</br>";
	}
	if (!empty(targetConfigParam($childFullTarget, 'info'))) {
		echo targetConfigParam($childFullTarget, 'info') . "</br>";
	}

	if ($childType == 'source') {
		$services = all_from_table($dbh, $configSchema, 'services');
		$serviceTarget = makeBasicTarget('service', targetConfigParam($childFullTarget, 'service'));
		$serviceConfig = array_column_search(targetId($serviceTarget), targetIdColumn($serviceTarget), $services);
		$serviceFullTarget = makeFullTarget('service', $serviceConfig);
		$serviceType = targetConfigParam($serviceFullTarget, 'type');
		if (strtolower($serviceType) == 'qgis') {
			$qgsXml = simplexml_load_file('/services/' . targetConfigParam($childFullTarget, 'service') . '/' . explode('#', $childId)[0] . '.qgs');
			if (!empty($qgsXml)) {
				echo "<b>Qgis-version: </b>" . $qgsXml['version'] . "<br>";
				echo "<b>Senast uppdaterad: </b>" . $qgsXml['saveDateTime'] . ", " . $qgsXml['saveUserFull'] . "<br>";
			}
		}
		unset($serviceTarget, $serviceConfig, $serviceFullTarget);
	}

	if ($childType == 'aduser') {
		printUniqueLogins(array_column($allOfChildType, 'lastlogin'));
	}

	$allParents = findAllParents($dbh, $child);
	if (!empty(array_values($allParents))) {
		echo "<h3 style='margin-top:0.5em'>Används av</h3></br>";
		printParents($allParents);
	}

	echo '</div>';

	if (!empty($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'info.php') !== false) {
		echo '&nbsp;<button type="button" title="Tillbaka" aria-label="Tillbaka" onclick="history.back()"><span class="backArrow" aria-hidden="true">&#x2190;</span></button>';
	}

	echo "&nbsp;<form action='manage.php' method='post' target='_blank' style='display:inline'><button type='submit' title='Administrera' aria-label='Administrera' name='" . $childType . "Id' value='" . $childId . "'><span class='adminArrow' aria-hidden='true'>&#x21AA;</span><span class='adminAsterisk' aria-hidden='true'>*</span></button></form>";
	echo "&nbsp;<button type=\"button\" title=\"Stäng\" aria-label=\"Stäng\" onclick=\"window.parent.postMessage({ action: 'close' }, window.location.origin);\"><span aria-hidden=\"true\">&#x22A0;</span></button>";
}

pg_close($dbh);

// === Slut på sidan ===
echo <<<HTML
</body>
</html>
HTML;