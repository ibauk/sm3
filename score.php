<?php
//error_reporting(E_ALL);
//ini_set('display_errors', 1);

/*
 * I B A U K   -   S C O R E M A S T E R
 *
 * I handle the scoring end of things, formatting the scoresheets and recording the results
 * 
 * D E P R E C A T E D     DECEMBER 2021	D E P R E C A T E D
 * 
 * See scorecard.php, scoring.php
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


$HOME_URL = "admin.php";

/*
 *	2.1	Use update_bonuses/combos/specials to update when nothing ticked
 *	2.1	Show PillionName on scoresheet
 *	2.1	Use '-' instead of space between label and bonus checkbox
 *	2.1	Multiple radio groups of specials
 *	2.1 RejectedClaims handling
 *	2.1 Odo check trip reading - used though not stored
 *	2.5	MarkConfirmed
 *
 */

require_once('common.php');



function ajaxclearlock()
// Called using AJAX
{
	global $DB;

	if (!isset($_REQUEST['e']))
		return;
	$sql = "UPDATE entrants SET ScoringNow=0,ScoredBy='' WHERE EntrantID=".$_REQUEST['e'];
	if ($DB->exec($sql))
		echo('ok');
	else
		echo('###');
}

function ajaxsetlock()
// Called using AJAX
{
	global $DB;

	if (!isset($_REQUEST['e']))
		return;
	$sql = "UPDATE entrants SET ScoringNow=1";
	if (isset($_REQUEST['s']))
		$sql .= ",ScoredBy='".$DB->escapeString($_REQUEST['s'])."'";
	$sql .= " WHERE EntrantID=".$_REQUEST['e']." AND ScoringNow=0";
	if ($DB->exec($sql) && $DB->changes()==1)
		echo('ok');
	else
		echo('### '.$sql);
}




function blankFormRecord()
// I return a blank entrant record
{
	global $DB;
	
	$sql = "PRAGMA table_info(entrants)";
	$blankrec = [];
	$R = $DB->query($sql);
	while ($rd = $R->fetchArray()) {
		$blankrec[$rd['name']] = '';
	}
	$blankrec['RiderName']		= '_________________';
	return $blankrec;
}



function defaultFinishTime()
/* This calculates a 'safe' default finishing time to be used until and
 * entrant has her actual time entered. This time should not affect
 * the entrant's score or finisher status.
 */
{
	global $DB, $KONSTANTS, $DBVERSION;

	$notime = ['',''];
	$R = $DB->query("SELECT FinishTime FROM rallyparams");
	if (!$R)
		return $notime;
	$RD = $R->fetchArray();
	$dtx = splitdatetime($RD['FinishTime']);
	$finishTime = $RD['FinishTime'];
	$where = $DBVERSION < 3 ? '' : " WHERE TimeSpec='".$KONSTANTS['TimeSpecDatetime']."' ";
	$sql = "SELECT PenaltyStart FROM timepenalties".$where." ORDER BY PenaltyStart";
	//echo($sql);
	$R = $DB->query($sql);
	if ($R)
	{
		if ($RD = $R->fetchArray())
		{
			$finishTime = $RD['PenaltyStart'];
		}
	}
	// Make it one minute earlier
	if ($finishTime != '')
		try {
			$finishTime = date_sub(DateTime::createFromFormat('Y-m-d\TH:i',$finishTime),new DateInterval('PT1M'))->format('Y-m-d H:i');
		} catch (Exception $e) {
			echo("<br>\r\nERROR TRAPPED FinishTime={".$finishTime."} ");
		}
	
	$res = splitDatetime($finishTime);

	return $res;
}
	

function emitClasses($entrantClass)
{
	global $DB, $KONSTANTS, $DBVERSION;
	
	echo('<input type="hidden" name="Class" id="entrantClass" value="'.$entrantClass.'">');
	$R = $DB->query("SELECT * FROM classes ORDER BY Class");
	while ($rd = $R->fetchArray()) {
		echo('<input type="hidden" class="classSpec" value="'.$rd['Class'].'" ');
		echo('data-BriefDesc="'.htmlspecialchars($rd['BriefDesc']).'" ');
		echo('data-AutoAssign="'.$rd['AutoAssign'].'" data-MinPoints="'.$rd['MinPoints'].'" ');
		echo('data-MinBonuses="'.$rd['MinBonuses'].'" data-BonusesReqd="'.htmlspecialchars($rd['BonusesReqd']).'" ');
		echo('data-LowestRank="'.$rd['LowestRank'].'" >');
	}
		
}

function inviteScorer()
{
	global $DB, $TAGS, $KONSTANTS, $DBVERSION;
	
	$rally = getValueFromDB('SELECT RallyTitle FROM rallyparams','RallyTitle','');
	
	startHtml($TAGS['ttWelcome'][0],'',false);
	echo('<div id="frontpage"><h4>'.$TAGS['OfferScore'][1].'</h4>');
	echo('<form method="post" action="score.php">');
	if (isset($_REQUEST['mc']) && $DBVERSION >= 4)
		echo('<input type="hidden" name="mc" value="mc">');
	echo('<input type="text" autofocus name="ScorerName" value="'.$KONSTANTS['DefaultScorer'].'" onfocus="this.select();">');
	echo('<input type="submit" name="login" value="'.$TAGS['login'][1].'">');
	echo('</form></div>');
	showFooter();
	
}

function loginNewScorer()
{
	global $DB, $TAGS, $KONSTANTS;

	$scorer = ucwords(strtolower($_REQUEST['ScorerName']));
	$_REQUEST['ScorerName'] = $scorer;
	
	prgPicklist();
}


function putScore()
{
	global $DB, $TAGS, $KONSTANTS, $AUTORANK, $DBVERSION;

	//var_dump($_REQUEST);exit;
	
	$sql = "UPDATE entrants SET ScoredBy='".$DB->escapeString($_REQUEST['ScorerName'])."'";
	
	$sql .= ",ScoringNow=0";	// Score's being saved so probably not continuing to be scored
	
	if (isset($_REQUEST['OdoKms']))
		$sql .= ",OdoKms=".intval($_REQUEST['OdoKms']);
	if (isset($_REQUEST['OdoCheckStart']))
		$sql .= ",OdoCheckStart=".floatval($_REQUEST['OdoCheckStart']);
	if (isset($_REQUEST['OdoCheckFinish']))
		$sql .= ",OdoCheckFinish=".floatval($_REQUEST['OdoCheckFinish']);
	if (isset($_REQUEST['OdoScaleFactor']))
		$sql .= ",OdoScaleFactor=".floatval($_REQUEST['OdoScaleFactor']);
	if (isset($_REQUEST['OdoRallyStart']))
		$sql .= ",OdoRallyStart=".floatval($_REQUEST['OdoRallyStart']);
	if (isset($_REQUEST['OdoRallyFinish']))
		$sql .= ",OdoRallyFinish=".floatval($_REQUEST['OdoRallyFinish']);
	if (isset($_REQUEST['CorrectedMiles']))
		$sql .= ",CorrectedMiles=".intval($_REQUEST['CorrectedMiles']);
	if (isset($_REQUEST['FinishTime']))
			$sql .= ",FinishTime='".$DB->escapeString(joinDateTime($_REQUEST['FinishDate'],$_REQUEST['FinishTime']))."'";
		
	if (isset($_REQUEST['BonusesVisited'])) {
		$sql .= ",BonusesVisited='".$_REQUEST['BonusesVisited']."'";	
		$confirmed = true;
		$bv = explode(',',$_REQUEST['BonusesVisited']);
		foreach($bv as $ba)
			$confirmed = $confirmed && ($ba == '' || startsWith($ba,$KONSTANTS['ConfirmedBonusMarker']));
		if ($DBVERSION >= 4)
			$sql .= ",Confirmed=".($confirmed ? 1 : 0);
	}
	elseif (isset($_REQUEST['BonusID']) || isset($_REQUEST['update_bonuses'])) 
		$sql .= ",BonusesVisited='".implode(',',$_REQUEST['BonusID'])."'";
	
	if (isset($_REQUEST['ComboID'])) // || isset($_REQUEST['update_combos']))
		$sql .= ",CombosTicked='".implode(',',$_REQUEST['ComboID'])."'";
	if (isset($_REQUEST['TotalPoints']))
		$sql .= ",TotalPoints=".intval(str_replace(',','',$_REQUEST['TotalPoints']));
	if (isset($_REQUEST['StartTime']))
		$sql .= ",StartTime='".$DB->escapeString(joinDateTime($_REQUEST['StartDate'],$_REQUEST['StartTime']))."'";
	if (isset($_REQUEST['FinishPosition']))
		$sql .= ",FinishPosition=".intval($_REQUEST['FinishPosition']);
	if (isset($_REQUEST['EntrantStatus']))
		$sql .= ",EntrantStatus=".intval($_REQUEST['EntrantStatus']);
	if (isset($_REQUEST['ScoreX']))
		$sql .= ",ScoreX='".$DB->escapeString($_REQUEST['ScoreX'])."'";
	if (isset($_REQUEST['RejectedClaims']))
		$sql .= ",RejectedClaims='".$DB->escapeString($_REQUEST['RejectedClaims'])."'";
	if (isset($_REQUEST['RestMinutes']))
		$sql .= ",RestMinutes=".intval($_REQUEST['RestMinutes']);
	if (isset($_REQUEST['Confirmed']))
		$sql .= ",Confirmed=".intval($_REQUEST['Confirmed']);
	if (isset($_REQUEST['AvgSpeed']) && $DBVERSION >= 5)
		$sql .= ",AvgSpeed='".floatval($_REQUEST['AvgSpeed'])."'";
	$sql .= " WHERE EntrantID=".$_REQUEST['EntrantID'];
	
	//echo('<hr>'.$sql.'<hr>');
	if (!$DB->exec($sql)) {
		dberror();
		exit;
	}
	if (($res = $DB->lastErrorCode()) <> 0) {
		return dberror();
	}
	
	if (putScoreTeam($confirmed)) {
	
		if ($AUTORANK)
			rankEntrants();
		
		updateClass();
		
		return true;
		
	}
	
}

function putScoreTeam($confirmed)
/*
 * I am called to apply the current entrant score to all other members of his team
 * if the CloneTeamMembers flag is set.
 *
 * $_REQUEST['EntrantID'] is the scoring member.
 *
 */
{
	global $DB, $TAGS, $KONSTANTS, $AUTORANK, $DBVERSION;
	
	$sql = "SELECT TeamRanking FROM rallyparams";
	if (getValueFromDB($sql,"TeamRanking",$KONSTANTS['RankTeamsAsIndividuals'])!=$KONSTANTS['RankTeamsCloning'])
		return true;
	$sql = "SELECT TeamID FROM entrants WHERE EntrantID=".$_REQUEST['EntrantID'];
	$team = getValueFromDB($sql,"TeamID","0");
	if ($team==0)
		return true;
	

	$sql = "UPDATE entrants SET ScoredBy='".$DB->escapeString($_REQUEST['ScorerName'])."'";
	
	$sql .= ",ScoringNow=0";	// Score's being saved so probably not continuing to be scored
	
		$sql .= ",CorrectedMiles=".intval($_REQUEST['CorrectedMiles']);
	if (isset($_REQUEST['FinishTime']))
			$sql .= ",FinishTime='".$DB->escapeString(joinDateTime($_REQUEST['FinishDate'],$_REQUEST['FinishTime']))."'";
		
	if (isset($_REQUEST['BonusesVisited'])) {
		$sql .= ",BonusesVisited='".$_REQUEST['BonusesVisited']."'";	
		if ($DBVERSION >= 4)
			$sql .= ",Confirmed=".($confirmed ? 1 : 0);
	}
	elseif (isset($_REQUEST['BonusID']) || isset($_REQUEST['update_bonuses'])) 
		$sql .= ",BonusesVisited='".implode(',',$_REQUEST['BonusID'])."'";
	
	if (isset($_REQUEST['ComboID']) || isset($_REQUEST['update_combos']))
		$sql .= ",CombosTicked='".implode(',',$_REQUEST['ComboID'])."'";
	if (isset($_REQUEST['TotalPoints']))
		$sql .= ",TotalPoints=".intval(str_replace(',','',$_REQUEST['TotalPoints']));
	if (isset($_REQUEST['StartTime']))
		$sql .= ",StartTime='".$DB->escapeString(joinDateTime($_REQUEST['StartDate'],$_REQUEST['StartTime']))."'";
	if (isset($_REQUEST['FinishPosition']))
		$sql .= ",FinishPosition=".intval($_REQUEST['FinishPosition']);
	if (isset($_REQUEST['EntrantStatus']))
		$sql .= ",EntrantStatus=".intval($_REQUEST['EntrantStatus']);
	if (isset($_REQUEST['ScoreX']))
		$sql .= ",ScoreX='".$DB->escapeString($_REQUEST['ScoreX'])."'";
	if (isset($_REQUEST['RejectedClaims']))
		$sql .= ",RejectedClaims='".$DB->escapeString($_REQUEST['RejectedClaims'])."'";
	if (isset($_REQUEST['RestMinutes']))
		$sql .= ",RestMinutes=".intval($_REQUEST['RestMinutes']);
	if (isset($_REQUEST['Confirmed']))
		$sql .= ",Confirmed=".intval($_REQUEST['Confirmed']);
	if (isset($_REQUEST['AvgSpeed']) && $DBVERSION >= 5)
		$sql .= ",AvgSpeed='".floatval($_REQUEST['AvgSpeed'])."'";
	$sql .= " WHERE TeamID=$team AND EntrantID<>".$_REQUEST['EntrantID'];

	echo('<hr>'.$sql.'<hr>');
	if (!$DB->exec($sql)) {
		dberror();
		exit;
	}
	if (($res = $DB->lastErrorCode()) <> 0) {
		echo($DB->lastErrorCode().' '.$DB->lastErrorMsg().' ');
		return dberror();
	}
	echo(' well '.$AUTORANK.' ');
	
	return true;
}


function scoreEntrant($showBlankForm = FALSE,$postRallyForm = TRUE)
/*
 * showBlankForm 	Produce template for ticksheets only
 * postRallyForm	Ticksheet used to capture score at check-in
 *
 * THIS IS ONLY USED FOR PRINTED SCORESHEETS. All interactive scorecards
 * are contained in scorecard.php
 */
{
	global $DB, $TAGS, $KONSTANTS, $DBVERSION;

	updateScoringFlags((isset($_REQUEST['EntrantID']) ? $_REQUEST['EntrantID'] : 0));

	if (isset($_REQUEST['prf']))
		$postRallyForm = $_REQUEST['prf'] == '1';
	
	if (!$showBlankForm) 
	{
		$sql = 'SELECT * FROM entrants';
		$sql .= ' WHERE EntrantID='.$_REQUEST['EntrantID'];
		$R = $DB->query($sql);
		$rd = $R->fetchArray();
	
		if (!$rd)
		{
			showPicklist('EntrantID');
			exit;
		}
		$ScorerName = (isset($_REQUEST['ScorerName']) ? $_REQUEST['ScorerName'] : '');
		$scorex_class = 'showscorex';
	}
	else
	{
		$ScorerName = '__________';
		$scorex_class = 'hidescorex';
	}
	
	$otherinfo = ''; // ($ScorerName=='' ? '' : $TAGS['Scorer'][0].': '.$ScorerName);
	if ($showBlankForm && !$postRallyForm)
			$otherinfo = '';
	startHtml($TAGS['ttScoring'][0],$otherinfo,false);

	eval("\$evs = ".$TAGS['EntrantStatusV'][0]);
	
	$R = $DB->query('SELECT * FROM rallyparams');
	$rd = $R->fetchArray();
	
	$dts = splitDatetime($rd['StartTime']);
	$dtf = splitDatetime($rd['FinishTime']);
	$OneDayRally = $dts[0] == $dtf[0];
	$rallyTimeDNF = $rd['FinishTime'];
	$rallyTimeStart = $rd['StartTime'];
	
	$certhours = ($DBVERSION >= 4) ? $rd['MaxHours'] : $rd['CertificateHours']; // Field was renamed at DBv4
	
	$axisnames = [];

	// Flag this page as a scoresheet so the javascript knows what to do.
	echo('<input type="hidden" name="scoresheetpage" id="scoresheetpage" value="scoresheetpage">');

	for ($i = 1; $i <= $KONSTANTS['NUMBER_OF_COMPOUND_AXES']; $i++)
	{
		echo('<input type="hidden" name="AxisScores[]" id="Axis'.$i.'Score" value="'.htmlspecialchars($rd['Cat'.$i.'Label']).'" data-bonuses="0" data-points="0" data-mults="0" data-axis="'.$i.'">');
		$axisnames[$i] = $rd['Cat'.$i.'Label'];
	}
	
	$rejectreasons = explode("\n",$rd['RejectReasons']);
	foreach($rejectreasons as $rrline)
	{
		$rr = explode('=',$rrline);
		if (count($rr)==2 && intval($rr[0])>0 && intval($rr[0])<10)
			echo('<input type="hidden" name="RejectReason" data-code="'.$rr[0].'" value="'.$rr[1].'">');
	}
	$RallyFinishTime = defaultFinishTime();
	$RallyStartTime = $dts;
	
	$ScoringMethod = $rd['ScoringMethod'];
	if ($ScoringMethod == $KONSTANTS['AutoScoring'])
		$ScoringMethod = chooseScoringMethod();
	$ShowMults = $rd['ShowMultipliers'];
	if ($ShowMults == $KONSTANTS['AutoShowMults'])
		$ShowMults = chooseShowMults($ScoringMethod);
	
	
	// This contains the bonus rejection menu
	echo("\r\n");
	echo('<div id="rcmenu" style="display:none;">');
	echo('<ul>');
	echo('<li><a href="#">'.$TAGS['RejectReason0'][0].'</a></li>');
	
	foreach($rejectreasons as $rrline)
	{
		$rr = explode('=',$rrline);
		echo('<li data-code="'.$rr[0].'"><a href="#'.$rr[0].'">'.$rrline.'</a></li>');
	}
	echo('</ul>');
	echo('</div>');
	echo("\r\n");
	// End of bonus rejection menu
	
	// =================================================================================== ScoreSheet
	echo('<div id="ScoreSheet">'."\r\n");
	echo("\r\n");
	echo('<form method="post" action="score.php" onsubmit="submitScore();">');
	echo('<input type="hidden" id="ScorerName" name="ScorerName" value="'.htmlspecialchars((isset($_REQUEST['ScorerName']) ? $_REQUEST['ScorerName'] : '')).'">');
	echo('<input type="hidden" id="MinPoints" value="'.$rd['MinPoints'].'">');
	echo('<input type="hidden" id="MinMiles" value="'.$rd['MinMiles'].'">');
	echo('<input type="hidden" id="PenaltyMaxMiles" value="'.$rd['PenaltyMaxMiles'].'">');
	echo('<input type="hidden" id="MaxMilesMethod" value="'.$rd['MaxMilesMethod'].'">');
	echo('<input type="hidden" id="MaxMilesPoints" value="'.$rd['MaxMilesPoints'].'">');
	echo('<input type="hidden" id="PenaltyMilesDNF" value="'.$rd['PenaltyMilesDNF'].'">');
	echo('<input type="hidden" id="ScoringMethod" value="'.$ScoringMethod.'">');
	echo('<input type="hidden" id="ShowMults" value="'.$ShowMults.'">');
	echo('<input type="hidden" name="ScoreX" id="scorexstore" value=""/>');
	echo('<input type="hidden" id="bduText" value="'.($KONSTANTS['BasicDistanceUnit']==$KONSTANTS['DistanceIsMiles'] ? $TAGS['OdoKmsM'][0] : $TAGS['OdoKmsK'][0]).'">');
	
	//emitClasses($rd['Class']);
	
	// mc offers confirmation
	if (isset($_REQUEST['mc']) && $DBVERSION >= 4)
		echo('<input type="hidden" name="mc" value="mc">');
	
	//					Time penalties
	//echo(" 1 ");
	$TimePenaltyTime =($DBVERSION < 3 ? '0 as TimeSpec' : 'TimeSpec');
		
	$R = $DB->query('SELECT rowid AS id,'.$TimePenaltyTime.',PenaltyStart,PenaltyFinish,PenaltyMethod,PenaltyFactor FROM timepenalties ORDER BY PenaltyStart,PenaltyFinish');
	while ($rd = $R->fetchArray())
		echo('<input type="hidden" name="TimePenalty[]" data-spec="'.$rd['TimeSpec'].'" data-start="'.$rd['PenaltyStart'].'" data-end="'.$rd['PenaltyFinish'].'" data-factor="'.$rd['PenaltyFactor'].'" data-method="'.$rd['PenaltyMethod'].'">');
	
	
	// 					Speed penalties
	//echo(" 2 ");
	if ($DBVERSION >= 4)
	{
		$R = $DB->query('SELECT Basis,MinSpeed,PenaltyType,PenaltyPoints FROM speedpenalties ORDER BY MinSpeed DESC');
		while ($rd = $R->fetchArray())
		{
			echo('<input type="hidden" name="SpeedPenalty[]" data-Basis="'.$rd['Basis'].'" data-MinSpeed="'.$rd['MinSpeed'].'" ');
			echo('data-PenaltyType="'.$rd['PenaltyType'].'" value="'.$rd['PenaltyPoints'].'">');
		}
	}
	
	//					Compound rules
	
	$R = $DB->query('SELECT rowid AS id,Axis,Cat,NMethod,ModBonus,NMin,PointsMults,NPower,Ruletype FROM catcompound ORDER BY Axis,NMin DESC');
	while ($rd = $R->fetchArray())
		echo('<input type="hidden" name="catcompound[]" data-axis="'.$rd['Axis'].'" data-cat="'.$rd['Cat'].'" data-method="'.$rd['NMethod'].'" data-mb="'.$rd['ModBonus'].'" data-min="'.$rd['NMin'].'" data-pm="'.$rd['PointsMults'].'" data-power="'.$rd['NPower'].'" data-reqd="'.$rd['Ruletype'].'">');
	
	
	//					Entrant record
	//echo(" 3 ");
	
	if ($showBlankForm) {
		$_REQUEST['EntrantID']	= '';
		$rd = blankFormRecord();
	} else {
		$sql = 'SELECT * FROM entrants';
		$sql .= ' WHERE EntrantID='.$_REQUEST['EntrantID'];
		$R = $DB->query($sql);
		$rd = $R->fetchArray();
	}

	if ($rd['StartTime'] != '') {
		if (is_null($rd['StartTime']))
			$starttime = date("Y-m-d\TH:i");
		else
			$starttime = $rd['StartTime'];
		error_log($starttime);
		$mtDNF = DateTime::createFromFormat('Y\-m\-d\TH\:i',$starttime);
		try {
			$mtDNF = date_add($mtDNF,new DateInterval("PT".$certhours."H"));
		} catch(Exception $e) {
			echo('omg! '.$e->getMessage());
		}
		$myTimeDNF = joinDateTime(date_format($mtDNF,'Y-m-d'),date_format($mtDNF,'H:i'));
		if ($rallyTimeDNF < $myTimeDNF)
			$myTimeDNF = $rallyTimeDNF;
	} else {
		$starttime = '';
		$mtDNF = '';
		$myTimeDNF = '';
	}
		
		
	echo('<input type="hidden" id="MaxHours" value="'.$certhours.'">');
	echo('<input type="hidden" id="RallyTimeDNF" value="'.$rallyTimeDNF.'">');
	echo('<input type="hidden" id="RallyTimeStart" value="'.$rallyTimeStart.'">');
	echo('<input type="hidden" id="FinishTimeDNF" value="'.$myTimeDNF.'">');
	echo('<input type="hidden" name="BonusesVisited" id="BonusesVisited" value="'.$rd['BonusesVisited'].'">');
	
	echo('<input type="hidden" id="OdoScaleFactor" name="OdoScaleFactor" value="'.$rd['OdoScaleFactor'].'">');
	echo('<input type="hidden" id="EntrantID" name="EntrantID" value="'.$_REQUEST['EntrantID'].'">');
	echo('<input type="hidden" name="RejectedClaims" id="RejectedClaims" value="'.$rd['RejectedClaims'].'">');
	$rm = ($DBVERSION >= 4 ? $rd['RestMinutes'] : 0);
	echo('<input type="hidden" name="RestMinutes" id="RestMinutes" value="'.$rm.'">');
	$rm = ($rd['Confirmed'] == 1 ? 1 : 0);
	//echo('<input type="hidden" name="Confirmed" id="Confirmed" value="'.$rm.'">');
	
	
	// ======================================================================================== ScoreHeader
	echo("\r\n");
	echo('<div id="ScoreHeader"');
	if ($ScoringMethod == $KONSTANTS['ManualScoring'])
		echo(' class="manualscoring" ');
	echo('>');
	echo("\r\n");
	echo('<label style="font-weight: bold;" id="RiderID">'.$TAGS['EntrantID'][0].' '.$_REQUEST['EntrantID'].' - '.htmlspecialchars($rd['RiderName']));
	if ($rd['PillionName'] <> '')
		echo(' &amp; '.htmlspecialchars($rd['PillionName']));
	echo('</label> ');
	$dt1 = splitDatetime($rd['StartTime']);
	if ($dt1[0] == '')
		$dt1 = $RallyStartTime;
	$hideclass = ' class="hide" ';
	$dt = splitDatetime($rd['FinishTime']);
	if ($dt[0] == '')
		$dt = $RallyStartTime; //$RallyFinishTime;
	if ($OneDayRally)
		$hideclass = ' class="hide" ';
	else
		$hideclass = '';
	$datetype = 'date';
	$timetype = 'time';
	$numbertype = 'number';
	$sbfro = '';
	if ($showBlankForm)
	{
		$dt[0] = '__________';
		$dt[1] = '______';
		$dt1[0] = $dt[0];
		$dt1[1] = $dt[1];
		$datetype = 'text';
		$timetype = 'text';
		$numbertype = 'text';
		$sbfro = ' readonly="readonly" ';
		$codof = '______';
		$codof1 = $codof;
		$cmiles = '_____';
	}
	else
	{
		$codof = $rd['OdoRallyFinish'];
		$codof1 = $rd['OdoRallyStart'];
		$cmiles = intval($rd['CorrectedMiles']);
	}
	echo("\r\n");
	echo('<span '.$hideclass.'title="'.$TAGS['StartDateE'][1].'"><label for="StartDate">'.$TAGS['StartDateE'][0].' </label> ');
	echo('<input type="'.$datetype.'" id="StartDate" name="StartDate" value="'.$dt1[0].'"   />');
	echo('</span> ');
	echo('<span  title="'.$TAGS['StartTimeE'][1].'"><label for="StartTime">'.$TAGS['StartTimeE'][0].' </label> ');
	echo('<input '.$sbfro.'type="'.$timetype.'" id="StartTime" name="StartTime" value="'.$dt1[1].'"   />');
	echo('</span>');
	echo("\r\n");
	echo('<span '.$hideclass.'title="'.$TAGS['FinishDateE'][1].'"><label for="FinishDate">'.$TAGS['FinishDateE'][0].' </label> ');
	echo('<input '.$sbfro.' type="'.$datetype.'" id="FinishDate" name="FinishDate" value="'.$dt[0].'"  />');
	echo('</span> ');
	echo('<span id="Timings" title="'.$TAGS['FinishTimeE'][1].'"><label for="FinishTime">'.$TAGS['FinishTimeE'][0].' </label> ');
	echo('<input '.$sbfro.'type="'.$timetype.'" id="FinishTime" name="FinishTime" value="'.$dt[1].'"  />');
	//if ($ScoringMethod == $KONSTANTS['ManualScoring'])
	//	echo(' <input type="button" value="'.$TAGS['nowlit'][0].'" onclick="setSplitNow(\'Finish\');" />');	
	echo('</span> ');
	
	//echo('<input type="hidden" id="OdoRallyStart" name="OdoRallyStart" value="0'.$rd['OdoRallyStart'].'">');

	echo("\r\n");
	echo('<span title="'.$TAGS['OdoRallyStart'][1].'"><label for="OdoRallyStart">'.$TAGS['OdoRallyStart'][0].' </label> ');
	echo('<input '.$sbfro.' class="bignumber" type="'.$numbertype.'" name="OdoRallyStart" id="OdoRallyStart" value="'.$codof1.'"  /> ');
	echo('</span>');
	echo("\r\n");
	echo('<span title="'.$TAGS['OdoRallyFinish'][1].'"><label for="OdoRallyFinish">'.$TAGS['OdoRallyFinish'][0].' </label> ');
	echo('<input '.$sbfro.' class="bignumber" type="'.$numbertype.'" name="OdoRallyFinish" id="OdoRallyFinish" value="'.$codof.'" /> ');
	echo('</span>');
	
	if (!$showBlankForm)
	{
		echo('<span title="'.$TAGS['OdoKms'][1].'"><label for="OdoKms">'.$TAGS['OdoKms'][0].' </label> ');
		echo('<select name="OdoKms" id="OdoKms">');
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
		echo('<span title="'.$TAGS['CorrectedMiles'][1].'"><label for="CorrectedMiles">'.$TAGS['CorrectedMiles'][0].' </label> ');
		echo('<input '.$sbfro.' type="'.$numbertype.'"  name="CorrectedMiles" id="CorrectedMiles" value="'.$cmiles.'"/> ');
		echo('</span> ');
		
		echo('<span class="explain" title="'.$TAGS['CalculatedAvgSpeed'][1].'"><label for="CalculatedAvgSpeed" >'.$TAGS['CalculatedAvgSpeed'][0].' </label> ');
		echo('<input tabindex="-1" readonly type="text" name="AvgSpeed" id="CalculatedAvgSpeed" value="'.($DBVERSION >= 5 ? $rd['AvgSpeed'] : '').'">');
		echo('</span>');
	}
	
		
	if ($ScoringMethod <> $KONSTANTS['ManualScoring'])
		$ro = 'readonly="readonly" ';
	else
		$ro = '';
	if ($showBlankForm)
	{
		$ctotal = '_____';
		$tp_id = 'tpoints'; // So it's not updated
	}
	else
	{
		$ctotal = $rd['TotalPoints'];
		$tp_id = 'TotalPoints';
	}
	// call to sxtoggle removed
	echo("\r\n".'<span><label  class="clickme" title="'.$TAGS['ToggleScoreX'][1].'" for="'.$tp_id.'">'.$TAGS['TotalPoints'][0].' </label> ');
	echo('<input  class="clickme"  title="'.$TAGS['TotalPoints'][1].'" type="'.($ro != ''? 'text' : 'number').'" '.$ro.' name="TotalPoints" id="'.$tp_id.'" value="'.$ctotal.'" /> ');
	echo('</span> ');
	
	if (!$showBlankForm)
	{
	
	// echo(' <span class="clickme noprint" onclick="sxtoggle();"> ? </span>');
	if ($ScoringMethod == $KONSTANTS['CompoundScoring'])
	{
		if ($ShowMults == $KONSTANTS['SuppressMults'])
			$style = ' style="display:none;" ';
		else
			$style = '';
		echo('<span '.$style.' title="'.$TAGS['TotalMults'][1].'"><label for="TotalMults">'.$TAGS['TotalMults'][0].'</label> ');
		echo(' <input type="text" readonly="readonly" title="'.$TAGS['TotalMults'][1].'" id="TotalMults" value="0" /> ');
		echo('</span>'."\r\n");
	}
	echo('<br /><span title="'.$TAGS['EntrantStatus'][1].'"><label for="EntrantStatus">'.$TAGS['EntrantStatus'][0].' </label> ');
	echo('<select name="EntrantStatus" id="EntrantStatus" onchange="sfs(this.value,\'\');enableSaveButton()">'); // Don't recalculate if status changed manually
	if ($rd['EntrantStatus']=='')
		$rd['EntrantStatus'] = $KONSTANTS['DefaultEntrantStatus'];
	echo('<option value="'.$KONSTANTS['EntrantDNS'].'" '.($rd['EntrantStatus']==$KONSTANTS['EntrantDNS'] ? ' selected="selected" ' : '').'>'.$TAGS['EntrantDNS'][0].'</option>');
	echo('<option value="'.$KONSTANTS['EntrantOK'].'" '.($rd['EntrantStatus']==$KONSTANTS['EntrantOK'] ? ' selected="selected" ' : '').'>'.$TAGS['EntrantOK'][0].'</option>');
	echo('<option value="'.$KONSTANTS['EntrantFinisher'].'" '.($rd['EntrantStatus']==$KONSTANTS['EntrantFinisher'] ? ' selected="selected" ' : '').'>'.$TAGS['EntrantFinisher'][0].'</option>');
	echo('<option value="'.$KONSTANTS['EntrantDNF'].'" '.($rd['EntrantStatus']==$KONSTANTS['EntrantDNF'] ? ' selected="selected" ' : '').'>'.$TAGS['EntrantDNF'][0].'</option>');
	echo('</select>');
	echo('</span> ');
	
	echo('<input type="submit" class="noprint" title="'.$TAGS['SaveScore'][1].'" id="savescorebutton" data-triggered="0" ');
	echo('onclick="'."this.setAttribute('data-triggered','1');".'"');
	if ($rd['Confirmed'] == $KONSTANTS['ScorecardIsDirty'])
		echo(' value="'.$TAGS['SaveScore'][0].'"');
	else
		echo(' disabled value="'.$TAGS['ScoreSaved'][0].'"');
	echo(' accesskey="S" name="savescore" data-altvalue="'.$TAGS['SaveScore'][0].'"  /> ');
	
	//echo('<input type="submit" id="backtolistbutton" name="showpicklist" data-altvalue="'.$TAGS['ShowEntrants'][0].'" value="'.$TAGS['ShowEntrants'][0].'"> ');
	
	if (isset($_REQUEST['mc']) && $DBVERSION >= 4){
		echo('<span title="'.$TAGS['MarkConfirmed'][1].'"><label for="MarkConfirmed">'.$TAGS['MarkConfirmed'][0].'</label> ');
		echo('<input type="checkbox" name="MarkConfirmed" id="MarkConfirmed" value="1" ');
		echo(' onchange="if (this.checked) markAsConfirmed();"');
		if ($rd['Confirmed'] != 0)
			echo(' checked');
		echo('> </span>');
	}
	
	} // End !$showBlank Form
	
	
	
	
	
	echo("\r\n");
	echo('</div>');		// =======================================  End of ScoreHeader
	echo("\r\n");
	
	
	if ($ScoringMethod <> $KONSTANTS['ManualScoring'])
	{
		if ($showBlankForm && $postRallyForm)
		{
			echo('<ul id="BlankFormRejectReasons">');
			//print_r($rejectreasons);
			foreach($rejectreasons as $rrline)
			{
				echo('<li>'.$rrline.'</li>');
			}
			echo('</ul>');
		}
		
		
		echo('<fieldset id="tab_bonuses"><legend>'.$TAGS['BonusesLit'][0].'</legend>');
		showBonuses($rd['BonusesVisited'],$showBlankForm,$postRallyForm);
		echo('</fieldset><!-- showBonuses -->'."\r\n");
		if (!$showBlankForm && getValueFromDB('SELECT count(*) as rex FROM combinations','rex',0) > 0)
		{
			echo('<fieldset id="tab_combos"><legend>'.$TAGS['CombosLit'][0].'</legend>');
			showCombinations($rd['CombosTicked']);
			echo('</fieldset><!-- showCombinations -->'."\r\n");
		}
	}
	
	echo("\r\n");
	echo('</form>');
	echo("\r\n");
	echo('</div>'."\r\n"); // ========================================= End ScoreSheet


	if ($ScoringMethod == $KONSTANTS['CompoundScoring'] && !$showBlankForm)
	{
		echo('<div id="cat_results">');
		for ($i = 1; $i <= $KONSTANTS['NUMBER_OF_COMPOUND_AXES']; $i++)
			if ($axisnames[$i] <> '')
				showCategory($i,$axisnames[$i]);
		echo('</div>');
	}
	echo('<div id="scorex" oncontextmenu="showBonusOrder()" title="'.$TAGS['ScorexHints'][0].'" class="'.$scorex_class.' scorex" data-show="0" ondblclick="sxprint();" >'.$rd['ScoreX'].'</div>');
	echo('<div id="ddarea" class="hide"><p> </p></div>');	// Used for drag/drop operations
	echo('</body></html>');
}

function showBonuses($bonusesTicked,$showBlankForm,$postRallyForm)
{
	global $DB, $TAGS, $KONSTANTS;

	$BA = explode(',',','.$bonusesTicked); // The leading comma means that the first element is index 1 not 0
	$BP = [];
	$R = $DB->query('SELECT * FROM bonuses ORDER BY BonusID');
	while ($rd = $R->fetchArray())
	{
		$bk = $rd['BonusID'];
		$bd = $rd['BriefDesc'];
		$tick = '';
		$chk = array_search($rd['BonusID'], $BA) ? ' checked="checked" ' : '';  // Depends on first item having index 1 not 0
		if ($chk=='') {
			$chk = array_search($KONSTANTS['ConfirmedBonusMarker'].$rd['BonusID'], $BA) ? ' checked="checked" ' : '';  // Depends on first item having index 1 not 0
			$tick = $chk=='' ? '' :  $KONSTANTS['ConfirmedBonusTick'];
		}
		$spncls = ($chk <> '') ? ' checked' : ' unchecked';
		if ($rd['Compulsory']<>0)
			$spncls .= ' compulsory';
		echo('<span class="showbonus'.$spncls.'"');
		if (!$showBlankForm)
			echo(' oncontextmenu="showPopup(this);"');
		
		echo(' data-title="'.$bd.'"');	// HTML intact, use for scorex
		
		echo(' title="'.strip_tags($bd).' [ '.$rd['Points'].' ]">');
		echo('<label for="'.$KONSTANTS['ORDINARY_BONUS_PREFIX'].$bk.'">'.$bk.'</label>');
		echo('<input type="checkbox"'.$chk.' name="BonusID[]" id="'.$KONSTANTS['ORDINARY_BONUS_PREFIX'].$bk.'" value="'.$bk.'" onchange="tickBonus(this)"');
		echo(' data-points="'.$rd['Points'].'" ');
		for ($c = 1; $c <= $KONSTANTS['NUMBER_OF_COMPOUND_AXES']; $c++)
			echo('data-cat'.$c.'="'.intval($rd['Cat'.$c]).'" ');
		echo('data-reqd="'.intval($rd['Compulsory']).'" /> ');
		if ($showBlankForm && !$postRallyForm)
		{
			echo(' ____ ____ ');
		}
		echo('</span>'.$tick);
		echo("\r\n");
		
		
		
	}
	echo('<input type="hidden" name="update_bonuses" value="1" />'); // Flag in case of no bonuses ticked = empty array
	echo("\r\n");
}


function showCategory($axis,$axisdesc)
{
	global $DB, $TAGS, $KONSTANTS;

	$R = $DB->query("SELECT * FROM categories WHERE Axis=$axis ORDER BY Cat");
	echo("\r\n");
	echo('<table id="cat'.$axis.'">');
	echo("\r\n");
	if ($axisdesc <> '')
		echo('<caption>'.$axisdesc.'</caption>');
	while ($rd = $R->fetchArray())
		echo('<tr><td class="catdesc">'.$rd['BriefDesc'].'</td><td class="scoredetail" id="cat'.$axis.'_'.$rd['Cat'].'"></td></tr>');
	echo("\r\n");
	echo('</table>');
	echo("\r\n");
}



function showCombinations($Combos)
{
	global $DB, $TAGS, $KONSTANTS, $DBVERSION;

	$BA = explode(',',','.$Combos); // The leading comma means that the first element is index 1 not 0
	
	$R = $DB->query('SELECT * FROM combinations ORDER BY ComboID');
	while ($rd = $R->fetchArray())
	{
		if ($DBVERSION < 3)
			$rd['MinimumTicks'] = 0;
		
		$bk = $rd['ComboID'];
		$bd = $rd['BriefDesc'];
		$chk = array_search($rd['ComboID'], $BA) ? ' checked="checked" ' : '';  // Depends on first item having index 1 not 0
		if ($chk=='')
			$chk = array_search($KONSTANTS['ConfirmedBonusMarker'].$rd['ComboID'], $BA) ? ' checked="checked" ' : '';  // Depends on first item having index 1 not 0
		$spncls = ($chk <> '') ? ' checked' : ' unchecked';
		echo('<span class="combo '.$spncls.'" title="'.htmlspecialchars($bk).' [ ');
		if ($rd['ScoreMethod']==$KONSTANTS['ComboScoreMethodMults'])
			echo('x');
		echo($rd['ScorePoints']);
		echo(' ]" oncontextmenu="showPopup(this);">');
		echo('<label for="'.$KONSTANTS['COMBO_BONUS_PREFIX'].$bk.'">'.htmlspecialchars($bd).' </label>');
		echo('<input type="checkbox"'.$chk.' name="ComboID[]" disabled="disabled" id="'.$KONSTANTS['COMBO_BONUS_PREFIX'].$bk.'" value="'.$bk.'"');
		echo(' data-method="'.$rd['ScoreMethod'].'" data-points="'.$rd['ScorePoints'].'" data-bonuses="'.$rd['Bonuses'].'" ');
		if ($DBVERSION >= 3)
			for ($c = 1; $c <= $KONSTANTS['NUMBER_OF_COMPOUND_AXES']; $c++)
				echo(' data-cat'.$c.'="'.intval($rd['Cat'.$c]).'" ');
			
		echo(' data-reqd="'.$rd['Compulsory'].'"');
		echo(' data-minticks="'.$rd['MinimumTicks'].'"');
		echo(' data-pointsarray="'.$rd['ScorePoints'].'"'); // Combos might have different values depending on MinimumTicks
		echo('/> ');
		echo(' &nbsp;&nbsp;</span> ');
		echo("\r\n");
	}
	echo('<input type="hidden" name="update_combos" value="1" />'."\r\n");
}




function showPicklist($ord)
{
	global $DB, $TAGS, $KONSTANTS, $HOME_URL, $DBVERSION;
	

	$minEntrant = getValueFromDB("SELECT min(EntrantID) as MaxID FROM entrants","MaxID",1);
	$maxEntrant = getValueFromDB("SELECT max(EntrantID) as MaxID FROM entrants","MaxID",$minEntrant);

	$R = $DB->query('SELECT * FROM entrants ORDER BY '.$ord);
	
	if (false && isset($_REQUEST['ScorerName'])) {
		$lnk = '<a href="'.$HOME_URL.'" onclick="return areYouSure(\'\r\n'.$TAGS['LogoutScorer'][0].' '.$_REQUEST['ScorerName'].' ?\');">';
		startHtml($TAGS['ttScoring'][0],$lnk.$TAGS['Scorer'][0].': '.$_REQUEST['ScorerName'].'</a>',true);
	} else {
		$lnk = '<a href="'.$HOME_URL.'">';
		startHtml($TAGS['ttScoring'][0],$TAGS['oi_Scorecards'.(isset($_REQUEST['mc']) ? 'MC' : '')][0],true);
	}
	
	eval("\$evs = ".$TAGS['EntrantStatusV'][0]);
?>
<script>
function clearlock(obj)
// This uses an AJAX call to clear the lock on the specified entrant record
{
	event.preventDefault();
	let entrantid = obj.getAttribute('data-entrantid');
	let xhttp = new XMLHttpRequest();
	xhttp.onreadystatechange = function() {
		let ok = new RegExp("\W*ok\W*");
		if (this.readyState == 4 && this.status == 200) {
			console.log('{'+this.responseText+'}');
			if (ok.test(this.responseText)) {
				console.log('cleared ok');
				obj.innerHTML = '';
			}
		}
	};
	xhttp.open("GET", "score.php?c=clearlock&e="+entrantid, true);
	xhttp.send();

}
function submitMe(obj)
{
	var ent = '';
    for ( var i = 0; i < obj.childNodes.length; i++ ) {
        if ( obj.childNodes[i].className == 'EntrantID' )
			ent = obj.childNodes[i].innerText;
     }
	 if (ent == '')
		 return;
	 var frm = document.getElementById('entrantpick');
	 document.getElementById('EntrantID').value = ent;
	 frm.submit();

}

function filterByName(x)
{
	//alert('FBN=='+x);
	if (x=='')
		return;
	
	let tab = document.getElementById('entrantrows');
	let firstLink = -1;
	let nrows = 0;
	
	for (let i = 0; i < tab.childNodes.length; i++ )
	{
		//alert('Row ' + i + ' is ' + tab.childNodes[i].className);
		for ( let j = 0; j < tab.childNodes[i].childNodes.length; j++ )
		{
			//alert('col ' + j + ' is ' + tab.childNodes[i].childNodes[j].className);
			if ( tab.childNodes[i].childNodes[j].className == 'EntrantID' )
			{
				//alert('Row ' + i + '[' + tab.childNodes[i].childNodes[j].innerText + ']');
				tab.childNodes[i].setAttribute('data-ent',tab.childNodes[i].childNodes[j].innerText);
			}
			if ( tab.childNodes[i].childNodes[j].className == 'RiderName' )
			{
				if (tab.childNodes[i].childNodes[j].innerText.toUpperCase().indexOf(x.toUpperCase()) < 0)
					tab.childNodes[i].className = 'tabContenthide';
				else
				{
					//alert('Row ' + i + ' has firstLink already set to ' + firstLink);
					if (firstLink < 0)
						firstLink = i;
					tab.childNodes[i].className = 'link';
					nrows++;
				}
			}
		}
	}
	if (firstLink >= 0)
	{
		//alert('Setting EntrantID from row '+firstLink+'; value '+tab.childNodes[firstLink].getAttribute('data-ent'));
		document.getElementById('EntrantID').value = tab.childNodes[firstLink].getAttribute('data-ent');
	}
	document.getElementById('savedata').disabled = (nrows != 1);
}
</script>
<?php	
	echo('<div id="pickentrant">');
	if (isset($_REQUEST['mc'])) {
		echo('<p>'.$TAGS['MarkConfirmedFull'][0].' '.$TAGS['MarkConfirmedFull'][1].'</p>');
	}
	echo('<h4>'.$TAGS['PickAnEntrant'][1].'</h4>');
	echo('<form id="entrantpick" method="get" action="score.php">');
	echo('<label for="EntrantID">'.$TAGS['EntrantID'][0].'</label> ');
	echo('<input oninput="showPickedName();" type="number" autofocus id="EntrantID" name="EntrantID" min="'.$minEntrant.'" max="'.$maxEntrant.'"> '); 
	echo('<input type="hidden" name="c" value="score">');
	if (isset($_REQUEST['mc']) && $DBVERSION >= 4)
		echo('<input type="hidden" name="mc" value="mc">');
	$sname = (isset($_REQUEST['ScorerName']) ? htmlspecialchars($_REQUEST['ScorerName']) : '');
	echo('<input type="hidden" id="ScorerName" name="ScorerName" value="'.$sname.'">');
	echo('<label for="NameFilter">'.$TAGS['NameFilter'][0].' </label>');
	echo(' <input  type="text" id="NameFilter" title="'.$TAGS['NameFilter'][1].'" onkeyup="filterByName(this.value)">');
	echo('<input class="button" type="submit" id="savedata" disabled="disabled" value="'.$TAGS['ScoreThis'][0].'" > ');
	echo('</form>');
	echo("</div>\r\n");
	
	echo('<table><thead><tr>');
	echo('<th></th>');
	echo('<th></th>');
	echo('<th></th>');
	echo('<th></th>');
	echo('</tr></thead><tbody id="entrantrows">');
	while ($rd = $R->fetchArray())
	{
		echo('<tr class="link" onclick="submitMe(this)"><td class="EntrantID">'.$rd['EntrantID'].'</td>');
		echo('<td class="RiderName">'.$rd['RiderName']);
		if ($rd['PillionName'] != '')
			echo(' / '.$rd['PillionName']);
		echo('</td>');
		$es = $evs[''.$rd['EntrantStatus']];
		if ($es=='')
			$es = '[[ '.$rd['EntrantStatus'].']]';
		if ($DBVERSION >= 4)
			if ($rd['Confirmed'] == $KONSTANTS['ScorecardIsDirty'])
				$es .= ' <span class="red" style="padding:0;" title="'.$TAGS['ScorecardIsDirty'][1].'">'.$TAGS['ScorecardIsDirty'][0].'</span>';
			else if ($rd['Confirmed'] != 0)
				$es .= ' '.$KONSTANTS['ConfirmedBonusTick'];
		echo('<td class="EntrantStatus">'.$es.'</td>');
		echo('<td class="ScoredBy" title="'.$TAGS['ScorecardInUse'][1].'"');
		echo(' oncontextmenu="clearlock(this);" data-entrantid="'.$rd['EntrantID'].'">');
		if ($rd['ScoringNow']<>0) {
			echo($TAGS['ScorecardInUse'][0]);
		 	if ($rd['ScoredBy']<>'')
				echo(' == '.$rd['ScoredBy']);
		}
		echo('</td>');
		echo('</tr>');
	}
	echo('</tbody></table>');
	echo("\r\n");
}




function updateClass()
{
		global $DB;
		
		$sql = "SELECT * FROM classes WHERE AutoAssign=1 and Class > 0 ORDER BY Class";
		$R = $DB->query($sql);
		$mp = []; $mb = []; $br = []; $lr = [];
		while ($rd = $R->fetchArray()) {
			$mp[$rd['Class']] = $rd['MinPoints'];
			$mb[$rd['Class']] = $rd['MinBonuses'];
			$br[$rd['Class']] = explode(',',$rd['BonusesReqd']);
			$lr[$rd['Class']] = $rd['LowestRank'];
		}
		if (count($mp) < 1)
			return;
		
		// There are automatic classes available - don't even think about mixing auto/manual classes
		
		$team = getValueFromDB("SELECT TeamID FROM entrants WHERE EntrantID=".$_REQUEST['EntrantID'],"TeamID",0);
		$sql = "SELECT Class,EntrantID,TotalPoints,BonusesVisited,CombosTicked,FinishPosition FROM entrants WHERE ";
		if ($team = 0)
			$sql .= "EntrantID=".$_REQUEST['EntrantID'];
		else
			$sql .= "TeamID=".$team;
		$R = $DB->query($sql);
		$recs = [];
		while ($rd = $R->fetchArray()) {
			$recs[$rd['EntrantID']] = 0; // Default is 0 unless successfully matched this time
			$nc = 1;
			while ($nc <= count($mp)) {
				$ok = $mp[$nc] == 0 || $rd['TotalPoints'] >= $mp[$nc];
				$ok = $ok && ($mb[$nc] == 0 || count(explode(',',$rd['BonusesVisited'])) >= $mb[$nc]);
				// Compulsory bonuses not yet implemented
				if ($ok && count($br[$nc]) > 0) {
					$ok = updateClassBR($br[$nc],$rd['BonusesVisited'],$rd['CombosTicked']); // NIY
				}
				$ok = $ok && ($lr[$nc] == 0 || $rd['FinishPosition'] <= $lr[$nc]);
				if ($ok) {
					$recs[$rd['EntrantID']] = $nc;
					break;
				}
				$nc++;
			}
		}
		foreach ($recs as $ent => $cls) {
			$sql = "UPDATE entrants SET Class=".$cls." WHERE EntrantID=".$ent;
			if (!$DB->exec($sql)) {
				dberror();
				exit;
			}
		}

			
}

function updateClassBR($br,$bv,$ct)
/*
 * I check that every element in $br exists in $bv,$st or $ct
 * and return true if so
 *
 */
{
	foreach($br as $B =>$v) {
		$re = "/\b$v\b/";
		$ok = (preg_match($re,$bv) ||  preg_match($re,$ct));
		error_log($re.' == '.$ok);
		if (!$ok)
			return false;
	}
	return true;		
	
}

function updateScoringFlags($EntrantID=0)
{
	global $DB;

	return; // <<<<<<<<<<<<<<<<<======================
	
	if ($DB->exec('BEGIN IMMEDIATE TRANSACTION')) {
		// Clear records being scored by this scorer
		$sql = "UPDATE entrants SET ScoringNow=0 WHERE ScoredBy='".$DB->escapeString((isset($_REQUEST['ScorerName']) ? $_REQUEST['ScorerName'] : ''))."'";
		if ($DB->exec($sql)) {
			if ($EntrantID <> 0) {
				// Mark this one as being scored now
				$sql = "UPDATE entrants SET ScoringNow=1, ScoredBy='".$DB->escapeString((isset($_REQUEST['ScorerName']) ? $_REQUEST['ScorerName'] : ''))."' WHERE EntrantID=".$EntrantID;
				$DB->exec($sql);
			}
		}
		$DB->exec('COMMIT TRANSACTION');
	}
	
}

function prgPicklist()
/*
 * prg = post/redirect/get
 *
 * Called to get browser to ask for picklist after a post
 *
 */
{
	$get = "score.php";
	if (isset($_REQUEST['mc']))
		$get .= '?mc='.$_REQUEST['mc'];
	header("Location: ".$get);
	exit;
}

//var_dump($_REQUEST);
//var_dump($_COOKIE);

if (isset($_REQUEST['clear']))
	updateScoringFlags(0);

if (isset($_REQUEST['showpicklist']))
{
	if ($_REQUEST['showpicklist']=='savescore') {
		if (putScore())
			prgPicklist();
		exit;
	}
	showPicklist('EntrantID');
	exit;
}

if (isset($_REQUEST['savescore']))
{
	if (putScore())
		prgPicklist();
	exit;
}

if (isset($_REQUEST['login']) && $_REQUEST['ScorerName'] <> '') 
	loginNewScorer();
else if (isset($_REQUEST['c']) && $_REQUEST['c'] == 'clearlock')
	ajaxclearlock();
else if (isset($_REQUEST['c']) && $_REQUEST['c'] == 'setlock')
	ajaxsetlock();
else if (isset($_REQUEST['c']) && $_REQUEST['c'] == 'score')
	scoreEntrant(FALSE);
else if (isset($_REQUEST['c']) && $_REQUEST['c'] == 'blank')
	scoreEntrant(TRUE);
else if (isset($_REQUEST['c']) && $_REQUEST['c'] == 'pickentrant')
	showPicklist($_REQUEST['ord']);
else if (isset($_REQUEST['ScorerName']) || rally_params_established())
	showPicklist('EntrantID');
else if (rally_params_established())
	inviteScorer();
else
	include("setup.php");
exit;
?>