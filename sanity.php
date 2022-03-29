<?php

/*
 * I B A U K   -   S C O R E M A S T E R
 *
 * I handle the scoring end of things, formatting the scoresheets and recording the results
 *
 * I am written for readability rather than efficiency, please keep me that way.
 *
 *
 * Copyright (c) 2020 Bob Stammers
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


// Checklist stuff

// Registered, Check-out, First bonus, Check-in

$HOME_URL = "admin.php";

require_once('common.php');

function showStats()
{
	global $DB, $TAGS, $KONSTANTS;
	
	eval("\$evs = ".$TAGS['EntrantStatusV'][0]);

	
	echo('<p>'.$TAGS['sc_Overview'][1].'</p>');
?>
<style>
.datatable	{ border: 1px solid; }
.right		{ text-align: right; }
.center		{ text-align: center; padding: 0 1em 0 1em;}
</style>
<?php	
	echo('<table class="datatable">');
	$sql = "SELECT EntrantStatus As Status,Count(*) As Num FROM entrants GROUP BY EntrantStatus";
	$R = $DB->query($sql);
	while ($rd = $R->fetchArray()) {
		echo('<tr>');
		echo('<td class="right">'.$TAGS['sc_NoEntrants'][0].'<strong>'.$evs[$rd['Status']].'</strong></td>');
		echo('<td class="center">'.$rd['Num'].'</td>');
		echo('</tr>');
	}
	$sql = "SELECT Count(*) As Num FROM entrants WHERE Confirmed=1";
	$num = getValueFromDB($sql,"Num",0);
	echo('<tr><td class="right">'.$TAGS['sc_NoConfirmed'][0].'</td>');
	echo('<td class="center">'.$num.'</td></tr>');
	$sql = "SELECT * FROM entrants";
	$R = $DB->query($sql);
	$hispeed = 0.0;
	$himiles = 0;
	$maxbonuses = 0;
	while ($rd = $R->fetchArray()) {
		$speed = floatval($rd['AvgSpeed']);
		if ($speed > $hispeed)
			$hispeed = $speed;
		if ($rd['CorrectedMiles'] > $himiles)
			$himiles = $rd['CorrectedMiles'];
		$bonuses = count(explode(',',$rd['BonusesVisited']));
		if ($bonuses > $maxbonuses)
			$maxbonuses = $bonuses;
	}
	echo('<tr><td class="right">'.$TAGS['sc_HiSpeed'][0].'</td><td class="center">'.$hispeed.'</td></tr>');
	echo('<tr><td class="right">'.$TAGS['sc_HiDistance'][0].'</td><td class="center">'.$himiles.'</td></tr>');
	echo('<tr><td class="right">'.$TAGS['sc_MaxBonuses'][0].'</td><td class="center">'.$maxbonuses.'</td></tr>');
	echo('</table>');
}

function showspeeds()
{
	global $DB, $TAGS, $KONSTANTS;
	
	eval("\$evs = ".$TAGS['EntrantStatusV'][0]);
	
	$sql = "SELECT * FROM entrants";
	if (isset($_REQUEST['status']))
		$sql .= " WHERE EntrantStatus=".$_REQUEST['status'];
	$R = $DB->query($sql);
	$lospeed = 999.0;	$lomiles = 9999;
	$hispeed = 0.0;		$himiles = 0;
	$E = [];
	while ($rd = $R->fetchArray()) {
		$speed = floatval($rd['AvgSpeed']);
		if ($speed < $lospeed)
			$lospeed = $speed;
		if ($speed > $hispeed)
			$hispeed = $speed;
		if ($rd['CorrectedMiles'] < $lomiles)
			$lomiles = $rd['CorrectedMiles'];
		if ($rd['CorrectedMiles'] > $himiles)
			$himiles = $rd['CorrectedMiles'];
		$E[$rd['EntrantID']] = [$rd['RiderName'],$speed,$rd['CorrectedMiles'],$rd['EntrantStatus']];
	}
	echo('<table>');
	echo('<tr>');
	echo('<th colspan="2">'.$TAGS['sc_EntrantID'][0].'</th>');
	echo('<th>'.$TAGS['sc_Status'][0].'</th>');
	echo('<th>'.$TAGS['sc_AvgSpeed'][0].'</th>');
	echo('<th>'.$TAGS['sc_Distance'][0].'</th>');
	echo('</tr>');
	foreach($E as $num => $dat) {
		echo('<tr>');
		echo('<td>'.$num.'</td>');
		echo('<td>'.$dat[0].'</td>');
		echo('<td>'.$evs[$dat[3]].'</td>');
		echo('<td><meter min="0" max="'.$hispeed.'" value="'.$dat[1].'" title="'.$dat[1].'"></td>');
		echo('<td><meter min="0" max="'.$himiles.'" value="'.$dat[2].'" title="'.$dat[2].'"></td>');
		echo('</tr>');
	}
	echo('</table>');
}

startHtml($TAGS['ttSanity'][0]);
if (isset($_REQUEST['c'])) {
	switch($_REQUEST['c']) {
		case 'show':
			showspeeds();
			break;
		default:
			showStats();
	}
}
else
	showStats();

?>
