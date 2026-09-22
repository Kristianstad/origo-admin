<?php

	function addControlsToJson($mapControls=null, &$mapCss='', &$mapJs='', &$mapOnload='', array &$context=array())
	{
		$map =& $context['map'];
		$controls =& $context['controls'];
		if (!isset($mapControls))
		{
			$mapControls = pgArrayToPhp($map['controls']);
		}
		$controlsJson = array();
		foreach ($mapControls as $control)
		{
			$control = array_column_search($control, 'control_id', $controls);
			$controlName = trim(explode('#', $control['control_id'], 2)[0]);
			$controlJson = array('name' => $controlName);
			if (!empty($control['options']) && $control['options'] !== 'null')
			{
				$controlJson['options'] = json_decode($control['options'], true);
			}
			$controlsJson[] = $controlJson;
			if (!empty($control['css']))
			{
				$mapCss=$mapCss.$control['css'];
			}
			if (!empty($control['js']))
			{
				$mapJs=$mapJs.$control['js'];
			}
			if (!empty($control['onload']))
			{
				$mapOnload=$mapOnload."\n".$control['onload'];
			}
		}
		return $controlsJson;
	}