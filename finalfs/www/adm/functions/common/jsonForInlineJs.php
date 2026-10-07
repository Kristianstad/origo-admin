<?php

function jsonForInlineJs(mixed $value): string
{
	return json_encode($value, JSON_THROW_ON_ERROR | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP);
}