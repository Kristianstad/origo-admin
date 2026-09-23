<?php

	// Takes a full service target (array), inheritPosts (array), and helps (array).
	// Prints form fields and buttons that are used to view and edit the configuration for the given service.
	function printServiceForm($service, $inheritPosts, $helps=array())
	{
		if (!isFullTarget($service))
		{
			die("printServiceForm($service, $inheritPosts, $helps=array()) failed!");
		}
		$sizePosts=sizePosts($inheritPosts);
		echo '<div><div class="printXFormDiv"><form method="post">';
		printTextarea($service, 'service_id', 'textareaMedium', 'Id:', in_array('service_id', $helps), $sizePosts);
		printUpdateSelect($service, array('type'=>array("File","OpenStreetMap","QGIS","Geoserver")), 'bodySelect', 'Typ:', in_array('type', $helps));
		$serviceType=targetConfigParam($service, 'type');
		if (!empty($serviceType))
		{
			if ($serviceType != 'File' && $serviceType != 'OpenStreetMap')
			{
				printTextarea($service, 'base_url', 'textareaLarge', 'Huvudurl:', in_array('base_url', $helps), $sizePosts);
			}
			printUpdateSelect($service, array('restricted'=>array("f", "t")), 'miniSelect', 'Rättighetsstyrd:', in_array('restricted', $helps));
			printTextarea($service, 'formats', 'textareaLarge', 'Tillgängliga format:', in_array('formats', $helps), $sizePosts);
			printTextarea($service, 'abstract', 'textareaLarge', 'Beskrivning:', in_array('abstract', $helps), $sizePosts);
		}
		else
		{
			printHiddenInputs(array(
				'updateBase_url' => targetConfigParam($service, 'base_url'),
				'updateRestricted' => targetConfigParam($service, 'restricted'),
				'updateFormats' => targetConfigParam($service, 'formats'),
				'updateAbstract' => targetConfigParam($service, 'abstract')
			));
		}
		printTextarea($service, 'info', 'textareaLarge', 'Info:', in_array('info', $helps), $sizePosts);
		printHiddenInputs($inheritPosts);
		echo '<div class="buttonDiv">';
		$serviceForHistory=makeTargetBasic($service);
		printHistoryButtons($serviceForHistory);
		printUpdateButton('service', $inheritPosts['_formChanged'] ?? false);
		printCopyButton('service');
		$service=makeTargetBasic($service);
		printInfoButton($service);
		$deleteConfirmStr="Är du säker att du vill radera tjänsten ".targetId($service)."? Referenser till tjänsten hanteras separat.";
		printDeleteButton($service, $deleteConfirmStr, $inheritPosts);
		echo '</div></form></div></div>';
	}