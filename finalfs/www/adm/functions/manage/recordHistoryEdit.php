<?php

	// Stores a before/after snapshot of $target's configuration as a new edit, so the change
	// can later be undone/redone. Any previously undone (now stale) redo-branch is discarded.
	function recordHistoryEdit($dbh, $target, $action, $beforeConfig, $afterConfig)
	{
		require("./constants/configSchema.php");
		$targetKey=objectHistoryKey($target);
		$targetTable=targetTable($target);
		$targetId=targetId($target);
		pg_query_params($dbh, "INSERT INTO $configSchema.object_identity (target_key, target_table, target_id) VALUES ($1, $2, $3) ON CONFLICT (target_key) DO UPDATE SET updated_at = now()", array($targetKey, $targetTable, $targetId));
		$cursorResult=pg_query_params($dbh, "SELECT current_edit_id FROM $configSchema.edit_cursor WHERE target_key = $1", array($targetKey));
		$currentEditId=null;
		if ($cursorResult !== false && pg_num_rows($cursorResult) > 0)
		{
			$currentEditId=pg_fetch_result($cursorResult, 0, 0);
		}
		if ($currentEditId !== null)
		{
			pg_query_params($dbh, "DELETE FROM $configSchema.edits WHERE target_key = $1 AND edit_id > $2", array($targetKey, $currentEditId));
		}
		else
		{
			pg_query_params($dbh, "DELETE FROM $configSchema.edits WHERE target_key = $1", array($targetKey));
		}
		$insertResult=pg_query_params($dbh, "INSERT INTO $configSchema.edits (target_key, target_table, target_id, action, before_data, after_data) VALUES ($1, $2, $3, $4, $5, $6) RETURNING edit_id", array($targetKey, $targetTable, $targetId, $action, json_encode($beforeConfig), json_encode($afterConfig)));
		if ($insertResult === false)
		{
			return false;
		}
		$newEditId=pg_fetch_result($insertResult, 0, 0);
		pg_query_params($dbh, "INSERT INTO $configSchema.edit_cursor (target_key, current_edit_id, can_undo, can_redo, updated_at) VALUES ($1, $2, true, false, now()) ON CONFLICT (target_key) DO UPDATE SET current_edit_id = $2, can_undo = true, can_redo = false, updated_at = now()", array($targetKey, $newEditId));
		return true;
	}