<?php
/*
clearSearchConfigCache.php
 ├─ includeDirectory("./functions/common")
 ├─ searchConfigMarkStale()                  [common] → markerar konfigurationscachen som gammal
 ├─ searchConfigRefresh(null, 5)             [common] → bygger om den från databasen (väntar högst 5 s på ett pågående bygge)
 └─ svarar med en alert i hiddenFrame; misslyckas bygget används den gamla cachen vidare
*/

header("Cache-Control: must-revalidate, max-age=0, s-maxage=0, no-cache, no-store");

require_once("./functions/includeDirectory.php");
includeDirectory("./functions/common");

searchConfigMarkStale();
$message = searchConfigRefresh(null, 5)
    ? 'Sökkonfigurationens cache har uppdaterats.'
    : 'Sökkonfigurationen kunde inte uppdateras just nu. Den gamla cachen används tills en ny har byggts.';
echo '<script>alert(' . jsonForInlineJs($message) . ');</script>';
