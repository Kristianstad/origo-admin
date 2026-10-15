<?php

    // Takes a full origin target (array), inheritPosts (array), and helps (array).
    // Prints form fields and buttons that are used to view and edit the configuration for the given origin.
    function printOriginForm($origin, $inheritPosts, $helps=array())
    {
        printSimpleEntityForm($origin, 'origin', array(
            array('name'=>'origin_id', 'class'=>'textareaMedium', 'label'=>'Id:'),
            array('name'=>'name', 'class'=>'textareaMedium', 'label'=>'Namn:'),
            array('name'=>'web', 'class'=>'textareaMedium', 'label'=>'Webbsida:'),
            array('name'=>'email', 'class'=>'textareaMedium', 'label'=>'E-mail:'),
            array('name'=>'abstract', 'class'=>'textareaLarge', 'label'=>'Beskrivning:'),
            array('name'=>'info', 'class'=>'textareaLarge', 'label'=>'Info:')
        ), $inheritPosts, $helps, array(
            'deleteConfirm'=>function ($target) { return "Är du säker att du vill radera ursprungskällan ".targetId($target)."? Referenser till ursprungskällan hanteras separat."; }
        ));
    }