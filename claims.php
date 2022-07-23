<?php
//error_reporting(E_ALL);
//ini_set('display_errors', 1);

/*
 * I B A U K   -   S C O R E M A S T E R
 *
 * I handle the claims log
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
 *
 */

$HOME_URL = "admin.php";
require_once('common.php');
require_once('recalc.php');
require_once('scoring.php');


function emitClaimsJS()
{
?>

<script>
//<!--
function showCurrentClaim(obj)
{
	let claimid = obj.parentNode.getAttribute('data-rowid');
	let url = "claims.php?c=showclaim&claimid="+claimid+'&dd=';
	url += document.getElementById('decisiondefault').value;
	url += '&showe='+document.getElementById('showe').value;
	url += '&showb='+document.getElementById('showb').value;
	window.location.href = url;
}
function updateClaimApplied(obj)
{
	let val = obj.checked ? 1 : 0;
	let oldval = !obj.checked;
	let rowid = obj.parentNode.parentNode.getAttribute('data-rowid');
	let xhttp = new XMLHttpRequest();
	xhttp.onreadystatechange = function() {
		let ok = new RegExp("\W*ok\W*");
		if (this.readyState == 4 && this.status == 200) {
			console.log('{'+this.responseText+'}');
			if (!ok.test(this.responseText)) {
				obj.checked = oldval;
				alert(UPDATE_FAILED);
			}
		}
	};
	xhttp.open("GET", "claims.php?c=applyclaim&claim="+rowid+'&val='+val, true);
	xhttp.send();
	
}
function updateClaimDecision(obj)
{
	let val = obj.value;
	let oldval = obj.getAttribute('data-oldval');
	console.log(val+' == '+oldval);
	let rowid = obj.parentNode.parentNode.getAttribute('data-rowid');
	let xhttp = new XMLHttpRequest();
	xhttp.onreadystatechange = function() {
		let ok = new RegExp("\W*ok\W*");
		if (this.readyState == 4 && this.status == 200) {
			console.log('{'+this.responseText+'}');
			if (!ok.test(this.responseText)) {
				obj.value = oldval;
				alert(UPDATE_FAILED);
			}
			else
				obj.setAttribute('data-oldval',obj.value);
		}
	};
	xhttp.open("GET", "claims.php?c=decideclaim&claim="+rowid+'&val='+val, true);
	xhttp.send();
	
}
function updateDD(obj)
{
	let val = obj.value;
	let xhttp = new XMLHttpRequest();
	xhttp.onreadystatechange = function() {
		let ok = new RegExp("\W*ok\W*");
		if (this.readyState == 4 && this.status == 200) {
			console.log('{'+this.responseText+'}');
		}
	};
	xhttp.open("GET", "claims.php?c=updatedd&"+'&val='+val, true);
	xhttp.send();
	
}
function updateDDate(obj)
{
	let val = obj.value;
	let xhttp = new XMLHttpRequest();
	xhttp.onreadystatechange = function() {
		let ok = new RegExp("\W*ok\W*");
		if (this.readyState == 4 && this.status == 200) {
			console.log('{'+this.responseText+'}');
		}
	};
	xhttp.open("GET", "claims.php?c=updateddate&"+'&val='+val, true);
	xhttp.send();
	
}

function updateFA(obj)
// Filter by applied changed
{
	let val = obj.value;
	console.log('updateFA: '+val);
	let xhttp = new XMLHttpRequest();
	xhttp.onreadystatechange = function() {
		let ok = new RegExp("\W*ok\W*");
		if (this.readyState == 4 && this.status == 200) {
			console.log('{'+this.responseText+'}');
			document.getElementById('refreshlist').click();			
		}
	};
	xhttp.open("GET", "claims.php?c=updatefa&"+'&val='+val, true);
	xhttp.send();
	
}


function updateFD(obj)
// Filter by decision changed
{
	let val = obj.value;
	console.log('updateFD: '+val);
	let xhttp = new XMLHttpRequest();
	xhttp.onreadystatechange = function() {
		let ok = new RegExp("\W*ok\W*");
		if (this.readyState == 4 && this.status == 200) {
			console.log('{'+this.responseText+'}');
			document.getElementById('refreshlist').click();			
		}
	};
	xhttp.open("GET", "claims.php?c=updatefd&"+'&val='+val, true);
	xhttp.send();
	
}

//-->
</script>
<?php	
}

function deleteClaim()
{
	global $DB,$TAGS,$KONSTANTS;
		
	$sql = "DELETE FROM claims WHERE rowid=".$_REQUEST['claimid'];
	if (!$DB->exec($sql)) {
		dberror();
		exit;
	}
	if ($DB->lastErrorCode()<>0) {
		echo("SQL ERROR: ".$DB->lastErrorMsg().'<hr>'.$sql.'<hr>');
		exit;
	}
	
}

function listclaims()
{
	global $DB,$TAGS,$KONSTANTS;
	
	$virtualrally = getValueFromDB("SELECT isvirtual FROM rallyparams","isvirtual",0) != 0;
	$todaysDate = date('Y-m-d');
	$rallyStartDate = substr(getValueFromDB("SELECT StartTime FROM rallyparams","StartTime",$todaysDate),0,10);
	$rallyFinishDate = substr(getValueFromDB("SELECT FinishTime FROM rallyparams","FinishTime",$todaysDate),0,10);
	$defaultDate = ($todaysDate<$rallyStartDate ? $rallyStartDate : ($todaysDate>$rallyFinishDate ? $rallyFinishDate : $todaysDate));
	$rr = explode("\n",str_replace("\r","",getValueFromDB("SELECT RejectReasons FROM rallyparams","RejectReasons","1=1")));
	$decisions = [];
	$decisions['0'] = $TAGS['BonusClaimOK'][0];
	foreach($rr as $rt) {
		$rtt = explode('=',$rt);
		if (isset($rtt[1]))
			$decisions[$rtt[0]] = $rtt[1];
	}

	$decided = isset($_REQUEST['showd']) ? intval($_REQUEST['showd']) : (isset($_SESSION['fd']) ? $_SESSION['fd'] : $KONSTANTS['showAll']);
	$applied = isset($_REQUEST['showa']) ? intval($_REQUEST['showa']) : (isset($_SESSION['fa']) ? $_SESSION['fa'] : $KONSTANTS['showNot']);

	$sql = "SELECT Count(*) AS rex FROM claims";
	$sqlw = '';
	if (isset($_REQUEST['showe']) && $_REQUEST['showe']!='')
		$sqlw .= "EntrantID=".$_REQUEST['showe'];
	if (isset($_REQUEST['showb']) && $_REQUEST['showb']!='')
		$sqlw .= ($sqlw != ''? ' AND ' : '')."Bonusid='".$DB->escapeString(strtoupper($_REQUEST['showb']))."'";
	if ($decided != $KONSTANTS['showAll']) {
		$sqlw .= ($sqlw != '' ? ' AND ' : '');
		$sqlw .= 'Decision';
		if ($decided==$KONSTANTS['showNot'])
			$sqlw .=  '='.$KONSTANTS['UNDECIDED_CLAIM'];
		elseif ($decided >= 10)
			$sqlw .= '='. ($decided - 10);
		else
			$sqlw .= '<> '.$KONSTANTS['UNDECIDED_CLAIM'];
	}
	if ($applied != $KONSTANTS['showAll'])
		$sqlw .= ($sqlw != '' ? ' AND ' : '').'Applied'.($applied==$KONSTANTS['showNot'] ? '=0' : '<>0');

	if ($sqlw !='')
		$sql .= " WHERE ".$sqlw;

		$R = $DB->query($sql);
	if ($DB->lastErrorCode() <> 0)
		echo($DB->lastErrorMsg().'<br>'.$sql.'<hr>');
	$rd = $R->fetchArray();
	$rex = ($rd ? $rd['rex'] :-1);
	$sql = "SELECT rowid, * FROM claims";
	if ($sqlw !='')
		$sql .= " WHERE ".$sqlw;
	$sql .= " ORDER BY LoggedAt DESC";
//	echo($sql);
	$R = $DB->query($sql);
	if ($DB->lastErrorCode() <> 0)
		echo($DB->lastErrorMsg().'<br>'.$sql.'<hr>');
	startHtml($TAGS['ttClaims'][0],$TAGS['AdmClaims'][0]);
	emitClaimsJS();
	
	echo('<p>'.$TAGS['cl_ClaimsBumf'][1].'</p>');
	
	echo('<div id="listctrl">');
	echo('<form method="get" action="claims.php">');
	echo('<input id="refreshc" type="hidden" name="c" value="listclaims">');
	echo('<input type="hidden" name="nobc" value="1">');

	echo('<span title="'.$TAGS['cl_DDLabel'][1].'" style="font-size:small;">');
	echo('<label for="decisiondefault">'.$TAGS['cl_DDLabel'][0].'</label>');
	echo('<select id="decisiondefault" name="dd" onchange="updateDD(this);" style="font-size:small;"> ');
	echo('<option value="-1" '.(isset($_SESSION['dd']) && $_SESSION['dd']=='-1'?'selected':'').'>'.$TAGS['BonusClaimUndecided'][0].'</option>');
	echo('<option value="0" '.(!isset($_SESSION['dd']) || $_SESSION['dd']=='0'?'selected':'').'>'.$TAGS['BonusClaimOK'][0].'</option>');
	echo('</select> ');
	echo('<input type="date" style="font-size:small;" name="ddate" value="'.(isset($_SESSION['ddate'])?$_SESSION['ddate']:$defaultDate).'" onchange="updateDDate(this);"> ');
	echo('</span>');
?>
<script>
function chooseRefresh() {
	let btn = document.getElementById('refreshlist');
	if (btn) {
		btn.setAttribute('default',1);
	}
}
function clickDefault() {
	console.log("clickDefault called");
	let btn = document.getElementById('refreshlist');
	if (btn && btn.hasAttribute('default')) {
		console.log("Clicking refresh");
		document.getElementById('refreshc').value='listclaims';
	} else {
		console.log("Clicking new claim");
		document.getElementById('refreshc').value='shownew';
	}
	return true;
}
</script>
<?php
	echo('<input autofocus style="font-size:large;padding:1em;" onclick="return clickDefault();" type="submit" title="'.$TAGS['cl_PostNewClaim'][1].'" value="'.$TAGS['cl_PostNewClaim'][0].'"> ');

	echo('<span>'.$TAGS['cl_FilterLabel'][0].'</span> ');
	echo('<input type="number" placeholder1="'.$TAGS['cl_FilterEntrant'][0].'" title="'.$TAGS['cl_FilterEntrant'][1].'" id="showe" onchange="chooseRefresh();" name="showe" value="'.(isset($_REQUEST['showe'])? $_REQUEST['showe']:'').'"> ');
	echo('<input type="text" placeholder1="'.$TAGS['cl_FilterBonus'][0].'" title="'.$TAGS['cl_FilterBonus'][1].'" id="showb" onchange="chooseRefresh();" name="showb" value="'.(isset($_REQUEST['showb'])? $_REQUEST['showb']:'').'"> ');
	
	$mybc = "<a href='claims.php'>".$TAGS['AdmClaims'][0]."</a>";

	
	echo('<select name="showd" style="font-size: small;" title="'.$TAGS['cl_showAllD'][1].'" onchange="updateFD(this);"> ');
	echo('<option value="'.$KONSTANTS['showAll'].'" '.($decided==$KONSTANTS['showAll'] ? 'selected' : '').'>'.$TAGS['cl_showAllD'][0].'</option>');
	echo('<option value="'.$KONSTANTS['showOnly'].'" '.($decided==$KONSTANTS['showOnly'] ? 'selected' : '').'>'.$TAGS['cl_showOnlyD'][0].'</option>');
	echo('<option value="'.$KONSTANTS['showNot'].'" '.($decided==$KONSTANTS['showNot'] ? 'selected' : '').'>'.$TAGS['cl_showNotD'][0].'</option>');
	foreach($decisions as $di => $dx) {
		echo('<option value="'.($di+10).'" '.($decided==$di+10 ? 'selected' : '').'>'.$dx.'</option>');
	}
	echo('</select> ');
	
	echo('<select name="showa" style="font-size: small;" title="'.$TAGS['cl_showAllA'][1].'" onchange="updateFA(this);"> ');
	echo('<option value="'.$KONSTANTS['showAll'].'" '.($applied==$KONSTANTS['showAll'] ? 'selected' : '').'>'.$TAGS['cl_showAllA'][0].'</option>');
	echo('<option value="'.$KONSTANTS['showOnly'].'" '.($applied==$KONSTANTS['showOnly'] ? 'selected' : '').'>'.$TAGS['cl_showOnlyA'][0].'</option>');
	echo('<option value="'.$KONSTANTS['showNot'].'" '.($applied==$KONSTANTS['showNot'] ? 'selected' : '').'>'.$TAGS['cl_showNotA'][0].'</option>');
	echo('</select> ');
	
	echo('<span title="'.$TAGS['cl_NumClaims'][1].'">'.$rex.' </span> ');
	
	echo('<input type="submit" id="refreshlist" onclick="chooseRefresh();clickDefault();" title="'.$TAGS['cl_RefreshList'][1].'" value="'.$TAGS['cl_RefreshList'][0].'"> ');
	

	if (strtolower(getSetting('claimsShowPost',"true"))=='true') {
		$go = "window.location.href='claims.php?c=applyclaims';";
		echo(' <input type="button" title="'.$TAGS['cl_ApplyBtn'][1].'" value="'.$TAGS['cl_ApplyBtn'][0].'" onclick="'.$go.'";>');
	}
	echo('</form>');

	echo('</div>');



	echo('<table><thead class="listhead">');
	echo('<tr><th>'.$TAGS['cl_EntrantHdr'][0].'</th><th>'.$TAGS['cl_BonusHdr'][0].'</th><th>'.$TAGS['cl_OdoHdr'][0].'<th>'.$TAGS['cl_ClaimedHdr'][0].'</th>');
	echo('<th>'.$TAGS['cl_DecisionHdr'][0].'</th>');
	if ($virtualrally) {
		echo('<th title="'.$TAGS['cl_PenaltyFuel'][1].'">'.$TAGS['cl_PenaltyFuel'][0].'</th>');
		echo('<th title="'.$TAGS['cl_PenaltySpeed'][1].'">'.$TAGS['cl_PenaltySpeed'][0].'</th>');
		echo('<th title="'.$TAGS['cl_PenaltyMagic'][1].'">'.$TAGS['cl_PenaltyMagic'][0].'</th>');
	}
	echo('<th>'.$TAGS['cl_AppliedHdr'][0].'</th><th>'.$TAGS['cl_LoggedHdr'][0].'</th></tr>');
	echo('<tbody>');
	while ($rd = $R->fetchArray()) {
		echo('<tr class="link" data-rowid="'.$rd['rowid'].'">');
		echo('<td title="'.$rd['EntrantID'].'" onclick="showCurrentClaim(this);" class="clickme">');
		$ename = getValueFromDB("SELECT RiderName FROM entrants WHERE EntrantID=".$rd['EntrantID'],"RiderName",$rd['EntrantID']);
		echo(htmlspecialchars($ename).' </td>');
		echo('<td title="');
		fetchBonusName($rd['BonusID'],false);
		echo('" onclick="showCurrentClaim(this);"> '.$rd['BonusID'].' </td>');
		echo('<td onclick="showCurrentClaim(this);"> '.$rd['OdoReading'].' </td>');
		echo('<td onclick="showCurrentClaim(this);"> '.logtime($rd['ClaimTime']).' </td>');
		if ($rd['Applied']==0) {
			$status = '<select onchange="updateClaimDecision(this);">';
			$status .= '<option value="-1"'.($rd['Decision']==$KONSTANTS['UNDECIDED_CLAIM']? ' selected' : '').'>'.$TAGS['BonusClaimUndecided'][0].'</option>';
			for ($i=0; $i<10; $i++)
				$status .= '<option value="'.$i.'"'.($rd['Decision']==$i? ' selected' :'').'>'.$decisions[$i].'</option>';
			$status .= '</select>';
		} else
			$status = ($rd['Decision'] != $KONSTANTS['UNDECIDED_CLAIM'] ? $decisions[$rd['Decision']] : $TAGS['BonusClaimUndecided'][0]);
		echo('<td> '.$status.' </td>');	
		if ($virtualrally) {
			echo('<td onclick="showCurrentClaim(this);" title="'.$TAGS['cl_PenaltyFuel'][1].'">'.($rd['FuelPenalty'] != 0 ? '&nbsp;*' : '').'</td>');
			echo('<td onclick="showCurrentClaim(this);" title="'.$TAGS['cl_PenaltySpeed'][1].'">'.($rd['SpeedPenalty'] != 0 ? '&nbsp;*' : '').'</td>');
			echo('<td onclick="showCurrentClaim(this);" title="'.$TAGS['cl_PenaltyMagic'][1].'">'.($rd['MagicPenalty'] != 0 ? '&nbsp;*' : '').'</td>');
		}
		echo('<td title="'.$TAGS['cl_Applied'][1].'" style="text-align:center;">'.'<input type="checkbox" '.($rd['Decision']==$KONSTANTS['UNDECIDED_CLAIM']? ' disabled ' : '').' onchange="updateClaimApplied(this);" value="1"'.($rd['Applied']!=0? ' checked' :'').'>');
		echo('<td onclick="showCurrentClaim(this);"> '.logtime($rd['LoggedAt']).'</td>');
		echo('</tr>');
	}
	echo('</tbody></table>');
?>
<script>
setTimeout(function(){document.getElementById('refreshlist').click();},120000);
</script>
<?php

	
}


function parseTimeMins($tx)
/*
 * I accept a string containing a duration specification which
 * I return as an integral number of minutes. I expect hours and
 * minutes or just minutes, entered in a variety of formats.
 *
 */
{
	if (preg_match('/(\d*)[alpha|blank|:|.]+(\d*)/',$tx,$matches)) {
		$hh = $matches[1];
		$mm = $matches[2];
	} else {
		$hh = 0;
		$mm = intval($tx);
	}
//	echo('hh='.$hh.' mm='.$mm.'<br>');
	return $hh * 60 + $mm;
	
}

function saveClaim()
{
	global $DB,$TAGS,$KONSTANTS;
	
	//print_r($_REQUEST);

	if (!$DB->exec("BEGIN IMMEDIATE TRANSACTION")) {
		dberror();
		exit;
	}

	$virtualrally = false;
	$virtualstopmins = 0;
	$R = $DB->query("SELECT * FROM rallyparams");
	if ($rd = $R->fetchArray()) {
		$virtualrally = ($rd['isvirtual'] != 0);
		$virtualstopmins = $rd['stopmins'];
	}
	$XF = ['FuelBalance','SpeedPenalty','FuelPenalty','MagicPenalty','Points','RestMinutes','AskPoints','AskMinutes'];
	
	$dtn = new DateTime(Date('Y-m-dTH:i:s'),new DateTimeZone($KONSTANTS['LocalTZ']));
	$datenow = $dtn->format('c');
	$la = (isset($_REQUEST['LoggedAt']) && $_REQUEST['LoggedAt'] != '' ? $_REQUEST['LoggedAt'] : $datenow);


	$cd = (isset($_REQUEST['ClaimDate']) && $_REQUEST['ClaimDate'] != '' ? $_REQUEST['ClaimDate'] : date('Y-m-d'));
	$claimtime = (isset($_REQUEST['ClaimTime']) && $_REQUEST['ClaimTime'] != '' ? joinDateTime($cd,$_REQUEST['ClaimTime']) : $la);


	$claimid = isset($_REQUEST['claimid']) ? intval($_REQUEST['claimid']) : 0;
	if ($claimid <= 0) 
		$claimid = saveNewClaim($virtualrally,$virtualstopmins,$XF,$claimtime);
	else
		saveOldClaim($virtualrally,$virtualstopmins,$XF,$claimtime,$claimid);

	//echo('Applying claim #'.$claimid.'<br>');
	//exit;
	applyClaim($claimid,true);
	//echo('Applied that then!<br>');

	updateTeamScorecards($_REQUEST['EntrantID']);
    rankEntrants(true);
    updateAutoClass($_REQUEST['EntrantID']);

	$DB->exec("COMMIT TRANSACTION");

	$get = "claims.php";
	header("Location: ".$get);
	exit;

}

function saveOldClaim($virtualrally,$virtualstopmins,$XF,$claimtime,$claimid)
{
	global $DB,$TAGS,$KONSTANTS;
	
	$sql = "UPDATE claims SET ";
	$sql = '';
	$sql .= "ClaimTime='".$claimtime."'";

	foreach (['BonusID'] as $fld)
		if (isset($_REQUEST[$fld]))
			$sql .= ($sql==''? '' : ',').$fld."='".$DB->escapeString(strtoupper($_REQUEST[$fld]))."'";
	foreach (['BCMethod','EntrantID','OdoReading','Decision','Applied','FuelBalance'] as $fld)
		if (isset($_REQUEST[$fld]))
			$sql .= ($sql==''? '' : ',').$fld."=".intval($_REQUEST[$fld]);
	//echo("[[ $sql ]]");

	if (isset($_REQUEST['MagicWord']))
		$sql .= ($sql==''? '' : ',')."MagicWord='".$DB->escapeString($_REQUEST['MagicWord'])."'";
	
	if (isset($_REQUEST['NextTimeMins'])){
		$mins = parseTimeMins($_REQUEST['NextTimeMins']);
		$sql .= ($sql==''? '' : ',').'NextTimeMins='.$mins;
	}

	if (isset($_REQUEST['QuestionAsked']))
		$sql .= ($sql==''? '' : ',')."QuestionAsked=".$_REQUEST['QuestionAsked'];
	if (isset($_REQUEST['QuestionAnswered']))
		$sql .= ($sql==''? '' : ',')."QuestionAnswered=1";
	else
		$sql .= ($sql==''? '' : ',')."QuestionAnswered=0";
	if (isset($_REQUEST['AnswerSupplied']))
		$sql .= ($sql==''? '' : ',')."AnswerSupplied='".$DB->escapeString($_REQUEST['AnswerSupplied'])."'";

	foreach($XF as $F)
		if (isset($_REQUEST[$F]))
			$sql .= ($sql==''? '' : ',').$F.'='.intval($_REQUEST[$F]);
		else
			$sql .= ($sql==''? '' : ',').$F.'=0';
	if ($sql=='')
		return;
	$sql.= " WHERE rowid=".$claimid;
	
	//echo('<p style="overflow-x:scroll;">'.$sql.'</p>');
	
	if (!$DB->exec("UPDATE claims SET ".$sql)) {
		dberror();
		exit;
	}
	if ($DB->lastErrorCode()<>0) {
		echo("SQL ERROR: ".$DB->lastErrorMsg().'<hr>'.$sql.'<hr>');
		exit;
	}
	
}

function saveNewClaim($virtualrally,$virtualstopmins,$XF,$claimtime)
{
	global $DB,$TAGS,$KONSTANTS;
	
//	print_r($_REQUEST);
		
	$sql = "INSERT INTO claims (LoggedAt,ClaimTime,BCMethod,EntrantID,BonusID,OdoReading,Decision,Applied";
	if (isset($_REQUEST['MagicWord'])) 
		$sql .= ",MagicWord";
	if (isset($_REQUEST['NextTimeMins'])) 
		$sql .= ",NextTimeMins";
	if (isset($_REQUEST['QuestionAsked']))
		$sql .= ",QuestionAsked";
	$sql .= ",QuestionAnswered"; // Checkbox, only present when checked
	if (isset($_REQUEST['AnswerSupplied']))
		$sql .= ",AnswerSupplied";
	foreach ($XF as $F)
		if (isset($_REQUEST[$F]))
			$sql .= ",$F";
	$sql .= ") VALUES(";

	$dtn = new DateTime(Date('Y-m-dTH:i:s'),new DateTimeZone($KONSTANTS['LocalTZ']));
	$datenow = $dtn->format('c');
	$la = (isset($_REQUEST['LoggedAt']) && $_REQUEST['LoggedAt'] != '' ? $_REQUEST['LoggedAt'] : $datenow);
	$sql .= "'".$la."'";

	$sql .= ",'".$claimtime."'";
	$sql .= ",".(isset($_REQUEST['BCMethod']) ? $_REQUEST['BCMethod'] : $KONSTANTS['BCM_EBC']);
	$sql .= ",".intval($_REQUEST['EntrantID']);
	$sql .= ",'".$DB->escapeString(strtoupper($_REQUEST['BonusID']))."'";
	$sql .= ",".intval($_REQUEST['OdoReading']);
	$sql .= ",".(isset($_REQUEST['Decision']) ? $_REQUEST['Decision'] : 0);
	$sql .= ",".(isset($_REQUEST['Applied']) ? $_REQUEST['Applied'] : 0);
	
	if (isset($_REQUEST['MagicWord']))
		$sql .= ",'".$DB->escapeString($_REQUEST['MagicWord'])."'";
	
	if (isset($_REQUEST['NextTimeMins'])){
		$mins = parseTimeMins($_REQUEST['NextTimeMins']);
		if ($virtualrally)
			$mins += $virtualstopmins;
		$sql .= ",".$mins;
	}
	if (isset($_REQUEST['QuestionAsked']))
		$sql .= ",".$_REQUEST['QuestionAsked'];
	if (isset($_REQUEST['QuestionAnswered']))
		$sql .= ",1";
	else
		$sql .= ",0";
	if (isset($_REQUEST['AnswerSupplied']))
		$sql .= ",'".$DB->escapeString($_REQUEST['AnswerSupplied'])."'";

	foreach($XF as $F)
		if (isset($_REQUEST[$F]))
			$sql .= ",".intval($_REQUEST[$F]);
	
	$sql .= ")";
	//echo('<br>'.$sql.'<br>');
	if (!$DB->exec($sql)) {
		dberror();
		exit;
	}
	$sql = "SELECT last_insert_rowid() As rowid";
	$R = $DB->query($sql);
	if ($rd = $R->fetchArray())
		return $rd['rowid'];
	return -1;
}

function showClaim($claimid = 0)
{
	global $DB,$TAGS,$KONSTANTS;

	startHtml($TAGS['ttClaims'][0]);

	$R = $DB->query("SELECT StartTime,FinishTime FROM rallyparams");
	if ($rd = $R->fetchArray()) {
		echo('<input type="hidden" id="rallyStart" value="'.$rd['StartTime'].'">');
		echo('<input type="hidden" id="rallyFinish" value="'.$rd['FinishTime'].'">');
	}
	$virtualrally = false;
	$tankrange = 0;
	$refuelstops = 'NONE'; // re matching nothing
	$stopmins = 0;

	$sql = "SELECT * FROM rallyparams";
	$R = $DB->query($sql);
	if ($rd = $R->fetchArray()) 
		$virtualrally = ($rd['isvirtual'] != 0);
	if ($virtualrally) {
		$tankrange = $rd['tankrange'];
		$refuelstops = $rd['refuelstops'];
		$stopmins = $rd['stopmins'];
	}
	
	echo('<input type="hidden" id="virtualrally" value="'.($virtualrally ? 1 : 0).'">');
	echo('<input type="hidden" id="tankrange" value="'.$rd['tankrange'].'">'); 
	echo('<input type="hidden" id="refuelstops" value="'.$rd['refuelstops'].'">');
	echo('<input type="hidden" id="stopmins" value="'.$rd['stopmins'].'">');

	if ($claimid == 0 && $virtualrally) {		//Only use magic words to validate new claims
		$sql = "SELECT * FROM magicwords ORDER BY asfrom";
		$R = $DB->query($sql);
		while ($rd = $R->fetchArray()) 
			echo('<input type="hidden" name="mw" data-asfrom="'.$rd['asfrom'].'" value="'.$rd['magic'].'">');		
	}
	if ($claimid > 0) {  //Check that this is actually a claim record and not called with random number
		$R  = $DB->query("SELECT * FROM claims WHERE rowid=".$claimid);
		if (!$rd = $R->fetchArray())
			$claimid =0;
	} else
		$rd = defaultRecord('claims');

echo("<script>\n");
include 'claimsphp.js';
echo("</script>\n");

	echo('<div id="singleclaim">');

	echo('<form method="post" action="claims.php" onsubmit="return validateClaim(true);">');
	echo('<input type="hidden" name="c" value="newclaim">');
	echo('<input type="hidden" name="nobc" value="1">');
	echo('<input type="hidden" name="LoggedAt" value="">');
	echo('<input type="hidden" name="BCMethod" value="'.$KONSTANTS['BCM_EBC'].'">');
	echo('<input type="hidden" name="Applied" value="0">');
	echo('<input type="hidden" id="claimid" name="claimid" value="'.$claimid.'">');
	echo('<input type="hidden" name="dd" value="'.(isset($_REQUEST['dd']) ? $_REQUEST['dd'] : '-1').'">');
	echo('<input type="hidden" name="showa" value="'.(isset($_REQUEST['showa']) ? $_REQUEST['showa'] : $KONSTANTS['showAll']).'">');
	echo('<input type="hidden" name="showd" value="'.(isset($_REQUEST['showd']) ? $_REQUEST['showd'] : $KONSTANTS['showAll']).'">');
	echo('<input type="hidden" name="ddate" value="'.(isset($_REQUEST['ddate'])?$_REQUEST['ddate']:date('Y-m-d')).'">');
	if (isset($_REQUEST['showe']))
		echo('<input type="hidden" name="showe" value="'.$_REQUEST['showe'].'">');
	if (isset($_REQUEST['showb']))
		echo('<input type="hidden" name="showb" value="'.$_REQUEST['showb'].'">');
	echo('<input type="hidden" name="FuelBalance" id="saveFuelBalance" value="'.($claimid > 0 ? $rd['FuelBalance'] : 0 ).'">');
	
	echo('<div class="frmContent" style="max-width: 45em;">');
	
	echo('<p ondblclick="enableSaveButton();">'.$TAGS['cl_EditHeader'][0]);
	if ($claimid==0)
		echo(' '.$TAGS['cl_EditHeader'][1]);
	echo('</p>');
	
	echo('<span class="vlabel" title="'.$TAGS['EntrantID'][1].'"><label for="EntrantID">'.$TAGS['EntrantID'][0].'</label> ');
	echo('<input onpaste="pasteNewClaim();" autofocus type="number" name="EntrantID" id="EntrantID" tabindex="1" oninput="checkEnableSave();" onchange="showEntrant(this);"');
	echo(' value="'.($claimid > 0 ? $rd['EntrantID'] : '').'"> ');
	echo('<span id="EntrantName" style="display:inline-block; vertical-align: top;"');
	if ($claimid > 0) {
		echo(' data-ok="1">');
		fetchEntrantDetail($rd['EntrantID']);
	} else
		echo('>');
	echo('</span>');
	echo('</span>');
	echo('<span class="vlabel" title="'.$TAGS['BonusIDLit'][1].'"><label for="BonusID">'.$TAGS['BonusIDLit'][0].'</label> ');
	echo('<input type="text" name="BonusID" id="BonusID" tabindex="2" oninput="showBonus(this);checkEnableSave();" onchange="showBonus(this);"');
	echo(' value="'.($claimid> 0 ? $rd['BonusID'] : '').'"> ');
	echo('<span id="BonusName"');
	if ($claimid > 0) {
		echo(' data-ok="1">');
		fetchBonusName($rd['BonusID'],true);
	} else
		echo('>');
	echo('</span>');
	echo('</span>');

	if ($rd['Photo'] > '') {
		echo('<script>function cisz(img) {let szs = ["512px","100%"];let sz=parseInt(img.getAttribute("data-size"))+1;');
		echo('if (sz >= szs.length) sz = 0;img.style.width=szs[sz];img.setAttribute("data-size",sz);}</script>');
		echo('<div id="imgdiv" style="text-align: center; float: left; cursor: se-resize; border: solid;" title="'.$TAGS['ebc_JudgeThis'][1].'">');
		echo('<img onclick="cisz(this);" data-size="0" src="'.$rd['Photo'].'" alt="**" style="width:512px;"/>');
		echo('</div>');
		$bphoto = getValueFromDB("SELECT IfNull(Image,'') As Image FROM bonuses WHERE BonusID='".$rd['BonusID']."'",'Image','');
		if ($bphoto != '') {
			echo('<div id="bimgdiv" style="text-align: center; float:right;" title="'.$TAGS['ebc_RallyPhoto'][1].'">');
			echo(' <img onclick="cisz(this);" data-size="0" src="images/bonuses/'.rawurlencode($bphoto).'" style="width:512px;"/>');
			echo('</div>');
		}
		echo('<div style="clear:both;"></div>');
	}
	
	
	echo('<span class="vlabel" title="'.$TAGS['OdoReadingLit'][1].'"><label for="OdoReading">'.$TAGS['OdoReadingLit'][0].'</label> ');
	echo('<input type="number" class="bignumber" min="0" name="OdoReading" onkeypress="digitonly();" oninput="checkEnableSave();" id="OdoReading"');
	if ($claimid==0)
		echo(' onchange="odoChanged(this.value,false);"');
	echo(' tabindex="3"');
	echo(' value="'.($claimid > 0 ? $rd['OdoReading'] : '').'"> ');
	$ck = ($claimid > 0 && $rd['FuelPenalty'] != 0 ? ' checked ' : '');
	$ds = ($claimid > 0 && $rd['FuelPenalty'] != 0 ? 'inline' : 'none');
	echo('<span id="FuelWarning" style="display:'.$ds.';" title="'.$TAGS['FuelWarning'][1].'"> ');
	$fw = $TAGS['FuelWarning'][0].' <input type="checkbox" name="FuelPenalty" value="1"  oninput="checkEnableSave();" id="TickFW" '.$ck.'>';
	echo($fw);
	echo(' </span>');
	echo('</span> ');

	$ct = splitDatetime($claimid > 0 ? $rd['ClaimTime'] : (isset($_REQUEST['ddate'])?$_REQUEST['ddate']:date('Y-m-d')).'T ');
	echo('<span class="vlabel" title="'.$TAGS['BonusClaimTime'][1].'"><label for="ClaimTime">'.$TAGS['BonusClaimTime'][0].'</label> ');
	$oc = ($claimid == 0 ? ' onchange="checkSpeeding('.$virtualrally.');"' : '');
	echo('<input type="date" name="ClaimDate" id="ClaimDate" value="'.$ct[0].'" oninput="checkEnableSave();" tabindex="8"'.$oc.'> ');
	$ctz = substr(str_replace(".",":",trim($ct[1])),0,5);
	echo('<input type="time" name="ClaimTime" id="ClaimTime" value="'.$ctz.'" oninput="checkEnableSave();" tabindex="4"'.$oc.'> ');
	$ds = ($claimid > 0 && $rd['SpeedPenalty'] != 0 ? 'inline' : 'none');
	$oc = ($claimid > 0 && $rd['SpeedPenalty'] != 0 ? '&dzigrarr; <input type="checkbox" value="1" name="SpeedPenalty" oninput="checkEnableSave();" checked>' : '');
	echo('<span id="SpeedWarning" style="display:'.$ds.';">'.$oc.'</span>');
	echo('</span>');

	if (getSetting('useBonusQuestions','false')=='true') {
		echo('<input type="hidden" name="QuestionAsked" id="QuestionAsked" value="'.($claimid> 0 ? $rd['QuestionAsked'] : '0').'"> ');
		echo('<input type="hidden" id="valBonusQuestions" value="');
		echo(intval(getSetting('valBonusQuestions','0')).'">');
		$disp = ($claimid> 0 && $rd['QuestionAsked'] ? 'inline': 'none');
		echo('<span id="BonusAnswerSpan" class="vlabel" style="display:'.$disp.';" title="'.$TAGS['BonusAnswer'][1].'">');
		echo('<label for="AnswerSupplied">'.$TAGS['BonusAnswer'][0].'</label> ');
		echo('<input type="text" tabindex="5" oninput="checkEnableSave();" name="AnswerSupplied" id="AnswerSupplied" value="'.($claimid> 0 ? $rd['AnswerSupplied'] : '').'"> ');
		echo('<input type="checkbox" tabindex="6" id="QuestionAnswered" name="QuestionAnswered" onchange="answerQuestion(this);"');
		if ($claimid > 0 && $rd['QuestionAnswered'] != 0) {
			echo(' checked');
		}
		echo('> ');
		echo('<span id="CorrectAnswer">');
		if ($claimid> 0 && $rd['QuestionAsked'] != 0) {
			echo(getValueFromDB("SELECT Answer FROM bonuses WHERE BonusID='".$rd['BonusID']."'","Answer",""));
		}
		echo('</span>');
		echo('</span>');
	}
	
	if ($virtualrally) {
		echo('<span class="vlabel" title="'.$TAGS['NextTimeMins'][1].'"><label for="NextTimeMins">'.$TAGS['NextTimeMins'][0].'</label> ');
		echo('<input type="text" class="bignumber" name="NextTimeMins" id="NextTimeMins" value="'.($claimid>0?showTimeMins($rd['NextTimeMins']):'').'" oninput="checkEnableSave();" tabindex="5">');
		echo('</span>');
		
		echo('<span class="vlabel" id="magicspan" title="'.$TAGS['magicword'][1].'">');
		echo('<label for="magicword">'.$TAGS['magicword'][0].'</label> ');
		$oc =  ($claimid == 0 ? ' oninput="checkMagicWord();"' : '');
		echo('<input type="text" style="width:10em;" id="magicword" name="MagicWord"'.$oc.' value="'.($claimid > 0 ? $rd['MagicWord'] : '').'" oninput="checkEnableSave();" tabindex="5"> ');
		$oc = ($claimid > 0 && $rd['MagicPenalty'] != 0 ? ' &cross;  <input type="checkbox" value="1" name="MagicPenalty" checked>' : '');
		$cl = ($oc != '' ? ' class="yellow"' : '');
		echo('<span id="mwok"'.$cl.'>'.$oc.'</span>');
		echo('</span>');
	}

	$cl = $claimid != 0 && $rd['AskPoints'] != 0 ? 'vlabel' : 'hide';
	echo('<span class="'.$cl.'" id="pointsspan" title="'.$TAGS['BonusPoints'][1].'">');
	echo('<label for="PointsValue">'.$TAGS['BonusPoints'][0].'</label> ');
	$pv = ($claimid != 0) ? $rd['Points'] : '';
	echo('<input type="number" tabindex="5" name="Points" id="PointsValue" value="'.$pv.'">');
	echo('<input type="hidden" id="AskPoints" name="AskPoints" value="'.($claimid != 0 ? $rd['AskPoints'] : 0).'">');
	echo('</span>');
	
	$cl = $claimid != 0 && $rd['AskMinutes'] != 0 ? 'vlabel' : 'hide';
	echo('<span class="'.$cl.'" id="restspan" title="'.$TAGS['RestMinutesLit'][1].'">');
	echo('<label for="RestMinutes">'.$TAGS['RestMinutesLit'][0].'</label> ');
	$pv = ($claimid != 0) ? $rd['RestMinutes'] : '';
	echo('<input type="number" tabindex="5" name="RestMinutes" id="RestMinutes" value="'.$pv.'">');
	echo('<input type="hidden" id="AskMinutes" name="AskMinutes" value="'.($claimid != 0 ? $rd['AskMinutes'] : 0).'">');
	echo('</span>');
	

	echo('<span class="vlabel" title="'.$TAGS['BonusClaimDecision'][1].'"><label for="Decision">'.$TAGS['BonusClaimDecision'][0].'</label> ');
	echo('<select name="Decision" id="Decision" tabindex="7" style="font-size:smaller;" oninput="checkEnableSave();">');
	//$dnum = $claimid > 0 ? ($rd['Judged']!=0 ? $rd['Decision'] : -1) : (isset($_REQUEST['dd']) && $_REQUEST['dd']=='0' ? 0 : -1);
	$dnum = $claimid > 0 ? $rd['Decision'] : (isset($_REQUEST['dd']) ? $_REQUEST['dd'] : $KONSTANTS['UNDECIDED_CLAIM']);
	echo('<option value="-1" '.($dnum==-1 ? 'selected' : '').'>'.$TAGS['BonusClaimUndecided'][0].'</option>');
	echo('<option value="0" '.($dnum==0 ? 'selected' : '').'>'.$TAGS['BonusClaimOK'][0].'</option>');
	$rr = explode("\n",str_replace("\r","",getValueFromDB("SELECT RejectReasons FROM rallyparams","RejectReasons","1=1")));
	foreach($rr as $rt) {
		$rtt = explode('=',$rt);
		if ($rtt[0]!='')
			echo("\r\n".'<option value="'.$rtt[0].'" '.($dnum==$rtt[0] ? 'selected' : '').'>'.$rtt[1].'</option>');
	}
	echo('</select>');

	if (getSetting('useMagicPenalty','false')=='true') {
		echo('<input type="hidden" id="valMagicPenalty" value="');
		echo(intval(getSetting('valMagicPenalty','0')).'">');
		$chk = '';
		if ($rd['MagicPenalty']==1)
			$chk = ' checked ';
		echo('<span title="'.$TAGS['cl_MagicPenalty'][1].'">');
		echo(' <label for="MagicPenalty">'.$TAGS['cl_MagicPenalty'][0].'</label> ');
		echo('<input tabindex="9" '.$chk.'type="checkbox" id="MagicPenalty" name="MagicPenalty"');
		echo(' value="'.$rd['MagicPenalty'].'"');
		echo(' onchange="applyMagicPenalty(this);">');
		echo('</span>');
	}
	
	echo('</span>');

	echo('</div>'); // frmContent

	echo('<span class="vlabel" title="'.'"><label for="savedata">'.''.'</label>');
	echo('<input  disabled type="submit" id="savedata" data-altvalue="'.$TAGS['SaveRecord'][1].'" title="'.$TAGS['SaveRecord'][1].'" name="saveclaim" ');
	echo(' onclick="'."this.setAttribute('data-triggered','1');".'"');
	echo(' data-triggered="0" value="'.$TAGS['SaveRecord'][0].'" tabindex="7"> ');
	
	if ($claimid > 0) {
		echo(' &nbsp;&nbsp;<input type="checkbox" id="ReallyDelete" onchange="document.getElementById(\'deletebutton\').disabled=!this.checked;"> ');
		echo('<input disabled onclick="return document.getElementById(\'ReallyDelete\').checked;" type="submit" title="'.$TAGS['DeleteClaim'][1].'" name="deleteclaim" id="deletebutton" value="'.$TAGS['DeleteClaim'][0].'" tabindex="9"></span> ');
	}
	echo('</span>');
	echo('</form>');

	echo('</div> <!-- singleclaim -->');
	
	echo('</body></html>');
}

function showNewClaim()
{
	showClaim(0);
}

function showTimeMins($mins)
{
	$h = intval($mins / 60);
	$m = $mins % 60;
	$res = ($h > 0 ? ''.$h.'h ' : '');
	$res .= ($m > 0 ? ''.$m.'m' : '');
	return $res;
}

function fetchBonusName($b,$htmlok)
{
	global $DB,$TAGS,$KONSTANTS;

	if ($b=='') {
		echo('');
		return;
	}
	$R = $DB->query("SELECT BriefDesc, Points, RestMinutes, AskPoints, AskMinutes, Notes, Flags, Question, Answer FROM bonuses WHERE BonusID='".strtoupper($b)."'");
	if ($rd = $R->fetchArray()) {
		$res = '';
		if ($htmlok) {
			$res .= $rd['BriefDesc'];
			$res .= '<br><span id="ebc_notes">'.$rd['Notes'].'</span> ';
			$res .= '<span id="ebc_flags">';

			$flags = $rd['Flags'];
//			if (tr.getAttribute('data-team') > '0') {
//				flags += '2';
//			}
			for ($i = 0; $i < strlen($flags); $i++) {
				$img = '';
				 switch(substr($flags,$i,1)) {
					 case '2':
						$img = '<img src="images/alertteam.png" alt="2" title="Team rules" class="icon">';
						break;
					 case 'A':
						$img = '<img src="images/alertalert.png" alt="!" title="Read the notes!" class="icon">';
						break;
					 case 'B':
						$img = '<img src="images/alertbike.png" alt="B" title="Bike in photo" class="icon">';
						break;
					 case 'D':
						$img = '<img src="images/alertdaylight.png" alt="D" title="Daylight only" class="icon">';
						break;
					 case 'F':
						$img = '<img src="images/alertface.png" alt="F" title="Face in photo" class="icon">';
						break;
					 case 'R':
						$img = '<img src="images/alertrestricted.png" alt="R" title="Restricted access" class="icon">';
						break;
					 case 'T':
						$img = '<img src="images/alertreceipt.png" alt="T" title="Need a receipt (ticket)" class="icon">';
						break;
				 }
				 if ($img != '') {
					 $res .= $img;
				 }
				}
			$res .= '</span><br>';

			$res .= '<span style="display:none">[';
			$res .= 'ap='.$rd['AskPoints'].';pv='.$rd['Points'].';am='.$rd['AskMinutes'].';mv='.$rd['RestMinutes'];
			$res .= ']';
			$res .= '<span id="qqq">';
			$res .= str_replace('"','&quot;',$rd['Question']);
			$res .= '</span>';
			$res .= '<span id="aaa">';
			$res .= str_replace('"','&quot;',$rd['Answer']);
			$res .= '</span>';
			$res .= '</span>';
		} else {
			$res .= strip_tags($rd['BriefDesc']);
			$res .= ' ['.'ap='.$rd['AskPoints'].';pv='.$rd['Points'].';am='.$rd['AskMinutes'].';mv='.$rd['RestMinutes'].']';
		}
		echo($res);
	} else {
		$R = $DB->query("SELECT BriefDesc FROM combinations WHERE ComboID='".$b."'");
		if ($rd = $R->fetchArray()) {
			if ($htmlok)
				echo($rd['BriefDesc']);
			else
				echo(strip_tags($rd['BriefDesc']));
		} else
			echo('***');
	}
}

function updateClaimApplied()
{
	global $DB,$TAGS,$KONSTANTS;

	if (!isset($_REQUEST['claim']) || !isset($_REQUEST['val'])) {
		echo('');
		return;
	}
	$sql = "UPDATE claims SET Applied=".$_REQUEST['val'];
	$sql .= " WHERE rowid=".$_REQUEST['claim'];
	echo($DB->exec($sql) && $DB->changes()==1? 'ok' : 'error' );
}

function updateClaimDecision()
{
	global $DB,$TAGS,$KONSTANTS;

	if (!isset($_REQUEST['claim']) || !isset($_REQUEST['val'])) {
		echo('');
		return;
	}
	$sql = "UPDATE claims SET Decision=".$_REQUEST['val'];
	$sql .= " WHERE rowid=".$_REQUEST['claim'];
	$sql .= " AND Applied=0";
	echo($DB->exec($sql) && $DB->changes()==1? 'ok' : 'error');
}


function fetchEntrantDetail($e)
{
	global $DB,$TAGS,$KONSTANTS;
	
	if ($e=='') {
		echo('');
		return;
	}
	$virtualrally = getValueFromDB("SELECT isvirtual FROM rallyparams","isvirtual",0);
	$R = $DB->query("SELECT * FROM entrants WHERE EntrantID=".$e);
	if ($rd = $R->fetchArray()) {
		echo($rd['RiderName']);
		if ($rd['PillionName'] != '')
			echo(' &amp; '.$rd['PillionName']);

		if ($rd['TeamID'] > 0)
			echo(' <img src="images/alertteam.png" alt="2" title="Team rules" class="icon">');

		$tankrange = getValueFromDB("SELECT tankrange FROM rallyparams","tankrange",0);
		
		$sql = "SELECT * FROM claims WHERE EntrantID=".$e;
		$sql .= " ORDER BY ClaimTime DESC";
		$R = $DB->query($sql);
		$lastodo = 0;
		$lastct = '';
		$nextmins = 0;
		$lastbonusid = '';
		$lbcok = 0;
		if (($rd = $R->fetchArray()) && isset($rd['FuelBalance'])) {
			$fuelbalance = $rd['FuelBalance'];
			$lastodo = $rd['OdoReading'];
			$lastct = $rd['ClaimTime'];
			$nextmins = $rd['NextTimeMins'];
			$lastbonusid = $rd['BonusID'];
			$lbcok = $rd['Decision'];
		} else
			$fuelbalance = $tankrange;
			
		if ($virtualrally) {
			$lo = $tankrange * .10;
			$hi = $tankrange * .90; 
			//echo('<br>');
			echo('  <label for="FuelBalance">'.$TAGS['FuelBalance'][0].'</label> ');
			echo('  <meter title="'.$fuelbalance.'" id="FuelBalance" low="'.$lo.'" high="'.$hi.'"');
			echo('min="0" max="'.$tankrange.'" data-value="'.$fuelbalance.'" value="'.$fuelbalance.'"> ['.$fuelbalance.']</meter>');
			
		} 
			
		if ($lastbonusid <> '') {
			echo('<span style="display:none" id="LastBonusClaimed">');
			echo(' <span title="'.$TAGS['cl_LastBonusID'][1].'">'.$TAGS['cl_LastBonusID'][0].' ');
			$res = '';
			switch($lbcok) {
				case -1:
					$res = $TAGS['BonusClaimUndecided'][0];
					break;
				case 0:
					$res = $TAGS['BonusClaimOK'][0];
					break;
				default:
					$rr = explode("\n",str_replace("\r","",getValueFromDB("SELECT RejectReasons FROM rallyparams","RejectReasons","1=1")));
					$res = $rr[$lbcok - 1];			

			}
		
			echo('<strong>'.$lastbonusid.' - '.$res.'</strong></span> ');
			echo('</span>');
		}

		
		echo('<input type="hidden" id="lastOdoReading" value="'.$lastodo.'">');
		echo('<input type="hidden" id="lastClaimTime" value="'.$lastct.'">');
		echo('<input type="hidden" id="lastNextMins" value="'.$nextmins.'">');	
	} else {
		echo('***');
	}

}


function applyClaimsForm()
{
	global $TAGS;
	
	$reprocess = (isset($_REQUEST['reprocess']) && $_REQUEST['reprocess'] > '0');
	startHtml($TAGS['cl_ClaimsTitle'][0]);
	if ($reprocess) {
		echo('<h3>'.$TAGS['cl_ReprocessHdr'][0].'</h3>');
		echo('<p>'.$TAGS['cl_ReprocessHdr'][1].'</p>');	
	} else {
		echo('<h3>'.$TAGS['cl_ApplyHdr'][0].'</h3>');
		echo('<p>'.$TAGS['cl_ApplyHdr'][1].'</p>');
	}
	echo('<form action="claims.php" method="post">');
	echo('<input type="hidden" name="c" value="applyclaims">');
	$lodate = date("Y-m-d");
	$lodate = substr(getValueFromDB("SELECT StartTime FROM rallyparams","StartTime",$lodate),0,10);

	$hidate = date("Y-m-d");
	if ($hidate < $lodate)
		$hidate = $lodate;

	$vlabelClass = "vlabel";

	if ($reprocess) {
		$vlabelClass = "hide";
		echo('<script>function rpx(sel) {document.getElementById("reprocessText").innerHTML=sel.options[sel.selectedIndex].getAttribute("title");}</script>');
		echo('<span class="vlabel" title="'.$TAGS['cl_ReprocessOpt'][1].'">');
		echo('<label for="reprocess">'.$TAGS['cl_ReprocessOpt'][0].'</label> ');
		echo('<select id="reprocess" name="reprocess" onchange="rpx(this);">');
		echo('<option value="2" selected title="'.$TAGS['cl_ReprocessZap'][1].'">'.$TAGS['cl_ReprocessZap'][0].'</option>');
		echo('<option value="1" title="'.$TAGS['cl_ReprocessYes'][1].'">'.$TAGS['cl_ReprocessYes'][0].'</option>');
		echo('<option value="0" title="'.$TAGS['cl_ReprocessNo'][1].'">'.$TAGS['cl_ReprocessNo'][0].'</option>');
		echo('</select>');

		echo('<p id="reprocessText">'.$TAGS['cl_ReprocessZap'][1].'</p>');
	}

	if (strtolower(getSetting('claimsAutopostAll','true'))=='true' || $reprocess)
		$chooseline = 2;
	else
		$chooseline = 1;

	echo('<span class="'.$vlabelClass.'" title="'.$TAGS['cl_DecisionsIncluded'][1].'">');
	echo('<label for="decisions">'.$TAGS['cl_DecisionsIncluded'][0].'</label> ');
	echo('<select id="decisions" name="decisions"> ');
	echo('<option value="0" '.($chooseline==1 ? 'selected' : '').' >'.$TAGS['cl_DecIncGoodOnly'][0].'</option>');
	echo('<option value="0,1,2,3,4,5,6,7,8,9" '.($chooseline==2 ? 'selected' : '').' >'.$TAGS['cl_DecIncDecided'][0].'</option>');
	echo('</select></span>');

	echo('<span class="'.$vlabelClass.'" title="'.$TAGS['cl_DateFrom'][1].'"><label for="lodate">'.$TAGS['cl_DateFrom'][0].'</label> <input type="date" id="lodate" name="lodate" value="'.$lodate.'"></span>');
	echo('<span class="'.$vlabelClass.'" title="'.$TAGS['cl_DateTo'][1].'"><label for="hidate">'.$TAGS['cl_DateTo'][0].'</label> <input type="date" id="hidate" name="hidate" value="'.$hidate.'"></span>');
	echo('<span class="'.$vlabelClass.'" title="'.$TAGS['cl_TimeFrom'][1].'"><label for="lotime">'.$TAGS['cl_TimeFrom'][0].'</label> <input type="time" id="lotime" name="lotime" value="00:00"></span>');
	echo('<span class="'.$vlabelClass.'" title="'.$TAGS['cl_TimeTo'][1].'"><label for="hitime">'.$TAGS['cl_TimeTo'][0].'</label> <input type="time" id="hitime" name="hitime" value="23:59"></span>');
	//echo('<input type="hidden" name="decisions" value="0">'); // Only process good claims

	echo('<span class="vlabel" title="'.$TAGS['cl_EntrantIDs'][1].'"><label for="entrants">'.$TAGS['cl_EntrantIDs'][0].'</label> ');
	echo('<input id="entrants" name="entrants"></span>');
	echo('<span class="vlabel"><label for="gobutton"></label> <input id="gobutton" type="submit" value="'.$TAGS['cl_Go'][0].'"></span>');
	echo('</form>');
}

function applyClaims()
// One-off for no ride rally April 2020
// and again May 2020
// and properly in Jorvik 2020
// copes with non-variable specials after BBR21
// copes with variable specials as well
{
	global $DB,$TAGS,$KONSTANTS;

	if (!isset($_REQUEST['lodate']) || !isset($_REQUEST['hidate']) ||
		!isset($_REQUEST['lotime']) || !isset($_REQUEST['hitime']) ||
		!isset($_REQUEST['decisions']) ) {								// Should only be used for Decision=0=Good Claim
		applyClaimsForm();
		//echo('<hr>NOT ENOUGH PARAMETERS<hr>');
		exit;
	}
	
	//print_r($_REQUEST); exit;
	if (!$DB->exec('BEGIN IMMEDIATE TRANSACTION')) {
		dberror();
		exit;
	}


	startHtml($TAGS['cl_ClaimsTitle'][0]);

	$sql = "SELECT count(*) As Rex FROM entrants WHERE ScoringNow<>0";
	if (getValueFromDB($sql,"Rex",0) > 0) {
		$DB->exec('ROLLBACK');
		echo('<h3>'.$TAGS['ExclusiveAccessNeeded'][0].'</h3>');
		echo('<p>'.$TAGS['ExclusiveAccessNeeded'][1].'</p>');
		exit;
	}

	echo('<h3>'.$TAGS['cl_Applying'][0].'</h3>');

	$isVirtual = getValueFromDB("SELECT isvirtual FROM rallyparams","isvirtual",0);

	$sql = "SELECT claims.*,claims.rowid as claimid,xbonus.BriefDesc,xbonus.Type As BonusType FROM claims JOIN (SELECT BonusID,BriefDesc,'B' As Type FROM bonuses) AS xbonus ON claims.BonusID=xbonus.BonusID WHERE ";
	
	// Because of the link to bonuses, only ordinary bonus claims will be processed here.
	// Claims for combos or non-existent bonuses must be handled by hand.
	
	$loclaimtime = joinDateTime($_REQUEST['lodate'],$_REQUEST['lotime']);
	$hiclaimtime = joinDateTime($_REQUEST['hidate'],$_REQUEST['hitime']);
	
	if (isset($_REQUEST['reprocess']) && $_REQUEST['reprocess'] > '0') {
		$sqlW = "TRUE";
	} else {
		$sqlW = "Applied=0";		// Not already applied
	}
	
	$sqlW .= " AND ClaimTime>='".$loclaimtime."'";
	$sqlW .= " AND ClaimTime<='".$hiclaimtime."'";
	$sqlW .= " AND Decision IN (".$_REQUEST['decisions'].") ";
	
	$sqlW .= " AND SpeedPenalty=0 AND FuelPenalty=0" ; // AND MagicPenalty=0"; // Penalties applied by hand
	
	if (isset($_REQUEST['entrants']) && $_REQUEST['entrants'] != '')
		$sqlW .= " AND EntrantID IN (".$_REQUEST['entrants'].")";
	if (isset($_REQUEST['bonuses']) && $_REQUEST['bonuses'] != '')
		$sqlW .= " AND BonusID IN (".$_REQUEST['bonuses'].")";
	if (isset($_REQUEST['exclude']) && $_REQUEST['exclude'] != '')
		$sqlW .= " AND BonusID NOT IN (".$_REQUEST['exclude'].")";
	$sql .= $sqlW;
	$sql .= " ORDER BY ClaimTime";
	
	// Load all claims records into memory
	// organised as EntrantID, BonusID
	$claims = [];
	//echo($sql.'<hr>'); exit;
	$R = $DB->query($sql);
	$claimcount = 0;
	while ($R->fetchArray())
		$claimcount++;
	$R->reset();
	echo('<p style="font-size: small;">'.sprintf($TAGS['cl_ProcessingCC'][0],$claimcount).'</p>');
	while ($rd = $R->fetchArray()){
		if (!isset($claims[$rd['EntrantID']])) 
			$claims[$rd['EntrantID']] = [];
		if (!isset([$rd['EntrantID']][$rd['claimid']]))
			$claims[$rd['EntrantID']][$rd['claimid']] = $rd['BonusID'];
	}
	//print_r($claims);
	//return;
	
	$scorecardsTouched = 0;
	foreach($claims as $entrant => $eclaims) {
		
		if (isset($_REQUEST['reprocess']) && $_REQUEST['reprocess'] > '1')
			zapScorecard($entrant);

		foreach($eclaims as $claimid => $bonusid) {
			applyClaim($claimid,true);
		}


		$scorecardsTouched++;


		updateAutoClass($entrant);
	
	}
	rankEntrants(true);
	$DB->exec('COMMIT');
	echo('<br>');
	
	echo($TAGS['cl_Complete'][$scorecardsTouched > 0 ? 0 : 0]);
}

function bonusPrefix($bonus)
/*
 * This returns either B or S for [ordinary] Bonus or Special
 * no consideration of combinations
 * 
 */
{
	global $KONSTANTS;

	return $KONSTANTS['ORDINARY_BONUS_PREFIX'];
}

function saveMagicWords()
{
	global $DB,$TAGS,$KONSTANTS;
	
	$arr = $_REQUEST['id'];
	if (!$DB->exec('BEGIN IMMEDIATE TRANSACTION')) {
		dberror();
		exit;
	}
	$DB->exec('DELETE FROM magicwords');
	for ($i = 0; $i < count($arr); $i++) {
		if ($_REQUEST['magic'][$i] != '') {
			$sql = "INSERT INTO magicwords(asfrom,magic) VALUES(";
			$sql .= "'".$_REQUEST['asfromdate'][$i].' '.$_REQUEST['asfromtime'][$i]."'";
			$sql .= ",'".$DB->escapeString($_REQUEST['magic'][$i])."')";
//			echo($sql.'<hr>');
			$DB->exec($sql);
			if ($DB->lastErrorCode() <> 0)
				echo($DB->lastErrorMsg().'<br>'.$sql.'<hr>');
		}
	}
	$DB->exec('COMMIT');
}

function magicWords()
{
	global $DB,$TAGS,$KONSTANTS;


?>
<script>
function deleteRow(e) {
    e = e || window.event;
    let target = e.target || e.srcElement;	
	document.querySelector('#magicwords').deleteRow(target.parentNode.parentNode.rowIndex);
	enableSaveButton();
}
function triggerNewRow(obj) {
	var oldnewrow = document.getElementsByClassName('newrow')[0];
	tab = document.getElementById('magicwords').getElementsByTagName('tbody')[0];
	var row = tab.insertRow(tab.rows.length);
	row.innerHTML = oldnewrow.innerHTML;
	obj.onchange = '';
}
</script>
<?php	

	startHtml($TAGS['ttMagicWords'][0]);
	
	echo('<form method="post" action="claims.php">');

	
	echo('<input type="hidden" name="c" value="magic">');
	echo('<input type="hidden" name="menu" value="setup">');
	echo('<p>'.htmlentities($TAGS['AdmMagicWords'][1]).'</p>');
	echo('<table id="magicwords">');
	echo('<thead><tr><th>'.$TAGS['mw_AsFrom'][0].'</th><th>'.$TAGS['mw_Word'][0].'</th>');
	echo('<th></th>');
	echo('</tr>');
	echo('</thead><tbody>');
	
	$sql = 'SELECT rowid AS id,asfrom,magic FROM magicwords ORDER BY asfrom';
	$R = $DB->query($sql);
	if ($DB->lastErrorCode() <> 0)
		echo($DB->lastErrorMsg().'<br>'.$sql.'<hr>');
	
	while ($rd = $R->fetchArray())
	{
		echo("\n".'<tr class="hoverlite">');
		echo('<td><input type="hidden" name="id[]" value="'.$rd['id'].'">');
		$afdt = explode(' ',$rd['asfrom']);
		echo('<input type="date" name="asfromdate[]" value="'.$afdt[0].'" onchange="enableSaveButton();"> ');
		echo('<input type="time" name="asfromtime[]" value="'.$afdt[1].'" onchange="enableSaveButton();"></td>');
		echo('<td><input type="text" name="magic[]" value="'.$rd['magic'].'" onchange="enableSaveButton();"></td>');
		echo('<td class="center"><button value="-" onclick="deleteRow(event);return false;">-</button></td>');
		echo('</tr>');
	}	
	echo('<tr class="newrow hide"><td><input type="hidden" name="id[]" value="">');
	echo('<input class="date" type="date" name="asfromdate[]" value="" onchange="enableSaveButton();"> ');
	echo('<input class="time" type="time" name="asfromtime[]" value="" onchange="enableSaveButton();"></td>');
	echo('<td ><input class="text" type="text" name="magic[]" value="" onchange="enableSaveButton();"> ');
	echo('<td class="center"><button value="-" onclick="deleteRow(event);return false;">-</button></td>');
	echo('</tr>');
	echo('</tbody></table>');
	echo('<button value="+" onclick="triggerNewRow(this);return false;">+</button><br>');
	
	echo('<input type="submit" class="noprint" title="'.$TAGS['SaveSettings'][1].'" id="savedata" data-triggered="0" onclick="'."this.setAttribute('data-triggered','1')".'" disabled accesskey="S" name="savemw" data-altvalue="'.$TAGS['SaveSettings'][0].'" value="'.$TAGS['SettingsSaved'][0].'" /> ');
	echo('</form>');
	//showFooter();
	
}



function zapScorecard($entrant) {

	global $DB, $KONSTANTS;

	$sql = "UPDATE entrants SET BonusesVisited='',CombosTicked='',TotalPoints=0,FinishPosition=0";
	$sql .= ",EntrantStatus=".$KONSTANTS['EntrantOK'];
	$sql .= ",RejectedClaims='',RestMinutes=0";
	$sql .= " WHERE EntrantID=$entrant";
	error_log($sql);
	$DB->exec($sql);

}


if (isset($_REQUEST['deleteclaim']) && isset($_REQUEST['claimid']) && $_REQUEST['claimid']>0) {
	deleteClaim();
	listclaims();
	exit;
}
//print_r($_REQUEST);
if (isset($_REQUEST['savemw'])) {
	saveMagicWords();
}	
if (isset($_REQUEST['saveclaim'])) {
	saveClaim();
}	
if (isset($_REQUEST['c'])) {
	
	if ($_REQUEST['c']=='applyclaims') {
		applyClaims();
		exit;
	}
	
	if ($_REQUEST['c']=='showclaim' && isset($_REQUEST['claimid']) && intval($_REQUEST['claimid'])>0) {
		showClaim(intval($_REQUEST['claimid']));
		exit;
	}
		
	if ($_REQUEST['c']=='shownew') {
		showNewClaim();
		exit;
	}
	if ($_REQUEST['c']=='decideclaim') {
		updateClaimDecision();
		exit;
	}
	if ($_REQUEST['c']=='applyclaim') {
		updateClaimApplied();
		exit;
	}
	if ($_REQUEST['c']=='entnam') {
		$e = (isset($_REQUEST['e']) ? $_REQUEST['e'] : '');
		fetchEntrantDetail($e);
		exit;
	}
	if ($_REQUEST['c']=='bondes') {
		$b = (isset($_REQUEST['b']) ? $_REQUEST['b'] : '');
		fetchBonusName($b,true);
		exit;
	}
	
	if ($_REQUEST['c']=='magic') {
		magicWords();
		exit;
	}
	if ($_REQUEST['c']=='updatedd') {
		$val = $_REQUEST['val'];
		$_SESSION['dd'] = $val;
		echo('ok');
		exit;
	}
	if ($_REQUEST['c']=='updateddate') {
		$val = $_REQUEST['val'];
		$_SESSION['ddate'] = $val;
		echo('ok');
		exit;
	}
	if ($_REQUEST['c']=='updatefa') {
		$val = $_REQUEST['val'];
		$_SESSION['fa'] = $val;
		echo('ok');
		exit;
	}
	
	if ($_REQUEST['c']=='updatefd') {
		$val = $_REQUEST['val'];
		$_SESSION['fd'] = $val;
		echo('ok');
		exit;
	}
	
	
	// If dropped through then just list the buggers.
	//echo('dropped through<br>');
	//print_r($_REQUEST);
	listclaims();
}
else
	listclaims();

?>
