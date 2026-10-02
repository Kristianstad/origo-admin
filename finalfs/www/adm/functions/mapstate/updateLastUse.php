<?php

function updateLastUse($dbh, string $id): void
{
    $table = getMapStatesTable();
    pg_query_params($dbh, "UPDATE $table SET lastuse = NOW() WHERE mapstate_id = $1", array($id));
}