<?php

	function printUrlButton($url, $type)
	{
		$typeSwe=toSwedish($type);
		$urlJs=jsonForInlineJs($url);
		echo <<<HERE
			<button class="urlButton" title="Öppna {$typeSwe} i nytt fönster" type="button" onclick='window.open({$urlJs}, "_blank")'>
				Öppna {$typeSwe}
			</button>
		HERE;
	}