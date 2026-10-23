<?php

// Returns the request body decoded as JSON (array), or an empty array if there is no valid JSON body.
function exportRequestBody(): array
{
    $json = file_get_contents('php://input');
    if (!is_string($json) || $json === '') {
        return array();
    }
    $decoded = json_decode($json, true);
    return is_array($decoded) ? $decoded : array();
}
