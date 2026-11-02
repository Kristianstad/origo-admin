<?php

    // Prints the Tools-view action that rebuilds the restricted layer cache (see rebuildRestrictedCache.php).
    function printRebuildRestrictedCacheButton()
    {
        $confirmAttribute=escapeHtml('Bygg om cachen över skyddade lager från den aktuella konfigurationen?');
        echo '<button title="Bygg om cachen över skyddade lager" class="topButton" type="button" data-manage-action="rebuild-restricted-cache" data-confirm="'.$confirmAttribute.'">Bygg om restricted-cache</button>';
    }
