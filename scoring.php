<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

/*
 * I B A U K   -   S C O R E M A S T E R
 *
 * I provide scoring functions called by scorecard.php and claims.php
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
 *
 */

/*
 *  $_REQUEST variables used by routines here AND MUST BE PRESENT are :-
 * 
 *  EntrantID
 * 
 * 
 * 
 */

function updateAutoClass($entrantid) {

    /*
     *
     *  I am always called inside a transaction. No new transaction needed here.
     * 
     */

		global $DB;

		error_log('Updating auto class');
		$sql = "SELECT * FROM classes WHERE AutoAssign=1 and Class > 0 ORDER BY Class";
		$R = $DB->query($sql);
		$mp = []; $mb = []; $br = []; $lr = [];
		while ($rd = $R->fetchArray()) {
			$mp[$rd['Class']] = $rd['MinPoints'];
			$mb[$rd['Class']] = $rd['MinBonuses'];
			$br[$rd['Class']] = explode(',',$rd['BonusesReqd']);
			$lr[$rd['Class']] = $rd['LowestRank'];
		}
		if (count($mp) < 1) // No automatic classes so ...
			return;
		
		// There are automatic classes available - don't even think about mixing auto/manual classes
		
		$team = getValueFromDB("SELECT TeamID FROM entrants WHERE EntrantID=".$entrantid,"TeamID",0);
		$sql = "SELECT Class,EntrantID,TotalPoints,BonusesVisited,CombosTicked,FinishPosition FROM entrants WHERE ";
		if ($team = 0)
			$sql .= "EntrantID=".$entrantid;
		else
			$sql .= "TeamID=".$team;
		$R = $DB->query($sql);
		$recs = [];
		while ($rd = $R->fetchArray()) {
			$recs[$rd['EntrantID']] = 0; // Default is 0 unless successfully matched this time
			$nc = 1;
			while ($nc <= count($mp)) {
				$ok = $mp[$nc] == 0 || $rd['TotalPoints'] >= $mp[$nc];
				$ok = $ok && ($mb[$nc] == 0 || count(explode(',',$rd['BonusesVisited'])) >= $mb[$nc]);
				
				if ($ok && count($br[$nc]) > 0) {
					$ok = updateAutoClassBR($br[$nc],$rd['BonusesVisited'],$rd['CombosTicked']); 
				}
				$ok = $ok && ($lr[$nc] == 0 || $rd['FinishPosition'] <= $lr[$nc]);
				if ($ok) {
					$recs[$rd['EntrantID']] = $nc;
					break;
				}
				$nc++;
			}
		}
		foreach ($recs as $ent => $cls) {
			$sql = "UPDATE entrants SET Class=".$cls." WHERE EntrantID=".$ent;
			if (!$DB->exec($sql)) {
				dberror();
				exit;
			}
		}

			
}

function updateAutoClassBR($br,$bv,$ct)
/*
 * I check that every element in $br exists in $bv,$st or $ct
 * and return true if so
 *
 */
{
	foreach($br as $B =>$v) {
		$re = "/\b$v\b/";
		$ok = (preg_match($re,$bv) || preg_match($re,$ct));
		error_log($re.' == '.$ok);
		if (!$ok)
			return false;
	}
	return true;		
	
}

function updateTeamScorecards($entrantid) {
/*
 * I am called to apply the current entrant score to all other members of his team
 * if the CloneTeamMembers flag is set.
 *
 * $_REQUEST['EntrantID'] is the scoring member.
 * 
 * I am always called inside a transaction so no new transaction needed here.
 * 
 */

	global $DB, $TAGS, $KONSTANTS, $AUTORANK, $DBVERSION;
	
	$sql = "SELECT TeamRanking FROM rallyparams";
	if (getValueFromDB($sql,"TeamRanking",$KONSTANTS['RankTeamsAsIndividuals']) != $KONSTANTS['RankTeamsCloning'])
		return true;
	$sql = "SELECT * FROM entrants WHERE EntrantID=".$entrantid;
    $R = $DB->query($sql);
    if (!$rd = $R->fetchArray())
        return true;
	$team = $rd["TeamID"];
	if ($team == 0)
		return true;
	

    $sql = "UPDATE entrants SET Confirmed=".$KONSTANTS['ScorecardIsClean'];
	$sql .= ",CorrectedMiles=".$rd['CorrectedMiles'];
	$sql .= ",FinishTime='".$rd['FinishTime']."'";
		
   	$sql .= ",BonusesVisited='".$DB->escapeString($rd['BonusesVisited'])."'";	
	$sql .= ",CombosTicked='".$DB->escapeString($rd['CombosTicked'])."'";	
	
	$sql .= ",TotalPoints=".$rd['TotalPoints'];
	$sql .= ",StartTime='".$rd['StartTime']."'";
	$sql .= ",FinishPosition=".$rd['FinishPosition'];
	$sql .= ",EntrantStatus=".$rd['EntrantStatus'];
	$sql .= ",ScoreX='".$DB->escapeString($rd['ScoreX'])."'";
	$sql .= ",RejectedClaims='".$DB->escapeString($rd['RejectedClaims'])."'";
	$sql .= ",RestMinutes=".$rd['RestMinutes'];
	$sql .= ",AvgSpeed='".$DB->escapeString($rd['AvgSpeed'])."'";
	$sql .= " WHERE TeamID=$team AND EntrantID<>".$entrantid;

	//echo('<hr>'.$sql.'<hr>');
	if (!$DB->exec($sql)) {
		dberror();
		exit;
	}
	if (($res = $DB->lastErrorCode()) <> 0) {
		echo($DB->lastErrorCode().' '.$DB->lastErrorMsg().' ');
		return dberror();
	}
//	echo(' well '.$AUTORANK.' ');
	
	return true;
}



?>
