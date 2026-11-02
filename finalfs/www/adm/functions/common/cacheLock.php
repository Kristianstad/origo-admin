<?php

    // Takes an exclusive file lock, waiting up to the requested number of seconds. Returns false if unavailable.
    function cacheLock($path, $waitSeconds=0)
    {
        $lock=@fopen($path, 'c');
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
        return $lock;
    }
