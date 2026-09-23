<?php

	// Moves the history cursor for $targetKey one step in $direction ('undo' or 'redo') and
	// writes the corresponding configuration snapshot back into the target's table.
	// Returns array('ok' => bool, 'target_table' => string|null, 'target_id' => string|null, 'error' => string|null)
	function applyHistoryNavigation($dbh, $targetKey, $direction)
	{
		require("./constants/configSchema.php");
		if ($direction != 'undo' && $direction != 'redo')
		{
			return array('ok' => false, 'target_table' => null, 'target_id' => null, 'error' => "Okänd riktning: $direction");
		}
		$editsResult=pg_query_params($dbh, "SELECT edit_id, target_table, target_id, before_data, after_data FROM $configSchema.edits WHERE target_key = $1 ORDER BY edit_id ASC", array($targetKey));
		if ($editsResult === false)
		{
			return array('ok' => false, 'target_table' => null, 'target_id' => null, 'error' => pg_last_error($dbh));
		}
		$edits=array();
		while ($row=pg_fetch_assoc($editsResult))
		{
			$edits[]=$row;
		}
		if (empty($edits))
		{
			return array('ok' => false, 'target_table' => null, 'target_id' => null, 'error' => 'Ingen historik hittades.');
		}
		$cursorResult=pg_query_params($dbh, "SELECT current_edit_id FROM $configSchema.edit_cursor WHERE target_key = $1", array($targetKey));
		$currentEditId=null;
		if ($cursorResult !== false && pg_num_rows($cursorResult) > 0)
		{
			$currentEditId=pg_fetch_result($cursorResult, 0, 0);
		}
		$index=null;
		foreach ($edits as $i => $edit)
		{
			if ($currentEditId !== null && $edit['edit_id'] == $currentEditId)
			{
				$index=$i;
				break;
			}
		}
		if ($direction == 'undo')
		{
			if ($index === null)
			{
				return array('ok' => false, 'target_table' => null, 'target_id' => null, 'error' => 'Det finns inget att ångra.');
			}
			$applyEdit=$edits[$index];
			$data=json_decode($applyEdit['before_data'], true);
			$newEditId=($index > 0) ? $edits[$index - 1]['edit_id'] : null;
		}
		else
		{
			$nextIndex=($index === null) ? 0 : $index + 1;
			if (!isset($edits[$nextIndex]))
			{
				return array('ok' => false, 'target_table' => null, 'target_id' => null, 'error' => 'Det finns inget att göra om.');
			}
			$applyEdit=$edits[$nextIndex];
			$data=json_decode($applyEdit['after_data'], true);
			$newEditId=$applyEdit['edit_id'];
		}
		if (!is_array($data))
		{
			return array('ok' => false, 'target_table' => null, 'target_id' => null, 'error' => 'Historikposten saknar data.');
		}
		$editType=explode(':', $targetKey, 2)[0];
		$editTargetId=$applyEdit['target_id'];
		$editTarget=makeBasicTarget($editType, $editTargetId);
		$targetTable=targetTable($editTarget);
		$idColumn=targetIdColumn($editTarget);
		$sql="UPDATE $configSchema.$targetTable SET";
		$statement=appendUpdatedColumnsToSql($data, $sql);
		$sql=$statement['sql'];
		$params=$statement['params'];
		$params[]=$editTargetId;
		$sql=$sql." WHERE $idColumn = $".count($params);
		$updateResult=pg_query_params($dbh, $sql, $params);
		if ($updateResult === false)
		{
			return array('ok' => false, 'target_table' => null, 'target_id' => null, 'error' => pg_last_error($dbh));
		}
		$canUndo=($newEditId !== null);
		$canRedo=false;
		foreach ($edits as $i => $edit)
		{
			if ($newEditId !== null && $edit['edit_id'] == $newEditId)
			{
				$canRedo=isset($edits[$i + 1]);
				break;
			}
			if ($newEditId === null && $i === 0)
			{
				$canRedo=true;
			}
		}
		pg_query_params($dbh, "INSERT INTO $configSchema.edit_cursor (target_key, current_edit_id, can_undo, can_redo, updated_at) VALUES ($1, $2, $3, $4, now()) ON CONFLICT (target_key) DO UPDATE SET current_edit_id = $2, can_undo = $3, can_redo = $4, updated_at = now()", array($targetKey, $newEditId, $canUndo ? 't' : 'f', $canRedo ? 't' : 'f'));
		return array('ok' => true, 'target_table' => $targetTable, 'target_id' => $editTargetId, 'error' => null);
	}