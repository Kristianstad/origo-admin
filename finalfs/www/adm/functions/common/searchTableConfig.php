<?php

    // Takes a searchtables row (array), the databases rows (array), the limit of the search model and its number of live rows.
    // The row's table is a tables id ("<database>.<schema>.<table>") from which database, schema and table name are taken.
    // Returns the settings used by the search module for that search table, or null (and logs) if the row is unusable.
    function searchTableConfig($searchtable, $databases, $modelLimit, $liveRows)
    {
        $tableKey=$searchtable['searchtable_id'] ?? '';
        list($databaseId, $schema, $table)=array_pad(explode('.', (string) ($searchtable['table'] ?? ''), 3), 3, '');
        $database=($databaseId === '') ? array() : arrayColumnSearch($databaseId, 'database_id', $databases);
        if (empty($database['connectionstring']) || $schema === '' || $table === '' || empty($searchtable['searchfield']))
        {
            error_log("Search: search table '$tableKey' lacks a table of the form database.schema.table in a known database, or lacks a search field");
            return null;
        }
        $title=(string) ($searchtable['title'] ?? '');
        $titleField=(string) ($searchtable['titlefield'] ?? '');
        return array(
            'tableKey' => $tableKey,
            'connectionString' => $database['connectionstring'],
            'usePconnect' => ($database['usepconnect'] ?? 'f') === 't',
            'schema' => $schema,
            'table' => $table,
            'searchField' => $searchtable['searchfield'],
            'geometryName' => $searchtable['geometryfield'] ?: 'geom',
            'gid' => $searchtable['gidfield'] ?: 'gid',
            'title' => $title,
            'titleField' => $titleField,
            'groupByTitle' => $title === '' && $titleField !== '',
            'customType' => $searchtable['customtype'] ?: $table,
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
