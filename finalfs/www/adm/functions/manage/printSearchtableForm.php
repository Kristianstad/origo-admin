<?php

	// Takes a full searchtable target (array), searchtable selectables (array), inheritPosts (array), and helps (array).
	// Prints form fields and buttons that are used to view and edit the configuration for the given searchtable.
	function printSearchtableForm($searchtable, $selectables, $inheritPosts, $helps=array())
	{
		printSimpleEntityForm($searchtable, 'searchtable', array(
			array('name'=>'searchtable_id', 'class'=>'textareaMedium', 'label'=>'Id:'),
			array('name'=>'title', 'class'=>'textareaMedium', 'label'=>'Titel:'),
			array('name'=>'mode', 'class'=>'textareaMedium', 'label'=>'Typ:'),
			array('name'=>'ttl', 'class'=>'textareaSmall', 'label'=>'Ttl:'),
			array('name'=>'limit', 'class'=>'textareaSmall', 'label'=>'Limit:'),
			array('name'=>'usecentroid', 'type'=>'select', 'options'=>booleanSelectOptions(), 'class'=>'miniSelect', 'label'=>'Använd centroid:'),
			array('name'=>'database', 'type'=>'select', 'options'=>$selectables['databases'], 'class'=>'bodySelect', 'label'=>'Databaser:'),
			array('name'=>'schema', 'class'=>'textareaMedium', 'label'=>'Schema:'),
			array('name'=>'table', 'class'=>'textareaMedium', 'label'=>'Tabell:'),
			array('name'=>'searchfield', 'class'=>'textareaMedium', 'label'=>'Sökfält:'),
			array('name'=>'geometryfield', 'class'=>'textareaMedium', 'label'=>'Geometrifält:'),
			array('name'=>'gidfield', 'class'=>'textareaMedium', 'label'=>'Gidfält:'),
			array('name'=>'abstract', 'class'=>'textareaLarge', 'label'=>'Beskrivning:'),
			array('name'=>'info', 'class'=>'textareaLarge', 'label'=>'Info:')
		), $inheritPosts, $helps, array(
			'separator'=>true,
			'deleteConfirm'=>function ($target) { return "Är du säker på att du vill radera söktabellen ".targetId($target)."? Berörda sökmodeller behöver hanteras separat."; }
		));
	}