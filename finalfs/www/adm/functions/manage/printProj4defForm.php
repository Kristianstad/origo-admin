<?php

    // Takes a full proj4def target (array), inheritPosts (array), and helps (array).
    // Prints form fields and buttons that are used to view and edit the configuration for the given proj4def.
    function printProj4defForm($proj4def, $inheritPosts, $helps=array())
    {
        printSimpleEntityForm($proj4def, 'proj4def', array(
            array('name'=>'code', 'class'=>'textareaMedium', 'label'=>'Kod:'),
            array('name'=>'projection', 'class'=>'textareaLarge', 'label'=>'Projektion:'),
            array('name'=>'projectionextent', 'class'=>'textareaMedium', 'label'=>'Projektionsutbredning:'),
            array('name'=>'alias', 'class'=>'textareaMedium', 'label'=>'Alias:'),
            array('name'=>'abstract', 'class'=>'textareaLarge', 'label'=>'Beskrivning:'),
            array('name'=>'info', 'class'=>'textareaLarge', 'label'=>'Info:')
        ), $inheritPosts, $helps, array(
            'deleteConfirm'=>function ($target) { return "Är du säker att du vill radera proj4def ".targetId($target)."? Referenser till aktuell proj4def hanteras separat."; }
        ));
    }