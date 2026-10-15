<?php

function buildLayerInsert(string $configSchema, array $layer, string $importId, array $styleResult): array
{
    $layerQueryable=!empty($layer['queryable']) ? var_export($layer['queryable'], true) : 'true';
    $layerVisible=array_key_exists('visible', $layer) ? var_export($layer['visible'], true) : 'true';
    $layerOpacity=$layer['opacity'] ?? 1;
    if (($layer['type'] ?? null) === 'GROUP')
    {
        $layerIds=array();
        foreach (($layer['layers'] ?? array()) as $layerLayer)
        {
            if (isset($layerLayer['name']))
            {
                $layerIds[]=$layerLayer['name']."#$importId";
            }
        }
        $layerLayers=toPgArrayLiteral($layerIds);
        $layerSource=null;
    }
    else
    {
        $layerLayers=toPgArrayLiteral(array());
        $layerSource=isset($layer['source']) ? $layer['source']."#$importId" : null;
    }
    $layerAbstract=array_key_exists('abstract', $layer) ? $layer['abstract'] : null;
    if ($layerAbstract !== null)
    {
        $layerAbstract=preg_replace("|(<br>)*<form action='[^']*' method='post' target='_blank'><button type='submit' name='layerId' value='[^']*' style='[^']*'>Administrera</button></form>|i", '', $layerAbstract);
        $layerAbstract=str_replace(array("\r\n", "\r", "\n"), "<br />", $layerAbstract);
    }
    $layerParams=array(
        ($layer['name'] ?? '').$importId,
        $layer['title'] ?? null,
        $layer['format'] ?? null,
        $layer['type'] ?? null,
        array_key_exists('attributes', $layer) ? json_encode($layer['attributes'], JSON_PRETTY_PRINT) : null,
        $layerAbstract,
        $layerQueryable,
        $layer['featureinfoLayer'] ?? null,
        $layerOpacity,
        $layerVisible,
        $layerSource,
        $styleResult['config'],
        $styleResult['icon'] !== '' ? 'true' : 'false',
        $styleResult['icon'],
        $styleResult['filter'],
        $styleResult['extendedIcon'],
        $layerLayers,
        $layer['layerType'] ?? null,
        $styleResult['clusterStyle'],
        $layer['attribution'] ?? null
    );
    $columns='layer_id, title, format, type, attributes, abstract, queryable, featureinfolayer, opacity, visible, source, style_config, show_icon, icon, style_filter, icon_extended, layers, layertype, clusterstyle, attribution';
    if (!empty($layer['maxScale']))
    {
        $columns.=', maxscale';
        $layerParams[]=$layer['maxScale'];
    }
    if (!empty($layer['minScale']))
    {
        $columns.=', minscale';
        $layerParams[]=$layer['minScale'];
    }
    if (!empty($layer['clusterOptions']) && $layer['clusterOptions'] !== '[]')
    {
        $columns.=', clusteroptions';
        $layerParams[]=json_encode($layer['clusterOptions'], JSON_PRETTY_PRINT);
    }
    $placeholders=array();
    foreach ($layerParams as $index => $unused)
    {
        $placeholders[]='$'.($index+1);
    }
    $sql="INSERT INTO {$configSchema}.layers($columns) VALUES (".implode(',', $placeholders).")";
    return array($sql, $layerParams);
}