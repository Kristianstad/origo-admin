<?php

	function isFullTarget($target)
	{
		return isTarget($target) && is_array($target[array_key_first($target)]);
	}