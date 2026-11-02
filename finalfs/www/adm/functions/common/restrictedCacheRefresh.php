<?php

    // Rebuilds the restricted layer cache atomically. A failed rebuild leaves the previous cache untouched.
    function restrictedCacheRefresh($dbh=null): bool
    {
        $dir=cacheStorageDir();
        $lock=cacheLock($dir.'restricted.lock', 5);
        if ($lock === false)
        {
            error_log('Restricted layer cache: could not acquire rebuild lock');
            return false;
        }
        $openedHere=($dbh === null);
        try
        {
            if ($openedHere)
            {
                $dbh=dbh();
            }
            $restrictedLayers=restrictedLayersFromConfig(configTables($dbh));
            $token=bin2hex(random_bytes(8));
            $cacheFile=$dir.'restricted.'.$token.'.php';
            if (!cacheWriteFile($cacheFile, "<?php\nreturn ".var_export($restrictedLayers, true).";\n"))
            {
                error_log('Restricted layer cache: could not write cache file');
                return false;
            }
            if (!cacheWriteFile($dir.'restricted.current', $token))
            {
                error_log('Restricted layer cache: could not update cache pointer');
                return false;
            }
            foreach (glob($dir.'restricted.*.php') ?: array() as $file)
            {
                if ($file !== $cacheFile)
                {
                    @unlink($file);
                }
            }
            return true;
        }
        catch (Throwable $exception)
        {
            error_log('Restricted layer cache: rebuild failed: '.$exception->getMessage());
            return false;
        }
        finally
        {
            if ($openedHere && $dbh instanceof \PgSql\Connection)
            {
                pg_close($dbh);
            }
            cacheUnlock($lock);
        }
    }
