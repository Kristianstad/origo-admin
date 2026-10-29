<?php

    // Takes a search table id, the query (string) and a variable that is set to true if the cache is missing or damaged.
    // Yields the rows of the table's result cache whose line contains the query, one at a time.
    function searchLoadCache($tableKey, $query, &$needsRefresh)
    {
        $file=searchCacheFile($tableKey);
        $handle=@fopen($file, 'r');
        if ($handle === false)
        {
            $needsRefresh=true;
            return;
        }
        // The lines are JSON, so a row can only match if its line contains the JSON-escaped query. Other lines are never decoded.
        $needle=json_encode($query, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $needle=($needle === false) ? null : substr($needle, 1, -1);
        $lineNumber=0;
        while (($line=fgets($handle)) !== false)
        {
            $lineNumber++;
            if ($needle !== null && mb_stripos($line, $needle) === false)
            {
                continue;
            }
            $row=json_decode(trim($line), true);
            if ($row === null)
            {
                fclose($handle);
                $needsRefresh=true;
                error_log("Search: invalid JSON on line $lineNumber in $file");
                return;
            }
            yield $row;
        }
        fclose($handle);
    }
