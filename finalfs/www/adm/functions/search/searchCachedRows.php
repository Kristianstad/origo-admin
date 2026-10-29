<?php

    // Takes a search table config (array), the query (string) and a variable that is set to true if the table's result cache is missing or damaged.
    // Returns the hits from the table's result cache, ranked by how well the name matches the query.
    function searchCachedRows($tableConfig, $query, &$needsRefresh)
    {
        $lowerQuery=mb_strtolower($query);
        $intelligent=($tableConfig['searchMode'] === 'intelligent');
        $scored=array();
        $damaged=false;
        foreach (searchLoadCache($tableConfig['tableKey'], $query, $damaged) as $row)
        {
            $name=$row['NAMN'] ?? '';
            unset($row['RELEVANCE']);
            // Quick pre-filter, skips the expensive scoring if the name does not contain the query at all
            if (mb_stripos($name, $query) === false)
            {
                continue;
            }
            if ($intelligent)
            {
                $lowerName=mb_strtolower($name);
                $score=200;
                if (mb_strpos($lowerName, $lowerQuery) === 0)
                {
                    $score+=500;
                }
                if (mb_strpos(' '.$lowerName, ' '.$lowerQuery) !== false)
                {
                    $score+=250;
                }
                // Short queries followed by a space (like "håsl ") get a little extra
                if (mb_strlen($lowerQuery) <= 6 && mb_strpos($lowerName, $lowerQuery.' ') === 0)
                {
                    $score+=150;
                }
            }
            else
            {
                $score=searchCacheMatchScore($name, $query);
            }
            if ($score > 0)
            {
                $scored[]=array('row' => $row, 'score' => $score);
            }
        }
        if ($damaged)
        {
            $needsRefresh=true;
            return array();
        }
        usort($scored, function ($a, $b) {
            if ($a['score'] !== $b['score'])
            {
                return $b['score'] <=> $a['score'];
            }
            return strnatcasecmp($a['row']['NAMN'] ?? '', $b['row']['NAMN'] ?? '');
        });
        return searchTrimRows(array_column($scored, 'row'), $tableConfig['limit'], $tableConfig['groupByTitle']);
    }
