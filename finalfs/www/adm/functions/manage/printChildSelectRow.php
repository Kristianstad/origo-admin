<?php

    // Takes a css class for the rule (string), a full target (array), child selects (array of array(column, heading, selectedValue=null)),
    // inheritPosts (array) and groupLevel (optional, integer). Prints the child selects as one table row preceded by a horizontal rule,
    // or nothing at all if no child select has anything to show.
    function printChildSelectRow($hrClass, $target, $childSelects, $inheritPosts, $groupLevel=1)
    {
        $thClass='thFirst';
        ob_start();
        foreach ($childSelects as $childSelect)
        {
            printChildSelect($target, $childSelect[0], $thClass, $childSelect[1], $inheritPosts, $groupLevel, $childSelect[2] ?? null);
        }
        $selects=ob_get_clean();
        if ($selects !== '')
        {
            echo '<hr class="'.$hrClass.'"><table><tr>'.$selects.'</tr></table>';
        }
    }
