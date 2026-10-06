<?php

	function updatedFullTarget($fullTarget, $updatePosts)
	{
		if (isFullTarget($fullTarget))
		{
			$config=targetConfig($fullTarget);
			foreach ($config as $column=>$value)
			{
				$updateKey = 'update'.ucfirst($column);
				if (!array_key_exists($updateKey, $updatePosts))
				{
					continue;
				}
				$newValue = $updatePosts[$updateKey];
				if (isArrayColumn($column))
				{
					$newValue='{'.$newValue.'}';
				}
				$config[$column]=$newValue;
			}
			return makeFullTarget(targetType($fullTarget), $config);
		}
		else
		{
			invalidTarget(__FUNCTION__);
		}
	}