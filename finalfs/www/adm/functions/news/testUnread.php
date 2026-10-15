<?php

    function testUnread($username, $userNews)
    {
        $result='false';
        foreach ($userNews as $aNews)
        {
            if (!in_array($username, $aNews['reads']))
            {
                $result='true';
                break;
            }
        }
        echo $result;
    }