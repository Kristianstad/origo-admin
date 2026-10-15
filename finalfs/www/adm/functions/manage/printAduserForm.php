<?php

    // Takes a full aduser target (array), inheritPosts (array), and helps (array).
    // Prints form fields and buttons that are used to view and edit the configuration for the given aduser.
    function printAduserForm($aduser, $inheritPosts, $helps=array())
    {
        $fields=array(
            array('name'=>'aduser_id', 'class'=>'textareaMedium', 'label'=>'Id:', 'readonly'=>true),
            array('name'=>'name', 'class'=>'textareaMedium', 'label'=>'Namn:', 'readonly'=>true),
            array('name'=>'email', 'class'=>'textareaMedium', 'label'=>'E-mail:', 'readonly'=>true),
            array('name'=>'company', 'class'=>'textareaMedium', 'label'=>'Företag/Förvaltning:', 'readonly'=>true),
            array('name'=>'department', 'class'=>'textareaMedium', 'label'=>'Avdelning:', 'readonly'=>true),
            array('name'=>'lastlogin', 'class'=>'textareaMedium', 'label'=>'Senast inloggad:', 'readonly'=>true),
            array('name'=>'adgroups', 'class'=>'textareaMedium', 'label'=>'AD-grupper:', 'readonly'=>true),
            array('name'=>'abstract', 'class'=>'textareaLarge', 'label'=>'Beskrivning:'),
            array('name'=>'info', 'class'=>'textareaLarge', 'label'=>'Info:')
        );
        printSimpleEntityForm($aduser, 'aduser', $fields, $inheritPosts, $helps, array(
            'deleteConfirm'=>function ($target) { return "Är du säker att du vill radera AD-användaren ".targetId($target)."? Referenser till AD-användaren hanteras separat."; }
        ));
    }