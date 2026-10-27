<?php

    // Takes a config table name, one of its columns and the configTables array. Returns the name of the config table
    // whose ids the column holds, or null. An array column refers to the table named like the column (or its alias in
    // tableAliases.php), a scalar column to the table of the type named like the column (f ex. source -> sources).
    function idReferencedTable($table, $column, $configTables)
    {
        require("./constants/idReferenceOverrides.php");
        require("./constants/tableAliases.php");
        if (array_key_exists($table.'.'.$column, $idReferenceOverrides))
        {
            return $idReferenceOverrides[$table.'.'.$column];
        }
        $referencedTable=isArrayColumn($column) ? ($tableAliases[$column] ?? $column) : typeTableName($column);
        return isset($configTables[$referencedTable]) ? $referencedTable : null;
    }
