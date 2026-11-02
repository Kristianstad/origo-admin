<?php
/*
rebuildRestrictedCache.php
 ├─ includeDirectory("./functions/common")
 ├─ restrictedCacheRefresh()              [common] → bygger restricted-cachen atomiskt från aktuell konfiguration
 └─ svarar med resultat i hiddenFrame; vid fel lämnas den tidigare cachen orörd
*/

header("Cache-Control: must-revalidate, max-age=0, s-maxage=0, no-cache, no-store");

require_once("./functions/includeDirectory.php");
includeDirectory("./functions/common");

$message = restrictedCacheRefresh()
    ? 'Restricted-cachen har byggts om.'
    : 'Restricted-cachen kunde inte byggas om. En tidigare cache används om den finns.';
echo '<script>alert(' . jsonForInlineJs($message) . ');</script>';
