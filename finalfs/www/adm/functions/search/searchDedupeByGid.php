<?php

    // Takes hits (array of rows with GID). Returns them without rows that repeat an earlier GID. Rows without GID are kept.
    function searchDedupeByGid($rows)
    {
        $seen=array();
        $unique=array();
        foreach ($rows as $row)
        {
            $gid=$row['GID'] ?? null;
            if ($gid !== null)
            {
                if (isset($seen[$gid]))
                {
                    continue;
                }
                $seen[$gid]=true;
            }
            $unique[]=$row;
        }
        return $unique;
    }
