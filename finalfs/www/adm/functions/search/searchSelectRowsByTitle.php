<?php

    // Takes hits (array of rows with TITLE) and a limit. Picks up to limit hits spread evenly over the TITLE categories
    // and returns them grouped by category, the category with the best hit first.
    function searchSelectRowsByTitle($rows, $limit)
    {
        $buckets=array();
        foreach ($rows as $row)
        {
            $buckets[(string) ($row['TITLE'] ?? '')][]=$row;
        }
        foreach ($buckets as $key => $bucketRows)
        {
            $buckets[$key]=searchDiversifyRows($bucketRows, $limit);
        }

        $take=array_fill_keys(array_keys($buckets), 0);
        $total=0;
        do
        {
            $progress=false;
            foreach ($buckets as $key => $bucketRows)
            {
                if ($total >= $limit)
                {
                    break 2;
                }
                if ($take[$key] < count($bucketRows))
                {
                    $take[$key]++;
                    $total++;
                    $progress=true;
                }
            }
        }
        while ($progress);

        $result=array();
        foreach ($buckets as $key => $bucketRows)
        {
            $result=array_merge($result, array_slice($bucketRows, 0, $take[$key]));
        }
        return $result;
    }
