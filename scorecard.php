<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

/*
 * I B A U K   -   S C O R E M A S T E R
 *
 * I handle new format scorecards
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


$HOME_URL = "picklist.php";

require_once('common.php');
require_once('scoring.php');

$RP = [];



function crewName($rd) {

    $p = str_replace(' ','&nbsp',str_replace('"','&quot;',trim($rd['PillionName'])));
    $r = str_replace(' ','&nbsp;',str_replace('"','&quot;',trim($rd['RiderName'])));
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


function showRallytime($stamp) {
/* We're really only interested in the time of day and which of a few days it's on */

    global $RP;

    $rs = splitDatetime($RP['StartTime']);
    $rf = splitDatetime($RP['FinishTime']);
    if ($rs[0] == $rf[0]) // Single day rally
        $dtfmt = 'H:i';
    else
        $dtfmt = 'D H:i';
	try {
		$dt = new DateTime(''.$stamp);
		$dtf = $dt->format($dtfmt);
	} catch (Exception $e) {
		$dtf = $stamp;
	}
	if (strpos(''.$stamp,'+00:00') > 0)
		$dtf .= "z";
	return '<span title="'.$stamp.'">'.$dtf.'</span>';
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

function emitCatCompoundRules() {

    global $DB, $RP;

    $R = $DB->query("SELECT * FROM  catcompound WHERE Leg=0 OR Leg=".$RP['CurrentLeg']." ORDER BY Axis,NMin DESC");
    while ($rd = $R->fetchArray()) {
        echo('<input type="hidden" name="catCompoundRules" ');
        echo('data-nmethod="'.$rd['NMethod'].'" ');
        echo('data-ruletype="'.$rd['Ruletype'].'" ');
        echo('data-pm="'.$rd['PointsMults'].'" ');
        echo('data-target="'.$rd['ModBonus'].'" ');
        echo('data-min="'.$rd['NMin'].'" ');
        echo('data-pwr="'.$rd['NPower'].'" ');
        echo('data-cat="'.$rd['Cat'].'" ');
        echo('data-axis="'.$rd['Axis'].'" ');
        echo('>');
    }
}

function emitPenalties() {

    global $DB, $RP;
    
    //	Time penalties
		
    $sql = "SELECT rowid AS id,TimeSpec";
    $sql .= ",PenaltyStart,PenaltyFinish,PenaltyMethod,PenaltyFactor,Leg";
    $sql .= " FROM timepenalties";
    $sql .= " WHERE (Leg = 0 OR Leg = ".$RP['CurrentLeg'].")";
    $sql .= " ORDER BY PenaltyStart,PenaltyFinish";
	$R = $DB->query($sql);
	while ($rd = $R->fetchArray()) {
		echo('<input type="hidden" name="TimePenalty[]"');
        echo(' data-spec="'.$rd['TimeSpec'].'" data-start="'.$rd['PenaltyStart'].'" data-end="'.$rd['PenaltyFinish'].'"');
        echo(' data-factor="'.$rd['PenaltyFactor'].'" data-method="'.$rd['PenaltyMethod'].'">');
    }

    // Speed penalties

    $sql = "SELECT Basis,MinSpeed,PenaltyType,PenaltyPoints,Leg";
    $sql .= " FROM speedpenalties WHERE Leg=0 OR Leg=".$RP['CurrentLeg']." ORDER BY MinSpeed DESC";
    error_log($sql);
    $R = $DB->query($sql);
    while ($rd = $R->fetchArray()) {
        echo('<input type="hidden" name="SpeedPenalty[]" data-Basis="'.$rd['Basis'].'" data-MinSpeed="'.$rd['MinSpeed'].'" ');
        echo('data-PenaltyType="'.$rd['PenaltyType'].'" value="'.$rd['PenaltyPoints'].'">');
    }

}

// This fetches and emits the various rally-specific variables needed by an active scorecard
function emitScorecardVars() {

    global $DB, $TAGS, $KONSTANTS, $RP;

    emitJS();

    $sql = "SELECT * FROM rallyparams";
    $R = $DB->query($sql);
    if (!$RP = $R->fetchArray())
        return;

    echo("\r\n<!-- Rally standard variables-->\r\n");

    if ($RP['MilesKms'] != 0)
        $bdu = $TAGS['OdoKmsK'][0];
    else
        $bdu = $TAGS['OdoKmsM'][0];
    echo('<input type="hidden" id="bduText" value="'.$bdu.'">');
    for ($i = 1; $i <= $KONSTANTS['NUMBER_OF_COMPOUND_AXES']; $i++) {
        echo('<input type="hidden" id="axisLabel'.$i.'" value="'.$RP['Cat'.$i.'Label'].'">');
    }
    $rr = explode("\n",$RP['RejectReasons']);
    $rcmenu = '<ul>';
    $rcmenu .= '<li><a href="#">'.$TAGS['RejectReason0'][0].'</a></li>';
    foreach($rr as $r) {
        $p = strpos($r,'=');
        if ($p === false)
            continue;
        $n = intval(substr($r,0,$p));
        $x = substr($r,$p + 1);
        echo('<input type="hidden" id="rejectReason'.$n.'" name="rejectreason" data-code="'.$n.'" value="'.$x.'">');
        $rcmenu .= '<li data-code="'.$n.'"><a href="#">'.$n.'='.$x.'</a></li>';
    }
    $rcmenu .= '</ul>';
    echo('<div id="rcmenu" style="display:none;">'.$rcmenu.'</div>');
    foreach(['MinPoints','MinMiles','PenaltyMaxMiles','MaxMilesMethod','MaxMilesPoints','PenaltyMilesDNF','MaxHours'] as $fld) {
        echo('<input type="hidden" id="'.$fld.'" value="'.$RP[$fld].'">');
    }
    echo('<input type="hidden" id="RallyTimeDNF" value="'.$RP['FinishTime'].'">');
    echo('<input type="hidden" id="RallyTimeStart" value="'.$RP['StartTime'].'">');

    emitCatCompoundRules();
    emitPenalties();

    echo('<input type="hidden" id="RTP_TPenalty" value="'.htmlentities(getSetting('RPT_TPenalty',$KONSTANTS['RPT_TPenalty'])).'">');
    echo('<input type="hidden" id="RTP_MPenalty" value="'.htmlentities(getSetting('RPT_MPenalty',$KONSTANTS['RPT_MPenalty'])).'">');
    echo('<input type="hidden" id="RTP_SPenalty" value="'.htmlentities(getSetting('RPT_SPenalty',$KONSTANTS['RPT_SPenalty'])).'">');
    echo('<input type="hidden" id="DNF_TOOFEWPOINTS" value="'.htmlentities(getSetting('DNF_TOOFEWPOINTS',$KONSTANTS['DNF_TOOFEWPOINTS'])).'">');
    echo('<input type="hidden" id="ccApplyToAll" value="'.htmlentities(getSetting('ccApplyToAll',$TAGS['ccApplyToAll'][0])).'">');
    echo('<input type="hidden" id="autoLateDNF" value="'.htmlentities(getSetting('autoLateDNF','false')).'">');
    // Needed for use with setFinisherStatus (score.js)

    echo("\r\n<!--End of rally standard variables-->\r\n");

}

function showScorecard($entrant) {

    global $DB, $TAGS, $KONSTANTS, $RP;

	startHtml($TAGS['ttScoring'][0],$TAGS['oi_Scorecards'][0],true);
    emitScorecardVars();

    $tmp = explode("\n",$RP['RejectReasons']);
    $rr = [];
    foreach($tmp as $r) {
        $p = strpos($r,'=');
        if ($p === false)
            continue;
        $n = intval(substr($r,0,$p));
        $x = substr($r,$p + 1);
        $rr[$n] = $x;
    }

    echo('<div id="ScoreSheet">');
    echo('<form method="post" action="scorecard.php">');

    $sql = "SELECT * FROM entrants WHERE EntrantID=".$entrant;
    $R = $DB->query($sql);
    if (!$rd = $R->fetchArray()) {
        return;
    }

    $teamnames = teamNames($rd['TeamID']);

    // Start/finish times
    if (is_null($rd['StartTime'])) 
        $rd['StartTime'] = $RP['StartTime'];

    $mtDNF = DateTime::createFromFormat('Y\-m\-d\TH\:i',$rd['StartTime']);
    try {
        $mtDNF = date_add($mtDNF,new DateInterval("PT".$RP['MaxHours']."H"));
    } catch(Exception $e) {
        echo('omg! '.$e->getMessage());
    }
    $myTimeDNF = joinDateTime(date_format($mtDNF,'Y-m-d'),date_format($mtDNF,'H:i'));
    if ($RP['FinishTime'] < $myTimeDNF)
        $myTimeDNF = $RP['FinishTime'];

    echo('<input type="hidden" id="FinishTimeDNF" value="'.$myTimeDNF.'">');
//    echo('<input type="hidden" id="CalculatedAvgSpeed">');

    $autoFinisher = getSetting('autoFinisher',"false");

    echo('<input type="hidden" id="autoFinisher" value="'.$autoFinisher.'">');

    foreach(['OdoScaleFactor','EntrantID','RestMinutes','BonusesVisited','CombosTicked','RejectedClaims','TeamID','AvgSpeed'] as $fld) {
        echo('<input type="hidden" id="'.$fld.'" name="'.$fld.'" value="'.$rd[$fld].'">');
    }

    echo('<div id="scorecardtop">');
    echo('<input type="hidden" id="teamnames" value="'.$teamnames.'">');
    echo('<span class="entrant" id="crewname">'.crewName($rd).'</span> ');
    echo('<span class="times"  style="cursor:pointer;" id="showtimes" onclick="editTimes();">'.showRallytime($rd['StartTime']).' '.showRallytime($rd['FinishTime']).'</span> ');
    echo('<span class="times hide" id="edittimes">');
    $dt = splitDatetime($rd['StartTime']);
    echo('<input type="date" title="'.$TAGS['StartDateE'][1].'" id="StartDate" name="StartDate" value="'.$dt[0].'" onchange="updateStarttime();">');
    echo('<input type="time" title="'.$TAGS['StartTimeE'][1].'" id="StartTime" name="StartTime" value="'.$dt[1].'" onchange="updateStarttime();"> - ');
    $dt = splitDatetime($rd['FinishTime']);
    echo('<input type="date" title="'.$TAGS['FinishDateE'][1].'" id="FinishDate" name="FinishDate" value="'.$dt[0].'" onchange="updateFinishtime();">');
    echo('<input type="time" title="'.$TAGS['FinishTimeE'][1].'" id="FinishTime" name="FinishTime" value="'.$dt[1].'" onchange="updateFinishtime();">');
    echo('<span onclick="showTimes();" style="cursor:pointer;"> &checkmark; &nbsp; </span>');
    echo('</span>');
    echo('<span class="distance" id="showmiles">');
    echo('<label style="cursor:pointer;" ');
    echo('onmouseover="if(document.getElementById('."'OdoRallyStart'".').value==document.getElementById('."'OdoRallyFinish'".').value) showOdos();" ');
    echo('onclick="showOdos();" title="'.$TAGS['ShowOdoReadings'][1].'" for="CorrectedMiles">'.$TAGS['CorrectedMiles'][0].'</label> ');
    echo('<input type="number" name="CorrectedMiles" id="CorrectedMiles" onchange="recalcScorecard();" value="'.$rd['CorrectedMiles'].'"> ');
    echo('</span> ');
    echo('<span id="showodos" class="hide">');
	echo('<span title="'.$TAGS['OdoKms'][1].'"><label for="OdoKms">'.$TAGS['OdoKms'][0].' </label> ');
	echo('<select name="OdoKms" id="OdoKms" onchange="calcMiles();recalcScorecard();">');
	if ($rd['OdoKms']==$KONSTANTS['OdoCountsKilometres']) {
		echo('<option value="'.$KONSTANTS['OdoCountsMiles'].'">'.$TAGS['OdoKmsM'][0].'</option>');
		echo('<option value="'.$KONSTANTS['OdoCountsKilometres'].'" selected >'.$TAGS['OdoKmsK'][0].'</option>');
	} else {
		echo('<option value="'.$KONSTANTS['OdoCountsMiles'].'" selected >'.$TAGS['OdoKmsM'][0].'</option>');
		echo('<option value="'.$KONSTANTS['OdoCountsKilometres'].'" >'.$TAGS['OdoKmsK'][0].'</option>');
	}
	echo('</select></span>');    
    echo( '<input id="OdoRallyStart" name="OdoRallyStart" type="number" class="bignumber" title="'.$TAGS['OdoRallyStart'][1].'" value="'.$rd['OdoRallyStart'].'" onchange="updateOdoStart();"> ');
    echo('<input id="OdoRallyFinish" name="OdoRallyFinish" type="number" class="bignumber" title="'.$TAGS['OdoRallyFinish'][1].'" value="'.$rd['OdoRallyFinish'].'" onchange="updateOdoFinish();"> ');
    echo('<span onclick="showMiles();" style="cursor:pointer;"> &checkmark; &nbsp; </span>');
    echo('</span>');

    echo('<span class="points"><label for="TotalPoints">'.$TAGS['TotalPoints'][0].'</label> ');
    echo('<input type="text" class="bignumber" readonly name="TotalPoints" id="TotalPoints" value="'.$rd['TotalPoints'].'"> ');
    echo('</span> ');

	echo('<span class="entrantstatus" title="'.$TAGS['EntrantStatus'][1].'"><label for="EntrantStatus">'.$TAGS['EntrantStatus'][0].' </label> ');
	echo('<select name="EntrantStatus" id="EntrantStatus" onchange="setManualStatus();">'); // Don't recalculate if status changed manually
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


    echo('</div>'); // End scorecardtop

    // Rejected claims
    //echo('<input type="hidden" id="RejectedClaims" value="'.$rd['RejectedClaims'].'">');
    $tmp = explode(',',''.$rd['RejectedClaims']);
    $rejectedClaims = [];
    foreach ($tmp as $r) {
        // Format is (code)=(reason)
        $e = strpos($r,'=');
        $rejectedClaims[substr($r,0,$e)] = intval(substr($r,$e + 1));
    }

    //print_r($rejectedClaims);

    $catcounts = [];
    
    $tmp = explode(',',''.$rd['BonusesVisited']);
    //echo('<input type="hidden" id="BonusesVisited" value="'.$rd['BonusesVisited'].'">');
    $bonusesScored = [];
    $bc = [];
    $bc['Bonus'] = '';
    $bc['Points'] = '';
    $bc['Mins'] = '';
    $bc['XP'] = false;
    $bc['PP'] = false;
    foreach ($tmp as $b) {
        // Format is (code)=?(points)?,?(minutes)
        parseBonusClaim($b,$bc['Bonus'],$bc['Points'],$bc['Mins'],$bc['XP'],$bc['PP']);
        $bonusesScored[$bc['Bonus']] = $bc;
    }


    //print_r($bonusesScored);

	echo('<div class="tabs_area" style="display:inherit"><ul id="tabs">');
	echo('<li><a href="#tab_bonuses">'.$TAGS['BonusesLit'][0].'</a></li>');
	echo('<li><a href="#tab_scorex">'.$TAGS['ScorexLit'][0].'</a></li>');
    for ($i = 1; $i <= $KONSTANTS['NUMBER_OF_COMPOUND_AXES']; $i++) {
        if ($RP['Cat'.$i.'Label'] == '')
            continue;
        echo('<li><a href="tab_cat'.$i.'">'.$RP['Cat'.$i.'Label'].'</a></li>');
    }

	echo('</ul></div>');


    echo('<div id="tab_bonuses" class="tabContent">');

    $sql = "SELECT * FROM bonuses WHERE Leg=0 OR Leg=".$RP['CurrentLeg']." ORDER BY IfNull(GroupName,''), BonusID";
    $R = $DB->query($sql);
    $lastGroup = 'ZZZ'; // Non-existent high value
    $lastGroupStarted = false;
    $bonusWidth = 0; $comboWidth = 0;
    while ($rb = $R->fetchArray()) {

        $thisGroup = $rb['GroupName'];
        if (is_null($thisGroup))
            $thisGroup = '';

        if ($thisGroup != $lastGroup) {
            if ($lastGroupStarted)
                echo('</fieldset>');
            $lastGroupStarted = $thisGroup != '';
            if ($lastGroupStarted)
                echo('<fieldset class="bonusgroup""><legend>'.$thisGroup.'</legend>');
            $lastGroup = $thisGroup;
        }
        $checked = array_key_exists($rb['BonusID'],$bonusesScored);
        $rejected = $checked && array_key_exists($rb['BonusID'],$rejectedClaims);

        //echo(' Bonus "'.$rb['BonusID'].'" is '.($checked ? 'checked' : ' not checked').' ! ');
        if ($rejected)
            $cls = 'rejected';
        else if ($checked)
            $cls = 'checked';
        else
            $cls = '';

        $tit = $rb['BriefDesc'];
        if ($rejected)
            $tit .= "\r".$rr[$rejectedClaims[$rb['BonusID']]];
        echo('<span class="showbonus '.$cls.'" title="'.str_replace('"','&quot;',strip_tags($tit)).'"');
        echo(' oncontextmenu="return showPopup(this);"');
        echo('>');
        $thisBonusLength = strlen($rb['BonusID']);
        if ($thisBonusLength > $bonusWidth) $bonusWidth = $thisBonusLength;
        echo('<label class="showbonuslabel" for="'.$rb['BonusID'].'">'.$rb['BonusID'].'</label>');
        echo('<input type="checkbox" name="BonusID[]" value="'.$rb['BonusID'].'" id="'.$rb['BonusID'].'" ');
        $pts = $rb['Points'];
        $mins = $rb['RestMinutes'];
        if ($checked) {
            echo(' checked ');
            $bs = $bonusesScored[$rb['BonusID']];
            if (isset($bs['Points'])) { $pts = $bs['Points']; }
            if (isset($bs['Mins'])) { $mins = $bs['Mins']; }
            if (isset($bs['XP'])) { 
                $xp = 'data-xp="';
                if ($bs['XP']) { $xp .= 'true'; }
                $xp .= '" '; 
            }
            if (isset($bs['PP'])) { 
                $pp = 'data-pp="';
                if ($bs['PP']) { $pp .= 'true'; }
                $pp .= '" '; 
            }
            echo($xp.$pp);

        }
        echo('data-points="'.$pts.'" data-askpoints="'.$rb['AskPoints'].'" ');
        echo('data-minutes="'.$mins.'" data-askminutes="'.$rb['AskMinutes'].'" ');
        echo('data-desc="'.htmlspecialchars($rb['BriefDesc']).'" ');
        echo('data-reqd="'.$rb['Compulsory'].'" ');

        for ($i = 1; $i <= $KONSTANTS['NUMBER_OF_COMPOUND_AXES']; $i++) {
            echo('data-cat'.$i.'="'.$rb['Cat'.$i].'" ');
            if ($checked && !$rejected && $rb['Cat'.$i] > 0) {
                if (isset($catcounts[$i][$rb['Cat'.$i]]))
                    $catcounts[$i][$rb['Cat'.$i]]++;
                else
                    $catcounts[$i][$rb['Cat'.$i]] = 1;

            }
        }
        echo(' onchange="tickBonus(this);" ');
        echo('></span> ');
    }
    if ($lastGroupStarted)
        echo('</fieldset>');

    $combosTicked = explode(',',''.$rd['CombosTicked']);
    //echo('<input type="hidden" id="CombosTicked" value="'.$rd['CombosTicked'].'" >');
    $sql = "SELECT * FROM combinations WHERE Leg=0 OR Leg=".$RP['CurrentLeg']." ORDER BY ComboID";
    $R = $DB->query($sql);
    $combosStarted = false;
    while ($rc = $R->fetchArray()) {
        if (!$combosStarted) {
            echo('<fieldset class="bonusgroup"><legend>'.$TAGS['CombosLit'][0].'</legend>');
            $combosStarted = true;
        }
        $checked = array_search($rc['ComboID'],$combosTicked) !== false;
        $rejected = $checked && array_key_exists($rc['ComboID'],$rejectedClaims);
        if ($rejected)
            $cls = 'rejected';
        else if ($checked)
            $cls = 'checked';
        else
            $cls = '';

        $tit = $rc['BriefDesc'];
        if ($rejected)
            $tit .= "\r".$rr[$rejectedClaims[$rc['ComboID']]];
        echo('<span class="showbonus '.$cls.'" title="'.str_replace('"','&quot;',strip_tags($tit)).'">');
        $thisBonusLength = strlen($rc['ComboID']);
        if ($thisBonusLength > $comboWidth) $comboWidth = $thisBonusLength;

        echo('<label class="showcombolabel" for="'.$rc['ComboID'].'">'.$rc['ComboID'].'</label>');
        echo('<input type="checkbox" name="ComboID[]" value="'.$rc['ComboID'].'" id="'.$rc['ComboID'].'" ');
        echo('data-desc="'.htmlspecialchars($rc['BriefDesc']).'" ');
        echo('data-bids="'.$rc['Bonuses'].'" data-pm="'.$rc['ScoreMethod'].'" ');

        // Establish pts as an array of results depending on number of claims
        $cmb_bids = explode(',',$rc['Bonuses']);
        $cmb_min = $rc['MinimumTicks'];
        $cmb_max = count($cmb_bids);

        $cmb_pts   = array_pad([],$cmb_max,0);
        if ($cmb_min == 0 || $cmb_min == $cmb_max) {
            $cmb_pts[$cmb_max - 1] = intval($rc['ScorePoints']);
            $cmb_min = $cmb_max;
        } else {
            $pa = explode(',',$rc['ScorePoints']);
            $i = 0;
            for ($j = $cmb_min - 1; $j < $cmb_max; $j++) {
                if ( isset ( $pa[$i]))
                    $cmb_pts[$j] = $pa[$i++];
                else
                    $cmb_pts[$j] = $pa[$i - 1];
            }
        }

        echo('data-pts="'.implode(',',$cmb_pts).'" ');
        echo('data-minticks="'.$cmb_min.'" data-points="'.$rc['ScorePoints'].'" ');
        echo('data-maxticks="'.$cmb_max.'" ');
        echo('data-reqd="'.$rc['Compulsory'].'" ');
        for ($i = 1; $i <= $KONSTANTS['NUMBER_OF_COMPOUND_AXES']; $i++) {
            echo('data-cat'.$i.'="'.$rc['Cat'.$i].'" ');
        }
        if ($checked)
            echo(' checked ');
        echo(' onclick="ignoreClick(this);" oncontextmenu="return showPopup(this);"');
        echo('></span>');
    
    }
    if ($combosStarted)
        echo('</fieldset>');
    
    echo('</div>'); // End tab_bonuses

    echo('<div id="tab_scorex" class="tabContent">');

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

    echo('<div id="scorex" oncontextmenu="showBonusOrder()" title="'.$TAGS['ScorexHints'][0].'" class="scorex" data-show="0" ondblclick="printscorex();" ');
    echo('data-title="'.htmlspecialchars($RP['RallyTitle']).'" ');
    echo('data-style="'.$style.'" >');
    echo($rd['ScoreX'].'</div>');
    echo('<input type="hidden" name="ScoreX" id="scorexText" value="'.htmlspecialchars(''.$rd['ScoreX']).'">');
	echo('<div id="ddarea" class="hide"><p> </p></div>');	// Used for drag/drop operations

    echo('</div>'); // End tab_scorex


    for ($i = 1; $i <= $KONSTANTS['NUMBER_OF_COMPOUND_AXES']; $i++) {
        if ($RP['Cat'.$i.'Label'] == '')
            continue;
        echo('<div id="tab_cat'.$i.'" class="tabContent">');
            showCategoryTab($i,$RP['Cat'.$i.'Label'],$catcounts);
        echo('</div>');
    }
    echo('</form>');

    echo('</div>'); // End scorecard
    if (getSetting('autoAdjustBonusWidth',"true")=="true") {
        echo('<style>');
        echo('.showbonuslabel { display: inline-block; width: ');
        echo($bonusWidth.'ch; }');
        echo('.showcombolabel { display: inline-block; width: ');
        echo($comboWidth.'ch; }');
        echo('</style>');                    
    }
}

function showCategoryTab($axis,$axisdesc,$catcounts) {
	global $DB, $TAGS, $KONSTANTS;

	$R = $DB->query("SELECT * FROM categories WHERE Axis=$axis ORDER BY Cat");
	//echo("\r\n");
	echo('<table id="cat'.$axis.'">');
	//echo("\r\n");
	while ($rd = $R->fetchArray()) {
        $cat = $rd['Cat'];
		echo('<tr><td class="catdescr">'.$rd['BriefDesc'].'</td><td class="scoredetail" id="cat'.$axis.'_'.$cat.'">');
        if ( isset( $catcounts[$axis][$cat] ) )
            echo($catcounts[$axis][$cat]);
        echo('</td></tr>');
    }
	//echo("\r\n");
	echo('</table>');
	//echo("\r\n");
}

function saveScorecard() {

    global $DB, $KONSTANTS;

    if (!$DB->exec('BEGIN IMMEDIATE TRANSACTION')) {
        dberror();
        exit;
    }

    $sql = "UPDATE entrants SET Confirmed=".$KONSTANTS['ScorecardIsClean'];
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
		
    if (isset($_REQUEST['BonusesVisited']))
        $sql .= ",BonusesVisited='".$_REQUEST['BonusesVisited']."'";
              
   	if (isset($_REQUEST['CombosTicked']))
        $sql .= ",CombosTicked='".$_REQUEST['CombosTicked']."'";

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
    if (isset($_REQUEST['AvgSpeed']))
		$sql .= ",AvgSpeed='".floatval($_REQUEST['AvgSpeed'])."'";
	$sql .= " WHERE EntrantID=".$_REQUEST['EntrantID'];
    error_log("SaveScorecard: $sql");
	if (!$DB->exec($sql)) {
		dberror();
		exit;
	}
	if (($res = $DB->lastErrorCode()) <> 0) {
		return dberror();
	}
	updateTeamScorecards($_REQUEST['EntrantID']);
    rankEntrants(true);
    updateAutoClass($_REQUEST['EntrantID']);

    $DB->exec('COMMIT TRANSACTION');
}


if (isset($_REQUEST['savescore'])) {
    //print_r($_REQUEST);
    saveScorecard();
    header("Location: ".$HOME_URL);
}

$e = 7; // Random default value for testing
if (isset($_REQUEST['EntrantID']))
    $e = $_REQUEST['EntrantID'];
showScorecard($e);

?>
