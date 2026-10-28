<?php

    // Takes a searchtables row (array), the databases rows (array), the limit of the search model and its number of live rows.
    // Returns the settings used by the search module for that search table, or null (and logs) if the row is unusable.
    function searchTableConfig($searchtable, $databases, $modelLimit, $liveRows)
    {
        $tableKey=$searchtable['searchtable_id'] ?? '';
        $database=empty($searchtable['database']) ? array() : arrayColumnSearch($searchtable['database'], 'database_id', $databases);
        if (empty($database['connectionstring']) || empty($searchtable['dbtable']) || empty($searchtable['searchfield']))
        {
            error_log("Search: search table '$tableKey' is missing, or lacks database, table or search field");
            return null;
        }
        $title=(string) ($searchtable['title'] ?? '');
        $titleField=(string) ($searchtable['titlefield'] ?? '');
        return array(
            'tableKey' => $tableKey,
            'connectionString' => $database['connectionstring'],
            'usePconnect' => ($database['usepconnect'] ?? 'f') === 't',
            'schema' => $searchtable['dbschema'] ?: 'public',
            'table' => $searchtable['dbtable'],
            'searchField' => $searchtable['searchfield'],
            'geometryName' => $searchtable['geometryfield'] ?: 'geom',
            'gid' => $searchtable['gidfield'] ?: 'gid',
            'title' => $title,
            'titleField' => $titleField,
            'groupByTitle' => $title === '' && $titleField !== '',
            'customType' => $searchtable['customtype'] ?: $searchtable['dbtable'],
            'fields' => array_values(array_filter(array_map('trim', pgArrayToPhp($searchtable['fields'])), 'strlen')),
            'searchMode' => ($searchtable['mode'] ?? '') === 'intelligent' ? 'intelligent' : 'simple',
            'sortMode' => ($searchtable['sortmode'] ?? '') === 'blockunit' ? 'blockunit' : '',
            'useCentroid' => ($searchtable['usecentroid'] ?? 't') !== 'f',
            'ttl' => max(0, (int) ($searchtable['ttl'] ?? 0)),
            'maxCacheAgeFactor' => is_numeric($searchtable['maxcacheagefactor'] ?? null) ? (float) $searchtable['maxcacheagefactor'] : null,
            'limit' => (int) ($searchtable['limit'] ?? 0) ?: $modelLimit,
            'liveRows' => $liveRows
        );
    }
