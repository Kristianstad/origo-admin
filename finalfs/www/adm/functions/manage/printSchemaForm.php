<?php

    // Takes a full schema target (array), schema selectables (array), inheritPosts (array), and helps (array).
    // Prints form fields and buttons that are used to view and edit the configuration for the given schema.
    function printSchemaForm($schema, $selectables, $inheritPosts, $helps=array())
    {
        printSimpleEntityForm($schema, 'schema', array(
            array('name'=>'schema_id', 'class'=>'textareaMedium', 'label'=>'Id:'),
            array('name'=>'abstract', 'class'=>'textareaLarge', 'label'=>'Beskrivning:'),
            array('name'=>'keywords', 'class'=>'textareaLarge', 'label'=>'Nyckelord:'),
            array('name'=>'contact', 'type'=>'select', 'options'=>$selectables['contacts'], 'class'=>'bodySelect', 'label'=>'Kontakt:'),
            array('name'=>'origin', 'type'=>'select', 'options'=>$selectables['origins'], 'class'=>'bodySelect', 'label'=>'Ursprungskälla:'),
            array('name'=>'updated', 'class'=>'textareaMedium', 'label'=>'Uppdaterad (åååå-mm-dd):'),
            array('name'=>'update', 'type'=>'select', 'options'=>$selectables['updates'], 'class'=>'bodySelect', 'label'=>'Uppdatering:'),
            array('name'=>'info', 'class'=>'textareaLarge', 'label'=>'Info:')
        ), $inheritPosts, $helps, array(
            'inlineButtons'=>function ($target) { printReadSchemaTablesButton(targetId($target)); },
            'deleteConfirm'=>function ($target) { return "Är du säker att du vill radera all metadata för schemat ".targetId($target)."? Metadata för ingående tabeller hanteras separat."; }
        ));
    }