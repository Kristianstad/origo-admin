<?php

    // Takes hits (array of rows with NAMN) and a limit. Spreads the hits over as many different base names as possible,
    // so that many hits with the same name (f ex all units of one property) do not crowd out the rest.
    function searchDiversifyRows($rows, $limit)
    {
        if (count($rows) <= $limit)
        {
            return $rows;
        }
        $grouped=array();
        foreach ($rows as $row)
        {
            $grouped[searchBaseName($row['NAMN'] ?? '')][]=$row;
        }
        $maxPerGroup=max(1, (int) ceil($limit / count($grouped)));
        if (count($grouped) >= $limit)
        {
            $maxPerGroup=1;
        }
        $result=array();
        foreach ($grouped as $groupRows)
        {
            $result=array_merge($result, array_slice($groupRows, 0, $maxPerGroup));
            if (count($result) >= $limit)
            {
                break;
            }
        }
        return array_slice($result, 0, $limit);
    }
