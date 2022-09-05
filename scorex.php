<?php

/*
 * I B A U K   -   S C O R E M A S T E R
 *
 * I handle score explanation printing in bulk
 *
 * I am written for readability rather than efficiency, please keep me that way.
 *
 *
 * Copyright (c) 2022 Bob Stammers
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
<style>

h2 { text-align: center; }

.certframe {
	clear: both;
	width: 185mm;
	height: 272mm;
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	padding: 0;
	margin: 0 auto 0 auto;
	background-color: white;
	color: black;
	page-break-after: always;
	position: relative;
	top: 10mm;
}

.certificate	{

	font-family: "Times New Roman", Helvetica, Verdana, Arial, sans-serif, Times;
	width: 164mm;
	height: 253mm;
    padding: 7mm 14mm 7mm 14mm !important;
	margin: auto;
	font-size: 18pt;
	overflow: hidden;
}

table.sxtable {  margin: 0 auto 0 auto; padding: 0 .5em 0 .5em; }
table.sxtable caption {  border: solid; padding: .5em; margin: auto auto 0 auto;}
table.sxtable tr { height: 1.5em; }
table.sxtable tr:last-of-type td 	{ border-top: solid; }
table.sxtable td { padding-right: .5em; vertical-align: top; }
.sxdescx  { font-style: italic; font-size: smaller; }
td.sxitempoints,
td.sxtotalpoints { text-align: right; }

</style>
</head><body>
<?php
}
function printScorex($rd,$RT) {

    echo('<div class="certframe">');
    echo('<div class="certificate">');
    echo('<h2>'.$RT.'</h2>');
    echo($rd['ScoreX']);
    echo('</div>');
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

	$status = $KONSTANTS['EntrantFinisher'];
	if (isset($_REQUEST['status']))
		$status = $_REQUEST['status'];
	$sql .= " WHERE EntrantStatus In (".$status.")";

	$sql .= ' AND FinishPosition>0';

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
		echo($TAGS['NoCerts2Print'][0]);
	}
	echo('</body></html>');

}


startScorexListing();
printScorexes();

?>
