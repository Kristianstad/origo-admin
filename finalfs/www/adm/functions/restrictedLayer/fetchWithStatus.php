<?php
    // Hämtar en URL och läser ut HTTP-statuskoden ur $http_response_header.
    // Gör om anropet vid transienta serverfel (5xx) eller om anropet
    // misslyckades helt, max $maxAttempts gånger. 4xx och andra "riktiga"
    // fel görs INTE om, eftersom de sannolikt inte löser sig av att man
    // frågar igen, och en extra request bara belastar backend-poolen i onödan.
    function fetchWithStatus($url, $context, $maxAttempts = 2)
    {
        $attempt = 0;
        $content = false;
        $statusCode = 0;
 
        while ($attempt < $maxAttempts)
        {
            $attempt++;
            $content = @file_get_contents($url, false, $context);
 
            $statusCode = 0;
            if (isset($http_response_header))
            {
                // Första raden ser ut som "HTTP/1.1 200 OK"
                foreach ($http_response_header as $header)
                {
                    if (preg_match('#^HTTP/\S+\s+(\d{3})#', $header, $matches))
                    {
                        $statusCode = (int)$matches[1];
                        break;
                    }
                }
            }
 
            $isServerError = ($statusCode >= 500 && $statusCode < 600);
            if ($content !== false && !$isServerError)
            {
                break;
            }
 
            // Vänta lite innan nästa försök, så vi inte piskar en redan
            // belastad backend direkt igen. Bara om det faktiskt blir
            // ett nytt försök (dvs. inte efter sista varvet).
            if ($attempt < $maxAttempts)
            {
                usleep(150000); // 150 ms
            }
        }
 
        return array('content' => $content, 'status' => $statusCode);
    }