<?php

function exportResponseSnippet($response): string
{
    if (!is_string($response)) {
        return '';
    }
    return substr(preg_replace('/[^\x20-\x7E]+/', ' ', $response), 0, 200);
}
