<?php

    function printNewsList($userNews)
    {
        foreach ($userNews as $key => $row)
        {
            $newsDate[$key] = $row['date'];
        }
        array_multisort($newsDate, SORT_DESC, $userNews);
        $userNewsList=array_column($userNews, 'new_id');
        echo json_encode($userNewsList);
    }