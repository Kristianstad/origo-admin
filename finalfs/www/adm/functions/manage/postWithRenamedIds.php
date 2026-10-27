<?php

    // Takes a post array and the renames from idRenames(). Returns the post where every selected id
    // (<type>Id, and the <type>Ids chains) that was renamed is replaced by its new id.
    function postWithRenamedIds($post, $renames)
    {
        foreach ($renames as [$table, $oldId, $newId])
        {
            $idKey=tableType($table).'Id';
            if (isset($post[$idKey]) && $post[$idKey] === $oldId)
            {
                $post[$idKey]=$newId;
            }
            if (isset($post[$idKey.'s']))
            {
                $chain=explode(',', $post[$idKey.'s']);
                $post[$idKey.'s']=implode(',', array_replace($chain, array_fill_keys(array_keys($chain, $oldId, true), $newId)));
            }
        }
        return $post;
    }
