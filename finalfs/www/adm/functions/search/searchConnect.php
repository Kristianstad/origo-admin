<?php

    // Takes a pg_connect connection string and whether to use a persistent connection. Returns the connection (UTF8) or false.
    function searchConnect($connectionString, $usePconnect)
    {
        $dbh=$usePconnect ? @pg_pconnect($connectionString) : @pg_connect($connectionString, PGSQL_CONNECT_FORCE_NEW);
        if ($dbh === false)
        {
            return false;
        }
        pg_set_client_encoding($dbh, 'UTF8');
        return $dbh;
    }
