<?php

    // The export formats (request parameter F) and how each one is handled.
    // source:     'layers' fetches the requested layers, 'baseMap' fetches the base map in exportConfig.php, 'none' fetches nothing
    // fetch:      how data is fetched from the map server: 'dxf', 'shape', 'geojson' or 'wms' (null when nothing is fetched)
    // drawings:   whether drawn layers sent in the request body are passed on to FME
    // workspace:  the FME workspace to run
    // output:     FME output path (parameter D). {userRoot} is the user's directory, {jobRoot} the job's directory, {id} the job id and {V} the request parameter V
    // srs:        whether the coordinate system is passed on (parameter C)
    // format:     value of FME parameter FF, or null to leave it out
    // parameters: request parameters that are passed on to FME unchanged
    // inputs:     FME parameters that list the fetched files (parameter => glob pattern). 'when' limits a parameter to jobs that fetched that image format
    $exportFormats = array(
        'bas_dxf' => array(
            'source' => 'baseMap',
            'fetch' => 'dxf',
            'drawings' => true,
            'workspace' => 'tl_dxf.fmw',
            'output' => '{userRoot}/export-{id}',
            'srs' => true,
            'format' => 'dxf',
            'parameters' => array(),
            'inputs' => array('F' => array('pattern' => '*.dxf'))
        ),
        'bas_tiff' => array(
            'source' => 'baseMap',
            'fetch' => 'wms',
            'drawings' => false,
            'workspace' => 'raster.fmw',
            'output' => '{jobRoot}/D',
            'srs' => true,
            'format' => null,
            'parameters' => array(),
            'inputs' => array('F' => array('pattern' => '*.png'))
        ),
        'tl_bak' => array(
            'source' => 'layers',
            'fetch' => 'wms',
            'drawings' => false,
            'workspace' => 'raster.fmw',
            'output' => '{jobRoot}/D',
            'srs' => true,
            'format' => null,
            'parameters' => array(),
            'inputs' => array(
                'F' => array('pattern' => '*.png', 'when' => 'image/png'),
                'FJ' => array('pattern' => '*.jpeg', 'when' => 'image/jpeg')
            )
        ),
        'tl_dxf' => array(
            'source' => 'layers',
            'fetch' => 'dxf',
            'drawings' => true,
            'workspace' => 'tl_dxf.fmw',
            'output' => '{userRoot}/export-{id}',
            'srs' => true,
            'format' => 'dxf',
            'parameters' => array(),
            'inputs' => array('F' => array('pattern' => '*.dxf'))
        ),
        'tl_shape' => array(
            'source' => 'layers',
            'fetch' => 'shape',
            'drawings' => true,
            'workspace' => 'tl_shape.fmw',
            'output' => '{userRoot}/export-{id}',
            'srs' => true,
            'format' => 'shp',
            'parameters' => array(),
            'inputs' => array('F' => array('pattern' => '*.zip'))
        ),
        'tl_geojson' => array(
            'source' => 'layers',
            'fetch' => 'geojson',
            'drawings' => true,
            'workspace' => 'tl_geojson.fmw',
            'output' => '{userRoot}/export-{id}',
            'srs' => true,
            'format' => 'geojson',
            'parameters' => array(),
            'inputs' => array('F' => array('pattern' => '*.geojson'))
        ),
        'intresse' => array(
            'source' => 'none',
            'fetch' => null,
            'drawings' => false,
            'workspace' => 'intresseyta.fmw',
            'output' => '{jobRoot}/Intresserapport-{V}',
            'srs' => false,
            'format' => null,
            'parameters' => array('V'),
            'inputs' => array()
        ),
        'nnh_csv' => array(
            'source' => 'none',
            'fetch' => null,
            'drawings' => false,
            'workspace' => 'nnh2csv.fmw',
            'output' => '{jobRoot}/Hojddata',
            'srs' => true,
            'format' => null,
            'parameters' => array(),
            'inputs' => array()
        ),
        'stmp_csv' => array(
            'source' => 'none',
            'fetch' => null,
            'drawings' => false,
            'workspace' => 'stompunkt2csv.fmw',
            'output' => '{jobRoot}/Stompunkter',
            'srs' => true,
            'format' => null,
            'parameters' => array(),
            'inputs' => array()
        )
    );
