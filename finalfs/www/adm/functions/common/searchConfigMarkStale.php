<?php

    // Marks the search configuration cache as older than the database, so that it is rebuilt. The old cache is kept and used until the new one is written.
    function searchConfigMarkStale()
    {
        $dir=searchStorageDir();
        $written=searchWriteFile($dir.'config.desired', bin2hex(random_bytes(8)));
        @unlink($dir.'config.attempt');
        return $written;
    }
