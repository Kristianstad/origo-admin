<?php

    function fetchDxf($serveraddr, $layers, $bbox, $srs, $savefile)
    {
        $ch=curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: text/xml; charset=utf-8'));
        commonCurlSetopt($ch);
        $wfsstr=$serveraddr."SERVICE=WMS&VERSION=1.1.1&REQUEST=GetMap&DPI=250&LAYERS=$layers&FORMAT=application/dxf&BBOX=$bbox&SRS=$srs&WIDTH=1000&HEIGHT=1000&FORMAT_OPTIONS=MODE:SYMBOLLAYERSYMBOLOGY%3BSCALE:1000%3BFORCE_2D:TRUE%3BLAYERATTRIBUTES:TYP&ttl=0";
        //$wfsstr=$serveraddr."SERVICE=WMS&VERSION=1.1.1&REQUEST=GetMap&DPI=250&LAYERS=$layers&FORMAT=application/dxf&BBOX=$bbox&SRS=$srs&WIDTH=1000&HEIGHT=1000&FORCE_2D:2D&FORMAT_OPTIONS=MODE:SYMBOLLAYERSYMBOLOGY&LAYERATTRIBUTES:TYP&SCALE:1000&ttl=0";
        //$wfsstr=$serveraddr."SERVICE=WMS&VERSION=1.1.1&REQUEST=GetMap&DPI=250&LAYERS=$layers&FORMAT=application/dxf&BBOX=$bbox&SRS=$srs&WIDTH=1000&HEIGHT=1000&FORMAT_OPTIONS=MODE:SYMBOLLAYERSYMBOLOGY;SCALE:1000;LAYERATTRIBUTES:TYP,TYP,TYP,TYP,TYP,TYP,TYP,TYP,TYP,TYP,TYP,TYP,TYP,TYP,TYP,TYP,TYP,TYP,TYP,TYP,TYP,TYP,TYP,TYP,TYP,TYP,TYP,TYP,TYP,TYP,TYP,TYP,TYP,TYP,TYP,TYP,TYP,TYP,TYP,TYP,TYP,TYP,TYP,TYP,TYP,TYP,TYP,TYP,TYP,TYP,TYP,TYP,TYP,TYP,TYP,TYP,TYP,TYP,TYP,TYP,TYP,TYP,TYP;FORCE_2D:2D";

        curl_setopt($ch, CURLOPT_URL, $wfsstr);
        $curlResult = curl_exec($ch);
        $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($curlResult === false || $httpStatus < 200 || $httpStatus >= 300) {
            error_log('DXF export request failed; HTTP status '.$httpStatus.'; cURL error: '.curl_error($ch).'; url: '.$wfsstr.'; response: '.exportResponseSnippet($curlResult));
        } elseif (stripos($curlResult, 'DXF created') === false) {
            error_log('DXF export response was not a DXF; url: '.$wfsstr.'; response: '.exportResponseSnippet($curlResult));
        } elseif (file_put_contents($savefile, $curlResult) === false) {
            error_log('Unable to write DXF export file.');
        }
        curl_close($ch);
    }
