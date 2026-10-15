<?php

function displayHtmlHeader()
{
    echo '<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<style>';
    require("./styles/authorization.css");
    echo '</style>
</head>
<body>';
}