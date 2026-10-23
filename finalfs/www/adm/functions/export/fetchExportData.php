<?php

// Takes an export format (exportFormats entry), the targets (see exportTargets), the validated request and the job directory (string).
// Fetches every target from the map server into the job directory.
// Returns the image formats (for example image/png) of the fetched targets.
function fetchExportData(array $format, array $targets, array $request, string $directory): array
{
    $imageFormats = array();
    foreach ($targets as $target) {
        $file = $directory.'/'.$target['fileName'];
        switch ($format['fetch']) {
            case 'dxf':
                fetchDxf($target['url'], $target['layer'], $request['bbox'], $request['srs'], $file.'.dxf');
                break;
            case 'shape':
                fetchShape($target['url'], $target['layer'], $request['bbox'], $request['srs'], $file.'.zip');
                break;
            case 'geojson':
                fetchGeojson($target['url'], $target['layer'], $request['bbox'], $request['srs'], $file.'.geojson');
                break;
            case 'wms':
                fetchWms($target['url'], $target['layer'], $target['format'], $request['bbox'], $request['srs'], $request['width'], $request['height'], $file);
                break;
            default:
                error_log('Unknown fetch type in exportFormats.php: '.json_encode($format['fetch']));
        }
        $imageFormats[] = $target['format'];
    }
    return array_values(array_unique($imageFormats));
}
