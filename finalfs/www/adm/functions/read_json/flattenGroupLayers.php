<?php

function flattenGroupLayers(array $jsonLayers): array
{
    $allLayers=array();
    foreach ($jsonLayers as $jsonLayer)
    {
        if (!is_array($jsonLayer))
        {
            continue;
        }
        if (($jsonLayer['type'] ?? null) === 'GROUP' && !empty($jsonLayer['layers']) && is_array($jsonLayer['layers']))
        {
            foreach ($jsonLayer['layers'] as $groupLayerLayer)
            {
                if (is_array($groupLayerLayer))
                {
                    $groupLayerLayer['group']='groupLayer';
                    $allLayers[]=$groupLayerLayer;
                }
            }
        }
        $allLayers[]=$jsonLayer;
    }
    return $allLayers;
}