<?php

function generateCsrfToken(): string
{
    ensureSessionWritable();
    if (empty($_SESSION['csrf_token']))
    {
        $_SESSION['csrf_token']=bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}