<?php

function removeDirectory(string $directory): bool
{
    if (!is_dir($directory) || is_link($directory)) {
        return false;
    }

    $entries = scandir($directory);
    if ($entries === false) {
        return false;
    }
    foreach (array_diff($entries, array('.', '..')) as $entry) {
        $path = $directory.DIRECTORY_SEPARATOR.$entry;
        $removed = is_dir($path) && !is_link($path)
            ? removeDirectory($path)
            : unlink($path);
        if (!$removed) {
            return false;
        }
    }
    return rmdir($directory);
}
