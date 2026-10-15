<?php

    // Takes an associative array and returns all values (and keys) where the key ends with 'Category'
    function categoryPosts($post)
    {
        $categoryPosts=array_filter($post, function($key) {return (substr($key, -8) == 'Category');}, ARRAY_FILTER_USE_KEY);
        return $categoryPosts;
    }