<?php

    // Include all files in given directory (does not permit subdirectories)
    function includeDirectory($directory)
    {
        $files = array_diff(scandir($directory), array('.', '..'));
        foreach ($files as $file)
        {
            include_once("$directory/$file");
        }
    }