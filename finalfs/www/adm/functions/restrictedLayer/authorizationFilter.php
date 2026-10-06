<?php

	function authorizationFilter($layerNames)
	{
		if (!is_array($layerNames))
		{
			$layerNames = array_unique(explode(',', $layerNames));
		}
		$authorizedLayers = array();
		foreach (RESTRICTEDLAYERS as $restrictedLayer)
		{
			$foundKey = array_search($restrictedLayer['name'], $layerNames);
			if ($foundKey !== false)
			{
				if (!empty($_SESSION['user']))
				{
					if (userAuthorized($_SESSION['user'], $restrictedLayer))
					{
						$authorizedLayers[] = $restrictedLayer;
					}
				}
				unset($layerNames[$foundKey]);
				if (empty($layerNames))
				{
					break;
				}
			}
		}
		foreach ($layerNames as $unrestrictedLayerName)
		{
			$authorizedLayers[] = array('name' => $unrestrictedLayerName);
		}
		return $authorizedLayers;
	}