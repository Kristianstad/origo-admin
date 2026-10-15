<?php

    // Takes an associative array and returns the first value where the key ends with 'Button' (should only be one)
    function postButton($post)
    {
        $postButton=array_keys(array_filter($post, function($key) {return (substr($key, -6) == 'Button');}, ARRAY_FILTER_USE_KEY));
        if (isset($postButton[0]))
        {
            return $postButton[0];
        }
        else
        {
            return null;
        }
    }