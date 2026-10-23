<?php
    function fetchWms($serveraddr, $layers, $format, $bbox, $srs, $width, $height, $savefile)
    {
        $ch=curl_init();
        $curlurl=$serveraddr."SERVICE=WMS&VERSION=1.1.1&REQUEST=GetMap&DPI=250&LAYERS=$layers&FORMAT=$format&TRANSPARENT=true&BBOX=$bbox&SRS=$srs&WIDTH=$width&HEIGHT=$height&ttl=0";
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-type: $format") );
        $suffix=trim(explode('/', $format, 2)[1] ?? '');
        if ($suffix === '') {
            error_log('Invalid WMS export format.');
            curl_close($ch);
            return;
        }
        $savefile="$savefile.$suffix";
        curl_setopt($ch, CURLOPT_URL, $curlurl);
        commonCurlSetopt($ch);
        $curlResult = curl_exec($ch);
        $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($curlResult === false || $httpStatus < 200 || $httpStatus >= 300) {
            error_log('WMS export request failed; HTTP status '.$httpStatus.'; cURL error: '.curl_error($ch).'; url: '.$curlurl.'; response: '.exportResponseSnippet($curlResult));
        } elseif (file_put_contents($savefile, $curlResult) === false) {
            error_log('Unable to write WMS export file.');
        }
        curl_close($ch);
    }
