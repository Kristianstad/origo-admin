<?php

    // Takes an id, the primary key- (id-) column, and a table (config) as parameters
    // Returns true if the given id is unique in the table, else returns false
    function isIdUniqueInTable($id, $tablePkColumn, $table)
    {
        return !in_array($id, array_column($table, $tablePkColumn));
    }