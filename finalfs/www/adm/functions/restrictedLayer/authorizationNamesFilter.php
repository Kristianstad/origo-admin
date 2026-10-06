<?php

	function authorizationNamesFilter($layerNames)
	{
		return array_column(authorizationFilter($layerNames), 'name');
	}