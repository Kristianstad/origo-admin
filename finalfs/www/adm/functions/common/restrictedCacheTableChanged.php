<?php

    // Rebuilds the restricted layer cache when one of its source tables has changed.
    function restrictedCacheTableChanged($dbh, $table): bool
    {
        if (!in_array($table, array('layers', 'sources', 'services'), true))
        {
            return true;
        }
        return restrictedCacheRefresh($dbh);
    }
