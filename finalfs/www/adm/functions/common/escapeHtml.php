<?php

function escapeHtml(string $value): string
{
	return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}