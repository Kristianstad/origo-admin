<?php

function toPgArrayLiteral(array $items): string
{
    $escaped=array();
    foreach ($items as $item)
    {
        $item=(string) $item;
        $escaped[]='"'.str_replace(array('\\', '"'), array('\\\\', '\\"'), $item).'"';
    }
    return '{'.implode(',', $escaped).'}';
}