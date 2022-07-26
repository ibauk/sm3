<?php

/*
 * I B A U K   -   S C O R E M A S T E R
 *
 * I contain all the text literals used throughout the system. If translation/improvement
 * is needed, this is the file to be doing it.
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



/*
 * This next constant determines whether the basic unit of distance used
 * by this application is the mile or the kilometre. The field names on
 * the database remain as 'miles' or 'mileage' as they're only used 
 * internally but calculations switching between miles and kilometres
 * are affected by this setting. Field labels and tooltips must also
 * be altered manually. It is assumed that a particular instance of this 
 * application will always use the same unit of measure.
 *
 * Field labels affected are marked  // Miles/Kms
 *
 */

// As from DBVERSION >= 5, these value is overridden from the database
 
$KONSTANTS['BasicDistanceUnit'] = $KONSTANTS['DistanceIsMiles'];
// $KONSTANTS['BasicDistanceUnit'] = $KONSTANTS['DistanceIsKilometres'];

// Default settings

$KONSTANTS['LocalTZ'] = '+0000'; // Default to GMT

$KONSTANTS['DecimalPointIsComma']  = 0;

// Used when setting up new entrants onscreen

$KONSTANTS['DefaultCountry'] = 'UK';
$KONSTANTS['DefaultLocale'] = 'en-GB';


// Not this one though <haha>

// Assume this value, which may be blank, if not overridden at run time
$KONSTANTS['DefaultScorer'] = 'Bob';

// Used for offline score recalculations - check with custom.js
// Modified for i8 
$KONSTANTS['DNF_TOOFEWPOINTS'] = "points";
$KONSTANTS['DNF_TOOFEWMILES'] = "distance"; // not used - i8
$KONSTANTS['DNF_TOOMANYMILES'] = "distance"; // not used - i8
$KONSTANTS['DNF_FINISHEDTOOLATE'] = "Finished too late"; // not used - i8
$KONSTANTS['DNF_MISSEDCOMPULSORY'] = "&#8265; ";
$KONSTANTS['DNF_HITMUSTNOT'] = "&#8264; ";
$KONSTANTS['DNF_COMPOUNDRULE'] = "Failed to meet a category rule";
$KONSTANTS['DNF_SPEEDING'] = "Excessive speed"; // not used - i8

// Elements of Score explanation, include trailing space, etc - check with custom.js
$KONSTANTS['RPT_Tooltip']	= "Click for explanation\rdoubleclick to print";
$KONSTANTS['RPT_Bonuses']	= "Bonuses ticked";
$KONSTANTS['RPT_Specials']	= "Specials";
$KONSTANTS['RPT_Combos']	= "Combos";
$KONSTANTS['RPT_MPenalty']	= "!! ";
$KONSTANTS['RPT_TPenalty']	= "&#x23F0;";
$KONSTANTS['RPT_Total'] 	= "TOTAL";
$KONSTANTS['RPT_SPenalty']	= "!! ";

$KONSTANTS['CLAIM_REJECTED'] = "!! ";

// This array specifies labels and tooltips for each onscreen field to avoid the need for 'literals in the procedure division'.
// This is in alphabetic order by key. It doesn't need to be but it makes your life easier, doesn't it?
// DO NOT alter key names!
$TAGS = array(
	'abtAuthor'			=> array('Author','Who developed this application'),
	'abtBasicDistance'	=> array('Basic distance unit','Miles or Kilometres'),
	'abtDatabase'		=> array('Database file','Full path to the file containing the database'),
	'abtDBVersion'		=> array('DB schema version',''),
	'abtDefaultOdo'		=> array('Odo default','Odometers assumed to record miles or kilometres'),
	'abtDocAdminGuide'	=> array('Administration Guide','User guide for rally administrators'),
	'abtDocDBSpec'		=> array('Database specs','Full contents of the database'),
	'abtDocTechRef'		=> array('Technical reference','For application developers'),
	'abtHostname'		=> array('Hostname','Name of the computer hosting this application'),
	'abtHostOS'			=> array('HostOS','Details of the host\'s operating system'),
	'abtInspired'		=> array('Inspired by','Creative inpiration sources'),
	'abtLicence'		=> array('Licence','The licence controlling use of this application'),
	'abtOnlineDoc'		=> array('Online documentation','Current application manuals available on the web'),
	'abtOnlineDocs'		=> array('Full documentation for all aspects of the software (needs internet connection)','Concepts, Configuration, Operations, Technical, Database'),
	'abtPHP'			=> array('PHP version',''),
	'abtSQLite'			=> array('SQLite version',''),
	'abtWebserver'		=> array('Webserver','What webserver software is hosting this'),
	'accessScorecards'	=> array('Scorecard access','Scorecards may be updated directly but any changes will not also update the claims log.'),
	'AddPoints'			=> array('Add points',''),
	'AddMults'			=> array('Add multipliers',''),
	'AdmAdvancedHeader'	=> array('ScoreMaster advanced setup','Advanced configuration options'),
	'AdmApplyClaims'	=> array('Process decided claims','Auto-post decided claims'),
	'AdmBonusHeader'	=> array('Bonuses',''),
	'AdmBonusTable'		=> array('Ordinary bonuses','View/edit schedule of ordinary bonuses'),
	'AdmCatTable'		=> array('Categories','View/edit set categories'),
	'AdmClaims'			=> array('Claims log','Access log of bonus claims'),
	'AdmClasses'		=> array('Classes','View/edit certificate classes'),
	'AdmCohortTable'	=> array('Cohorts','View/edit table of rally start cohorts'),
	'AdmCombosTable'	=> array('Combinations','View/edit combination bonuses'),
	'AdmCompoundCalcs'	=> array('Compound calculations','Maintain table of calculation records'),
	'AdmConfirm'		=> array('Reconcile scorecards','Confirm scorecards as accurate'),
	'AdmDoBlank'		=> array('Post score ticksheet','Show blank score with reject reasons sheet ready for printing'),
	'AdmDoBlankB4'		=> array('Scoring ticksheet','Show paper scoring log sheet ready for printing'),
	'AdmDoScoring'		=> array('Scorecards','Score individual entrants'),
	'AdmEBClaims'		=> array('EBC claims judging','Judge claims automatically retrieved from email'),
	'AdmEditCert'		=> array('Edit certificate content','Edit the HTML &amp; CSS of the master certificate'),
	'AdmEntrants'		=> array('Full Entrant records','View/edit list of Entrants'),
	'AdmEntrantChecks'	=> array('Check-out/in','Entrant checks @ start/end of rally'),
	'AdmEntrantsHeader'	=> array('Entrants',''),
	'AdmExportEntrants'	=> array('Export entrant records','Save CSV containing full details of entrants'),
	'AdmExportFinishers'=> array('Export finishers','Save CSV containing details of finishers'),
	'AdmImportBonuses'	=> array('Import ordinary bonuses','Load ordinary bonuses from a spreadsheet'),
	'AdmImportCombos'	=> array('Import combo bonuses','Load combo bonuses from a spreadsheet'),
	'AdmImportEntrants'	=> array('Import Entrants','Load entrant details from a spreadsheet'),
	'AdmMagicWords'		=> array('Magic words','Magic words are used to control claims in virtual rallies. If this table is empty, no magic words are required to validate claims otherwise each claim must include the most recent applicable word listed here.'),
	'AdminMenu'			=> array('Rally Administration','Logon to carry out administration (not scoring) of the rally'),
	'AdmMenuHeader'		=> array('ScoreMaster',''),
	'AdmNewBonus'		=> array('Setup new bonus','Add details of another bonus'),
	'AdmNewEntrant'		=> array('Setup new entrant','Add details of another entrant'),
	'AdmOdoChecks'		=> array('Odometer checks','Record details of odo check ride'),
	'AdmOdoReadings'	=> array('Odometer readings','Start/finish odo readings'),
	'AdmPrintCerts'		=> array('Finisher certificates','Print certificates for finishers'),
	'AdmPrintQlist'		=> array('Finisher quicklist','Print quick list of finishers'),
	'AdmPrintScoreX'	=> array('Score explanations','Print score explanations for everyone not DNS'),
	'AdmRallyParams'	=> array('Rally parameters','View/edit current rally parameters'),
	'AdmRankEntries'	=> array('Rank finishers','Calculate and apply the rank of each finisher'),
	'AdmRebuildScorecards'
						=> array('Rebuild scorecards','Reprocess all bonus claims'),
	'AdmRPHideAdv'		=> array('Hide advanced','Hide advanced rally parameters'),
	'AdmRPShowAdv'		=> array('Show advanced','Show advanced rally parameters'),
	'AdmSelectTag'		=> array('Search by keyword','Choose a tag to list relevant functions'),
	'AdmSendEmail'		=> array('Email entrants','Send an email to some or all entrants'),
	'AdmSetupHeader'	=> array('ScoreMaster Setup',''),
	'AdmSetupWiz'		=> array('Setup wizard','Basic rally setup wizard'),
	'AdmSGroups'		=> array('Bonus groups','Maintain special groups of bonuses'),
	'AdmShowAdvanced'	=> array('Advanced setup','Access advanced configuration options'),
	'AdmShowSetup'		=> array('Rally setup &amp; config','View/maintain rally configuration records'),
	'AdmShowTagMatches'	=> array('Items matching ','Showing functions matching tag '),
	'AdmSpecialTable'	=> array('Special bonuses','View/edit special bonuses'),
	'AdmSpeedPenalties'	=> array('Speed penalties','Maintain table of speed penalties'),
	'AdmTeamsTable'		=> array('Teams','Maintain table of teams'),
	'AdmThemes'			=> array('Display themes','Change the colourways used'),
	'AdmTimePenalties'	=> array('Time penalties','Maintain table of time penalties'),
	'AdmUtilHeader'		=> array('Utility functions',',,'),
	
	'AskEnabledSave'	=> array('Save this scoresheet?',''),
	'AskMinutes'		=> array('Variable?','Fixed or ask for this during scoring'),
	'AskMinutes0'		=> array('Fixed',''),
	'AskMinutes1'		=> array('Variable',''),
	'AskPoints'			=> array('Variable?','Fixed or ask for points value during scoring'),
	'AskPoints0'		=> array('Fixed','Points value is fixed'),
	'AskPoints1'		=> array('Variable','Points value entered during scoring'),
	'AvgSpeedLit'		=> array('Speed','Moving average speed over the whole rally'),
	'AxisCats'			=> array('Categories for set','List of categories belonging to this set'),
	'AxisLit'			=> array('Set','The set of categories this rule applies to'),
	'AutoRank'			=> array('Automatic Ranking','Rank automatically recalculated when scorecard updated'),
	'BasicDetails'		=> array('Basic',''),
	'BasicRallyConfig'	=> array('Basic','Basic rally configuration fields'),
	'BCHOME'			=> array(' &nbsp;/ ','Main menu'),
	'BCMethod'			=> array('Bonus claiming','Method of bonus claim: 0=unknown,1=EBC,2=paper/deferred'),
	'BCMethod0'			=> array('unknown',''),
	'BCMethod1'			=> array('EBC','Electronic Bonus Claiming'),
	'BCMethod2'			=> array('Paper','Paper or deferred claiming'),
	'Bike'				=> array('Bike','Make &amp; model of bike'),
	'BikeReg'			=> array('Registration','Registration number of the bike if known'),
	'BonusAnswer'		=> array('Answer','Answer needed for extra points'),
	'BonusClaimDecision'=> array('Decision','The status of this claim'),
	'BonusClaimOK'		=> array('Good claim',''),
	'BonusClaimTime'	=> array('Claim time','The claimed time of this Bonus claim'),
	'BonusClaimUndecided'
						=> array('undecided',''),
	'BonusCoords'		=> array('Coords','Location of this bonus'),
	'BonusFlags'		=> array('Scoring flags','Scoring flags'),
	'BonusesLit'		=> array('Bonuses','Ordinary bonuses'),
	'BonusIDLit'		=> array('Code','Unique identifier for this bonus'),
	'BonusListLit'		=> array('Underlying bonuses','Comma separated list of ordinary or combination bonuses'),
	'BonusMaintHead'	=> array('Ordinary Bonuses','Ordinary bonuses generally represent physical locations that entrants must visit and complete some task, typically take a photo. They are presented on scorecards in code order. Numeric only codes should all have the same number of digits (use leading \'0\' if necessary). Descriptions may include limited HTML to affect formatting on score explanations.'),
	'BonusNotes'		=> array('Scoring notes','Notes neeeded to judge this bonus'),
	'BonusPhoto'		=> array('Image','The rally book photo of this bonus. Must exist in the images/bonuses folder'),
	'BonusPoints'		=> array('Points','The basic points value of this bonus'),
	'BonusQuestion'		=> array('Question','Question scoring extra points'),
	'BonusScoringFlagA'	=> array('A','Alert!'),
	'BonusScoringFlagB'	=> array('B','Bike in photo'),
	'BonusScoringFlagD'	=> array('D','Daylight only'),
	'BonusScoringFlagF'	=> array('F','Face in photo'),
	'BonusScoringFlagR'	=> array('R','Restricted access/hours'),
	'BonusScoringFlagT'	=> array('T','Receipt/ticket required'),
	'BonusWaffle'		=> array('Waffle','Comments about this bonus, info only.'),
	'BriefDescLit'		=> array('Description',''),
	'CalcMaintHead'		=> array('Compound Calculation Rules','Compound score calculations are used in conjunction with category records and bonus classifications to implement powerful scoring logic. Please click the help button for a complete understanding of how these work.'),
	'CalculatedAvgSpeed'=> array('','Calculated average speed'),
	'Cat0Label'			=> array('Total','If summing across sets, use this label'),
	'Cat1Label'			=> array('Set 1 is','What do values in this set represent?'),
	'Cat2Label'			=> array('Set 2 is','What do values in this set represent?'),	
	'Cat3Label'			=> array('Set 3 is','What do values in this set represent?'),
	'Cat4Label'			=> array('Set 4 is','What do values in this set repres;ent?'),
	'Cat5Label'			=> array('Set 5 is','What do values in this set represent?'),
	'Cat6Label'			=> array('Set 6 is','What do values in this set represent?'),
	'Cat7Label'			=> array('Set 7 is','What do values in this set represent?'),
	'Cat8Label'			=> array('Set 8 is','What do values in this set represent?'),
	'Cat9Label'			=> array('Set 9 is','What do values in this set represent?'),
	'CatBriefDesc'		=> array('Description',''),
	'CategoryAxes'		=> array('Categories','Categories allow for more complex scoring mechanisms. If used, each ordinary or combination bonus can be marked as belonging to a particular category within each used set. The sets can be used to represent entities such as county, country, activity, etc. Such memberships can be used to modify basic bonus scoring and/or apply a second level of scoring using compound calculation records.'),
	
	'CategoryLit'		=> array('Category',''),
	'CatEntry'			=> array('Category','The number of this category within the set'),
	'CatEntryCC'		=> array('Which category','Which cat(s) does this rule apply to'),
	'CatExplainer'		=> array('CatExplainer','You can amend the description of categories or delete them entirely. New entries must have a category number which is unique within the set.'),
	'CatNotUsed'		=> array('(not used)',''),
	'ccApplyToAll'		=> array('&#8721;cats','applies to all cats'),
	'ccRuletype'		=> array('Rule type','What is the effect of this rule if triggered'),
	'ccRuletype0'		=> array('Scoring','Ordinary scoring rule'),
	'ccRuletype1'		=> array('Untrig=DNF','DNF unless this rule triggered'),
	'ccRuletype2'		=> array('Trigger=DNF','DNF if this rule triggered'),
	'ccRuletype3'		=> array('Placeholder','Placeholder rule'),
	'ccRuletype4'		=> array('Sequence','Sequential bonus award'),
	
	'CertExplainer'		=> array('Certificates are "web" documents comprising well-formed HTML and CSS parts.',
									'Please carefully specify the certificate layout and content in the texts below.'),
	'CertExplainerW'	=> array('Multiple \'classes\' may be defined, each with its own certificate.',''),
	'CertTitle'			=> array('Title','Description of this certificate class'),

	'cl_Applied'		=> array('Applied?','Has this claim been applied to the entrant\'s scorecard?'),
	'cl_AppliedHdr'		=> array('Applied',''),
	'cl_ApplyBtn'		=> array('&RuleDelayed;','Post decided claims'),
	'cl_ApplyHdr'		=> array('Process decided claim records','Post decided claims <span style="font-size:small;">(with no virtual rally penalties)</span> in one batch. Single-user access to the database needed during this process which should only take a minute or so.'),
	'cl_Applying'		=> array('Applying decided claims',''),
	'cl_BonusHdr'		=> array('Bonus',''),
	'cl_ClaimedHdr'		=> array('Claimed',''),
	'cl_ClaimsBumf'		=> array('Claims log','The claims log records individual bonus claims, typically during live bonus claiming. Claims posted here automatically update the scorecards.'),
	'cl_ClaimsTitle'	=> array('Claims','HTML page title'),
	'cl_Complete'		=> array('Processing complete &#x1F603;',
								'Processing complete &#x1F603; <br><br><span style="font-size:smaller;"><a href="score.php">Updated scorecards need recalculation</a></span>.'),
	'cl_DateFrom'		=> array('From date','Start of date range'),
	'cl_DateTo'			=> array('To date','End of date range'),
	
	'cl_DDLabel'		=> array('&nbsp;&nbsp;New claim defaults:','Default decision/date when posting new claims'),
	'cl_DecIncDecided'	=> array('All decided claims',''),
	'cl_DecIncGoodOnly'	=> array('Good claims only',''),
	'cl_DecisionHdr'	=> array('Decision',''),
	'cl_DecisionsIncluded'
						=> array('Claims included','What decided claims will be included'),

	'cl_EditHeader'		=> array('Full claim details.','New claims: can paste correctly formatted Subject line.'),
	'cl_EntrantHdr'		=> array('Entrant',''),
	'cl_EntrantIDs'		=> array('Entrant numbers. Leave blank for all','Comma-separated list of entrant numbers or blank=all'),
	'cl_FilterBonus'	=> array('B#','Filter list by Bonus'),
	'cl_FilterEntrant'	=> array('E#','Filter list by Entrant number'),
	'cl_FilterLabel'	=> array('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;List filters','Use these fields to filter this list'),
	'cl_Go'				=> array('Go on, go for it!','Go on, go for it!'),
	'cl_LastBonusID'	=> array('Last bonus claimed:','ID of last bonus claimed by this entrant'),
	'cl_LoggedHdr'		=> array('Logged',''),
	'cl_MagicPenalty'	=> array('Penalty','Special penalty (percentage)'),
	'cl_NumClaims'		=> array('Number of claims shown','Number of claims shown'),
	'cl_OdoHdr'			=> array('Odo',''),
	'cl_PenaltyFuel'	=> array('&nbsp;F&nbsp;','Fuel penalty'),
	'cl_PenaltyMagic'	=> array('&nbsp;M&nbsp;','Magic word penalty'),
	'cl_PenaltySpeed'	=> array('&nbsp;S&nbsp;','Speeding penalty'),
	'cl_PostNewClaim'	=> array('+','Post new claim'),
	'cl_ProcessingCC'	=> array('Processing %u decided claims',''),
	'cl_RecalcHdr'		=> array('Recalculating scorecards',''),
	'cl_Recalculating'	=> array('Recalculating',''),
	'cl_RefreshList'	=> array('&circlearrowright;','Refresh the claims list'),
	'cl_Rejecting'		=> array('Rejecting',''),
	'cl_ReprocessHdr'	=> array('Rebuild scorecards from Claims Log','Rebuild scorecards by reprocessing decided claims in one batch.<br>Single-user access to the database needed during this process which should only take a minute or so.'),
	'cl_ReprocessNo'	=> array('Process unapplied claims only','Complete scorecard by processing any unapplied claims.'),
	'cl_ReprocessOpt'	=> array('Reprocess method','Reprocess decided claims'),
	'cl_ReprocessYes'	=> array('Reapply decided claims only','This will not zero the scorecard but will reprocess any decided claims.'),
	'cl_ReprocessZap'	=> array('Fully rebuild scorecards','This will initialise the points, rest minutes and bonus claims / rejections of the scorecard then reprocess all decided claims to rebuild the scorecard.'),
	'cl_showAllA'		=> array('show all','Filter list on applied to scorecard status'),
	'cl_showNotA'		=> array('unapplied',''),
	'cl_showOnlyA'		=> array('applied',''),
	'cl_showAllD'		=> array('show all','Filter list on decided/undecided status'),
	'cl_showNotD'		=> array('undecided',''),
	'cl_showOnlyD'		=> array('decided',''),
	'cl_TimeFrom'		=> array('From time','Start of time range'),
	'cl_TimeTo'			=> array('To time','End of time range'),
	'cl_UpdatingSC'		=> array('Updating scorecard for Entrant %u',''),
	
	'Class'				=> array('Class #','The certificate class applicable'),
	'ClassMaintHead'	=> array('Classes','Classes may be used to produce different certificates for different groups or \'classes\' of entrant. Class can be assigned manually, as in the RBLR1000 to distinguish route for example, or can be assigned automatically using entrant scores, bonuses visited and/or rank.<br>Class 0 is the default class for all entrants and may not have any filters applied. Other classes are examined in numeric order starting at 1 until the filter criteria are matched. If no matching class is found, 0 is applied.'),

	'clg_EbcLogHdr'		=> array('Emailed claims ready to be judged','Emailed claims ready to be judged'),
	'clg_EbcJudge1'		=> array('Judge first claim','Judge first claim'),
	'clg_EbcNoLog'		=> array('Sorry, no claims need judging at the moment &#128543;','Sorry, no claims need judging at the moment &#128543;'),
	'clg_Header'		=> array('Schedule of claims received','Schedule of claims received'),
	'clg_NumberOfClaims'=> array('Number of claims received','Number of bonuses claimed'),


	'cls_Assigned'		=> array('Assigned','Auto/manual'),
	'cls_Assigned0'		=> array('MANUAL','manually'),
	'cls_Assigned1'		=> array('AUTO','automatically'),
	'cls_BonusesReqd'	=> array('Required<br>Bonuses','Comma separated list of ordinary bonus IDs needed to qualify for this class'),
	'cls_Certificate'	=> array('&equivDD;','Show certificate for this class'),
	'cls_LowestRank'	=> array('Rank','Lowest ranking to qualify for this class'),
	'cls_MinBonuses'	=> array('Bonuses<br>visited','Minimum number of ordinary bonuses visited'),
	'cls_MinPoints'		=> array('Min<br>points','Minimum points score to trigger this class'),
	'ChooseEntrant'		=> array('Choose entrant','Pick an entrant from this list'),
	'cht_FixedStart'	=> array('Start option','Common start time for this cohort'),
	'cht_FixedStart0'	=> array('1st claim','Each entrant starts with his first claim'),
	'cht_FixedStart1'	=> array('Fixed time','All entrants start together'),
	'cht_StartDate'		=> array('Start date','Start date'),
	'cht_StartTime'		=> array('Start time','Start time'),
	'Cohort'			=> array('Start cohort #','Unique number of this cohort'),
	'CohortMaintHead'	=> array('Cohorts','<br>Cohorts may be used to start groups of entrants at different times. If the "1st claim" method is selected, the date and time fields are ignored.<br>'),
	'CohortMembers'		=> array('Members of cohort: ','Members of cohort : '),
	'ComboIDLit'		=> array('Code',''),
	'ComboMaintHead'	=> array('Combination Bonuses','Combination bonuses are scored automatically when their underlying ordinary or combination bonuses are ticked. Combos can be set to score different values depending on the number of underlying bonuses ticked. By default all underlying bonuses must be ticked. Descriptions may include limited HTML to affect formatting on score explanations.'),
	'ComboScoreMethod'	=> array('Points or multipliers','Does this combo have a simple points value or multipliers applied to the total points scored?'),

	'CombosLit'			=> array('Combos','Combination bonuses'),
	'CommaSeparated'	=> array('Comma separated list',''),
	'CompulsoryBonus'	=> array('Compulsory?','Compulsory means this bonus is required for Finisher status'),
	'CompulsoryBonus0'	=> array('Optional',''),
	'CompulsoryBonus1'	=> array('Compulsory',''),
	'CompulsoryBonus2'	=> array('Must not',''),
	'ConfirmDelEntrant'	=> array('Delete this entrant?','Confirm deletion of this entrant'),
	'ConfirmedBonusTick'=> array('&#10004;','This bonus has been confirmed/reconciled'),
	
	'ContactDetails'	=> array('Contacts',''),
	
	'CorrectedMiles'	=> array('Distance ridden','Official rally distance ridden'),	// Miles/Kms
	
	'Country'			=> array('Country',"Entrant's home country"),
	'dberroragain'		=> array('The database save failed because [%s]. Please <button onClick="window.location.reload();">resubmit</button>. If problem persists tell Bob','The database save failed, probably temporary lock issue'),
	'dblclickprint'		=> array('Double-click to print',''),
	'DecimalComma'		=> array('Decimal point is','What character indicates a decimal point?'),
	'DecimalCommaFalse'	=> array('&nbsp;.&nbsp; ','Regular decimal point'),
	'DecimalCommaTrue'	=> array('&nbsp;,&nbsp;','Decimal point is comma'),
	
	'DeleteBonus'		=> array('Delete this bonus',''),
	'DeleteClaim'		=> array('Delete this claim',''),
	'DeleteEntrant'		=> array('Go ahead, delete the bugger!','Execute the deletion'),
	'DeleteEntryLit'	=> array('Delete this record?',''),
	
	'DistanceUnit'		=> array('Unit of distance','Rallies report either in miles or in kilometres'),
	
	'DuplicateRecord'	=> array('Duplicate record!','That record already exists in the database'),

	// These button labels have img-alt, title
	'ebc_BtnTeam'		=> array('2','Team rules'),
	'ebc_BtnAlert'		=> array('!','Read the notes!'),
	'ebc_BtnBike'		=> array('B','Bike in photo'),
	'ebc_BtnDaylight'	=> array('D','Daylight only'),
	'ebc_BtnFace'		=> array('F','Face in photo'),
	'ebc_BtnRestricted'	=> array('R','Restricted access'),
	'ebc_BtnReceipt'	=> array('T','Receipt/ticket required'),

	'ebc_ClickResize'	=> array('Click to resize','Click to resize'),
	'ebc_DoAccept'		=> array('Accept good claim','Accept the claim and award the points'),
	'ebc_DoCancel'		=> array('Leave Undecided','Refrain from judging this claim just now'),
	'ebc_DoReject'		=> array('Reject claim','Reject the claim for this reason'),
	'ebc_GetPoints'		=> array('Points','Enter the points value'),
	'ebc_GetMins'		=> array('Minutes','Enter the minutes value'),
	'ebc_HdrBonus'		=> array('Bonus',''),
	'ebc_HdrClaimedAt'	=> array('Claimed @',''),
	'ebc_HdrEntrant'	=> array('Entrant',''),
	'ebc_JudgeOrLeave'	=> array('Judge this bonus claim or leave it undecided',''),
	'ebc_JudgeThis'		=> array('Judge this','Judge this image'),
	'ebc_RallyPhoto'	=> array('Rally book','This is the rally book image'),
	
	'em_Attachment'		=> array('Add file attachment(s)','Upload a file'),
	'em_Body'			=> array('Message text','The main text of the email. If ScoreX included, it comes after this.'),
	
	'em_EntrantID'		=> array('Include entrants whose number is','Enter one or more EntrantIDs separated by commas'),
	'em_EntrantStatus'	=> array('Include entrants whose status is','Choose one or more entrant statuses to be emailed'),
	'em_includeCertificate'
						=> array('Attach certificate (Finishers only)','Include the individual entrant\'s Finisher Certificate'),
	'em_includeScorex'	=> array('Include score explanation (Finishers,DNF)','Include the individual entrant\'s score explanation'),
	'em_NotBlank'		=> array('must not be blank','must not be blank'),
	'em_NumberSelected'	=> array('Number of recipients selected','Number of recipients selected'),
	'em_SelectMethod'	=> array('Recipient selection method','Select based on what field'),
	'em_Signature'		=> array('Optional signature','The signature text of the email. Comes after any inclusions.'),
	
	'em_SpecialIncludes'=> array('Special includes :- ','Include these specials'),
	'em_Subject'		=> array('Subject line','The formal Subject of the email. May not be blank.'),
	'em_Submit'			=> array('Send email now','Send email now'),
	
	'email:Username'	=> array('Username','Username known to the host'),
	'email:SMTPAuth'	=> array('SMTPAuth','SMTP authentication required (true/false)'),
	'email:SMTPSecure'	=> array('SMTPSecure','SMTP security transport'),
	'email:Port'		=> array('Port','Port used for SMTP'),
	'email:Host'		=> array('Host','SMTP server'),
	'email:Password'	=> array('Password','Used with Username'),
	'email:SetFrom'		=> array('SetFrom','Email address, Sender name'),
	'EmailParams'		=> array('Email','Outgoing Email parameters: These fields contain rather technical information for use with PHPMailer. If you know what you\'re doing, have at it otherwise don\'t guess, do consult a grownup.'),
	
	'EntrantDNF'		=> array('DNF','Did not qualify as a finisher'),
	'EntrantDNS'		=> array('DNS','Entrant failed to start the rally'),
	'EntrantEmail'		=> array('Entrant email','Email for this entrant'),
	'EntrantFinisher'	=> array('Finisher','Rally finisher'),
	'EntrantID'			=> array('Entrant #','The unique reference for this Entrant'),
	'EntrantListBonus'	=> array('Entrants claiming bonus','List of entrants claiming a particular bonus'),
	'EntrantListCheck'	=> array('Entrant check-ins/outs','Choose an entrant for checkin-in or checking-out'),
	'EntrantListCombo'	=> array('Entrants claiming combo','List of entrants claiming a particular combination'),
	'EntrantListFind'	=> array('Entrants found','List of entrants matching your search key'),
	'EntrantListFull'	=> array('Full list of Entrants','Choose an entrant to view/edit his/her details'),
	'EntrantListSpecial'=> array('Entrants claiming special','List of entrants claiming a particular special'),
	'EntrantOK'			=> array('ok','Status normal'),
	'EntrantPhone'		=> array('Entrant phone','Contact phone for this entrant'),
	'EntrantStatus'		=> array('Status','The current rally status of this entrant'),
	
							// Careful! this is executed as PHP, get it right.
	'EntrantStatusV'	=> array('array("0" => "DNS", "1" => "ok", "8" => "Finisher", "3" => "DNF");','array used for vertical tables'),
	
	'ExcessMileage'		=> array('Distance',''),						// Miles/Kms
	
	'ExclusiveAccessNeeded'
						=> array('Exclusive use of the database is needed.','Please get everyone else out and try again.'),

	'ExpClasses'		=> array('Class help','Classes are used to produce different certificates for different groups of entrants. Classes may be either static, manually assigned, or dynamically calculated by reference to bonuses visited, points scored or rank achieved.'),
	'ExpTeams'			=> array('Teams help','A team consists of two or more bikes riding together. They can be scored individually or as a single team.'),
	'ExtraData'			=> array('ExtraData','Extra data to be passed on to the main database. Format is <i>name</i>=<i>value</i>'),
	
	'FetchCert'			=> array('Fetch certificate','Fetch the HTML, CSS &amp; options for this certificate'),
	'FinishDate'		=> array('Finish date','The last riding day of the rally.'),
	'FinishDateE'		=> array('Finish date','The last riding day of the rally.'),
	'FinishersExported'	=> array('Finishers exported!','Finisher details exported to CSV'),
	'FinishPosition'	=> array('Final place','Finisher ranking position',''),
	'FinishTime'		=> array('Finish time','Official finish time. Entrants finishing later are DNF'),
	'FinishTimeE'		=> array('Finish time','Official finish time. Check-in time.'),

	'fl_RefreshList'	=> array('&circlearrowright;','Refresh the list'),
	
	'FuelBalance'		=> array('Fuel','Fuel distance remaining'),
	'FuelWarning'		=> array('OUT OF FUEL!','This leg exceeded the remaining fuel capacity'),
	
	'FullDetails'		=> array('Full details','Show the complete record'),

	'gblMainMenu'		=> array('Main menu','Return to main menu'),
	
	'GroupNameLit'		=> array('Special group','Group used for presentation purposes'),
	'HelpAbout'			=> array('About ScoreMaster',''),
	
	'HostCountry'		=> array('Host country','Used to default addresses for entrants'),
	
	// If an imported bike field matches this re, replace with the phrase
	//                            re    phrase
	'ImportBikeTBC'		=> array('/tbc|tba|unknown/i','motorbike','Replace re with literal'),
	'InsertNewCC'		=> array('Enter new compound calc',''),
	'InsertNewCombo'	=> array('New combo','Setup a new combination bonus'),
	
	// Import Xls stuff
	'ix_ChooseAgain'	=> array('Choose again','Choose a different file'),
	'ix_Fileformat'		=> array('File format','Specification of file layout'),
	'ix_FileLoaded'		=> array('File loaded',''),
	'ix_HelpPrompt'		=> array('If the loaded file is in one of the known formats, choose the appropriate format using the \'file format\' dropdown. If it\'s in some other format, you\'ll have to
								use the individual column headers to map the database fields. You can also use the column dropdowns to customise a standard layout.<br>
								You can\'t append entries, you must overwrite any existing entries.',''),
	
	'jodit_Borders'		=> array('Print borders',''),
	'jodit_Borders_Double'
						=> array('Double',''),
	'jodit_Borders_None'=> array('None',''),
	'jodit_Borders_Solid'
						=> array('Solid',''),
	'jodit_InsertField'	=> array('Insert database field',''),
	'LegendPenalties'	=> array('Penalties',''),
	'LegendScoring'		=> array('Scoring &amp; Ranking',''),
	'LegendTeams'		=> array('Teams',''),
	'Locale'			=> array('Locale','ISO Locale string'),
	'LocalTZ'			=> array('Rally timezone','Timezone (offset from GMT) used in this rally'),
	
	'login'				=> array('login','Go on, log me in then!'),
	'LogoutScorer'		=> array('Logout','Log the named scorer off this terminal'),

	'magicword'			=> array('Magic','The \'magic\' word associated with this claim'),
	'MarkConfirmed'		=> array('Mark as confirmed','Mark all bonus claim decisions as having been confirmed'),
	'MarkConfirmedFull'	=> array('Mark scorecards as confirmed/reconciled.','Normally carried out by two people comparing the claims log or other evidence to the details recorded on the scorecards.'),
	'MaxHours'			=> array('Rideable hours','The maximum rideable hours available. Used to calculate DNF time, may show on certificates'),
	'MaxMilesFixedM'	=> array('Multiplier','Excess distance incurs deduction of multipliers'),							// Miles/Kms
	'MaxMilesFixedP'	=> array('Fixed points','Excess distance incurs fixed points deduction'),							// Miles/Kms
	
	'MaxMilesPerMile'	=> array('Points per mile/km','Excess distance incurs points deduction per excess mile/km'),				// Miles/Kms
	
	'MaxMilesPoints'	=> array('Points or Multipliers deducted','Number of points or multipliers for excess distance'),	// Miles/Kms
	'MaxMilesUsed'		=> array('Tick if maximum distance used','Will entrants be DNF if they exceed a maximum distance?'),	// Miles/Kms
	'MilesPenaltyText'	=> array('Distance penalty deduction',''),															// Miles/Kms
	'MinimumTicks'		=> array('Minimum Ticks','Minimum bonus ticks for this combo; 0=all'),
	'MinMiles'			=> array('Minimum distance','Minimum number of miles/kms to qualify as a finisher'),						// Miles/Kms
	'MinMilesUsed'		=> array('Tick if minimum distance used','Will entrants need to ride a minimum distance in order to qualify as finishers?'), // Miles/Kms
	
	'MinPoints'			=> array('Minimum points','Minimum points scored to be a finisher'),
	'MinPointsUsed'		=> array('Tick if minimum points used','Will entrants need to score a minimum number of points in order to qualify as finishers?'),
	'ModBonus0'			=> array('Set','Affects compound set score'),
	'ModBonus1'			=> array('Bonus','Modifies bonus score'),
	'ModBonusLit'		=> array('Score level','This rule either directly affects bonus value, or it\'s building the set score'),
	
	'mw_AsFrom'			=> array('As from','From what time does this word apply'),
	'mw_Word'			=> array('Word','The magic word'),
	
	'NameFilter'		=> array('Rider name','Use this to filter the list of riders shown below'),
	'NewEntrantNum'		=> array('New number','What\'s the number number for this entrant'),
	'NewPlaceholder'	=> array('start new entry','Placeholder for new table entries'),
	'NextTimeMins'		=> array('Time next leg','Enter estimated time of the next leg eg: 1h 35m; 1.35,1:35'),
	'NMethod-1'			=> array('Unused','Not used'),
	'NMethod0'			=> array('Bonuses per cat','No of bonuses per cat'),
	'NMethod1'			=> array('Cats per set','No of NZ cats per set'),
	'NMethodLit'		=> array('Compute N as','N is either the number of entries in a particular category or the number of categories with at least one hit'),
	'NMinLit'			=> array('Trigger value of N','The minimum value of N before this rule is triggered'),
	'NoCerts2Print'		=> array('Sorry, no certificates to print.',''),
	
	'NoKName'			=> array('NoK name','Name of Next of Kin (emergency contact)'),
	'NoKPhone'			=> array('NoK phone','Phone number for Next of Kin (emergency contact)'),
	'NoKRelation'		=> array('NoK relation','Relationship of Next of Kin (emergency contact'),
	'NoScoreX2Print'	=> array('Sorry, no score explanations to print.',''),
	'NoSelection'		=> array('{no selection}','{no selection}'),
	'nowlit'			=> array('Now','Record the current date/time'),
	'NPowerLit'			=> array('Power',"If bonus rule &amp; this is 0, R=bonuspoints(N-1)\n".
											"If bonus rule &amp; this > 0, R=bonuspoints(this^(N-1))\n".
											"If sequence rule, this is the multiplier\n".
											"If set rule &amp; this is 0, R=N\n".
											"If set rule &amp; this <> 0, R=this value"),
											
	'OdoCaution'		=> array('Caution!','Caution, if you change these values you might need to update the scorecard as well'),
	'OdoCheckFinish'	=> array('Odo-check finish','The odometer reading at the end of the odo check'),					// Miles/Kms
	'OdoCheckMiles'		=> array('Odo-check distance','The length of the route used to check the accuracy of odometers'),	// Miles/Kms
	'OdoCheckStart'		=> array('Odo-check start','The reading at the start of the odometer check'),						// Miles/Kms
	'OdoCheckTrip'		=> array('Odo-check trip','What distance did the trip meter record?'),								// Miles/Kms
	'OdoCheckUsed'		=> array('Tick if odo check used','Will entrants be required to ride an odometer check route?'),	// Miles/Kms
	'OdoKms'			=> array('Odo counts','What unit of distance does the odo track'),									// Miles/Kms
	'OdoKmsK'			=> array('kilometres',''),																			// Miles/Kms
	'OdoKmsM'			=> array('miles',''),																				// Miles/Kms
	'Odometer'			=> array('Odo&nbsp;readings',''),																		// Miles/Kms
	'OdoRallyStart'		=> array('Odo @ rally start','The reading at the start of the rally'),									// Miles/Kms
	'OdoRallyFinish'	=> array('Odo @ rally finish','The odometer reading at the end of the rally'),							// Miles/Kms
	'OdoReadingHdr'		=> array('Odo readings','Odo readings'),
	'OdoReadingLit'		=> array('Odo','Odo reading'),
	'OdoScaleFactor'	=> array('Correction factor','The number to multiply odo readings to get true distance'),			// Miles/Kms
	
	'OfferScore'		=> array('OfferScore','Would you like to help score this rally? If so, please tell me your name'),
	
	'oi_Scorecards'		=> array('Scorecards',''),
	'oi_ScorecardsMC'	=> array('Scorecards (confirm)',''),
	
	'optCompulsory'		=> array('Compulsory',''),
	'optOptional'		=> array('Optional',''),
	
	'PenaltyMaxMiles'	=> array('Max distance (penalties)','Distance ridden beyond this incurs penalties; 0=doesn\'t apply'),			// Miles/Kms
	'PenaltyMilesDNF'	=> array('DNF distance','Miles/kms beyond here result in DNF; 0=doesn\'t apply'),						// Miles/Kms
	
	'PickAnEntrant'		=> array('Pick an entrant','Pick an entrant by entering his number or using the list'),
	'PillionFirst'		=> array('Informal name',"Used for repeat mentions on finisher's certificate"),
	'PillionIBA'		=> array('IBA #',"Pillion's IBA number if known"),
	'PillionName'		=> array('Pillion','Full name of the pillion rider'),
	'PointsMults'		=> array('Score value type','Worth points or multipliers'),
	'PointsMults0'		=> array('PointsMults0','Points'),
	'PointsMults1'		=> array('PointsMults1','Multipliers'),
	
	'PreviewCert'		=> array('Preview','What will this certificate look like'),
	'Print1Cert'		=> array('Certificate','Show certificate for this entrant'),
	'Print1ClaimLog'	=> array('Claim log','Show claim log for this entrant'),
	
	// Quick dirty list headings
	'qPlace'			=> array('Rank',''),
	'qName'				=> array('Name',''),
	
	'qMiles'			=> array('Miles',''),						// Miles/Kms
	'qKms'				=> array('Kms',''),							// Miles/Kms
	
	'qPoints'			=> array('Points',''),
	
	// Renumber All Entrants texts
	'raeConfirm'		=> array('Are you sure','Must be checked before submission'),
	'raeFirst'			=> array('Starting number','The first number to be used'),
	'raeOrder'			=> array('In what order','How to sort the entrants for renumbering'),
	'raeRandom'			=> array('Random','Sort randomly'),
	'raeRiderFirst'		=> array('Firstname','Sort on first name'),
	'raeRiderLast'		=> array('Lastname','Sort on surname'),
	'raeSortA'			=> array('Ascending','Sort A-Z, 1-9'),
	'raeSortD'			=> array('Descending','Sort Z-A, 9-1'),
	'raeSubmit'			=> array('Go ahead, Renumber all entrants','Go ahead! Renumber all entrants'),

	'RallyConfigSaved'	=> array('Parameters saved',''),
	'RallyResults'		=> array('Rally&nbsp;results',''),
	'RallySlogan'		=> array('Rally slogan','Brief description of the rally, may be shown on finisher certificates.'),
	'RallyTitle'		=> array('Rally title','Formal title of the rally. May be shown on certificates in full or with an optional part omitted. May be split over several lines. Surround an optional part with [ ]; Use | for newlines. eg: IBA Rally [2020]'),
	
	'RB_nostart'		=> array('No start!','Cannot find a suitable rest bonus start claim'),

	'rcCategories'		=> array('Categories','Schedule of categories used for scoring'),
	
	'RecordSaved'		=> array('Record saved',''),
	
	'RegionalConfig'	=> array('Regional','Regional configuration'),
	
	
	// Used as 'clear' line in claim reject popup menu
	'RejectReason0'		=> array('0=not rejected','Bonus claim is not rejected'),
	
	// These are actually held in the rallyparams table
	'RejectReason1'		=> array('1=Photo missing',''),
	'RejectReason2'		=> array('2=Photo wrong',''),
	'RejectReason3'		=> array('3=Photo unclear',''),
	'RejectReason4'		=> array('4=Out of hours',''),
	'RejectReason5'		=> array('5=Wrong info',''),
	'RejectReason6'		=> array('6=Reason 6',''),
	'RejectReason7'		=> array('7=Reason 7',''),
	'RejectReason8'		=> array('8=Reason 8',''),
	'RejectReason9'		=> array('9=Ask Rallymaster',''),
	
	'RejectReasons'		=> array('RejectReasons','Reasons for bonus claim rejection. These may be customised for this particular rally and appear in the score explanations.'),
	
	'RejectsLit'		=> array('Rejections','Rejected bonus claims'),
	'RenumberGo'		=> array('Go ahead, renumber','Submit the request'),
	
	'RestMinutesLit'	=> array('Rest minutes','The number of minutes of rest/sleep this bonus represents'),
	
	'RiderFirst'		=> array('Informal name',"Used for repeat mentions on finisher's certificate"),
	'RiderIBA'			=> array('IBA #',"Rider's IBA number if known"),
	'RiderName'			=> array('Rider name','The full name of the rider'),
	'ROUseScore'		=> array('ReadOnly','These fields may not be changed here, use Scoring instead'),

	'rp_ebcsettings'	=> array('EBC','EBC settings: These fields control the use of EBCFetch which processes compliant bonus claims from an email account. Any changes here require the system to be restarted before becoming effective.'),
	'rp_settings'		=> array('Settings','Settings: These fields contain rather technical information which fine-tune the behaviour of ScoreMaster. If you know what you\'re doing, have at it otherwise don\'t guess, do consult a grownup.'),
	
	'SaveBonus'			=> array('Update database', 'Save changes'),
	'SaveCertificate'	=> array('Save certificate','Save the updated copy of this certificate'),
	'SaveEntrantRecord' => array('Save entrant details',''),
	'SaveNewCC'			=> array('Update database',''),
	'SaveRallyConfig'	=> array('Update rally configuration parameters',''),
	'SaveRecord'		=> array('Save record','Save record to the database'),
	'SaveScore'			=> array('Save scorecard','Save the updated score/status of this entrant'),
	'SaveSettings'		=> array('Save settings','Save these details to the database'),
	
	'sc_AvgSpeed'		=> array('Speed','Avg speed'),
	'sc_Distance'		=> array('Distance','Distance'),
	'sc_EntrantID'		=> array('Entrant','Entrant'),
	'sc_HiDistance'		=> array('Furthest distance','Highest distance'),
	'sc_HiSpeed'		=> array('Highest average speed','Highest average speed'),
	'sc_MaxBonuses'		=> array('Most ordinary bonuses','Max bonuses'),
	'sc_NoConfirmed'	=> array('Scorecards confirmed','N<sup>o</sup> of scorecards confirmed'),
	'sc_NoEntrants'		=> array('Entrants with status ','Number of entrants with status '),
	'sc_Overview'		=> array('Overview','Overall standings'),
	'sc_Status'			=> array('Status','Status'),
	
	'ScorecardInUse'	=> array('&Otimes;','Scorecard is being updated; right-click to clear the lock (check first!)'),
	'ScorecardIsDirty'	=> array('!!!','Scorecard needs to be updated'),
	'ScoredBy'			=> array('Scored by','Who is (or did) scoring this entrant?'),
	'ScoreNow'			=> array('Score now','Switch to live scoring this entrant(new tab)'),
	'ScoreMethodLit'	=> array('Score method',''),
	'Scorer'			=> array('Scorer','Person doing the scoring'),
	'ScoreSaved'		=> array('Scorecard saved','This screen matches the database, no changes yet'),
	'ScoreThis'			=> array('Score this rider',''),
	'ScoreValue'		=> array('Value','The number of points or multipliers; use commas for variable values starting with MinTicks'),
	'ScorexHints'		=> array('Right-click to reorder; double-click to print',''),
	'ScorexLit'			=> array('ScoreX','Score explanation'),
	'ScoringMethod'		=> array('Scoring method',''),
	'ScoringMethodA'	=> array('Automatic','The system will figure it out'),
	'ScoringMethodC'	=> array('Compound','Bonuses are ticked and points accrued by category'),
	'ScoringMethodM'	=> array('Manual','Entrant scores are entered manually as number of points'),
	'ScoringMethodS'	=> array('Simple','Bonuses are ticked and points added up'),
	
	// Texts for use in setup wizard
	'ScoringMethodWA'	=> array('Fully automatic','The system takes care of scoring method decisions based on your other configuration choices. This is probably the setting you should use.'),
	'ScoringMethodWC'	=> array('Compound scoring','Scoring makes use of categories to modify bonus scores or provide an extra layer of scoring with/without multipliers'),
	'ScoringMethodWM'	=> array('Manual scoring','Scores will be calculated manually by the scorers and entered as a simple points value'),
	'ScoringMethodWS'	=> array('Simple scoring','The rally uses only ordinary bonuses and combination bonuses'),
	'ScoringTab'		=> array('Scoring','Scoring details'),
	'ScoringNow'		=> array('Being scored now','Is this entrant being scored by someone right now?'),
	'SettingsSaved'		=> array('Settings saved','This screen matches the database, no changes yet'),
	'SGroupLit'			=> array('Unique name','Unique group name'),
	'SGroupMaintHead'	=> array('Specials Bonus Groups','Bonuses may be grouped together either to visually separate them or to enable the use of radio buttons rather than checkboxes. Each group must have a unique name.'),
	'SGroupTypeLit'		=> array('Interface','Radio buttons or checkboxes'),
	'SGroupTypeC'		=> array('Checkbox','Checkboxes, multiple choices'),
	'SGroupTypeR'		=> array('Radio','Radio buttons, one choice'),
	'ShowClaimsButton'	=> array('Claims','Show claims of this bonus by entrant'),
	'ShowClaimsCount'	=> array('Claims','Number of claims by entrants'),
	'ShowEntrants'		=> array('Show entrant picklist','Return to entrant picklist'),
	'ShowMembers'		=> array('Show members','Show members'),
	'ShowMultipliers'	=> array('Show multipliers',''),
	'ShowMultipliersA'	=> array('Automatic','Let the system decide'),
	'ShowMultipliersN'	=> array('Hide','Don\'t show multipliers'),
	'ShowMultipliersY'	=> array('Show','Show multipliers scored'),
	'ShowOdoReadings'	=> array('Odos','Click for Odo readings'),
	'SMDesc'			=> array('ScoreMaster description','An application designed to make scoring &amp; administration of IBA style motorcycle rallies easy'),


	// SendNextMail vars
	'snm_Number'		=> array('Number queued:','Number queued:'),
	'snm_Processing'	=> array('Processing mail queue','Processing mail queue'),
	'snm_QEmpty'		=> array('Queue empty, run complete','Queue empty, run complete'),
	'snm_Subject'		=> array('Subject:','Subject:'),

	'SpecialMaintHead'	=> array('Special Bonuses','Special bonuses are used to implement, for example, sleep, call-in or ferry bonuses or arbitrary penalties such as loss of flag. Each special must have its own unique <em>bonusid</em>.'),
	'SpecialMultLit'	=> array('Multipliers','Used in compound bonus calculations'),
	'SpecialPointsLit'	=> array('Points',''),
	'SpecialButton'		=> array('Details','Access all properties'),
	'SpecialsLit'		=> array('Specials','Special bonuses'),
	'SpeedPExplain'		=> array('Penalties for speeding based on average speed. The unit is either MPH or Km/h depending on the rally setting. Only the highest matching speed is applied.',''),
	'spMinSpeedCol'		=> array('Speed','Minimum average speed'),
	'spPenaltyPointsCol'
						=> array('Points','Number of penalty points'),
	'spPenaltyTypeCol'	=> array('Penalty','Type of penalty'),
	'spPenaltyTypeDNF'	=> array('DNF','Penalty applied is DNF'),
	'spPenaltyTypePoints'
						=> array('Points','Penalty points'),
	'StartDate'			=> array('Start date','The first day of the rally. Rally riding day as opposed to must arrive by day'),
	'StartDateE'		=> array('Start date','The first day of rally riding'),
	'StartTime'			=> array('Start time','Official start time. Rally clock starts at this time.'),
	'StartTimeE'		=> array('Start time','Official start time. Rally clock starts at this time.'),
	
	'TeamDefaults'		=> array('Team','alpha,beta,gamma,delta,epsilon,zeta,eta,theta,iota,kappa,lambda,mu,nu,xi,omicron,pi,rho,sigma,tau,upsilon,phi,chi,psi,omega'),
	'TeamID'			=> array('Team #','The team number this Entrant is a member of'),
	'TeamMembers'		=> array('Members of team: ','Members of team : '),
	'TeamRankingC'		=> array('Team cloning','Team scores are cloned to all members'),
	'TeamRankingH'		=> array('Highest ranked member','Rank team as highest member'),
	'TeamRankingI'		=> array('Individual placing','Rank each team member separately'),
	'TeamRankingL'		=> array('Lowest ranked member','Rank team as lowest member'),
	'TeamRankingText'	=> array('Teams are ranked according to',''),
	'TeamExplain'		=> array('Team matching','This attempts to identify entrants potentially riding together, whether declared or not, by finding matching strings of ordinary bonuses. A string must contains at least <em>m</em> matching bonuses separated by no more than <em>g</em> unmatched bonuses. It might also highlight where claims have been omitted or not correctly posted. This is not a conclusive set of matches, just something for scorers to check.'),
	'TeamMaintHead'		=> array('Teams','<br>Teams<br><br>A team is made up of two or more bikes riding together. Team 0 contains all solo (non-team) entrants.'),
	'TeamWatch'			=> array('Team watch','Inspect claims history looking for potential teams/missed claims'),

	'ThemeApplyLit'		=> array('Yes, apply this theme','Yes, apply this theme'),
	'ThemeLit'			=> array('Theme','The name of the theme to apply'),
	
	'tickdelete'		=> array('Tick to delete','Tick to enable deletion'),
	'TiedPointsRanking'	=> array('Split ties by distance travelled','In the event of a tie entrants with shorter journeys will be ranked higher'),	// Miles/Kms
	'TimePExplain'		=> array("Rally time runs from the rally start time to the rally finish time. Individual entrants may have less time available. Penalties other than DNF apply to specific periods within the overall or individual entrant's rally time. Periods are specified as date/time ranges or as minutes before DNF ranges.<br>Time penalties are triggered by entrant check-in time.",'Explanation of rally time penalties'),
	'TimepMaintHead'	=> array('Time Penalties','List of time penalty entries'),

	
	'ToggleScoreX'		=> array('Toggle ScoreX','Click to show/hide score explanation'),
	
	// time penalties
	'tpFactorLit'		=> array('Number','Number of points/mults'),
	'tpFinishLit'		=> array('Finish time','Time this penalty ends'),
	'tpMethod0'			=> array('tpMethod0','Deduct points'),
	'tpMethod1'			=> array('tpMethod1','Deduct multipliers'),
	'tpMethod2'			=> array('tpMethod2','Points per minute'),
	'tpMethod3'			=> array('tpMethod3','Mults per minute'),
	'tpMethodLit'		=> array('Penalty method','Which penalty method applies'),
	'tpStartLit'		=> array('Start time','Time this penalty starts from'),
	'tpTimeSpec0'		=> array('DateTime','Absolute date/time'),
	'tpTimeSpec1'		=> array('Mins &lt; RallyDNF','Minutes before overall rally DNF'),
	'tpTimeSpec2'		=> array('Mins &lt; EntrantDNF','Minutes before individual entrant DNF'),
	'tpTimeSpecLit'		=> array('TimeSpec','Time specification flag'),

	'TotalMults'		=> array('Total multipliers','The number of multipliers applied compiling the total score'),
	'TotalPoints'		=> array('Total points','Final rally score'),

	// Titles for browser tabs
	'ttAbout'			=> array('SM:About',''),
	'ttAdminMenu'		=> array('ScoreMaster','Showing main admin menu'),
	'ttCertificates'	=> array('Certificates',''),
	'ttClaims'			=> array('SM:Claims',''),
	'ttEmails'			=> array('SM:Emails','Send an email to entrants'),
	'ttEntrants'		=> array('SM:Entrants',''),
	'ttFinishers'		=> array('SM:Finishers','Quicklists'),
	'ttImport'			=> array('SM:Import','Importing'),
	'ttMagicWords'		=> array('SM:Magic','Magic words'),
	'ttSanity'			=> array('SM:Sanity','Sanity checks'),
	'ttScoreX'			=> array('ScoreX',''),
	'ttScoring'			=> array('Scoring','Logged on to scoring'),
	'ttSetup'			=> array('SM:Setup','Edit setups'),
	'ttTeams'			=> array('SM:Teams','Potential team matches'),
	'ttUpload'			=> array('SM:Upload','File pick screen'),
	'ttWelcome'			=> array('ScoreMaster','Welcome page for anyone'),
	


	'unset'				=> array('unset, empty, null',''),
	'unused'			=> array('unused',''),
	'UpdateAxis'		=> array('Update these records',''),
	'UpdateBonuses'		=> array('Update bonuses',''),
	'UpdateCategory'	=> array('Update category',''),
	'UpdateCCs'			=> array('Update compound calcs',''),
	'UpdateCombo'		=> array('Update database','Save this record to the database'),
	'UpdateSGroups'		=> array('Update bonus groups',''),
	'UpdateTimeP'		=> array('Update time penalties',''),
	
	'Upload'			=> array('Upload','Upload the file to the server'),
	'UploadBonusesH1'	=> array('Uploading Ordinary Bonuses','Upload Ordinary Bonuses from spreadsheet'),
	'UploadCombosH1'	=> array('Uploading Combo Bonuses','Upload Combo Bonuses from spreadsheet'),
	'UploadEntrantsH1'	=> array('Uploading Entrants','Upload Entrants data from spreadsheet'),
	'UploadForceBonuses'=> array('Force overwrite','Overwrite existing Bonus records'),
	'UploadForceCombos' => array('Force overwrite','Overwrite existing Combo records'),
	'UploadForceEntrants'	=> array('Force overwrite','Overwrite existing Entrant records'),
	'UploadPickFile'	=> array('Pick a file','Please select the input file'),

	'UtlDeleteEntrant'	=> array('Delete entrant','Delete an entrant record from the database'),
	'UtlFindEntrant'	=> array('Find entrant','Search for a particular entrant'),
	'UtlFolderMaker'	=> array('Folder maker','Generate script to make entrant/bonus folders'),
	'UtlRAE'			=> array('Renumber all entrants','Renumber all the entrants, regardless of status'),
	'UtlRenumEntrant'	=> array('Renumber entrant','Assign a new entrant number to an existing entrant'),

	'ValueHdr'			=> array('Value','Value(Points/mults)'),
	'VirtualParams'		=> array('Virtual','Virtual rally params'),
	
	'vr_RallyType'		=> array('Rally type','Is this a real or a virtual rally?'),
	'vr_RallyType0'		=> array('Real rally',''),
	'vr_RallyType1'		=> array('Virtual rally',''),
	'vr_RefuelStops'	=> array('Refuel stops','Comma separated list of ordinary bonuses acting as refuel stops'),
	'vr_TankRange'		=> array('Tank range','Virtual tank range (miles/kilometres)'),
	'vr_StopMins'		=> array('Mins/stop','Number of minutes for each virtual stop'),
	
	'WizNextPage'		=> array('Next','Save and move to the next page of the wizard'),
	'WizPrevPage'		=> array('Previous','Save and return to the previous wizard page'),
	'WizFinish'			=> array('Finish','Save and finish the wizard'),
	
	// This one's different; both entries are pure text blobs, each presented as an HTML paragraph
	'WizFinishText'		=> array('You have now completed the basic setup of the rally. <span style="font-size: 2em;">&#9786;</span>',
									'When you click [Finish] the main rally setup menu is presented and you can<ul><li>enter the details ' .
									'of ordinary &amp; combination bonuses</li><li>alter the text and layout of finisher certificates</li><li>load or enter details ' .
									'of rally entrants</li></ul> and maintain all other aspects of the rally configuration.'),
									
	'WizIsKms'			=> array('KILOMETRES',''),
	'WizIsMiles'		=> array('MILES',''),
	'WizIsReal'			=> array('Proper rally',''),
	'WizIsVirtual'		=> array('Pale imitation',''),
	'WizMaxHours'		=> array('Maximum hours','The maximum time available to individual entrants before becoming DNF. May be less than the number of hours between the rally start and finish times. For example, where a staggered start is used.'),
	'WizMilesKms'		=> array('Miles/Kms','Unit of distance for this rally. All reporting will use this unit. Individual odometers may use either miles or kilometres.'),
	'WizRallyTitle'		=> array('Rally title','What\'s the name of this rally? This will appear on all screens and finisher certificates. It can be refined later if necessary.'),
	'WizRealVirtual'	=> array('Rally type','Is this a real rally, involving actual motorbikes, or a virtual rally involving only computers?'),

	'WizRegion'			=> array('Region','Whereabouts will this rally be run - chooses miles/kms, timezone, etc'),
	'WizStartOption'	=> array('Rally start option','How will the entrants check-outs at the start be organised'),
	'WizStartOption0'	=> array('Single mass start','All entrants start at the official rally start time'),
	'WizStartOption1'	=> array('First claim starts','Each entrant is checked-out on receipt of his first bonus claim'),
	'WizStartOption2'	=> array('Staggered start','Entrants are split into cohorts for starting purposes'),
	'WizStopMins'		=> array('Minutes per stop','The number of minutes stopped at each bonus stop'),
	'WizTiedPoints'		=> array('Split ties by distance travelled','In the event of a tie, entrants can be left tied, or those riding further are ranked lower'),	// Miles/Kms
	'WizTiedPoints0'	=> array('Leave tied','Entrants tied on points are assigned the same rank'),
	'WizTiedPoints1'	=> array('Split','When entrants are tied on points, the one with lower distance travelled will be ranked higher'),
	'WizTitle'			=> array('This rally needs to be configured, please fill in the blanks',''),
	'WizVirtualPage'	=> array('Virtual rally parameters','Virtual rally parameters'),
	'WizTankRange'		=> array('Tank range','The maximum distance travelled before refuelling'),
	
	'xlsHeaders'		=> array('Header rows','Number of rows to skip before data'),
	'xlsImporting'		=> array('Importing','Importing data from spreadsheet'),
	'xlsNoSpecfile'		=> array('!specfile','No "specfile" parameter supplied'),
	'xlsNotEmpty'		=> array('Table already setup!','The table you\'re uploading is not empty, please tick override and retry'),
	
	'ZapDatabaseOffer'	=> array('Zap / Reinitialize Database','Clear the database ready to start from scratch'),
	'ZapDatabaseZapped'	=> array('Database Zapped/Initialized','The database is empty and ready to start from scratch'),
	
						/* Index 1 used as html content of P, pay attention */
	'ZapDBCaution'		=> array('BEWARE!','This will empty the database of all content except certificate templates. The rally database must then be setup from scratch.'),
						/* Index 1 used as default values in database, beware SQL */
	'ZapDBRallySlogan'	=> array('Toughest Motorcycle Rally','Toughest Motorcycle Rally'),
	'ZapDBRallyTitle'	=> array('IBA Motorcycle Rally','IBA Motorcycle Rally'),
	
	'ZapDBGo'			=> array('Go ahead, Zap the lot!','Execute the zap command'),
	
						/* Index 0 is the truth value of the checkbox, Index 1 is the associated question */
	'ZapDBRUSure1'		=> array('yESiMsURE','I am absolutely sure I want to do this'),
	'ZapDBRUSure2'		=> array('ImReallySure','Quite, quite definitely'),
	'ZapDBRUCancel'		=> array('NoIWont','I don\'t really mean this'),
	
	
	'zzzzzzzzzz'		=> array('zzz','dummy to mark end of array')
	);

// This is a list of makes/models of bike used to clean up the values entered by their owners during import
// Each key here uses the letter case shown here, mostly uppercase but could be anything
$KNOWN_BIKE_WORDS = array('BMW','BSA','cc','DCT','DVT','FJR','GS','GSA','GT','GTR','Harley-Davidson','KLE','KTM','LC',$TAGS['ImportBikeTBC'][1],'MV','RS','RT','SE','ST','TVS','VFR','V-Strom','VTR','XC','XRT');


// Full/relative path to database file
$DBFILENAME = 'ScoreMaster.db';

	


?>
