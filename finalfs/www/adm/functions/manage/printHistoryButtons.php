<?php

function printHistoryButtons($target, $dbh=null, $inheritPosts=array())
{
    if (!isTarget($target))
    {
        return;
    }
    // pg_connect() returns a PgSql\Connection object since PHP 8.1, not a resource
    if (!(is_resource($dbh) || $dbh instanceof \PgSql\Connection))
    {
        $dbh = dbh();
        $closeConnection = true;
    }
    else
    {
        $closeConnection = false;
    }
    $state = historyStateForTarget($dbh, $target);
    if (!$state['undo'] && !$state['redo'])
    {
        if ($closeConnection)
        {
            pg_close($dbh);
        }
        return;
    }
    $targetKey = $state['target_key'] ?? objectHistoryKey($target);
    printUndoButton($target, $state['undo'], $targetKey);
    printRedoButton($target, $state['redo'], $targetKey);
    if ($closeConnection)
    {
        pg_close($dbh);
    }
}