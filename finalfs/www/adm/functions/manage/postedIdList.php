<?php

    // Takes a post array and a key holding a comma separated id chain (f ex. groupIds). Returns the ids as an array.
    function postedIdList($post, $key)
    {
        return isset($post[$key]) ? explode(',', $post[$key]) : array();
    }
