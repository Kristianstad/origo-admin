<?php

    // Takes the search table configs (array) of a model. Rebuilds the result cache of every table whose cache is old or missing.
    // Only one process rebuilds at a time; others return at once.
    function searchRebuildCaches($tableConfigs)
    {
        $lock=fopen(searchStorageDir().'results.lock', 'c');
        if ($lock === false)
        {
            return;
        }
        if (!flock($lock, LOCK_EX | LOCK_NB))
        {
            fclose($lock);
            return;
        }
        try
        {
            $byConnection=array();
            foreach ($tableConfigs as $tableConfig)
            {
                if ($tableConfig['ttl'] > 0)
                {
                    $byConnection[$tableConfig['connectionString']][]=$tableConfig;
                }
            }
            foreach ($byConnection as $connectionString => $tables)
            {
                $dbh=searchConnect($connectionString, $tables[0]['usePconnect']);
                if ($dbh === false)
                {
                    error_log("Search: could not connect to rebuild the result cache of '{$tables[0]['tableKey']}'");
                    continue;
                }
                foreach ($tables as $tableConfig)
                {
                    if (searchShouldRefreshCache($tableConfig['tableKey'], $tableConfig['ttl'], $tableConfig['maxCacheAgeFactor']) > 0)
                    {
                        searchGenerateCache($tableConfig, $dbh);
                    }
                }
                pg_close($dbh);
            }
        }
        catch (Throwable $exception)
        {
            error_log('Search: rebuilding the result caches failed: '.$exception->getMessage());
        }
        finally
        {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
