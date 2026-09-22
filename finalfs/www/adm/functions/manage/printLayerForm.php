<?php

	// Takes a full layer target (array), layer selectables (array), layer operationtables (array), layer sources (array), inheritPosts (array), and helps (array).
	// Prints form fields and buttons that are used to:
	// 1. View and edit the configuration for the given layer.
	// 2. Add or remove given layer to/from maps or groups.
	function printLayerForm($layer, $selectables, $operationTables, $inheritPosts, $helps=array())
	{
		if (!isFullTarget($layer))
		{
			die("printLayerForm($layer, $selectables, $operationTables, $inheritPosts, $helps=array()) failed!");
		}
		$sizePosts=sizePosts($inheritPosts);
		echo '<div><div class="printXFormDiv"><form method="post">';
		printTextarea($layer, 'layer_id', 'textareaMedium', 'Id:', in_array('layer_id', $helps), $sizePosts);
		printTextarea($layer, 'title', 'textareaMedium', 'Titel:', in_array('title', $helps), $sizePosts);
		printUpdateSelect($layer, array('source'=>$selectables['sources']), 'miniSelect', 'Källa:', in_array('source', $helps), null, 'document.getElementById("sourceSet").style.display="none";');
		$layerSourceId=targetConfigParam($layer, 'source');
		if (empty($layerSourceId))
		{
			$selectables['formats']=array("GROUP");
		}
		elseif (!is_array($selectables['formats']))
		{
			$selectables['formats']=array();
		}
		printUpdateSelect($layer, array('type'=>$selectables['formats']), 'miniSelect', 'Typ:', in_array('type', $helps), null, 'document.getElementById("typeSet").style.display="none";');
		
		if (empty(targetConfigParam($layer, 'type')) || !in_array(targetConfigParam($layer, 'type'), $selectables['formats']))
		{
			$spanStyle="display:none";
		}
		else
		{
			$spanStyle='';
		}
		echo '<span id="typeSet" style="'.$spanStyle.'">';
	
			if (targetConfigParam($layer, 'type') == 'WFS')
			{
				printUpdateSelect($layer, array('layertype'=>array("vector", "cluster", "image")), 'miniSelect', 'WFS-typ:', in_array('layertype', $helps));
				if (!empty(targetConfigParam($layer, 'layertype')) && targetConfigParam($layer, 'layertype') == 'cluster')
				{
					printTextarea($layer, 'clusterstyle', 'textareaLarge', 'Klusterstil:', in_array('clusterstyle', $helps), $sizePosts);
					printTextarea($layer, 'clusteroptions', 'textareaLarge', 'Klusteralternativ:', in_array('clusteroptions', $helps), $sizePosts);
				}
				printUpdateSelect($layer, array('editable'=>array("f", "t")), 'miniSelect', 'Redigerbar:', in_array('editable', $helps));
				if (targetConfigParam($layer, 'editable') == "t")
				{
					printTextarea($layer, 'allowededitoperations', 'textareaMedium', 'Redigeringsalt.:', in_array('allowededitoperations', $helps), $sizePosts);
					printTextarea($layer, 'geometryname', 'textareaMedium', 'Geometrinamn:', in_array('geometryname', $helps), $sizePosts);
					printTextarea($layer, 'geometrytype', 'textareaMedium', 'Geometrityp:', in_array('geometrytype', $helps), $sizePosts);
					printTextarea($layer, 'featurelistattributes', 'textareaMedium', 'featureListAttributes:', in_array('featurelistattributes', $helps), $sizePosts);
					printTextarea($layer, 'drawtools', 'textareaMedium', 'drawTools:', in_array('drawtools', $helps), $sizePosts);
				}
				else
				{
					printHiddenInputs(array(
						'updateAllowededitoperations' => targetConfigParam($layer, 'allowededitoperations'),
						'updateGeometryname' => targetConfigParam($layer, 'geometryname'),
						'updateGeometrytype' => targetConfigParam($layer, 'geometrytype'),
						'updateFeaturelistattributes' => targetConfigParam($layer, 'featurelistattributes'),
						'updateDrawtools' => targetConfigParam($layer, 'drawtools')
					));
				}
			}
			elseif (targetConfigParam($layer, 'type') == 'WMS')
			{
				printUpdateSelect($layer, array('tiled'=>array("f", "t")), 'miniSelect', 'Tiled:', in_array('tiled', $helps));
			}
			elseif (targetConfigParam($layer, 'type') == 'GROUP')
			{
				printTextarea($layer, 'layers', 'textareaMedium', 'Lager:', in_array('layers', $helps), $sizePosts);
			}
			printUpdateSelect($layer, array('queryable'=>array("f", "t")), 'miniSelect', 'Klickbar:', in_array('queryable', $helps));
			printUpdateSelect($layer, array('visible'=>array("f", "t")), 'miniSelect', 'Synlig:', in_array('visible', $helps));
			printUpdateSelect($layer, array('exportable'=>array("f", "t")), 'miniSelect', 'Exporterbar:', in_array('exportable', $helps));
			printTextarea($layer, 'opacity', 'textareaSmall', 'Opacitet:', in_array('opacity', $helps), $sizePosts);
			if (!empty(targetConfigParam($layer, 'service_id')) && targetConfigParam($layer, 'service_restricted') == 't')
			{
				echo "<span><img class='yellowLock' src='../img/png/lock_yellow.png' alt='Skyddat lager' title='Skyddat lager'>";
				printTextarea($layer, 'adusers', 'textareaLarge', 'AD-användare:', in_array('adusers', $helps), $sizePosts);
				echo "</span><wbr><span><img class='yellowLock' src='../img/png/lock_yellow.png' alt='Skyddat lager' title='Skyddat lager'>";
				printTextarea($layer, 'adgroups', 'textareaLarge', 'AD-grupper:', in_array('adgroups', $helps), $sizePosts);
				echo "</span><wbr>";
			}
			else
			{
				printHiddenInputs(array(
					'updateAdusers' => targetConfigParam($layer, 'adusers'),
					'updateAdgroups' => targetConfigParam($layer, 'adgroups')
				));
			}
			printUpdateSelect($layer, array('swiper'=>array("f", "t", "under")), 'miniSelect', 'Swiper-lager:', in_array('swiper', $helps));
			if (!empty(targetConfigParam($layer, 'type')) && targetConfigParam($layer, 'type') == 'WMS')
			{
				printTextarea($layer, 'format', 'textareaMedium', 'Format:', in_array('format', $helps), $sizePosts);
				printTextarea($layer, 'featureinfolayer', 'textareaMedium', 'FeatureInfo-lager:', in_array('featureinfolayer', $helps), $sizePosts);
			}
			else
			{
				printHiddenInputs(array(
					'updateFormat' => targetConfigParam($layer, 'format'),
					'updateFeatureinfolayer' => targetConfigParam($layer, 'featureinfolayer')
				));
			}
			printTextarea($layer, 'attributes', 'textareaLarge', 'Attribut:', in_array('attributes', $helps), $sizePosts);
			printTextarea($layer, 'style_layer', 'textareaMedium', 'Stillager:', in_array('style_layer', $helps), $sizePosts);
	
			// If 'style_layer' is set then hide the following fields by inserting a span-tag.
			if (!empty(targetConfigParam($layer, 'style_layer')) && !empty(trim(targetConfigParam($layer, 'style_layer'))))
			{
				echo '<span title="style_layerSet" style="display:none">';
			}
	
				printTextarea($layer, 'style_config', 'textareaLarge', 'Stilkonfiguration:', in_array('style_config', $helps), $sizePosts);
			
				// If 'style_config' is set then hide the following fields by inserting a span-tag.
				if ((!empty(targetConfigParam($layer, 'style_config')) && !empty(trim(targetConfigParam($layer, 'style_config'), " []{}\n\r\t")) && targetConfigParam($layer, 'style_config') != 'null') || targetConfigParam($layer, 'type') == 'GEOJSON')
				{
					echo '<span title="style_configSet" style="display:none">';
				}
			
					printTextarea($layer, 'style_filter', 'textareaLarge', 'Stilfilter:', in_array('style_filter', $helps), $sizePosts);
					printUpdateSelect($layer, array('show_icon'=>array("f", "t")), 'miniSelect', 'Visa ikon:', in_array('show_icon', $helps));
					printUpdateSelect($layer, array('show_iconext'=>array("f", "t")), 'miniSelect', 'Visa utfälld ikon:', in_array('show_iconext', $helps));
					if (targetConfigParam($layer, 'show_icon') != 'f')
					{
						printTextarea($layer, 'icon', 'textareaLarge', 'Ikon:', in_array('icon', $helps), $sizePosts);
						if (targetConfigParam($layer, 'show_iconext') != 'f')
						{
							printTextarea($layer, 'icon_extended', 'textareaLarge', 'Utfälld ikon:', in_array('icon_extended', $helps), $sizePosts);
						}
						else
						{
							printHiddenInputs(array(
								'updateIcon_extended' => targetConfigParam($layer, 'icon_extended')
							));
						}
					}
					else
					{
						printHiddenInputs(array(
							'updateIcon' => targetConfigParam($layer, 'icon')
						));
						if (targetConfigParam($layer, 'show_iconext') == 't')
						{
							printTextarea($layer, 'icon_extended', 'textareaLarge', 'Utfälld ikon:', in_array('icon_extended', $helps), $sizePosts);
						}
						else
						{
							printHiddenInputs(array(
								'updateIcon_extended' => targetConfigParam($layer, 'icon_extended')
							));
						}
					}
					if ((targetConfigParam($layer, 'show_icon') == 'f' || empty(targetConfigParam($layer, 'icon'))) && ((targetConfigParam($layer, 'icon_extended') != 't' || empty(targetConfigParam($layer, 'icon_extended'))) && targetConfigParam($layer, 'type') != 'GROUP'))
					{
						printUpdateSelect($layer, array('thematicstyling'=>array("f", "t")), 'miniSelect', 'Regelbaserad visning:', in_array('thematicstyling', $helps));
					}
				
				// If 'style_config' is set then the fields above is hidden by a span-tag and the span-tag is closed.
				if ((!empty(targetConfigParam($layer, 'style_config')) && !empty(trim(targetConfigParam($layer, 'style_config'), " []{}\n\r\t")) && targetConfigParam($layer, 'style_config') != 'null') || targetConfigParam($layer, 'type') == 'GEOJSON')
				{
					echo '</span title="style_configSet">';
				}

			// If 'style_layer' is set then the fields above is hidden by a span-tag and the span-tag is closed.
			if (!empty(targetConfigParam($layer, 'style_layer')) && !empty(trim(targetConfigParam($layer, 'style_layer'))))
			{
				echo '</span title="style_layerSet">';
			}
			
			printTextarea($layer, 'indexweight', 'textareaSmall', 'Indexvikt:', in_array('indexweight', $helps), $sizePosts);
			printTextarea($layer, 'maxscale', 'textareaSmall', 'Maxskala:', in_array('maxscale', $helps), $sizePosts);
			printTextarea($layer, 'minscale', 'textareaSmall', 'Minskala:', in_array('minscale', $helps), $sizePosts);
			printTextarea($layer, 'exports', 'textareaMedium', 'Exportlager:', in_array('exports', $helps), $sizePosts);
			printTextarea($layer, 'attribution', 'textareaLarge', 'Tillskrivning:', in_array('attribution', $helps), $sizePosts);
			echo '<hr class="dashedHr">';
			printUpdateSelect($layer, array('show_meta'=>array("f", "t")), 'miniSelect', 'Visa metadata:', in_array('show_meta', $helps));
			printTextarea($layer, 'abstract', 'textareaLarge', 'Beskrivning:', in_array('abstract', $helps), $sizePosts);
			printTextarea($layer, 'keywords', 'textareaLarge', 'Nyckelord:', in_array('keywords', $helps), $sizePosts);
			printTextarea($layer, 'resources', 'textareaMedium', 'Resurser:', in_array('resources', $helps), $sizePosts);
			printUpdateSelect($layer, array('contact'=>$selectables['contacts']), 'bodySelect', 'Kontakt:', in_array('contact', $helps));
			printUpdateSelect($layer, array('origin'=>$selectables['origins']), 'bodySelect', 'Ursprungskälla:', in_array('origin', $helps));
			printTextarea($layer, 'updated', 'textareaMedium', 'Uppdaterad (åååå-mm-dd):', in_array('updated', $helps), $sizePosts);
			printUpdateSelect($layer, array('update'=>$selectables['updates']), 'bodySelect', 'Uppdatering:', in_array('update', $helps));
			printTextarea($layer, 'web', 'textareaMedium', 'Webbsida:', in_array('web', $helps), $sizePosts);
			printTextarea($layer, 'history', 'textareaLarge', 'Tillkomsthistorik:', in_array('history', $helps), $sizePosts);
			if (!empty(targetConfigParam($layer, 'tables')) && !empty(trim(targetConfigParam($layer, 'tables'), '{}')))
			{
				printTextarea($layer, 'tables', 'textareaMedium', 'Tabeller:', in_array('tables', $helps), $sizePosts, true);
			}
		
		echo '</span title="typeSet">';
			
		echo '<hr class="dashedHr">';
		printTextarea($layer, 'info', 'textareaLarge', 'Info:', in_array('info', $helps), $sizePosts);
		printHiddenInputs($inheritPosts);
		echo '<hr class="dashedHr">';
		echo '<div class="buttonDiv">';
		printUpdateButton('layer', $inheritPosts['_formChanged'] ?? false);
		printCopyButton('layer');
		$layer=makeTargetBasic($layer);
		printInfoButton($layer);
		printConfigPreviewButton('preview', null, targetId($layer));
		$deleteConfirmStr="Är du säker att du vill radera lagret ".targetId($layer)."? Referenser till lagret hanteras separat.";
		printDeleteButton($layer, $deleteConfirmStr, $inheritPosts);
		echo '</div></form></div></div><div class="addRemoveDiv">';
		printAddRemoveOperations($layer, $operationTables, $inheritPosts, array('add'=>array('maps'=>'Lägg till i karta', 'groups'=>'Lägg till i grupp'), 'remove'=>array('maps'=>'Ta bort från karta', 'groups'=>'Ta bort från grupp')));
		echo '</div>';
	}