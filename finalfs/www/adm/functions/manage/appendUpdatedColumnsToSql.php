<?php

    function appendUpdatedColumnsToSql($dbColumns, $sql, $params=array())
    {
        $first=true;
        foreach ($dbColumns as $column => $value)
        {
            if ((empty($value) && $value !== '0') || $value == '{}' || $value == '{{}}')
            {
                $value=null;
            }
            $params[]=$value;
            $placeholder='$'.count($params);
            if ($first)
            {
                $first=false;
            }
            else
            {
                $sql=$sql.',';
            }
            $sql=$sql." $column = $placeholder";
        }
        return array('sql' => $sql, 'params' => $params);
    }