<?php
//error_reporting(E_ALL);
//ini_set('display_errors', 1);

/*
 * I B A U K   -   S C O R E M A S T E R
 *
 * I handle [F1] help
 *
 * I am written for readability rather than efficiency, please keep me that way.
 *
 *
 * Copyright (c) 2020 Bob Stammers
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


$HOME_URL = "showhelp.php";

require_once('common.php');
require_once('Parsedown.php');

function getFileText($topic)
{
    global $KONSTANTS;

    $filename = $KONSTANTS['doxpath'].DIRECTORY_SEPARATOR.$topic.'.hlp';
    if (!file_exists($filename))
        return '!!! '.$filename;

    $md = file_get_contents($filename);
    $pd = new Parsedown();
    return $pd->text($md);

}

function modifyHtml($html)
{
    global $KONSTANTS;

    return str_replace('src="./','src="'.$KONSTANTS['doxpath'].DIRECTORY_SEPARATOR,str_replace('<a href="help:','<a href="showhelp.php?topic=',$html));
}

function showhelptopic($topic)
{
    global $KONSTANTS, $TAGS;

    startHelp('<a href="about.php" class="techie" title="'.$TAGS['HelpAbout'][1].'">'.$TAGS['HelpAbout'][0].'</a>');
    echo('<div class="currenttopic">');
    if ($topic != 'index') {
        $html = getFileText('helpindex');
        echo(modifyHtml($html));
    }
    $html = getFileText($topic);
    echo(modifyHtml($html));
    echo('</div>');
}

function startHelp($otherInfo) {

	global $DB, $TAGS, $KONSTANTS, $HTML_STARTED;
	
	if (isset($HTML_STARTED) && $HTML_STARTED)
		return;
	
	$HTML_STARTED = true;
	
	$R = $DB->query('SELECT * FROM rallyparams');
	$rd = $R->fetchArray();
	
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php echo('<title>Help</title>'); ?>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<link rel="stylesheet" type="text/css" href="reboot.css?ver=<?= filemtime('reboot.css')?>">
<link rel="stylesheet" type="text/css" href="score.css?ver=<?= filemtime('score.css')?>">
<?php echo('<style>:root {'.getThemeCSS($rd['Theme']).'}</style>');?>
<script src="custom.js?ver=<?= filemtime('custom.js')?>" defer></script>
<script src="score.js?ver=<?= filemtime('score.js')?>" defer></script>
<script src="recalc.js?ver=<?= filemtime('recalc.js')?>" defer></script>
</head>
<body onload="bodyLoaded();">
<div id="header">
<?php	
	
	echo('&nbsp; <span id="hdrOtherInfo">'.$otherInfo.'</span>');
	echo("\r\n</div>\r\n");
}



if (isset($_REQUEST['topic']))
    showhelptopic($_REQUEST['topic']);
else
    showhelptopic('index');
?>
