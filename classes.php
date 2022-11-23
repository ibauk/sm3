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


function listClasses() {
	
	global $DB, $TAGS, $KONSTANTS;

	$dr = defaultRecord('classes');
	
	$sql = 'SELECT rowid as id,* FROM classes ORDER BY Class';
	$R = $DB->query($sql);
	if ($DB->lastErrorCode() <> 0)
		echo($DB->lastErrorMsg().'<br>'.$sql.'<hr>');

	
?>
<script>
function addClass() {
	let tab = document.querySelector('#classes>tbody');
	let len = tab.rows.length - 1;
	let lastnum = 0;
	if (len > 0)
		lastnum = parseInt(tab.rows[len].cells[0].innerHTML);
	lastnum++;
	let row = tab.insertRow();
	row.setAttribute('data-newrow','1');
	row.innerHTML = document.getElementById('newclassrow').innerHTML;
	console.log(row.innerHTML);
	row.cells[0].innerHTML = lastnum;
	row.cells[1].firstChild.focus();
}
function certButton(row) {
	let buttons = row.cells[row.cells.length - 1].childNodes;
	let res = buttons[0];
	return res;
}
function deleteClass(obj) {
	console.log('Deleting class');
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
	xhttp.open("GET", 'classes.php?c=deleteclass&cl='+cls, true);
	xhttp.send();
	
}

function flipFilters(obj) {
	let row = obj.parentNode.parentNode;
	let cls = row.cells[0].innerHTML;
	let auto = row.cells[2].firstChild.value == 1 && cls > 0;
	for (let i = 3; i < row.cells.length; i++) {
		let inp = row.cells[i].firstChild;
		inp.readOnly = !auto;
		if (inp.classList.contains('smallnumber') && auto)
			inp.type = 'number';
		else
			inp.type = 'text';
	}
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
	certButton(row).disabled = false; // Don't care, just enable it
}
function saveButton(row) {
	let buttons = row.cells[row.cells.length - 1].childNodes;
	let sav = buttons[2];
	return sav;
}
function saveClass(obj,showCert) {
	console.log('Saving class');
	let row = obj.parentNode.parentNode;
	let cls = row.cells[0].innerHTML;
	let bd = row.cells[1].firstChild.value;
	let as = row.cells[2].firstChild.value;
	let mp = row.cells[3].firstChild.value;
	let mb = row.cells[4].firstChild.value;
	let br = row.cells[5].firstChild.value;
	let lr = row.cells[6].firstChild.value;
	let xhttp = new XMLHttpRequest();
	xhttp.onreadystatechange = function() {
		let ok = new RegExp("\W*ok\W*");
		if (this.readyState == 4 && this.status == 200) {
			console.log('{'+this.responseText+'}');
			if (ok.test(this.responseText)) {
				flipSave(obj,false);
				if (showCert)
					window.location.href = 'certedit.php?Class='+cls;
			}
		}
	};
	xhttp.open("GET", 'classes.php?c=saveclass&cl='+cls+'&bd='+bd+'&as='+as+'&mp='+mp+'&mb='+mb+'&br='+br+'&lr='+lr, true);
	xhttp.send();
	
}
function showCertificate(obj) {
	let row = obj.parentNode.parentNode;
	let cls = row.cells[0].innerHTML;
	let sav = saveButton(row);
	if (!sav.disabled)
		saveClass(sav,true);
	else
		window.location.href = 'certedit.php?Class='+cls;
}
</script>
<?php	
	echo('<div class="stickytop">'.$TAGS['ClassMaintHead'][1]);
	echo('<br><button value="+" autofocus onclick="addClass()">+</button>');
	echo('</div>');

	echo('<table id="classes">');
	echo("\r\n".'<thead class="listhead"><tr><th class="rowcol">#</th><th class="rowcol">'.$TAGS['BriefDescLit'][0].'</th>');
	echo('<th class="rowcol">'.$TAGS['cls_Assigned'][0].'</th>');
	echo('<th class="rowcol">'.$TAGS['cls_MinPoints'][0].'</th>');
	echo('<th class="rowcol">'.$TAGS['cls_MinBonuses'][0].'</th>');
	echo('<th class="rowcol">'.$TAGS['cls_BonusesReqd'][0].'</th>');
	echo('<th class="rowcol">'.$TAGS['cls_LowestRank'][0].'</th>');
	echo('<th></th>');
	echo('</tr>');
	echo('</thead><tbody>');

	echo('<tr id="newclassrow" style="display:none;">');
		echo('<td class="rowcol" title="'.$TAGS['Class'][1].'" ></td>');
		
		echo('<td  class="rowcol" title="'.$TAGS['BriefDescLit'][1].'"><input type="text" value="'.$dr['BriefDesc'].'" style="width:7em;" oninput="flipSave(this,true);">');
		echo('</td>');
		
		echo('<td  class="rowcol" title="'.$TAGS['cls_Assigned'][1].'">');
		echo('<select onchange="flipFilters(this);flipSave(this,true);">');
		echo('<option value="0" '.($dr['AutoAssign']==0? ' selected ' : '').'>'.$TAGS['cls_Assigned0'][0].'</option>');
		echo('<option value="1" '.($dr['AutoAssign']!=0? ' selected ' : '').'>'.$TAGS['cls_Assigned1'][0].'</option>');
		echo('</select>');
		echo('</td>');
		
		echo('<td  class="rowcol" title="'.$TAGS['cls_MinPoints'][1].'">');
		echo('<input  type="number" class="smallnumber" value="'.$dr['MinPoints'].'" oninput="flipSave(this,true);">');
		echo('</td>');
		
		
		echo('<td  class="rowcol" title="'.$TAGS['cls_MinBonuses'][1].'">');
		echo('<input type="number" class="smallnumber" value="'.$dr['MinBonuses'].'" oninput="flipSave(this,true);">');
		echo('</td>');

		echo('<td  class="rowcol" title="'.$TAGS['cls_BonusesReqd'][1].'">');
		echo('<input type="text" value="'.htmlspecialchars($dr['BonusesReqd']).'" oninput="flipSave(this,true);">');
		echo('</td>');

		echo('<td  class="rowcol" title="'.$TAGS['cls_LowestRank'][1].'">');
		echo('<input  type="number" class="smallnumber" value="'.$dr['LowestRank'].'" oninput="flipSave(this,true);">');
		echo('</td>');
	
		echo('<td class="rowcol">');
		echo('<button disabled title="'.$TAGS['cls_Certificate'][1].'" onclick="showCertificate(this);">'.$TAGS['cls_Certificate'][0].'</button> ');
		echo('<input type="button" disabled data-value="'.$TAGS['SaveRecord'][0].'" value="'.$TAGS['RecordSaved'][0].'" onclick="saveClass(this,false);"> ');
		echo('</td>');
	echo('</tr>');
	

	while ($rd = $R->fetchArray()) {

		echo("\r\n".'<tr>');
		echo('<td class="rowcol" title="'.$TAGS['Class'][1].'" >'.$rd['Class'].'</td>');
		
		echo('<td  class="rowcol" title="'.$TAGS['BriefDescLit'][1].'"><input type="text" value="'.$rd['BriefDesc'].'" style="width:7em;" oninput="flipSave(this,true);">');
		echo('</td>');
		
		$ro = $rd['Class'] > 0 ? '' : ' readonly ';
		$nt = $ro != '' ? 'text' : 'number';
		
		echo('<td  class="rowcol" title="'.$TAGS['cls_Assigned'][1].'">');
		echo('<select onchange="flipFilters(this);flipSave(this,true);"'.($rd['Class']==0?' disabled ' : '').'>');
		echo('<option value="0" '.($rd['AutoAssign']==0? ' selected ' : '').'>'.$TAGS['cls_Assigned0'][0].'</option>');
		echo('<option value="1" '.($rd['AutoAssign']!=0? ' selected ' : '').'>'.$TAGS['cls_Assigned1'][0].'</option>');
		echo('</select>');
		echo('</td>');
		
		echo('<td  class="rowcol" title="'.$TAGS['cls_MinPoints'][1].'">');
		echo('<input '.$ro.' type="'.$nt.'" class="smallnumber" value="'.$rd['MinPoints'].'" oninput="flipSave(this,true);">');
		echo('</td>');
		
		
		echo('<td  class="rowcol" title="'.$TAGS['cls_MinBonuses'][1].'">');
		echo('<input '.$ro.' type="'.$nt.'" class="smallnumber" value="'.$rd['MinBonuses'].'" oninput="flipSave(this,true);">');
		echo('</td>');

		echo('<td  class="rowcol" title="'.$TAGS['cls_BonusesReqd'][1].'">');
		echo('<input '.$ro.' type="text" value="'.htmlspecialchars($rd['BonusesReqd']).'" oninput="flipSave(this,true);">');
		echo('</td>');

		echo('<td  class="rowcol" title="'.$TAGS['cls_LowestRank'][1].'">');
		echo('<input '.$ro.' type="'.$nt.'" class="smallnumber" value="'.$rd['LowestRank'].'" oninput="flipSave(this,true);">');
		echo('</td>');
	
		echo('<td class="rowcol">');
		echo('<button title="'.$TAGS['cls_Certificate'][1].'" onclick="showCertificate(this);">'.$TAGS['cls_Certificate'][0].'</button> ');
		echo('<input type="button" disabled data-value="'.$TAGS['SaveRecord'][0].'" value="'.$TAGS['RecordSaved'][0].'" onclick="saveClass(this,false);"> ');
		$sql = "SELECT count(*) As Rex FROM entrants WHERE Class=".$rd['Class'];
		$rex = getValueFromDB($sql,"Rex",0);
		if ($rex < 1 && $rd['Class'] > 0) {
			echo('<input type="button" title="'.$TAGS['DeleteEntryLit'][0].'" value="-" onclick="deleteClass(this,false);"> ');

		}
		echo('</td>');
		
		echo('</tr>');
	}
	
	
	echo('</tbody></table>');




	
}










function ajaxDeleteClass() {

	global $DB,$TAGS,$KONSTANTS;
	
	error_log('adc::');
	if (!isset($_REQUEST['cl']))
		return;
	// Check for usage
	$sql = "SELECT count(*) As Rex FROM entrants WHERE Class=".$_REQUEST['cl'];
	$rex = getValueFromDB($sql,"Rex",0);
	if ($rex > 0) {
		echo('in use');
		return;
	}

	$sql = "DELETE FROM classes WHERE Class=".$_REQUEST['cl'];
	error_log($sql);
	echo($DB->exec($sql) && $DB->changes()==1? 'ok' : 'error');
	
}

function ajaxSaveClass() {

	global $DB,$TAGS,$KONSTANTS;
	
	error_log('asc::');
	foreach (['cl','bd','as','mp','br','lr'] as $k)
		if (!isset($_REQUEST[$k]))
			return;
	// Check for certificate
	$title = getValueFromDB("SELECT Title FROM certificates WHERE Class=".$_REQUEST['cl'],"Title","");
	if ($title=='') {
		$sql = "INSERT INTO certificates (Class,Title,css,html,options,image) SELECT ";
		$sql .= $_REQUEST['cl']." As Class,'".$DB->escapeString($_REQUEST['bd'])."' As Title,";
		$sql .= "css,html,options,image FROM certificates WHERE Class=0";
		error_log("asc:cert:-$sql");
		$DB->exec($sql);
	}
	$sql = "INSERT OR REPLACE INTO classes (Class,BriefDesc,AutoAssign,MinPoints,MinBonuses,BonusesReqd,LowestRank) VALUES(";
	$sql .= $_REQUEST['cl'].",'".$DB->escapeString($_REQUEST['bd'])."'";
	$sql .= ",".$_REQUEST['as'].",".$_REQUEST['mp'].",".$_REQUEST['mb'];
	$sql .= ",'".$DB->escapeString($_REQUEST['br'])."'";
	$sql .= ",".$_REQUEST['lr'];
	$sql .= ")";
	error_log($sql);
	echo($DB->exec($sql) && $DB->changes()==1? 'ok' : 'error');
	
}


//error_log($_REQUEST['c']);
if (isset($_REQUEST['c']))
	switch($_REQUEST['c']) {
			
		case 'saveclass':
			ajaxSaveClass();
			exit;
		case 'deleteclass':
			ajaxDeleteClass();
			exit;
		case 'putaxis':
			updateAxisName();
			exit;
		case 'getcats':
			if (isset($_REQUEST['a'])) {
				echo(listCategories($_REQUEST['a'],false));
				exit;
			}
			break;
		case 'putcat':
			saveAxisCat();
			exit;
		case 'delcat':
			deleteAxisCat();
			exit;
		case 'savecalc':
			saveCompoundCalc();
			

	}





startHtml($TAGS['ttSetup'][0]);


if (isset($_REQUEST['c']))
{
	switch($_REQUEST['c'])
	{
		case 'axes':
			listAxes();
			break;
			
		case 'classes':
			listClasses();
			break;
			
		case 'newcc':
			showNewCompoundCalc();
			break;

		case 'showcc':
			showCompoundCalc(isset($_REQUEST['ruleid'])?$_REQUEST['ruleid']:0);
			break;

		default:
	}
} else
	include "score.php"; // Some mistake has happened or maybe someone just tried logging on
//	print_r($_REQUEST);

?>

