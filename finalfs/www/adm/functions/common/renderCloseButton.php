<?php

function renderCloseButton(string $class = ''): string
{
	$classAttribute = $class === '' ? '' : ' class="'.htmlspecialchars($class, ENT_QUOTES, 'UTF-8').'"';
	return '<button'.$classAttribute.' type="button" title="Stäng" aria-label="Stäng" onclick="closeTopFrame();"><span aria-hidden="true">&#x22A0;</span></button>';
}