<?php

function objectHistoryKey($target)
{
	if (!isTarget($target))
	{
		die("objectHistoryKey($target) failed!");
	}
	return targetType($target) . ':' . targetId($target);
}