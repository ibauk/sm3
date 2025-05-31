<?php

$HOME_URL = "admin.php";
require_once('common.php');

$authcode = "UtterGobbledygook and then some more";

function reset_entrant_data() {

    global $KONSTANTS;

    $startoption = getValueFromDB("SELECT StartOption FROM rallyParams","StartOption",$KONSTANTS['StartOptionMass']);
    $rallystart = '';

    if ($startoption == $KONSTANTS['StartOptionMass']) {
        $rallystart = getValueFromDB("SELECT StartTime FROM rallyparams","StartTime","");
    }

    $zapsql = "UPDATE entrants SET OdoRallyStart=null,OdoRallyFinish=null,CorrectedMiles=null,FinishTime=null,BonusesVisited=null,CombosTicked=null,TotalPoints=0,FinishPosition=0,ScoreX=null,RejectedClaims=null,RestMinutes=0,AvgSpeed=null,ReviewedByTeam=0,AcceptedByEntrant=0,LastReviewed=null";
    if ($rallystart == $KONSTANTS['StartOption1stClaim']) {
        $zapsql .= ",StartTime=null";
    } else { // Don't worry about cohorts for now
        $zapsql .= ",StartTime='".$rallystart."'";
    }
	ZapDBExec($zapsql);

}

function zap_claims() {


    ZapDBExec("DELETE FROM ebclaims");

    ZapDBExec("DELETE FROM ebcphotos");

    ZapDBExec("DELETE FROM claims");

}

function ZapDBExec($zapsql) {

    global $DB;

    $DB->exec($zapsql);
	if ($DB->lastErrorCode()<>0) {
		echo("SQL ERROR: ".$DB->lastErrorMsg().'<hr>'.$zapsql.'<hr>');
		$DB->exec('ROLLBACK');
		exit;
	}

}



function zap_rally_configuration() {

    ZapDBExec("DELETE FROM bonuses");
    ZapDBExec("DELETE FROM catcompound");
    ZapDBExec("DELETE FROM categories");
    ZapDBExec("DELETE FROM classes WHERE Class > 0");
    ZapDBExec("DELETE FROM cohorts WHERE Cohort > 0");
    ZapDBExec("DELETE FROM emailq");
    ZapDBExec("DELETE FROM legs");
    ZapDBExec("DELETE FROM magicwords");
    ZapDBExec("DELETE FROM sgroups");
    ZapDBExec("DELETE FROM speedpenalties");
    ZapDBExec("DELETE FROM teams WHERE TeamID > 0");
    ZapDBExec("DELETE FROM timepenalties");

    $sql = "UPDATE rallyparams SET StartTime=null,FinishTime=null,DBState=0,NumLegs=1,Cat1Label=null,Cat2Label=null,Cat3Label=null,Cat4Label=null,Cat5Label=null,Cat6Label=null,Cat7Label=null,Cat8Label=null,Cat9Label=null,isvirtual=0,RallyTitle=''";
    ZapDBExec($sql);

}



function DoTheReset() {

    $lvl = intval($_POST['zaplevel']);
    if ($lvl < 1) return;

    // At least level 1 then

   	startHtml("Reset database","Reset database",true);

    ZapDBExec("BEGIN TRANSACTION");

    echo('<p>Deleting claims ... ');
    zap_claims();
    echo('ok</p>');

    echo('<p>Resetting scorecards ... ');
    reset_entrant_data();
    echo('ok</p>');

    if ($lvl >= 2) {

        echo('<p>Deleting entrant records ... ');
        ZapDBExec("DELETE FROM entrants");
        echo("ok</p>");
        
        if ($lvl >= 3) {

            echo('<p>Doing full reset to initial state ... ');
            zap_rally_configuration();
            echo('ok</p>');
            echo('<p>You will need to check/set the state of settings for general configuration, EBC and email. I have not returned them to defaults.');
        }

    }
    ZapDBExec("COMMIT");

}

function ShowChoiceConfirmation($optval,$opttext) {

    echo('<div id="firstchoice'.$optval.'" class="hide">');
    echo('<hr><p>You have chosen to '.$opttext.'.</p>');
    echo('<p class="yellow">There is no undo facility if you go ahead with this!</p>');
    echo('<fieldset><label for="choice'.$optval.'">Are you really sure you want to do this?</label>');
    echo('<select id="choice'.$optval.'">');
    echo('<option value="0" selected>No!,Get me back to safety please</option>');
    echo('<option value="'.$optval.'">I know what I\'m doing, just get on with it</option>');
    echo('</select> <input type="button" id="choice'.$optval.'please" value="Do it now!" onclick="doit(this)"></fieldset>');
    echo('</div>');

}

function showResetDatabase() {

    global $authcode;

   	startHtml("Reset database","Reset database",true);

?>
<script>
    function doit(obj) {
        obj.disabled = true;
        let choice;
        console.log(obj.id);
        if (obj.id == "firstchoiceplease") choice = document.getElementById('firstchoice');
        if (obj.id == "choice1please") choice = document.getElementById('choice1');
        if (obj.id == "choice2please") choice = document.getElementById('choice2');
        if (obj.id == "choice3please") choice = document.getElementById('choice3');
        if (choice) 
            choice = choice.value;
        else
            choice = 0;
        if (choice == 0) {
            window.location.href = "/";
            return;
        }
        if (obj.id == "firstchoiceplease") {
            let c1 = document.getElementById('firstchoice'+choice);
            if (c1) c1.classList.remove('hide');
            return;
        } else {
            let frm = document.getElementById('zapper');
            if (!frm) return;
            let lvl = document.getElementById('zaplevel');
            if (!lvl) return;
            lvl.value = choice;
            frm.submit();
            return;
        }
        window.location.href = "/";
    }
</script>
<?php
    echo('<form id="zapper" action="reset.php" method="post">');
    echo('<input type="hidden" name="cmd" value="zap">');
    echo('<input type="hidden" name="zaplevel" id="zaplevel" value="1">');
    echo('<input type="hidden" name="authcode" value="'.urlencode($authcode).'">');
    echo('</form>');

    echo('<article class="resetdb">');
    echo('<h1>RESET THE DATABASE</h1>');
    echo('<p>This procedure will <strong>RESET THE DATABASE</strong> back to an initial state depending on the settings below.</p>');
    echo('<p>Once triggered, this procedure cannot be stopped and it <strong>CANNOT BE REVERSED</strong>.</p>');
    echo('<p>I offer three levels of reset:</p>');
    echo('<ol>');
    echo('<li>Remove all scoring info including claims. Rally is ready for live running.</li>');
    echo('<li>Remove all claims and entrants. Rally is ready for entrant loading before rally.</li>');
    echo('<li>Remove claims, entrants, bonuses, combos and other config data. Need to full configure rally.</li>');
    echo('</ol>');
    echo('<fieldset><label for="firstchoice">What is your desire at this stage?</label>');
    echo('<select id="firstchoice">');
    echo('<option value="0">Get me back to safety please</option>');
    echo('<option value="1">1 - Just clear out my testing claims, etc</option>');
    echo('<option value="2">2 - Clear all scoring and entrants</option>');
    echo('<option value="3">3 - I want to build everything from scratch</option>');
    echo('</select> <input type="button" id="firstchoiceplease" value="Do it now!" onclick="doit(this)"></fieldset>');

    ShowChoiceConfirmation(1,'clear out all bonus claims, clear the scorecards, reset start times and make the rally ready for a live start');
    ShowChoiceConfirmation(2,'clear out all bonus claims and DELETE THE ENTRANTS, leaving the rally ready to load the entrants');
    ShowChoiceConfirmation(3,'clear out EVERYTHING and build the rally from scratch');


    echo('</article>');

}

    //print_r($_POST);
    //echo('<br>'.$authcode.' == '.(urldecode($_POST['authcode'])==$authcode ? "true" : "false"));

if (isset($_POST['cmd']) && 
            isset($_POST['zaplevel']) && 
            isset($_POST['authcode']) && 
            urldecode($_POST['authcode'])==$authcode) {
    DoTheReset();
    exit;
}
showResetDatabase();

?>
