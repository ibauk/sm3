/*
 * I B A U K   -   S C O R E M A S T E R
 *
 * legs.js
 *
 * I provide handling for the JSON data relating to rally legs.
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

function changeCurrentLeg(newleg) {

    let cl = document.getElementById('CurrentLeg');

    /* Must save changes before running me */
    let cmd = document.getElementById('savedata');
    if (cmd && !cmd.disabled) {
        document.getElementById('CurrentLeg'+cl.value).checked = true;
        return;
    }
    let r0 = document.getElementById('LegRuSure0').value;
    let r1 = document.getElementById('LegRuSure1').value;
    if (!window.confirm(r0+"\n\n"+r1)) {
        document.getElementById('CurrentLeg'+cl.value).checked = true;
        return;
    }

    let xhttp = new XMLHttpRequest();
	xhttp.onreadystatechange = function() {
		let ok = new RegExp("\\W*ok\\W*");
		if (this.readyState == 4 && this.status == 200) {
			console.log('{'+this.responseText+'}');
			if (!ok.test(this.responseText)) {
				alert(UPDATE_FAILED);
			}
		}
	};
	xhttp.open("GET", "entrants.php?c=changeleg&oldleg="+cl.value+'&newleg='+newleg, true);
	xhttp.send();

    //cl.value = newleg;

    //enableSaveButton();
    //return true;
}

function saveLegsEditTable() {

    let tab = document.getElementById('LegsDataTable');
    if (!tab) return;
    let dta = JSON.parse(document.getElementById('LegsDataJSON').innerText);
    if (!dta) return;
//    alert(dta.length);

    for (let ix=0; ix < dta.length; ix++) {
        console.log('Saving row '+(ix + 1));
        let x = '#LegsDataTable tbody tr:nth-child('+(ix+1)+') input[name=';
        if (!document.querySelector(x+'rsd]')) break;
        console.log('Still saving '+(ix + 1));
        dta[ix]['StartTime'] = document.querySelector(x+'rsd]').value+'T'+document.querySelector(x+'rst]').value;
        dta[ix]['FinishTime'] = document.querySelector(x+'rfd]').value+'T'+document.querySelector(x+'rft]').value;
        dta[ix]['MaxHours'] = document.querySelector(x+'rmh]').value;
    }
    document.getElementById('LegsDataJSON').innerText = JSON.stringify(dta);
    enableSaveButton();
}

function showLegsEditTable() {

    let tab = document.getElementById('LegsDataTable');
    if (!tab) return;
    let dta = JSON.parse(document.getElementById('LegsDataJSON').innerText);
    if (!dta) return;
    let num = document.getElementById('NumLegs').value;

    let tbody = tab.getElementsByTagName('tbody')[0];
    tbody.innerHTML = '';

    if (num > 1) {
        tab.style.display = 'inherit';
        for (let prop = 0; prop < num; prop++) {
            console.log('Making row '+prop);
            let tr = document.createElement('tr');
            tr.classList.add('LegRow');
            let trow = document.createElement('td');
            trow.innerHTML = ''+(parseInt(prop)+1)+'';
            trow.classList.add('LegHdr');
            tr.appendChild(trow);
            let dt = splitLegDatetime(dta[prop]['StartTime']);
            let tsd = document.createElement('td');
            tsd.innerHTML = '<input type="date" name="rsd" value="'+dt[1]+'" onchange="saveLegsEditTable();">';
            tsd.classList.add('LegStartDate');
            tr.appendChild(tsd);
            let tst = document.createElement('td');
            tst.innerHTML = '<input type="time" name="rst" value="'+dt[2]+'" onchange="saveLegsEditTable();">';
            tst.classList.add('LegStartTime');
            tr.appendChild(tst);
            dt = splitLegDatetime(dta[prop]['FinishTime']);
            let tfd = document.createElement('td');
            tfd.innerHTML = '<input type="date" name="rfd" value="'+dt[1]+'" onchange="saveLegsEditTable();">';
            tfd.classList.add('LegFinishDate');
            tr.appendChild(tfd);
            let tft = document.createElement('td');
            tft.innerHTML = '<input type="time" name="rft" value="'+dt[2]+'" onchange="saveLegsEditTable();">';
            tft.classList.add('LegFinishTime');
            tr.appendChild(tft);
            let mh = document.createElement('td');
            mh.innerHTML = '<input type="number" class="smallnumber" name="rmh" value="'+dta[prop]['MaxHours']+'" onchange="saveLegsEditTable();">';
            mh.classList.add('LegMaxHours');
            tr.appendChild(mh);
            tbody.appendChild(tr);
        }
    } else
        tab.style.display = 'none';

    let p2 = document.getElementById('LegsX2');
    if (num > 1)
        p2.style.display = 'inherit';
    else 
        p2.style.display = 'none';
    
    let cls = document.getElementById('CurrentLegSpan');
    if (num > 1) {
        cls.style.display = 'inherit';
        let curleg = document.getElementById('CurrentLeg').value;
        let cls2 = document.querySelector('#CurrentLegSpan span');
        cls2.innerHTML = '';
        let cls2x = '';
        for ( let cl = 0; cl <= num; cl++) {
            cls2x += '<label class="short" for="CurrentLeg'+cl+'">'+cl+'</label> ';
            cls2x += '<input type="radio" value="'+cl+'" id="CurrentLeg'+cl+'" name="CurrentLegArray"';
            if (cl == curleg)
                cls2x += ' checked ';
            cls2x += ' onchange="return changeCurrentLeg(this.value);">';
        }
        cls2.innerHTML = cls2x;
    
    } else 
        cls.style.display = 'none';
    

}

function splitLegDatetime(dt) {
/* Accept either 'T' or space as splitting date/time */

    const re = /(.+)[T ](.+)/;
    let res = re.exec(dt);
    return res;
		
}

function adjustLegCount() {

    let dta = JSON.parse(document.getElementById('LegsDataJSON').innerText);
    if (!dta) return;
    let num = document.getElementById('NumLegs').value;
    let cur = dta.length;
    if (num > cur) {
        while (cur < num) {
            dta[cur] = dta[0];
            cur++;
        }
    }

    document.getElementById('LegsDataJSON').innerText = JSON.stringify(dta);
    showLegsEditTable();


}

