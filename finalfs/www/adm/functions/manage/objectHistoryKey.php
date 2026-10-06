<?php

function objectHistoryKey($target)
{
	if (!isTarget($target))
	{
		invalidTarget(__FUNCTION__);
	}
	return targetType($target) . ':' . targetId($target);
}