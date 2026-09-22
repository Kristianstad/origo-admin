<?php

	// Takes a full update target (array), inheritPosts (array), and helps (array).
	// Prints form fields and buttons that are used to view and edit the configuration for the given update.
	function printUpdateForm($update, $inheritPosts, $helps=array())
	{
		if (!isFullTarget($update))
		{
			die("printUpdateForm($update, $inheritPosts, $helps=array()) failed!");
		}
		$sizePosts=sizePosts($inheritPosts);
		echo '<div><div class="printXFormDiv"><form method="post">';
		printTextarea($update, 'update_id', 'textareaMedium', 'Id:', in_array('update_id', $helps), $sizePosts);
		printTextarea($update, 'name', 'textareaMedium', 'Name:', in_array('name', $helps), $sizePosts);
		printUpdateSelect($update, array('interval'=>array("+1 day", "+1 week", "+1 month", "+1 year")), 'miniSelect', 'Intervall:', in_array('interval', $helps));
		printUpdateSelect($update, array('method'=>array("manuellt", "automatiskt")), 'miniSelect', 'Metod:', in_array('method', $helps));
		printTextarea($update, 'abstract', 'textareaLarge', 'Beskrivning:', in_array('abstract', $helps), $sizePosts);
		printTextarea($update, 'info', 'textareaLarge', 'Info:', in_array('info', $helps), $sizePosts);
		printHiddenInputs($inheritPosts);
		echo '<div class="buttonDiv">';
		printUpdateButton('update', $inheritPosts['_formChanged'] ?? false);
		printCopyButton('update');
		$update=makeTargetBasic($update);
		printInfoButton($update);
		$deleteConfirmStr="Är du säker att du vill radera uppdateringsrutinen ".targetId($update)."? Referenser till uppdateringsrutinen hanteras separat.";
		printDeleteButton($update, $deleteConfirmStr, $inheritPosts);
		echo '</div></form></div></div>';
	}