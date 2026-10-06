<?php

	function printParents($allParents)
	{
		if (empty(assocArrayValues($allParents)))
		{
			return false;
		}
		else
		{
			foreach ($allParents as $parentTable=>$parentTableOptions)
			{
				if (!empty($parentTableOptions))
				{
					foreach ($parentTableOptions as $parentOption=>$parents)
					{
						if (!empty($parents))
						{
							$parentTableSv=toSwedish($parentTable);
							$parentOptionSv=toSwedish($parentOption);
							$parentType=tableType($parentTable);
							$first=true;
							$headerString="$parentTableSv ($parentOptionSv): ";
							echo "<b>$headerString</b><span style='display:inline-flex;width:0'><span style='min-width:calc(100vw - 20em)'>";
							foreach ($parents as $parent)
							{
								if (!$first)
								{
									echo ', ';
								}
								else
								{
									$first=false;
								}
								echo '<a href="info.php?type='.$parentType.'&id='.urlencode($parent).'">'.$parent.'</a>';
							}
							echo "</span></span></br>";
						}
					}
				}
			}
		}
	}