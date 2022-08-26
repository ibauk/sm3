<?php

/*
 * I B A U K   -   S C O R E M A S T E R
 *
 * I handle entrant reviews
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

function emitScorecardVars() {

    global $DB, $TAGS, $KONSTANTS, $RP;

    

    $sql = "SELECT * FROM rallyparams";
    $R = $DB->query($sql);
    if (!$RP = $R->fetchArray())
        return;

    $RP['RestBonusStartGroup'] = getSetting('restBonusStartGroup','');

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


    echo('<input type="hidden" id="RTP_TPenalty" value="'.htmlentities(getSetting('RPT_TPenalty',$KONSTANTS['RPT_TPenalty'])).'">');
    echo('<input type="hidden" id="RTP_MPenalty" value="'.htmlentities(getSetting('RPT_MPenalty',$KONSTANTS['RPT_MPenalty'])).'">');
    echo('<input type="hidden" id="RTP_SPenalty" value="'.htmlentities(getSetting('RPT_SPenalty',$KONSTANTS['RPT_SPenalty'])).'">');
    echo('<input type="hidden" id="DNF_TOOFEWPOINTS" value="'.htmlentities(getSetting('DNF_TOOFEWPOINTS',$KONSTANTS['DNF_TOOFEWPOINTS'])).'">');
    echo('<input type="hidden" id="ccApplyToAll" value="'.htmlentities(getSetting('ccApplyToAll',$TAGS['ccApplyToAll'][0])).'">');
    // Needed for use with setFinisherStatus (score.js)

    echo("\r\n<!--End of rally standard variables-->\r\n");

}


function reviewEntrant($entrant) {

    global $DB, $TAGS, $KONSTANTS, $RP;

	startHtml($TAGS['ttScoring'][0],$TAGS['oi_EReviews'][0],true);

    echo('<div id="EntrantReview">');
    echo('<form method="post" action="ereviews.php">');

    emitScorecardVars();

    $sql = "SELECT * FROM entrants WHERE EntrantID=".$entrant;
    $R = $DB->query($sql);
    if (!$rd = $R->fetchArray()) {
        return;
    }

    $teamnames = teamNames($rd['TeamID']);
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
    
    echo('<div id="ereviewstop">');
    echo('<input type="hidden" id="teamnames" value="'.$teamnames.'">');
    echo('<span class="entrant" id="crewname">'.crewName($rd).'</span> ');
    echo('</span>');

    echo('<span class="distance" id="showmiles">');
    echo('<label ');
    echo(' for="CorrectedMiles">'.$TAGS['CorrectedMiles'][0].'</label> ');
    echo('<input type="text" class="bignumber" readonly name="CorrectedMiles" id="CorrectedMiles" value="'.$rd['CorrectedMiles'].'"> ');
    echo('</span> ');
    

    echo('<span class="points"><label for="TotalPoints">'.$TAGS['TotalPoints'][0].'</label> ');
    echo('<input type="text" class="bignumber" readonly name="TotalPoints" id="TotalPoints" value="'.$rd['TotalPoints'].'"> ');
    echo('</span> ');

	echo('<span class="entrantstatus" title="'.$TAGS['EntrantStatus'][1].'"><label for="EntrantStatus">'.$TAGS['EntrantStatus'][0].' </label> ');
    echo('<input type="text" readonly class="bignumber" name="EntrantStatus" id="EntrantStatus" value="');
    switch($rd['EntrantStatus']) {
        case $KONSTANTS['EntrantDNS']:
            echo($TAGS['EntrantDNS'][0]); break;
        case $KONSTANTS['EntrantOK']:
            echo($TAGS['EntrantOK'][0]); break;
        case $KONSTANTS['EntrantFinisher']:
            echo($TAGS['EntrantFinisher'][0]); break;
        case $KONSTANTS['EntrantDNF']:
            echo($TAGS['EntrantDNF'][0]); break;                                    
    }
    echo('">');

	echo('</span> ');

	echo('<input type="submit" class="noprint" title="'.$TAGS['SaveScore'][1].'" id="savescorebutton" data-triggered="0" ');
	echo('onclick="'."this.setAttribute('data-triggered','1');".'"');
	if ($rd['Confirmed'] == $KONSTANTS['ScorecardIsDirty'])
		echo(' value="'.$TAGS['SaveScore'][0].'"');
	else
		echo(' disabled value="'.$TAGS['ScoreSaved'][0].'"');
	echo(' accesskey="S" name="savescore" data-altvalue="'.$TAGS['SaveScore'][0].'"  /> ');


    echo('</div>'); // End scorecardtop

    $rr = explode("\n",str_replace("\r","",getValueFromDB("SELECT RejectReasons FROM rallyparams","RejectReasons","1=1")));
	$decisions = [];
	$decisions['0'] = $TAGS['BonusClaimOK'][0];
	foreach($rr as $rt) {
		$rtt = explode('=',$rt);
		if (isset($rtt[1]))
			$decisions[$rtt[0]] = $rtt[1];
	}


	echo('<div class="tabs_area" style="display:inherit"><ul id="tabs">');
	echo('<li><a href="#tab_scorex">'.$TAGS['ScorexLit'][0].'</a></li>');
	echo('<li><a href="#tab_claims">'.$TAGS['ClaimsLit'][0].'</a></li>');

	echo('</ul></div>');


    echo('<div id="tab_scorex" class="tabContent ereviews">');

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
    echo('<input type="hidden" name="ScoreX" id="scorexText" value="'.htmlspecialchars($rd['ScoreX']).'">');
	echo('<div id="ddarea" class="hide"><p> </p></div>');	// Used for drag/drop operations

    echo('</div>'); // End tab_scorex

    echo('<div id="tab_claims" class="tabContent ereviews">');

    $sql = "SELECT claims.*,bonuses.BriefDesc,bonuses.Notes,bonuses.Flags,bonuses.GroupName,claims.rowid";
    $sql .= " FROM claims LEFT JOIN bonuses ON claims.BonusID=bonuses.BonusID WHERE claims.EntrantID=$entrant";
    $sql .= " ORDER BY ClaimTime";
    $R = $DB->query($sql);

    echo('<table class="claimslist">');
    echo('<thead><tr>');
    echo('<th>'.$TAGS['cl_BonusHdr'][0].'</th><th></th><th>'.$TAGS['cl_OdoHdr'][0].'<th>'.$TAGS['cl_ClaimedHdr'][0].'</th>');
	echo('<th>'.$TAGS['cl_DecisionHdr'][0].'</th>');
    echo('<th>'.$TAGS['ebc_JudgesNotes'][0].'</th>');
    echo('</tr></thead><tbody>');

    $nclaims = 0; $nbonuses = 0;
    $bonusesclaimed = [];
    while ($rd = $R->fetchArray()) {
        $bonusid = str_replace('-','',$rd['BonusID']);
        $nclaims++;
        if (!isset($bonusesclaimed[$bonusid]) || ($RP['RestBonusStartGroup'] != '' && $rd['GroupName']==$RP['RestBonusStartGroup'])) {
            $nbonuses++;
            $bonusesclaimed[$bonusid] = 1;
        } else 
            $bonusesclaimed[$bonusid]++;
    }
    reset($R);

    while ($rd = $R->fetchArray()) {
        $bonusid = str_replace('-','',$rd['BonusID']);
        $s1 = ''; $s2 = '';
        if (--$bonusesclaimed[$bonusid] > 0) {
            $s1 = '<s>';
            $s2 = '</s>';
        }
        $lnk = 'claims.php?c=showclaim&claimid='.$rd['rowid'].'&returnshow=picklist.php?review';
        echo('<tr class="link"');
        echo(' onclick="window.open('."'".$lnk."','_self'".')">');
        echo('<td class="BonusID">'.$s1.$bonusid.$s2.'</td>');
        $nf = $rd['Notes'].' <strong>'.$rd['Flags'].'</strong>';
        echo('<td class="BriefDesc">'.$s1.$rd['BriefDesc'].$s2.'<br><span class="NotesFlags">'.$nf.'</span></td>');
        echo('<td class="OdoReading">'.$s1.$rd['OdoReading'].$s2.'</td>');
        echo('<td class="ClaimTime">'.$s1.logtime($rd['ClaimTime']).$s2.'</td>');
        echo('<td class="Decision">'.$s1.$decisions[$rd['Decision']].$s2.'</td>');
        echo('<td class="JudgesNotes">'.$rd['MagicWord'].'</td>');
        echo('</tr>');

    }
    echo('</tbody></table>');

    echo('</div>'); // End tab_claims

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

$e = 7; // Random default value for testing
if (isset($_REQUEST['EntrantID']))
    $e = $_REQUEST['EntrantID'];
reviewEntrant($e);

?>
