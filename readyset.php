<?php

$HOME_URL = "admin.php";
require_once('common.php');

function calcMaxHours($start,$finish) {

    $sdt = iso2dt($start);
    $fdt = iso2dt($finish);
    $dd = date_diff($fdt,$sdt);
    $hrs = $dd->format("%d") * 24;
    return $dd->format("%h")+$hrs;

}

function getYmlSetting($yml,$param) {

    $lines = explode("\n",$yml);
    for ($i = 0; $i < sizeof($lines); $i++) {
        $p = strpos($lines[$i],":");
        $x = substr($lines[$i],0,$p);
        if ($x == $param) return trim(substr($lines[$i],$p+1));
    }
    return "";
}

function iso2dt($iso) {

    $dt = DateTime::createFromFormat('Y\-m\-d\TH\:i',substr($iso,0,16)); // Strip everything after y-m-dTh:m
    return $dt;

}

function showDatetimes($start,$finish,$max) {

    $sdt = iso2dt($start);
    $fdt = iso2dt($finish);
    $sd = $sdt->format("Y-m-d");
    $fd = $fdt->format("Y-m-d");
    $sdx = $sdt->format("D, d F Y");
    $fdx = $fdt->format("D, d F Y");
    $shx = $sdt->format("H:i");
    $fhx = $fdt->format("H:i");
    $dd = date_diff($fdt,$sdt);
    
    $dys = $dd->format("%d");
    $hrs = $dd->format("%h")+($dys * 24);
    $dys++;
    if ($sd==$fd) {
        $xx = $sdx.' '.$shx.'-'.$fhx;
        $xx .= " (";
    } else {
        $xx = $sdx.' '.$shx.' - '.$fdx.' '.$fhx;
        $xx .= " ($dys day".($dys>1? 's':'').'; ';
    }
    $xx .= "$hrs hour".($hrs>1?'s':'').')';
    if ($max <> $hrs) $xx .= " <em>Max hours is $max</em>";
    return $xx;
}

function showReadySet() {


    global $DB, $TAGS, $KONSTANTS, $RP;

	startHtml("Ready set","Ready set",true);

    $autoFinisher = getSetting('autoFinisher','false') == 'true';
    $showExcludedClaims = getSetting('ignoreClaimDecisionCode','9');

    $autoStart = getValueFromDB("SELECT StartOption FROM rallyparams","StartOption","0")=='1';
    $localTZ = getValueFromDB("SELECT LocalTZ FROM rallyparams","LocalTZ","Europe/London");
    $reportKM = getValueFromDB("SELECT MilesKms FROM rallyparams","MilesKms","0") != "0";

    $startTime = getValueFromDB("SELECT StartTime FROM rallyparams","StartTime","0");
    $finishTime = getValueFromDB("SELECT FinishTime FROM rallyparams","FinishTime","0");
    $maxHours = getValueFromDB("SELECT MaxHours FROM rallyparams","MaxHours","0");

    
    $ebcsettings = getValueFromDB("SELECT ebcsettings FROM rallyparams","ebcsettings","");

    $imapServer = getYmlSetting($ebcsettings,"imapserver","");
    $imapLogin = getYmlSetting($ebcsettings,"login","");
    $imapPassword = getYmlSetting($ebcsettings,"password","") != "";
    $dontRun = getYmlSetting($ebcsettings,"dontrun","false") == "true";
    $notBefore = getYmlSetting($ebcsettings,"notbefore","");
    $testMode = getYmlSetting($ebcsettings,"testmode","false") == "true";
    $matchEmail = getYmlSetting($ebcsettings,"matchemail","false") == "true";

    $email = json_decode(getValueFromDB("SELECT EmailParams FROM rallyparams","EmailParams","{}"),true);

    $claims = getValueFromDB("SELECT count(*) AS rex FROM claims","rex","0");
    $ebclaims = getValueFromDB("SELECT count(*) AS rex FROM ebclaims","rex","0");
?>   
    <style>
        .readyset {
            display: flex;
            flex-direction: column;
        }
        .readyset field {
            margin: .2em;
        }
        .readyset label {
            display: inline-block;
            width: 20%;
            padding-right: .5em;
            text-align: right;
        }
        .readyset h1 {
            display: inline-block;
            margin: 1em auto 1em auto;
            font-size: 1.75em;
        }
        .readyset span {
            font-weight: bold;
        }
        .readyset .small {
            font-size: .8em;
        }
        .readyset .alert {
            background-color: yellow;
            color: black;
        }
    </style>
<?php
    echo('<article class="readyset">');
    echo('<h1>RALLY READINESS CHECK</h1>');

    echo('<p>If these settings are wrong, use [Rally setup &amp; config] to update the relevant values.</p>');
    echo('</form>');


    echo('<field><label for="startDate">Rally timings</label>');
    echo('<span id="startDate">'.showDatetimes($startTime,$finishTime,$maxHours));
    //echo(' ('.calcMaxHours($startTime,$finishTime)).' hours)';
    echo('</span></field>');


    echo('<field><label for="imapLogin">EBC address</label>');
    echo('<input type="text" id="imapLogin" name="imapLogin" value="'.$imapLogin.'" readonly>');
    echo('</field>');


    echo('<field><label for="dontRun">Processing emails</label>');
    echo('<select id="dontRun" ');
    if ($dontRun || $testMode) echo('class="alert"');
    echo('>');
    echo('<option ');
    if ($dontRun || !$imapPassword) echo(' selected');
    echo('>No, EMAILS ARE NOT BEING PROCESSED</option>');
    echo('<option ');
    if (!$dontRun && !$testMode) echo (' selected');
    echo('>Yes, emails are being processed for LIVE SCORING</option>');
    echo('<option ');
    if (!$dontRun && $testMode) echo (' selected');
    echo('>Yes, emails are being processed in TEST MODE ONLY</option>');
    echo('</select></field>');

    echo('<field><label for="mailEmatch">Email address mode</label>');
    echo('<select id="matchEmail" ');
    if (!$matchEmail) echo('class="alert"');
    echo('>');
    echo('<option ');
    if ($matchEmail) echo(' selected');
    echo('>Sender email address MUST MATCH rider record</option>');
    echo('<option ');
    if (!$matchEmail) echo (' selected');
    echo('>Emails are accepted from any address</option>');
    echo('</select></field>');

    if ($notBefore != "") {
        echo('<field class="small"><label for="notBefore">Ignore emails before</label>');
        echo('<span id="notBefore">'.$notBefore.'</span>');
        echo('</field>');
    }

    echo('<field class="small"><label for="imapServer">IMAP server</label>');
    echo('<span id="imapServer">'.$imapServer.'</span>');
    echo('</field>');

    echo('<field class="small"><label for="sendFrom">Send emails from</label>');
    echo('<span id="sendFrom">'.implode(", ",$email['SetFrom']).'</span>');
    echo('</field>');

    echo('<hr>');

    echo('<field><label for="startOption">Start option</label>');
    echo('<select id="startOption">');
    echo('<option ');
    if ($autoStart) echo(' selected');
    echo('>Automatic, start at first claim</option>');
    echo('<option ');
    if (!$autoStart) echo (' selected');
    echo('>Must complete check-out</option>');
    echo('</select></field>');

    echo('<field><label for="autoFinisher">Finisher status</label>');
    echo('<select id="autoFinisher" name="autoFinisher">');
    echo('<option ');
    if ($autoFinisher) echo(' selected');
    echo('>Automatic, claim by claim</option>');
    echo('<option ');
    if (!$autoFinisher) echo (' selected');
    echo('>Must complete check-in</option>');
    echo('</select></field>');

    echo('<field class="small"><label for="localTZ">Rally timezone</label>');
    echo('<span id="localTZ">'.$localTZ.'</span>');
    echo('</field>');

    echo('<field class="small"><label for="reportKM">Report distance in</label>');
    echo('<span id="reportKM">');
    if ($reportKM)
        echo("KILOMETRES");
    else
        echo("MILES");
    echo('</span>');
    echo('</field>');


    $rr = explode("\n",getValueFromDB("SELECT RejectReasons FROM rallyparams","RejectReasons",""));
    //print_r($rr);

    echo('<field class="small"><label for="showExcludedClaims">Report all decisions except</label>');
    echo('<span id="showExcludedClaims">'.$showExcludedClaims.'  ===  ');
    if (isset($rr[$showExcludedClaims-1])) 
        echo(' <em>'.$rr[$showExcludedClaims-1].'</em>');
    else
        echo('<em>no description available</em>');
    echo('</span></field>');

    echo('<hr>');

    if ($claims > 0 || $ebclaims > 0) {
        echo('<field><label for="claims" class="alert">Bonus claims (ebclaims)</label>');
        echo('<span id="claims" class="alert">'.$claims.' ('.$ebclaims.')</span></field>');
    }
    echo('</form>');
    echo("</article>");
}

showReadySet();
?>
