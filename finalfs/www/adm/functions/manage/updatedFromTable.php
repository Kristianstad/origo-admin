<?php

	function updatedFromTable($dbh, $tableWithSchema)
	{
		$table = qualifiedTableIdentifier($dbh, $tableWithSchema);
		if ($table === false)
		{
			return false;
		}
		$result=pg_query($dbh, "SELECT pg_xact_commit_timestamp(xmin) FROM $table ORDER BY 1 DESC NULLS LAST");
		if (!$result)
		{
			sqlQueryError($dbh);
		}
		$row = pg_fetch_row($result);
		return $row === false ? null : $row;
	}