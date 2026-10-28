<?php

    // Takes a search table id and a variable that is set to true if the cache is missing or damaged.
    // Returns the rows of the table's result cache.
    function searchLoadCache($tableKey, &$needsRefresh)
    {
        $file=searchCacheFile($tableKey);
        $handle=@fopen($file, 'r');
        if ($handle === false)
        {
            $needsRefresh=true;
            return array();
        }
        $rows=array();
        $lineNumber=0;
        while (($line=fgets($handle)) !== false)
        {
            $lineNumber++;
            $row=json_decode(trim($line), true);
            if ($row === null)
            {
                fclose($handle);
                $needsRefresh=true;
                error_log("Search: invalid JSON on line $lineNumber in $file");
                return array();
            }
            $rows[]=$row;
        }
        fclose($handle);
        return $rows;
    }
