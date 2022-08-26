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
 *
 */

$HOME_URL = "admin.php";
require_once('common.php');
require_once('recalc.php');
require_once('scoring.php');

function entrantClaimsLog($entrant) {

    global $DB,$TAGS,$KONSTANTS;
	    
    $rname = getValueFromDB("SELECT RiderName FROM entrants WHERE EntrantID=".$entrant,"RiderName","");
    $sql = "SELECT BonusID,OdoReading,ClaimTime FROM claims WHERE EntrantID=$entrant ORDER BY ClaimTime";
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
    reset($R);
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
    echo('<input type="text" readonly id="ebc_bonus" name="BonusID" value=""> ');
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
    echo('<img onclick="cycleImageSize(this);" data-size="0" style="width:512px; ">');
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
    echo('<input type="button" name="cancel" value="'.$TAGS['ebc_DoCancel'][0].'" onclick="cancelClaimDecision()" class="judge"> ');
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

    echo(' <input type="text" title="'.$TAGS['ebc_JudgesNotes'][1].'" placeholder="'.$TAGS['ebc_JudgesNotes'][0].'" name="JudgesNotes" style="width:30em; max-width:100vw;">');

    echo('<br></span>');
    echo('<span title="'.$TAGS['ebc_DoReject'][1].'">');
    foreach($rejects as $ln) {
        $xx = explode("=",$ln);
        if (isset($xx[1])) {
            echo('<input type="button" data-value="'.$xx[0].'" value="'.$xx[1].'" onclick="submitClaimDecision(this)" class="judge"> ');
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

    function showClaimEBC(tr) {
        RELOADOK = false;
        let claimid = tr.getAttribute('data-claimid');
        let entrant = tr.getAttribute('data-entrant');
        let decider = document.getElementById('ebc_decide');
        let log = document.getElementById('ebc_log');
        let img = document.getElementById('imgdiv').firstChild;
        let is =  tr.getAttribute('data-photo');
        if (is == '') {
            img.src = "ebcimg/img-7-NARM-51.jpg";
        } else {
            img.src = is;
        }
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
        document.getElementById('ebc_photo').value = is;
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

        document.getElementById('ebc_claimtime_show').innerHTML = tr.getAttribute('data-odo') + ', ' + tr.getAttribute('data-claimtime-show');
        document.getElementById('ebc_notes').innerHTML = tr.getAttribute('data-notes');
        let flagsregion = document.getElementById('ebc_flags');
        let flags = tr.getAttribute('data-flags');
        if (tr.getAttribute('data-team') > '0') {
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
                xx += '<span id="CorrectAnswer">'+ca+'</span>';
                ebcq.innerHTML = xx;
            }
        }
        log.style.display = "none";
        decider.style.display = "block";
        document.getElementById('goodclaim').focus();
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

    $sqlx = "INSERT INTO claims(LoggedAt,ClaimTime,EntrantID,BonusID,OdoReading,Decision,Photo,Points,RestMinutes,AskPoints,AskMinutes";
    if (isset($_REQUEST['QuestionAsked']))
        $sqlx .= ",QuestionAsked";
    if (isset($_REQUEST['AnswerSupplied']))
        $sqlx .= ",AnswerSupplied";
    if (isset($_REQUEST['QuestionAnswered']))
        $sqlx .= ",QuestionAnswered";
    if (isset($_REQUEST['JudgesNotes']))
        $sqlx .= ",MagicWord";
    $sqlx .= ") VALUES(";
    $dtn = new DateTime(Date('Y-m-dTH:i:s'),new DateTimeZone($KONSTANTS['LocalTZ']));
	$loggedat = $dtn->format('c');
    $sqlx .= "'".$loggedat."'";
    $sqlx .= ",'".$_REQUEST['ClaimTime']."'";
    $sqlx .= ",".$_REQUEST['EntrantID'];
    $sqlx .= ",'".$_REQUEST['BonusID']."'";
    $sqlx .= ",".$_REQUEST['OdoReading'];
    $sqlx .= ",".$_REQUEST['decision'];
    $sqlx .= ",'".$_REQUEST['photo']."'";
    $sqlx .= ",".$_REQUEST['Points'];
    $sqlx .= ",".$_REQUEST['RestMinutes'];
    $sqlx .= ",".$_REQUEST['AskPoints'];
    $sqlx .= ",".$_REQUEST['AskMinutes'];
    if (isset($_REQUEST['QuestionAsked']))
        $sqlx .= ",".$_REQUEST['QuestionAsked'];
    if (isset($_REQUEST['AnswerSupplied']))
        $sqlx .= ",'".$DB->escapeString($_REQUEST['AnswerSupplied'])."'";
    if (isset($_REQUEST['QuestionAnswered']))
        $sqlx .= ",1";
    if (isset($_REQUEST['JudgesNotes']))
        $sqlx .= ",'".$DB->escapeString($_REQUEST['JudgesNotes'])."'";
    $sqlx .= ")";
    if (!$inTransaction)
        $DB->exec("BEGIN IMMEDIATE TRANSACTION");
    $DB->exec($sqlx);
    $claimid = $DB->lastInsertRowID();
    $DB->exec("UPDATE ebclaims SET Processed=1, Decision=".$_REQUEST['decision']." WHERE rowid=".$_REQUEST['claimid']);
    applyClaim($claimid,!$inTransaction);
	updateTeamScorecards($_REQUEST['EntrantID']);
    rankEntrants(true);
    updateAutoClass($_REQUEST['EntrantID']);

    if (!$inTransaction)
        $DB->exec('COMMIT TRANSACTION');

}
function listEBClaims() {
    global $DB,$TAGS,$KONSTANTS;

    $useQA = getSetting('useBonusQuestions','false')=='true';
    $valQA = intval(getSetting('valBonusQuestions','0'));

    $sql = "SELECT ebclaims.rowid,ebclaims.EntrantID,RiderName,PillionName,xbonus.BonusID,xbonus.BriefDesc";
    $sql .= ",OdoReading,ClaimTime,ExtraField,StrictOK,ebcphotos.Image,Notes,Flags,TeamID";
    $sql .= ",xbonus.Points,xbonus.AskPoints,xbonus.RestMinutes,xbonus.AskMinutes,xbonus.Image as BImage,Question,Answer";
    $sql .= " FROM ebclaims LEFT JOIN entrants ON ebclaims.EntrantID=entrants.EntrantID";
    $sql .= " LEFT JOIN (SELECT BonusID,BriefDesc,Notes,Flags,Points,AskPoints,RestMinutes,AskMinutes,Image,Question,Answer FROM bonuses";
    $sql .= " ) AS xbonus";
    $sql .= " ON ebclaims.BonusID=xbonus.BonusID  LEFT JOIN ebcphotos ON ebclaims.PhotoID=ebcphotos.rowid WHERE Processed=0 ORDER BY FinalTime;";

    $R = $DB->query($sql);
    $claims = 0;
    while($R->fetchArray()) {
        $claims++;
    }
    $R->reset();
    startHtml($TAGS['AdmEBClaims'][0],$TAGS['AdmEBClaims'][0]);

    emitEBCjs();
    emitDecisionFrame();

    echo('<div class="ebclaimslog" id="ebc_log">');
    echo('<h2>'.$TAGS['clg_EbcLogHdr'][0].'</h2>');
    if ($claims > 0) {
        echo('<button autofocus onclick="showFirstClaim()">'.$TAGS['clg_EbcJudge1'][0].'</button>');
    } else {
        echo('<p>'.$TAGS['clg_EbcNoLog'][0].'</p>');
    }

    echo('<div class="ebclaimsitems">');

    echo('<input type="hidden" id="useBonusQuestions" value="'.$useQA.'">');
    echo('<input type="hidden" id="valBonusQuestions" value="'.$valQA.'">');
    echo('<table><thead>');
    if ($claims > 0) {
        echo('<tr><th>Entrant</th><th>Bonus</th><th>Odo</th><th>Claimtime</th></tr>');
    }
    echo('</thead><tbody>');
    while ($rs = $R->fetchArray()) {
        echo('<tr data-claimid="'.$rs['rowid'].'" ');
        echo('data-entrant="'.$rs['EntrantID'].'" data-photo="'.$rs['Image'].'" ');
        echo('data-bphoto="'.rawurlencode($rs['BImage']).'" ');
        echo('data-team="'.$rs['TeamID'].'" ');
        echo('data-bonus="'.$rs['BonusID'].'" data-odo="'.$rs['OdoReading'].'" ');
        echo('data-points="'.$rs['Points'].'" data-askpoints="'.$rs['AskPoints'].'"');
        echo('data-restminutes="'.$rs['RestMinutes'].'" data-askminutes="'.$rs['AskMinutes'].'"');
        echo('data-claimtime="'.$rs['ClaimTime'].'" ');
        $lt = [];
        preg_match('/<span[^>]*>([^<]+)/',logtime($rs['ClaimTime']),$lt);
        //logtime($rs['ClaimTime']);
        echo('data-claimtime-show="'.$lt[1].'" ');
        echo('data-bonusdesc="'.str_replace('"','&quot;',$rs['BriefDesc']).'" data-rider="'.str_replace('"','&quot;',$rs['RiderName']).'" ');
        echo('data-notes="'.str_replace('"','&quot;',$rs['Notes']).'" data-flags="'.$rs['Flags'].'" ');
        echo('data-extra="'.str_replace('"','&quot;',$rs['ExtraField']).'" ');
        echo('class="link ebc" ');
        echo('onkeydown="testkey(this)" ');
        echo('onclick="showClaimEBC(this)" ');
        if ($useQA) {
            echo('data-question="'.str_replace('"','&quot;',$rs['Question']).'" ');
            echo('data-answer="'.str_replace('"','&quot;',$rs['Answer']).'" ');
            echo('data-qval="'.$valQA.'" ');
        }
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
