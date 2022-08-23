<?php

/*
 * I B A U K   -   S C O R E M A S T E R
 *
 * I handle the scoring end of things, formatting the scoresheets and recording the results
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


// Rest bonus stuff

$HOME_URL = "admin.php";

require_once('common.php');

/**
 * bonusCodes returns an array of the BonusIDs which belong in any of
 * the bonus groups listed in $grps
 */
function bonusCodes($grps) {

    global $DB;

    $grpxx = inList(explode(',',$grps));
    $sql = "SELECT BonusID FROM bonuses WHERE GroupName in ($grpxx)";
    error_log($sql);
    $R = $DB->query($sql);
    $codes = [];
    while ($rd = $R->fetchArray()) {
        array_push($codes,$rd['BonusID']);
    }
    return $codes;
}

/**
 * inList formats the entries in the array $arr into a single string
 * of single quoted literals separated by ','.
 */
function inList($arr) {

    $res = "";
    for ($i = 0; $i < count($arr); $i++) {
        if ($res != "") $res .= ",";
        $res .= "'".$arr[$i]."'";
    }
    return $res;
}


function minutesBetween($periodstarts,$periodends) {

    $periodstartx = substr($periodstarts,0,16);
    $periodendx = substr($periodends,0,16);
    error_log($periodstartx.' ==> '.$periodendx);
    try {
        $ps = DateTime::createFromFormat('Y-m-d\TH:i+',$periodstartx);
        //var_dump(DateTime::getLastErrors());
    } catch(Exception $e) {
        error_log('ps :: '.$e->getMessage());
        return;
    }
    error_log("PS".json_encode($ps));
    try {
        $pe = DateTime::createFromFormat('Y-m-d\TH:i+',$periodendx);
        //var_dump(DateTime::getLastErrors());
    } catch(Exception $e) {
        error_log('pe :: '.$e->getMessage());
        return;
    }
    error_log("PE".json_encode($pe));
    try {
        $diff = $ps->diff($pe);
        $mins = $diff->i + $diff->h * 60;
    } catch(Exception $e) {
        error_log('diff :: '.$e->getMessage());
    }
    return $mins;
}

function mins2HM($mins) {

    $h = intdiv($mins,60);
    $m = $mins % 60;
    $res = '';
    if ($h > 0) $res .= $h.'h ';
    $res .= $m.'m';
    return $res;
}

function ajaxNewRBClaim() {

    global $DB, $TAGS, $KONSTANTS;

    error_log('ajaxNewClaim started');
    $reqs = ['BonusID','EntrantID','RestMins','ClaimTime','OdoReading'];
    foreach($reqs as $parm) {
        if (!isset($_REQUEST[$parm])) {
            error_log('missing '.$parm);
            echo('{"result":"missing '.$parm.'"}');
            return;
        }
    }

    $restBonusGroups = getSetting('restBonusGroups','RestBonuses');
    $restBonusCodes = bonusCodes($restBonusGroups);
    error_log('restBonusCodes=='.implode(',',$restBonusCodes).' from '.$restBonusGroups);
    if (!in_array($_REQUEST['BonusID'],$restBonusCodes)) {
        echo('{"result":"notrest"}');
        return;
    }

    // Definitely have an attempt at claiming a rest bonus now. Start claims don't count.

    error_log('ajaxNewClaim continuing');
    $restBonusStartGroup = getSetting('restBonusStartGroup','RB0');
    $startBonusCodes = bonusCodes($restBonusStartGroup);
    if (count($startBonusCodes) < 1) {
        echo('{"result":"error3"}');
        return;
    }

    // Fetch the most recent start claim
    $sql = "SELECT BonusID,ClaimTime,OdoReading FROM claims WHERE EntrantID=".$_REQUEST['EntrantID'];
    $sql .= " AND BonusID In (".inList($startBonusCodes).")";
    $sql .= "ORDER BY ClaimTime DESC";
    error_log($sql);
    $R = $DB->query($sql);
    if (!$claimstart = $R->fetchArray()) {
        echo('{"result":"nostart","error":"'.$TAGS['RB_nostart'][1].'"}');
        return;
    }
    error_log('Found RB start '.$claimstart['BonusID']);

    $gn = getValueFromDB("SELECT IfNull(GroupName,'') As GroupName FROM bonuses WHERE BonusID='".$_REQUEST['BonusID']."'","GroupName","");

    // Now fetch newer, repeat or mismatched RB claims
    $sql = "SELECT claims.BonusID,ClaimTime,OdoReading,IfNull(GroupName,'') As GroupName FROM claims";
    $sql .= " JOIN bonuses ON claims.BonusID=bonuses.BonusID";
    $sql .= " WHERE EntrantID=".$_REQUEST['EntrantID'];
    $sql .= " AND claims.BonusID In (".inList($restBonusCodes).")";
    $sql .= " AND (";
    $sql .= "       ClaimTime > '".$claimstart['ClaimTime']."'";
    $sql .= "       OR";
    $sql .= "       claims.BonusID='".$_REQUEST['BonusID']."'";
    $sql .= "       OR";
    $sql .= "       GroupName != '$gn'";
    $sql .= "     )";
    error_log($sql);


    $R = $DB->query($sql);
    if ($lastclaim = $R->fetchArray()) {

        $lt = [];
        preg_match('/<span[^>]*>([^<]+)/',logtime($lastclaim['ClaimTime']),$lt);

        $errmsg = '☹️ '.$claimstart['BonusID'].'=='.$lastclaim['BonusID'].' '.$lt[1];
        $errmsg .= ' @ '.$lastclaim['OdoReading'];
        echo('{"result":"claimed","error":"'.$errmsg.'"}');
        return;
    }

    // Is it being claimed too soon?
    $mins = minutesBetween($claimstart['ClaimTime'],$_REQUEST['ClaimTime']);
    error_log('mins = '.$mins);
    if ($_REQUEST['RestMins'] > $mins) {
        echo('{"result":"toosoon","error":"☹️'.$claimstart['BonusID'].'=='.$claimstart['ClaimTime']." (".mins2HM(intval($mins)).")".'"}');
        return;
    }


    // Ok, it's all good now so let's offer some warm comforting info to the judge
    $odokms = getValueFromDB("SELECT OdoKms FROM entrants WHERE EntrantID=".$_REQUEST['EntrantID'],"OdoKms",0);
	$rallyUsesKms = ($KONSTANTS['BasicDistanceUnit'] != $KONSTANTS['DistanceIsMiles']);
    $cm = calcCorrectedMiles($odokms,$claimstart['OdoReading'],$_REQUEST['OdoReading'],1);
    if ($rallyUsesKms != 0)
        $bdu = $TAGS['OdoKmsK'][0];
    else
        $bdu = $TAGS['OdoKmsM'][0];

    $delta = "&#916;";
    $info = $claimstart['BonusID'].'  '." $delta = ".mins2HM(intval($mins)).", $cm $bdu";
    echo('{"result":"ok","info": "'.$info.'"}');
}

if (isset($_REQUEST['c']) && $_REQUEST['c']=='rb')
    ajaxNewRBClaim();
?>