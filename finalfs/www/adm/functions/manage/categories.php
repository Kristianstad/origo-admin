<?php

    // Takes an Origo table configuration and the primary key- (id-) column name for the same table.
    // Returns an associative array with the keyword categories for the specific table.
    // The array keys are keywords and the values are arrays of item ids that has the corresponding keyword set in their config
    function categories($config, $catParam)
    {
        $categories=array();
        foreach (str_replace('"', '', array_filter(array_column($config, 'keywords', $catParam))) as $id => $pgarray)
        {
            foreach (pgArrayToPhp($pgarray) as $category)
            {
                if (!isset($categories[$category]))
                {
                    $categories[$category]=array();
                }
                $categories[$category][]=$id;
            }
        }
        ksort($categories, SORT_LOCALE_STRING);
        $categories=array_merge(array("Alla" => array_column($config, $catParam)), $categories);
        return $categories;
    }