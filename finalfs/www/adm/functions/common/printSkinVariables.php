<?php

function printSkinVariables(array $skin): void
{
    $properties=array(
        'bg_color'=>'--skin-bg',
        'surface_color'=>'--skin-surface',
        'text_color'=>'--skin-text',
        'primary_color'=>'--skin-primary',
        'button_text_color'=>'--skin-button-text',
        'header_color'=>'--skin-header',
        'header_text_color'=>'--skin-header-text',
        'focus_text_color'=>'--skin-focus-text',
        'focus_font_weight'=>'--skin-focus-weight',
        'no_focus_font_weight'=>'--skin-no-focus-weight',
        'hover_color'=>'--skin-hover',
        'active_color'=>'--skin-active',
        'accent_color'=>'--skin-accent',
        'border_color'=>'--skin-border',
        'danger_color'=>'--skin-danger',
        'font_family'=>'--skin-font',
        'border_radius'=>'--skin-radius',
        'shadow_color'=>'--skin-shadow'
    );
    echo "\t\t:root{\n";
    foreach ($properties as $column => $cssVariable)
    {
        if (!empty($skin[$column]))
        {
            $safeValue=preg_replace('/[^a-zA-Z0-9#%.,\-\(\) ]/', '', (string) $skin[$column]);
            if ($safeValue !== '')
            {
                echo "\t\t\t{$cssVariable}:{$safeValue};\n";
            }
        }
    }
    echo "\t\t}\n";
}