<?php

	function printNewsSubjects($dbh, $username, $userNews)
	{
		if (!empty($userNews))
		{
			require('./constants/proxyRoot.php');
			$formAction=$proxyRoot.$_SERVER["PHP_SELF"];
			echo '<table>';
			foreach ($userNews as $aNews)
			{
				if (!in_array($username, $aNews['deletes']))
				{
					echo '<tr><td><li><a href="'.$formAction.'?action=load&newId='.urlencode($aNews['new_id']).'&return=text">';
					if (!in_array($username, $aNews['reads']))
					{
						echo '<b>';
						printNews($dbh, $username, $aNews, array('abstract'));
						echo '</b>';
					}
					else
					{
						printNews($dbh, $username, $aNews, array('abstract'));
					}
					echo '</a></li></td><td><a href="'.$formAction.'?action=delete&newId='.urlencode($aNews['new_id']).'"><img src="/img/png/list_remove.png" alt="Radera" title="Radera"></a></td></tr>';
				}
			}
			echo '</table>';
		}
		else
		{
			echo 'Det finns inga nyheter.';
		}
	}