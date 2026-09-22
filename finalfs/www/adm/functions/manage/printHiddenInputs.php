<?php

	// Takes inheritPosts or similarly ordered array and prints a hidden form input element for each key-value pair in the array.
	// The array keys are used for the input names, and the array values are used for the input values. 
	function printHiddenInputs($inheritPosts)
	{
		$hiddenInputs="";
		foreach ($inheritPosts as $idKey => $idValue)
		{
			if ($idKey != 'layerCategory' && !str_starts_with($idKey, '_'))
			{
				$hiddenInputs=$hiddenInputs.'<input type="hidden" name="'.$idKey.'" value="'.$idValue.'">';
			}
		}
		if (!empty($hiddenInputs))
		{
			echo $hiddenInputs;
		}
	}