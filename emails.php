<?php

/*
 * I B A U K   -   S C O R E M A S T E R
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

 

$HOME_URL = 'admin.php';

require_once('common.php');
require_once("vendor".DIRECTORY_SEPARATOR."autoload.php");
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require_once('certificate.php');
require_once('claimslog.php');

function newMailer()
{
	$params = json_decode(getValueFromDB("SELECT EmailParams FROM rallyparams","EmailParams","{}"));
	
	$mail = new PHPMailer(true);

	foreach ($params as $key => $val) 
		switch($key) {
			case 'SetFrom':
				$mail->SetFrom($val[0],$val[1]);
				break;
			default:
				$mail->$key = $val;
		}

	$mail->IsSMTP();
	$mail->Mailer = "smtp";
	$mail->IsHTML(true);
	
	return $mail;
}


function cleanFilename($filename)
{
	return str_replace(' ','_',$filename); // Crude sanity check
}



function setupEmailRun()
{
	global $DB, $KONSTANTS, $TAGS;
	
	startHtml($TAGS['ttEmails'][0]);

?>
<link rel="stylesheet" href="jodit/jodit.min.css"/>
<script src="jodit/jodit.min.js"></script>
<script>
//<!--
function countrecs() {
	let dns = document.getElementById('EntrantDNS').checked;
	let ok = document.getElementById('EntrantOK').checked;
	let finisher = document.getElementById('EntrantFinisher').checked;
	let dnf = document.getElementById('EntrantDNF').checked;
	let ids = document.getElementById('EntrantID').value;
	let status = '';
	if (dns) status = ' '+EntrantDNS;
	if (ok) status += ' '+EntrantOK;
	if (finisher) status += ' '+EntrantFinisher;
	if (dnf) status += ' '+EntrantDNF;
	if (status != '') status = status.trim().replace(/ /g,',');
	let sql = (status != '' ? 'EntrantStatus In ('+status+')' : '');
	if (ids != '') {
		if (sql != '')
			sql += ' or ';
		sql += 'EntrantID In ('+ids+')';
	}
	console.log('sql.where = '+sql);
	document.getElementById('wheresql').value = sql;
	if (sql == '') {
		document.getElementById("selectedcount").innerHTML = '0';
		document.getElementById('sendmail').disabled = true;
		return;
	}

	let xhttp = new XMLHttpRequest();
	xhttp.onreadystatechange = function() {
		if (this.readyState == 4 && this.status == 200) {
			document.getElementById("selectedcount").innerHTML = this.responseText;
			let rex = parseInt(this.responseText);
			document.getElementById('sendmail').disabled = rex < 1;
		}
	};
	xhttp.open("GET", "emails.php?c=count&where="+sql, true);
	xhttp.send();
	
}
function showEntrants() {
	let ids = document.getElementById('EntrantID').value
	let names = document.getElementById('entrantnames');
	names.innerHTML = '';
	if (ids=='') return;
	let xhttp = new XMLHttpRequest();
	xhttp.onreadystatechange = function() {
		if (this.readyState == 4 && this.status == 200) {
			names.innerHTML = this.responseText;
		}
	};
	xhttp.open("GET", "emails.php?c=names&e="+ids, true);
	xhttp.send();
}
function validate() {
	let fld = document.getElementById('Subject');
	if (fld.value=='') {
		console.log('Subject is blank!');
		fld.focus();
		return false;
	}
	fld = document.getElementById('Body');
	if (fld.value=='') {
		console.log('Body is blank!');
		fld.focus();
		return false;
	}
	return true;
}
//-->
</script>
<?php	
	echo('<h4>'.$TAGS['ttEmails'][1].'</h4>');
	
	
	echo('<form method="post" action="emails.php" enctype="multipart/form-data" onsubmit="return validate();">');
	echo('<input type="hidden" name="c" value="email">');
	echo('<input type="hidden" id="wheresql" name="wheresql">');
	
	echo('<span class="vlabel" title="'.$TAGS['em_EntrantStatus'][1].'"><label  style="vertical-align: middle;" for="EntrantDNS">'.$TAGS['em_EntrantStatus'][0].'</label> ');
	echo('<label class="short" for="EntrantDNS">'.$TAGS['EntrantDNS'][0].'</label> <input onchange="countrecs();" type="checkbox" id="EntrantDNS" name="EntrantStatus" value="'.$KONSTANTS['EntrantDNS'].'"> ');
	echo('<label class="short" for="EntrantOK">'.$TAGS['EntrantOK'][0].'</label> <input onchange="countrecs();" type="checkbox" id="EntrantOK" name="EntrantStatus" value="'.$KONSTANTS['EntrantOK'].'"> ');
	echo('<label class="short" for="EntrantFinisher">'.$TAGS['EntrantFinisher'][0].'</label> <input onchange="countrecs();" type="checkbox" id="EntrantFinisher" name="EntrantStatus" value="'.$KONSTANTS['EntrantFinisher'].'"> ');
	echo('<label class="short" for="EntrantDNF">'.$TAGS['EntrantDNF'][0].'</label> <input onchange="countrecs();" type="checkbox" id="EntrantDNF" name="EntrantStatus" value="'.$KONSTANTS['EntrantDNF'].'"> ');
	//echo('</span>');
	
	//echo('<span class="vlabel" title="'.$TAGS['em_EntrantID'][1].'"><label  style="vertical-align: middle;" for="EntrantID">'.$TAGS['em_EntrantID'][0].'</label> ');
	echo('<br><label  style="vertical-align: middle;" for="EntrantID">'.$TAGS['em_EntrantID'][0].'</label> ');
	echo('<input title="'.$TAGS['em_EntrantID'][1].'" type="text" name="EntrantID" id="EntrantID" onchange="countrecs();showEntrants();" > ');
	echo('<span id="entrantnames"></span>');
	echo('</span>');
	
	echo('<span class="vlabel" title="'.$TAGS['em_Subject'][1].'"><label for="Subject">'.$TAGS['em_Subject'][0].'</label> ');
	echo('<input placeholder="'.$TAGS['em_NotBlank'][0].'" type="text" name="Subject" id="Subject" class="textarea" style="width:20em;">');
	echo('</span>');
	
	//echo('<span class="vlabel" title="'.$TAGS['em_Body'][1].'"><label for="Body" style="vertical-align: top;">'.$TAGS['em_Body'][0].'</label> ');
	echo('<span class="vlabel" title="'.$TAGS['em_Body'][1].'">');
	echo('<textarea   title="'.$TAGS['em_Body'][1].'" placeholder="'.$TAGS['em_NotBlank'][0].'" id="Body" name="Body" cols="80" rows="10" ></textarea>');
	echo('</span>');

	//echo('<span class="vlabel" title="'.$TAGS['em_includeScorex'][1].'">');
	//echo('<label for="includeScorex">'.$TAGS['em_includeScorex'][0].' </label> ');
	//echo('<input type="checkbox" name="includeScorex" id="includeScorex"> ');
	//echo('</span>');
	//echo('<span class="vlabel" title="Include a list of claims received without scoring">');
	//echo('<label for="includeClaimsLog">Include claims received log </label> ');
	//echo('<input type="checkbox" name="includeClaimsLog" id="includeClaimsLog"> ');
	//echo('</span>');

	echo('<span class="vlabel" title="What claims record should I include">');
	echo('<label for="includeScorex">Include inline </label>');
	echo('<select name="includeScorex" id="includeScorex">');
	echo('<option value="0" selected >nothing</option>');
	echo('<option value="1">Score Explanation (Finishers, DNF)</option>');
	echo('<option value="2">Unscored log of claims received</option>');
	echo('</select>');
	echo('</span>');

	//echo('<span class="vlabel" title="'.$TAGS['em_Signature'][1].'"><label for="Signature" style="vertical-align: top;">'.$TAGS['em_Signature'][0].'</label> ');
	echo('<span class="vlabel" title="'.$TAGS['em_Signature'][1].'">');
	echo('<textarea  title="'.$TAGS['em_Signature'][1].'" id="Signature" name="Signature" cols="80" rows="2" ></textarea>');
	echo('</span>');
	
	echo('<span class="vlabel" title="'.$TAGS['em_includeCertificate'][1].'">');
	echo('<label for="includeCertificate">'.$TAGS['em_includeCertificate'][0].' </label> ');
	echo('<input type="checkbox" name="includeCertificate" id="includeCertificate"> ');
	echo('</span>');
		

	echo('<span class="vlabel" title="'.$TAGS['em_Attachment'][1].'">');
	echo('<label for="Attachment">'.$TAGS['em_Attachment'][0].'</label> ');
	echo('<input id="Attachment" name="Attachment[]" type="file" multiple>');
	echo('</span>');

	
	echo('<span class="vlabel" title="'.$TAGS['em_NumberSelected'][1].'"><label >'.$TAGS['em_NumberSelected'][0].'</label> ');
	echo('<span id="selectedcount" style="font-size:larger; font-weight: bold; padding-left:.5em;">0</span>');
	echo(' <input disabled type="submit" id="sendmail" value="'.$TAGS['em_Submit'][0].'">');
	echo('</span>');
	
	echo('</form>');

?>
<script>
var bodyJodit = new Jodit('#Body', {
       //
beautifyHTMLCDNUrlsJS: '',
useAceEditor: false,
sourceEditorCDNUrlsJS : '',
width: '50em',
height: '15em',
tabIndex: '0',
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
	'link',
	//,'about'
	] // Buttons
});
var sigJodit = new Jodit('#Signature', {
       //
beautifyHTMLCDNUrlsJS: '',
useAceEditor: false,
sourceEditorCDNUrlsJS : '',
width: '50em',
//minHeight: '2em',
tabIndex: '0',
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
	'link',
	//,'about'
	] // Buttons
});
	
</script>

<?php



	
	echo('</body></html>');
}




function buildMailQ()
{
	global $DB, $KONSTANTS;
	
	foreach(['wheresql','Subject','Body'] as $key)
		if (!isset($_REQUEST[$key]))
			return;
	$recipients = getCountWhere($_REQUEST['wheresql']);
	if ($recipients < 1)
		return;
	
	error_log("Emailing $recipients recipients");
	
	$uploads = [];
	$filenames = [];
	if (isset($_FILES['Attachment']) && $_FILES['Attachment']['name'][0] != '') {
		$nfiles = count($_FILES['Attachment']['name']);
		error_log("Attaching $nfiles files");
		for ($i = 0; $i < $nfiles; $i++) {
			$filenames[$i] = cleanFilename($_FILES['Attachment']['name'][$i]); 
			$uploads[$i] = joinPaths($KONSTANTS['UPLOADS_FOLDER'],$filenames[$i]);
			error_log("Moving ".$filenames[$i]." to ".$uploads[$i]);
			if (!move_uploaded_file($_FILES['Attachment']['tmp_name'][$i],$uploads[$i]))
				die('!!!!!!!!! ['.$_FILES['Attachment']['tmp_name'][$i].']==>['.$uploads[$i].']');
		}
	}


	$sql = "INSERT OR REPLACE INTO emailtemplates (TemplateID";
	$sql .= ",EmailSubject,EmailBody,EmailSignature,IncludeScorex,IncludeCertificate,AttachFiles,AttachNames,WhereSQL)";
	$sql .= " VALUES(0,'".$DB->escapeString(($_REQUEST['Subject']))."'";
	$sql .= ",'".$DB->escapeString($_REQUEST['Body'])."'";
	$sql .= ",'".$DB->escapeString($_REQUEST['Signature'])."'";
	//$ix = (isset($_REQUEST['includeScorex']) ? 1 : 0);
	//$ix = $ix | (isset($_REQUEST['includeClaimsLog']) ? 2 : 0);
	$sql .= ",".$_REQUEST['includeScorex'];
	$sql .= ",".(isset($_REQUEST['includeCertificate']) ? '1' : '0');
	$sql .= ",'".$DB->escapeString(implode('|',$uploads))."'";
	$sql .= ",'".$DB->escapeString(implode('|',$filenames))."'";
	$sql .= ",'".$DB->escapeString($_REQUEST['wheresql'])."')";
	
	//print_r($_REQUEST);
	//echo("<hr>$sql<hr>");
	try {
		if (!$DB->exec("BEGIN IMMEDIATE TRANSACTION")) {
			dberror();
			exit;
		}
		$DB->exec($sql);
		$sql = "DELETE FROM emailq";
		$DB->exec($sql);
	} catch (Exception  $e) {

	}

	$sql = "SELECT EntrantID, RiderName, Email, ScoreX, EntrantStatus FROM entrants WHERE ".$_REQUEST['wheresql'];
	$R = $DB->query($sql);
	while ($rd = $R->fetchArray()) {
		$sql = "INSERT INTO emailq (EntrantID) VALUES(".$rd['EntrantID'].")";
		try {
			$DB->exec($sql);
		} catch (Exception $e) {

		}
	}
	try {
		$DB->exec("COMMIT");
	} catch (Exception $e) {

	}

	
}






function getCountWhere($where)
{

	$sql = "SELECT count(*) As Rex FROM entrants WHERE ".$where;
	return getValueFromDB($sql,"Rex",0);
}

function echoCountWhere()
{
	$where = $_REQUEST['where'];
	echo(getCountWhere($where));
}

function getNames($ids)
{
	global $DB;
	
	$sql = "SELECT RiderName FROM entrants WHERE EntrantID In ($ids)";
	$R = $DB->query($sql);
	$res = '';
	while ($rd = $R->fetchArray())
		$res .= ($res != '' ? ', '.$rd['RiderName'] : $rd['RiderName']);
	return $res;
}


function echoNames()
{
	$ids = $_REQUEST['e'];
	echo(getNames($ids));
}


function replaceVars($txt,$rd)
{
	$res = $txt;
	$mt = [];
	preg_match_all("/(#[\\w]*#)/",$res,$mt,PREG_SET_ORDER);
	foreach ($mt as $fld) {
		$fldname = substr($fld[0],1,strlen($fld[0])-2);
		//echo (" [ $fldname ] ");
		try {
			$res = str_replace($fld,formattedField($fldname,(isset($rd[$fldname]) ? $rd[$fldname] : '')),$res);
		} catch(Exception $e) {
			echo(" Error: ".$e->getMessage()."; ");
		}
	}
	return $res;

}

function sendMail()
{
	global $DB, $KONSTANTS, $TAGS;
	
	foreach(['wheresql','Subject','Body'] as $key)
		if (!isset($_REQUEST[$key]))
			return;
	$recipients = getCountWhere($_REQUEST['wheresql']);
	if ($recipients < 1)
		return;
	
	error_log("Emailing $recipients recipients");
	
	try {
		$mail = newMailer();
	} catch (Exception $e) {
		error_log("Can't create emailer ".$e->getMessage());
		return;
	}
	
	$mail->SMTPKeepAlive = true; // SMTP connection will not close after each email sent, reduces SMTP overhead
	$mail->SMTPDebug = SMTP::DEBUG_OFF;


	$uploads = [];
	$filenames = [];
	if (isset($_FILES['Attachment']) && $_FILES['Attachment']['name'][0] != '') {
		$nfiles = count($_FILES['Attachment']['name']);
		error_log("Attaching $nfiles files");
		for ($i = 0; $i < $nfiles; $i++) {
			$filenames[$i] = cleanFilename($_FILES['Attachment']['name'][$i]); 
			$uploads[$i] = joinPaths($KONSTANTS['UPLOADS_FOLDER'],$filenames[$i]);
			error_log("Moving ".$filenames[$i]." to ".$uploads[$i]);
			if (!move_uploaded_file($_FILES['Attachment']['tmp_name'][$i],$uploads[$i]))
				die('!!!!!!!!! ['.$_FILES['Attachment']['tmp_name'][$i].']==>['.$uploads[$i].']');
		}
	}

	$sql = "SELECT EntrantID, RiderName, Email, ScoreX, EntrantStatus FROM entrants WHERE ".$_REQUEST['wheresql'];
	$R = $DB->query($sql);
	while ($rd = $R->fetchArray()) {
		$mail->clearAddresses();
		$mail->clearAttachments();		
		try {
			$mail->AddAddress($rd['Email'],$rd['RiderName']);
		} catch (Exception $e) {
			error_log("AddAddress failed for ".$rd['RiderName']);
			echo('******* '.htmlspecialchars($rd['Email']).' - '.htmlspecialchars($rd['RiderName']).'<br>');
			continue;
		}
		$mail->Subject = replaceVars($_REQUEST['Subject'],$rd);
		$msg = '<p>'.replaceVars($_REQUEST['Body'],$rd).'</p>';
		if (isset($_REQUEST['includeScorex']) && $_REQUEST['includeScorex']==1 && ($rd['EntrantStatus']==$KONSTANTS['EntrantFinisher'] || $rd['EntrantStatus']==$KONSTANTS['EntrantDNF']) )
			$msg .= '<p>'.$rd['ScoreX'].'</p>';
		if (isset($_REQUEST['includeScorex']) && $_REQUEST['includeScorex']==2)
			$msg .= entrantClaimsLog($rd['EntrantID']);
		if (isset($_REQUEST['Signature']))
			$msg .= '<p>'.replaceVars($_REQUEST['Signature'],$rd).'</p>';
		$mail->MsgHTML($msg);
		if (isset($_REQUEST['includeCertificate']) && $rd['EntrantStatus']==$KONSTANTS['EntrantFinisher'])
			$mail->addStringAttachment(getViewCertificate($rd['EntrantID']), 'certificate.html');

		$nfiles = count($uploads);
		for ($i = 0; $i < $nfiles; $i++)
			$mail->addAttachment($uploads[$i],$filenames[$i]);

		try {
			error_log("Emailing ".htmlspecialchars($rd['Email']).' - '.htmlspecialchars($rd['RiderName']));
			$mail->Send();
			echo(htmlspecialchars($rd['Email']).' - '.htmlspecialchars($rd['RiderName']).'<br>');
		} catch (Exception $e) {
			error_log("Email failed!");
			error_log($e->getMessage());
			error_log('******* '.htmlspecialchars($rd['Email']).' - '.htmlspecialchars($rd['RiderName']).' ('.$mail->ErrorInfo.')');
			echo('******* '.htmlspecialchars($rd['Email']).' - '.htmlspecialchars($rd['RiderName']).' ('.$mail->ErrorInfo.')<br>');
			$mail->getSMTPInstance()->reset();
		}
	}

	
}


function sendNextMail()
/*
 * This is called by AJAX and returns a 0 or 1 indicating no record/record followed by suitable text
 * 
 */
{
	global $DB, $KONSTANTS, $TAGS;
	
	$sql = "SELECT * FROM emailtemplates WHERE TemplateID=0";
	$R = $DB->query($sql);
	$et = $R->fetchArray();

	$sql = "SELECT EntrantID FROM emailq WHERE EmailSent=0";
	$entrant = getValueFromDB($sql,"EntrantID",0);
	if ($entrant < 1) {
		echo('0:'.$TAGS['snm_QEmpty'][0]);
		return;
	}
	
	try {
		$mail = newMailer();
	} catch (Exception $e) {
		error_log("Can't create emailer ".$e->getMessage());
		return;
	}
	
	$mail->SMTPKeepAlive = true; // SMTP connection will not close after each email sent, reduces SMTP overhead
	$mail->SMTPDebug = SMTP::DEBUG_OFF;

	$uploads = explode('|',$et['AttachFiles']);
	$filenames = explode('|',$et['AttachNames']);
	$sql = "SELECT EntrantID, RiderName, Email, ScoreX, EntrantStatus FROM entrants WHERE EntrantID=".$entrant;
	$R = $DB->query($sql);
	if ($rd = $R->fetchArray()) {
		$mail->clearAddresses();
		$mail->clearAttachments();		
		try {
			$mail->AddAddress($rd['Email'],$rd['RiderName']);
		} catch (Exception $e) {
			error_log("AddAddress failed for ".$rd['RiderName']);
			echo('1:******* '.htmlspecialchars($rd['Email']).' - '.htmlspecialchars($rd['RiderName']));
			return;
		}
		$mail->Subject = replaceVars($et['EmailSubject'],$rd);
		$msg = '<p>'.replaceVars($et['EmailBody'],$rd).'</p>';
		//$msg .= "<hr>".$et['IncludeScorex']."<hr>";
		if (($et['IncludeScorex'] == 1) && ($rd['EntrantStatus']==$KONSTANTS['EntrantFinisher'] || $rd['EntrantStatus']==$KONSTANTS['EntrantDNF']) )
			$msg .= '<p>'.$rd['ScoreX'].'</p>';
		if ($et['IncludeScorex'] == 2) 
			$msg .= entrantClaimsLog($entrant);

		if (!is_null($et['EmailSignature']))
			$msg .= '<p>'.replaceVars($et['EmailSignature'],$rd).'</p>';
		$mail->MsgHTML($msg);
		if ($et['IncludeCertificate']==1 && $rd['EntrantStatus']==$KONSTANTS['EntrantFinisher'])
			$mail->addStringAttachment(getViewCertificate($rd['EntrantID']), 'certificate.html');

		$nfiles = count($uploads);
		for ($i = 0; $i < $nfiles; $i++)
			if ($uploads[$i] != '' && $filenames[$i] != '')
				$mail->addAttachment($uploads[$i],$filenames[$i]);

		try {
			error_log("Emailing ".htmlspecialchars($rd['Email']).' - '.htmlspecialchars($rd['RiderName']));
			$mail->Send();
			echo('1:'.htmlspecialchars($rd['Email']).' - '.htmlspecialchars($rd['RiderName']));

			$dtn = new DateTime(Date('Y-m-d'),new DateTimeZone($KONSTANTS['LocalTZ']));
			$datenow = $dtn->format('c');
		
			$sql = "UPDATE emailq SET EmailSent=1,SentAt='".$datenow."' WHERE EntrantID=".$rd['EntrantID'];
			if (!$DB->exec($sql)) {
				echo('0:******* '.htmlspecialchars($rd['Email']).' - '.htmlspecialchars($rd['RiderName']).' ('.$mail->ErrorInfo.')');
			}
		} catch (Exception $e) {
			error_log("Email failed!");
			error_log($e->getMessage());
			error_log('******* '.htmlspecialchars($rd['Email']).' - '.htmlspecialchars($rd['RiderName']).' ('.$mail->ErrorInfo.')');
			echo('0:******* '.htmlspecialchars($rd['Email']).' - '.htmlspecialchars($rd['RiderName']).' ('.$mail->ErrorInfo.')');
			$mail->getSMTPInstance()->reset();
		}
	} else
		echo('0:'.$TAGS['snm_QEmpty'][0]);

	
}

function showProcessEmailQ()
{
	global $TAGS;

	startHtml(($TAGS['ttEmails'][0]));

	$sql = "SELECT count(*) As Rex FROM emailq WHERE EmailSent=0";
	$rex = getValueFromDB($sql,"Rex",0);
	$sql = "SELECT EmailSubject FROM emailtemplates WHERE TemplateID=0";
	$subject = getValueFromDB($sql,"EmailSubject",'**************');

	echo('<h4>'.$TAGS['snm_Processing'][0].'</h4>');
	echo('<h5>'.$TAGS['snm_Subject'][0].' '.$subject.'</h5>');
	echo('<h5>'.$TAGS['snm_Number'][0].' <span id="numleft">'.$rex.'</span></h5>');

	echo('<p id="log"></p>')
	?>
	<script>
	// <!--
	function sendNext()
	{
		console.log('Sending next');
		let xhttp = new XMLHttpRequest();
		xhttp.onreadystatechange = function() {
			let alldone = new RegExp("\W*0:");
			if (this.readyState == 4 && this.status == 200) {
				console.log('{'+this.responseText+'}');
				let log = document.getElementById('log');
				let num = document.getElementById('numleft');
				let p = this.responseText.indexOf(':');
				if (p > 0 && this.responseText.substring(p-1,1)=='0') {
					num.innerHTML = '0';
				} else {
					let numleft = parseInt(num.innerHTML);
					if (numleft > 0)
						numleft--;
					num.innerHTML = numleft;
				}
				log.innerHTML = this.responseText.substring(p+1) + '<br>' + log.innerHTML;
			}
		};
		xhttp.open("GET", "emails.php?c=pumpq", true);
		xhttp.send();
	
	}
	
	function pumpq()
	{
		let num = parseInt(document.getElementById('numleft').innerHTML);
		while (num-- > 0) {
			console.log('Next!!');
			sendNext();
		}
	}

	pumpq();
	// -->
	</script>
	</body>
	</html>
	<?php
	
}

function prgCleanForm()
/*
 * prg = post/redirect/get
 *
 * Called to get browser to ask for picklist after a post
 *
 */
{
	$get = "emails.php";
	header("Location: ".$get);
	exit;
}

//var_dump($_FILES);
//echo('<hr>');
//var_dump($_REQUEST);
//echo('<hr>');

if (isset($_REQUEST['c'])) {
	switch($_REQUEST['c']) {
		case 'count':
			echoCountWhere();
			exit;
		case 'names':
			echoNames();
			exit;
		case 'email':
			//sendMail();
			buildMailQ();
			showProcessEmailQ();
			exit;
			//if (!retraceBreadcrumb())
			//	prgCleanForm();
		case 'pumpq':
			sendNextMail();
			exit;
			
			
	}
}


setupEmailRun();

?>
