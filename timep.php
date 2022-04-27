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



function saveTimePenalties()
{
	global $DB, $TAGS, $KONSTANTS, $DBVERSION;

	if (!$DB->exec('BEGIN IMMEDIATE TRANSACTION')) {
		dberror();
		exit;
	}
	if ($DB->lastErrorCode()<>0) 
		return dberror();			
	$DB->exec('DELETE FROM timepenalties');
	if ($DB->lastErrorCode()<>0) 
		return dberror();			
	
	$Nmax = count($_REQUEST['PenaltyFactor']);
	for ($i = 0; $i < $Nmax; $i++)
	{
		if ($DBVERSION < 3)
		{
			$ts = '';
			$_REQUEST['TimeSpec'][$i] = $KONSTANTS['TimeSpecDatetime'];
		}
		else
			$ts = 'TimeSpec,';
		
		$sql = "INSERT INTO timepenalties (".$ts."PenaltyStart,PenaltyFinish,PenaltyMethod,PenaltyFactor) VALUES (";
		if ($ts != '')
			$sql .= $_REQUEST['TimeSpec'][$i].',';
		if ($_REQUEST['TimeSpec'][$i] == $KONSTANTS['TimeSpecDatetime'])
		{
			$sql .= "'".$_REQUEST['PenaltyStartDate'][$i].'T'.$_REQUEST['PenaltyStartTime'][$i]."'";
			$sql .= ",'".$_REQUEST['PenaltyFinishDate'][$i].'T'.$_REQUEST['PenaltyFinishTime'][$i]."'";
		}
		else
		{
			$sql .= $_REQUEST['PenaltyStartTime'][$i];
			$sql .= ','.$_REQUEST['PenaltyFinishTime'][$i];
		}
		$sql .= ",".$_REQUEST['PenaltyMethod'][$i];
		$sql .= ",".$_REQUEST['PenaltyFactor'][$i];
		$sql .= ")";
			
		if ( ($_REQUEST['TimeSpec'][$i] != $KONSTANTS['TimeSpecDatetime'] || $_REQUEST['PenaltyStartDate'][$i] <> '') && 
			$_REQUEST['PenaltyStartTime'][$i] <> '')
		{
			$DB->exec($sql);
			if ($DB->lastErrorCode()<>0) 
				return dberror();			
		}
		else
			echo('Row '.$i." wasn't posted");
		
		
	}
	$DB->exec('COMMIT TRANSACTION');
	if ($DB->lastErrorCode()<>0) 
		return dberror();			
	if (isset($_REQUEST['menu'])) 
	{
		$_REQUEST['c'] = $_REQUEST['menu'];
		include("admin.php");
		exit;
	}

	
}












function showTimePenalties()
{
	global $DB, $TAGS, $KONSTANTS, $DBVERSION;
	

	$tpdefs = defaultRecord('timepenalties');
	
	$sql = 'SELECT rowid AS id,TimeSpec,PenaltyStart,PenaltyFinish,PenaltyMethod,PenaltyFactor FROM timepenalties ORDER BY PenaltyStart';
	$R = $DB->query($sql);
	if ($DB->lastErrorCode() <> 0)
		echo($DB->lastErrorMsg().'<br>'.$sql.'<hr>');

?>
<script>
function deleteRow(e)
{
    e = e || window.event;
    let target = e.target || e.srcElement;	
	document.getElementById('timepenalties').deleteRow(target.parentNode.parentNode.rowIndex);
	enableSaveButton();
}

function triggerNewRow(obj)
{
	var oldnewrow = document.getElementsByClassName('newrow')[0];
	tab = document.getElementById('timepenalties').getElementsByTagName('tbody')[0];
	var row = tab.insertRow(tab.rows.length);
	row.innerHTML = oldnewrow.innerHTML;
	obj.onchange = '';
}
function changeTimeSpec(obj)
{
	function setv(obj,v)
	{
		try {
			obj.value = v;
		} catch(err) {
		}
	}
	
	var row = obj.parentNode.parentNode; // TR
	var opt = obj.value;
	var idt = row.getElementsByClassName('date');
	var iti = row.getElementsByClassName('time');
	xdt = opt==0 ? 'date' : 'hidden';
	xti = opt==0 ? 'time' : 'number';
	for (var i=0; i < idt.length; i++)
	{
		var v = idt[i].value;
		idt[i].type = xdt;
		setv(idt[i],v);
		v = iti[i].value;
		iti[i].type = xti;
		setv(iti[i],v);
	}
	enableSaveButton();
}
</script>
<?php	


	echo('<form method="post" action="timep.php">');
	
	echo('<input type="hidden" name="c" value="timep">');
	echo('<input type="hidden" name="menu" value="setup">');
	echo('<p>'.$TAGS['TimePExplain'][0].'</p>');

	echo('<table id="timepenalties">');
	echo('<caption></caption>');
	echo('<thead><tr><th>'.$TAGS['tpTimeSpecLit'][0].'</th><th>'.$TAGS['tpStartLit'][0].'</th>');
	echo('<th>'.$TAGS['tpFinishLit'][0].'</th>');
	echo('<th>'.$TAGS['tpMethodLit'][0].'</th>');
	echo('<th>'.$TAGS['tpFactorLit'][0].'</th>');
	echo('<th></th>');
	echo('</tr>');
	echo('</thead><tbody>');
	
	
	while ($rd = $R->fetchArray())
	{
		echo("\n".'<tr class="hoverlite">');
		echo('<td><input type="hidden" name="id[]" value="'.$rd['id'].'">');
		echo('<select name="TimeSpec[]" onchange="changeTimeSpec(this)">');
		for ($i=0; $i<3; $i++) // Max TimeSpec==3
		{
			echo('<option value="'.$i.'" ');
			if ($i==$rd['TimeSpec'])
				echo(' selected ');
			echo('>'.$TAGS['tpTimeSpec'.$i][0].'</option>');
		}
		echo('</select></td><td title="'.$TAGS['tpStartLit'][1].'">');
		if ($rd['TimeSpec']==$KONSTANTS['TimeSpecDatetime'])
		{
			$dtx = splitDatetime($rd['PenaltyStart']);
			echo('<input type="date" class="date" name="PenaltyStartDate[]" value="'.$dtx[0].'" onchange="enableSaveButton();"> ');
			echo('<input type="time" class="time" name="PenaltyStartTime[]" value="'.$dtx[1].'" onchange="enableSaveButton();"></td>');
			$dtx = splitDatetime($rd['PenaltyFinish']);		
			echo('<td title="'.$TAGS['tpFinishLit'][1].'"><input class="date" type="date" name="PenaltyFinishDate[]" value="'.$dtx[0].'" onchange="enableSaveButton();"> ');
			echo('<input class="time" type="time" name="PenaltyFinishTime[]" value="'.$dtx[1].'" onchange="enableSaveButton();"></td>');
		}
		else
		{
			echo('<input class="date" type="hidden" name="PenaltyStartDate[]" value="0"> ');
			echo('<input class="time" type="number" name="PenaltyStartTime[]" value="'.$rd['PenaltyStart'].'" onchange="enableSaveButton();"></td>');
			echo('<td title="'.$TAGS['tpFinishLit'][1].'"><input class="date" type="hidden" name="PenaltyFinishDate[]" value="0"> ');
			echo('<input class="time" type="number" name="PenaltyFinishTime[]" value="'.$rd['PenaltyFinish'].'" onchange="enableSaveButton();"></td>');
		}
		echo('<td><select name="PenaltyMethod[]" onchange="enableSaveButton();">');
		for ($i=0;$i<=3;$i++)
		{
			echo("<option value=\"$i\"");
			if ($i==$rd['PenaltyMethod'])
				echo(' selected');
			echo(">");
			echo($TAGS['tpMethod'.$i][1].'</option>');
		}
		echo('</select></td>');
		echo('<td><input type="number" name="PenaltyFactor[]" value="'.$rd['PenaltyFactor'].'" onchange="enableSaveButton();"></td>');
		echo('<td class="center"><button value="-" onclick="deleteRow(event);return false;">-</button></td>');
		echo('</tr>');
	}
	echo('<tr class="newrow hide"><td><input type="hidden" name="id[]" value="">');
	echo('<select name="TimeSpec[]" onchange="changeTimeSpec(this)">');
	for ($i=0; $i<3; $i++) // Max TimeSpec==3
	{
		echo('<option value="'.$i.'" ');
		if ($i==$tpdefs['TimeSpec'])
			echo(' selected ');
		echo('>'.$TAGS['tpTimeSpec'.$i][0].'</option>');
	}
	echo('</select></td><td title="'.$TAGS['tpStartLit'][1].'">');
	$datetype = ($tpdefs['TimeSpec'] == $KONSTANTS['TimeSpecDatetime'] ? 'date' : 'hidden');
	$timetype = ($tpdefs['TimeSpec'] == $KONSTANTS['TimeSpecDatetime'] ? 'time' : 'number');

	echo('<input class="date" type="'.$datetype.'" name="PenaltyStartDate[]" value="" onchange="enableSaveButton();"> ');
	echo('<input class="time" type="'.$timetype.'" name="PenaltyStartTime[]" value="" onchange="enableSaveButton();"></td>');
	echo('<td title="'.$TAGS['tpFinishLit'][1].'"><input class="date" type="'.$datetype.'" name="PenaltyFinishDate[]" value="" onchange="enableSaveButton();"> ');
	echo('<input class="time" type="'.$timetype.'" name="PenaltyFinishTime[]" value="" onchange="enableSaveButton();"></td>');
	echo('<td><select name="PenaltyMethod[]" onchange="enableSaveButton();">');
	for ($i=0;$i<=3;$i++)
	{
		echo("<option value=\"$i\"");
		if ($i==$tpdefs['PenaltyMethod'])
			echo(' selected');
		echo(">");
		echo($TAGS['tpMethod'.$i][1].'</option>');
	}
	echo('</select></td>');
	echo('<td><input type="number" name="PenaltyFactor[]" value="'.$tpdefs['PenaltyFactor'].'" onchange="enableSaveButton();"></td>');
	echo('<td class="center"><button value="-" onclick="deleteRow(event);return false;">-</button></td>');
	echo('</tr>');
	
	echo('</tbody></table>');
	echo('<button value="+" autofocus onclick="triggerNewRow(this);return false;">+</button><br>');
	
	echo('<input type="submit" class="noprint" title="'.$TAGS['SaveSettings'][1].'" id="savedata" data-triggered="0" onclick="'."this.setAttribute('data-triggered','1')".'" disabled accesskey="S" name="savedata" data-altvalue="'.$TAGS['SaveSettings'][0].'" value="'.$TAGS['SettingsSaved'][0].'" /> ');
	echo('</form>');
	//showFooter();
	
}




if (isset($_REQUEST['c']))
	switch($_REQUEST['c']) {
			
		case 'timep':
			if (isset($_REQUEST['savedata']))
				saveTimePenalties();
			break;

	}





startHtml($TAGS['ttSetup'][0]);


if (isset($_REQUEST['c']))
{
	switch($_REQUEST['c'])
	{
		case 'timep':
			showTimePenalties();
			break;
	}
} else
	include "score.php"; // Some mistake has happened or maybe someone just tried logging on
//	print_r($_REQUEST);

?>

