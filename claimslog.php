<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

/*
 * I B A U K   -   S C O R E M A S T E R
 *
 * I handle claims log reporting
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
require_once('recalc.php');
require_once('scoring.php');

function entrantClaimsLog($entrant) {

    global $DB,$TAGS,$KONSTANTS;
	    
    $rname = getValueFromDB("SELECT RiderName FROM entrants WHERE EntrantID=".$entrant,"RiderName","");
    $sql = "SELECT BonusID,OdoReading,ClaimTime FROM claims WHERE EntrantID=$entrant ORDER BY ClaimTime,LoggedAt";
    $R = $DB->query($sql);
    $res = '<div class="claimslog">';
    $res .= '<style>* {font-size: calc(14pt + 1vmin);} td {padding-right: 1em;}.spacert { padding-right: 3em; } s{background-color: lightgray;}</style>';
    $res .= '<h2>'.getSetting('clgHeader',$TAGS['clg_Header'][0]).'</h2>';
    $res .= '<form method="get" action="claimslog.php">';
    $res .= '<p>#<input type="number" style="width:3em;" name="entrant" min="1" onchange="this.form.submit();" value="'.$entrant.'"> '.$rname.'</p>';
    $res .= '</form>';
    $res .= '<table>';
    $res .= '<tbody>';
    $nclaims = 0; $nbonuses = 0;
    $bonusesclaimed = [];
    while ($rd = $R->fetchArray()) {
        $nclaims++;
        if (!isset($bonusesclaimed[$rd['BonusID']])) {
            $nbonuses++;
            $bonusesclaimed[$rd['BonusID']] = 1;
        } else 
            $bonusesclaimed[$rd['BonusID']]++;
    }
    $R->reset();
    while ($rd = $R->fetchArray()) {
        $res .= '<tr>';
        $bonusid = str_replace('-','',$rd['BonusID']);
        $s1 = ''; $s2 ='';
        if (--$bonusesclaimed[$bonusid] > 0) {
            $s1 = '<s>';
            $s2 = '</s>';
        }
        $res .= '<td>'.$s1.$bonusid.$s2.'</td>';
        $bd = getValueFromDB("SELECT BriefDesc FROM bonuses where BonusID='".$bonusid."'","BriefDesc","");
        $res .= '<td class="spacert">'.$s1.$bd.$s2.'</td>';
        $res .= '<td class="spacert">'.$s1.$rd['OdoReading'].$s2.'</td>';
        $res .= '<td>'.$s1.logtime($rd['ClaimTime']).$s2.'</td>';
        $res .= '</tr>';
    }
    $res .= '</tbody></table>';
    $res .= '<p>'.getSetting('clgClaimsCount',$TAGS['clg_NumberOfClaims'][0]).': <strong>'.$nclaims.'</strong></p>';
    if ($nclaims != $nbonuses)
        $res .= '<p>'.getSetting('clgBonusCount',$TAGS['clg_NumberOfClaims'][1]).': <strong>'.$nbonuses.'</strong></p>';
    $res .= '</div>';

    return $res;

}

function emitDecisionFrame() {

    global $TAGS, $KONSTANTS;

    echo('<div id="ebc_decide" style="display:none; position:absolute; top: 1em; left: 0; width:100%; height: 100%;">');
    echo('<div id="ebc_decide_panel" style="padding-left:.5em;">');
    echo('<h2>'.$TAGS['ebc_JudgeOrLeave'][0].'</h2>');
    echo('<form action="claimslog.php" method="post">');
    echo('<input type="hidden" name="ebc" value="save">');
    echo('<input type="hidden" id="ebc_claimid"  name="claimid" value="">');
    echo('<input type="hidden" name="decision" id="ebc_decision" value="-1">');
    echo('<label for="ebc_entrant">'.$TAGS['ebc_HdrEntrant'][0].'</label> ');
    echo('<input type="text" readonly id="ebc_entrant" name="EntrantID" value=""> ');
    echo('<span id="ebc_entrant_name" style="font-weight:bold;"></span> ');

    echo('<label for="ebc_bonus">'.$TAGS['ebc_HdrBonus'][0].'</label> ');
    echo('<input type="text" readonly id="ebc_bonus" name="BonusID" value="" ondblclick="this.readOnly=!this.readOnly;" onchange="fetchBonus(this.value);"> ');
    echo('<span id="ebc_bonus_name" style="font-weight:bold;" ></span> ');

    // Need to pass this through but don't care about it here
    echo('<input type="hidden" id="ebc_odo" name="OdoReading" value="">');

    echo('<label for="ebc_claimtime">'.$TAGS['ebc_HdrClaimedAt'][0].'</label> ');
    echo('<input type="hidden" id="ebc_claimtime" name="ClaimTime" value=""> ');
    echo('<span id="ebc_claimtime_show" style="font-weight: bold;"></span><br>');
    echo('<span id="ebc_notes"></span> <span id="ebc_flags"></span><br>');

    echo('<div id="restbonusline"></div>');

    if (getSetting('useBonusQuestions','false')=='true') {
        echo('<span id="ebc_question"></span><br>');
    }
    echo('<input type="hidden" id="ebc_photo" name="photo" value="">');
    emitDecisionsTable();

    echo('<div id="imgdiv" style="text-align: center; float: left; cursor: se-resize; border: solid;" title="'.$TAGS['ebc_JudgeThis'][1].'">');
    echo('<img id="imgdivimg" onclick="cycleImageSize(this);" data-size="0" style="width:512px; ">');

    echo('<div id="imgdivs" style="width: 512px; cursor: pointer;">'); // Same as width above
    //echo('<img style="width:96px;" src="ebcimg\img-6-B1-56.jpg">');
    echo('</div>');

    echo('</div>');

    echo('<div id="bimgdiv" style="text-align: center; float:right;" title="'.$TAGS['ebc_RallyPhoto'][1].'">');
    echo('<img style="width:512px;">');
    echo('</div>');

    echo('</form>');

    echo('</div> <!-- panel -->');
    echo('</div>');

}

function emitDecisionsTable() {

    global $DB, $TAGS;

    $R = $DB->query("SELECT RejectReasons FROM rallyparams");
    $rd = $R->fetchArray();
    $rejects = explode("\n",$rd['RejectReasons']);

    echo('<div>');
    echo('<span title="'.$TAGS['ebc_DoCancel'][1].'">');
    echo('<input type="button" name="cancel" value="'.$TAGS['ebc_DoCancel'][0].'" onclick="submitClaimDecision(this)" class="judge" data-value="-2"> ');
    echo('</span>');

    echo('<span class="hide" title="'.$TAGS['BonusPoints'][1].'">');
    echo('<label for="ebc_GetPoints">'.$TAGS['BonusPoints'][0].'</label> ');
    echo('<input type="hidden" id="ebc_AskPoints" name="AskPoints">');
    echo('<input type="number" id="ebc_points" name="Points">');
    echo('</span> ');

    echo('<span class="hide" title="'.$TAGS['RestMinutesLit'][1].'">');
    echo('<label for="RestMinutes">'.$TAGS['RestMinutesLit'][0].'</label> ');
    echo('<input type="hidden" id="ebc_AskMinutes" name="AskMinutes">');
    echo('<input type="number" id="ebc_restminutes" name="RestMinutes">');
    echo('</span> ');


    echo('<span title="'.$TAGS['ebc_DoAccept'][1].'">');


    echo('<input type="button" id="goodclaim" name="decision" data-value="0" value="'.$TAGS['ebc_DoAccept'][0].'" onclick="submitClaimDecision(this)" class="judge">' );

    echo(' <input type="text" title="'.$TAGS['ebc_JudgesNotes'][1].'" placeholder="'.$TAGS['ebc_JudgesNotes'][0].'" id="JudgesNotes" name="JudgesNotes" style="width:30em; max-width:100vw;">');
    echo('<input type="hidden" id="ebc_Evidence" name="Evidence">');

    	// This block is concerned with individual bonus percentage penalty, not 'magic' penalties.
	if (getSetting('usePercentPenalty','false')=='true') {
        $pct = intval(getSetting('valPercentPenalty','0'));
		echo('<input type="hidden" id="valPercentPenalty" value="');
		echo($pct.'">');
		$chk = '';
		echo('<span title="'.$TAGS['cl_PercentPenalty'][1].'">');
		echo('<input type="hidden" id="PercentPenalty" name="PercentPenalty">');
        echo(' &nbsp;&nbsp;<input type="button" name="decision" data-value="0" ');
        echo('value="'.$pct.'% '.$TAGS['cl_PercentPenalty'][0].'" class="judge" ');
        echo('onclick="applyPercentPenalty(true);submitClaimDecision(this);">');
		echo('</span>');
	}
	


    echo('<br></span>');
    echo('<span title="'.$TAGS['ebc_DoReject'][1].'">');
    foreach($rejects as $ln) {
        $xx = explode("=",$ln);
        if (isset($xx[1])) {
            echo('<input type="button" id="badclaim'.$xx[0].'" data-value="'.$xx[0].'" value="'.$xx[1].'" onclick="submitClaimDecision(this)" class="judge"> ');
        }
    }
    echo('</span>');
    echo('</div>');

    //$R->close();
}
function emitEBCjs() {

    $reloadseconds = getSetting('claimsReloadEBC',30); // Reload the EBC claims page after this number of seconds, unless judging a claim

    echo('<script>const RELOADSECS = '.$reloadseconds.';var RELOADOK = true;</script>');
?>
<script>
    function countup(secs) {
	var sex = 0;
	var spo = document.querySelector('#countdown');
	setInterval(function() {
		sex++;
		var x = 't=' + secs + ' : ' + sex;
		spo.textContent = x;
	},1000);
}

    function fetchRB(rbObj) {
        
        console.log('rb: '+JSON.stringify(rbObj));
        let url = "restbonuses.php?c=rb";
        url += "&EntrantID="+rbObj.entrant;
        url += "&BonusID="+rbObj.bonus;
        url += "&ClaimTime="+rbObj.claimtime;
        url += "&RestMins="+rbObj.mins;
        url += "&OdoReading="+rbObj.odo;

        xhttp = new XMLHttpRequest();
    	xhttp.onreadystatechange = function() {
	    	if (this.readyState == 4 && this.status == 200) {
                console.log(">>"+this.responseText+"<<");
                let res = JSON.parse(this.responseText);
                console.log(res.result);
                if (res.hasOwnProperty('error')) {
                    console.log('Error is '+res.error);
                    let rbdiv = document.querySelector('#restbonusline');
                    rbdiv.classList.add('yellow');
                    rbdiv.innerHTML = res.error;
                }
                if (res.hasOwnProperty('info')) {
                    console.log('info is '+res.info);
                    let rbdiv = document.querySelector('#restbonusline');
                    rbdiv.classList.add('green');
                    rbdiv.innerHTML = res.info;
                }
                if (res.hasOwnProperty('photo')) {
                    console.log('photo is '+res.photo);
                    let bimgdiv = document.querySelector('#bimgdiv');
                    let bimg = document.querySelector('#bimgdiv img');
                    bimgdiv.title = "⁉";
                    bimg.src = res.photo;
                }
			}
		};
		xhttp.open("GET", url, true);
	    xhttp.send();

    }
    function answerQuestion(obj) {

        let pts = document.getElementById('ebc_points');
        let pv = parseInt(pts.value);
        let qv = parseInt(document.getElementById('valBonusQuestions').value);
        console.log('Answering question: pv='+pv+' qv='+qv+' checked='+obj.checked);
        if (obj.checked)
            pv += qv;
        else
            pv -= qv;
        pts.value = pv;
        let qa = document.getElementById('QuestionAnswered');
        qa.value = 1;
    }

    async function bonusCountOK(e,b) {
        await new Promise(resolve => setTimeout(resolve,1000));
        console.log('returning bcok = false');
        return false;
    }
    function swapimg(event) {

        console.log('Swapping img '+event.currentTarget.id);
        let img = document.getElementById(event.currentTarget.id);
        let main = document.getElementById('imgdivimg');
        main.src = img.src;
    }
    function showPhotoArray(photos) {

        let iadiv = document.getElementById('imgdivs');
        iadiv.innerHTML = '';
        let plen = photos.length;
        if (plen < 2) return;
        let imgwidth = (100 / plen);
        for (let i = 0; i < plen; i++) {
            let img = document.createElement('img');
            img.id = "ephoto"+i;
            img.src = photos[i];
            img.style = "width:"+imgwidth+"%;";
            img.addEventListener('click',swapimg,false);
            iadiv.appendChild(img);
        }
    }

    function sleep(ms) {
        setTimeout(sleepnot,ms);   
    }
    function sleepnot() {}

    function showClaimEBC(tr) {
        RELOADOK = false;
        let claimid = tr.getAttribute('data-claimid');
        let entrant = tr.getAttribute('data-entrant');
        
        let excludeClaim = tr.getAttribute('data-excludeclaim');

        let decider = document.getElementById('ebc_decide');
        let log = document.getElementById('ebc_log');
        let img = document.getElementById('imgdiv').firstChild;
        let photos = tr.getAttribute('data-photo').split(',');
        console.log(photos);
        let is =  photos[0];
        let iss = tr.getAttribute('data-photo');
        img.src = is; // Might be empty
        showPhotoArray(photos);
        let bis = tr.getAttribute('data-bphoto');
        if (bis != '') {
            let bimg = document.getElementById('bimgdiv').firstChild;
            bimg.src = 'images/bonuses/'+bis;
        }
        let ebcpoints = document.getElementById('ebc_points');
        ebcpoints.value = tr.getAttribute('data-points');
        let ap = tr.getAttribute('data-askpoints');
        document.getElementById('ebc_AskPoints').value = ap;
        if (ap == '1') 
            ebcpoints.parentNode.classList = '';
        else
            ebcpoints.parentNode.classList = 'hide';
        let ebcmins = document.getElementById('ebc_restminutes');
        ebcmins.value = tr.getAttribute('data-restminutes');
        ap = tr.getAttribute('data-askminutes');
        document.getElementById('ebc_AskMinutes').value = ap;
        if (ap == '1') 
            ebcmins.parentNode.classList = '';
        else
            ebcmins.parentNode.classList = 'hide';
        
        let default_button = 'goodclaim';
        let bcok = tr.getAttribute('data-bonuscountok') == '1';
        console.log("bcok is "+bcok);
        let tooFar = tr.getAttribute('data-toofar') == '1';

        document.getElementById('goodclaim').disabled = false;
        if (excludeClaim != '') {
            default_button = 'badclaim'+document.getElementById('ignoreClaimDecision').value;
            console.log('disabling good claim');
            document.getElementById('goodclaim').disabled = true;
            document.getElementById('JudgesNotes').value = excludeClaim;
        } else if (tooFar) {
            document.getElementById('JudgesNotes').value = document.getElementById('distanceLimitExceeded').value;
            default_button = 'badclaim'+document.getElementById('ignoreClaimDecision').value;
        } else if (!bcok) {
            document.getElementById('JudgesNotes').value = document.getElementById('bonusClaimsExceeded').value;
            default_button = 'badclaim'+document.getElementById('ignoreClaimDecision').value;
        } else if (tr.getAttribute('data-reclaimok') == '0') {
            document.getElementById('JudgesNotes').value = document.getElementById('bonusReclaimNG').value;
            default_button = 'badclaim'+document.getElementById('bonusReclaims').value;
        } else if (photos.length > 1) {
            document.getElementById('JudgesNotes').value = document.getElementById('multiplePhotosLit').value;
            default_button = 'badclaim'+document.getElementById('ignoreClaimDecision').value;
        } else if (is == '') {
            document.getElementById('JudgesNotes').value = document.getElementById('missingPhotoLit').value;
            default_button = 'badclaim'+document.getElementById('missingPhotoDecision').value;
        }


        document.getElementById('ebc_Evidence').value = tr.getAttribute('data-evidence');
        document.getElementById('ebc_photo').value = iss;
        document.getElementById('ebc_claimid').value = claimid;
        document.getElementById('ebc_entrant').value = entrant;
        document.getElementById('ebc_entrant_name').innerHTML = tr.firstChild.innerHTML;
        document.getElementById('ebc_bonus').value = tr.children[1].innerHTML;
        document.getElementById('ebc_bonus_name').innerHTML = tr.children[1].getAttribute('title');
        document.getElementById('ebc_odo').value = tr.getAttribute('data-odo');
        document.getElementById('ebc_claimtime').value = tr.getAttribute('data-claimtime');

        let rbObj = {};
        rbObj.entrant = entrant;
        rbObj.bonus = tr.children[1].innerHTML;
        rbObj.claimtime = tr.getAttribute('data-claimtime');
        rbObj.mins = ebcmins.value;
        rbObj.odo = tr.getAttribute('data-odo');
        fetchRB(rbObj);

        let xx = tr.getAttribute('data-odo') + ', ' + tr.getAttribute('data-claimtime-show');
        let xy = ' onclick="alert(this.title);" ';
        let yy = '<span ' + xy + 'title="' + tr.getAttribute('data-evidence') + '">' + xx;
        yy += ' <span style="cursor:pointer;">&nbsp;&nbsp; &#9993; &nbsp;&nbsp;</span></span>';

        document.getElementById('ebc_claimtime_show').innerHTML = yy;

        document.getElementById('ebc_notes').innerHTML = tr.getAttribute('data-notes');
        let flagsregion = document.getElementById('ebc_flags');
        let flags = tr.getAttribute('data-flags');
        if (tr.getAttribute('data-team') != '0') {
            flags += '2';
        }
        for (var i = 0; i < flags.length; i++) {
            let img = document.createElement('img');
             switch(flags.charAt(i)) {
                 case '2':
                    img.src = "images/alertteam.png";
                    img.setAttribute('alt','2');
                    img.setAttribute('title',EBC_Flag2);
                    break;
                 case 'A':
                    img.src = "images/alertalert.png";
                    img.setAttribute('alt','!');
                    img.setAttribute('title',EBC_FlagA);
                    break;
                 case 'B':
                    img.src = "images/alertbike.png";
                    img.setAttribute('alt','B');
                    img.setAttribute('title',EBC_FlagB);
                    break;
                 case 'D':
                    img.src = "images/alertdaylight.png";
                    img.setAttribute('alt','D');
                    img.setAttribute('title',EBC_FlagD);
                    break;
                 case 'F':
                    img.src = "images/alertface.png";
                    img.setAttribute('alt','F');
                    img.setAttribute('title',EBC_FlagF);
                    break;
                 case 'N':
                    img.src = "images/alertnight.png";
                    img.setAttribute('alt','N');
                    img.setAttribute('title',EBC_FlagN);
                    break;
                 case 'R':
                    img.src = "images/alertrestricted.png";
                    img.setAttribute('alt','R');
                    img.setAttribute('title',EBC_FlagR);
                    break;
                 case 'T':
                    img.src = "images/alertreceipt.png";
                    img.setAttribute('alt','T');
                    img.setAttribute('title',EBC_FlagT);
                    break;
             }
             if (img.getAttribute('alt') != '') {
                 img.setAttribute('class','icon');
                 flagsregion.appendChild(img);
             }
        }
        let qq = tr.getAttribute('data-question');
        if (qq != '') {
            let ebcq = document.getElementById('ebc_question');
            if (ebcq) {
                let xx = '<span class="question">'+qq+'? </span>';
                xx += '<input type="hidden" name="QuestionAsked" value="1">';
                xx += '<input class="AnswerSupplied" readonly type="text" name="AnswerSupplied" id="AnswerSupplied"';
                let sa = tr.getAttribute('data-extra');
                let ca = tr.getAttribute('data-answer');
                xx += ' value="'+sa+'" > ';
                let chk = '';
                if (sa.toLowerCase() === ca.toLowerCase()) {
                    chk = ' checked';
                    ebcpoints.value = parseInt(ebcpoints.value) + parseInt(document.getElementById('valBonusQuestions').value);

                }
                xx += '<input type="checkbox" name="QuestionAnswered" '+chk+' onchange="answerQuestion(this);"> ';
                xx += '<span id="CorrectAnswer">&#8773; '+ca+'</span>';
                ebcq.innerHTML = xx;
            }
        }
        log.style.display = "none";
        decider.style.display = "block";
        document.getElementById(default_button).focus();
    }
    function showFirstClaim() {

        let rows = document.getElementsByTagName('tr');
        showClaimEBC(rows[1]);  // 1 not 0. 0 = header row

    }
    function cycleImageSize(img) {
        let szs = ['512px','100%'];
        let sz = parseInt(img.getAttribute('data-size')) + 1;
        if (sz >= szs.length) {
            sz = 0;
        }
        console.log('Setting image size to '+ sz);
        let szsx = szs[sz];

        /** Need to hide the rally book photo when maximising the claim image */
        let bimg = document.getElementById('bimgdiv');
        if (szsx =='100%')
            bimg.style.display = 'none';
        else
            bimg.style.display = 'inline';

        img.style.width = szsx;
        img.setAttribute('data-size',sz);
    }
    function cancelClaimDecision() {
        RELOADOK = true;
        setTimeout(reloadPage,1000);

        let decider = document.getElementById('ebc_decide');
        let log = document.getElementById('ebc_log');
        decider.style.display = "none";
        log.style.display = "initial";
    }
    function submitClaimDecision(btn) {
        let code = btn.getAttribute('data-value');
        let frm = btn.form;
        frm.ebc_decision.value = code;
        //alert('Submitted - '+code);
        frm.submit();
    }
    // This won't actually be called as there are no input fields or anywhere to catch a keystroke
    function testkey(tr) {
        if (event.key == ' ') {
            showClaimEBC(tr);
        }
    }

    // I fetch the description of a bonus
    function fetchBonus(bonusCode) {

        console.log("Fetching bonus "+bonusCode);
        let url = 'bonuses.php?c=getname&bid='+encodeURIComponent(bonusCode);
        console.log("Using url "+url);
        fetch(url)
            .then(res => res.json())
        	.then(function (res) {
                let name = '!!!!';
                let namespan = document.getElementById('ebc_bonus_name');
 	    	    console.log(res);
		        name = res.briefDesc;
                console.log('omg == '+name);
                if (namespan) namespan.innerText = name;
    		});
    }


    function reloadPage() {
        if (!RELOADOK) {
            return;
        }
        console.log("RELOADING");
        window.location.reload(true);
    }

    // Below here is executed once on load

    var datetime = "LastSync: " + new Date().toLocaleString();
    console.log(datetime);
    countup(RELOADSECS);
    setTimeout(reloadPage,RELOADSECS * 1000);


    document.onkeydown = function(evt) {
    evt = evt || window.event;
    var isEscape = false;
    if ("key" in evt) {
        isEscape = (evt.key === "Escape" || evt.key === "Esc");
    } else {
        isEscape = (evt.keyCode === 27);
    }
    if (isEscape) {
        cancelClaimDecision();
//        alert("Escape");
    }
};
</script>
<?php
}
function saveEBClaim($inTransaction) {

    global $DB,$KONSTANTS;

    $CurrentLeg = getValueFromDB("SELECT CurrentLeg FROM rallyparams","CurrentLeg","1");

    $processed = ($_REQUEST['decision'] < 0 ? 0 : 1);

    $odoreading = intval($_REQUEST['OdoReading']);
    $claimtime = $_REQUEST['ClaimTime'];
    $entrant = intval($_REQUEST['EntrantID']);

    $sqlx = "SELECT OdoReading FROM claims WHERE EntrantID=$entrant AND ClaimTime < '$claimtime' ORDER BY ClaimTime DESC LIMIT 1";
    $lastodo = intval(getValueFromDB($sqlx,"OdoReading","$odoreading"));

    if (abs($odoreading - $lastodo) > intval(getSetting("maxOdoGap",1000)) ){
        $odoreading = $lastodo;
    }

    if (!$inTransaction)
        $DB->exec("BEGIN IMMEDIATE TRANSACTION");

    $sqlx = "UPDATE ebclaims SET Processed=".$processed.", Decision=".$_REQUEST['decision'];
    $sqlx .= " WHERE Processed=0 ";
    $sqlx .= " AND rowid=".$_REQUEST['claimid'];
    $DB->exec($sqlx);

    if ($DB->changes() <> 1) {
        if (!$inTransaction)
            $DB->exec('COMMIT TRANSACTION');
            return;
    }
    $sqlx = "INSERT INTO claims(LoggedAt,ClaimTime,EntrantID,BonusID,OdoReading,Decision,Photo,Points,RestMinutes,AskPoints,AskMinutes,Leg";
    if (isset($_REQUEST['QuestionAsked']))
        $sqlx .= ",QuestionAsked";
    if (isset($_REQUEST['AnswerSupplied']))
        $sqlx .= ",AnswerSupplied";
    if (isset($_REQUEST['QuestionAnswered']))
        $sqlx .= ",QuestionAnswered";
    if (isset($_REQUEST['MagicWord']))
        $sqlx .= ",MagicWord";
    if (isset($_REQUEST['JudgesNotes']))
        $sqlx .= ",JudgesNotes";
    if (isset($_REQUEST['Evidence']))
        $sqlx .= ",Evidence";
    if (isset($_REQUEST['PercentPenalty']))
        $sqlx .= ",PercentPenalty";
    $sqlx .= ") VALUES(";
    $dtn = new DateTime("now",new DateTimeZone($KONSTANTS['LocalTZ']));
	$loggedat = $dtn->format('c');
    $sqlx .= "'".$loggedat."'";
    $sqlx .= ",'".$_REQUEST['ClaimTime']."'";
    $sqlx .= ",".$_REQUEST['EntrantID'];
    $sqlx .= ",'".$_REQUEST['BonusID']."'";
    $sqlx .= ",".$odoreading; // Current or previous depending on gap
    $sqlx .= ",".$_REQUEST['decision'];
    $sqlx .= ",'".$_REQUEST['photo']."'";
    $sqlx .= ",".intval($_REQUEST['Points']);
    $sqlx .= ",".intval($_REQUEST['RestMinutes']);
    $sqlx .= ",".intval($_REQUEST['AskPoints']);
    $sqlx .= ",".intval($_REQUEST['AskMinutes']);
    $sqlx .= ",".$CurrentLeg;
    if (isset($_REQUEST['QuestionAsked']))
        $sqlx .= ",".$_REQUEST['QuestionAsked'];
    if (isset($_REQUEST['AnswerSupplied']))
        $sqlx .= ",'".$DB->escapeString($_REQUEST['AnswerSupplied'])."'";
    if (isset($_REQUEST['QuestionAnswered']))
        $sqlx .= ",1";
    if (isset($_REQUEST['MagicWord']))
        $sqlx .= ",'".$DB->escapeString($_REQUEST['MagicWord'])."'";
    if (isset($_REQUEST['JudgesNotes']))
        $sqlx .= ",'".$DB->escapeString($_REQUEST['JudgesNotes'])."'";
    if (isset($_REQUEST['Evidence']))
        $sqlx .= ",'".$DB->escapeString($_REQUEST['Evidence'])."'";
    if (isset($_REQUEST['PercentPenalty']))
        $sqlx .= ",".$_REQUEST['PercentPenalty'];
    $sqlx .= ")";

    if ($_REQUEST['decision'] >= 0) {
        error_log($sqlx);
        $DB->exec($sqlx);
        $claimid = $DB->lastInsertRowID();
        error_log("Claimid is ".$claimid);
    }

    if ($processed) {
        applyClaim($claimid,!$inTransaction);
	    updateTeamScorecards($_REQUEST['EntrantID']);
        rankEntrants(true);
        updateAutoClass($_REQUEST['EntrantID']);
    } else {
        // For sequences, need to set vars and call applyClaims
    }

    if (!$inTransaction)
        $DB->exec('COMMIT TRANSACTION');

}
function fmtEvidenceDate($dt) {

    $NA = 'n/a';
    $GoodCheck = '2022';

    if ($dt < $GoodCheck)
        return $NA;

    $zz = str_replace('Z',' Z',$dt);
    $zz = str_replace('+',' +',$zz);
    return str_replace('T',' ',$zz);
}

function listEBClaims() {
    global $DB,$TAGS,$KONSTANTS;

    $useQA = getSetting('useBonusQuestions','false')=='true';
    $valQA = intval(getSetting('valBonusQuestions','0'));
    $bonusReclaimNG = getSetting('bonusReclaimNG','Bonus claimed earlier, reclaim out of sequence');
    $bonusReclaims = getSetting('bonusReclaims','0');
    $ignoreClaimDecision = getSetting('ignoreClaimDecisionCode','9');
    $missingPhotoDecision = getSetting('missingPhotoDecisionCode','1');
    $bonusClaimsExceeded = getSetting('bonusClaimsExceeded','Claim limit exceeded');
    $distanceLimitExceeded = getSetting('distanceLimitExceeded','Distance limit exceeded');

    $currentLeg = getValueFromDB("SELECT CurrentLeg FROM rallyparams","CurrentLeg",1);

    $sql = "SELECT ebclaims.rowid,ebclaims.EntrantID,RiderName,PillionName,ebclaims.BonusID,xbonus.BriefDesc";
    $sql .= ",OdoReading,ClaimTime,ExtraField,StrictOK,xphoto.Image,Notes,Flags,TeamID";
    $sql .= ",ebclaims.AttachmentTime As PhotoTS, ebclaims.DateTime As EmailTS,ebclaims.LoggedAt,ebclaims.Subject";
    $sql .= ",xbonus.Points,xbonus.AskPoints,xbonus.RestMinutes,xbonus.AskMinutes,xbonus.Image as BImage,Question,Answer";
    $sql .= " FROM ebclaims LEFT JOIN entrants ON ebclaims.EntrantID=entrants.EntrantID";
    $sql .= " LEFT JOIN (SELECT BonusID,BriefDesc,Notes,IfNull(Flags,'') AS Flags,Points,AskPoints,RestMinutes,AskMinutes,";
    $sql .= "IfNull(Image,'') AS Image,IfNull(Question,'') AS Question,IfNull(Answer,'') AS Answer FROM bonuses";
    $sql .= " ) AS xbonus";
    $sql .= " ON ebclaims.BonusID=xbonus.BonusID  LEFT JOIN ";
    $sql .= " (SELECT EmailID,Group_concat(Image) As Image from ebcphotos GROUP BY EmailID) AS xphoto ON ebclaims.EmailID=xphoto.EmailID WHERE Processed=0 ORDER BY Decision DESC,FinalTime;";

    $R = $DB->query($sql);
    $claims = 0;
    while($R->fetchArray()) {
        $claims++;
    }
    $R->reset();
    startHtml($TAGS['AdmEBClaims'][0],$TAGS['AdmEBClaims'][0]);

    echo('<span id="countdown">&glj;</span>');
    emitEBCjs();
    emitDecisionFrame();

    echo('<div class="ebclaimslog" id="ebc_log">');

    echo('<h2>'.$TAGS['clg_EbcLogHdr'][0].'</h2>');
    if ($claims > 0) {
        echo('<button autofocus onclick="showFirstClaim()">'.$TAGS['clg_EbcJudge1'][0].'</button>');
        echo(' 1/'.$claims);
    } else {
        echo('<p>'.$TAGS['clg_EbcNoLog'][0].'</p>');
    }

    echo('<div class="ebclaimsitems">');

    echo('<input type="hidden" id="useBonusQuestions" value="'.$useQA.'">');
    echo('<input type="hidden" id="valBonusQuestions" value="'.$valQA.'">');
    echo('<input type="hidden" id="bonusReclaimNG" value="'.$bonusReclaimNG.'">');
    echo('<input type="hidden" id="bonusReclaims" value="'.$bonusReclaims.'">');
    echo('<input type="hidden" id="ignoreClaimDecision" value="'.$ignoreClaimDecision.'">');
    echo('<input type="hidden" id="missingPhotoDecision" value="'.$missingPhotoDecision.'">');
    echo('<input type="hidden" id="bonusClaimsExceeded" value="'.$bonusClaimsExceeded.'">');
    echo('<input type="hidden" id="distanceLimitExceeded" value="'.$distanceLimitExceeded.'">');
    echo('<input type="hidden" id="multiplePhotosLit" value="'.$TAGS['clg_ManyPhotos'][1].'">');
    echo('<input type="hidden" id="missingPhotoLit" value="'.$TAGS['clg_MissingPhoto'][1].'">');
    echo('<table><thead>');
    if ($claims > 0) {
        echo('<tr><th>Entrant</th><th>Bonus</th><th>Odo</th><th>Claimtime</th></tr>');
    }
    echo('</thead><tbody>');
    while ($rs = $R->fetchArray()) {
        $excludeClaim = "";
        if ("".$rs['BriefDesc']=="") {
            $rs['BriefDesc'] = $TAGS['clg_BadBonus'][0];
            $excludeClaim = $rs['BriefDesc'];
        }
        error_log($rs['rowid'].' == ['.$rs['Image'].']');
        echo('<tr data-claimid="'.$rs['rowid'].'" ');
        echo('data-entrant="'.$rs['EntrantID'].'" data-photo="'.$rs['Image'].'" ');
        $bphoto = '';
        if ($rs['BImage'] != '')
            $bphoto = rawurlencode($rs['BImage']);
        echo('data-bphoto="'.$bphoto.'" ');
        if ($rs['TeamID']=='0' && $rs['PillionName'] != '') {
            echo('data-team="-1"');
        } else {
            echo('data-team="'.$rs['TeamID'].'" ');
        }
        echo('data-bonus="'.$rs['BonusID'].'" data-odo="'.$rs['OdoReading'].'" ');
        echo('data-points="'.$rs['Points'].'" data-askpoints="'.$rs['AskPoints'].'"');
        echo('data-restminutes="'.$rs['RestMinutes'].'" data-askminutes="'.$rs['AskMinutes'].'"');
        echo('data-claimtime="'.$rs['ClaimTime'].'" ');
        $lt = [];
        preg_match('/<span[^>]*>([^<]+)/',logtime($rs['ClaimTime']),$lt);
        //logtime($rs['ClaimTime']);
        echo('data-claimtime-show="'.$lt[1].'" ');

        echo(' data-reclaimok="'.(bonusReclaimOK($rs['EntrantID'],$rs['BonusID'],$rs['ClaimTime'],$currentLeg) ? 1 : 0).'"');
        echo(' data-bonuscountok="'.(bonusCountOK($rs['EntrantID'],$rs['BonusID']) ? 1 : 0).'"');
        echo(' data-toofar="'.(excludeTooFar($rs['EntrantID'],$rs['OdoReading']) ? 1 : 0).'"');

        // Now store some timestamp evidence
        $ev = "Photo: ".fmtEvidenceDate($rs['PhotoTS']);
        $ev .= "&amp;#10;Claim: ".fmtEvidenceDate($rs['ClaimTime']);
        $ev .= "&amp;#10;Email: ".fmtEvidenceDate($rs['EmailTS']);
        $ev .= "&amp;#10;Rcvd: ".fmtEvidenceDate($rs['LoggedAt']);
        $ev .= "&amp;#10;&amp;#10;Subject: ".htmlspecialchars($rs['Subject']);
        echo('data-evidence="'.$ev.'" ');

        echo('data-bonusdesc="'.str_replace('"','&quot;',''.$rs['BriefDesc']).'" data-rider="'.str_replace('"','&quot;',$rs['RiderName']).'" ');
        echo('data-notes="'.str_replace('"','&quot;',''.$rs['Notes']).'" data-flags="'.$rs['Flags'].'" ');
        echo('data-extra="'.str_replace('"','&quot;',''.$rs['ExtraField']).'" ');
        echo('class="link ebc" ');
        echo('onkeydown="testkey(this)" ');
        echo('onclick="showClaimEBC(this)" ');
        if ($useQA) {
            $qx = "".$rs['Question'];
            $ax = "".$rs['Answer'];
            echo('data-question="'.str_replace('"','&quot;',$qx).'" ');
            echo('data-answer="'.str_replace('"','&quot;',$ax).'" ');
            echo('data-qval="'.$valQA.'" ');
        }
        echo(' data-excludeclaim="'.$excludeClaim.'" ');
        echo('>');
        echo('<td title="'.$rs['EntrantID'].'">'.$rs['RiderName']);
        if ($rs['PillionName'] != '')
            echo(' &amp; '.$rs['PillionName']);
        echo('</td>');
        echo('<td title="'.$rs['BriefDesc'].'">'.$rs['BonusID'].'</td>');
        echo('<td>'.$rs['OdoReading'].'</td>');
        echo('<td>'.logtime($rs['ClaimTime']).'</td>');
        echo('</tr>');
    }
    echo('</tbody></table>');
    echo('</div>');

    echo('</div>');
}

function prgListEBClaims()
/*
 * prg = post/redirect/get
 *
 * Called to get browser to ask for listing after a post
 *
 */
{
	$get = "claimslog.php?ebc";
	header("Location: ".$get);
	exit;
}

if (isset($_REQUEST['ebc'])) {
    if ($_REQUEST['ebc']=='save') {
        //print_r($_REQUEST);
        $inTransaction = false;
        saveEBClaim($inTransaction);
        prgListEBClaims();
    }
    listEBClaims();
    exit;
}

if (isset($_REQUEST['entrant'])) {
    echo(entrantClaimsLog($_REQUEST['entrant']));
}
?>
