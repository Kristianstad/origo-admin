<?php

    // Rebuilds the search configuration cache from the database if it is missing or stale. Only one process rebuilds at a time.
    // Takes a connection to the configuration database (optional, dbh() is used if none is given), the number of seconds to wait for
    // a rebuild in progress (optional) and whether to skip the attempt if a recent one was made (optional, avoids hammering a failing database).
    // The old cache stays in use until the new one is completely written. Returns true if the cache is up to date afterwards.
    function searchConfigRefresh($dbh=null, $waitSeconds=0, $honorBackoff=false)
    {
        $dir=searchStorageDir();
        if ($honorBackoff && time() - (int) @filemtime($dir.'config.attempt') < 30)
        {
            return false;
        }
        $lock=fopen($dir.'config.lock', 'c');
        if ($lock === false)
        {
            return false;
        }
        $deadline=microtime(true) + $waitSeconds;
        while (!flock($lock, LOCK_EX | LOCK_NB))
        {
            if (microtime(true) >= $deadline)
            {
                fclose($lock);
                return false;
            }
            usleep(50000);
        }
        $current=searchConfigToken('current');
        $token=searchConfigToken('desired') ?? $current ?? bin2hex(random_bytes(8));
        $upToDate=($current === $token && is_file($dir.'config.'.$token.'.php'));
        if (!$upToDate)
        {
            $openedHere=($dbh === null);
            try
            {
                touch($dir.'config.attempt');
                if ($openedHere)
                {
                    $dbh=dbh();
                }
                $models=searchConfigFromDb($dbh);
                $upToDate=($models !== null)
                    && searchWriteFile($dir.'config.'.$token.'.php', "<?php\nreturn ".var_export($models, true).";\n")
                    && searchWriteFile($dir.'config.current', $token);
                if ($upToDate)
                {
                    foreach (glob($dir.'config.*.php') ?: array() as $file)
                    {
                        if (basename($file) !== 'config.'.$token.'.php')
                        {
                            @unlink($file);
                        }
                    }
                    @unlink($dir.'config.attempt');
                }
            }
            catch (Throwable $exception)
            {
                error_log('Search: could not rebuild the configuration cache: '.$exception->getMessage());
            }
            finally
            {
                if ($openedHere && $dbh !== null)
                {
                    pg_close($dbh);
                }
            }
        }
        flock($lock, LOCK_UN);
        fclose($lock);
        return $upToDate;
    }
