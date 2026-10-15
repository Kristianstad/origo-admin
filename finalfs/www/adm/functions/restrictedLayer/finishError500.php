<?php

    function finishError500($cause)
    {
        header($_SERVER['SERVER_PROTOCOL'].' 500 Internal Server Error', true, 500);
        header('Content-Type: text/html');
        echo 'Rättigheter saknas!';
        ignore_user_abort(false);
        exit(1);
    }