<?php
    function fetchShape($serveraddr, $layers, $bbox, $srs, $savefile)
    {
        $ch=curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: text/xml; charset=utf-8'));
        commonCurlSetopt($ch);
        $wfsstr=$serveraddr."SERVICE=WFS&VERSION=1.0.0&REQUEST=GetFeature&TYPENAME=$layers&OUTPUTFORMAT=SHP&BBOX=$bbox&SRSNAME=$srs";
        curl_setopt($ch, CURLOPT_URL, $wfsstr);
        $curlResult = curl_exec($ch);
        $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($curlResult === false || $httpStatus < 200 || $httpStatus >= 300) {
            error_log('Shapefile export request failed; HTTP status '.$httpStatus.'; cURL error: '.curl_error($ch).'; url: '.$wfsstr.'; response: '.exportResponseSnippet($curlResult));
        } elseif (file_put_contents($savefile, $curlResult) === false) {
            error_log('Unable to write shapefile export file.');
        }
        curl_close($ch);
    }
