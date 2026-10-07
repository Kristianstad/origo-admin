<?php
// Tell browsers to not cache response
header("Cache-Control: must-revalidate, max-age=0, s-maxage=0, no-cache, no-store");

require_once("./functions/includeDirectory.php");
includeDirectory("./functions/common");
require("./constants/configSchema.php");

$dbh = dbh();
$currentSkin = currentSkin(allFromTable($dbh, $configSchema, 'skins'));
$content = '';

if (isset($_GET['id'])) {
    $helps = allFromTable($dbh, $configSchema, 'helps');

    $help = arrayColumnSearch($_GET['id'], 'help_id', $helps);
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

$content .= '<br style="clear:both"><div class="helpCloseButton">'.renderCloseButton().'</div>';

// === Början av sidan ===
echo <<<HTML
<!DOCTYPE html>
<html>
<head>
HTML;
renderUtilityHead($currentSkin, 'help');

echo <<<HTML
</head>
<body>
{$content}
</body>
</html>
HTML;