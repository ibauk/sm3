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

 
 if (!isset($_REQUEST['c']))
{
	echo('404');
	exit;
}

 
 
 
 
$HOME_URL = 'admin.php';

require_once('common.php');

// Alphabetic from here on in


function deleteSpecial($bonusid)
{
	global $DB;

	$sql = "DELETE FROM specials WHERE BonusID='".$DB->escapeString(strtoupper($bonusid))."'";
	if (!$DB->exec($sql)) {
		dberror();
		exit;
	}
	if ($DB->lastErrorCode()<>0) 
		return dberror();
	
}


function emitBonusTicks()
{
	global $DB;
	
	$R = $DB->query('SELECT * FROM bonuses ORDER BY BonusID');
	while ($rd = $R->fetchArray())
	{
		echo("<label for=\"B".$rd['BonusID']."\">".$rd['BonusID']." </label>");
		echo("<input type=\"checkbox\" name=\"BonusID[]\" id=\"B".$rd['BonusID']."\" value=\"".$rd['BonusID']."\">");
		echo("<input type=\"text\" name=\"BriefDesc[]\" value=\"".$rd['BriefDesc']."\">");
		echo("<br>");
	}
}



function saveCombinations()
{
	global $DB, $TAGS, $KONSTANTS, $DBVERSION;

	//var_dump($_REQUEST); echo('<br>');
	$arr = $_REQUEST['ComboID'];
	if (!$DB->exec('BEGIN IMMEDIATE TRANSACTION')) {
		dberror();
		exit;
	}
	if ($DB->lastErrorCode()<>0) 
		return dberror();			
	
	for ($i=0; $i < count($arr); $i++)
	{
		// Let's make sure the bonus list is good
		$bl = str_replace(' ',',',$_REQUEST['Bonuses'][$i]); // we want commas as separators not spaces
		$bls = explode(',',$bl);
		// On second thoughts, let's not bothering validating them here.
		$sql = "INSERT OR REPLACE INTO combinations (ComboID,BriefDesc,ScoreMethod,ScorePoints,Bonuses";
		if ($DBVERSION >= 3)
		{
			$sql .= ",MinimumTicks";
			for ($ai=1; $ai <= $KONSTANTS['NUMBER_OF_COMPOUND_AXES']; $ai++)
				if (isset($_REQUEST['Cat'.$ai.'Entry']))
					$sql .= ",Cat".$ai;
		}
		$sql .=") VALUES(";
		$sql .= "'".$DB->escapeString(strtoupper($_REQUEST['ComboID'][$i]))."'";
		$sql .= ",'".$DB->escapeString($_REQUEST['BriefDesc'][$i])."'";
		$sql .= ','.intval($_REQUEST['ScoreMethod'][$i]);
		$sql .= ",'".$_REQUEST['ScorePoints'][$i]."'";
		$sql .= ",'".$DB->escapeString(strtoupper($bl))."'";
		if ($DBVERSION >= 3)
		{
			$sql .=','.intval($_REQUEST['MinimumTicks'][$i]);
			for ($ai=1; $ai <= $KONSTANTS['NUMBER_OF_COMPOUND_AXES']; $ai++)
				if (isset($_REQUEST['Cat'.$ai.'Entry'][$i]))
					$sql .= ",".intval($_REQUEST["Cat".$ai."Entry"][$i]);
		}
		$sql .= ")";
		if ($_REQUEST['ComboID'][$i]<>'')
		{
			//echo($sql.'<br>');			
			$DB->exec($sql);
			if ($DB->lastErrorCode()<>0) 
				return dberror();			
		}
	}
	if (isset($_REQUEST['DeleteEntry']))
	{
		$arr = $_REQUEST['DeleteEntry'];
		for ($i=0; $i < count($arr); $i++)
		{
			$sql = "DELETE FROM combinations WHERE ComboID='".$DB->escapeString(strtoupper($_REQUEST['DeleteEntry'][$i]))."'";
			$DB->exec($sql);
			if ($DB->lastErrorCode()<>0) 
				return dberror();			
		}
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






function saveSGroups()
{
	global $DB, $TAGS, $KONSTANTS;

	//var_dump($_REQUEST);
	$arr = $_REQUEST['GroupName'];
	if (!$DB->exec('BEGIN IMMEDIATE TRANSACTION')) {
		dberror();
		exit;
	}
	for ($i=0; $i < count($arr); $i++)
	{
		if (isset($_REQUEST['GroupName'][$i]) && isset($_REQUEST['GroupType'][$i]))
		{
			$sql = "INSERT OR REPLACE INTO sgroups (GroupName,GroupType) VALUES(";
			$sql .= "'".$DB->escapeString($_REQUEST['GroupName'][$i])."'";
			$sql .= ",'".$DB->escapeString($_REQUEST['GroupType'][$i])."'";
			$sql .= ")";
			if ($_REQUEST['GroupName'][$i]<>'')
			{
				$DB->exec($sql);
				if ($DB->lastErrorCode()<>0) 
					return dberror();			
			}
		}
	}
	if (isset($_REQUEST['DeleteEntry']))
	{
		$arr = $_REQUEST['DeleteEntry'];
		for ($i=0; $i < count($arr); $i++)
		{
			$sql = "DELETE FROM sgroups WHERE GroupName='".$DB->escapeString($_REQUEST['DeleteEntry'][$i])."'";
			$DB->exec($sql);
			if ($DB->lastErrorCode()<>0) 
				return dberror();			
		}
	}
	$DB->exec('COMMIT TRANSACTION');
	if (isset($_REQUEST['menu'])) 
	{
		$_REQUEST['c'] = $_REQUEST['menu'];
		include("admin.php");
		exit;
	}
	
}

function saveEmailConfig()
{
	$email = [];

	if (isset($_REQUEST['EmailParams']))
		return $_REQUEST['EmailParams'];	// Accept as is!
	
	foreach ($_REQUEST as $key => $val) {
		$keyparts = explode(':',$key);
		if (!isset($keyparts[0]) || $keyparts[0] != 'email')
			continue;
		if (is_array($val)) {
			$i = 0;
			foreach($val as $thisval) 
				$email[$keyparts[1]][$i++] = $thisval;
		} else
			$email[$keyparts[1]] = $val;
	}
	return json_encode($email);

}


function saveRallyConfig()
{
	global $DB, $KONSTANTS, $DBVERSION;

	$RejectReasons = "";
	$k = count($_REQUEST['RejectReason']);
	for ($i =  0; $i < $k; $i++) {
		$ix = $i + 1;
		$v = $_REQUEST['RejectReason'][$i];
		$RejectReasons .= "$ix=$v\n";
	}

	$sql = "UPDATE rallyparams SET ";
	$sql .= "RallyTitle='".$DB->escapeString($_REQUEST['RallyTitle'])."'";
	$sql .= ",RallySlogan='".$DB->escapeString($_REQUEST['RallySlogan'])."'";
	$sql .= ",MaxHours=".intval($_REQUEST['MaxHours']);
	$sql .= ",StartOption=".intval($_REQUEST['StartOption']);
	$sql .= ",StartTime='".$DB->escapeString($_REQUEST['StartDate']).'T'.$DB->escapeString($_REQUEST['StartTime'])."'";
	$sql .= ",FinishTime='".$DB->escapeString($_REQUEST['FinishDate']).'T'.$DB->escapeString($_REQUEST['FinishTime'])."'";
	$sql .= ",OdoCheckMiles=".floatval($_REQUEST['OdoCheckMiles']);
	$sql .= ",MinMiles=".intval($_REQUEST['MinMiles']);
	$sql .= ",MinPoints=".intval($_REQUEST['MinPoints']);
	$sql .= ",PenaltyMaxMiles=".intval($_REQUEST['PenaltyMaxMiles']);
	$sql .= ",MaxMilesMethod=".intval($_REQUEST['MaxMilesMethod']);
	$sql .= ",MaxMilesPoints=".intval($_REQUEST['MaxMilesPoints']);
	$sql .= ",PenaltyMilesDNF=".intval($_REQUEST['PenaltyMilesDNF']);
	$sql .= ",ScoringMethod=".intval($_REQUEST['ScoringMethod']);
	$sql .= ",ShowMultipliers=".intval($_REQUEST['ShowMultipliers']);
	$sql .= ",TiedPointsRanking=0".(isset($_REQUEST['TiedPointsRanking']) ? intval($_REQUEST['TiedPointsRanking']) : 0);
	$sql .= ",TeamRanking=".intval($_REQUEST['TeamRanking']);
	$sql .= ",AutoRank=".(isset($_REQUEST['AutoRank']) ? intval($_REQUEST['AutoRank']) : 0);
	$bdu = (isset($_REQUEST['MilesKms']) ? intval($_REQUEST['MilesKms']) : $KONSTANTS['BasicDistanceUnit']);
	$sql .= ",MilesKms=".$bdu;
	$KONSTANTS['BasicDistanceUnit'] = $bdu;
	$ltz = (isset($_REQUEST['LocalTZ']) ? $_REQUEST['LocalTZ'] : $KONSTANTS['LocalTZ']);
	$sql .= ",LocalTZ='".$DB->escapeString($ltz)."'";
	$KONSTANTS['LocalTZ'] = $ltz;
	$dc = (isset($_REQUEST['DecimalComma']) ? intval($_REQUEST['DecimalComma']) : $KONSTANTS['DecimalPointIsComma']);
	$sql .= ",DecimalComma=".$dc;
	$KONSTANTS['DecimalPointIsComma'] = $dc;
	$hc = (isset($_REQUEST['HostCountry']) ? $_REQUEST['HostCountry'] : $KONSTANTS['DefaultCountry']);
	$sql .= ",HostCountry='".$DB->escapeString($hc)."'";
	$KONSTANTS['DefaultCountry'] = $hc;
	$hc = (isset($_REQUEST['Locale']) ? $_REQUEST['Locale'] : $KONSTANTS['DefaultLocale']);
	$sql .= ",Locale='".$DB->escapeString($hc)."'";
	$KONSTANTS['DefaultLocale'] = $hc;

	$sql .= ",EmailParams='".$DB->escapeString(saveEmailConfig())."'";
	if (isset($_REQUEST['settings']))
		$sql .= ",settings='".$DB->escapeString($_REQUEST['settings'])."'";
	if (isset($_REQUEST['ebcsettings']))
		$sql .= ",ebcsettings='".$DB->escapeString($_REQUEST['ebcsettings'])."'";

	$sql .= ",RejectReasons='".$DB->escapeString($RejectReasons)."'";
	//echo($sql.'<hr>');
	if (!$DB->exec($sql)) {
		dberror();
		exit;
	}
	if ($DB->lastErrorCode()<>0) 
		return dberror();			
	//echo("Rally configuration saved ".$DB->lastErrorCode().' ['.$DB->lastErrorMsg().']<hr>');
	//exit;
	//show_regular_admin_screen();
	
	$sql = "UPDATE rallyparams SET isvirtual=".intval($_REQUEST['isvirtual']);
	$sql .= ", tankrange=".intval($_REQUEST['tankrange']);
	$sql .= ", stopmins=".intval($_REQUEST['stopmins']);
	$sql .= ", refuelstops='".$DB->escapeString($_REQUEST['refuelstops'])."'";
	if (!$DB->exec($sql)) {
		dberror();
		exit;
	}
	if ($DB->lastErrorCode()<>0) 
		return dberror();			
	
	if (isset($_REQUEST['StartOption']))
		updateCohort(0,$_REQUEST['StartOption']);
		
	error_log('not retrace');
	if (isset($_REQUEST['menu'])) 
	{
		$_REQUEST['c'] = $_REQUEST['menu'];
		include("admin.php");
		exit;
	}
	error_log('not menu');
	
}

function saveSingleCombo()
{
	global $DB, $TAGS, $KONSTANTS, $DBVERSION;

	if (isset($_REQUEST['DeleteCombo']))
	{
		if (!isset($_REQUEST['comboid']) || $_REQUEST['comboid'] == '')
			return;
		$sql = "DELETE FROM combinations WHERE ComboID='".$DB->escapeString(strtoupper($_REQUEST['comboid']))."'";
		if (!$DB->exec($sql)) {
			dberror();
			exit;
		}
		if ($DB->lastErrorCode()<>0) 
			return dberror();			
		return;
	}


	
	$sql = "INSERT OR REPLACE INTO combinations (";
	$sql .= "ComboID,BriefDesc,Compulsory,ScoreMethod,Bonuses,MinimumTicks,ScorePoints";
	if ($DBVERSION >= 3)
		for ($i=1; $i <= $KONSTANTS['NUMBER_OF_COMPOUND_AXES']; $i++)
			$sql .= ',Cat'.$i;
	$sql .= ") VALUES (";
	$sql .= "'".$DB->escapeString(strtoupper($_REQUEST['comboid']))."'";
	$sql .= ",'".$DB->escapeString($_REQUEST['BriefDesc'])."'";
	$sql .= ",'".$DB->escapeString(isset($_REQUEST['Compulsory']) ? $_REQUEST['Compulsory'] : '')."'";
	$sql .= ','.intval($_REQUEST['ScoreMethod']);
	$sql .= ",'".$DB->escapeString(strtoupper($_REQUEST['Bonuses']))."'";
	$sql .= ','.intval($_REQUEST['MinimumTicks']);
	$sql .= ",'".$DB->escapeString($_REQUEST['ScorePoints'])."'";
	if ($DBVERSION >= 3)
		for ($i=1; $i <= $KONSTANTS['NUMBER_OF_COMPOUND_AXES']; $i++)
			$sql .= ','.(isset($_REQUEST['Cat'.$i.'Entry']) ? intval($_REQUEST['Cat'.$i.'Entry']) : 0);
	$sql .= ")";
	if ($_REQUEST['comboid']<>'')
	{
		//echo($sql.'<br>');			
		if (!$DB->exec($sql)) {
			dberror();
			exit;
		}
		if ($DB->lastErrorCode()<>0) 
			return dberror();			
	}
	
}


function saveSpecial()
{
	global $DB, $TAGS, $KONSTANTS;

	$R = $DB->query("SELECT BonusID FROM specials WHERE BonusID='".$DB->escapeString(strtoupper($_REQUEST['BonusID']))."'");
	$newrec = $R->fetchArray()==FALSE;
	
	if ($newrec)
	{
		$sql = "INSERT INTO specials (BonusID,BriefDesc,GroupName,Points,AskPoints,MultFactor";
		$sql .= ",Compulsory,RestMinutes,AskMinutes) VALUES(";
		$sql .= "'".$DB->escapeString(strtoupper($_REQUEST['BonusID']))."'";
		$sql .= ",'".$DB->escapeString($_REQUEST['BriefDesc'])."'";
		$sql .= ",'".$DB->escapeString($_REQUEST['GroupName'])."'";
		$sql .= ",".intval($_REQUEST['Points']);
		$sql .= ",".intval($_REQUEST['AskPoints']);
		$sql .= ",".intval($_REQUEST['MultFactor']);
		$sql .= ",".intval($_REQUEST['Compulsory']);
		$sql .= ",".intval($_REQUEST['RestMinutes']);
		$sql .= ",".intval($_REQUEST['AskMinutes']);
		$sql .= ")";
	}
	else
	{
		$sql = "UPDATE specials SET BriefDesc='".$DB->escapeString($_REQUEST['BriefDesc'])."'";
		$sql .= ",GroupName='".$DB->escapeString($_REQUEST['GroupName'])."'";
		$sql .= ",Points=".intval($_REQUEST['Points']);
		$sql .= ",AskPoints=".intval($_REQUEST['AskPoints']);
		$sql .= ",MultFactor=".intval($_REQUEST['MultFactor']);
		$sql .= ",Compulsory=".intval($_REQUEST['Compulsory']);
		$sql .= ",RestMinutes=".intval($_REQUEST['RestMinutes']);
		$sql .= ",AskMinutes=".intval($_REQUEST['AskMinutes']);
		$sql .= " WHERE BonusID='".$DB->escapeString(strtoupper($_REQUEST['BonusID']))."'";
	}
	$DB->exec($sql);
	if ($DB->lastErrorCode()<>0) 
		return dberror();			
}


function saveSpecials()
{
	global $DB, $TAGS, $KONSTANTS;

	//var_dump($_REQUEST);
	$arr = $_REQUEST['BonusID'];
	if (!$DB->exec('BEGIN IMMEDIATE TRANSACTION')) {
		dberror();
		exit;
	}
	for ($i=0; $i < count($arr); $i++)
	{
		$sql = "INSERT OR REPLACE INTO specials (BonusID,BriefDesc,GroupName,Points,MultFactor) VALUES(";
		$sql .= "'".$DB->escapeString(strtoupper($_REQUEST['BonusID'][$i]))."'";
		$sql .= ",'".$DB->escapeString($_REQUEST['BriefDesc'][$i])."'";
		$sql .= ",'".$DB->escapeString(isset($_REQUEST['GroupName'][$i]) ? $_REQUEST['GroupName'][$i] : '')."'";
		$sql .= ','.intval($_REQUEST['Points'][$i]);
		$sql .= ','.intval($_REQUEST['MultFactor'][$i]);
		$sql .= ")";
		if ($_REQUEST['BonusID'][$i]<>'')
		{
			//echo($sql.'<br>');			
			$DB->exec($sql);
			if ($DB->lastErrorCode()<>0) 
				return dberror();			
		}
	}
	if (isset($_REQUEST['Compulsory']))
	{
		$arr = $_REQUEST['Compulsory'];
		for ($i = 0 ; $i < count($arr) ; $i++ )
		{
			$sql = "UPDATE specials SET Compulsory=1 WHERE BonusID='".$DB->escapeString($_REQUEST['Compulsory'][$i])."'";
			$DB->exec($sql);
			if ($DB->lastErrorCode()<>0) 
				return dberror();			
		}
	}
	if (isset($_REQUEST['AskPoints']))
	{
		$arr = $_REQUEST['AskPoints'];
		for ($i = 0 ; $i < count($arr) ; $i++ )
		{
			$sql = "UPDATE specials SET AskPoints=1 WHERE BonusID='".$DB->escapeString($_REQUEST['AskPoints'][$i])."'";
			$DB->exec($sql);
			if ($DB->lastErrorCode()<>0) 
				return dberror();			
		}
	}
	if (isset($_REQUEST['DeleteEntry']))
	{
		$arr = $_REQUEST['DeleteEntry'];
		for ($i=0; $i < count($arr); $i++)
		{
			$sql = "DELETE FROM specials WHERE BonusID='".$DB->escapeString(strtoupper($_REQUEST['DeleteEntry'][$i]))."'";
			$DB->exec($sql);
			if ($DB->lastErrorCode()<>0) 
				return dberror();			
		}
	}
	$DB->exec('COMMIT TRANSACTION');
	if (isset($_REQUEST['menu'])) 
	{
		$_REQUEST['c'] = $_REQUEST['menu'];
		include("admin.php");
		exit;
	}

	
}







function showCombinations()
{
	global $DB, $TAGS, $KONSTANTS, $DBVERSION;
	

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
	if ($DBVERSION < 3)
		$rd['MinimumTicks'] = 0;

?>
<script>
function triggerNewRow(obj)
{
	var oldnewrow = document.getElementsByClassName('newrow')[0];
	tab = document.getElementById('bonuses').getElementsByTagName('tbody')[0];
	var row = tab.insertRow(tab.rows.length);
	row.innerHTML = oldnewrow.innerHTML;
	obj.onchange = '';
}
</script>
<?php	


	$myurl = "<a href='sm.php?c=combos'>".$TAGS['ComboMaintHead'][0].'</a>';
	pushBreadcrumb($myurl);


	echo('<form method="post" action="sm.php">');

	echo('<input type="hidden" name="c" value="combos">');
	echo('<input type="hidden" name="menu" value="setup">');
	
	echo('<p>'.$TAGS['ComboMaintHead'][1].'</p>');
	
	echo('<table id="bonuses">');
//	echo('<caption title="'.htmlentities($TAGS['ComboMaintHead'][1]).'">'.htmlentities($TAGS['ComboMaintHead'][0]).'</caption>');
	echo('<theadclass="listhead"><tr><th>'.$TAGS['ComboIDLit'][0].'</th>');
	echo('<th>'.$TAGS['BriefDescLit'][0].'</th>');
	if (false) {
	echo('<th>'.$TAGS['ScoreMethodLit'][0].'</th>');
	}
	echo('<th>'.$TAGS['BonusListLit'][0].'</th>');
	if (false) {
	echo('<th>'.$TAGS['MinimumTicks'][0].'</th>');
	}
	echo('<th>'.$TAGS['ValueHdr'][0].'</th>');
	if (false) {
	if ($DBVERSION >= 3)
		for ($i=1; $i <= $KONSTANTS['NUMBER_OF_COMPOUND_AXES']; $i++)
			if (isset($cats[$i]))
				echo('<th>'.$catlabels[$i].'</th>');		
	echo('<th>'.$TAGS['DeleteEntryLit'][0].'</th>');
	}
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
		if ($DBVERSION < 3)
			$rd['MinimumTicks'] = 0;
		echo('<tr class="hoverlite" onclick="window.location=\'sm.php?c=combo&amp;comboid='.$rd['ComboID'].'\'">');
		echo('<td><input class="ComboID" type="text" name="ComboID[]" readonly value="'.$rd['ComboID'].'"></td>');
		echo('<td><input readonly class="BriefDesc" type="text" name="BriefDesc[]" value="'.$rd['BriefDesc'].'"></td>');
		if (false) {
		echo('<td><select disabled name="ScoreMethod[]">');
		echo('<option value="0" '.($rd['ScoreMethod']<>1 ? 'selected="selected" ' : '').'>'.$TAGS['AddPoints'][0].'</option>');
		echo('<option value="1" '.($rd['ScoreMethod']==1 ? 'selected="selected" ' : '').'>'.$TAGS['AddMults'][0].'</option>');
		echo('</select></td>');
		}
		echo('<td><input readonly title="'.$TAGS['BonusListLit'][1].'" class="Bonuses" type="text" name="Bonuses[]" value="'.$rd['Bonuses'].'" ></td>');
		if (false) {
		echo('<td><input readonly title="'.$TAGS['MinimumTicks'][1].'" type="number" name="MinimumTicks[]" value="'.$rd['MinimumTicks'].'"></td>');
		}
		echo('<td><input readonly title="'.$TAGS['ValueHdr'][1].'" class="ScorePoints" type="text" name="ScorePoints[]" value="'.$rd['ScorePoints'].'"></td> ');
		if (false) {
		if ($DBVERSION >= 3)
			for ($i=1; $i <= $KONSTANTS['NUMBER_OF_COMPOUND_AXES']; $i++)
				if (isset($cats[$i]))
				{
					echo('<td><select name="Cat'.$i.'Entry[]">');
					foreach ($cats[$i] as $ce => $bd)
					{
						echo('<option value="'.$ce.'" ');
						if ($ce == $rd['Cat'.$i])
							echo('selected ');
						echo('>'.htmlspecialchars($bd).'</option>');
					}
					echo('</select></td>');
				}
		echo('<td class="center"><input type="checkbox" name="DeleteEntry[]" value="'.$rd['ComboID'].'">');
		}
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
	if (false) {
	echo('<tr class="newrow"><td><input class="ComboID" type="text" name="ComboID[]" onchange="triggerNewRow(this)"></td>');
	echo('<td><input type="text" name="BriefDesc[]"></td>');
	echo('<td><select name="ScoreMethod[]">');
	echo('<option value="0" selected="selected" >'.$TAGS['AddPoints'][0].'</option>');
	echo('<option value="1" >'.$TAGS['AddMults'][0].'</option>');
	echo('</select></td>');
	echo('<td><input title="'.$TAGS['BonusListLit'][1].'" class="Bonuses" type="text" name="Bonuses[]" placeholder="'.$TAGS['CommaSeparated'][0].'"></td>');
	echo('<td><input title="'.$TAGS['MinimumTicks'][1].'" type="number" name="MinimumTicks[]" ></td>');
	echo('<td><input title="'.$TAGS['ValueHdr'][1].'" class="ScorePoints" type="text" name="ScorePoints[]" ></td> ');
	for ($i=1; $i <= $KONSTANTS['NUMBER_OF_COMPOUND_AXES']; $i++)
		if (isset($cats[$i]))
		{
			$S = ' selected ';
			echo('<td><select name="Cat'.$i.'Entry[]">');
			foreach ($cats[$i] as $ce => $bd)
			{
				echo('<option value="'.$ce.'" ');
				echo($S);
				$S = '';
				echo('>'.htmlspecialchars($bd).'</option>');
			}
			echo('</select></td>');
		}
	echo('</tr>');
	}
	echo('</tbody></table>');
	if (false) {
	echo('<input type="submit" name="savedata" value="'.$TAGS['UpdateBonuses'][0].'"> ');
	}
	echo('</form>');
	$url = "sm.php?c=combo&amp;comboid=";
	echo('<button value="+" onclick="window.location='."'".$url."'".'">+</button><br>');

	//showFooter();
	
}

function showSingleCombo($comboid)
{
	global $DB, $TAGS, $KONSTANTS, $DBVERSION;

	$R = $DB->query('SELECT * FROM rallyparams');
	$rd = $R->fetchArray();
	$ScoringMethod = $rd['ScoringMethod'];
	if ($ScoringMethod == $KONSTANTS['AutoScoring'])
		$ScoringMethod = chooseScoringMethod();
	$ShowMults = $rd['ShowMultipliers'];
	if ($ShowMults == $KONSTANTS['AutoShowMults'])
		$ShowMults = chooseShowMults($ScoringMethod);


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
	}
	else
	{
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
function checkComponentList() {
	console.log('ccl');
	let bid = document.getElementById('comboid');
	let bcl = document.getElementById('bonuses');
	bcl.classList.remove('yellow');
	bcl.classList.remove('red');
	let bcs = bcl.value.split(',');
	console.log('bcslength='+bcs.length);
	let ok = document.getElementById('bonuscheck');
	for (let i = 0; i < bcs.length; i++) {
		if (bcs[i]=='')
			continue;
		if (bcs[i]==bid.value) {
			bcl.classList.add('red');
			return;
		}
		checkBonusOk(bcs[i]);
		console.log('i='+i+' {'+ok.innerHTML.trim()+'}');
		if (ok.innerHTML.trim()=='')
			bcl.classList.add('yellow');
	}
}
function checkBonus(str) {
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
			}
		};
	}
	xhttp.open("GET", "sm.php?c=checkbonus&bid="+str, true);
	xhttp.send();
	
}
function checkBonusOk(str) {
	let xhttp;
	let ok = document.getElementById('bonuscheck');
	ok.innerHTML = '';
	console.log('['+str+']');
	if (str == "")     
		return;
  
	xhttp = new XMLHttpRequest();
	xhttp.onreadystatechange = function() {
		if (this.readyState == 4 && this.status == 200) {
			ok.innerHTML = this.responseText;
			console.log(ok);
		};
	}
	xhttp.open("GET", "sm.php?c=checkbonus&bid="+str, false);
	xhttp.send();
	
	
}
</script>
<?php	
	echo('<div class="comboedit">');
	echo('<form method="post" action="sm.php" onsubmit="return comboFormOk();">');
	echo('<input type="hidden" name="c" value="combo">');
	echo('<input type="hidden" name="menu" value="setup">');
	
	echo('<p>'.$TAGS['ComboMaintHead'][1].'</p>');

	echo('<input type="submit" name="savedata" value="'.$TAGS['UpdateCombo'][0].'"> ');
	if ($comboid != '')
	{
		echo('<span title="'.$TAGS['DeleteEntryLit'][1].'"><label for="deletecombo">'.$TAGS['DeleteEntryLit'][0].'</label> ');
		echo('<input type="checkbox" id="deletecombo" name="DeleteCombo"></span>');
	}
	echo('<span class="vlabel" title="'.$TAGS['ComboIDLit'][1].'"><label class="wide" for="comboid">'.$TAGS['ComboIDLit'][0].'</label> ');
	echo('<input type="text" '.$comboid_ro.' data-ok="'.($rd['ComboID']=='' ? '0' : '1').'" name="comboid" id="comboid" value="'.$rd['ComboID'].'" oninput="checkBonus(this.value);"> ');
	echo('<span id="badbonusid" style="display:none;" class="red" title="'.$TAGS['DuplicateRecord'][1].'">'.$TAGS['DuplicateRecord'][0].'</span>');
	echo('<span id="bonuscheck" style="display:none;"></span>');
	echo('</span>');
	
	echo('<span class="vlabel" title="'.$TAGS['BriefDescLit'][1].'"><label class="wide" for="briefdesc">'.$TAGS['BriefDescLit'][0].'</label> ');
	echo('<input type="text" name="BriefDesc" id="briefdesc" value="'.$rd['BriefDesc'].'"> </span>');
	
	echo('<span class="vlabel" title="'.$TAGS['CompulsoryBonus'][1].'"><label class="wide" for="compulsory">'.$TAGS['CompulsoryBonus'][0].'</label> ');
	echo('<select name="Compulsory" id="compulsory">');
	echo('<option value="0" '.($rd['Compulsory']<>1 ? 'selected ' : '').'>'.$TAGS['optOptional'][0].'</option>');
	echo('<option value="1" '.($rd['Compulsory']==1 ? 'selected ' : '').'>'.$TAGS['optCompulsory'][0].'</option>');
	echo('</select></span>');

	if ($ShowMults) {
		echo('<span class="vlabel" title="'.$TAGS['ComboScoreMethod'][1].'"><label class="wide" for="scoremethod">'.$TAGS['ComboScoreMethod'][0].'</label> ');
		echo('<select name="ScoreMethod" id="scoremethod">');
		echo('<option value="0" '.($rd['ScoreMethod']<>1 ? 'selected ' : '').'>'.$TAGS['AddPoints'][0].'</option>');
		echo('<option value="1" '.($rd['ScoreMethod']==1 ? 'selected ' : '').'>'.$TAGS['AddMults'][0].'</option>');
		echo('</select></span>');
	} else {
		echo('<br><input type="hidden" name="ScoreMethod" id="scoremethod" value="0">'); // Must be points
	}

	echo('<span class="vlabel" title="'.$TAGS['BonusListLit'][1].'"><label class="wide" for="bonuses">'.$TAGS['BonusListLit'][0].'</label> ');
	echo('<input type="text" name="Bonuses" id="bonuses" class="ComboBonusList" value="'.$rd['Bonuses'].'" oninput="checkComponentList();"> </span>');
	echo('<span class="vlabel" title="'.$TAGS['MinimumTicks'][1].'"><label class="wide" for="minimumticks">'.$TAGS['MinimumTicks'][0].'</label> ');
	echo('<input type="number" class="smallnumber" name="MinimumTicks" id="minimumticks" value="'.$rd['MinimumTicks'].'"> </span>');
	echo('<span class="vlabel" title="'.$TAGS['ScoreValue'][1].'"><label class="wide" for="scorepoints">'.$TAGS['ScoreValue'][0].'</label> ');
	echo('<input type="text" name="ScorePoints" id="scorepoints" value="'.$rd['ScorePoints'].'"> </span>');
	
	if ($DBVERSION >= 3)
		for ($i=1; $i <= $KONSTANTS['NUMBER_OF_COMPOUND_AXES']; $i++)
			if (isset($cats[$i]))
			{
				echo('<span class="vlabel"><label class="wide" for="Cat'.$i.'Entry">'.$catlabels[$i].'</label> ');
				echo('<select id="Cat'.$i.'Entry" name="Cat'.$i.'Entry">');
				foreach ($cats[$i] as $ce => $bd)
				{
					echo('<option value="'.$ce.'" ');
					if ($ce == $rd['Cat'.$i])
						echo('selected ');
					echo('>'.htmlspecialchars($bd).'</option>');
				}
				echo('</select></span>');
			}
			
	echo('</form>');
	echo('</div>');
}



function optionSelected($n)
{
	return ($n>0 ? ' selected ' : '');
}


function showRallyConfig($showAdvanced)
{
	global $DB, $TAGS, $KONSTANTS, $DBVERSION;
	

	$R = $DB->query('SELECT * FROM rallyparams');
	if (!$rd = $R->fetchArray())
		$rd = defaultRecord('rallyparams');


	if ($showAdvanced) {
		$advInit = 1;
		$advFlip = 0;
		$advButtonValue = $TAGS['AdmRPHideAdv'][0];
		$advButtonTitle = $TAGS['AdmRPHideAdv'][1];
		$advButtonAltValue = $TAGS['AdmRPShowAdv'][0];
		$advButtonAltTitle = $TAGS['AdmRPShowAdv'][1];
	} else {
		$advInit = 0;
		$advFlip = 1;
		$advButtonValue = $TAGS['AdmRPShowAdv'][0];
		$advButtonTitle = $TAGS['AdmRPShowAdv'][1];
		$advButtonAltValue = $TAGS['AdmRPHideAdv'][0];
		$advButtonAltTitle = $TAGS['AdmRPHideAdv'][1];
	}

	$showAdvanced = true;

	$showDistance = $showAdvanced || $rd['OdoCheckMiles'] > 0 || $rd['PenaltyMaxMiles'] > 0 || $rd['MinMiles'] > 0 || $rd['PenaltyMilesDNF'] > 0;
	$showVirtual = $showAdvanced || $rd['isvirtual'] != 0;
	
	echo('<div id="RallyParamsSet">');

	echo('<form method="post" action="sm.php">');

	echo('<input type="hidden" name="c" value="rallyparams">');
	echo('<input type="hidden" name="menu" value="setup">');
	
	
	echo('<div class="tabs_area" style="display:inherit;"><ul id="tabs">');
	
	echo('<li><a href="#tab_basic">'.$TAGS['BasicRallyConfig'][0].'</a></li>');
	echo('<li><a href="#tab_regional">'.$TAGS['RegionalConfig'][0].'</a>');
	if ($showAdvanced) 
		echo('<li class="advanced"><a href="#tab_scoring">'.$TAGS['ScoringTab'][0].'</a></li>');
	
	if ($showDistance)
		echo('<li class="advanced"><a href="#tab_penalties">'.$TAGS['ExcessMileage'][0].'</a></li>');
	
	echo('<li><a href="#tab_rejections">'.$TAGS['RejectReasons'][0].'</a></li>');
	
	if ($showVirtual)
		echo('<li class="advanced"><a href="#tab_virtual">'.$TAGS['VirtualParams'][0].'</a></li>');
	echo('<li><a href="#tab_email">'.$TAGS['EmailParams'][0].'</a></li>');
	if ($showAdvanced) 
		echo('<li class="advanced"><a href="#tab_ebcsettings">'.$TAGS['rp_ebcsettings'][0].'</a></li>');
	if ($showAdvanced) 
		echo('<li class="advanced"><a href="#tab_settings">'.$TAGS['rp_settings'][0].'</a></li>');
	echo('</ul></div>');
	
	

	echo('<fieldset id="tab_basic" class="tabContent"><legend>'.$TAGS['BasicRallyConfig'][0].'</legend>');
	echo('<span class="vlabel">');
	echo('<label for="RallyTitle" class="vlabel">'.$TAGS['RallyTitle'][0].' </label> ');
	echo('<input size="50" type="text" name="RallyTitle" id="RallyTitle" value="'.htmlspecialchars($rd['RallyTitle']).'" title="'.$TAGS['RallyTitle'][1].'" oninput="enableSaveButton();"> ');
	//echo(' <input type="button" onclick="alert(document.getElementById(\'RallyTitle\').getAttribute(\'title\'));" value="?">');
	echo('</span>');
	
	echo('<span class="vlabel">');
	echo('<label for="RallySlogan" class="vlabel">'.$TAGS['RallySlogan'][0].' </label> ');
	echo('<input size="50" type="text" name="RallySlogan" id="RallySlogan" value="'.htmlspecialchars($rd['RallySlogan']).'" title="'.$TAGS['RallySlogan'][1].'" oninput="enableSaveButton();"> ');
	echo('</span>');
?>
<script>
function calcMaxHours() {
	let sd = document.getElementById('StartDate').value;
	let st = document.getElementById('StartTime').value;
	let fd = document.getElementById('FinishDate').value;
	let ft = document.getElementById('FinishTime').value;
	if (sd=='' || st=='' || fd=='' || ft=='') return;
	let start = Date.parse(sd+' '+st);
	let finish = Date.parse(fd+' '+ft);
	let msecs = Math.abs(finish - start);
	let hrs = Math.trunc(msecs / ((1000 * 60) * 60));
	if (hrs * ((1000 * 60) *60) < msecs)
		hrs++;  //Peter Ihlo
	let mh = document.getElementById('MaxHours');
	console.log('MaxHours == '+hrs);
	if (hrs > 0)
		mh.setAttribute('max',hrs);
}
</script>
<?php
	$date1 = new DateTime($rd['StartTime']);
	$date2 = new DateTime($rd['FinishTime']);
	$diff = $date2->diff($date1);
	$maxhours = ($diff->h) + ($diff->days*24);
	if ($diff->i > 0)
		$maxhours++;// Peter Ihlo
	

	$dt = splitDatetime($rd['StartTime']); 

	echo('<span class="vlabel">');
	echo('<label for="StartDate" class="vlabel">'.$TAGS['StartDate'][0].' </label> ');
	echo('<input type="date" name="StartDate" id="StartDate" value="'.$dt[0].'" title="'.$TAGS['StartDate'][1].'" oninput="enableSaveButton();" onchange="calcMaxHours();"> ');
	echo('<label for="StartTime">'.$TAGS['StartTime'][0].' </label> ');
	echo('<input type="time" name="StartTime" id="StartTime" value="'.$dt[1].'" title="'.$TAGS['StartTime'][1].'" oninput="enableSaveButton();" onchange="calcMaxHours();"> ');
	echo('</span>');


	$dt = splitDatetime($rd['FinishTime']); 

	echo('<span class="vlabel">');
	echo('<label for="FinishDate" class="vlabel">'.$TAGS['FinishDate'][0].' </label> ');
	echo('<input type="date" name="FinishDate" id="FinishDate" value="'.$dt[0].'" title="'.$TAGS['FinishDate'][1].'" oninput="enableSaveButton();" onchange="calcMaxHours();"> ');
	echo('<label for="FinishTime">'.$TAGS['FinishTime'][0].' </label> ');
	echo('<input type="time" name="FinishTime" id="FinishTime" value="'.$dt[1].'" title="'.$TAGS['FinishTime'][1].'" oninput="enableSaveButton();" onchange="calcMaxHours();"> ');
	echo('</span>');

	echo('<span class="vlabel">');
	echo('<label for="MaxHours" class="vlabel">'.$TAGS['MaxHours'][0].' </label> ');
	echo('<input type="number" max="'.$maxhours.'" class="smallnumber" name="MaxHours" id="MaxHours" value="'.$rd['MaxHours'].'" title="'.$TAGS['MaxHours'][1].'" oninput="enableSaveButton();"> ');
	echo('</span>');

	echo('<span class="vlabel">');
	echo('<label for="StartOption" class="vlabel">'.$TAGS['WizStartOption'][0].'</label>');
	$sm = $rd['StartOption'];
	echo('<select id="StartOption" name="StartOption" onchange="settitle(this);enableSaveButton();" title="'.$TAGS['WizStartOption'.$sm][1].'">');
	echo('<option data-title="'.$TAGS['WizStartOption0'][1].'" value="0"'.optionSelected($sm==0).'>'.$TAGS['WizStartOption0'][0].'</option>');
	echo('<option data-title="'.$TAGS['WizStartOption1'][1].'" value="1"'.optionSelected($sm==1).'>'.$TAGS['WizStartOption1'][0].'</option>');
	echo('<option data-title="'.$TAGS['WizStartOption2'][1].'" value="2"'.optionSelected($sm==2).'>'.$TAGS['WizStartOption2'][0].'</option>');
	echo('</select>');
	echo('</span>');

	echo('</fieldset>');

	echo('<fieldset id="tab_regional" class="tabContent"><legend>'.$TAGS['RegionalConfig'][0].'</legend>');

	echo('<span class="vlabel" title="'.$TAGS['DistanceUnit'][1].'">');
	echo('<label for="MilesKms">'.$TAGS['DistanceUnit'][0].' </label> ');
	echo('<select name="MilesKms" id="MilesKms"  oninput="enableSaveButton();">');
	if ($rd['MilesKms']==$KONSTANTS['OdoCountsKilometres'])
	{
		echo('<option value="'.$KONSTANTS['OdoCountsMiles'].'">'.$TAGS['OdoKmsM'][0].'</option>');
		echo('<option value="'.$KONSTANTS['OdoCountsKilometres'].'" selected >'.$TAGS['OdoKmsK'][0].'</option>');
	}
	else
	{
		echo('<option value="'.$KONSTANTS['OdoCountsMiles'].'" selected >'.$TAGS['OdoKmsM'][0].'</option>');
		echo('<option value="'.$KONSTANTS['OdoCountsKilometres'].'" >'.$TAGS['OdoKmsK'][0].'</option>');
	}
	echo('</select>');
	echo('</span>');

	echo('<span class="vlabel" title="'.$TAGS['LocalTZ'][1].'">');
	echo('<label for="LocalTZ">'.$TAGS['LocalTZ'][0].' </label> ');
	emitChooseTZ('LocalTZ','LocalTZ');


	echo('</span>');
	
	echo('<span class="vlabel" title="'.$TAGS['DecimalComma'][1].'">');
	echo('<label for="DecimalComma">'.$TAGS['DecimalComma'][0].' </label> ');
	echo('<select name="DecimalComma" id="DecimalComma"  oninput="enableSaveButton();">');
	if ($rd['DecimalComma']==1)
	{
		echo('<option value="0">'.$TAGS['DecimalCommaFalse'][0].'</option>');
		echo('<option value="1" selected >'.$TAGS['DecimalCommaTrue'][0].'</option>');
	}
	else
	{
		echo('<option value="0" selected>'.$TAGS['DecimalCommaFalse'][0].'</option>');
		echo('<option value="1" >'.$TAGS['DecimalCommaTrue'][0].'</option>');
	}
	echo('</select>');
	echo('</span>');


	echo('<span class="vlabel" title="'.$TAGS['HostCountry'][1].'">');
	echo('<label for="HostCountry">'.$TAGS['HostCountry'][0].' </label> ');
	echo('<input type="text" name="HostCountry" id="HostCountry" value="'.$rd['HostCountry'].'" oninput="enableSaveButton();">');
	echo('</span>');

	echo('<span class="vlabel" title="'.$TAGS['Locale'][1].'">');
	echo('<label for="Locale">'.$TAGS['Locale'][0].' </label> ');
	echo('<input type="text" name="Locale" id="Locale" value="'.$rd['Locale'].'" oninput="enableSaveButton();">');
	echo('</span>');

	echo('</fieldset>'); // tab_regional

	$dn = $showAdvanced ? '' : ' style="display:none;" ';
	$dnd = $showDistance ? '' : ' style="display:none;" ';
	
	echo('<fieldset '.$dn.' id="tab_scoring" class="tabContent"><legend>'.$TAGS['LegendScoring'][0].'</legend>');
	
	echo('<span class="vlabel" style="display:none;">');
	echo('<label for="ScoringMethod">'.$TAGS['ScoringMethod'][0].': </label> ');
	echo('<select name="ScoringMethod" id="ScoringMethod" oninput="enableSaveButton();">');
	$chk = ($rd['ScoringMethod']==$KONSTANTS['ManualScoring']) ? ' selected ' : '';
	echo('<option '.$chk.' value="'.$KONSTANTS['ManualScoring'].'">'.$TAGS['ScoringMethodM'][0].'</option>');
	$chk = ($rd['ScoringMethod']==$KONSTANTS['SimpleScoring']) ? ' selected ' : '';
	echo('<option '.$chk.' value="'.$KONSTANTS['SimpleScoring'].'">'.$TAGS['ScoringMethodS'][0].'</option>');
	$chk = ($rd['ScoringMethod']==$KONSTANTS['CompoundScoring']) ? ' selected ' : '';
	echo('<option '.$chk.' value="'.$KONSTANTS['CompoundScoring'].'">'.$TAGS['ScoringMethodC'][0].'</option>');
	$chk = ($rd['ScoringMethod']==$KONSTANTS['AutoScoring']) ? ' selected ' : '';
	echo('<option '.$chk.' value="'.$KONSTANTS['AutoScoring'].'">'.$TAGS['ScoringMethodA'][0].'</option>');
	echo('</select>');
	echo('</span>');

	echo('<span class="vlabel" style="display:none;">');
	echo('<label for="ShowMultipliers">'.$TAGS['ShowMultipliers'][0].': </label> ');
	echo('<select name="ShowMultipliers" id="ShowMultipliers" oninput="enableSaveButton();">');
	$chk = ($rd['ShowMultipliers']==$KONSTANTS['SuppressMults']) ? ' selected ' : '';
	echo('<option '.$chk.' value="'.$KONSTANTS['SuppressMults'].'">'.$TAGS['ShowMultipliersN'][0].'</option>');
	$chk = ($rd['ShowMultipliers']==$KONSTANTS['ShowMults']) ? ' selected ' : '';
	echo('<option '.$chk.' value="'.$KONSTANTS['ShowMults'].'">'.$TAGS['ShowMultipliersY'][0].'</option>');
	$chk = ($rd['ShowMultipliers']==$KONSTANTS['AutoShowMults']) ? ' selected ' : '';
	echo('<option '.$chk.' value="'.$KONSTANTS['AutoShowMults'].'">'.$TAGS['ScoringMethodA'][0].'</option>');
	
	echo('</select>');
	echo('</span>');


	echo('<span class="vlabel">');
	echo('<label for="TiedPointsRanking" title="'.$TAGS['TiedPointsRanking'][1].'">'.$TAGS['TiedPointsRanking'][0].' </label> ');
	$chk = ($rd['TiedPointsRanking']==$KONSTANTS['TiedPointsSplit']) ? ' checked="checked" ' : '';
	echo(' &nbsp;&nbsp;<input type="checkbox"'.$chk.' name="TiedPointsRanking" id="TiedPointsRanking" value="'.$KONSTANTS['TiedPointsSplit'].'" oninput="enableSaveButton();">');
	echo('</span>');


	echo('<span class="vlabel">');
	echo('<label for="TeamRanking">'.$TAGS['TeamRankingText'][0].': </label> ');
	echo('<select name="TeamRanking" id="TeamRanking" oninput="enableSaveButton();">');
	$chk = ($rd['TeamRanking']==$KONSTANTS['RankTeamsAsIndividuals']) ? ' selected ' : '';
	echo('<option '.$chk.' value="'.$KONSTANTS['RankTeamsAsIndividuals'].'">'.$TAGS['TeamRankingI'][0].'</option>');
	$chk = ($rd['TeamRanking']==$KONSTANTS['RankTeamsHighest']) ? ' selected ' : '';
	echo('<option '.$chk.' value="'.$KONSTANTS['RankTeamsHighest'].'">'.$TAGS['TeamRankingH'][0].'</option>');
	$chk = ($rd['TeamRanking']==$KONSTANTS['RankTeamsLowest']) ? ' selected ' : '';
	echo('<option '.$chk.' value="'.$KONSTANTS['RankTeamsLowest'].'">'.$TAGS['TeamRankingL'][0].'</option>');
	$chk = ($rd['TeamRanking']==$KONSTANTS['RankTeamsCloning']) ? ' selected ' : '';
	echo('<option '.$chk.' value="'.$KONSTANTS['RankTeamsCloning'].'">'.$TAGS['TeamRankingC'][0].'</option>');
	echo('</select>');
	echo('</span>');

	echo('<span class="vlabel">');
	echo('<label for="AutoRank" title="'.$TAGS['AutoRank'][1].'">'.$TAGS['AutoRank'][0].' </label> ');
	$chk = ($rd['AutoRank']==$KONSTANTS['AutoRank']) ? ' checked ' : '';
	echo(' &nbsp;&nbsp;<input type="checkbox"'.$chk.' name="AutoRank" id="AutoRank" value="'.$KONSTANTS['AutoRank'].'" oninput="enableSaveButton();">');
	echo('</span>');
	echo('<span class="vlabel">');
	echo('<label for="MinPoints" class="vlabel">'.$TAGS['MinPoints'][0].': </label> ');
	echo('<input type="number" name="MinPoints" id="MinPoints" value="'.$rd['MinPoints'].'" title="'.$TAGS['MinPoints'][1].'" oninput="enableSaveButton();"> ');
	echo('</span>');
	echo('</fieldset>');
	

	
	
	echo('<fieldset '.$dnd.' id="tab_penalties" class="tabContent"><legend>'.$TAGS['ExcessMileage'][0].'</legend>');

	echo('<span class="vlabel">');
	echo('<label for="OdoCheckMiles" class="vlabel">'.$TAGS['OdoCheckMiles'][0].' </label> ');
	echo('<input type="number" step="any" name="OdoCheckMiles" id="OdoCheckMiles" value="'.$rd['OdoCheckMiles'].'" title="'.$TAGS['OdoCheckMiles'][1].'" oninput="enableSaveButton();"> ');
	echo('</span>');

	echo('<span class="vlabel">');
	echo('<label for="MinMiles" class="vlabel">'.$TAGS['MinMiles'][0].' </label> ');
	echo('<input type="number" name="MinMiles" id="MinMiles" value="'.$rd['MinMiles'].'" title="'.$TAGS['MinMiles'][1].'" oninput="enableSaveButton();"> ');
	echo('</span>');

	echo('<span class="vlabel">');
	echo('<label for="PenaltyMaxMiles" class="vlabel wide">'.$TAGS['PenaltyMaxMiles'][0].' </label> ');
	echo('<input type="number" name="PenaltyMaxMiles" id="PenaltyMaxMiles" value="'.$rd['PenaltyMaxMiles'].'" title="'.$TAGS['PenaltyMaxMiles'][1].'" oninput="enableSaveButton();"> ');
	echo('</span>');

	echo('<span class="vlabel">');
	echo('<label for="MaxMilesMethod">'.$TAGS['MilesPenaltyText'][0].': </label> ');
	echo('<select name="MaxMilesMethod" id="MaxMilesMethod" oninput="enableSaveButton();">');
	$chk = ($rd['MaxMilesMethod']==$KONSTANTS['MaxMilesFixedP']) ? ' selected ' : '';
	echo('<option '.$chk.' value="'.$KONSTANTS['MaxMilesFixedP'].'">'.$TAGS['MaxMilesFixedP'][0].'</option>');
	$chk = ($rd['MaxMilesMethod']==$KONSTANTS['MaxMilesFixedM']) ? ' selected ' : '';
	echo('<option '.$chk.' value="'.$KONSTANTS['MaxMilesFixedM'].'">'.$TAGS['MaxMilesFixedM'][0].'</option>');	
	$chk = ($rd['MaxMilesMethod']==$KONSTANTS['MaxMilesPerMile']) ? ' selected ' : '';
	echo('<option '.$chk.' value="'.$KONSTANTS['MaxMilesPerMile'].'">'.$TAGS['MaxMilesPerMile'][0].'</option>');
	echo('</select>');
	echo('</span>');

	echo('<span class="vlabel">');
	echo('<label for="MaxMilesPoints" class="vlabel wide">'.$TAGS['MaxMilesPoints'][0].' </label> ');
	echo('<input type="number" name="MaxMilesPoints" id="MaxMilesPoints" value="'.$rd['MaxMilesPoints'].'" title="'.$TAGS['MaxMilesPoints'][1].'" oninput="enableSaveButton();"> ');
	echo('</span>');

	echo('<span class="vlabel">');
	echo('<label for="PenaltyMilesDNF" class="vlabel wide">'.$TAGS['PenaltyMilesDNF'][0].' </label> ');
	echo('<input type="number" name="PenaltyMilesDNF" id="PenaltyMilesDNF" value="'.$rd['PenaltyMilesDNF'].'" title="'.$TAGS['PenaltyMilesDNF'][1].'" oninput="enableSaveButton();"> ');
	echo('</span>');

	echo('</fieldset>');


	echo('<fieldset id="tab_rejections" class="tabContent"><legend>'.$TAGS['RejectReasons'][0].'</legend>');
	echo('<p>'.$TAGS['RejectReasons'][1].'</p>');
	echo('<ol>');
	$rejectreasons = explode("\n",$rd['RejectReasons']);
	foreach($rejectreasons as $rrline)
	{
		$rr = explode('=',$rrline);
		if (count($rr)==2 && intval($rr[0])>0 && intval($rr[0])<10) {
			echo('<li>');
			echo('<input type="text" name="RejectReason[]" data-code="'.$rr[0].'" value="'.$rr[1].'" oninput="enableSaveButton();">');
			echo('</li>');
		}		
	}
	echo('</ol>');
	echo('</fieldset>');
	
	echo('<fieldset '.$dn.' id="tab_virtual" class="tabContent"><legend>'.$TAGS['VirtualParams'][0].'</legend>');
?>
<script>
function flipa(obj) {
	obj.setAttribute('data-a',switcha(obj.getAttribute('data-a')));
	let tit = obj.title;
	obj.title = obj.getAttribute('data-title');
	obj.setAttribute('data-title',tit);
	let val = obj.value;
	obj.value = obj.getAttribute('data-value');
	obj.setAttribute('data-value',val);	
}
function switchv(sel) {
	let vflds = document.getElementById('vflds');
	let disp = (sel.value==0 ? 'none' : 'block');
	vflds.style.display = disp;
}
function switcha(show) {
	let aflds = document.getElementsByClassName('advanced');
	let disp = (show > 0 ? 'inline' : 'none');
	console.log('switcha showing '+disp);
	for (let el of aflds) {
		el.style.display = disp;
	}
	let r = (parseInt(show) + 1) % 2;
	console.log('switcha returning '+r)
	return r;
}
function settitle(sel) {
	sel.title = sel.options[sel.selectedIndex].getAttribute('data-title');
}
</script>
<?php	
	echo('<span class="vlabel" title="'.$TAGS['vr_RallyType'][1].'"><label for="isvirtual">'.$TAGS['vr_RallyType'][0].'</label> ');
	echo('<select name="isvirtual" id="isvirtual" onchange="switchv(this);" oninput="enableSaveButton();">');
	echo('<option value="0" '.($rd['isvirtual']==0 ? ' selected ' : '').'>'.$TAGS['vr_RallyType0'][0].'</option>');
	echo('<option value="1" '.($rd['isvirtual']==1 ? ' selected ' : '').'>'.$TAGS['vr_RallyType1'][0].'</option>');
	echo('</select></span>');

	echo('<fieldset id="vflds" style="border:none; display:'.($rd['isvirtual']==0?'none':'block').'">');
	echo('<span class="vlabel" title="'.$TAGS['vr_TankRange'][1].'"><label for="tankrange">'.$TAGS['vr_TankRange'][0].'</label > ');
	echo('<input type="number" id="tankrange" name="tankrange" value="'.$rd['tankrange'].'" oninput="enableSaveButton();"></span>');
	
	echo('<span class="vlabel" title="'.$TAGS['vr_StopMins'][1].'"><label for="stopmins">'.$TAGS['vr_StopMins'][0].'</label > ');
	echo('<input type="number" id="stopmins" name="stopmins" value="'.$rd['stopmins'].'" oninput="enableSaveButton();"></span>');
	
	echo('<span class="vlabel" title="'.$TAGS['vr_RefuelStops'][1].'"><label for="refuelstops">'.$TAGS['vr_RefuelStops'][0].'</label > ');
	echo('<input type="text" id="refuelstops" name="refuelstops" value="'.$rd['refuelstops'].'" oninput="enableSaveButton();"></span>');
	
	echo('</fieldset>');
	
	echo('</fieldset>');
	
	
	echo('<fieldset id="tab_email" class="tabContent"><legend>'.$TAGS['EmailParams'][0].'</legend>');
	
	$email = json_decode($rd['EmailParams'],true);
	echo('<p>'.$TAGS['EmailParams'][1]);
	echo(' <input title="Help!" type="button" value=" ? " onclick="showHelp('."'emailsetup'".');">');
	echo('</p>');
	
	echo('<span class="vlabel"><label for="EmailParams"> </label> '); // Label merely to maintain spacing
	echo('<textarea id="EmailParams" name="EmailParams" cols="160" rows="20" oninput="enableSaveButton();">');
	//echo($rd['EmailParams']);
	if ($email)
		echo(json_encode($email,JSON_PRETTY_PRINT));  // Make sure it's legible
	else
		echo($rd['EmailParams']);
	echo('</textarea></span>');

	echo('</fieldset>');

	if ($showAdvanced) {
		echo('<fieldset id="tab_settings" class="tabContent"><legend>'.$TAGS['rp_settings'][0].'</legend>');
	
		$settings = json_decode($rd['settings'],true);
		echo('<p>'.$TAGS['rp_settings'][1]);
		echo(' <input title="Help!" type="button" value=" ? " onclick="showHelp('."'rpsettings'".');">');
		echo('</p>');
	
		echo('<span class="vlabel"><label for="settings"> </label> '); // Label merely to maintain spacing
		echo('<textarea id="settings" name="settings" cols="160" rows="20" oninput="enableSaveButton();">');
	
		if ($settings)
			echo(json_encode($settings,JSON_PRETTY_PRINT));  // Make sure it's legible
		else
			echo($rd['settings']);
		echo('</textarea></span>');


		echo('</fieldset>');
	}

	if ($showAdvanced) {
		echo('<fieldset id="tab_ebcsettings" class="tabContent"><legend>'.$TAGS['rp_ebcsettings'][0].'</legend>');
	
		$ebcsettings = $rd['ebcsettings'];
		echo('<p>'.$TAGS['rp_ebcsettings'][1]);
		echo('</p>');
	
		echo('<span class="vlabel"><label for="ebcsettings"> </label> '); // Label merely to maintain spacing
		echo('<textarea id="ebcsettings" name="ebcsettings" cols="160" rows="20" oninput="enableSaveButton();">');
	
		echo($ebcsettings);
		echo('</textarea></span>');


		echo('</fieldset>');
	}



	echo('<input type="button" ');
	echo('data-a="'.$advFlip.'" ');
	echo('onclick="flipa(this);" ');
	echo('title="'.$advButtonTitle.'" data-title="'.$advButtonAltTitle.'" ');
	echo('value="'.$advButtonValue.'" data-value="'.$advButtonAltValue.'"> ');
	echo('<input type="submit" disabled onclick="this.setAttribute(\'data-triggered\',\'1\');" data-triggered="0" name="savedata" id="savedata" data-altvalue="'.$TAGS['SaveRallyConfig'][0].'" value="'.$TAGS['RallyConfigSaved'][0].'">');

	echo('</div');

	echo('</form>');
	echo('</div> <!-- RallyParamsSet -->');
	echo('<script>');
	echo('switcha('.$advInit.');');
	echo('</script>');
	//showFooter();
}


function showSGroups()
{
	global $DB, $TAGS, $KONSTANTS;
	

	
	$R = $DB->query('SELECT * FROM sgroups ORDER BY GroupName');
	if (!$rd = $R->fetchArray())
		$rd = defaultRecord('sgroups');

?>
<script>
function triggerNewRow(obj)
{
	var oldnewrow = document.getElementsByClassName('newrow')[0];
	var tab = document.getElementById('sgroups').getElementsByTagName('tbody')[0];
	var row = tab.insertRow(tab.rows.length);
	row.innerHTML = oldnewrow.innerHTML;
	obj.onchange = '';
	document.getElementsByClassName('newGroupType')[0].disabled = false;
	
}
</script>
<?php	


	echo('<form method="post" action="sm.php">');
	
	echo('<input type="hidden" name="c" value="sgroups">');
	echo('<input type="hidden" name="menu" value="setup">');
	echo('<p>'.$TAGS['SGroupMaintHead'][1].'</p>');
	echo('<table id="sgroups">');
//	echo('<caption title="'.htmlentities($TAGS['SGroupMaintHead'][1]).'">'.htmlentities($TAGS['SGroupMaintHead'][0]).'</caption>');
	echo('<thead><tr><th style="text-align: left; ">'.$TAGS['SGroupLit'][0].'</th>');
	echo('<th style="text-align: left; ">'.$TAGS['SGroupTypeLit'][0].'</th>');
	echo('<th>'.$TAGS['DeleteEntryLit'][0].'</th>');
	echo('</tr>');
	echo('</thead><tbody>');
	
	
	$sql = 'SELECT * FROM sgroups ORDER BY GroupName';
	$R = $DB->query($sql);
	if ($DB->lastErrorCode() <> 0)
		echo($DB->lastErrorMsg().'<br>'.$sql.'<hr>');
	while ($rd = $R->fetchArray())
	{
		echo('<tr class="hoverlite"><td><input class="SGroupName" type="text" name="GroupName[]" readonly value="'.$rd['GroupName'].'"></td>');
		echo('<td>');
		echo('<select name="GroupType[]">');
		echo('<option value="R"'.($rd['GroupType']=='R' ? ' selected ' : '').'>'.$TAGS['SGroupTypeR'][1].'</option>');
		echo('<option value="C"'.($rd['GroupType']=='C' ? ' selected ' : '').'>'.$TAGS['SGroupTypeC'][1].'</option>');
		echo('</select>');
		echo('</td>');
		echo('<td class="center"><input type="checkbox" name="DeleteEntry[]" value="'.$rd['GroupName'].'">');
		echo('</tr>');
	}
	echo('<tr class="newrow"><td><input type="text" placeholder="'.$TAGS['NewPlaceholder'][0].'" name="GroupName[]" onchange="triggerNewRow(this)"></td>');
		echo('<td><select class="newGroupType" name="GroupType[]" disabled>');
		echo('<option value="R">'.$TAGS['SGroupTypeR'][1].'</option>');
		echo('<option value="C" selected>'.$TAGS['SGroupTypeC'][1].'</option>');
		echo('</select>');
		echo('</td>');
	echo('</tr>');
	
	echo('</tbody></table>');
	echo('<input type="submit" name="savedata" value="'.$TAGS['UpdateSGroups'][0].'"> ');
	echo('</form>');
	//showFooter();
	
}



function showSpecial($specialid)
{
	global $DB, $TAGS, $KONSTANTS, $DBVERSION;

	$sql = "SELECT * FROM rallyparams";
	$R = $DB->query($sql);
	$rd = $R->fetchArray();
	$ScoringMethod = $rd['ScoringMethod'];
	if ($ScoringMethod == $KONSTANTS['AutoScoring'])
		$ScoringMethod = chooseScoringMethod();
	$ShowMults = $rd['ShowMultipliers'];
	if ($ShowMults == $KONSTANTS['AutoShowMults'])
		$ShowMults = chooseShowMults($ScoringMethod);


	$sql = "SELECT * FROM sgroups ORDER BY GroupName";
	$R = $DB->query($sql);
	if ($DB->lastErrorCode() <> 0)
		echo($DB->lastErrorMsg().'<br>'.$sql.'<hr>');
	$groups = [''];
	$ngroups = 1;
	while($rd = $R->fetchArray())
	{
		array_push($groups,$rd['GroupName']);
		$ngroups++;
	}
	//print_r($groups);
	//echo($ngroups);
	$sql = "SELECT * FROM specials WHERE BonusID='".$specialid."'";
	$R = $DB->query($sql);
	if ($DB->lastErrorCode() <> 0)
		echo($DB->lastErrorMsg().'<br>'.$sql.'<hr>');
	if (!$rd = $R->fetchArray())
		$rd = defaultRecord('specials');
	$valid = "if(document.querySelector('#BonusID').value==''){";
	$valid .= "document.querySelector('#BonusID').focus();return false;}";
	$valid .= "if(document.querySelector('#BriefDesc').value==''){";
	$valid .= "document.querySelector('#BriefDesc').focus();return false;}";
	$valid .= "return true;";
	echo("\r\n");
	echo('<form onsubmit="'.$valid.'">');
	echo('<input type="hidden" name="c" value="special">');
	echo('<span class="vlabel" title="'.$TAGS['BonusIDLit'][1].'"><label for="BonusID">'.$TAGS['BonusIDLit'][0].'</label> ');
	$ro = ($specialid != '' ? ' readonly ' : '');
	echo('<input type="text"'.$ro.' name="BonusID" id="BonusID" value="'.$specialid.'" onchange="enableSaveButton();">');
	echo('</span>');
	echo('<span class="vlabel" title="'.$TAGS['BriefDescLit'][1].'"><label for="BriefDesc">'.$TAGS['BriefDescLit'][0].'</label> ');
	echo('<input type="text" name="BriefDesc" id="BriefDesc" value="'.$rd['BriefDesc'].'" onchange="enableSaveButton();">');
	echo('</span>');
	if ($ngroups > 1)
	{
		echo('<span class="vlabel" title="'.$TAGS['GroupNameLit'][1].'"><label for="GroupName">'.$TAGS['GroupNameLit'][0].'</label> ');
		echo('<select name="GroupName" id="GroupName" onchange="enableSaveButton();">');
		for ($i=0; $i<$ngroups; $i++)
			echo('<option value="'.$groups[$i].'" '.($rd['GroupName']==$groups[$i] ? ' selected' : '').'>'.$groups[$i].'</option>');
		echo('</select>');
		echo('</span>');
	}
	else // No special groups available so don't offer any
		echo('<input type="hidden" name="GroupName" id="GroupName" value="'.$rd['GroupName'].'">');
	echo('<span class="vlabel" title="'.$TAGS['SpecialPointsLit'][1].'"><label for="Points">'.$TAGS['SpecialPointsLit'][0].'</label> ');
	echo('<input type="number" name="Points" id="Points" value="'.$rd['Points'].'" onchange="enableSaveButton();">');
//	echo('</span>');
//	echo('<span class="vlabel"><label for="AskPoints">'.$TAGS['AskPoints'][0].'</label> ');
//	echo(' <label for="AskPoints">'.$TAGS['AskPoints'][0].'</label> ');
	echo(' <select name="AskPoints" id="AskPoints" onchange="enableSaveButton();">');
	echo('<option value="0"'.($rd['AskPoints']==0 ? ' selected>' : '>').$TAGS['AskPoints0'][0].'</option>');
	echo('<option value="1"'.($rd['AskPoints']==0 ? '>' : ' selected>').$TAGS['AskPoints1'][0].'</option>');
	echo('</select>');
	echo('</span>');

	if ($ShowMults) {
		echo('<span class="vlabel" title="'.$TAGS['SpecialMultLit'][1].'"><label for="MultFactor">'.$TAGS['SpecialMultLit'][0].'</label> ');
		echo('<input type="number" class="smallnumber" name="MultFactor" id="MultFactor" value="'.$rd['MultFactor'].'" onchange="enableSaveButton();">');
		echo('</span>');
	} else {
		echo('<input type="hidden" name="MultFactor" id="MultFactor" value="'.$rd['MultFactor'].'" >');		
	}

	echo('<span class="vlabel" title="'.$TAGS['CompulsoryBonus'][1].'"><label for="Compulsory">'.$TAGS['CompulsoryBonus'][0].'</label> ');
	echo('<select name="Compulsory" id="Compulsory" onchange="enableSaveButton();">');
	echo('<option value="0"'.($rd['Compulsory']==0 ? ' selected>' : '>').$TAGS['CompulsoryBonus0'][0].'</option>');
	echo('<option value="1"'.($rd['Compulsory']==1 ? ' selected>' : '>').$TAGS['CompulsoryBonus1'][0].'</option>');
	echo('<option value="2"'.($rd['Compulsory']==2 ? ' selected>' : '>').$TAGS['CompulsoryBonus2'][0].'</option>');
	echo('</select>');
	echo('</span>');
	echo('<span class="vlabel" title="'.$TAGS['RestMinutesLit'][1].'"><label for="RestMinutes">'.$TAGS['RestMinutesLit'][0].'</label> ');
	echo('<input type="number" class="smallnumber" name="RestMinutes" id="RestMinutes" value="'.$rd['RestMinutes'].'" onchange="enableSaveButton();">');
//	echo('</span>');
//	echo('<span class="vlabel"><label for="AskMinutes">'.$TAGS['AskMinutes'][0].'</label> ');
//	echo(' <label for="AskMinutes">'.$TAGS['AskMinutes'][0].'</label> ');
	echo(' <select name="AskMinutes" id="AskMinutes" onchange="enableSaveButton();">');
	echo('<option value="0"'.($rd['AskMinutes']==0 ? ' selected>' : '>').$TAGS['AskMinutes0'][0].'</option>');
	echo('<option value="1"'.($rd['AskMinutes']==0 ? '>' : ' selected>').$TAGS['AskMinutes1'][0].'</option>');
	echo('</select>');
	echo('<span class="vlabel">');
	if ($specialid != '')
	{
		echo(' <input type="submit" name="savedata" id="savedata" data-altvalue="'.$TAGS['SaveRecord'][0].'" value="'.$TAGS['RecordSaved'][0].'" disabled> ');
		$rex = getValueFromDB("SELECT count(*) As rex FROM entrants WHERE ',' || SpecialsTicked || ',' LIKE '%,".$rd['BonusID'].",%'","rex",0);
		if ($rex < 1)
		{
			echo(' <input type="submit" name="delete" value="'.$TAGS['DeleteBonus'][0].'"');
			if ($rex > 0)
				echo(' disabled');
			echo('>');
		}
	}
	else
		echo(' <input type="submit" name="savedata" id="savedata" value="'.$TAGS['SaveRecord'][0].'"> ');
	
	
	
	echo('</span>');
	echo('</form>');
}


function showSpecials()
{
	global $DB, $TAGS, $KONSTANTS;

	
	$showclaimsbutton = (getValueFromDB("SELECT count(*) As rex FROM entrants","rex",0)>0);


	echo('<p>'.$TAGS['SpecialMaintHead'][1].'</p>');
	echo('<table id="bonuses">');
//	echo('<caption title="'.htmlentities($TAGS['SpecialMaintHead'][1]).'">'.htmlentities($TAGS['SpecialMaintHead'][0]).'</caption>');
	echo('<thead><tr><th>'.$TAGS['BonusIDLit'][0].'</th>');
	echo('<th>'.$TAGS['BriefDescLit'][0].'</th>');
	echo('<th>'.$TAGS['SpecialPointsLit'][0].'</th>');
	echo('<th>'.$TAGS['AskPoints'][0].'</th>');
	echo('<th>'.$TAGS['CompulsoryBonus'][0].'</th>');
	if ($showclaimsbutton)
		echo('<th class="ClaimsCount">'.$TAGS['ShowClaimsCount'][0].'</th>');
	echo('</tr>');
	echo('</thead><tbody>');

	$sql = 'SELECT * FROM specials ORDER BY BonusID';
	$R = $DB->query($sql);
	if ($DB->lastErrorCode() <> 0)
		echo($DB->lastErrorMsg().'<br>'.$sql.'<hr>');
	while ($rd = $R->fetchArray())
	{
	
		echo('<tr class="link" onclick="window.location.href=\'sm.php?c=special&amp;bonus='.$rd['BonusID'].'\'">');
		echo('<td>'.$rd['BonusID'].'</td>');
		echo('<td>'.$rd['BriefDesc'].'</td>');
		echo('<td>'.$rd['Points'].'</td>');
		if ($rd['AskPoints']<>0)
			$chk = " &checkmark; ";
		else
			$chk = "";
		echo('<td class="center">'.$chk.'</td>');
		if ($rd['Compulsory']==1)
			$chk = " &checkmark; ";
		else if ($rd['Compulsory']==2)
			$chk = " &cross; ";
		else
			$chk = "";
		echo('<td class="center">'.$chk.'</td>');
		if ($showclaimsbutton)
		{
			$rex = getValueFromDB("SELECT count(*) As rex FROM entrants WHERE ',' || SpecialsTicked || ',' LIKE '%,".$rd['BonusID'].",%'","rex",0);
			echo('<td class="ClaimsCount" title="'.$TAGS['ShowClaimsButton'][1].'">');
			if ($rex > 0)
				echo('<a href='."'entrants.php?c=entrants&mode=special&bonus=".$rd['BonusID']."'".'> '.$rex.' </a>');
			echo('</td>');
		}
	
	echo('</tr>');

	}
	echo('</tbody></table>');
	echo(' <button onclick="window.location='."'sm.php?c=special&bonus"."'".'">+</button>');
}









function checkBonusid($b)
/*
 * I am called to check whether the bonusid exists as
 * an ordinary, special or combo record.
 *
 * I return BriefDesc if exists or an empty string if not.
 *
 */
 {
	global $DB;
	
	$bx = $DB->escapeString($b);
	$bd = getValueFromDB("SELECT 'B-' || BriefDesc As BriefDesc FROM bonuses WHERE BonusID='".$bx."'","BriefDesc","");
	if ($bd=="")
		$bd = getValueFromDB("SELECT 'C-' || BriefDesc As BriefDesc FROM combinations WHERE ComboID='".$bx."'","BriefDesc","");

	return "$bd";
}

function callbackCheckBonusid($b)
{
	echo(checkBonusID($b));
}



if (isset($_REQUEST['c']))
	switch($_REQUEST['c']) {
		case 'rallyparams':
			if (isset($_REQUEST['savedata']))
				saveRallyConfig();
			break;
			
			
		case 'special':
			//print_r($_REQUEST);
			if (isset($_REQUEST['delete']) && isset($_REQUEST['BonusID']))
			{
				deleteSpecial($_REQUEST['BonusID']);
				break;
			}
			if (isset($_REQUEST['savedata']))
				saveSpecial();
			
		case 'specials':
			break;

		case 'combo':
			if (isset($_REQUEST['comboid']))
			{
				if (isset($_REQUEST['savedata']))
				{
					saveSingleCombo();
					exit;
				}
				break;
			}
		case 'combos':
			if (isset($_REQUEST['savedata']))
				saveCombinations();
			break;
			
		case 'sgroups':
			if (isset($_REQUEST['savedata']))
				saveSGroups();
			break;

		case 'checkbonus':
			callbackCheckBonusid(strtoupper($_REQUEST['bid']));
			exit;

	}





startHtml($TAGS['ttSetup'][0]);


if (isset($_REQUEST['c']))
{
	switch($_REQUEST['c'])
	{
		case 'rallyparams':
			showRallyConfig(isset($_REQUEST['adv']) && $_REQUEST['adv']!='0');
			break;
			
			
		case 'special':
			//print_r($_REQUEST);
			if (isset($_REQUEST['delete']) && isset($_REQUEST['BonusID']))
			{
				showSpecials();
				break;
			}
			if (isset($_REQUEST['bonus']))
			{
				showSpecial($_REQUEST['bonus']);
				break;
			}
			
		case 'specials':
//			if (isset($_REQUEST['savedata']))
//				saveSpecials();
			showSpecials();
			break;

		case 'combo':
			if (isset($_REQUEST['comboid']))
			{
				showSingleCombo($_REQUEST['comboid']);
				break;
			}
		case 'combos':
			showCombinations();
			break;
			
		case 'sgroups':
			showSGroups();
			break;

		default:
			showSpecial($_REQUEST['c']);
			//echo("<p>I don't know what to do with '".$_REQUEST['c']."'!");
	}
} else
	include "score.php"; // Some mistake has happened or maybe someone just tried logging on
//	print_r($_REQUEST);

?>

