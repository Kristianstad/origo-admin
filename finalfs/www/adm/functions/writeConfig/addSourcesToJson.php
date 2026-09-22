<?php

	// Uses writeConfig functions: pgBoolToText, pgArrayToText, pgBoxToText

	function addSourcesToJson(array &$context)
	{
		$mapSources =& $context['mapSources'];
		$map =& $context['map'];
		$sources =& $context['sources'];
		$services =& $context['services'];
		$tilegrids =& $context['tilegrids'];
		require("./constants/sourcesQueryColumns.php");
		$sourcesJson = array();
		if (!is_array($mapSources))
		{
			$mapSources = pgArrayToPhp($mapSources);
		}

		$mapSources = array_unique($mapSources);
		foreach ($mapSources as $sourceId)
		{
			$source = array_column_search(trim(explode('@', $sourceId, 2)[0]), 'source_id', $sources);
			if (!empty($source))
			{
				$type = array_column_search($source['service'], 'service_id', $services, 'type');
				$url = array_column_search($source['service'], 'service_id', $services, 'base_url');
				$restricted = array_column_search($source['service'], 'service_id', $services, 'restricted');
				$sourceProject = trim(explode('#', $source['source_id'], 2)[0]);
				if (strpos($sourceId, '@wfs') !== false)
				{
					$wfsSource = true;
				}
				else
				{
					$wfsSource = false;
				}
				$url = rtrim($url, '/') . '/' . $sourceProject;
				$queryParams = [];
				if (!$wfsSource) {
					foreach (array_keys($source) as $column) {
						if (in_array($column, $sourcesQueryColumns) && !empty($source[$column])) {
							$queryParams[$column] = pgBoolToText($source[$column]);
						}
					}
				}
				if ($restricted === 't') {
					$queryParams['restricted'] = 't';
				}
				if (!empty($queryParams)) {
					$url .= '?' . http_build_query($queryParams);
				}
				$sourceJson = array('url' => $url);
				if ($wfsSource)
				{
					$sourceJson['workspace'] = 'qgs';
				}
				if (!empty($type))
				{
					$sourceJson['type'] = $type;
				}
				if (!empty($source['tilegrid']))
				{
					$tilegrid = array_column_search($source['tilegrid'], 'tilegrid_id', $tilegrids);
					$tileGridJson = array();
					if (!empty($tilegrid['tilesize']))
					{
						$tileGridJson['tileSize'] = is_numeric($tilegrid['tilesize']) ? $tilegrid['tilesize'] + 0 : $tilegrid['tilesize'];
					}
					if (!empty($tilegrid['resolutions']))
					{
						$resolutions=$tilegrid['resolutions'];
					}
					else
					{
						$resolutions=$map['resolutions'];
					}
					$tileGridJson['resolutions'] = array_map(function ($value) {
						return is_numeric($value) ? $value + 0 : $value;
					}, pgArrayToPhp($resolutions));
					if (!empty($tilegrid['extent']))
					{
						$extent=$tilegrid['extent'];
					}
					else
					{
						$extent=$map['extent'];
					}
					$tileGridJson['extent'] = array_map(function ($value) {
						return is_numeric($value) ? $value + 0 : $value;
					}, explode(',', pgBoxToText($extent)));
					$sourceJson['tileGrid'] = $tileGridJson;
				}
				$sourcesJson[$sourceId] = $sourceJson;
			}
		}
		return $sourcesJson;
	}