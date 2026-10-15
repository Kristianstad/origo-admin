<?php

function printInfogroupForm($infogroup, $operationTables, $inheritPosts, $helps=array())
{
    printSimpleEntityForm($infogroup, 'infogroup', array(
        array('name'=>'infogroup_id', 'class'=>'textareaMedium', 'label'=>'Id:'),
        array('name'=>'infogroups', 'class'=>'textareaLarge', 'label'=>'Informationsgrupper:'),
        array('name'=>'layers', 'class'=>'textareaLarge', 'label'=>'Lager:'),
        array('name'=>'tables', 'class'=>'textareaLarge', 'label'=>'Tabeller:'),
        array('name'=>'abstract', 'class'=>'textareaLarge', 'label'=>'Beskrivning:'),
        array('name'=>'info', 'class'=>'textareaLarge', 'label'=>'Info:')
    ), $inheritPosts, $helps, array(
        'deleteConfirm'=>function ($target) { return "Är du säker på att du vill radera informationsgruppen ".targetId($target)."?"; },
        'afterFormSections'=>function ($target, $posts) use ($operationTables) {
            printAddRemoveOperations($target, $operationTables, $posts);
        }
    ));
}