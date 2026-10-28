<?php

    // Returns array('config' => search models, 'stale' => bool) from the search configuration cache, or null if there is no usable cache.
    // A stale cache is older than the configuration in the database but is still returned until a new one has been written.
    function searchConfigRead()
    {
        $dir=searchStorageDir();
        // The pointer can move (and the old file be removed) between reading it and including the file.
        for ($attempt=0; $attempt < 2; $attempt++)
        {
            $current=searchConfigToken('current');
            if ($current === null)
            {
                return null;
            }
            try
            {
                $config=@include $dir.'config.'.$current.'.php';
            }
            catch (Throwable $exception)
            {
                $config=false;
            }
            if (is_array($config))
            {
                $desired=searchConfigToken('desired');
                return array('config' => $config, 'stale' => ($desired !== null && $desired !== $current));
            }
        }
        return null;
    }
