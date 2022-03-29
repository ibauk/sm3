<?php
//error_reporting(E_ALL);
//ini_set('display_errors', 1);

/*
 * I B A U K   -   S C O R E M A S T E R
 *
 * I include utilities useful to the administrator
 *
 * I am written for readability rather than efficiency, please keep me that way.
 *
 *
 * Copyright (c) 2019 Bob Stammers
 *
 *
 * This file is part of IBAUK-SCOREMASTER.
 *
 * IBAUK-SCOREMASTER is free software: you can redistribute it and/or modify
 * it under the terms of the MIT License
 *
 * IBAUK-SCOREMASTER is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * MIT License for more details.
 *
 */


$HOME_URL = "admin.php";
require_once('common.php');

function clean_foldername($x)
{
	$res = '';
	for($i = 0; $i < strlen($x); $i++)
		if (ctype_alnum($x[$i]))
			$res .= $x[$i];
	return $res;	
}

function generate_foldermaker()
{
	global $DB;
	
	//startHtml();
	$nl = "<br>\r\n";
	
	//echo("<div>$nl");
	$R = $DB->query('SELECT * FROM rallyparams');
	$rd = $R->fetchArray();
	echo("@echo off$nl");
	echo("::$nl");
	echo(":: Image folder maker for ".$rd['RallyTitle']."$nl");
	echo("::$nl");
	echo("if #%1==# goto BOJ$nl");
	echo("if not exist %1\ mkdir %1$nl");
	echo("cd %1$nl");
	echo(":BOJ$nl");
	echo("mkdir photoclaims$nl");
	echo("cd photoclaims$nl");
	
	$MaxE = getValueFromDB("SELECT Max(EntrantID) As MaxE FROM entrants","MaxE",0);
	if ($MaxE > 99)
		$MaxEL = 3;         
	else
		$MaxEL = 2;
	$sql = "SELECT EntrantID,substr(RiderName,1,RiderPos-1) As RiderFirst";
	$sql .= ",substr(RiderName,RiderPos+1) As RiderLast";
	$sql .= " FROM (SELECT *,instr(RiderName,' ') As RiderPos FROM entrants) ";
	$sql .= "ORDER BY EntrantID";
	
	echo("mkdir by-entrant$nl");
	echo("cd by-entrant$nl");
	$R = $DB->query($sql);
	$E = array();
	while ($rd = $R->fetchArray()) {
		echo("mkdir ".sprintf("%0".$MaxEL."u",$rd['EntrantID']).'-'.clean_foldername($rd['RiderLast'])."$nl");
	}
	echo("cd ..$nl");
	echo("mkdir by-bonus$nl");
	echo("cd by-bonus$nl");
	
	$sql = "SELECT BonusID,BriefDesc FROM bonuses ORDER BY BonusID";
	$R = $DB->query($sql);
	$B = array();
	while ($rd = $R->fetchArray()) {
		echo("mkdir ".$rd['BonusID']."-".clean_foldername($rd['BriefDesc'])."$nl");
	}
	echo("cd ..$nl");
	echo("cd ..$nl");
	echo("echo ALL DONE ****$nl");
	echo("dir photoclaims$nl");
	
	//echo("</div$nl");
	//echo("</body></html>$nl");
	
}

generate_foldermaker();
?>

