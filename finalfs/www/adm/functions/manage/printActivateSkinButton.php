<?php

function printActivateSkinButton(string $skinId): void
{
	$skinIdAttribute=escapeHtml($skinId);
	echo <<<HTML
		<button title="Byt till detta utseende" type="button" data-manage-action="activate-skin" data-skin-id="{$skinIdAttribute}">Byt till detta utseende</button>
HTML;
}