<?php

    // Takes a search table id, its cache time in seconds and its max cache age factor (float or null, default 2).
    // Returns 0 if the result cache is fresh (or not used), 1 if it is old but usable and should be rebuilt, and 2 if it is missing or too old.
    function searchShouldRefreshCache($tableKey, $ttl, $maxAgeFactor)
    {
        if ($ttl === 0)
        {
            return 0;
        }
        $age=searchCacheAge($tableKey);
        if ($age === null || $age > $ttl * ($maxAgeFactor ?? 2.0))
        {
            return 2;
        }
        return $age > $ttl ? 1 : 0;
    }
