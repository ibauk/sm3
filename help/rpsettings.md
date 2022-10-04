## Settings (rally parameters)

These fields, maintained in JSON format, hold settings used to fine-tune the behaviour of various parts of ScoreMaster.

The fields are generally self-explanatory but if in doubt as to their exact use, either ask a grownup or inspect the source.


### Scoring
useBonusQuestions / valBonusQuestions
: Control the use of bonus "extra points" questions

useMagicPenalty / valMagicPenalty
: Specifies the use of percentage penalties

autoFinisher
: If true, no formal 'rally complete' process needed; if false, must supply final odo reading

autoLateDNF
: If true, any timed odo or claim past time results in DNF

multStepValue
: The value of 'step' on HTML INPUT tag for multipliers. Default is '1'. Use '0.1' or .'0.01' to allow decimals

### Presentation
claimsReloadEBC
: Number of seconds between refreshes of EBC list

autoAdjustBonusWidth
: Make all bonus codes occupy the same width on scorecards

showPicklistStatus
: Show entrant status and points in scorecard picklist

---
### Language

clgHeader
: "Schedule of claims received"

clgClaimsCount
: "Number of claims received"

clgBonusCount
: "Number of bonuses claimed"

RPT_TPenalty
: "&#x23F0;Late arrival penalty"

RPT_MPenalty
: "Excess distance penalty"

RPT_SPenalty
: "Excess speed penalty"

---
### Debug settings
claimsShowPost, claimsAutopostAll, singleuserMode

