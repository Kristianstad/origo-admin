<?php

    // Returns the cached restricted layer list, building it on first use; null means no usable cache could be obtained.
    function restrictedCacheLoad($dbh=null): ?array
    {
        $dir=cacheStorageDir();
        for ($attempt=0; $attempt < 2; $attempt++)
        {
            $token=@file_get_contents($dir.'restricted.current');
            if ($token !== false && preg_match('/\A[a-f0-9]{16}\z/', $token))
            {
                try
                {
                    $restrictedLayers=@include $dir.'restricted.'.$token.'.php';
                }
                catch (Throwable $exception)
                {
                    $restrictedLayers=null;
                }
                if (is_array($restrictedLayers))
                {
                    return $restrictedLayers;
                }
            }
            if ($attempt === 0)
            {
                restrictedCacheRefresh($dbh);
            }
        }
        return null;
    }
