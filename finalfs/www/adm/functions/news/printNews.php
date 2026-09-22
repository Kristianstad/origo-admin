<?php

	function printNews($dbh, $username, $selectedNew, $return)
	{
		if (count($return) == 1 && !empty($return[0]) && $return[0]!='all')
		{
			if ($return[0] == 'text' && !in_array($username, $selectedNew['reads']))
			{
				readDelete($dbh, $username, $selectedNew, 'read');
			}

			if ($return[0] == 'text')
			{
				require('./constants/proxyRoot.php');
				$formAction=$proxyRoot.$_SERVER["PHP_SELF"];
				echo '<html><head></head><body style="padding-bottom:50px;"><div style="height:100%;width:100%;overflow:auto;margin-bottom:50px;padding-bottom:30px;">'.$selectedNew[$return[0]];
				echo '</br></br><button type="button" onclick="window.open(\''.$formAction.'?action=subjects\',\'_self\');">Tillbaka</button></div></body></html>';
			}
			else
			{
				echo $selectedNew[$return[0]];
			}
		}
		else
		{
			if (empty($return[0]) || $return[0]=='all')
			{
				$return=array_keys($selectedNew);
			}
			foreach ($return as $field)
			{
				if ($field == 'read' || $field == 'delete')
				{
					$json[$field]=in_array($username, $selectedNew[$field]);
				}
				else
				{
					$json[$field]=$selectedNew[$field];
				}
			}
			header('Content-Type: application/json; charset=UTF-8');
			echo json_encode($json);
		}
	}