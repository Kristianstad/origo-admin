<?php

    // Takes a full keyword target (array), inheritPosts (array), and helps (array).
    // Prints form fields and buttons that are used to view and edit the configuration for the given keyword.
    function printKeywordForm($keyword, $inheritPosts, $helps=array())
    {
        printSimpleEntityForm($keyword, 'keyword', array(
            array('name'=>'keyword_id', 'class'=>'textareaMedium', 'label'=>'Id:'),
            array('name'=>'abstract', 'class'=>'textareaLarge', 'label'=>'Beskrivning:'),
            array('name'=>'info', 'class'=>'textareaLarge', 'label'=>'Info:')
        ), $inheritPosts, $helps, array(
            'deleteConfirm'=>function ($target) { return "Är du säker att du vill radera nyckelordet ".targetId($target)."? Referenser till nyckelordet hanteras separat."; }
        ));
    }