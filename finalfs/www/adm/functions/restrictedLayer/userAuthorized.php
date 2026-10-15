<?php

    function userAuthorized($user, $restrictedLayer)
    {
        if (empty($user))
        {
            return false;
        }
        else
        {
            if (!empty($user['id']) && in_array($user['id'], $restrictedLayer['authorized_users']))
            {
                return true;
            }
            elseif (!empty($user['groups']) && !empty(array_intersect($user['groups'], $restrictedLayer['authorized_groups'])))
            {
                return true;
            }
            else
            {
                return false;
            }
        }
    }