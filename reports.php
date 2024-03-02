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
$cats = "";
$hdrs = [];
$rt = "";
$combos_scored_array = [];

function buildComboScores()
{

    global $DB, $combos_scored_array;

    $sql = "SELECT CombosTicked FROM entrants";
    $R = $DB->query($sql);
    while ($rd = $R->fetchArray()) {
        $B = explode(",", $rd['CombosTicked']);
        for ($i = 0; $i < count($B); $i++) {
            if (!isset($combos_scored_array[$B[$i]])) {
                $combos_scored_array[$B[$i]] = 1;
            } else {
                $combos_scored_array[$B[$i]]++;
            }
        }
    }
}
function buildCats()
{

    global $DB, $rt, $cats, $hdrs;

    $sql = "SELECT RallyTitle";
    for ($i = 1; $i <= 9; $i++) {
        $sql .= ",Cat" . $i . "Label";
    }
    $cats = "";
    $hdrs = [];
    $sql .= " FROM rallyparams";
    $R = $DB->query($sql);
    if ($rd = $R->fetchArray()) {
        for ($i = 1; $i <= 9; $i++) {
            if ($rd["Cat" . $i . "Label"] != "") {
                $cats .= ",Cat" . $i;
                $hdrs[$i] = $rd["Cat" . $i . "Label"];
            }
        }
        $rt = $rd['RallyTitle'];
    }
}
function buildCBA()
{

    global $combo_bonuses_array, $DB;

    $sql = "SELECT ComboID,Bonuses FROM combinations";
    $R = $DB->query($sql);
    while ($rd = $R->fetchArray()) {
        $bonuses = explode(",", $rd['Bonuses']);
        for ($i = 0; $i < count($bonuses); $i++) {
            if (!isset($combo_bonuses_array[$bonuses[$i]])) {
                $combo_bonuses_array[$bonuses[$i]] = [];
            }
            array_push($combo_bonuses_array[$bonuses[$i]], $rd['ComboID']);
        }
    }
    //    print_r($combo_bonuses_array);



}

function exportBonusesReport()
{

    global $EXPORT_BONUS_SELECT, $EXPORT_BONUS_FILES, $DB, $combo_bonuses_array, $cats, $hdrs, $rt;

    buildCBA();
    buildCats();
?>
    <html>
    <script src="https://bossanova.uk/jspreadsheet/v4/jexcel.js"></script>
    <script src="https://jsuites.net/v4/jsuites.js"></script>
    <link rel="stylesheet" href="https://bossanova.uk/jspreadsheet/v4/jexcel.css" type="text/css" />
    <link rel="stylesheet" href="https://jsuites.net/v4/jsuites.css" type="text/css" />
    <?php
    echo ('<h1>' . $rt . '</h1>');
    echo ('<h2>Bonus analysis</h2>');
    echo ('<p><button id="exportcsv">Save as CSV</button>  Flags: ');
    echo ('<strong>A</strong>lert - <strong>B</strong>ike in photo - <strong>D</strong>aylight only - ');
    echo ('<strong>F</strong>ace in photo - <strong>N</strong>ight only - <strong>R</strong>estricted - ');
    echo ('<strong>T</strong>icket/receipt</p>');
    echo ('<table id="bonusdump"><caption>' . $rt . '</caption><thead>');
    echo ('<tr><th>Bonus</th><th>Name</th>');
    echo ('<th>Claims</th><th>Points</th><th>Flags</th>');
    for ($i = 1; isset($hdrs[$i]); $i++) {
        echo ('<th>' . $hdrs[$i] . '</th>');
    }
    echo ('<th>Combos</th>');
    echo ('</tr></thead><tbody>');
    $sql = $EXPORT_BONUS_SELECT . $cats . $EXPORT_BONUS_FILES;
    //   print_r($hdrs);
    //   echo($sql);
    $R = $DB->query($sql);
    while ($rd = $R->fetchArray()) {
        // Output grid line
        echo ('<tr>');
        //        print_r($rd);
        $nmax = (count($rd) / 2) - count($hdrs);
        for ($i = 0; $i < $nmax; $i++) {
            echo ('<td>' . $rd[$i] . '</td>');
        }
        for ($i = 1; $i <= count($hdrs); $i++) {
            $y = $rd[$nmax + $i - 1];
            $x = getValueFromDB("SELECT BriefDesc FROM categories WHERE Axis=" . $i . " AND Cat=" . $y, "BriefDesc", $y);
            echo ('<td>' . $x . '</td>');
        }
        echo ('<td>');
        if (isset($combo_bonuses_array[$rd['Bonus']])) {
            echo (implode(" ", $combo_bonuses_array[$rd['Bonus']]) . '</td>');
        }
        echo ('</td>');
        echo ('</tr>');
    }
    echo ('</tbody></table>');
    ?>
    <script>
        var table = jspreadsheet(document.getElementById('bonusdump'), {
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
        document.getElementById('exportcsv').onclick = function() {
            table.download();
        }
    </script>
<?php
}

function exportCombosReport()
{

    global $DB, $rt, $combos_scored_array;

    buildCats();
    buildComboScores();
?>
    <html>
    <script src="https://bossanova.uk/jspreadsheet/v4/jexcel.js"></script>
    <script src="https://jsuites.net/v4/jsuites.js"></script>
    <link rel="stylesheet" href="https://bossanova.uk/jspreadsheet/v4/jexcel.css" type="text/css" />
    <link rel="stylesheet" href="https://jsuites.net/v4/jsuites.css" type="text/css" />
    <?php
    echo ('<h1>' . $rt . '</h1>');
    echo ('<h2>Combos analysis</h2>');
    echo ('<table id="combodump">');
    echo ('<thead><tr>');
    echo ('<th>Combo</th><th>Name</th><th>Points</th><th>Bonuses</th><th>Needed</th><th>Scored</th>');
    echo ('</tr></thead><tbody>');
    $sql = "SELECT ComboID AS Combo, BriefDesc AS Name,ScorePoints AS Points,Bonuses,MinimumTicks AS Needed";
    $sql .= " FROM combinations ORDER BY ComboID";
    $R = $DB->query($sql);
    while ($rd = $R->fetchArray()) {
        if ($rd['Combo'] == 'ZZZ') continue;
        echo ('<tr>');
        echo ('<td>' . $rd['Combo'] . '</td>');
        echo ('<td>' . $rd['Name'] . '</td>');
        echo ('<td>' . $rd['Points'] . '</td>');
        echo ('<td>' . $rd['Bonuses'] . '</td>');
        $nmax = count(explode(",", $rd['Bonuses']));
        $nmin = ($rd['Needed'] > 0 ? $rd['Needed'] : $nmax);
        echo ('<td>' . $nmin . ' / ' . $nmax . '</td>');
        echo ('<td>');
        if (isset($combos_scored_array[$rd['Combo']])) {
            echo ($combos_scored_array[$rd['Combo']]);
        }
        echo ('</td>');
        echo ('</tr>');
    }
    echo ('</tbody></table>');
    ?>
    <script>
        var table = jspreadsheet(document.getElementById('combodump'), {
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
            csvFileName: 'combos',
        })
        document.getElementById('exportcsv').onclick = function() {
            table.download();
        }
    </script>
<?php

}
if (isset($_REQUEST['ca']))
    exportCombosReport();
else
    exportBonusesReport();
?>