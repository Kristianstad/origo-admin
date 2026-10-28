<?php

    // Takes the search models (array keyed by model id) and the request parameters (array). The first parameter whose name is a model id
    // selects the model and its value is the query. Returns array(model, query), or null if no parameter names a model.
    function searchSelectModel($models, $params)
    {
        foreach ($params as $key => $value)
        {
            if (is_string($value) && isset($models[$key]))
            {
                return array($models[$key], trim($value));
            }
        }
        return null;
    }
