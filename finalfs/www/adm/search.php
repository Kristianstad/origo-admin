<?php
/*
search.php
 ├─ includeDirectory("./functions/common")
 ├─ includeDirectory("./functions/search")
 ├─ tolkar $_GET: första parametern vars namn är en sökmodell (searchmodels.searchmodel_id) väljer modell, värdet är sökordet; unset($_GET)
 ├─ searchLoadConfig()                       [search] → sökmodellerna ur konfigurationscachen (se nedan); bygger den om den saknas
 ├─ okänd modell → 400 + JSON-fel (ingen reservmodell)
 ├─ för varje söktabell i modellen (i modellens ordning), beroende på söktabellens ttl och resultatcachens ålder:
 │    ├─ live-sökning  → searchLiveRows()    [search] → searchSql() mot söktabellens databas (databases.connectionstring)
 │    └─ resultatcache → searchCachedRows()  [search] → läser söktabellens cachefil, poängsätter och sorterar träffarna
 ├─ searchTrimToLimit()                      [search] → proportionell kapning till modellens limit
 ├─ skriver JSON-svaret + headerna X-Search-Source / X-Search-Cache-Age-Seconds
 └─ efter svaret (searchAfterResponse):
      ├─ searchRebuildCaches()               [search] → bygger om gamla/saknade resultatcachar (ett bygge i taget, flock)
      └─ searchConfigRefresh()               [common] → bygger om en gammal konfigurationscache (manage.php markerar den gammal vid ändringar)

Konfigurationscachen (functions/common/searchConfig*.php) ligger i sys_get_temp_dir()/origo-search/ och byggs från databasens tabeller
searchmodels, searchtables och databases. Den gamla cachen används tills en ny är helt skriven (atomiskt byte). Anropas via
www/search/search-loader.php.
*/

require_once("./functions/includeDirectory.php");

includeDirectory("./functions/common");
includeDirectory("./functions/search");

header('Content-Type: application/json; charset=utf-8');

$params = $_GET;
unset($_GET);

$configCache = searchLoadConfig();
$selected = searchSelectModel($configCache['config'], $params);
if ($selected === null) {
    http_response_code(400);
    echo json_encode(array('error' => 'Unknown search model'));
    exit;
}
list($model, $query) = $selected;

$allResults = array();
$connections = array();
$needsRefresh = false;
$usedLive = false;
$usedCache = false;
$minCacheAge = null;
foreach ($model['tables'] as $tableConfig) {
    $cacheStatus = searchShouldRefreshCache($tableConfig['tableKey'], $tableConfig['ttl'], $tableConfig['maxCacheAgeFactor']);
    if ($tableConfig['ttl'] === 0 || $cacheStatus === 2) {
        $usedLive = true;
        if ($cacheStatus === 2) {
            @unlink(searchCacheFile($tableConfig['tableKey']));
        }
        $rows = searchLiveRows($tableConfig, $query, $connections);
    } else {
        $usedCache = true;
        $rows = searchCachedRows($tableConfig, $query, $needsRefresh);
        $age = searchCacheAge($tableConfig['tableKey']);
        if ($age !== null && ($minCacheAge === null || $age < $minCacheAge)) {
            $minCacheAge = $age;
        }
    }
    if ($rows !== null) {
        $allResults = array_merge($allResults, $rows);
    }
    if ($cacheStatus > 0) {
        $needsRefresh = true;
    }
}
$allResults = searchTrimToLimit($allResults, $model['limit']);

foreach ($connections as $connection) {
    if ($connection instanceof \PgSql\Connection) {
        pg_close($connection);
    }
}

$source = 'none';
if ($usedLive && $usedCache) {
    $source = 'mixed';
} elseif ($usedLive) {
    $source = 'live';
} elseif ($usedCache) {
    $source = 'cache';
}
header('X-Search-Source: ' . $source);
header('X-Search-Cache-Age-Seconds: ' . ($minCacheAge ?? 'null'));

echo json_encode($allResults);

if ($needsRefresh || $configCache['stale']) {
    $tableConfigs = $model['tables'];
    $configStale = $configCache['stale'];
    searchAfterResponse(function () use ($needsRefresh, $tableConfigs, $configStale) {
        // The configuration refresh goes last: dbh() ends the script if the configuration database is unavailable
        if ($needsRefresh) {
            searchRebuildCaches($tableConfigs);
        }
        if ($configStale) {
            searchConfigRefresh(null, 0, true);
        }
    });
}
