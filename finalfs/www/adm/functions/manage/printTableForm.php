<?php

	// Takes a full table target (array), pg_connect connection string, table selectables (array), inheritPosts (array), and helps (array).
	// Prints form fields and buttons that are used to view and edit the configuration for the given table.
	function printTableForm($table, $dbhConnectionString, $selectables, $operationTables, $inheritPosts, $helps=array())
	{
		if (!isFullTarget($table))
		{
			die("printTableForm($table, $selectables, $inheritPosts, $helps=array()) failed!");
		}
		$sizePosts=sizePosts($inheritPosts);
		echo '<div><div class="printXFormDiv"><form method="post">';
		printTextarea($table, 'table_id', 'textareaMedium', 'Id:', in_array('table_id', $helps), $sizePosts);
		printTextarea($table, 'abstract', 'textareaLarge', 'Beskrivning:', in_array('abstract', $helps), $sizePosts);
		printTextarea($table, 'keywords', 'textareaLarge', 'Nyckelord:', in_array('keywords', $helps), $sizePosts);
		printUpdateSelect($table, array('contact'=>$selectables['contacts']), 'bodySelect', 'Kontakt:', in_array('contact', $helps));
		printUpdateSelect($table, array('origin'=>$selectables['origins']), 'bodySelect', 'Ursprungskälla:', in_array('origin', $helps));
		$dbh2=dbh($dbhConnectionString);
		$tableWithSchema=substr(targetId($table), strpos(targetId($table), '.')+1);
		$updated=substr(updated_from_table($dbh2, $tableWithSchema)[0], 0, 10);
		setTargetConfigParam($table, 'updated', $updated);
		printTextarea($table, 'updated', 'textareaMedium', 'Uppdaterad:', in_array('updated', $helps), $sizePosts, true);
		printUpdateSelect($table, array('update'=>$selectables['updates']), 'bodySelect', 'Uppdatering:', in_array('update', $helps));
		printTextarea($table, 'history', 'textareaLarge', 'Tillkomsthistorik:', in_array('history', $helps), $sizePosts);
		printTextarea($table, 'info', 'textareaLarge', 'Info:', in_array('info', $helps), $sizePosts);
		printHiddenInputs($inheritPosts);
		echo '<div class="buttonDiv">';
		printUpdateButton('table', $inheritPosts['_formChanged'] ?? false);
		printCopyButton('table');
		$table=makeTargetBasic($table);
		printInfoButton($table);
		$deleteConfirmStr="Är du säker att du vill radera all metadata för tabellen ".targetId($table)."?";
		printDeleteButton($table, $deleteConfirmStr, $inheritPosts);
		echo '</div></form></div></div>';
		echo '<div class="addRemoveDiv">';
		printAddRemoveOperations($table, $operationTables, $inheritPosts);
		echo '</div>';
	}