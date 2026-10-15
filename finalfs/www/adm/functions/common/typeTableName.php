<?php

    // Takes an item type (f ex. map, layer, source) and returns the corresponding table name
    function typeTableName($type)
    {
        return $type.'s';
    }