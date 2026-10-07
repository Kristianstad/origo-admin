<?php

	// Takes a full mapstate target (array), inheritPosts (array), and helps (array).
	// Prints form fields and buttons that are used to view and edit the configuration for the given mapstate.
	function printMapstateForm($mapstate, $inheritPosts, $helps=array())
	{
		printSimpleEntityForm($mapstate, 'mapstate', array(
			array('name'=>'mapstate_id', 'class'=>'textareaMedium', 'label'=>'Id:', 'readonly'=>true),
			array('name'=>'abstract', 'class'=>'textareaLarge', 'label'=>'Beskrivning:'),
			array('name'=>'mapurl', 'class'=>'textareaLarge', 'label'=>'Kart-url:'),
			array('name'=>'state', 'class'=>'textareaLarge', 'label'=>'Mapstate:'),
			array('name'=>'created', 'class'=>'textareaMedium', 'label'=>'Skapad:', 'readonly'=>true),
			array('name'=>'lastuse', 'class'=>'textareaMedium', 'label'=>'Senast använd:', 'readonly'=>true),
			array('name'=>'preserve', 'type'=>'select', 'options'=>booleanSelectOptions(), 'class'=>'miniSelect', 'label'=>'Rensas ej:'),
			array('name'=>'info', 'class'=>'textareaLarge', 'label'=>'Info:')
		), $inheritPosts, $helps, array(
			'deleteConfirm'=>function ($target) { return "Är du säker att du vill radera mapstatet ".targetId($target)."? Referenser till mapstatet hanteras separat."; }
		));
	}