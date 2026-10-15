<?php

    // Takes a full plugin target (array), maps (array), inheritPosts (array), and helps (array).
    // Prints form fields and buttons that are used to view and edit the configuration for the given plugin.
    function printPluginForm($plugin, $maps, $inheritPosts, $helps=array())
    {
        printSimpleEntityForm($plugin, 'plugin', array(
            array('name'=>'plugin_id', 'class'=>'textareaMedium', 'label'=>'Id:'),
            array('name'=>'abstract', 'class'=>'textareaLarge', 'label'=>'Beskrivning:'),
            array('name'=>'css_files', 'class'=>'textareaLarge', 'label'=>'CSS-filer:'),
            array('name'=>'css', 'class'=>'textareaLarge', 'label'=>'CSS:'),
            array('name'=>'js_files', 'class'=>'textareaLarge', 'label'=>'JS-filer:'),
            array('name'=>'js', 'class'=>'textareaLarge', 'label'=>'JS:'),
            array('name'=>'onload', 'class'=>'textareaLarge', 'label'=>'Origo.on(load)-JS:'),
            array('name'=>'info', 'class'=>'textareaLarge', 'label'=>'Info:')
        ), $inheritPosts, $helps, array(
            'separator'=>true,
            'deleteConfirm'=>function ($target) { return "Är du säker att du vill radera pluginen ".targetId($target)."? Referenser till pluginen hanteras separat."; },
            'afterFormSections'=>function ($target, $posts) use ($maps) {
                printAddOperation($target, array('maps'=>array_column($maps['maps'], 'map_id')), 'Lägg till i karta', $posts);
                printRemoveOperation($target, $maps, 'Ta bort från karta', $posts);
            }
        ));
    }