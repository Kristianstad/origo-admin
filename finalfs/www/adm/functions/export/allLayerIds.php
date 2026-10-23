<?php

    function allLayerIds(array $mapOrGroup, array $groups, array $allLayerIds = array(), array $visitedGroupIds = array()): array
    {
        $allLayerIds = array_merge($allLayerIds, pgArrayToPhp($mapOrGroup['layers'] ?? null));
        foreach (pgArrayToPhp($mapOrGroup['groups'] ?? null) as $groupId) {
            if (in_array($groupId, $visitedGroupIds, true)) {
                continue;
            }
            $visitedGroupIds[] = $groupId;
            $group = arrayColumnSearch($groupId, 'group_id', $groups);
            if (!empty($group)) {
                $allLayerIds = allLayerIds($group, $groups, $allLayerIds, $visitedGroupIds);
            }
        }
        return array_values(array_unique($allLayerIds));
    }
