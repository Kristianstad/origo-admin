<?php

function qualifiedTableIdentifier($dbh, string $tableWithSchema): string|false
{
	$parts = explode('.', $tableWithSchema);
	if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '')
	{
		return false;
	}

	$result = pg_query_params(
		$dbh,
		"SELECT 1 FROM information_schema.tables WHERE table_schema = $1 AND table_name = $2 AND table_type = 'BASE TABLE'",
		$parts
	);
	if ($result === false)
	{
		sqlQueryError($dbh);
	}
	if (pg_num_rows($result) === 0)
	{
		return false;
	}

	return pg_escape_identifier($dbh, $parts[0]).'.'.pg_escape_identifier($dbh, $parts[1]);
}