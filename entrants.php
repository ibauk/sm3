<?php

/*
 * I B A U K   -   S C O R E M A S T E R
 *
 * I handle basic maintenance of entrant records
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


$HOME_URL = 'admin.php';

 
require_once('common.php');



// Alphabetic order below


function deleteEntrant()
{
	global $DB, $TAGS, $KONSTANTS;

	$entrantid = $_POST['entrantid'];
	if ($entrantid == '')
		return;
	if ($_POST['rusure'] != $KONSTANTS['AreYouSureYes'])
		return;
	$sql = "DELETE FROM entrants WHERE EntrantID=$entrantid";		
	if (!$DB->exec($sql)) {
		dberror();
		exit;
	}
	if ($DB->lastErrorCode()<>0) {
		echo("SQL ERROR: ".$DB->lastErrorMsg().'<hr>'.$sql.'<hr>');
		exit;
	}

}

function fetchShowEntrant()
{
	global $DB, $TAGS, $KONSTANTS;

	//print_r($_REQUEST);
	$entrant = intval($_REQUEST['id']);
	$rel = '=';
	$ord = '';
	if (isset($_REQUEST['next'])) 
		$rel = '>';
	elseif (isset($_REQUEST['prev']))
		$rel = '<';
	if (isset($_REQUEST['ord'])) {
		$ord = $_REQUEST['ord'];
		if ($rel == '<') {
			if (strpos(strtolower($ord),' desc')===false) {
				if ($ord == '')
					$ord = 'EntrantID';
				$ord .= ' desc';
			}
		}
	}
	while(true) {
		$sql = "SELECT * FROM entrants WHERE EntrantID";
		$sql .= $rel;
		$sql .= intval($_REQUEST['id']);
		if ($ord != '')
			$sql .= " ORDER BY ".$ord;
	
			//echo('<hr>'.$sql.'<hr>');
			$R = $DB->query($sql);
	
		if (!$rd = $R->fetchArray()) {
			if ($rel == '=') {
				return;
			}
			$rel = '=';
		} else
			break;
	}

		if (!isset($_REQUEST['mode']) || $_REQUEST['mode']!='check')
			showEntrantRecord($rd);
		else
			showEntrantChecks($rd);
}

function fetchSpeedText($rd)
/*
 * I return either a blank string or an average speed depending on
 * whether the entrant record $rd has odo readings, times, and 
 * restminutes.
 *
 */
{
	global $DB, $TAGS, $KONSTANTS;
	
	if ($rd['CorrectedMiles']<1)
		return '';
	if (is_null($rd['FinishTime']))
		return '';
	if ($rd['FinishTime']<=$rd['StartTime'])
		return '';
	$start = new DateTime($rd['StartTime']);
	$finish = new DateTime($rd['FinishTime']);
	$etime = $start->diff($finish);
//	print_r($etime);
	$hours = ($etime->d * 24) + $etime->h + ($etime->i /60);
	$speed = $rd['CorrectedMiles'] / $hours;
	return number_format($speed,1);
}

function listEntrants($ord = "EntrantID")
{
	global $DB, $TAGS, $KONSTANTS;

	eval("\$evs = ".$TAGS['EntrantStatusV'][0]);

	
	$ShowTeamCol = TRUE;
	$R = $DB->query("SELECT Count(*) As Rex FROM entrants WHERE TeamID <> 0");
	if ($rd = $R->fetchArray())
		if ($rd['Rex'] == 0)
			$ShowTeamCol = FALSE;
		
	$sql = "SELECT *,substr(RiderName,1,RiderPos-1) As RiderFirst";
	$sql .= ",substr(RiderName,RiderPos+1) As RiderLast";
	$sql .= " FROM (SELECT *,instr(RiderName,' ') As RiderPos FROM entrants) ";
	$bonus = '';
	if (isset($_REQUEST['mode']))
	{
		if ($_REQUEST['mode']=='bonus')
		{
			$bonus = $_REQUEST['bonus'];
			$sql .= " WHERE ',' || BonusesVisited || ',' LIKE '%,$bonus,%'";
		}
		else if ($_REQUEST['mode']=='special')
		{
			$bonus = $_REQUEST['bonus'];
			$sql .= " WHERE ',' || SpecialsTicked || ',' LIKE '%,$bonus,%'";
		}
		else if ($_REQUEST['mode']=='combo')
		{
			$bonus = $_REQUEST['bonus'];
			$sql .= " WHERE ',' || CombosTicked || ',' LIKE '%,$bonus,%'";
		}
		else if ($_REQUEST['mode']=='find' && isset($_REQUEST['x']) && is_numeric($_REQUEST['x']))
		{
			$n = intval($_REQUEST['x']);
			if (substr($n,0,1) > '0' && strlen($n) <= 3) // Make sure it's reasonable to suppose it's an EntrantID
			$sql .= " WHERE EntrantID=$n";
		}
	}
	if (isset($_REQUEST['ord'])) {
		$ord = $_REQUEST['ord'];
	}
	$tab = '0';
	if (isset($_REQUEST['tab'])) {
		$tab = $_REQUEST['tab'];
	}

	if ($ord <> '')
		$sql .= " ORDER BY $ord";
	//echo('<br>listEntrants: '.$sql.'<br>');
	$R = $DB->query($sql);
	
	if (!isset($_REQUEST['mode']))
		$_REQUEST['mode'] = 'full';


	echo(' <button title="'.$TAGS['AdmNewEntrant'][0].'" autofocus onclick="window.location='."'entrants.php?c=newentrant"."'".'">+</button>');

	echo('<table id="entrants">');
	
	
	switch($_REQUEST['mode'])
	{
		case 'full':
		case 'check':
	}
	echo('</caption>');
	
	if ($ord == 'RiderName' || $ord == 'RiderFirst')
		$riderord = 'RiderLast';
	else
		$riderord = 'RiderName';
	$COLS = [
		["EntrantID","EntrantID","EntrantID"],
		["RiderName",$riderord,"RiderName"],
		["PillionName","PillionName","PillionName"],
		["Bike","Bike","Bike"],
		["TeamID","TeamID","TeamID"],
		["EntrantStatus","EntrantStatus","EntrantStatus"],
		["FinishPosition","EntrantStatus DESC,FinishPosition","FinishPosition"],
		["TotalPoints","TotalPoints","TotalPoints"],
		["CorrectedMiles","CorrectedMiles","CorrectedMiles"]
	];
	echo('<thead><tr>');

	foreach($COLS as $flds) {
		if ($flds[0] == 'TeamID' && !$ShowTeamCol)
			continue;
		echo('<th class="'.$flds[0].'">');
		echo('<a href="entrants.php?c=entrants&amp;mode=full&amp;ord=');
		$dir = '';
		if (substr($ord,0,strlen($flds[1])) == $flds[1] && substr($ord,-4) != 'DESC') 
			$dir = ' DESC';
		echo($flds[1].$dir);
		echo('">'.$TAGS[$flds[2]][0].'</a></th>');
	}

	echo('</tr>');
	echo('</thead><tbody id="entrantrows">');
	$fldsrch = (isset($_REQUEST['x']) &&  strpos($_REQUEST['x'],'=') != FALSE);
	if ($fldsrch)
		$fv = explode('=',$_REQUEST['x']);
	
	while ($rd = $R->fetchArray(SQLITE3_ASSOC))
	{
		//print_r($rd); echo('<hr>');
		$show_row = true;
		$found_field = '';
		$found_value = '';
		if ($_REQUEST['mode']=='find' && isset($_REQUEST['x']))
		{
			$show_row = false;
			foreach ($rd as $rdf=>$rdv)
				if (stripos($rdv,$_REQUEST['x'])!==FALSE || ($fldsrch && strcasecmp($fv[0],$rdf)==0 && strcasecmp($fv[1],$rdv)==0))
				{
					$found_field = $rdf;
					$found_value = $rdv;
					$show_row = true;
					break;
				}
			if (!$show_row)
				continue;
			//var_dump($rd);
		}
		$bclast = (isset($_REQUEST['nobc']) ? '' : '');
		
		echo('<tr class="link" onclick="window.location.href=\'entrants.php?c=entrant&amp;id='.$rd['EntrantID']);
		echo('&amp;mode='.$_REQUEST['mode'].'&amp;ord='.$ord.'&amp;tab='.$tab.'\'">');
		foreach($COLS as $flds) {
			if ($flds[0] == 'TeamID' && !$ShowTeamCol)
				continue;
			if ($flds[0] == 'EntrantStatus') {
				$es = $evs[''.$rd['EntrantStatus']];
				if ($es=='')
					$es = '[[ '.$rd['EntrantStatus'].']]';
				echo('<td class="EntrantStatus">'.$es.'</td>');
				continue;
			}
			echo('<td class="'.$flds[0].'">'.$rd[$flds[0]].'</td>');
		}
		if ($_REQUEST['mode']=='find' && isset($_REQUEST['x'])) {
			echo('<td>');
			if ($found_field != 'ExtraData')
				echo($found_field.'=');
			echo($found_value.'</td>');
		}


		echo('</tr>');
	}
	echo('</tbody></table>');

}



function saveEntrantRecord()
{
	global $DB, $TAGS, $KONSTANTS, $DBVERSION;

	$fa1 = array('RiderName','RiderFirst','RiderIBA','PillionName','PillionFirst','PillionIBA',
				'Bike','BikeReg','TeamID','Country','OdoKms','OdoCheckStart','OdoCheckFinish',
				'OdoScaleFactor','OdoRallyStart','OdoRallyFinish','CorrectedMiles','FinishTime',
				'BonusesVisited','CombosTicked','TotalPoints','FinishPosition',
				'EntrantStatus','ScoredBy','StartTime','Class','OdoCheckTrip','ExtraData','Cohort');

	if ($DBVERSION >= 2) {
		$fa2 = array('Phone','Email','NoKName','NoKRelation','NoKPhone');
		if ($DBVERSION >= 4)
			$fa2 = array_merge($fa2,['RestMinutes']);
		$fa21 = array_merge($fa1,$fa2);
		if ($DBVERSION >= 3) {
			$fa3 = array('BCMethod');
			$fa = array_merge($fa21,$fa3);
		} else {
			$fa = $fa21;
		}
	} else {
		$fa = $fa1;
	}

	$fab = array('BonusesVisited' => 'BonusID', 'CombosTicked' => 'ComboID');
	
	//var_dump($_REQUEST);
	//echo('<hr>');
	
	//if (isset($_REQUEST['BonusID']))
		//echo(" BonusID ");

	$adding = !isset($_REQUEST['updaterecord']);
	
	if ($adding)
	{
		$sql = "SELECT Max(EntrantID) AS MaxID FROM entrants";
		$newid = getValueFromDB($sql,"MaxID",0) + 1;			// Next in sequence
		$wishid = $newid;
		if (isset($_REQUEST['EntrantID']) && $_REQUEST['EntrantID'] != '') {
			$wishid = intval($_REQUEST['EntrantID']);
			if ($wishid > 0) {
				$sql = "SELECT EntrantStatus FROM entrants WHERE EntrantID=$wishid";
				$res = getValueFromDB($sql,"EntrantStatus",-1);
				if ($res >= 0)	// already exists
					$wishid = $newid;
			} else
				$wishid = $newid;
		}
	}
	
	
	if (!$adding)
		$sql = "UPDATE entrants SET ";
	else
	{
		$sql = "INSERT INTO entrants (EntrantID,";
		$comma = '';
		foreach($fa as $faa)
		{
			if (isset($_REQUEST[$faa]) || (isset($fab[$faa]) && isset($_REQUEST[$fab[$faa]])))
			{
				$sql .= $comma.$faa;
				$comma = ',';
			}
		}
		if (!$adding)
			$sql .= ",ScoringNow";
		$sql .= ") VALUES (";
	}

	if ($adding)
	{
		$sql .= $wishid.',';
	}
	$comma = '';
	foreach($fa as $faa)
	{
			if (isset($_REQUEST[$faa]) || (isset($fab[$faa]) && isset($_REQUEST[$fab[$faa]])))
		{
			$sql .= $comma;
			$comma = ',';
			if (!$adding) 
				$sql .= $faa.'=';
			switch($faa)
			{
				case 'RiderIBA':
				case 'PillionIBA':
				case 'OdoKms':
				case 'TeamID':
				case 'CorrectedMiles':
				case 'TotalPoints':
				case 'Class':
				case 'RestMinutes':
					$sql .= intval($_REQUEST[$faa]);
					break;
				case 'OdoCheckStart':
				case 'OdoCheckFinish':
				case 'OdoScaleFactor':
				case 'OdoRallyStart':
				case 'OdoRallyFinish':
					$sql .= floatval($_REQUEST[$faa]);
					break;
				case 'FinishTime':
					if ($_REQUEST['FinishDate']<>'' && $_REQUEST['FinishTime']<>'')
						$sql .= "'".$_REQUEST['FinishDate'].'T'.$_REQUEST['FinishTime']."'";
					else
						$sql .= "null";
					break;
				case 'StartTime':
					if ($_REQUEST['StartDate']<>'' && $_REQUEST['StartTime']<>'')
						$sql .= "'".$_REQUEST['StartDate'].'T'.$_REQUEST['StartTime']."'";
					else
						$sql .= "null";
					break;
				case 'BonusesVisited':
				case 'CombosTicked':
					//echo(' $fab[$faa] == '.$fab[$faa].' == '.$_REQUEST[$fab[$faa]].' ;');
					if (isset($_REQUEST[$fab[$faa]]))
					{
						$sql .= "'".$DB->escapeString(implode(',',$_REQUEST[$fab[$faa]]))."'";
						break;
					}
				default:
					$sql .= "'".$DB->escapeString($_REQUEST[$faa])."'";
			}
		}
	}
	if (!$adding)
	{
		$sql .= $comma."ScoringNow=";
		if (isset($_REQUEST['ScoringNow']))
			$sql .= $KONSTANTS['BeingScored'];
		else
			$sql .= $KONSTANTS['NotBeingScored'];
	}

	
	if ($adding)
		$sql .= ")";
	else
		$sql .= " WHERE EntrantID=".$_REQUEST['EntrantID'];
	
//	echo($sql.'<br>');
	if (!$DB->exec($sql)) {
		dberror();
		exit;
	}
	if ($DB->lastErrorCode()<>0) {
		echo("SQL ERROR: ".$DB->lastErrorMsg().'<hr>'.$sql.'<hr>');
		exit;
	}
	
}

function showEntrantBonuses($bonuses,$rejections)
{
	global $DB, $TAGS, $KONSTANTS;

	$ro = ' onclick="return false;" ';
	echo('<h4>'.$TAGS['ROUseScore'][1].'</h4>');
	$REJ = parseStringArray($rejections,',','=');
	$BA = explode(',',','.$bonuses); // The leading comma means that the first element is index 1 not 0
	//print_r($BA);
	$R = $DB->query('SELECT * FROM bonuses ORDER BY BonusID');
	$BP = array();
	while ($rd = $R->fetchArray())
	{
		$BP[$rd['BonusID']] = $rd['BriefDesc'];
	}
	foreach($BP as $bk => $b)
	{
		if ($bk <> '') {
			$tick = '';
			$chk = array_search($bk, $BA) ? ' checked="checked" ' : '';  // Depends on first item having index 1 not 0
			if ($chk=='') {
				$chk = array_search($KONSTANTS['ConfirmedBonusMarker'].$bk,$BA) ? ' checked="checked" ' : '';  // Depends on first item having index 1 not 0
				$tick = $chk==''? '' : $KONSTANTS['ConfirmedBonusTick'];
			}
			echo('<span title="'.htmlspecialchars($b).'"');
			if ($chk) echo(' class="keep checked"'); else if (isset($REJ['B'.$bk]) && $REJ['B'.$bk] != '') echo(' class="rejected"'); else echo(' class="keep"');
			echo('><label for="B'.$bk.'">'.$bk.' </label>');
			echo('<input '.$ro.' type="checkbox"'.$chk.' name="BonusID[]" id="B'.$bk.'" value="'.$bk.'"> ');
			echo('</span>'.$tick."\r\n");
		}
	}
}

function parseStringArray($str,$delim1,$delim2)
/*
 * Takes a string containing one or more item, each comprising a key, value pair
 *
 */
{
	$xx = explode($delim1,$str);
	$res = array();
	foreach($xx as $x)
	{
		$kvp = explode($delim2,$x);
		if (count($kvp) > 1)
			$res[$kvp[0]] = $kvp[1];
	}
	return $res;
}

function renumberAllEntrants()
// 
// This will renumber all the entrants into a single contiguous range
// The strategy is make two update passes to avoid problems with PK
// clashes
{
	global $DB, $TAGS, $KONSTANTS;
	
	$firstnum	= $_POST['firstnum'];
	$step		= $_POST['step'];		// No-one's ever going to use step <> 1 but ...
	$order		= $_POST['order'];

	if ($_POST['rusure'] != $KONSTANTS['AreYouSureYes'])
		return;
	
	$rex = [];
	$sql = "SELECT *,substr(RiderName,1,RiderPos-1) As RiderFirst";
	$sql .= ",substr(RiderName,RiderPos+1) As RiderLast";
	$sql .= " FROM (SELECT *,instr(RiderName,' ') As RiderPos FROM entrants) ";
	
	$R = $DB->query("$sql ORDER BY $order");
	$nextnum = $firstnum;
	$hinum = 0;
	while ($rd = $R->fetchArray())
	{
		if ($rd['EntrantID'] > $hinum)
			$hinum = $rd['EntrantID'];
		$rex[$rd['EntrantID']] = $nextnum;
		$nextnum += $step;
	}
	$base = 0;
	while ($firstnum + $base <= $hinum)
		$base += 1000; // Should be big enough
	if (!$DB->exec("BEGIN IMMEDIATE TRANSACTION")) {
		dberror();
		exit;
	}
	foreach ($rex as $k => $v)
	{
		$newnumber = $base + $v;
		$DB->exec("UPDATE entrants SET EntrantID=$newnumber WHERE EntrantID=$k");
		if ($DB->lastErrorCode()<>0) {
			echo("SQL ERROR: ".$DB->lastErrorMsg().'<hr>'.$sql.'<hr>');
			exit;
		}
	}
	
	if ($base > 0)
	{
		foreach ($rex as $k => $v)
		{
			$newnumber = $base + $v;
			$DB->exec("UPDATE entrants SET EntrantID=$v WHERE EntrantID=$newnumber");
			if ($DB->lastErrorCode()<>0) {
				echo("SQL ERROR: ".$DB->lastErrorMsg().'<hr>'.$sql.'<hr>');
				exit;
			}
		}
	}
	$DB->exec("COMMIT");
}

function renumberEntrant()
{
	global $DB, $TAGS, $KONSTANTS;

	error_log('Renumbering entrant');
	$entrantid = $_POST['entrantid'];
	if ($entrantid == '')
		return;
	$newnumber = $_POST['newnumber'];
	if ($newnumber == '' || $newnumber == $entrantid)
		return;
	if (getValueFromDB("SELECT EntrantID FROM entrants WHERE EntrantID=$newnumber","EntrantID",0) != 0)
		return;
	$sql = "UPDATE entrants SET EntrantID=$newnumber WHERE EntrantID=$entrantid";
	error_log($sql);
	if (!$DB->exec($sql)) {
		dberror();
		exit;
	}
	if ($DB->lastErrorCode()<>0) {
		echo("SQL ERROR: ".$DB->lastErrorMsg().'<hr>'.$sql.'<hr>');
		exit;
	}

}


function showEntrantRejectedClaims($rejections)
{
	global $DB, $TAGS, $KONSTANTS;

	$R = $DB->query('SELECT RejectReasons FROM rallyparams');
	$rd = $R->fetchArray();
	
	$RRlines = explode("\n",$rd['RejectReasons']);
	//var_dump($RRlines);
	//echo('<hr>');
	//var_dump($rejections);
	//$R->close();
	$RR = array();
	foreach($RRlines as $rrl) {
		//var_dump($rrl);
		$x = explode('=',$rrl);
		if (count($x) >1)
			$RR[$x[0]] = $x[1];
	}
	$BA = explode(',',$rejections); // The leading comma means that the first element is index 1 not 0
	
	//var_dump($BA);

	echo('<ul>');
	foreach($BA as $r)
	{
		//echo(' # '.$r.' ## ');
		$reject = explode('=',$r);
		$bonustype = substr($reject[0],0,1);
		$bonusid = substr($reject[0],1);
		//echo(' [[ '.$r.' - '.$bonustype.' -- '.$bonusid.'  ]] ');
		switch($bonustype)
		{
			case 'B':
				$sql = "SELECT BonusID as bid, BriefDesc as bd FROM bonuses WHERE BonusID='$bonusid'";
				break;
			case 'C':
				$sql = "SELECT ComboID as bd, BriefDesc as bid FROM combinations WHERE ComboID='$bonusid'";
				break;
			case '':
				continue 2; // next foreach
			default:
				echo('<p>OMG</p>');
				var_dump($bonustype);
				return; // don't know what's going on so give up
		}
		$x = $RR[$reject[1]];
		if ($x == '')
			continue;
		$R = $DB->query($sql);
		$rd = $R->fetchArray();
		echo('<li title="'.$rd['bd'].'">');
		echo($rd['bid'].' = '.htmlspecialchars($x).'; ');
		echo('</li>');
	}	
	echo('</ul>');
	
}

	
function emitJS() {
	?>
	<script>
	
		function printscorex() {
			let w = window.open('','scorex');
			let x = document.getElementById('scorex');
			let head = '<!DOCTYPE html><html><head><title>ScoreX</title></head><body>';
			let y = '<style>'+x.getAttribute('data-style')+'</style>';
			let z = '<h2>'+x.getAttribute('data-title')+'</h2>';
			w.document.firstChild.innerHTML = head + y + z + x.innerHTML;
			w.print();
		}
	</script>
	<?php
}
	
function showEntrantScorex($scorex)
{
	global $TAGS;

	$RallyTitle = getValueFromDB("SELECT RallyTitle FROM rallyparams","RallyTitle","***");
	emitjs();

	echo('<button onclick="printscorex();return false;">Print</button>');

	$style  = '@page { margin-top: 0;  margin-bottom: 2em;   }';
    
    $style .= 'h2 {top: 2em; text-align: center; }';
    $style .= 'table.sxtable { margin: 0 auto 0 auto; padding: 0 .5em 0 .5em; }';
    $style .= 'table.sxtable caption {  border: solid; padding: .5em; margin: auto auto 0 auto;}';
    $style .= 'table.sxtable tr { height: 1.5em; }';
    $style .= 'table.sxtable tr:last-of-type td 	{ border-top: solid; }';
    $style .= 'table.sxtable td { padding-right: .5em; vertical-align: top; font-size: larger; }';
    $style .= '.sxdescx  { font-style: italic; font-size: smaller; }';
    $style .= 'td.sxitempoints,';
    $style .= 'td.sxtotalpoints { text-align: right; }';

	echo('<div id="scorex" title="'.$TAGS['dblclickprint'][0].'" ondblclick="sxprint();" data-style="'.$style.'" ');
	echo('data-title="'.htmlspecialchars($RallyTitle).'" ');

	echo(')>');
	echo($scorex);
	echo('</div>');
}


function showFinisherList()
/*
 * quick & dirty list of finishers only
 *
 */
{

	global $DB, $TAGS, $KONSTANTS;

	$TIMEOUTSECS = 30;

	$rallyUsesKms = ($KONSTANTS['BasicDistanceUnit'] != $KONSTANTS['DistanceIsMiles']);
	
	if (isset($_REQUEST['t']))
		$TIMEOUTSECS = intval($_REQUEST['t']);
	
	if ($KONSTANTS['DecimalPointIsComma'])
	{
		$dp = ',';
		$cm = '.';
	}
	else
	{
		$dp = '.';
		$cm = ',';
	}

	$status = $KONSTANTS['EntrantFinisher'];
	if (isset($_REQUEST['ok']))
		$status .= ",".$KONSTANTS['EntrantOK'];
	if (isset($_REQUEST['dnf']))
		$status .= ",".$KONSTANTS['EntrantDNF'];
	if (isset($_REQUEST['status']))
		$status = $_REQUEST['status'];
	
	$sortspec = 'EntrantStatus DESC,FinishPosition,TotalPoints DESC,CorrectedMiles ';
	if (isset($_REQUEST['seq']))
		$sortspec = $_REQUEST['seq'];
	
	$sql = "SELECT *,substr(RiderName,1,RiderPos-1) As RiderFirst";
	$sql .= ",substr(RiderName,RiderPos+1) As RiderLast";
	$sql .= " FROM (SELECT *,instr(RiderName,' ') As RiderPos FROM entrants) ";

	$sql .= " WHERE EntrantStatus IN (".$status.")";
	
	if (isset($_REQUEST['class']))
		$sql .= ' AND Class In ('.$_REQUEST['class'].')';
	$sql .= ' ORDER BY '.$sortspec;
	//echo($sql);
	$R = $DB->query($sql);
?><!DOCTYPE html>
<html>
<head>
<?php
echo('<title>'.$TAGS['ttFinishers'][0].'</title>');
?>
<meta name="viewport" content="width=device-width, initial-scale=1" />
<link rel="stylesheet" type="text/css" href="score.css?ver=<?= filemtime('score.css')?>">
<meta http-equiv="refresh" content="<?php echo($TIMEOUTSECS); ?>">
<script>
<!--
function countdown(secs) {
	var spo = document.querySelector('#countdown');
	setInterval(function() {
		spo.textContent = secs;
		--secs;
	},1000);
}
function countup(secs) {
	var sex = 0;
	var spo = document.querySelector('#countdown');
	setInterval(function() {
		sex++;
		var x = 't=' + secs + ' : ' + sex;
		spo.textContent = x;
	},1000);
}
function formSubmit(e) {
	e.target.form.submit();
}
-->
</script>
</head>
<body onload="<?php echo("countup($TIMEOUTSECS)")?>">
<div class="noprint" style="font-size:8pt; padding:0; margin:0;">
<span id="countdown">&glj;</span>
<span id="listctrl">
<form method="get" action="entrants.php">
<input type="hidden" name="c" value="qlist">
<?php
	echo('<label for="cd_ok">+ok</label> <input type="checkbox" id="cd_ok" name="ok" value="ok" '.(isset($_REQUEST['ok'])? 'checked' : '').' onclick="formSubmit(event);"> ');
	echo('<label for="cd_dnf">+dnf</label> <input type="checkbox" id="cd_dnf" name="dnf" value="dnf" '.(isset($_REQUEST['dnf'])? 'checked' : '').' onclick="formSubmit(event);"> ');
?> 
 </form>
 </span>
</div>
<?php
	
	$PAGEROWS = 0;
	if (isset($_REQUEST['page']))
		$PAGEROWS = intval($_REQUEST['page']);
	$nrows = 0;
	$R->reset();
	while ($R->fetchArray())
		$nrows++;
	$R->reset();
	
	$rowsdone = 0;
	while ($rowsdone < $nrows)
	{
		echo('<table class="qdfinishers">');
		echo('<thead><tr>');
		echo('<th>'.$TAGS['qPlace'][0].'</th>');
		echo('<th>'.$TAGS['qName'][0].'</th>');
		
		if ($rallyUsesKms)
			$dist = $TAGS['qKms'][0];
		else
			$dist = $TAGS['qMiles'][0];
		$dist = getSetting('distanceCustomUnit',$dist);
		echo('<th>'.$dist.'</th>');
		echo('<th>'.$TAGS['qPoints'][0].'</th>');
	
		if (isset($_REQUEST['ss']))
			echo('<th>Speed</th>');
		echo('</tr></thead><tbody>');
		$n = 0;
		
		while ($rd = $R->fetchArray())
		{
			$rowsdone++;
			echo('<tr>');
			switch($rd['EntrantStatus']) {
				case $KONSTANTS['EntrantDNF']:
					echo('<td>DNF</td>');
					break;
				case $KONSTANTS['EntrantDNS']:
					echo('<td>DNS</td>');
					break;
				case $KONSTANTS['EntrantOK']:
					echo('<td>ok</td>');
					break;
				default:
					echo('<td>'.$rd['FinishPosition'].'</td>');
			}
			echo('<td>'.$rd['RiderName']);
			if ($rd['PillionName'] > '')
				echo(' & '.$rd['PillionName']);
			echo('</td>');
			$dist = $rd['CorrectedMiles'];
			if (getSetting('distanceZeroBased','false')=='true')
				$dist++;
			echo('<td>'.number_format($dist,0,$dp,$cm).'</td>');
			echo('<td>'.number_format($rd['TotalPoints'],0,$dp,$cm).'</td>');
			if (isset($_REQUEST['ss']))
				echo('<td> '.fetchSpeedText($rd).'</td>');
			
			echo('</tr>');
			$n++;
			if ($PAGEROWS > 0 && $n >= $PAGEROWS)
				break;
		}
		echo('</tbody></table>');
	}
	if ($nrows < 1)
		echo('<p>'.$TAGS['NoCerts2Print'][0].'</p>');
	echo('<p>&nbsp;</p>'); //Spacer to facilitate screen capture
?>
</body>
</html>
<?php	
}
















function showAllScorex()
{
	global $DB, $TAGS, $KONSTANTS;

	$R = $DB->query("SELECT RallyTitle FROM rallyparams");
	$rd = $R->fetchArray();
	$title = htmlspecialchars(preg_replace('/\[|\]|\|/','',$rd['RallyTitle']));

	$sortspec = 'RiderLast ';
	if (isset($_REQUEST['seq']))
		$sortspec = $_REQUEST['seq'];
	
	$sql = "SELECT *,substr(RiderName,1,RiderPos-1) As RiderFirst";
	$sql .= ",substr(RiderName,RiderPos+1) As RiderLast";
	$sql .= " FROM (SELECT *,instr(RiderName,' ') As RiderPos FROM entrants) ";

	$sql .= " WHERE EntrantStatus<>".$KONSTANTS['EntrantDNS'];
	
	$sql .= " AND ScoreX Is Not Null";

	if (isset($_REQUEST['class']))
		$sql .= ' AND Class In ('.$_REQUEST['class'].')';
	if (isset($_REQUEST['entrant']))
		$sql .= ' AND EntrantID In ('.$_REQUEST['entrant'].')';
	$sql .= ' ORDER BY '.$sortspec;
	$R = $DB->query($sql);
?><!DOCTYPE html>
<html>
<head>
<?php
echo('<title>'.$TAGS['ttScoreX'][0].'</title>');
?>
<meta name="viewport" content="width=device-width, initial-scale=1" />
<link rel="stylesheet" type="text/css" href="score.css?ver=<?= filemtime('score.css')?>">
</head>
<body>
<?php
	$n = 0;
	while ($rd = $R->fetchArray())
	{
		echo('<h1 class="center">'.$title.'</h1>');
		echo('<div class="scorex">');
		echo($rd['ScoreX']);
		echo('</div>');
		$n++;
	}
	if ($n < 1)
		echo('<p>'.$TAGS['NoScoreX2Print'][0].'</p>');
?>
</body>
</html>
<?php	
}


function showDeleteEntrant()
{
	global $DB, $TAGS, $KONSTANTS;

	echo('<div class="maindiv">');
	echo('<form method="post" action="entrants.php">');

	echo('<p>'.$TAGS['UtlDeleteEntrant'][1].'</p>');

	echo('<input type="hidden" name="c" value="kill">');
	echo('<span class="vlabel" title="'.$TAGS['ChooseEntrant'][1].'">');
	echo('<select id="entrantid" name="entrantid">');
	echo('<option value="">'.$TAGS['ChooseEntrant'][0].'</option>');
	$R = $DB->query("SELECT * FROM entrants ORDER BY EntrantID");
	while ($rd = $R->fetchArray())
	{
		echo('<option value="'.$rd['EntrantID'].'">'.$rd['EntrantID'].' - '.$rd['RiderName'].'</option>');
	}
	echo('</select>');
	echo('</span>');
	echo('<span class="vlabel" title="'.$TAGS['ConfirmDelEntrant'][1].'">');
	echo('<label class="wide" for="rusure">'.$TAGS['ConfirmDelEntrant'][0].'</label> ');
	echo('<input type="checkbox" id="rusure" onclick="document.getElementById('."'killbutton'".').disabled=!this.checked;" name="rusure" value="'.$KONSTANTS['AreYouSureYes'].'">');
	echo('</span>');
	echo('<span class="vlabel">');
	echo('<input type="submit" id="killbutton" disabled name="killer" value="'.$TAGS['DeleteEntrant'][0].'">');
	echo('</span>');
	echo('</form>');
	echo('</div>');
}

function showRAE()
{
	global $DB, $TAGS, $KONSTANTS;

	echo('<div class="maindiv">');
	echo('<form method="post" action="entrants.php">');

	echo('<p>'.$TAGS['UtlRAE'][1].'</p>');

	echo('<input type="hidden" name="c" value="rae">');
	echo('<input type="hidden" name="step" value="1">');
	echo('<input type="hidden" name="seq" value="">'); // ascending/descending
	echo('<span class="vlabel" title="'.$TAGS['raeFirst'][1].'">');
	echo('<label for="firstnum">'.$TAGS['raeFirst'][0].'</label> ');
	echo('<input type="number" id="firstnum" name="firstnum" value="1">');
	echo('</span>');
	echo('<span class="vlabel" title="'.$TAGS['raeOrder'][1].'">');
	echo('<label for="order">'.$TAGS['raeOrder'][0].'</label> ');
	echo('<select id="order" name="order">');
	echo('<option selected value="EntrantID">'.$TAGS['EntrantID'][0].'</option>');
	echo('<option value="RiderLast">'.$TAGS['raeRiderLast'][0].'</option>');
	echo('<option value="RiderFirst">'.$TAGS['raeRiderFirst'][0].'</option>');
	echo('<option value="random()">'.$TAGS['raeRandom'][0].'</option>');
	echo('</select> ');
	
	echo('<span title="'.$TAGS['raeSortA'][1].'">');
	echo('<label for="seqasc">'.$TAGS['raeSortA'][0].'</label> ');
	echo('<input type="radio" id="seqasc" name="seq" checked value="">  ');
	echo('</span>');
	echo('<span title="'.$TAGS['raeSortD'][1].'">');
	echo('<label for="seqdes">'.$TAGS['raeSortD'][0].'</label> ');
	echo('<input type="radio" id="seqdes" name="seq" value=" DESC">');
	echo('</span>');
	echo('</span>');
	echo('<span class="vlabel" title="'.$TAGS['raeConfirm'][1].'">');
	echo('<label class="wide" for="rusure">'.$TAGS['raeConfirm'][0].'</label> ');
	echo('<input type="checkbox" id="rusure" onclick="document.getElementById('."'killbutton'".').disabled=!this.checked;" name="rusure" value="'.$KONSTANTS['AreYouSureYes'].'">');
	echo('</span>');
	echo('<span class="vlabel">');
	echo('<input type="submit" id="killbutton" disabled name="killer" value="'.$TAGS['raeSubmit'][0].'">');
	echo('</span>');
	echo('</form>');
	echo('</div>');
	
}



function showRenumberEntrant()
{
	global $DB, $TAGS, $KONSTANTS;

	echo('<div class="maindiv">');
	echo('<form method="post" action="entrants.php">');

	echo('<p>'.$TAGS['UtlRenumEntrant'][1].'</p>');

	echo('<input type="hidden" name="c" value="renumentrant">');
	echo('<span class="vlabel" title="'.$TAGS['ChooseEntrant'][1].'">');
	echo('<select id="entrantid" name="entrantid">');
	echo('<option value="">'.$TAGS['ChooseEntrant'][0].'</option>');
	$R = $DB->query("SELECT * FROM entrants ORDER BY EntrantID");
	while ($rd = $R->fetchArray())
	{
		echo('<option value="'.$rd['EntrantID'].'">'.$rd['EntrantID'].' - '.$rd['RiderName'].'</option>');
	}
	echo('</select>');
	echo('</span>');
	
	echo('<span class="vlabel" title="'.$TAGS['NewEntrantNum'][1].'">');
	echo('<label for="newnumber">'.$TAGS['NewEntrantNum'][0].'</label> ');
	echo('<input type="number" id="newnumber" name="newnumber" onchange="document.getElementById('."'killbutton'".').disabled=this.value < 1;" value="">');
	echo('</span>');
	echo('<span class="vlabel">');
	echo('<input type="submit" id="killbutton" disabled name="killer" value="'.$TAGS['RenumberGo'][0].'">');
	echo('</span>');
	echo('</form>');
	echo('</div>');
}



/* Check-in/check-out stuff */
function showEntrantChecks($rd)
{
	global $DB, $TAGS, $KONSTANTS;

	echo('<form method="post" action="entrants.php">');

	echo('<input type="hidden" name="c" value="entrants">');
	echo('<input type="hidden" name="mode" value="check">');
	echo('<input type="hidden" name="updaterecord" value="'.$rd['EntrantID'].'">');
	
	echo('<span class="vlabel"  style="font-weight: bold;" title="'.$TAGS['EntrantID'][1].'"><label for="EntrantID">'.$TAGS['EntrantID'][0].' </label> ');
	echo('<input type="text" class="number"  readonly name="EntrantID" id="EntrantID" value="'.$rd['EntrantID'].'">'.' <label>'.htmlspecialchars($rd['RiderName']).'</label>');
	
	popBreadcrumb();
	echo('<input title="'.$TAGS['FullDetails'][1].'" id="FullDetailsButton" type="button" value="'.$TAGS['FullDetails'][0].'"');
	echo(' onclick="window.location='."'entrants.php?c=entrant&amp;id=".$rd['EntrantID']."&mode=full");
	echo("'".'"> ');
	
	echo('<input type="submit" name="savedata" value="'.$TAGS['SaveEntrantRecord'][0].'">');
	echo('</span>');

	
	
	
	
	
	
	echo('<fieldset  id="tab_odo">');
	
	$odoF = $DB->query("SELECT OdoCheckMiles,StartTime FROM rallyparams");
	$odoC = $odoF->fetchArray();
	
	echo('<input type="hidden" name="OdoCheckMiles" id="OdoCheckMiles" value="'.$odoC['OdoCheckMiles'].'">');

	if (floatval($odoC['OdoCheckMiles']) < 1.0)
		$hideOdoCheck = true;
	else
		$hideOdoCheck = false;
	

	echo('<span class="vlabel" title="'.$TAGS['OdoKms'][1].'">');
	echo('<label for="OdoKms">'.$TAGS['OdoKms'][0].' </label> ');
	echo('<select name="OdoKms" id="OdoKms" onchange="odoAdjust();">');
	if ($rd['OdoKms']==$KONSTANTS['OdoCountsKilometres'])
	{
		echo('<option value="'.$KONSTANTS['OdoCountsMiles'].'">'.$TAGS['OdoKmsM'][0].'</option>');
		echo('<option value="'.$KONSTANTS['OdoCountsKilometres'].'" selected >'.$TAGS['OdoKmsK'][0].'</option>');
	}
	else
	{
		echo('<option value="'.$KONSTANTS['OdoCountsMiles'].'" selected >'.$TAGS['OdoKmsM'][0].'</option>');
		echo('<option value="'.$KONSTANTS['OdoCountsKilometres'].'" >'.$TAGS['OdoKmsK'][0].'</option>');
	}
	echo('</select>');
	echo('</span>');




	if ($hideOdoCheck)
		echo('<div style="display:none;">');
	echo('<span  class="xlabel" title="'.$TAGS['OdoCheckStart'][1].' "><label for="OdoCheckStart">'.$TAGS['OdoCheckStart'][0].' </label> ');
	echo('<input  onchange="odoAdjust(false);" type="number" class="bignumber" step="any" min="0" name="OdoCheckStart" id="OdoCheckStart" value="'.$rd['OdoCheckStart'].'"> </span>');
	
	echo('<span  title="'.$TAGS['OdoCheckFinish'][1].' "><label for="OdoCheckFinish">'.$TAGS['OdoCheckFinish'][0].' </label> ');
	echo('<input  onchange="odoAdjust(false);" type="number" class="bignumber" step="any" min="0" name="OdoCheckFinish" id="OdoCheckFinish" value="'.$rd['OdoCheckFinish'].'"> </span>');
	
	echo('<span  title="'.$TAGS['OdoCheckTrip'][1].' "><label for="OdoCheckTrip">'.$TAGS['OdoCheckTrip'][0].' </label> ');
	echo('<input  onchange="odoAdjust(true);" type="number" step="any" min="0" name="OdoCheckTrip" id="OdoCheckTrip" value="'.$rd['OdoCheckTrip'].'"> </span>');
	
	echo('<span   title="'.$TAGS['OdoScaleFactor'][1].'"><label for="OdoScaleFactor">'.$TAGS['OdoScaleFactor'][0].' </label> ');
	echo('<input type="number" step="any" class="bignumber" min="0" name="OdoScaleFactor" id="OdoScaleFactor" value="'.$rd['OdoScaleFactor'].'"> </span>');
	
	if ($hideOdoCheck)
		echo('</div>');
	
	echo('<span  class="xlabel" title="'.$TAGS['OdoRallyStart'][1].' "><label for="OdoRallyStart">'.$TAGS['OdoRallyStart'][0].' </label> ');
	echo('<input  onchange="odoAdjust();" type="number" step="any" min="0" name="OdoRallyStart" id="OdoRallyStart" value="'.$rd['OdoRallyStart'].'"> </span>');
	
	echo('<span  title="'.$TAGS['OdoRallyFinish'][1].' "><label for="OdoRallyFinish">'.$TAGS['OdoRallyFinish'][0].' </label> ');
	echo('<input  onchange="odoAdjust();" type="number" step="any" min="0" name="OdoRallyFinish" id="OdoRallyFinish" value="'.$rd['OdoRallyFinish'].'"> </span>');
	
	echo('<span >');
	echo('<label for="CorrectedMiles" >'.$TAGS['CorrectedMiles'][0].' </label>');
	echo(' <input type="number" name="CorrectedMiles" id="CorrectedMiles" value="'.$rd['CorrectedMiles'].'" title="'.$TAGS['CorrectedMiles'][1].'"> ');
	echo('</span>');
	
	echo('<hr><br>');
	echo('<span   title="'.$TAGS['EntrantStatus'][1].'"><label for="EntrantStatus">'.$TAGS['EntrantStatus'][0].' </label>');
	echo('<select name="EntrantStatus" id="EntrantStatus">');
	if ($rd['EntrantStatus']=='')
		$rd['EntrantStatus'] = $KONSTANTS['DefaultEntrantStatus'];
	echo('<option value="'.$KONSTANTS['EntrantDNS'].'" '.($rd['EntrantStatus']==$KONSTANTS['EntrantDNS'] ? ' selected="selected" ' : '').'>'.$TAGS['EntrantDNS'][0].'</option>');
	echo('<option value="'.$KONSTANTS['EntrantOK'].'" '.($rd['EntrantStatus']==$KONSTANTS['EntrantOK'] ? ' selected="selected" ' : '').'>'.$TAGS['EntrantOK'][0].'</option>');
	echo('<option value="'.$KONSTANTS['EntrantFinisher'].'" '.($rd['EntrantStatus']==$KONSTANTS['EntrantFinisher'] ? ' selected="selected" ' : '').'>'.$TAGS['EntrantFinisher'][0].'</option>');
	echo('<option value="'.$KONSTANTS['EntrantDNF'].'" '.($rd['EntrantStatus']==$KONSTANTS['EntrantDNF'] ? ' selected="selected" ' : '').'>'.$TAGS['EntrantDNF'][0].'</option>');
	echo('</select></span>');
	echo('<br><hr>');
	$dt = splitDatetime($rd['StartTime']); 
	if ($dt[0]=='')
	{
		$dt = splitDatetime($odoC['StartTime']); // Default to rally start time
	}
	echo('<span class="vlabel">');
	echo('<label for="StartDate" class="vlabel">'.$TAGS['StartDateE'][0].' </label>');
	echo(' <input type="date" name="StartDate" id="StartDate" value="'.$dt[0].'" title="'.$TAGS['StartDateE'][1].'"> ');
	echo('<label for="StartTime">'.$TAGS['StartTimeE'][0].' </label>');
	echo(' <input type="time" name="StartTime" id="StartTime" value="'.$dt[1].'" title="'.$TAGS['StartTimeE'][1].'"> ');
	echo(' <input type="button" value="'.$TAGS['nowlit'][0].'" onclick="setSplitNow(\'Start\');">');
	echo('</span>');

	$dt = splitDatetime($rd['FinishTime']); 

	echo('<span class="vlabel">');
	echo('<label for="FinishDate" class="vlabel">'.$TAGS['FinishDateE'][0].' </label>');
	echo(' <input type="date" name="FinishDate" id="FinishDate" value="'.$dt[0].'" title="'.$TAGS['FinishDateE'][1].'"> ');
	echo('<label for="FinishTime">'.$TAGS['FinishTimeE'][0].' </label>');
	echo(' <input type="time" name="FinishTime" id="FinishTime" value="'.$dt[1].'" title="'.$TAGS['FinishTimeE'][1].'"> ');
	echo(' <input type="button" value="'.$TAGS['nowlit'][0].'" onclick="setSplitNow(\'Finish\');">');
	echo('</span>');

	
	
	echo('</fieldset>');
	
	
	echo('</form>');
		
}
























function showEntrantCombinations($Combos,$rejections)
{
	global $DB, $TAGS, $KONSTANTS;
	
	$ro = ' onclick="return false;" ';
	echo('<h4>'.$TAGS['ROUseScore'][1].'</h4>');

	$REJ = parseStringArray($rejections,',','=');
	$BAB = explode(',',','.$Combos); // The leading comma means that the first element is index 1 not 0
	
	$R = $DB->query('SELECT * FROM combinations ORDER BY ComboID');
	$BA = array();
	while ($rd = $R->fetchArray())
	{
		$BA[$rd['ComboID']] = $rd['BriefDesc'];
	}
	echo('<span  class="xlabel" ></span>');
	//var_dump($BA);
	foreach($BA as $bk => $b)
	{
		if ($bk <> '') {
			$tick = '';
			$chk = array_search($bk, $BAB) ? ' checked="checked" ' : '';  // Depends on first item having index 1 not 0
			if ($chk=='') {
				$chk = array_search($KONSTANTS['ConfirmedBonusMarker'].$bk,$BAB) ? ' checked="checked" ' : '';  // Depends on first item having index 1 not 0
				$tick = $chk==''? '' : $KONSTANTS['ConfirmedBonusTick'];
			}
			echo('<span title="'.htmlspecialchars($bk).'"');
			if ($chk) echo(' class="keep checked"'); else if (isset($REJ['C'.$bk]) && $REJ['C'.$bk] != '') echo(' class="rejected"'); else echo(' class="keep"');
			echo('><label for="C'.$bk.'">'.htmlspecialchars($b).' </label>');
			echo('<input '.$ro.' type="checkbox"'.$chk.' name="ComboID[]" id="C'.$bk.'" value="'.$bk.'"> ');
			echo(' &nbsp;&nbsp;</span>'.$tick."\r\n");
		}
	}
}

function showEntrantExtraData($xd)
{
	global $DB, $TAGS, $KONSTANTS;

	$rows = substr_count($xd,"\n") + 1;
	echo('<h4>'.$TAGS['ExtraData'][1].'</h4>');
	echo('<textarea onchange="enableSaveButton();" name="ExtraData" style="width:100%;" rows="'.$rows.'">'.$xd.'</textarea>');
}


function showEntrantRecord($rd)
{
	global $DB, $TAGS, $KONSTANTS, $DBVERSION;
//var_dump($rd);
?>
<script>
	function applyCohortChange(sel) {
		console.log("Hello sailor");
		let opt = sel.options[sel.selectedIndex];
		let status = document.getElementById('EntrantStatus');
		if (status.value == EntrantOK) {
			console.log("Here we go, here we go");
			let sdate = document.getElementById('StartDate');
			let stime = document.getElementById('StartTime');
			if (opt.getAttribute('data-fixed')=='0') {
				console.log('Setting start time to null');
				sdate.value = '';
				stime.value = '';
			} else {
				let dt = opt.getAttribute('data-time').split('T');
				sdate.value = dt[0];
				stime.value = dt[1];
			}
		}
		enableSaveButton();
	}
</script>
<?php
	$is_new_record = ($rd['EntrantID']=='');
	echo('<form method="post" action="entrants.php">');

	if (isset($_REQUEST['ord'])) {
		echo('<input type="hidden" name="ord" value="'.$_REQUEST['ord'].'">');
	}

	$tab2show = 0;
	if (isset($_REQUEST['tab'])) {
		$tab2show = intval($_REQUEST['tab']);
	}
	echo('<input type="hidden" name="tab" id="tab2show" value="'.$tab2show.'">');

	echo('<input type="hidden" name="c" value="entrants">');
	echo('<span class="vlabel"  style="font-weight: bold;" title="'.$TAGS['EntrantID'][1].'"><label for="EntrantID">'.$TAGS['EntrantID'][0].' </label> ');
	if ($is_new_record)
	{
		$ro = '';
		$rd['OdoKms'] = $KONSTANTS['BasicDistanceUnit'];
	}
	else
		$ro = ' readonly ';
	echo('<input type="text" onchange="enableSaveButton();"  class="number"  '.$ro.' name="EntrantID" id="EntrantID" value="'.$rd['EntrantID'].'">');
	echo(' <label>'.htmlspecialchars($rd['RiderName']).'</label> ');
	if (!$is_new_record)
	{
		echo('<input type="hidden" name="updaterecord" value="'.$rd['EntrantID'].'">');
		echo('<input title="'.$TAGS['ScoreNow'][1].'" id="ScoreNowButton" type="button" value="'.$TAGS['ScoreNow'][0].'"');
		echo(' onclick="window.open('."'scorecard.php?c=score&amp;EntrantID=".$rd['EntrantID']."','score'".')" >');
	}
	if ($rd['RiderName'] <> '')
		$dis = '';
	else
		;
		$dis = ' disabled ';
	echo(' <input type="submit"'.$dis.' data-triggered="0" onclick="'."this.setAttribute('data-triggered','1')".'" id="savedata" name="savedata" value="'.$TAGS['RecordSaved'][0].'" data-altvalue="'.$TAGS['SaveEntrantRecord'][0].'">');

	if (!$is_new_record) {
		if ($rd['EntrantStatus']==$KONSTANTS['EntrantFinisher']) {
			echo(' <input title="'.$TAGS['Print1Cert'][1].'" id="PrintCertButton" type="button" value="'.$TAGS['Print1Cert'][0].'" ');
			echo(' onclick="window.open('."'certificate.php?c=viewcert&amp;EntrantID=".$rd['EntrantID']."','cert'".')" >');
		}
	
		echo(' <input title="'.$TAGS['Print1ClaimLog'][1].'" id="PrintCLButton" type="button" value="'.$TAGS['Print1ClaimLog'][0].'" ');
		echo(' onclick="window.open('."'claimslog.php?entrant=".$rd['EntrantID']."','cert'".')" >');

		$lnk = '<a class="link navLink" style="text-decoration:none;" title="*" href="entrants.php?c=entrant&amp;id='.$rd['EntrantID'];
		if (isset($_REQUEST['ord']))
			$lnk .= '&amp;ord='.$_REQUEST['ord'];
		if (isset($_REQUEST['tab']))
			$lnk .= '&amp;tab='.$_REQUEST['tab'];
		echo('  '.$lnk.'&amp;prev">&Ll;</a> ');
		echo($lnk.'&amp;next">&Gg;</a> ');
	}
echo('</span> ');
	
	echo('<div class="tabs_area" style="display:inherit"><ul id="tabs">');
	echo('<li><a href="#tab_basic">'.$TAGS['BasicDetails'][0].'</a></li>');
	echo('<li><a href="#tab_contact">'.$TAGS['ContactDetails'][0].'</a></li>');
	echo('<li><a href="#tab_odo">'.$TAGS['Odometer'][0].'</a></li>');
	echo('<li><a href="#tab_results">'.$TAGS['RallyResults'][0].'</a></li>');
	if (!$is_new_record)
	{
		echo('<li><a href="#tab_bonuses">'.$TAGS['BonusesLit'][0].'</a></li>');
		echo('<li><a href="#tab_combos">'.$TAGS['CombosLit'][0].'</a></li>');
		echo('<li><a href="#tab_rejects">'.$TAGS['RejectsLit'][0].'</a></li>');
		echo('<li><a href="#tab_scorex">'.$TAGS['ScorexLit'][0].'</a></li>');
		echo('<li><a href="#tab_xtra">'.$TAGS['ExtraData'][0].'</a></li>');
	}
	echo('</ul></div>');
	
	
	
	echo('<fieldset class="tabContent" id="tab_basic"><legend>'.$TAGS['BasicDetails'][0].'</legend>');
	echo('<span class="xlabel" title="'.$TAGS['RiderName'][1].'"><label for="RiderName">'.$TAGS['RiderName'][0].' </label> ');
	$blurJS = "var f=document.getElementById('RiderFirst');if (f.value=='') {var n=document.getElementById('RiderName').value.split(' ');f.value=n[0];}";
	echo('<input autofocus type="text" onchange="enableSaveButton();" onblur="'.$blurJS.'" name="RiderName" id="RiderName" value="'.htmlspecialchars($rd['RiderName']).'"> </span>');
	echo('<span  title="'.$TAGS['RiderFirst'][1].'"><label for="RiderFirst">'.$TAGS['RiderFirst'][0].' </label> ');
	echo('<input type="text" onchange="enableSaveButton();"  name="RiderFirst" id="RiderFirst" value="'.htmlspecialchars($rd['RiderFirst']).'"> </span>');
	
	echo('<span  title="'.$TAGS['RiderIBA'][1].'"><label for="RiderIBA">'.$TAGS['RiderIBA'][0].' </label> ');
	echo('<input type="number"  onchange="enableSaveButton();" name="RiderIBA" id="RiderIBA" value="'.$rd['RiderIBA'].'"> </span>');
	
	echo('<span class="xlabel" title="'.$TAGS['Bike'][1].'"><label for="Bike">'.$TAGS['Bike'][0].' </label> ');
	echo('<input type="text"  onchange="enableSaveButton();" name="Bike" id="Bike" value="'.str_replace('"','&quot;',$rd['Bike']).'"> </span>');
	
	echo('<span title="'.$TAGS['BikeReg'][1].'"><label for="BikeReg">'.$TAGS['BikeReg'][0].' </label> ');
	echo('<input type="text"  onchange="enableSaveButton();" name="BikeReg" id="BikeReg" value="'.$rd['BikeReg'].'"> </span>');
	
	
	
	echo('<span  class="xlabel" title="'.$TAGS['PillionName'][1].'"><label for="PillionName">'.$TAGS['PillionName'][0].' </label> ');
	echo('<input type="text"  onchange="enableSaveButton();" name="PillionName" id="PillionName" value="'.htmlspecialchars($rd['PillionName']).'"> </span>');
	echo('<span  title="'.$TAGS['PillionFirst'][1].'"><label for="PillionFirst">'.$TAGS['PillionFirst'][0].' </label> ');
	echo('<input type="text"  onchange="enableSaveButton();" name="PillionFirst" id="PillionFirst" value="'.htmlspecialchars($rd['PillionFirst']).'"> </span>');
	
	echo('<span  title="'.$TAGS['PillionIBA'][1].'"><label for="PillionIBA">'.$TAGS['PillionIBA'][0].' </label> ');
	echo('<input type="number"  onchange="enableSaveButton();" name="PillionIBA" id="PillionIBA" value="'.$rd['PillionIBA'].'"> </span>');
	
	echo('<span class="vlabel" title="'.$TAGS['Country'][1].'"><label for="Country">'.$TAGS['Country'][0].' </label> ');
	echo('<input type="text"  onchange="enableSaveButton();" name="Country" id="Country" value="'.$rd['Country'].'"> </span>');
	
	echo('<span class="vlabel" title="'.$TAGS['TeamID'][1].'"><label for="TeamID">'.$TAGS['TeamID'][0].' </label> ');
//	echo('<input type="number"  onchange="enableSaveButton();" name="TeamID" id="TeamID" value="'.$rd['TeamID'].'"> </span>');
	echo('<select onchange="enableSaveButton();" name="TeamID" id="TeamID">');
	
	$sql = "SELECT TeamID,BriefDesc FROM teams ORDER BY TeamID";
	$R = $DB->query($sql);
	$ok = false;
	while ($crd = $R->fetchArray()) {
		echo('<option value="'.$crd['TeamID'].'" ');
		if ($crd['TeamID'] == $rd['TeamID']) {
			$ok = true;
			echo(' selected');
		}
		echo('>'.$crd['TeamID'].' : '.$crd['BriefDesc'].'</option>');
	}
	if (!$ok) {
		echo('<option value="'.$rd['TeamID'].'" selected>'.$rd['TeamID'].'</option>');
	}
	echo('</select></span>');

	echo('<span class="vlabel" title="'.$TAGS['Class'][1].'"><label for="Class">'.$TAGS['Class'][0].' </label> ');
//	echo('<input type="number"  onchange="enableSaveButton();" name="Class" id="Class" value="'.$rd['Class'].'">');
	echo('<select  onchange="enableSaveButton();" name="Class" id="Class">');

	$sql = "SELECT Class,BriefDesc FROM classes ORDER BY Class";
	$R = $DB->query($sql);
	$ok = false;
	while ($crd = $R->fetchArray()) {
		echo('<option value="'.$crd['Class'].'" ');
		if ($crd['Class'] == $rd['Class']) {
			$ok = true;
			echo(' selected');
		}
		echo('>'.$crd['Class'].' : '.$crd['BriefDesc'].'</option>');
	}
	if (!$ok) {
		echo('<option value="'.$rd['Class'].'" selected>'.$rd['Class'].'</option>');
	}
	echo('</select>');
	echo('</span>');

	$sql = "SELECT Cohort,FixedStart,StartTime FROM cohorts ORDER BY Cohort";
	$R = $DB->query($sql);
	$ok = false;
	echo('<span class="vlabel" title="'.$TAGS['Cohort'][1].'"> ');
	echo('<label for="Cohort">'.$TAGS['Cohort'][0].'</label> ');
	echo('<select name="Cohort" id="Cohort" onchange="applyCohortChange(this);">');
	while($crd = $R->fetchArray()) {
		echo('<option value="'.$crd['Cohort'].'" ');
		if ($crd['Cohort'] == $rd['Cohort']) {
			$ok = true;
			echo(' selected');
		}
		echo(' data-fixed="'.$crd['FixedStart'].'" data-time="'.$crd['StartTime'].'"');
		echo('>'.$crd['Cohort'].' : ');
		echo($crd['FixedStart']==0 ? $TAGS['cht_FixedStart0'][0] : str_replace("T"," ",$crd['StartTime']).'</option>');
	}
	echo('</select>');
	echo('</span>');

	echo('<span class="vlabel" title="'.$TAGS['BCMethod'][1].'"><label for="BCMethod">'.$TAGS['BCMethod'][0].' </label> ');
	echo('<select name="BCMethod" id="BCMethod" onchange="enableSaveButton();">');
	for ($bcm = $KONSTANTS['BCM_UNKNOWN']; $bcm <= $KONSTANTS['BCM_PAPER']; $bcm++)
	{
		echo('<option value="'.$bcm.'"');
		if ($bcm==$rd['BCMethod'])
			echo(' selected');
		echo('>'.$TAGS['BCMethod'.$bcm][0].'</option>');
	}
	echo('</select>');
	echo('</span>');
	
	echo('</fieldset>');
	
	echo('<fieldset  class="tabContent" id="tab_contact"><legend>'.$TAGS['ContactDetails'][0].'</legend>');
	
	echo('<span class="vlabel" title="'.$TAGS['EntrantPhone'][1].'"><label for="Phone">'.$TAGS['EntrantPhone'][0].' </label> ');
	echo('<input type="tel"  onchange="enableSaveButton();" name="Phone" id="Phone" value="'.$rd['Phone'].'"> </span>');
	
	echo('<span class="vlabel" title="'.$TAGS['EntrantEmail'][1].'"><label for="Email">'.$TAGS['EntrantEmail'][0].' </label> ');
	echo('<input type="email"  onchange="enableSaveButton();" name="Email" id="Email" value="'.$rd['Email'].'"> </span>');
	
	echo('<span class="vlabel" title="'.$TAGS['NoKName'][1].'"><label for="NoKName">'.$TAGS['NoKName'][0].' </label> ');
	echo('<input type="text"  onchange="enableSaveButton();" name="NoKName" id="NoKName" value="'.$rd['NoKName'].'"> </span>');
	
	echo('<span class="vlabel" title="'.$TAGS['NoKRelation'][1].'"><label for="NoKRelation">'.$TAGS['NoKRelation'][0].' </label> ');
	echo('<input type="text"  onchange="enableSaveButton();" name="NoKRelation" id="NoKRelation" value="'.$rd['NoKRelation'].'"> </span>');
	
	echo('<span class="vlabel" title="'.$TAGS['NoKPhone'][1].'"><label for="NoKPhone">'.$TAGS['NoKPhone'][0].' </label> ');
	echo('<input type="tel"  onchange="enableSaveButton();" name="NoKPhone" id="NoKPhone" value="'.$rd['NoKPhone'].'"> </span>');
		
	echo('</fieldset>');
	
	
	echo('<fieldset  class="tabContent" id="tab_odo"><legend>'.$TAGS['Odometer'][0].'</legend>');
	
	$odoF = $DB->query("SELECT OdoCheckMiles FROM rallyparams");
	$odoC = $odoF->fetchArray();
	
	echo('<input type="hidden" name="OdoCheckMiles" id="OdoCheckMiles" value="'.$odoC['OdoCheckMiles'].'">');


	echo('<span class="xlabel" title="'.$TAGS['OdoKms'][1].'">');
	echo('<label for="OdoKms">'.$TAGS['OdoKms'][0].' </label> ');
	echo('<select name="OdoKms" id="OdoKms" onchange="odoAdjust();enableSaveButton();">');
	if ($rd['OdoKms']==$KONSTANTS['OdoCountsKilometres'])
	{
		echo('<option value="'.$KONSTANTS['OdoCountsMiles'].'">'.$TAGS['OdoKmsM'][0].'</option>');
		echo('<option value="'.$KONSTANTS['OdoCountsKilometres'].'" selected >'.$TAGS['OdoKmsK'][0].'</option>');
	}
	else
	{
		echo('<option value="'.$KONSTANTS['OdoCountsMiles'].'" selected >'.$TAGS['OdoKmsM'][0].'</option>');
		echo('<option value="'.$KONSTANTS['OdoCountsKilometres'].'" >'.$TAGS['OdoKmsK'][0].'</option>');
	}
	echo('</select>');
	echo('</span>');





	

	echo('<span  class="xlabel" title="'.$TAGS['OdoCheckStart'][1].' "><label for="OdoCheckStart">'.$TAGS['OdoCheckStart'][0].' </label> ');
	echo('<input onkeypress="digitonly();"  onchange="odoAdjust();enableSaveButton();" type="number" class="bignumber" step="any" min="0" name="OdoCheckStart" id="OdoCheckStart" value="'.$rd['OdoCheckStart'].'"> </span>');
	
	echo('<span  title="'.$TAGS['OdoCheckFinish'][1].' "><label for="OdoCheckFinish">'.$TAGS['OdoCheckFinish'][0].' </label> ');
	echo('<input onkeypress="digitonly();"  onchange="odoAdjust();enableSaveButton();" type="number" class="bignumber" step="any" min="0" name="OdoCheckFinish" id="OdoCheckFinish" value="'.$rd['OdoCheckFinish'].'"> </span>');
	
	echo('<span  title="'.$TAGS['OdoCheckTrip'][1].' "><label for="OdoCheckTrip">'.$TAGS['OdoCheckTrip'][0].' </label> ');
	echo('<input  onchange="odoAdjust(true);enableSaveButton();" type="number" step="any" min="0" name="OdoCheckTrip" id="OdoCheckTrip" value="'.$rd['OdoCheckTrip'].'"> </span>');

	echo('<span  class="xlabel" title="'.$TAGS['OdoScaleFactor'][1].'"><label for="OdoScaleFactor">'.$TAGS['OdoScaleFactor'][0].' </label> ');
	echo('<input type="number" step="any" min="0" name="OdoScaleFactor" id="OdoScaleFactor"  class="bignumber" onchange="enableSaveButton();" value="'.$rd['OdoScaleFactor'].'"> </span>');
	
	echo('<span  class="xlabel" title="'.$TAGS['OdoRallyStart'][1].' "><label for="OdoRallyStart">'.$TAGS['OdoRallyStart'][0].' </label> ');
	echo('<input  onkeypress="digitonly();" onchange="odoAdjust();enableSaveButton();" class="bignumber" type="number" step="any" min="0" name="OdoRallyStart" id="OdoRallyStart" value="'.$rd['OdoRallyStart'].'"> </span>');
	
	echo('<span  title="'.$TAGS['OdoRallyFinish'][1].' "><label for="OdoRallyFinish">'.$TAGS['OdoRallyFinish'][0].' </label> ');
	echo('<input  onkeypress="digitonly();" onchange="odoAdjust();enableSaveButton();" type="number" class="bignumber" step="any" min="0" name="OdoRallyFinish" id="OdoRallyFinish" value="'.$rd['OdoRallyFinish'].'"> </span>');
	
	
	echo('</fieldset>');

	
	echo('<fieldset  class="tabContent" id="tab_results"><legend>'.$TAGS['RallyResults'][0].'</legend>');
	echo('<span  class="xlabel" title="'.$TAGS['EntrantStatus'][1].'"><label for="EntrantStatus">'.$TAGS['EntrantStatus'][0].' </label>');
	echo('<select name="EntrantStatus" id="EntrantStatus" onchange="enableSaveButton();">');
	if ($rd['EntrantStatus']=='')
		$rd['EntrantStatus'] = $KONSTANTS['DefaultEntrantStatus'];
	echo('<option value="'.$KONSTANTS['EntrantDNS'].'" '.($rd['EntrantStatus']==$KONSTANTS['EntrantDNS'] ? ' selected="selected" ' : '').'>'.$TAGS['EntrantDNS'][0].'</option>');
	echo('<option value="'.$KONSTANTS['EntrantOK'].'" '.($rd['EntrantStatus']==$KONSTANTS['EntrantOK'] ? ' selected="selected" ' : '').'>'.$TAGS['EntrantOK'][0].'</option>');
	echo('<option value="'.$KONSTANTS['EntrantFinisher'].'" '.($rd['EntrantStatus']==$KONSTANTS['EntrantFinisher'] ? ' selected="selected" ' : '').'>'.$TAGS['EntrantFinisher'][0].'</option>');
	echo('<option value="'.$KONSTANTS['EntrantDNF'].'" '.($rd['EntrantStatus']==$KONSTANTS['EntrantDNF'] ? ' selected="selected" ' : '').'>'.$TAGS['EntrantDNF'][0].'</option>');
	echo('</select></span>');
	
	$dt = splitDatetime($rd['StartTime']); 

	echo('<span class="vlabel">');
	echo('<label for="StartDate" class="vlabel">'.$TAGS['StartDateE'][0].' </label>');
	echo(' <input type="date" name="StartDate" id="StartDate" onchange="enableSaveButton();" value="'.$dt[0].'" title="'.$TAGS['StartDateE'][1].'"> ');
	echo('<label for="StartTime">'.$TAGS['StartTimeE'][0].' </label>');
	echo(' <input type="time" name="StartTime" id="StartTime" onchange="enableSaveButton();" value="'.$dt[1].'" title="'.$TAGS['StartTimeE'][1].'"> ');
	echo('</span>');

	$dt = splitDatetime($rd['FinishTime']); 

	echo('<span class="vlabel">');
	echo('<label for="FinishDate" class="vlabel">'.$TAGS['FinishDateE'][0].' </label>');
	echo(' <input type="date" name="FinishDate" id="FinishDate" value="'.$dt[0].'" onchange="enableSaveButton();" title="'.$TAGS['FinishDateE'][1].'"> ');
	echo('<label for="FinishTime">'.$TAGS['FinishTimeE'][0].' </label>');
	echo(' <input type="time" name="FinishTime" id="FinishTime" value="'.$dt[1].'" onchange="enableSaveButton();" title="'.$TAGS['FinishTimeE'][1].'"> ');
	echo('</span>');

	echo('<span class="vlabel">');
	echo('<label for="CorrectedMiles" class="vlabel">'.$TAGS['CorrectedMiles'][0].' </label>');
	echo(' <input type="number" name="CorrectedMiles" id="CorrectedMiles" value="'.$rd['CorrectedMiles'].'" onchange="enableSaveButton();" title="'.$TAGS['CorrectedMiles'][1].'"> ');
	echo('<label for="RestMinutes" class="vlabel">'.$TAGS['RestMinutesLit'][0].' </label>');
	echo(' <input type="number" name="RestMinutes" id="RestMinutes" value="'.$rd['RestMinutes'].'" onchange="enableSaveButton();" title="'.$TAGS['RestMinutesLit'][1].'"> ');
	if ($rd['AvgSpeed'] != "" && $rd['CorrectedMiles'] > 0) {
		echo('<label for="AvgSpeed" class="vlabel">'.$TAGS['AvgSpeedLit'][0].' </label>');
		$mph = ($KONSTANTS['BasicDistanceUnit'] != $KONSTANTS['DistanceIsMiles'] ? 'km/h' : 'mph');
		echo(' <span title="'.$TAGS['AvgSpeedLit'][1].'">'.$rd['AvgSpeed'].$mph.'</span>');
	}
	echo('</span>');
	
	echo('<span class="vlabel">');
	echo('<label for="TotalPoints" class="vlabel">'.$TAGS['TotalPoints'][0].' </label>');
	echo(' <input type="number" name="TotalPoints" class="bignumber" id="TotalPoints" onchange="enableSaveButton();" value="'.$rd['TotalPoints'].'" title="'.$TAGS['TotalPoints'][1].'"> ');
	echo('</span>');

	echo('<span class="vlabel">');
	echo('<label for="FinishPosition" class="vlabel">'.$TAGS['FinishPosition'][0].' </label>');
	echo(' <input type="number" name="FinishPosition" id="FinishPosition" onchange="enableSaveButton();" value="'.$rd['FinishPosition'].'" title="'.$TAGS['FinishPosition'][1].'"> ');
	echo('</span>');

	if (false) {
		echo('<span class="xlabel" title="'.$TAGS['ScoringNow'][1].'">');
		echo('<label for="ScoringNow" class="vlabel">'.$TAGS['ScoringNow'][0].' </label>');
		$chk = $rd['ScoringNow'] == $KONSTANTS['BeingScored'] ? ' checked="checked" ' : '';
		echo('<input type="checkbox"'.$chk.' name="ScoringNow" disabled id="ScoringNow" value="'.$KONSTANTS['BeingScored'].'"> ');
		echo('</span>');
	
		echo('<span title="'.$TAGS['ScoredBy'][1].'">');
		echo('<label for="ScoredBy" class="vlabel">'.$TAGS['ScoredBy'][0].' </label>');
		echo('<input type="text" name="ScoredBy" readonly id="ScoredBy" value="'.$rd['ScoredBy'].'"> ');
		echo('</span>');
	}
	
	echo('</fieldset>');
	
	if (!$is_new_record)
	{
		echo('<fieldset  class="tabContent" id="tab_bonuses"><legend>'.$TAGS['BonusesLit'][0].'</legend>');
		showEntrantBonuses($rd['BonusesVisited'],$rd['RejectedClaims']);
		echo('<!-- B --> </fieldset>');
		echo('<fieldset  class="tabContent" id="tab_combos"><legend>'.$TAGS['CombosLit'][0].'</legend>');
		showEntrantCombinations($rd['CombosTicked'],$rd['RejectedClaims']);
		echo('</fieldset>');
		echo('<fieldset  class="tabContent" id="tab_rejects"><legend>'.$TAGS['RejectsLit'][0].'</legend>');
		showEntrantRejectedClaims($rd['RejectedClaims']);
		echo('</fieldset>');
		echo('<fieldset  class="tabContent" id="tab_scorex"><legend>'.$TAGS['ScorexLit'][0].'</legend>');
		showEntrantScorex($rd['ScoreX']);
		echo('</fieldset>');
		echo('<fieldset  class="tabContent" id="tab_xtra"><legend>'.$TAGS['ExtraData'][0].'</legend>');
		showEntrantExtraData($rd['ExtraData']);
		echo('</fieldset>');

	}
	echo('</form>');
		
}






function showNewEntrant()
{
	global $DB, $TAGS, $KONSTANTS;

	$rd = defaultNewEntrant();
	
	
	showEntrantRecord($rd);
}


function prgListEntrants()
/*
 * prg = post/redirect/get
 *
 * Called to get browser to ask for listing after a post
 *
 */
{
	$get = "entrants.php?c=entrants";
	if (isset($_REQUEST['ord']))
		$get .= '&ord='.$_REQUEST['ord'];
	if (isset($_REQUEST['tab']))
		$get .= '&tab='.$_REQUEST['tab'];
	header("Location: ".$get);
	exit;
}


function editTeams() {

	global $DB,$TAGS,$KONSTANTS;


	?>
	<script>
	function addTeam() {
		let tab = document.querySelector('#teams>tbody');
		let len = tab.rows.length - 1;
		console.log('len=='+len);
		let lastnum = 0;
		if (len > 0)
			lastnum = parseInt(tab.rows[len].cells[0].innerHTML);
		lastnum++;
		let def = document.getElementById('TeamDefaults');
		let defs = def.value.split(',');
		console.log(defs);
		let row = tab.insertRow();
		row.setAttribute('data-newrow','1');
		row.innerHTML = document.getElementById('newteamrow').innerHTML;
		console.log(row.innerHTML);
		row.cells[0].innerHTML = lastnum;
		let inp = row.cells[1].firstChild;
		inp.focus();
		if (lastnum <= defs.length) {
			inp.value = def.getAttribute('data-basename')+' '+defs[lastnum - 1];
			inp.select();
			flipSave(inp,true);
		}
	}
	function deleteTeam(obj) {
		console.log('Deleting team');
		let row = obj.parentNode.parentNode;
		let cls = row.cells[0].innerHTML;
		let xhttp = new XMLHttpRequest();
		xhttp.onreadystatechange = function() {
			let ok = new RegExp("\W*ok\W*");
			if (this.readyState == 4 && this.status == 200) {
				console.log('{'+this.responseText+'}');
				if (ok.test(this.responseText)) {
					row.parentNode.removeChild(row);
				}
			}
		};
		xhttp.open("GET", 'entrants.php?c=deleteteam&team='+cls, true);
		xhttp.send();
		
	}
	
	
	function flipSave(obj,enable) {
		console.log('flipSave: '+enable);
		let row = obj.parentNode.parentNode;
		let sav = saveTeamButton(row);
		if (sav.disabled != enable) 
			return;
		console.log('need to flipsave');
		let val = sav.value;
		sav.value = sav.getAttribute('data-value');
		sav.setAttribute('data-value',val);
		sav.disabled = !sav.disabled;
	}
	function saveTeamButton(row) {
		let buttons = row.cells[row.cells.length - 1].childNodes;
		let sav = buttons[2];
		return sav;
	}
	function saveTeam(obj,showCert) {
		console.log('Saving team');
		let row = obj.parentNode.parentNode;
		let cls = row.cells[0].innerHTML;
		let bd = row.cells[1].firstChild.value;
		let xhttp = new XMLHttpRequest();
		xhttp.onreadystatechange = function() {
			let ok = new RegExp("\W*ok\W*");
			if (this.readyState == 4 && this.status == 200) {
				console.log('{'+this.responseText+'}');
				if (ok.test(this.responseText)) {
					flipSave(obj,false);
				}
			}
		};
		xhttp.open("GET", 'entrants.php?c=saveteam&team='+cls+'&bd='+bd, true);
		xhttp.send();
		
	}
	function showTeamMembers(team) {
	let xhttp = new XMLHttpRequest();
	xhttp.onreadystatechange = function() {
		let ok = new RegExp("\W*ok\W*");
		if (this.readyState == 4 && this.status == 200) {
			document.getElementById('teammembers').innerHTML = this.responseText;
		}
	};
	xhttp.open("GET", "entrants.php?c=getteam&team="+team, true);
	xhttp.send();
	
}
function addMember() {
	let tab = document.getElementById('teamrows');
	let len = tab.rows.length;
	//let row = tab.insertRow(-1);
	//row.setAttribute('data-newrow','1');
	//let ncell = row.insertCell(-1);

	let xhttp = new XMLHttpRequest();
	xhttp.onreadystatechange = function() {
		let ok = new RegExp("\W*ok\W*");
		if (this.readyState == 4 && this.status == 200) {
			console.log('addMember: '+this.responseText);
			document.getElementById('memberchoice').innerHTML = this.responseText;
		}
	};
	xhttp.open("GET", "entrants.php?c=addmember", true);
	xhttp.send();

}

function addThisMember(obj) {
	
	let tab = document.querySelector('#teamrows>tbody');
	let entrant = obj.value;
	let team = document.getElementById('currentteam').innerText;
	obj.remove(obj.selectedIndex);
	let row = tab.insertRow();

	let xhttp = new XMLHttpRequest();
	xhttp.onreadystatechange = function() {
		let ok = new RegExp("\W*ok\W*");
		if (this.readyState == 4 && this.status == 200) {
			console.log('addMember: '+this.responseText);
			row.innerHTML = this.responseText;
		}
	};
	xhttp.open("GET", "entrants.php?c=addthismember&entrant="+entrant+"&team="+team, true);
	xhttp.send();

}
function deleteMember(obj) {
	let row = obj.parentNode.parentNode;
	let tab = document.getElementById('teamrows');
	let entrant = row.firstChild.innerText;
	console.log('Removing member ['+entrant+']');
	let xhttp = new XMLHttpRequest();
	xhttp.onreadystatechange = function() {
		let ok = new RegExp("\W*ok\W*");
		if (this.readyState == 4 && this.status == 200) {
			console.log('{'+this.responseText+'}');
			if (ok.test(this.responseText)) {
				tab.deleteRow(row.rowIndex);
			}
		}
	};
	xhttp.open("GET", "entrants.php?c=delmember&entrant="+entrant, true);
	xhttp.send();


}

	</script>
	<?php	
	echo('<input type="hidden" id="TeamDefaults" data-basename="'.$TAGS['TeamDefaults'][0].'" value="'.$TAGS['TeamDefaults'][1].'">');
	echo('<div class="stickytop">'.$TAGS['TeamMaintHead'][1]);
	echo('<br><button value="+" autofocus onclick="addTeam()">+</button>');
	echo('</div>');

	echo('<table id="teams">');
	echo("\r\n".'<thead class="listhead"><tr><th class="rowcol">#</th><th class="rowcol">'.$TAGS['BriefDescLit'][0].'</th>');
	echo('<th></th>');
	echo('</tr>');
	echo('</thead><tbody>');

	echo('<tr id="newteamrow" style="display:none;">');
		echo('<td class="rowcol" title="'.$TAGS['TeamID'][1].'" ></td>');
		
		echo('<td  class="rowcol" title="'.$TAGS['BriefDescLit'][1].'">');
		echo('<input type="text" value="" style="width:14em;" oninput="flipSave(this,true);">');
		echo('</td>');
		
	
		echo('<td class="rowcol">');
		echo('<input type="button" disabled data-value="'.$TAGS['SaveRecord'][0].'" value="'.$TAGS['RecordSaved'][0].'" onclick="saveTeam(this,false);"> ');
		echo('</td>');
	echo('</tr>');
	

	$sql = "SELECT TeamID,BriefDesc FROM teams ORDER BY TeamID";
	$R = $DB->query($sql);

	while ($rd = $R->fetchArray()) {

		echo("\r\n".'<tr>');
		echo('<td onclick="showTeamMembers(this.innerHTML);" class="rowcol ');
		if ($rd['TeamID'] > 0) 
			echo('clickme" title="'.$TAGS['ShowMembers'][1].'" ');
		else
			echo('"');
		echo('>'.$rd['TeamID'].'</td>');
		
		echo('<td  class="rowcol" title="'.$TAGS['BriefDescLit'][1].'">');
		echo('<input type="text" value="'.str_replace('"','&quot;',$rd['BriefDesc']).'" style="width:14em;" oninput="flipSave(this,true);">');
		echo('</td>');

		echo('<td>');

        echo('<input type="button" title="'.$TAGS['ShowMembers'][1].'" value="&Cconint;" ');
		if ($rd['TeamID'] < 1)
			echo(' disabled ');
        echo('onclick="'."showTeamMembers(this.getAttribute('data-team'));".'" data-team="'.$rd['TeamID'].'"> ');

		echo('<input type="button" disabled data-value="'.$TAGS['SaveRecord'][0].'" value="'.$TAGS['RecordSaved'][0].'" onclick="saveTeam(this,false);"> ');
		if ($rd['TeamID'] > 0)
			echo('<input type="button" title="'.$TAGS['DeleteEntryLit'][0].'" value="-" onclick="deleteTeam(this,false);"> ');
		echo('</td></tr>');
	}

	
	echo('</tbody></table>');

	echo('<hr>');
	echo('<div id="teammembers"></div>');
}

function listTeamMembers($team) {

	global $DB,$TAGS,$KONSTANTS;
	
	if ($team < 1) {
		return "";
	}
	$sql = "SELECT BriefDesc FROM teams WHERE TeamID=$team";
	$teamname = getValueFromDB($sql,"BriefDesc",$team);
	$res = '<p>'.$TAGS['TeamMembers'][0].' <span id="currentteam">'.$team.'</span> '.$teamname.'</p>';
	$res .= '<table id="teamrows" data-team="'.$team.'"><tr><th>'.$TAGS['EntrantID'][0].'</th><th>'.$TAGS['RiderName'][0].'</th>';
	$res .= '<th>'.$TAGS['CorrectedMiles'][0].'</th>';
	$res .= '<th>'.$TAGS['TotalPoints'][0].'</th>';
	$res .= '<th></th></tr>';
	$R = $DB->query("SELECT * from entrants WHERE TeamID=$team ORDER BY EntrantID");
	while ($rd = $R->fetchArray()) {
		$res .= '<tr data-newrow="0"><td class="EntrantID">'.$rd['EntrantID'].'</td>';
		$res .= '<td class="RiderName">'.$rd['RiderName'].'</td>';
		$res .= '<td class="CorrectedMiles">'.$rd['CorrectedMiles'].'</td>';
		$res .= '<td class="TotalPoints">'.$rd['TotalPoints'].'</td>';
		$res .= '<td> ';
		$res .= ' <input type="button" data-value="-" value="-" onclick="deleteMember(this);"></td>';
		$res .= '</tr>';
	}
	
	$res .= '</table>';
	
	$res .= '<input type="button"  id="addMemberButton" value="+" onclick="addMember();">';
	$res .= ' <span id="memberchoice"></span>';
	return $res;

}

function ajaxAddMemberSelector() {

	global $DB,$TAGS,$KONSTANTS;
	

	$sql = "SELECT * FROM entrants WHERE TeamID=0 ORDER BY EntrantID";
	$R = $DB->query($sql);
	$res = '<select id="newmember" onchange="addThisMember(this);">';
	$res .= '<option value="0" selected>'.$TAGS['ChooseEntrant'][0].'</option>';
	while ($rd = $R->fetchArray())  {
		$res .= '<option value="'.$rd['EntrantID'].'">'.$rd['EntrantID'].' : '.$rd['RiderName'].'</option>';
	}
	$res .= '</select>';
	echo($res);

}

function ajaxAddThisMember() {

	global $DB, $TAGS, $KONSTANTS;

	if (!isset($_REQUEST['entrant']) || !isset($_REQUEST['team'])) {
		echo("");
		return;
	}
	$sql = "UPDATE entrants SET TeamID=".$_REQUEST['team']." WHERE EntrantID=".$_REQUEST['entrant'];
	$DB->exec($sql);
	$sql = "SELECT * FROM entrants WHERE EntrantID=".$_REQUEST['entrant'];
	$R = $DB->query($sql);
	$rd = $R->fetchArray();
	$res = '<td class="EntrantID">'.$rd['EntrantID'].'</td><td class="RiderName">'.$rd['RiderName'].'</td>';
	$res .= '<td class="CorrectedMiles">'.$rd['CorrectedMiles'].'</td><td class="TotalPoints">'.$rd['TotalPoints'].'</td>';
	$res .= '<td> ';
	$res .= ' <input type="button" data-value="-" value="-" onclick="deleteMember(this);"></td>';

	echo($res);
}
function ajaxDeleteMember() {

	global $DB,$TAGS,$KONSTANTS;

	error_log('adm::'.$_REQUEST['entrant']);
	if (!isset($_REQUEST['entrant'])) {
		echo('');
		return "";
	}

	$sql = "UPDATE entrants SET TeamID=0 WHERE EntrantID=".$_REQUEST['entrant'];
	$DB->exec($sql);
	echo('ok');
}
function ajaxDeleteTeam() {

	global $DB,$TAGS,$KONSTANTS;
	
	error_log('adt::');
	if (!isset($_REQUEST['team']))
		return;
	// Check for usage
	$sql = "SELECT count(*) As Rex FROM entrants WHERE TeamID=".$_REQUEST['team'];
	$rex = getValueFromDB($sql,"Rex",0);
	if ($rex > 0) {
		echo('in use');
		return;
	}

	$sql = "DELETE FROM teams WHERE TeamID=".$_REQUEST['team'];
	error_log($sql);
	echo($DB->exec($sql) && $DB->changes()==1? 'ok' : 'error');
	
}

function ajaxSaveTeam() {

	global $DB,$TAGS,$KONSTANTS;
	
	error_log('ast::');
	foreach (['team','bd'] as $k)
		if (!isset($_REQUEST[$k]))
			return;
	$sql = "INSERT OR REPLACE INTO teams (TeamID,BriefDesc) VALUES(";
	$sql .= $_REQUEST['team'].",'".$DB->escapeString($_REQUEST['bd'])."'";
	$sql .= ")";
	error_log($sql);
	echo($DB->exec($sql) && $DB->changes()==1? 'ok' : 'error');
	
}







if (isset($_REQUEST['c']) && $_REQUEST['c']=='saveteam') {
	ajaxSaveTeam();
	exit;
}

if (isset($_REQUEST['c']) && $_REQUEST['c']=='deleteteam') {
	ajaxDeleteTeam();
	exit;
}

if (isset($_REQUEST['c']) && $_REQUEST['c']=='delmember') {
	ajaxDeleteMember();
	exit;
}
if (isset($_REQUEST['c']) && $_REQUEST['c']=='addmember') {
	ajaxAddMemberSelector();
	exit;
}
if (isset($_REQUEST['c']) && $_REQUEST['c']=='addthismember') {
	ajaxAddThisMember();
	exit;
}

if (isset($_REQUEST['c']) && $_REQUEST['c']=='scorex')
	{
		showAllScorex();
		exit;
	}

	if (isset($_REQUEST['c']) && $_REQUEST['c']=='qlist')
	{
		showFinisherList();
		exit;
	}


if (isset($_REQUEST['savedata']))
{
	saveEntrantRecord();
	prgListEntrants();
}


if (isset($_POST['c']) && $_POST['c']=='kill')
{
	deleteEntrant();
	prgListEntrants();
}
else if (isset($_POST['c']) && $_POST['c']=='rae')
{
	renumberAllEntrants();
	prgListEntrants();
}
else if (isset($_POST['c']) && $_POST['c']=='renumentrant')
{
	renumberEntrant();
	prgListEntrants();
}

if (isset($_REQUEST['c']))
	switch($_REQUEST['c']) {
			
		case 'getteam':
			if (isset($_REQUEST['team'])) {
				echo(listTeamMembers($_REQUEST['team']));
				exit;
			}
			break;
	}


startHtml($TAGS['ttEntrants'][0]);

if (isset($_REQUEST['c']) && $_REQUEST['c']=='showrae')
	showRAE();
else if (isset($_REQUEST['c']) && $_REQUEST['c']=='moveentrant')
	showRenumberEntrant();
else if (isset($_REQUEST['c']) && $_REQUEST['c']=='delentrant')
	showDeleteEntrant();
else if (isset($_REQUEST['c']) && $_REQUEST['c']=='entrant')
	fetchShowEntrant();
else if (isset($_REQUEST['c']) && $_REQUEST['c']=='newentrant')
	showNewEntrant();
else if (isset($_REQUEST['c']) && $_REQUEST['c']=='entrants')
	listEntrants(isset($_REQUEST['ord']) ? $_REQUEST['ord'] : '');
else if (isset($_REQUEST['c']) && $_REQUEST['c']=='teams')
	editTeams();


?>

