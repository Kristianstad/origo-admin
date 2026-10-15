<?php

    // Takes an updatePosts array and an configTables array as input parameters and passes back a third parameter as boolean.
    // The third parameter will be set to true if all updatePosts pass as valid
    function validateUpdate($updatePosts, $configTables, &$updateValid)
    {
        require("./constants/multiselectables.php");
        require("./constants/tableAliases.php");
        $updateValid=true;
        foreach ($multiselectables as $table)
        {
            if (!empty($updatePosts['update'.ucfirst($table)]))
            {
                $updateValues=explode(',', $updatePosts['update'.ucfirst($table)]);
                if (!empty($tableAliases[$table]))
                {
                    $trueTable=$tableAliases[$table];
                }
                else
                {
                    $trueTable=$table;
                }
                $unknownValues=array_diff($updateValues, array_column($configTables[$trueTable], pkColumnOfTable($trueTable)));
                if (!empty($unknownValues))
                {
                    echo '<script>window.onload=function(){alert("Uppdatering misslyckades!\nOkända värden för '.toSwedish($table).': '.implode(', ', $unknownValues).'");}</script>';
                    $updateValid=false;
                    break;
                }
            }
        }
    }