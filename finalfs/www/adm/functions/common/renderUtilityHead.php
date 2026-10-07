<?php

function renderUtilityHead(array $skin, string $styleName): void
{
    $stylePaths = array(
        'help' => './styles/help.css',
        'info' => './styles/info.css',
        'multiselect' => './styles/multiselect.css',
        'read_json' => './styles/read_json.css',
        'sql_import' => './styles/sql_import.css'
    );
    if (!isset($stylePaths[$styleName])) {
        throw new InvalidArgumentException('Unknown utility stylesheet.');
    }

    echo '<style>';
    printSkinVariables($skin);
    require './styles/common.css';
    require $stylePaths[$styleName];
    echo "</style>\n<script>";
    includeDirectory('./js-functions/common');
    echo '</script>';
}