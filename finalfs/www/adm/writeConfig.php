<?php
/*
writeConfig.php
 ├─ laddar composer-biblioteket "minify" (MatthiasMullie\Minify) för CSS/JS
 ├─ includeDirectory("./functions/common")
 ├─ includeDirectory("./functions/writeConfig")
 ├─ tolkar $_GET['map'] (mapId, ev. med "\"-separerad extra data – "swiper"-workaround)
 ├─ configTables($dbh)                       [common] → hämtar ALLA konfigtabeller på en gång, extract() till lokala variabler
 ├─ bygger upp $configJson som en PHP-array:
 │    ├─ addControlsToJson()      [writeConfig] → kartkontroller (zoom, mätverktyg etc.)
 │    ├─ pageSettings (footer, mapGrid, embedded)
 │    ├─ projektion, extent, center, zoom, upplösningar
 │    ├─ proj4Defs (koordinatsystem-definitioner)
 │    ├─ groupDepth() + getArrayValuesRecursively() + indexweightedLayersList()  [writeConfig]
 │    │     → bygger och sorterar den fullständiga, platta listan av lager (rekursivt genom grupper)
 │    ├─ addGroupsToJson()        [writeConfig] → grupphierarki
 │    └─ addLayersToJson()        [writeConfig] → lagerlistan + metadata som PHP-array
 ├─ serialiserar och validerar konfigurationen med json_encode()/json_decode()
 ├─ formaterar den snyggt med json_format()  [writeConfig]
 └─ två huvudlägen beroende på $_GET['getJson']/$_GET['getHtml']:
      ├─ getJson=y  → returnera bara JSON-konfigurationen (nedladdningsbar)
      └─ annars     → generera fullständig HTML-sida:
           ├─ addPlugins()              [writeConfig] → plugin-JS/CSS
           ├─ renderCssTags()/renderJavaScriptTags()  [writeConfig, EJ SEDDA ÄNNU]
           ├─ minifiera CSS/JS med Minify-biblioteket
           ├─ fixDuplicateDeclarations() [writeConfig] → JS-transformation, se nedan
           ├─ (om sökmotorindexerbar) bygger strukturerad data (schema.org JSON-LD) + sitemap.xml
           ├─ publishMapFiles()          [writeConfig, EJ SEDD ÄNNU] → skriver till disk
           ├─ markMapUnchanged()         [writeConfig] → nollställer "ändrad"-flaggan i databasen
           └─ bygger RESTRICTEDLAYERS-konstanten (!) baserat på tjänsternas restricted-flagga
                → defineFileConstant('RESTRICTEDLAYERS', ...)  [common]
*/

// Tell browsers to not cache response
header("Cache-Control: must-revalidate, max-age=0, s-maxage=0, no-cache, no-store");

// Expose specific functions
require_once('../../composer/minify/autoload.php');
require_once("./functions/includeDirectory.php");

// Expose all functions in given folders
includeDirectory("./functions/common");
includeDirectory("./functions/writeConfig");

use MatthiasMullie\Minify;

require("./constants/webRoot.php");

// Workaround for swiper in preview https://github.com/SigtunaGIS/swiper-plugin/issues/41 <start>
$getMapParam = explode('\\', $_GET['map'] ?? '', 2);
$mapId = $getMapParam[0];
if (isset($getMapParam[1])) {
	$_GET['getJson'] = 'y';
}
// Workaround for swiper in preview </end>

$mapIdArray = explode('#', $mapId, 2);
$mapName = trim($mapIdArray[0]);
if (!empty($mapIdArray[1])) {
	$mapNumber = trim($mapIdArray[1]);
} else {
	$mapNumber = '';
}

$configDir = "$webRoot/maps/$mapName";
if (!is_dir($configDir)) {
	if (!mkdir($configDir, 0770, true) && !is_dir($configDir)) {
		die("Kunde inte skapa konfigurationskatalogen: $configDir");
	}
	chmod($configDir, 0770);
}

ignore_user_abort(true);
$dbh = dbh();
$configTables = configTables($dbh);
extract($configTables);

$map = array_column_search($mapId, 'map_id', $maps);
$writeConfigContext = array(
	'map' => &$map,
	'controls' => &$controls,
	'groups' => &$groups,
	'layers' => &$layers,
	'mapLayers' => &$mapLayers,
	'mapStyles' => &$mapStyles,
	'mapSources' => &$mapSources,
	'sources' => &$sources,
	'services' => &$services,
	'contacts' => &$contacts,
	'origins' => &$origins,
	'tables' => &$tables,
	'plugins' => &$plugins,
	'tilegrids' => &$tilegrids,
);
$configJson = array();

if (isset($map['css'])) {
	$mapCss = $map['css'];
} else {
	$mapCss = '';
}
if (isset($map['js'])) {
	$mapJs = $map['js'];
} else {
	$mapJs = '';
}
if (isset($map['onload'])) {
	$mapOnload = $map['onload'];
} else {
	$mapOnload = '';
}

if (!empty($map['controls'])) {
	$mapControls = pgArrayToPhp($map['controls']);
	$configJson['controls'] = addControlsToJson($mapControls, $mapCss, $mapJs, $mapOnload, $writeConfigContext);
}

$pageSettings = array();
if (!empty($map['footer'])) {
	$footer = array_column_search($map['footer'], 'footer_id', $footers);
	$footerJson = array(
		'img' => $footer['img'],
		'url' => $footer['url']
	);
	if (!empty($footer['text'])) {
		$footerJson['text'] = $footer['text'];
	}
	$pageSettings['footer'] = $footerJson;
}

$pageSettings['mapGrid'] = array('visible' => $map['mapgrid'] === 't');
if ($map['embedded'] == 'f') {
	$pageSettings['mapInteractions'] = array('embedded' => false);
}
$configJson['pageSettings'] = $pageSettings;
unset($pageSettings);

$toJsonNumber = function ($value) {
	return is_numeric($value) ? $value + 0 : $value;
};

$mapJson = array(
	'projectionCode' => $map['projectioncode'],
	'extent' => array_map($toJsonNumber, explode(',', pgBoxToText($map['extent']))),
	'center' => array_map($toJsonNumber, explode(',', pgCoordsToText($map['center']))),
	'zoom' => $toJsonNumber($map['zoom']),
	'enableRotation' => $map['enablerotation'] === 't',
	'constrainResolution' => $map['constrainresolution'] === 't',
	'resolutions' => array_map($toJsonNumber, pgArrayToPhp($map['resolutions']))
);
if (!empty(array_column_search($map['projectioncode'], 'code', $proj4defs)['projectionextent'])) {
	$mapProjectionExtent = pgBoxToText(array_column_search($map['projectioncode'], 'code', $proj4defs)['projectionextent']);
	$mapJson['projectionExtent'] = array_map($toJsonNumber, explode(',', $mapProjectionExtent));
} else {
	if ($map['projectioncode'] != 'EPSG:3857' && $map['projectioncode'] != 'EPSG:4326') {
		require("./constants/proxyRoot.php");
		echo '<script>alert("Projektionsutbredning saknas! Ingen konfiguration skriven."); window.location.href="' . $proxyRoot . $_SERVER["REQUEST_URI"] . '&badJson=y";</script>';
		pg_close($dbh);
		exit;
	}
}

$mapJson['featureinfoOptions'] = json_decode($map['featureinfooptions'], true);
if (!empty($map['palette'])) {
	$mapJson['palette'] = json_decode($map['palette'], true);
}
if (!empty($map['tilegrid'])) {
	$tilegrid = array_column_search($map['tilegrid'], 'tilegrid_id', $tilegrids);
	$mapJson['tileGridOptions'] = array('tileSize' => $toJsonNumber($tilegrid['tilesize']));
}
$configJson = array_merge($configJson, $mapJson);
unset($mapJson, $toJsonNumber);

// Proj4Defs <start>
$mapProj4defs = pgArrayToPhp($map['proj4defs']);
$proj4DefsJson = array();
foreach ($mapProj4defs as $proj4def) {
	$proj4def = array_column_search($proj4def, 'code', $proj4defs);
	$proj4defJson = array(
		'code' => $proj4def['code'],
		'projection' => $proj4def['projection']
	);
	if (!empty($proj4def['alias'])) {
		$proj4defJson['alias'] = $proj4def['alias'];
	}
	$proj4DefsJson[] = $proj4defJson;
}
$configJson['proj4Defs'] = $proj4DefsJson;
// Proj4Defs </end>

$mapLayers = array();
if (!empty(pgArrayToPhp($map['layers']))) {
	$mapLayers['root'] = pgArrayToPhp($map['layers']);
	$mapLayers['root'] = preg_filter('/^/', 'root>', $mapLayers['root']);
}

if (isset($_GET['getHtml']) && $_GET['getHtml'] == 'y' && (!empty($_GET['group']) || !empty($_GET['layer']))) {
	if (!empty($_GET['group'])) {
		if (empty(trim($map['groups'], '{}')) || explode('#', $_GET['group'], 2)[0] == 'background') {
			$map['groups'] = '{' . $_GET['group'] . '}';
		} else {
			$map['groups'] = '{' . $_GET['group'] . ',' . trim($map['groups'], '{}') . '}';
		}
	} elseif (!empty($_GET['layer'])) {
		$mapLayers['root'] = array();
		$mapLayers['root'][] = 'root>' . $_GET['layer'];
	}
}

$mapGroups = pgArrayToPhp($map['groups']);
$mapLayerIds = groupDepth($mapGroups, $mapLayers, $writeConfigContext);
$mapLayersList = getArrayValuesRecursively($mapLayerIds);
$mapLayersList = indexweightedLayersList($mapLayersList, $writeConfigContext);
$groupsJson = addGroupsToJson($map['groups'], $writeConfigContext);
if (!empty($groupsJson)) {
	$configJson['groups'] = $groupsJson;
}
unset($groupsJson);
$layersMeta = array();
$layersJson = addLayersToJson($mapLayersList, $layersMeta, false, $writeConfigContext);
$configJson['layers'] = $layersJson;
$configJson['source'] = addSourcesToJson($writeConfigContext);
$configJson['styles'] = addStylesToJson($writeConfigContext);
$json = json_encode($configJson, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
unset($configJson);
unset($layersJson);

if (isset($_GET['badJson'])) {
	header('Content-Type: application/octet-stream');
	header("Content-Disposition: attachment;filename=$mapId.json");
	echo "$json";
	pg_close($dbh);
	exit;
}

if (json_decode($json) === null) {
	require("./constants/proxyRoot.php");
	echo '<script>alert("Fel i Json! Ingen konfiguration skriven."); window.location.href="' . $proxyRoot . $_SERVER["REQUEST_URI"] . '&badJson=y";</script>';
	pg_close($dbh);
	exit;
}

$json = json_encode(json_decode($json), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$jsonPretty = json_format($json);

if (isset($_GET['getJson']) && $_GET['getJson'] == 'y') {
	if (isset($_GET['download']) && $_GET['download'] == 'y') {
		header('Content-Type: application/octet-stream');
		header("Content-Disposition: attachment;filename=$mapId.json");
	}
	echo "$jsonPretty";
} else {
	$html = <<<HERE
<!DOCTYPE html>
<html lang="sv">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
		<meta http-equiv="X-UA-Compatible" content="IE=Edge;chrome=1">
		<meta name="robots" content="index, follow">
		<title>{$map['title']}</title>
		<link rel="shortcut icon" href="{$map['icon']}">
HERE;

	if (isset($_GET['getHtml']) && $_GET['getHtml'] == 'y') {
		require("./constants/previewBase.php");
		$base = rtrim($previewBase, '/') . '/';
	} else {
		require("./constants/proxyRoot.php");
		$base = rtrim($proxyRoot, '/') . '/';
	}
	$html = $html . "\n\t\t<base href='$base'>";
	unset($base);

	$html = $html . "\n" . <<<HERE
	</head>
	<body>
HERE;

	$mapCssFiles = pgArrayToPhp($map['css_files']);
	$mapJsFiles = pgArrayToPhp($map['js_files']);
	if (!empty($map['plugins'])) {
		$mapPlugins = pgArrayToPhp($map['plugins']);
		addPlugins($mapPlugins, $mapCssFiles, $mapJsFiles, $mapCss, $mapJs, $mapOnload, $writeConfigContext);
	}

	$html = $html . renderCssTags($mapCssFiles);
	if (!empty($mapCss)) {
		$cssMinifier = new Minify\CSS($mapCss);
		$minifiedCss = $cssMinifier->minify();
		$html = $html . "\n\t\t<style>$minifiedCss</style>";
		unset($cssMinifier, $minifiedCss);
	}
	unset($mapCss);

	$html = $html . renderJavaScriptTags($mapJsFiles);
	$html = $html . "\n" . <<<HERE
		<div id="app-wrapper"></div>
		<script>
HERE;

	$mapJsInit = <<<HERE
			const urlParams = new URLSearchParams(window.location.search);
			const hashParams = new URLSearchParams(window.location.hash.slice(1));
			function getUrlParam(param) {
				return urlParams.get(param) ?? hashParams.get(param);
			}
			let origo;
			let origoConfig = {$json};
			origo = Origo(origoConfig);
HERE;

	if (!empty($mapJs)) {
		$mapJs = $mapJsInit . $mapJs;
	} else {
		$mapJs = $mapJsInit;
	}

	$jsMinifier = new Minify\JS($mapJs);
	$minifiedJs = $jsMinifier->minify();
	$html = $html . "\n{$minifiedJs}\n";
	unset($mapJs, $jsMinifier, $minifiedJs);

	if (!empty($mapOnload)) {
		$mapOnload = fixDuplicateDeclarations($mapOnload);
/*
			$mapOnloadInit = <<<HERE
			function defineConst(name, value) {
				try {
					if (typeof window[name] === 'undefined') {
						window[name] = value;
					} else {
						setVariable(name, value, 'let');
					}
				} catch (e) {
					console.error('Error defining const ' + name + ':', e.message);
				}
			}
			
			function setVariable(name, value, type = 'let') {
				try {
					if (typeof window[name] === 'undefined') {
						window[name] = value;
					} else if (type !== 'const') {
						window[name] = value;
					}
				} catch (e) {
					console.error('Error setting ' + type + ' ' + name + ':', e.message);
				}
			}
			
			
			HERE;
			$mapOnload=$mapOnloadInit.$mapOnload;
*/
		$mapOnload = "\norigo.on('load', function (viewer) {\n{$mapOnload}\n});\n";
		$onloadMinifier = new Minify\JS($mapOnload);
		$minifiedOnload = $onloadMinifier->minify();
		$html = $html . "\n{$minifiedOnload}\n";
		unset($onloadMinifier, $minifiedOnload);
	}
	unset($mapOnload);

	$html = $html . <<<HERE
		</script>

HERE;

	if (isset($_GET['getHtml']) && $_GET['getHtml'] == 'y') {
		$html = $html . <<<HERE
	</body>
</html>
HERE;
		if (isset($_GET['download']) && $_GET['download'] == 'y') {
			header('Content-Type: application/octet-stream');
			header("Content-Disposition: attachment;filename=$mapId.html");
		}
		echo "$html";
		fastcgi_finish_request();
	} else {
		fastcgi_finish_request();
		if ($map['searchengineindexable'] == "t") {
			$mapTitle = trim($map['title'], " \t\n\r\0\x0B\"");
			$mapAbstract = trim($map['abstract'], " \t\n\r\0\x0B\"");
			$mapUrl = trim($map['url'], " \t\n\r\0\x0B\"");
			$tmpMapKeywords = array_unique(array_filter(array_column($layersMeta, 'keywords')));
			$mapKeywords = array();
			foreach ($tmpMapKeywords as $layerKeywords) {
				$mapKeywords = array_merge($mapKeywords, explode(',', $layerKeywords));
			}
			$mapKeywords = array_unique($mapKeywords);
			$mapKeywords = implode(',', $mapKeywords);

			$structuredData = array(
				'@context' => 'https://schema.org',
				'@type' => 'WebPage',
				'name' => $mapTitle,
				'description' => $mapAbstract,
				'url' => $mapUrl,
				'keywords' => $mapKeywords,
				'about' => array()
			);
			foreach ($layersMeta as $lmeta) {
				$layerStructuredData = array('@type' => 'Thing', 'name' => $lmeta['title']);
				if (isset($lmeta['abstract']) && !empty($lmeta['abstract']) && $lmeta['abstract'] !== 'null') {
					$layerStructuredData['description'] = $lmeta['abstract'];
				}
				if (isset($lmeta['keywords']) && !empty($lmeta['keywords']) && $lmeta['keywords'] !== 'null') {
					$layerStructuredData['keywords'] = $lmeta['keywords'];
				}
				$structuredData['about'][] = $layerStructuredData;
			}

			require("./constants/searchEngineMeta.php");
			$structuredData['geo'] = array(
				'@type' => 'GeoCoordinates',
				'latitude' => $geoLatitude + 0,
				'longitude' => $geoLongitude + 0
			);
			$structuredData['contentLocation'] = array(
				'@type' => 'Place',
				'name' => $contentLocationName,
				'address' => array(
					'@type' => 'PostalAddress',
					'addressLocality' => $contentLocationAddressLocality,
					'addressCountry' => $contentLocationAddressCountry
				)
			);
			$structuredData['publisher'] = array(
				'@type' => 'Organization',
				'name' => $publisherName,
				'url' => $publisherUrl
			);

			$structuredDataJson = json_encode($structuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
			$html = $html . <<<HERE
		<script type="application/ld+json">
{$structuredDataJson}
		</script>

HERE;
			$structuredDataJson = json_format($structuredDataJson);
			$structuredDataFile = "$configDir/structured-data$mapNumber.json";
			file_put_contents($structuredDataFile, $structuredDataJson);

			$sitemapStr = <<<HERE
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url>
    <loc>{$mapUrl}</loc>
    <changefreq>weekly</changefreq>
    <priority>1.0</priority>
  </url>
</urlset>
HERE;
			$sitemapFile = "$configDir/sitemap.xml";
			file_put_contents($sitemapFile, $sitemapStr);
		}

		$html = $html . <<<HERE
	</body>
</html>
HERE;

		$filepathWithoutSuffix = "$configDir/index$mapNumber";
		publishMapFiles($filepathWithoutSuffix, $html, $jsonPretty, $mapId);
		unset($filepathWithoutSuffix, $html, $jsonPretty);
		markMapUnchanged($dbh, $mapId);

		$restrictedLayers = array();
		foreach ($layers as $layer) {
			if ($layer['type'] !== 'GROUP') {
				$layerServiceId = array_column_search($layer['source'], 'source_id', $sources)['service'];
				$layerServiceRestricted = array_column_search($layerServiceId, 'service_id', $services)['restricted'];
				if ($layerServiceRestricted == 't') {
					$restrictedLayers[] = array(
						'name' => explode('#', $layer['layer_id'])[0],
						'authorized_users' => $layer['adusers'],
						'authorized_groups' => $layer['adgroups']
					);
				}
			}
		}
		array_walk($restrictedLayers, function (&$restrictedLayer) {
			$restrictedLayer['authorized_users'] = pgArrayToPhp(str_replace('"', '', (strtolower($restrictedLayer['authorized_users']))));
			$restrictedLayer['authorized_groups'] = pgArrayToPhp(str_replace('"', '', (strtolower($restrictedLayer['authorized_groups']))));
		});
		defineFileConstant('RESTRICTEDLAYERS', $restrictedLayers);
	}
}

pg_close($dbh);