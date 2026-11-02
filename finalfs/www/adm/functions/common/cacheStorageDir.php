<?php

    // Returns the directory (with trailing slash) used for application caches, created with owner-only access if missing.
    function cacheStorageDir()
    {
        $dir=rtrim(sys_get_temp_dir(), '/\\').'/origo-cache/';
        if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir))
        {
            error_log("Cache: could not create $dir");
        }
        return $dir;
    }
