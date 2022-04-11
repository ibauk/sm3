/*
 * I B A U K   -   S C O R E M A S T E R
 *
 * score.js
 *
 * I provide automatic scoring in the browser.
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

"use strict";

/*
 *	2.1	Only autoset EntrantStatus to Finisher if status was 'ok' and CorrectedMiles > 0
 *	2.1	Multiple special groups; cleanup explanations
 *	2.1	Accept/Reject claim handling; 
 *	2.1 kms/mile, mile/kms handling
 *	2.1 Odo check trip reading - used though not stored
 *  2.2	Variable specials
 *	2.3	OdoScaleFactor SanityCheck
 *	2.4	Variable combos, flexible axes
 *	2.5	Reorder ordinary bonus visits, speeding
 *	2.7 Report rejected status on scorex for ordinary, special and combo bonuses
 *
 */
 

// No translateable literals below here


const KmsPerMile = 1.60934;

 
const EntrantDNS = 0;
const EntrantOK = 1;
const EntrantFinisher = 8;
const EntrantDNF = 3;

const COMPULSORYBONUS = '1';
const MUSTNOTMATCH = '2';

/* Compound calc rule types */
const CC_ORDINARYSCORE = 0;
const CC_UNTRIGDNF = 1;
const CC_IFTRIGDNF = 2;
const CC_PLACEHOLDER = 3;
const CC_ScorePoints = 0;
const CC_ScoreMults = 1;

const RULE_TRIGGERED = '1';
const RULE_NOT_TRIGGERED = '0';

const MMM_FixedPoints = 0;
const MMM_Multipliers = 1;
const MMM_PointsPerMile = 2;

const TPM_MultPerMin = 3;
const TPM_PointsPerMin = 2;
const TPM_FixedMult = 1;
const TPM_FixedPoints = 0;

/* Combinations */
const CMB_ScorePoints = 0;
const CMB_ScoreMults = 1;


/* Each simple bonus may be classified using
 * this number of axes (1,2,3). This reflects 
 * the database structure, it may not be
 * arbitrarily increased.
 */ 
/*const CALC_AXIS_COUNT = 3;*/

/* This is now overridden in bodyLoaded using DBVERSION */
var CALC_AXIS_COUNT = 9;
var COMBOS_USE_CATS = false;

// Compound category rules
const CAT_NumBonusesPerCatMethod = 0;
const CAT_ResultPoints = 0;
const CAT_ResultMults = 1;
const CAT_NumNZCatsPerAxisMethod = 1;
const CAT_ModifyBonusScore = 1;
const CAT_ModifyAxisScore = 0;

const CAT_OrdinaryScoringRule = 0;
const CAT_DNF_Unless_Triggered = 1;
const CAT_DNF_If_Triggered = 2;
const CAT_PlaceholderRule = 3;


/* Scoring method enum */
const SM_Manual = 0;
const SM_Simple = 1;
const SM_Compound = 2;

/* Show multipliers */
const SM_ShowMults = 1;
const SM_HideMults = 0;

/* Score explanation DOM id */
const SX_id	= "scorex";
const SX_StoreID = "scorexstore";

/* Tabbed display variables */
var tabLinks = new Array();
var contentDivs = new Array();

/* Time penalty specs */
const TPS_absolute		= 0;
const TPS_rallyDNF		= 1;
const TPS_entrantDNF	= 2;

/* Bonus display classes */
const class_showbonus	= ' showbonus ';
const class_rejected	= ' rejected ';
const class_checked		= ' checked ';
const class_unchecked	= ' unchecked ';

/* Don't create any elements with this id */
const NON_EXISTENT_BONUSID = 'zzyy23'; 

const DDAREA_id	= "ddarea";
const ORDINARY_BONUS_PREFIX = 'B';
const SPECIAL_BONUS_PREFIX = 'S';
const COMBO_BONUS_PREFIX = 'C';
const ORDINARY_BONUSES_VISITED = 'BonusesVisited';
const CONFIRMED_BONUS_MARKER	= '++';

// Nice flexible string formatting
if (!String.format) {
  String.format = function(format) {
    var args = Array.prototype.slice.call(arguments, 1);
    return format.replace(/{(\d+)}/g, function(match, number) { 
      return typeof args[number] != 'undefined'
        ? args[number] 
        : match
      ;
    });
  };
}


// Drag n drop stuff
const NODE_IS_DOCUMENT = 9;
var _el;


function dragOver(e) {
	if (isBefore(_el, e.target))
		e.target.parentNode.insertBefore(_el, e.target);
	else
		e.target.parentNode.insertBefore(_el, e.target.nextSibling);
	
	// This is a bit cheeky cos it ASSUMES that you're using DDAREA!
	document.getElementById(DDAREA_id).setAttribute('dropped',1);
}

function dragStart(e) {
  e.dataTransfer.effectAllowed = "move";
  e.dataTransfer.setData("text/plain", null); // Firefox
  _el = e.target;
}

function isBefore(el1, el2) {
  if (el2.parentNode === el1.parentNode)
    for (var cur = el1.previousSibling; cur && cur.nodeType !== NODE_IS_DOCUMENT; cur = cur.previousSibling)
      if (cur === el2)
        return true;
  return false;
}



// Alphabetic order below

function areYouSure(question)
{
	return window.confirm(question);
}



function bodyLoaded()
{
	
	var dbv = document.getElementById('DBVERSION');
	if (dbv)
		switch(parseInt(dbv.value))
		{
			case 3:
				CALC_AXIS_COUNT = 9;
				COMBOS_USE_CATS = true;
		}
	
	
	var isScoresheetpage = document.getElementById("scoresheetpage");
	var hasTabs = document.getElementById('tabs');
	console.log('bodyLoaded '+(hasTabs ? 'tabs' : ' no tabs'));
	if (hasTabs)
		tabsSetupTabs();
	
	trapDirtyPage();
	if (!isScoresheetpage)
		return;

	// If scorecard is dirty, get lock right away
	let cmd = document.getElementById('savescorebutton');
	if (cmd && !cmd.disabled)
		getScoreLock(cmd);
		
}

function bonusScoredOk(B)
/*
 * B is a checkbox reflecting the current state of the bonus
 *
 */
{
	return B.checked && (!B.hasAttribute('data-rejected') || B.getAttribute('data-rejected')=='0');
}


function calcAvgEfficiency()
/*
 * Calculate points per mile/km
 *
 */
{
	// NOT IMPLEMENTED YET

	let distance = document.getElementById('CorrectedMiles').value;
	let points = document.getElementById('TotalPoints').value;
	let ppm = 0;
	if (distance > 0 && points > 0) {
		ppm = (points / distance).toFixed(0);
	}
	return ppm;

}

function calcAvgSpeed()
{
	console.log('Calculating average speed');
	const msecsPerMinute = 60000;
	let speedDisplay = document.querySelector('#AvgSpeed');
	speedDisplay.value = '';
	let basicKms = parseInt(document.getElementById('BasicDistanceUnit').value) != 0;
	let isoStart = document.querySelector('#StartDate').value+'T'+document.querySelector('#StartTime').value+'Z';
	let isoFinish = document.querySelector('#FinishDate').value+'T'+document.querySelector('#FinishTime').value+'Z';
	let dtStart = new Date(isoStart);
	let dtFinish = new Date(isoFinish);
	let minsDuration = Math.abs(dtFinish - dtStart) / msecsPerMinute;
	//console.log('cas: mins='+minsDuration);
	/* Now add up rest minutes and store the result for posting to Entrant record */
	let specials = document.querySelectorAll('input[data-mins]');
	//console.log('cas: '+JSON.stringify(specials));
	let restMins = document.querySelector('#RestMinutes');
	
	minsDuration -= restMins.value;

	if (minsDuration < 1)
		return;
	let odoScale = parseFloat(document.querySelector('#OdoScaleFactor').value);
	if (odoScale < 0.5)
		odoScale = 1.0;

	let odoDistance = parseInt(document.querySelector('#CorrectedMiles').value);

	
	//console.log('cas: distance='+odoDistance);
	
	let hoursDuration = minsDuration / 60.0;
	let speed = odoDistance / hoursDuration;
	
	//console.log('Hrs='+hoursDuration+' Avg='+speed);
	
	

	let speedText = (Math.round(speed * 100) / 100).toFixed(2);
	
	speedDisplay.value = speedText + ' ' + (basicKms ? 'km/h' : 'mph');
}

function calcMileagePenalty()
{
	var CM = parseInt(document.getElementById('CorrectedMiles').value);
	var PMM = parseInt(document.getElementById('PenaltyMaxMiles').value);
	var PMMethod = parseInt(document.getElementById('MaxMilesMethod').value);
	var PMPoints = parseInt(document.getElementById('MaxMilesPoints').value);
	var PenaltyMiles = CM - PMM;
	
	if (PenaltyMiles <= 0) // No penalty
		return [0,0]; 
		
	//console.log('PM='+PenaltyMiles+'; Method='+PMMethod+'; Points='+PMPoints);
	switch (PMMethod)
	{
		case MMM_PointsPerMile:
			return [0 - PMPoints * PenaltyMiles,0];
		case MMM_Multipliers:
			return [0,PMPoints];
		default:
			return [0 - PMPoints,0];
	}
		
}





function calcSpeedPenalty(dnf)
/*
 * If parameter dnf is false then
 * This will return the number of penalty points (not multipliers) or 0
 * If highest match gives DNF, I return 0
 *
 * If parameter dnf is true then
 * If highest match give DNF, return true otherwise false
 *
 */
{
	let SP = document.getElementsByName('SpeedPenalty[]');
	let tmp = document.getElementById('CalculatedAvgSpeed');
	if (tmp == null)
		return (dnf ? false : 0);
	let speed = parseFloat(tmp.value);
	//console.log('Checking '+speed+' against '+SP.length+' speed penalty records');
	for (let i =0; i < SP.length; i++)
		if (speed >= parseFloat(SP[i].getAttribute('data-MinSpeed')))
		{
			console.log('Matched '+speed+' to '+SP[i].getAttribute('data-MinSpeed'));
			if (parseInt(SP[i].getAttribute('data-PenaltyType'))==1)
			{
				if (dnf)
					return true;
				else
					return 0; /* Penalty points */
			}
			if (dnf)
				return false;
			else
				return 0 - parseInt(SP[i].value);
			
		}
		return 0;
}

function calcTimePenalty()
{
	const OneMinute = 1000 * 60;
	var TP = document.getElementsByName('TimePenalty[]');
	var FT = new Date(document.getElementById('FinishDate').value + 'T' + document.getElementById('FinishTime').value+'Z');
	var  FTDate = new Date(FT);
	//console.log("TP: "+FTDate);
	for ( var i = 0 ; i < TP.length ; i++ )
	{
		var ds, de, dnf;
		switch(parseInt(TP[i].getAttribute('data-spec')))
		{
			case TPS_rallyDNF:
				dnf = new Date(document.getElementById('RallyTimeDNF').value+'Z');
				ds = dnf - parseInt(TP[i].getAttribute('data-start')) * 60000;
				de = dnf - parseInt(TP[i].getAttribute('data-end')) * 60000;
				break;
			case TPS_entrantDNF:
				dnf = new Date(document.getElementById('FinishTimeDNF').value+'Z');
				ds = dnf - parseInt(TP[i].getAttribute('data-start')) * 60000;
				de = dnf - parseInt(TP[i].getAttribute('data-end')) * 60000;
				break;
			default:
				ds = new Date(TP[i].getAttribute('data-start')+'Z');
				de = new Date(TP[i].getAttribute('data-end')+'Z');
		}
		
		//if (FT >= TP[i].getAttribute('data-start') && FT <= TP[i].getAttribute('data-end'))
		if (FT >= ds && FT <= de)
		{
			var PF = parseInt(TP[i].getAttribute('data-factor'));
			var PM = parseInt(TP[i].getAttribute('data-method'));
			var PStartDate = ds; //new Date(TP[i].getAttribute('data-start'));
			var Mins = 1 + (Math.abs(FTDate - PStartDate) / OneMinute);
			//console.log(PStartDate + ' == ' + FTDate + ' == ' + PM + '=' + TPM_PointsPerMin + ' == ' + Mins);
			switch(PM)
			{
				case TPM_MultPerMin:
					return [0,0 - PF * Mins];
				case TPM_PointsPerMin:
					return [0 - PF * Mins,0];
				case TPM_FixedMult:
					return [0,0 - PF];
				default:
					return [0 - PF,0];
			}
		}
	}
	return [0,0];

}

function ccChangeRuletype(rtype) {

	let pm = document.getElementById('PointsMults');
	let np = document.getElementById('NPower');
	switch(rtype) {
		case CC_ORDINARYSCORE:
			pm.disabled = false;
			np.disabled = false;
			break;
		default:
			pm.value = CC_ScorePoints;
			pm.disabled = true;
			np.value = 0;
			np.disabled = true;
	}

}
function ccShowSelectAxisCats(axis,sel)
{
	var lst = 0;
	
	try {
		lst = document.getElementById('axis'+axis+'cats');
	} catch(err) {
		return;
	}
	
	var cats = lst.value.split(',');
	var optval = sel.options[sel.selectedIndex].value;
	while (sel.options.length > 0)
		sel.options.remove(0);
	for (var i = 0; i < cats.length; i++)
	{
		var f = cats[i].split('=');
		var opt = document.createElement("option");
		opt.text = f[1]+' ('+f[0]+')';
		opt.value = f[0];
		if (f[0]==optval)
			opt.selected = true;
		sel.options.add(opt);
	}
	if (sel.selectedIndex < 0 && sel.opt.length > 0)
		sel.selectedIndex = 0;

}

// Compound rule maintenance - switch between NZ & NC
function ccSwapCatMethod(method) {
	let cat = document.getElementById('selcat');
	if (method==1) { // NZ
		cat.value = '0';
		cat.disabled = true;
	} else {
		cat.disabled = false;
	}

}

function chooseNZ(i,j)
{if (i==0)
		return j;
	else
		return i;
}

function convertUTCDateToLocalDate(date)
{
    var newDate = new Date(date);
    newDate.setMinutes(date.getMinutes() - date.getTimezoneOffset());
    return newDate;
}

function countNZ(cnts)
{
	var res = 0;
	for (var i = 0; i < cnts.length; i++)
		if (cnts[i] > 0)
			res++;
	return res;
}

function digitonly() {

	let x = event.keyCode 
	let y = String.fromCharCode(x);          // Convert the value into a character
	console.log("y="+y);
	if ( isNaN(y)) {
		event.preventDefault();
	}
}
	

function getScoreLock(btn)
{

	return;	// Don't want to actually implement locking

	let entrantid = document.getElementById('EntrantID').value;
	let scorer = document.getElementById('ScorerName').value;

	let xhttp = new XMLHttpRequest();
	xhttp.onreadystatechange = function() {
		let ok = new RegExp("\W*ok\W*");
		if (this.readyState == 4 && this.status == 200) {
			console.log('{'+this.responseText+'}');
			if (!ok.test(this.responseText)) {
				console.log('Could not get lock');
				btn.disabled = true;
				alert(CANT_LOCK);
			}
		}
	};
	xhttp.open("GET", encodeURI("score.php?c=setlock&e="+entrantid+"&s="+scorer), true);
	xhttp.send();

}

function enableSaveButton()
{
	var cmd;
	console.log('enabling save');
	cmd = document.getElementById('savescorebutton');
	if (cmd == null)
		cmd = document.getElementById('savedata'); /* Forms other than scoresheet */
	if (cmd == null) {
		console.log('No save button found!');
		return;
	}

	//let x = document.getElementById('ScoreSheet');
	//if (x != null && cmd.disabled)
	//	getScoreLock(cmd);

	cmd.disabled = false;

	try {
		var aval = cmd.getAttribute('data-altvalue');
		if (aval != '' && aval != null)
			cmd.value = aval;
	} catch(err) {
	}
	
}

function explainOrdinaryBonuses(totalSoFar)
{
	function showB(B)
	{
		sxappend(B.getAttribute('id'),B.parentNode.getAttribute("data-title").replace(/\[.+\]/,""),B.getAttribute('data-points'),0,totalSoFar += parseInt(B.getAttribute('data-points')));
	}
	var bv = document.getElementById(ORDINARY_BONUSES_VISITED);
	if (!bv)
	{
		var bp = document.getElementsByName("BonusID[]");
		for (var i = 0; i < bp.length; i++ )
			if (bp[i].checked)
				if (!bp.hasAttribute('data-rejected') || bp[i].getAttribute('data-rejected') == '0')
					showB(bp[i]);
				else
					reportRejectedClaim(bp[i].id,bp[i].getAttribute('data-rejected'));
	}
	else if (bv.value.length > 0)
	{
		var bva = bv.value.split(',');
		for (var i = 0; i < bva.length; i++ )
		{
			var bp = document.getElementById(bva[i].replace(CONFIRMED_BONUS_MARKER,''));
			if (bp && bp.checked)
				if (!bp.hasAttribute('data-rejected') || bp.getAttribute('data-rejected') == '0')
					showB(bp);
				else
					reportRejectedClaim(bp.id,bp.getAttribute('data-rejected'));
			else if (!bp)
				console.log("Can't find "+bva[i]);
		}
	}
}

function findEntrant()
{
	var x;
	
	x = window.prompt(LOOKUP_ENTRANT,'');
	if (x == null)
		return true;
	window.location='entrants.php?c=entrants&mode=find&x='+x;
	return false;
}

function formatMinutes(mins)
{
	let hh = Math.floor(mins/60);
	let mm = mins % 60;
	if (hh>0)
		return hh+'h'+(mm>0?' '+mm+'m':'');
	else
		return mm+'m';
}

function formatNumberScore(n,prettyPrint)
/*
 * If n is a number and not equal zero, return its value
 * formatted as the machine's locale
 * otherwise return n, which may be blank or not a number
 *
 */
{
	if (prettyPrint===undefined) {
		return n;
	}
	let loc = document.getElementById('DefaultLocale');
	console.log('loc is '+loc);
	if (loc)
		console.log('loc.value is '+loc.value);
	else
		console.log('Locale is '+MY_LOCALE);
	var NF = new Intl.NumberFormat(loc ? loc.value : MY_LOCALE);
	
	if (parseInt(n) > 0)
		return NF.format(n);
	else
		return n;

}
	
function getFirstChildWithTagName( element, tagName ) {  // Tabbing
     for ( var i = 0; i < element.childNodes.length; i++ ) {
        if ( element.childNodes[i].nodeName == tagName ) return element.childNodes[i];
     }
}

function hidePopup()
{
	var menu = document.getElementById('rcmenu');
	menu.style.display='none';
}

/* ODO readings form filler */
function odoAdjust(useTrip)
{
	var odox = document.getElementById('tab_odo');
	if (!odox) return;
	
	// Any non-zero value here means that we're handling kilometres rather than miles
	var basickms = parseInt(document.getElementById('BasicDistanceUnit').value) != 0;
	
	var odokms = document.getElementById('OdoKms').value=='1';
	var odocheckmiles = parseFloat(document.getElementById('OdoCheckMiles').value);
	var correctionfactor = parseFloat(document.getElementById('OdoScaleFactor').value);
	if (correctionfactor < 0.5)	//SanityCheck
		correctionfactor = 1.0;
	if (odocheckmiles > 0.1)
	{
		var odocheckstart = parseFloat(document.getElementById('OdoCheckStart').value);
		var odocheckfinish = parseFloat(document.getElementById('OdoCheckFinish').value);
		var odochecktrip = parseFloat(document.getElementById('OdoCheckTrip').value);
		if (!useTrip)
			odochecktrip = odocheckfinish - odocheckstart;
		
		if (document.getElementById('OdoCheckStart').value != '' || odochecktrip > 0)
		{
			var checkdistance = odochecktrip;
			if (odokms && !basickms) // Want miles, have kms
				checkdistance = checkdistance / KmsPerMile;
			else if (!odokms && basickms) // Want kms, have miles
				checkdistance = checkdistance * KmsPerMile;
			correctionfactor = odocheckmiles / checkdistance ;
			if (correctionfactor < 0.5)	//SanityCheck
				correctionfactor = 1.0;
			document.getElementById('OdoScaleFactor').value = correctionfactor.toString();
		}
	}		
	if (useTrip)
		document.getElementById('OdoCheckFinish').value = odocheckstart + odochecktrip;
	else if (document.getElementById('OdoCheckTrip').value == '')
		document.getElementById('OdoCheckTrip').value = odocheckfinish - odocheckstart;
	
	var odorallystart = parseFloat(document.getElementById('OdoRallyStart').value);
	var odorallyfinish = parseFloat(document.getElementById('OdoRallyFinish').value);
	if (document.getElementById('OdoRallyStart').value != '' && odorallyfinish > odorallystart)
	{
		var rallydistance = (odorallyfinish - odorallystart) * correctionfactor;
		if (odokms && !basickms)
			rallydistance = rallydistance / KmsPerMile;
		else if (!odokms && basickms)
			rallydistance = rallydistance * KmsPerMile;
		
		document.getElementById('CorrectedMiles').value = rallydistance.toFixed(0);
	}
}

function calcMiles()
{
	var basickms = parseInt(document.getElementById('BasicDistanceUnit').value) != 0;
	var odokms = document.getElementById('OdoKms').value=='1';
	console.log('cm: bk='+basickms+' ok='+odokms);
	var correctionfactor = parseFloat(document.getElementById('OdoScaleFactor').value);
	if (correctionfactor < 0.5)	//SanityCheck
		correctionfactor = 1.0;
	
	var odorallystart = parseFloat(document.getElementById('OdoRallyStart').value);
	var odorallyfinish = parseFloat(document.getElementById('OdoRallyFinish').value);
	if (document.getElementById('OdoRallyStart').value != '' && odorallyfinish > odorallystart)
	{
		var rallydistance = (odorallyfinish - odorallystart) * correctionfactor;
		if (odokms && !basickms)
			rallydistance = rallydistance / KmsPerMile;
		else if (!odokms && basickms)
			rallydistance = rallydistance * KmsPerMile;
		
		document.getElementById('CorrectedMiles').value = rallydistance.toFixed(0);
	}
	
}

function clearUnrejectedClaims()
/*
 * I clear the rejected status on bonuses that are
 * now checked as ok.
 *
 */
 {
	var RC = document.getElementById('RejectedClaims');
	var rca = RC.value.split(',');
	for (var i = 0; i < rca.length; i++ )
	{
		var cr = rca[i].split('=');
		var B = document.getElementById(cr[0]);
		if (B == null)
			continue;
		if (B.checked)
			setRejectedClaim(cr[0],0);
	}
 }
 

function markAsConfirmed()
/*
 * I mark the scorecard as having been confirmed then save
 * it back to the database
 *
 */
{
	console.log('mac called');
	let bv = document.getElementById(ORDINARY_BONUSES_VISITED);
	if (!bv)
		return;
	let bva = bv.value.split(',');
	
	for (let i = 0; i < bva.length; i++ )
		bva[i] = CONFIRMED_BONUS_MARKER+bva[i].replace(CONFIRMED_BONUS_MARKER,'');
	bv.value = bva.join(',');
	
	//document.getElementById('Confirmed').value = '1';
	
	enableSaveButton();
	document.getElementById('savescorebutton').click();
}


function markRejectedClaims()
/*
 * I'm called on page load to mark individual bonuses as being rejected
 *
 */
{
	let RC = document.getElementById('RejectedClaims');
	console.log('rca ['+RC.value+']');
	let rca = RC.value.split(',');
	console.log(rca.length);
	for (let i = 0; i < rca.length; i++ )
	{
		let cr = rca[i].split('=');
		let bonusid = cr[0];
		let reason = cr[1];
		console.log('mRC: '+bonusid+','+reason);
		if (bonusid=='')
			continue;
		let B = document.getElementById(bonusid);
		if (B == null)
			continue;
		B.setAttribute('data-rejected',reason);
		setRejectedTooltip(B.parentNode,reason);

	}
}

function reflectBonusCheckedState(B)
{
	if (B.id == 'B03')
		console.log('Bonus ' + B.id + ' has checked = ' + B.checked + ' and Reject = ' + B.getAttribute('data-rejected'));
	var S = B.parentElement;
	if (bonusScoredOk(B))
		S.className = class_showbonus + class_checked;
	else if (B.hasAttribute('data-rejected') && B.getAttribute('data-rejected') > '0')
		S.className = class_showbonus + class_rejected;
	else
		S.className = class_showbonus + class_unchecked;
}


function repaintBonuses()
{
	walkBonusArrays(function(id) {
		var B = document.getElementById(id);
		reflectBonusCheckedState(B);
		B.parentElement.addEventListener('contextmenu',function(e){e.preventDefault()});
		for (var j = 0; j < B.parentElement.childNodes.length; j++)
			B.parentElement.childNodes[j].addEventListener('contextmenu',function(e){e.preventDefault()});
	});
		
	
}

function reportRejectedClaim(bonusid,reason)
{
	console.log('rRC: '+bonusid+','+reason);
	if (bonusid=='')
		return;
	var B = document.getElementById(bonusid);
	if (B == null)
		return;
	B.setAttribute('data-rejected',reason);
	setRejectedTooltip(B.parentNode,reason);
	var BP = B.parentNode;
	var R = document.getElementsByName('RejectReason');
	for (var i = 0; i < R.length; i++)
		if (R[i].getAttribute('data-code') == reason)
		{
			//console.log("Reporting " + bonusid + " for " + R[i].value);
			if (B.name != 'BonusID[]')
				sxappend(B.getAttribute('id'),B.parentNode.firstChild.innerHTML.replace(/\[.+\]/,""),'X','','');
			else
			{
				var xtit = B.parentNode.getAttribute("title").replace(/\[.+\]/,"");
				var p = xtit.indexOf('\r');
				if (p >= 0)
					xtit = xtit.substr(0,p);
				// Only append the first line of the tooltip, not the whole thing
				sxappend(B.getAttribute('id'),xtit,'X','','');
			}
			sxappend('',CLAIM_REJECTED + ' - ' + R[i].value,'','','');
		}
	if (reason == 0)
		B.parentElement.className = class_showbonus + class_unchecked;
	else
		B.parentElement.className = class_showbonus + class_rejected;
	//console.log("Reporting reason " + reason + " for bonus " + bonusid);
}

function SFS(status,x)
{
	var es = document.getElementById('EntrantStatus');
	es.value = status;
	es.setAttribute('title',x);
	if (x != '')
		sxappend(' '+es.options[status].text,x,'','',0);
	var sxsfs = document.getElementById('sxsfs');
	if (sxsfs)
		sxsfs.innerHTML = es.options[es.selectedIndex].text;
}


function setFinishTimeDNF()
{
	var CH = parseInt(document.getElementById('MaxHours').value);
	var ST = document.getElementById('RallyTimeStart').value;
	var st = document.getElementById('StartDate').value + 'T' + document.getElementById('StartTime').value;
	var mst = st < ST ? ST : st;
	var dt = new Date(mst+'Z');
	dt.setHours(dt.getHours()+CH);
	//console.log('ST='+st);
	//console.log('DNF='+dt.toISOString());
	var FT = document.getElementById('RallyTimeDNF').value;
	var xt = dt.toISOString();
	xt = xt.substring(0,16);
	if (FT < xt)
		xt = FT;
	document.getElementById('FinishTimeDNF').value = xt;
	//console.log("set="+xt);

}

function setRejectedClaim(bonusid,reason)
{
	// reason == 0 - unset, claim not rejected
	console.log(' Flagging ' + bonusid + ' as ' + reason);
	var B = document.getElementById(bonusid);
	if (B == null)
		return;
	B.setAttribute('data-rejected',reason);
	var RC = document.getElementById('RejectedClaims');
//	console.log('src:' + bonusid + '=' + reason + '; rc=' + RC.value);
	var rca = [];
	if (RC.value.length > 0)
		rca = RC.value.split(',');
	var done = false;
	for (var i = 0; i < rca.length; i++ )
	{
		var cr = rca[i].split('=');
		if (cr[0] == bonusid || cr[0] == ORDINARY_BONUS_PREFIX+bonusid || cr[0] == COMBO_BONUS_PREFIX+bonusid)
		{
			cr[1] = reason;
			if (reason == 0) {
				console.log('Unrejecting '+rca[i]+' @ '+i);
				rca.splice(i);
				//cr[0] = NON_EXISTENT_BONUSID;
			} else
				rca[i] = cr.join('=');	
			done = true;
			break;
		}
	}
	if (!done && reason > 0)
		rca.push(bonusid+'='+reason);
	RC.value = rca.toString();
	console.log('Rejected claims include {'+RC.value+'} {{'+document.getElementById('RejectedClaims').value+'}}');
	if (reason == 0) {
		B.parentElement.className = class_showbonus + class_unchecked;
		if (true) { //(bonusid.substr(0,1) == ORDINARY_BONUS_PREFIX) {
			B.checked = false;
			console.log('Unchecking '+bonusid);
		}
	} else {
		B.parentElement.className = class_showbonus + class_rejected;
		console.log('Marking as rejected');
	}
	
	setRejectedTooltip(B.parentNode,reason);
	//console.log('Setting RC value == ' + RC.value);
}

function setRejectedTooltip(BP,reason)
{
	console.log('setRejectedTooltip '+BP.value+'='+reason);
	var tit = BP.getAttribute('title').split('\r');
	if (reason == 0)
		BP.setAttribute('title',tit[0]);
	else {
		var rcm = document.getElementById('rcmenu');
		BP.setAttribute('title',tit[0]+'\r'+rcm.firstChild.childNodes[reason].innerText);
	}

}

function setSplitNow(id_prefix)
{
	var dt = convertUTCDateToLocalDate(new Date(Date.now()));
	var dtDate = document.getElementById(id_prefix+'Date');
	if (!dtDate) return;
	var dtTime = document.getElementById(id_prefix+'Time');
	if (!dtTime) return;
	var x = dt.toJSON();
	var xd = x.slice(0,10);
	var xt = x.slice(11,16);
	//console.log('ssn:'+id_prefix+' x='+x+' xd='+xd+' xt='+xt);
	dtDate.value = xd;
	dtTime.value = xt;
	enableSaveButton();
}

function synchronizeCssStyles(src, destination, recursively) {

    // if recursively = true, then we assume the src dom structure and destination dom structure are identical (ie: cloneNode was used)

    // window.getComputedStyle vs document.defaultView.getComputedStyle 
    // @TBD: also check for compatibility on IE/Edge 
    destination.style.cssText = document.defaultView.getComputedStyle(src, "").cssText;

    if (recursively) {
        var vSrcElements = src.getElementsByTagName("*");
        var vDstElements = destination.getElementsByTagName("*");

        for (var i = vSrcElements.length; i--;) {
            var vSrcElement = vSrcElements[i];
            var vDstElement = vDstElements[i];
//          console.log(i + " >> " + vSrcElement + " :: " + vDstElement);
            vDstElement.style.cssText = document.defaultView.getComputedStyle(vSrcElement, "").cssText;
        }
    }
}
function fetchBonusOrder()
{
	var obs = document.getElementById(ORDINARY_BONUSES_VISITED);
	var res = [];
	if (obs.value.length > 0)
		res = obs.value.split(',');
	return res;
}

function finishBonusOrder()
{
	var dda = document.getElementById(DDAREA_id);
	var lis = dda.getElementsByClassName('ddlist')[0].getElementsByTagName('li');
	var obs = [];
	for (var i = 0; i < lis.length; i++)
	{
		var txt = lis[i].innerText;
		obs.push(txt.substr(0,txt.indexOf(' ')));
	}
	var bv = document.getElementById(ORDINARY_BONUSES_VISITED);
	bv.value = obs.join(',');
	dda.className = 'hide';
	if (dda.getAttribute('dropped')==1)
		recalcScorecard(); 
	sxshow();
}

function showBonusOrder()
{
	event.preventDefault();
	var dda = document.getElementById(DDAREA_id);
	var obs = fetchBonusOrder();
	console.log("obs: "+JSON.stringify(obs));
	var html = '<input type="button" title="' + OBSORTAZ + '" style="font-size: 1.1em;" value="&duarr;" onclick="sortBonusOrder()"/> ';
	html += '<input type="button" title="' + APPLYCLOSE + '" style="float: right; font-size: 1.1em;" value="&cross;" onclick="finishBonusOrder()"/>';
	html += '<ol class="ddlist">';
	for (var i = 0; i < obs.length; i++)
	{
		if (obs[i]=='')
			continue;
		let e = obs[i].indexOf('=');
		let bone = obs[i];
		if (e >= 0)
			bone = bone.substr(0,e);
		console.log('bone=='+bone);
		var bon = document.getElementById(bone.replace(CONFIRMED_BONUS_MARKER,''));
		console.log("sbo: "+JSON.stringify(bon));
		html += '<li draggable="true" ondragstart="dragStart(event)" ondragover="dragOver(event)" >';
		var tit = bon.parentNode.getAttribute('title');
		var p = tit.indexOf('[');
		if (p >= 0)
			tit = tit.substr(0,p);
		html += obs[i].replace(CONFIRMED_BONUS_MARKER,'') + ' ' + tit;
		html += '</li>';
	}
	html += '</ol>';
	dda.innerHTML = html;
	dda.setAttribute('dropped',0);
	sxhide();
	dda.className = 'show';
	return false;
}

function sortBonusOrder()
{
	var ddl = document.getElementById(DDAREA_id).getElementsByClassName('ddlist')[0];
	sortList(ddl);
}

function sortList(ul){
    var new_ul = ul.cloneNode(false);

    // Add all lis to an array
    var lis = [];
    for(var i = ul.childNodes.length; i--;){
        if(ul.childNodes[i].nodeName === 'LI')
            lis.push(ul.childNodes[i]);
    }

//    lis.sort(function(a, b){
//      return parseInt(a.childNodes[0].data , 10) - 
//              parseInt(b.childNodes[0].data , 10);
//    });

    lis.sort(function(a, b){
       return a.childNodes[0].data > 
              b.childNodes[0].data;
    });

    // Add them into the ul in order
    for(var i = 0; i < lis.length; i++)
        new_ul.appendChild(lis[i]);
    ul.parentNode.replaceChild(new_ul, ul);
}



function showCat(cat,N,ent)
{
	if (typeof(N) == 'undefined')
		var X = '';
	else
		var X = N;
	try	{ document.getElementById('cat'+cat+'_'+ent).innerText = X; } catch(err) { }		
}

function showHelp(topic)
{
	window.open('showhelp.php?topic='+topic, 'smhelp', 'location=no,height=800,width=800,scrollbars=yes,status=no');
}

/* Called from Entrant picklist when an Entrant number is entered */
function showPickedName()
{
	var eid = parseInt(document.getElementById('EntrantID').value);
	var eids = document.getElementsByClassName("EntrantID");
	var enames = document.getElementsByClassName("RiderName");
	document.getElementById("NameFilter").value = '';
	for (var i = 0 ; i < eids.length; i++)
		if (eid == eids[i].innerHTML)
		{
			document.getElementById("NameFilter").value = enames[i].innerHTML;
			break;
		}
	enableSaveButton();
}



/*
 * This is called in response to a contextmenu event, right-click or long press
 * it identifies the clicked bonus then shows a popup menu containing claim
 * reject reasons or 0 to clear the rejection.
 *
 */
function showPopup(obj)
{
	var menu = document.getElementById('rcmenu');
	if (menu == null)
		return true;
	var el = obj;
	//console.log(el.tagName + ' == ' + (el.tagName != 'SPAN') + ' id=' + el.id);
	if (el.tagName != 'SPAN')
		el = el.parentElement;
	var ee = el.getBoundingClientRect();
	var B = el.getElementsByTagName('input')[0];
	menu.setAttribute('data-bonusid',B.id);
	menu.onclick = function(e) { 
		menu.style.display='none'; // hide the menu
		var reason = e.target; 
		var code = reason.innerText.split('=')[0];
		var bid = menu.getAttribute('data-bonusid');
		
		document.getElementById(bid).checked = code > 0; 

		setRejectedClaim(bid,code);
		//if (bid.substr(0,1) == ORDINARY_BONUS_PREFIX)
		tickBonus(document.getElementById(bid));
	}
    menu.style.left = ee.left + window.scrollX + 'px';
    menu.style.top = ee.top + + window.scrollY + 'px';
    menu.style.display = 'block';

	return false;
}



/* Call when score submit button is clicked */
function submitScore()
{
	//alert('submitting score');
	/* Enable any combos so they'll be saved */
	var cmbs = document.getElementsByName('ComboID[]');
	for (var i = 0; i < cmbs.length; i++ )
	{
		cmbs[i].disabled = false;
	}
	
	//alert('Combos enabled for saving');
	
	/* Save the score explanation as part of the form
	 * so that it can be saved to the entrant record
	 * for later [bulk] printing.
	 */
	var sx = document.getElementById(SX_id);
	var sxs = document.getElementById(SX_StoreID);
	if (sx && sxs)
		sxs.value = sx.innerHTML;
	
	return true;
}


/* Score eXplanations */
function sxappend(id,desc,bp,bm,tp)
{
	var showMults = document.getElementById("ShowMults").value == SM_ShowMults;

	var sx = document.getElementById(SX_id);
	if (!sx) return;
	
	var sxb = getFirstChildWithTagName(sx,'TABLE');
	if (!sxb) return;
	sxb = getFirstChildWithTagName(sxb,'TBODY');
	//return;
	
	var estat = document.getElementById('EntrantStatus');
	
	//document.getElementById('sxsfs').innerHTML = estat.options[estat.selectedIndex].text;
	
	
	var row = sxb.insertRow(-1);
	var td_id = row.insertCell(-1);
	var id1 = id.substr(0,1);
	if (id1 != ' ' && id1 != '') {
		td_id.innerHTML = id1 + '-' + id.substr(1);
	} else {
		td_id.innerHTML = id;
	}
	td_id.className = 'id';
	var td_desc = row.insertCell(-1);
	td_desc.innerHTML = desc;
	td_desc.className = 'desc';
	var td_bp = row.insertCell(-1);
	td_bp.innerHTML = formatNumberScore(bp,true);
	td_bp.className = 'bp';
	if (showMults)
	{
		var td_bm = row.insertCell(-1);
		td_bm.innerHTML = bm;
		td_bm.className = 'bm';
	}
	var td_tp = row.insertCell(-1);
	td_tp.innerHTML = formatNumberScore(tp,true);
	td_tp.className = 'tp';
}
function sxhide()
{
	var sx = document.getElementById(SX_id);	
	sx.className = 'hidescorex scorex';
	sx.setAttribute('data-show','0');
}
function sxprint()
{
	var ent = document.getElementById('EntrantID').value;
    var mywindow = window.open('entrants.php?c=scorex&entrant='+ent, 'PRINT', 'height=400,width=600');
	
    return true;	
}
function sxshow()
{
	var sx = document.getElementById(SX_id);
	sx.className = 'showscorex scorex';
	sx.setAttribute('data-show','1');
}	
function sxstart()
{
//	console.log('start');
	let showMults = document.getElementById("ShowMults").value == SM_ShowMults;
	
	let sx = document.getElementById(SX_id);
	if (!sx) return;
	
	let html = '<table><caption>'+document.getElementById("RiderID").innerHTML+' [&nbsp;<span id="sxsfs"></span>&nbsp;]';
	let distance = '';
	let cm = parseInt(document.getElementById('CorrectedMiles').value);
	if (cm > 0)
		distance = distance + cm + ' ' + document.getElementById('bduText').value;
	let avg = document.getElementById('CalculatedAvgSpeed').value;

	// Suppress average speed result on score explanation
	//if (avg != '')
	//	distance = distance + ' @ ' + avg;
	
	if (distance != '')
		html += '<br><span class="explain">'+distance+'</span>';
	html += '</caption><thead><tr><th class="id">id</th><th class="desc"></th><th class="bp">BP</th>';
	if (showMults) html += '<th class="bm">BM</th>';
	html += '<th class="tp">TP</th></tr></thead><tbody></tbody></table>';
	sx.innerHTML = html;
	
}
function sxtoggle()
{
	hidePopup();
	var sx = document.getElementById(SX_id);	
	if (sx.getAttribute('data-show') != '1')
		sxshow();
	else
		sxhide();
}


function tabsGetHash( url ) {	// Tabbing
     var hashPos = url.lastIndexOf ( '#' );
     return url.substring( hashPos + 1 );
}



function tabsSetupTabs()
{
	// Grab the tab links and content divs from the page
   var tabListItems = document.getElementById('tabs').childNodes;
	
   for ( var i = 0; i < tabListItems.length; i++ ) {
     if ( tabListItems[i].nodeName == "LI" ) {
       var tabLink = getFirstChildWithTagName( tabListItems[i], 'A' );
       var id = tabsGetHash( tabLink.getAttribute('href') );
       tabLinks[id] = tabLink;
       contentDivs[id] = document.getElementById( id );
     }
   }

      // Assign onclick events to the tab links, and
      // highlight the first tab
      var i = 0;

      for ( var id in tabLinks ) {
        tabLinks[id].onclick = tabsShowTab;
        tabLinks[id].onfocus = function() { this.blur() };
        if ( i == 0 ) tabLinks[id].className = 'selected';
        i++;
      }

      // Hide all content divs except the first
      var i = 0;

      for ( var id in contentDivs ) {
        if ( i != 0 ) {
			contentDivs[id].classList.remove('tabContent');
			contentDivs[id].classList.add('tabContenthide');
		}
		var legend = getFirstChildWithTagName( contentDivs[id], 'LEGEND' );
		
		if ( legend ) legend.innerText = '';
        i++;
      }

}

function tabsShowTab() 
{
      var selectedId = tabsGetHash( this.getAttribute('href') );

      // Highlight the selected tab, and dim all others.
      // Also show the selected content div, and hide all others.
      for ( var id in contentDivs ) {
        if ( id == selectedId ) {
          tabLinks[id].className = 'selected';
		  contentDivs[id].classList.remove('tabContenthide');
          contentDivs[id].classList.add('tabContent');
        } else {
          tabLinks[id].className = '';
		  contentDivs[id].classList.remove('tabContent');
          contentDivs[id].classList.add('tabContenthide');
        }
      }

      // Stop the browser following the link
      return false;
}


function tickBonus1(B)
/*
 * This handles individual ordinary bonus tick/unticks
 * B is the checkbox obect
 */
{
	var bv = document.getElementById(ORDINARY_BONUSES_VISITED);
	if (bv)
	{
		console.log('tickBonus was '+bv.value);
		var bva = [];
		if (bv.value.length > 0)
			bva = bv.value.split(',');
		if (B.checked)
			if (bva.indexOf(B.value) < 0 && bva.indexOf(CONFIRMED_BONUS_MARKER+B.value) < 0)
				bva.push(B.value);
			else
				;
		else {
			let ix = bva.indexOf(B.value);
			if (ix < 0)
				ix = bva.indexOf(CONFIRMED_BONUS_MARKER+B.value);
			if (ix >= 0)
				bva.splice(ix,1);
		}
		bv.value = bva.join(',');			
		console.log('tickBonus is '+bv.value);
	}
	recalcScorecard();	
}

function tickCombos()
/*
 * This ticks or unticks combinations depending on the ticked status of their underlying
 * bonuses. A combo is ticked if some or all of its bonuses are ticked, controlled by the
 * value of MinimumTicks in the combo record.
 *
 */
{
	var cmbs = document.getElementsByName('ComboID[]');
	for (var i = 0; i < cmbs.length; i++ )
	{
		var tick = true;
		if (false && cmbs[i].hasAttribute('data-rejected') && cmbs[i].getAttribute('data-rejected') > '0')
			tick = false;
		else
		{
			var bps = cmbs[i].getAttribute('data-bonuses').split(',');
			var ticks = 0;
			var nticks = 0;
			for (var j = 0; j < bps.length; j++ )
				if (bps[j] != '') 
				{
					var bp = document.getElementById(bps[j]);
					//var sp = document.getElementById(SPECIAL_BONUS_PREFIX+bps[j]);
					//var cp = document.getElementById(COMBO_BONUS_PREFIX+bps[j]);
					//if ( (bp != null && bonusScoredOk(bp)) || (sp != null && bonusScoredOk(sp)) || (cp != null && bonusScoredOk(cp)) )
					if (bp != null && bonusScoredOk(bp))
						ticks++;
					else
						nticks++;
				}
				var minticks = cmbs[i].getAttribute('data-minticks');
				if (minticks == 0)
					tick = ticks > 0 && nticks == 0;
				else
					tick = ticks >= minticks;
				var ptsa = cmbs[i].getAttribute('data-pointsarray').split(',');
				var pts = 0;
				if (ptsa.length < 2)
					pts = ptsa[0];
				else
					if (ticks > 0)
					{
						ticks = ticks - minticks;
						if (minticks == 0 || ticks >= ptsa.length)
							pts = ptsa[ptsa.length - 1];
						else
							pts = ptsa[ticks];
					}
				document.getElementById(cmbs[i].getAttribute('id')).setAttribute('data-points',pts);
		}
		let bonus = document.getElementById(cmbs[i].getAttribute('id'));
		bonus.checked = tick; // && (!bonus.hasAttribute('data-rejected') || bonus.getAttribute('data-rejected')=='0');
	}
}


function trapDirtyPage()
{

	// This method does not allow for clearing lock flags when definitely leaving a dirty page
	
	window.addEventListener('beforeunload', function(e) {
	
	var cmd = document.getElementById('savescorebutton');
	if (cmd == null)
		cmd = document.getElementById('savedata'); /* Forms other than scoresheet */
	if (cmd == null)
		return;
	var myPageIsDirty = !cmd.disabled && cmd.getAttribute('data-triggered')=='0';  //you implement this logic...
	if (myPageIsDirty) {
		//following two lines will cause the browser to ask the user if they
		//want to leave. The text of this dialog is controlled by the browser.
		e.preventDefault(); //per the standard
		e.returnValue = ''; //required for Chrome
	}
		//else: user is allowed to leave without a warning dialog
	});
}

function walkBonusArrays(f)
{
	var bt = "BonusID[],SpecialID[]";
	var sgObj = document.getElementById('SGroupsUsed');
	if (sgObj != null)
	{
		var sg = sgObj.value.split(',');
		for (var i = 0; i < sg.length; i++)
			bt += ',SpecialID_' + sg[i] + '[]';
	}
	bt += ',ComboID[]';
	var bta = bt.split(',');
	for (var i = 0; i < bta.length; i++)
	{
		var ba = document.getElementsByName(bta[i]);
		for (var j = 0; j < ba.length; j++)
			f(ba[j].id);
	}
}


function zapScoreDetails()
{
	var sd = document.getElementsByClassName('scoredetail');
	for (var i = 0 ; i < sd.length; i++)
		sd[i].innerText='';
}




