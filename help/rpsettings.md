## Settings (rally parameters)

These fields, maintained in JSON format, hold settings used to fine-tune the behaviour of various parts of ScoreMaster.

The fields are generally self-explanatory but if in doubt as to their exact use, either ask a grownup or inspect the source.


### Scoring
*useBonusQuestions* / *valBonusQuestions*
: Control the use of bonus "extra points" questions

*usePercentPenalty* / *valPercentPenalty*
: Specifies the use of percentage penalties

*autoFinisher*
: If true, no formal 'rally complete' process needed; if false, must supply final odo reading

*autoLateDNF*
: If true, any timed odo or claim past time results in DNF

*multStepValue*
: The value of 'step' on HTML INPUT tag for multipliers. Default is '1'. DO NOT USE

*rankPointsPerMile*
: Rank finishers by points per mile(km) rather than simple points value

*bonusReclaims*
: Each bonus may be claimed several times by an entrant and the normal rule is that only the last one submitted is considered. Reclaims are always limited by time constraints and may also be disallowed once a different bonus has been claimed.

0 = reclaims of bonuses are limited only by time. 

1-9 = reclaims of bonuses disallowed after next bonus claimed. The number used is the reject code to be applied. When a non-zero code is used here, all claims with that decision code will be disregarded by the scoring system.

*ignoreClaimDecisionCode*
: Default is '9'. Claims with a Decision of this value will be excluded from scoring. It's used where claims are disallowed (perhaps for being out of time) rather than scored as rejected.

*missingPhotoDecisionCode*
: Default is '1'. Used to preselect the decision for claims received with no photo.

*bonusClaimsLimit*
: It is possible to impose a limit on the number of bonuses claimed with excess claims automatically ignored.

0 = No limit on the number of bonuses claimed. Any other value is the maximum number of allowable claims. This restricts the number of different bonuses claimed, not the number of claims itself (each bonus may be claimed more than once).

### Presentation
*claimsReloadEBC*
: Number of seconds between refreshes of EBC list

*autoAdjustBonusWidth*
: Make all bonus codes occupy the same width on scorecards

*showPicklistStatus*
: Show entrant status and points in scorecard picklist

*decimalsPPM*
: Number of decimal places to show for points per mile(km)

---
### Language

*clgHeader*
: "Schedule of claims received"

*clgClaimsCount*
: "Number of claims received"

*clgBonusCount*
: "Number of bonuses claimed"

*RPT_TPenalty*
: "&#x23F0;Late arrival penalty"

*RPT_MPenalty*
: "Excess distance penalty"

*RPT_SPenalty*
: "Excess speed penalty"

*bonusReclaimNG*
: The text used to indicate that a claim has been rejected because it's out of sequence

*bonusClaimsExceeded*
: The text used to indicate that a claim has been rejected because the claim limit is exceeded.

*distanceLimitExceeded*
: The text used to indicate that a claim has been rejected because excessive distance ridden.

---
### Debug settings
claimsShowPost, claimsAutopostAll, singleuserMode

