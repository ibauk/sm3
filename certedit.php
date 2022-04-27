<?php
// certedit.php
/*
 * I B A U K   -   S C O R E M A S T E R
 *
 * I provide wysiwyg certificate editing
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



require_once('common.php');
$HOME_URL = 'admin.php?menu=setup';

// These database fields should not be included in the dropdown picklist
$EXCLUDE_FIELDS = array(
	"AutoRank","BCMethod","BonusesVisited","CombosTicked","DBState","DBVersion",
	"Cat1Label","Cat2Label","Cat3Label","Cat4Label","Cat5Label","Cat6Label","Cat7Label","Cat8Label","Cat9Label",
	"Confirmed","DecimalComma","EmailParams",
	"fpbonus","spbonus","mpbonus","isvirtual","refuelstops","stopmins","tankrange","Theme",
	"ExtraData","MaxMilesMethod","NoKName","NoKRelation","NoKPhone","OdoCheckMiles","OdoCheckStart","OdoCheckFinish",
	"OdoKms","RejectReasons","RejectedClaims","ScoredBy","ScoringMethod","ScoringNow","ShowMultipliers",
	"SpecialsTicked","TeamRanking","TiedPointsRanking","EntrantStatus"
);

// These non-database fields should be included in the dropdown picklist`
$CERT_FIELDS = array(
	"CrewFirst"				=> "CrewFirst",
	"CrewName"				=> "CrewName",
	"DateRallyRange"		=> "DateRallyRange",
	'FinishRank'			=> "FinishRank",
	"RallyTitleSplit"		=> "RallyTitleSplit",
	"RallyTitleShort"		=> "RallyTitleShort"
	
);


// Alphabetical order from here on down. Mainline at EOF


function editCertificateW() {
	
	global $DB, $TAGS, $KONSTANTS;

	$EntrantID = (isset($_REQUEST['EntrantID']) ? intval($_REQUEST['EntrantID']) : 0);
	$class = (isset($_REQUEST['Class']) ? intval($_REQUEST['Class']) : 0);
	$rd = fetchCertificateW($EntrantID,$class);
	
	startHtmlW($rd['css']);
	
	//echo('<p>'.$TAGS['CertExplainerW'][0].'<br>');
	//echo($TAGS['CertExplainerW'][1].'</p>');

	echo('<form id="certform" method="post" action="certedit.php" ');
	echo('onsubmit="return document.querySelector('."'#savehtml'".').value=document.querySelector('."'#area_editor'".').value;">');
	
	echo('<input type="hidden" name="c" value="editcert">');
	echo('<input type="hidden" name="EntrantID" value="'.$EntrantID.'">');
	echo('<input type="hidden" name="certcss" id="certcss" value="'.$rd['css'].'">');
	echo('<div class="editwControls" style="font-size:.8em;">');

	// Make provision for multiple classes 
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
		//$pv .= "document.getElementById('certcss').disabled=true;";
		//$pv .= "document.getElementById('certhtml').disabled=true;";
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

	
		//echo('<label for="Class">'.$TAGS['Class'][0].' </label>');
		$x = ' onchange="document.getElementById('."'".'fetchcert'."'".').disabled=false;"';
		echo('<input title="'.$TAGS['Class'][1].'" type="hidden" min="0" name="Class" id="Class" value="'.$class.'" '.$x.' class="smallnumber"> ');
	
		if ($MC > 1)
			echo('<input type="submit" style="display:none;" disabled id="fetchcert" name="fetchcert" value="'.$TAGS['FetchCert'][0].'" title="'.$TAGS['FetchCert'][1].'"> ');
		//echo('<label for="Title">'.$TAGS['CertTitle'][0].' </label>');
		echo('<input title="'.$TAGS['CertTitle'][1].'" type="hidden" name="Title" id="Title" value="'.$rd['Title'].'" > ');
	
	

	
	
	echo('<input type="submit" data-triggered="0" onclick="this.setAttribute(\'data-triggered\',1);return true;" disabled name="savecert" value="'.$TAGS['RecordSaved'][0].'" id="savedata" data-altvalue="'.$TAGS['SaveCertificate'][0].'" title="'.$TAGS['SaveCertificate'][1].'"> ');

	echo('</div>');
	echo('<input type="hidden" name="certhtml"  value="" id="savehtml">');
	echo('</form>');

	loadTableData();
	
	emitContainers($rd['html']);
	echo('</body></html>');
	
	//showFooter();
}


function emitContainers($rtf) {

	global $CERT_FIELDS, $TAGS;

?>
<div id="main_container">
    <div class="result" style="margin:1em;">
        <textarea id="area_editor"><?php echo($rtf);?></textarea>
    </div>
</div>

<script>
var editor = new Jodit('#area_editor', {
       //
beautifyHTMLCDNUrlsJS: '',
useAceEditor: false,
sourceEditorCDNUrlsJS : '',
width: '164mm',
height: '253mm',
uploader: {'insertImageAsBase64URI':true},

buttons: [
	'source','|',
	'bold',
	'strikethrough',
	'underline',
	'italic',
	'|',
	'font',
	'fontsize',
	'brush',
	'align',
	'|',
	'image',
	{	
		name: 'fields',
		iconURL: './jodit/fields.png',
<?php		
		echo("tooltip: '".$TAGS['jodit_InsertField'][0]."',");
		echo("list: ");
		echo(JSON_encode($CERT_FIELDS));
?>		   
		,
		exec: ( ed, nodeOrFalse, control, origEvent, btn) => {
			console.log('Control: '+JSON.stringify(control));
			console.log('btn: '+JSON.stringify(btn));
			var key = control.args[0], value = control.args[1];
	
			ed.selection.insertNode(ed.create.element('span', '#'+key+'#'));
		},
		template: function (ed, key, value) {
			return '<div>' + value + '</div>';
		}
	}, // Fields
	{
		name: 'borders',
		iconURL: './jodit/borders.png',
<?php		
		echo("tooltip: '".$TAGS['jodit_Borders'][0]."',");
		echo("list: {");
		echo("'".$TAGS['jodit_Borders_Double'][0]."':	'2mm double',");
		echo("'".$TAGS['jodit_Borders_Solid'][0]."':	'2mm solid',");
		echo("'".$TAGS['jodit_Borders_None'][0]."':	'none',");
		echo("},");
?>		
		exec: ( ed, nodeOrFalse, control, origEvent, btn) => {
			var key = control.args[0], value = control.args[1];
			document.querySelector(':root').style.setProperty('--border-style',value);
			document.querySelector('#certcss').value = ':root{ --border-style:'+value+'}';
			enableSaveButton();
		},
		template: function (ed, key, value) {
			return '<div>' + key + '</div>';
		}
	} // Borders
	//,'about'
	] // Buttons
});
	
document.querySelector('#area_editor').addEventListener('change',function(e){enableSaveButton();});
</script>

<?php
}
	

// Add fields from the name table into $CERT_FIELDS
function extendCertFields($table) {
	
	global $DB, $CERT_FIELDS;

	$R = $DB->query("PRAGMA table_info($table)");
	while($rd = $R->fetchArray()) {
		$CERT_FIELDS[$rd['name']] = $rd['name'];
	}
}


function fetchCertificateW($EntrantID,$Class) {
	
	global $DB, $TAGS, $KONSTANTS;
	if ($EntrantID == '')
		$EntrantID = 0;
	if ($Class == '')
		$Class = 0;
	$sql = "SELECT * FROM certificates WHERE EntrantID=";
	$R = $DB->query($sql.$EntrantID." AND Class=$Class");
	if (!$rd = $R->fetchArray())
		$rd = defaultRecord('certificates');
	return ['html'=>$rd['html'],'css'=>$rd['css'],'Title'=>$rd['Title'],'Class'=>$rd['Class']];
	
}





// Complete the $CERT_FIELDS array ready for picking
function loadTableData(){
	
	global $CERT_FIELDS,$EXCLUDE_FIELDS;
	
	extendCertFields('rallyparams');
	extendCertFields('entrants');
	foreach($EXCLUDE_FIELDS as $fld)
		unset($CERT_FIELDS[$fld]);
	asort($CERT_FIELDS);
}



function saveCertificateW() {
	
	global $DB, $TAGS, $KONSTANTS;
	
	//var_dump($_REQUEST);
	$R = $DB->query("SELECT Count(*) As Rex FROM certificates WHERE EntrantID=".$_REQUEST['EntrantID']." AND Class=".$_REQUEST['Class']);
	$rd = $R->fetchArray();
	$adding = $rd['Rex'] < 1;
	
	if ($adding)
	{
//		echo(' adding ');
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
//		echo(' updating ');
		$sql = "UPDATE certificates SET html='".$DB->escapeString($_REQUEST['certhtml'])."'";
		$sql .= ",css='".$DB->escapeString($_REQUEST['certcss'])."'";
		$sql .= ",Title='".$DB->escapeString($_REQUEST['Title'])."'";
		$sql .= " WHERE EntrantID=".$_REQUEST['EntrantID']." AND Class=".$_REQUEST['Class'];
	}
//	echo($sql."<hr>");
	if (!$DB->exec($sql)) {
		dberror();
		exit;
	}
	if ($DB->lastErrorCode() <> 0)
		echo($DB->lastErrorCode().' == '.$DB->lastErrorMsg().'<br>'.$sql.'<hr>');

	
}



function startHtmlW($css) {

	global $CERT_FIELDS, $TAGS;
	
	startHtml($TAGS['ttSetup'][0]);

?>
<link rel="stylesheet" href="jodit/jodit.min.css"/>
<script src="jodit/jodit.min.js"></script>
<link href="certificate.css" rel="stylesheet">

<?php
echo('<style>'.$css.'</style>');
}



if (isset($_REQUEST['savecert']))
	saveCertificateW();

editCertificateW();


?>
