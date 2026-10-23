<?php

// Takes the database handle, the logged in user (array), the request parameters (array), the request body (array),
// the host address that the map server is reached on (string), the FME token (string) and whether to keep the files for debugging (bool).
// Fetches the requested data from the map server into a job directory and asks FME Flow to convert and deliver it.
// The job directory is removed when the script ends. Problems are logged; nothing is returned to the user.
function runExportJob($dbh, array $user, array $query, array $body, string $exportBaseUrl, string $fmeToken, bool $keepFiles): void
{
    require './constants/configSchema.php';
    require './constants/proxyRoot.php';
    require './constants/exportConfig.php';
    require './constants/exportFormats.php';

    $request = exportRequest($query, $exportConfig, $exportFormats);
    if ($request === null) {
        return;
    }
    $format = $exportFormats[$request['format']];

    $tables = array();
    foreach (array('maps', 'groups', 'layers', 'sources', 'services') as $table) {
        $tables[$table] = allFromTable($dbh, $configSchema, $table);
    }
    pg_close($dbh);
    $map = arrayColumnSearch($request['mapId'], 'map_id', $tables['maps']);
    $allLayerIds = allLayerIds($map, $tables['groups']);

    $job = createExportJob($exportConfig, $user['id']);
    $state = array('finished' => false);
    registerExportCleanup($job, $exportConfig['cleanupDelay'], $keepFiles, $state);

    $hasDrawings = $format['drawings'] && writeExportDrawings($body, $request['layerNames'], $allLayerIds, $job['directory']);
    $layerRequests = $format['source'] === 'layers'
        ? exportLayerRequests($request['layerNames'], $allLayerIds, $tables, $exportBaseUrl, $proxyRoot)
        : array();
    $targets = exportTargets($format, $request, $layerRequests, $exportConfig, $exportBaseUrl);
    $imageFormats = fetchExportData($format, $targets, $request, $job['directory']);

    $parameters = exportFmeParameters($format, $request, $job, $imageFormats, $hasDrawings, $user['mail']);
    foreach (array_merge(array_keys($format['inputs']), array('JF')) as $name) {
        if (isset($parameters[$name]) && empty($parameters[$name])) {
            error_log('No local input files found for FME parameter '.$name.'.');
            return;
        }
    }
    $state['finished'] = submitFmeJob($exportConfig, $fmeToken, $format['workspace'], $parameters);
}
