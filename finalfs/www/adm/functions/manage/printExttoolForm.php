<?php

function printExttoolForm($exttool, $inheritPosts, $helps=array())
{
    $url=targetConfigParam($exttool, 'url');
    printSimpleEntityForm($exttool, 'exttool', array(
        array('name'=>'exttool_id', 'class'=>'textareaMedium', 'label'=>'Id:'),
        array('name'=>'url', 'class'=>'textareaLarge', 'label'=>'Url:'),
        array('name'=>'abstract', 'class'=>'textareaLarge', 'label'=>'Beskrivning:'),
        array('name'=>'info', 'class'=>'textareaLarge', 'label'=>'Info:')
    ), $inheritPosts, $helps, array(
        'inlineButtons'=>function ($target) use ($url) {
            if (!empty($url)) {
                printUrlButton($url, 'exttool');
            }
        },
        'deleteConfirm'=>function ($target) { return "Är du säker på att du vill radera det externa verktyget ".targetId($target)."?"; }
    ));
}