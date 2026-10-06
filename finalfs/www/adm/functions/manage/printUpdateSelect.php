<?php

	// Takes a full target array, parameter values (array), style class (string), label (string), help-exist (boolean), and select options (optional, array, needs to have equal number of elements as parameter two).
	// Prints a drop-down selection box with option values to choose from. It's the parameter value of the chosen option that is set to be posted on form submit.
	// Parameter three and four are used to set the class and label for the select tag. A help-button is printed if a help exists.
	function printUpdateSelect($fullTarget, $configParamValues, $class, $label, $help=false, $options=null, $onchange='')
	{
		if (!isFullTarget($fullTarget))
		{
			invalidTarget(__FUNCTION__);
		}
		$targetId=targetId($fullTarget);
		$targetType=targetType($fullTarget);
		$configParam=key($configParamValues);
		$ucConfigParam=ucfirst($configParam);
		$sName='update'.$ucConfigParam;
		$selected=targetConfigParam($fullTarget, $configParam);
		echo <<<HERE
			<span class="optionSpan">
				<label title="{$targetType}:{$configParam}" for='{$targetId}{$ucConfigParam}'>{$label}</label>
				<select onchange='{$onchange}' class='{$class}' id='{$targetId}{$ucConfigParam}' name='{$sName}'>
		HERE;
		if (!isset($options))
		{
			$options=array("");
			if (is_array(current($configParamValues)))
			{
				$options=array_merge($options, current($configParamValues));
			}
		}
		printSelectOptions($options, $selected);
		echo '</select>';
		if ($help)
		{
			printHelpButton($targetType, $configParam);
		}
		echo '</span><wbr>';
	}