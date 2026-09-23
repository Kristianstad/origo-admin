<?php

function historyStateForTarget($dbh, $target)
{
	// pg_connect() returns a PgSql\Connection object since PHP 8.1, not a resource
	if (!isTarget($target) || !(is_resource($dbh) || $dbh instanceof \PgSql\Connection))
	{
		return array('undo' => false, 'redo' => false, 'current_edit_id' => null, 'edits' => array());
	}
	$targetKey = objectHistoryKey($target);
	$loaded = pg_query_params($dbh, "SELECT current_edit_id FROM map_configs.edit_cursor WHERE target_key = $1", array($targetKey));
	$currentEditId = null;
	if ($loaded !== false && pg_num_rows($loaded) > 0)
	{
		$currentEditId = pg_fetch_result($loaded, 0, 0);
	}
	$result = pg_query_params($dbh, "SELECT edit_id, date, action, before_data, after_data FROM map_configs.edits WHERE target_key = $1 ORDER BY date ASC, edit_id ASC", array($targetKey));
	$edits = array();
	if ($result !== false)
	{
		while ($row = pg_fetch_assoc($result))
		{
			if (isset($row['before_data']) && is_string($row['before_data']))
			{
				$row['before_data'] = json_decode($row['before_data'], true);
			}
			if (isset($row['after_data']) && is_string($row['after_data']))
			{
				$row['after_data'] = json_decode($row['after_data'], true);
			}
			$edits[] = $row;
		}
	}
	$index = null;
	if ($currentEditId !== null)
	{
		foreach ($edits as $i => $edit)
		{
			if ($edit['edit_id'] == $currentEditId)
			{
				$index = $i;
				break;
			}
		}
	}
	if ($index === null && !empty($edits))
	{
		$index = count($edits) - 1;
	}
	$undo = ($index !== null && $index > 0);
	$redo = ($index !== null && $index < count($edits) - 1);
	return array(
		'undo' => $undo,
		'redo' => $redo,
		'current_edit_id' => $currentEditId,
		'edits' => $edits,
		'index' => $index,
	);
}