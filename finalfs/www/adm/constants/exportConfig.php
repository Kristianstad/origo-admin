<?php

    // Settings for the export module (export.php). The values are for the Kristianstad installation; adapt them to your server setup.
    $exportConfig = array(
        // Directory where PHP writes the fetched data. FME Flow must be able to read the same directory (shared volume)
        'tempRoot' => '/fmeflow/ikarta',
        // The same directory as FME Flow sees it. It is concatenated directly with the user name, so include a trailing separator if FME needs one
        'fmeResourceRoot' => '$(MSF_docker_fmeflow_ikarta)',
        // FME Flow REST endpoint that runs a workspace synchronously (POST)
        'fmeJobUrl' => 'https://fmeflow.kristianstad.se/fmeapiv4/jobs/sync',
        // FME Flow repository that contains the workspaces named in exportFormats.php
        'fmeRepository' => 'MSF_Origo',
        // Base map for the formats with source 'baseMap': path on the map server (appended to the host), layer name, default file name and image format
        'baseMap' => array(
            'path' => '/qgisserver-internt/ows/grundkarta_qgs',
            'layer' => 'grundkarta',
            'fileName' => 'baskarta',
            'imageFormat' => 'image/png'
        ),
        // Coordinate system used when the request does not specify one
        'defaultSrs' => 'EPSG:3008',
        // Pixel size of the longest side of an exported image
        'longestSide' => 2000,
        // Seconds to wait after a successful FME job before the temporary files are removed
        'cleanupDelay' => 10
    );
