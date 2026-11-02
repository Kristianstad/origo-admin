<?php

/**
 * Publish map files with uncompressed and compressed variants,
 * and create public symlinks in the maps directory and web root. Returns false if a write or link fails.
 *
 * @param string $filepathWithoutSuffix Base path without extension (e.g. /storage/maps/123)
 * @param string $html                 HTML content
 * @param string $json                 Pretty-printed JSON content
 * @param string $mapId                The public identifier (e.g. "123")
 */
function publishMapFiles(
    string $filepathWithoutSuffix,
    string $html,
    string $json,
    string $mapId
): bool {
    require("./constants/webRoot.php"); // Defines $webRoot

    $htmlFile = $filepathWithoutSuffix . '.html';
    $jsonFile = $filepathWithoutSuffix . '.json';

    // 1. Save uncompressed files
    if (!saveFile($htmlFile, $html) || !saveFile($jsonFile, $json)) {
        return false;
    }

    // 2. Create Brotli versions (if supported)
    $htmlBr = compressBrotli($html);
    if ($htmlBr !== null) {
        if (!saveFile($htmlFile . '.br', $htmlBr)) {
            return false;
        }
    }

    $jsonBr = compressBrotli($json);
    if ($jsonBr !== null) {
        if (!saveFile($jsonFile . '.br', $jsonBr)) {
            return false;
        }
    }

    // 3. Create gzip versions
    $htmlGz = compressGzip($html);
    if ($htmlGz !== null) {
        if (!saveFile($htmlFile . '.gz', $htmlGz)) {
            return false;
        }
    }

    $jsonGz = compressGzip($json);
    if ($jsonGz !== null) {
        if (!saveFile($jsonFile . '.gz', $jsonGz)) {
            return false;
        }
    }

    // 4. Create symlinks in /www/maps
    $base = rtrim($webRoot, '/') . '/maps/';

    if (!createSymlinkIfNotExists($htmlFile, $base . $mapId . '.html') || !createSymlinkIfNotExists($jsonFile, $base . $mapId . '.json')) {
        return false;
    }

    if ($htmlBr !== null) {
        if (!createSymlinkIfNotExists($htmlFile . '.br', $base . $mapId . '.html.br')) {
            return false;
        }
    }
    if ($jsonBr !== null) {
        if (!createSymlinkIfNotExists($jsonFile . '.br', $base . $mapId . '.json.br')) {
            return false;
        }
    }

    if ($htmlGz !== null) {
        if (!createSymlinkIfNotExists($htmlFile . '.gz', $base . $mapId . '.html.gz')) {
            return false;
        }
    }
    if ($jsonGz !== null) {
        if (!createSymlinkIfNotExists($jsonFile . '.gz', $base . $mapId . '.json.gz')) {
            return false;
        }
    }

    // 5. Link the map directory and create matching symlinks in /www
    $mapDirectory = dirname($filepathWithoutSuffix);
    $publicMapDirectory = rtrim($webRoot, '/') . '/' . basename($mapDirectory);
    if (!createSymlinkIfNotExists($mapDirectory, $publicMapDirectory)) {
        return false;
    }

    $publicHtmlFile = $publicMapDirectory . '/' . basename($htmlFile);
    $publicJsonFile = $publicMapDirectory . '/' . basename($jsonFile);
    $publicBase = rtrim($webRoot, '/') . '/';

    if (!createSymlinkIfNotExists($publicHtmlFile, $publicBase . $mapId . '.html') || !createSymlinkIfNotExists($publicJsonFile, $publicBase . $mapId . '.json')) {
        return false;
    }

    if ($htmlBr !== null) {
        if (!createSymlinkIfNotExists($publicHtmlFile . '.br', $publicBase . $mapId . '.html.br')) {
            return false;
        }
    }
    if ($jsonBr !== null) {
        if (!createSymlinkIfNotExists($publicJsonFile . '.br', $publicBase . $mapId . '.json.br')) {
            return false;
        }
    }
    if ($htmlGz !== null) {
        if (!createSymlinkIfNotExists($publicHtmlFile . '.gz', $publicBase . $mapId . '.html.gz')) {
            return false;
        }
    }
    if ($jsonGz !== null) {
        if (!createSymlinkIfNotExists($publicJsonFile . '.gz', $publicBase . $mapId . '.json.gz')) {
            return false;
        }
    }
    return true;
}
