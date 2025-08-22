<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

/*
 * I B A U K   -   S C O R E M A S T E R
 *
 * I offer a picklist of entrants, usually for scorecards
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


$HOME_URL = "index.php";

require_once('recalc.php');

/**
 * This is intended to provide a rapid and relatively foolproof method of capturing odo readings at the
 * start and end of a rally.
 * 
 * Capturing the readings themselves is very straightforward but catering for the potential implications
 * is not so simple.
 * 
 * If capturing odo check values, the dependent variable is OdoScaleFactor.
 * 
 * If capturing odo at finish values, the dependent variable is CorrectedMiles. This must be recalculated and
 * then any scoring implications must also be addressed. One of the possibilities is that status becomes DNF.
 * 
 */

function showOdoValue($odo) {

    $reading = intval($odo);
    if ($reading < 1)
        return '';
    return $odo;
}

function showOdoList() {

    global $DB, $TAGS, $KONSTANTS, $HOME_URL, $DBVERSION;

    $R = $DB->query('SELECT * FROM entrants ORDER BY RiderLast,RiderFirst');

	$lnk = '<a href="'.$HOME_URL.'">';
    $isOdoCheck = isset($_REQUEST['odocheck']);

    $isCheckIn = !$isOdoCheck && !isset($_REQUEST['co']);

    if ($isOdoCheck) {
        $checkout = "odo check/start";
        $checkin = "ODO CHECK";
        $checkoutname = "OdoCheckStart";
        $checkinname = "OdoCheckFinish";
        $disabledstop = '';
    } else {
        $checkout = "check-out/start";
        $checkin = "check-in/finish";
        $checkoutname = "OdoRallyStart";
        $checkinname = "OdoRallyFinish";
        $disabledstop = ' disabled ';
    }

	startHtml($TAGS['OdoReadingHdr'][0],$TAGS['OdoReadingHdr'][1],true);

	
	eval("\$evs = ".$TAGS['EntrantStatusV'][0]);

    echo("<script>\n");
    include 'fastodosphp.js';
    echo("</script>\n");

    echo('<div style="width:20em; max-width: 100vw; margin-left:auto; margin-right: auto;">');
    echo('<div id="sshdr" style="width:100%;text-align:center;margin-bottom: .5em;">');
    //echo('<form>');
    $chk = ($isOdoCheck || isset($_REQUEST['ci']) || isset($_REQUEST['co']) ? "checked" : '');

    if (!$isOdoCheck  && !isset($_REQUEST['ci'])) {
        echo('<input type="radio" '.$chk.' onchange="swapss(this);" name="startstop" id="ss_start" value="start" style="display:none;"> ');
        $startlit = $isCheckIn ? $checkout : strtoupper($checkout);
        echo('<label class="start" for="ss_start">'.$startlit.'</label> ');
        echo(' &nbsp;&nbsp;&nbsp; ');
        $chk = ($chk == '' ? 'checked' : '');
    }
    if ($isOdoCheck || !isset($_REQUEST['co'])) {
        echo('<input type="radio" '.$chk.' onchange="swapss(this);" name="startstop" id="ss_stop" value="stop" style="display:none;"> ');
        $startlit = $isCheckIn ? strtoupper($checkin) : $checkin;
        echo('<label class="stop" for="ss_stop">'.$startlit.'</label> ');
    }

    echo(' <br>&nbsp;&nbsp;&nbsp;<span id="timenow" data-time="" data-refresh="1000" data-pause="120000" data-paused="0" onclick="clickTime();">');
    //echo('12:34.05');
    echo('</span>');
    //echo('</form>');
    echo('</div>');


    echo('<div class="odolist">');
    //echo('<form action="fastodos.php" method="post">');

    //echo('<table>');
    //echo('<tbody id="ssbuttons">');

    $rowspresent = false;
    $startstop = $isOdoCheck ? 'stop' : 'start';
    $autofocus = ' autofocus ';
    $cellid = 1;
    while($rd = $R->fetchArray()) {
        echo('<div class="odorow">');
        echo('<span class="EntrantID">'.$rd['EntrantID'].'</span>');
        echo('<span><strong>'.$rd['RiderLast'].'</strong>, '.$rd['RiderFirst'].'</span>');

        if (!$isCheckIn) {
            echo('<span><input type="number" ');
            echo('id="cid'.$cellid.'" '); $cellid++;
            if ($isCheckIn) echo(' disabled ');
            echo('placeholder="start" name="'.$checkoutname.'" ');
            echo('min="0" tabindex="0" class="odoreading '.$startstop);
            if ($isCheckIn) echo(' hide');
            echo('" ');
            echo('onchange="oc(this);" oninput="oi(this);" onblur="ob(this);" value="'.showOdoValue($rd[$checkoutname]).'"');
            if (!$isCheckIn) {
                echo($autofocus);
                $autofocus = '';
            }
            echo('></span>');
        } else {
            echo('<span><input type="number" ');
            echo('id="cid'.$cellid.'" '); $cellid++;
            if (!$isCheckIn && !$isOdoCheck) echo(' disabled ');
            echo('placeholder="finish" name="'.$checkinname.'" ');
            echo('min="0" tabindex="0" class="odoreading stop');
            if (!$isCheckIn) echo(' hide');
            echo('" ');
            echo('onchange="oc(this);" oninput="oi(this);" value="'.showOdoValue($rd[$checkinname]).'"');
            if ($isCheckIn) {
                echo($autofocus);
                $autofocus = '';
            }
            echo('></span>');
        }

        echo('</div> <!-- odorow -->');
        $rowspresent = true;
    }
    //echo('</tbody>');
    //echo('</table>');
    //echo('</form>');
    if (!$rowspresent)
        echo('<span style="font-size:2vw;">&#128530;</span>');
    echo('</div>');

    echo('</div>');

    echo('<script>refreshTime(); timertick = setInterval(refreshTime,1000);</script>');

}
function recalcDistance($e) {

    global $DB,$TAGS,$KONSTANTS;

    $ocm = getValueFromDB("SELECT OdoCheckMiles FROM rallyparams","OdoCheckMiles",0);
    $mk = getValueFromDB("SELECT MilesKms FROM rallyparams","MilesKms",0);
    $sql = "SELECT OdoKms,ifnull(OdoCheckStart,0) as OdoCheckStart,ifnull(OdoCheckFinish,0) as OdoCheckFinish";
    $sql .= ",ifnull(OdoCheckTrip,0) as OdoCheckTrip,ifnull(OdoScaleFactor,1) as OdoScaleFactor";
    $sql .= ",ifnull(OdoRallyStart,0) as OdoRallyStart,ifnull(OdoRallyFinish,0) as OdoRallyFinish";
    $sql .= ",ifnull(CorrectedMiles,0) as CorrectedMiles";
    $sql .= " FROM entrants WHERE EntrantID=$e";
    $R = $DB->query($sql);
    if (!$rd = $R->fetchArray()) {
        return;
    }
    error_log("recalcD: ".$rd['OdoScaleFactor'].'; '.$rd['OdoCheckTrip'].'; '.$ocm);
    if ($rd['OdoScaleFactor'] < 0.5)
        $rd['OdoScaleFactor'] = 1.0;
    if ($rd['OdoCheckTrip']== "")
        $rd['OdoCheckTrip'] = 0.0;
    if ($ocm > 1) {
        $doscale = false;
        if (intval($rd['OdoCheckTrip']) > 0) {
            // We're good
            $doscale = true;
        } else if (intval($rd['OdoCheckStart'])==0 && intval($rd['OdoCheckFinish'])==0) {
            if ($rd['OdoCheckTrip'] > 1) {
                $doscale = true;
            } else if ($rd['OdoCheckStart'] < $rd['OdoCheckFinish']) {
                $rd['OdoCheckTrip'] = $rd['OdoCheckFinish'] - $rd['OdoCheckStart'];
                $doscale = true;
            }
        } else if (intval($rd['OdoCheckFinish']) > intval($rd['OdoCheckStart'])) {
            $rd['OdoCheckTrip'] = floatval($rd['OdoCheckFinish']) - floatval($rd['OdoCheckStart']);
            $doscale = true;
        }
        if ($doscale) {
            $checkdistance = floatval($rd['OdoCheckTrip']);

            
            $rd['OdoCheckFinish'] = 0;      // We'll only use trip distance from now on
                                            // Let's not recalculate every time
            

			if ($rd['OdoKms'] > 0 && $mk != 1) // Want miles, have kms
				$checkdistance = $checkdistance / $KONSTANTS['KmsPerMile'];
			else if ($rd['OdoKms'] != 1 && $mk == 1) // Want kms, have miles
				$checkdistance = $checkdistance * $KONSTANTS['KmsPerMile'];
			$correctionfactor = $ocm / $checkdistance ;
            error_log("OdoCorrect: ".$checkdistance."; ocm=".$ocm."; Rawcf=".$correctionfactor);
			if ($correctionfactor < 0.5)	//SanityCheck
				$correctionfactor = 1.0;

            $rd['OdoScaleFactor'] =  $correctionfactor;
        }
    }
    
    if ($rd['OdoRallyFinish'] > $rd['OdoRallyStart']) {
        $rallydistance = ($rd['OdoRallyFinish'] - $rd['OdoRallyStart']) * $rd['OdoScaleFactor'];
        if ($rd['OdoKms'] == 1 && $mk != 1)
            $rallydistance = $rallydistance / $KONSTANTS['KmsPerMile'];
        else if ($rd['OdoKms'] < 1 && $mk == 1)
            $rallydistance = $rallydistance * $KONSTANTS['KmsPerMile'];
        $rd['CorrectedMiles'] = number_format($rallydistance,0,'','');
    }
    
    $sql = "UPDATE entrants SET OdoKms=".$rd['OdoKms'];
    $sql .= ",OdoCheckStart=".$rd['OdoCheckStart'];
    $sql .= ",OdoCheckFinish=".$rd['OdoCheckFinish'];
    $sql .= ",OdoCheckTrip=".$rd['OdoCheckTrip'];
    $sql .= ",OdoScaleFactor=".$rd['OdoScaleFactor'];
    $sql .= ",OdoRallyStart=".$rd['OdoRallyStart'];
    $sql .= ",OdoRallyFinish=".$rd['OdoRallyFinish'];
    $sql .= ",CorrectedMiles=".$rd['CorrectedMiles'];
    $sql .= " WHERE EntrantID=$e";
    error_log($sql);
    $DB->exec($sql);
    
}
function updateFastOdo() {

	global $DB,$TAGS,$KONSTANTS;

	if (!isset($_REQUEST['e']) || !isset($_REQUEST['f']) || !isset($_REQUEST['v'])) {
		echo('');
		return;
	}
    //var_dump($_REQUEST);
    $okFinisher = false;
    $okStarter = false;
    switch($_REQUEST['f']) {
        case 'OdoRallyFinish':
            // If this is the last or only leg of the rally, recording the finishing odo makes him 
            // a Finisher if not otherwise disqualified. If the setting 'autoFinisher' is true, the
            // entrants will usually already be finishers at this point.
            $cleg = getValueFromDB("SELECT CurrentLeg FROM rallyparams","CurrentLeg",1);
            $nleg = getValueFromDB("SELECT NumLegs FROM rallyparams","NumLegs",1);
            $okFinisher = $cleg >= $nleg;
            break;
        case 'OdoRallyStart':
            $okStarter = true;
            break;
        case 'OdoCheckStart':
        case 'OdoCheckFinish':
        case 'OdoCheckTrip':
        case 'OdoKms':
            break;
        default:
            echo('');
            return;
    }
    error_log("okstarter=$okStarter; okfinisher=$okFinisher");
    $DB->exec("BEGIN TRANSACTION");
	$sql = "UPDATE entrants SET ".$_REQUEST['f']."=".$_REQUEST['v'];
	$sql .= " WHERE EntrantID=".$_REQUEST['e'];
	$updateok = ($DB->exec($sql) && $DB->changes()==1);
    if ($updateok) {
        if ($okStarter) {
            $sql = "UPDATE entrants SET EntrantStatus=".$KONSTANTS['EntrantOK'];
            $sql .= ",ExtraData=ifnull(ExtraData,'') || char(13) || char(10) || 'StartOdo=".$_REQUEST['v']."'";
            $sql .= ",OdoCheckStart=".$_REQUEST['v'];
            $sql .= " WHERE EntrantID=".$_REQUEST['e'];
            $sql .= " AND EntrantStatus=".$KONSTANTS['EntrantDNS'];
            error_log($sql);
            echo($sql);
            $DB->exec($sql);
        } else {
           if ($okFinisher) {
                $sql = "UPDATE entrants SET EntrantStatus=".$KONSTANTS['EntrantFinisher'];
                $sql .= ",ExtraData=ifnull(ExtraData,'') || char(13) || char(10) || 'FinishOdo=".$_REQUEST['v']."'";
                $sql .= ",OdoCheckFinish='".$_REQUEST['v']."'";
                if (isset($_REQUEST['t']))
                    $sql .= ", FinishTime='".substr($_REQUEST['t'],0,16)."'";
                $sql .= " WHERE EntrantID=".$_REQUEST['e'];
                $sql .= " AND EntrantStatus=".$KONSTANTS['EntrantOK'];
                error_log($sql);
                echo($sql);
                $DB->exec($sql);
            }
            recalcDistance($_REQUEST['e']);
            recalcScorecard($_REQUEST['e'],true);
            rankEntrants(true);
        }
    }
    $DB->exec("COMMIT");
	echo($updateok? 'ok' : 'error' );
}

if (isset($_REQUEST['c'])) {
    updateFastOdo();
    exit;
}
showOdoList();

?>
