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

    global $DB, $TAGS;

    error_log('ajaxNewClaim started');
    if (!isset($_REQUEST['BonusID'])) {
        echo('{"result":"error1b"}');
        return;
    }
    if (!isset($_REQUEST['EntrantID'])) {
        echo('{"result":"error1e"}');
        return;
    }
    if (!isset($_REQUEST['RestMins'])) {
        echo('{"result":"error2m"}');
        return;
    }
    if (!isset($_REQUEST['ClaimTime'])) {
        echo('{"result":"error2t"}');
        return;
    }
    $restBonusGroups = getSetting('restBonusGroups','RestBonuses');
    $restBonusCodes = bonusCodes($restBonusGroups);
    error_log('restBonusCodes=='.implode(',',$restBonusCodes).' from '.$restBonusGroups);
    if (!in_array($_REQUEST['BonusID'],$restBonusCodes)) {
        echo('{"result":"notrest"}');
        return;
    }
    error_log('ajaxNewClaim continuing');
    $restBonusStartGroup = getSetting('restBonusStartGroup','RB0');
    $startBonusCodes = bonusCodes($restBonusStartGroup);
    if (count($startBonusCodes) < 1) {
        echo('{"result":"error3"}');
        return;
    }
    $sql = "SELECT BonusID,ClaimTime FROM claims WHERE EntrantID=".$_REQUEST['EntrantID'];
    $sql .= " AND BonusID In (".inList($startBonusCodes).")";
    $sql .= "ORDER BY ClaimTime DESC";
    error_log($sql);
    $R = $DB->query($sql);
    if (!$claimstart = $R->fetchArray()) {
        echo('{"result":"nostart","error":"'.$TAGS['RB_nostart'][1].'"}');
        return;
    }
    error_log('Found RB start '.$claimstart['BonusID']);
    $sql = "SELECT BonusID,ClaimTime FROM claims WHERE EntrantID=".$_REQUEST['EntrantID'];
    $sql .= " AND BonusID In (".inList($restBonusCodes).")";
    $sql .= " AND ClaimTime > '".$claimstart['ClaimTime']."'";
    error_log($sql);
    $R = $DB->query($sql);
    if ($lastclaim = $R->fetchArray()) {
        echo('{"result":"claimed","error":"'.$claimstart['BonusID'].'=='.$lastclaim['BonusID'].' '.$lastclaim['ClaimTime'].'"}');
        return;
    }
    $mins = minutesBetween($claimstart['ClaimTime'],$_REQUEST['ClaimTime']);
    error_log('mins = '.$mins);
    if ($_REQUEST['RestMins'] > $mins) {
        echo('{"result":"tooson","error":"'.$claimstart['BonusID'].'=='.$claimstart['ClaimTime']." (".mins2HM(intval($mins)).")".'"}');
        return;
    }
    echo('{"result":"ok"}');
}

if (isset($_REQUEST['c']) && $_REQUEST['c']=='rb')
    ajaxNewRBClaim();
?>