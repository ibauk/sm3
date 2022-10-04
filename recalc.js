/*
 * I B A U K   -   S C O R E M A S T E R
 *
 * recalc.js
 *
 * I provide scoring algorithm v3 in to the browser-hosted scorecard
 *
 * I am written for readability rather than efficiency, please keep me that way.
 *
 *
 * Copyright (c) 2021 Bob Stammers
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

var scorex      = [];

class SCOREXLINE {

    constructor() {
        this.id             = '';
        this.desc           = '';
        this.pointsDesc     = '';
        this.points         = '';
        this.totalPoints    = '';
    }

    asHTML() {
        let res = '<tr><td class="sxcode">'+this.id+'</td>';
        res += '<td class="sxdesc">'+this.desc;
        res += '<span class="sxdescx">'+this.pointsDesc+'</span></td>';
        res += '<td class="sxitempoints">'+this.points+'</td>';
        res += '</tr>';
        return res;
    }

}

var reasons = [];

function editTimes() {
    let st = document.getElementById('showtimes');
    let et = document.getElementById('edittimes');
    st.classList.remove('showspan');
    st.classList.add('hide');
    et.classList.remove('hide');
    et.classList.add('showspan');
}

function showMiles() {
    let sm = document.getElementById('showmiles');
    let so = document.getElementById('showodos');
    so.classList.remove('showspan');
    so.classList.add('hide');
    sm.classList.remove('hide');
    sm.classList.add('showspan');
}
function showOdos() {
    let sm = document.getElementById('showmiles');
    let so = document.getElementById('showodos');
    sm.classList.remove('showspan');
    sm.classList.add('hide');
    so.classList.remove('hide');
    so.classList.add('showspan');
}
function showTimes() {
    let st = document.getElementById('showtimes');
    let et = document.getElementById('edittimes');
    et.classList.remove('showspan');
    et.classList.add('hide');
    st.classList.remove('hide');
    st.classList.add('showspan');
}

function updateFinishtime() {

    recalcScorecard();

}

function updateOdoStart() {

    calcMiles();
    recalcScorecard();

}

function updateOdoFinish() {

    calcMiles();
    recalcScorecard();

}
function updateStarttime() {

    let startx = document.getElementById('StartDate').value+'T'+document.getElementById('StartTime').value;
    let rstart = document.getElementById('RallyTimeStart').value;
    if (startx < rstart) {
        startx = rstart;
        document.getElementById('StartDate').value = startx.substring(0,10);
        document.getElementById('StartTime').value = startx.substring(11);
    }
    setFinishTimeDNF();
    recalcScorecard();

}

function initScorecardVariables() {

    for (let rr of document.getElementsByName('rejectreason')) {
        reasons[rr.getAttribute('data-code')] = rr.value;
    }

    let catcounts = [];
    for (let i = 0; i <= CALC_AXIS_COUNT; i++) {
        catcounts[i] = [];
        catcounts[i]['catcounts'] = [];
        catcounts[i]['samecount'] = 0;
        catcounts[i]['samepoints'] = 0;
        catcounts[i]['lastcat'] = -1;
    }

    zapCats();

    for(let c of document.getElementsByName('catCompoundRules'))
        c.setAttribute('data-triggered',RULE_NOT_TRIGGERED);

    for(let b of document.getElementsByName('BonusID[]'))
        b.setAttribute('data-scored',false);

    for(let s of document.getElementsByName('specialValues'))
        s.setAttribute('data-scored',false);

    for(let c of document.getElementsByName('ComboID[]'))
        c.setAttribute('data-scored',false);

    return catcounts;
    
}

function parseBonusClaim(bonus,obj) {
    // Format is bonus[=[points][;minutes]]
    console.log("Parsing BC "+JSON.stringify(bonus));
    let m = /([A-Z0-9]+-?)=?(\d+)?(X|)?(P|)?;?(\d+)?/.exec(bonus);
    console.log("Parsed m = "+JSON.stringify(m));
    obj.bon = m[1];
    obj.points = m[2];
    obj.xp = m[3] == 'X';
    obj.pp = m[4] == 'P';
    obj.minutes = m[5];

    

/**    
    obj.points = '';
    obj.minutes = '';
    let e = bonus.indexOf('=');
    if (e < 0) {
        obj.bon = bonus;
        return;
    }
    obj.bon = bonus.substring(0,e);
    let pm = bonus.substring(e + 1);
    e = pm.indexOf(';');
    if (e < 0) {
        obj.points = pm;
        return;
    }
    obj.points = pm.substring(0,e);
    obj.minutes = pm.substring(e + 1);
**/    
    console.log("Object is "+JSON.stringify(obj));
    
}

function checkApplySequences(bonv,catcounts,bonusPoints) {

    let extraBonusPoints = 0;

    for(let ccr of document.getElementsByName('catCompoundRules')) {
        if (ccr.getAttribute('data-ruletype') != CC_SEQUENCERULE)
            continue;
    
        // I don't care whether this was specified as bonus level or axis level
        
        let ccr_cat = parseInt(ccr.getAttribute('data-cat'));
        let ccr_axis = parseInt(ccr.getAttribute('data-axis'));
        let ccr_min = parseInt(ccr.getAttribute('data-min'));
        let ccr_pwr = parseFloat(ccr.getAttribute('data-pwr'));
        
    
        if (bonv != '') { // is there a current bonus or are we done.
            if (catcounts[ccr_axis]['lastcat'] == bonv.getAttribute('data-cat'+ccr_axis)) {
                continue; // still building sequence. wait until it's built.
            }
        }
        if (catcounts[ccr_axis]['samecount'] < ccr_min) {
            continue;
        }
        // Now trigger sequential bonus
    
            
        let cdesc = '[###]';
        let clbl = document.getElementById('cat'+ccr_axis+'_'+catcounts[ccr_axis]['lastcat']);
        if (clbl !== null)
            cdesc = clbl.parentElement.firstChild.innerText;
        
    
        console.log('SP='+parseInt(catcounts[ccr_axis]['samepoints'])+' Pwr='+parseFloat(ccr_pwr));
        //'&#x2713; == checkmark
        let bonusDesc = '&#x2713; '+cdesc+ " x "+ccr_min;
        if (catcounts[ccr_axis]['samecount'] > ccr_min) {
            bonusDesc += '+';
        }
        let pointsDesc = '';
        if (ccr.getAttribute('data-pm') == CAT_ResultPoints) {
            extraBonusPoints = ccr_pwr;
        } else { 
            extraBonusPoints = catcounts[ccr_axis]['samepoints'] * ccr_pwr;
            if (ccr_pwr != 1 && ccr_pwr != 0) {
                pointsDesc = " (+ "+catcounts[ccr_axis]['samepoints'];            
                pointsDesc += "x"+ccr_pwr+ ")";
            }
        }
    
        bonusPoints += extraBonusPoints;
    
        let sx = new SCOREXLINE();
        sx.desc = bonusDesc;
        sx.pointsDesc = pointsDesc;
        sx.points = extraBonusPoints;
        sx.totalPoints = bonusPoints;
        
        scorex.push(sx);
        
        
    
        break;  // Only apply the first matching rule
    
                
    }
    return extraBonusPoints;
}

function recalcScorecard() {

    let catcounts = initScorecardVariables();
    
    // Now fetch the base data

    // Rejected claims
    let tmp = document.getElementById('RejectedClaims').value.split(',');
    let rejectedClaims = {};
    for (let r of tmp) {
        // Format is (code)=(reason)
        console.log('c=r ['+r+'] tmp={'+JSON.stringify(tmp)+'} ++ '+document.getElementById('RejectedClaims').value);
        let e = r.indexOf('=');
        rejectedClaims[r.substr(0,e)] = parseInt(r.substr(e + 1));
    }

    //console.log(' RejectedClaims:'+JSON.stringify(rejectedClaims));
    //print_r($rejectedClaims);
    //echo('<br>');

    scorex = []; // Reinitialize the score explanation

    let bonusPoints = 0;
    let multipliers = 1;
    let numBonusesTicked = 0;
    let restMinutes = 0;
    let bonusesScored = {};    // Keeps track of ordinary, special and combo bonuses successfully claimed

    // Ordinary bonuses

    let BA = document.getElementById('BonusesVisited').value.split(','); 
        
    for(let bonus of BA) {

        if (bonus === '')
            continue;

        let obj = {bon: '', points: '', minutes: '', xp: false, pp: false};
        parseBonusClaim(bonus,obj);
        
        let bonv = '';
        for(let bv of document.getElementsByName('BonusID[]')) {
            if (bv.value === obj.bon) {
                bv.setAttribute('data-scored',true);
                let xpx = '';
                if (obj.xp) {
                    xpx = 'true';
                }
                bv.setAttribute('data-xp',xpx);
                let ppx = '';
                if (obj.pp) {
                    ppx = 'true';
                }
                bv.setAttribute('data-pp',ppx);
                bonv = bv;
                break;
            }
        }
        console.log("rejectedclaims == "+JSON.stringify(rejectedClaims));
        console.log("Checking "+obj.bon+" for RC="+rejectedClaims.hasOwnProperty(obj.bon));
        if (bonv === '') // non-existent bonus!
            continue;

        if (obj.points === '')
            obj.points = bonv.getAttribute('data-points');
        if (obj.minutes === '')
            obj.minutes = bonv.getAttribute('data-minutes');
            

        let basicBonusPoints = obj.points;

        bonusPoints += checkApplySequences(bonv,catcounts,bonusPoints);


        if (obj.bon === '' || rejectedClaims.hasOwnProperty(obj.bon)) { // is it a rejected claim?

            // Zap the sequence then
            for (let i = 1; i <= CALC_AXIS_COUNT; i++) {
                catcounts[i]['samecount'] = 0;
                catcounts[i]['samepoints'] = 0;
                catcounts[i]['lastcat'] = -1;
            }


            let sx = new SCOREXLINE();
            sx.id = bonv.value;
            sx.desc = bonv.getAttribute('data-desc')+'<br>'+CLAIM_REJECTED+' - '+reasons[rejectedClaims[obj.bon]];
            sx.pointsDesc = '';
            sx.points = 'X';
            sx.totalPoints = '';
            //echo('<table>'.$sx->asHTML().'</table>');
            scorex.push(sx);
            continue;
        }
        

        bonusesScored[obj.bon] = obj.bon;
        numBonusesTicked++;
        restMinutes += parseInt(obj.minutes);
        let pointsDesc = "";
        
        if (obj.minutes > 0) {
            pointsDesc = pointsDesc+' ['+formatRestMinutes(obj.minutes)+'] ';
        }


        // Keep track of cat counts
        catcounts = updateCatcounts(bonv,catcounts,basicBonusPoints);

        // Look for and apply cat mods to basic points
        for(let ccr of document.getElementsByName('catCompoundRules')) {
            if (ccr.getAttribute('data-ruletype') != CC_ORDINARYSCORE)
                continue;
            console.log('ccr-1');
            if (ccr.getAttribute('data-target') != CAT_ModifyBonusScore)
                continue;   // Only interested in rules affecting basic bonus
            console.log('ccr-2');
            if (ccr.getAttribute('data-pm') != CAT_ResultPoints) // Multipliers not allowed at this level
                continue;
            console.log('ccr-3');
            let ccr_cat = parseInt(ccr.getAttribute('data-cat'));
            let ccr_axis = parseInt(ccr.getAttribute('data-axis'));
            let ccr_min = parseInt(ccr.getAttribute('data-min'));
            let ccr_pwr = parseFloat(ccr.getAttribute('data-pwr'));
            console.log("Checking rule ccr-dat"+ccr_cat);
            if (ccr_cat > 0)
                if (bonv.getAttribute('data-cat'+ccr_axis) != ccr_cat)
                    continue;

            console.log("Applying rule ccr_cat="+ccr_cat);
            let catcount = 0;
            if (ccr_cat == 0) {
                for(let cc of catcounts[ccr_axis]['catcounts']) {
                    console.log('testing cc = '+cc);
                    if (typeof cc !== 'undefined')
                        catcount += cc;
                }
            }
            else if (typeof(catcounts[ccr_axis]['catcounts'][ccr_cat]) !== 'undefined')
                catcount = catcounts[ccr_axis]['catcounts'][ccr_cat];
            console.log("Catcount="+catcount+' Min='+ccr_min);
            if (catcount < ccr_min)
                continue;

            let pdx = '';
            if (ccr_pwr === 0) {
                pdx = basicBonusPoints+" x "+(catcount - 1);
                basicBonusPoints = basicBonusPoints * (catcount - 1);
            } else {
                pdx = ""+basicBonusPoints+" x "+ccr_pwr+"^"+(catcount - 1);
                basicBonusPoints = basicBonusPoints * (Math.pow(ccr_pwr,(catcount - 1)));
            }
            if (pdx !== "")
                pointsDesc = pointsDesc+" ( "+pdx+" ) ";

                    //echo(" BonusMod $catcount = $basicBonusPoints<br>");

            break;  // Only apply the first matching rule
            
        }

        if (bonv.getAttribute('data-xp')=='true') {
            pointsDesc += ' &#8224;';
        }
        if (bonv.getAttribute('data-pp')=='true') {
            pointsDesc += ' &#10016;';
        }



        // basicBonusPoints is now the live figure
        bonusPoints += parseInt(basicBonusPoints);    


        let sx = new SCOREXLINE();
        sx.id = bonv.value;
        sx.desc = bonv.getAttribute('data-desc');
        sx.pointsDesc = pointsDesc;
        sx.points = basicBonusPoints;
        sx.totalPoints = bonusPoints;

        console.log('Processing bonus '+obj.bon+'  +'+basicBonusPoints+' = '+bonusPoints+' '+sx.asHTML());

        scorex.push(sx);






    } // Ordinary bonus loop

    bonusPoints += checkApplySequences('',catcounts,bonusPoints);

    // Combos
    
    let combosScored = {};
    for(let c of document.getElementsByName('ComboID[]')) {

        console.log("Checking combo "+c.value+' Bids='+c.getAttribute('data-bids'));

        // Is this combo already marked as rejected, a necessarily manual act?
        if (rejectedClaims.hasOwnProperty(c.value)) { // is it a rejected claim?
            c.parentElement.classList.add('rejected');
            c.parentElement.classList.remove('checked');
                let sx = new SCOREXLINE();
            sx.id = c.value;
            sx.desc = c.getAttribute('data-desc')+'<br>'+CLAIM_REJECTED+' - '+reasons[rejectedClaims[c.value]];
            sx.pointsDesc = '';
            sx.points = 'X';
            sx.totalPoints = '';
            //echo('<table>'.$sx->asHTML().'</table>');
            scorex.push(sx);
            continue;
        }

    
        let numbids = 0;
        for(let b of c.getAttribute('data-bids').split(',')) 
            if (bonusesScored.hasOwnProperty(b))
                numbids++;
        if (numbids < c.getAttribute('data-minticks')) {
            c.parentElement.classList.remove('checked');
            c.checked = false;
            continue;
        }

            console.log('Scoring combo '+c.value);
        c.setAttribute('data-scored',true);
        c.checked = true;
        c.parentElement.classList.add('checked');
        c.parentElement.classList.remove('rejected');
        let pts = c.getAttribute('data-pts').split(',');
        let pointsDesc = "";
        let mults = 0;
        let basicBonusPoints = 0;
        if (c.getAttribute('data-pm') == CMB_ScoreMults) {
            console.log('Combo scores mults '+pts[numbids - 1]);
            mults = pts[numbids - 1];
            basicBonusPoints = 0;
        } else {
            basicBonusPoints = pts[numbids - 1];
            mults = 0;
        }
        //echo(" BP=".$basicBonusPoints."; nb=".$numbids." === ");
        bonusesScored[c.value] = c.value;
        combosScored[c.value] = c.value;

        // Keep track of cat counts
        catcounts = updateCatcounts(c,catcounts,basicBonusPoints);


        bonusPoints += parseInt(basicBonusPoints);
        console.log("Processing combo "+c.value+' + '+basicBonusPoints+' = '+bonusPoints+' ['+numbids+'/'+c.getAttribute('data-minticks')+'] Mx='+mults);
        multipliers += parseFloat(mults);

        let sx = new SCOREXLINE();
        sx.id = c.value;
        sx.desc = c.getAttribute('data-desc');
        sx.pointsDesc = " ( "+numbids+" / "+c.getAttribute('data-maxticks')+" ) ";
        if (c.getAttribute('data-pm') == CMB_ScoreMults) {
            sx.points = 'x '+mults;
        } else {
            sx.points = basicBonusPoints;
        }
        sx.totalPoints = bonusPoints;
        scorex.push(sx);

        //echo("C:  $c->cid - $c->desc$pointsDesc = $basicBonusPoints = $bonusPoints<br>");

    }

    console.log("Setting RestMinutes to "+restMinutes);
    document.getElementById('RestMinutes').value = restMinutes;

    let bv = document.getElementById('CombosTicked');
    const keys = Object.keys(combosScored);
    bv.value = '';
    keys.forEach((key, index) => {
        if (combosScored[key]!=='') {
            if (bv.value !== '') {
                bv.value += ',';
            }
            bv.value += combosScored[key];
        }            
    });


    //debugCombos();

    //debugCatcounts($catcounts);

    //                      CALCULATE AXIS SCORES

    let nzAxisCounts = [];
    for (let i = 1; i <= CALC_AXIS_COUNT; i++) 
        nzAxisCounts[i] = countNZ(catcounts[i]['catcounts']);


    // First rules for number of non-zero cats

    let lastAxis = -1; 
    let lastmin = '';
    for(let ccr of document.getElementsByName('catCompoundRules')) {

        if (ccr.getAttribute('data-ruletype') == CC_SEQUENCERULE)
            continue;

        console.log('Trying compound rule want method '+CAT_NumNZCatsPerAxisMethod);
        if (ccr.getAttribute('data-nmethod') != CAT_NumNZCatsPerAxisMethod || ccr.getAttribute('data-target') != CAT_ModifyAxisScore) 
            continue;
        console.log('Looking good');
        let ccr_cat = parseInt(ccr.getAttribute('data-cat'));
        let ccr_axis = parseInt(ccr.getAttribute('data-axis'));
        let ccr_min = parseInt(ccr.getAttribute('data-min'));
        let ccr_pwr = parseFloat(ccr.getAttribute('data-pwr'));
        let ccr_rtype = parseInt(ccr.getAttribute('data-ruletype'));
        let ccr_pm = parseInt(ccr.getAttribute('data-pm'));
        if (ccr_axis <= lastAxis) // Process each axis only once
            continue;

        console.log('axis ok');

        let nzCount = 0;
        if (ccr_axis > 0)
            nzCount = nzAxisCounts[ccr_axis];
        else
            for (let i = 1; i <= CALC_AXIS_COUNT; i++)  
                nzCount += nzAxisCounts[i];

        console.log('Comparing count '+nzCount+' to min '+ccr_min);
        if (nzCount < ccr_min) {
            lastmin = ccr_min;
            continue;
        }

        // Let's apply this rule then

        lastAxis = ccr_axis;
        ccr.setAttribute('data-triggered',RULE_TRIGGERED);
        console.log('Applying compound rule a='+ccr_axis+', c='+ccr_cat);
        let points = chooseNZ(ccr_pwr,nzCount);
        let bpx = '';
        if (ccr_rtype === CAT_DNF_Unless_Triggered) { // DNF type condition
            points = '&#x2713;'; //checkmark
        } else if (ccr_rtype === CAT_DNF_If_Triggered) {
            points = EntrantStatusDNF;
        } else if (ccr_rtype === CAT_PlaceholderRule ) {
            continue;
        } else if (ccr_pm === CAT_ResultPoints) {
            bonusPoints += points;
        } else { // multipliers
            console.log('multipliers+='+points);
            multipliers += points;
            bpx = 'x ';
        }

        let sx = new SCOREXLINE();
        sx.id = '';
        let axl = document.getElementById('axisLabel'+ccr_axis);
        if (axl === null)
            sx.desc = '###';
        else
            sx.desc = axl.value+': n';
        if (ccr_cat !== 0) {
            let clbl = document.getElementById('cat'+ccr_axis+'_'+ccr_cat);
            if (clbl === null)
                sx.desc += '[###]';
            else
                sx.desc += '['+clbl.parentElement.firstChild.innerText+'] ';
        } else
            sx.desc += ' ';
        let pi = parseInt(points);
        if ((isNaN(pi) || pi <= 0) && lastmin != '')
            sx.desc += ' &lt; '+lastmin;
        else
            sx.desc += ' &#8805; '+ccr_min;
        //$sx->desc .= $ccr->min;
        sx.pointsDesc = "";
        sx.points = bpx+points;
        sx.totalPoints = bonusPoints;
        scorex.push(sx);


    } // NZ axis

    // Secondly, rules for number of bonuses per cat

    let lastaxis = -1;
    let lastcat = -1;
    lastmin = '';

    for(let ccr of document.getElementsByName('catCompoundRules')) {

        if (ccr.getAttribute('data-ruletype') == CC_SEQUENCERULE)
            continue;

        if (ccr.getAttribute('data-nmethod') != CAT_NumBonusesPerCatMethod || ccr.getAttribute('data-target') == CAT_ModifyBonusScore)
            continue;

        let ccr_cat = parseInt(ccr.getAttribute('data-cat'));
        let ccr_axis = parseInt(ccr.getAttribute('data-axis'));
        let ccr_min = parseInt(ccr.getAttribute('data-min'));
        let ccr_pwr = parseFloat(ccr.getAttribute('data-pwr'));
        let ccr_rtype = parseInt(ccr.getAttribute('data-ruletype'));
        let ccr_pm = parseInt(ccr.getAttribute('data-pm'));
    
        if (ccr_axis <= lastaxis && ccr_cat <= lastcat)
            continue;

        let catcount = 0;
        if (ccr_cat === 0)
            for(let cc of catcounts[ccr_axis]['catcounts'])
                catcount += cc;
        else if (typeof(catcounts[ccr_axis]['catcounts'][ccr_cat]) !== 'undefined')
            catcount = catcounts[ccr_axis]['catcounts'][ccr_cat];

        if (catcount < ccr_min) {
            lastmin = ccr_min;
            continue;
        }
        ccr.setAttribute('data-triggered',RULE_TRIGGERED);
        if (lastaxis < 0)
            lastaxis = ccr_axis;
        if (ccr_axis > lastaxis)
            lastcat = -1;
        else
            lastcat = ccr_cat;
        lastaxis = ccr_axis;

        let basicPoints = 0;
        let bpx = '';
        if (ccr_rtype === CAT_DNF_Unless_Triggered) { // DNF type condition
            basicPoints = '&#x2713;';//checkmark
        } else if (ccr_rtype === CAT_DNF_If_Triggered) {
            basicPoints = EntrantStatusDNF;
        } else if (ccr_rtype === CAT_PlaceholderRule ) {
            continue;
        } else if (ccr_pm === CAT_ResultPoints) {
            basicPoints = chooseNZ(ccr_pwr,catcount);
            bonusPoints += basicPoints;
            //echo("RC $basicPoints / $bonusPoints <br>");
        } else { // Multipliers then
            let mults = chooseNZ(ccr_pwr,catcount);
            console.log('xMultipliers+='+mults);
            multipliers += parseFloat(mults);
            bpx = 'x ';
            basicPoints = mults;
        }


        let sx = new SCOREXLINE();
        sx.id = '';
        let axl = document.getElementById('axisLabel'+ccr_axis);
        if (axl === null)
            sx.desc = '###';
        else
            sx.desc = axl.value+': n';

        if (ccr_cat !== 0) {
            let clbl = document.getElementById('cat'+ccr_axis+'_'+ccr_cat);
            if (clbl === null)
                sx.desc += '[###]';
            else
                sx.desc += '['+clbl.parentElement.firstChild.innerText+']';
        } else
            sx.desc += ' ';
    
        console.log('bp='+basicPoints+' lm="'+lastmin+'" i(bp)='+parseInt(basicPoints));
        let pi = parseInt(basicPoints);
        if ((isNaN(pi) || pi <= 0) && lastmin != '')
            sx.desc += ' &lt; '+lastmin;
        else
            sx.desc += ' &#8805; '+ccr_min;
        //$sx->desc .= $ccr->min;
        sx.pointsDesc = "";
        sx.points = bpx+basicPoints;
        sx.totalPoints = bonusPoints;
        scorex.push(sx);


    } // Bonus per cat axis

    let tp = calcTimePenalty();
    let tpP = tp[0]; // Points
    let tpM = tp[1]; // Multipliers

    if (tpM != 0 || tpP != 0) {
        console.log('xxMultipliers+='+tpM);
        multipliers += parseFloat(tpM);
        bonusPoints += tpP;
        let sx = new SCOREXLINE();
        let tpx = RPT_TPenalty;
        let tpxx = document.getElementById('RTP_TPenalty');
        console.log('tpxx is '+tpxx+' tp2='+tp[2]);
        if (tpxx) {
            tpx = tpxx.value;
        }
        sx.id = tpx;
        if (tp[2].length > 1 && (tp[2].substring(0,10) == tp[3].substring(0,10))) {
            sx.desc = tp[3].substring(11,16)+' &#8805; '+tp[2].substring(11,16);
        } else {
            sx.desc = tp[3].replace('T',' ')+' &#8805; '+tp[2].replace('T',' ').substring(0,16);
        }
        if (tpM != 0) {
            sx.points = ''+(tpM*100)+'%';
        } else {
            sx.points = tpP;
        }
        sx.totalPoints = bonusPoints;
        scorex.push(sx);
    }

    let mp = calcMileagePenalty();
    let mpP = mp[0]; // Points
    let mpM = mp[1]; // Multipliers

    if (mpM != 0 || mpP != 0) {
        console.log('xyMultipliers+='+mpM);
        multipliers += parseFloat(mpM);
        bonusPoints += mpP;
        let sx = new SCOREXLINE();
        let tpx = RPT_MPenalty;
        let tpxx = document.getElementById('RTP_MPenalty');
        console.log('mpxx is '+tpxx);
        if (tpxx) {
            tpx = tpxx.value;
        }
        sx.id = tpx;
        sx.desc = mp[3]+' '+document.getElementById('bduText').value+' > '+mp[2];
        if (mpM != 0) {
            sx.points = ''+mpM+' x';
        } else {
            sx.points = mpP;
        }
        sx.totalPoints = bonusPoints;
        scorex.push(sx);
    }

    calcAvgSpeed();
    
    let sp = calcSpeedPenalty(false);

    if (sp[0] != 0) {
        bonusPoints += sp[0];
        let sx = new SCOREXLINE();
        let tpx = RPT_SPenalty;
        let tpxx = document.getElementById('RTP_SPenalty');
        console.log('spxx is '+tpxx);
        if (tpxx) {
            tpx = tpxx.value;
        }
        sx.id = tpx;
        sx.desc = document.getElementById('AvgSpeed').value+' > '+sp[1];
        sx.points = sp[0];
        sx.totalPoints = bonusPoints;
        scorex.push(sx);
    }
    

    if (multipliers != 1) {
        let sx = new SCOREXLINE();
        sx.desc = bonusPoints+' x '+multipliers;
        sx.points = parseInt(bonusPoints * multipliers);
        sx.totalPoints = sx.points;
        scorex.push(sx);
        bonusPoints = sx.points;
    }



    document.getElementById('TotalPoints').value = bonusPoints;
    let sxl = new SCOREXLINE();
    sxl.desc = RPT_Total;
    sxl.points = parseInt(document.getElementById('TotalPoints').value);
    scorex.push(sxl);


    setFinisherStatusx();
    writeScorex();
    showCats(catcounts);
    enableSaveButton();
    //alert("RM="+document.getElementById('RestMinutes').value+"; TP="+document.getElementById('TotalPoints').value);

}

function tickBonus(obj) {

    let bv = document.getElementById('BonusesVisited');
    let ticks = bv.value.split(',');
    console.log("tb: Bonuses ticked - "+bv.value);
    let b = {};
    let bix = [];
    for (let t of ticks) {

        let e = t.indexOf('=');
        if (e < 0) {
            b[t] = t;
            bix.push(t);
        } else {
            b[t.substring(0,e)] = t;
            bix.push(t.substring(0,e));
        }
    }
    console.log('tb: bix = '+JSON.stringify(bix));
    if (obj.checked) {
        console.log('tb: checked');
        if (b[obj.value] === undefined) {
            b[obj.value] = obj.value;
            bix.push(obj.value);
        }
        if (bonusScoredOk(obj)) {
            console.log('tb: scoredok');
            obj.parentElement.classList.add('checked');
            obj.parentElement.classList.remove('rejected');
            var lbl = obj.parentNode.firstChild.innerHTML;
            if (obj.getAttribute('data-askpoints') === '1') {
                let pts = obj.getAttribute('data-points');
	
                let npts = window.prompt(ASK_POINTS+' '+lbl,pts);
                if (npts != null)
                    pts = parseInt(npts);
                
                obj.setAttribute('data-points',pts);        
            }
            if (obj.getAttribute('data-askminutes') === '1') {
                let mins = obj.getAttribute('data-minutes');
                let nmins = window.prompt(ASK_MINUTES+' '+lbl,mins);
                if (nmins != null)
                    mins = parseInt(nmins);
                obj.setAttribute('data-minutes',mins);
        
            }
        } else {
            console.log('tb: not scored ok');
            obj.parentElement.classList.add('rejected');
            obj.parentElement.classList.remove('checked');
        }
    } else {
        console.log('tb: unchecked');
        setRejectedClaim(obj.value,0);
        obj.parentElement.className = "showbonus";
        if (b[obj.value] !== undefined) {
            delete b[obj.value];
        }
    }
    const keys = Object.keys(b);
    bv.value = '';
    let ixl = bix.length;
    // Object.keys returns an unordered list but I need to maintain the sequence so walk the separate key array instead
    //keys.forEach((key, index) => {
    for (let ix = 0; ix < ixl; ix++ ) {
        let key = bix[ix];
        if (b[key] == undefined)
            continue;
         
        if (b[key]!=='') {
            if (bv.value !== '') {
                bv.value += ',';
            }
            let e = b[key].indexOf('=');
            let x = b[key];
            if (e >= 0) {
                x = b[key].substring(0,e);
            }
    
            console.log("tb: Getting bonus "+x);
            let B = document.getElementById(x);
            let xp = '';
            if (B.getAttribute('data-xp')) {
                xp = 'X';
            }
            let pp = '';
            if (B.getAttribute('data-pp')) {
                pp = 'P';
            }
            bv.value += x+'='+B.getAttribute('data-points')+xp+pp+';'+B.getAttribute('data-minutes');
        }            
    }
    
    console.log("tb2: Bonuses ticked : "+bv.value);
    recalcScorecard();
}

function updateCatcounts(bonus,catcounts,points) {

    console.log('updateCatCounts called');
    // Keep track of cat counts
    for (let i = 1; i <= CALC_AXIS_COUNT; i++) {
        let cat = parseInt(bonus.getAttribute('data-cat'+i));
        console.log('checking cat '+cat+' with points='+points);


        if (cat == 0) {
            catcounts[i]['samecount'] = 0;
            catcounts[i]['samepoints'] = 0;
            catcounts[i]['lastcat'] = cat;
        } else if (cat == catcounts[i]['lastcat']) {
            catcounts[i]['samecount']++;
            catcounts[i]['samepoints'] = parseInt(catcounts[i]['samepoints']) + parseInt(points);
        } else {
            catcounts[i]['samecount'] = 1;
            catcounts[i]['samepoints'] = points;
            catcounts[i]['lastcat'] = cat;
        }


        if (cat < 1) 
            continue;
            
        if (typeof(catcounts[i]['catcounts'][cat]) === 'undefined')
            catcounts[i]['catcounts'][cat] = 1;
        else
            catcounts[i]['catcounts'][cat]++;
        // and overall figures
        if (typeof(catcounts[0]['catcounts'][cat]) === 'undefined')
            catcounts[0]['catcounts'][cat] = 1;
        else
            catcounts[0]['catcounts'][cat]++;
    }
    return catcounts;
}

function formatRestMinutes(minutes) {

    if (minutes < 1)
        return '0';
    let h = Math.floor(minutes / 60);
    let m = minutes % 60;
    
    if (h > 1 && m == 0)
        return h+" hrs";
    if (h == 1 && m == 0)
        return '60 mins';
    if (h > 0)
        return h+'h '+m+'m';
    if (m == 1)
        return '1 min';
    return m+' mins';
}

function getRiderNames() {

    let t = parseInt(document.getElementById('TeamID').value);
    if (t > 0)
        return document.getElementById('teamnames').value;
    return document.getElementById('crewname').innerHTML;
}
function getStatusText() {
	let es = document.getElementById('EntrantStatus');
    return  es.options[es.selectedIndex].text;
}

function ignoreClick(obj) {

    // Used on checkboxes that shouldn't be directly clickable (combos)

    if (obj.type === 'checkbox')
        obj.checked = !obj.checked;
}


function SFSx(status,x)
{
	var es = document.getElementById('EntrantStatus');
	es.value = status;
	es.setAttribute('title',x);
	if (x != '') {
        let sx = new SCOREXLINE();
        sx.id = '';
        sx.id = es.options[status].text;
        sx.desc = ' '+x;
        scorex.push(sx);
    }
}

function ccTestFail(ccr) {

    let msg = ''; 
    let axis = ccr.getAttribute('data-axis');
    msg += document.getElementById('axisLabel'+axis).value+': ';
    let cat = ccr.getAttribute('data-cat');
    let catx = cat;
    msg += ' n';
    if (cat == '0')
        catx = '' 
    else {
        let catd = document.getElementById('cat'+axis+'_'+cat);
        console.log('cat'+axis+'_'+cat+' == '+catd.tagName);
        catx = '['+catd.parentElement.firstChild.innerText+']';
    }
    msg += catx;
    if (ccr.getAttribute('data-triggered')!=RULE_TRIGGERED)
        msg += ' &lt; '
    else
        msg += ' &#8805; ';
    msg += ccr.getAttribute('data-min');

    return msg;
}

function setFinisherStatusx()
/*
 *
 *							s e t F i n i s h e r S t a t u s x
 *
 * This determines status depending on score, mileage, speed and timings.
 *
 */
{
	

	var CS = parseInt(document.getElementById('EntrantStatus').value);
	//if (CS != EntrantOK && CS != EntrantFinisher)
		//return;
	
    let sp = calcSpeedPenalty(true);
	if (sp[0])
		return SFSx(EntrantDNF,document.getElementById('AvgSpeed').value+' > '+sp[1]);
	
    let bdu = document.getElementById('bduText').value;

	var CM = parseInt(document.getElementById('CorrectedMiles').value);
	var MM = parseInt(document.getElementById('MinMiles').value);
	if (MM > 0 && CM < MM)
		return SFSx(EntrantDNF,bdu+' < '+MM);
	var PM = parseInt(document.getElementById('PenaltyMilesDNF').value);
	if (PM > 0 && CM > PM)
		return SFSx(EntrantDNF,bdu+' > '+PM);

	var DT = document.getElementById('FinishTimeDNF').value;
	var FT = document.getElementById('FinishDate').value + 'T' + document.getElementById('FinishTime').value;
	if (FT != 'T' && FT > DT) {
        if (FT.substring(0,10) == DT.substring(0,10))
            return SFSx(EntrantDNF,FT.substring(11)+' > '+DT.substring(11));
        else
		    return SFSx(EntrantDNF,FT.replace('T',' ')+' > '+DT.replace('T',' '));
    }
	
	var BL = document.getElementsByName('BonusID[]');
    console.log('Checking '+BL.length+' bonuses');
	for (var i = 0 ; i < BL.length; i++ ) {

		if (BL[i].getAttribute('data-reqd')==COMPULSORYBONUS && !bonusScoredOk(BL[i])) {
			return SFSx(EntrantDNF,DNF_MISSEDCOMPULSORY+BL[i].getAttribute('data-desc')+' [ '+BL[i].getAttribute('id')+' ]');
        } else if (BL[i].getAttribute('data-reqd')==MUSTNOTMATCH && bonusScoredOk(BL[i])) {
			return SFSx(EntrantDNF,DNF_HITMUSTNOT+' [ '+BL[i].getAttribute('id')+' ]');
        }
    }
	
	
	BL = document.getElementsByName('ComboID[]'); 
    console.log('Checking '+BL.length+' combos');
	for (var i = 0 ; i < BL.length; i++ ) {
		if (BL[i].getAttribute('data-reqd')==COMPULSORYBONUS && !bonusScoredOk(BL[i]))
			return SFSx(EntrantDNF,DNF_MISSEDCOMPULSORY+' [ '+BL[i].getAttribute('id')+' ]');
    }

	BL = document.getElementsByName('catCompoundRules');
    console.log('Testing '+BL.length+' rules');
	for (var i = 0 ; i < BL.length; i++ ) {
        console.log('ix='+i+' rt='+BL[i].getAttribute('data-ruletype')+' tr='+BL[i].getAttribute('data-triggered'));
		if (BL[i].getAttribute('data-ruletype')==CC_UNTRIGDNF && BL[i].getAttribute('data-triggered')!=RULE_TRIGGERED) {
            let dnf = ccTestFail(BL[i]);
			return SFSx(EntrantDNF,dnf);
        } else if (BL[i].getAttribute('data-ruletype')==CC_IFTRIGDNF && BL[i].getAttribute('data-triggered')==RULE_TRIGGERED) {
            let dnf = ccTestFail(BL[i]);
			return SFSx(EntrantDNF,dnf);
        }
    }
	
	var TS = parseInt(document.getElementById('TotalPoints').value);
	var MP = parseInt(document.getElementById('MinPoints').value);
	if (TS < MP)  { /* Admin manual specifies this */
        let tfpx = DNF_TOOFEWPOINTS;
        let tfp = document.getElementById('DNF_TOOFEWPOINTS');
        if (tfp) tfpx = tfp.value;
		return SFSx(EntrantDNF,tfpx+' < '+MP);
    }
	
	let af = document.querySelector('#autoFinisher');
    if (af && af.value == 'true')
	    SFSx(EntrantFinisher,'');
	
}

function setManualStatus() {
/* This is called when some human being manually sets the entrant status using the dropdown select
 * We don't want to do anything apart from update the score explanation and enable save
 */
    let sxsfs = document.getElementById('sxsfs');
    sxsfs.innerHTML = getStatusText();
    let sx = document.getElementById('scorex');
    let sxv = document.getElementById('scorexText');
    sxv.value = sx.innerHTML;
    enableSaveButton();

}

function showCats(catcounts) {

    for (let i = 1; i <= CALC_AXIS_COUNT; i++) 
        for (let j = 0; j < catcounts[i]['catcounts'].length; j++) {
            let X = '';
            if (typeof(catcounts[i]['catcounts'][j]) !== 'undefined')
                X = catcounts[i]['catcounts'][j];
        let cn = document.getElementById('cat'+i+'_'+j);
        if (cn) 
            cn.innerText = X;
    }

}


function showRallytime(stamp) {
    /* We're really only interested in the time of day and which of a few days it's on */
    
    let weekdays = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
    let res = '';
    let dt = new Date(stamp);
    let rs = document.getElementById('RallyTimeStart').value.substring(0,10);
    let rf = document.getElementById('RallyTimeDNF').value.substring(0,10);
    if (rs !== rf) // Single day rally
        res += weekdays[dt.getUTCDay()]+' ';
    res += dt.getHours()+':'+dt.getMinutes();
    return '<span title="'+stamp+'">'+res+'</span>';

}

function htmlEncode(s) {
  var el = document.createElement("div");
  el.innerText = el.textContent = s;
  s = el.innerHTML;
  return s;
}
    
function writeScorex() {

    let sx = document.getElementById('scorex');
    let html = '<table class="sxtable">';
    html += '<caption>';
    html += getRiderNames();
    html += ' [&nbsp;<span id="sxsfs">'+getStatusText()+'</span>&nbsp;]';

	let distance = '';
	let cm = parseInt(document.getElementById('CorrectedMiles').value);
	if (cm > 0) 
		distance = distance + cm + ' ' + document.getElementById('bduText').value;
    html += '<br><span class="explain">'+distance+' ';
    /**
    let avgspeed = document.getElementById('AvgSpeed').value;
    if (avgspeed != '')
        html += '@ '+avgspeed;
    **/
    html += '</span>';
    html += '</caption><thead>';
    html += '<tr><th class="sxcode"></th><th class="sxdesc"></th>';
    html += '<th class="sxitempoints"></th>';
    html += '</tr></thead><tbody>';
    for (let sx of scorex) {
        html += sx.asHTML();
    }
    html += '</tbody></table>';
    sx.innerHTML = html;
    let sxv = document.getElementById('scorexText');
    sxv.value = html;
}

function zapCats() {

    for (let i = 1; i <= CALC_AXIS_COUNT; i++) {
        let tab = document.getElementById('cat'+i);
        if (!tab) continue;
        let cells = tab.getElementsByClassName('scoredetail');
        for (let j = 0; j < cells.length; j++) {
            cells[j].innerText = '';
        }
    }

}
