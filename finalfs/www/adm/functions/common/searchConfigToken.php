<?php

    // Takes 'desired' or 'current'. Returns the version token stored for the search configuration cache, or null if there is none.
    function searchConfigToken($name)
    {
        $token=@file_get_contents(searchStorageDir().'config.'.$name);
        return ($token !== false && preg_match('/^[0-9a-f]{16}$/', $token)) ? $token : null;
    }
