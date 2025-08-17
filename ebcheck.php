<?php

$HOME_URL = "admin.php";
require_once('common.php');


function audit_ebclaims() {

    global $DB;

    startHtml("EBClaims audit","",true);
    $lastEmailID = -1;
    $lastClaimTime = '';
    $lastSubject = '';
    $nrex = 0;
    $dupes = 0;
    $skipped = 0;
    $sql = "SELECT ClaimTime,Subject,EmailID FROM ebclaims ORDER BY EmailID";
    $R = $DB->query($sql);
    echo("<p>");
    while ($rd = $R->fetcharray()) {
        $nrex++;
        if ($lastEmailID < 0) {
            $lastEmailID = $rd['EmailID'];
            $lastSubject = $rd['Subject'];
            $lastClaimTime = $rd['ClaimTime'];
            continue;
        }
        if ($rd['EmailID']==$lastEmailID) {
            $dupes++;
            continue;
        }
        $nextID = $lastEmailID + 1;
        if ($rd['EmailID'] != $nextID) {
            $skipped++;
            echo("#".$nextID." after ".$lastClaimTime." [".$lastSubject."] missing<br>");
        }
        $lastClaimTime = $rd['ClaimTime'];
        $lastSubject=$rd['Subject'];
        $lastEmailID=$rd['EmailID'];

    }
    echo("</p>");
    echo("<p>Number of claim records: ".$nrex."</p>");
    echo("<p>Number of duplicates: ".$dupes."</p>");
    echo("<p>Number skipped: ".$skipped."</p>");
}

if (isset($_REQUEST['rank'])) {
	rankEntrants(false);
	prgPicklist();
	exit;
}

audit_ebclaims();