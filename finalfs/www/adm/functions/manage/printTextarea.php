<?php

	// Takes a full target array, a config parameter name (string), a textarea css class name (string), a label (string), help available (boolean), sizePosts (array), is readonly (optional, boolean).
	// Prints a textarea containing the configuration parameter for the given target. Class name and label for the textarea are taken from the parameter three and four. 
	// The textarea is set to readonly if parameter six is set to true. A help button is printed if a help target exists, and a multiselect button is printed if the config
	// parameter name exists in the multiselectables.php constant.
	function printTextarea($fullTarget, $configParam, $class, $label, $help=false, $sizePosts=array(), $readonly=false)
	{
		if (!isFullTarget($fullTarget))
		{
			invalidTarget(__FUNCTION__);
		}
		require("./constants/multiselectables.php");
		$configParamValue=targetConfigParam($fullTarget, $configParam);
		if (preg_match('/^\{(("[[:alnum:]åäöÅÄÖ=\-\+#_\:\.\/\?\&\(\)]+([[:space:]][[:alnum:]åäöÅÄÖ=\-\+#_\:\.\/\?\&\(\)]+)*"|[[:alnum:]åäöÅÄÖ=\-\+#_\:\.\/\?\&\(\)]*),?)*\}$/', $configParamValue))
		{
			$configParamValue=str_replace('"', '', trim($configParamValue, '{}'));
		}
		$configParamValue=str_replace('&center=', '&amp;center=', $configParamValue);
		$ucConfigParam=ucfirst($configParam);
		if ($readonly)
		{
			$ro='readonly ';
		}
		else
		{
			$ro='';
		}
		$targetId=targetId($fullTarget);
		$targetType=targetType($fullTarget);
		if (isset($sizePosts['width'.$ucConfigParam], $sizePosts['height'.$ucConfigParam]))
		{
			$styleStr='style="width:'.$sizePosts['width'.$ucConfigParam].'px;height:'.$sizePosts['height'.$ucConfigParam].'px"';
		}
		else
		{
			$styleStr='';
		}
		$scrollTopAttribute='';
		if (isset($sizePosts['scroll'.$ucConfigParam]))
		{
			$scrollTopAttribute='data-scroll-top="'.max(0, (int) $sizePosts['scroll'.$ucConfigParam]).'"';
		}
		echo <<<HERE
			<span class="optionSpan">
				<label title="{$targetType}:{$configParam}" for="{$targetId}{$ucConfigParam}">{$label}</label>
				<textarea {$ro}rows="1" class="{$class}" id="{$targetId}{$ucConfigParam}" name="update{$ucConfigParam}" data-config-textarea {$scrollTopAttribute} {$styleStr}>{$configParamValue}</textarea>
				<input type="hidden" name="newwidth{$ucConfigParam}" id="{$targetId}{$ucConfigParam}_width">
				<input type="hidden" name="newheight{$ucConfigParam}" id="{$targetId}{$ucConfigParam}_height">
				<input type="hidden" name="newscroll{$ucConfigParam}" id="{$targetId}{$ucConfigParam}_scroll">
		HERE;
		if (in_array($configParam, $multiselectables))
		{
			$textareaId = $targetId . $ucConfigParam;
			printMultiselectButton($configParam, $configParamValue, $textareaId, '+', 'smallMultiselectButton');
		}
		if ($help)
		{
			printHelpButton($targetType, $configParam);
		}
		echo '</span><wbr>';
	}