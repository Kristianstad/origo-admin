<?php

    // Takes an associative array and returns all values (and keys) where the key ends with 'Id',
    // with the exception of operation parent keys
    function idPosts($post)
    {
        $idPosts=array_filter($post, function($key) {return (substr($key, -2) == 'Id');}, ARRAY_FILTER_USE_KEY);
        foreach (array('Map', 'Group', 'Classe', 'Infogroup') as $parentType)
        {
            unset($idPosts['from'.$parentType.'Id'], $idPosts['to'.$parentType.'Id']);
        }
        return $idPosts;
    }