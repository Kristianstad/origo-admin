<?php

function sqlQueryError($dbh): never
{
	die("Error in SQL query: " . pg_last_error($dbh));
}