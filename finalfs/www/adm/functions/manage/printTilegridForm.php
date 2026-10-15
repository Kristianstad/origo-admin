<?php

    // Takes a full tilegrid target (array), inheritPosts (array), and helps (array).
    // Prints form fields and buttons that are used to view and edit the configuration for the given tilegrid.
    function printTilegridForm($tilegrid, $inheritPosts, $helps=array())
    {
        printSimpleEntityForm($tilegrid, 'tilegrid', array(
            array('name'=>'tilegrid_id', 'class'=>'textareaMedium', 'label'=>'Id:'),
            array('name'=>'tilesize', 'class'=>'textareaSmall', 'label'=>'Tile-storlek:'),
            array('name'=>'abstract', 'class'=>'textareaLarge', 'label'=>'Beskrivning:'),
            array('name'=>'info', 'class'=>'textareaLarge', 'label'=>'Info:')
        ), $inheritPosts, $helps, array(
            'deleteConfirm'=>function ($target) { return "Är du säker att du vill radera tilegriden ".targetId($target)."? Referenser till tilegriden hanteras separat."; }
        ));
    }