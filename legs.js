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

function saveLegsEditTable() {

    let tab = document.getElementById('LegsDataTable');
    if (!tab) return;
    let dta = JSON.parse(document.getElementById('LegsDataJSON').value);
    if (!dta) return;
    let tbody = tab.getElementsByTagName('tbody')[0];
    let ix = 0;
    for (let tr in tbody.getElementsByTagName('tr')) {
        if (!dta[ix]) break;
        console.log('Saving row '+(ix + 1));
        let x = '#LegsDataTable>tbody tr:nth-child('+(ix+1)+') input[name=';
        dta[ix]['StartTime'] = document.querySelector(x+'rsd]').value+'T'+document.querySelector(x+'rst]').value;
        dta[ix]['FinishTime'] = document.querySelector(x+'rfd]').value+'T'+document.querySelector(x+'rft]').value;
        dta[ix]['MaxHours'] = document.querySelector(x+'rmh]').value;
        ix++;
    }
    document.getElementById('LegsDataJSON').value = JSON.stringify(dta);
    enableSaveButton();
}

function showLegsEditTable() {

    let tab = document.getElementById('LegsDataTable');
    if (!tab) return;
    let dta = JSON.parse(document.getElementById('LegsDataJSON').value);
    if (!dta) return;
    let num = document.getElementById('NumLegs').value;

    let tbody = tab.getElementsByTagName('tbody')[0];
    tbody.innerHTML = '';

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


}

function splitLegDatetime(dt) {
/* Accept either 'T' or space as splitting date/time */

    const re = /(.+)[T ](.+)/;
    let res = re.exec(dt);
    return res;
		
}

function adjustLegCount() {

    let dta = JSON.parse(document.getElementById('LegsDataJSON').value);
    if (!dta) return;
    let num = document.getElementById('NumLegs').value;
    let cur = dta.length;
    if (num > cur) {
        while (cur < num) {
            dta[cur] = dta[0];
            cur++;
        }
    }

    document.getElementById('LegsDataJSON').value = JSON.stringify(dta);
    showLegsEditTable();


}

