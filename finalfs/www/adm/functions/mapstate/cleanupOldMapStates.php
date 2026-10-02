<?php

function cleanupOldMapStates($dbh, int $days): void
{
    $table  = getMapStatesTable();
    $sql = "
        DELETE FROM $table
        WHERE 
            (lastuse IS NOT NULL AND lastuse < NOW() - ($1::integer * INTERVAL '1 day') AND NOT preserve)
            OR
            (created < NOW() - ($2::integer * INTERVAL '1 day') AND lastuse IS NULL AND NOT preserve)
    ";
    $result = pg_query_params($dbh, $sql, array($days, 30));
    if ($result === false) {
        error_log("Cleanup av gamla map states misslyckades: " . pg_last_error($dbh));
    }
}