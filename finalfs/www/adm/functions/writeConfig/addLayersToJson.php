<?php
/*
addLayersToJson($mapLayersList, &$layersMeta, $groupLayer=false, &$context)
 ├─ för varje lager i listan:
 │    ├─ slår upp lagrets fullständiga data (array_column_search)
 │    ├─ bygger $layersMeta[] (används för SEO-strukturerad data i writeConfig.php)
 │    ├─ sätter en rad standardvärden (type, style_layer, queryable, visible, legend)
 │    ├─ bygger lagrets grundläggande JSON-fält (name, title, type, group, format, etc.)
 │    ├─ typ-specifik logik: GEOJSON / WMS / WFS (olika fält beroende på typ)
 │    ├─ bygger ihop en "abstract"-beskrivning (HTML!) av flera källor:
 │    │    tabellbeskrivningar, kontakt, källa/ursprung, uppdateringsdatum,
 │    │    en inbäddad "Administrera"-knapp-formulär
 │    ├─ OM lagret är en GRUPP → rekursivt anrop till addLayersToJson() för dess undergrupper
 │    ├─ bygger legend-/ikon-URL:er mot bakomliggande WMS-tjänst (GetLegendGraphic)
 │    │    med olika DPI/symbolstorlekar beroende på om det är "kompakt" eller
 │    │    "utökad" legend, samt hanterar "tematisk stil" (thematicStyling)
 │    └─ samlar ihop unika källor ($mapSources) och stilar ($mapStyles) som
 │         används av hela kartan (konsumeras sedan av addSourcesToJson/addStylesToJson)
 └─ returnerar lager-arrayen; writeConfig.php serialiserar därefter
	 lager, källor och stilar som separata JSON-block
*/

	function addLayersToJson($mapLayersList, &$layersMeta, $groupLayer=false, array &$context=array())
	{
		$map =& $context['map'];
		$layers =& $context['layers'];
		$mapStyles =& $context['mapStyles'];
		$mapSources =& $context['mapSources'];
		$sources =& $context['sources'];
		$services =& $context['services'];
		$contacts =& $context['contacts'];
		$origins =& $context['origins'];
		$tables =& $context['tables'];
		$layersJson = array();
		if (!isset($mapSources))
		{
			$mapSources = array();
		}
		if (!isset($mapStyleLayers))
		{
			$mapStyleLayers = array();
		}
		if (!isset($mapStyles))
		{
			$mapStyles = array();
		}
		foreach ($mapLayersList as $listItem)
		{
			$listItem=explode('>', $listItem);
			if (isset($listItem[1]))
			{
				$group=explode('#', $listItem[0])[0];
				$layerId=$listItem[1];
			}
			else
			{
				$group='root';
				$layerId=$listItem[0];
			}
			$layer = array_column_search($layerId, 'layer_id', $layers);
			$layersMeta[]=array('title'=>trim(json_encode(trim($layer['title'], " \t\n\r\0\x0B\""), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), '"'), 'abstract'=>trim(json_encode(trim($layer['abstract'], " \t\n\r\0\x0B\""), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), '"'), 'keywords'=>trim(json_encode(str_replace(array("'", "\""), '', trim($layer['keywords'], " \t\n\r\0\x0B\"{}")), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), '"'));
			if ($layer['type'] !== 'GROUP')
			{
				$source = array_column_search($layer['source'], 'source_id', $sources);
				if ($layer['type'] == 'WFS')
				{
					$layer['source'] = $layer['source'].'@wfs';
				}
				$service = array_column_search($source['service'], 'service_id', $services);
				if ($layer['attributes'] == '[]' || $layer['attributes'] == '{}' || $layer['attributes'] == '""' || $layer['attributes'] == 'null')
				{
					$layer['attributes'] = '';
				}
			}
			if ($layer['style_config'] == '[]' || $layer['style_config'] == '{}' || $layer['style_config'] == '""' || $layer['style_config'] == 'null')
			{
				$layer['style_config'] = '';
			}
			// Set default values <start>
			if (empty($layer['type']))
			{
				$layer['type'] = 'WMS';
			}
			if (empty($layer['style_layer']) && ($layer['type'] == 'WMS' || $layer['type'] == 'GROUP' || (!empty($layer['icon']) || !empty($layer['style_config']))))
			{
				$layer['style_layer'] = $layer['layer_id'];
			}
			if (empty($layer['queryable']) && $layer['type'] !== 'GROUP')
			{
				$layer['queryable'] = 't';
			}
			if (empty($layer['visible']))
			{
				$layer['visible'] = 'f';
			}
			if (empty($layer['legend']) && $layer['type'] !== 'GROUP')
			{
				$layer['legend'] = 'f';
			}
			// Set default values </end>
			$layerName = trim(explode('#', $layer['layer_id'], 2)[0]);
			$layerJson = array(
				'name' => $layerName,
				'title' => $layer['title'],
				'type' => $layer['type']
			);
			if (!$groupLayer)
			{
				$layerJson['group'] = $group;
			}
			if (!empty($layer['format']) && $layer['format'] !== 'image/png')
			{
				$layerJson['format'] = $layer['format'];
			}
			if (!empty($layer['attribution']))
			{
				$layerJson['attribution'] = $layer['attribution'];
			}
			if (!empty($layer['style_layer']))
			{
				$styleLayerName = trim(explode('#', $layer['style_layer'], 2)[0]);
				$styleLayer = array_column_search($layer['style_layer'], 'layer_id', $layers);
				if ($group == 'background')
				{
					$layer['style_layer'] = $layer['style_layer'].'-bg';
				}
				if ($styleLayer['show_icon'] != 'f' || $styleLayer['show_iconext'] == 't' || $layer['type'] == 'GROUP' || $layer['type'] == 'GEOJSON')
				{
					$layerJson['style'] = $layer['style_layer'];
				}
			}
			if ($layer['queryable'] == 'f')
			{
				$layerJson['queryable'] = false;
			}
			if ($layer['visible'] == 't')
			{
				$layerJson['visible'] = true;
			}
			elseif ($layer['visible'] == 'f')
			{
				$layerJson['visible'] = false;
			}
			if ($layer['swiper'] == 't')
			{
				$layerJson['isSwiperLayer'] = true;
			}
			elseif ($layer['swiper'] == 'under')
			{
				$layerJson['isUnderSwiper'] = true;
			}
			if (empty($group) && $layer['legend'] == 't')
			{
				$layerJson['legend'] = true;
			}
			if (isset($layer['opacity']) && $layer['opacity'] < 1)
			{
				$layerJson['opacity'] = $layer['opacity'] + 0;
			}
			if ($layer['type'] == 'GEOJSON')
			{
				$layerJson['source'] = $source['file'];
				$layerJson['headers'] = array('Accept' => 'application/geo+json');
			}
			else
			{
				if ($layer['type'] != 'OSM')
				{
					$layerJson['source'] = $layer['source'];
					if ($layer['type'] == 'WMS')
					{
						if (!empty($layer['gutter']))
						{
							$layerJson['gutter'] = $layer['gutter'] + 0;
						}
						if ($layer['tiled'] == 'f')
						{
							$layerJson['renderMode'] = 'image';
						}
						if (!empty($layer['featureinfolayer']))
						{
							$layerJson['featureinfoLayer'] = $layer['featureinfolayer'];
						}
					}
					elseif ($layer['type'] == 'WFS')
					{
						$layerJson['projection'] = 'EPSG:4326';
						if ($layer['editable'] == 't')
						{
							$layerJson['editable'] = true;
							if (!empty($layer['allowededitoperations']))
							{
								$allowededitoperations=explode(',', str_replace(["\r\n", "\r", "\n", ' ', '"', '[', ']'], '', $layer['allowededitoperations']));
								$layerJson['allowedEditOperations'] = $allowededitoperations;
								unset($allowededitoperations);
							}
							if (!empty($layer['geometryname']))
							{
								$layerJson['geometryName'] = $layer['geometryname'];
							}
							if (!empty($layer['geometrytype']))
							{
								$layerJson['geometryType'] = $layer['geometrytype'];
							}
							if (!empty($layer['featurelistattributes']))
							{
								$featurelistattributes=explode(',', str_replace(["\r\n", "\r", "\n", ' ', '"', '[', ']'], '', $layer['featurelistattributes']));
								$layerJson['featureListAttributes'] = $featurelistattributes;
								unset($featurelistattributes);
							}
							if (!empty($layer['drawtools']))
							{
								$layerJson['drawTools'] = json_decode($layer['drawtools'], true);
							}
						}
					}
				}
			}
			if (empty($layer['abstract']) && empty($layer['resources']))
			{
				$beskr='';
				foreach (pgArrayToPhp($layer['tables']) as $tableId)
				{
					$table = array_column_search($tableId, 'table_id', $tables);
					if (!empty($table['abstract']))
					{
						if (empty($beskr))
						{
							$beskr=$table['abstract'];
						}
						else
						{
							$beskr=$beskr.' '.$table['abstract'];
						}
					}
				}
			}
			else
			{
				$beskr=$layer['abstract'];
			}
			if (!empty($layer['web']))
			{
				$beskr=$beskr." <a href='".$layer['web']."' target='_blank'>Mer info.</a>";
			}
			$abstract = '';
			if ($layer['show_meta'] != 'f')
			{
				if ($map['show_meta'] == 't')
				{
					$layerContact = array_column_search($layer['contact'], 'contact_id', $contacts);
					if (!empty($layerContact['web']))
					{
						$contactStr="<a href='".$layerContact['web']."' target='_blank'>".$layerContact['name']."</a>";
					}
					elseif (!empty($layerContact['email']))
					{
						$contactStr="<a href='mailto:".$layerContact['email']."'>".$layerContact['name']."</a>";
					}
					elseif (!empty($layerContact['name']))
					{
						$contactStr=$layerContact['name'];
					}
					else
					{
						$contactStr='';
					}
					$layerOrigin = array_column_search($layer['origin'], 'origin_id', $origins);
					if (!empty($layerOrigin['web']))
					{
						$originStr="<a href='".$layerOrigin['web']."' target='_blank'>".$layerOrigin['name']."</a>";
					}
					elseif (!empty($layerOrigin['email']))
					{
						$originStr="<a href='mailto:".$layerOrigin['email']."'>".$layerOrigin['name']."</a>";
					}
					elseif (!empty($layerOrigin['name']))
					{
						$originStr=$layerOrigin['name'];
					}
					else
					{
						$originStr='';
					}
					$abstract = '<b>Beskrivning: </b>'.$beskr.'<br><b>Resurser: </b>';
					$layerTables=trim($layer['tables'], '{}');
					if (empty($layer['resources']))
					{
						$abstract .= str_replace(',', ', ', $layerTables);
					}
					else
					{
						$abstract .= $layer['resources'];
					}
					$abstract .= '<br><b>Kontakt: </b>'.$contactStr.'<br><b>Källa: </b>'.$originStr.'<br>';
					if (!empty($layer['updated']))
					{
						$abstract .= '<b>Uppdaterad: </b>'.$layer['updated'];
					}
					elseif (!empty($layerTables))
					{
						$abstract .= "<b>Uppdaterad: </b><iframe id='".$layer['layer_id']."uppd' src='' style='display:none;width:6em;height:1em;padding-top:3px'></iframe><button onclick='var iframe=document.getElementById(\\\"".$layer['layer_id']."uppd\\\");iframe.src=\\\"/php/updated/updated-loader.php?table=".$layerTables."\\\";iframe.style.display=null;this.style.display=\\\"none\\\";'>Visa</button>";
					}
					$abstract .= '<br>';
					unset($layerTables);
				}
				else
				{
					if (!empty($beskr))
					{
						$abstract = $beskr.'<br>';
					}
				}
			}
			$adminForm="<form action='".parse_url($_SERVER["HTTP_REFERER"], PHP_URL_PATH)."?view=Origo' method='post' target='_blank'><button type='submit' name='layerId' value='".$layer['layer_id']."' style='float:right; color:blue'>Administrera</button></form>";
			$layerJson['abstract'] = $abstract.$adminForm;
			if (!empty($layer['attributes']) && $layer['type'] !== 'GROUP')
			{
				$layerJson['attributes'] = json_decode($layer['attributes'], true);
			}
			if (!empty($layer['maxscale']))
			{
				$layerJson['maxScale'] = $layer['maxscale'] + 0;
			}
			if (!empty($layer['minscale']))
			{
				$layerJson['minScale'] = $layer['minscale'] + 0;
			}
			if (!empty($layer['layertype']) && $layer['layertype'] !== 'vector')
			{
				$layerJson['layerType'] = $layer['layertype'];
				if ($layer['layertype'] == 'cluster')
				{
					$layerJson['clusterStyle'] = $layer['style_layer'].'-cluster';
					if (!empty($layer['clusteroptions']) && $layer['clusteroptions'] !== '{}')
					{
						$layerJson['clusterOptions'] = json_decode($layer['clusteroptions'], true);
					}
				}
			}
			if ($layer['type'] === 'GROUP')
			{
				$layerJson['layers'] = addLayersToJson(pgArrayToPhp($layer['layers']), $layersMeta, true, $context);
			}
			if (!empty($layer['source']) && !in_array($layer['source'], $mapSources))
			{
				$mapSources[] = $layer['source'];
			}
			if (!empty($layer['style_layer']))
			{
				if ($styleLayer['style_config'] == '[]' || $styleLayer['style_config'] == '{}' || $styleLayer['style_config'] == '""' || $styleLayer['style_config'] == 'null')
				{
					$styleLayer['style_config'] = '';
				}
				if (empty($styleLayer['style_config']))
				{
					if ($styleLayer['type'] !== 'GROUP')
					{
						$styleSource = array_column_search($styleLayer['source'], 'source_id', $sources);
						$styleService = array_column_search($styleSource['service'], 'service_id', $services);
						if (empty($styleLayer['type']))
						{
							$styleLayer['type'] = 'WMS';
						}
						if (strtoupper($styleLayer['type']) == 'WMS')
						{
							$styleSourceProject = trim(explode('#', $styleSource['source_id'], 2)[0]);
							if (strpos($styleService['base_url'], '?') === false)
							{
								$paramSeparator='?';
							}
							else
							{
								$paramSeparator='&';
							}
							if (isset($styleLayer['show_icon']) && $styleLayer['show_icon'] == 't' && empty($styleLayer['icon']))
							{
								$styleLayer['icon'] = $styleService['base_url'].'/'.$styleSourceProject.$paramSeparator.'SERVICE=WMS&REQUEST=GetLegendGraphic&DPI=96&FORMAT=image/png&ICONLABELSPACE=0&LAYERTITLE=TRUE&RULELABEL=FALSE&ITEMFONTSIZE=1&TRANSPARENT=TRUE&BOXSPACE=1.8&SYMBOLWIDTH=6&SYMBOLHEIGHT=6&LAYERSPACE=5&LAYERTITLESPACE=-6&LAYERFONTSIZE=0.5&LAYERFONTCOLOR=%23FFFFFF&LAYERS='.$styleLayerName;
							}
							if (empty($styleLayer['icon_extended']) && $group != 'background')
							{
								if ($styleLayer['show_icon'] == 'f')
								{
									$styleLayer['icon_extended'] = $styleService['base_url'].'/'.$styleSourceProject.$paramSeparator.'SERVICE=WMS&REQUEST=GetLegendGraphic&DPI=72&FORMAT=image/png&ICONLABELSPACE=3&LAYERTITLE=TRUE&RULELABEL=TRUE&TRANSPARENT=TRUE&BOXSPACE=1&SYMBOLWIDTH=6&SYMBOLHEIGHT=4&SYMBOLSPACE=3&LAYERSPACE=5&LAYERTITLESPACE=-5.3&LAYERFONTSIZE=0.5&LAYERFONTCOLOR=%23FFFFFF&LAYERS='.$styleLayerName;
								}
								else
								{
									$styleLayer['icon_extended'] = $styleService['base_url'].'/'.$styleSourceProject.$paramSeparator.'SERVICE=WMS&REQUEST=GetLegendGraphic&DPI=96&FORMAT=image/png&ICONLABELSPACE=3&LAYERTITLE=TRUE&RULELABEL=TRUE&TRANSPARENT=TRUE&BOXSPACE=1&SYMBOLWIDTH=6&SYMBOLHEIGHT=4&SYMBOLSPACE=3&LAYERSPACE=5&LAYERTITLESPACE=-5.3&LAYERFONTSIZE=0.5&LAYERFONTCOLOR=%23FFFFFF&LAYERS='.$styleLayerName;
								}
							}
						}
						elseif (strtoupper($styleLayer['type']) == 'WFS')
						{
							$styleLayer['label'] = $styleLayer['title'];
						}
						unset($styleSource, $styleService, $styleSourceProject, $paramSeparator);
					}
					require("./constants/iconTtl.php");
					if (isset($service) && isset($service['restricted']) && $service['restricted'] == 't')
					{
						$restricted = true;
					}
					else
					{
						$restricted = false;
					}
					if (isset($styleLayer['show_icon']) && $styleLayer['show_icon'] == 't' && !empty($styleLayer['icon']))
					{
						$params = [];
						if ($iconTtl != '-1')
						{
							$params[] = 'ttl=' . $iconTtl;
						}
						if ($restricted)
						{
							$params[] = 'restricted=t';
						}
						if (!empty($params))
						{
							$separator = (strpos($styleLayer['icon'], '?') === false) ? '?' : '&';
							$styleLayer['icon'] .= $separator . implode('&', $params);
						}
					}
					else
					{
						unset($styleLayer['icon']);
						if (($styleLayer['show_iconext'] != 't' || empty($styleLayer['icon_extended'])) && $layer['type'] != 'GROUP')
						{
							unset($styleLayer['icon_extended']);
							$layerJson['hasThemeLegend'] = true;
							if ($styleLayer['thematicstyling'] == 't')
							{
								$layerJson['thematicStyling'] = true;
								$legendParams = [
									'FORMAT'         => 'image/png',
									'LAYERTITLE'     => true,
									'SHOWRULEDETAILS'=> true,
									'TRANSPARENT'    => true
								];
								if ($iconTtl !== '-1') {
									$legendParams['ttl'] = $iconTtl;
								}
								$layerJson['legendParams'] = $legendParams;
							}
							else
							{
								$legendParams = [
									'FORMAT'          => 'image/png',
									'LAYERTITLE'      => true,
									'ICONLABELSPACE'  => 3,
									'RULELABEL'       => true,
									'TRANSPARENT'     => true,
									'BOXSPACE'        => 3,
									'SYMBOLWIDTH'     => 6,
									'SYMBOLHEIGHT'    => 4,
									'SYMBOLSPACE'     => 2,
									'LAYERSPACE'      => 5,
									'LAYERTITLESPACE' => -7,
									'LAYERFONTSIZE'   => 0.5,
									'LAYERFONTCOLOR'  => '#FFFFFF',
									'ITEMFONTSIZE'    => 8
								];
								if ($iconTtl !== '-1') {
									$legendParams['ttl'] = $iconTtl;
								}
								if ($restricted) {
									$legendParams['restricted'] = 't';
								}
								$layerJson['legendParams'] = $legendParams;
							}
						}
					}
					if ($styleLayer['show_iconext'] != 'f' && !empty($styleLayer['icon_extended'])) {
						$queryParams = [];
						if ($iconTtl !== '-1') {
							$queryParams['ttl'] = $iconTtl;
						}
						if ($restricted) {
							$queryParams['restricted'] = 't';
						}
						if (!empty($queryParams)) {
							$separator = (strpos($styleLayer['icon_extended'], '?') === false) ? '?' : '&';
							$styleLayer['icon_extended'] .= $separator . http_build_query($queryParams);
						}
					}
				}
				$styleLayerId=$styleLayer['layer_id'];
				if ($group == 'background')
				{
					$styleLayer['layer_id'] = $styleLayerId.'-bg';
				}
				if (!in_array($styleLayer['layer_id'], $mapStyleLayers))
				{
					$mapStyleLayers[] = $styleLayer['layer_id'];
					$mapStyles[] = $styleLayer;
					if (!empty($styleLayer['clusterstyle']) && $styleLayer['clusterstyle'] !== '[]')
					{
						$styleLayer['layer_id'] = $styleLayerId.'-cluster';
						if (!in_array($styleLayer['layer_id'], $mapStyleLayers))
						{
							$mapStyleLayers[] = $styleLayer['layer_id'];
							$styleLayer['style_config']=$styleLayer['clusterstyle'];
							$mapStyles[] = $styleLayer;
						}
					}
				}
			}
			$layersJson[] = $layerJson;
		}
		return $layersJson;
	}