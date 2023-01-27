/*
 * I B A U K   -   S C O R E M A S T E R
 *
 * custom.js
 *
 * I hold all translateable/customisable variables and settings
 *
 *
 * I am written for readability rather than efficiency, please keep me that way.
 *
 *
 * Copyright (c) 2020 Bob Stammers
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

 
const DNF_TOOFEWPOINTS = "points";
const DNF_TOOFEWMILES = "Not enough distance"; // not used i8
const DNF_TOOMANYMILES = "Too much distance"; // not used i8
const DNF_FINISHEDTOOLATE = "Finished too late"; // not used i8
const DNF_MISSEDCOMPULSORY = "&#8265; ";
const DNF_HITMUSTNOT = "&#8264; ";
const DNF_COMPOUNDRULE = "&#8853; ";
const DNF_SPEEDING = "Excessive speed"; // not used i8
const EntrantStatusDNF = "DNF";

// Elements of Score explanation, include trailing space, etc
const RPT_Tooltip	= "Click for explanation\rdoubleclick to print";
const RPT_Bonuses	= "Bonuses ticked";
const RPT_Specials	= "Specials";
const RPT_Combos	= "Combos";
const RPT_MPenalty	= "!! ";
const RPT_TPenalty	= "&#x23F0;";
const RPT_Total 	= "TOTAL";
const RPT_SPenalty	= "!! ";

const EBC_Flag2     = "Team rules";
const EBC_FlagA     = "Read the notes!";
const EBC_FlagB     = "Bike in photo";
const EBC_FlagD     = "Daylight only";
const EBC_FlagF     = "Face in photo";
const EBC_FlagN     = "Night only";
const EBC_FlagR     = "Restricted access";
const EBC_FlagT     = "Need a receipt (ticket)";

const CFGERR_MethodNIY = "Error: compoundCalcRuleMethod {0} not implemented yet";
const CFGERR_NotBonuses = "Error: compoundCalcRuleType {0} not applicable to bonuses";

const ASK_MINUTES = "Please enter the number of rest minutes for ";
const ASK_POINTS = "Please enter the points for";
const LOOKUP_ENTRANT = "Find entrant record matching what?";
const CLAIM_REJECTED = "!! ";
const FINISHERS_EXPORTED = "Finishers Exported!";
const ENTRANTS_EXPORTED = "Full entrant details exported!"

const OBSORTAZ = "Sort into Bonus id order";
const APPLYCLOSE = "Apply changes/close";

const UPDATE_FAILED = "UPDATE FAILED!";

const CANT_LOCK = "USER CONFLICT\n\nCannot acquire record lock - someone else is updating!";

const MY_LOCALE	= "en-GB";

// Regular expression for parsing subject line from emails
// In English this is rider, bonus, odo, time
// Odo and time are optional as are the commas
const EBC_SUBJECT_LINE = /(\d+)\,*\s*([a-z0-9\-]+)\,*\s*(\d+)?\.*\d*\s*\,*\s*(\d\d?[.:]*\d\d)?\s*(.*)/i;