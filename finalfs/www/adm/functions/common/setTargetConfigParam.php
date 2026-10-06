<?php

	// Takes a full target array, an Origo configuration parameter name (string), and a configuration parameter value.
	// Sets the configuration parameter to the new value in the given target.
	function setTargetConfigParam(&$fullTarget, $configParam, $value)
	{
		if (isFullTarget($fullTarget))
		{
			$fullTarget[array_key_first($fullTarget)][$configParam]=$value;
		}
		else
		{
			invalidTarget(__FUNCTION__);
		}
	}