<?php

    function selectNew($userNews, $newId)
    {
        foreach ($userNews as $row)
        {
            if ($row['new_id'] == $newId)
            {
                return $row;
                break;
            }
        }
    }