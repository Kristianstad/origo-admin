<?php

    // Prints a top button that deletes the search result caches and rebuilds the search configuration cache from the database (see clearSearchCache.php)
    function printClearSearchCacheButton()
    {
        $confirmAttribute=escapeHtml("Töm sökcachen? Alla söktabellers resultatcachar tas bort och byggs om vid nästa sökning. Sökkonfigurationens cache byggs om från databasen direkt, och den gamla används tills den nya är klar.");
        echo <<<HERE
			<button title="Ta bort söktabellernas resultatcachar och bygg om sökkonfigurationens cache" class="topButton" type="button" data-manage-action="clear-search-cache" data-confirm="{$confirmAttribute}">Töm sökcache</button>
		HERE;
    }
