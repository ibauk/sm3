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
 * Copyright (c) 2021 Bob Stammers
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

require_once('common.php');


function showPicklist($ord)
{
	global $DB, $TAGS, $KONSTANTS, $HOME_URL, $DBVERSION;
	

	$minEntrant = getValueFromDB("SELECT min(EntrantID) as MaxID FROM entrants","MaxID",1);
	$maxEntrant = getValueFromDB("SELECT max(EntrantID) as MaxID FROM entrants","MaxID",$minEntrant);

	$R = $DB->query('SELECT * FROM entrants ORDER BY '.$ord);
	
	$lnk = '<a href="'.$HOME_URL.'">';
	startHtml($TAGS['ttScoring'][0],$TAGS['oi_Scorecards'][0],true);

	
	eval("\$evs = ".$TAGS['EntrantStatusV'][0]);
?>
<script>

function choosePickedName() {
// Picklist entrant chooser

    let en = document.getElementById('EntrantID').value;
    let pl = document.getElementById('picklistNames');
    pl.value = en;
    if (pl.value !== '')
        enableSaveButton();
    else {
        let sb = document.getElementById('savedata');
        sb.disabled = true;
    }

}

function setEntrantFromList(sel) {

    let ent = document.getElementById('EntrantID');
    ent.value = sel.value;
    enableSaveButton();
    let sb = document.getElementById('savedata');
    sb.click();

}

function setEntrantFromNumber(num) {

let ent = document.getElementById('EntrantID');
ent.value = num;
enableSaveButton();
let sb = document.getElementById('savedata');
sb.click();

}


</script>
<?php	
    echo('<p>'.$TAGS['accessScorecards'][1].'</p>');
    echo('<h4>'.$TAGS['PickAnEntrant'][1].'</h4>');
	echo('<div id="pickentrant">');

	echo('<form id="entrantpick" method="get" action="scorecard.php">');
	echo('<label for="EntrantID">'.$TAGS['EntrantID'][0].'</label> ');
	echo('<input oninput="choosePickedName();" type="number" autofocus id="EntrantID" name="EntrantID" min="'.$minEntrant.'" max="'.$maxEntrant.'"> '); 
	echo('<input type="hidden" name="c" value="score">');
	echo('<label for="NameFilter">'.$TAGS['NameFilter'][0].' </label>');

    echo('<select id="picklistNames" onchange="setEntrantFromList(this);">');
    echo('<option>'.$TAGS['PickAnEntrant'][0].'</option>');
	while ($rd = $R->fetchArray()) {
        echo('<option value="'.$rd['EntrantID'].'" >'.$rd['RiderName'].' [#'.$rd['EntrantID'].']</option>');
    }
    echo('</select>');

	echo(' <input class="button" type="submit" id="savedata" disabled="disabled" value="'.$TAGS['ScoreThis'][0].'" > ');
	echo('</form>');

    $R = $DB->query('SELECT * FROM entrants ORDER BY EntrantID');

    reset($R);

    echo('<div id="picklistdiv">');
    echo('<table><tbody>');
    while ($rd = $R->fetchArray()) {
        echo('<tr onclick="setEntrantFromNumber('.$rd['EntrantID'].');">');
        echo('<td class="EntrantID">'.$rd['EntrantID'].'</td>');
        echo('<td class="RiderName">'.$rd['RiderName']);
        if ($rd['PillionName'] != '') {
            echo(' &amp; '.$rd['PillionName']);
        }
        echo('</td>');
        echo('<td class="EntrantStatus">'.$evs[$rd['EntrantStatus']].'</td>');
        echo('<td class="TotalPoints">'.$rd['TotalPoints'].'</td>');
        echo('</tr>');
    }
    echo('</tbody></table>');
    echo('</div>');


	echo("</div>\r\n");

}
	
showPicklist('RiderName');
?>
