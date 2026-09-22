<?php

	function addStylesToJson(array &$context)
	{
		$mapStyles =& $context['mapStyles'];
		$stylesJson = array();
		foreach ($mapStyles as $style)
		{
			if (!empty($style['style_config']) && $style['style_config'] != '[]' && $style['style_config'] != '{}' && $style['style_config'] != '""' && $style['style_config'] != 'null')
			{
				$stylesJson[$style['layer_id']] = json_decode($style['style_config'], true);
			}
			else
			{
				$styleJson = array();
				if (!empty($style['label']))
				{
					$styleJson['label'] = $style['label'];
				}
				if (!empty($style['icon']))
				{
					if (substr($style['layer_id'], -strlen('-bg'))==='-bg' || (empty($style['icon_extended']) && $style['type'] != 'WFS'))
					{
						$styleJson['image'] = array('src' => $style['icon']);
					}
					else
					{
						$styleJson['icon'] = array('src' => $style['icon']);
					}
				}
				if (!empty($style['style_filter']))
				{
					$styleJson['filter'] = $style['style_filter'];
				}
				$styleJsonLayers = array($styleJson);
				if (substr($style['layer_id'], -strlen('-bg'))!=='-bg' && !empty($style['icon_extended']))
				{
					$styleJsonLayers[] = array('icon' => array('src' => $style['icon_extended']), 'extendedLegend' => true);
				}
				$stylesJson[$style['layer_id']] = array($styleJsonLayers);
			}
		}
		return $stylesJson;
	}