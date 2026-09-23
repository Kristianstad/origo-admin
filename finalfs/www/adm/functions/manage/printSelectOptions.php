<?php

	function printSelectOptions($optionValues, $selectedValue=null, $preserveOrder=false)
	{
		$isAssociativeArray=hasStringKeys($optionValues);
		if ($isAssociativeArray && !$preserveOrder)
		{
			asort($optionValues);
		}
		foreach ($optionValues as $value => $label)
		{
			if (!$isAssociativeArray)
			{
				$value=$label;
			}
			$selectOption="<option value='$value'";
			if (isset($selectedValue))
			{
				if ($value == $selectedValue)
				{
					$selectOption="$selectOption selected";
				}
			}
			$selectOption="$selectOption>".ltrim(substr($label, strrpos($label,',')), ',')."</option>";
			echo $selectOption;
		}
	}