<?php

function printSkinForm($skin, $inheritPosts, $helps=array())
{
    $skinId=targetId($skin);
    printSimpleEntityForm($skin, 'skin', array(
        array('name'=>'skin_id', 'class'=>'textareaMedium', 'label'=>'Id:'),
        array('name'=>'abstract', 'class'=>'textareaLarge', 'label'=>'Beskrivning:'),
        array('name'=>'info', 'class'=>'textareaLarge', 'label'=>'Info:'),
        array('name'=>'bg_color', 'class'=>'textareaSmall', 'label'=>'Bakgrundsfärg:'),
        array('name'=>'surface_color', 'class'=>'textareaSmall', 'label'=>'Ytfärg:'),
        array('name'=>'text_color', 'class'=>'textareaSmall', 'label'=>'Textfärg:'),
        array('name'=>'primary_color', 'class'=>'textareaSmall', 'label'=>'Primärfärg:'),
        array('name'=>'button_text_color', 'class'=>'textareaSmall', 'label'=>'Knapptextfärg:'),
        array('name'=>'header_color', 'class'=>'textareaSmall', 'label'=>'Rubrikfärg:'),
        array('name'=>'header_text_color', 'class'=>'textareaSmall', 'label'=>'Rubriktextfärg:'),
        array('name'=>'focus_text_color', 'class'=>'textareaSmall', 'label'=>'Fokustextfärg:'),
        array('name'=>'focus_font_weight', 'class'=>'textareaSmall', 'label'=>'Fokusvikt:'),
        array('name'=>'no_focus_font_weight', 'class'=>'textareaSmall', 'label'=>'Icke-fokusvikt:'),
        array('name'=>'hover_color', 'class'=>'textareaSmall', 'label'=>'Hover-färg:'),
        array('name'=>'active_color', 'class'=>'textareaSmall', 'label'=>'Aktiv färg:'),
        array('name'=>'accent_color', 'class'=>'textareaSmall', 'label'=>'Accentfärg:'),
        array('name'=>'border_color', 'class'=>'textareaSmall', 'label'=>'Ramfärg:'),
        array('name'=>'danger_color', 'class'=>'textareaSmall', 'label'=>'Varningsfärg:'),
        array('name'=>'font_family', 'class'=>'textareaMedium', 'label'=>'Typsnitt:'),
        array('name'=>'border_radius', 'class'=>'textareaSmall', 'label'=>'Hörnradie:'),
        array('name'=>'shadow_color', 'class'=>'textareaSmall', 'label'=>'Skuggfärg:')
    ), $inheritPosts, $helps, array(
        'inlineButtons'=>function ($target) use ($skinId) {
            printActivateSkinButton($skinId);
        },
        'deleteConfirm'=>function ($target) { return "Är du säker på att du vill radera utseendet ".targetId($target)."?"; }
    ));
}