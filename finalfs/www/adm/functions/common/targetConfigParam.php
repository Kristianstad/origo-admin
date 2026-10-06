<?php

	// Takes a full target array and an Origo configuration parameter name (string).
	// Returns the value for the given configuration parameter for the given target
	function targetConfigParam($fullTarget, $configParam)
	{
		if (isFullTarget($fullTarget))
		{
			$config = $fullTarget[array_key_first($fullTarget)];
			return $config[$configParam] ?? null;
		}
		else
		{
			invalidTarget(__FUNCTION__);
		}
	}