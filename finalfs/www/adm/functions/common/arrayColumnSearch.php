<?php

	// Takes a search (string, array), a column (string), an array, and optionally a return key (string) as parameters.
	// Searches the specified column of the array for the given search. Returns an empty array if no match is found.
	// If a match is found and no return key was given, then it returns the the first matching value.
	// If a return key was given then it is assumed that the matching value is an array and returns the value of its return key (match[returnkey]).
	function arrayColumnSearch($search, $column, $array, $return=false)
	{
		$columnValues = array_column($array, $column);
		$key = array_search($search, $columnValues);
		if ( $key !== false )
		{
			if (!$return)
			{
				return $array[$key];
			}
			else
			{
				return $array[$key][$return];
			}
		}
		else
		{
			return array();
		}
	}