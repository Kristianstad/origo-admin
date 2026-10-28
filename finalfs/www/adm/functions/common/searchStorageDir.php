<?php

    // Returns the directory (with trailing slash) where the search module keeps its caches, created with owner-only access if missing
    function searchStorageDir()
    {
        $dir=rtrim(sys_get_temp_dir(), '/\\').'/origo-search/';
        if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir))
        {
            error_log("Search: could not create $dir");
        }
        return $dir;
    }
