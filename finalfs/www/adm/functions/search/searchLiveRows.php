<?php

    // Takes a search table config (array), the query (string) and an array of open connections keyed by connection string (extended as needed).
    // Searches the table in its database. Returns the hits, or null if the connection or the query failed.
    function searchLiveRows($tableConfig, $query, &$connections)
    {
        $connectionString=$tableConfig['connectionString'];
        if (!array_key_exists($connectionString, $connections))
        {
            $connections[$connectionString]=searchConnect($connectionString, $tableConfig['usePconnect']);
        }
        $dbh=$connections[$connectionString];
        if (!($dbh instanceof \PgSql\Connection))
        {
            error_log("Search: could not connect for search table '{$tableConfig['tableKey']}'");
            return null;
        }
        $statement=searchSql($dbh, $query, $tableConfig);
        $result=pg_query_params($dbh, $statement['sql'], $statement['params']);
        if ($result === false)
        {
            error_log("Search: SQL error for search table '{$tableConfig['tableKey']}': ".pg_last_error($dbh));
            return null;
        }
        $rows=searchDedupeByGid(pg_fetch_all($result) ?: array());
        pg_free_result($result);

        if ($tableConfig['searchMode'] === 'intelligent')
        {
            usort($rows, function ($a, $b) {
                $relevanceA=(float) ($a['RELEVANCE'] ?? 0);
                $relevanceB=(float) ($b['RELEVANCE'] ?? 0);
                if ($relevanceA !== $relevanceB)
                {
                    return $relevanceB <=> $relevanceA;
                }
                return strnatcasecmp($a['NAMN'] ?? '', $b['NAMN'] ?? '');
            });
        }
        $rows=searchTrimRows($rows, $tableConfig['limit'], $tableConfig['groupByTitle']);
        foreach ($rows as &$row)
        {
            unset($row['RELEVANCE']);
        }
        unset($row);
        return $rows;
    }
