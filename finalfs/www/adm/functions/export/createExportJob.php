<?php

// Takes the export settings (exportConfig) and the user id (string).
// Creates the job directory (tempRoot/<user>/<job id>) and returns the job:
// id, directory and archive (the zip file that FME writes next to the directory) as seen by PHP,
// and fmeUserRoot and fmeJobRoot as seen by FME.
function createExportJob(array $config, string $userId): array
{
    $userRoot = $config['tempRoot'].'/'.$userId;
    foreach (array($config['tempRoot'], $userRoot) as $directory) {
        if (!file_exists($directory)) {
            mkdir($directory);
            chmod($directory, 0777);
        }
    }
    $id = uniqid();
    $directory = $userRoot.'/'.$id;
    mkdir($directory);
    chmod($directory, 0777);
    $fmeUserRoot = $config['fmeResourceRoot'].$userId;
    return array(
        'id' => $id,
        'directory' => $directory,
        'archive' => $userRoot.'/export-'.$id.'.zip',
        'fmeUserRoot' => $fmeUserRoot,
        'fmeJobRoot' => $fmeUserRoot.'/'.$id
    );
}
