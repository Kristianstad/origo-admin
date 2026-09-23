<?php

	// Takes a full source target (array), source selectables (array), inheritPosts (array), and helps (array).
	// Prints form fields and buttons that are used to view and edit the configuration for the given source.
	function printSourceForm($source, $selectables, $inheritPosts, $helps=array())
	{
		if (!isFullTarget($source))
		{
			die("printSourceForm($source, $selectables, $inheritPosts, $helps=array()) failed!");
		}
		$sizePosts=sizePosts($inheritPosts);
		echo '<div><div class="printXFormDiv"><form method="post">';
		printTextarea($source, 'source_id', 'textareaMedium', 'Id:', in_array('source_id', $helps), $sizePosts);
		printUpdateSelect($source, array('service'=>$selectables['services']), 'bodySelect', 'Tjänst:', in_array('service', $helps), null, 'document.getElementById("serviceSet").style.display="none";');
		$sourceServiceId=targetConfigParam($source, 'service');
		
		if (empty($sourceServiceId))
		{
			$spanStyle="display:none";
		}
		else
		{
			$spanStyle='';
		}
		echo '<span id="serviceSet" style="'.$spanStyle.'">';

			if (targetConfigParam($source, 'service_type') == "File")
			{
				printTextarea($source, 'file', 'textareaLarge', 'Fil:', in_array('file', $helps), $sizePosts);
			}

			// If 'service_type' == 'File'/'OpenStreetMap' then hide the following fields by inserting a span-tag.
			if (targetConfigParam($source, 'service_type') == "File" || targetConfigParam($source, 'service_type') == "OpenStreetMap")
			{
				echo '<span title="service_typeFileOpenStreetMap" style="display:none">';
			}

				printUpdateSelect($source, array('with_geometry'=>array("f", "t")), 'miniSelect', 'With_geometry:', in_array('with_geometry', $helps));
				printTextarea($source, 'fi_point_tolerance', 'textareaSmall', 'Fi_point_tolerance:', in_array('fi_point_tolerance', $helps), $sizePosts);
				printTextarea($source, 'ttl', 'textareaSmall', 'Ttl:', in_array('ttl', $helps), $sizePosts);
				printUpdateSelect($source, array('tilegrid'=>$selectables['tilegrids']), 'bodySelect', 'Tilegrid:', in_array('tilegrid', $helps));
				printTextarea($source, 'softversion', 'textareaSmall', 'Programversion:', in_array('softversion', $helps), $sizePosts);
				if (!empty(targetConfigParam($source, 'tables')) && !empty(trim(targetConfigParam($source, 'tables'), '{}')))
				{
					printTextarea($source, 'tables', 'textareaLarge', 'Tabeller:', in_array('tables', $helps), $sizePosts, true);
				}
					
			// If 'service_type' == 'File'/'OpenStreetMap' then the fields above is hidden by a span-tag and the span-tag is closed.
			if (targetConfigParam($source, 'service_type') == "File" || targetConfigParam($source, 'service_type') == "OpenStreetMap")
			{
				echo '</span title="service_typeFileOpenStreetMap">';
			}
			
			printTextarea($source, 'abstract', 'textareaLarge', 'Beskrivning:', in_array('abstract', $helps), $sizePosts);
			printUpdateSelect($source, array('contact'=>$selectables['contacts']), 'bodySelect', 'Kontakt:', in_array('contact', $helps));
			printTextarea($source, 'updated', 'textareaMedium', 'Uppdaterad (åååå-mm-dd):', in_array('updated', $helps), $sizePosts);
			printTextarea($source, 'history', 'textareaLarge', 'Tillkomsthistorik:', in_array('history', $helps), $sizePosts);

		echo '</span title="serviceSet">';
		
		printTextarea($source, 'info', 'textareaLarge', 'Info:', in_array('info', $helps), $sizePosts);
		printHiddenInputs($inheritPosts);
		echo '<div class="buttonDiv">';
		$sourceForHistory=makeTargetBasic($source);
		printHistoryButtons($sourceForHistory);
		printUpdateButton('source', $inheritPosts['_formChanged'] ?? false);
		printCopyButton('source');
		$source=makeTargetBasic($source);
		printInfoButton($source);
		$deleteConfirmStr="Är du säker att du vill radera källan ".targetId($source)."? Referenser till källan hanteras separat.";
		printDeleteButton($source, $deleteConfirmStr, $inheritPosts);
		echo '</div></form></div></div>';
	}