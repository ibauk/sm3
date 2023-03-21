<?php
error_reporting(E_ALL);
ini_set("display_errors", 1); 

/*
 * I B A U K   -   S C O R E M A S T E R
 *
 * I contain all the text literals used throughout the system. If translation/improvement
 * is needed, this is the file to be doing it.
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

session_start();

$KONSTANTS['DistanceIsMiles'] = 0;
$KONSTANTS['DistanceIsKilometres'] = 1;
$KONSTANTS['OdoCountsMiles'] = 0;
$KONSTANTS['OdoCountsKilometres'] = 1;
$KONSTANTS['KmsPerMile'] = 1.60934;

$KONSTANTS['TimezoneCities'] = ['Europe/London','Europe/Berlin','Europe/Helsinki','Europe/Moscow','Europe/Dublin'];
// Timezone range to offer: GMT-n .. GMT+n
$KONSTANTS['GMTPlusMinus'] = 10;

require_once('customvars.php');
	
// Uninteresting values
$KONSTANTS['MaxMilesFixedP'] = 0;
$KONSTANTS['MaxMilesFixedM'] = 1;
$KONSTANTS['MaxMilesPerMile'] = 2;
$KONSTANTS['MaxMilesExclude'] = 3;
$KONSTANTS['ManualScoring'] = 0;
$KONSTANTS['SimpleScoring'] = 1;
$KONSTANTS['CompoundScoring'] = 2;
$KONSTANTS['AutoScoring'] = 3;
$KONSTANTS['SuppressMults'] = 0;
$KONSTANTS['ShowMults'] = 1;
$KONSTANTS['AutoRank'] = 1;
$KONSTANTS['AutoShowMults'] = 2;
$KONSTANTS['TiedPointsSplit'] = 1;
$KONSTANTS['RankTeamsAsIndividuals'] = 0;	
$KONSTANTS['RankTeamsHighest'] = 1;
$KONSTANTS['RankTeamsLowest'] = 2;
$KONSTANTS['RankTeamsCloning'] = 3;
$KONSTANTS['EntrantDNS'] = 0;
$KONSTANTS['EntrantOK'] = 1;
$KONSTANTS['EntrantFinisher'] = 8;
$KONSTANTS['EntrantDNF'] = 3;
$KONSTANTS['BeingScored'] = 1;
$KONSTANTS['NotBeingScored'] = 0;
$KONSTANTS['AreYouSureYes'] = 'yesIamSure';
$KONSTANTS['StartOptionMass'] = 0;
$KONSTANTS['StartOption1stClaim'] = 1;
$KONSTANTS['StartOptionCohorts'] = 2;
$KONSTANTS['TiesSplitByMiles'] = 1;
$KONSTANTS['TeamRankIndividuals'] = 0;
$KONSTANTS['TeamRankHighest'] = 1;
$KONSTANTS['TeamRankLowest'] = 2;
$KONSTANTS['TimeSpecDatetime'] = 0;
$KONSTANTS['TimeSpecRallyDNF'] = 1;
$KONSTANTS['TimeSpecEntrantDNF'] = 2;

$KONSTANTS['TPM_MultPerMin'] = 3;
$KONSTANTS['TPM_PointsPerMin'] = 2;
$KONSTANTS['TPM_FixedMult'] = 1;
$KONSTANTS['TPM_FixedPoints'] = 0;

$KONSTANTS['ORDINARY_BONUS_PREFIX'] = 'B';
$KONSTANTS['SPECIAL_BONUS_PREFIX'] = 'S';
$KONSTANTS['COMBO_BONUS_PREFIX'] = 'C';


$KONSTANTS['BCM_UNKNOWN'] = 0;
$KONSTANTS['BCM_EBC'] = 1;
$KONSTANTS['BCM_PAPER'] = 2;

$KONSTANTS['MMM_FixedPoints'] = 0;
$KONSTANTS['MMM_Multipliers'] = 1;
$KONSTANTS['MMM_PointsPerMile'] = 2;



// Beware, these next two used for combinations & catcompounds
$KONSTANTS['ComboScoreMethodPoints'] = 0;
$KONSTANTS['ComboScoreMethodMults'] = 1;


$KONSTANTS['DefaultOdoScaleFactor'] = 1;
$KONSTANTS['DefaultEntrantStatus'] = $KONSTANTS['EntrantOK'];

$KONSTANTS['ConfirmedBonusMarker'] = '++';
$KONSTANTS['ConfirmedBonusTick'] = '<span class="ConfirmedBonusTick" title="'.$TAGS['ConfirmedBonusTick'][1].'">'.$TAGS['ConfirmedBonusTick'][0].'</span>';

//entrants.confirmed value
$KONSTANTS['ScorecardIsClean'] = 0;
$KONSTANTS['ScorecardIsConfirmed'] = 1;
$KONSTANTS['ScorecardIsDirty'] = 2;


// Claims list constants	
$KONSTANTS['showAll'] = 0;		// ignore Judged/applied status
$KONSTANTS['showOnly'] = 1;		// show only Judged/applied claims
$KONSTANTS['showNot'] = 2;		// show only undecided/unapplied claims
$KONSTANTS['UNDECIDED_CLAIM']	= -1;

$KONSTANTS['UPLOADS_FOLDER'] = "uploads";

$KONSTANTS['COMPULSORYBONUS'] = 1;
$KONSTANTS['MUSTNOTMATCH'] = 2;

$KONSTANTS['doxpath'] = 'help'; 	// path from sm to folder containing help documents
$KONSTANTS['doxpage'] = 'smhelp';	// browser tab name for showing helps

// Compound category rules
$KONSTANTS['CAT_NumBonusesPerCatMethod'] = 0;
$KONSTANTS['CAT_NumNZCatsPerAxisMethod'] = 1;
$KONSTANTS['CAT_OrdinaryScoringRule'] = 0;
$KONSTANTS['CAT_DNF_Unless_Triggered'] = 1;
$KONSTANTS['CAT_DNF_If_Triggered'] = 2;
$KONSTANTS['CAT_PlaceholderRule'] = 3;
$KONSTANTS['CAT_OrdinaryScoringSequence'] = 4;
$KONSTANTS['CAT_ResultPoints'] = 0;
$KONSTANTS['CAT_ResultMults'] = 1;
$KONSTANTS['CAT_ModifyAxisScore'] = 0;
$KONSTANTS['CAT_ModifyBonusScore'] = 1;

$KONSTANTS['BonusScoringFlags'] = "ABDFNRT";

// Common subroutines below here; nothing translateable below
	
	
// Open the database	
try
{
	$DB = new SQLite3($DBFILENAME);
	$DB->exec("PRAGMA busy_timeout = 5000");  // Set lock timeout to 15 seconds
	//$DB->exec("PRAGMA journal_mode = wal");
} catch(Exception $ex) {
	echo("OMG ".$ex->getMessage().' file=[ '.$DBFILENAME.' ]');
}
$DBVERSION = getValueFromDB("SELECT DBVersion FROM rallyparams","DBVersion",0);

$AUTORANK =  getValueFromDB("SELECT AutoRank FROM rallyparams","AutoRank",0);

$KONSTANTS['BasicDistanceUnit'] = getValueFromDB("SELECT MilesKms FROM rallyparams","MilesKms",$KONSTANTS['BasicDistanceUnit']);
$KONSTANTS['LocalTZ'] = getValueFromDB("SELECT LocalTZ FROM rallyparams","LocalTZ",$KONSTANTS['LocalTZ']);
$KONSTANTS['DecimalPointIsComma'] = getValueFromDB("SELECT DecimalComma FROM rallyparams","DecimalComma",$KONSTANTS['DecimalPointIsComma']);
$KONSTANTS['DefaultCountry'] = getValueFromDB("SELECT HostCountry FROM rallyparams","HostCountry",$KONSTANTS['DefaultCountry']);
$KONSTANTS['DefaultLocale'] = getValueFromDB("SELECT Locale FROM rallyparams","Locale",$KONSTANTS['DefaultLocale']);


/* Each simple bonus may be classified using
 * this number of categories. This reflects 
 * the database structure, it may not be
 * arbitrarily increased.
 */
 
$KONSTANTS['NUMBER_OF_COMPOUND_AXES'] = 9; // 9




$RALLY_INITIALISED = (1==1);
$HTML_STARTED = false;

// Common subroutines

function bonusesPresent()
{
	return getValueFromDB("SELECT count(*) As Rex FROM bonuses","Rex",-1);
}



// Called to check that, if a limit on the number of bonuses which may be claimed is active,
// this claim is within the limit.
function bonusCountOK($entrantid,$bonusid) {

	global $DB;

	$mbc = getSetting('bonusClaimsLimit',0);
	if ($mbc < 1) {
		return true;
	}
	$icdc = getSetting('ignoreClaimDecisionCode','9');
	$sql = "SELECT DISTINCT BonusID FROM claims WHERE EntrantID=".$entrantid. " AND Decision <> ".$icdc;
	$R = $DB->query($sql);
	$BonusesClaimed = 0;
	while ($rd = $R->fetchArray()) {
		$BonusesClaimed++;
		if ($BonusesClaimed > $mbc) {
			return false;
		}
		if ($bonusid == $rd['BonusID']) { // Already claimed so still good
			return true;
		}

	}
	$BonusesClaimed++;
	if ($BonusesClaimed > $mbc) {
		return false;
	} 
	return true;

}




// This is called to check whether this bonus claim is denied because of being out of sequence with
// and earlier claim for the same bonus. The flag setting bonusReclaims is checked. A value of 0
// permits out of sequence reclaims, 1 prevents out of sequence reclaims.
//
// Time | Bonus | bonusReclaimOK(0) | bonusReclaimOK(1)
// ---- | ----- | ----------------- | -----------------
// 1301 |  A1   |		true		| 		true
// 1331 |  B1   |		true		|		true
// 1401 |  B1   |		true		|		true
// 1421 |  C1   |		true		|		true
// 1431 |  A1   |		true		|		false
// 1441 |  B1	|		true		|		false

function bonusReclaimOK($entrantid,$bonusid,$claimtime,$leg) {

	global $DB;

	$bonusReclaims = getSetting('bonusReclaims',"0");
	if ($bonusReclaims == "0")
		return true;

	$sql = "SELECT * FROM claims WHERE EntrantID=".$entrantid." AND Leg=".$leg." AND ClaimTime < '".$claimtime."'  ORDER BY ClaimTime DESC";
	error_log('BRC: '.$sql);
	$R = $DB->query($sql);
	// Looking for earlier claims for this bonus
	$lastBonusid = $bonusid;
	while ($rd = $R->fetchArray()) {
		if ($rd['BonusID'] == $bonusid && $lastBonusid != $bonusid) return FALSE;
		$lastBonusid = $rd['BonusID'];
	}
	return TRUE;

}

function calcCorrectedMiles($entrantOdoKms,$entrantOdoStart,$entrantOdoFinish,$entrantOdoScaleFactor)
// This is here because it might be needed from multiple locations throughout the application
// ASSUMPTIONS:
// All params apart from scale are positive integers
// Finish >= Start
{
	global $KONSTANTS;
	
	$rallyUsesKms = ($KONSTANTS['BasicDistanceUnit'] != $KONSTANTS['DistanceIsMiles']);
	$cf = $entrantOdoScaleFactor;
	// Now sanity check
	if ($cf < 0.5)
		$cf = 1.0;
	
	//echo("rK=$rallyUsesKms; eK=$entrantOdoKms; eS=$entrantOdoStart; eF=$entrantOdoFinish; cf=$cf<br>");
	
	$odoDistance = ($entrantOdoFinish - $entrantOdoStart) * $cf;

	if ($entrantOdoKms && !$rallyUsesKms)
		$odoDistance = $odoDistance / $KONSTANTS['KmsPerMile'];
	if (!$entrantOdoKms && $rallyUsesKms)
		$odoDistance = $odoDistance * $KONSTANTS['KmsPerMile'];
	
	return intval($odoDistance);
	
	
}

// If ScoringMethod is automatic, I choose what to do
function chooseScoringMethod()
{
	global $DB, $TAGS, $KONSTANTS;
	
	$R = $DB->query("SELECT Count(*) AS Rex FROM catcompound");
	$rd = $R->fetchArray();
	if ($rd['Rex'] > 0)
		return $KONSTANTS['CompoundScoring'];	

	$R = $DB->query("SELECT Count(*) AS Rex FROM bonuses");
	$rd = $R->fetchArray();
	if ($rd['Rex'] > 0)
		return $KONSTANTS['SimpleScoring'];
	
	
	return $KONSTANTS['ManualScoring'];
	
	
}



// If ShowMults is automatic, I decide.
function chooseShowMults($ScoringMethod)
{
	global $DB, $TAGS, $KONSTANTS;
	
	if ($ScoringMethod <> $KONSTANTS['CompoundScoring'])                              
		return $KONSTANTS['SuppressMults'];
	
	$R = $DB->query("SELECT Count(*) AS Rex FROM catcompound WHERE PointsMults=".$KONSTANTS['ComboScoreMethodMults']);
	$rd = $R->fetchArray();
	if ($rd['Rex'] > 0)
		return $KONSTANTS['ShowMults'];
	$R = $DB->query("SELECT Count(*) AS Rex FROM combinations WHERE ScoreMethod=".$KONSTANTS['ComboScoreMethodMults']);
	$rd = $R->fetchArray();
	if ($rd['Rex'] > 0)
		return $KONSTANTS['ShowMults'];
	
	return $KONSTANTS['SuppressMults'];
}


function dberror()
{
	global $DB,$TAGS;
	
	startHtml('!');
	echo('<div id="dberror" title="'.$TAGS['dberroragain'][1].'">'.sprintf($TAGS['dberroragain'][0],$DB->lastErrorMsg()).'</div>');
	return false;
	
}

function defaultRecord($table)
/*
 * I return an array of fields corresponding to each of the columns
 * in $table. Each field is set to the relevant default value.
 *
 */
{
	global $DB;
	
	$sql = "PRAGMA table_info($table)";
	$R = $DB->query($sql);
	$res = [];
	while ($rd = $R->fetchArray()) 
		$res[$rd['name']] = $rd['dflt_value'];
	return $res;
	
}


function emitChooseTZ($name,$id)
/*
 * This contructs and emits a SELECT to show/choose
 * a timezone either as GMT +/- offset or registered
 * timezone city identifier
 *
 */
{
	global $KONSTANTS;
	
	echo('<select name="'.$name.'" id="'.$id.'" oninput="enableSaveButton();" >');
	foreach($KONSTANTS['TimezoneCities'] As $tz) {
		echo('<option value="'.$tz.'"');
		if ($tz==$KONSTANTS['LocalTZ'])
			echo(' selected');
		echo('>'.$tz.'</option>');
	}
	$tz = 0 - $KONSTANTS['GMTPlusMinus'];
	while ($tz <= $KONSTANTS['GMTPlusMinus']) {
		$tzz = sprintf("%+03d",$tz).'00';
		echo('<option value="'.$tzz.'"');
		if ($tzz==$KONSTANTS['LocalTZ'])
			echo(' selected');
		echo('>GMT'.$tzz.'</option>');
		$tz++;
	}
	echo('</select>');
	
	
}


function entrantsPresent()
{
	return getValueFromDB("SELECT count(*) As Rex FROM entrants","Rex",-1);
}


// excludeTooFar checks whether this claim is to be excluded for being beyond the threshold.
function excludeTooFar($entrantid,$odo) {

	global $DB, $KONSTANTS;

	$maxMiles = getValueFromDB("SELECT PenaltyMaxMiles FROM rallyparams","PenaltyMaxMiles",0);
	if ($maxMiles < 1) { return false; }
	$method = getValueFromDB("SELECT MaxMilesMethod FROM rallyparams","MaxMilesMethod",$KONSTANTS['MaxMilesFixedP']); // NOT MaxMilesExclude!
	if ($method != $KONSTANTS['MaxMilesExclude']) { return false; }

	$sql = "SELECT OdoKms,OdoRallyStart,OdoScaleFactor FROM entrants WHERE EntrantID=$entrantid";
	$R = $DB->query($sql);
	if (!$rd = $R->fetchArray()) { return false; }
	$cm = calcCorrectedMiles($rd['OdoKms'],$rd['OdoRallyStart'],$odo,$rd['OdoScaleFactor']);
	return $cm >$maxMiles;

}

function getSetting($setting,$default)
{
	global $DB, $DBVERSION;

	if ($DBVERSION < 6)
		return $default;

	$settings = json_decode(getValueFromDB("SELECT settings FROM rallyparams","settings","{}"),true);
	if (isset($settings[$setting]))
		return $settings[$setting];
	else
		return $default;

}

function joinDateTime($dt,$tm)
// Accept a date and a time and return a properly formatted Datetime based on ISO8601
{
	return $dt.'T'.$tm;
}

function joinPaths() {
    $paths = func_get_args();
	
	return preg_replace('~[/\\\\]+~', DIRECTORY_SEPARATOR, implode(DIRECTORY_SEPARATOR, $paths));
}

function logtime($stamp)
/* We're really only interested in the time of day and which of a few days it's on */
{
	try {
		$dt = new DateTime(''.$stamp);
		$dtf = $dt->format('D H:i');
	} catch (Exception $e) {
		$dtf = $stamp;
	}
	if (strpos(''.$stamp,'+00:00') > 0)
		$dtf .= "z";
	return '<span title="'.$stamp.'">'.$dtf.'</span>';
}

function parseBonusClaim($claim,&$bonusid,&$points,&$minutes,&$xp,&$pp) {

    $m = [];
    preg_match('/([a-zA-z0-9]+)=?(\d+)?(X|)?(P|)?;?(\d+)?/',$claim,$m);
    if (isset($m[1])) {
        $bonusid = $m[1];
        if (isset($m[2]))
            $points = $m[2];
        else
            $points = '';
			if (isset($m[3]))
            $xp = $m[3]=='X';
        else 
            $xp = false;
		if (isset($m[4]))
            $pp = $m[4]=='P';
        else 
            $pp = false;
        if (isset($m[5]))
            $minutes = $m[5];
        else
            $minutes = '';
    } else {
        $bonusid = $claim;
        $points = 0;
        $minutes = 0;
        $xp = false;
		$pp = false;
    }

}


function properName($enteredName)
// Used to fix names entered online; not everyone knows about shift keys
// If they've tried, I just return what they entered but if not I'll
// return initial capitals followed by lowercase
{
	$x = explode(' ',$enteredName);
	$z = false;
	for ($i = 0; $i < sizeof($x); $i++)
		if (ctype_lower($x[$i]) || ctype_upper($x[$i]))
			$z = true;
	if ($z)
		return ucwords(strtolower(str_replace('  ',' ',$enteredName)));
	else
		return str_replace('  ',' ',$enteredName);
	
}

function splitDatetime($dt)
/* Accept either 'T' or space as splitting date/time */
{
	if (strpos(''.$dt,'T'))
		$S = 'T';
	else if (strpos(''.$dt,' '))
		$S = ' ';
	else
		return ['','']; 
	
	$dtx = explode($S,$dt);
	return $dtx;
		
}

function getThemeCSS($theme)
{
	
	return getValueFromDB("SELECT css FROM themes WHERE Theme='$theme'","css","");
}

function getValueFromDB($sql,$col,$defaultvalue)
{
	global $DB;
	
	try {
		$R = $DB->query($sql);
		if ($rd = $R->fetchArray())
			return $rd[$col];
		else
			return $defaultvalue;
	} catch (Exception $ex) {
		return $defaultvalue.$ex;
	}
}


function presortTeams($TeamRanking,$rankPointsPerMile)
{
	global $DB, $TAGS, $KONSTANTS;
	
	$sql = 'SELECT * FROM _ranking WHERE TeamID>0 ORDER BY TeamID,';
	if ($rankPointsPerMile)
		$sql .= 'PPM';
	else
		$sql .= 'TotalPoints';
	if ($TeamRanking == $KONSTANTS['TeamRankHighest'])
		$sql .= ' DESC';
	$LastTeam = -1;
	$LastTeamPoints = 0;
	$LastTeamPPM = 0;
	$LastTeamMiles = 0;

	//echo($sql.'<br>');
	$R = $DB->query($sql);

	while ($rd = $R->fetchArray())
	{

		if ($LastTeam <> $rd['TeamID'])
		{
			$LastTeam = $rd['TeamID'];
			$LastTeamPoints = $rd['TotalPoints'];
			$LastTeamPPM = $rd['PPM'];
			$LastTeamMiles = $rd['CorrectedMiles'];
			//echo("UPDATE _ranking SET TotalPoints=$LastTeamPoints, CorrectedMiles=$LastTeamMiles WHERE TeamID=$LastTeam<br>");
			$DB->exec("UPDATE _ranking SET TotalPoints=$LastTeamPoints, CorrectedMiles=$LastTeamMiles, PPM=$LastTeamPPM WHERE TeamID=$LastTeam");
		}	
	}

}



function rankEntrants($intransaction)
{
	global $DB, $TAGS, $KONSTANTS;

	//error_log(' ranking entrants ');

	$rankPointsPerMile = getSetting('rankPointsPerMile','false') == 'true';

	$R = $DB->query('SELECT * FROM rallyparams');
	$rd = $R->fetchArray();
	$TiedPointsRanking = $rd['TiedPointsRanking'];
	$TeamRanking = $rd['TeamRanking'];
	
	if (!$intransaction)
		if (!$DB->exec('BEGIN IMMEDIATE TRANSACTION')) {
			dberror();
			exit;
		}

	$DB->exec('UPDATE entrants SET FinishPosition=0');

	$sql = 'CREATE TEMPORARY TABLE "_ranking" ';
	$sql .= 'AS SELECT EntrantID,TeamID,TotalPoints,CorrectedMiles,IfNull((TotalPoints*1.0)/CorrectedMiles,0) AS PPM,0 AS Rank FROM entrants WHERE EntrantStatus = '.$KONSTANTS['EntrantFinisher'];
	$DB->exec($sql);

	if ($TeamRanking != $KONSTANTS['TeamRankIndividuals'])
		presortTeams($TeamRanking,$rankPointsPerMile);

	$sql = 'SELECT * FROM _ranking ORDER BY ';
	if ($rankPointsPerMile)
		$sql .= 'PPM';
	else
		$sql .= 'TotalPoints';
	$sql .= ' DESC,CorrectedMiles ASC';
	$R = $DB->query($sql);
	
	$fp = 0;
	$lastTotalPoints = -1;
	$lastPPM = -1;
	$lastCorrectedMiles = -1;
	$N = 1;
	$LastTeam = -1;

	

	While ($rd = $R->fetchArray()) 
	{
		//echo($rd['EntrantID'].':'.$rd['TeamID'].' = '.$rd['TotalPoints'].', '.$rd['CorrectedMiles'].'<br>');
		
		
		If (($TiedPointsRanking != $KONSTANTS['TiesSplitByMiles']) || 
				($rd['TotalPoints'] <> $lastTotalPoints && !$rankPointsPerMile) ||
				($rd['TotalPoints']==$lastTotalPoints && $rd['CorrectedMiles']==$lastCorrectedMiles && !$rankPointsPerMile) ||
				($rd['PPM'] <> $lastPPM && $rankPointsPerMile) ||
				($rd['PPM']==$lastPPM && $rd['CorrectedMiles']==$lastCorrectedMiles && $rankPointsPerMile) 
			)
		{
			// No splitting needed, just assign rank
			if ($rd['TeamID'] == $LastTeam && $TeamRanking != $KONSTANTS['TeamRankIndividuals']) 
				;
			else if ($rd['TotalPoints'] == $lastTotalPoints && !$rankPointsPerMile)
				$N++;
			else if ($rd['PPM'] == $lastPPM && $rankPointsPerMile)
				$N++;
		
			else 
			{
				$fp += $N;
				$N = 1;
			}
		}
		else
		{
			// Must be split according to mileage
			if ($LastTeam != $rd['TeamID'])
			{
				$fp += $N;
				$N = 1;
			}
			else if ($rd['TeamID'] > 0 && $rd['TeamID'] != $LastTeam)
				$N++;
		}
		if ($rd['TeamID'] > 0)
			$LastTeam = $rd['TeamID'];
		
		$lastTotalPoints = $rd['TotalPoints'];
		$LastPPM = $rd['PPM'];
		$lastCorrectedMiles = $rd['CorrectedMiles'];
		$sql = "UPDATE entrants SET FinishPosition=$fp WHERE EntrantID=".$rd['EntrantID'];
		//echo($sql.'; LastTeam='.$LastTeam.', N='.$N.', fp='.$fp.'<br>');
		$DB->exec($sql);

	}
	if (!$intransaction)
		$DB->exec('COMMIT TRANSACTION');
	
	//error_log(' ranking complete ');
	//exit;
}








function show_menu_taglist()
{
	global $TAGS,$DB;
	
	$R = $DB->query("SELECT * FROM functions");
	$mytags = array();
	while ($rd = $R->fetchArray())
	{
		$t = explode(',',$rd['Tags']);
		foreach($t as $tg)
		{
			$mytags[$tg] = $tg;
		}
	}
	sort($mytags);
	echo("<select id='navbar_tagselect'");
	echo(' title="'.$TAGS['AdmSelectTag'][1].'"');
	echo(' onchange="window.location.href = '."'admin.php?tag='");
	echo("+document.getElementById('navbar_tagselect').value".';"');
	echo('>');
	echo('<option value="">'.$TAGS['AdmSelectTag'][0].'</option>');
	foreach($mytags as $t)
	{
		if ($t != '')
			echo("<option value='$t'>$t</option>");
	}
	echo("</select>");
	
}


function showNav()
{
	global $TAGS;
	
	return;
	
	echo('<div id="navbar">');
	
	echo( '<span id="navbar_breadcrumbs"></span> ');
	
	$xx = "return document.getElementById('EntrantSearchKey').value!='';";
	echo('<form method="get" action="entrants.php"  onsubmit="'.$xx.'">');
	echo(' <span title="'.$TAGS['UtlFindEntrant'][1].'">');
	echo('<input type="hidden" name="c" value="entrants">');
	echo('<input type="hidden" name="mode" value="find">');
	echo('<input type="text" name="x" id="EntrantSearchKey" oninput="document.getElementById(\'LookupEntrant\').disabled = this.value.length==0;" placeholder="'.$TAGS['UtlFindEntrant'][0].'">');
	echo('<input type="submit" disabled id="LookupEntrant" value="?"> ');
	echo('</span> ');
	echo('</form>');
	
	show_menu_taglist();

	echo(' <input title="Help!" type="button" value=" ? " onclick="showHelp('."'index'".');">');
	echo('</div>');
}

function startHtml($pagetitle,$otherInfo = '',$showNav=true)
{
	global $DB, $TAGS, $KONSTANTS, $HTML_STARTED;
	global $HOME_URL, $DBVERSION;
	
	if ($HTML_STARTED)
		return;
	
	$HTML_STARTED = true;
	
	$R = $DB->query('SELECT * FROM rallyparams');
	$rd = $R->fetchArray();
	
//header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
//header("Pragma: no-cache"); // HTTP 1.0.
//header("Expires: 0"); // Proxies.	
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php
echo('<title>'.$pagetitle.'</title>');
?>
<meta charset="UTF-8" >
<meta name="viewport" content="width=device-width, initial-scale=1" >
<link rel="stylesheet" type="text/css" href="reboot.css?ver=<?= filemtime('reboot.css')?>">
<link rel="stylesheet" type="text/css" href="score.css?ver=<?= filemtime('score.css')?>">
<?php if ($DBVERSION>=4) echo('<style><!-- :root {'.getThemeCSS($rd['Theme']).'} --></style>');?>
<script src="custom.js?ver=<?= filemtime('custom.js')?>" defer></script>
<script src="score.js?ver=<?= filemtime('score.js')?>" defer></script>
<script src="recalc.js?ver=<?= filemtime('recalc.js')?>" defer></script>
</head>
<body onload="bodyLoaded();">
<?php echo('<input type="hidden" id="BasicDistanceUnit" value="'.$KONSTANTS['BasicDistanceUnit'].'">'); ?>
<?php echo('<input type="hidden" id="DBVERSION" value="'.$DBVERSION.'">'); ?>
<?php echo('<input type="hidden" id="DefaultLocale" value="'.$KONSTANTS['DefaultLocale'].'">'); ?>
<div id="header">
<?php	
	echo(' <input title="Help!" style="padding-top:0; padding-bottom: 0;margin-left: 1em;" type="button" value=" ? " onclick="showHelp('."'index'".');"> ');

	echo(' <input title="'.htmlspecialchars($TAGS['gblMainMenu'][1]).'"');
	echo(' style="padding-top:1px; padding-bottom: 3px;" ');
	echo(' type="button" value=" &#127968; " onclick="'."window.location='".'admin.php'."'".'"> ');


	echo("<a href=\"".$HOME_URL);
	if (isset($_REQUEST['ScorerName']))
	{
		$scorer = $_REQUEST['ScorerName'];
		if ($scorer <> '')
			echo("?ScorerName=$scorer&amp;clear");
	}
	echo("\">");
	echo('<span id="hdrRallyTitle" title="'.htmlspecialchars($TAGS['gblMainMenu'][1]).'"> ');
	echo(htmlspecialchars(preg_replace('/\[|\]|\|/','',$rd['RallyTitle'])));
	if ($rd['NumLegs'] > 1 && $rd['CurrentLeg'] > 0) {
		echo(' <span title="'.$TAGS['CurrentLeg'][1].'">['.$rd['CurrentLeg'].'</span>/');
		echo('<span title="'.$TAGS['LegsCount'][1].'">'.$rd['NumLegs'].'] </span>');
	}
	echo(' </span>');
	echo("</a>");
	echo('<span id="hdrOtherInfo">'.$otherInfo.'</span>');
	echo("\r\n</div>\r\n");
	if ($showNav)
		showNav();
}
function showFooter()
{
	global $DB, $TAGS;
	//echo('<div id="footer">');
	//echo('<span id="ftrAdminMenu" title="'.$TAGS['AdminMenu'][1].'"><a href="admin.php">'.$TAGS['AdminMenu'][0].'</a></span></div>');
	echo('</body></html>');
}

function rally_params_established()
{
	global $DB;
	
	$sql = "SELECT DBState FROM rallyparams";
	$R = $DB->query($sql);
	$rd = $R->fetchArray();
	return ($rd['DBState'] > 0);
}

function startsWith($string, $startString) { 
  $len = strlen($startString); 
  return (substr($string, 0, $len) === $startString); 
} 

function updateCohort($cohort,$startOption) {

	global $DB, $TAGS, $KONSTANTS;

	$fixedStart = $startOption != $KONSTANTS['StartOption1stClaim'] ? 1 : 0;
	$useST = false;
	if ($startOption == $KONSTANTS['StartOptionMass']) {
		$st = getValueFromDB("SELECT StartTime FROM rallyparams","StartTime","");
		$useST = true;
	}
	$sql = "INSERT OR REPLACE INTO cohorts (Cohort,FixedStart";
	if ($useST) 
		$sql .= ",StartTime";
	$sql .= ") VALUES($cohort,$fixedStart";
	if ($useST)
		$sql .= ",'$st'";
	$sql .= ")";
	//echo($sql);
	$DB->exec($sql);

	updateCohortEntrants($cohort);
}

function updateCohortEntrants($cohort) {

	global $DB, $TAGS, $KONSTANTS;

	$R = $DB->query("SELECT * FROM cohorts WHERE Cohort=".$cohort);
	if (!$rd = $R->fetchArray())
		return;
	$sql = "UPDATE entrants SET StartTime=";
	if ($rd['FixedStart'] == 0)
		$sql .= "NULL";
	else
		$sql .= "'".$rd['StartTime']."'";
	$sql .= " WHERE Cohort=$cohort AND EntrantStatus=".$KONSTANTS['EntrantOK'];
	$DB->exec($sql);
}

function defaultNewEntrant() {

	global $DB, $TAGS, $KONSTANTS;

	$rd = defaultRecord('entrants');
	
	// Set some defaults
	$rd['KmsOdo'] = $KONSTANTS['BasicDistanceUnit'];
	$rd['OdoScaleFactor'] = $KONSTANTS['DefaultOdoScaleFactor'];
	$rd['EntrantStatus'] = $KONSTANTS['DefaultEntrantStatus'];
	$rd['Country'] = $KONSTANTS['DefaultCountry'];
	if (getValueFromDB("SELECT StartOption FROM rallyparams","StartOption",$KONSTANTS['StartOptionMass']) == $KONSTANTS['StartOptionMass'])
		$rd['StartTime'] = getValueFromDB('SELECT StartTime FROM rallyparams','StartTime','');
	
	return $rd;
}


?>

