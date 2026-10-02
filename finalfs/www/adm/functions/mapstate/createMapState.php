<?php
// Returns mapStateId – exactly as Origo frontend expects

function createMapState($dbh): never
{
    $input = json_decode(file_get_contents('php://input'), true);

    if ($input === null && json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['error' => 'Ogiltig JSON']);
        exit;
    }

    if (!is_array($input)) {
        http_response_code(400);
        echo json_encode(['error' => 'Ogiltigt karttillstånd']);
        exit;
    }

	$stateJson = json_encode($input, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	
    $referer = $_SERVER['HTTP_REFERER'] ?? null;

    if ($referer !== null) {
        $referer = parse_url($referer, PHP_URL_SCHEME) . '://' .
                   parse_url($referer, PHP_URL_HOST) .
                   parse_url($referer, PHP_URL_PATH);
    }

    // Generera UUID v4 i PHP – kompatibelt med character varying
    $new_id = sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );

    $table = getMapStatesTable();

    $sql = "INSERT INTO $table (mapstate_id, state, created, mapurl) VALUES ($1, $2, NOW(), $3) RETURNING mapstate_id";

    $result = pg_query_params($dbh, $sql, array($new_id, $stateJson, $referer));

    if (!$result) {
        http_response_code(500);
        echo json_encode(['error' => 'Kunde inte spara mapstate']);
        exit;
    }

    $row = pg_fetch_assoc($result);
    echo json_encode(['mapStateId' => $row['mapstate_id']]);
    exit;
}