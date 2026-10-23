<?php

// Takes the request parameters (array), the export settings (exportConfig) and the export formats (exportFormats).
// Returns the validated request, or null (after logging the reason) if it cannot be handled.
function exportRequest(array $query, array $config, array $formats): ?array
{
    $format = is_string($query['F'] ?? null) ? $query['F'] : '';
    if (!isset($formats[$format])) {
        error_log('Unknown export format: '.substr($format, 0, 50));
        return null;
    }
    $isPolygon = !isset($query['B']);
    $coordinates = $isPolygon ? ($query['BP'] ?? null) : $query['B'];
    if (!is_string($coordinates) || (isset($query['BP']) && !is_string($query['BP']))) {
        error_log('Export request is missing a valid B or BP parameter.');
        return null;
    }
    $box = exportBoundingBox($coordinates, $isPolygon, $config['longestSide']);
    if ($box === null) {
        error_log('Export request has invalid coordinates in B or BP.');
        return null;
    }
    foreach (array('map', 'layers', 'C', 'V') as $name) {
        if (isset($query[$name]) && !is_string($query[$name])) {
            error_log('Export request parameter '.$name.' must be a string.');
            return null;
        }
    }
    return array(
        'format' => $format,
        'mapId' => $query['map'] ?? '',
        'layerNames' => explode(';', $query['layers'] ?? ''),
        'srs' => !empty($query['C']) ? $query['C'] : $config['defaultSrs'],
        'bbox' => $box['bbox'],
        'width' => $box['width'],
        'height' => $box['height'],
        'fmeBox' => isset($query['BP']) ? array('BP' => $query['BP']) : array('B' => str_replace(',', ' ', $query['B'])),
        'fileName' => $query['SF'] ?? null,
        'V' => $query['V'] ?? ''
    );
}
