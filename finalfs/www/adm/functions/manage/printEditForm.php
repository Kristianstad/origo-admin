<?php

	// Takes a full edit target (array), inheritPosts (array), and helps (array).
	// Prints form fields and buttons that are used to view (and annotate) a logged history entry.
	// Only 'abstract'/'info' are editable - the rest is system-generated audit data.
	function printEditForm($edit, $inheritPosts, $helps=array())
	{
		printSimpleEntityForm($edit, 'edit', array(
			array('name'=>'edit_id', 'class'=>'textareaMedium', 'label'=>'Id:', 'readonly'=>true),
			array('name'=>'target_key', 'class'=>'textareaMedium', 'label'=>'Objektnyckel:', 'readonly'=>true),
			array('name'=>'target_table', 'class'=>'textareaMedium', 'label'=>'Tabell:', 'readonly'=>true),
			array('name'=>'target_id', 'class'=>'textareaMedium', 'label'=>'Objekt-id:', 'readonly'=>true),
			array('name'=>'date', 'class'=>'textareaMedium', 'label'=>'Datum:', 'readonly'=>true),
			array('name'=>'action', 'class'=>'textareaMedium', 'label'=>'Åtgärd:', 'readonly'=>true),
			array('name'=>'changed_by', 'class'=>'textareaMedium', 'label'=>'Ändrad av:', 'readonly'=>true),
			array('name'=>'abstract', 'class'=>'textareaLarge', 'label'=>'Beskrivning:'),
			array('name'=>'before_data', 'class'=>'textareaLarge', 'label'=>'Tillstånd före:', 'readonly'=>true),
			array('name'=>'after_data', 'class'=>'textareaLarge', 'label'=>'Tillstånd efter:', 'readonly'=>true),
			array('name'=>'info', 'class'=>'textareaLarge', 'label'=>'Info:')
		), $inheritPosts, $helps, array(
			'deleteConfirm'=>function ($target) { return "Är du säker att du vill radera ändringen ".targetId($target)."? Detta kan påverka möjligheten att ångra/göra om för det berörda objektet."; }
		));
	}