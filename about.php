<?php

/*
 * I B A U K   -   S C O R E M A S T E R
 *
 * I merely provide info about the application / server
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


$PROGRAM = array("version" => "3.1.1",	"title"	=> "ScoreMaster");
/*
 *	2.0	25May18	Used live at BBR18
 *
 *				Bug fixes from BBR18
 *				EntrantStatus = Finisher changed from 2 to 8
 *				Scoring status to Finisher if was OK and CorrectedMiles > 0
 *				Multiple radio button groups of specials
 *				Autosuppress Team# in listings
 *				Certificate class
 *				Programmable certificate sequence
 *				Accept/Reject claim handling
 *				Include ExtraData with finisher export
 *	2.1	22Sep18	Live at Jorvic 18
 *
 *	2.2			Accept zero miles as finisher, after min/max checks
 *				Show entrant bonus arrays with same formatting as scoresheet
 *				Ability to reject combos; Special/combo rejections report title
 *		Issued to John Cunniffe
 *	2.2.1		Programmable admin menus
 *				Full display of entrant table with scorex and rejects
 *
 *	2.3	13Jun19	Post BBR19
 *				Breadcrumbs, new CSS, MIT licence, Tabnames
 *
 *	2.3.1		Post BBL19
 *				OdoScaleFactor SanityCheck, QuickList spacing, Ticksheet print font size
 *
 *	2.4			Pre Magic-12
 *				Major update
 *
 *	2.4.1		Post Magic-12
 *				parseInt(EntrantID) in scoring picklist
 *				BCMethod support
 *				Trap unsaved scorecard
 *				Average speed
 *
 *	2.5			Average speeds, Team, cloning, Team detection, wysiwyg editing, specials maintenance
 *				Themes, adhoc Entrant import
 *
 *	2.6	May20	Virtual rallies, claims capture, claims posting, 'must not' specials
 *
 *	2.6.1		First beta after refactor. Checklists not implemented, automated classes not implemented.
 *	2.6.1a		Plus classes maintenance
 *	2.6.2		Sent to Rick
 *	2.6.2a		First Rick update: scorecards/import/wizard buttons
 *	2.6.2b		Pre Jorvik mods
 *	2.7			Post Jorvik - claims rebuild, locking conflict traps
 *	2.7.1		Post Gerhard
 *  2.7.2		Invictus Tour live
 *  2.8			Post Invictus -> Lee
 *  2.8.1		Updated PHPMailer, PHPSpreadsheet
 *  2.9			Claims log
 *  3.0			Complete refactor
 *  3.1.1		urlencode bonus image filenames
 */

$MIT = <<<'EOT'
Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
EOT;

$HOME_URL = "admin.php";
require_once("common.php");

/*
 * convert the path supplied, good within the hosting environment, into a host absolute path
 */
function absolutePath($webfile)
{
	$basepath = dirname($_SERVER['SCRIPT_FILENAME']);
	$pos = strpos($basepath,'/');
	if ($pos === FALSE)
		$pathsep = '\\';
	else
		$pathsep = '/';
	if (substr($basepath,1,-1) != $pathsep && substr($webfile,0,1) != $pathsep)
		$basepath .= $pathsep;
	$basepath .= $webfile;
	return $basepath;
}

function showAbout()
{
	global $PROGRAM, $TAGS, $DBFILENAME, $DB, $KONSTANTS, $MIT;
	
	startHtml($TAGS['ttAbout'][0],'',false);
	
	
	$serveraddr = $_SERVER['HTTP_HOST'];

	if (isset($_SERVER['SERVER_ADDR']))
		$serveraddr = gethostbynamel($_SERVER['SERVER_ADDR']);
	if ($serveraddr=='')
		$serveraddr = $_SERVER['LOCAL_ADDR'];
	
	echo("\n<div id=\"helpabout\">\n");
	echo('<h1>'.$PROGRAM['title'].' v'.$PROGRAM['version'].'</h1>');
	echo('<p class="slogan">'.$TAGS['SMDesc'][1].'</p>');
	echo('<hr>');
	echo('<dl class="main">');
	if (is_array($serveraddr))
	{
		$serverdetail = '';
		foreach($serveraddr as $ip)
		{
			if ($serverdetail != '')
				$serverdetail .= ', ';
			$serverdetail .= $ip;
		}
		$serverdetail = implode(',',$serveraddr);
	}
	else
		$serverdetail = $serveraddr;
	echo('<dt title="'.$TAGS['abtHostname'][1].'">'.$TAGS['abtHostname'][0].'</dt><dd>'.php_uname('n').' [ '.$serverdetail.' ]</dd>');
	echo('<dt title="'.$TAGS['abtDatabase'][1].'">'.$TAGS['abtDatabase'][0].'</dt><dd>'.absolutePath($DBFILENAME).'</dd>');
	
	echo('<!-- </dl><hr>');
	echo('<dl class="techie">');
	echo('<dt title="'.$TAGS['abtOnlineDoc'][1].'">'.$TAGS['abtOnlineDoc'][0].'</dt>');

	echo('<dd>');
	echo('<span class="dox" title="'.$TAGS['abtOnlineDocs'][1].'">');
	echo('<a href="https://drive.google.com/drive/folders/1vTDJCPXaJ2ixyRa8yucFdHKbiMyTNSPh?usp=sharing" target="smdox">'.$TAGS['abtOnlineDocs'][0].'</a>');
	echo('</span>');
	echo('</dd>-->');

	echo('</dl><hr>');
	echo('<dl class="techie">');
	$dbversion = 0;
	if ($R = $DB->query("SELECT DBVersion,MilesKms FROM rallyparams"))
	{
		$rd = $R->fetchArray();
		$dbversion = $rd['DBVersion'];
	}
	echo('<dt title="'.$TAGS['abtDBVersion'][1].'">'.$TAGS['abtDBVersion'][0].'</dt><dd>'.$dbversion.'</dd>');
	
	echo('<dt title="'.$TAGS['abtHostOS'][1].'">'.$TAGS['abtHostOS'][0].'</dt><dd>'.php_uname('s').' [ '.php_uname('v').' ]</dd>');
	echo('<dt title="'.$TAGS['abtWebserver'][1].'">'.$TAGS['abtWebserver'][0].'</dt><dd>'.$_SERVER['SERVER_SOFTWARE'].'</dd>');
	echo('<dt title="'.$TAGS['abtPHP'][1].'">'.$TAGS['abtPHP'][0].'</dt><dd>'.phpversion().'</dd>');
	echo('<dt title="'.$TAGS['abtSQLite'][1].'">'.$TAGS['abtSQLite'][0].'</dt><dd>'.SQLite3::version()['versionString'].'</dd>');
	$mk = ($KONSTANTS['BasicDistanceUnit'] == $KONSTANTS['DistanceIsMiles'] ? 'miles' : 'kilometres');
	echo('<dt title="'.$TAGS['abtBasicDistance'][1].'">'.$TAGS['abtBasicDistance'][0].'</dt><dd>'.$mk.'</dd>');
	echo('<dt title="'.$TAGS['abtAuthor'][1].'">'.$TAGS['abtAuthor'][0].'</dt><dd>Bob Stammers &lt;webmaster@ironbutt.co.uk&gt; (IBA #51220)</dd>');
	echo('<dt title="'.$TAGS['abtInspired'][1].'">'.$TAGS['abtInspired'][0].'</dt><dd><span class="keep">Chris Kilner #40058</span>, <span class="keep">Steve Eversfield #169</span>, <span class="keep">Lee Edwards #59974</span>, <span class="keep">Robert Koeber #552</span>, <span class="keep">Graeme Dawson #40020</span>, <span class="keep">Peter Ihlo #576</span>, <span class="keep">Steve Westall #40092</span></dd>');
	echo('<dt title="'.$TAGS['abtLicence'][1].'">'.$TAGS['abtLicence'][0].'</dt><dd class="clickme" onclick="document.getElementById(\'mit\').className=\'show\';">MIT</dd>');
	echo('</dl>');
	echo('<p id="mit" class="hide">'.$MIT.'</p>');
	echo("</div> <!-- helpabout -->\n");
	
	if (isset($_REQUEST['?']))
		echo '<pre>' . var_export($_SERVER, true) . '</pre>';
}

showAbout();	
?>
