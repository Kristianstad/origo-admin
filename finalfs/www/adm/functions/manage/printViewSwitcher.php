<?php

    // Takes a view (string) and prints a html form with radio buttons to switch between views defined in the constant views.php.
    // The radio button for the given view is selected on load.
    function printViewSwitcher($view)
    {
        require("./constants/views.php");
        if (empty($view))
        {
            $view=key($views);
        }
        echo "<form class='viewsForm'>";
        foreach ($views as $k => $v)
        {
            echo "<input title='Välj visningsvy' type='radio' name='view' value='$k' onchange='this.form.submit();'";
            if ($k == $view)
            {
                echo " checked='checked'";
            }
            echo "><span class='viewsSpan'>$k</span>";
        }
        echo "</form>";
    }