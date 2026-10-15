<?php

    // Takes an id and a table name as parameters.
    // Returns a sql-query string for creating a new row in the table with given id as primary key
    function insertIdSql($id, $tableName)
    {
        require("./constants/configSchema.php");
        $tablePkColumn=pkColumnOfTable($tableName);
        $columns=$tablePkColumn;
        $values='$1';
        if ($tableName === 'maps')
        {
            $columns.=', changed';
            $values.=', true';
        }
        return array(
            'sql' => "INSERT INTO $configSchema.$tableName($columns) VALUES ($values)",
            'params' => array($id)
        );
    }