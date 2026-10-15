<?php

    // Takes an associative array and returns all values (and keys) where the key starts with 'width', 'newwidth', 'height', 'newheight', 'scroll' or 'newscroll'.
    function sizePosts($post)
    {
        $widthPosts=array_filter($post, function($key) {return (substr($key, 0, 5) == 'width');}, ARRAY_FILTER_USE_KEY);
        $newwidthPosts=array_filter($post, function($key) {return (substr($key, 0, 8) == 'newwidth');}, ARRAY_FILTER_USE_KEY);
        foreach ($newwidthPosts as $key=>$value)
        {
            $widthPosts[substr($key, 3)]=$value;
        }
        $heightPosts=array_filter($post, function($key) {return (substr($key, 0, 6) == 'height');}, ARRAY_FILTER_USE_KEY);
        $newheightPosts=array_filter($post, function($key) {return (substr($key, 0, 9) == 'newheight');}, ARRAY_FILTER_USE_KEY);
        foreach ($newheightPosts as $key=>$value)
        {
            $heightPosts[substr($key, 3)]=$value;
        }
        $scrollPosts=array_filter($post, function($key) {return (substr($key, 0, 6) == 'scroll');}, ARRAY_FILTER_USE_KEY);
        $newscrollPosts=array_filter($post, function($key) {return (substr($key, 0, 9) == 'newscroll');}, ARRAY_FILTER_USE_KEY);
        foreach ($newscrollPosts as $key=>$value)
        {
            $scrollPosts[substr($key, 3)]=$value;
        }
        return array_merge($widthPosts, $heightPosts, $scrollPosts);
    }