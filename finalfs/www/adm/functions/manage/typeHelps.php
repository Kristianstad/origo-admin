<?php

    // Takes a type (string) and a helps array.
    // Returns an array of all helps available for the given type
    function typeHelps($type, $helps)
    {
        $typeHelps=array();
        foreach ($helps as $helpId)
        {
            $idParts=explode(':', $helpId, 2);
            if ($idParts[0] == $type)
            {
                $typeHelps[]=$idParts[1];
            }
        }
        return $typeHelps;
    }