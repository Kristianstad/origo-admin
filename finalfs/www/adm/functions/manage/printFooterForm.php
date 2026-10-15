<?php

    // Takes a full footer target (array), inheritPosts (array), and helps (array).
    // Prints form fields and buttons that are used to view and edit the configuration for the given footer.
    function printFooterForm($footer, $inheritPosts, $helps=array())
    {
        printSimpleEntityForm($footer, 'footer', array(
            array('name'=>'footer_id', 'class'=>'textareaMedium', 'label'=>'Id:'),
            array('name'=>'img', 'class'=>'textareaLarge', 'label'=>'Logotyp:'),
            array('name'=>'url', 'class'=>'textareaLarge', 'label'=>'Url:'),
            array('name'=>'text', 'class'=>'textareaMedium', 'label'=>'Text:'),
            array('name'=>'abstract', 'class'=>'textareaLarge', 'label'=>'Beskrivning:'),
            array('name'=>'info', 'class'=>'textareaLarge', 'label'=>'Info:')
        ), $inheritPosts, $helps, array(
            'deleteConfirm'=>function ($target) { return "Är du säker att du vill radera sidfoten ".targetId($target)."? Referenser till sidfoten hanteras separat."; }
        ));
    }