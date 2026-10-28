<?php

    // Prints a top button that marks the search configuration cache as stale and rebuilds it from the database (see clearSearchConfigCache.php)
    function printClearSearchConfigCacheButton()
    {
        $confirmAttribute=escapeHtml("Töm sökkonfigurationens cache? Den byggs om från databasen direkt, och den gamla används tills den nya är klar.");
        echo <<<HERE
			<button title="Bygg om sökkonfigurationens cache från databasen" class="topButton" type="button" data-manage-action="clear-search-config-cache" data-confirm="{$confirmAttribute}">Töm sökcache</button>
		HERE;
    }
