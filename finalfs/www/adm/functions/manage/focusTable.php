<?php

    // Takes an array of idPosts (see idposts.php) and returns the name of the table that has focus
    function focusTable($idPosts)
    {
        if (isset($idPosts['mapId']))
        {
            $focusTable='maps';
        }
        elseif (isset($idPosts['databaseId']))
        {
            $focusTable='databases';
        }
        elseif (isset($idPosts['schemaId']))
        {
            $focusTable='schemas';
        }
        elseif (isset($idPosts['groupId']))
        {
            $focusTable='groups';
        }
        elseif (isset($idPosts['classeId']))
        {
            $focusTable='classes';
        }
        elseif (isset($idPosts['infogroupId']))
        {
            $focusTable='infogroups';
        }
        else
        {
            $firstKey=key($idPosts);
            if (isset($firstKey))
            {
                $focusTable=substr($firstKey, 0, -2).'s';
            }
            else
            {
                $focusTable=null;
            }
        }
        return $focusTable;
    }