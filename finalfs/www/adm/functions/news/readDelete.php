<?php

	function readDelete($dbh, $username, $selectedNew, $action)
	{
		require("./constants/configSchema.php");
		$actionColumn=$action.'s';
		$field=$selectedNew[$actionColumn];
		$field[]=$username;
		sort($field);
		$field='{'.implode(',', $field).'}';
		$newId=$selectedNew['new_id'];
		$sql="UPDATE $configSchema.news SET $actionColumn = '$field' WHERE new_id = '$newId'";
		$updateResult=pg_query($dbh, $sql);
		pg_free_result($updateResult);
		if ($action=='delete')
		{
			header('Location: ?action=subjects');
		}
	}