<?php

    // Takes a full contact target (array), inheritPosts (array), and helps (array).
    // Prints form fields and buttons that are used to view and edit the configuration for the given contact.
    function printContactForm($contact, $inheritPosts, $helps=array())
    {
        printSimpleEntityForm($contact, 'contact', array(
            array('name'=>'contact_id', 'class'=>'textareaMedium', 'label'=>'Id:'),
            array('name'=>'name', 'class'=>'textareaMedium', 'label'=>'Namn:'),
            array('name'=>'web', 'class'=>'textareaMedium', 'label'=>'Webbsida:'),
            array('name'=>'email', 'class'=>'textareaMedium', 'label'=>'E-mail:'),
            array('name'=>'abstract', 'class'=>'textareaLarge', 'label'=>'Beskrivning:'),
            array('name'=>'info', 'class'=>'textareaLarge', 'label'=>'Info:')
        ), $inheritPosts, $helps, array(
            'deleteConfirm'=>function ($target) { return "Är du säker att du vill radera kontakten ".targetId($target)."? Referenser till kontakten hanteras separat."; }
        ));
    }