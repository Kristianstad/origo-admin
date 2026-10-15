<?php

function validateCsrfToken(?string $token): bool
{
    ensureSessionWritable();
    return is_string($token) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}