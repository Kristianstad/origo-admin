<?php

    // Takes a path and file contents. Writes to a temporary file in the same directory and renames it into place.
    function cacheWriteFile($path, $contents)
    {
        $tempPath=tempnam(dirname($path), 'tmp');
        if ($tempPath === false)
        {
            return false;
        }
        if (file_put_contents($tempPath, $contents) !== strlen($contents) || !rename($tempPath, $path))
        {
            @unlink($tempPath);
            return false;
        }
        return true;
    }
