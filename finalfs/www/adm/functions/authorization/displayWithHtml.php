<?php

function displayWithHtml($content)
{
    displayHtmlHeader();
    echo $content;
    displayHtmlFooter();
}