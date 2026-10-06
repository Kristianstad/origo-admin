<?php

	// Removes exactly one trailing 's' from a table name to get its item type.
	function tableType($table)
	{
		return $table !== '' && substr($table, -1) === 's' ? substr($table, 0, -1) : $table;
	}