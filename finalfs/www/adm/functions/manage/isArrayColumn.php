<?php

    function isArrayColumn($column)
    {
        require("./constants/arrayColumns.php");
        if (is_string($column) && !empty($column))
        {
            return in_array($column, $arrayColumns);
        }
        else
        {
            die("isArrayColumn($column) failed!");
        }
    }