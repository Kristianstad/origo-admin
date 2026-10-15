<?php

    // Takes a pg_connect connection string or, if none is given, reads constant from dbhConnectionString.php. Returns database handle.
    function dbh($dbhConnectionString=null)
    {
        if (!isset($dbhConnectionString))
        {
            require("./constants/dbhConnectionString.php");
        }
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $dbh = @pg_connect($dbhConnectionString, PGSQL_CONNECT_FORCE_NEW);
            if ($dbh !== false) {
                return $dbh;
            }
            usleep(500000);
        }
        error_log('PostgreSQL connection failed after 3 attempts');
        http_response_code(503);
        echo '{"save_status":"Database unavailable"}';
        exit;
    }