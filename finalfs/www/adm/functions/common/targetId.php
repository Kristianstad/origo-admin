<?php

	function targetId($target)
	{
		if (!isTarget($target))
		{
			invalidTarget(__FUNCTION__);
		}
		$type = array_key_first($target);
		$value = $target[$type];
		if (is_array($value))
		{
			$id = $value[pkColumnOfTable(typeTableName($type))] ?? null;
		}
		else
		{
			$id = $value;
		}
		if (!is_string($id) || $id === '')
		{
			invalidTarget(__FUNCTION__);
		}
		return $id;
	}