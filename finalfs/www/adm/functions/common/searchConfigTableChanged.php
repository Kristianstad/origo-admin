<?php

    // Takes a connection to the configuration database, the name of a configuration table that has just been changed and the id renames
    // made (optional, see idRenames()). If the search configuration depends on the change, its cache is marked stale and rebuilt.
    // It depends on searchmodels, searchtables and databases, and on table ids: a search table refers to one, and a table id
    // changes when its schema or database is renamed.
    function searchConfigTableChanged($dbh, $table, $renames=array())
    {
        if (in_array($table, array('searchmodels', 'searchtables', 'databases'), true) || in_array('tables', array_column($renames, 0), true))
        {
            searchConfigMarkStale();
            searchConfigRefresh($dbh);
        }
    }
