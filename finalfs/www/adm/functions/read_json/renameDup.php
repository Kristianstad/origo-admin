<?php

function renameDup(string $name, array &$uniqueLayers): string
{
	$count=0;
	do
	{
		$newname=$name.($count > 0 ? "#$count" : '#');
		$count++;
	}
	while (in_array($newname, $uniqueLayers, true));
	$uniqueLayers[]=$newname;
	return $newname;
}