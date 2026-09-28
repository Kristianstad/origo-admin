<?php

function restoreDeletedEdit($dbh, $editId)
{
	require("./constants/configSchema.php");
	if (pg_query($dbh, "BEGIN") === false)
	{
		return array('ok'=>false, 'error'=>pg_last_error($dbh));
	}
	$editResult=pg_query_params($dbh, "SELECT target_key, target_table, target_id, before_data FROM $configSchema.edits WHERE edit_id = $1 AND action = 'delete' FOR UPDATE", array($editId));
	if ($editResult === false || pg_num_rows($editResult) != 1)
	{
		$error=($editResult === false) ? pg_last_error($dbh) : 'Den valda ändringen är inte en radering.';
		pg_query($dbh, "ROLLBACK");
		return array('ok'=>false, 'error'=>$error);
	}
	$edit=pg_fetch_assoc($editResult);
	$targetKey=$edit['target_key'];
	$targetId=$edit['target_id'];
	$targetType=explode(':', $targetKey, 2)[0];
	if ($targetType === '' || objectHistoryKey(makeBasicTarget($targetType, $targetId)) !== $targetKey)
	{
		pg_query($dbh, "ROLLBACK");
		return array('ok'=>false, 'error'=>'Raderingsposten har en ogiltig objektnyckel.');
	}
	$target=makeBasicTarget($targetType, $targetId);
	$targetTable=targetTable($target);
	$idColumn=targetIdColumn($target);
	$data=json_decode($edit['before_data'], true);
	if ($edit['target_table'] !== $targetTable || !is_array($data) || !isset($data[$idColumn]) || (string)$data[$idColumn] !== (string)$targetId)
	{
		pg_query($dbh, "ROLLBACK");
		return array('ok'=>false, 'error'=>'Raderingsposten saknar ett giltigt objektsnapshot.');
	}
	$laterResult=pg_query_params($dbh, "SELECT 1 FROM $configSchema.edits WHERE target_key = $1 AND edit_id > $2 LIMIT 1", array($targetKey, $editId));
	if ($laterResult === false || pg_num_rows($laterResult) > 0)
	{
		$error=($laterResult === false) ? pg_last_error($dbh) : 'Endast den senaste historikposten kan återställas.';
		pg_query($dbh, "ROLLBACK");
		return array('ok'=>false, 'error'=>$error);
	}
	$cursorResult=pg_query_params($dbh, "SELECT current_edit_id FROM $configSchema.edit_cursor WHERE target_key = $1 FOR UPDATE", array($targetKey));
	if ($cursorResult === false || pg_num_rows($cursorResult) != 1 || pg_fetch_result($cursorResult, 0, 0) != $editId)
	{
		$error=($cursorResult === false) ? pg_last_error($dbh) : 'Historikmarkören pekar inte på den valda raderingen.';
		pg_query($dbh, "ROLLBACK");
		return array('ok'=>false, 'error'=>$error);
	}
	$existsResult=pg_query_params($dbh, "SELECT 1 FROM $configSchema.$targetTable WHERE $idColumn = $1 LIMIT 1", array($targetId));
	if ($existsResult === false || pg_num_rows($existsResult) > 0)
	{
		$error=($existsResult === false) ? pg_last_error($dbh) : 'Ett objekt med samma id finns redan.';
		pg_query($dbh, "ROLLBACK");
		return array('ok'=>false, 'error'=>$error);
	}
	$columns=array_keys($data);
	foreach ($columns as $column)
	{
		if (!preg_match('/^[a-z_][a-z0-9_]*$/i', $column))
		{
			pg_query($dbh, "ROLLBACK");
			return array('ok'=>false, 'error'=>'Objektsnapshotet innehåller ett ogiltigt kolumnnamn.');
		}
	}
	$placeholders=array();
	$params=array();
	foreach ($data as $value)
	{
		$params[]=$value;
		$placeholders[]='$'.count($params);
	}
	$quotedColumns=array_map(function ($column) { return '"'.$column.'"'; }, $columns);
	$insertSql="INSERT INTO $configSchema.$targetTable (".implode(', ', $quotedColumns).") VALUES (".implode(', ', $placeholders).")";
	$insertResult=pg_query_params($dbh, $insertSql, $params);
	if ($insertResult === false)
	{
		$error=pg_last_error($dbh);
		pg_query($dbh, "ROLLBACK");
		return array('ok'=>false, 'error'=>$error);
	}
	$restoredResult=pg_query_params($dbh, "UPDATE $configSchema.edits SET action = 'restored' WHERE edit_id = $1 AND action = 'delete'", array($editId));
	if ($restoredResult === false)
	{
		$error=pg_last_error($dbh);
		pg_query($dbh, "ROLLBACK");
		return array('ok'=>false, 'error'=>$error);
	}
	$previousResult=pg_query_params($dbh, "SELECT edit_id FROM $configSchema.edits WHERE target_key = $1 AND edit_id < $2 AND action NOT IN ('delete', 'restored') AND edit_id >= COALESCE((SELECT MAX(edit_id) FROM $configSchema.edits WHERE target_key = $1 AND edit_id < $2 AND action IN ('baseline', 'create', 'copy', 'restore')), 0) ORDER BY edit_id DESC LIMIT 1", array($targetKey, $editId));
	if ($previousResult === false)
	{
		$error=pg_last_error($dbh);
		pg_query($dbh, "ROLLBACK");
		return array('ok'=>false, 'error'=>$error);
	}
	$previousEditId=(pg_num_rows($previousResult) > 0) ? pg_fetch_result($previousResult, 0, 0) : null;
	if ($previousEditId === null)
	{
		if (!recordHistoryEdit($dbh, $target, 'restore', $data, $data))
		{
			$error=pg_last_error($dbh);
			pg_query($dbh, "ROLLBACK");
			return array('ok'=>false, 'error'=>$error);
		}
	}
	else
	{
		$countResult=pg_query_params($dbh, "SELECT COUNT(*) FROM $configSchema.edits WHERE target_key = $1 AND edit_id <= $2 AND action NOT IN ('delete', 'restored') AND edit_id >= COALESCE((SELECT MAX(edit_id) FROM $configSchema.edits WHERE target_key = $1 AND edit_id <= $2 AND action IN ('baseline', 'create', 'copy', 'restore')), 0)", array($targetKey, $previousEditId));
		if ($countResult === false)
		{
			$error=pg_last_error($dbh);
			pg_query($dbh, "ROLLBACK");
			return array('ok'=>false, 'error'=>$error);
		}
		$canUndo=(pg_fetch_result($countResult, 0, 0) > 1);
		$cursorUpdateResult=pg_query_params($dbh, "UPDATE $configSchema.edit_cursor SET current_edit_id = $2, can_undo = $3, can_redo = false, updated_at = now() WHERE target_key = $1", array($targetKey, $previousEditId, $canUndo ? 't' : 'f'));
		if ($cursorUpdateResult === false)
		{
			$error=pg_last_error($dbh);
			pg_query($dbh, "ROLLBACK");
			return array('ok'=>false, 'error'=>$error);
		}
	}
	if (pg_query($dbh, "COMMIT") === false)
	{
		$error=pg_last_error($dbh);
		pg_query($dbh, "ROLLBACK");
		return array('ok'=>false, 'error'=>$error);
	}
	return array('ok'=>true, 'target'=>$target, 'target_id'=>$targetId, 'error'=>null);
}