<?php

    // Takes a full database target (array), inheritPosts (array), and helps (array).
    // Prints form fields and buttons that are used to view and edit the configuration for the given database.
    function printDatabaseForm($database, $inheritPosts, $helps=array())
    {
        printSimpleEntityForm($database, 'database', array(
            array('name'=>'database_id', 'class'=>'textareaMedium', 'label'=>'Id:'),
            array('name'=>'connectionstring', 'class'=>'textareaLarge', 'label'=>'Anslutningssträng:'),
            array('name'=>'usepconnect', 'type'=>'select', 'options'=>booleanSelectOptions(), 'class'=>'miniSelect', 'label'=>'Beständig anslutning:'),
            array('name'=>'abstract', 'class'=>'textareaLarge', 'label'=>'Beskrivning:'),
            array('name'=>'info', 'class'=>'textareaLarge', 'label'=>'Info:')
        ), $inheritPosts, $helps, array(
            'inlineButtons'=>function ($target) { printReadDbSchemasButton(targetId($target)); },
            'deleteConfirm'=>function ($target) { return "Är du säker att du vill radera all metadata för databasen ".targetId($target)."? Metadata för ingående scheman och tabeller hanteras separat."; }
        ));
    }