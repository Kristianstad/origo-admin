<?php

	// Takes a type string and config array and combine them into a full target array.
	// The full target array has the type as only key and config array as value
	function makeFullTarget($type, $config)
	{
		if (is_string($type) && $type !== '' && is_array($config) && !empty($config))
		{
			return array($type=>$config);
		}
		else
		{
			invalidTarget(__FUNCTION__);
		}
	}