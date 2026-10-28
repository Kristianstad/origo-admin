<?php

    // Takes a connection to the searched database, the query (string) and a search table config (array, see searchTableConfig()).
    // Returns array('sql' => ..., 'params' => ...) selecting matching rows as NAMN, GID, <fields>, TYPE, TITLE (if any), GEOM and,
    // in 'intelligent' mode, RELEVANCE. The query '%' selects all rows without limit (used when building a cache).
    function searchSql($dbh, $query, $options)
    {
        $table=pg_escape_identifier($dbh, $options['table']);
        $column=function ($name) use ($dbh, $table) { return $table.'.'.pg_escape_identifier($dbh, $name); };
        $searchField=$column($options['searchField']);
        $searchText='CAST('.$searchField.' AS TEXT)';
        $lowerText='LOWER('.$searchText.')';
        $intelligent=($options['searchMode'] === 'intelligent');
        $params=array();

        $selectFields=array($searchText.' AS "NAMN"', $column($options['gid']).' AS "GID"');
        foreach ($options['fields'] as $field)
        {
            $selectFields[]=$column($field);
        }
        $selectFields[]=pg_escape_literal($dbh, $options['customType']).' AS "TYPE"';
        if ($options['title'] !== '')
        {
            $selectFields[]=pg_escape_literal($dbh, $options['title']).' AS "TITLE"';
        }
        elseif ($options['titleField'] !== '')
        {
            $selectFields[]='CAST('.$column($options['titleField']).' AS TEXT) AS "TITLE"';
        }
        $geometry=$column($options['geometryName']);
        $selectFields[]='ST_AsText('.($options['useCentroid'] ? 'ST_PointOnSurface('.$geometry.')' : $geometry).') AS "GEOM"';
        if ($intelligent)
        {
            // The trailing :* makes the match prefix based, like the score used for caches
            $params[]=str_replace(' ', ' & ', $query).':*';
            $selectFields[]='ts_rank_cd(to_tsvector(\'simple\', '.$searchText.'), to_tsquery(\'simple\', $'.count($params).')) AS "RELEVANCE"';
        }

        $params[]=$intelligent ? '%'.$query.'%' : $query.'%';
        $where=$lowerText.' ILIKE LOWER($'.count($params).')';

        $lastNumber='substring('.$lowerText.' FROM \'(\d+)([^0-9]*$)\')::integer';
        $blockNumber='substring('.$lowerText.' FROM \'(\d+):\')::integer';
        $unitNumber='substring('.$lowerText.' FROM \':(\d+)\')::integer';
        if ($intelligent)
        {
            $numberOrder=($options['sortMode'] === 'blockunit')
                ? 'COALESCE('.$blockNumber.', 999999) ASC, COALESCE('.$unitNumber.', 999999) ASC'
                : 'COALESCE('.$lastNumber.', 999999) ASC';
            $orderBy='"RELEVANCE" DESC, '.$numberOrder.', '.$searchField.' ASC';
        }
        else
        {
            $orderBy=$lastNumber.' ASC NULLS LAST, '.$searchField.' ASC';
        }

        $limitSql='';
        if ($query !== '%')
        {
            $limit=$intelligent ? $options['liveRows'] : $options['limit'];
            $limitSql=$limit ? ' LIMIT '.(int) $limit : '';
        }

        $sql='SELECT '.implode(', ', $selectFields).' FROM '.pg_escape_identifier($dbh, $options['schema']).'.'.$table
            .' WHERE '.$where.' ORDER BY '.$orderBy.$limitSql;
        return array('sql' => $sql, 'params' => $params);
    }
