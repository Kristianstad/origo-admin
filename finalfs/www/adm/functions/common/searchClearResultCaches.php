<?php

    // Deletes the result cache files of all search tables. The next search of a table runs live and rebuilds its cache after the response.
    function searchClearResultCaches()
    {
        foreach (glob(searchStorageDir().'results_*.jsonl') ?: array() as $file)
        {
            @unlink($file);
        }
    }
