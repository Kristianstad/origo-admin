<?php

	function arrayMove(&$a, $oldpos, $newpos)
	{
    	if ($oldpos==$newpos) {return;}
    	array_splice($a,max($newpos,0),0,array_splice($a,max($oldpos,0),1));
	}