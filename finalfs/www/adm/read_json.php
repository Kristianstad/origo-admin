<?php
/* Importerar valda delar av en Origo-konfiguration i en transaktion. */

header("Cache-Control: must-revalidate, max-age=0, s-maxage=0, no-cache, no-store");

require_once("./functions/includeDirectory.php");
includeDirectory("./functions/common");
includeDirectory("./functions/read_json");
require("./constants/configSchema.php");

function importRequested(array $post, string $name): bool
{
	return ($post[$name] ?? null) === 'yes';
}

function importError(string $message): void
{
	http_response_code(400);
	echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
	exit;
}

function pgPointLiteral(array $coordinates): ?string
{
	if (count($coordinates) !== 2)
	{
		return null;
	}
	return '('.(string) $coordinates[0].','.(string) $coordinates[1].')';
}

function pgExtentLiteral(array $coordinates): ?string
{
	if (count($coordinates) !== 4)
	{
		return null;
	}
	return '('.(string) $coordinates[0].','.(string) $coordinates[1].'),('.(string) $coordinates[2].','.(string) $coordinates[3].')';
}

// === Visa formulär om ingen JSON skickats ===
$post=$_POST;
if (empty($post['json'])) {
    $importid = uniqid();
	$csrfToken=generateCsrfToken();
	$helpJson=topFormHelpButton('read_json:json');
	$helpImportId=topFormHelpButton('read_json:importid');
	$helpLayers=topFormHelpButton('read_json:layers');
	$helpGroups=topFormHelpButton('read_json:groups');
	$helpMap=topFormHelpButton('read_json:map');
	$helpMapId=topFormHelpButton('read_json:mapid');
	$helpControls=topFormHelpButton('read_json:controls');
	$helpFooters=topFormHelpButton('read_json:footers');
	$helpProj4defs=topFormHelpButton('read_json:proj4defs');
	$helpSources=topFormHelpButton('read_json:sources');
	$helpTilegrids=topFormHelpButton('read_json:tilegrids');
	$helpStyles=topFormHelpButton('read_json:styles');
	$helpServices=topFormHelpButton('read_json:services');
	$dbh=dbh();
	$currentSkin=currentSkin(all_from_table($dbh, $configSchema, 'skins'));
	pg_close($dbh);

    echo <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Importera Origo-konfiguration</title>
<style>
HTML;

	printSkinVariables($currentSkin);
	require("./styles/read_json.css");

	echo <<<HTML
</style>
<script>
	window.onload = function() {
		if (window.parent !== window) {
			window.parent.postMessage({ action: 'resize' }, window.location.origin);
		}
	};
</script>
</head>
<body>
<form method="post"
      onsubmit="return confirm('Att importera en hel origokonfiguration i JSON-format till databasen är riskabelt. Det kan innebära att ett stort antal redundanta poster läggs till i databasen och att redan befintliga origokonfigurationer slutar att fungera. Är du säker på att du vill importera till databasen?');"
		>
	<div class="printXFormDiv">
		<input type="hidden" name="csrf_token" value="{$csrfToken}">
		<span class="optionSpan"><label title="read_json:json" for="json">Json:</label> <textarea rows="1" class="textareaXLarge" id="json" name="json"></textarea>{$helpJson}</span><wbr>

		<span class="optionSpan"><label title="read_json:importid" for="importid">Unikt import-id:</label> <textarea rows="1" class="textareaMedium" id="importid" name="importid">{$importid}</textarea>{$helpImportId}</span><wbr>

		<span class="optionSpan"><label title="read_json:layers" for="layers">Lager:</label> <input type="checkbox" id="layers" name="layers" value="yes" checked>{$helpLayers}</span><wbr>

		<span class="optionSpan"><label title="read_json:groups" for="groups">Grupper:</label> <input type="checkbox" id="groups" name="groups" value="yes" checked>{$helpGroups}</span><wbr>

		<span class="optionSpan"><label title="read_json:map" for="map">Karta:</label> <input type="checkbox" id="map" name="map" value="yes" checked>{$helpMap}</span>
		<span class="optionSpan"><label title="read_json:mapid" for="mapid">Namn:</label> <textarea rows="1" class="textareaMedium" id="mapid" name="mapid">map#{$importid}</textarea>{$helpMapId}</span><wbr>

		<span class="optionSpan"><label title="read_json:controls" for="controls">Kontroller:</label> <input type="checkbox" id="controls" name="controls" value="yes" checked>{$helpControls}</span><wbr>

		<span class="optionSpan"><label title="read_json:footers" for="footers">Sidfötter:</label> <input type="checkbox" id="footers" name="footers" value="yes" checked>{$helpFooters}</span><wbr>

		<span class="optionSpan"><label title="read_json:proj4defs" for="proj4defs">proj4defs:</label> <input type="checkbox" id="proj4defs" name="proj4defs" value="yes" checked>{$helpProj4defs}</span><wbr>

		<span class="optionSpan"><label title="read_json:sources" for="sources">Källor:</label> <input type="checkbox" id="sources" name="sources" value="yes" checked>{$helpSources}</span><wbr>

		<span class="optionSpan"><label title="read_json:tilegrids" for="tilegrids">Tilegrids:</label> <input type="checkbox" id="tilegrids" name="tilegrids" value="yes" checked>{$helpTilegrids}</span><wbr>

		<span class="optionSpan"><label title="read_json:styles" for="styles">Stilar:</label> <input type="checkbox" id="styles" name="styles" value="yes" checked>{$helpStyles}</span><wbr>

		<span class="optionSpan"><label title="read_json:services" for="services">Tjänster:</label> <input type="checkbox" id="services" name="services" value="yes" checked>{$helpServices}</span><wbr>

	<div class="readJsonButtonDiv">
		<button class="updateButton" type="submit" name="submit" value="submit">Importera</button>
		<button class="updateButton" type="button" title="Stäng" aria-label="Stäng" onclick="window.parent.postMessage({ action: 'close' }, window.location.origin);"><span aria-hidden="true">&#x22A0;</span></button>
	</div>
	</div>
</form>
</body>
</html>
HTML;
    exit;
}

if (!validateCsrfToken($post['csrf_token'] ?? null))
{
	importError('Ogiltig eller saknad säkerhetstoken. Ladda om formuläret och försök igen.');
}

$importId=trim((string) ($post['importid'] ?? ''));
if (!preg_match('/\A[A-Za-z0-9_-]{1,64}\z/D', $importId))
{
	importError('Import-id får endast innehålla bokstäver, siffror, bindestreck och understreck.');
}

$json=(string) ($post['json'] ?? '');
$json_arr=json_decode($json, true, 512, JSON_BIGINT_AS_STRING);
if (json_last_error() !== JSON_ERROR_NONE || !is_array($json_arr))
{
	importError('JSON kunde inte läsas: '.json_last_error_msg());
}

foreach (array('controls', 'layers', 'styles', 'groups', 'source', 'proj4Defs', 'resolutions') as $field)
{
	if (isset($json_arr[$field]) && !is_array($json_arr[$field]))
	{
		importError('JSON-fältet '.$field.' måste vara en array.');
	}
}

if (isset($json_arr['pageSettings']) && !is_array($json_arr['pageSettings']))
{
	importError('JSON-fältet pageSettings måste vara en array.');
}
if (isset($json_arr['pageSettings']['mapGrid']) && !is_array($json_arr['pageSettings']['mapGrid']))
{
	importError('JSON-fältet pageSettings.mapGrid måste vara en array.');
}
if (isset($json_arr['pageSettings']['footer']) && !is_array($json_arr['pageSettings']['footer']))
{
	importError('JSON-fältet pageSettings.footer måste vara en array.');
}
foreach (array('projectionExtent', 'extent', 'center') as $field)
{
	if (isset($json_arr[$field]) && !is_array($json_arr[$field]))
	{
		importError('JSON-fältet '.$field.' måste vara en array.');
	}
}
if (isset($json_arr['tileGridOptions']) && !is_array($json_arr['tileGridOptions']))
{
	importError('JSON-fältet tileGridOptions måste vara en array.');
}
if (importRequested($post, 'map') && empty(trim((string) ($post['mapid'] ?? ''))))
{
	importError('Kartans namn får inte vara tomt.');
}
foreach ($json_arr['controls'] ?? array() as $control)
{
	if (!is_array($control) || !isset($control['name']) || !is_string($control['name']))
	{
		importError('Varje kontroll måste ha ett namn.');
	}
}
foreach ($json_arr['groups'] ?? array() as $group)
{
	if (!is_array($group) || !isset($group['name']) || !is_string($group['name']))
	{
		importError('Varje grupp måste ha ett namn.');
	}
}
foreach ($json_arr['source'] ?? array() as $source)
{
	if (!is_array($source) || !isset($source['url']) || !is_string($source['url']))
	{
		importError('Varje källa måste ha en URL.');
	}
}

$dbh=dbh();
$configTables=configTables($dbh);
/*
 *************************
 *  DATABAS-OPERATIONER  *
 *************************
*/

//$maps=$configTables['maps'];
$groups=$configTables['groups'];
//$controls=$configTables['controls'];
//$sources=$configTables['sources'];
//$services=$configTables['services'];
//$footers=$configTables['footers'];
//$tilegrids=$configTables['tilegrids'];
$proj4defs=$configTables['proj4defs'];
//setLayers();

$jsonControls=$json_arr['controls'] ?? array();
$jsonLayers=$json_arr['layers'] ?? array();
$jsonStyles=$json_arr['styles'] ?? array();
$jsonPageSettings=$json_arr['pageSettings'] ?? array();

if (isset($jsonPageSettings['mapGrid']))
{
	$jsonMapGrid=$jsonPageSettings['mapGrid'];
}
else
{
	$jsonMapGrid['visible']=false;
}
$jsonFooter=$jsonPageSettings['footer'] ?? array();
$jsonProjectionCode=$json_arr['projectionCode'] ?? '';
$jsonProjectionExtent=$json_arr['projectionExtent'] ?? array();
$jsonFeatureinfoOptions=$json_arr['featureinfoOptions'] ?? array();
$jsonProj4Defs=$json_arr['proj4Defs'] ?? array();
$jsonExtent=$json_arr['extent'] ?? array();
$jsonCenter=$json_arr['center'] ?? array();
$jsonZoom=$json_arr['zoom'] ?? null;
$jsonGroups=array();
$backgroundGroup=null;
$noneLayer=false;
foreach ($json_arr['groups'] ?? array() as $group)
{
	if (stripos($group['name'], 'background') === 0)
	{
		$backgroundGroup=$group;
	}
	else
	{
		$jsonGroups[]=$group;
	}
}

$jsonSource=$json_arr['source'] ?? array();
$jsonServices=array();
$jsonTilegrids=array();
$serviceCount=1;
$tilegridCount=1;
$jsonResolutions=toPgArrayLiteral($json_arr['resolutions'] ?? array());
$mapGroups=array();
$mapFooter=null;
$tilegridId=null;

try
{
	executeImportQuery($dbh, 'BEGIN');
foreach ($jsonSource as $sourceId => $source)
{
	$urlQuery=array();
	$sourceTilegridId=null;
	if (importRequested($post, 'tilegrids') && !empty($source['tileGrid']))
	{
		if (!in_array($source['tileGrid'], $jsonTilegrids, true))
		{
			$tilegridId="tilegrid$tilegridCount#$importId";
			$sourceTilegridId=$tilegridId;
			$jsonTilegrids[$tilegridId]=$source['tileGrid'];
			$sql="INSERT INTO {$configSchema}.tilegrids(tilegrid_id, tilesize, resolutions) VALUES ($1, $2, $3)";
			executeImportQuery($dbh, $sql, array($tilegridId, $source['tileGrid']['tileSize'] ?? null, toPgArrayLiteral($source['tileGrid']['resolutions'] ?? array())));
			$tilegridCount++;

		}
		elseif (isset($tilegridId) && in_array($source['tileGrid'], $jsonTilegrids, true))
		{
			$sourceTilegridId=array_search($source['tileGrid'], $jsonTilegrids, true);
		}
	}

	if (strpos($source['url'], "?") !== false)
	{
		parse_str(substr($source['url'], strpos($source['url'], "?") + 1), $urlQuery);
		//$source['url']=substr($source['url'], 0, strpos($source['url'], "?"));
	}
	$source['url']=dirname($source['url']);
	if (importRequested($post, 'services'))
	{
		if (!in_array($source['url'], $jsonServices))
		{
			$jsonServices["service$serviceCount"]=$source['url'];
			$source['service']=array_search($source['url'], $jsonServices)."#$importId";
			$sql="INSERT INTO {$configSchema}.services(service_id, base_url) VALUES ($1, $2)";
			executeImportQuery($dbh, $sql, array($source['service'], $source['url']));
			$serviceCount++;
		}
		else
		{
			$source['service']=array_search($source['url'], $jsonServices)."#$importId";
		}
	}
	if (importRequested($post, 'sources'))
	{
		$sourceColumns='source_id, service, tilegrid';
		$sourceParams=array($sourceId."#$importId", $source['service'] ?? null, $sourceTilegridId);
		$sourcePlaceholders=array('$1', '$2', '$3');
		if (!empty($urlQuery['with_geometry']))
		{
			$sourceColumns=$sourceColumns.',with_geometry';
			$sourceParams[]=$urlQuery['with_geometry'];
			$sourcePlaceholders[]='$'.count($sourceParams);
		}
		if (!empty($urlQuery['fi_point_tolerance']))
		{
			$sourceColumns=$sourceColumns.',fi_point_tolerance';
			$sourceParams[]=$urlQuery['fi_point_tolerance'];
			$sourcePlaceholders[]='$'.count($sourceParams);
		}
		$sql="INSERT INTO {$configSchema}.sources($sourceColumns) VALUES (".implode(',', $sourcePlaceholders).")";
		executeImportQuery($dbh, $sql, $sourceParams);
	}

}

$mapControls=array();
if (importRequested($post, 'controls'))
{
	foreach ($jsonControls as $control)
	{
		$mapControls[]=$control['name']."#$importId";
		$sql="INSERT INTO {$configSchema}.controls(control_id, options) VALUES ($1, $2)";
		executeImportQuery($dbh, $sql, array(($control['name'] ?? '')."#$importId", isset($control['options']) ? json_encode($control['options'], JSON_PRETTY_PRINT) : null));
	}
}

$uniqueLayers=array();
$mapLayers=array();
$groupsLayers=array();
$allLayers=array();
if (importRequested($post, 'layers'))
{
	$allLayers=flattenGroupLayers($jsonLayers);
	foreach ($allLayers as $layer)
	{
		$layer['name']=renamedup($layer['name'] ?? '', $uniqueLayers);
		if (importRequested($post, 'styles'))
		{
			$styleResult=extractLayerStyleConfig($jsonStyles[$layer['style'] ?? ''] ?? array());
			$layerStyleConfig=$styleResult['config'];
			$layerIcon=$styleResult['icon'];
			$layerExtendedIcon=$styleResult['extendedIcon'];
			$layerStyleFilter=$styleResult['filter'];
			$layerClusterStyle=!empty($layer['clusterStyle']) && isset($jsonStyles[$layer['clusterStyle']])
				? json_encode($jsonStyles[$layer['clusterStyle']], JSON_PRETTY_PRINT)
				: '[]';
		}
		else
		{
			$layerStyleConfig='[]';
			$layerIcon='';
			$layerExtendedIcon='';
			$layerStyleFilter='';
			$layerClusterStyle='[]';
		}
		if (empty($layer['group']))
		{
			$mapLayers[]=$layer['name']."$importId";
		}
		else
		{
			if ($layer['group'] == 'none')
			{
				$noneLayer=true;
			}
			else
			{
				$noneLayer=false;
			}
			if (isset($groupsLayers[$layer['group']]) && is_array($groupsLayers[$layer['group']]))
			{
				$groupsLayers[$layer['group']][]=$layer['name']."$importId";
			}
			else
			{
				$groupsLayers[$layer['group']]=array($layer['name']."$importId");
			}
		}
		$styleResult=array(
			'config'=>$layerStyleConfig,
			'icon'=>$layerIcon,
			'extendedIcon'=>$layerExtendedIcon,
			'filter'=>$layerStyleFilter,
			'clusterStyle'=>$layerClusterStyle
		);
		list($sql, $layerParams)=buildLayerInsert($configSchema, $layer, $importId, $styleResult);
		executeImportQuery($dbh, $sql, $layerParams);
	}
}

if (importRequested($post, 'groups'))
{
	if ($noneLayer && !empty($backgroundGroup) && ($backgroundGroup['name'] ?? '') === 'background')
	{
		$jsonGroups[]=array('name' => "none");
	}
	if (!empty($backgroundGroup))
	{
		$jsonGroups[]=$backgroundGroup;
	}
	$mapGroups=recursiveGroups($dbh, $jsonGroups, $importId, $groupsLayers);
}

$mapProj4Defs=array();
foreach ($jsonProj4Defs as $def)
{
	if (!isset($def['code']))
	{
		continue;
	}
	$mapProj4Defs[]=$def['code'];
	if (importRequested($post, 'proj4defs'))
	{
		if (!in_array($def['code'], array_column($proj4defs, 'code')))
		{
			if ($def['code'] == $jsonProjectionCode)
			{
				$sql="INSERT INTO {$configSchema}.proj4defs(code, projection, projectionextent, alias) VALUES ($1, $2, $3, $4)";
				$params=array($def['code'], $def['projection'] ?? null, pgExtentLiteral($jsonProjectionExtent), $def['alias'] ?? null);
			}
			else
			{
				$sql="INSERT INTO {$configSchema}.proj4defs(code, projection, alias) VALUES ($1, $2, $3)";
				$params=array($def['code'], $def['projection'] ?? null, $def['alias'] ?? null);
			}
			executeImportQuery($dbh, $sql, $params);
		}
	}
}

if (!empty($jsonFooter))
{
	$mapFooter="footer#$importId";
	if (importRequested($post, 'footers'))
	{
		$sql="INSERT INTO {$configSchema}.footers(footer_id, img, url, text) VALUES ($1, $2, $3, $4)";
		executeImportQuery($dbh, $sql, array($mapFooter, $jsonFooter['img'] ?? null, $jsonFooter['url'] ?? null, $jsonFooter['text'] ?? null));
	}
}

if (importRequested($post, 'map'))
{
	if (!empty($json_arr['enableRotation']))
	{
		$jsonEnableRotation=var_export($json_arr['enableRotation'], true);
	}
	else
	{
		$jsonEnableRotation='false';
	}
	if (!empty($json_arr['constrainResolution']))
	{
		$jsonConstrainResolution=var_export($json_arr['constrainResolution'], true);
	}
	else
	{
		$jsonConstrainResolution='true';
	}
	if (importRequested($post, 'tilegrids') && !empty($json_arr['tileGridOptions']))
	{
		$tileGridOptions=$json_arr['tileGridOptions'];
		if (!isset($tileGridOptions['resolutions']))
		{
			$tileGridOptions['resolutions']=$json_arr['resolutions'] ?? array();
		}
		if (!in_array($tileGridOptions, $jsonTilegrids, true))
		{
			$tilegridId="tilegrid$tilegridCount#$importId";
			$jsonTilegrids[$tilegridId]=$tileGridOptions;

			$sql="INSERT INTO {$configSchema}.tilegrids(tilegrid_id, tilesize, resolutions) VALUES ($1, $2, $3)";
			executeImportQuery($dbh, $sql, array($tilegridId, $tileGridOptions['tileSize'] ?? null, toPgArrayLiteral($tileGridOptions['resolutions'])));
			$tilegridCount++;
		}
	}
	$mapColumns='map_id, mapgrid, projectioncode, featureinfooptions, extent, enablerotation, constrainresolution, resolutions, controls, groups, layers, proj4defs, footer, tilegrid, changed';
	$mapParams=array(
		trim((string) ($post['mapid'] ?? '')),
		var_export($jsonMapGrid['visible'] ?? false, true),
		$jsonProjectionCode,
		json_encode($jsonFeatureinfoOptions, JSON_PRETTY_PRINT),
		pgExtentLiteral($jsonExtent),
		$jsonEnableRotation,
		$jsonConstrainResolution,
		$jsonResolutions,
		toPgArrayLiteral($mapControls),
		toPgArrayLiteral($mapGroups),
		toPgArrayLiteral($mapLayers),
		toPgArrayLiteral($mapProj4Defs),
		$mapFooter ?? null,
		$tilegridId ?? null,
		't'
	);
	if (!empty($jsonCenter))
	{
		$mapColumns=$mapColumns.', center';
		$mapParams[]=pgPointLiteral($jsonCenter);
	}
	if (isset($jsonZoom))
	{
		$mapColumns=$mapColumns.', zoom';
		$mapParams[]=$jsonZoom;
	}
	$mapPlaceholders=array();
	foreach ($mapParams as $index => $unused)
	{
		$mapPlaceholders[]='$'.($index+1);
	}
	$sql="INSERT INTO {$configSchema}.maps($mapColumns) VALUES (".implode(',', $mapPlaceholders).")";
	executeImportQuery($dbh, $sql, $mapParams);
}

	executeImportQuery($dbh, 'COMMIT');
pg_close($dbh);
}
catch (Throwable $exception)
{
	if (isset($dbh))
	{
		@pg_query($dbh, 'ROLLBACK');
		pg_close($dbh);
	}
	error_log('read_json import failed: '.$exception->getMessage());
	importError('Importen misslyckades. Inga ändringar sparades.');
}

$dbh=dbh();
$currentSkin=currentSkin(all_from_table($dbh, $configSchema, 'skins'));
pg_close($dbh);

echo <<<HTML
<!DOCTYPE html>
<html lang="sv">
<head>
<meta charset="utf-8">
<title>Importera Origo-konfiguration</title>
<style>
HTML;

printSkinVariables($currentSkin);
require("./styles/read_json.css");

echo <<<HTML
</style>
</head>
<body>
<div class="importSuccessMessage">Import lyckades!</div>
<button class="updateButton" type="button" title="Stäng" aria-label="Stäng" onclick="window.parent.postMessage({ action: 'close' }, window.location.origin);"><span aria-hidden="true">&#x22A0;</span></button>
</body>
</html>
HTML;