<?php

// Takes the requested layer names (array), the ids of all layers in the map (array), the tables layers, sources and services (array),
// the host address that the map server is reached on (string) and the proxy root (string).
// Returns one entry (layer, url, format) per exportable layer, in the same order as the request.
// A layer that lists other layers in its "exports" column is replaced by those layers.
function exportLayerRequests(array $layerNames, array $allLayerIds, array $tables, string $exportBaseUrl, string $proxyRoot): array
{
    $layerIds = array();
    foreach ($layerNames as $layerName) {
        $layerId = null;
        foreach ($allLayerIds as $candidateLayerId) {
            if (explode('#', $candidateLayerId, 2)[0] === $layerName) {
                $layerId = $candidateLayerId;
                break;
            }
        }
        if ($layerId === null) {
            continue;
        }
        $layer = arrayColumnSearch($layerId, 'layer_id', $tables['layers']);
        if (($layer['exportable'] ?? null) !== 't') {
            continue;
        }
        $layerExports = pgArrayToPhp($layer['exports'] ?? null);
        $layerIds = array_merge($layerIds, $layerExports ?: array($layerId));
        $layerIds = array_values(array_unique($layerIds));
    }
    $requests = array();
    foreach ($layerIds as $layerId) {
        $layer = arrayColumnSearch($layerId, 'layer_id', $tables['layers']);
        $sourceId = $layer['source'] ?? '';
        $source = arrayColumnSearch($sourceId, 'source_id', $tables['sources']);
        $service = arrayColumnSearch($source['service'] ?? '', 'service_id', $tables['services']);
        $baseUrl = str_ireplace('../', rtrim($proxyRoot, '/').'/', $service['base_url'] ?? '');
        $requests[] = array(
            'layer' => trim(explode('#', $layerId, 2)[0]),
            'url' => $exportBaseUrl.$baseUrl.'/'.trim(explode('#', $sourceId, 2)[0]).'?',
            'format' => trim(explode(';', $layer['format'] ?? '', 2)[0])
        );
    }
    return $requests;
}
