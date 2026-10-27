<?php

    // Takes a config table name, the old and new id of one of its rows, and the configTables array.
    // Returns a list of array(table, oldId, newId): the row itself first, then the rows whose ids are
    // built from it (see idPrefixChildren.php) with the new id prefix.
    function idRenames($table, $oldId, $newId, $configTables)
    {
        require("./constants/idPrefixChildren.php");
        $renames=array(array($table, $oldId, $newId));
        $prefix=$oldId.'.';
        foreach (($idPrefixChildren[$table] ?? array()) as $childTable)
        {
            foreach (array_column($configTables[$childTable], pkColumnOfTable($childTable)) as $childId)
            {
                if (str_starts_with($childId, $prefix))
                {
                    $renames[]=array($childTable, $childId, $newId.substr($childId, strlen($oldId)));
                }
            }
        }
        return $renames;
    }
