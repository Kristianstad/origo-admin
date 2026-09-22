<?php

	function addGroupsToJson($mapGroups, array &$context)
	{
		$groups =& $context['groups'];
		$mapLayers =& $context['mapLayers'];
		$groupsJson = array();
		if (!empty($mapGroups))
		{
			$mapGroups = pgArrayToPhp($mapGroups);
			foreach ($mapGroups as $group)
			{
				$group = array_column_search($group, 'group_id', $groups);
				$groupName = trim(explode('#', $group['group_id'], 2)[0]);
				$mapLayers = array_merge($mapLayers, array($groupName => pgArrayToPhp($group['layers'])));
				if ($groupName !== 'none')
				{
					$groupJson = array('name' => $groupName, 'title' => $group['title']);
					if ($group['expanded'] == 't')
					{
						$groupJson['expanded'] = true;
					}
					if ($group['show_meta'] != 'f' && !empty($group['abstract']))
					{
						$groupJson['abstract'] = $group['abstract'];
					}
					if (!empty(trim($group['groups'], '{}')))
					{
						$groupJson['groups'] = addGroupsToJson($group['groups'], $context);
					}
					$groupsJson[] = $groupJson;
				}
			}
		}
		return $groupsJson;
	}