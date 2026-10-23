<?php

// Takes an export format (exportFormats entry), the validated request, the layer requests (see exportLayerRequests),
// the export settings (exportConfig) and the host address that the map server is reached on (string).
// Returns the list of things to fetch from the map server: layer, url, format and the file name (without extension).
function exportTargets(array $format, array $request, array $layerRequests, array $config, string $exportBaseUrl): array
{
    $isImage = $format['fetch'] === 'wms';
    if ($format['source'] === 'baseMap') {
        $baseMap = $config['baseMap'];
        return array(array(
            'layer' => $baseMap['layer'],
            'url' => $exportBaseUrl.$baseMap['path'].'?',
            'format' => $baseMap['imageFormat'],
            'fileName' => $isImage ? $baseMap['fileName'] : exportFileName($request['fileName'], $baseMap['fileName'])
        ));
    }
    $targets = array();
    if ($format['source'] === 'layers') {
        foreach (array_values($layerRequests) as $index => $layerRequest) {
            $targets[] = $layerRequest + array('fileName' => $isImage ? (string) ($index + 1) : $layerRequest['layer']);
        }
    }
    return $targets;
}
