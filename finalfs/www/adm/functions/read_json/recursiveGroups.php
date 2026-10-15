<?php

function recursiveGroups($dbh, array $groupsArr, string $importId, array $groupsLayers): array
{
    require './constants/configSchema.php';
    $parentGroups=array();
    foreach ($groupsArr as $group)
    {

        $parentGroups[]=$group['name']."#$importId";
        if (!empty($group['groups']))
        {
            $childGroups=recursiveGroups($dbh, $group['groups'], $importId, $groupsLayers);
        }
        else
        {
            $childGroups=array();
        }
        if (!empty($group['expanded']))
        {
            $groupExpanded=var_export($group['expanded'], true);
        }
        else
        {
            $groupExpanded='false';
        }
        $groupAbstract=isset($group['abstract']) ? str_replace(array("\r\n", "\r", "\n"), "<br />", $group['abstract']) : null;
        $sql="INSERT INTO {$configSchema}.groups(group_id, title, expanded, abstract, groups, layers) VALUES ($1, $2, $3, $4, $5, $6)";
        executeImportQuery($dbh, $sql, array(
            $group['name']."#$importId",
            $group['title'] ?? null,
            $groupExpanded,
            $groupAbstract,
            toPgArrayLiteral($childGroups),
            toPgArrayLiteral((array) ($groupsLayers[$group['name']] ?? array()))
        ));
    }
    return $parentGroups;
}