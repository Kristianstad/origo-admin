<?php

    // Takes a search table id. Returns the path of its result cache file.
    function searchCacheFile($tableKey)
    {
        // The hash keeps ids that differ only in characters replaced below apart
        return cacheStorageDir().'results_'.preg_replace('/[^a-zA-Z0-9_]/', '_', $tableKey).'_'.substr(md5($tableKey), 0, 8).'.jsonl';
    }
