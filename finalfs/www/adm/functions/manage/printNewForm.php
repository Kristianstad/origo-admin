<?php

    // Takes a full new target (array), inheritPosts (array), and helps (array).
    // Prints form fields and buttons that are used to view and edit the configuration for the given new.
    function printNewForm($new, $inheritPosts, $helps=array())
    {
        printSimpleEntityForm($new, 'new', array(
            array('name'=>'new_id', 'class'=>'textareaMedium', 'label'=>'Id:'),
            array('name'=>'abstract', 'class'=>'textareaLarge', 'label'=>'Beskrivning:'),
            array('name'=>'text', 'class'=>'textareaLarge', 'label'=>'Text:'),
            array('name'=>'date', 'class'=>'textareaLarge', 'label'=>'Skapad:'),
            array('name'=>'reads', 'class'=>'textareaLarge', 'label'=>'Läst av:'),
            array('name'=>'deletes', 'class'=>'textareaLarge', 'label'=>'Raderad av:'),
            array('name'=>'info', 'class'=>'textareaLarge', 'label'=>'Info:')
        ), $inheritPosts, $helps, array(
            'deleteConfirm'=>function ($target) { return "Är du säker att du vill radera nyheten ".targetId($target)."?"; }
        ));
    }