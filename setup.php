<?php

/*
 * I B A U K   -   S C O R E M A S T E R
 *
 * I run wizards to setup new rally scoring systems
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

 
$HOME_URL = "setup.php";
require_once("common.php");

$LAST_WIZARD_PAGE = 5;

function savePage($page_number)
{
	global $DB;
	
	$R = $DB->query("SELECT * FROM rallyparams");
	$rd = $R->fetchArray(SQLITE3_ASSOC);
	$sql = "UPDATE rallyparams SET ";
	$sql_fields = '';
	foreach ($_REQUEST as $rq => $rv)
	{
		if (!array_key_exists($rq,$rd))
			continue;
		if ($sql_fields != '')
			$sql_fields .= ',';
		$sql_fields .= $rq.'=';
		switch($rq)
		{
			case 'RallyTitle':
			case 'RallySlogan':
			case 'Cat1Label':
			case 'Cat2Label':
			case 'Cat3Label':
			case 'RejectReasons':
			case 'refuelstops':
			case 'spbonus':
			case 'fpbonus':
			case 'mpbonus':
			case 'LocalTZ':
			case 'HostCountry':
			case 'Locale':
				$sql_fields .= "'".$DB->escapeString($rv)."'";
				break;
			case 'StartTime':
				if ($_REQUEST['StartDate'] != '' && $_REQUEST['StartTime'] != '')
					$sql_fields .= "'".$DB->escapeString(joinDateTime($_REQUEST['StartDate'],$_REQUEST['StartTime']))."'";
				break;
			case 'FinishTime':
				if ($_REQUEST['FinishDate'] != '' && $_REQUEST['FinishTime'] != '')
					$sql_fields .= "'".$DB->escapeString(joinDateTime($_REQUEST['FinishDate'],$_REQUEST['FinishTime']))."'";
				break;
			default:
				$sql_fields .= $rv;
		}
	}
	//echo($sql.$sql_fields);
	if ($DB->exec($sql.$sql_fields)==FALSE)
	{
		echo("OMG! - ".$DB->lastErrorMsg());
	}

	if (isset($_REQUEST['StartOption'])) {
		updateCohort(0,$_REQUEST['StartOption']);
	}
	
}


function showPage($page_number)
{
	global $TAGS,$DB,$KONSTANTS, $DBVERSION;
	
//	echo('Showing page '.$page_number.'</hr>');
	function isChecked($n)
	{
		return ($n>0 ? ' checked ' : '');
	}
	function isSelected($n)
	{
		return ($n>0 ? ' selected ' : '');
	}
	$page2show = $page_number;
	if ($page_number < 1)
	{
		// Figure out what to do based on contents of database
		$page2show = 1;
	}
	showPageHeader($page2show);
	$R = $DB->query("SELECT * FROM rallyparams");
	if ($rd = $R->fetchArray()) ; // Should complain or do something
	switch($page2show)
	{
		case 1:
			echo('<h2>'.$TAGS['WizTitle'][0].'</h2>');
			echo('<div class="wizitem"><p>'.$TAGS['WizRallyTitle'][1].'</p>');
			echo('<label for="RallyTitle">'.$TAGS['WizRallyTitle'][0].'</label> ');
			echo('<input autofocus type="text" name="RallyTitle" id="RallyTitle" value="'.$rd['RallyTitle'].'" onfocus="this.select();">');
			echo('</div>');
			
			
//			echo('<div class="wizitem"><p>'.$TAGS['WizMilesKms'][1].'</p>');
//			echo('<label for="MilesKms">'.$TAGS['WizMilesKms'][0].'</label> ');
//			echo('<select name="MilesKms" id="MilesKms">');
//			echo('<option value="0" '.($rd['MilesKms']==0 ? ' selected ' : '').'">'.$TAGS['WizIsMiles'][0].'</option>');
//			echo('<option value="1"'.($rd['MilesKms']!=0 ? ' selected ' : '').'>'.$TAGS['WizIsKms'][0].'</option>');
//			echo('</select>');
//			echo('</div>');

			echo('<div class="wizitem"><p>'.$TAGS['WizRegion'][1].'</p>');
			
			// Crude locale customisation
			$regionspecs['United Kingdom'] = ['MilesKms'=>'0','LocalTZ'=>'Europe/London','DecimalComma'=>'0','HostCountry'=>'UK','Locale'=>'en-GB'];
			$regionspecs['Republic of Ireland'] = ['MilesKms'=>'1','LocalTZ'=>'Europe/Dublin','DecimalComma'=>'0','HostCountry'=>'Eire','Locale'=>'en-IE'];
			$regionspecs['Western Europe'] = ['MilesKms'=>'1','LocalTZ'=>'Europe/Berlin','DecimalComma'=>'1','HostCountry'=>'DE','Locale'=>'de-DE'];
			$regionspecs['Eastern Europe'] = ['MilesKms'=>'1','LocalTZ'=>'Europe/Helsinki','DecimalComma'=>'1','HostCountry'=>'Finland','Locale'=>'fi-FI'];
			
			echo('<span class="hide" id="regionspecs">'.json_encode($regionspecs).'</span>');
			$init = array_keys($regionspecs)[0];
			foreach ($regionspecs[$init] as $k => $v)
				echo('<input type="hidden" id="'.$k.'" name="'.$k.'" value="'.$v.'">');
				
?>
<script>
function chgregion() {
	let reg = document.getElementById('WizRegion').value;
	let specs = JSON.parse(document.getElementById('regionspecs').innerHTML);
	for (setting in specs[reg]) 
		document.getElementById(setting).value = specs[reg][setting];
}
</script>
<?php
			echo('<label for="WizRegion">'.$TAGS['WizRegion'][0].'</label> ');
			echo('<select id="WizRegion" onchange="chgregion();">');

			$init = false;
			foreach (array_keys($regionspecs) As $reg => $parms) {
				echo('<option ');
				echo('value="'.$parms.'"');
				if (!$init) {
					echo(' selected');
					$init = true;
				}
				echo('>'.$parms.'</option>');
			}
			echo('</select>');
			echo('</div>');
			
			echo('<div class="wizitem"><p>'.$TAGS['WizRealVirtual'][1].'</p>');
			echo('<label for="isvirtual">'.$TAGS['WizRealVirtual'][0].'</label> ');
			echo('<select name="isvirtual" id="isvirtual">');
			echo('<option value="0" '.($rd['isvirtual']==0 ? ' selected ' : '').'">'.$TAGS['WizIsReal'][0].'</option>');
			echo('<option value="1"'.($rd['isvirtual']!=0 ? ' selected ' : '').'>'.$TAGS['WizIsVirtual'][0].'</option>');
			echo('</select>');
			echo('</div>');
			
			break;
		case 2:
			if ($rd['StartTime']=='') {
				$rd['StartTime'] = date("Y-m-d 09:00");
				$rd['FinishTime'] = date("Y-m-d 17:00");
				$rd['MaxHours'] = 0;
			}
			$dt = splitDatetime($rd['StartTime']); 
			echo('<div class="wizitem"><p>'.$TAGS['StartDate'][1].'</p>');
			echo('<label for="StartDate">'.$TAGS['StartDate'][0].'</label> ');
			echo('<input autofocus type="date" name="StartDate" id="StartDate" value="'.$dt[0].'">');
			echo('</div>');
			echo('<div class="wizitem"><p>'.$TAGS['StartTime'][1].'</p>');
			echo('<label for="StartTime">'.$TAGS['StartTime'][0].'</label> ');
			echo('<input type="time" name="StartTime" id="StartTime" value="'.$dt[1].'">');
			echo('</div>');
			$dt = splitDatetime($rd['FinishTime']); 
			echo('<div class="wizitem"><p>'.$TAGS['FinishDate'][1].'</p>');
			echo('<label for="FinishDate">'.$TAGS['FinishDate'][0].'</label> ');
			echo('<input type="date" name="FinishDate" id="FinishDate" value="'.$dt[0].'">');
			echo('</div>');
			echo('<div class="wizitem"><p>'.$TAGS['FinishTime'][1].'</p>');
			echo('<label for="FinishTime">'.$TAGS['FinishTime'][0].'</label> ');
			echo('<input type="time" name="FinishTime" id="FinishTime" value="'.$dt[1].'">');
			echo('</div>');
			break;
		case 3:
			$date1 = new DateTime($rd['StartTime']);
			$date2 = new DateTime($rd['FinishTime']);
			$diff = $date2->diff($date1);
			$hours = ($diff->h) + ($diff->days*24);
			if ($rd['MaxHours']<1)
				$rd['MaxHours'] = $hours;
			
			echo('<div class="wizitem"><p>'.$TAGS['WizMaxHours'][1].'</p>');
			echo('<label for="MaxHours">'.$TAGS['WizMaxHours'][0].'</label> ');
			echo('<input type="number" autofocus max="'.$hours.'" class="smallnumber" name="MaxHours" id="MaxHours" value="'.$rd['MaxHours'].'" onfocus="this.select();">');
			echo('</div>');
			
			echo('<div class="wizitem"><p>'.$TAGS['MaxMilesUsed'][1].'</p>');
			echo('<label for="MaxMilesUsed">'.$TAGS['MaxMilesUsed'][0].'</label> ');
			$js = "document.getElementById('maxmDiv').className=(document.getElementById('MaxMilesUsed').checked?'':'wizhide');";
			echo('<input type="checkbox" name="MaxMilesUsed" id="MaxMilesUsed" '.isChecked($rd['PenaltyMilesDNF']).' onchange="'.$js.'">');
			$wclss = (isChecked($rd['PenaltyMilesDNF'])!= '' ? '' : 'wizhide');
			echo(' &nbsp;&nbsp;&nbsp;<span id="maxmDiv" class="'.$wclss.'" title="'.$TAGS['PenaltyMilesDNF'][1].'">');
			echo('<label for="PenaltyMilesDNF">'.$TAGS['PenaltyMilesDNF'][0].'</label> ');
			echo('<input type="number" name="PenaltyMilesDNF" id="PenaltyMilesDNF" value="'.$rd['PenaltyMilesDNF'].'">');
			echo('</span>');
			echo('</div>');
			echo('<div class="wizitem"><p>'.$TAGS['MinMilesUsed'][1].'</p>');
			echo('<label for="MinMilesUsed">'.$TAGS['MinMilesUsed'][0].'</label> ');
			$js = "document.getElementById('minmDiv').className=(document.getElementById('MinMilesUsed').checked?'':'wizhide');";
			echo('<input type="checkbox" name="MinMilesUsed" id="MinMilesUsed" '.isChecked($rd['MinMiles']).' onchange="'.$js.'">');
			$wclss = (isChecked($rd['MinMiles'])!= '' ? '' : 'wizhide');
			echo(' &nbsp;&nbsp;&nbsp;<span id="minmDiv" class="'.$wclss.'" title="'.$TAGS['MinMiles'][1].'">');
			echo('<label for="MinMiles">'.$TAGS['MinMiles'][0].'</label> ');
			echo('<input type="number" name="MinMiles" id="MinMiles" value="'.$rd['MinMiles'].'">');
			echo('</span>');
			echo('</div>');

			break;

		case 88:
			

			echo('<div class="wizitem"><p>'.$TAGS['MinPointsUsed'][1].'</p>');
			echo('<label for="MinPointsUsed">'.$TAGS['MinPointsUsed'][0].'</label> ');
			$js = "document.getElementById('minpDiv').className=(document.getElementById('MinPointsUsed').checked?'':'wizhide');";
			echo('<input type="checkbox" name="MinPointsUsed" id="MinPointsUsed" '.isChecked($rd['MinPoints']).' onchange="'.$js.'">');
			$wclss = (isChecked($rd['MinPoints'])!= '' ? '' : 'wizhide');
			echo(' &nbsp;&nbsp;&nbsp;<span id="minpDiv" class="'.$wclss.'" title="'.$TAGS['MinPoints'][1].'">');
			echo('<label for="MinPoints">'.$TAGS['MinPoints'][0].'</label> ');
			echo('<input type="number" name="MinPoints" id="MinPoints" value="'.$rd['MinPoints'].'">');
			echo('</span>');
			echo('</div>');
			break;
		case 4:
		// Virtual rallies only
			if ($rd['isvirtual'] != 0) {
				echo('<h2>'.$TAGS['WizVirtualPage'][0].'</h2>');
				echo('<div class="wizitem"><p>'.$TAGS['WizTankRange'][1].'</p>');
				echo('<label for="tankrange">'.$TAGS['WizTankRange'][0].'</label> ');
				echo('<input autofocus type="number" name="tankrange" id="tankrange" value="'.$rd['tankrange'].'">');
				echo('</div>');
				echo('<div class="wizitem"><p>'.$TAGS['WizStopMins'][1].'</p>');
				echo('<label for="stopmins">'.$TAGS['WizStopMins'][0].'</label> ');
				echo('<input type="number" name="stopmins" id="stopmins" value="'.$rd['stopmins'].'">');
				echo('</div>');
			} else {
				echo('<div class="wizitem"><p>'.$TAGS['OdoCheckUsed'][1].'</p>');
				echo('<label for="OdoCheckUsed">'.$TAGS['OdoCheckUsed'][0].'</label> ');
				$js = "document.getElementById('ocmDiv').className=(document.getElementById('OdoCheckUsed').checked?'':'wizhide');";
				echo('<input autofocus type="checkbox" name="OdoCheckUsed" id="OdoCheckUsed" '.isChecked($rd['OdoCheckMiles']).' onchange="'.$js.'">');
			
				$wclss = (isChecked($rd['OdoCheckMiles'])!= '' ? '' : 'wizhide');
				echo(' &nbsp;&nbsp;&nbsp;<span id="ocmDiv" class="'.$wclss.'" title="'.$TAGS['OdoCheckMiles'][1].'">');
				echo('<label for="OdoCheckMiles">'.$TAGS['OdoCheckMiles'][0].'</label> ');
				echo('<input type="number" name="OdoCheckMiles" id="OdoCheckMiles" value="'.$rd['OdoCheckMiles'].'">');
				echo('</span>');
				echo('</div>');
			}
			
			$sm = $rd['TiedPointsRanking'];
			echo('<div class="wizitem"><p>'.$TAGS['WizTiedPoints'.$sm][1].'</p>');
			echo('<label for="TiedPointsRanking">'.$TAGS['WizTiedPoints'][0].'</label> ');
			echo('<select name="TiedPointsRanking" id="TiedPointsRanking" onchange="this.parentNode.firstChild.innerHTML=this.options[this.selectedIndex].getAttribute(\'data-help\');">');
			echo('<option data-help="'.$TAGS['WizTiedPoints0'][1].'" value="0"'.isSelected($sm==0).'>'.$TAGS['WizTiedPoints0'][0].'</option>');
			echo('<option data-help="'.$TAGS['WizTiedPoints1'][1].'" value="1"'.isSelected($sm==1).'>'.$TAGS['WizTiedPoints1'][0].'</option>');
			echo('</select>');
			echo('</div>');

			$sm = $rd['StartOption'];
			echo('<div class="wizitem"><p>'.$TAGS['WizStartOption'.$sm][1].'</p>');
			echo('<label for="StartOption">'.$TAGS['WizStartOption'][0].'</label> ');
			echo('<select name="StartOption" id="StartOption" onchange="this.parentNode.firstChild.innerHTML=this.options[this.selectedIndex].getAttribute(\'data-help\');">');
			echo('<option data-help="'.$TAGS['WizStartOption0'][1].'" value="0"'.isSelected($sm==0).'>'.$TAGS['WizStartOption0'][0].'</option>');
			echo('<option data-help="'.$TAGS['WizStartOption1'][1].'" value="1"'.isSelected($sm==1).'>'.$TAGS['WizStartOption1'][0].'</option>');
			echo('<option data-help="'.$TAGS['WizStartOption2'][1].'" value="2"'.isSelected($sm==1).'>'.$TAGS['WizStartOption2'][0].'</option>');
			echo('</select>');
			echo('</div>');



			break;

				


			$sm = intval($rd['ScoringMethod']);
			switch($sm) {
				case $KONSTANTS['CompoundScoring']:
					$smt = "C";
					break;
				case $KONSTANTS['SimpleScoring']:
					$smt = "S";
					break;
				case $KONSTANTS['ManualScoring']:
					$smt = "M";
					break;
				default:
					$smt = "A";				
			}
			echo('<div class="wizitem"><p>'.$TAGS['ScoringMethodW'.$smt][1].'</p>');
			echo('<label for="ScoringMethod">'.$TAGS['ScoringMethod'][0].'</label> ');
			echo('<select autofocus name="ScoringMethod" id="ScoringMethod" onchange="this.parentNode.firstChild.innerHTML=this.options[this.selectedIndex].getAttribute(\'data-help\');">');
			echo('<option data-help="'.$TAGS['ScoringMethodWA'][1].'" value="'.$KONSTANTS['AutoScoring'].'"'.isSelected($sm==$KONSTANTS['AutoScoring']).'>'.$TAGS['ScoringMethodWA'][0].'</option>');
			echo('<option data-help="'.$TAGS['ScoringMethodWM'][1].'" value="'.$KONSTANTS['ManualScoring'].'"'.isSelected($sm==$KONSTANTS['ManualScoring']).'>'.$TAGS['ScoringMethodWM'][0].'</option>');
			echo('<option data-help="'.$TAGS['ScoringMethodWS'][1].'" value="'.$KONSTANTS['SimpleScoring'].'"'.isSelected($sm==$KONSTANTS['SimpleScoring']).'>'.$TAGS['ScoringMethodWS'][0].'</option>');
			echo('<option data-help="'.$TAGS['ScoringMethodWC'][1].'" value="'.$KONSTANTS['CompoundScoring'].'"'.isSelected($sm==$KONSTANTS['CompoundScoring']).'>'.$TAGS['ScoringMethodWC'][0].'</option>');
			echo('</select>');
			echo('</div>');
			
			break;
			
		case 5:
			echo('<div><p>'.$TAGS['WizFinishText'][0].'</p>');
			echo('<div><p>'.$TAGS['WizFinishText'][1].'</p>');
			echo('<input type="hidden" name="DBState" value="1">');
			break;

		case 99:
			
		
		
	}
	showPageTrailer($page2show);
}

function showPageHeader($page_number)
{
	global $TAGS;
	
	startHtml($TAGS['ttWelcome'][0],'<a href="about.php" class="techie" title="'.$TAGS['HelpAbout'][1].'">'.$TAGS['HelpAbout'][0].'</a>',false);

?>
<form method="post" action="setup.php">
<input type="hidden" name="frompage" value="<?php echo($page_number);?>">
<div id="setupwiz">
<?php
}

function showPageTrailer($page_number)
{
	global $TAGS, $LAST_WIZARD_PAGE;

	echo('<div style="display:flex; flex-direction: row-reverse;">'); 
	/* This display method means that the desired default button, [Next], can be emitted
	 * first so that most browsers will choose it but displayed on the right with
	 * [Previous] to the left, which is the intuitive order.
	 */
	//echo("Showing page $page_number of ".$LAST_WIZARD_PAGE."<hr>");
	if ($page_number < $LAST_WIZARD_PAGE)
		echo('<input type="submit" class="wizbutton" name="nextpage" title="'.$TAGS['WizNextPage'][1].'" value="'.$TAGS['WizNextPage'][0].'"> ');
	else
		echo('<input type="submit" class="wizbutton" name="endwiz" autofocus title="'.$TAGS['WizFinish'][1].'" value="'.$TAGS['WizFinish'][0].'"> ');
	
	// Show 'back' button second so that it's not the default button
	if ($page_number > 1)
		echo('<input type="submit" class="wizbutton" name="prevpage" title="'.$TAGS['WizPrevPage'][1].'" value="'.$TAGS['WizPrevPage'][0].'"> ');
	echo('</div>');
		
?>
</div>
</form>
<?php
}
//	var_dump($_REQUEST);
	
	if (isset($_REQUEST['nextpage']) || isset($_REQUEST['prevpage']) || isset($_REQUEST['endwiz']))
		savePage($_REQUEST['frompage']);
	if (isset($_REQUEST['endwiz'])) {
		$get = 'admin.php?menu=setup';
		header("Location: $get");
		exit;
	}
	if (isset($_REQUEST['nextpage']))
		$_REQUEST['page'] = $_REQUEST['frompage'] + 1;
	if (isset($_REQUEST['prevpage']))
		$_REQUEST['page'] = $_REQUEST['frompage'] - 1;
	
	if (isset($_REQUEST['page']) && is_numeric($_REQUEST['page']))
		showPage($_REQUEST['page']);
	else
		showPage(0);
?>
