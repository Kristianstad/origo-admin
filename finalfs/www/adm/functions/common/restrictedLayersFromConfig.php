<?php

    // Builds the restricted layer list from loaded configuration-table rows.
    function restrictedLayersFromConfig(array $configTables): array
    {
        foreach (array('layers', 'sources', 'services') as $table)
        {
            if (!array_key_exists($table, $configTables))
            {
                throw new UnexpectedValueException("Missing configuration table '$table' for restricted cache");
            }
        }
        $restrictedLayers=array();
        $layers=$configTables['layers'];
        $sources=$configTables['sources'];
        $services=$configTables['services'];
        foreach ($layers as $layer)
        {
            if (($layer['type'] ?? null) === 'GROUP')
            {
                continue;
            }
            $source=arrayColumnSearch($layer['source'] ?? null, 'source_id', $sources);
            if (empty($source))
            {
                throw new UnexpectedValueException("Layer '{$layer['layer_id']}' refers to an unknown source");
            }
            if (!array_key_exists('service', $source))
            {
                throw new UnexpectedValueException("Source '{$source['source_id']}' has no service field");
            }
            if ($source['service'] === null || $source['service'] === '')
            {
                continue;
            }
            $service=arrayColumnSearch($source['service'], 'service_id', $services);
            if (empty($service) || !array_key_exists('restricted', $service))
            {
                throw new UnexpectedValueException("Source '{$source['source_id']}' refers to an unknown or invalid service");
            }
            if ($service['restricted'] === null || $service['restricted'] === 'f')
            {
                continue;
            }
            if ($service['restricted'] !== 't')
            {
                throw new UnexpectedValueException("Service '{$source['service']}' has an invalid restricted flag");
            }
            $authorizedUsers=$layer['adusers'] ?? null;
            $authorizedGroups=$layer['adgroups'] ?? null;
            $restrictedLayers[]=array(
                'name' => explode('#', $layer['layer_id'])[0],
                'authorized_users' => array_map(function ($user) { return mb_strtolower($user, 'UTF-8'); }, $authorizedUsers === null ? array() : pgArrayToPhp(str_replace('"', '', $authorizedUsers))),
                'authorized_groups' => array_map(function ($group) { return mb_strtolower($group, 'UTF-8'); }, $authorizedGroups === null ? array() : pgArrayToPhp(str_replace('"', '', $authorizedGroups)))
            );
        }
        return $restrictedLayers;
    }
