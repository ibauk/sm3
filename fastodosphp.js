
"use strict";

const MyStackItem = 'odoStack';
var timertick;

/**
 * clickTime called when the real time display is clicked.
 * 
 * The clock is either stopped for two minutes, or continued.
 * 
 */
function clickTime() {
    let timeDisplay = document.querySelector('#timenow');
    console.log('Clicking time');
    clearInterval(timertick);
    if (timeDisplay.getAttribute('data-paused') != 0) {
        timeDisplay.setAttribute('data-paused',0);
        timertick = setInterval(refreshTime,timeDisplay.getAttribute('data-refresh'));
        timeDisplay.classList.remove('held');
    } else {
        timeDisplay.setAttribute('data-paused',1);
        timertick = setInterval(clickTime,timeDisplay.getAttribute('data-pause'));
        timeDisplay.classList.add('held');
    }
    console.log('Time clicked');
}

/**
 * oi is called oninput to change state
 * 
 */
function oi(obj) {
    console.log("oi called");
    obj.setAttribute('data-saveneeded','1');
    obj.classList.add('oi');
}

function ob(obj) {
    console.log("ob called");
    oc(obj);
}
/**
 * oc is called onchange to change state
 * 
 */
function oc(obj) {
    console.log("oc called");
    if (obj.getAttribute('data-saveneeded') != '1') return;
    obj.setAttribute('data-saveneeded','0');
    obj.classList.remove('oi');
    obj.classList.add('oc');
    let tr = obj.parentNode.parentNode;
    let ent = tr.childNodes[0].innerText;
    let timeDisplay = document.querySelector('#timenow');
    let ts = timeDisplay.getAttribute('data-time');
    let url = "fastodos.php?c=setodo&e="+ent+'&f='+obj.name+'&v='+obj.value+'&t='+ts;
    let newTrans = {};
    newTrans.url = url;
    newTrans.obj = obj.id;
    newTrans.sent = false;
    const stackx = sessionStorage.getItem(MyStackItem);
    let stack = [];
    if (stackx != null) 
        stack = JSON.parse(stackx);
    stack.push(newTrans);
    sessionStorage.setItem('odoStack',JSON.stringify(stack));
}

/**
 * sendTransactions is called periodically to push transactions held in the stack to the backend server.
 * 
 */
function sendTransactions() {

    let stackx = sessionStorage.getItem(MyStackItem);
    if (stackx == null) return;

    let stack = JSON.parse(stackx);

    let N = stack.length;

    if (N < 1) return;

    for (let i = 0; i < N; i++) {
        
        if (stack[i].sent) continue;

        console.log(stack[i].url);
        let xhttp = new XMLHttpRequest();
        xhttp.onerror = function() {
            return; // Probably means the network's not available
        }
        xhttp.onload = function() {
            let ok = new RegExp("\\W*ok\\W*");
            if (xhttp.status == 200) {
                console.log('{'+this.responseText.substring(0,30)+'}');
                if (!ok.test(this.responseText)) {                
                    console.log(UPDATE_FAILED);
                    return;
                } else {
                    stack[i].sent = true;
                    sessionStorage.setItem('odoStack',JSON.stringify(stack));
                    document.getElementById(stack[i].obj).classList.replace('oc','ok');
                }
            }
        }
        xhttp.open("GET", stack[i].url, true);
        xhttp.send();
    }

}


/**
 * swapss is called when a different option radio button is chosen
 * 
 */
function swapss(rad) {
    console.log(rad.value);
    let frm = document.querySelector('#ssbuttons');
    let inps = frm.querySelectorAll('input');
    for (let i = 0; i < inps.length; i++) {
        inps[i].disabled = !inps[i].classList.contains(rad.value);
        inps[i].setAttribute('tabindex',inps[i].classList.contains(rad.value) ? "0" : "-1");
    }
    let hdr = document.querySelector('#sshdr');
    let labs = hdr.querySelectorAll('label');
    for (let i = 0; i < labs.length; i++) {
        labs[i].style.textTransform = labs[i].classList.contains(rad.value) ? "uppercase" : "lowercase";
    }
}

function t2(n) {
    if (n < 10)
        return '0'+n;
    return n;
}
function getRallyDateTime(D) {

    let yy = D.getFullYear();
    let mt = D.getMonth() + 1;
    let dy = D.getDate();
    let hh = D.getHours();
    let mm = D.getMinutes();
    return yy+'-'+t2(mt)+'-'+t2(dy)+'T'+t2(hh)+':'+t2(mm);
}
/**
 * refreshTime is called periodically to update the real time display
 * 
 */
function refreshTime() {
    let timeDisplay = document.querySelector('#timenow');
    let dt = new Date();
    timeDisplay.setAttribute('data-time', getRallyDateTime(dt));
    // Locale values should be settable in rallyparams.settings
    let dateString = dt.toLocaleString('en-GB',{weekday: "short",hour:"2-digit",minute:"2-digit",second:"2-digit"});
    let formattedString = dateString.replace(", ", " - ");
    timeDisplay.innerHTML = formattedString;
}

/**
 * I'm clearing the temporary storage, including on refresh.
 * 
 */
sessionStorage.removeItem(MyStackItem);
setInterval(sendTransactions,1000);
