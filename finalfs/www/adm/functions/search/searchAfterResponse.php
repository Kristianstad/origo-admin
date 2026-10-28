<?php

    // Takes a function. Sends the response to the client and then runs the function, so that slow work does not delay the answer.
    // Does nothing under a web server that cannot finish the request early.
    function searchAfterResponse($task)
    {
        if (php_sapi_name() !== 'cli')
        {
            if (!function_exists('fastcgi_finish_request'))
            {
                return;
            }
            fastcgi_finish_request();
        }
        ignore_user_abort(true);
        set_time_limit(0);
        $task();
    }
