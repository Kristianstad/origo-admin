<?php

    // Returns array('config' => search models, 'stale' => bool) from the search configuration cache. If there is no cache it is built first
    // (waiting briefly for a build in progress). Ends the request with 503 if no configuration can be had.
    function searchLoadConfig()
    {
        $cached=searchConfigRead();
        if ($cached === null)
        {
            searchConfigRefresh(null, 5);
            $cached=searchConfigRead();
        }
        if ($cached === null)
        {
            http_response_code(503);
            echo json_encode(array('error' => 'Search configuration unavailable'));
            exit;
        }
        return $cached;
    }
