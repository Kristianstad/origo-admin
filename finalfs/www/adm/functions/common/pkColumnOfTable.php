<?php

	// Takes the name of an Origo configuration table as parameter and returns the name of the primary key- (id-) column
	function pkColumnOfTable($tableName)
	{
		if ($tableName == 'proj4defs')
		{
			return 'code';
		}
		else
		{
			return tableType($tableName).'_id';
		}
	}