<?php

    function userAuthorized($user, $restrictedLayer)
    {
        if (empty($user))
        {
            return false;
        }

        if (!empty($user['id']) && in_array($user['id'], $restrictedLayer['authorized_users']))
        {
            return true;
        }

        $userGroups=is_array($user['groups'] ?? null) ? $user['groups'] : array();
        $authorizedGroups=is_array($restrictedLayer['authorized_groups'] ?? null) ? $restrictedLayer['authorized_groups'] : array();
        $userGroups=array_map(function ($group) { return mb_strtolower($group, 'UTF-8'); }, $userGroups);
        $authorizedGroups=array_map(function ($group) { return mb_strtolower($group, 'UTF-8'); }, $authorizedGroups);

        return !empty(array_intersect($userGroups, $authorizedGroups));
    }