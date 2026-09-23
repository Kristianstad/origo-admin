<?php

	function printUrlButton($url, $type)
	{
		$typeSwe=toSwedish($type);
		$urlJs=json_encode($url, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP);
		echo <<<HERE
			<button title="Öppna {$typeSwe} i nytt fönster" type="button" onclick='window.open({$urlJs}, "_blank")'>
				Öppna {$typeSwe}
			</button>
		HERE;
	}