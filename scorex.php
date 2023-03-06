<?php

/*
 * I B A U K   -   S C O R E M A S T E R
 *
 * I handle score explanation printing in bulk
 *
 * I am written for readability rather than efficiency, please keep me that way.
 *
 *
 * Copyright (c) 2023 Bob Stammers
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

require_once('common.php');

function startScorexListing() {

?>
<!DOCTYPE html><html><head><title>ScoreX</title>
<meta charset="UTF-8" >
<meta name="viewport" content="width=device-width, initial-scale=1" >
<link rel="stylesheet" type="text/css" href="reboot.css?ver=<?= filemtime('reboot.css')?>">
<link rel="stylesheet" type="text/css" href="score.css?ver=<?= filemtime('score.css')?>">


</head><body>
<div class="noprint">
<form method="get" action="scorex.php" style="display:inline;">
<label for="showall"> 💯 </label>
<input type="checkbox" id="showall" name="all" onchange="this.form.submit();"
<?php
if (isset($_REQUEST['entrant']) && $_REQUEST['entrant'] != '') {
	if (isset($_REQUEST['all'])) unset($_REQUEST['all']);
}
if (isset($_REQUEST['all']) || !isset($_REQUEST['entrant']) || $_REQUEST['entrant']=='') {
	echo(' checked ');
}
echo('></form>');
echo('<form method="get" action="scorex.php" style="display:inline;">');
echo(' &nbsp;&nbsp; #<input type="number" style="width:3em;" name="entrant" onchange="this.form.submit();" ');
$entrant = 0;
if (!isset($_REQUEST['all']) && isset($_REQUEST['entrant']) && $_REQUEST['entrant']!='') {
	$entrant = $_REQUEST['entrant'];
	$rname = getValueFromDB("SELECT RiderName FROM entrants WHERE EntrantID=".$entrant,"RiderName","");
    echo('value="'.$entrant.'"> '.$rname);
} else {
	echo('>');
}
echo('</form></div>');

}
function printScorex($rd,$RT) {

    echo('<div class="scorex">');
    echo('<h2 class="center">'.$RT.'</h2>');
    echo($rd['ScoreX']);
	echo('<p class="center explain">@ '.date("Y-m-d H:i:s O", time()).'</p>');
    echo('</div>');
 
}
function printScorexes() {

	global $DB, $TAGS, $KONSTANTS;

    $RT = getValueFromDB("SELECT RallyTitle FROM rallyparams","RallyTitle","OMG!");
	//echo("Fetching records to print<br>");
	$sortspec = 'FinishPosition DESC';
	if (isset($_REQUEST['seq']))
		$sortspec = $_REQUEST['seq'];
	
	$sql = "SELECT *,substr(RiderName,1,RiderPos-1) As RiderFirst";
	$sql .= ",substr(RiderName,RiderPos+1) As RiderLast";
	$sql .= " FROM (SELECT *,instr(RiderName,' ') As RiderPos FROM entrants) ";

	$status = ''; //$KONSTANTS['EntrantFinisher'];
	if (isset($_REQUEST['status']))
		$status = $_REQUEST['status'];
	$sql .= " WHERE TRUE";
	if ($status != '') {
		$sql .= " AND EntrantStatus In (".$status.")";
		$sql .= ' AND FinishPosition>0';
	}

	if (isset($_REQUEST['class']))
		$sql .= ' AND Class In ('.$_REQUEST['class'].')';
	if (isset($_REQUEST['entrant']))
		$sql .= ' AND EntrantID In ('.$_REQUEST['entrant'].')';
	$sql .= ' ORDER BY '.$sortspec;
	$R = $DB->query($sql);
	$ds = false;
	while ($rd = $R->fetchArray())
	{
		//echo("Printing ".$rd['EntrantID']."<br>");
		printScorex($rd,$RT);
		$ds = true;
	}
	//echo '<hr>'.$_SERVER['HTTP_USER_AGENT'];
	if (!$ds)
	{
		echo('<p style="font-size:24pt;">&#8241;</p>');
	}
	echo('</body></html>');

}


startScorexListing();
printScorexes();

?>
