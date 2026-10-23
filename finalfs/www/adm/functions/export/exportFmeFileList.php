<?php

// Takes the job directory (string), the job directory as FME sees it (string) and a glob pattern (string).
// Returns the paths, as FME sees them, of the files in the job directory that match the pattern.
function exportFmeFileList(string $directory, string $fmeJobRoot, string $pattern): array
{
    $localFiles = glob($directory.'/'.$pattern);
    if ($localFiles === false) {
        error_log('Unable to enumerate local FME input files matching '.$pattern);
        return array();
    }
    $fmeFiles = array();
    foreach ($localFiles as $localFile) {
        if (is_file($localFile)) {
            $fmeFiles[] = $fmeJobRoot.'/'.basename($localFile);
        }
    }
    return $fmeFiles;
}
