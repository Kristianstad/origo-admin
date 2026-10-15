<?php

    function pgNewsArray(&$dbh)
    {
        require("./constants/configSchema.php");
        if (!$dbh)
        {
            die("Error in connection: " . pg_last_error());
        }
        $result = pg_query($dbh, 'SELECT * FROM '.$configSchema.'.news ORDER BY "date" DESC');
        return pg_fetch_all($result);
        pg_free_result($result);
    }