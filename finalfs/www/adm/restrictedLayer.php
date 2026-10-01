<?php
/*
restrictedLayer.php
 ├─ includeDirectory("./functions/common")
 ├─ includeDirectory("./functions/restrictedLayer")
 ├─ readAndCloseSession()                    [common]
 ├─ includeFileConstant('RESTRICTEDLAYERS')  [common] → laddar RESTRICTEDLAYERS-konstanten (lista över skyddade lager)
 ├─ (om authMethod === 'ldap') initUserLdap()   → öppnar en egen databasanslutning när $dbh saknas
 ├─ tolkar QUERY_STRING manuellt (byter ut '&?' och '?' mot '&', för att hantera Origos url-format)
 ├─ bygger $call = URL mot bakomliggande karttjänst (restrictedServiceUrl + path + querystring)
 ├─ plockar ut begärda lagernamn ur LAYERS/LAYER/TYPENAME-parametrar
 ├─ avgör om anropet är "obegränsat" (GetCapabilities, eller inga av de begärda lagren är skyddade)
 │
 ├─ OM obegränsat:
 │    └─ fetchWithStatus($call, ...)  [restrictedLayer] → hämtar och returnerar svaret oförändrat
 │
 └─ OM begränsat:
      ├─ authorization_names_filter($callLayers)  [restrictedLayer]
      │    └─ authorization_filter($layerNames)    [restrictedLayer]
      │         └─ userAuthorized($_SESSION['user'], $restrictedLayer)  [restrictedLayer]
      ├─ OM alla begärda lager är godkända → fetchWithStatus(...) som ovan
      ├─ OM GetLegendGraphic → returnerar en "lås"-bild istället för riktig legend
      ├─ OM GetMap → returnerar en tom/transparent bild istället för kartdata
      ├─ OM GetFeatureInfo → returnerar tom GeoJSON FeatureCollection
      └─ annars → finishError500('saknar rättigheter')  [restrictedLayer]
*/

	// Tell browsers to not cache response
	header("Cache-Control: must-revalidate, max-age=0, s-maxage=0, no-cache, no-store");
	
	// Expose specific functions
	require_once("./functions/includeDirectory.php");
	
	// Expose all functions in given folders
	includeDirectory("./functions/common");
	includeDirectory("./functions/restrictedLayer");
	
	require './constants/authMethod.php';
	readAndCloseSession();
	includeFileConstant('RESTRICTEDLAYERS');
	if ($authMethod === 'ldap')
	{
		initUserLdap();
	}
	//ini_set('output_buffering', 'off');
	if (isset($_SERVER['QUERY_STRING']))
	{
		$_SERVER['QUERY_STRING']=str_replace('&?', '&', $_SERVER['QUERY_STRING']);
		$_SERVER['QUERY_STRING']=str_replace('?', '&', $_SERVER['QUERY_STRING']);
		parse_str($_SERVER['QUERY_STRING'], $queryarray);
		$queryarray=array_change_key_case($queryarray, CASE_UPPER);
		$path=$queryarray['PATH'];
		unset ($queryarray['PATH']);
		require("./constants/restrictedServiceUrl.php");
		$call=$restrictedServiceUrl.$path."?".$_SERVER['QUERY_STRING'];
		if ($queryarray['REQUEST'] === 'GetCapabilities' || (!isset($queryarray['FORMAT']) && !isset($queryarray['INFO_FORMAT']) && $queryarray['OUTPUTFORMAT'] === 'geojson'))
		{
			header('Content-Type: text/xml; charset=utf-8');
		}
		elseif (isset($queryarray['INFO_FORMAT']))
		{
			header('Content-Type: '.$queryarray['INFO_FORMAT']);
		}
		else
		{
			header('Content-Type: '.$queryarray['FORMAT']);
		}
		if (isset($queryarray['LAYERS']))
		{
			$callLayers=explode(',', $queryarray['LAYERS']);
		}
		else
		{
			$callLayers=array();
		}
		if (isset($queryarray['LAYER']))
		{
			$callLayers=array_merge($callLayers, explode(',', $queryarray['LAYER']));
		}
		if (isset($queryarray['TYPENAME']))
		{
			$callLayers=array_merge($callLayers, explode(',', $queryarray['TYPENAME']));
		}
		$unrestricted = false;
		//if (stripos($queryarray['SERVICE'], 'wms') !== false && stripos($queryarray['REQUEST'], 'getmap') === false && stripos($queryarray['REQUEST'], 'getfeatureinfo') === false && stripos($queryarray['REQUEST'], 'getlegendgraphic') === false)
		if (stripos($queryarray['SERVICE'], 'wms') !== false && stripos($queryarray['REQUEST'], 'getcapabilities') !== false)
		{
			$unrestricted = true;
		}
		elseif (count($callLayers) === count(array_diff($callLayers, array_column(RESTRICTEDLAYERS, 'name'))))
		{
			$unrestricted = true;
		}
		$headers=array("Connection: close");
		if (!empty($queryarray['TTL']))
		{
			$headers[]="ttl: ".$queryarray['TTL'];
		}
		if ($unrestricted)
		{
			$opts=array('http'=>array('protocol_version'=>1.1, 'method'=>"GET",'header'=>$headers, 'ignore_errors'=>true));
			$context=stream_context_create($opts);
			$result=fetchWithStatus($call, $context);
			if ($result['status'] > 0)
			{
				http_response_code($result['status']);
			}
			echo $result['content'];
		}
		else
		{
			header('Restricted: 1');
			if (empty(array_diff($callLayers, authorization_names_filter($callLayers))))
			{
				$opts=array('http'=>array('protocol_version'=>1.1, 'method'=>"GET",'header'=>$headers, 'ignore_errors'=>true));
				$context=stream_context_create($opts);
				$result=fetchWithStatus($call, $context);
				if ($result['status'] > 0)
				{
					http_response_code($result['status']);
				}
				echo $result['content'];
			}
			elseif ($queryarray['REQUEST'] === 'GetLegendGraphic')
			{
				$lockPng=file_get_contents('../img/png/lock_yellow.png');
				echo $lockPng;
			}
			elseif ($queryarray['REQUEST'] === 'GetMap')
			{
				$emptyPng=file_get_contents('../img/png/empty.png');
				echo $emptyPng;
			}
			elseif ($queryarray['REQUEST'] === 'GetFeatureInfo')
			{
				header('Content-Type: application/json; charset=utf-8');
				echo '{"features":[],"type":"FeatureCollection"}';
			}
			else
			{
				finishError500('saknar rättigheter');
			}
		}
	}