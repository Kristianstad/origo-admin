<?php

    function getArrayValuesRecursively(array $array)
    {
        $values = [];
        foreach ($array as $value)
        {
            if (is_array($value))
            {
                $values = array_merge($values, getArrayValuesRecursively($value));
            }
            else
            {
                $values[] = $value;
            }
        }
        return $values;
    }