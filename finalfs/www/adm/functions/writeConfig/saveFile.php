<?php

/**
 * Atomically save content to a file in its destination directory.
 * Returns true on success, false on failure.
 */
function saveFile(string $path, string $content): bool
{
    $tempPath = @tempnam(dirname($path), 'tmp');
    if ($tempPath === false) {
        return false;
    }
    $currentPermissions = @fileperms($path);
    $permissions = ($currentPermissions === false) ? 0644 : ($currentPermissions & 0777);
    if (@file_put_contents($tempPath, $content) !== strlen($content) || !@chmod($tempPath, $permissions) || !@rename($tempPath, $path)) {
        @unlink($tempPath);
        return false;
    }
    return true;
}
