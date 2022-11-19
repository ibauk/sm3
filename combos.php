<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

/*
 * I B A U K   -   S C O R E M A S T E R
 *
 * I handle maintenance of combination records
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

function showCombinations()
{
	global $DB, $TAGS, $KONSTANTS, $DBVERSION;
	

    // Only want to show the claims button if there are entrants in the database yet.
	$showclaimsbutton = (getValueFromDB("SELECT count(*) As rex FROM entrants","rex",0)>0);

	$R = $DB->query('SELECT * FROM rallyparams');
	$rd = $R->fetchArray();
	for ($i=1; $i <= $KONSTANTS['NUMBER_OF_COMPOUND_AXES']; $i++)
		$catlabels[$i] = $rd['Cat'.$i.'Label'];

	$R = $DB->query('SELECT * FROM categories ORDER BY Axis,BriefDesc');
	while ($rd = $R->fetchArray())
	{
		if (!isset($cats[$rd['Axis']]))
			$cats[$rd['Axis']][0] = '';
		$cats[$rd['Axis']][$rd['Cat']] = $rd['BriefDesc'];
		
	}


	
	$R = $DB->query('SELECT * FROM combinations ORDER BY ComboID');
	if (!$rd = $R->fetchArray())
		$rd = [];



	echo('<form method="post" action="combos.php">');


	echo('<input type="hidden" name="c" value="list">');
	echo('<input type="hidden" name="menu" value="setup">');
	
	echo('<div class="stickyhead">'.$TAGS['ComboMaintHead'][1]);
	$url = "combos.php?c=edit";
	echo('<br><button value="+" autofocus onclick="window.location.href='."'".$url."'; return false;".'">+</button>');
    echo('</div>');
	
	echo('<table id="bonuses">');
//	echo('<caption title="'.htmlentities($TAGS['ComboMaintHead'][1]).'">'.htmlentities($TAGS['ComboMaintHead'][0]).'</caption>');
	echo('<thead class="listhead"><tr><th class="left">'.$TAGS['ComboIDLit'][0].'</th>');
	echo('<th class="left">'.$TAGS['BriefDescLit'][0].'</th>');
	echo('<th class="left">'.$TAGS['BonusListLit'][0].'</th>');
	echo('<th class="left">'.$TAGS['ValueHdr'][0].'</th>');
	if ($showclaimsbutton)
		echo('<th class="ClaimsCount">'.$TAGS['ShowClaimsCount'][0].'</th>');
	echo('</tr>');
	echo('</thead><tbody>');
	
	
	$sql = 'SELECT * FROM combinations ORDER BY ComboID';
	$R = $DB->query($sql);
	if ($DB->lastErrorCode() <> 0)
		echo($DB->lastErrorMsg().'<br>'.$sql.'<hr>');
	while ($rd = $R->fetchArray())
	{
		echo('<tr class="hoverlite" onclick="window.location=\'combos.php?c=edit&amp;combo='.$rd['ComboID'].'\'">');
		echo('<td><input class="ComboID" type="text" name="ComboID[]" readonly value="'.$rd['ComboID'].'"></td>');
		echo('<td><input readonly class="BriefDesc" type="text" name="BriefDesc[]" value="'.str_replace('"','&quot;',$rd['BriefDesc']).'"></td>');
		echo('<td><input readonly title="'.$TAGS['BonusListLit'][1].'" class="Bonuses" type="text" name="Bonuses[]" value="'.$rd['Bonuses'].'" ></td>');
        if ($rd['ScoreMethod'] == $KONSTANTS['ComboScoreMethodMults'] )
            $m = " x ";
        else
            $m = '';
		echo('<td><input readonly title="'.$TAGS['ValueHdr'][1].'" class="ScorePoints" type="text" name="ScorePoints[]" value="'.$m.$rd['ScorePoints'].'"></td> ');
		if ($showclaimsbutton)
		{
			$rex = getValueFromDB("SELECT count(*) As rex FROM entrants WHERE ',' || CombosTicked || ',' LIKE '%,".$rd['ComboID'].",%'","rex",0);
			echo('<td class="ClaimsCount" title="'.$TAGS['ShowClaimsButton'][1].'">');
			if ($rex > 0)
				echo('<a href='."'entrants.php?c=entrants&mode=combo&bonus=".$rd['ComboID']."'".'> &nbsp;'.$rex.'&nbsp; </a>');
			echo('</td>');
		}
		echo('</tr>');
	}
	echo('</tbody></table>');
	echo('</form>');

	//showFooter();
	
}


function showSingleCombo($comboid)
{
	global $DB, $TAGS, $KONSTANTS, $DBVERSION;

	$R = $DB->query('SELECT * FROM rallyparams');
	$rd = $R->fetchArray();
	$NumLegs = $rd['NumLegs'];	
	$ScoringMethod = $rd['ScoringMethod'];
	if ($ScoringMethod == $KONSTANTS['AutoScoring'])
		$ScoringMethod = chooseScoringMethod();
	$ShowMults = $rd['ShowMultipliers'];
	if ($ShowMults == $KONSTANTS['AutoShowMults'])
		$ShowMults = true || chooseShowMults($ScoringMethod);


	for ($i=1; $i <= $KONSTANTS['NUMBER_OF_COMPOUND_AXES']; $i++)
		$catlabels[$i] = $rd['Cat'.$i.'Label'];
	


	$R = $DB->query('SELECT * FROM categories ORDER BY Axis,BriefDesc');

	while ($rd = $R->fetchArray())
	{
		if (!isset($cats[$rd['Axis']]))
			$cats[$rd['Axis']][0] = '';
		$cats[$rd['Axis']][$rd['Cat']] = $rd['BriefDesc'];
		
	}

	if ($comboid=='')
	{
		$comboid_ro = '';
		$rd = defaultRecord('combinations');
		$afcombo = " autofocus ";
		$afdesc = "";
	}
	else
	{
		$afcombo = "";
		$afdesc = " autofocus ";
		$comboid_ro = ' readonly ';
		$R = $DB->query("SELECT * FROM combinations WHERE ComboID='".$comboid."'");
		if (!($rd = $R->fetchArray()))
			return;
	}
?>
<script>
function comboFormOk() {
	let bid = document.getElementById('comboid');
	if (bid.getAttribute('data-ok')=='0') {
		bid.focus();
		return false;
	}
	bid = document.getElementById('briefdesc');
	if (bid.value.trim()=='') {
		bid.focus();
		return false;
	}
	return true;
}
function checkComboID(str) {
	let xhttp;
	let bid = document.getElementById("comboid");
	bid.setAttribute('data-ok','1');
	let bad = document.getElementById('badbonusid');
	bad.style.display = 'none';
	document.getElementById("briefdesc").value = "";  
	console.log('['+str+']');
	if (str == "")     
		return;
  
	xhttp = new XMLHttpRequest();
	xhttp.onreadystatechange = function() {
		if (this.readyState == 4 && this.status == 200) {
			document.getElementById("briefdesc").value = this.responseText;
			console.log('>>'+this.responseText+'<<');
			if (this.responseText.trim()!="") {
				bad.style.display = 'inline-block';
				bid.setAttribute('data-ok','0');
			} else
				enableSaveButton();
		};
	}
	xhttp.open("GET", "sm.php?c=checkbonus&bid="+str, true);
	xhttp.send();
	
}

// Marks underlying bonus options as eligible/ineligible for use by this combo
// If the underlying bonus is a combo, not alphabetically lower than this one
// it won't be eligible as it'll be out of scope during scoring.
function touchBonusids() {
    let cid = document.getElementById('comboid').value.toUpperCase();
    let bonusArray = document.getElementsByName('BonusID[]');
    console.log('tbi '+bonusArray.length+' cid='+cid);
    for (i=0; i < bonusArray.length; i++) {
        let opts = bonusArray[i].options;
        for (j=0; j < opts.length; j++) {
            opts[j].disabled = opts[j].getAttribute('data-combo') == 1 && opts[j].value >= cid;
        }
    }
    console.log('tbi done');
}

// A line in the table of underlying bonuses has chosen a different bonus
function lineBonusUpdated(sel) {

    let tr = sel.parentNode.parentNode;
    let opt = sel.selectedOptions[0];
    let bd = opt.getAttribute('data-desc');
    console.log(sel.value+' == '+bd);
    let bdtd = tr.cells[1];
    bdtd.innerHTML = bd;
}
function changeTickCount() {

    let nub = document.getElementsByName('BonusID[]');
    let nubl = nub.length;
    let mt = document.getElementById('minimumticks');
    console.log('nub.length='+nubl+'  MT='+mt.value);
    if (mt.value > nubl)
        mt.value = nubl;
    mt.setAttribute('max',nubl);
}
function deleteRow(obj) {

    let tr = obj.parentNode.parentNode.parentNode;
    let tbl = tr.parentNode; // tbody
    console.log(tbl.nodeName);
    tbl.deleteRow(tr.rowIndex);
    changeTickCount();
	enableSaveButton();
    return false;

}

// Enable the delete button a line in the underlying bonus table
function enableKill(obj) {
	let bonuskiller = obj.parentNode.childNodes[2];
	bonuskiller.disabled = !obj.checked;
	return false;
}
function insertRow() {

    let tab = document.getElementById('ubonuses').getElementsByTagName('tbody')[0];
    var oldnewrow = document.getElementsByClassName('newrow')[0];
	var row = tab.insertRow(tab.rows.length);
	row.innerHTML = oldnewrow.innerHTML;
	row.firstChild.firstChild.setAttribute('name','BonusID[]');
    row.classList = '';
	let mt = document.getElementById('minimumticks');
	let max = mt.getAttribute('max');
	max++;
	mt.setAttribute('max',max);
	row.firstChild.focus();
	enableSaveButton();
	return false;

}

// This is called whenever an entry in the ScorePoints array is updated
// It rebuilds the comma separated list in ScorePoints text field
function rebuildValues() {

    let mt = document.getElementById('minimumticks');
    let min = mt.value;
    let max = mt.getAttribute('max');
    let sp = document.getElementById('scorepoints');
    let spa = sp.value.split(',');
    let nsp = spa.length - 1;
    let vtab = document.getElementById('valuetable');
    let trb = vtab.getElementsByTagName('tbody')[0].firstChild;
    let res = [];
    let hipts = -1;

	enableSaveButton();
	
	for (let i = 1; i < trb.cells.length; i++) {
        let cval = trb.cells[i].firstChild.value;
        if (cval >= hipts) {
            res.push(cval);
            hipts = cval;
        } else
            trb.cells[i].firstChild.value = hipts;
    }
    sp.value = res.join();
	let spn = document.getElementById('scorepointsnum');
	spn.value = res[0];
}

// This is called to reconstruct the ScorePoints array when something changes
function refreshValues() {

    let mt = document.getElementById('minimumticks');
    let min = mt.value;
    let max = mt.getAttribute('max');
    let sp = document.getElementById('scorepoints');
    let spa = sp.value.split(',');
    let nsp = spa.length - 1;
    let vtab = document.getElementById('valuetable');

	enableSaveButton();

    if (min == 0 || min == max) {
        vtab.classList.add('hide');
		sp.parentNode.classList.add('vlabel');
        sp.parentNode.classList.remove('hide');

        return;
    }
    let trh = vtab.getElementsByTagName('thead')[0].firstChild;
    let trb = vtab.getElementsByTagName('tbody')[0].firstChild;
	let j = 0;
    for(let i = min ;i <= max; i++) {
        let k = trh.cells.length;
        if (j + 1 >= k) {
            trh.insertCell(j + 1);
            trb.insertCell(j + 1);
        }
        trh.cells[j + 1].innerHTML = i;
        let pts = spa[nsp];
        if (j < nsp)
            pts = spa[j];
        trb.cells[j + 1].innerHTML = '<input type="number" onchange="rebuildValues();" value="'+pts+'">';
        j++;
		console.log("j == "+j);
    }
	j++;
	console.log("j="+j+"  cells.length="+trh.cells.length);

	for(; j < trh.cells.length;) {
		trh.deleteCell(j);
		trb.deleteCell(j);
	}
	console.log(sp.parentNode.tagName);
	sp.parentNode.classList.remove('vlabel');
    sp.parentNode.classList.add('hide');
	console.log(sp.parentNode.classList);
    vtab.classList.remove('hide');
}
function updateScorePoints() {

	let spt = document.getElementById('scorepoints');
	let spn = document.getElementById('scorepointsnum');
	spt.value = spn.value;

}
function setTriggered(obj) {

	obj.setAttribute('data-triggered','1');
}
function editBonusList() {
	let tab = document.getElementById('ubonuses');
	tab.style.display = 'none';
	let ubb = document.getElementById('addubonus');
	ubb.style.display = 'none';
	let bids = document.getElementsByName('BonusID[]');
	for (let i = 0; i < bids.length; i++) {
		bids[i].name = 'noname[]';
	}
	enableSaveButton();
}
function hideBonusList() {
	let bli = document.getElementById('Bonuses');
	bli.style.display = 'none';
}
</script>
<?php	
	echo('<div class="comboedit">');
	echo('<form method="post" action="combos.php" onsubmit="return comboFormOk();">');
	echo('<input type="hidden" name="c" value="save">');
	echo('<input type="hidden" name="menu" value="setup">');
	
	echo('<div class="stickytop"><p>'.$TAGS['ComboMaintHead'][1].'</p>');

	echo('<input type="submit" disabled id="savedata" name="savedata" data-triggered="0" onclick="setTriggered(this);" value="'.$TAGS['UpdateCombo'][0].'"> ');
	
	if ($comboid != '')
	{
		echo('<span title="'.$TAGS['DeleteEntryLit'][1].'"><label for="deletecombo">'.$TAGS['DeleteEntryLit'][0].'</label> ');
		echo('<input type="checkbox" id="deletecombo" name="DeleteCombo" onchange="enableSaveButton();"></span>');
	}

	echo('</div>');


	echo('<span class="vlabel" title="'.$TAGS['ComboIDLit'][1].'"><label class="wide" for="comboid">'.$TAGS['ComboIDLit'][0].'</label> ');
	echo('<input type="text" '.$comboid_ro.$afcombo.' data-ok="'.($rd['ComboID']=='' ? '0' : '1').'" name="comboid" id="comboid" value="'.$rd['ComboID'].'" oninput="checkComboID(this.value);" onblur="touchBonusids();"> ');
	echo('<span id="badbonusid" style="display:none;" class="red" title="'.$TAGS['DuplicateRecord'][1].'">'.$TAGS['DuplicateRecord'][0].'</span>');
	echo('<span id="bonuscheck" style="display:none;"></span>');
	
	echo(' <input type="text" '.$afdesc.' name="BriefDesc" id="briefdesc" value="'.str_replace('"','&quot;',$rd['BriefDesc']).'" oninput="enableSaveButton();"> </span>');
	echo('</span>');
	

    echo("\r\n");
    echo('<div class="stickyhead tablehead">');
	echo('<button id="addubonus" value="+" onclick="hideBonusList();return insertRow();">+</button> ');
    echo($TAGS['BonusListLit'][0]);
	echo(' ');
	echo('<input type="text" id="Bonuses" name="Bonuses" value="'.$rd['Bonuses'].'" oninput="editBonusList();" title="'.$TAGS['BonusListLit'][1].'">');
    echo('</div>');

    $bonuses = getBonusList(true);

    //print_r($bonuses);
    $maxt = 0;
    echo('<table id="ubonuses">');
    $rows = explode(',',$rd['Bonuses']);
    $rows[] = '';
    foreach ($rows as $bid) {
        if ($bid=='') {
            $cls = 'newrow hide';
            $nme = 'newrow';
        } else {
            $cls = '';
            $nme = 'BonusID[]';
        }

        echo('<tr class="'.$cls.'">');
        echo('<td><select name="'.$nme.'" onchange="lineBonusUpdated(this);">');
        if ($bid != '')
            $maxt++;
        foreach ($bonuses as $B => $BD) {
            if ($B == '' && $bid != '')
                continue;
            echo('<option value="'.$B.'" data-desc="'.str_replace('"','&quot;',$BD[1]).'" data-combo="'.$BD[0].'"');
            if ($BD[0]==1 && $B >= $comboid)
                echo(' disabled');
            elseif ($B == $bid) {
                echo(' selected');
            }
            echo('>'.$B.'</option>');
        }
        echo('</select></td>');
            
        $bd = $bonuses[$bid][1];
        echo('<td>'.htmlspecialchars($bd).'</td>');
        echo('<td>');
        echo('<span  title="'.$TAGS['DeleteEntryLit'][0].'">');
        echo('<input type="checkbox" onchange="enableKill(this);"> <button disabled value="-" onclick="hideBonusList();return deleteRow(this);">-</button>');
        echo('</span>');
        echo('</td>');
        echo('</tr>');
    }
    echo('</table>');
    
	echo('<span class="vlabel" title="'.$TAGS['MinimumTicks'][1].'"><label class="wide" for="minimumticks">'.$TAGS['MinimumTicks'][0].'</label> ');
	echo('<input type="number" class="smallnumber" onchange="refreshValues();" name="MinimumTicks" id="minimumticks" value="'.$rd['MinimumTicks'].'" min="0" max="'.$maxt.'"> </span>');

    $nt = $rd['MinimumTicks'];

	if ($nt == 0 || $nt == $maxt) {
		$spclass = 'vlabel';
		$vtclass = 'hide';
	} else {
		$spclass = 'hide';
		$vtclass = 'inherit';
	}
    // values
	echo('<span class="'.$spclass.'" title="'.$TAGS['ScoreValue'][1].'"><label class="wide" for="scorepoints">'.$TAGS['ScoreValue'][0].'</label> ');
	echo('<input type="hidden" name="ScorePoints" id="scorepoints" value="'.$rd['ScorePoints'].'" onchange="refreshValues();">');
	echo('<input type="number" id="scorepointsnum" value="'.intval($rd['ScorePoints']).'" onchange="updateScorePoints();">');
	echo('</span>');

//    echo('<p>--- nt='.$nt.' == '.$maxt.' --</p>');
    
    $sva = explode(',',$rd['ScorePoints']);
    $nsva = count($sva) - 1;


    echo('<table id="valuetable" class="'.$vtclass.'"><thead><tr>');
    echo('<td class="right" >Number of ticked bonuses</td>');
    for ($i = $nt; $i <= $maxt; $i++) {
        echo("<th>$i</th>");
    }
    echo('</tr><tbody><tr>');
    echo('<td class="right">Value of combo</td>');
    for ($j = 0,$i = $nt; $i <= $maxt; $i++) {
        echo("<td>");
		echo('<input type="number" onchange="rebuildValues();" value="');
        if (isset($sva[$j]))
            echo($sva[$j++]);
        else
            echo($sva[$j - 1]);
		echo('">');
        echo("</td>");
    }
    echo('</tr></tbody></table>');

    

	if ($ShowMults) {
		echo('<span class="vlabel" title="'.$TAGS['ComboScoreMethod'][1].'"><label class="wide" for="scoremethod">'.$TAGS['ComboScoreMethod'][0].'</label> ');
		echo('<select name="ScoreMethod" id="scoremethod" onchange="enableSaveButton();">');
		echo('<option value="0" '.($rd['ScoreMethod']<>1 ? 'selected ' : '').'>'.$TAGS['AddPoints'][0].'</option>');
		echo('<option value="1" '.($rd['ScoreMethod']==1 ? 'selected ' : '').'>'.$TAGS['AddMults'][0].'</option>');
		echo('</select></span>');
	} else {
		echo('<br><input type="hidden" name="ScoreMethod" id="scoremethod" value="0">'); // Must be points
	}

	echo('<span class="vlabel" title="'.$TAGS['CompulsoryBonus'][1].'"><label class="wide" for="compulsory">'.$TAGS['CompulsoryBonus'][0].'</label> ');
	echo('<select name="Compulsory" id="compulsory" onchange="enableSaveButton();">');
	echo('<option value="0" '.($rd['Compulsory']<>1 ? 'selected ' : '').'>'.$TAGS['optOptional'][0].'</option>');
	echo('<option value="1" '.($rd['Compulsory']==1 ? 'selected ' : '').'>'.$TAGS['optCompulsory'][0].'</option>');
	echo('</select></span>');

	
	for ($i=1; $i <= $KONSTANTS['NUMBER_OF_COMPOUND_AXES']; $i++)
		if (isset($cats[$i])) {
			echo('<span class="vlabel"><label class="wide" for="Cat'.$i.'Entry">'.$catlabels[$i].'</label> ');
			echo('<select id="Cat'.$i.'Entry" name="Cat'.$i.'Entry" onchange="enableSaveButton();">');
			foreach ($cats[$i] as $ce => $bd) {
				echo('<option value="'.$ce.'" ');
    			if ($ce == $rd['Cat'.$i])
					echo('selected ');
				echo('>'.htmlspecialchars($bd).'</option>');
			}
			echo('</select></span>');
		}

	echo('<span class="vlabel"');
	if ($NumLegs < 2) echo(' style="display:none;"');
	echo(' title="'.$TAGS['BonusLeg'][1].'">');
	echo('<label class="wide" for="Leg">'.$TAGS['BonusLeg'][0].'</label> ');
	echo('<input type="number" class="tinynumber" id="Leg" name="Leg" min="0"');
	echo(' max="'.$NumLegs.'" value="'.$rd['Leg'].'" onchange="enableSaveButton();">');
	echo('</span>');
			
	echo('</form>');
	echo('</div>');
}


function getBonusList($includeEmpty) {

    global $DB, $TAGS;

    $B = [];
    if ($includeEmpty)
        $B[''] = [0,$TAGS['NoSelection'][0]];
    $R = $DB->query("SELECT BonusID, BriefDesc FROM bonuses");
    while ($rd = $R->fetchArray()) {
        $B[$rd['BonusID']] = [0,$rd['BriefDesc']];
    }
    $R = $DB->query("SELECT ComboID, BriefDesc FROM combinations");
    while ($rd = $R->fetchArray()) {
        $B[$rd['ComboID']] = [1,$rd['BriefDesc']];
    }
    ksort($B,2); // Use sort-string:2
    return $B;
}

function deleteSingleCombo() {

	global $DB;

		
	$sql = "DELETE FROM combinations WHERE ComboID='".$DB->escapeString($_REQUEST['comboid'])."'";
	$DB->exec($sql);
	if ($DB->lastErrorCode()<>0) 
		return dberror();


}

function saveSingleCombo() {

	// Array ( [c] => save [menu] => setup [breadcrumbs] =>  / ;#; [savedata] => Update database 
	// [comboid] => C1 [BriefDesc] => first combo [BonusID] => Array ( [0] => 01 [1] => 02 [2] => 03 [3] => 04 [4] => 05 [5] => 06 [6] => 07 ) 
	// [newrow] => [MinimumTicks] => 2 [ScorePoints] => 27,30 [ScoreMethod] => 1 [Compulsory] => 0 [Cat1Entry] => 0 [Cat2Entry] => 0 ) 

	global $DB, $KONSTANTS;

	$comboid = $_REQUEST['comboid'];
	if (isset($_REQUEST['BonusID']))
		$bonuses = implode(',',$_REQUEST['BonusID']);
	else if (isset($_REQUEST['Bonuses']))
		$bonuses = $_REQUEST['Bonuses'];
	else
		$bonuses = '';


	$sql = "INSERT OR REPLACE INTO combinations(ComboID,BriefDesc,ScoreMethod,MinimumTicks,ScorePoints,Bonuses,Compulsory,Leg";
	for ($i=1; $i <= $KONSTANTS['NUMBER_OF_COMPOUND_AXES']; $i++)
		if (isset($_REQUEST['Cat'.$i.'Entry']))
			$sql .= ",Cat".$i;
	$sql .= ") VALUES (";
	$sql .= "'".$DB->escapeString($comboid)."'";
	$sql .= ",'".$DB->escapeString($_REQUEST['BriefDesc'])."'";
	$sql .= ",".$_REQUEST['ScoreMethod'];
	$sql .= ",".$_REQUEST['MinimumTicks'];
	$sql .= ",'".$_REQUEST['ScorePoints']."'";
	$sql .= ",'".$bonuses."'";
	$sql .= ",".$_REQUEST['Compulsory'];
	$sql .= ",".$_REQUEST['Leg'];
	for ($i=1; $i <= $KONSTANTS['NUMBER_OF_COMPOUND_AXES']; $i++)
		if (isset($_REQUEST['Cat'.$i.'Entry']))
			$sql .= ",".$_REQUEST['Cat'.$i.'Entry'];
	$sql .= ")";

	//print_r($_REQUEST);echo('<hr>'.$sql); exit;
	$DB->exec($sql);
	if ($DB->lastErrorCode()<>0) 
		return dberror();


}


//print_r($_REQUEST);

if (isset($_REQUEST['c'])) {
    switch($_REQUEST['c']) {
		case 'save':
			if (isset($_REQUEST['DeleteCombo']))
				deleteSingleCombo();
			else
				saveSingleCombo();
			$get = "combos.php?c=list";
			header("Location: ".$get);
			exit;
		
    }
}
if (isset($_REQUEST['c'])) {
    startHtml($TAGS['ttSetup'][0]);
    switch($_REQUEST['c']) {
		case 'save':
        case 'list':
            showCombinations();
            break;
        case 'edit':
            showSingleCombo( (isset($_REQUEST['combo']) ? $_REQUEST['combo'] : ''));
            break;
    }
}
?>
