<?php

    // Moves the history cursor for $targetKey one step in $direction ('undo' or 'redo') and
    // writes the corresponding configuration snapshot back into the target's table.
    // Returns array('ok' => bool, 'target_table' => string|null, 'target_id' => string|null, 'renames' => list from idRenames() (only on success), 'error' => string|null)
    function applyHistoryNavigation($dbh, $targetKey, $direction)
    {
        require("./constants/configSchema.php");
        if ($direction != 'undo' && $direction != 'redo')
        {
            return array('ok' => false, 'target_table' => null, 'target_id' => null, 'error' => "Okänd riktning: $direction");
        }
        $identityResult=pg_query_params($dbh, "SELECT target_id FROM $configSchema.object_identity WHERE target_key = $1", array($targetKey));
        if ($identityResult === false || pg_num_rows($identityResult) === 0)
        {
            return array('ok' => false, 'target_table' => null, 'target_id' => null, 'error' => 'Historikens objektidentitet saknas.');
        }
        $currentTargetId=pg_fetch_result($identityResult, 0, 'target_id');
        $editsResult=pg_query_params($dbh, "SELECT edit_id, target_table, target_id, before_data, after_data FROM $configSchema.edits WHERE target_key = $1 AND action NOT IN ('delete', 'restored') AND edit_id >= COALESCE((SELECT MAX(edit_id) FROM $configSchema.edits WHERE target_key = $1 AND action IN ('baseline', 'create', 'copy', 'restore')), 0) ORDER BY edit_id ASC", array($targetKey));
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
        $index=($currentEditId === null) ? -1 : null;
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
            if ($index === null || $index < 0 || ($index === 0 && $edits[$index]['action'] !== 'update'))
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
        $editTarget=makeBasicTarget($editType, $currentTargetId);
        $targetTable=targetTable($editTarget);
        $idColumn=targetIdColumn($editTarget);
        $newTargetId=$data[$idColumn] ?? $currentTargetId;
        $renames=array();
        $renameStatements=array();
        if ($newTargetId !== $currentTargetId)
        {
            $configTables=configTables($dbh);
            $renames=idRenames($targetTable, $currentTargetId, $newTargetId, $configTables);
            $renameStatements=sqlForIdChange($renames, $configTables);
        }
        $sql="UPDATE $configSchema.$targetTable SET";
        $statement=appendUpdatedColumnsToSql($data, $sql);
        $sql=$statement['sql'];
        $params=$statement['params'];
        $params[]=$currentTargetId;
        $sql=$sql." WHERE $idColumn = $".count($params);
        if (pg_query($dbh, "BEGIN") === false)
        {
            return array('ok' => false, 'target_table' => null, 'target_id' => null, 'error' => pg_last_error($dbh));
        }
        $updateResult=pg_query_params($dbh, $sql, $params);
        if ($updateResult === false)
        {
            pg_query($dbh, "ROLLBACK");
            return array('ok' => false, 'target_table' => null, 'target_id' => null, 'error' => pg_last_error($dbh));
        }
        foreach ($renameStatements as $renameStatement)
        {
            if (pg_query_params($dbh, $renameStatement['sql'], $renameStatement['params']) === false)
            {
                $error=pg_last_error($dbh);
                pg_query($dbh, "ROLLBACK");
                return array('ok' => false, 'target_table' => null, 'target_id' => null, 'error' => $error);
            }
        }
        $identityUpdate=pg_query_params($dbh, "UPDATE $configSchema.object_identity SET target_id = $2, updated_at = now() WHERE target_key = $1", array($targetKey, $newTargetId));
        if ($identityUpdate === false)
        {
            pg_query($dbh, "ROLLBACK");
            return array('ok' => false, 'target_table' => null, 'target_id' => null, 'error' => pg_last_error($dbh));
        }
        $editsUpdate=pg_query_params($dbh, "UPDATE $configSchema.edits SET target_id = $2 WHERE target_key = $1", array($targetKey, $newTargetId));
        if ($editsUpdate === false)
        {
            pg_query($dbh, "ROLLBACK");
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
        $cursorUpdate=pg_query_params($dbh, "INSERT INTO $configSchema.edit_cursor (target_key, current_edit_id, can_undo, can_redo, updated_at) VALUES ($1, $2, $3, $4, now()) ON CONFLICT (target_key) DO UPDATE SET current_edit_id = $2, can_undo = $3, can_redo = $4, updated_at = now()", array($targetKey, $newEditId, $canUndo ? 't' : 'f', $canRedo ? 't' : 'f'));
        if ($cursorUpdate === false)
        {
            pg_query($dbh, "ROLLBACK");
            return array('ok' => false, 'target_table' => null, 'target_id' => null, 'error' => pg_last_error($dbh));
        }
        if (pg_query($dbh, "COMMIT") === false)
        {
            pg_query($dbh, "ROLLBACK");
            return array('ok' => false, 'target_table' => null, 'target_id' => null, 'error' => pg_last_error($dbh));
        }
        return array('ok' => true, 'target_table' => $targetTable, 'target_id' => $newTargetId, 'renames' => $renames, 'error' => null);
    }