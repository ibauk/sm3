<?php

/*
 * I B A U K   -   S C O R E M A S T E R
 *
 * I handle team specific coding
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



// Alphabetic order below



function alignHits($k1,$k2,$L1,$L2,$offset1,$offset2,$maxgap)
{
	

	$R = alignMatches($L1,$L2);
	for ($i=0; ($i < count($R[0]) && $i < count($R[1])); $i++)
		if ($R[0][$i]==$R[1][$i])
		{
			$R[0][$i] = '<strong>'.$R[0][$i].'</strong>';
			$R[1][$i] = '<strong>'.$R[1][$i].'</strong>';
		}
	showFixedLine($k1,$R[0],'');
	showFixedLine($k2,$R[1],'lastrow');
	//echo('<hr>');
	
}


function alignMatches($L1,$L2)
{
	//echo("L1.length=".count($L1).'; L2.length='.count($L2).'<br>');
	if (count($L1) < 1)
		return [$L1,$L2];
	$filler = str_repeat('&nbsp;',strlen($L1[0]));
	for ($i = 0, $j = 0; ($i < count($L1) && $j < count($L2)); $i++)
//	for ($i = 0, $j = 0; $i < count($L1); $i++)
	{
		$p = findMatch($L1[$i],$L2,$j);
		$q = findMatch($L2[$j],$L1,$i);
		//echo("p=$p, q=$q, j=$j<br>");
		if ($p != $j)
		{			
			if ($p >= 0 && ($q < 0 || $p - $j <= $q - $i))
			{
				while ($j++ < $p)
				{
					array_splice($L1,$i,0,$filler);
					$i++;
				}
				$R = alignMatches(array_slice($L1,$i+1),array_slice($L2,$j));
				return [array_merge(array_slice($L1,0,$i+1),$R[0]),array_merge(array_slice($L2,0,$j),$R[1])];
			}
			if ($q >= 0 && ($p < 0 || $p - $j > $q - $i))
			{
				while ($i++ < $q)
				{
					array_splice($L2,$j,0,$filler);
					$j++;
				}
				$R = alignMatches(array_slice($L1,$i),array_slice($L2,$j+1));
				return [array_merge(array_slice($L1,0,$i),$R[0]),array_merge(array_slice($L2,0,$j+1),$R[1])];
			}
		}
		$j++;
	}
	return [$L1,$L2];
	
}


function compareLists($L1,$L2,$minmatch,$maxgap)
/*
 * minmatch	= minimum number of sequential items matched in L1, L2
 * maxgap	= maximum mismatches finding minmatch run
 *
 */
{
	$DEBUG = FALSE;
	
	if ($DEBUG) echo('Count($L1)='.count($L1).'; count($L2)='.count($L2).'; min='.$minmatch.'; max='.$maxgap.'<br>');
	
	$matchix1 = 0; 	$matchlen = 0; 	$matchgap = 0; 	$matchix2 = 0;
	
	// Skip to first match
	while ( ($matchix1 + $matchlen  < count($L1)) && ($matchlen < $minmatch) && ($matchix2 + $matchgap < count($L2)) ) { // Area of list1 to be matched
		if ($DEBUG)	{ flush(); ob_flush(); 	}
		
		if ($matchix2 >= count($L2)) {
			$matchix1++;
			$matchlen = 0; 	$matchgap = 0; 	$matchix2 = 0;
			continue;
		}

		$x = $L1[$matchix1 + $matchlen]; // What to look for
		$y = $L2[$matchix2 + $matchgap];
		
		if ($DEBUG) { echo("x[$matchix1+$matchlen]=$x == y[$matchix2+$matchgap]=$y; <br>"); }
		if ($x == $y) {	// build match
			$matchlen++;
			$matchix2 += $matchgap;
			$matchgap = 0;
		} else {
			if ($matchlen > 0) 	{ // match underway
				$matchgap++;
				if ($matchgap > $maxgap) {
					$matchgap = 0; $matchlen = 0; $matchix2 = 0;
					$matchix1++;
					continue;
				}
			} else
				$matchix2++;
		}
		//if ($DEBUG) if ($matchlen > 0) echo('ix: '.$matchix1.'('.$x.') len='.$matchlen.'; gap='.$matchgap.' @ '.$matchix2.'('.$y.')<br>');
	}
	$res = [$matchlen >= $minmatch,$matchix1,$matchix2 - $matchlen];
	if ($DEBUG && false)
	{
		echo('MLb: '.$matchlen.': ');
		while ($matchlen--)
			echo($L1[$matchix1++].'.');
		echo('<br>');
	}
	return $res;
	
}

function fetchLists()
{
	global $DB;
	
	$sql = "SELECT EntrantID,BonusesVisited FROM entrants ORDER BY TeamID,EntrantID";
	$R = $DB->query($sql);
	$res = [];
	while ($rd = $R->fetchArray()) {
		$bv = explode(',',''.$rd['BonusesVisited']);
		$bvv = [];
		for($i = 0; $i < count($bv); $i++) {
			if ($bv[$i] != '') {
				$p = strpos($bv[$i],"=");
				if ($p === false) {
					array_push($bvv,$bv[$i]);
				} else {
					array_push($bvv,substr($bv[$i],0,$p));
				}
			}
		}
		//echo('<br>'.implode(',',$bvv));
		$res[$rd['EntrantID']] = $bvv;
	}
	
	return $res;
}


function detectTeamMembers()
/*
 * I attempt to identify members of undeclared teams by looking at their bonus claims
 * I can also be used as a sanity check for missed entries
 */
{
	global $DB, $TAGS, $KONSTANTS;

	$lists = fetchLists();
	//print_r($lists);
	$minmatch = isset($_REQUEST['m']) ? intval($_REQUEST['m']) : 3;
	$maxgap = isset($_REQUEST['g']) ? intval($_REQUEST['g']) : 2;
	$keys = array_keys($lists);
	
	echo('<form method="get" action="teams.php">');
	echo('<h4>'.$TAGS['ttTeams'][1].' - ');
	echo('<em>m</em>=<input class="smallnumber" type="number" name="m" min="2" onchange="this.form.submit();" value="'.$minmatch.'">');
	echo(' <em>g</em>=<input class="smallnumber" type="number" name="g" min="0" onchange="this.form.submit();" value="'.$maxgap.'"></h4>');
	echo('</form>');
	echo('<p>'.$TAGS['TeamExplain'][1].'</p>');
	echo('<table class="teamslist">');
	
	
	for ($i = 0; $i + 1 < count($lists); $i++)
	{
		$ishown = false;
		for ($j = $i + 1; $j < count($lists); $j++)
		{
			$res = compareLists($lists[$keys[$i]],$lists[$keys[$j]],$minmatch,$maxgap + 1); // entered as gap but used as offset
			
			if ($res[0])
				alignHits($keys[$i],$keys[$j],$lists[$keys[$i]],$lists[$keys[$j]],$res[1],$res[2],$maxgap);
			
		}
	}		
	echo('</table>');
	echo('</body>');
	echo('</html>');
}

function findMatch($needle,$haystack,$frompos)
{
	//echo('fm: {'.$needle.'}; {'.$haystack[$frompos].'}; '.$frompos.'; '.count($haystack));
	while($frompos < count($haystack))
	{
		if ($haystack[$frompos] == $needle)
			return $frompos;
		$frompos++;
	}
	return -1;
}

function showFixedLine($k,$arr,$rowcls)
{
	global $DB;
	
	$sql = "SELECT * FROM entrants WHERE EntrantID=$k";
	$R = $DB->query($sql);
	$rd = $R->fetchArray();
	echo('<tr class="'.$rowcls.'">');
	echo('<td class="RiderName">'.$k.': ');
	if ($rd['TeamID'] > 0)
		echo(' ('.$rd['TeamID'].') ');
	echo($rd['RiderName'].'</td>');
	echo('<td class="BonusMatches">');
	for ($i=0;$i<count($arr);$i++)
		echo($arr[$i].' ');
	echo('</td>');
	echo('</tr>');
}




startHtml($TAGS['ttTeams'][0]);

detectTeamMembers();

?>
