<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

/*
 * I B A U K   -   S C O R E M A S T E R
 *
 * I handle the bulk scorecard recalculation
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
 *
 */


// TODO Average speed penalties

require_once('common.php');


$RP = [];               // Holds contents of RallyParams table
$catCompoundRules = [];
$bonusValues = [];
$specialValues = [];
$comboValues = [];
$scorex = [];
$reasons = [];
$axisLabels = [];
$catlabels = [];
$timePenalties = [];
$speedPenalties = [];
$finisherStatus = $KONSTANTS['EntrantOK'];
eval("\$evs = ".$TAGS['EntrantStatusV'][0]);

class SCOREXLINE {

    public $id;
    public $desc;
    public $pointsDesc = '';
    public $points;
    public $totalPoints;

    public function asHTML() {
        $res = '<tr><td class="sxcode">'.$this->id.'</td>';
        $res .= '<td class="sxdesc">'.$this->desc;
        $res .= '<span class="sxdescx">'.$this->pointsDesc.'</span></td>';
        $res .= '<td class="sxitempoints">'.$this->points.'</td>';
        //$res .= '<td class="sxtotalpoints">'.$this->totalPoints.'</td>';
        $res .= '</tr>';
        return $res;
    }

}



function applyClaim($claimid,$intransaction) {

	global $DB, $KONSTANTS;

    error_log('applying claim # '.$claimid);
    
    $bonusReclaims = getSetting('bonusReclaims',"0");
    $ignoreClaimDecisionCode = getSetting('ignoreClaimDecisionCode',"9");

	$sql = "SELECT * FROM claims WHERE rowid=".$claimid;
	$R = $DB->query($sql);
	if (!$rc = $R->fetchArray())
		return;


    if ($rc['Decision'] == $ignoreClaimDecisionCode) {
        return;
    }

	if (!$intransaction) {
		if (!$DB->exec("BEGIN IMMEDIATE TRANSACTION")) {
			dberror();
			exit;
		}
	}

    $resetTeamHappiness = $rc['Decision'] > 0 && $bonusReclaims == 0;

    initScorecardVariables();



	$sql = "SELECT IfNull(FinishTime,'2020-01-01') As FinishTime,IfNull(OdoRallyFinish,0) As OdoRallyFinish,BonusesVisited";
	$sql .= ",RejectedClaims";
	$sql .= ",IfNull(StartTime,'') As StartTime";
	$sql .= ",IfNull(OdoRallyStart,0) As OdoRallyStart,IfNull(OdoScaleFactor,1) As OdoScaleFactor";
	$sql .= ",IfNull(CorrectedMiles,0) As CorrectedMiles,OdoKms";
	$sql .= " FROM entrants WHERE EntrantID=".$rc['EntrantID'];
	$R = $DB->query($sql);
	$rd = $R->fetchArray();

    if ($rd['FinishTime'] == 'T') {
        $rd['FinishTime'] = '2020-01-01';
    }

	$fo = $rd['OdoRallyFinish'];
	$cm = $rd['CorrectedMiles'];
	
    $rejectedBonusesLV = [];
    if ($bonusReclaims == 0) {
    	$rcd = explode(',',$rd['RejectedClaims']);
        error_log("RejectedClaims == ".$rd['RejectedClaims']);
        $handled = false;
        for($i = 0; $i < count($rcd); $i++) {
            $x = explode('=',$rcd[$i]);
            array_push($rejectedBonusesLV,$x[0]);
            if ($x[0] === $rc['BonusID']) {
                $handled = true;
                if ($rc['Decision'] < 1) { // Was rejected but isn't now
                    unset($rcd[$i]);
                    unset($rejectedBonusesLV[$i]); 
                } 
                    
                
            } 
        }
        if (!$handled && $rc['Decision'] > 0) {
            array_push($rcd,$rc['BonusID'].'='.$rc['Decision']);
            array_push($rejectedBonusesLV,$rc['BonusID']);
        }
    }

    error_log("rejectedBonusesLV == ".implode(", ",$rejectedBonusesLV));

	$bv = explode(',',$rd['BonusesVisited']);
    $bonusid = '';
    $points = 0;
    $minutes = 0;
    $xp = false;
    $pp = false;
    $appendclaim = true;

    // We need to keep track of the value of the last successful bonus in order to implement
    // AskPoints=2 multiplier logic. This needs a simple lastPointsValue unless we cater for
    // reclaims in which case the sequencing is upset.
    $lastPointsValue = 0;
    
    foreach($bv as $ix => $bonusclaim) {
        parseBonusClaim($bonusclaim,$bonusid,$points,$minutes,$xp,$pp);


        if (!strpos($bonusclaim,"?") > 0 )
            $lastPointsValue = $points;

        if (in_array($bonusid,$rejectedBonusesLV) || ($rc['Decision'] > 0 && $rc['BonusID']==$bonusid)) {
            error_log("lastPointsValue killed because bonus ".$bonusid." was rejected");
            $lastPointsValue = 0;
        } 



        error_log('bv check "'.$bonusclaim.'" rc[bonusid]=="'.$rc['BonusID'].'" bonusid="'.$bonusid.'"; LV='.$lastPointsValue);

        
        if ($rc['BonusID'] == $bonusid) {
            $bonusclaim = $bonusid.'=';
            if ($rc['AskPoints'] == $KONSTANTS['AskPointsMultiplier'])
                $bonusclaim .= $lastPointsValue * $rc['Points'].'?'.$rc['Points'];
            else
                $bonusclaim .= $rc['Points'];
            $bonusclaim .= ($rc['QuestionAnswered']==1 ? 'X' : '');
            $bonusclaim .= ($rc['PercentPenalty']==1 ? 'P' : '');
            $bonusclaim .= ';'.$rc['RestMinutes'];
            $bv[$ix] = $bonusclaim;
            $appendclaim = false;
        }

    

    }

    if ($appendclaim) {
        error_log('Appending new claim for '.$rc['BonusID']);
        $newclaim = $rc['BonusID'].'=';
        if ($rc['AskPoints'] == $KONSTANTS['AskPointsMultiplier'])
            $newclaim .= $lastPointsValue * $rc['Points'].'?'.$rc['Points'];
        else
            $newclaim .= $rc['Points'];
        $newclaim .= ($rc['QuestionAnswered']==1 ? 'X' : '');
        $newclaim .= ($rc['PercentPenalty']==1 ? 'P' : '');
        $newclaim .= ';'.$rc['RestMinutes'];
        array_push($bv,$newclaim);
    }




    // If StartTime has not already been set for this entrant then use the time of the first claim
	if ($rd['StartTime']=='')
		$rd['StartTime'] = $rc['ClaimTime'];

    if ($rc['ClaimTime'] > $rd['FinishTime'])
        $rd['FinishTime'] = $rc['ClaimTime'];

    if ($rd['OdoRallyStart'] == 0)
        $rd['OdoRallyStart'] = $rc['OdoReading'];

    /**
     * Automatic updating of final odo reading below.
     * 
     * If the flag is false then an erroneously large odo reading will result in erroneously large CorrectedMiles.
     * If the flag is true then recalculating after final readings are manually entered will overwrite those final readings.
     */
    if (false || $rc['OdoReading'] > $rd['OdoRallyFinish']) {
        $rd['OdoRallyFinish'] = $rc['OdoReading'];
        $rd['CorrectedMiles'] = calcCorrectedMiles($rd['OdoKms'],$rd['OdoRallyStart'],$rd['OdoRallyFinish'],$rd['OdoScaleFactor']);
    }


	$sql = "UPDATE entrants SET BonusesVisited='".implode(',',$bv)."', Confirmed=".$KONSTANTS['ScorecardIsDirty'];
    if ($resetTeamHappiness) {
        $sql .= ",ReviewedByTeam=0";
    }
	$sql .= ",RejectedClaims='".implode(',',$rcd)."'";
	$sql .= ",StartTime='".$rd['StartTime']."'";
	$sql .= ",OdoRallyStart=".$rd['OdoRallyStart'];
	$sql .= ",FinishTime='".$rd['FinishTime']."',OdoRallyFinish=".$rd['OdoRallyFinish'];
	$sql .= ",CorrectedMiles=".$rd['CorrectedMiles'];
	$sql .= " WHERE EntrantID=".$rc['EntrantID'];
	$DB->exec($sql);
	if ($DB->lastErrorCode()<>0) {
		//echo("SQL ERROR: ".$DB->lastErrorMsg().'<hr>'.$sql.'<hr>');
		$DB->exec('ROLLBACK');
		exit;
	}


    $sql = "UPDATE claims SET Applied=1 WHERE ";
    $sql .= "rowid=".$claimid;
    $DB->exec($sql);
    if ($DB->lastErrorCode()<>0) {
        //echo("SQL ERROR: ".$DB->lastErrorMsg().'<hr>'.$sql.'<hr>');
        $DB->exec('ROLLBACK');
        exit;
    }

    if (true) {
        recalcScorecard($rc['EntrantID'],true);
    }


	if (!$intransaction)
		$DB->exec("COMMIT TRANSACTION");
}

function calcAvgSpeed($rd) {

    global $KONSTANTS;

	$basicKms = $KONSTANTS['BasicDistanceUnit'];
	$isoStart = $rd['StartTime'].'Z';
	$isoFinish = $rd['FinishTime'].'Z';
    try {
        error_log('Parsing starttime of ['.$isoStart.']');
	    $dtStart = new DateTime($isoStart);
    } catch(Exception $ex) {
        error_log('Starttime ['.$isoStart.'] cannot be parsed - '.$ex->getMessage());
        //echo('Starttime ['.$isoStart.'] cannot be parsed - '.$ex->getMessage());
        return;
    }
    try {
        error_log('Parsing finishtime of ['.$isoFinish.']');
	    $dtFinish = new DateTime($isoFinish);
    } catch(Exception $ex) {
        error_log('Finishtime ['.$isoFinish.'] cannot be parsed - '.$ex->getMessage());
        //echo('Finishtime ['.$isoFinish.'] cannot be parsed - '.$ex->getMessage());
        return;
    } 
	$rideDuration = $dtFinish->diff($dtStart);
    $minsDuration = ($rideDuration->days * 24 * 60) + ($rideDuration->h * 60) + $rideDuration->i;
    $restMins = $rd['RestMinutes'];
    error_log('Duration mins = '.$minsDuration.' rest mins = '.$restMins);
	
    $minsDuration = $minsDuration - $restMins;

	if ($minsDuration < 1)
		return '';

	$odoDistance = $rd['CorrectedMiles'];

	
	$hoursDuration = $minsDuration / 60.0;
	$speed = $odoDistance / $hoursDuration;
	
    return round($speed,2);

}


function ccTestFail($ccr) {

    global $RP, $KONSTANTS, $catlabels;

    $msg = ''; 
    $axis = $ccr->axis;
    $msg .= $RP['Cat'.$axis.'Label'].': ';
    $cat = $ccr->cat;
    $catx = $cat;
    $msg .= ' n';
    if ($cat == 0)
        $catx = '' ;
    else {
        $catx = '['.$catlabels[$ccr->axis][$ccr->cat].']';
    }
    $msg .= $catx;
    if ($ccr->triggered != $KONSTANTS['COMPULSORYBONUS']) {
        $msg .= '='.$ccr->value;
        $msg .= ' &lt; ';
    } else
        $msg .= ' &#8805; ';
    $msg .= $ccr->min;

    return $msg;
}


// This checks the various constraints on 'Finisher' status and applies the appropriate
// status (Finisher / DNF) according to the rules of the rally.
function calcEntrantStatus($rd) {

    global $scorex, $bonusValues, $specialValues, $comboValues, $catCompoundRules, $TAGS,$KONSTANTS, $RP;

    $sx = new SCOREXLINE();
    $sx->id = $TAGS['EntrantDNF'][0];

    $spp = calcSpeedPenalty(true,$rd['AvgSpeed']);
    error_log('spp0='.$spp[0].' spp1='.$spp[1]);
    if ($spp[0]) {
        $sx->desc = $rd['AvgSpeed'].$RP['speedLabel'].' > '.$spp[1];
        $scorex[] = $sx;
        return $KONSTANTS['EntrantDNF'];
    }

    if ($RP['MilesKms'] != 0)
        $bdu = $TAGS['OdoKmsK'][0];
    else
        $bdu = $TAGS['OdoKmsM'][0];


    if ($rd['CorrectedMiles'] < $RP['MinMiles']) {
        $sx->desc = $bdu.' < '.$RP['MinMiles']; //$KONSTANTS['DNF_TOOFEWMILES'];
        $scorex[] = $sx;
        return $KONSTANTS['EntrantDNF'];
    }
    if ($rd['CorrectedMiles'] > $RP['PenaltyMilesDNF']  && $RP['PenaltyMilesDNF'] > 0) {
        $sx->desc = $bdu.' > '.$RP['PenaltyMilesDNF']; //$KONSTANTS['DNF_TOOMANYMILES'];
        $scorex[] = $sx;
        return $KONSTANTS['EntrantDNF'];
    }
    
    // Check for finish time DNF        
    $ft = calcFinishTimeDNF($rd);
    if (getSetting('autoLateDNF','false')=='true' && $rd['FinishTime'] > $ft) {
        if (substr($rd['FinishTime'],0,10) == substr($ft,0,10))
            $sx->desc = substr($rd['FinishTime'],11).' > '.substr($ft,11);
        else
            $sx->desc = str_replace('T',' ',$rd['FinishTime']).' > '.str_replace('T',' ',$ft);
    
        //$sx->desc = $KONSTANTS['DNF_FINISHEDTOOLATE'].' > '.str_replace('T',' ',$ft);
        $scorex[] = $sx;
        return $KONSTANTS['EntrantDNF'];
    }

    foreach ($bonusValues as $b) {
        if ($b->compulsory == $KONSTANTS['COMPULSORYBONUS']  && !$b->scored) {
            $sx->desc = $KONSTANTS['DNF_MISSEDCOMPULSORY'].$b->desc.' [ '.$b->bid.' ]';
            $scorex[] = $sx;
            return $KONSTANTS['EntrantDNF'];
        } else if ($b->compulsory == $KONSTANTS['MUSTNOTMATCH']  && $b->scored) {
            $sx->desc = $KONSTANTS['DNF_HITMUSTNOT'].$b->desc.' [ '.$b->bid.' ]';
            $scorex[] = $sx;
            return $KONSTANTS['EntrantDNF'];
        }
    }

    foreach ($specialValues as $b) {
        if ($b->compulsory && !$b->scored) {
            $sx->desc = $KONSTANTS['DNF_MISSEDCOMPULSORY'].$b->desc.' [ '.$b->bid.' ]';
            $scorex[] = $sx;
            return $KONSTANTS['EntrantDNF'];
        }
    }

    foreach ($comboValues as $b) {
        if ($b->compulsory && !$b->scored) {
            $sx->desc = $KONSTANTS['DNF_MISSEDCOMPULSORY'].$b->desc.' [ '.$b->cid.' ]';
            $scorex[] = $sx;
            return $KONSTANTS['EntrantDNF'];
        }
    }

    foreach ($catCompoundRules as $ccr) {
        if ($ccr->rtype == $KONSTANTS['COMPULSORYBONUS'] && !$ccr->triggered) {
            $sx->desc = ccTestFail($ccr);
            $scorex[] = $sx;
            return $KONSTANTS['EntrantDNF'];
        }
        if ($ccr->rtype == $KONSTANTS['MUSTNOTMATCH'] && $ccr->triggered) {
            $sx->desc = ccTestFail($ccr);
            $scorex[] = $sx;
            return $KONSTANTS['EntrantDNF'];
        }
    }
    if ($rd['TotalPoints'] < $RP['MinPoints']) {
        $sx->desc = getSetting('DNF_TOOFEWPOINTS',$KONSTANTS['DNF_TOOFEWPOINTS']).' < '.$RP['MinPoints'];
        $scorex[] = $sx;
        return $KONSTANTS['EntrantDNF'];
    }

    if (getSetting('autoFinisher','false') != 'true')
        return $rd['EntrantStatus'];
    
    return $KONSTANTS['EntrantFinisher'];

}

// The DNF time for an entrant is the earlier of his start time + max hours or the rally end time
function calcFinishTimeDNF($rd) {

    global $RP, $KONSTANTS;

//    print_r($RP);
//    echo('<hr>');
    //print_r($rd);
//    echo('<hr>');
    $startTime = ($rd['StartTime'] < $RP['StartTime'] ? $RP['StartTime'] : $rd['StartTime']);
    $dt = new DateTime($startTime,new DateTimeZone($KONSTANTS['LocalTZ']));
    $dt->add(new DateInterval("PT".$RP['MaxHours']."H"));
    $finishTime = substr($dt->format("c"),0,16);
    if ($finishTime > $RP['FinishTime'])
        $finishTime = $RP['FinishTime'];
    return $finishTime;

}

function calcMileagePenalty($correctedMiles) {

    global $KONSTANTS, $RP;

	$CM = $correctedMiles;
	$PMM = $RP['PenaltyMaxMiles'];
	$PMMethod = $RP['MaxMilesMethod'];
	$PMPoints = $RP['MaxMilesPoints'];
	$PenaltyMiles = $CM - $PMM;
	
	if ($PenaltyMiles <= 0) // No penalty
		return [0,0,$PMM,$PenaltyMiles]; 
		
	error_log("calcMileagePenalty returning penalty");
	switch ($PMMethod) 	{
		case $KONSTANTS['MMM_PointsPerMile']:
			return [0 - $PMPoints * $PenaltyMiles,0,$PMM,$PenaltyMiles];
		case $KONSTANTS['MMM_Multipliers']:
			return [0,0 - $PMPoints,$PMM,$PenaltyMiles];
		default:
			return [0 - $PMPoints,0,$PMM,$PenaltyMiles];
	}
		
}

function calcSpeedPenalty($dnf,$AvgSpeed)
/*
 * If parameter dnf is false then
 * This will return the number of penalty points (not multipliers) or 0
 * If highest match gives DNF, I return 0
 *
 * If parameter dnf is true then
 * If highest match give DNF, return true otherwise false
 *
 */
{
    global $RP, $KONSTANTS, $speedPenalties;

	$speed = floatval($AvgSpeed);
	error_log('Checking '.$speed.' against '.sizeof($speedPenalties).' speed penalty records');
	foreach ($speedPenalties as $SP) {
		if ($speed >= floatval($SP->MinSpeed))	{
			error_log('Matched '.$speed.' to '.$SP->MinSpeed);
			if (intval($SP->PenaltyType)==1) {
				if ($dnf)
					return [1,$SP->MinSpeed];
				else
					return [0,$SP->MinSpeed]; /* Penalty points */
			}
			if ($dnf)
				return [0,$SP->MinSpeed];
			else {
                error_log('Returning points '.$SP->value);
				return [0 - $SP->value,$SP->MinSpeed];
            }
		}
    }
	return [0,0];
}



function calcTimePenalty($STDate,$FTDate) {

    global $timePenalties, $KONSTANTS, $RP;

	$OneMinute = 1000 * 60;


    // Start/finish times
    if (is_null($STDate)) {
        $STDate = $RP['StartTime'];
    }

    $mtDNF = DateTime::createFromFormat('Y\-m\-d\TH\:i',$STDate);
    try {
        $mtDNF = date_add($mtDNF,new DateInterval("PT".$RP['MaxHours']."H"));
    } catch(Exception $e) {
        echo('omg! '.$e->getMessage());
    }
    $myTimeDNF = joinDateTime(date_format($mtDNF,'Y-m-d'),date_format($mtDNF,'H:i'));
    if ($RP['FinishTime'] < $myTimeDNF)
        $myTimeDNF = $RP['FinishTime'];

    error_log('myTimeDNF is '.$myTimeDNF.' FTDate is '.$FTDate);

    $EntrantFinishTime = new DateTime($FTDate);

    foreach ($timePenalties as $TP) {
        switch($TP->spec) {

            // Do I need 'Z'?
            case $KONSTANTS['TimeSpecRallyDNF']:
                $dnf = new DateTime($RP['FinishTime']);
                $ds = date_sub($dnf,new DateInterval("PT".$TP->start."i"));
                $de = date_sub($dnf,new DateInterval("PT".$TP->end."i"));
                break;
            case $KONSTANTS['TimeSpecEntrantDNF']:
                $dnf = new DateTime($myTimeDNF);
                $ds = date_sub($dnf,new DateInterval("PT".$TP->start."i"));
                $de = date_sub($dnf,new DateInterval("PT".$TP->end."i"));
                break;
            default:
                $ds = new DateTime($TP->start);
                $de = new DateTime($TP->end);
        }
        if ($EntrantFinishTime >= $ds && $EntrantFinishTime <= $de) {
            $PF = $TP->factor;
            $PM = $TP->method;
            $PStartDate = $ds;
            $xs = date_diff($EntrantFinishTime,$PStartDate,TRUE);
            $Mins = intval($xs->format("%i")) + 1;
            $DTx = date_format($ds,'Y-m-d H:i');
            switch($PM) {

                case $KONSTANTS['TPM_MultPerMin']:
                    return [0,0 - $PF * $Mins,$DTx,$FTDate];
                case $KONSTANTS['TPM_PointsPerMin']:
                    return [0 - $PF * $Mins,0,$DTx,$FTDate];
                case $KONSTANTS['TPM_FixedMult']:
                    return [0,0 - $PF,$DTx,$FTDate];
                default:
                    return [0 - $PF,0,$DTx,$FTDate];
            }
        }
    }
  	return [0,0,0,0];

}







function catFieldList() {

    global $KONSTANTS;

    $res = '';
    for ($i = 1; $i <= $KONSTANTS['NUMBER_OF_COMPOUND_AXES']; $i++) {
        $res .= ',Cat'.$i;
    }
    return $res;

}

function chooseNZ($i,$j) {

    if ($i == 0)
        return $j;
    else
        return $i;

}

function countNZ($cats) {

    $res = 0;
    foreach($cats as $c)
        if ($c > 0)
            $res++;
    return $res;
}

function crewName($rd) {

    $p = str_replace(' ','&nbsp',trim($rd['PillionName']));
    $r = str_replace(' ','&nbsp;',trim($rd['RiderName']));
    if ($p != '')
        $r .= ' &amp; '.$p;
    return '#'.$rd['EntrantID'].'&nbsp;-&nbsp;'.$r;
    
}

function teamNames($team) {

    global $DB;

    $sql = "SELECT EntrantID,RiderName,PillionName FROM entrants WHERE TeamID=$team";
    $R = $DB->query($sql);
    $res = '';
    while ($rd = $R->fetchArray()) {
        if ($res != '')
            $res .= ' + ';
        $res .= crewName($rd).'<br>';
    }
    return $res;
}
function debugBonuses() {

    global $bonusValues;

    foreach($bonusValues as $b) {
        echo(' Bonus: ');
        print_r($b);
        echo('<br>');
    }

}

function debugCatcounts($catcounts) {

    global $KONSTANTS;

    echo('<hr>');
    for ($i = 0; $i <= $KONSTANTS['NUMBER_OF_COMPOUND_AXES']; $i++) {
        echo("$i : ");
        var_dump($catcounts[$i]);
        echo('<br>');
    }
    echo('<hr>');
}

function debugCCR() {

    global $catCompoundRules;

    foreach($catCompoundRules as $c) {
        echo(' CCR: ');
        print_r($c);
        echo('<br>');
    }

}

function debugCombos() {

    global $comboValues;

    echo('<br>');
    foreach($comboValues as $c) {
        echo(' Combo: ');
        print_r($c);
        echo('<br>');
    }

}

function debugTimePenalties() {

    global $timePenalties;

    echo('<br>');
    foreach($timePenalties as $t) {
        echo(' TP: ');
        print_r($t);
        echo('<br>');
    }
}

// This recalculates all dirty scorecards (or all scorecards if forced)
function recalcAll($force) {

    global $KONSTANTS, $DB, $TAGS;


    startHtml($TAGS['cl_ClaimsTitle'][0]);

	echo('<h3>'.$TAGS['cl_RecalcHdr'][0].'</h3>');



    $sql = "SELECT EntrantID,Confirmed FROM entrants";
    $R = $DB->query($sql);
    $scorecards = [];
    while ($rd = $R->fetchArray()) {
        if ($rd['Confirmed'] == $KONSTANTS['ScorecardIsDirty'] || $force) 
            $scorecards[] = $rd['EntrantID'];
    }
    foreach ($scorecards as $sc) {
        echo('<br>'.$TAGS['cl_Recalculating'][0].' '.$sc.' ...');
        recalcScorecard($sc,false);
    }
        
}

function initRallyVariables() {

    global $RP, $catlabels, $axisLabels, $reasons, $comboValues, $specialValues, $bonusValues,$timePenalties,$speedPenalties,$catCompoundRules, $sgroups, $KONSTANTS, $DB, $TAGS;

    if (isset($RP['RallyTitle']))
        return;

    //echo('Initializing $RP ...');

    $R = $DB->query("SELECT * FROM rallyparams");
    if ($RP = $R->fetchArray()) {
        $rr = explode("\n",$RP['RejectReasons']);
        foreach($rr as $rc) {
            $rcp = explode("=",$rc);
            if (count($rcp) != 2)
                continue;
            $reasons[$rcp[0]] = $rcp[1];
        }
        
        for ($i = 1; $i <= $KONSTANTS['NUMBER_OF_COMPOUND_AXES']; $i++)
            $axisLabels[$i] = $RP['Cat'.$i.'Label'];
    }

    $RP['speedLabel'] = ($KONSTANTS['BasicDistanceUnit']==$KONSTANTS['DistanceIsMiles'] ? ' mph' : ' km/h');

//    echo(' Reasons ');
//    print_r($reasons);
//    echo('<br>');

    $R = $DB->query("SELECT * FROM categories");
    while ($rd = $R->fetchArray()) {
        if (!isset($catlabels[$rd['Axis']]))
            $catlabels[$rd['Axis']] = [];
        $catlabels[ $rd['Axis']] [$rd['Cat']]  = $rd['BriefDesc'];
    }

//    echo(' Cats ');
//    print_r($catlabels);
//    echo('<br>');

    $R = $DB->query("SELECT * FROM combinations WHERE Leg=0 OR Leg=".$RP['CurrentLeg']." ORDER BY ComboID");
    while($rd = $R->fetchArray()) {
        $cmb = new StdClass();
        $cmb->cid   = $rd['ComboID'];
        $cmb->desc  = $rd['BriefDesc'];
        $cmb->bids  = explode(',',$rd['Bonuses']);
        $cmb->pm    = $rd['ScoreMethod'];
        $cmb->min   = $rd['MinimumTicks'];
        $cmb->max   = count($cmb->bids);

        //echo('<br> [ '.$cmb->cid.' '.$rd['Bonuses'].' / '.$rd['ScorePoints'].' ] ');

        // Establish pts as an array of results depending on number of claims
        $cmb->pts   = array_pad([],$cmb->max,0);
        if ($cmb->min == 0 || $cmb->min == $cmb->max) {
            $cmb->pts[$cmb->max - 1] = intval($rd['ScorePoints']);
            $cmb->min = $cmb->max;
        } else {
            $pa = explode(',',$rd['ScorePoints']);
            $i = 0;
            for ($j = $cmb->min; $j <= $cmb->max; $j++) {
                if ( isset ( $pa[$i]))
                    $cmb->pts[$j] = $pa[$i++];
                else
                    $cmb->pts[$j] = $pa[$i - 1];
            }
        }
        $cmb->compulsory  = $rd['Compulsory'];
        for ($i = 1; $i <= $KONSTANTS['NUMBER_OF_COMPOUND_AXES']; $i++) {
            $cmb->cat[$i] = $rd['Cat'.$i];
        }
        $cmb->scored  = false;
        $comboValues[] = $cmb;

    }
    //debugCombos();

    $sql = "SELECT rowid AS id,Axis,Cat,NMethod,ModBonus,NMin,PointsMults,NPower,Ruletype";
    $sql .= " FROM catcompound WHERE Leg=0 OR Leg=".$RP['CurrentLeg'];
    $sql .= " ORDER BY Axis,NMin DESC";
    $R = $DB->query($sql);
	while ($rd = $R->fetchArray()) {
        $ccr = new StdClass();
        $ccr->rid   = $rd['id'];
        $ccr->axis  = $rd['Axis'];
        $ccr->cat   = $rd['Cat'];
        $ccr->method= $rd['NMethod'];
        $ccr->target= $rd['ModBonus'];
        $ccr->value = 0;
        $ccr->min   = $rd['NMin'];
        $ccr->pm    = $rd['PointsMults'];
        $ccr->pwr   = $rd['NPower'];
        $ccr->rtype = $rd['Ruletype'];
        $ccr->triggered = false; // Triggered(1)
        $catCompoundRules[] = $ccr;
    }
//    debugCCR();

    $cats = catFieldList();



    $sql = "SELECT BonusID,BriefDesc,Compulsory,Points,RestMinutes";
    $sql .= $cats;
    $sql .= " FROM bonuses WHERE Leg=0 OR Leg=".$RP['CurrentLeg'];
    $sql .= " ORDER BY BonusID";
	$R = $DB->query($sql);
    while ($rd = $R->fetchArray()) {
        $bon = new StdClass();
        $bon->bid   = $rd['BonusID'];
        $bon->desc  = $rd['BriefDesc'];
        $bon->compulsory  = $rd['Compulsory'];
        $bon->pts   = $rd['Points'];
        for ($i = 1; $i <= $KONSTANTS['NUMBER_OF_COMPOUND_AXES']; $i++) {
            $bon->cat[$i] = $rd['Cat'.$i];
        }
        $bon->mins  = $rd['RestMinutes'];
        $bon->scored  = false;    // is it scored?
        $bonusValues[] = $bon;
    }
//    debugBonuses();

//	Time penalties

    $sql = "SELECT rowid AS id,TimeSpec";
    $sql .= ",PenaltyStart,PenaltyFinish,PenaltyMethod,PenaltyFactor";
    $sql .= " FROM timepenalties";
    $sql .= " WHERE (Leg = 0 OR Leg = ".$RP['CurrentLeg'].")";
    $sql .= " ORDER BY PenaltyStart,PenaltyFinish";
    $R = $DB->query($sql);
    
    while ($rd = $R->fetchArray()) {
        $timp = new StdClass();
        $timp->spec = $rd['TimeSpec'];
        $timp->start = $rd['PenaltyStart'];
        $timp->end = $rd['PenaltyFinish'];
        $timp->factor = $rd['PenaltyFactor'];
        $timp->method = $rd['PenaltyMethod'];
        $timePenalties[] = $timp;
    }

    //debugTimePenalties();

// Speed penalties

    $sql = "SELECT Basis,MinSpeed,PenaltyType,PenaltyPoints";
    $sql .= " FROM speedpenalties WHERE Leg=0 OR Leg=".$RP['CurrentLeg']." ORDER BY MinSpeed DESC";
    $R = $DB->query($sql);

    while ($rd = $R->fetchArray()) {
        $spd = new StdClass();
        $spd->Basis = $rd['Basis'];
        $spd->MinSpeed = $rd['MinSpeed'];
        $spd->PenaltyType = $rd['PenaltyType'];
        $spd->value = $rd['PenaltyPoints'];
        $speedPenalties[] = $spd;
    }

}

function initScorecardVariables() {

    global $scorex, $comboValues, $bonusValues, $specialValues, $catCompoundRules,$DB,$KONSTANTS;

    $scorex = [];

    for ($i = 0; $i <= $KONSTANTS['NUMBER_OF_COMPOUND_AXES']; $i++) {
        
        $catcounts[$i] = new StdClass();
        $catcounts[$i]->catcounts = [];
        $catcounts[$i]->samecount = 0;
        $catcounts[$i]->samepoints = 0;
        $catcounts[$i]->lastcat = -1;

    }

    foreach($catCompoundRules as $c)
        $c->triggered = false;

    foreach($bonusValues as $b)
        $b->scored = false;

    foreach($specialValues as $s)
        $s->scored = false;

    foreach($comboValues as $c)
        $c->scored = false;

    return $catcounts;
    
}


function updateCatcounts($bonus,$catcounts,$points) {

    global $KONSTANTS;

    // Keep track of cat counts
    for ($i = 1; $i <= $KONSTANTS['NUMBER_OF_COMPOUND_AXES']; $i++) {
        $cat = $bonus->cat[$i];

        if ($cat == 0) {
            $catcounts[$i]->samecount = 0;
            $catcounts[$i]->samepoints = 0;
            $catcounts[$i]->lastcat = $cat;
        } else if ($cat == $catcounts[$i]->lastcat) {
            $catcounts[$i]->samecount++;
            $catcounts[$i]->samepoints += $points;
        } else {
            $catcounts[$i]->samecount = 1;
            $catcounts[$i]->samepoints = $points;
            $catcounts[$i]->lastcat = $cat;
        }

        if ($cat < 1) 
            continue;
            
        if (!isset($catcounts[$i]->catcounts[$cat]))
            $catcounts[$i]->catcounts[$cat] = 1;
        else
            $catcounts[$i]->catcounts[$cat]++;
        // and overall figures
        if (!isset($catcounts[0]->catcounts[$cat]))
            $catcounts[0]->catcounts[$cat] = 1;
        else
            $catcounts[0]->catcounts[$cat]++;
    }
    return $catcounts;
}

function formatRestMinutes($minutes) {

    if ($minutes < 1)
        return '0';
    $h = intdiv($minutes ,60);
    $m = $minutes % 60;
    
    if ($h > 1 && $m == 0)
        return $h." hrs";
    if ($h == 1 && $m == 0)
        return '60 mins';
    if ($h > 0)
        return $h.'h '.$m.'m';
    if ($m == 1)
        return '1 min';
    return $m.' mins';
}

function checkApplySequences($bonv,$catcounts,$bonusPoints) {

    global $evs, $RP, $catlabels, $axisLabels, $scorex, $reasons, $bonusValues, $specialValues, $comboValues, $catCompoundRules, $DB, $KONSTANTS, $TAGS;


    $extraBonusPoints = 0;

    // Look for and apply sequence mods
    foreach($catCompoundRules as $ccr) {
        if ($ccr->rtype != $KONSTANTS['CAT_OrdinaryScoringSequence'])
            continue;


        if ($bonv != '') { // is there a current bonus or are we done.
            error_log("Testing CCR sequence BC=".$bonv->cat[$ccr->axis].' LC='.$catcounts[$ccr->axis]->lastcat.' SC='.$catcounts[$ccr->axis]->samecount.' Min='.$ccr->min);
            if ($catcounts[$ccr->axis]->lastcat == $bonv->cat[$ccr->axis]) {
                continue; // still building sequence. wait until it's built.
            }
        }
    


        if ($catcounts[$ccr->axis]->samecount < $ccr->min) {
            continue;
        }
        // Now trigger sequential bonus

        
            
        //'&#x2713; == checkmark
//        $bonusDesc = '&#x2713; '.$catlabels[$ccr->axis][$catcounts[$ccr->axis]->lastcat]. " x ".$ccr->min;
        $bonusDesc = $catlabels[$ccr->axis][$catcounts[$ccr->axis]->lastcat]. "(&#8752;&ge;".$ccr->min.")&#x2713;";
        if ($catcounts[$ccr->axis]->samecount > $ccr->min) {
            $bonusDesc .= '+';
        }
        $pointsDesc = '';
        if ($ccr->pm == $KONSTANTS['CAT_ResultPoints']) {
            $extraBonusPoints = $ccr->pwr;
        } else {
            $extraBonusPoints = $catcounts[$ccr->axis]->samepoints * $ccr->pwr;
            if ($ccr->pwr != 1 && $ccr->pwr != 0) {
                $pointsDesc = " (+ ".$catcounts[$ccr->axis]->samepoints;            
                $pointsDesc .= "x".$ccr->pwr. ")";
            }
        }

        error_log('Sequence: bp='.$bonusPoints.' xbp='.$extraBonusPoints);
        $bonusPoints += $extraBonusPoints;

        $sx = new SCOREXLINE();
        $sx->desc = $bonusDesc;
        $sx->pointsDesc = $pointsDesc;
        $sx->points = $extraBonusPoints;
        $sx->totalPoints = $bonusPoints;
    
        $scorex[] = $sx;
    
    

        break;  // Only apply the first matching rule

            
    }

    return $extraBonusPoints;


}




// This recalculates a single scorecard and updates the entrant record with the results.
//
// Algorithm v3.0
//
// The values calculated are:-
//
// TotalPoints      The final score after all points and multipliers applied
// RestMinutes      The total value of rest accrued
// CombosTicked     Which combination bonuses have been scored
// EntrantStatus    Finisher or DNF status
// ScoreX           The score explanation
// numBonusesTicked Reserved for future use
//
// Values on which this routine depends from the entrant record:-
//
// CorrectedMiles   The distance travelled
// StartTime        The start time
// FinishTime       The finish or latest claim time
// BonusesVisited   List of ordinary bonuses visited, whether scored or not
// RejectedClaims   List of claims that have been rejected in scoring

function recalcScorecard($entrant,$intransaction) {

    global $evs, $RP, $catlabels, $axisLabels, $scorex, $reasons, $bonusValues, $specialValues, $comboValues, $catCompoundRules, $DB, $KONSTANTS, $TAGS;


    initRallyVariables();


    // First, initialize some variables
    $catcounts = initScorecardVariables();

    // Now fetch the base data
    $sql = "SELECT * FROM entrants WHERE EntrantID=$entrant";
    $R = $DB->query($sql);
    if (!$rd = $R->fetchArray()) {
        echo('FAILED<br>');
        return;
    }

    error_log('recalcScorecard for '.$entrant);

    // Rejected claims
    $rejectedClaims = [];
    if ($rd['RejectedClaims'] != '') {
        error_log("Exploding rejectedClaims [".$rd['RejectedClaims'].']');
        $tmp = explode(',',$rd['RejectedClaims']);
        foreach ($tmp as $r) {
            // Format is (code)=(reason)
            $e = strpos($r,'=');
            if ($e != false) {
                $rc = intval(substr($r,$e + 1));
                $rejectedClaims[substr($r,0,$e)] = $rc;
            }
        }
    }

    error_log(' RejectedClaims has '.count($rejectedClaims));
    //print_r($rejectedClaims);
    //exit;

    $bonusPoints = 0;
    $restMinutes = 0;
    $multipliers = 1;
    $numBonusesTicked = 0;
    $bonusesScored = [];    // Keeps track of ordinary, special and combo bonuses successfully claimed


    // Ordinary bonuses

    error_log("Bonuses visited = ".$rd['BonusesVisited']);
    $BA = explode(',',$rd['BonusesVisited']); 
        
    $lastBonusPointsValue = 0;
    $lastBPVMultiplier = 1;
    foreach($BA as $bonus) {

        if ($bonus == '')
            continue;

        $bon = ''; $points = ''; $minutes = ''; $xp = false; $pp = false;
        parseBonusClaim($bonus,$bon,$points,$minutes,$xp,$pp);

        $lastBonusMultiplier = strpos($bonus,"?")>0;
        if ($lastBonusMultiplier) {
            $n = strpos($bonus,"?") + 1;
            $m = strpos($bonus,";",$n);
            $lastBPVMultiplier = intval(substr($bonus,$n,$m-$n));
        }

        error_log('PBC:='.$bonus.': '.$bon.', '.$points.', '.$minutes.', '.$lastBonusPointsValue);

        $bonv = new stdClass(); // avoid intellisense warning
        foreach($bonusValues as $bv) {
            if ($bv->bid == $bon && strlen($bv->bid) == strlen($bon)) {
                error_log("'".$bv->bid."' == '".$bon."'");
                $bv->scored = true;
                $bonv = $bv;
                break;
            }
        }
        if (count((array)$bonv) == 0) {// non-existent bonus!
            error_log("Bloody non-existent bonus!");
            $sx = new SCOREXLINE();
            $sx->id = $bon;
            $sx->desc = $TAGS['clg_BadBonus'][1].'<br>'.$KONSTANTS['CLAIM_REJECTED'].' - '.$reasons[$rejectedClaims[$bon]];
            $sx->pointsDesc = '';
            $sx->points = 'X';
            $sx->totalPoints = '';
            error_log('<table>'.$sx->asHTML().'</table>');
            $scorex[] = $sx;

            continue;
        }

        if ($points == '')
            $points = $bonv->pts;
        if ($minutes == '')
            $minutes = $bonv->mins;

        $bonusPoints += checkApplySequences($bonv,$catcounts,$bonusPoints);

        if ($bon == '' || array_key_exists($bon,$rejectedClaims) ) { // is it a rejected claim?

            error_log("Rejecting [$bon] (".array_key_exists($bon,$rejectedClaims).")");
            // Zap the sequence then
            for ($i = 1; $i <= $KONSTANTS['NUMBER_OF_COMPOUND_AXES']; $i++) {
                $catcounts[$i]->samecount = 0;
                $catcounts[$i]->samepoints = 0;
                $catcounts[$i]->lastcat = -1;
            }

            //echo($TAGS['cl_Rejecting'][0].' '.$bon.' '.$bonv->bid);
            $sx = new SCOREXLINE();
            $sx->id = $bonv->bid;
            $sx->desc = $bonv->desc.'<br>'.$KONSTANTS['CLAIM_REJECTED'].' - '.$reasons[$rejectedClaims[$bon]];
            $sx->pointsDesc = '';
            $sx->points = 'X';
            $sx->totalPoints = '';
            error_log('<table>'.$sx->asHTML().'</table>');
            $scorex[] = $sx;
            continue;
        }
        

        $basicBonusPoints = $points;
        $bonusesScored[] = $bon;
        $numBonusesTicked++;
        $restMinutes += $minutes;
        $pointsDesc = "";
        if ($lastBonusMultiplier) {
            $pointsDesc .= " (".$lastBPVMultiplier." x ".$lastBonusPointsValue.")";
        }
        if ($minutes > 0) {
            $pointsDesc = ' ['.formatRestMinutes($minutes).']';
        }
        


        // Keep track of cat counts
        $catcounts = updateCatcounts($bonv,$catcounts,$basicBonusPoints);

        // Look for and apply cat mods to basic points
        foreach($catCompoundRules as $ccr) {
            if ($ccr->rtype != $KONSTANTS['CAT_OrdinaryScoringRule'])
                continue;

            if ($ccr->target != $KONSTANTS['CAT_ModifyBonusScore'])
                continue;   // Only interested in rules affecting basic bonus

            if ($ccr->pm != $KONSTANTS['CAT_ResultPoints']) // Multipliers not allowed at this level
                continue;

            if ($ccr->cat > 0)
                if ($bonv->cat[$ccr->axis] != $ccr->cat)
                    continue;
                    
            $catcount = 0;
            if ($ccr->cat == 0)
                foreach($catcounts[$ccr->axis]->catcounts as $cc)
                    $catcount += $cc;
            elseif (isset($catcounts[$ccr->axis]->catcounts[$ccr->cat]))
                $catcount = $catcounts[$ccr->axis]->catcounts[$ccr->cat];
        
            if ($catcount < $ccr->min)
                continue;
        
            if ($ccr->pwr == 0) {
                $pdx = "$basicBonusPoints x ".($catcount - 1);
                $basicBonusPoints = $basicBonusPoints * ($catcount - 1);
            } else {
                $pdx = "$basicBonusPoints x $ccr->pwr^".($catcount - 1);
                $basicBonusPoints = $basicBonusPoints * (pow($ccr->pwr,($catcount - 1)));
            }
            if ($pdx != "")
                $pointsDesc = $pointsDesc." ( $pdx )";

            //echo(" BonusMod $catcount = $basicBonusPoints<br>");

            break;  // Only apply the first matching rule
            
        }

        if ($xp) 
            $pointsDesc .= ' &#8224;';
        if ($pp)
            $pointsDesc .= ' &#10016;';


        // basicBonusPoints is now the live figure
        $bonusPoints += $basicBonusPoints;    

        $sx = new SCOREXLINE();
        $sx->id = $bonv->bid;
        $sx->desc = $bonv->desc;
        $sx->pointsDesc = $pointsDesc;
        $sx->points = $basicBonusPoints;
        $sx->totalPoints = $bonusPoints;

        $scorex[] = $sx;

        $lastBonusPointsValue = $points;
        if (array_key_exists($bon,$rejectedClaims)) {
            $lastBonusPointsValue = 0;
        }

        if ($lastBonusMultiplier) {
            $lastBonusPointsValue = 0;
        }



    } // Ordinary bonus loop


    $bonusPoints += checkApplySequences('',$catcounts,$bonusPoints);


    // Combos
    $combosScored = [];
    foreach($comboValues as $c) {

        // Is this combo already marked as rejected, a necessarily manual act?
        if (array_key_exists($c->cid,$rejectedClaims)) {
            $sx = new SCOREXLINE();
            $sx->id = $c->cid;
            $sx->desc = $c->desc.'<br>'.$KONSTANTS['CLAIM_REJECTED'].' - '.$reasons[$rejectedClaims[$c->cid]];
            $sx->pointsDesc = '';
            $sx->points = 'X';
            $sx->totalPoints = '';
            $scorex[] = $sx;
            continue;
        }
    
        $numbids = 0;
        foreach($c->bids as $b) 
            if (in_array($b,$bonusesScored))
                $numbids++;
        if ($numbids < $c->min)
            continue;

        $c->scored = true;
        $pointsDesc = "";
        if ($c->pm == $KONSTANTS['ComboScoreMethodMults']) {
            $mults = $c->pts[$numbids - 1];
            $basicBonusPoints = 0;
        } else {
            $basicBonusPoints = $c->pts[$numbids - 1];
            $mults = 0;
        }
        //echo(" BP=".$basicBonusPoints."; nb=".$numbids." === ");
        $bonusesScored[] = $c->cid;
        $combosScored[] = $c->cid;

        // Keep track of cat counts. Don't accrue same points for combos
        $catcounts = updateCatcounts($c,$catcounts,0);


        $bonusPoints += $basicBonusPoints;
        $multipliers += $mults;

        $sx = new SCOREXLINE();
        $sx->id = $c->cid;
        $sx->desc = $c->desc;
        if ($numbids < $c->max) {
            $sx->pointsDesc = " ( $numbids / $c->max ) ";
        }
        if ($c->pm == $KONSTANTS['ComboScoreMethodMults']) {
            $sx->points = "x $mults";
        } else {
            $sx->points = $basicBonusPoints;
        }
        $sx->totalPoints = $bonusPoints;
        $scorex[] = $sx;

        //echo("C:  $c->cid - $c->desc$pointsDesc = $basicBonusPoints = $bonusPoints<br>");

    }

    $rd['CombosTicked'] = implode(',',$combosScored);

    //debugCombos();

    //debugCatcounts($catcounts);

    //                      CALCULATE AXIS SCORES

    $nzAxisCounts = [];
    for ($i = 1; $i <= $KONSTANTS['NUMBER_OF_COMPOUND_AXES']; $i++) 
        $nzAxisCounts[$i] = countNZ($catcounts[$i]->catcounts);


    // First rules for number of non-zero cats

    $lastAxis = -1;
    $lastmin = '';
    foreach($catCompoundRules as $ccr) {

        if ($ccr->rtype == $KONSTANTS['CAT_OrdinaryScoringSequence'])
            continue;

        if ($ccr->method != $KONSTANTS['CAT_NumNZCatsPerAxisMethod'] || $ccr->target == $KONSTANTS['CAT_ModifyBonusScore']) 
            continue;

        if ($ccr->axis <= $lastAxis) // Process each axis only once
            continue;

        $nzCount = 0;
        if ($ccr->axis > 0)
            $nzCount = $nzAxisCounts[$ccr->axis];
        else
            for ($i = 1; $i <= $KONSTANTS['NUMBER_OF_COMPOUND_AXES']; $i++) 
                $nzCount += $nzAxisCounts[$i];

        $ccr->value = $nzCount;
        if ($nzCount < $ccr->min) {
            $lastmin = $ccr->min;
            continue;
        }

        // Let's apply this rule then

        $lastAxis = $ccr->axis;
        $ccr->triggered = true;

        $points = chooseNZ($ccr->pwr,$nzCount);
        $pbx = '';
        if ($ccr->rtype == $KONSTANTS['CAT_DNF_Unless_Triggered']) { // DNF type condition
            $points = '&#x2713;';//checkmark
        } elseif ($ccr->rtype == $KONSTANTS['CAT_DNF_If_Triggered']) {
            $points = $TAGS['EntrantDNF'][0];
        } elseif ($ccr->rtype == $KONSTANTS['CAT_PlaceholderRule'] ) {
            continue;
        } else if ($ccr->pm == $KONSTANTS['CAT_ResultPoints']) {
            $bonusPoints += $points;
        } else { // multipliers
            $multipliers += ($points - 1);
            //$points = "x $points";
            $pbx = 'x ';
        }

        $sx = new SCOREXLINE();
        $sx->id = '';
        $sx->desc = $axisLabels[$ccr->axis].': <em>n</em>='.$nzCount;
        if ($ccr->cat != 0)
            $sx->desc .= '['.$catlabels[$ccr->axis][$ccr->cat].'] ';
        else
            $sx->desc .= ' ';
        if (intval($points) <= 0 && $lastmin != '')
            $sx->desc .= '&lt; '.$lastmin;
        else
            $sx->desc .= '&ge; '.$ccr->min;
        //$sx->desc .= $ccr->min;
        $sx->pointsDesc = "";
        $sx->points = $pbx.$points;
        $sx->totalPoints = $bonusPoints;
        $scorex[] = $sx;


    } // NZ axis

    // Secondly, rules for number of bonuses per cat

    $lastaxis = -1;
    $lastcat = -1;
    $lastmin = '';

    foreach($catCompoundRules as $ccr) {

        if ($ccr->rtype == $KONSTANTS['CAT_OrdinaryScoringSequence'])
            continue;

        if ($ccr->method != $KONSTANTS['CAT_NumBonusesPerCatMethod'] || $ccr->target == $KONSTANTS['CAT_ModifyBonusScore'])
            continue;
        if ($ccr->axis <= $lastaxis && $ccr->cat <= $lastcat)
            continue;

        $catcount = 0;
        if ($ccr->cat == 0)
            foreach($catcounts[$ccr->axis]->catcounts as $cc)
                $catcount += $cc;
        elseif (isset($catcounts[$ccr->axis]->catcounts[$ccr->cat]))
            $catcount = $catcounts[$ccr->axis]->catcounts[$ccr->cat];

        if ($catcount < $ccr->min) {
            $lastmin = $ccr->min;
            continue;
        }
        $ccr->triggered = true;
        if ($lastaxis < 0)
            $lastaxis = $ccr->axis;
        if ($ccr->axis > $lastaxis)
            $lastcat = -1;
        else
            $lastcat = $ccr->cat;
        $lastaxis = $ccr->axis;

        $pbx = '';
        if ($ccr->rtype == $KONSTANTS['CAT_DNF_Unless_Triggered']) { // DNF type condition
            $basicPoints = '&#x2713;'; //checkmark
        } elseif ($ccr->rtype == $KONSTANTS['CAT_DNF_If_Triggered']) {
            $basicPoints = $TAGS['EntrantDNF'][0];
        } elseif ($ccr->rtype == $KONSTANTS['CAT_PlaceholderRule'] ) {
            continue;
        } elseif ($ccr->pm == $KONSTANTS['CAT_ResultPoints']) {
            $basicPoints = chooseNZ($ccr->pwr,$catcount);
            $bonusPoints += $basicPoints;
            
            //echo("RC $basicPoints / $bonusPoints <br>");
        } else { // Multipliers then
            $mults = chooseNZ($ccr->pwr,$catcount);
            $multipliers += $mults;
            $basicPoints = $mults;
            $pbx = 'x ';
        }


        $sx = new SCOREXLINE();
        $sx->id = '';
        $sx->desc = $axisLabels[$ccr->axis].': <em>n</em>';
        if ($ccr->cat != 0)
            $sx->desc .= '['.$catlabels[$ccr->axis][$ccr->cat].']='.$catcount.' ';
        else
            $sx->desc .= ' ';
        if ($basicPoints <= 0 && $lastmin != '')
            $sx->desc .= '&lt; '.$lastmin;
        else
            $sx->desc .= '&#8805; '.$ccr->min;
        //$sx->desc .= $ccr->min;
        $sx->pointsDesc = "";
        $sx->points = $pbx.$basicPoints;
        $sx->totalPoints = $bonusPoints;
        $scorex[] = $sx;


    } // Bonus per cat axis


    if ($multipliers != 1) {
        $sx = new SCOREXLINE();
        $sx->desc = "= ".$bonusPoints.' x '.$multipliers;
        $sx->points = intval($bonusPoints * $multipliers);
        $sx->totalPoints = $sx->points;
        $scorex[] = $sx;
        $bonusPoints = $sx->points;
    }

    $sx = new SCOREXLINE();
    $scorex[] = $sx;

    $tp = calcTimePenalty($rd['StartTime'],$rd['FinishTime']);
    $tpP = $tp[0]; // Points
    $tpM = $tp[1] / 100; // Percentage

    $multDeduct = 0; // Might be used below
    if ($tpM != 0 || $tpP != 0) {
        if ($tpM != 0) {
            $multDeduct = intval($tpM * $bonusPoints);
            $bonusPoints += $multDeduct;
        }
        $bonusPoints += $tpP;
        $sx = new SCOREXLINE();
        if (substr($tp[2],0,10) == substr($tp[3],0,10) && substr($tp[3],11,5) >= substr($tp[2],11,5)) {
            $y = ''.substr($tp[3],11,5).' &#8805; '.substr($tp[2],11,5);
        } else {
            $y = ''.str_replace('T',' ',substr($tp[3],0,16)).' &#8805; '.str_replace('T',' ',$tp[2]);
        }
        $sx->desc = getSetting('RPT_TPenalty',$KONSTANTS['RPT_TPenalty']);
        $sx->pointsDesc = " $y";
        if ($tpM != 0) {
            $sx->points = ($tpM*100)."%";
        } else {
            $sx->points = $tpP;
        }
        $sx->totalPoints = $bonusPoints;
        $scorex[] = $sx;
        if ($tpM != 0) {
            $sx = new SCOREXLINE();
            $sx->pointsDesc = ($bonusPoints-$multDeduct)." x ".($tpM*100*-1)."% =";
            $sx->points = $multDeduct;
            $scorex[] = $sx;
        }
    }

    $tp = calcMileagePenalty($rd['CorrectedMiles']);
    $tpP = $tp[0]; // Points
    $tpM = $tp[1] / 100; // Percentage

    $multDeduct = 0;
    if ($tpM != 0 || $tpP != 0) {
        error_log('Applying MileagePenalty');
        if ($tpM !=0) {
            $multDeduct = intval($tpM * $bonusPoints);
            $bonusPoints += $multDeduct;
        }
        $bonusPoints += $tpP;
        $sx = new SCOREXLINE();
        //$sx->desc = getSetting('RPT_MPenalty',$KONSTANTS['RPT_MPenalty']); //."m=$tpM, $p=$tpP";
        if ($RP['MilesKms'] != 0)
            $bdu = $TAGS['OdoKmsK'][0];
        else
            $bdu = $TAGS['OdoKmsM'][0];
        $sx->desc = getSetting('RPT_MPenalty',$KONSTANTS['RPT_MPenalty']);
        $sx->pointsDesc = " ".$tp[3]." $bdu > ".$tp[2];
        if ($tpM != 0) {
            $sx->points = ($tpM*100)."%";
        } else {
            $sx->points = $tpP;
        }
        $sx->totalPoints = $bonusPoints;
        $scorex[] = $sx;
        if ($tpM != 0) {
            $sx = new SCOREXLINE();
            $sx->pointsDesc = ($bonusPoints-$multDeduct)." x ".($tpM*100*-1)."% =";
            $sx->points = $multDeduct;
            $scorex[] = $sx;
        }

    }

    $rd['RestMinutes'] = $restMinutes;
    $rd['AvgSpeed'] = calcAvgSpeed($rd);


    $spp = calcSpeedPenalty(false,$rd['AvgSpeed']);
    if ($spp[0] != 0) {
        $bonusPoints += $spp[0];
        $sx = new SCOREXLINE();
        $sx->id = getSetting('RPT_SPenalty',$KONSTANTS['RPT_SPenalty']);
        $sx->desc = $rd['AvgSpeed'].$RP['speedLabel'].' > '.$spp[1];
        $sx->points = $spp[0];
        $sx->totalPoints = $bonusPoints;
        $scorex[] = $sx;
    }



    $rd['TotalPoints'] = $bonusPoints;

    $sx = new SCOREXLINE();
    $sx->desc = $KONSTANTS['RPT_Total'];
    $sx->points = $bonusPoints;
    $scorex[] = $sx;
    $rd['EntrantStatus'] = calcEntrantStatus($rd);
    //echo(' TP='.$bonusPoints.' '.$rd['EntrantStatus']);
    //echo(' ok<br>');


    $rd['ScoreX'] = '<table class="sxtable">';
    $rd['ScoreX'] .= '<caption>';
    if ($rd['TeamID'] > 0) {
        $rd['ScoreX'] .= teamNames($rd['TeamID']);
    } else {
        $rd['ScoreX'] .= crewName($rd);
    }
    $rd['ScoreX'] .= ' [&nbsp;<span id="sxsfs">'.$evs[$rd['EntrantStatus']].'</span>&nbsp;]';

    if ($rd['CorrectedMiles'] > 0) {
        $bdutxt = ($KONSTANTS['BasicDistanceUnit']==$KONSTANTS['DistanceIsMiles'] ? $TAGS['OdoKmsM'][0] : $TAGS['OdoKmsK'][0]);
        $rd['ScoreX'] .= '<br><span class="explain">'.$rd['CorrectedMiles'].' '.$bdutxt;
        /**
        if ($rd['AvgSpeed'] != '')
            $rd['ScoreX'] .= ' @ '.$rd['AvgSpeed'].($KONSTANTS['BasicDistanceUnit']==$KONSTANTS['DistanceIsMiles'] ? ' mph' : ' km/h');
        **/
        $rd['ScoreX'] .= '</span>';
    }
    $rd['ScoreX'] .= '</caption><thead>';
    $rd['ScoreX'] .= '<tr><th class="sxcode"></th><th class="sxdesc"></th>';
    //$rd['ScoreX'] .= '<th class="sxdescx"></th>';
    $rd['ScoreX'] .= '<th class="sxitempoints"></th>';
    //$rd['ScoreX'] .= '<th class="sxtotalpoints">=</th>';
    $rd['ScoreX'] .= '</tr></thead><tbody>';

    foreach($scorex as $s)
        $rd['ScoreX'].=$s->asHTML();
    $rd['ScoreX'] .= '</tbody></table>';

//    echo($rd['ScoreX']);

    if (!$intransaction)
        if (!$DB->exec("BEGIN IMMEDIATE TRANSACTION"))
            return;
    $sql = "UPDATE entrants SET TotalPoints=".$rd['TotalPoints'];
    $sql .= ',EntrantStatus='.$rd['EntrantStatus'];
    $sql .= ",CombosTicked='".$rd['CombosTicked']."'";
    $sql .= ",ScoreX='".$DB->escapeString($rd['ScoreX'])."'";
    $sql .= ",Confirmed=".$KONSTANTS['ScorecardIsClean'];
    $sql .= ",BonusesVisited='".$rd['BonusesVisited']."'";
    $sql .= ",RejectedClaims='".$rd['RejectedClaims']."'";
    $sql .= ",CorrectedMiles=".$rd['CorrectedMiles'];
    $sql .= ",RestMinutes=".$rd['RestMinutes'];
    $sql .= ",AvgSpeed='".$rd['AvgSpeed']."'";
    if ($RP['TeamRanking'] == $KONSTANTS['RankTeamsCloning'] && $rd['TeamID'] > 0) {
        $sql .= " WHERE TeamID=".$rd['TeamID'];
    } else
        $sql .= " WHERE EntrantID=".$rd['EntrantID'];
    $DB->exec($sql);

    if (!$intransaction)
        $DB->exec("COMMIT");
}

if (isset($_REQUEST['recalc']))
    recalcAll($_REQUEST['recalc']=='all');

?>
