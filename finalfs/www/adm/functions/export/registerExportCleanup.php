<?php

// Takes the job (see createExportJob), the seconds to wait after a successful FME job (int),
// whether to keep the files for debugging (bool) and the job state (array with the key finished, passed by reference).
// Removes the job directory when the script ends. After a successful FME job it first waits and removes FME's zip file.
function registerExportCleanup(array $job, int $delay, bool $keepFiles, array &$state): void
{
    register_shutdown_function(function () use ($job, $delay, $keepFiles, &$state) {
        if ($keepFiles) {
            return;
        }
        if ($state['finished']) {
            sleep($delay);
            if (is_file($job['archive']) && !unlink($job['archive'])) {
                error_log('Unable to remove FME export archive.');
            }
        }
        if (!removeDirectory($job['directory'])) {
            error_log('Unable to remove export temp directory.');
        }
    });
}
