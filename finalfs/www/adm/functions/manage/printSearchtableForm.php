<?php

    // Takes a full searchtable target (array), searchtable selectables (array), inheritPosts (array), and helps (array).
    // Prints form fields and buttons that are used to view and edit the configuration for the given searchtable.
    function printSearchtableForm($searchtable, $selectables, $inheritPosts, $helps=array())
    {
        printSimpleEntityForm($searchtable, 'searchtable', array(
            array('name'=>'searchtable_id', 'class'=>'textareaMedium', 'label'=>'Id:'),
            array('name'=>'title', 'class'=>'textareaMedium', 'label'=>'Titel:'),
            array('name'=>'titlefield', 'class'=>'textareaMedium', 'label'=>'Titelfält:'),
            array('name'=>'mode', 'type'=>'select', 'options'=>array('simple', 'intelligent'), 'class'=>'bodySelect', 'label'=>'Sökläge:'),
            array('name'=>'sortmode', 'type'=>'select', 'options'=>array('blockunit'), 'class'=>'bodySelect', 'label'=>'Sortering:'),
            array('name'=>'ttl', 'class'=>'textareaSmall', 'label'=>'Cache-tid (s):'),
            array('name'=>'maxcacheagefactor', 'class'=>'textareaSmall', 'label'=>'Cache-faktor:'),
            array('name'=>'limit', 'class'=>'textareaSmall', 'label'=>'Max antal träffar:'),
            array('name'=>'usecentroid', 'type'=>'select', 'options'=>booleanSelectOptions(), 'class'=>'miniSelect', 'label'=>'Använd centroid:'),
            array('name'=>'database', 'type'=>'select', 'options'=>$selectables['databases'], 'class'=>'bodySelect', 'label'=>'Databas:'),
            array('name'=>'dbschema', 'class'=>'textareaMedium', 'label'=>'Schema:'),
            array('name'=>'dbtable', 'class'=>'textareaMedium', 'label'=>'Tabell:'),
            array('name'=>'searchfield', 'class'=>'textareaMedium', 'label'=>'Sökfält:'),
            array('name'=>'geometryfield', 'class'=>'textareaMedium', 'label'=>'Geometrifält:'),
            array('name'=>'gidfield', 'class'=>'textareaMedium', 'label'=>'Gidfält:'),
            array('name'=>'customtype', 'class'=>'textareaMedium', 'label'=>'Egen typ:'),
            array('name'=>'fields', 'class'=>'textareaLarge', 'label'=>'Extrafält:'),
            array('name'=>'abstract', 'class'=>'textareaLarge', 'label'=>'Beskrivning:'),
            array('name'=>'info', 'class'=>'textareaLarge', 'label'=>'Info:')
        ), $inheritPosts, $helps, array(
            'separator'=>true,
            'deleteConfirm'=>function ($target) { return "Är du säker på att du vill radera söktabellen ".targetId($target)."? Berörda sökmodeller behöver hanteras separat."; }
        ));
    }