# ScoreMaster v3.2

I am delighted to announce the release of ScoreMaster v3.2 incorporating the following significant changes:-

- String builder scoring facility.
- Fast capture of start & finish odo readings as well as odo check readings.
- Optional bonus points percentage deduction facility.
- Improved international language handling.
- Improved help texts.
- Full scorecard rebuild facility.
- Entrant record paging.
- Improved undeclared team detection.
- Complex methods simplification.
- Present side-by-side photos during claim reprocessing.


## String builder

Uninterrupted claiming of a particular category can be used to award extra points. For example, three consecutive farms results in doubling the points awarded for the farm bonuses. The extra points can be either a multiple of the sum of the points scored by the relevant bonuses or a fixed number of extra points. The "uninterrupted" refers to the order of bonus claiming, not the proximity of bonuses within a group. eg. An entrant claims M3, M1, F1, M2: this gives an uninterrupted sequence (US) of two 'M's, not three. Claiming M5, M1, M3, F2 gives a US of three Ms.

## Odo readings

These can now be conveniently and reliably captured at the start and finish of the rally without needing to dig into entrant records. If an odo check is used, those readings can also be captured using the new interface.

## Percentage points deduction
This is typically used to punish sloppy bonus claims, 10% being the usual figure. The percentage used is defined for the rally as a whole rather than for individual or classes of bonus. It always requires the rally team to tick the box manually. Only one such provision can be defined.

## Team detection

A report may be run at any time to identify pairs of bikes riding, declared or not, as a team. It does this by comparing their respective sequences of bonus claims with settable minimum matches and maximum gap length.

## Scorecard rebuild

This will rebuild some or all scorecards by reprocessing the entire claim queue in claimtime order.