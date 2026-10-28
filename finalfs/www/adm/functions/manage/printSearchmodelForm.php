<?php

    // Takes a full searchmodel target (array), inheritPosts (array), and helps (array).
    // Prints form fields and buttons that are used to view and edit the configuration for the given searchmodel.
    function printSearchmodelForm($searchmodel, $inheritPosts, $helps=array())
    {
        printSimpleEntityForm($searchmodel, 'searchmodel', array(
            array('name'=>'searchmodel_id', 'class'=>'textareaMedium', 'label'=>'Id:'),
            array('name'=>'searchtables', 'class'=>'textareaLarge', 'label'=>'Söktabeller:'),
            array('name'=>'limit', 'class'=>'textareaSmall', 'label'=>'Max antal träffar:'),
            array('name'=>'liverows', 'class'=>'textareaSmall', 'label'=>'Rader vid direktsökning:'),
            array('name'=>'abstract', 'class'=>'textareaLarge', 'label'=>'Beskrivning:'),
            array('name'=>'info', 'class'=>'textareaLarge', 'label'=>'Info:')
        ), $inheritPosts, $helps, array(
            'deleteConfirm'=>function ($target) { return "Är du säker på att du vill radera sökmodellen ".targetId($target)."?"; }
        ));
    }
