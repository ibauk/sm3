<?php

/*
 * I B A U K   -   S C O R E M A S T E R
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

// Alphabetic from here on in


function listCohorts() {
	
	global $DB, $TAGS, $KONSTANTS;

	$dr = defaultRecord('cohorts');
	
	$sql = 'SELECT rowid as id,* FROM cohorts ORDER BY Cohort';
	$R = $DB->query($sql);
	if ($DB->lastErrorCode() <> 0)
		echo($DB->lastErrorMsg().'<br>'.$sql.'<hr>');

	
?>
<script>
function addCohort() {
	let tab = document.querySelector('#cohorts>tbody');
	let len = tab.rows.length - 1;
	let lastnum = 0;
	if (len > 0)
		lastnum = parseInt(tab.rows[len].cells[0].innerHTML);
	lastnum++;
	let row = tab.insertRow();
	row.setAttribute('data-newrow','1');
	row.innerHTML = document.getElementById('newcohortrow').innerHTML;
	console.log(row.innerHTML);
	row.cells[0].innerHTML = lastnum;
	row.cells[1].firstChild.focus();
}
function deleteCohort(obj) {
	console.log('Deleting cohort');
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
	xhttp.open("GET", 'cohorts.php?c=deletecohort&cohort='+cls, true);
	xhttp.send();
	
}

function flipFilters(obj) {
    obj.title = obj.options[obj.selectedIndex].title;
}
function flipSave(obj,enable) {
	console.log('flipSave: '+enable);
	let row = obj.parentNode.parentNode;
	let sav = saveButton(row);
	if (sav.disabled != enable) 
		return;
	console.log('need to flipsave');
	let val = sav.value;
	sav.value = sav.getAttribute('data-value');
	sav.setAttribute('data-value',val);
	sav.disabled = !sav.disabled;
}
function saveButton(row) {
	let buttons = row.cells[row.cells.length - 1].childNodes;
	let sav = buttons[0];
	return sav;
}
function saveCohort(obj) {
	console.log('Saving cohort');
	let row = obj.parentNode.parentNode;
	let cls = row.cells[0].innerHTML;
	let fs = row.cells[1].firstChild.value;
	let sd = row.cells[2].firstChild.value;
	let st = row.cells[3].firstChild.value;
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
	xhttp.open("GET", 'cohorts.php?c=savecohort&cohort='+cls+'&fs='+fs+'&sd='+sd+'&st='+st, true);
	xhttp.send();
	
}
function showCohortMembers(cohort) {
	let xhttp = new XMLHttpRequest();
	xhttp.onreadystatechange = function() {
		let ok = new RegExp("\W*ok\W*");
		if (this.readyState == 4 && this.status == 200) {
			document.getElementById('cohortmembers').innerHTML = this.responseText;
		}
	};
	xhttp.open("GET", "cohorts.php?c=getcohort&cohort="+cohort, true);
	xhttp.send();
	
}
function addMember() {
	let tab = document.getElementById('cohortrows');
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
	xhttp.open("GET", "cohorts.php?c=addmember", true);
	xhttp.send();

}

function addThisMember(obj) {
	
	let tab = document.querySelector('#cohortrows>tbody');
	let entrant = obj.value;
	let cohort = document.getElementById('currentcohort').innerText;
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
	xhttp.open("GET", "cohorts.php?c=addthismember&entrant="+entrant+"&cohort="+cohort, true);
	xhttp.send();

}
function deleteMember(obj) {
	let row = obj.parentNode.parentNode;
	let tab = document.getElementById('cohortrows');
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
	xhttp.open("GET", "cohorts.php?c=delmember&entrant="+entrant, true);
	xhttp.send();


}

</script>
<?php	
	echo('<div class="stickytop">'.$TAGS['CohortMaintHead'][1]);
	echo('<br><button value="+" autofocus onclick="addCohort()">+</button>');
	echo('</div>');

	echo('<table id="cohorts">');
	echo("\r\n".'<thead class="listhead"><tr><th class="rowcol">#</th>');
	echo('<th class="rowcol">'.$TAGS['cht_FixedStart'][0].'</th>');
	echo('<th class="rowcol">'.$TAGS['cht_StartDate'][0].'</th>');
	echo('<th class="rowcol">'.$TAGS['cht_StartTime'][0].'</th>');
	echo('<th></th>');
	echo('</tr>');
	echo('</thead><tbody>');

	echo('<tr id="newcohortrow" style="display:none;">');
		echo('<td class="rowcol" title="'.$TAGS['Cohort'][1].'" ></td>');
		
		echo('<td  class="rowcol" title="'.$TAGS['cht_FixedStart'][1].'">');
		echo('<select onchange="flipFilters(this);flipSave(this,true);" title="'.$TAGS['cht_FixedStart'.$dr['FixedStart']][1].'" >');
		echo('<option  title="'.$TAGS['cht_FixedStart0'][1].'" value="0" '.($dr['FixedStart']==0? ' selected ' : '').'>'.$TAGS['cht_FixedStart0'][0].'</option>');
		echo('<option  title="'.$TAGS['cht_FixedStart1'][1].'" value="1" '.($dr['FixedStart']!=0? ' selected ' : '').'>'.$TAGS['cht_FixedStart1'][0].'</option>');
		echo('</select>');
		echo('</td>');
		
        $dt = splitDatetime($dr['StartTime']);
		echo('<td  class="rowcol" title="'.$TAGS['cht_StartDate'][1].'">');
		echo('<input  type="date" value="'.$dt[0].'" oninput="flipSave(this,true);">');
		echo('</td>');
		
		
		echo('<td  class="rowcol" title="'.$TAGS['cht_StartTime'][1].'">');
		echo('<input type="time" value="'.$dt[1].'" oninput="flipSave(this,true);">');
		echo('</td>');

	
		echo('<td class="rowcol">');
		echo('<input type="button" disabled data-value="'.$TAGS['SaveRecord'][0].'" value="'.$TAGS['RecordSaved'][0].'" onclick="saveCohort(this);"> ');
		echo('</td>');
	echo('</tr>');
	

	while ($rd = $R->fetchArray()) {

		echo("\r\n".'<tr>');
		echo('<td onclick="showCohortMembers(this.innerHTML);" class="rowcol clickme" title="'.$TAGS['ShowMembers'][1].'" >'.$rd['Cohort'].'</td>');
		
		
		$ro = $rd['Cohort'] > 0 ? '' : ' readonly ';
		$nt = $ro != '' ? 'text' : 'number';
		
		echo('<td  class="rowcol" title="'.$TAGS['cht_FixedStart'.$rd['FixedStart']][1].'">');
		echo('<select onchange="flipFilters(this);flipSave(this,true);"'.($rd['Cohort']==0?' disabled ' : '').'>');
		echo('<option title="'.$TAGS['cht_FixedStart0'][1].'" value="0" '.($rd['FixedStart']==0? ' selected ' : '').'>'.$TAGS['cht_FixedStart0'][0].'</option>');
		echo('<option  title="'.$TAGS['cht_FixedStart1'][1].'" value="1" '.($rd['FixedStart']!=0? ' selected ' : '').'>'.$TAGS['cht_FixedStart1'][0].'</option>');
		echo('</select>');
		echo('</td>');
		
        $dt = splitDatetime($rd['StartTime']);
		echo('<td  class="rowcol" title="'.$TAGS['cht_StartDate'][1].'">');
		echo('<input  type="date" value="'.$dt[0].'" oninput="flipSave(this,true);">');
		echo('</td>');
		
		
		echo('<td  class="rowcol" title="'.$TAGS['cht_StartTime'][1].'">');
		echo('<input  type="time" value="'.$dt[1].'" oninput="flipSave(this,true);">');
		echo('</td>');

		echo('<td class="rowcol">');


		echo('<input type="button" disabled data-value="'.$TAGS['SaveRecord'][0].'" value="'.$TAGS['RecordSaved'][0].'" onclick="saveCohort(this);"> ');
        echo('<input type="button" title="'.$TAGS['ShowMembers'][1].'" value="&Cconint;" ');
		echo('onclick="'."showCohortMembers(this.getAttribute('data-cohort'));".'" data-cohort="'.$rd['Cohort'].'"> ');
	
		$sql = "SELECT count(*) As Rex FROM entrants WHERE Cohort=".$rd['Cohort'];
		$rex = getValueFromDB($sql,"Rex",0);
		if ($rex < 1 && $rd['Cohort'] > 0) {
			echo('<input type="button" title="'.$TAGS['DeleteEntryLit'][0].'" value="-" onclick="deleteCohort(this,false);"> ');

		}
		echo('</td>');
		
		echo('</tr>');
	}
	
	echo('</tbody></table>');
	echo('<hr><div id="cohortmembers"></div>');




	
}




function listCohortMembers($cohort) {

	global $DB,$TAGS,$KONSTANTS;
	
	$res = '<p>'.$TAGS['CohortMembers'][0].' <span id="currentcohort">'.$cohort.'</span> </p>';
	$res .= '<table id="cohortrows" data-cohort="'.$cohort.'"><tr><th>'.$TAGS['EntrantID'][0].'</th><th>'.$TAGS['RiderName'][0].'</th>';
	$res .= '<th></th></tr>';
	$R = $DB->query("SELECT * from entrants WHERE Cohort=$cohort ORDER BY EntrantID");
	while ($rd = $R->fetchArray()) {
		$res .= '<tr data-newrow="0"><td class="EntrantID">'.$rd['EntrantID'].'</td>';
		$res .= '<td class="RiderName">'.$rd['RiderName'].'</td>';
		$res .= '<td> ';
        if ($cohort > 0)
		    $res .= ' <input type="button" data-value="-" value="-" onclick="deleteMember(this);">';
		$res .= '</td></tr>';
	}
	
	$res .= '</table>';
	
	$res .= '<input type="button"  id="addMemberButton" value="+" onclick="addMember();">';
	$res .= ' <span id="memberchoice"></span>';
	return $res;

}






function ajaxDeleteCohort() {

	global $DB,$TAGS,$KONSTANTS;
	
	error_log('adc::');
	if (!isset($_REQUEST['cohort']))
		return;
	// Check for usage
	$sql = "SELECT count(*) As Rex FROM entrants WHERE Cohort=".$_REQUEST['cohort'];
	$rex = getValueFromDB($sql,"Rex",0);
	if ($rex > 0) {
		echo('in use');
		return;
	}

	$sql = "DELETE FROM cohorts WHERE Cohort=".$_REQUEST['cohort'];
	error_log($sql);
	echo($DB->exec($sql) && $DB->changes()==1? 'ok' : 'error');
	
}

function ajaxSaveCohort() {

	global $DB,$TAGS,$KONSTANTS;
	
	error_log('asc::');
	foreach (['cohort','fs','sd','st'] as $k)
		if (!isset($_REQUEST[$k]))
			return;
	$sql = "INSERT OR REPLACE INTO cohorts (Cohort,FixedStart,StartTime) VALUES(";
	$sql .= $_REQUEST['cohort'];
	$sql .= ",".$_REQUEST['fs'].",'".$_REQUEST['sd']."T".$_REQUEST['st']."'";
	$sql .= ")";
	error_log($sql);
	echo($DB->exec($sql) && $DB->changes()==1? 'ok' : 'error');
	
}

function ajaxAddMemberSelector() {

	global $DB,$TAGS,$KONSTANTS;
	

	$sql = "SELECT * FROM entrants WHERE Cohort=0 ORDER BY EntrantID";
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

	if (!isset($_REQUEST['entrant']) || !isset($_REQUEST['cohort'])) {
		echo("");
		return;
	}
	reassignCohort($_REQUEST['cohort'],$_REQUEST['entrant']);
	$sql = "SELECT * FROM entrants WHERE EntrantID=".$_REQUEST['entrant'];
	$R = $DB->query($sql);
	$rd = $R->fetchArray();
	$res = '<td class="EntrantID">'.$rd['EntrantID'].'</td><td class="RiderName">'.$rd['RiderName'].'</td>';
	$res .= '<td> ';
	$res .= ' <input type="button" data-value="-" value="-" onclick="deleteMember(this);"></td>';

	echo($res);
}

function reassignCohort($cohort,$entrant) {

	global $DB,$TAGS,$KONSTANTS;

	$sql = "SELECT * FROM cohorts WHERE Cohort=".$cohort;
	$R = $DB->query($sql);
	if (!$rd = $R->fetchArray()) {
		echo("");
		return;
	}
	if ($rd['FixedStart'] != 0)
		$setStart = "'".$rd['StartTime']."'";
	else
		$setStart = "Null";
	$sql = "UPDATE entrants SET Cohort=".$cohort." WHERE EntrantID=".$entrant;
	$DB->exec($sql);
	$sql = "UPDATE entrants SET StartTime=".$setStart." WHERE EntrantID=".$entrant;
	$sql .= " AND EntrantStatus=".$KONSTANTS['EntrantOK'];
	$DB->exec($sql);

}

function ajaxDeleteMember() {

	global $DB,$TAGS,$KONSTANTS;

	error_log('adm::'.$_REQUEST['entrant']);
	if (!isset($_REQUEST['entrant'])) {
		echo('');
		return "";
	}

	reassignCohort(0,$_REQUEST['entrant']);
	echo('ok');
}

//error_log($_REQUEST['c']);


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

if (isset($_REQUEST['c']))
	switch($_REQUEST['c']) {
			
		case 'savecohort':
			ajaxSaveCohort();
			exit;
		case 'deletecohort':
			ajaxDeleteCohort();
			exit;
        case 'getcohort':
            if (isset($_REQUEST['cohort'])) {
                echo(listCohortMembers($_REQUEST['cohort']));
                exit;
            }
            break;
    
	}





startHtml($TAGS['ttSetup'][0]);


if (isset($_REQUEST['c']))
{
	switch($_REQUEST['c'])
	{
		case 'cohorts':
			listCohorts();
			break;
			

		default:
	}
}
?>

