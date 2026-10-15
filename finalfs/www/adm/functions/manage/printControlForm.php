<?php

    // Takes a full control target (array), maps (array), inheritPosts (array), and helps (array).
    // Prints form fields and buttons that are used to view and edit the configuration for the given control.
    function printControlForm($control, $maps, $inheritPosts, $helps=array())
    {
        printSimpleEntityForm($control, 'control', array(
            array('name'=>'control_id', 'class'=>'textareaMedium', 'label'=>'Id:'),
            array('name'=>'abstract', 'class'=>'textareaLarge', 'label'=>'Beskrivning:'),
            array('name'=>'options', 'class'=>'textareaLarge', 'label'=>'Inställningar:'),
            array('name'=>'css', 'class'=>'textareaLarge', 'label'=>'CSS:'),
            array('name'=>'js', 'class'=>'textareaLarge', 'label'=>'JS:'),
            array('name'=>'onload', 'class'=>'textareaLarge', 'label'=>'Origo.on(load)-JS:'),
            array('name'=>'info', 'class'=>'textareaLarge', 'label'=>'Info:')
        ), $inheritPosts, $helps, array(
            'separator'=>true,
            'deleteConfirm'=>function ($target) { return "Är du säker att du vill radera kontrollen ".targetId($target)."? Referenser till kontrollen hanteras separat."; },
            'afterFormSections'=>function ($target, $posts) use ($maps) {
                printAddOperation($target, array('maps'=>array_column($maps['maps'], 'map_id')), 'Lägg till i karta', $posts);
                printRemoveOperation($target, $maps, 'Ta bort från karta', $posts);
            }
        ));
    }