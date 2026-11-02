<?php

    function authorizationNamesFilter($layerNames, $restrictedLayers)
    {
        return array_column(authorizationFilter($layerNames, $restrictedLayers), 'name');
    }