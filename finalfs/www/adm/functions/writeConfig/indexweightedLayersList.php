<?php

	// Uses writeConfig functions: arrayMove

	function indexweightedLayersList($layersList, array &$context)
	{
		$layers =& $context['layers'];
		$layerweights=array();
		$whileDo=true;
		while ($whileDo)
		{
			$whileDo=false;
			foreach ($layersList as $key=>$listItem)
			{
				$layerId=explode('>', $listItem)[1];
				$layer=arrayColumnSearch($layerId, 'layer_id', $layers);
				if (isset($layer['indexweight']) && !isset($layerweights[$layerId]))
				{
					$layerweights[$layerId]=$layer['indexweight'];
					$from=$key;
					$to=$key-$layer['indexweight'];
					arrayMove($layersList, $from, $to);
					$whileDo=true;
					break;
				}
			}
		}
		return $layersList;
	}