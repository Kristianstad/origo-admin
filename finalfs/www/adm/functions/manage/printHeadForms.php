<?php

	// Takes view (string), configTables (array), focusTable (string) and inheritPosts (array) as parameters.
	// Prints a html code with a div containing a table of selection forms. 
	// Which forms that are displayed depends on the given view. The given focusTable is placed first and its heading is in bold.
	function printHeadForms($view, $configTables, $focusTable, $inheritPosts)
	{
		require("./constants/views.php");
		if (empty($view))
		{
			$view=key($views);
		}
		if (empty($views[$view]))
		{
			$forms=$configTables;
		}
		else
		{
			$forms=array_flip(array_intersect($views[$view], array_keys($configTables)));
			foreach ($forms as $k => $v)
			{
				$forms[$k]=$configTables[$k];
			}
		}
		if (isset($focusTable) && isset($configTables[$focusTable]))
		{
			unset($forms[$focusTable]);
			$forms=array_merge(array($focusTable=>$configTables[$focusTable]), $forms);
		}
		unset($configTables);
		echo <<<HERE
			<div class="headFormsDiv">
				<table class="headFormsTable">
					<tr>
		HERE;
		$i=1;
		$l=count($forms);
		foreach ($forms as $tableName=>$table)
		{
			$tmpInheritPosts=$inheritPosts;
			if ($i == $l)
			{
				echo '<th class="thRight">';
			}
			else
			{
				if ($i == 1)
				{
					echo '<th class="thLeft">';
				}
				else
				{
					echo '<th class="thMiddle">';
				}
				$i++;
			}
			$tableType=rtrim($tableName, 's');
			if ($focusTable == $tableName)
			{
				$focusClass='h3Focus';
			}
			else
			{
				$focusClass='h3NoFocus';
				unset($tmpInheritPosts[$tableType.'Id']);
			}
			echo "<h3 class='$focusClass'>".mbUcfirst(toSwedish($tableType))."</h3>";
			printHeadForm(array($tableName=>$table), $tmpInheritPosts);
			echo "</div></th>";
		}
		echo <<<HERE
					</tr>
				</table>
			</div>
		HERE;
	}