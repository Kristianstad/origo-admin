<?php

function renderIconButton(array $button, string $icon, ?string $caption = null, string $iconClass = ''): string
{
	$title = escapeHtml((string) ($button['title'] ?? ''));
	$label = escapeHtml((string) ($button['aria-label'] ?? $button['title'] ?? ''));
	$class = escapeHtml((string) ($button['class'] ?? ''));
	$type = escapeHtml((string) ($button['type'] ?? 'button'));
	$html = '<button type="'.$type.'" title="'.$title.'" aria-label="'.$label.'" class="'.$class.'"';

	foreach (array('name', 'value', 'onclick', 'form') as $attribute)
	{
		if (array_key_exists($attribute, $button))
		{
			$html .= ' '.$attribute.'="'.escapeHtml((string) $button[$attribute]).'"';
		}
	}

	$html .= '><span aria-hidden="true"';
	if ($iconClass !== '')
	{
		$html .= ' class="'.escapeHtml($iconClass).'"';
	}
	$html .= '>'.escapeHtml($icon).'</span>';
	if ($caption !== null)
	{
		$html .= '<span class="operationCaption">'.escapeHtml($caption).'</span>';
	}
	return $html.'</button>';
}