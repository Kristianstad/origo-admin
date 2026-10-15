<?php

    function toSwedish($engStr)
    {
        require("./constants/swedishDic.php");
        if (isset($swedishDic[$engStr]))
        {
            return $swedishDic[$engStr];
        }
        else
        {
            return $engStr;
        }
    }