<?php
// Tell browsers to not cache response
header("Cache-Control: must-revalidate, max-age=0, s-maxage=0, no-cache, no-store");

require_once("./functions/includeDirectory.php");
includeDirectory("./functions/common");
require("./constants/configSchema.php");

$dbh = dbh();
$currentSkin = currentSkin(all_from_table($dbh, $configSchema, 'skins'));
$content = '';

if (isset($_GET['id'])) {
    $helps = all_from_table($dbh, $configSchema, 'helps');

    $help = array_column_search($_GET['id'], 'help_id', $helps);
    if (isset($help['abstract'])) {
        $content = $help['abstract'];
    }
} else {
    $content = <<<HERE
				<a href="../Origo_admin_tutorial_swedish.pdf" target="_blank">Origo admin tutorial</a><br>
                <a href="https://origo-map.github.io/origo-documentation/latest/#origo-map" target="_blank">Origo-dokumentation</a>
HERE;
}

pg_close($dbh);

$content .= "<br style=\"clear:both\"><div class=\"helpCloseButton\"><button type=\"button\" title=\"Stäng\" aria-label=\"Stäng\" onclick=\"window.parent.postMessage({ action: 'close' }, window.location.origin);\"><span aria-hidden=\"true\">&#x22A0;</span></button></div>";

// === Början av sidan ===
echo <<<HTML
<!DOCTYPE html>
<html>
<head>
	<style>
HTML;

printSkinVariables($currentSkin);
require("./styles/help.css");

echo <<<HTML
	</style>
	<script>
		window.onload = function() {
			if (window.parent !== window) { // Make sure we are in an iframe
				window.parent.postMessage({ action: 'resize' }, window.location.origin);
			}
		};
	</script>
</head>
<body>
{$content}
</body>
</html>
HTML;