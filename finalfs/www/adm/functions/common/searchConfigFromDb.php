<?php

    // Takes a connection to the configuration database. Returns all search models with their search tables resolved (see searchTableConfig())
    // as array(<model id> => array('limit', 'liveRows', 'tables')), or null if the search configuration tables do not exist.
    function searchConfigFromDb($dbh)
    {
        require("./constants/configSchema.php");
        if (array_diff(array('searchmodels', 'searchtables', 'databases'), configTableNames($dbh)))
        {
            return null;
        }
        $searchtables=allFromTable($dbh, $configSchema, 'searchtables');
        $databases=allFromTable($dbh, $configSchema, 'databases');
        $models=array();
        foreach (allFromTable($dbh, $configSchema, 'searchmodels') as $model)
        {
            $limit=(int) ($model['limit'] ?? 0) ?: 20;
            $liveRows=(int) ($model['liverows'] ?? 0) ?: 300;
            $tables=array();
            foreach (pgArrayToPhp($model['searchtables']) as $searchtableId)
            {
                $searchtable=arrayColumnSearch($searchtableId, 'searchtable_id', $searchtables);
                if (empty($searchtable))
                {
                    error_log("Search: model '{$model['searchmodel_id']}' refers to unknown search table '$searchtableId'");
                    continue;
                }
                $tableConfig=searchTableConfig($searchtable, $databases, $limit, $liveRows);
                if ($tableConfig !== null)
                {
                    $tables[]=$tableConfig;
                }
            }
            $models[$model['searchmodel_id']]=array('limit' => $limit, 'liveRows' => $liveRows, 'tables' => $tables);
        }
        return $models;
    }
