<?php

    // Takes a view (string) as parameter. If the given view is an empty string or 'Allt',
    // then return the constant in keywordCategorized.php. Else, read the constant in views.php
    // to determine which tables should be shown in the manager, then return a subset of the
    // constant in keywordCategorized.php reflecting that.
    function viewKeywordCategorized($view)
    {
        require("./constants/views.php");
        require("./constants/keywordCategorized.php");
        if (empty($view) || $view == 'Allt')
        {
            return $keywordCategorized;
        }
        else
        {
            return array_intersect($keywordCategorized, $views[$view]);
        }
    }