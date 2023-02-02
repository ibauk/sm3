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

 

 
 // TODO - disallow result=multipliers when level=bonus
 //	TODO - trapdirtypage
 
 
$HOME_URL = 'admin.php';

require_once('common.php');

// Alphabetic from here on in


function listCompoundCalcs() {
	
	global $DB, $TAGS, $KONSTANTS;

	$AxisLabels = [];
	for ($i = 1; $i <= $KONSTANTS['NUMBER_OF_COMPOUND_AXES']; $i++)
		$AxisLabels['Cat'.$i.'Label'] = '';
	$alx = implode(',',array_keys($AxisLabels));
	
	$R = $DB->query("SELECT $alx FROM rallyparams");
	
	$rd = $R->fetchArray();
	foreach (explode(',',$alx) As $cat)
		$AxisLabels[$cat] = $rd[$cat];

	$cats = fetchCategoryArrays();
	// Now add the '0' entries
	for ($i=1;$i<=$KONSTANTS['NUMBER_OF_COMPOUND_AXES'];$i++)
		if (isset($cats[$i]))
			$cats[$i][0] = $TAGS['ccApplyToAll'][0];

	$sql = 'SELECT rowid as id,Cat,Axis,NMethod,ModBonus,NMin,PointsMults,NPower,Ruletype FROM catcompound ORDER BY Axis,Cat,NMin DESC';
	$R = $DB->query($sql);
	if ($DB->lastErrorCode() <> 0)
		echo($DB->lastErrorMsg().'<br>'.$sql.'<hr>');

	
	echo('<div class="stickytop">'.$TAGS['CalcMaintHead'][1]);
	echo(' <input title="Help!" type="button" value=" ? " onclick="showHelp('."'compound'".');">');
	$url = "cats.php?c=newcc";
	echo('<br>');
	$numAxes = 0;
	foreach($AxisLabels as $al => $av) {
		if ($av != '')
			$numAxes++;
	}
	if ($numAxes < 1) {
		echo (' <label class="link" for="newcc_button">' . $TAGS['ccNoAxesAvail'][0] . '</label> ');
	}

	echo('<button value="+" autofocus id="newcc_button" onclick="window.location='."'".$url."'".'">+</button>');
	echo('</div>');

	echo('<table id="catcalcs">');
//	echo('<caption title="'.htmlentities($TAGS['CalcMaintHead'][1]).'">'.htmlentities($TAGS['CalcMaintHead'][0]).'</caption>');
	echo("\r\n".'<thead class="listhead"><tr><th class="rowcol">#</th><th class="rowcol">'.$TAGS['AxisLit'][0].'</th>');
	echo('<th class="rowcol">'.$TAGS['CatEntry'][0].'</th>');
	echo('<th class="rowcol">'.$TAGS['ModBonusLit'][0].'</th>');
	echo('<th class="rowcol">'.$TAGS['NMethodLit'][0].'</th>');
	echo('<th class="rowcol">'.$TAGS['NMinLit'][0].'</th>');
	echo('<th class="rowcol">'.$TAGS['PointsMults'][0].'</th>');
	echo('<th class="rowcol">'.$TAGS['NPowerLit'][0].'</th>');
	echo('<th class="rowcol">'.$TAGS['ccRuletype'][0].'</th>');
	echo('</tr>');
	echo('</thead><tbody>');


	while ($rd = $R->fetchArray()) {

		echo("\r\n".'<tr class="hoverlite clickme" onclick="window.location=\'cats.php?c=showcc&amp;ruleid='.$rd['id'].'\'">');
		echo('<td class="rowcol">'.$rd['id'].'</td>');
		
		echo('<td  class="rowcol" title="'.$TAGS['AxisLit'][1].'"><input type="hidden" name="id[]" value="'.$rd['id'].'">');
		echo($AxisLabels['Cat'.$rd['Axis'].'Label']);
		echo('</td>');
		
		echo('<td  class="rowcol" title="'.$TAGS['CatEntry'][1].'">');
		$sax = strval($rd['Axis']);  // Key not index
		$scat = strval($rd['Cat']);	 // Key not index
		echo($rd['Cat'].':'.$cats[$sax][$scat]);
		echo('</td>');
		
		echo('<td  class="rowcol" title="'.$TAGS['ModBonusLit'][1].'">');
		echo($TAGS['ModBonus'.$rd['ModBonus']][0]);
		echo('</td>');
		
		
		echo('<td  class="rowcol" title="'.$TAGS['NMethodLit'][1].'">');
		echo($TAGS['NMethod'.$rd['NMethod']][0]);
		echo('</td>');

		echo('<td  class="rowcol" title="'.$TAGS['NMinLit'][1].'">');
		echo($rd['NMin']);
		echo('</td>');

		echo('<td  class="rowcol" title="'.$TAGS['PointsMults'][1].'">');
		echo($TAGS['PointsMults'.$rd['PointsMults']][1]);
		echo('</td>');

		echo('<td class="rowcol" title="'.$TAGS['NPowerLit'][1].'">');
		echo($rd['NPower']);
		echo('</td>');
		
		echo('<td class="rowcol" title="'.$TAGS['ccRuletype'][1].'">');
		
		echo($TAGS['ccRuletype'.$rd['Ruletype']][0]);
		echo('</td>');
		
	
		echo('</tr>');
	}
	
	
	
	echo('</tbody></table>');




	
}


function fetchCategoryArrays()
{
	global $DB;
	
	$R = $DB->query("SELECT Axis,Cat,BriefDesc FROM categories ORDER BY Axis,BriefDesc");
	$res = [];
	while ($rd = $R->fetchArray())
		$res[$rd['Axis']][$rd['Cat']] = $rd['BriefDesc'];
	return $res;
}

function saveCompoundCalc()
{
	global $DB, $TAGS, $KONSTANTS, $DBVERSION;
	
	if (!isset($_REQUEST['ruleid']))
	{
		$sql = "INSERT INTO catcompound (Axis, Cat, NMethod, ModBonus, NMin, PointsMults, NPower";
		$sql .= ", Ruletype";
		$sql .= ") VALUES (";
		$sql .= intval($_REQUEST['Axis']);
		if (!isset($_REQUEST['Cat']))
			$sql .= ",0";
		else
			$sql .= ','.intval($_REQUEST['Cat']);
		$sql .= ','.intval($_REQUEST['NMethod']);
		$sql .= ','.intval($_REQUEST['ModBonus']);
		$sql .= ','.intval($_REQUEST['NMin']);
		if (!isset($_REQUEST['PointsMults']))
			$sql .= ',0';
		else
			$sql .= ','.intval($_REQUEST['PointsMults']);
		if (!isset($_REQUEST['NPower']))
			$sql .= ',0';
		else
			$sql .= ','.floatval($_REQUEST['NPower']);
		$sql .= ','.intval($_REQUEST['Ruletype']);
		$sql .= ');';
	}
	else if (isset($_REQUEST['deletecc']))
	{
		$sql = "DELETE FROM catcompound WHERE rowid=".intval($_REQUEST['ruleid']);
	}
	else
	{
		$sql = "UPDATE catcompound SET ";
		$sql .= "Axis=".intval($_REQUEST['Axis']);
		if (!isset($_REQUEST['Cat']))
			$sql .=",Cat=0";
		else
			$sql .= ",Cat=".intval($_REQUEST['Cat']);
		$sql .= ",NMethod=".intval($_REQUEST['NMethod']);
		$sql .= ",ModBonus=".intval($_REQUEST['ModBonus']);
		$sql .= ",NMin=".intval($_REQUEST['NMin']);
		if (isset($_REQUEST['PointsMults']))
			$sql .= ",PointsMults=".intval($_REQUEST['PointsMults']);
		if (isset($_REQUEST['NPower']))
			$sql .= ",NPower=".floatval($_REQUEST['NPower']);
		$sql .= ",Ruletype=".intval($_REQUEST['Ruletype']);
		$sql .=  " WHERE rowid=".intval($_REQUEST['ruleid']);
	}
	$DB->exec($sql);
	if ($DB->lastErrorCode()<>0) 
		return dberror();			

}

function showCompoundCalc($ruleid)
{

	global $DB, $TAGS, $KONSTANTS;

	$NumLegs = getValueFromDB("SELECT NumLegs FROM rallyparams","NumLegs","1");

	$cats = fetchCategoryArrays();

	$AxisLabels = [];
	for ($i = 1; $i <= $KONSTANTS['NUMBER_OF_COMPOUND_AXES']; $i++)
		$AxisLabels['Cat'.$i.'Label'] = '';
	$alx = implode(',',array_keys($AxisLabels));
	
	$R = $DB->query("SELECT $alx FROM rallyparams");
	
	$rd = $R->fetchArray();
	foreach (explode(',',$alx) As $cat)
		$AxisLabels[$cat] = $rd[$cat];
		
	
	for ($i=1;$i<=$KONSTANTS['NUMBER_OF_COMPOUND_AXES'];$i++) // Possible to specify no axis
		if ($AxisLabels['Cat'.$i.'Label']=='')
			unset($AxisLabels['Cat'.$i.'Label']);
		else
			$AxisLabels['Cat'.$i.'Label']=$AxisLabels['Cat'.$i.'Label'];

	$numAxes = count($AxisLabels);
	
	if ($numAxes < 1) {	// Can't create rules if no axes defined
		listAxes();
		exit;
	}
	
	if ($ruleid > 0) 	{
		$R = $DB->query("SELECT * FROM catcompound WHERE rowid=".$ruleid);
		$rd = $R->fetchArray();
	} else
		$rd = defaultRecord('catcompound');

	echo('<form method="post" action="cats.php">');
	for ($i = 0; $i < $KONSTANTS['NUMBER_OF_COMPOUND_AXES']; $i++)
	{
		$j = $i + 1;
		$k = '0='.$TAGS['ccApplyToAll'][0];
		if (isset($cats[$j]))
			foreach($cats[$j] as $cat => $bd)
				$k .= ",$cat=$bd";
		echo('<input type="hidden" id="axis'.$j.'cats" value="'.$k.'">');
	}
	echo('<input type="hidden" name="c" value="savecalc">');
	echo('<input type="hidden" name="menu" value="advanced">');
	
	
	echo('<span class="vlabel" title="'.$TAGS['AxisLit'][1].'">');
	echo('<label class="wide" for="axis">'.$TAGS['AxisLit'][0].'</label> ');
	
	if ($numAxes < 2) {
		$key = array_keys($AxisLabels)[0];
		echo('<input type="hidden" name="Axis" value="'.substr($key,3,1).'">');
		echo('<input type="text" readonly value="'.$AxisLabels[$key].'">');
	} else {
		echo('<select id="axis" name="Axis" onchange="ccShowSelectAxisCats(this.value,document.getElementById(\'selcat\'));">');
		foreach ($AxisLabels As $i => $x) {
			$ax = substr($i,3,1);
			echo('<option value="'.$ax.'"');
			if ($ax==$rd['Axis'])
				echo(' selected');
			echo('>'.$x.'</option>');
		}
		echo('</select> ');
	}
	echo('</span>');


	echo('<span class="vlabel" title="'.$TAGS['NMethodLit'][1].'">');
	echo('<label class="wide" for="NMethod">'.$TAGS['NMethodLit'][0].'</label> ');
	echo('<select name="NMethod" onchange="ccSwapCatMethod(this.value);">');
	echo('<option value="0" '.($rd['NMethod']==0 ? 'selected>' : '>').$TAGS['NMethod0'][1].'</option>');
	echo('<option value="1"'.($rd['NMethod']==1 ? 'selected>' : '>').$TAGS['NMethod1'][1].'</option>');
	//echo('<option value="-1"'.($rd['NMethod']<0 ? 'selected>' : '>').$TAGS['NMethod-1'][1].'</option>');
	echo('</select> ');
	echo('</span>');
	



	echo('<span class="vlabel" title="'.$TAGS['CatEntryCC'][1].'">');
	echo('<label class="wide" for="Cat">'.$TAGS['CatEntryCC'][0].'</label> ');
	//echo('<input type="number" name="Cat" id="Cat" value="0">');
	echo('<select id="selcat" name="Cat" '.($rd['NMethod']==1 ? " disabled ": "").'>');
	echo('<option value="0" ');
	if ($rd['Cat']==0) echo(' selected');
	echo('>0:'.$TAGS['ccApplyToAll'][0].'</option>');
	if (isset($cats[1]))
		foreach($cats[1] as $cat => $bd) {
			echo('<option value="'.$cat.'"');
			if ($rd['Cat']==$cat) echo(' selected');
			echo('>'.$cat.':'.$bd.'</option>');
		}
	
	echo('</select>');
	echo('</span>');
	echo('<span class="vlabel" title="'.$TAGS['ModBonusLit'][1].'">');
	echo('<label class="wide" for="ModBonus">'.$TAGS['ModBonusLit'][0].'</label> ');
	echo('<select name="ModBonus">');
	for ($i=0;$i<=1;$i++)
	{
		echo("<option value=\"$i\"");
		if ($i==$rd['ModBonus'])
			echo(' selected');
		echo('>'.$TAGS['ModBonus'.$i][1].'</option>');
	}
	echo('</select>');
	echo('</span>');
	
	echo('<span class="vlabel" title="'.$TAGS['ccRuletype'][1].'">');
	echo('<label class="wide" for="ccRuletype">'.$TAGS['ccRuletype'][0].'</label> ');
	echo('<select name="Ruletype">');
	for ($i=0;$i<=4;$i++)
	{
		echo("<option value=\"$i\"");
		if ($i==$rd['Ruletype'])
			echo(' selected');
		echo('>'.$TAGS['ccRuletype'.$i][1].'</option>');
	}
	echo('</select>');
	echo('</span>');


	echo('<span class="vlabel" title="'.$TAGS['NMinLit'][1].'">');
	echo('<label class="wide" for="NMin">'.$TAGS['NMinLit'][0].'</label> ');
	echo('<input type="number" class="smallnumber" name="NMin" value="'.$rd['NMin'].'">');
	echo('</span>');
	
	echo('<span class="vlabel" title"'.$TAGS['PointsMults'][1].'">');
	echo('<label class="wide" for="PointsMults">'.$TAGS['PointsMults'][0].'</label> ');
	echo('<select name="PointsMults" id="PointsMults">');
	for ($i=0;$i<=1;$i++)
	{
		echo("<option value=\"$i\"");
		if ($i==$rd['PointsMults'])
			echo(' selected');
		echo('>'.$TAGS['PointsMults'.$i][1].'</option>');
	}
	echo('</select>');
	echo('</span>');
	
	echo('<span class="vlabel" title="'.$TAGS['NPowerLit'][1].'">');
	echo('<label class="wide" for="NPower">'.$TAGS['NPowerLit'][0].'</label> ');
	$stepv = floatval(getSetting('multStepValue','1'));
	echo('<input type="number" name="NPower" id="NPower" min="0.0" step="'.$stepv.'" value="'.$rd['NPower'].'">');
	echo('</span>');

	echo('<span class="vlabel"');
	if ($NumLegs < 2) echo(' style="display:none;"');
	echo(' title="'.$TAGS['BonusLeg'][1].'">');
	echo('<label class="wide" for="Leg">'.$TAGS['BonusLeg'][0].'</label>');
	echo(' <input type="number" class="tinynumber" id="Leg" name="Leg" min="0"');
	echo(' max="'.$NumLegs.'" onchange="enableSaveButton();" value="'.$rd['Leg'].'">');
	echo('</span>'); // Leg

	echo('<span class="vlabel"><label for="sdbutton"></label>');
	if ($ruleid < 1)
		echo('<input type="hidden" name="newcc" value="1">');
	else {
		echo('<input type="hidden" name="ruleid" value="'.$ruleid.'">');
		echo('<span title="'.$TAGS['DeleteEntryLit'][1].'">');
		echo('<label for="deletecmd">'.$TAGS['DeleteEntryLit'][0].'</label> ');
		echo('<input id="deletecmd" type="checkbox" name="deletecc"> '); 
		echo('</span>');
	}
	echo('<input type="submit" id="sdbutton" name="savedata" value="'.$TAGS['SaveNewCC'][0].'"> ');
	echo('</span>');
	
	echo('</form>');
	//showFooter();
}

function showNewCompoundCalc()
{
	
	showCompoundCalc(0);
	
}






















function emitJS() {
?>
<script>
function lineHasChanged(obj) {
	let row = obj.parentNode.parentNode;
	let sav = row.childNodes[2].firstChild;
	if (sav.disabled) {
		let val = sav.value;
		sav.value = sav.getAttribute('data-value');
		sav.setAttribute('data-value',val);
		sav.disabled = false;
	}
}
function saveAxis(obj) {
	let row = obj.parentNode.parentNode;
	let sav = row.childNodes[2].firstChild;
	let axis = row.childNodes[1].firstChild;
	let xhttp = new XMLHttpRequest();
	xhttp.onreadystatechange = function() {
		let ok = new RegExp("\W*ok\W*");
		if (this.readyState == 4 && this.status == 200) {
			console.log('{'+this.responseText+'}');
			if (ok.test(this.responseText)) {
				document.getElementById('addCatButton').disabled = false;
				if (!sav.disabled) {
					let val = sav.value;
					sav.value = sav.getAttribute('data-value');
					sav.setAttribute('data-value',val);
					sav.disabled = true;
				}
			}
		}
	};
	xhttp.open("GET", "cats.php?c=putaxis&a="+axis.name+'&v='+axis.value, true);
	xhttp.send();
	
}
function saveCat(obj) {
	let row = obj.parentNode.parentNode;
	let sav = row.childNodes[2].firstChild;
	let axis = document.getElementById('catrows').getAttribute('data-axis');
	let cat = row.firstChild.firstChild.value;
	let desc = row.cells[1].firstChild.value;
	let newrow = row.getAttribute('data-newrow')=='1';
	let xhttp = new XMLHttpRequest();
	xhttp.onreadystatechange = function() {
		let ok = new RegExp("\W*ok\W*");
		if (this.readyState == 4 && this.status == 200) {
			console.log('{'+this.responseText+'}');
			if (ok.test(this.responseText)) {
				if (!sav.disabled) {
					let val = sav.value;
					sav.value = sav.getAttribute('data-value');
					sav.setAttribute('data-value',val);
					sav.disabled = true;
				}
			}
		}
	};
	xhttp.open("GET", "cats.php?c=putcat&a="+axis+'&ac='+cat+'&v='+desc, true);
	xhttp.send();
	
}

function showAxisCats(obj) {
	let row = obj.parentNode;
	let axis = row.getAttribute('data-axis');
	let xhttp = new XMLHttpRequest();
	xhttp.onreadystatechange = function() {
		let ok = new RegExp("\W*ok\W*");
		if (this.readyState == 4 && this.status == 200) {
			document.getElementById('categories').innerHTML = this.responseText;
		}
	};
	xhttp.open("GET", "cats.php?c=getcats&a="+axis, true);
	xhttp.send();
	
}

function addAxis(obj) {
	let tab = document.getElementById('axes');
	let i = 0;
	while (i < tab.rows.length) {
		if (tab.rows[i].classList.contains('hide')) {
			tab.rows[i].className = 'show';
			if (i + 1 >= tab.rows.length)
				obj.disabled = true;
			return;
		}
		i++;
	}
}
function addCat() {
	let tab = document.getElementById('catrows');
	let len = tab.rows.length;
	let lastnum = 0;
	if (len > 1)
		lastnum = parseInt(tab.rows[len-1].firstChild.firstChild.value);
	lastnum++;
	let row = tab.insertRow(-1);
	row.setAttribute('data-newrow','1');
	let ncell = row.insertCell(-1);
	ncell.style.textAlign = 'center';
	ncell.innerHTML = '<input readonly tabindex="-1" type="text" class="smallnumber" value="'+lastnum+'">';
	let dcell = row.insertCell(-1);
	dcell.innerHTML = '<input type="text">';
	dcell.firstChild.focus();
	let bcell = row.insertCell(-1);
	bcell.innerHTML = document.getElementById('newbuttonarray').innerHTML;
}
function flipSave(obj) {
	let row = obj.parentNode.parentNode;
	let buttons = row.cells[row.cells.length - 1].childNodes;
	console.log(buttons);
	let sav = buttons[buttons.length - 1];
	if (sav.disabled != obj.checked) 
		return;
	console.log('need to flipsave');
	let val = sav.value;
	sav.value = sav.getAttribute('data-value');
	sav.setAttribute('data-value',val);
	sav.disabled = !sav.disabled;
}
function deleteCat(obj) {
	let row = obj.parentNode.parentNode;
	let tab = document.getElementById('catrows');
	let axis = tab.getAttribute('data-axis');
	let cat = row.firstChild.firstChild.value;
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
	xhttp.open("GET", "cats.php?c=delcat&a="+axis+'&ac='+cat, true);
	xhttp.send();


}
</script>
<?php	
}

function listAxes() {
	
	global $DB, $TAGS, $KONSTANTS;
	
	emitJS();
	
	echo('<p>'.$TAGS['CategoryAxes'][1].'</p>');
	
	$sql = "SELECT * FROM rallyparams";
	$R = $DB->query($sql);
	if (!$rd = $R->fetchArray())
		$rd = defaultRecord('rallyparams');
	echo('<table id="axes">');
	
	$rows = 0;
	
	for ($i = 1; $i <= $KONSTANTS['NUMBER_OF_COMPOUND_AXES']; $i++) {
		$lbl = 'Cat'.$i.'Label';
		if ($rd[$lbl] != '') {
			$cls = 'show';
			$rows++;
		} else
			$cls = 'hide';
		$cls = $rd[$lbl] != '' ? 'show' : 'hide';
		echo('<tr class="'.$cls.'" data-axis="'.$i.'"><td onclick="showAxisCats(this);" style="padding-right:.5em; cursor:pointer;">'.$TAGS[$lbl][0].'</td><td style="padding-right:.5em;"  title="'.$TAGS[$lbl][1].'" >');
		echo('<input oninput="lineHasChanged(this);" type="text" placeholder="'.$TAGS['unset'][0].'" name="Cat'.$i.'Label" value="'.htmlspecialchars(''.$rd[$lbl]).'" onchange="saveAxis(this);">');
		echo('</td><td><input type="button" disabled data-value="'.$TAGS['SaveRecord'][0].'" value="'.$TAGS['RecordSaved'][0].'" onclick="saveAxis(this);">');
		echo('</td></tr>');
	}
	echo('</table>');
	if ($rows < $KONSTANTS['NUMBER_OF_COMPOUND_AXES'])
		echo('<input type="button" autofocus value="+" onclick="addAxis(this);">');
	
	echo('<hr>');
	echo('<div id="newbuttonarray" style="display:none;">');
	echo('<input type="button" data-value="'.$TAGS['RecordSaved'][0].'" value="'.$TAGS['SaveRecord'][0].'" onclick="saveCat(this);"> ');
//	echo('<input type="checkbox" title="'.$TAGS['tickdelete'][1].'" onclick="flipSave(this);"> <input disabled type="button" data-value="-" value="-" onclick="deleteCat(this);">');
	echo('</div>');
	
	echo('<div id="categories">');
	if (isset($_REQUEST['a']))
		echo(listCategories($_REQUEST['a'],$rows < 1));
	else
		echo(listCategories(1,$rows < 1));
	echo('</div>');
}

function listCategories($axis,$disabled) {

	global $DB, $TAGS, $KONSTANTS;

	$axisLabel = getValueFromDB("SELECT Cat".$axis."Label As lbl FROM rallyparams","lbl","***");
	$res = '<p>'.$TAGS['AxisCats'][0].' '.$axis.' '.$axisLabel.'</p>';
	$res .= '<table id="catrows" data-axis="'.$axis.'"><tr><th>'.$TAGS['CategoryLit'][0].'</th><th>'.$TAGS['CatBriefDesc'][0].'</th><th></th></tr>';
	$R = $DB->query("SELECT * from categories WHERE Axis=$axis ORDER BY Cat");
	while ($rd = $R->fetchArray()) {
		$res .= '<tr data-newrow="0"><td style="text-align:center; padding-right:.5em;"><input tabindex="-1" readonly class="smallnumber" type="text" value="'.$rd['Cat'].'"></td>';
		$res .= '<td style="padding-right:.5em;"><input type="text" value="'.$rd['BriefDesc'].'" oninput="lineHasChanged(this);"></td>';
		$res .= '<td><input type="button" disabled data-value="'.$TAGS['SaveRecord'][0].'" value="'.$TAGS['RecordSaved'][0].'" onclick="saveCat(this);"> ';
		$res .= '<input type="checkbox" title="'.$TAGS['tickdelete'][1].'" onclick="flipSave(this);"> <input disabled type="button" data-value="-" value="-" onclick="deleteCat(this);"></td></tr>';
	}
	
	$res .= '</table>';
	
	$res .= '<input type="button" '.($disabled ? ' disabled ' : '').' id="addCatButton" value="+" onclick="addCat();">';
	return $res;
}




function deleteAxisCat() {

	global $DB,$TAGS,$KONSTANTS;

	if (!isset($_REQUEST['a']) || !isset($_REQUEST['ac'])) {
		echo('OMG');
		return;
	}
	$axis = $_REQUEST['a'];
	$cat = $_REQUEST['ac'];
	$sql = "DELETE FROM categories WHERE Axis=$axis AND Cat=$cat";
	echo($DB->exec($sql) && $DB->changes()==1? 'ok' : 'error');
	
}
function saveAxisCat() {

	global $DB,$TAGS,$KONSTANTS;

	if (!isset($_REQUEST['a']) || !isset($_REQUEST['ac']) || !isset($_REQUEST['v'])) {
		echo('OMG');
		return;
	}
	$axis = $_REQUEST['a'];
	$cat = $_REQUEST['ac'];
	$desc = $_REQUEST['v'];
	$sql = "INSERT OR REPLACE INTO categories (Axis,Cat,BriefDesc) VALUES($axis,$cat,'".$DB->escapeString($desc)."')";
	echo($DB->exec($sql) && $DB->changes()==1? 'ok' : 'error');
	
}


function updateAxisName() {
// Update the relevant axis label in rallyparams using ajax call params
	global $DB,$TAGS,$KONSTANTS;

	if (!isset($_REQUEST['a']) || !isset($_REQUEST['v'])) {
		echo('');
		return;
	}
	if (!preg_match('/Cat\dLabel/',$_REQUEST['a'])) {
		echo('OMG');
		return;
	}
	$sql = "UPDATE rallyparams SET ".$_REQUEST['a']."='".$DB->escapeString($_REQUEST['v'])."'";
	echo($DB->exec($sql) && $DB->changes()==1? 'ok' : 'error');

	
}

//error_log($_REQUEST['c']);
if (isset($_REQUEST['c']))
	switch($_REQUEST['c']) {
			
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
			
		case 'catcalcs':
		case 'savecalc':
//			showCompoundCalcs();
			listCompoundCalcs();
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

