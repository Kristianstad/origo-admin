<?php

    function printMultiselectButton($configParam, $value=null, $textareaId, $buttonText='+', $buttonClass='smallMultiselectButton')
    {
        require("./constants/tableAliases.php");
        if (!empty($tableAliases[$configParam]))
        {
            $configParam=$tableAliases[$configParam];
        }
        $buttonValue=$configParam;
        if (!empty($value))
        {
            $buttonValue=$buttonValue.":$value";
        }
        echo <<<HERE
			<button id="{$textareaId}:multiselect" title="Visa/dölj flervalsverktyg" form="multiselectForm" onclick="toggleTopFrame('{$buttonValue}');" type="submit" name="table" value="{$textareaId}::{$buttonValue}" class="{$buttonClass}">
				{$buttonText}
			</button>
		HERE;
    }