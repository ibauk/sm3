# ScoreMaster v3.3.4

## Claims processing sequence
The claims processing sequence has been updated to use both ClaimTime and LoggedAt. This is to ensure that multiple claims having the same claim time are processed in the correct (or at least consistent) order.

## Bonus/combo/claims analyses
New reports are available providing post rally analyses for bonuses, combos and claims. These are useful for determining overall rally performance.

## Enhanced email parser
The regular expression used to select and parse incoming emails has been updated to permit both '.' and ',' as separating whitespace for bonus claims.

## Combo codes highlighted on scorecards
Combo codes on scorecards surrounded by '[ ]' in order to differentiate from ordinary bonus codes.

## Odo updating enhanced
Odo updating from bonus claims only uses most recent available claim. This avoids updates where claims are being reprocessed out of sequence.

## Team rules includes pillions
During EBC judging, the team rules icon is now shown both for multi-bike teams and for rider/pillion pairs.


## EBCFetch v1.7.5

### Claim resubmission handling
Where an entrant resends claims in bulk in a multi-day rally, the ClaimTime, using the submitted *hhmm*, was being assigned the resubmission date rather than the original date. This mod checks for existing matching claims and uses the original submission date.

### Claims sent from rally address accepted
Any bonus claims sent from the rally email address are accepted as valid claims belonging to the entrant identified by number in the parsed Subject line. This allows rally team members to edit and submit incorrectly excluded claims.