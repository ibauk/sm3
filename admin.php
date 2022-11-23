<?php
//error_reporting(E_ALL);
//ini_set('display_errors', 1);

/*
 * I B A U K   -   S C O R E M A S T E R
 *
 * I handle the rally scoring administration
 *
 * I am written for readability rather than efficiency, please keep me that way.
 *
 *
 * Copyright (c) 2019 Bob Stammers
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

function fetchCertificate($EntrantID,$Class)
{
	global $DB, $TAGS, $KONSTANTS;
	if ($EntrantID == '')
		$EntrantID = 0;
	if ($Class == '')
		$Class = 0;
	$sql = "SELECT * FROM certificates WHERE EntrantID=";
	$R = $DB->query($sql.$EntrantID." AND Class=$Class");
	$rd = $R->fetchArray();
	return ['html'=>$rd['html'],'css'=>$rd['css'],'Title'=>$rd['Title'],'Class'=>$rd['Class']];
	
}

function saveCertificate()
{
	global $DB, $TAGS, $KONSTANTS;
	
	//var_dump($_REQUEST);
	$R = $DB->query("SELECT Count(*) As Rex FROM certificates WHERE EntrantID=".$_REQUEST['EntrantID']." AND Class=".$_REQUEST['Class']);
	$rd = $R->fetchArray();
	$adding = $rd['Rex'] < 1;
	
	if ($adding)
	{
		$sql = "INSERT INTO certificates(EntrantID,Class,html,css,Title) VALUES(";
		$sql .= $_REQUEST['EntrantID'];
		$sql .= ",";
		$sql .= $_REQUEST['Class'];
		$sql .= ",'";
		$sql .= $DB->escapeString($_REQUEST['certhtml'])."'";
		$sql .= ",'";
		$sql .= $DB->escapeString($_REQUEST['certcss'])."'";
		$sql .= ",'";
		$sql .= $DB->escapeString($_REQUEST['Title'])."'";
		$sql .= ')';
	}
	else
	{
		$sql = "UPDATE certificates SET html='".$DB->escapeString($_REQUEST['certhtml'])."'";
		$sql .= ",css='".$DB->escapeString($_REQUEST['certcss'])."'";
		$sql .= ",Title='".$DB->escapeString($_REQUEST['Title'])."'";
		$sql .= " WHERE EntrantID=".$_REQUEST['EntrantID']." AND Class=".$_REQUEST['Class'];
	}
	//echo($sql."<hr>");
	if (!$DB->exec($sql)) {
		dberror();
		exit;
	}
	if ($DB->lastErrorCode() <> 0)
		echo($DB->lastErrorCode().' == '.$DB->lastErrorMsg().'<br>'.$sql.'<hr>');
	
}

function editCertificate()
{
	global $DB, $TAGS, $KONSTANTS;

	$EntrantID = (isset($_REQUEST['EntrantID']) ? intval($_REQUEST['EntrantID']) : 0);
	$class = 0;
	if (isset($_REQUEST['Class']))
		$class = $_REQUEST['Class'];
	$rd = fetchCertificate($EntrantID,$class);
	startHtml($TAGS['ttSetup'][0]);
	//var_dump($rd);
	echo('<p>'.$TAGS['CertExplainer'][0].'<br>');
	echo($TAGS['CertExplainer'][1].'</p>');
	echo('<form id="certform" method="post" action="admin.php">');
	echo('<input type="hidden" name="c" value="editcert">');
	echo('<input type="hidden" name="EntrantID" value="'.$EntrantID.'">');
	
	$MC = getValueFromDB("SELECT count(*) As Rex FROM certificates WHERE EntrantID=$EntrantID","Rex",0);
	if ($MC > 1)
	{
		$sql = "SELECT Class,Title FROM certificates WHERE EntrantID=$EntrantID ORDER BY Class";
		$R = $DB->query($sql);
		if ($DB->lastErrorCode() <> 0)
			echo($DB->lastErrorCode().' == '.$DB->lastErrorMsg().'<br>'.$sql.'<hr>');
		$pv = "document.getElementById('Class').value=this.value;";
		$pv .= "var T=this.options[this.selectedIndex].text;";
		$pv .= "document.getElementById('Title').value=T.split(' - ')[1];";
		$pv .= "document.getElementById('certcss').disabled=true;";
		$pv .= "document.getElementById('certhtml').disabled=true;";
		$pv .= "document.getElementById('fetchcert').disabled=false;";
		$pv .= "document.getElementById('fetchcert').click();";
	
		echo('<select onchange="'.$pv.'">');
		while ($rrd = $R->fetchArray())
		{
			echo('<option value="'.$rrd['Class'].'"');
			if ($rrd['Class'] == $rd['Class']) {
				echo(' selected ');
			}
			echo('>'.$rrd['Class'].' - '.$rrd['Title'].'</option>');
		}
		echo('</select> ');
	}
	
	echo('<label for="Class">'.$TAGS['Class'][0].' </label>');
	$x = ' onchange="document.getElementById('."'".'fetchcert'."'".').disabled=false;"';
	echo('<input title="'.$TAGS['Class'][1].'" type="number" name="Class" id="Class" value="'.$class.'" '.$x.'> ');
	
	echo('<input type="submit" disabled id="fetchcert" name="fetchcert" value="'.$TAGS['FetchCert'][0].'" title="'.$TAGS['FetchCert'][1].'"> ');
	echo('<label for="Title">'.$TAGS['CertTitle'][0].' </label>');
	echo('<input title="'.$TAGS['CertTitle'][1].'" type="text" name="Title" id="Title" value="'.$rd['Title'].'" > ');

	
	echo('<div class="tabs_area" style="display:inherit"><ul id="tabs">');
	echo('<li><a href="#tab_html">html</a></li>');
	echo('<li><a href="#tab_css">css</a></li>');
	echo('</ul></div>');
	
	
//	echo('<br>html<br>');
	echo('<fieldset class="tabContent" id="tab_html"><legend></legend>');
	echo("<textarea form='certform' name='certhtml' id='certhtml' contenteditable='true' style='height:20em; width:100%;' oninput='enableSaveButton();'>");
	echo($rd['html']);
	echo('</textarea>');
	echo('</fieldset>');
	
//	echo('<br><br>css<br>');
	echo('<fieldset class="tabContent" id="tab_css"><legend></legend>');
	echo("<textarea form='certform' name='certcss' id='certcss' contenteditable='true' style='height:20em; width:100%;' oninput='enableSaveButton();'>");
	echo($rd['css']);
	echo('</textarea>');
	echo('</fieldset>');

	
	
	echo('<input type="submit" disabled name="savecert" value="'.$TAGS['RecordSaved'][0].'" id="savedata" data-altvalue="'.$TAGS['SaveCertificate'][0].'" title="'.$TAGS['SaveCertificate'][1].'"> ');

	$pv = "var W=window.open('','preview');";
	$pv .= "W.document.write('<html><head><style>'";
	$pv .= "+document.getElementById('certcss').value+'</style></head>'";
	$pv .= "+'<body><div class=certificate>'+document.getElementById('certhtml').value+'</div></body></html>');";
	$pv .= "W.document.close();";
	
	echo('<input type="button" onclick="'.$pv.'" value="'.$TAGS['PreviewCert'][0].'" title="'.$TAGS['PreviewCert'][1].'"> ');

	echo('</form>');
	//showFooter();
}



function show_menu($menuid)
{
	global $TAGS,$DB,$HOME_URL;
	
	$R = $DB->query("SELECT * FROM menus WHERE menuid='$menuid'");
	if (!($rd = $R->fetchArray()))
	{
		return; // Should complain
	}
	
	
	$menulbl = $rd['menulbl'];
	$functions = explode(',',$rd['menufuncs']);
	//showNav();
	echo('<div id="adminMM">');
	echo('<h4 title="'.$TAGS[$menulbl][1].'">'.$TAGS[$menulbl][0].'</h4>');
	echo('<ul class="menulist">');
	
	foreach($functions as $f)
	{
		if ($f == '')
		{
			continue;
		}
		$R = $DB->query("SELECT * FROM functions WHERE functionid=$f");
		if (!($rd = $R->fetchArray()))
		{
			continue;
		}
		echo('<li title="'.$TAGS[$rd['menulbl']][1].'"');
		if (!is_null($rd['onclick']))
		{
			$x = $rd['onclick'];
			echo(' onclick="'.$x.'"');
		}
		echo('>');
		echo('<a href="'.$rd['url']);
		echo('">'.$TAGS[$rd['menulbl']][0].'</a>');
		echo('</li>');
		
	}
	echo('</ul>');
	//show_menu_taglist();
	echo('</div>');
	showFooter();
	exit;
	
	
}

function show_tagmenu($tag)
{
	global $TAGS,$DB;
	

	echo('<div id="adminMM">');
	echo('<h4 title="'.$TAGS['AdmShowTagMatches'][1].$tag.'">'.$TAGS['AdmShowTagMatches'][0].$tag.'</h4>');
	echo('<ul class="menulist">');
	$R = $DB->query("SELECT * FROM functions");
	while ($rd = $R->fetchArray())
	{
		$taglist = explode(',',$rd['Tags']);
		//var_dump($taglist);
		if (!in_array($tag,$taglist))
			continue;
		echo('<li title="'.$TAGS[$rd['menulbl']][1].'"');
		if (!is_null($rd['onclick']))
		{
			$x = $rd['onclick'];
			echo(' onclick="'.$x.'"');
		}
		echo('>');
		echo('<a href="'.$rd['url'].'">'.$TAGS[$rd['menulbl']][0].'</a>');
		echo('</li>');
		
	}
	echo('</ul>');
	//show_menu_taglist();
	echo('</div>');
	showFooter();
	exit;
	
	
}

function show_theme_chooser()
{
	global $TAGS, $DB;
	
	$theme = getValueFromDB("SELECT Theme FROM rallyparams","Theme","default");
	$themes = [];
	$R = $DB->query("SELECT Theme,css FROM themes");
	while ($rd = $R->fetchArray())
		$themes[$rd['Theme']] = $rd['css'];
	echo('<div style="display:none;" id="themes">'.json_encode($themes).'</div>');
?>
<script>
function applyTheme() {
	let themes = JSON.parse(document.querySelector('#themes').innerText);
	let theme = document.querySelector('#theme').value;
	
	for (let sheeti= 0; sheeti<document.styleSheets.length; sheeti++) {
		let sheet= document.styleSheets[sheeti];
		let rules= ('cssRules' in sheet)? sheet.cssRules : sheet.rules;
		for (let rulei= 0; rulei<rules.length; rulei++) {
			let rule= rules[rulei];
			if (rule.selectorText==':root') 
				document.styleSheets[sheeti].rules[rulei].style.cssText=themes[theme];
		}
    }
	
}
</script>
<?php
	
	echo('<form method="post" action="admin.php">');
	echo('<input type="hidden" name="c" value="applytheme">'."\n");
	echo('<span class="vlabel"><label for="theme">'.$TAGS['ThemeLit'][0].'</label> ');
	echo('<select id="theme" name="theme" onchange="applyTheme();">');
	foreach ($themes as $t => $v) {
		echo("\n".'<option value="'.$t.'"');
		if ($t==$theme)
			echo(" selected");
		echo('>');
		echo($t.'</option>');
	}
	echo('</select> ');
	echo('<input type="submit" value="'.$TAGS['ThemeApplyLit'][0].'">');
	echo('</form>');
?>
<hr>
<h4>Lorem ipsum dolor sit amet, consectetur adipiscing elit,</h4>
<div id="adminMM">
<ul>
<li><a href="#">aaaaa</a></li>
<li><a href="#">bbbbb</a></li>
<li><a href="#">ccccc</a></li>
</ul>
</div>
<ul  id="tabs">
<li><a href="#">aaa</a></li>
<li><a href="#">bbb</a></li>
</ul>
<fieldset class="tabContent">
<label>Lorem ipsum</label> <input type="text" value="123"><br>
<label>dolor sit amet</label> <select><option selected>aaaa</option><option>bbbb</option></select><br>
<label>date stamp</label> <input type="date"><br>
</fieldset>
<div>
</div>
<?php	
}

function applyTheme()
{
	global $TAGS, $DB;

	if (!isset($_REQUEST['theme']))
		return;
	
	$sql = "UPDATE rallyparams SET Theme='".$DB->escapeString($_REQUEST['theme'])."'";
	if (!$DB->exec($sql)) {
		dberror();
		exit;
	}
	
}

function startAdminOutput() {

	global $TAGS;

	startHtml($TAGS['ttAdminMenu'][0],'<a href="about.php" class="techie" title="'.$TAGS['HelpAbout'][1].'">'.$TAGS['HelpAbout'][0].'</a>',rally_params_established());

}
//var_dump($_REQUEST);

if (isset($_REQUEST['c']) && $_REQUEST['c']=='rank') {
	rankEntrants(false);
	include("entrants.php");
	listEntrants('EntrantStatus DESC,FinishPosition');
	exit;
}

if (isset($_REQUEST['savecert']))
	saveCertificate();

if (isset($_REQUEST['c']) && $_REQUEST['c']=='editcert') {
	editCertificate();
	exit;
}

if (isset($_REQUEST['c']) && $_REQUEST['c']=='applytheme' && isset($_REQUEST['theme'])) 
	applyTheme();

startAdminOutput();

if (isset($_REQUEST['c'])) {
	switch($_REQUEST['c']) {
		case 'applytheme':
			if (isset($_REQUEST['theme']))
				show_menu('admin');
			break;
		case 'entrants':
			show_menu('entrant');
			break;
		case 'bonus':
			show_menu('bonus');
			break;
		/*
		case 'offerzap':
			showInitialisationOffer();
			break;
		*/
		case 'themes':
			show_theme_chooser();
			break;
		default:
			show_menu('admin');
	}
	
} else if (isset($_REQUEST['menu']))
	show_menu($_REQUEST['menu']);
else if (isset($_REQUEST['tag']))
	show_tagmenu($_REQUEST['tag']);
else if (rally_params_established()) 
	if (entrantsPresent() < 1 || bonusesPresent() < 1)
		show_menu('setup');
	else
		show_menu('admin');
else {
	include("setup.php");
	exit;
};


?>