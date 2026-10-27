<?php

    // Takes the renames from idRenames() and the configTables array (state before the rename). Returns the statements, in run order,
    // that complete the rename of the first entry (whose own row is updated by the caller): the derived rows with their history
    // identity, and every column in the configuration that refers to a renamed id.
    function sqlForIdChange($renames, $configTables)
    {
        require("./constants/configSchema.php");
        $statements=array();
        foreach ($renames as $i=>[$table, $oldId, $newId])
        {
            if ($i > 0)
            {
                $pkColumn=pkColumnOfTable($table);
                $statements[]=array('sql'=>"UPDATE $configSchema.$table SET $pkColumn = $1 WHERE $pkColumn = $2", 'params'=>array($newId, $oldId));
                $statements[]=array('sql'=>"UPDATE $configSchema.object_identity SET target_id = $1, updated_at = now() WHERE target_id = $2 AND target_table = $3", 'params'=>array($newId, $oldId, $table));
                $statements[]=array('sql'=>"UPDATE $configSchema.edits SET target_id = $1 WHERE target_id = $2 AND target_table = $3", 'params'=>array($newId, $oldId, $table));
            }
            foreach ($configTables as $referencingTable=>$rows)
            {
                foreach (array_keys($rows[0] ?? array()) as $column)
                {
                    if (idReferencedTable($referencingTable, $column, $configTables) !== $table)
                    {
                        continue;
                    }
                    if (isArrayColumn($column))
                    {
                        $sql="UPDATE $configSchema.$referencingTable SET $column = array_replace($column, $2::varchar, $1::varchar) WHERE $2::varchar = ANY($column)";
                    }
                    else
                    {
                        $sql="UPDATE $configSchema.$referencingTable SET $column = $1 WHERE $column = $2";
                    }
                    $statements[]=array('sql'=>$sql, 'params'=>array($newId, $oldId));
                }
            }
        }
        return $statements;
    }
