<?php

function executeImportQuery($dbh, string $sql, array $params=array())
{
    $result=pg_query_params($dbh, $sql, $params);
    if ($result === false)
    {
        throw new RuntimeException(pg_last_error($dbh));
    }
    return $result;
}