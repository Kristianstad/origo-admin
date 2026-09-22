<?php

	// Takes a full map target (array), map selectables (array), inheritPosts (array), and helps (array).
	// Prints form fields and buttons that are used to view and edit the configuration for the given map.
	function printMapForm($map, $selectables, $inheritPosts, $helps=array())
	{
		if (!isFullTarget($map))
		{
			die("printMapForm($map, $selectables, $inheritPosts, $helps=array()) failed!");
		}
		$sizePosts=sizePosts($inheritPosts);
		echo '<div><div class="printXFormDiv"><form method="post">';
		printTextarea($map, 'map_id', 'textareaMedium', 'Id:', in_array('map_id', $helps), $sizePosts);
		printTextarea($map, 'title', 'textareaMedium', 'Titel:', in_array('title', $helps), $sizePosts);
		printTextarea($map, 'url', 'textareaLarge', 'Url:', in_array('url', $helps), $sizePosts);
		printTextarea($map, 'layers', 'textareaLarge', 'Lager:', in_array('layers', $helps), $sizePosts);
		printTextarea($map, 'groups', 'textareaLarge', 'Grupper:', in_array('groups', $helps), $sizePosts);
		printTextarea($map, 'controls', 'textareaLarge', 'Kontroller:', in_array('controls', $helps), $sizePosts);
		printTextarea($map, 'plugins', 'textareaLarge', 'Plugins:', in_array('plugins', $helps), $sizePosts);
		printTextarea($map, 'proj4defs', 'textareaMedium', 'Proj4defs:', in_array('proj4defs', $helps), $sizePosts);
		printUpdateSelect($map, array('footer'=>$selectables['footers']), 'bodySelect', 'Sidfot:', in_array('footer', $helps));
		printTextarea($map, 'featureinfooptions', 'textareaMedium', 'FeatureInfoOptions:', in_array('featureinfooptions', $helps), $sizePosts);
		printTextarea($map, 'projectioncode', 'textareaMedium', 'Projektion:', in_array('projectioncode', $helps), $sizePosts);
		printTextarea($map, 'extent', 'textareaMedium', 'Utbredning:', in_array('extent', $helps), $sizePosts);
		printTextarea($map, 'center', 'textareaMedium', 'Mittpunkt:', in_array('center', $helps), $sizePosts);
		printTextarea($map, 'zoom', 'textareaXSmall', 'Zoom:', in_array('zoom', $helps), $sizePosts);
		printUpdateSelect($map, array('mapgrid'=>array("f", "t")), 'miniSelect', 'Visa rutnät:', in_array('mapgrid', $helps));
		printUpdateSelect($map, array('enablerotation'=>array("f", "t")), 'miniSelect', 'Roterbar:', in_array('enablerotation', $helps));
		printUpdateSelect($map, array('embedded'=>array("f", "t")), 'miniSelect', 'Inbäddad:', in_array('embedded', $helps));
		printTextarea($map, 'palette', 'textareaLarge', 'Färgpalett:', in_array('palette', $helps), $sizePosts);
		printTextarea($map, 'resolutions', 'textareaMedium', 'Upplösningar:', in_array('resolutions', $helps), $sizePosts);
		printUpdateSelect($map, array('constrainresolution'=>array("f", "t")), 'miniSelect', 'Upplösningsbegränsad:', in_array('constrainresolution', $helps));
		printUpdateSelect($map, array('tilegrid'=>$selectables['tilegrids']), 'bodySelect', 'Tilegrid:', in_array('tilegrid', $helps));
		printUpdateSelect($map, array('show_meta'=>array("f", "t")), 'miniSelect', 'Visa metadata:', in_array('show_meta', $helps));
		printUpdateSelect($map, array('searchengineindexable'=>array("f", "t")), 'miniSelect', 'Kan indexeras av sökmotorer:', in_array('searchengineindexable', $helps));
		printTextarea($map, 'icon', 'textareaMedium', 'Genvägsikon:', in_array('icon', $helps), $sizePosts);
		printTextarea($map, 'css_files', 'textareaLarge', 'CSS-filer:', in_array('css_files', $helps), $sizePosts);
		printTextarea($map, 'css', 'textareaLarge', 'CSS:', in_array('css', $helps), $sizePosts);
		printTextarea($map, 'js_files', 'textareaLarge', 'JS-filer:', in_array('js_files', $helps), $sizePosts);
		printTextarea($map, 'js', 'textareaLarge', 'JS:', in_array('js', $helps), $sizePosts);
		printTextarea($map, 'onload', 'textareaLarge', 'Origo.on(load)-JS:', in_array('onload', $helps), $sizePosts);
		printTextarea($map, 'abstract', 'textareaLarge', 'Beskrivning:', in_array('abstract', $helps), $sizePosts);
		printTextarea($map, 'keywords', 'textareaLarge', 'Nyckelord:', in_array('keywords', $helps), $sizePosts);
		printTextarea($map, 'info', 'textareaLarge', 'Info:', in_array('info', $helps), $sizePosts);
		printHiddenInputs($inheritPosts);
		echo '<hr class="dashedHr">';
		echo '<div class="buttonDiv">';
		printUpdateButton('map', $inheritPosts['_formChanged'] ?? false);
		printCopyButton('map');
		$id=targetId($map);
		$url=targetConfigParam($map, 'url');
		$changed=targetConfigParam($map, 'changed');
		$map=makeTargetBasic($map);
		printInfoButton($map);
		$deleteConfirmStr="Är du säker på att du vill radera kartan $id? Ingående kontroller, grupper och lager påverkas ej.";
		printDeleteButton($map, $deleteConfirmStr, $inheritPosts);
		printConfigPreviewButton($id);
		printWriteConfigButton($id, $changed);
		printExportJsonButton($id);
		if (empty($url))
		{
			$url="../".str_replace('#', '%23', $id).".html";
		}
		printUrlButton($url, 'map');
		echo '</div></form></div></div>';
	}