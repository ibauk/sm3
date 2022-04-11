<?php

/*
 * I B A U K   -   S C O R E M A S T E R
 *
 * I handle imports of data from spreadsheets
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

$debuglog = TRUE;
$HOME_URL = "admin.php?c=entrants";

require_once("common.php");

$target_dir = $KONSTANTS['UPLOADS_FOLDER']; // './uploads/';
$upload_state = '';

$TYPE_ENTRANTS = 0;
$TYPE_BONUSES = 1;
$TYPE_COMBOS = 2;

//print_r($_REQUEST);

// These are defaults but, let's face it, there's never going to be a need to override
$IMPORTSPEC['xlsname']	= "import.xls";
$IMPORTSPEC['whichsheet']	= 0;
$IMPORTSPEC['FirstDataRow']	= 2;
$IMPORTSPEC['type'] = (isset($_REQUEST['type']) ? $_REQUEST['type'] : $TYPE_ENTRANTS);

// Declare psuedo fields here then load from database schema
$IGNORE_COLUMN = 'zzzzzzz';
$ENTRANT_FIELDS = [$IGNORE_COLUMN=>'ignore','RiderLast'=>'RiderLast','PillionLast'=>'PillionLast','NoKFirst'=>'NoKFirst','NoKLast'=>'NoKLast'];
$BONUS_FIELDS = [$IGNORE_COLUMN=>'ignore'];
$COMBO_FIELDS = [$IGNORE_COLUMN=>'ignore'];

$sql = "SELECT MilesKms, HostCountry FROM rallyparams";
$R = $DB->query($sql);
$rd = $R->fetchArray();

$IMPORTSPEC['default']['OdoKms'] = $rd['MilesKms'];
$IMPORTSPEC['default']['Country'] = $rd['HostCountry'];


// Load list of templates
$SPECFILES = [];
$sql = "SELECT * FROM importspecs WHERE importType=".$IMPORTSPEC['type']." ORDER BY specid";
$R = $DB->query($sql);
while ($rd = $R->fetchArray()) {
	$SPECFILES[$rd['specid']] = $rd['specTitle'];
}

// Loadup PhpSpreadsheet
require_once("vendor".DIRECTORY_SEPARATOR."autoload.php");
use PhpOffice\PhpSpreadsheet\Spreadsheet;


function buildList($lst,$itm)
{
	if ($lst != '')
		$res = $lst.','.$itm;
	else
		$res = $itm;
	return $res;
}



function cleanBikename($bike)
{
	$words = explode(' ',$bike);
	for ($i = 0; $i < sizeof($words); $i++)
	{
		$wk = '';
		if (knownWord($words[$i],$wk))
			$words[$i] = $wk;
		else if (preg_match('/\\d/', $words[$i]) > 0)
			$words[$i] = strtoupper($words[$i]);
		else
			$words[$i] = properName(strtolower($words[$i]));
	}
	return implode(' ',$words);
}

function extendBonusFields() {

	global $DB, $BONUS_FIELDS;

	$R = $DB->query("PRAGMA table_info(bonuses)");
	while($rd = $R->fetchArray())
		$BONUS_FIELDS[$rd['name']] = $rd['name'];
	asort($BONUS_FIELDS);
}

function extendComboFields() {

	global $DB, $COMBO_FIELDS;

	$R = $DB->query("PRAGMA table_info(combinations)");
	while($rd = $R->fetchArray())
		$COMBO_FIELDS[$rd['name']] = $rd['name'];
	asort($COMBO_FIELDS);
}

function extendEntrantFields() {
	
	global $DB, $ENTRANT_FIELDS;

	$R = $DB->query("PRAGMA table_info(entrants)");
	while($rd = $R->fetchArray()) {
		$ENTRANT_FIELDS[$rd['name']] = $rd['name'];
	}
	asort($ENTRANT_FIELDS);
}


function getMergeCols($sheet,$row,$colspec,$sep = ' ')
// Extract and return the contents of one or more cells
{
	if ($colspec === NULL)
		return '';
	$cols = explode(':',$colspec);
	$res = '';
	for ($i = 0; $i < sizeof($cols); $i++)
	{
		if ($res <> '') $res .= $sep;
		//echo("  C=$cols[$i],R=$row  ");
		
		// PhpSpreadsheet uses columns starting at 1
		// PHPExcel (deprecated) used columns starting at 0
		// Need to add 1 to column values
		$res .= $sheet->getCellByColumnAndRow($cols[$i]+1,$row)->getValue();
	}
	return trim($res);
}

function getNameFields($sheet,$row,$namelabels)
{
	global $IMPORTSPEC;
	
	$res = ['',''];
	if (isset($IMPORTSPEC['cols'][$namelabels[0]])) {
		$res[0] = getMergeCols($sheet,$row,$IMPORTSPEC['cols'][$namelabels[0]]);
		$res[1] = explode(' ',$res[0])[0].' ';
		$res[0] .= ' ';
	} elseif (isset($IMPORTSPEC['cols'][$namelabels[1]]) && isset($IMPORTSPEC['cols'][$namelabels[2]])) {
		$res[0] = getMergeCols($sheet,$row,$IMPORTSPEC['cols'][$namelabels[1]].':'.$IMPORTSPEC['cols'][$namelabels[2]]);
		$res[1] = getMergeCols($sheet,$row,$IMPORTSPEC['cols'][$namelabels[1]]);
	}
	else
		;//var_dump($IMPORTSPEC);
	return $res;

}


function knownWord($w,&$formattedword)
{
	global $KNOWN_BIKE_WORDS;
	
	for ($i = 0; $i < sizeof($KNOWN_BIKE_WORDS); $i++)
		if (strtolower($w)==strtolower($KNOWN_BIKE_WORDS[$i]))
		{
			$formattedword = $KNOWN_BIKE_WORDS[$i];
			return true;
		}
	return false;
}


function loadSpecs()
{
	global $DB,$IMPORTSPEC,$TAGS;

	if (!isset($_REQUEST['specfile']))
		return;
	
	$sql = "SELECT * FROM importspecs WHERE specid='".$_REQUEST['specfile']."'";
	$R = $DB->query($sql);
	if (!$rd =$R->fetchArray())
		return;
		//die($TAGS['xlsNoSpecfile'][1]);
	

	try {
		eval($rd['fieldSpecs']);
		
		if (isset($_REQUEST['hdrs']))
			$IMPORTSPEC['FirstDataRow'] = intval($_REQUEST['hdrs']) + 1;
	} catch (Exception $e) {
		die("HELLO !!! ".$e->getMessage());
	}
	
}

function loadSpreadsheet()
{
	global $DB,$TAGS,$IMPORTSPEC,$KONSTANTS,$target_dir,$TYPE_BONUSES,$TYPE_COMBOS,$TYPE_ENTRANTS;

	// Load the relevant specs

	loadSpecs();
	
	if (isset($_REQUEST['colmaps']))
		replaceColMaps();



	//Now we're doing it - load the spreadsheet

	startHtml($TAGS['ttImport'][0],$TAGS['xlsImporting'][0]);


	$sheet = openWorksheet();
	$maxrow = $sheet->getHighestRow();
	$row = $IMPORTSPEC['FirstDataRow'];  // Skip the column headers
	$nrows = 0;

	echo("<p>".$TAGS['xlsImporting'][1]."</p>");

	switch($IMPORTSPEC['type']) {
		case $TYPE_BONUSES:
			$tablename = 'bonuses';
			break;
		case $TYPE_COMBOS:
			$tablename = 'combinations';
			break;
		case $TYPE_ENTRANTS:
		default:
			$tablename = 'entrants';
	}
	
	// Check for overwriting
	$sql = "SELECT Count(*) AS Rex FROM $tablename";
	$R = $DB->query($sql);
	$rr = $R->fetchArray();
	if ($rr['Rex'] > 0) {
		if (!isset($_REQUEST['force'])) {
			die($TAGS['xlsNotEmpty'][1]);
			exit;
		}
	}
	
	// All good now
	
	$R = $DB->query("PRAGMA table_info($tablename)");
	while($rd = $R->fetchArray()) {
		$fldval[$rd['name']] = $rd['dflt_value']; // Text values will come with single quotes
		$fldtyp[$rd['name']] = $rd['type'];
	}
	
	// These fields need handling beyond simple copying
	$specialfields = ['RiderName','RiderLast','RiderFirst',
						'PillionName','PillionLast','PillionFirst',
						'NoKName','NoKLast','NoKFirst',
						'Bike','Make','Model','BikeReg'
					];
	
	if (!$DB->exec("BEGIN IMMEDIATE TRANSACTION")) {
		dberror();
		exit;
	}
	$DB->exec("DELETE FROM $tablename");

	$SqlBuilt = FALSE;

	echo('<p class="techie">');

	$row = $row - 1;  // Step back one so I can bump below

	while ($row++ <= $maxrow) {
		if (isset($IMPORTSPEC['cols']['EntrantID'])) {
			$entrantid = getMergeCols($sheet,$row,$IMPORTSPEC['cols']['EntrantID']);
			if ($entrantid == '')
				break;				// Blank line so all done now
			if ($entrantid == 'X')	// Entrant flagged as withdrawn for whatever reason
				continue;
		} else
			$entrantid = '';
			
		// Should I be rejecting this entry?
		if (isset($IMPORTSPEC['reject']))
			foreach($IMPORTSPEC['reject'] as $col => $re) {
				$val = getMergeCols($sheet,$row,$col);
				if (preg_match($re,$val) === 1 ? TRUE : FALSE)
					continue 2; // Move to next entry row
			}

		// Am I positively selecting things?
		if (isset($IMPORTSPEC['select'])) {
			$ok = TRUE;
			foreach($IMPORTSPEC['select'] as $col => $re) {
				$val = getMergeCols($sheet,$row,$col);
				$ok = $ok && preg_match($re,$val);
			}
			if (!$ok)
				continue;
		}
		
		if ($IMPORTSPEC['type']==$TYPE_ENTRANTS) {
			$ridernames = getNameFields($sheet,$row,array('RiderName','RiderFirst','RiderLast'));
			//echo('[ ');print_r($ridernames);echo(' ]');
			if (trim($ridernames[0])=='') 
					break;						// Blank line, all done

			if (isset($IMPORTSPEC['options']['sourceisclean']) && $IMPORTSPEC['options']['sourceisclean']) {
				$fldval['RiderName'] = $ridernames[0];
				$fldval['RiderFirst'] = $ridernames[1];
			} else {
				$fldval['RiderName'] = properName(trim($ridernames[0]));
				$fldval['RiderFirst'] = properName(trim($ridernames[1]));
			}
		}
		
		if ($IMPORTSPEC['type']==$TYPE_BONUSES) {
			$bonusid = getMergeCols($sheet,$row,$IMPORTSPEC['cols']['BonusID']);
			if ($bonusid=='')
				break;
		}

		if ($IMPORTSPEC['type']==$TYPE_COMBOS) {
			$comboid = getMergeCols($sheet,$row,$IMPORTSPEC['cols']['ComboID']);
			if ($comboid=='')
				break;
		}

		if (isset($IMPORTSPEC['default']))
			foreach($IMPORTSPEC['default'] as $fld => $defval) {
				$fldval[$fld] = $defval;
				if (isset($IMPORTSPEC['setif'][$fld]))
					foreach($IMPORTSPEC['setif'][$fld] as $val => $mtch)
						if (preg_match($mtch[1],getMergeCols($sheet,$row,$mtch[0])))
							$fldval[$fld] = $val;
			}

		if ($IMPORTSPEC['type']==$TYPE_ENTRANTS) {
			$pillionnames = getNameFields($sheet,$row,array('PillionName','PillionFirst','PillionLast'));
			if (isset($IMPORTSPEC['options']['sourceisclean']) && $IMPORTSPEC['options']['sourceisclean']) {
				$fldval['PillionName'] = $pillionnames[0];
				$fldval['PillionFirst'] = $pillionnames[1];
			} else {
				$fldval['PillionName'] = properName(trim($pillionnames[0]));
				$fldval['PillionFirst'] = properName(trim($pillionnames[1]));
			}
		
			$noknames = getNameFields($sheet,$row,array('NoKName','NoKFirst','NoKLast'));
			$fldval['NoKName'] = properName(trim($noknames[0]));

			$bike = getNameFields($sheet,$row,array('Bike','Make','Model'));
			if(preg_match($TAGS['ImportBikeTBC'][0],trim($bike[0])))
				$bike[0] = $TAGS['ImportBikeTBC'][1];

			if (isset($IMPORTSPEC['options']['sourceisclean']) && $IMPORTSPEC['options']['sourceisclean']) 
				$fldval['Bike'] = $bike[0];
			else
				$fldval['Bike'] = cleanBikename(trim($bike[0]));
			
			if (isset($IMPORTSPEC['cols']['BikeReg']))
				$fldval['BikeReg'] = strtoupper(trim(getMergeCols($sheet,$row,$IMPORTSPEC['cols']['BikeReg'])));
		}


		foreach ($fldval as $col => $val)
			if (isset($IMPORTSPEC['cols'][$col]) && !array_search($col,$specialfields))
				$fldval[$col] = getMergeCols($sheet,$row,$IMPORTSPEC['cols'][$col]);
		
		if ($IMPORTSPEC['type']==$TYPE_ENTRANTS) {
			$xtraData = '';
			if (isset($IMPORTSPEC['data']))
				foreach($IMPORTSPEC['data'] as $k => $kcol)
					$xtraData .= $k.'='.getMergeCols($sheet,$row,$IMPORTSPEC['data'][$k])."\n";
			$fldval['ExtraData'] = $xtraData;
		}
		
		if (!$SqlBuilt) {
			$sql = "INSERT INTO $tablename (";
			$fl = '';
			foreach ($fldval as $fld => $val) 
				$fl = buildList($fl,$fld);
			$sql .= $fl;
			$sql .= ") VALUES (";
			$fl = '';
			foreach ($fldval as $fld => $val) 
				$fl = buildList($fl,":$fld");
			$sql .= $fl;		
			$sql .= ")";
			
			try {
				$stmt = $DB->prepare($sql);
				if ($stmt == FALSE)
					die("Prepare failed ".$DB->lastErrorMsg().'<hr>'.$sql);
			} catch(Exception $e) {
				die($e->getMessage());
			}
			$SqlBuilt = TRUE;
		}
		try {
			foreach ($fldval as $fld => $val) {
				$typ = ($fldtyp[$fld] == 'INTEGER' ? SQLITE3_INTEGER : SQLITE3_TEXT);
				$stmt->bindValue(":$fld",$val,$typ);
			}
			
			if ($IMPORTSPEC['type']==$TYPE_ENTRANTS)
				echo($fldval["EntrantID"].' : '.$fldval["RiderName"].' - '.$fldval["Bike"]."<br>");
			
			if ($IMPORTSPEC['type']==$TYPE_BONUSES)
				echo($fldval["BonusID"].' : '.$fldval["BriefDesc"]."<br>");
			
			if ($IMPORTSPEC['type']==$TYPE_COMBOS)
				echo($fldval["ComboID"].' : '.$fldval["BriefDesc"]."<br>");
			
			$stmt->execute();
			$nrows++;
			
		} catch(Exception $e) {
			die("Caught ".$e->getMessage());
			break;
		}
	}


	if ($IMPORTSPEC['type'] == $TYPE_ENTRANTS) {
		$StartTime = getValueFromDB('SELECT StartTime FROM rallyparams','StartTime','');
		$stmt = $DB->prepare("UPDATE entrants SET StartTime=:StartTime");
		$stmt->bindValue(":StartTime",$StartTime,SQLITE3_TEXT);
		$stmt->execute();
	}

	$DB->exec("COMMIT TRANSACTION");

	echo("</p><p>All done - $nrows rows loaded </p>");
	
} //loadSpreadsheet



function openWorksheet()
{
	global $target_dir,$IMPORTSPEC;
	
	$filetype = \PhpOffice\PhpSpreadsheet\IOFactory::identify($target_dir.DIRECTORY_SEPARATOR.$IMPORTSPEC['xlsname']);

	$rdr = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($filetype);

	$rdr->setReadDataOnly(true);
	$rdr->setLoadSheetsOnly($IMPORTSPEC['whichsheet']);
	try {
		$xls = $rdr->load($target_dir.DIRECTORY_SEPARATOR.$IMPORTSPEC['xlsname']);
	} catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
		die("Error: ".$e->getMessage());
	}

	$sheet = $xls->getSheet($IMPORTSPEC['whichsheet']);
	return $sheet;
}

function previewSpreadsheet()
{
	global $DB,$target_dir, $ENTRANT_FIELDS, $IMPORTSPEC, $IGNORE_COLUMN, $TYPE_BONUSES, $TYPE_COMBOS, $TYPE_ENTRANTS, $BONUS_FIELDS, $COMBO_FIELDS;
	
//	loadSpecs(); // already loaded now
	
	if (!isset($IMPORTSPEC['xlsname'])) 
		return;


	//echo('1 .. ');
	$sheet = openWorksheet();
	//echo('2 .. ');
	$maxcol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestColumn());
	$maxrow = $sheet->getHighestRow();
	//echo('3 .. ');
	// Build column lookup by name table
	for ($i=1; $i<=$maxcol;$i++)
	{
		$colname = $sheet->getCellByColumnAndRow($i,1)->getValue();
		$XLSFIELDS[$colname]=$i;
	}
	
	
	//echo('preview-4 ');

	$hdrs = $IMPORTSPEC['FirstDataRow'];  // Skip the column headers
	$row = 0;
	
	switch($IMPORTSPEC['type']) {
		case $TYPE_BONUSES:
			$MYFIELDS = $BONUS_FIELDS;
			break;
		case $TYPE_COMBOS:
			$MYFIELDS = $COMBO_FIELDS;
			break;
		case $TYPE_ENTRANTS:
		default:
			$MYFIELDS = $ENTRANT_FIELDS;
	}

	
	//print_r($IMPORTSPEC['cols']);
	echo('<table id="previewrows" style="font-size:small;">');
	echo('<tr>');
	for ($col = 1; $col <= $maxcol; $col++) {
		echo('<td><select name="colmaps[]" class="fldsel">');
		$selfld = '';
		foreach($MYFIELDS as $k => $v) {
			echo('<option value="'.$k.'"');
			if (isset($IMPORTSPEC['cols']) && array_search($col - 1,$IMPORTSPEC['cols'])==$k){
				$selfld = $k;
				echo(" selected ");
			} else if ($k == $IGNORE_COLUMN && $selfld == '')
				echo(" selected ");
			echo('>'.$v.'</option>');
		}
		echo('</select></td>');
	}
	echo('</tr>');
	while ($row++ < $maxrow)
	{
		if ($row < $hdrs)
			echo('<tr class="xlshdr">');
		else
			echo('<tr>');
		$col = 0;
		while ($col++ <= $maxcol)
			echo('<td>'.$sheet->getCellByColumnAndRow($col,$row)->getValue().'</td>');
		echo('</tr>');
	}
	echo('</table>');
}

function processUpload()
{
	global $IMPORTSPEC, $target_dir, $TAGS, $upload_state;
	
	if(isset($_FILES['fileid']['tmp_name']) && $_FILES['fileid']['tmp_name']!='')
	  if (!move_uploaded_file($_FILES['fileid']['tmp_name'],$target_dir.DIRECTORY_SEPARATOR.$IMPORTSPEC['xlsname']))
		die('Upload failed ['.$_FILES['fileid']['tmp_name'].']==>['.$target_dir.DIRECTORY_SEPARATOR.$IMPORTSPEC['xlsname'].']');
	$upload_state = 2;
}


function replaceColMaps()
{
	global $IMPORTSPEC;
	
	if (isset($IMPORTSPEC['cols'])) {
		$ck = array_keys($IMPORTSPEC['cols']);
		$nc = count($ck);
		for ($i = 0; $i < $nc; $i++)
			unset ($IMPORTSPEC['cols'][$ck[$i]]);
	}
	$ck = array_keys($_REQUEST['colmaps']);
	$nc = count($ck);
	for ($i = 0; $i < $nc; $i++)
		if ($_REQUEST['colmaps'] != '')
			$IMPORTSPEC['cols'][$_REQUEST['colmaps'][$i]] = $i;
}


function showUpload()
{
	global $TAGS, $SPECFILES, $upload_state, $IMPORTSPEC, $TYPE_BONUSES, $TYPE_COMBOS, $TYPE_ENTRANTS;
	
	startHtml($TAGS['ttUpload'][0]);
	
	$type = (isset($_REQUEST['type']) ? $_REQUEST['type'] : $TYPE_ENTRANTS);
	
	echo('<form id="uploadxls" action="importxls.php" method="get" enctype="multipart/form-data">');
	echo('<input type="hidden" name="type" value="'.$type.'">');

	switch($IMPORTSPEC['type']) {
		case $TYPE_BONUSES:
			echo('<h3>'.$TAGS['UploadBonusesH1'][1].'</h3>');
			break;
		case $TYPE_COMBOS:
			echo('<h3>'.$TAGS['UploadCombosH1'][1].'</h3>');
			break;
		case $TYPE_ENTRANTS:
		default:
			echo('<h3>'.$TAGS['UploadEntrantsH1'][1].'</h3>');
	}
	$myfile = (isset($_REQUEST['filename']) ? htmlentities(basename($_REQUEST['filename']))	: '');
?>
<script>
function repainthdrs(nhdrs) {
	let tab = document.getElementById('previewrows');
	if (!tab)
		return;
	for (let i = 1, row; row = tab.rows[i]; i++) /* First row is my select, not data */
		if (i <= nhdrs)
			row.classList.add('xlshdr');
		else
			row.classList.remove('xlshdr');
}
function postForm(obj) {
	console.log('Posting form');
	let frm = obj.form;
	if (!frm) return;
	console.log('Posting form: '+frm.id);
	frm.method = 'post';
	//frm.submit();
	//alert('Form posted');
	return true;
}
function uploadFile(obj) {
	console.log('Uploading file '+obj.value);
	document.getElementById('fileuploaded').value=1;
	document.getElementById('filename').value=obj.value;
	obj.form.method = 'post';
	obj.form.submit();
}
</script>

<?php
	echo('<input type="hidden" id="fileuploaded" name="fileuploaded" value="'.$upload_state.'">');
	echo('<span class="vlabel" '.($myfile=='' ? ' style="display:none;">' : '>'));
	echo('<label for="filename">'.$TAGS['ix_FileLoaded'][0].' </label> ');
	echo('<input type="text" readonly name="filename" id="filename"  value="'.$myfile.'" > '); 
	echo('<button onclick="document.getElementById(\'filepick\').style.display=\'block\';this.disabled=true;return false;">'.$TAGS['ix_ChooseAgain'][1].'</button>');
	echo('</span>');
	echo('<span class="vlabel"  id="filepick" style="font-size:smaller; '.($myfile!='' ? 'display:none;">' : '">'));
	echo('<label for="fileid">'.$TAGS['UploadPickFile'][1].'</label> ');
	echo('<input type="file" name="fileid" id="fileid" onchange="uploadFile(this);">');
	echo('</span>');


	defaultSpecfile($type);
	
	loadSpecs();
	
	$chk = isset($_REQUEST['specfile']) ? $_REQUEST['specfile'] : '';
	$i = 0;
	if (!isset($_REQUEST['fileuploaded'])) {
		echo('</form>');
		return;
	}
	
	echo('<p style="font-size:smaller;">'.$TAGS['ix_HelpPrompt'][0].'</p>');

	echo('<span class="vlabel" style="font-size:smaller;">');
	echo('<label for="specfile">'.$TAGS['ix_Fileformat'][0].'</label> ');
	echo('<select name="specfile" id="specfile" onchange="'."document.getElementById('uploadxls').submit();".'">');
	//print_r($SPECFILES);
	
	foreach ($SPECFILES as $spc => $specs)
	{
		$i++;
		echo('<option id="specfile'.$i.'"');
		if ($chk==$spc) {
			echo(' selected ');
			$chk = FALSE;
		}
		echo(' value="'.$spc.'">'.$specs.'</option>');
	}
	echo('</select>');
	echo(' <label title="'.$TAGS['xlsHeaders'][1].'" for="hdrs">'.$TAGS['xlsHeaders'][0].'</label> ');
	echo('<input type="number" name="hdrs" id="hdrs" style="width:2em;" value="'.(intval($IMPORTSPEC['FirstDataRow'])-1).'" onchange="repainthdrs(this.value);"> ');
	switch($IMPORTSPEC['type']) {
		case $TYPE_BONUSES:
			$xx = $TAGS['UploadForceBonuses'];
			$tab = "bonuses";
			break;
		case $TYPE_COMBOS:
			$xx = $TAGS['UploadForceCombos'];
			$tab = "combinations";
			break;
		case $TYPE_ENTRANTS:
		default:
		$xx = $TAGS['UploadForceEntrants'];
		$tab = "entrants";
	}

	$rex = getValueFromDB("SELECT count(*) As Rex FROM $tab","Rex",0);
	if ($rex > 0) {
		$dis = ' disabled ';
		echo(' <label for="force">'.$xx[1].'</label> <input type="checkbox" name="force" id="force" onchange="document.getElementById(\'submitform\').disabled=!this.checked;">  ');
	} else
		$dis = '';
	echo('<input '.$dis.' id="submitform" type="submit" name="load" value="'.$TAGS['Upload'][0].'" onclick="return postForm(this);">');
	echo('</span>');

	previewSpreadsheet();
	echo('</form>');
		
	
}

function defaultSpecfile($datatype)
{
	global $TAGS, $SPECFILES, $upload_state, $IMPORTSPEC, $TYPE_BONUSES, $TYPE_COMBOS, $TYPE_ENTRANTS;
	
	if (isset($_REQUEST['specfile']))
		return;
	
	$_REQUEST['specfile'] = getValueFromDB("SELECT specid FROM importspecs WHERE importType=$datatype ORDER BY specid LIMIT 1","specid","");
	
}




// Mainline here

extendBonusFields();
extendComboFields();
extendEntrantFields();

//print_r($_REQUEST);


if (isset($_REQUEST['fileuploaded']) && $_REQUEST['fileuploaded']=='1')
{
	//echo('a ... ');
	processUpload();
	//echo('b ... ');
	showUpload();
	//echo('ccc ... ');
	exit;
}


//if (!isset($_REQUEST['specfile']))
//	die($TAGS['xlsNoSpecfile'][1]);

if (isset($_REQUEST['load'])) {
	loadSpreadsheet();
	exit;
}

showUpload();

?>
