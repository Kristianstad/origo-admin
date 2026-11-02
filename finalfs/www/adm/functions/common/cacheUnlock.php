<?php

    // Releases and closes a lock returned by cacheLock().
    function cacheUnlock($lock)
    {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
