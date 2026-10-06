<?php

	// Takes a target array and returns it as a basic target array.
	function toBasicTarget($target)
	{
		if (!isTarget($target))
		{
			invalidTarget(__FUNCTION__);
		}
		return array(targetType($target) => targetId($target));
	}