<?php

	function updated_from_table2($dbh, $tableWithSchema)
	{
		$table = qualifiedTableIdentifier($dbh, $tableWithSchema);
		if ($table === false)
		{
			return false;
		}
		$result=pg_query($dbh, "SELECT pg_xact_commit_timestamp(xmin),xmin FROM $table ORDER BY pg_xact_commit_timestamp(xmin) DESC NULLS LAST");
		if (!$result)
		{
			sqlQueryError($dbh);
		}
		$row = pg_fetch_row($result);
		return $row === false ? null : $row;
	}