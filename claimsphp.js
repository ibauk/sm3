
"use strict";

function checkMagicWord() {
	let lmw = '';
	let lmwtime = '';
	let mwok = document.getElementById('mwok');
	mwok.innerHTML = '';
	let mw = document.getElementById('magicword').value;
	console.log('cmw={'+mw+'}');
	let ct = document.getElementById('ClaimDate').value+'T'+document.getElementById('ClaimTime').value;
	let wms = document.getElementsByName('mw');
	let n = 0;
	
	for (let i = wms.length - 1; i >= 0; i--) {
		console.log(ct+' '+wms[i].getAttribute('data-asfrom')+' '+wms[i].value);
		if (ct >= wms[i].getAttribute('data-asfrom')) { // in force at the time of this claim
			n++; // count the number of records in time
			if (mw.toLowerCase() == wms[i].value.toLowerCase() ) {
				console.log('Match found at '+i);
				if (n == 1)	{ // Most recent
					mwok.innerHTML = ' &checkmark; ';
					mwok.className = 'green';
					return true;		// all good
				}
				if (n == 2) {
					mwok.innerHTML = ' &checkmark; &nbsp;&nbsp;\''+lmw+ '\' >= '+lmwtime+' '+'<input type="checkbox" value="1" name="MagicPenalty">';
					mwok.className = 'yellow';
					return false;
				}
			}
		}
		
		lmw = wms[i].value;
		lmwtime = wms[i].getAttribute('data-asfrom');
	}
	mwok.innerHTML = ' &cross;  <input type="checkbox" value="1" name="MagicPenalty" checked>';
	mwok.className = 'red';
	return false;
}
function checkBonusFuel(str,fuelAnyway) {
	if (!fuelAnyway) {
		console.log('Checking bonus fuel ... '+str);
		let rs = document.getElementById('refuelstops');
		if (!rs)
			return false;
		let rx = new RegExp(rs.value);
		rx.ignoreCase = true;
		console.log("Testing "+rx.source);
		if (!rx.test(str)) 
			return false;
		console.log(rs.value+' true');
	}
	// This bonus is a refuel stop!
	
	let fb = document.getElementById('FuelBalance');
	fb.value = document.getElementById('tankrange').value;
	fb.setAttribute('title',fb.value);
	return true;

}

function checkDateTime() {

	let cdt = document.getElementById('ClaimDate');
	let ctm = document.getElementById('ClaimTime');
	console.log('cdt testing {'+ctm.value+'}');
	if (ctm.value == '')
		return;
	console.log('Checking claim date ... ');
	let rs = document.getElementById('rallyStart');
	let rf = document.getElementById('rallyFinish');

	/** lastClaimTime may/may not be set yet. It's a race to see which thread completed first so don't rely on it */
	let lc = document.getElementById('lastClaimTime');
	let ok = false
	
	let ct = cdt.value+'T'+ctm.value;
	let x = (lc ? lc.value : '<null>');
	console.log(rs.value+'  '+rf.value+' '+x+' '+ct);
	ok = (!lc || ct > lc.value) && (!rs || ct >= rs.value) && (!rf || ct <= rf.value);


	console.log('Decided it was ok = '+ok);
	if (ok) {
		cdt.classList.remove('yellow');
		ctm.classList.remove('yellow');
	} else {
		cdt.classList.add('yellow');
		ctm.classList.add('yellow');
	}
}

function addMins(dt,m) {
	let tm = Date.parse(dt);
	tm = tm + m * 60 * 1000;
	return new Date(tm);
}
function formatDatetime(dt) {

	let yy = dt.getFullYear();
	let mm = dt.getMonth() + 1;
	let dd = dt.getDate();
	let hh = dt.getHours();
	let nn = dt.getMinutes();
	
	let edate = '' + yy + "-";
	edate = edate + (mm < 10 ? '0' : '') + mm + '-';
	edate = edate + (dd < 10 ? '0' : '') + dd + 'T';
	edate = edate + (hh < 10 ? '0' : '') + hh + ':';
	edate = edate + (nn < 10 ? '0' : '') + nn;
	return edate;
	
}

function checkEnableSave()
{
	if (validateClaim(false))
		enableSaveButton();
}

function checkSpeeding($penalise) {

	checkDateTime();  // Is the current claim time reasonable ?
	
	let claimid = parseInt(document.getElementById('claimid').value);
	console.log('Claimid='+claimid);
	if (claimid > 0)
		return;
	
	console.log('Checking speed');
	let speedok = document.getElementById('SpeedWarning');
	if (speedok) {
		speedok.style.display = 'none';
		speedok.innerHTML = '';
	}
	console.log('Warning cleared');
	let lc = document.getElementById('lastClaimTime');
	if (!lc)
		return;
	let lct = lc.value;
	if (lct=='')
		return;
	console.log('Fetched last claim: t='+lct);
	let ct = document.getElementById('ClaimDate').value+'T'+document.getElementById('ClaimTime').value;
	let nm = document.getElementById('lastNextMins').value;
	let ed = addMins(lct,nm);
	let edate = formatDatetime(ed);
	console.log('ct='+ct);
	console.log('earliest+'+nm+'='+ed+' == '+edate+' ??? '+(ct < edate));
	if (speedok && ct < edate) {
		console.log('OMG!');
		let tickspeed = $penalise ? ' <input type="checkbox" value="1" name="SpeedPenalty" checked>' : '';
		speedok.innerHTML = ' < ' + edate.replace('T',' ') + tickspeed;
		speedok.style.display = 'inline';
	}
}

	
function odoChanged(odo,emptyok) {

	console.log('Odo has changed  '+emptyok);
	let lastodo = 0;
	let vr = document.getElementById('virtualrally').value != 0;
	if (!vr)
		return;
	let fb = document.getElementById('FuelBalance');
	if (!fb)
		return;
	else
		fbd = parseInt(fb.getAttribute('data-value'));
	try {
		lastodo = parseInt(document.getElementById('lastOdoReading').value);
	}catch(e){
	}
	let thisleg = (odo > lastodo ? odo - lastodo : 0);
	let odor = document.getElementById('OdoReading');
	if (odo < lastodo)
		odor.classList.add('yellow');
	else
		odor.classList.remove('yellow');
	
	let fw = document.getElementById('FuelWarning');
	let tick = document.getElementById('TickFW');

	console.log('thisleg='+thisleg+' fbd='+fbd);
	if (thisleg <= fbd) {		// enough fuel
		tick.checked = false;
		fw.style.display = "none";
		if (emptyok) {
			fb.value = fbd - thisleg;
			fb.setAttribute('title',fb.value);
			console.log('New fb is '+fb.value);
			checkBonusFuel(document.getElementById('BonusID').value,false);
			console.log('After checking bonus = '+fb.value);
		}
	} else if (emptyok) {		// don't care
		checkBonusFuel(document.getElementById('BonusID').value,true)
		console.log('EmptyOk/cbf='+fb.value+'; fbd='+fbd+' thisleg='+thisleg);
		fb.value = fb.value + (fbd - thisleg);
		console.log('and now '+fb.value);
		fb.setAttribute('title',fb.value);
		//alert('EmptyOk ?? ');
		
	} else {
		fw.style.display = 'inline';
		fw.setAttribute('title',(fbd-thisleg));
		if (tick)
			tick.checked = true;
	}
		
}


function fixTimeFormat(x) {
	// Accepts dd.dd, dd:dd and dddd all with leading zero if ness 
	console.log("Fixing time "+x);
	let y = x.replace('.',':');
	let res = y
	if (y.indexOf(':') < 0) {
		if (y.length < 4) {
			res = '0'+y.substr(0,1)+':'+y.substr(1)
		} else {
			res = y.substr(0,2)+':'+y.substr(2)
		}
	} else if (y.length < 5) {
		res = '0'+y
	}
	return res;
}

function pasteNewClaim()
{
	event.stopPropagation();
	event.preventDefault();
	let clipboardData = event.clipboardData || window.clipboardData;
	let txt = clipboardData.getData('Text');
	let re = new RegExp(EBC_SUBJECT_LINE);
	let matches = re.exec(txt);
	console.log("pnc found "+matches);
	if (!matches)
		return;
	let mlen = matches.length;

	console.log(matches+'--'+mlen);

	let echg = new Event('change');

	let e = document.getElementById('EntrantID');
	let b = document.getElementById('BonusID');
	let o = document.getElementById('OdoReading');
	let t = document.getElementById('ClaimTime');

	
	
	if (mlen > 1) { // Fields were captured
		e.value = matches[1];	
		e.dispatchEvent(echg);

		if (mlen > 2 && typeof(matches[2]) !== 'undefined') {
			b.value = matches[2];
			b.dispatchEvent(echg);
			if (mlen > 3 && typeof(matches[3]) !== 'undefined') {
				o.value = matches[3];
				console.log("odo value is "+matches[3]);
				o.dispatchEvent(echg);
				if (mlen > 4 && typeof(matches[4]) !== 'undefined') {
					matches[4] = fixTimeFormat(matches[4]);
					console.log("setting time to "+matches[4]);
					t.value = timevalue(matches[4]);
					console.log("time value is '"+t.value+"' matches[4] is '"+matches[4]+"'");
					t.dispatchEvent(echg);
				} else
					t.focus();
			} else
				o.focus();
		} else
			b.focus();
	} 
	checkEnableSave();
}

function showBonus(obj) {
	console.log("xxx showBonus");
	let str = obj.value
	let xhttp;
	if (str == "") {
		obj.parentNode.classList.remove('yellow');
		document.getElementById("BonusName").innerHTML = "";
		return;
	}
	xhttp = new XMLHttpRequest();
	xhttp.onreadystatechange = function() {
		if (this.readyState == 4 && this.status == 200) {
			let bname = document.getElementById("BonusName");
			bname.innerHTML = this.responseText;
			if (this.responseText.trim().startsWith('*')) {
				obj.parentNode.classList.add('yellow');
				bname.setAttribute('data-ok','0');
			} else {
				obj.parentNode.classList.remove('yellow');
				bname.setAttribute('data-ok','1');
				console.log('Parsing '+this.responseText);
				let myString = this.responseText;
				let myRegexp = /\[ap=(\d);pv=(\d+);am=(\d);mv=(\d+)\]/;
				let match = myRegexp.exec(myString);
				console.log("pv="+match[2]);
				let pv = document.getElementById('PointsValue');
				pv.value = match[2];
				if (match[1] == 1) 
					pv.parentNode.className = "vlabel";
				else
					pv.parentNode.className = "hide";
				pv = document.getElementById('RestMinutes');
				pv.value = match[4];
				if (match[3] == 1) 
					pv.parentNode.className = "vlabel";
				else
					pv.parentNode.className = "hide";
				document.getElementById('AskPoints').value = match[1];
				document.getElementById('AskMinutes').value = match[3];
				checkEnableSave();  // Triggered by JayBee submitting fine bug report indicating race condition
			}
		}
	};
	xhttp.open("GET", "claims.php?c=bondes&b="+str, true);
	xhttp.send();
}

function showEntrant(obj) {
	let str = obj.value;
	let xhttp;
	if (str == "") {
		obj.parentNode.classList.remove('yellow');
		document.getElementById("EntrantName").innerHTML = "";
		return;
	}
	xhttp = new XMLHttpRequest();
	xhttp.onreadystatechange = function() {
		if (this.readyState == 4 && this.status == 200) {
			let ename = document.getElementById("EntrantName");
			ename.innerHTML = this.responseText;
			console.log('{ '+this.responseText+' }');
			if (this.responseText.trim().startsWith('*')) {
				obj.parentNode.classList.add('yellow');
				ename.setAttribute('data-ok','0');
			} else {
				obj.parentNode.classList.remove('yellow');
				ename.setAttribute('data-ok','1');
			}
			let lastodo = document.getElementById('lastOdoReading');
			let thisodo = document.getElementById('OdoReading');
			if (lastodo && thisodo)
				thisodo.setAttribute('value',lastodo.value);		// Use last reading as default if new value not entered
			let lbc = document.getElementById('LastBonusClaimed');
			let bn = document.getElementById('BonusName');
			if (lbc && bn) {
				bn.innerHTML = lbc.innerHTML;
			}
		}
	};
	xhttp.open("GET", "claims.php?c=entnam&e="+str, true);
	xhttp.send();
}

// timevalue takes whatever was entered and attempts to returns it 
// as a correctly formatted, not necessarily correct, value 
// suitable for a 'time' input control
function timevalue(asentered) {

	console.log("timevalue called with '" + asentered + "'");
	let x = asentered.replace(".",":");
	let n = x.length;
	let p = x.indexOf(':');
	if (p < 0 && n > 2) {		// No ':' but at least three chars
		x = x.substr(0,n-2) + ':' + z.substr(n-2);
		p = n - 3;
	}
	while (p < 2) {
		x = "0" + x;
		p++;
	}
	console.log("timevalue returning '" + x + "'");
	return x;

}

function validateClaim(final) {
	
	console.log('Validating form ... ');
	let eno = document.getElementById('EntrantID');
	console.log('enok ok');
	let ename = document.getElementById('EntrantName');
	console.log('ename ok');
	if (eno.value == '' || !ename.hasAttribute('data-ok') || ename.getAttribute('data-ok')=='0') {
		if (final) eno.focus();
		return false;
	}
	console.log('entrant ok');
	let bid = document.getElementById('BonusID');
	console.log('bid ok');
	let bname = document.getElementById('BonusName');
	console.log('bname ok');
	if (bid.value == '' || !bname.hasAttribute('data-ok') || bname.getAttribute('data-ok')=='0') {
		console.log("bid.value='"+bid.value+"'; bname.value='"+bname.value+"'; bname.data-ok="+bname.getAttribute('data-ok'));
		if (final) document.getElementById('BonusID').focus();
		return false;
	}
	console.log('Bonus ok');
	if (document.getElementById('OdoReading').value == '') {
		if (final) document.getElementById('OdoReading').focus();
		return false;
	}
	if (document.getElementById('ClaimTime').value == '') {
		if (final) document.getElementById('ClaimTime').focus();
		return false;
	}
	let claimid = parseInt(document.getElementById('claimid').value);
	console.log('Claimid=='+claimid);
	//alert('Check1');
	if (claimid > 0)
		return true;
	console.log('Checking ntm');
	let ntm = document.getElementById('NextTimeMins');
	if (ntm && ntm.value == '') {
		console.log('ntm failed');
		if (final) ntm.focus();
		return false;
	}
	console.log('Checking mw');
	let mw = document.getElementById('magicword');
	if (mw && mw.value == '') {
		let wms = document.getElementsByName('mw');
		if (wms.length > 0) {
			console.log('mw failed');
			if (final) mw.focus();
			return false;
		}
	}
	console.log('Checking odo');
	let odo = document.getElementById('OdoReading').value;
	//alert('Check2');
	odoChanged(odo,true);
	//alert('Returning true');
	let fb = document.getElementById('FuelBalance');
	if (fb) {
		console.log('saving fb of '+fb.value);
		document.getElementById('saveFuelBalance').value = fb.value;
	}

	
	return true;
}

