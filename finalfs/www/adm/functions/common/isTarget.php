<?php

	function isTarget($target)
	{
		if (!is_array($target) || count($target) !== 1)
		{
			return false;
		}
		$type = array_key_first($target);
		return is_string($type) && $type !== '';
	}