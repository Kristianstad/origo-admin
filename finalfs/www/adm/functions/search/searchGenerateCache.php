<?php

    // Takes a search table config (array) and a connection to its database. Writes all rows of the table to its result cache,
    // as one JSON object per line, via a temporary file so that readers never see a partly written cache. Returns true on success.
    function searchGenerateCache($tableConfig, $dbh)
    {
        $statement=searchSql($dbh, '%', array_merge($tableConfig, array('limit' => null)));
        $result=pg_query_params($dbh, $statement['sql'], $statement['params']);
        if (!$result)
        {
            error_log("Search: SQL error when building the cache of '{$tableConfig['tableKey']}': ".pg_last_error($dbh));
            return false;
        }
        $finalFile=searchCacheFile($tableConfig['tableKey']);
        $tempFile=$finalFile.'.tmp';
        $handle=@fopen($tempFile, 'wb');
        if ($handle === false)
        {
            pg_free_result($result);
            return false;
        }
        while ($row=pg_fetch_assoc($result))
        {
            unset($row['RELEVANCE']);
            $line=json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($line === false)
            {
                fclose($handle);
                @unlink($tempFile);
                pg_free_result($result);
                error_log("Search: json_encode failed when building the cache of '{$tableConfig['tableKey']}'");
                return false;
            }
            fwrite($handle, $line."\n");
        }
        pg_free_result($result);
        if (!fclose($handle) || filesize($tempFile) < 10 || !rename($tempFile, $finalFile))
        {
            @unlink($tempFile);
            error_log("Search: could not write the cache of '{$tableConfig['tableKey']}'");
            return false;
        }
        return true;
    }
