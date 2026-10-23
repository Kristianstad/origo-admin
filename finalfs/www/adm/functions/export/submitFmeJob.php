<?php

// Takes the export settings (exportConfig), the FME token (string), the workspace (string) and the published parameters (array).
// Runs the workspace on FME Flow and waits for it to finish. Returns true if FME accepted and completed the job.
// Failures are logged with the parameter values removed from FME's response.
function submitFmeJob(array $config, string $token, string $workspace, array $parameters): bool
{
    $postfields = json_encode(array(
        'repository' => $config['fmeRepository'],
        'workspace' => $workspace,
        'publishedParameters' => $parameters
    ), JSON_UNESCAPED_SLASHES);
    if ($postfields === false) {
        error_log('Unable to encode FME Flow job request: '.json_last_error_msg());
        return false;
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Authorization: fmetoken token='.$token));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
    curl_setopt($ch, CURLOPT_URL, $config['fmeJobUrl']);
    $result = curl_exec($ch);
    $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $succeeded = $result !== false && $httpStatus >= 200 && $httpStatus < 300;
    if (!$succeeded) {
        $response = $result === false ? '' : $result;
        foreach ($parameters as $parameterValue) {
            foreach (is_array($parameterValue) ? $parameterValue : array($parameterValue) as $value) {
                if (is_string($value) && $value !== '') {
                    $response = str_replace($value, '[redacted]', $response);
                }
            }
        }
        error_log('FME Flow job execution failed; HTTP status '.$httpStatus.'; cURL error: '.curl_error($ch).'; response: '.substr($response, 0, 2000));
    }
    curl_close($ch);
    return $succeeded;
}
