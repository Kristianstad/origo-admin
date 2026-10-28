<?php

    // Takes a hit name. Removes a trailing unit or number (f ex "JULEBODA 1:2" -> "JULEBODA"), so that hits of the same kind can be recognized.
    function searchBaseName($name)
    {
        if (empty($name))
        {
            return '';
        }
        $name=preg_replace('/\s*\d+:\d+$/', '', $name);
        // Requires whitespace before the number, so plans like "1290K-P02/1" are left alone
        $name=preg_replace('/\s+\d+[A-Za-z]*$/', '', $name);
        return trim($name);
    }
