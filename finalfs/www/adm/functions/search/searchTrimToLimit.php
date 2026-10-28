<?php

    // Takes hits from all search tables (array of rows with TYPE) and the model's limit. If there are more hits than the limit,
    // each TYPE keeps a share proportional to its number of hits (at least one). Returns the kept hits in their original order.
    function searchTrimToLimit($results, $limit)
    {
        if (count($results) <= $limit)
        {
            return $results;
        }
        $counts=array();
        foreach ($results as $result)
        {
            $type=$result['TYPE'] ?? 'unknown';
            $counts[$type]=($counts[$type] ?? 0) + 1;
        }

        $factor=$limit / count($results);
        $kept=array();
        $remaining=$limit;
        foreach ($counts as $type => $count)
        {
            $kept[$type]=max(1, min((int) round($count * $factor), $count));
            $remaining-=$kept[$type];
        }

        if ($remaining > 0)
        {
            arsort($counts);
            foreach ($counts as $type => $count)
            {
                if ($remaining <= 0)
                {
                    break;
                }
                if ($kept[$type] < $count)
                {
                    $kept[$type]++;
                    $remaining--;
                }
            }
        }
        elseif ($remaining < 0)
        {
            arsort($kept);
            foreach ($kept as $type => &$keep)
            {
                if ($remaining >= 0)
                {
                    break;
                }
                if ($keep > 1)
                {
                    $keep--;
                    $remaining++;
                }
            }
            unset($keep);
        }

        $trimmed=array();
        foreach ($results as $result)
        {
            $type=$result['TYPE'] ?? 'unknown';
            if ($kept[$type] > 0)
            {
                $trimmed[]=$result;
                $kept[$type]--;
            }
        }
        return $trimmed;
    }
