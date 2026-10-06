<?php

	// Takes a target array and returns its type (string)
	function targetType($target)
	{
		if (isTarget($target))
		{
			return array_key_first($target);
		}
		else
		{
			invalidTarget(__FUNCTION__);
		}
	}