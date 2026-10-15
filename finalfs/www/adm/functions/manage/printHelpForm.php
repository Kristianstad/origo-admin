<?php

    // Takes a full help target (array), inheritPosts (array), and helps (array).
    // Prints form fields and buttons that are used to view and edit the configuration for the given help.
    function printHelpForm($help, $inheritPosts, $helps=array())
    {
        printSimpleEntityForm($help, 'help', array(
            array('name'=>'help_id', 'class'=>'textareaMedium', 'label'=>'Verktygsfält:'),
            array('name'=>'abstract', 'class'=>'textareaLarge', 'label'=>'Hjälptext:'),
            array('name'=>'info', 'class'=>'textareaLarge', 'label'=>'Info:')
        ), $inheritPosts, $helps, array(
            'deleteConfirm'=>function ($target) { return "Är du säker att du vill radera hjälpen ".targetId($target)."?"; }
        ));
    }