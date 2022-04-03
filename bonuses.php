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


function showBonus($bonusid) {

	global $DB, $TAGS, $KONSTANTS;

	$R = $DB->query("SELECT * FROM sgroups ORDER BY GroupName");
	$sgroups[''] = $TAGS['NoSelection'][0];
	while ($rd = $R->fetchArray()) {
		$sgroups[$rd['GroupName']] = $rd['GroupName'];
	}

	$R = $DB->query('SELECT * FROM rallyparams');
	$rd = $R->fetchArray();
	
	for ($i=1; $i <= $KONSTANTS['NUMBER_OF_COMPOUND_AXES']; $i++)
		$catlabels[$i] = $rd['Cat'.$i.'Label'];
	

	$R = $DB->query('SELECT * FROM categories ORDER BY Axis,BriefDesc');
	while ($rd = $R->fetchArray())	{
		if (!isset($cats[$rd['Axis']]))
			$cats[$rd['Axis']][0] = $TAGS['NoSelection'][0];
		$cats[$rd['Axis']][$rd['Cat']] = $rd['BriefDesc'];
	}



	$R = $DB->query("SELECT * FROM bonuses WHERE BonusID='".strtoupper($bonusid)."'");
	if (!$rd = $R->fetchArray()) {
		echo('OMG!!! '.$bonusid);
		return;
	}

	echo('<div class="bonusdetail">');
	echo('<form method="post" action="bonuses.php">');

	echo('<input type="hidden" name="savesinglebonus" value="1">');
	echo('<input type="hidden" name="c" value="bonuses">');


	echo('<span class="vlabel"><label for="sdbutton"></label>');
	echo('<span title="'.$TAGS['DeleteEntryLit'][1].'">');
	echo('<label for="deletecmd">'.$TAGS['DeleteEntryLit'][0].'</label> ');
	echo('<input id="deletecmd" type="checkbox" name="deletebonus"> '); 
	echo('<input type="submit" id="sdbutton" name="savedata" value="'.$TAGS['SaveBonus'][0].'"> ');
	echo('</span>');

	echo('<span class="vlabel" title="'.$TAGS['BonusIDLit'][1].'">');
	echo('<label for="BonusID">'.$TAGS['BonusIDLit'][0].'</label> ');
	echo('<input type="text" readonly name="BonusID" id="BonusID" value="'.$bonusid.'">');
	echo('</span>');

	echo('<span class="vlabel" title="'.$TAGS['BriefDescLit'][1].'">');
	echo('<label for="BriefDesc">'.$TAGS['BriefDescLit'][0].'</label> ');
	echo('<input type="text" name="BriefDesc" id="BriefDesc" value="'.str_replace('"','&quot;',$rd['BriefDesc']).'">');
	echo('</span>');

	echo('<span class="vlabel">');
	echo('<span title="'.$TAGS['BonusPoints'][1].'">');
	echo('<label for="Points">'.$TAGS['BonusPoints'][0].'</label> ');
	echo('<input type="number" name="Points" id="Points" value="'.$rd['Points'].'"> ');
	echo('</span>');
	echo('<span title="'.$TAGS['AskPoints'][1].'">');
	echo('<select name="AskPoints">');
	echo('<option value="0"'.($rd['AskPoints']==0 ? ' selected>' : '>').$TAGS['AskPoints0'][0].'</option>');
	echo('<option value="1"'.($rd['AskPoints']==0 ? '>' : ' selected>').$TAGS['AskPoints1'][0].'</option>');
	echo('</select>');
	echo('</span>');
	echo('</span>');

	for ($i=1; $i <= $KONSTANTS['NUMBER_OF_COMPOUND_AXES']; $i++)
		if (isset($cats[$i])) {
			echo('<span class="vlabel">');
			echo('<label for="Cat'.$i.'">'.$catlabels[$i].'</label> ');
			echo('<select id="Cat'.$i.'" name="Cat'.$i.'">');
			foreach ($cats[$i] as $ce => $bd) 		{
				echo('<option value="'.$ce.'" ');
				if ($ce == $rd['Cat'.$i])
					echo('selected ');
				echo('>'.htmlspecialchars($bd).'</option>');
			}
			echo('</select></span>');
		}

	echo('<span class="vlabel" title="'.$TAGS['BonusNotes'][1].'">');
	echo('<label for="Notes">'.$TAGS['BonusNotes'][0].'</label> ');
	echo('<textarea name="Notes" id="Notes" cols="80" rows="1">'.str_replace('"','&quot;',$rd['Notes']).'</textarea>');
	echo('</span>');
	
	echo('<span class="vlabel" title="'.$TAGS['BonusFlags'][1].'">');
	echo('<label >'.$TAGS['BonusFlags'][0].'</label> ');
	for ($i= 0; $i < strlen($KONSTANTS['BonusScoringFlags']); $i++) {
		$flg = substr($KONSTANTS['BonusScoringFlags'],$i,1);
		echo('<span title="'.$TAGS['BonusScoringFlag'.$flg][1].'">');
		echo('<label class="short" for="BonusScoringFlag'.$flg.'">');
		echo('<img class="icon" src="images/');
		switch($flg) {
			case 'A':	echo('alertalert.png'); break;
			case 'B':	echo('alertbike.png'); break;
			case 'D':	echo('alertdaylight.png'); break;
			case 'F':	echo('alertface.png'); break;
			case 'R':	echo('alertrestricted.png'); break;
			case 'T':	echo('alertreceipt.png'); break;
		}
		echo('" alt="'.$flg.'"/></label> ');
		echo('<input type="checkbox" name="BonusScoringFlag'.$flg.'"');
		if (!(strpos($rd['Flags'],$flg)===false))
			echo(' checked ');
		echo('> ');
		echo('</span>');

	}

	echo('<span class="vlabel" title="'.$TAGS['BonusPhoto'][1].'">');
	echo('<label for="Image">'.$TAGS['BonusPhoto'][0].'</label> ');
	echo('<input type="text" name="Image" id="Image" value="'.str_replace('"','&quot;',$rd['Image']).'"> ');
?>
<script>
	function thumbimg(img) {
		if (img.getAttribute('data-t')==1) {
			img.setAttribute('data-t',0);
			img.classList.remove('thumbnail');
		} else {
			img.setAttribute('data-t',1);
			img.classList.add('thumbnail');
		}
	}
</script>
<?php
	echo('<img style="vertical-align: middle;" src="images/bonuses/'.$rd['Image'].'" alt="*" class="thumbnail" onclick="thumbimg(this);" data-t="1" loading="lazy"/>');
	echo('</span>');
	
	
	echo('<span class="vlabel" title="'.$TAGS['CompulsoryBonus'][1].'">');
	echo('<label for="Compulsory">'.$TAGS['CompulsoryBonus'][0].'</label> ');
	echo('<select id="Compulsory" name="Compulsory">');
	echo('<option value="0"'.($rd['Compulsory']==0 ? ' selected>' : '>').$TAGS['CompulsoryBonus0'][0].'</option>');
	echo('<option value="1"'.($rd['Compulsory']==1 ? ' selected>' : '>').$TAGS['CompulsoryBonus1'][0].'</option>');
	echo('<option value="2"'.($rd['Compulsory']==2 ? ' selected>' : '>').$TAGS['CompulsoryBonus2'][0].'</option>');
	echo('</select>');
	echo('</span>');

	echo('<span class="vlabel">');
	echo('<span title="'.$TAGS['RestMinutesLit'][1].'">');
	echo('<label for="RestMinutes">'.$TAGS['RestMinutesLit'][0].'</label> ');
	echo('<input type="number" class="smallnumber" name="RestMinutes" id="RestMinutes" value="'.$rd['RestMinutes'].'"> ');
	echo('</span>');
	echo('<span title="'.$TAGS['AskMinutes'][1].'">');
	echo('<select name="AskMinutes">');
	echo('<option value="0"'.($rd['AskMinutes']==0 ? ' selected>' : '>').$TAGS['AskMinutes0'][0].'</option>');
	echo('<option value="1"'.($rd['AskMinutes']==0 ? '>' : ' selected>').$TAGS['AskMinutes1'][0].'</option>');
	echo('</select>');
	echo('</span>');
	echo('</span>');

	echo('<span class="vlabel" title="'.$TAGS['GroupNameLit'][1].'">');
	echo('<label for="GroupName">'.$TAGS['GroupNameLit'][0].'</label> ');
	echo('<select name="GroupName" id="GroupName">');
	foreach($sgroups as $g => $n) {
		echo('<option value="'.$g.'" '.($g==$rd['GroupName'] ? ' selected ' : '' ).'>'.$n.'</option>');
	}
	echo('</select>');
	echo('</span>');

	echo('<span class="vlabel" title="'.$TAGS['BonusCoords'][1].'">');
	echo('<label for="Coords">'.$TAGS['BonusCoords'][0].'</label> ');
	echo('<input type="text" class="wider" name="Coords" id="Coords" value="'.str_replace('"','&quot;',$rd['Coords']).'">');
	echo('</span>');
	
	echo('<span class="vlabel" title="'.$TAGS['BonusWaffle'][1].'">');
	echo('<label for="Waffle">'.$TAGS['BonusWaffle'][0].'</label> ');
	echo('<textarea name="Waffle" id="Waffle" cols="80" rows="1">'.str_replace('"','&quot;',$rd['Waffle']).'</textarea>');
	echo('</span>');
	



	echo('</form>');

	echo('</div>');
}
function showBonuses()
/*
 *										s h o w B o n u s e s
 *
 * This handles viewing and maintenance of the table of ordinary bonuses
 *
 */
{
	global $DB, $TAGS, $KONSTANTS;
	

?>
<script>
function deleteRow(obj)
{
	let tr = obj.parentNode.parentNode.parentNode.parentNode;
	console.log('tr is '+tr.tagName);
	let B = tr.cells[0].firstChild.value;

	let xhttp;
 
	xhttp = new XMLHttpRequest();
	xhttp.onreadystatechange = function() {
		if (this.readyState == 4 && this.status == 200) {
			if (this.responseText.trim()=='')
				document.getElementById('bonuses').deleteRow(tr.rowIndex);
		}
	};
	
	xhttp.open("POST", "bonuses.php", true);
	xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	xhttp.send(encodeURI("c=deletebonus&bid="+B));
	return false;
}
function enableKill(obj)
{
	let bonuskiller = obj.parentNode.childNodes[2];
	bonuskiller.disabled = !obj.checked;
	return false;
}
function enableNewSave(obj)
{
	let tr = obj.parentNode.parentNode.parentNode;
	console.log('enableNewSave '+tr.tagName);
	tr.setAttribute('saveneeded',1);
	let B = tr.cells[0].firstChild.firstChild.value;
	console.log('B=='+B+';');
	let bd = tr.cells[1].firstChild.firstChild.value;
	console.log('bd=='+bd+';');
	tr.cells[tr.cells.length - 1].childNodes[1].firstChild.disabled = (B == '' || bd == '');  // Save button
	return false;
}
function saveBonus(obj,isNew)
{
	let tr = obj.parentNode.parentNode.parentNode;
	//alert('saveBonus: '+obj.tagName+'   '+tr.tagName);
	tr.setAttribute('saveneeded',0);
	tr.setAttribute('data-indb','1');
	let xhttp;
 
	xhttp = new XMLHttpRequest();
	xhttp.onreadystatechange = function() {
		if (this.readyState == 4 && this.status == 200) {
			if (this.responseText.trim()=='')
				if (tr.getAttribute('showspecial')==1) {
					showBonusSpecials(tr.firstChild.firstChild.firstChild);
					return;
				}

				showdelete(tr);
		}
	};
	

	let ix = 0;
	let rec = 'bid='+tr.cells[ix++].firstChild.firstChild.value;
	rec += '&bd='+tr.cells[ix++].firstChild.firstChild.value;
	rec += '&p='+tr.cells[ix++].firstChild.firstChild.value;
	let nc = document.getElementById('numcats').value;
	for (let i = 1; i <= nc; i++) {
		let c = tr.cells[ix++].firstChild.firstChild;
		let axis = c.getAttribute('data-axis');
		rec += '&cat'+axis+'='+c.value;
	}
	
	let comp = tr.cells[ix++].firstChild.firstChild.checked ? 1 : 0;
	rec += '&comp='+comp;
	
	if (isNew)
		cmd = "c=insertbonus&";
	else
		cmd = "c=updatebonus&";

	xhttp.open("POST", "bonuses.php", true);
	xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	xhttp.send(encodeURI(cmd+rec));
	console.log('sNB: '+rec+' Saved!');
	return false;
	
}
function saveNewBonus(e)
{
	let obj = e.target;
	let res = saveBonus(obj,true);
	console.log("SNB: fired from "+obj.tagName);
	console.log("SNB: removing click");
	obj.removeEventListener("click",saveNewBonus);
	console.log("SNB: adding click");
	obj.addEventListener("click",saveOldBonus);
	let row = obj.parentNode.parentNode.parentNode;
	console.log("snb: "+row.childNodes[0].firstChild.firstChild.value);
	row.childNodes[0].firstChild.firstChild.readOnly = "readonly";
	for (let i = 1; i <= 4; i++) {
		row.childNodes[i].firstChild.firstChild.removeEventListener("blur",enableNewSave)
	}
	console.log(row.childNodes[1].firstChild.firstChild.value);
	document.addEventListener('input',function(e){
    if(e.target && e.target.hasAttribute('data-newrow')){
          showsave(e.target);
     }
 });
	return res;
	
}
function showBonusSpecials(obj) {
// This needs to save existing changes then call for detailed display

	let tr = obj.parentNode.parentNode.parentNode;
	//alert('showBonusSpecials '+tr.tagName);
	let bonusid = tr.cells[0].firstChild.firstChild.value.toUpperCase();
	if (tr.getAttribute('saveneeded')=='1')
		saveBonus(obj,tr.getAttribute('data-indb') != '1');
	if (bonusid=='') {
		bonusid.focus();
		return; // No bonus code yet so no.
	}
	console.log("Loading bonus "+bonusid);
	window.location.href = 'bonuses.php?c=bonus&bonus='+bonusid;

}

function saveOldBonus(e)
{
	let obj = e.target;
	console.log("SOB: "+obj.tagName);
	return saveBonus(obj,false);
	
}
function showdelete(tr)
{
	swapdelsave(tr,false);
}
function showsave(obj)
{
	let tr = obj.parentNode.parentNode;
	swapdelsave(tr,true);
}
function swapdelsave(tr,showsave)
{
	let ix = tr.cells.length - 1;
	if (!tr.cells[ix].classList.contains('buttons'))
		ix--;
	let ds = tr.cells[ix].childNodes[0];
	let ss = tr.cells[ix].childNodes[1];
	ix--; // Now point to 'specials'
	let sds = tr.cells[ix].childNodes[0];
	if (showsave) {
		ds.style.display = 'none';
		ss.style.display = 'inline';
		sds.style.display = 'none';
		tr.setAttribute('saveneeded',1);
	} else {
		ss.style.display = 'none';
		ds.style.display = 'inline';
		sds.style.display = 'inline';
		tr.setAttribute('saveneeded',0);
	}
	
	return false;
}

function triggerNewRow()
{
	var oldnewrow = document.getElementsByClassName('newrow')[0];
	tab = document.getElementById('bonuses').getElementsByTagName('tbody')[0];
	var row = tab.insertRow(0);
	row.innerHTML = oldnewrow.innerHTML;
	row.firstChild.firstChild.focus();
	return false;
}
</script>
<?php	
	

	$R = $DB->query('SELECT * FROM rallyparams');
	$rd = $R->fetchArray();
	
	for ($i=1; $i <= $KONSTANTS['NUMBER_OF_COMPOUND_AXES']; $i++)
		$catlabels[$i] = $rd['Cat'.$i.'Label'];
	


	$R = $DB->query('SELECT * FROM categories ORDER BY Axis,BriefDesc');
	$lc = 0;
	while ($rd = $R->fetchArray())
	{
		if (!isset($cats[$rd['Axis']]))
			$cats[$rd['Axis']][0] = '';
		$cats[$rd['Axis']][$rd['Cat']] = $rd['BriefDesc'];
		
	}
	//print_r($cats1);
	
	
	
	$showclaimsbutton = (getValueFromDB("SELECT count(*) As rex FROM entrants","rex",0) > 0);
	
	for ($i=1, $j = 0; $i <= $KONSTANTS['NUMBER_OF_COMPOUND_AXES']; $i++)
		if (isset($cats[$i]))
			$j++;
		
	echo('<input type="hidden" id="numcats" value="'.$j.'">');
	
	echo('<input type="hidden" name="c" value="bonuses">');
//	echo('<input type="hidden" name="menu" value="setup">');
	echo("\r\n");
	

	echo('<div class="stickytop">'.$TAGS['BonusMaintHead'][1]);
	echo('<br><button autofocus value="+" onclick="return triggerNewRow();">+</button>');
	
	echo('</div>');
	echo('<table id="bonuses">');
//	echo('<caption title="'.htmlentities($TAGS['BonusMaintHead'][1]).'">'.htmlentities($TAGS['BonusMaintHead'][0]).'</caption>');
	echo('<thead class="listhead"><tr><th class="left">'.$TAGS['BonusIDLit'][0].'</th>');
	echo('<th class="left">'.$TAGS['BriefDescLit'][0].'</th>');
	echo('<th>'.$TAGS['BonusPoints'][0].'</th>');

	for ($i=1; $i <= $KONSTANTS['NUMBER_OF_COMPOUND_AXES']; $i++)
		if (isset($cats[$i]))
			echo('<th class="left">'.$catlabels[$i].'</th>');		
		
	echo('<th></th><th></th>');
	if ($showclaimsbutton)
		echo('<th class="ClaimsCount">'.$TAGS['ShowClaimsCount'][0].'</th>');
	echo("</tr>\r\n");
	echo('</thead><tbody>');
	
	
	$sql = 'SELECT * FROM bonuses ORDER BY BonusID';
	$R = $DB->query($sql);
	if ($DB->lastErrorCode() <> 0)
		echo($DB->lastErrorMsg().'<br>'.$sql.'<hr>');
	while ($rd = $R->fetchArray())
	{
		$rex = getValueFromDB("SELECT count(*) As rex FROM entrants WHERE ',' || BonusesVisited || ',' LIKE '%,".$rd['BonusID'].",%'","rex",0);
		
		$isspecial = $rd['AskPoints'] == 1 || $rd['AskMinutes'] == 1 || $rd['Compulsory'] != 0 || 
					$rd['Notes'] != '' || $rd['Flags'] != '' || $rd['RestMinutes'] != 0 ||
					$rd['Image'] != '' || $rd['Waffle'] != '' || $rd['Coords'] != '';
		echo('<tr class="hoverlite" data-indb="1"><td><span><input class="BonusID" type="text" readonly  value="'.$rd['BonusID'].'"></span></td>');
		echo('<td><span><input class="BriefDesc" type="text" value="'.str_replace('"','&quot;',$rd['BriefDesc']).'" oninput="return showsave(this);"></span></td>');
		echo('<td><span><input type="number" value="'.$rd['Points'].'" oninput="return showsave(this);"></span></td>');
		for ($i=1; $i <= $KONSTANTS['NUMBER_OF_COMPOUND_AXES']; $i++)
			if (isset($cats[$i]))
			{
				echo('<td><span><select data-axis="'.$i.'" oninput="return showsave(this);">');
				foreach ($cats[$i] as $ce => $bd)
				{
					echo('<option value="'.$ce.'" ');
					if ($ce == $rd['Cat'.$i])
						echo('selected ');
					echo('>'.htmlspecialchars($bd).'</option>');
				}
				echo('</select></span></td>');
			}
		
		if ($rd['Compulsory']==1)
			$chk = " checked ";
		else
			$chk = "";
		
		echo('<td class="center" title="'.$TAGS['SpecialButton'][1].'">');
		echo('<span>');
		echo('<button class="list" onclick="showBonusSpecials(this);">'.$TAGS['SpecialButton'][0]);
		if ($isspecial)
			echo('*');
		else
			echo('&nbsp;');
		echo('</button>');
		echo('</span>');
		echo('</td>');

		echo('<td class="center buttons">');
		echo('<span class="deletebutton">');

		if ($rex > 0) {
			;
		} else {
			echo('<span  title="'.$TAGS['DeleteEntryLit'][0].'">');
			echo('<input type="checkbox" onchange="enableKill(this);"> <button disabled value="-" onclick="return deleteRow(this);">-</button>');
			echo('</span>');
		}
		echo('</span>');
		echo('<span class="savebutton" style="display:none;">');
		echo('<button title="'.$TAGS['SaveRecord'][1].'" onclick="return saveOldBonus(event);">'.$TAGS['SaveRecord'][0].'</button>');
		echo('</span>');
		echo('</td>');
		
		
		if ($showclaimsbutton)
		{
			echo('<td class="ClaimsCount" title="'.$TAGS['ShowClaimsButton'][1].'">');
			if ($rex > 0)
				echo('<a href='."'entrants.php?c=entrants&mode=bonus&bonus=".$rd['BonusID']."'".'> '.$rex.' </a>');
			echo('</td>');
		}
		echo("</tr>\r\n");
	}
	echo('<tr class="newrow hide" data-indb="0"><td><span><input title="'.$TAGS['BonusIDLit'][1].'" class="BonusID" type="text" onblur="enableNewSave(this);"></span></td>');
	echo('<td><span><input data-newrow="x" type="text" onblur="enableNewSave(this);"></span></td>');
	echo('<td><span><input data-newrow="x" type="number" value="1" onblur="enableNewSave(this);"></span></td>');
	for ($i=1; $i <= $KONSTANTS['NUMBER_OF_COMPOUND_AXES']; $i++)
		if (isset($cats[$i]))
		{
			$S = ' selected ';
			echo('<td><span><select data-newrow="x" data-axis="'.$i.'" onblur="enableNewSave(this);">');
			foreach ($cats[$i] as $ce => $bd)
			{
				echo('<option value="'.$ce.'" ');
				echo($S);
				$S = '';
				echo('>'.htmlspecialchars($bd).'</option>');
			}
			echo('</select></span></td>');
		}
		
		echo('<td class="center" title="'.$TAGS['SpecialButton'][1].'">');
		echo('<span style="display:none;">');
		echo('<button class="list" onclick="showBonusSpecials(this);">'.$TAGS['SpecialButton'][0]);
		echo('&nbsp;');
		echo('</button>');
		echo('</span>');
		echo('</td>');
	
	echo('<td class="center buttons">');
	echo('<span class="deletebutton hide" >');
	echo('<input type="checkbox" onchange="enableKill(this);"> <button disabled  value="-" onclick="return deleteRow(this);">-</button>');
	echo('</span>');
	echo('<span>');
	echo('<button disabled title="'.$TAGS['SaveRecord'][1].'" onclick="return saveNewBonus(event);">'.$TAGS['SaveRecord'][0].'</button>');
	echo('</span>');
	echo('</td>');
	echo('</tr>');
	
	echo('</tbody></table>');
	
	
}

function saveSingleBonus() {

	global $DB, $KONSTANTS;

	$sql = "UPDATE bonuses SET ";
	$sql .= "BriefDesc='".$DB->escapeString($_REQUEST['BriefDesc'])."'";
	$sql .= ",Points=".intval($_REQUEST['Points']);
	$sql .= ",AskPoints=".intval($_REQUEST['AskPoints']);
	$sql .= ",Compulsory=".intval($_REQUEST['Compulsory']);
	$sql .= ",RestMinutes=".intval($_REQUEST['RestMinutes']);
	$sql .= ",AskMinutes=".intval($_REQUEST['AskMinutes']);
	for ($i=1; $i <= $KONSTANTS['NUMBER_OF_COMPOUND_AXES']; $i++)
		if (isset($_REQUEST['Cat'.$i]))
			$sql .= ",Cat$i=".intval($_REQUEST['Cat'.$i]);
	$sql .= ",Notes='".$DB->escapeString($_REQUEST['Notes'])."'";
	$sql .= ",Waffle='".$DB->escapeString($_REQUEST['Waffle'])."'";
	$sql .= ",Coords='".$DB->escapeString($_REQUEST['Coords'])."'";
	$sql .= ",Image='".$DB->escapeString($_REQUEST['Image'])."'";

	$flags = '';
	for ($i= 0; $i < strlen($KONSTANTS['BonusScoringFlags']); $i++) {
		$flg = substr($KONSTANTS['BonusScoringFlags'],$i,1);
		if (isset($_REQUEST['BonusScoringFlag'.$flg]))
			$flags .= $flg;
	}

	$sql .= ",Flags='$flags'";
	$sql .= ",GroupName='".$DB->escapeString($_REQUEST['GroupName'])."'";
	$sql .= " WHERE BonusID='".$DB->escapeString($_REQUEST['BonusID'])."'";

	$DB->exec($sql);
}



function callbackDeleteBonus($b)
{
	global $DB;
	
	$sql = "DELETE FROM bonuses WHERE BonusID='".$DB->escapeString(strtoupper($b))."'";
	$DB->exec($sql);
	if ($DB->lastErrorCode()<>0) 
		return dberror();
}

function callbackInsertBonus()
{
	global $DB, $KONSTANTS;
	
	$sql = "INSERT INTO bonuses (BonusID,BriefDesc,Points";
	for ($i = 1; $i < $KONSTANTS['NUMBER_OF_COMPOUND_AXES']; $i++)
		if (isset($_POST['cat'.$i]))
			$sql .= ",Cat$i";
	$sql .= ") VALUES(";
	$sql .= "'".$DB->escapeString(strtoupper($_POST['bid']))."'";
	$sql .= ",'".$DB->escapeString($_POST['bd'])."'";
	$sql .= ",".intval($_POST['p']);
	for ($i = 1; $i < $KONSTANTS['NUMBER_OF_COMPOUND_AXES']; $i++)
		if (isset($_POST['cat'.$i]))
			$sql .= ",".intval($_POST['cat'.$i]);
	$sql .= ")";
	$DB->exec($sql);
	if ($DB->lastErrorCode()<>0) 
		return dberror();
	
}

function callbackUpdateBonus()
{
	global $DB, $KONSTANTS;

	$sql = "UPDATE bonuses SET BriefDesc='".$DB->escapeString($_POST['bd'])."'";
	$sql .= ",Points=".intval($_POST['p']);
	for ($i = 1; $i < $KONSTANTS['NUMBER_OF_COMPOUND_AXES']; $i++)
		if (isset($_POST['cat'.$i]))
			$sql .= ",Cat".$i."=".intval($_POST['cat'.$i]);
	$sql .= " WHERE BonusID='".$DB->escapeString(strtoupper($_POST['bid']))."'";
	$DB->exec($sql);
	if ($DB->lastErrorCode()<>0) 
		return dberror();
		
}



if (isset($_REQUEST['c']))
	switch($_REQUEST['c']) {
		case 'insertbonus':
			callbackInsertBonus();
			exit;
		case 'updatebonus':
			callbackUpdateBonus();
			exit;
		case 'deletebonus':
			callbackDeleteBonus($_POST['bid']);
			exit;
	}




startHtml($TAGS['ttSetup'][0]);

//print_r($_REQUEST);

if (isset($_REQUEST['savesinglebonus'])) {
	saveSingleBonus();
	$get = "bonuses.php?c=bonuses";
	header("Location: ".$get);
	exit;

}

if (isset($_REQUEST['c']))
{
	switch($_REQUEST['c'])
	{
		case 'bonus':
			showBonus($_REQUEST['bonus']);
			break;
			
		case 'bonuses':
			showBonuses();
			break;

		default:
			echo("<p>I don't know what to do with '".$_REQUEST['c']."'!");
	}
} else
	include "score.php"; // Some mistake has happened or maybe someone just tried logging on
//	print_r($_REQUEST);

?>

