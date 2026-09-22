<?php
/*
manage.php
 ├─ includeDirectory("./functions/common")
 ├─ includeDirectory("./functions/manage")
 ├─ $post = array_filter($_POST, ...)      → rensar bort tomma POST-värden (men behåller "0")
 ├─ $view = $_GET['view']                   → styr vilken vy/flik som visas
 ├─ unset($_POST, $_GET)                    → försiktighetsmönster vi sett förut
 │
 ├─ FAS 1: TOLKA INKOMMANDE POST-DATA
 │    ├─ bygger $groupIdsArray från groupIds/groupId
 │    ├─ idPosts($post)            [manage] → alla *Id-fält (vilken rad är vald i varje kolumn)
 │    ├─ sizePosts($post)          [manage] → sparade textarea-dimensioner (UI-state)
 │    ├─ categoryPosts($post)      [manage] → vilken nyckelordskategori som valts per fält
 │    ├─ focusTable($idPosts)      [manage] → vilken tabell som är "i fokus"
 │    ├─ dbh(), configTables($dbh) [common] → ALLA konfigtabeller i minnet
 │    ├─ viewKeywordCategorized($view)  [manage] → vilka tabeller kategoriseras via nyckelord i denna vy
 │    └─ bygger $categoriesByTable[<table>] för varje kategoriserad tabell
 │
 ├─ FAS 2: HANTERA FORMULÄRINSKICK (om en knapp klickades)
 │    ├─ postButton($post)          [manage] → vilken knapp klickades (avslöjar $type + $command)
 │    ├─ beroende på $command:
 │    │    ├─ 'copy'      → kopiera en rad (med "-kopia"-suffix vid namnkrock)
 │    │    ├─ 'create'    → skapa ny rad med angivet id
 │    │    ├─ 'delete'    → radera rad, MEN BARA om inget annat refererar till den
 │    │    │                (findAllParents-koll, samma skyddsmönster som i info.php)
 │    │    ├─ 'update'/'copy' → validateUpdate() → bygg UPDATE-sats via sqlForUpdate()
 │    │    │    └─ SPECIALFALL: layer/source med QGIS-tjänst → läser .qgs-fil från disk
 │    │    │       för att auto-fylla 'updated', 'softversion', 'tables' (samma mönster
 │    │    │       som info.php och writeTablesForAllLayers.php)
 │    │    └─ 'operation' → lägg till/ta bort ett barn (layer/group/control) från en
 │    │                     förälder (map/group) → sqlForOperation()
 │    ├─ kör statements, om lyckat:
 │    │    ├─ läser om configTables (färsk data)
 │    │    ├─ usedInMaps() FÖRE och EFTER ändringen → markMapsChanged() för påverkade
 │    │    │   kartor (sätter troligen maps.changed='t', vilket writeConfig.php senare
 │    │    │   läser för att veta vilka kartor som behöver publiceras om)
 │    │    └─ vid fel: bygger ett JS alert() med Postgres felmeddelande
 │
 ├─ FAS 3: RENDERA SIDAN (rubrik, JS, CSS, toppknappar, vyväxlare)
 │    ├─ printViewSwitcher($view)      [manage]
 │    ├─ printHeadForms(...)           [manage] → toppradens urvalsformulär (beror på vy)
 │    └─ bygger JS-variabler för varje nyckelordskategori (läser $categoriesByTable)
 │
 └─ FAS 4: RENDERA DET VALDA OBJEKTETS FORMULÄR (djupt kaskaderande, en gren per typ)
      ├─ OM map vald   → printMapForm() + printChildSelect() för layers/groups/controls
      ├─ OM database   → printDatabaseForm() + printChildSelect() för schemas
      ├─ OM schema     → printSchemaForm() + printChildSelect() för tables
      ├─ OM group(s)   → loop genom $groupIdsArray (nästlade grupper) → printGroupForm()
      │                  + printChildSelect() för layers/groups (rekursivt genom hierarkin)
      └─ OM övrigt <item> vald (idPosts icke-tom):
           ├─ makeTargetFull(makeBasicTarget(...))  [manage] → normaliserad datastruktur
           ├─ targetType()                           [manage] → vilken typ är detta?
           └─ $formFunction = 'print'.ucfirst($childType).'Form'; $formFunction(...)  ← DYNAMISKT FUNKTIONSANROP (variabel-funktion, ej eval)
                (layer/source/table/searchtable har specialhantering före detta;
                control/plugin och "allt annat" går via samma mönster)
*/

// Tell browsers to not cache response
header("Cache-Control: must-revalidate, max-age=0, s-maxage=0, no-cache, no-store");

// Expose specific functions
require_once("./functions/includeDirectory.php");

// Expose all functions in given folders
includeDirectory("./functions/common");
includeDirectory("./functions/manage");

// Expose posted data as $post (array)
$post = array_filter($_POST, function ($value) {
    return (!empty($value) || $value === "0");
});

// Expose view query parameter as $view (string)
$view = null;
if (isset($_GET['view'])) {
    $view = $_GET['view'];
}

// Unset request variables that are no longer needed
unset($_POST, $_GET);

// Create $groupIdsArray (array) from $post and update related, missing $post values
if (isset($post['groupIds'])) {
    $groupIdsArray = explode(',', $post['groupIds']);
    if (!isset($post['groupId'])) {
        $post['groupId'] = $groupIdsArray[0];
    }
} elseif (isset($post['groupId'])) {
    $post['groupIds'] = $post['groupId'];
    $groupIdsArray = array($post['groupId']);
} else {
    $groupIdsArray = array();
}

if (isset($post['infogroupIds'])) {
    $infogroupIdsArray = explode(',', $post['infogroupIds']);
    if (!isset($post['infogroupId'])) {
        $post['infogroupId'] = $infogroupIdsArray[0];
    }
} elseif (isset($post['infogroupId'])) {
    $post['infogroupIds'] = $post['infogroupId'];
    $infogroupIdsArray = array($post['infogroupId']);
} else {
    $infogroupIdsArray = array();
}

// Expose all $post values where the key ends with 'Id' (excluding 'fromMapId', 'toMapId', 'fromGroupId', 'toGroupId') as $idPosts (array)
$idPosts = idPosts($post);

// Expose posted textarea widths, heights and scroll as $sizePosts (array)
$sizePosts = sizePosts($post);

// Expose all $post values where the key ends with 'Category' as $categoryPosts (array)
$categoryPosts = categoryPosts($post);

// Determine which table has focus and expose the id as $focusTable (string)
$focusTable = focusTable($idPosts);

// Expose postgresql database handle as $dbh (handle)
$dbh = dbh();

// Read configuration tables from database and expose as $configTables (array)
$configTables = configTables($dbh);

// Ids of tables (in current view) that should be categorized by keywords, exposed as $keywordCategorized (array)
$keywordCategorized = viewKeywordCategorized($view);

// Configs (from tables) that should be categorized by keywords, exposed as $categoryConfigs (array)
$categoryConfigs = array_intersect_key($configTables, array_flip($keywordCategorized));

// Extract the categories (keywords) for all $categoryConfigs and expose as $categoriesByTable[<table>] (array)
$categoriesByTable = array();
foreach ($categoryConfigs as $table => $config) {
    $catParam = pkColumnOfTable($table);
    $categoriesByTable[$table] = categories($config, $catParam);
}

// If a post form was submitted by clicking a button, then the id of the button is exposed as $postButton (string)
$postButton = postButton($post);

// If a form has been submitted, then make changes to the configuration database accordingly
if (isset($postButton)) {
    // Extract type from the $postButton and expose as $type (string)
    $type = substr($postButton, 0, -6);

    // Expose the table name of $type as $typeTableName (string)
    $typeTableName = typeTableName($type);

    // Expose the primary key column of $typeTableName as $typeTablePkColumn (string)
    $typeTablePkColumn = pkColumnOfTable($typeTableName);

    // Expose the table for $type as $typeTable (array)
    $typeTable = $configTables[$typeTableName];

    // Expose the command associated with the $postButton as $command
    $command = $post[$postButton];

    $sqlStatements = array();

    if ($command == 'copy' && isset($post[$type . 'Id'])) {
        if (isset($post['update' . ucfirst($typeTablePkColumn)])) {
            $copyId = $post['update' . ucfirst($typeTablePkColumn)];
        } else {
            $copyId = $post[$type . 'Id'];
        }
        while (!isIdUniqueInTable($copyId, $typeTablePkColumn, $typeTable)) {
            $copyId = $copyId . '-kopia';
        }
        $sqlStatements[] = insertIdSql($copyId, $typeTableName);
    }

    if ($command == 'create' && !empty($post[$type . 'IdNew'])) {
        $id = $post[$type . 'IdNew'];
        if (isIdUniqueInTable($id, $typeTablePkColumn, $typeTable)) {
            $sqlStatements[] = insertIdSql($id, $typeTableName);
        }
    }

    elseif ($command == 'delete' && !empty($post[$type . 'IdDel'])) {
        $id = $post[$type . 'IdDel'];
        if (!isIdUniqueInTable($id, $typeTablePkColumn, $typeTable)) {
            $child = makeBasicTarget($type, $id);
            $allParents = findAllParents($dbh, $child);
            if (empty(assoc_array_values($allParents))) {
                $sqlStatements[] = deleteIdSql($id, $typeTableName);
                unset($post[$type . 'Id'], $idPosts[$type . 'Id']);
            } else {
                $errorAlert = 'window.onload=function(){alert("Radering misslyckades då ' . $id . ' används!\nAnvänd Info-verktyget för att ta reda på var ' . $id . ' används.");}';
            }
            unset($child, $allParents);
        }
    }

    elseif (isset($post[$type . 'Id'])) {

        // The selected item is exposed as a basic target.
        $target = makeBasicTarget($type, $post[$type . 'Id']);
        $id = targetId($target);

        if ($command == 'update' || $command == 'copy') {

            // If $type is 'layer' or 'group', make the posted abstract field html compatible
            if (($type == 'layer' || $type == 'group') && isset($post['updateAbstract'])) {
                $post['updateAbstract'] = str_replace(["\r\n", "\r", "\n"], "<br>", $post['updateAbstract']);
            }

            // Expose posted configuration fields as $updatePosts (array)
            $updatePosts = updatePosts($post);

            // Makes sure posted configuration fields are valid before continueing database update, or else aborts and gives an alert
            validateUpdate($updatePosts, $configTables, $updateValid);
            if ($updateValid) {
                $fullTarget = makeTargetFull($target, $configTables);
                $config = targetConfig($fullTarget);

                // If $type is 'layer' or 'source' and is originating from Qgis Server, then $post is updated with information gathered from corresponding Qgis project file
                if ($type == 'layer' || $type == 'source') {
                    if ($type == 'layer') {
                        $layerName = explode('#', $id)[0];
                        $sourceConfig = array_column_search($config['source'], 'source_id', $configTables['sources']);
                    } else {
                        $layerName = null;
                        $sourceConfig = $config;
                    }
                    $serviceType = array_column_search($sourceConfig['service'], 'service_id', $configTables['services'])['type'];
                    if (strtolower($serviceType) == 'qgis') {
                        $qgsXml = simplexml_load_file('/services/' . $sourceConfig['service'] . '/' . explode('#', $sourceConfig['source_id'])[0] . '.qgs');
                        if (!empty($qgsXml)) {
                            if ($type == 'source') {
                                if (!empty($fromQgs = substr($qgsXml['saveDateTime'], 0, 10))) {
                                    $post['updateUpdated'] = $fromQgs;
                                }
                                if (!empty($fromQgs = strstr($qgsXml['version'], '-', true))) {
                                    $post['updateSoftversion'] = $fromQgs;
                                }
                            }
                            if (!empty($fromQgs = tablesFromQgsXml($qgsXml, $layerName))) {
                                $post['updateTables'] = implode(',', $fromQgs);
                            }
                        }
                        unset($qgsXml, $fromQgs);
                    }
                    unset($layerName, $sourceConfig, $serviceType);
                }

                // If $command is 'copy' then use the new $copyId in the update operation
                if ($command == 'copy') {
                    setTargetConfigParam($fullTarget, $typeTablePkColumn, $copyId);
                    $updatePosts['update' . ucfirst($typeTablePkColumn)] = $copyId;
					if ($typeTableName === 'maps')
					{
						$updatePosts['updateChanged']='t';
					}
                }

                $sqlStatements[] = sqlForUpdate($fullTarget, $updatePosts);
                unset($config, $fullTarget);
            } else {
                $failedUpdate['type'] = $type;
                $failedUpdate['id'] = $id;
                $failedUpdate['values'] = array();
                foreach ($updatePosts as $key => $value) {
                    $failedUpdate['values'][lcfirst(substr($key, 6))] = $value;
                }
            }
            unset($updateValid);
        }

        elseif ($command == 'operation') {

            // If a parent has been given (whos config is to be edited) then expose its type as $parentKey (string)
            foreach (array('map', 'group', 'classe', 'infogroup') as $possibleParentKey) {
                if (!empty($post['to' . ucfirst($possibleParentKey) . 'Id']) || !empty($post['from' . ucfirst($possibleParentKey) . 'Id'])) {
                    $parentKey = $possibleParentKey;
                    break;
                }
            }

            // If a parent has been given:
            // Determine if the operation is 'add' or 'remove' and expose the result as $operation
            // Read the id of the parent from $post and expose as $parentPkColumnValue (string)
            if (isset($parentKey)) {
                if (!empty($post['to' . ucfirst($parentKey) . 'Id'])) {
                    $operation = 'add';
                    $parentPkColumnValue = $post['to' . ucfirst($parentKey) . 'Id'];
                } elseif (!empty($post['from' . ucfirst($parentKey) . 'Id'])) {
                    $operation = 'remove';
                    $parentPkColumnValue = $post['from' . ucfirst($parentKey) . 'Id'];
                }

                if (isset($operation)) {
                    $operationParent = makeTargetFull(makeBasicTarget($parentKey, $parentPkColumnValue), $configTables);
                    $sqlStatements[] = sqlForOperation($operation, $target, $operationParent);
                    unset($operation, $parentPkColumnValue, $operationParent);
                }
                unset($parentKey);
            }
        }
    }

    // if statements have been set, then perform database operations and re-read data
    if (!empty($sqlStatements)) {
        $changedTarget = isset($target) ? $target : makeBasicTarget($type, $id);
        $usedInMapsOld = usedInMaps($dbh, $changedTarget);
        $allOk = pg_query($dbh, "BEGIN") !== false;
        $result = false;
        if ($allOk) {
            foreach ($sqlStatements as $statement) {
                $result = pg_query_params($dbh, $statement['sql'], $statement['params']);
                if ($result === false) {
                    $allOk = false;
                    break;
                }
            }
        }
        if ($allOk) {
            $result = pg_query($dbh, "COMMIT");
            $allOk = $result !== false;
        }
        if (!$allOk) {
            pg_query($dbh, "ROLLBACK");
            $errorAlert = 'window.onload=function(){alert("Misslyckades att skriva till databas!\n\n' . str_replace('"', '\"', str_replace(["\r", "\n"], '\n', pg_last_error())) . '");}' . "\n";
            $failedUpdate['type'] = $type;
            $failedUpdate['id'] = $id;
            $failedUpdate['values'] = array();
            foreach ($updatePosts as $key => $value) {
                $failedUpdate['values'][lcfirst(substr($key, 6))] = $value;
            }
        } else {
            if ($command === 'copy' && isset($copyId))
            {
                $post[$type . 'Id']=$copyId;
                $idPosts[$type . 'Id']=$copyId;
                $id=$copyId;
            }
            $configTables = configTables($dbh);
            if ($command != 'operation' && in_array($typeTableName, $keywordCategorized)) {
                $categoriesByTable[$typeTableName] = categories($configTables[$typeTableName], $typeTablePkColumn);
            }
            if ($command == 'update' || $command == 'operation') {
                $usedInMapsNew = usedInMaps($dbh, $changedTarget);
                $usedInMaps = array_unique(array_merge($usedInMapsOld, $usedInMapsNew));
                if (!empty($usedInMaps)) {
                    markMapsChanged($dbh, $usedInMaps);
                    $configTables = configTables($dbh);
                }
                unset($usedInMapsNew, $usedInMaps);
            }
        }
        unset($usedInMapsOld, $changedTarget, $result);
    }
    unset($updatePosts, $copyId, $id, $target, $type, $typeTableName, $typeTablePkColumn, $typeTable, $command, $sqlStatements);
}
pg_close($dbh);

// Some common information needs to be passed on every time a form is posted, this info is aggregated in $inheritPosts (array)
// $inheritPosts is set to include $idPosts, $sizePosts, $post['groupIds'] and $categoryPosts
$inheritPosts = array_merge($idPosts, $sizePosts);
$inheritPosts['_viewDepth'] = 0;
$inheritPosts['_formChanged'] = false;
if (isset($post['groupIds'])) {
    $inheritPosts['groupIds'] = $post['groupIds'];
}
if (isset($post['infogroupIds'])) {
    $inheritPosts['infogroupIds'] = $post['infogroupIds'];
}
foreach ($categoryPosts as $postName => $category) {
    $inheritPosts[$postName] = $category;
}

$currentSkin=currentSkin($configTables['skins'] ?? array());

// === Början av sidan ===
echo <<<HTML
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8"/>
	<title>Administrationsverktyg för Origo</title>
	<link rel="shortcut icon" href="../img/png/logo.png">
	<script>
		let topFrame="";
HTML;

// Include all js-code from the given directory
includeDirectory("./js-functions/manage");

// Prepare js-code to run later that sets the contents of the item selection boxes based on selected keyword category, store it in $updateSelects (string)
// Create and expose a js-variable for each keyword category, each containing a list of items that has the specified keyword among their keywords
$updateSelects = "";
foreach ($keywordCategorized as $categorized) {
    $categories = $categoriesByTable[$categorized];
    $categories2 = array();
    foreach ($categories as $category => $member) {
        $category = str_replace(array('-', '+', '/'), '', str_replace(' ', '_', $category));
        $categories2[$category] = $member;
        unset($category, $member);
    }
    echo "var {$categorized}Categories = " . json_encode(array_keys($categories2)) . ";\n";
    $updateSelects = $updateSelects . "updateSelect('{$categorized}Categories', {$categorized}Categories);";
    foreach ($categories2 as $category => $member) {
        $member = array_merge(array(""), $member);
        echo "var {$categorized}$category = " . json_encode($member) . ";\n";
        unset($category, $member);
    }
    unset($categorized, $categories, $categories2);
}

echo <<<HTML
	</script>
	<style>
HTML;

// Include all css-stylesheets from the given directory
printSkinVariables($currentSkin);
require("./styles/manage.css");

echo <<<HTML
	</style>
</head>
<body onresize="Array.from(document.getElementsByClassName('resizeimg')).forEach(function(element) { element.onerror(); });">

	<!-- Print the top buttons, including radio buttons to change view -->
	<form id="helpForm" action="help.php" target="topFrame">
		<button title="Visa/dölj hjälptext" class="topButton" onclick="toggleTopFrame('help');" type="submit">Hjälp</button>
	</form>
HTML;

if ($view === 'Verktyg')
{
    echo <<<HTML
    <form action="read_json.php" target="topFrame">
        <button title="Importera konfiguration från JSON-fil" class="topButton" onclick="toggleTopFrame('read_json');" type="submit">Importera JSON</button>
    </form>
    <form action="sql_import.php" target="topFrame">
        <button title="Kör SQL mot admin-databasen" class="topButton" onclick="toggleTopFrame('sql_import');" type="submit">Importera SQL</button>
    </form>
HTML;
}

printViewSwitcher($view);

echo <<<HTML

	<!-- Initialize iframes "topFrame" and "hiddenFrame", start hidden -->
	<iframe id="topFrame" name="topFrame" style="display:none;resize:vertical"></iframe>
	<iframe id="hiddenFrame" name="hiddenFrame" style="display:none"></iframe>

	<!-- Initialize form "multiselectForm" and set its target to topFrame -->
	<form id="multiselectForm" action="multiselect.php" method="get" target="topFrame"></form>
	
	<!-- Print a row of selection forms. What columns shown depends on the selected view -->
HTML;

printHeadForms($view, $configTables, $focusTable, $inheritPosts);

echo <<<HTML

	<script>
HTML;

// Alert if error
if (!empty($errorAlert)) {
    echo $errorAlert;
}

// Run the prepared js-code stored in $updateSelects
echo $updateSelects;
unset($updateSelects);

// Add js-code that populates the keyword category selection boxes
foreach ($categoryPosts as $postName => $category) {
    $table = substr($postName, 0, -8);
    $type = tableType($table);
    if (isset($idPosts[$type . 'Id'])) {
        $id = $idPosts[$type . 'Id'];
        echo <<<HERE
			document.getElementById("{$table}Categories").value="{$category}";
			updateSelect("{$type}Select", {$table}{$category});
			document.getElementById("{$type}Select").value="{$id}";
		HERE;
        unset($id);
    }
    unset($table, $type);
}
unset($categoryPosts, $postName, $category);

echo <<<HTML
	</script>
HTML;

/*
 *********************************************
 *  DYNAMIC CONTENTS BASED ON SELECTED ITEM  *
 *********************************************
*/
// Track the number of targets (depth) shown in the dynamic view.
// Expose field help ids (identifying fields with existing help text) as $helps (array)
$helps = array_column($configTables["helps"], "help_id");

// If a map is selected
if (isset($post['mapId'])) {
    $inheritPosts['_viewDepth']++;

    // Expose selected map target as $map (array).
    // If a failed update occured then show those values, else show the stored values.
    if (isset($failedUpdate) && $failedUpdate['type'] == 'map') {
        $map = makeFullTarget('map', $failedUpdate['values']);
        $inheritPosts['_formChanged'] = true;
    } else {
        $map = makeFullTarget('map', array_column_search($post['mapId'], 'map_id', $configTables['maps']));
    }
    if (!empty(current($map))) {
        // Map selectable items (footers, tilegrids) are exposed as $selectables (array)
        $selectables = array(
            'footers' => array_column($configTables['footers'], 'footer_id'),
            'tilegrids' => array_column($configTables['tilegrids'], 'tilegrid_id')
        );

        // Print the form for the selected map
        printMapForm($map, $selectables, $inheritPosts, typeHelps("map", $helps));

        // Print child select dialogs for layers, groups and controls if any exists for selected map
        echo '<hr class="childSelectHr"><table><tr>';
        $thClass = 'thFirst';
        if (!empty($groupIdsArray)) {
            $inheritPosts['groupId'] = $groupIdsArray[0];
        }
        if (isset($inheritPosts['layerId']) && !isset($inheritPosts['groupId'])) {
            printChildSelect($map, 'layers', $thClass, 'Lager', $inheritPosts);
            printChildSelect($map, 'groups', $thClass, 'Grupp', $inheritPosts);
            printChildSelect($map, 'controls', $thClass, 'Kontroll', $inheritPosts);
        } elseif (isset($inheritPosts['controlId'])) {
            printChildSelect($map, 'controls', $thClass, 'Kontroll', $inheritPosts);
            printChildSelect($map, 'groups', $thClass, 'Grupp', $inheritPosts);
            printChildSelect($map, 'layers', $thClass, 'Lager', $inheritPosts);
        } else {
            printChildSelect($map, 'groups', $thClass, 'Grupp', $inheritPosts);
            printChildSelect($map, 'layers', $thClass, 'Lager', $inheritPosts);
            printChildSelect($map, 'controls', $thClass, 'Kontroll', $inheritPosts);
        }
        echo '</tr></table>';
        unset($selectables, $thClass);
    }
    unset($map, $idPosts['mapId']);
}

// If a database is selected
elseif (isset($post['databaseId'])) {
    $inheritPosts['_viewDepth']++;

    // Expose selected database target as $database (array).
    // If a failed update occured then show those values, else show the stored values.
    if (isset($failedUpdate) && $failedUpdate['type'] == 'database') {
        $database = makeFullTarget('database', $failedUpdate['values']);
        $inheritPosts['_formChanged'] = true;
    } else {
        $database = makeFullTarget('database', array_column_search($post['databaseId'], 'database_id', $configTables['databases']));
    }
    if (!empty(current($database))) {
        // Print the form for the selected database
        printDatabaseForm($database, $inheritPosts, typeHelps("database", $helps));

        // Expose all schema ids of selected database as $databaseSchemas (array)
        $databaseSchemas = preg_grep("/^" . $post['databaseId'] . "[.]/", array_column($configTables['schemas'], 'schema_id'));

        // Add $databaseSchemas to $database
        setTargetConfigParam($database, 'schemas', '{' . implode(',', $databaseSchemas) . '}');

        // Print child select dialog for schemas if any exists for selected database
        echo '<hr class="childSelectHr"><table><tr>';
        $thClass = 'thFirst';
        printChildSelect($database, 'schemas', $thClass, 'Schema', $inheritPosts);
        echo '</tr></table>';
        unset($databaseSchemas, $thClass);
    }
    unset($database, $idPosts['databaseId']);
}

// If a schema is selected
if (isset($post['schemaId'])) {
    $inheritPosts['_viewDepth']++;

    // Expose selected schema target as $schema (array).
    // If a failed update occured then show those values, else show the stored values.
    if (isset($failedUpdate) && $failedUpdate['type'] == 'schema') {
        $schema = makeFullTarget('schema', $failedUpdate['values']);
        $inheritPosts['_formChanged'] = true;
    } else {
        $schema = makeFullTarget('schema', array_column_search($post['schemaId'], 'schema_id', $configTables['schemas']));
    }
    if (!empty(current($schema))) {
        // Schema selectable items (contacts, origins, updates) are exposed as $selectables (array)
        $selectables = array(
            'contacts' => array_combine(array_column($configTables['contacts'], 'contact_id'), array_column($configTables['contacts'], 'name')),
            'origins' => array_combine(array_column($configTables['origins'], 'origin_id'), array_column($configTables['origins'], 'name')),
            'updates' => array_column($configTables['updates'], 'update_id')
        );

        // Print the form for the selected schema
        printSchemaForm($schema, $selectables, $inheritPosts, typeHelps("schema", $helps));

        // Expose all table ids of selected schema as $schemaTables (array)
        $schemaTables = preg_grep("/^" . $post['schemaId'] . "[.]/", array_column($configTables['tables'], 'table_id'));

        // Add $schemaTables to $schema
        setTargetConfigParam($schema, 'tables', '{' . implode(',', $schemaTables) . '}');

        // Print child select dialog for tables if any exists for selected schema
        echo '<hr class="childSelectHr"><table><tr>';
        $thClass = 'thFirst';
        printChildSelect($schema, 'tables', $thClass, 'Tabell', $inheritPosts);
        echo '</tr></table>';
        unset($schemaTables, $thClass);
    }
    unset($schema, $idPosts['schemaId']);
}

// If a class is selected
if (isset($post['classeId'])) {
    $inheritPosts['_viewDepth']++;
    if (isset($failedUpdate) && $failedUpdate['type'] == 'classe') {
        $classe = makeFullTarget('classe', $failedUpdate['values']);
        $inheritPosts['_formChanged'] = true;
    } else {
        $classe = makeFullTarget('classe', array_column_search($post['classeId'], 'classe_id', $configTables['classes']));
    }
    if (!empty(current($classe))) {
        $operationTables = array();
        foreach (array('infogroups', 'layers', 'tables') as $operationTable) {
            if (isset($configTables[$operationTable])) {
                $operationTables[$operationTable] = $configTables[$operationTable];
            }
        }
        printClasseForm($classe, $operationTables, $inheritPosts, typeHelps('classe', $helps));
        unset($operationTables, $operationTable);
        echo '<hr class="childSelectHr"><table><tr>';
        $thClass = 'thFirst';
        printChildSelect($classe, 'infogroups', $thClass, 'Informationsgrupp', $inheritPosts);
        printChildSelect($classe, 'layers', $thClass, 'Lager', $inheritPosts);
        printChildSelect($classe, 'tables', $thClass, 'Tabell', $inheritPosts);
        echo '</tr></table>';
    }
    unset($classe, $idPosts['classeId'], $thClass);
}

// If an infogroup is selected
$infogroupLevel = 1;
foreach ($infogroupIdsArray as $infogroupId) {
    $inheritPosts['_viewDepth']++;
    if (isset($failedUpdate) && $failedUpdate['type'] == 'infogroup' && $failedUpdate['id'] == $infogroupId) {
        $infogroup = makeFullTarget('infogroup', $failedUpdate['values']);
        $inheritPosts['_formChanged'] = true;
    } else {
        $infogroup = makeFullTarget('infogroup', array_column_search($infogroupId, 'infogroup_id', $configTables['infogroups']));
    }
    $inheritPosts['infogroupId'] = $infogroupId;
    if (!empty(current($infogroup))) {
        $operationTables = array();
        foreach (array('classes', 'infogroups', 'layers', 'tables') as $operationTable) {
            if (isset($configTables[$operationTable])) {
                $operationTables[$operationTable] = $configTables[$operationTable];
            }
        }
        printInfogroupForm($infogroup, $operationTables, $inheritPosts, typeHelps('infogroup', $helps));
        unset($operationTables, $operationTable);
        echo '<hr class="overflowHr"><table><tr>';
        $thClass = 'thFirst';
        printChildSelect($infogroup, 'infogroups', $thClass, 'Informationsgrupp', $inheritPosts, $infogroupLevel);
        printChildSelect($infogroup, 'layers', $thClass, 'Lager', $inheritPosts, $infogroupLevel);
        printChildSelect($infogroup, 'tables', $thClass, 'Tabell', $inheritPosts, $infogroupLevel);
        echo '</tr></table>';
    }
    unset($infogroup);
    $infogroupLevel++;
}
unset($infogroupIdsArray, $infogroupId, $infogroupLevel, $idPosts['infogroupId']);

//  (If a group is selected)

// Expose a copy of $groupIdsArray as $tmpGroupIds (array)
$tmpGroupIds = $groupIdsArray;

// Expose the parent group id of selected group as $parent (string)
$parent = array_shift($tmpGroupIds);

// Expose the count of total parent group levels as $totGroupLevels (integer)
$totGroupLevels = count($groupIdsArray);
$groupLevel = 1;

// Loop through the tree of groups where selected group belongs
foreach ($groupIdsArray as $groupId) {
    $inheritPosts['_viewDepth']++;

    // Expose current loop group target as $group (array).
    // If a failed update occured and was current loop group then show those values, else show the stored values.
    if (isset($failedUpdate) && $failedUpdate['type'] == 'group' && $failedUpdate['id'] == $groupId) {
        $group = makeFullTarget('group', $failedUpdate['values']);
        $inheritPosts['_formChanged'] = true;
    } else {
        $group = makeFullTarget('group', array_column_search($groupId, 'group_id', $configTables['groups']));
    }

    $inheritPosts['groupId'] = $groupId;
    if (!empty(current($group))) {
        // If there is multiple parents to the selected group (ie parents of parents), use the loop to append them to $parent
        if (count($tmpGroupIds) > 0) {
            $parent = "$parent," . array_shift($tmpGroupIds);
        }

        // Print the form for the current loop group
        printGroupForm($group, array('maps' => $configTables['maps'], 'groups' => $configTables['groups']), $inheritPosts, typeHelps("group", $helps));

        // Print child select dialogs for layers and/or groups if any exists for the current loop group
        echo '<hr class="overflowHr"><table><tr>';
        $thClass = 'thFirst';
        if ($groupLevel == $totGroupLevels && isset($inheritPosts['layerId'])) {
            printChildSelect($group, 'layers', $thClass, 'Lager', $inheritPosts, $groupLevel);
            printChildSelect($group, 'groups', $thClass, 'Grupp', $inheritPosts, $groupLevel, $parent);
        } else {
            printChildSelect($group, 'groups', $thClass, 'Grupp', $inheritPosts, $groupLevel, $parent);
            printChildSelect($group, 'layers', $thClass, 'Lager', $inheritPosts, $groupLevel);
        }
        echo '</tr></table>';
        $groupLevel++;
    }
    unset($group);
}
unset($tmpGroupIds, $parent, $groupLevel, $groupId, $thClass, $idPosts['groupId'], $totGroupLevels);

// If any <item> is selected
if (!empty($idPosts)) {
    $inheritPosts['_viewDepth']++;

    // Expose selected <item> target as $childFullTarget (array)
    $childFullTarget = makeTargetFull(makeBasicTarget(substr(key($idPosts), 0, -2), current($idPosts)), $configTables);

    // Expose the type of the selected <item> as $childType (string)
    $childType = targetType($childFullTarget);

    // If a failed update occured then show those values.
    if (isset($failedUpdate) && $failedUpdate['type'] == $childType) {
        $childFullTarget = array($childType => $failedUpdate['values']);
        $inheritPosts['_formChanged'] = true;
    }

    // Expose the help ids available for $childType as $typeHelps (array)
    $typeHelps = typeHelps($childType, $helps);

    //  If a layer is selected
    if ($childType == 'layer') {
        // If the selected layer has a source set, then append the 'service_id' of that source to $childFullTarget and also append the service's 'restricted' as 'service_restricted'.
        $layerSourceId = targetConfigParam($childFullTarget, 'source');
        if (!empty($layerSourceId)) {
            $layerSource = array_column_search($layerSourceId, 'source_id', $configTables['sources']);
            $layerServiceId = $layerSource['service'];
            if (!empty($layerServiceId)) {
                setTargetConfigParam($childFullTarget, 'service_id', $layerServiceId);
                $layerService = array_column_search($layerServiceId, 'service_id', $configTables['services']);
                setTargetConfigParam($childFullTarget, 'service_restricted', $layerService['restricted']);
                $layerServiceFormats = pgArrayToPhp($layerService['formats']);
                unset($layerService);
            }
            unset($layerSource, $layerServiceId);
        }
        unset($layerSourceId);

        // Layer selectable items (contacts, origins, formats, updates) are exposed as $selectables (array)
        $selectables = array(
            'contacts' => array_combine(array_column($configTables['contacts'], 'contact_id'), array_column($configTables['contacts'], 'name')),
            'origins' => array_combine(array_column($configTables['origins'], 'origin_id'), array_column($configTables['origins'], 'name')),
            'formats' => $layerServiceFormats,
            'sources' => array_column($configTables["sources"], "source_id"),
            'updates' => array_column($configTables['updates'], 'update_id')
        );

        // Print the form for the selected layer
        $operationTables = array("maps" => $configTables["maps"], "groups" => $configTables["groups"]);
        if (isset($configTables["classes"])) {
            $operationTables["classes"] = $configTables["classes"];
        }
        if (isset($configTables["infogroups"])) {
            $operationTables["infogroups"] = $configTables["infogroups"];
        }
        printLayerForm($childFullTarget, $selectables, $operationTables, $inheritPosts, $typeHelps);
        unset($operationTables);
        unset($selectables);
    }

    //  Else, if a source is selected
    elseif ($childType == 'source') {
        // If the selected source has a service set, then append the type of that service to $childFullTarget as 'service_type'.
        $sourceServiceId = targetConfigParam($childFullTarget, 'service');
        if (!empty($sourceServiceId)) {
            $sourceService = array_column_search($sourceServiceId, 'service_id', $configTables['services']);
            setTargetConfigParam($childFullTarget, 'service_type', $sourceService['type']);
            unset($sourceService);
        }
        unset($sourceServiceId);

        // Source selectable items (services, tilegrids, contacts) are exposed as $selectables (array)
        $selectables = array(
            'services' => array_column($configTables['services'], 'service_id'),
            'tilegrids' => array_column($configTables['tilegrids'], 'tilegrid_id'),
            'contacts' => array_combine(array_column($configTables['contacts'], 'contact_id'), array_column($configTables['contacts'], 'name'))
        );

        // Print the form for the selected source
        printSourceForm($childFullTarget, $selectables, $inheritPosts, $typeHelps);
        unset($selectables);
    }

    // Else, if a table is selected
    elseif ($childType == 'table') {
        // Table selectable items (contacts, origins, updates) are exposed as $selectables (array)
        $selectables = array(
            'contacts' => array_combine(array_column($configTables['contacts'], 'contact_id'), array_column($configTables['contacts'], 'name')),
            'origins' => array_combine(array_column($configTables['origins'], 'origin_id'), array_column($configTables['origins'], 'name')),
            'updates' => array_column($configTables['updates'], 'update_id')
        );

        // The id of the database where the table is stored is exposed as $databaseId (string)
        $tableId = targetConfigParam($childFullTarget, 'table_id');
        $databaseId = substr($tableId, 0, strpos($tableId, '.'));

        // The connection string for $databaseId is exposed as $connectionString (string)
        $connectionString = array_column_search($databaseId, 'database_id', $configTables['databases'])['connectionstring'];

        // Print the form for the selected table
        $operationTables = array();
        if (isset($configTables["classes"])) {
            $operationTables["classes"] = $configTables["classes"];
        }
        if (isset($configTables["infogroups"])) {
            $operationTables["infogroups"] = $configTables["infogroups"];
        }
        printTableForm($childFullTarget, $connectionString, $selectables, $operationTables, $inheritPosts, $typeHelps);
        unset($operationTables);
        unset($selectables, $tableId, $databaseId, $connectionString);
    }

    // Else, if a searchtable is selected
    elseif ($childType == 'searchtable') {
        // Table selectable items (databases) are exposed as $selectables (array)
        $selectables = array(
            'databases' => array_column($configTables['databases'], 'database_id')
        );

        // Print the form for the selected table
        printSearchtableForm($childFullTarget, $selectables, $inheritPosts, $typeHelps);
        unset($selectables);
    }

    //  Else, if a control or plugin is selected
    elseif ($childType == 'control' || $childType == 'plugin') {
        // Print the form for the selected control/plugin
        $formFunction = 'print' . ucfirst($childType) . 'Form';
        $formFunction($childFullTarget, array("maps" => $configTables["maps"]), $inheritPosts, $typeHelps);
        unset($formFunction);
    }

    // Else, if <item> of other type is selected
    else {
        // Print the form for the selected <item> based on its type
        $formFunction = 'print' . ucfirst($childType) . 'Form';
        $formFunction($childFullTarget, $inheritPosts, $typeHelps);
        unset($formFunction);
    }
    unset($childFullTarget, $childType, $typeHelps);
}
unset($helps, $idPosts);

echo <<<HTML
	<script>
		/* Save scroll position at form submission and restore after page load. */
		preservePageScroll();
		
		/* Change the appearance of the "Uppdatera" button on form edit. */
		formChangeButton();
		
		/* Detect field changes made with the multiselect tool, frame close and resize commands */
		initMessageListener();
	</script>
</body>
</html>
HTML;