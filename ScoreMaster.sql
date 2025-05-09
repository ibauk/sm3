/*
 * I B A U K   -   S C O R E M A S T E R
 *
 * I provide the initial database when building new rallies.
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

-- DBVERSION: 12

BEGIN TRANSACTION;

CREATE TABLE IF NOT EXISTS "rallyparams" (
	"RallyTitle"	TEXT,
	"RallySlogan"	TEXT,
	"MaxHours"	INTEGER NOT NULL DEFAULT 0,
	"StartTime"	TEXT,
	"FinishTime"	TEXT,
	"MinMiles"	INTEGER NOT NULL DEFAULT 0,
	"PenaltyMaxMiles"	INTEGER NOT NULL DEFAULT 0,
	"MaxMilesMethod"	INTEGER NOT NULL DEFAULT 0,
	"MaxMilesPoints"	INTEGER NOT NULL DEFAULT 0,
	"PenaltyMilesDNF"	INTEGER NOT NULL DEFAULT 0,
	"MinPoints"	INTEGER NOT NULL DEFAULT 0,
	"ScoringMethod"	INTEGER NOT NULL DEFAULT 3,
	"ShowMultipliers"	INTEGER NOT NULL DEFAULT 2,
	"TiedPointsRanking"	INTEGER NOT NULL DEFAULT 1,
	"TeamRanking"	INTEGER NOT NULL DEFAULT 3,
	"OdoCheckMiles"	NUMERIC DEFAULT 0,
	"Cat1Label"	TEXT,
	"Cat2Label"	TEXT,
	"Cat3Label"	TEXT,
	"Cat4Label"	TEXT,
	"Cat5Label"	TEXT,
	"Cat6Label"	TEXT,
	"Cat7Label"	TEXT,
	"Cat8Label"	TEXT,
	"Cat9Label"	TEXT,
	"RejectReasons"	TEXT,							/* Defaults entered in INSERT below */
	"DBState" INTEGER NOT NULL DEFAULT 0,
	"DBVersion" INTEGER NOT NULL DEFAULT 12, 		/* DBVERSION */
	"AutoRank" INTEGER NOT NULL DEFAULT 1,
	"Theme" TEXT NOT NULL DEFAULT 'default',
	"MilesKms" INTEGER NOT NULL DEFAULT 0,
	"LocalTZ" TEXT NOT NULL DEFAULT 'Europe/London',
	"DecimalComma" INTEGER NOT NULL DEFAULT 0,
	"HostCountry" TEXT NOT NULL DEFAULT 'UK',
	"Locale" TEXT NOT NULL DEFAULT 'en-GB',
	"EmailParams" TEXT,								/* Defaults entered in INSERT below */
	"isvirtual"	INTEGER NOT NULL DEFAULT 0,
	"tankrange"	INTEGER NOT NULL DEFAULT 200,
	"refuelstops"	TEXT,
	"stopmins"	INTEGER NOT NULL DEFAULT 10,
	"spbonus"	TEXT,
	"fpbonus"	TEXT,
	"mpbonus"	TEXT,
	"settings"	TEXT,								/* Defaults entered in INSERT below */
	"StartOption" INTEGER NOT NULL DEFAULT 0,
	"ebcsettings" TEXT,
	"CurrentLeg" INTEGER NOT NULL DEFAULT 1,
	"NumLegs" INTEGER NOT NULL DEFAULT 1,
	"LegData" TEXT
);

DELETE FROM "rallyparams";

INSERT INTO "rallyparams" (RallyTitle,RallySlogan,RejectReasons,EmailParams,settings,ebcsettings) VALUES ('IBA rally','Fun with motorcycles',
'1=No photo
2=Wrong/unclear photo
3=Out of hours
4=Face not in photo
5=Bike not in photo
6=Flag not in photo
7=Missing rider/pillion
8=Missing receipt
9=Claim excluded',
'{
    "SMTPAuth": "TRUE",
    "SMTPSecure": "tls",
    "Port": 587,
    "Host": "smtp.gmail.com",
    "Username": "ibaukebc@gmail.com",
    "Password": "",
	"Encoding": "quoted-printable",
	"CharSet": "UTF-8",
    "SetFrom": [
        "ibaukebc@gmail.com",
        "The Rally Team"
    ]
}',
'{
	"claimsShowPost": "false",
	"claimsAutopostAll": "true",
	"claimsReloadEBC": "30",
	"singleuserMode": "false",
	"autoAdjustBonusWidth": "true",
	"useBonusQuestions": "false",
	"valBonusQuestions": "50",
	"clgHeader": "Schedule of claims received", 
	"clgClaimsCount": "Number of claims received", 
	"clgBonusCount": "Number of bonuses claimed",
	"RPT_TPenalty": "&#x23F0;Late arrival penalty",
	"RPT_MPenalty": "Excess distance penalty",	
	"RPT_SPenalty": "Excess speed penalty",
	"usePercentPenalty": "false",
	"valPercentPenalty": "10",
	"autoFinisher": "false",
	"autoLateDNF": "false",
	"showPicklistStatus": "true",
	"multStepValue": "1",
	"rankPointsPerMile": "false",
	"decimalsPPM": "1",
	"restBonusStartGroup": "RBStart",
	"restBonusGroups": "RBClaims",
	"bonusReclaims": "0",
	"bonusReclaimNG": "Bonus claimed earlier, reclaim out of sequence",
	"ignoreClaimDecisionCode": "9",
	"missingPhotoDecisionCode": "1",
	"bonusClaimsLimit": "0",
	"bonusClaimsExceeded": "Claim limit exceeded",
	"distanceLimitExceeded": "Distance limit exceeded",
	"maxOdoGap": "1000"
}','imapserver: imap.gmail.com:993

login: ibaukebc@gmail.com

# If password is blank, EBC fetching will be suppressed
password:

# If true, only process emails sent from entrant''s registered address
# For general testing purposes you probably want to leave this false so that you can use your own email address to send test claims.
# You should turn this on for the actual rally though as it will prevent scores being affected by mistaken claims.
matchemail: false


# If this flag is true, EBC fetching will be suppressed
dontrun: false


# Executable to convert HEIC image files to JPG
# The arguments are expected to be:- filename.HEIC filename.JPG
# Will be called at BOJ with no arguments to validate installation
# This uses the ImageMagick package which must be installed on the server
heic2jpg: magick

convertheic2jpg: true




# Sleep this long between mailbox inspections
sleepseconds: 10

# Don''t fetch emails older (imap.internaldate) than this date
notbefore: 2021-07-01

# Fetch emails without any of these flags
selectflags: ["\\Flagged", "\\Seen"]


# Acceptable subject line RE. This accepts decorated entrant numbers, various time formats. Must have four fields.
subject: ''(\d+)[\s\.,]+([\w\-]+\-?)[\s\.,]+(\d+)[\s\.,]+([\d:\.\-\+TZ]+)[\s\.,]*(.*)''

# Subject line RE to measure strict adherence to standard
strict: ''^\s*(\d+)\s+([a-zA-Z0-9\-]+)\s+(\d+)\s+(\d\d\d\d)''

checkstrict: true

allowbody: true

# Filesystem path to ScoreMaster folder
path2sm: sm

# Path from ScoreMaster folder to EBC image folder
imagefolder: ebcimg

################ Testmode settings below here

# testmode = true = respond to emails with analysis; false = pass emails to ScoreMaster
testmode: false

# Setting this to true turns on debugging level log entries
verbose: false

# Literals used in testmode response emails

TestResponseSubject: EBC test result / Testergebnis
TestResponseGood: Good bonus claim received / Guter Bonusanspruch erhalten
TestResponseBad: Bad bonus claim received / Ungültiger Bonusanspruch erhalten
TestResponseGoodEmail: 🇬🇧 In the rally you MUST use your registered email address.<br>🇩🇪 Während der Rally MÜSSEN Sie Ihre registrierte E-Mail-Adresse verwenden 
TestResponseBadEmail: 🇬🇧 Ok for testing but in the rally you MUST use your registered email address.<br>🇩🇪 Zum Testen okay, aber bei der Rally MÜSSEN Sie Ihre registrierte E-Mail-Adresse verwenden
TestResponseAdvice: |
  <p>🇬🇧 Good/bad here means that the email did/did not contain a
  valid bonus claim. It does not mean that the claim will succeed (maybe the
  photo is the wrong one) or fail, it just means that it will be processed
  correctly.<br>🇩🇪 Gut/schlecht bedeutet hier, dass die E-Mail einen gültigen Bonusanspruch enthielt/enthielt. Dies bedeutet nicht, dass der Anspruch erfolgreich ist (vielleicht ist das Foto das falsche) oder fehlschlägt, es bedeutet nur, dass er korrekt verarbeitet wird.</p>

  <p>🇬🇧 You are receiving this because we''re operating in test mode. YOU WILL NOT RECEIVE ANY RESPONSES DURING THE RALLY.<br>🇩🇪 Sie erhalten diese, weil wir uns im Testmodus befinden. WÄHREND DER RALLYE ERHALTEN SIE KEINE ANTWORTEN.</p>

TestModeLiteral: TEST MODE


# Can be used to expressly permit more than 1 photo
# MaxExtraPhotos: 1

TestResponseBCC:


');

CREATE TABLE IF NOT EXISTS "cohorts" (
	"Cohort"	INTEGER NOT NULL,
	"FixedStart"	INTEGER NOT NULL DEFAULT 1,
	"StartTime"	TEXT,
	PRIMARY KEY("Cohort")
);

CREATE TABLE IF NOT EXISTS "teams" (
	"TeamID"	INTEGER NOT NULL,
	"BriefDesc"	TEXT,
	PRIMARY KEY("TeamID")
);

CREATE TABLE IF NOT EXISTS "ebclaims" (
	"LoggedAt"	TEXT NOT NULL,
	"AttachmentTime"	TEXT,
	"DateTime"	TEXT NOT NULL,
	"FirstTime"	TEXT,
	"FinalTime"	TEXT NOT NULL,
	"EntrantID"	INTEGER NOT NULL,
	"BonusID"	TEXT NOT NULL,
	"OdoReading"	INTEGER NOT NULL,
	"ClaimTime"	TEXT NOT NULL,
	"ClaimHH"	INTEGER NOT NULL DEFAULT 0,
	"ClaimMM"	INTEGER NOT NULL DEFAULT 0,
	"Decision"	INTEGER NOT NULL DEFAULT -1,
	"Points"	INTEGER NOT NULL DEFAULT 0,
	"EmailID"	INTEGER NOT NULL,
	"Subject"	TEXT NOT NULL,
	"ExtraField"	TEXT NOT NULL,
	"StrictOk"	INTEGER NOT NULL,
	"Processed"	INTEGER NOT NULL DEFAULT 0,
	"PhotoID"	INTEGER,
	"RestMinutes"	INTEGER NOT NULL DEFAULT 0,
	"AskPoints"		INTEGER NOT NULL DEFAULT 0,
	"AskMinutes"	INTEGER NOT NULL DEFAULT 0,
	"Leg"		INTEGER NOT NULL DEFAULT 1
);

CREATE TABLE IF NOT EXISTS "ebcphotos" (
	"EntrantID"	INTEGER NOT NULL,
	"BonusID"	TEXT NOT NULL,
	"EmailID"	INTEGER NOT NULL,
	"Image"	TEXT
);

CREATE TABLE IF NOT EXISTS "emailq" (
	"EntrantID"	INTEGER NOT NULL,
	"TemplateID"	INTEGER NOT NULL DEFAULT 0,
	"EmailSent"	INTEGER NOT NULL DEFAULT 0,
	"SentAt"	TEXT
);

CREATE TABLE IF NOT EXISTS "emailtemplates" (
	"TemplateID"	INTEGER NOT NULL,
	"EmailSubject"	TEXT,
	"EmailBody"	TEXT,
	"EmailSignature"	TEXT,
	"IncludeScorex"	INTEGER NOT NULL DEFAULT 0,
	"IncludeCertificate"	INTEGER NOT NULL DEFAULT 0,
	"AttachFiles"	TEXT,
	"AttachNames"	TEXT,
	"WhereSQL"	TEXT,
	PRIMARY KEY("TemplateID")
);

CREATE TABLE IF NOT EXISTS "functions" (
	"functionid"	INTEGER,
	"menulbl"	TEXT,
	"url"	TEXT,
	"onclick"	TEXT,
	"Tags"	TEXT,
	PRIMARY KEY("functionid")
);


CREATE TABLE IF NOT EXISTS "menus" (
	"menuid"	TEXT,
	"menulbl"	TEXT,
	"menufuncs"	TEXT,
	PRIMARY KEY("menuid")
);


CREATE TABLE IF NOT EXISTS "certificates" (
	"EntrantID"	INTEGER NOT NULL DEFAULT 0,
	"css"	TEXT,
	"html"	TEXT,
	"options"	TEXT,
	"image"	TEXT,
	"Class"	INTEGER NOT NULL DEFAULT 0,
	"Title"	TEXT,
	PRIMARY KEY("EntrantID","Class")
);


CREATE TABLE IF NOT EXISTS "timepenalties" (
	"TimeSpec"	INTEGER NOT NULL DEFAULT 2,
	"PenaltyStart"	TEXT,
	"PenaltyFinish"	TEXT,
	"PenaltyMethod"	INTEGER NOT NULL DEFAULT 0,
	"PenaltyFactor"	INTEGER NOT NULL DEFAULT 0,
	"Leg" INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS "sgroups" (
	"GroupName"	TEXT NOT NULL,
	"GroupType"	TEXT DEFAULT 'C',
	PRIMARY KEY("GroupName")
);
CREATE TABLE IF NOT EXISTS "entrants" (
	"EntrantID"	INTEGER,
	"Bike"	TEXT,
	"BikeReg"	TEXT,
	"RiderName"	TEXT,
	"RiderFirst"	TEXT,
	"RiderIBA"	INTEGER,
	"PillionName"	TEXT,
	"PillionFirst"	TEXT,
	"PillionIBA"	INTEGER,
	"TeamID"	INTEGER NOT NULL DEFAULT 0,
	"Country"	TEXT DEFAULT 'UK',
	"OdoKms"	INTEGER NOT NULL DEFAULT 0,
	"OdoCheckStart"	NUMERIC,
	"OdoCheckFinish"	NUMERIC,
	"OdoCheckTrip"	NUMERIC,
	"OdoScaleFactor"	NUMERIC DEFAULT 1,
	"OdoRallyStart"	NUMERIC,
	"OdoRallyFinish"	NUMERIC,
	"CorrectedMiles"	NUMERIC DEFAULT 0,
	"FinishTime"	TEXT,
	"BonusesVisited"	TEXT,
	"CombosTicked"	TEXT,
	"TotalPoints"	INTEGER NOT NULL DEFAULT 0,
	"StartTime"	TEXT,
	"FinishPosition"	INTEGER NOT NULL DEFAULT 0,
	"EntrantStatus"	INTEGER NOT NULL DEFAULT 0,
	"ScoringNow"	INTEGER NOT NULL DEFAULT 0,
	"ScoredBy"	TEXT,
	"ExtraData"	TEXT,
	"Class"	INTEGER NOT NULL DEFAULT 0,
	"ScoreX"	TEXT,
	"RejectedClaims"	TEXT,
	"Phone" TEXT,
	"Email" TEXT,
	"NoKName" TEXT,
	"NoKRelation" TEXT,
	"NoKPhone" TEXT,
	"BCMethod" INTEGER NOT NULL DEFAULT 1,
	"RestMinutes" INTEGER NOT NULL DEFAULT 0,
	"Confirmed" INTEGER NOT NULL DEFAULT 0,
	"AvgSpeed" TEXT,
	"Cohort" INTEGER NOT NULL DEFAULT 0,
	"ReviewedByTeam" INTEGER NOT NULL DEFAULT 0,
	"AcceptedByEntrant" INTEGER NOT NULL DEFAULT 0,
	"LastReviewed" 	TEXT,
	"LegData"	TEXT,
	PRIMARY KEY("EntrantID")
);


CREATE TABLE IF NOT EXISTS "combinations" (
	"ComboID"	TEXT,
	"BriefDesc"	TEXT,
	"ScoreMethod"	INTEGER NOT NULL DEFAULT 0,
	"MinimumTicks"	INTEGER NOT NULL DEFAULT 0,
	"ScorePoints"	TEXT DEFAULT 0,
	"Bonuses"	TEXT,
	"Cat1"	INTEGER NOT NULL DEFAULT 0,
	"Cat2"	INTEGER NOT NULL DEFAULT 0,
	"Cat3"	INTEGER NOT NULL DEFAULT 0,
	"Cat4"	INTEGER NOT NULL DEFAULT 0,
	"Cat5"	INTEGER NOT NULL DEFAULT 0,
	"Cat6"	INTEGER NOT NULL DEFAULT 0,
	"Cat7"	INTEGER NOT NULL DEFAULT 0,
	"Cat8"	INTEGER NOT NULL DEFAULT 0,
	"Cat9"	INTEGER NOT NULL DEFAULT 0,
	"Compulsory"	INTEGER NOT NULL DEFAULT 0,
	"Leg" INTEGER NOT NULL DEFAULT 0,
	PRIMARY KEY("ComboID")
);
CREATE TABLE IF NOT EXISTS "claims" (
	"LoggedAt"	TEXT,
	"ClaimTime"	TEXT,
	"BCMethod"	INTEGER NOT NULL DEFAULT 0,
	"EntrantID"	INTEGER,
	"BonusID"	TEXT,
	"OdoReading"	INTEGER,
	"Decision"	INTEGER NOT NULL DEFAULT 0,
	"Applied"	INTEGER NOT NULL DEFAULT 0,
	"NextTimeMins"	INTEGER NOT NULL DEFAULT 0,
	"FuelBalance"	INTEGER NOT NULL DEFAULT 0,
	"SpeedPenalty"	INTEGER NOT NULL DEFAULT 0,
	"FuelPenalty"	INTEGER NOT NULL DEFAULT 0,
	"MagicPenalty"	INTEGER NOT NULL DEFAULT 0,
	"MagicWord"		TEXT,
	"Photo"			TEXT,
	"Points"		INTEGER NOT NULL DEFAULT 0,
	"RestMinutes"	INTEGER NOT NULL DEFAULT 0,
	"AskPoints"		INTEGER NOT NULL DEFAULT 0,
	"AskMinutes"	INTEGER NOT NULL DEFAULT 0,
	"QuestionAsked"	INTEGER NOT NULL DEFAULT 0,
	"QuestionAnswered"	INTEGER NOT NULL DEFAULT 0,
	"AnswerSupplied" TEXT,
	"JudgesNotes"	 TEXT,
	"PercentPenalty" INTEGER NOT NULL DEFAULT 0,
	"Evidence"		 TEXT,
	"Leg"            INTEGER NOT NULL DEFAULT 1
);
CREATE TABLE IF NOT EXISTS "categories" (
	"Axis"	INTEGER NOT NULL DEFAULT 1,
	"Cat"	INTEGER,
	"BriefDesc"	TEXT,
	PRIMARY KEY("Axis","Cat")
);
CREATE TABLE IF NOT EXISTS "catcompound" (
	"Axis"	INTEGER NOT NULL DEFAULT 1,
	"Cat"	INTEGER,
	"NMethod"	INTEGER NOT NULL DEFAULT -1,
	"ModBonus"	INTEGER NOT NULL DEFAULT 0,
	"NMin"	INTEGER NOT NULL DEFAULT 1,
	"PointsMults"	INTEGER NOT NULL DEFAULT 0,
	"NPower"	INTEGER NOT NULL DEFAULT 2,
	"Ruletype" INTEGER NOT NULL DEFAULT 0,
	"Leg" INTEGER NOT NULL DEFAULT 0
);
CREATE TABLE IF NOT EXISTS "bonuses" (
	"BonusID"	TEXT,
	"BriefDesc"	TEXT,
	"Points"	INTEGER NOT NULL DEFAULT 1,
	"Cat1"	INTEGER NOT NULL DEFAULT 0,
	"Cat2"	INTEGER NOT NULL DEFAULT 0,
	"Cat3"	INTEGER NOT NULL DEFAULT 0,
	"Cat4"	INTEGER NOT NULL DEFAULT 0,
	"Cat5"	INTEGER NOT NULL DEFAULT 0,
	"Cat6"	INTEGER NOT NULL DEFAULT 0,
	"Cat7"	INTEGER NOT NULL DEFAULT 0,
	"Cat8"	INTEGER NOT NULL DEFAULT 0,
	"Cat9"	INTEGER NOT NULL DEFAULT 0,
	"Compulsory"	INTEGER NOT NULL DEFAULT 0,
	"Notes" TEXT,
	"Flags" TEXT,
	"AskPoints" INTEGER NOT NULL DEFAULT 0,
	"RestMinutes" INTEGER NOT NULL DEFAULT 0,
	"AskMinutes" INTEGER NOT NULL DEFAULT 0,
	"GroupName" TEXT,
	"Image" TEXT,
	"Coords" TEXT,
	"Waffle" TEXT,
	"Question" TEXT,
	"Answer" TEXT,
	"Leg" INTEGER NOT NULL DEFAULT 0,
	PRIMARY KEY("BonusID")
);

CREATE TABLE IF NOT EXISTS "magicwords" (
	"asfrom"	TEXT NOT NULL,
	"magic"	TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS "speedpenalties" (
	"Basis"	INTEGER NOT NULL DEFAULT 0,
	"MinSpeed"	INTEGER NOT NULL,
	"PenaltyType"	INTEGER NOT NULL DEFAULT 0,
	"PenaltyPoints"	INTEGER DEFAULT 0,
	"Leg" INTEGER NOT NULL DEFAULT 0
);


CREATE TABLE IF NOT EXISTS "importspecs" (
	"specid"	TEXT NOT NULL,
	"specTitle"	TEXT,
	"importType"	INTEGER NOT NULL DEFAULT 0,
	"fieldSpecs"	TEXT,
	PRIMARY KEY("specid")
);

CREATE TABLE IF NOT EXISTS "themes" (
	"Theme"	TEXT NOT NULL,
	"css"	TEXT NOT NULL,
	PRIMARY KEY("Theme")
);


CREATE TABLE IF NOT EXISTS "classes" (
	"Class"	INTEGER NOT NULL,
	"BriefDesc"	TEXT NOT NULL,
	"AutoAssign" INTEGER NOT NULL DEFAULT 1,
	"MinPoints" INTEGER NOT NULL DEFAULT 0,
	"MinBonuses" INTEGER NOT NULL DEFAULT 0,
	"BonusesReqd" TEXT,
	"LowestRank" INTEGER NOT NULL DEFAULT 0,
	PRIMARY KEY("Class")
);


CREATE TABLE IF NOT EXISTS "legs" (
	"Leg"	INTEGER NOT NULL,
	"LegStartTime"	TEXT NOT NULL,
	"LegFinishTime"	TEXT NOT NULL,
	"LegMaxHours"	INTEGER NOT NULL DEFAULT 0,
	"LegMinMiles"	INTEGER NOT NULL DEFAULT 0,
	"LegPenaltyMaxMiles"	INTEGER NOT NULL DEFAULT 0,
	"LegPenaltyMaxMilesMethod"	INTEGER NOT NULL DEFAULT 0,
	"LegPenaltyMaxMilesPoints"	INTEGER NOT NULL DEFAULT 0,
	"LegPenaltyMilesDNF"	INTEGER NOT NULL DEFAULT 0,
	"LegMinPoints"	INTEGER NOT NULL DEFAULT 0,
	PRIMARY KEY("Leg")
);


DELETE FROM "functions";
DELETE FROM "menus";

DELETE FROM "certificates";
DELETE FROM "speedpenalties";
DELETE FROM "timepenalties";
DELETE FROM "sgroups";
DELETE FROM "entrants";
DELETE FROM "combinations";
DELETE FROM "claims";
DELETE FROM "categories";
DELETE FROM "catcompound";
DELETE FROM "bonuses";
DELETE FROM "importspecs";
DELETE FROM "themes";
DELETE FROM "magicwords";
DELETE FROM "classes";

-- INSERT INTO "sgroups" (GroupName,GroupType) VALUES('RBStart','C');
-- INSERT INTO "sgroups" (GroupName,GroupType) VALUES('RBClaims','C');
INSERT INTO "classes" (Class,BriefDesc,AutoAssign) VALUES(0,'Default',0);
INSERT INTO "teams" (TeamID,BriefDesc) VALUES(0,'No team');
INSERT INTO "cohorts" (Cohort,FixedStart) VALUES(0,0);

INSERT INTO "functions" (functionid,menulbl,url,onclick,Tags) VALUES (1,'AdmEntrantChecks','entrants.php?c=entrants&amp;ord=EntrantID&amp;mode=check',NULL,'entrant,check-in/check-out');
INSERT INTO "functions" (functionid,menulbl,url,onclick,Tags) VALUES (2,'AdmDoScoring','picklist.php',NULL,'entrant,score,scorex');
INSERT INTO "functions" (functionid,menulbl,url,onclick,Tags) VALUES (3,'AdmRankEntries','admin.php?c=rank',NULL,'entrant,rank,finisher');
INSERT INTO "functions" (functionid,menulbl,url,onclick,Tags) VALUES (4,'AdmPrintCerts','certificate.php?c=showcerts','window.open(''certificate.php?c=showcerts'',''certificates'');return false;','entrant,rank,finisher,certificate');
INSERT INTO "functions" (functionid,menulbl,url,onclick,Tags) VALUES (5,'AdmShowSetup','admin.php?menu=setup',NULL,'setup');
INSERT INTO "functions" (functionid,menulbl,url,onclick,Tags) VALUES (6,'AdmExportFinishers','exportxls.php?c=expfinishers','this.firstChild.innerHTML=FINISHERS_EXPORTED;','entrant,finisher,export');
INSERT INTO "functions" (functionid,menulbl,url,onclick,Tags) VALUES (7,'AdmBonusTable','bonuses.php?c=bonuses',NULL,'bonus');
INSERT INTO "functions" (functionid,menulbl,url,onclick,Tags) VALUES (9,'AdmSGroups','sm.php?c=sgroups',NULL,'bonus,special,group,penalty');
INSERT INTO "functions" (functionid,menulbl,url,onclick,Tags) VALUES (10,'AdmCombosTable','combos.php?c=list',NULL,'bonus,combo/combination');
INSERT INTO "functions" (functionid,menulbl,url,onclick,Tags) VALUES (11,'AdmEntrants','entrants.php?c=entrants&amp;ord=EntrantID&amp;mode=full',NULL,'entrant');
INSERT INTO "functions" (functionid,menulbl,url,onclick,Tags) VALUES (12,'AdmNewEntrant','entrants.php?c=newentrant',NULL,'entrant');
INSERT INTO "functions" (functionid,menulbl,url,onclick,Tags) VALUES (13,'AdmDoBlank','score.php?c=blank&prf=1',NULL,'score,blank score sheet');
INSERT INTO "functions" (functionid,menulbl,url,onclick,Tags) VALUES (14,'AdmRankEntries','admin.php?c=rank',NULL,'entrant,rank,finisher');
INSERT INTO "functions" (functionid,menulbl,url,onclick,Tags) VALUES (15,'AdmImportEntrants','importxls.php?showupload&type=0',NULL,'entrant,import');
INSERT INTO "functions" (functionid,menulbl,url,onclick,Tags) VALUES (16,'AdmRallyParams','sm.php?c=rallyparams',NULL,'params,rally');
INSERT INTO "functions" (functionid,menulbl,url,onclick,Tags) VALUES (17,'AdmEditCert','certedit.php',NULL,'entrant,certificate');
INSERT INTO "functions" (functionid,menulbl,url,onclick,Tags) VALUES (18,'AdmEntrantsHeader','admin.php?menu=entrant',NULL,'entrant');
INSERT INTO "functions" (functionid,menulbl,url,onclick,Tags) VALUES (19,'AdmBonusHeader','admin.php?menu=bonus',NULL,'bonus');
INSERT INTO "functions" (functionid,menulbl,url,onclick,Tags) VALUES (20,'AdmTimePenalties','timep.php?c=timep',NULL,'params,time,penalty');
INSERT INTO "functions" (functionid,menulbl,url,onclick,Tags) VALUES (21,'AdmCatTable','cats.php?c=axes',NULL,'params,category,compound');
INSERT INTO "functions" (functionid,menulbl,url,onclick,Tags) VALUES (22,'AdmCompoundCalcs','cats.php?c=catcalcs',NULL,'params,category,compound');
INSERT INTO "functions" (functionid,menulbl,url,onclick,Tags) VALUES (23,'AdmSetupWiz','setup.php',NULL,'params,category,compound');
INSERT INTO "functions" (functionid,menulbl,url,onclick,Tags) VALUES (24,'AdmPrintScoreX','entrants.php?c=scorex','window.open(''entrants.php?c=scorex'',''scorex'');return false;','entrant,score,finisher,scorex');
INSERT INTO "functions" (functionid,menulbl,url,onclick,Tags) VALUES (25,'AdmPrintQlist','entrants.php?c=qlist','window.open(''entrants.php?c=qlist'',''qlist'');return false;','entrant,rank,finisher');
INSERT INTO "functions" (functionid,menulbl,url,onclick,tags) VALUES (26,'UtlFolderMaker','utils.php','window.open(''utils.php'',''utils'');return false;','entrant,bonus,folder,directory,script');
INSERT INTO "functions" (functionid,menulbl,url,onclick,tags) VALUES (27,'UtlDeleteEntrant','entrants.php?c=delentrant',NULL,'entrant,delete');
INSERT INTO "functions" (functionid,menulbl,url,onclick,tags) VALUES (28,'UtlRenumEntrant','entrants.php?c=moveentrant',NULL,'entrant,renumber entrant,entrant number,number');
INSERT INTO "functions" (functionid,menulbl,url,onclick,tags) VALUES (29,'UtlRAE','entrants.php?c=showrae',NULL,'entrant,renumber all entrants,entrant number,number');
INSERT INTO "functions" (functionid,menulbl,url,onclick,Tags) VALUES (30,'AdmUtilHeader','admin.php?menu=util',NULL,'utilities,renumber,magic,teams,export');
INSERT INTO "functions" (functionid,menulbl,url,onclick,Tags) VALUES (31,'UtlFindEntrant','#','return findEntrant();','entrant,find');
INSERT INTO "functions" (functionid,menulbl,url,onclick,Tags) VALUES (32,'AdmDoBlankB4','score.php?c=blank&prf=0',NULL,'score,blank score sheet');
INSERT INTO "functions" (functionid,menulbl,url,onclick,Tags) VALUES (33,'ttTeams','teams.php?m=5&g=0',NULL,'teams,integrity');
INSERT INTO "functions" (functionid,menulbl,url,onclick,Tags) VALUES (34,'AdmSpeedPenalties','speeding.php',NULL,'speeding,penalties');
INSERT INTO "functions" (functionid,menulbl,url,onclick,Tags) VALUES (35,'AdmThemes','admin.php?c=themes',NULL,'colours,themes');
INSERT INTO "functions" (functionid,menulbl,url,onclick,Tags) VALUES (36,'AdmConfirm','score.php?mc=mc',NULL,'confirm,reconcile');
INSERT INTO "functions" (functionid,menulbl,url,onclick,Tags) VALUES (37,'AdmClaims','claims.php',NULL,'claims,reconcile');
INSERT INTO "functions" (functionid,menulbl,url,onclick,Tags) VALUES (38,'AdmApplyClaims','claims.php?c=applyclaims',NULL,'claims');
INSERT INTO "functions" (functionid,menulbl,url,onclick,Tags) VALUES (39,'AdmImportBonuses','importxls.php?showupload&type=1',NULL,'bonus,import');
INSERT INTO "functions" (functionid,menulbl,url,onclick,Tags) VALUES (40,'AdmMagicWords','claims.php?c=magic',NULL,'magic,virtual,word');
INSERT INTO "functions" (functionid,menulbl,url,onclick,Tags) VALUES (41,'AdmSendEmail','emails.php',NULL,'email,scorex,certificate');
INSERT INTO "functions" (functionid,menulbl,url,onclick,Tags) VALUES (42,'AdmShowAdvanced','admin.php?menu=advanced',NULL,'advanced,setup');
INSERT INTO "functions" (functionid,menulbl,url,onclick,Tags) VALUES (43,'AdmRallyParams','sm.php?c=rallyparams&adv',NULL,'params,rally,advanced');
INSERT INTO "functions" (functionid,menulbl,url,onclick,Tags) VALUES (44,'AdmClasses','classes.php?c=classes',NULL,'params,class,advanced');
INSERT INTO "functions" (functionid,menulbl,url,onclick,Tags) VALUES (45,'AdmExportEntrants','exportxls.php?c=expentrants','this.firstChild.innerHTML=ENTRANTS_EXPORTED;','entrant,all,export');
INSERT INTO "functions" (functionid,menulbl,url,onclick,Tags) VALUES (46,'AdmEBClaims','claimslog.php?ebc',NULL,'claims,ebc,email');
INSERT INTO "functions" (functionid,menulbl,url,onclick,Tags) VALUES (47,'AdmTeamsTable','entrants.php?c=teams',NULL,'entrant,team');
INSERT INTO "functions" (functionid,menulbl,url,onclick,Tags) VALUES (48,'AdmCohortTable','cohorts.php?c=cohorts',NULL,'entrant,cohort');
INSERT INTO "functions" (functionid,menulbl,url,onclick,Tags) VALUES (49,'AdmImportCombos','importxls.php?showupload&type=2',NULL,'bonus,combo,import');
INSERT INTO "functions" (functionid,menulbl,url,onclick,Tags) VALUES (50,'AdmRebuildScorecards','claims.php?c=applyclaims&reprocess=1',NULL,'claims,scorecards');
INSERT INTO "functions" (functionid,menulbl,url,onclick,Tags) VALUES (51,'AdmOdoReadings','fastodos.php',NULL,'entrant,odo');
INSERT INTO "functions" (functionid,menulbl,url,onclick,Tags) VALUES (52,'AdmOdoChecks','fastodos.php?odocheck',NULL,'entrant,odo');
INSERT INTO "functions" (functionid,menulbl,url,onclick,Tags) VALUES (53,'AdmOdoReadingsCO','fastodos.php?co',NULL,'entrant,odo');
INSERT INTO "functions" (functionid,menulbl,url,onclick,Tags) VALUES (54,'AdmOdoReadingsCI','fastodos.php?ci',NULL,'entrant,odo');
INSERT INTO "functions" (functionid,menulbl,url,onclick,Tags) VALUES (55,'AdmDoReviewing','picklist.php?review',NULL,'entrant,score,scorex');
INSERT INTO "functions" (functionid,menulbl,url,onclick,Tags) VALUES (56,'AdmPrintScorex','scorex.php','window.open(''scorex.php'',''ScoreX'');return false;','entrant,score,scorex');
INSERT INTO "functions" (functionid,menulbl,url,onclick,Tags) VALUES (57,'RptBonusAnal','reports.php?ba','window.open(''reports.php?ba'',''reports'');return false;','bonus');
INSERT INTO "functions" (functionid,menulbl,url,onclick,Tags) VALUES (58,'RptComboAnal','reports.php?ca','window.open(''reports.php?ca'',''reports'');return false;','combo');
INSERT INTO "functions" (functionid,menulbl,url,onclick,Tags) VALUES (59,'RptClaimsAnal','claims.php?aclaims',NULL,'reports,claims');
INSERT INTO "menus" (menuid,menulbl,menufuncs) VALUES ('admin','AdmMenuHeader','37,46,55,4,56,25,6,5');
INSERT INTO "menus" (menuid,menulbl,menufuncs) VALUES ('setup','AdmSetupHeader','16,17,18,19,20,42');
INSERT INTO "menus" (menuid,menulbl,menufuncs) VALUES ('entrant','AdmEntrantsHeader','11,53,54,41,47,48,52,15');
INSERT INTO "menus" (menuid,menulbl,menufuncs) VALUES ('bonus','AdmBonusHeader','7,10,9,39,49');
INSERT INTO "menus" (menuid,menulbl,menufuncs) VALUES ('util','AdmUtilHeader','29,28,27,32,13,33,35,40,45,50');
INSERT INTO "menus" (menuid,menulbl,menufuncs) VALUES ('advanced','AdmAdvancedHeader','2,21,22,44,34,23,57,58,59,30');





INSERT INTO `certificates` (EntrantID,css,html,options,image,Class,Title) VALUES (0,'','<div id="topimagefiller" style="text-align: center;"><img src="data:image/png;base64,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" style="width: 380px; height: 247px;"></div>
<p style="text-align: center;"><br></p><p style="text-align: center;"><br></p><p style="text-align: center;"><span style="font-size: 24px;">#RallyTitle#</span></p><p style="text-align: center;"><span><em>#RallySlogan#</em></span></p><p style="text-align: center;"><br></p><h1 class="CrewName" style="text-align: center;">#CrewName#</h1>
<p style="text-align: center;"><em><span style="font-size: 24px;">#TeamName#</span></em><br></p><p style="text-align: center;"><br></p><h2 class="FinishPosition" style="text-align: center;">#FinishPosition# place</h2>
<h3 style="text-align: center;"><span class="TotalPoints">#TotalPoints#</span> Points | #CorrectedMiles# Miles</h3>
<p><br></p><p><br></p><p class="main" style="text-align: center;">Congratulations on your outstanding performance in the <br><br><span>#RallyTitle#</span></p><p style="text-align: center;"><br></p><p id="signature" style="text-align: center;">________________________<br>Donald Trump, Rallymaster</p>

',NULL,NULL,0,'Rally finisher');

INSERT INTO "importspecs" (specid,specTitle,importType,fieldSpecs) VALUES(' unknown','unknown format',0,'// Following lists use zero-based column numbers
$IMPORTSPEC[''default''][''BCMethod'']       = 1;
$IMPORTSPEC[''default''][''CorrectedMiles'']       = 0;
$IMPORTSPEC[''default''][''EntrantStatus'']	= 0; // Default to DNS

');


INSERT INTO "importspecs" (specid,specTitle,importType,fieldSpecs) VALUES('Rally','IBAUK rally',0,'// Following lists use zero-based column numbers

// cols represent fields in the ScoreMaster.entrants table
$IMPORTSPEC[''default''][''BCMethod'']       = 1;
$IMPORTSPEC[''default''][''CorrectedMiles'']       = 0;
$IMPORTSPEC[''default''][''EntrantStatus'']	= 0; // Default to DNS

$IMPORTSPEC[''cols''][''EntrantID'']	= 0;
$IMPORTSPEC[''cols''][''RiderLast'']	= 9;
$IMPORTSPEC[''cols''][''RiderFirst'']	= 8;
$IMPORTSPEC[''cols''][''RiderIBA'']		= 10;

// Import pillion details regardless of has_pillion
$IMPORTSPEC[''cols''][''PillionLast'']	= 14;
$IMPORTSPEC[''cols''][''PillionFirst'']	= 13;
$IMPORTSPEC[''cols''][''Bike''] 		= 25;
$IMPORTSPEC[''cols''][''BikeReg''] 		= 26;

$IMPORTSPEC[''default''][''OdoKms'']    = 0;
$IMPORTSPEC[''setif''][''OdoKms''][0]	= [27,''/M/''];
$IMPORTSPEC[''setif''][''OdoKms''][1]	= [27,''/K/''];


$IMPORTSPEC[''cols''][''Email'']		= 24;
$IMPORTSPEC[''cols''][''Phone'']		= 23;
$IMPORTSPEC[''cols''][''NoKName'']		= 28;
$IMPORTSPEC[''cols''][''NoKPhone'']		= 29;
$IMPORTSPEC[''cols''][''NoKRelation'']	= 30;
$IMPORTSPEC[''cols''][''Country''] 		= 22;

// data collects lines to be stored as ExtraData
$IMPORTSPEC[''data''][''Postcode'']		= 21;							// Export to rides database
$IMPORTSPEC[''data''][''Country'']		= 22;							// Export to rides database
$IMPORTSPEC[''data''][''Postal_Address'']		= ''17:18:19:20:21:22'';// Export to rides database
																		// Duplications deliberate
$IMPORTSPEC[''data''][''NoviceRider'']	= 11;
$IMPORTSPEC[''data''][''NovicePillion'']= 16;

// If the content of the indexed column matches the RE, reject (don''t load) the entry
$IMPORTSPEC[''reject''][35]	= ''/Withdrawn/'';

$IMPORTSPEC[''default''][''BCMethod'']       = 1;
$IMPORTSPEC[''setif''][''BCMethod''][1]	= [31,''/Electronic/''];
$IMPORTSPEC[''setif''][''BCMethod''][2]	= [31,''/Paper|Delayed/''];


');

INSERT INTO "importspecs" (specid,specTitle,importType,fieldSpecs) VALUES('Rallyx','IBAUK rally-reglist',0,'// Following lists use zero-based column numbers

$IMPORTSPEC[''default''][''BCMethod'']       = 1;
$IMPORTSPEC[''default''][''CorrectedMiles'']       = 0;
$IMPORTSPEC[''default''][''EntrantStatus'']	= 0; // Default to DNS

// If this flag exists and is set true then names and bikes won''t be reformatted
$IMPORTSPEC[''options''][''sourceisclean''] = true;

// cols represent fields in the ScoreMaster.entrants table
$IMPORTSPEC[''cols''][''EntrantID'']	= 0;
$IMPORTSPEC[''cols''][''RiderLast'']	= 2;
$IMPORTSPEC[''cols''][''RiderFirst'']	= 1;
$IMPORTSPEC[''cols''][''RiderIBA'']		= 3;

// Import pillion details regardless of has_pillion
$IMPORTSPEC[''cols''][''PillionLast'']	= 7;
$IMPORTSPEC[''cols''][''PillionFirst'']	= 6;
$IMPORTSPEC[''cols''][''PillionIBA'']	= 8;
$IMPORTSPEC[''cols''][''Bike''] 		= 11;
$IMPORTSPEC[''cols''][''BikeReg''] 		= 14;

$IMPORTSPEC[''default''][''OdoKms'']    = 0;
$IMPORTSPEC[''setif''][''OdoKms''][0]	= [15,''/M/''];
$IMPORTSPEC[''setif''][''OdoKms''][1]	= [15,''/K/''];


$IMPORTSPEC[''cols''][''Email'']		= 16;
$IMPORTSPEC[''cols''][''Phone'']		= 17;
$IMPORTSPEC[''cols''][''NoKName'']		= 24;
$IMPORTSPEC[''cols''][''NoKPhone'']		= 25;
$IMPORTSPEC[''cols''][''NoKRelation'']	= 26;
$IMPORTSPEC[''cols''][''Country''] 		= 23;

// data collects lines to be stored as ExtraData
$IMPORTSPEC[''data''][''Postcode'']		= 22;							// Export to rides database
$IMPORTSPEC[''data''][''Country'']		= 23;							// Export to rides database
$IMPORTSPEC[''data''][''Postal_Address'']		= ''18:19:20:21:22:23'';// Export to rides database
																		// Duplications deliberate
$IMPORTSPEC[''data''][''RiderRBL'']	= 4;
$IMPORTSPEC[''data''][''NoviceRider'']	= 5;
$IMPORTSPEC[''data''][''PillionRBL'']	= 9;
$IMPORTSPEC[''data''][''NovicePillion'']= 10;

// If the content of the indexed column matches the RE, reject (don''t load) the entry
// $IMPORTSPEC[''reject''][37]	= ''/Withdrawn/'';

$IMPORTSPEC[''default''][''BCMethod'']       = 1;
$IMPORTSPEC[''setif''][''BCMethod''][1]	= [27,''/Electronic/''];
$IMPORTSPEC[''setif''][''BCMethod''][2]	= [27,''/Paper|Delayed/''];


');



INSERT INTO "importspecs" (specid,specTitle,importType,fieldSpecs) VALUES('RBLR','RBLR1000',0,'// Following lists use zero-based column numbers

// cols represent fields in the ScoreMaster.entrants table
$IMPORTSPEC[''cols''][''EntrantID'']	= 0;
$IMPORTSPEC[''cols''][''RiderLast'']	= 9;
$IMPORTSPEC[''cols''][''RiderFirst'']	= 8;
$IMPORTSPEC[''cols''][''RiderIBA'']		= 10;
$IMPORTSPEC[''cols''][''ScoredBy'']		= 9; 	// RiderLast for surname sorting (cheat)

// Import pillion details regardless of has_pillion
$IMPORTSPEC[''cols''][''PillionLast'']	= 14;
$IMPORTSPEC[''cols''][''PillionFirst'']	= 13;
$IMPORTSPEC[''cols''][''Bike''] 		= 25;
$IMPORTSPEC[''cols''][''BikeReg''] 		= 26;

$IMPORTSPEC[''default''][''OdoKms'']    = 0;
$IMPORTSPEC[''setif''][''OdoKms''][0]	= [27,''/Miles/''];
$IMPORTSPEC[''setif''][''OdoKms''][1]	= [27,''/Kilometres/''];


$IMPORTSPEC[''cols''][''Email'']		= 24;
$IMPORTSPEC[''cols''][''Phone'']		= 23;
$IMPORTSPEC[''cols''][''NoKName'']		= 28;
$IMPORTSPEC[''cols''][''NoKPhone'']		= 29;
$IMPORTSPEC[''cols''][''NoKRelation'']	= 30;
$IMPORTSPEC[''cols''][''Country''] 		= 22;

// data collects lines to be stored as ExtraData
$IMPORTSPEC[''data''][''Postcode'']		= 21;							// Export to rides database
$IMPORTSPEC[''data''][''Country'']		= 22;							// Export to rides database
$IMPORTSPEC[''data''][''Postal_Address'']		= ''17:18:19:20:21:22'';// Export to rides database
																		// Duplications deliberate
$IMPORTSPEC[''data''][''NoviceRider'']	= 11;
$IMPORTSPEC[''data''][''NovicePillion'']= 16;

// If the content of the indexed column matches the RE, reject (don''t load) the entry
$IMPORTSPEC[''reject''][35]	= ''/Withdrawn/'';

$IMPORTSPEC[''default''][''BCMethod'']       = 0;
$IMPORTSPEC[''setif''][''BCMethod''][1]	= [31,''/Electronic/''];
$IMPORTSPEC[''setif''][''BCMethod''][2]	= [31,''/Paper|Delayed/''];

$IMPORTSPEC[''default''][''FinishPosition''] = 1; /* Print certs right away */

// Now choose only rows matching the regex below; multiple rows = and
//$IMPORTSPEC[''select''][21]			= ''/North Anti Clock Wise/'';


/* Set the field after ''setif'' to the following value if the column matches the regex */
$IMPORTSPEC[''default''][''EntrantStatus'']	= 8; // Finisher so certificate can be printed straight away

$IMPORTSPEC[''default''][''Class'']		= 0;
$IMPORTSPEC[''setif''][''Class''][1]	= array(35,''/B \- North Anti\-Clockwise/'');
$IMPORTSPEC[''setif''][''Class''][2]	= array(35,''/A \- North Clockwise/'');
$IMPORTSPEC[''setif''][''Class''][3]	= array(35,''/D \- South Anti\-Clockwise/'');
$IMPORTSPEC[''setif''][''Class''][4]	= array(35,''/C \- South Clockwise/'');
$IMPORTSPEC[''setif''][''Class''][5]	= array(35,''/BBG 1500/'');
$IMPORTSPEC[''setif''][''Class''][6]	= array(35,''/E \- 500 Clockwise/'');
$IMPORTSPEC[''setif''][''Class''][7]	= array(35,''/F \- 500 Anti\-Clockwise/'');

$IMPORTSPEC[''default''][''CorrectedMiles'']		= 0;
/* These mileage settings need to be unique */
$IMPORTSPEC[''setif''][''CorrectedMiles''][1006]	= array(35,''/North/'');
$IMPORTSPEC[''setif''][''CorrectedMiles''][1004]	= array(35,''/South/'');
$IMPORTSPEC[''setif''][''CorrectedMiles''][1527]	= array(35,''/BBG 1500/'');
$IMPORTSPEC[''setif''][''CorrectedMiles''][504]	= array(35,''/500/'');


$IMPORTSPEC[''data''][''Miles2Squires'']	= 37;
$IMPORTSPEC[''data''][''FreeCamping'']		= 36;
$IMPORTSPEC[''data''][''T-shirt'']			= 33;
$IMPORTSPEC[''data''][''T-shirt2'']			= 34;





');


INSERT INTO "importspecs" (specid,specTitle,importType,fieldSpecs) VALUES('RBLRx','RBLR1000-reglist',0,'// Following lists use zero-based column numbers


// If this flag exists and is set true then names and bikes won''t be reformatted
$IMPORTSPEC[''options''][''sourceisclean''] = true;

// cols represent fields in the ScoreMaster.entrants table
$IMPORTSPEC[''cols''][''EntrantID'']	= 0;
$IMPORTSPEC[''cols''][''RiderLast'']	= 2;
$IMPORTSPEC[''cols''][''RiderFirst'']	= 1;
$IMPORTSPEC[''cols''][''RiderIBA'']		= 3;
$IMPORTSPEC[''cols''][''ScoredBy'']		= 2; 	// RiderLast for surname sorting (cheat)

// Import pillion details regardless of has_pillion
$IMPORTSPEC[''cols''][''PillionLast'']	= 7;
$IMPORTSPEC[''cols''][''PillionFirst'']	= 6;
$IMPORTSPEC[''cols''][''PillionIBA'']	= 8;

$IMPORTSPEC[''cols''][''Bike''] 		= 11;
$IMPORTSPEC[''cols''][''BikeReg''] 		= 14;

$IMPORTSPEC[''default''][''OdoKms'']    = 0;
$IMPORTSPEC[''setif''][''OdoKms''][0]	= [13,''/M/''];
$IMPORTSPEC[''setif''][''OdoKms''][1]	= [13,''/K/''];


$IMPORTSPEC[''cols''][''Email'']		= 16;
$IMPORTSPEC[''cols''][''Phone'']		= 17;
$IMPORTSPEC[''cols''][''NoKName'']		= 24;
$IMPORTSPEC[''cols''][''NoKPhone'']		= 25;
$IMPORTSPEC[''cols''][''NoKRelation'']	= 26;
$IMPORTSPEC[''cols''][''Country''] 		= 23;

// data collects lines to be stored as ExtraData
$IMPORTSPEC[''data''][''Postcode'']		= 22;							// Export to rides database
$IMPORTSPEC[''data''][''Country'']		= 23;							// Export to rides database
$IMPORTSPEC[''data''][''Postal_Address'']		= ''18:19:20:21:22:23'';// Export to rides database
																		// Duplications deliberate
$IMPORTSPEC[''data''][''NoviceRider'']	= 4;
$IMPORTSPEC[''data''][''NovicePillion'']= 8;

// If the content of the indexed column matches the RE, reject (don''t load) the entry
// $IMPORTSPEC[''reject''][37]	= ''/Withdrawn/'';

$IMPORTSPEC[''default''][''BCMethod'']       = 0;
$IMPORTSPEC[''setif''][''BCMethod''][1]	= [27,''/Electronic/''];
$IMPORTSPEC[''setif''][''BCMethod''][2]	= [27,''/Paper|Delayed/''];

$IMPORTSPEC[''default''][''FinishPosition''] = 1; /* Print certs right away */

// Now choose only rows matching the regex below; multiple rows = and
//$IMPORTSPEC[''select''][23]			= ''/North Anti Clock Wise/'';


/* Set the field after ''setif'' to the following value if the column matches the regex */
$IMPORTSPEC[''default''][''EntrantStatus'']	= 8; // Finisher so certificate can be printed straight away

$IMPORTSPEC[''default''][''Class'']		= 0;
$IMPORTSPEC[''setif''][''Class''][1]	= array(28,''/B \- North Anti\-Clockwise/'');
$IMPORTSPEC[''setif''][''Class''][2]	= array(28,''/A \- North Clockwise/'');
$IMPORTSPEC[''setif''][''Class''][3]	= array(28,''/D \- South Anti\-Clockwise/'');
$IMPORTSPEC[''setif''][''Class''][4]	= array(28,''/C \- South Clockwise/'');
$IMPORTSPEC[''setif''][''Class''][5]	= array(28,''/BBG 1500/'');
$IMPORTSPEC[''setif''][''Class''][6]	= array(28,''/E \- 500 Clockwise/'');
$IMPORTSPEC[''setif''][''Class''][7]	= array(28,''/F \- 500 Anti\-Clockwise/'');

$IMPORTSPEC[''default''][''CorrectedMiles'']		= 0;
/* These mileage settings need to be unique */
$IMPORTSPEC[''setif''][''CorrectedMiles''][1006]	= array(28,''/North/'');
$IMPORTSPEC[''setif''][''CorrectedMiles''][1004]	= array(28,''/South/'');
$IMPORTSPEC[''setif''][''CorrectedMiles''][1527]	= array(28,''/BBG 1500/'');
$IMPORTSPEC[''setif''][''CorrectedMiles''][504]	= array(28,''/500/'');


$IMPORTSPEC[''data''][''Miles2Squires'']	= 33;
$IMPORTSPEC[''data''][''FreeCamping'']		= 32;
$IMPORTSPEC[''data''][''T-shirt'']			= 29;
$IMPORTSPEC[''data''][''T-shirt2'']			= 30;





');



INSERT INTO "importspecs" (specid,specTitle,importType,fieldSpecs) VALUES('Standard','Standard bonus import',1,'// Following lists use zero-based column numbers

// cols represent fields in the ScoreMaster.bonuses table
$IMPORTSPEC[''cols''][''BonusID'']	= 0;
$IMPORTSPEC[''cols''][''BriefDesc'']	= 1;
$IMPORTSPEC[''cols''][''Points'']	= 2;


');

INSERT INTO "importspecs" (specid,specTitle,importType,fieldSpecs) VALUES('Combos','Combo bonus import',2,'// Following lists use zero-based column numbers

// cols represent fields in the ScoreMaster.bonuses table
$IMPORTSPEC[''cols''][''ComboID'']	= 0;
$IMPORTSPEC[''cols''][''BriefDesc'']	= 1;
$IMPORTSPEC[''cols''][''ScorePoints'']	= 2;
$IMPORTSPEC[''cols''][''Bonuses'']	= 3;


');

INSERT INTO "themes" (Theme,css) VALUES('default','
--button-text : white;
--button-background : #006600;
--button-text-disabled : darkgray;
--button-back-disabled : #339966;
--bright-text : red;
--bright-background : #ffff00;
--regular-background : #ccffcc;
--form-background : #ccffcc;
--input-text : #000;
--hover-background : lightgray;
--hover-text : black;
--tabs-background : #a2f2a0;
--tabs-text : #42454a;
--via-background : white;
--via-text : black;
--solid-border : #c9c3ba;
--header-background : lightgray;
--scorex-background : #66D2FF;
--rejected-background : red;
--rejected-text : white;
--checked-background : #31ad16;
--checked-text : white;
');

INSERT INTO "themes" (Theme,css) VALUES('Brown sugar','
/* Brown sugar */
--button-text : 042037;
--button-background : #718EA4;
--button-text-disabled : 29506D;
--button-back-disabled : #496D89;
--bright-text : red;
--bright-background : #ffff00;
--regular-background : #FFDBAA;
--regular-text : #553100;
--form-background : #D4A76A;
--input-text : #553100;
--hover-background : lightgray;
--hover-text : black;
--tabs-background : #dedbde;
--tabs-text : #42454a;
--via-background : white;
--via-text : black;
--solid-border : #c9c3ba;
--header-background : #D4A76A;
--scorex-background : white;
--rejected-background : red;
--rejected-text : white;
--checked-background : #31ad16;
--checked-text : white;
');

INSERT INTO "themes" (Theme,css) VALUES('Purple haze','
/* Purple haze */
--button-text : #553F00;
--button-background : #FFE9AA;
--button-text-disabled : darkgray;
--button-back-disabled : #339966;
--bright-text : red;
--bright-background : #ffff00;
--regular-background : #7F81B2;
--regular-text : #090A3B;
--form-background : #555794;
--input-text : #000;
--hover-background : lightgray;
--hover-text : black;
--tabs-background : #dedbde;
--tabs-text : #42454a;
--via-background : white;
--via-text : black;
--solid-border : #c9c3ba;
--header-background : lightgray;
--scorex-background : #66D2FF;
--rejected-background : red;
--rejected-text : white;
--checked-background : #31ad16;
--checked-text : white;
');

INSERT INTO "themes" (Theme,css) VALUES('Mulligatawny soup','
/* Mulligatawny soup */
--button-text : white;
--button-background : #006600;
--button-text-disabled : darkgray;
--button-back-disabled : #339966;
--bright-text : red;
--bright-background : #ffff00;
--regular-background : #4d4c05;
--form-background : #4d4c05;
--input-text : #ffffc8;
--regular-text : #ffffc8;
--hover-background : lightgray;
--hover-text : black;
--tabs-background : #dedbde;
--tabs-text : #42454a;
--via-background : white;
--via-text : black;
--solid-border : #c9c3ba;
--header-background : darkgray;
--scorex-background : #66D2FF;
--rejected-background : red;
--rejected-text : white;
--checked-background : #31ad16;
--checked-text : white;
');

INSERT INTO "themes" (Theme,css) VALUES('Mellow yellow','
/* Mellow yellow */
--button-text : black;
--button-background : #f1fa41;
--button-text-disabled : darkgray;
--button-back-disabled : #339966;
--bright-text : red;
--bright-background : #ffff00;
--regular-background : #ffe699;
--input-text : black;
--hover-background : #ce99ff;
--hover-text : black;
--tabs-background : #cade11;
--tabs-text : #42454a;
--form-background : #ffe673;
--via-background : white;
--via-text : black;
--solid-border : #c9c3ba;
--header-background : lightgray;
--scorex-background : #66D2FF;
--rejected-background : red;
--rejected-text : white;
--checked-background : #31ad16;
--checked-text : white;
');


COMMIT;
