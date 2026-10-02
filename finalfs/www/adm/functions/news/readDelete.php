<?php

	function readDelete($dbh, $username, $selectedNew, $action)
	{
		require("./constants/configSchema.php");
		if (!in_array($action, array('delete', 'read'), true))
		{
			http_response_code(400);
			exit('Invalid action');
		}
		$actionColumn=$action.'s';
		$field=$selectedNew[$actionColumn];
		$field[]=$username;
		sort($field);
		$newId=$selectedNew['new_id'];
		$table = pg_escape_identifier($dbh, $configSchema).'.'.pg_escape_identifier($dbh, 'news');
		$column = pg_escape_identifier($dbh, $actionColumn);
		$sql="UPDATE $table SET $column = $1 WHERE new_id = $2";
		$updateResult=pg_query_params($dbh, $sql, array(toPgArrayLiteral($field), $newId));
		if ($updateResult === false)
		{
			sqlQueryError($dbh);
		}
		pg_free_result($updateResult);
		if ($action=='delete')
		{
			header('Location: ?action=subjects');
		}
	}