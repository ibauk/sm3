<?php
//error_reporting(E_ALL);
//ini_set('display_errors', 1);

/*
 * I B A U K   -   S C O R E M A S T E R
 *
 * I handle the rally scoring administration
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

function editSpeedPenalties()
{
	global $DB, $TAGS, $KONSTANTS;
	
	startHtml('speed');
	
	$NumLegs = getValueFromDB("SELECT NumLegs FROM rallyparams","NumLegs","1");

?>
<script>
function deleteRow(e)
{
    e = e || window.event;
    let target = e.target || e.srcElement;	
	document.querySelector('#speedPenalties').deleteRow(target.parentNode.parentNode.rowIndex);
	enableSaveButton();
}
function triggerNewRow(obj1)
{
	let obj = document.querySelector('#newrow');
	
	tab = document.getElementById('speedPenalties').getElementsByTagName('tbody')[0];
	var row = tab.insertRow(tab.rows.length);
	row.innerHTML = obj.innerHTML;
	row.style.display = 'table-row';
	row.id ='';
}
</script>
<?php	

	echo('<form method="post" action="speeding.php">');
	

	echo('<p>'.$TAGS['SpeedPExplain'][0].'</p>');
	
	echo('<table id="speedPenalties"><caption></caption>');
	echo('<thead>');
	echo('<tr><th>'.$TAGS['spMinSpeedCol'][0].'</th>');
	echo('<th>'.$TAGS['spPenaltyTypeCol'][0].'</th>');
	echo('<th>'.$TAGS['spPenaltyPointsCol'][0].'</th>');
	echo('<th');
	if ($NumLegs < 2) echo(' style="display:none;"');
	echo('>'.$TAGS['LegHdr'][0].'</th>');
	echo('<th></th>');
	echo('</thead><tbody>');
	$sql = 'SELECT rowid AS id,Basis,MinSpeed,PenaltyType,PenaltyPoints,Leg FROM speedpenalties ORDER BY MinSpeed';
	$R = $DB->query($sql);
	if ($DB->lastErrorCode() <> 0)
		echo($DB->lastErrorMsg().'<br>'.$sql.'<hr>');
	while ($rd = $R->fetchArray())
	{
		echo('<tr>');
		echo('<td><input type="hidden" name="id[]" value="'.$rd['id'].'"><input type="hidden" name="Basis[]" value="'.$rd['Basis'].'">');
		echo('<input class="smallnumber" type="number" name="MinSpeed[]" value="'.$rd['MinSpeed'].'" onchange="enableSaveButton();"></td>');
		echo('<td><select name="PenaltyType[]"  onchange="enableSaveButton();">');
		echo('<option value="0"'.($rd['PenaltyType']==0?" selected":"").'>'.$TAGS['spPenaltyTypePoints'][0].'</value>');
		echo('<option value="1"'.($rd['PenaltyType']==0?"":" selected").'>'.$TAGS['spPenaltyTypeDNF'][0].'</value>');
		echo('</select></td>');
		echo('<td><input type="number" name="PenaltyPoints[]" value="'.$rd['PenaltyPoints'].'" onchange="enableSaveButton();"');
		echo('></td>');
		echo('<td');
		if ($NumLegs < 2) echo(' style="display:none;"');
		echo('><input type="number" class="tinynumber" min="0"');
		echo(' name="Leg[]" onchange="enableSaveButton();"');
		echo(' max="'.$NumLegs.'" value="'.$rd['Leg'].'"></td>');
		echo('<td><button value="-" onclick="deleteRow(event);return false;">-</button></td>');
		echo('</tr>');
	}
?>
<tr id="newrow" style="display:none;">
<td>
<input type="hidden" name="id[]" value="">
<input type="hidden" name="Basis[]" value="0">
<input class="smallnumber" type="number" name="MinSpeed[]" value="" onchange="enableSaveButton();">
</td>
<td><select name="PenaltyType[]" onchange="enableSaveButton();" >
<?php
	echo('<option value="0" selected >'.$TAGS['spPenaltyTypePoints'][0].'</value>');
	echo('<option value="1">'.$TAGS['spPenaltyTypeDNF'][0].'</value>');
?>		
</select></td>
<?php
echo('<td');
if ($NumLegs < 2) echo(' style="display:none;"');
echo('><input type="number" class="tinynumber" min="0" max="'.$NumLegs.'" name="Leg[]" onchange="enableSaveButton();" value="0">');
echo('</td>');
?>
<td><input type="number" name="PenaltyPoints[]" value="" onchange="enableSaveButton();"></td>
<td><button value="-" onclick="deleteRow(event);return false;">-</button></td>
</tr>
<?php	


	echo('</tbody></table>');
	echo('<button value="+" autofocus onclick="triggerNewRow(this);return false;">+</button><br>');
	echo('<input type="submit" class="noprint" title="'.$TAGS['SaveSettings'][1].'" id="savedata" data-triggered="0" onclick="'."this.setAttribute('data-triggered','1')".'" disabled accesskey="S" name="savedata" data-altvalue="'.$TAGS['SaveSettings'][0].'" value="'.$TAGS['SettingsSaved'][0].'" /> ');
	
	echo('</form>');

	
	echo('</body></html>');
}


function saveSpeedPenalties()
{
	global $DB, $TAGS, $KONSTANTS;
	
	
	if (!$DB->exec('BEGIN IMMEDIATE TRANSACTION')) {
		dberror();
		exit;
	}

	// Delete all existing rules
	$sql = "DELETE FROM speedpenalties";
	$DB->exec($sql);
	
	// Now save the new ones	
	$lines = count($_REQUEST['MinSpeed']);
	for ($i = 0; $i < $lines; $i++)
	{
		if ($_REQUEST['MinSpeed'][$i] != '')
		{
			$sql = "INSERT INTO speedpenalties (Basis,MinSpeed,PenaltyType,PenaltyPoints,Leg) VALUES(";
			$sql .= intval($_REQUEST['Basis'][$i]);
			$sql .= ",".intval($_REQUEST['MinSpeed'][$i]);
			$sql .= ",".intval($_REQUEST['PenaltyType'][$i]);
			$sql .= ",".intval($_REQUEST['PenaltyPoints'][$i]);
			$sql .= ",".intval($_REQUEST['Leg'][$i]);
			$sql .= ")";
			$DB->exec($sql);
		}
		
	}
	$DB->exec('COMMIT TRANSACTION');
}

//print_r($_REQUEST);
if (isset($_REQUEST['savedata']))
	saveSpeedPenalties();
editSpeedPenalties();
?>
