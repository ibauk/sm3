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
function showOdoList() {

    global $DB, $TAGS, $KONSTANTS, $HOME_URL, $DBVERSION;

    $R = $DB->query('SELECT * FROM entrants ORDER BY EntrantID');

	$lnk = '<a href="'.$HOME_URL.'">';
    $isOdoCheck = isset($_REQUEST['odocheck']);

    if ($isOdoCheck) {
        $checkout = "odo check/start";
        $checkin = "ODO CHECK";
        $checkoutname = "OdoCheckStart";
        $checkinname = "OdoCheckFinish";
        $disabledstop = '';
        $stoptab = "0";
    } else {
        $checkout = "check-out/start";
        $checkin = "check-in/finish";
        $checkoutname = "OdoRallyStart";
        $checkinname = "OdoRallyFinish";
        $disabledstop = ' disabled ';
        $stoptab = "-1";
    }

	startHtml($TAGS['OdoReadingHdr'][0],$TAGS['OdoReadingHdr'][1],true);

	
	eval("\$evs = ".$TAGS['EntrantStatusV'][0]);

?>
<script>
    function oi(obj) {
        obj.style.background = "var(--bright-background)";
    }
    function oc(obj) {
        let tr = obj.parentNode.parentNode;
        let ent = tr.cells[0].innerText;
        let url = "fastodos.php?c=setodo&e="+ent+'&f='+obj.name+'&v='+obj.value
        let xhttp = new XMLHttpRequest();
    	xhttp.onreadystatechange = function() {
	    	let ok = new RegExp("\W*ok\W*");
		    if (this.readyState == 4 && this.status == 200) {
			    console.log('{'+this.responseText+'}');
			    if (!ok.test(this.responseText)) {
				    
				    alert(UPDATE_FAILED);
			    } else {
                    obj.style.background = "var(--regular-background)";
                }
		    }
	    };
        console.log(url);
	    xhttp.open("GET", url, true);
	    xhttp.send();
	}
    function swapss(rad) {
        console.log(rad.value);
        let frm = document.querySelector('#ssbuttons');
        let inps = frm.querySelectorAll('input');
        for (let i = 0; i < inps.length; i++) {
            inps[i].disabled = !inps[i].classList.contains(rad.value);
            inps[i].setAttribute('tabindex',inps[i].classList.contains(rad.value) ? "0" : "-1");
        }
        let hdr = document.querySelector('#sshdr');
        let labs = hdr.querySelectorAll('label');
        for (let i = 0; i < labs.length; i++) {
            labs[i].style.textTransform = labs[i].classList.contains(rad.value) ? "uppercase" : "lowercase";
        }
    }
</script>
<?php
    echo('<div style="width:36em; margin-left:auto; margin-right: auto;">');
    echo('<div id="sshdr" style="width:100%;text-align:center;height:10vh;"><br>');
    echo('<form>');
    $chk = "checked";
    /*
    if ($isOdoCheck) {
        echo('<input type="radio" checked onchange="swapss(this);" name="startstop" id="ss_trip" value="trip"> ');
        echo('<label class="trip" for="ss_trip">ODO CHECK TRIP</label> ');
        echo(' &nbsp;&nbsp;&nbsp; ');    
        $chk = '';
    }
    */
    if (!$isOdoCheck) {
        echo('<input type="radio" '.$chk.' onchange="swapss(this);" name="startstop" id="ss_start" value="start"> ');
        $startlit = $chk != '' ? strtoupper($checkout) : $checkout;
        echo('<label class="start" for="ss_start">'.$startlit.'</label> ');
        echo(' &nbsp;&nbsp;&nbsp; ');
        $chk = '';
    }
    echo('<input type="radio" '.$chk.' onchange="swapss(this);" name="startstop" id="ss_stop" value="stop"> ');
    echo('<label class="stop" for="ss_stop">'.$checkin.'</label> ');
    echo('</form>');
    echo('<br></div>');


    echo('<div id="picklistdiv" style="min-height:80vh;">');
    echo('<form action="fastodos.php" method="post">');
    echo('<table>');
    echo('<tbody id="ssbuttons">');
    $rowspresent = false;
    $startstop = $isOdoCheck ? 'stop' : 'start';
    while($rd = $R->fetchArray()) {
        echo('<tr>');
        echo('<td class="EntrantID">'.$rd['EntrantID'].'</td>');
        echo('<td>'.$rd['RiderName'].'</td>');
        echo('<td><input type="number" placeholder="start" name="'.$checkoutname.'" min="0" tabindex="0" class="bignumber '.$startstop.'" onchange="oc(this);" oninput="oi(this);" value="'.$rd[$checkoutname].'"></td>');
        echo('<td><input type="number" '.$disabledstop.' placeholder="finish" name="'.$checkinname.'" min="0" tabindex="'.$stoptab.'" class="bignumber stop" onchange="oc(this);" oninput="oi(this);" value="'.$rd[$checkinname].'"></td>');
        if ($isOdoCheck) {
            echo('<td><input type="number" placeholder="nn.n" name="OdoCheckTrip" min="0" tabindex="0" class="stop" onchange="oc(this);" oninput="oi(this);" value="'.$rd['OdoCheckTrip'].'"></td>');
        }
        echo('<td>');
        echo('<select name="OdoKms" id="OdoKms" onchange="oc(this);">');
        if ($rd['OdoKms']==$KONSTANTS['OdoCountsKilometres']) {
            echo('<option value="'.$KONSTANTS['OdoCountsMiles'].'">'.'M'.'</option>');
            echo('<option value="'.$KONSTANTS['OdoCountsKilometres'].'" selected >'.'K'.'</option>');
        } else {
            echo('<option value="'.$KONSTANTS['OdoCountsMiles'].'" selected >'.'M'.'</option>');
            echo('<option value="'.$KONSTANTS['OdoCountsKilometres'].'" >'.'K'.'</option>');
        }
        echo('</select>');        
        echo('</td>');
        echo('</tr>');
        $rowspresent = true;
    }
    echo('</tbody>');
    echo('</table>');
    echo('</form>');
    if (!$rowspresent)
        echo('<span style="font-size:2vw;">&#128530;</span>');
    echo('</div>');

    echo('</div>');

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
    switch($_REQUEST['f']) {
        case 'OdoRallyStart':
        case 'OdoRallyFinish':
        case 'OdoCheckStart':
        case 'OdoCheckFinish':
        case 'OdoCheckTrip':
        case 'OdoKms':
            break;
        default:
            echo('');
            return;
    }
    $DB->exec("BEGIN TRANSACTION");
	$sql = "UPDATE entrants SET ".$_REQUEST['f']."=".$_REQUEST['v'];
	$sql .= " WHERE EntrantID=".$_REQUEST['e'];
	$updateok = ($DB->exec($sql) && $DB->changes()==1);
    if ($updateok) {
        recalcDistance($_REQUEST['e']);
        recalcScorecard($_REQUEST['e'],true);
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
