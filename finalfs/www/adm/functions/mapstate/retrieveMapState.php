<?php

function retrieveMapState($dbh): never
{
    $id = trim($_GET['mapStateId'] ?? '');

    if (!validateMapStateId($id)) {
        http_response_code(400);
        echo json_encode(['error' => 'Ogiltigt eller saknat id']);
        exit;
    }

    updateLastUse($dbh, $id);

    $table  = getMapStatesTable();
    $sql    = "SELECT state FROM $table WHERE mapstate_id = $1";
    $result = pg_query_params($dbh, $sql, array($id));
    if ($result === false) {
        error_log("Läsning av mapstate misslyckades: " . pg_last_error($dbh));
        http_response_code(500);
        echo json_encode(['error' => 'Kunde inte läsa mapstate']);
        exit;
    }
    $row    = pg_fetch_assoc($result);

    if (!$row) {
        http_response_code(404);
        echo json_encode(['error' => 'Mapstate hittades inte']);
        exit;
    }

	echo $row['state'];
    exit;
}