<?php

    // Takes a full format target (array), inheritPosts (array), and helps (array).
    // Prints form fields and buttons that are used to view and edit the configuration for the given format.
    function printFormatForm($format, $inheritPosts, $helps=array())
    {
        printSimpleEntityForm($format, 'format', array(
            array('name'=>'format_id', 'class'=>'textareaMedium', 'label'=>'Format:'),
            array('name'=>'abstract', 'class'=>'textareaLarge', 'label'=>'Beskrivning:'),
            array('name'=>'info', 'class'=>'textareaLarge', 'label'=>'Info:')
        ), $inheritPosts, $helps, array(
            'deleteConfirm'=>function ($target) { return "Är du säker att du vill radera formatet ".targetId($target)."? Referenser till formatet hanteras separat."; }
        ));
    }