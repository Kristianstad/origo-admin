<?php

	function targetTable($target)
	{
		if (isTarget($target))
		{
			return typeTableName(array_key_first($target));
		}
		else
		{
			invalidTarget(__FUNCTION__);
		}
	}