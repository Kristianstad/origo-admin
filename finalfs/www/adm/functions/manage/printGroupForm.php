<?php

	// Takes a full group target (array), group operationtables (array), inheritPosts (array), and helps (array).
	// Prints form fields and buttons that are used to:
	// 1. View and edit the configuration for the given group.
	// 2. Add or remove given group to/from maps or other groups.
	function printGroupForm($group, $operationTables, $inheritPosts, $helps=array())
	{
		printSimpleEntityForm($group, 'group', array(
			array('name'=>'group_id', 'class'=>'textareaMedium', 'label'=>'Id:'),
			array('name'=>'layers', 'class'=>'textareaLarge', 'label'=>'Lager:'),
			array('name'=>'groups', 'class'=>'textareaLarge', 'label'=>'Grupper:'),
			array('name'=>'title', 'class'=>'textareaMedium', 'label'=>'Titel:'),
			array('name'=>'expanded', 'type'=>'select', 'options'=>booleanSelectOptions(), 'class'=>'miniSelect', 'label'=>'Expanderad:'),
			array('name'=>'show_meta', 'type'=>'select', 'options'=>booleanSelectOptions(), 'class'=>'miniSelect', 'label'=>'Visa metadata:'),
			array('name'=>'abstract', 'class'=>'textareaMedium', 'label'=>'Beskrivning:'),
			array('name'=>'keywords', 'class'=>'textareaLarge', 'label'=>'Nyckelord:'),
			array('name'=>'info', 'class'=>'textareaLarge', 'label'=>'Info:')
		), $inheritPosts, $helps, array(
			'separator'=>true,
			'inlineButtons'=>function ($target) { printConfigPreviewButton('preview', targetId($target)); },
			'deleteConfirm'=>function ($target) { return "Är du säker på att du vill radera gruppen ".targetId($target)."? Ingående lager påverkas ej och referenser till gruppen hanteras separat."; },
			'afterFormSections'=>function ($target, $posts) use ($operationTables) {
				printAddOperation($target, array('maps'=>array_column($operationTables['maps'], 'map_id')), 'Lägg till i karta', $posts);
				printRemoveOperation($target, array('maps'=>$operationTables['maps']), 'Ta bort från karta', $posts);
				printAddOperation($target, array('groups'=>array_column($operationTables['groups'], 'group_id')), 'Lägg till i grupp', $posts);
				printRemoveOperation($target, array('groups'=>$operationTables['groups']), 'Ta bort från grupp', $posts);
			}
		));
	}