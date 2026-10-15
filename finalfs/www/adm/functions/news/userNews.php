<?php

    function userNews($username, $pgNewsArray)
    {
        $userNews=array();
        foreach ($pgNewsArray as $row)
        {
            $deletes=pgArrayToPhp($row['deletes']);
            if (!in_array($username, $deletes))
            {
                $userNews[]=array('new_id'=>$row['new_id'], 'abstract'=>$row['abstract'], 'text'=>$row['text'], 'reads'=>pgArrayToPhp($row['reads']), 'deletes'=>$deletes, 'date'=>$row['date']);
            }
        }
        return $userNews;
    }