<?php

// Takes an export format (exportFormats entry), the validated request, the job (see createExportJob),
// the image formats that were fetched (array), whether drawn layers were written (bool) and the user's mail address (string).
// Returns the published parameters for the FME workspace.
function exportFmeParameters(array $format, array $request, array $job, array $imageFormats, bool $hasDrawings, string $mail): array
{
    $parameters = array_merge(array('M' => $mail), $request['fmeBox']);
    $parameters['D'] = strtr($format['output'], array(
        '{userRoot}' => $job['fmeUserRoot'],
        '{jobRoot}' => $job['fmeJobRoot'],
        '{id}' => $job['id'],
        '{V}' => $request['V']
    ));
    if ($format['srs']) {
        $parameters['C'] = $request['srs'];
    }
    if ($format['format'] !== null) {
        $parameters['FF'] = $format['format'];
    }
    if (in_array('V', $format['parameters'], true)) {
        $parameters['V'] = $request['V'];
    }
    foreach ($format['inputs'] as $name => $input) {
        if (isset($input['when']) && !in_array($input['when'], $imageFormats, true)) {
            continue;
        }
        $parameters[$name] = exportFmeFileList($job['directory'], $job['fmeJobRoot'], $input['pattern']);
    }
    if ($hasDrawings) {
        $parameters['JF'] = exportFmeFileList($job['directory'], $job['fmeJobRoot'], '*.json');
    }
    return $parameters;
}
