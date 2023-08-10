<?php
/**

quicklist.php - one-off for Michiel Buitenwerf, 12 Days

*/

require_once('common.php');

function show_quicklist() {

    global $DB, $KONSTANTS;

	$sql = "SELECT *,substr(RiderName,1,RiderPos-1) As RiderFirst";
	$sql .= ",substr(RiderName,RiderPos+1) As RiderLast";
	$sql .= " FROM (SELECT *,instr(RiderName,' ') As RiderPos FROM entrants) ";
    $sql .= " WHERE EntrantStatus <> ".$KONSTANTS['EntrantDNS']. " AND CorrectedMiles > 0";
    $sql .= " ORDER BY RiderLast,RiderFirst";



    ?><!DOCTYPE html>
    <html>
    <head>
    <title>MBList</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta http-equiv="refresh" content="30">
    <link rel="stylesheet" type="text/css" href="score.css">
    <style>
        th,td { text-align: center; padding: 0 1em 0 0; }
        main { margin: 0 auto 0 auto; }
        .name { text-align: left; }
    </style>
</head>
<body>
<main>
<h1>12 Days Euro, August 2023</h1>
<table>
    <thead>
        <tr>
            <th class="name">Rider</th>
            <th>Bonuses</th>
            <th>Countries</th>
            <th>Breaks</th>
            <th>Gas</th>
            <th>Clock</th>
            <th>Km</th>
        </tr>
    </thead>
    <tbody>
<?php
    error_log($sql);
    $R = $DB->query($sql);
    while ($rd = $R->fetchArray()) {
        $sql = "SELECT count(DISTINCT BonusID) AS rex FROM claims WHERE EntrantID=".$rd['EntrantID']. " AND Decision=0";
        $numBonuses = getValueFromDB($sql,"rex",-1);
        $sql = "SELECT count(DISTINCT substr(BonusID,1,2)) AS rex FROM claims WHERE EntrantID=".$rd['EntrantID']. " AND Decision=0 AND BonusID < '60'";
        $numCountries = getValueFromDB($sql,"rex",-1);
        $sql = "SELECT count(DISTINCT BonusID) AS rex FROM claims WHERE EntrantID=".$rd['EntrantID']. " AND Decision=0 AND BonusID LIKE '60%'";
        $numBreaks = getValueFromDB($sql,"rex",-1);
        $sql = "SELECT count(DISTINCT BonusID) AS rex FROM claims WHERE EntrantID=".$rd['EntrantID']. " AND Decision=0 AND BonusID LIKE '61%'";
        $numGas = getValueFromDB($sql,"rex",-1);
        $sql = "SELECT count(DISTINCT BonusID) AS rex FROM claims WHERE EntrantID=".$rd['EntrantID']. " AND Decision=0 AND BonusID LIKE '62%'";
        $numClock = getValueFromDB($sql,"rex",-1);
        echo('<tr><td class="name">'.$rd['RiderLast'].', '.$rd['RiderFirst'].'</td>');
        echo('<td>'.$numBonuses.'</td>');
        echo('<td>'.$numCountries.'</td>');
        echo('<td>'.$numBreaks.'</td>');
        echo('<td>'.$numGas.'</td>');
        echo('<td>'.$numClock.'</td>');
        echo('<td>'.$rd['CorrectedMiles'].'</td></tr>');
    }
?>
    </tbody>
</table>
</main>    
<?php
}

show_quicklist();

?>
