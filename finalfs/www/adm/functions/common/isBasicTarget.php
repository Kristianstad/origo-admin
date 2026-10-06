<?php

	// Takes a target array and returns true if it is a basic target array. Returns false if it is a full target array.
	function isBasicTarget($target)
	{
		return isTarget($target) && is_string($target[array_key_first($target)]);
	}