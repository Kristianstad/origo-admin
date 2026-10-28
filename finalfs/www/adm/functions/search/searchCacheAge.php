<?php

    // Takes a search table id. Returns the age in seconds of its result cache, or null if there is none.
    function searchCacheAge($tableKey)
    {
        $modified=@filemtime(searchCacheFile($tableKey));
        return $modified === false ? null : time() - $modified;
    }
