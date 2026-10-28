<?php

    // Takes a connection to the configuration database and the name of a configuration table that has just been changed.
    // If the search configuration depends on that table, the search configuration cache is marked stale and rebuilt.
    function searchConfigTableChanged($dbh, $table)
    {
        if (in_array($table, array('searchmodels', 'searchtables', 'databases'), true))
        {
            searchConfigMarkStale();
            searchConfigRefresh($dbh);
        }
    }
