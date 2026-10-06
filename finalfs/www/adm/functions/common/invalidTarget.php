<?php

function invalidTarget(string $functionName): never
{
	die($functionName.'() failed! Invalid target.');
}