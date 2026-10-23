<?php

// Takes the request body (array), the requested layer names (array), the ids of all layers in the map (array) and the job directory (string).
// The body can contain drawn layers: J (GeoJSON per layer, separated by @) and RN (the layer names, separated by commas).
// Writes the drawn layers that were requested and belong to the map to the job directory as ritlager_<name>.json.
// Returns true if at least one file was written.
function writeExportDrawings(array $body, array $layerNames, array $allLayerIds, string $directory): bool
{
    if (!isset($body['J'], $body['RN']) || !is_string($body['J']) || !is_string($body['RN']) || $body['J'] === '') {
        return false;
    }
    $geoJson = explode('@', $body['J']);
    $names = explode(',', $body['RN']);
    if (count($names) !== count($geoJson)) {
        error_log('Drawing JSON layer names do not match the provided values.');
        return false;
    }
    $drawings = array_combine($names, $geoJson);
    $mapLayerNames = array();
    foreach ($allLayerIds as $layerId) {
        $mapLayerNames[] = explode('#', $layerId, 2)[0];
    }
    $written = false;
    foreach ($layerNames as $layerName) {
        if (!in_array($layerName, $mapLayerNames, true) || !isset($drawings[$layerName])) {
            continue;
        }
        if (file_put_contents($directory.'/ritlager_'.$layerName.'.json', $drawings[$layerName]) === false) {
            error_log('Unable to write drawing JSON export file.');
        } else {
            $written = true;
        }
    }
    return $written;
}
