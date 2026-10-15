<?php

function extractLayerStyleConfig(array $layerStyle): array
{
    $result=array(
        'config'=>'[]',
        'icon'=>'',
        'extendedIcon'=>'',
        'filter'=>'',
    );
    $styleItems=isset($layerStyle[0]) && is_array($layerStyle[0]) ? $layerStyle[0] : array();
    if (count($styleItems) > 1)
    {
        $first=$styleItems[0] ?? array();
        $second=$styleItems[1] ?? array();
        if (count($styleItems) === 2 && !empty($first['icon']['src']) && !empty($second['icon']['src']) && (!empty($first['extendedLegend']) || !empty($second['extendedLegend'])))
        {
            if (!empty($first['extendedLegend']))
            {
                $result['extendedIcon']=$first['icon']['src'];
                $result['icon']=$second['icon']['src'];
            }
            else
            {
                $result['extendedIcon']=$second['icon']['src'];
                $result['icon']=$first['icon']['src'];
            }
            $result['filter']=!empty($first['filter']) ? $first['filter'] : ($second['filter'] ?? '');
        }
        else
        {
            $result['config']=json_encode($layerStyle, JSON_PRETTY_PRINT);
        }
        return $result;
    }
    $style=$styleItems[0] ?? array();
    if (!empty($style['icon']['src']))
    {
        if (!empty($style['extendedLegend']))
        {
            $result['extendedIcon']=$style['icon']['src'];
        }
        else
        {
            $result['icon']=$style['icon']['src'];
        }
        $result['filter']=$style['filter'] ?? '';
    }
    elseif (!empty($style['image']['src']))
    {
        $result['icon']=$style['image']['src'];
        $result['filter']=$style['filter'] ?? '';
    }
    else
    {
        $result['config']=json_encode($layerStyle, JSON_PRETTY_PRINT);
    }
    return $result;
}