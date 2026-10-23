<?php

// Takes a file name from the request (any value) and a default name (string).
// Returns the name without any directory part, or the default if nothing usable is left.
function exportFileName($name, string $default): string
{
    if (is_string($name)) {
        $name = basename(str_replace('\\', '/', $name));
    }
    if (!is_string($name) || $name === '' || $name === '.' || $name === '..') {
        return $default;
    }
    return $name;
}
