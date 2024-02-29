<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

/*
 * I B A U K   -   S C O R E M A S T E R
 *
 * I do post-rally reporting
 *
 * I am written for readability rather than efficiency, please keep me that way.
 *
 *
 * Copyright (c) 2024 Bob Stammers
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

$EXPORT_BONUS_SELECT = "SELECT bonuses.BonusID AS Bonus,bonuses.BriefDesc AS Name,IFNULL(bonusclaims.Claims,0) AS Claims,Points,Flags";
$EXPORT_BONUS_FILES = " FROM bonuses LEFT JOIN (SELECT BonusID,COUNT(DISTINCT EntrantID) AS Claims FROM claims GROUP BY BonusID) AS bonusclaims ON bonuses.BonusID=bonusclaims.BonusID;";

$combo_bonuses_array = [];

function buildCBA() {

    global $combo_bonuses_array,$DB;

    $sql = "SELECT ComboID,Bonuses FROM combinations";
    $R = $DB->query($sql);
    while ($rd = $R->fetchArray()) {
        $bonuses =explode(",",$rd['Bonuses']);
        for ($i = 0; $i < count($bonuses); $i++) {
            if (!isset($combo_bonuses_array[$bonuses[$i]])) {
                $combo_bonuses_array[$bonuses[$i]] = [];
            }
            array_push($combo_bonuses_array[$bonuses[$i]],$rd['ComboID']);
        }
    }
//    print_r($combo_bonuses_array);
}

function exportBonusesReport() {

    global $EXPORT_BONUS_SELECT, $EXPORT_BONUS_FILES, $DB, $combo_bonuses_array;

    buildCBA();
    $cats = [];
    $sql = "SELECT RallyTitle";
    for($i=1;$i<=9;$i++) {
        $sql .= ",Cat".$i."Label";
    }
    $cats = "";
    $hdrs = [];
    $sql .= " FROM rallyparams";
    $R = $DB->query($sql);
    if ($rd = $R->fetchArray()) {
        for($i=1;$i<=9;$i++) {
            if ($rd["Cat".$i."Label"] != "") {
                $cats .= ",bonuses.Cat".$i;
                $hdrs[$i] = $rd["Cat".$i."Label"];
            }
        }
    }
?>
<html>
<script src="https://bossanova.uk/jspreadsheet/v4/jexcel.js"></script>
<script src="https://jsuites.net/v4/jsuites.js"></script>
<link rel="stylesheet" href="https://bossanova.uk/jspreadsheet/v4/jexcel.css" type="text/css" />
<link rel="stylesheet" href="https://jsuites.net/v4/jsuites.css" type="text/css" />
 <?php
    echo('<h1>'.$rd['RallyTitle'].'</h1>');
    echo('<h2>Bonus analysis</h2>');
    echo('<p><button id="exportcsv">Save as CSV</button></p>');
    echo('<table id="bonusdump"><caption>'.$rd['RallyTitle'].'</caption><thead>');
    echo('<tr><th>Bonus</th><th>Name</th>');
    echo('<th>Claims</th><th>Points</th><th>Flags</th>');
    for($i=1;isset($hdrs[$i]);$i++) {
        echo('<th>'.$hdrs[$i].'</th>');
    }
    echo('<th>Combos</th>');
    echo('</tr></thead><tbody>');
    $sql = $EXPORT_BONUS_SELECT.$cats.$EXPORT_BONUS_FILES;
 //   print_r($hdrs);
 //   echo($sql);
    $R = $DB->query($sql);
    while($rd = $R->fetchArray()) {
        // Output grid line
        echo('<tr>');
//        print_r($rd);
        $nmax = (count($rd) / 2) - count($hdrs);
        for($i=0;$i < $nmax;$i++) {
            echo('<td>'.$rd[$i].'</td>');
        }
        for($i=1;$i<=count($hdrs);$i++) {
            $y = $rd[$nmax + $i - 1];
            $x = getValueFromDB("SELECT BriefDesc FROM categories WHERE Axis=".$i." AND Cat=".$y,"BriefDesc",$y);
            echo('<td>'.$x.'</td>');
        }
        echo('<td>');
        if (isset($combo_bonuses_array[$rd['Bonus']])) {
            echo(implode(" ",$combo_bonuses_array[$rd['Bonus']]).'</td>');
        }
        echo('</td>');
        echo('</tr>');
    }
    echo('</tbody></table>');
?>
<script>
    var table = jspreadsheet(document.getElementById('bonusdump'),{
        filters: true,
        includeHeadersOnDownload: true,
        editable: false,
        rowDrag: false,
        allowInsertRow: false,
        allowManualInsertRow: false,
        allowInsertColumn: false,
        allowManualInsertColumn: false,
        allowDeleteRow: false,
        allowDeleteColumn: false,
        csvFileName: 'bonuses',
    })
    document.getElementById('exportcsv').onclick = function() {table.download();}
</script>
<?php
}

exportBonusesReport();

?>
