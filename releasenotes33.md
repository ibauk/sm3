# ScoreMaster v3.3

I am delighted to announce the release of ScoreMaster v3.3 incorporating the following significant developments:-

- Multi-leg rallies
- Rank by points per mile(km) rather than total points
- Fractional multipliers
- Scorecard reviews
- Rest bonus start/finish handling
- Offline odo capture
- EBC evidence peeking
- Batched ScoreX printing
- Finisher quicklist enhancements
- Automap suppression on import template selection

This is a major release and updates the database schema from 11 to 12.

## Multi-leg rallies
While UK based rallies are all single-leg affairs, longer rallies abroad are often split into two or more legs with entrants having to check in and out at the end of each leg. To some extent each leg can be considered as a standalone rally but the scores all contribute to the overall rally score.

The software has facilities to flag bonuses, combos and penalties as being available either during one specified leg or in all legs. Scorecards are produced for individual legs but finisher certificates reflect the whole rally.

## Rank by Points per mile(km)
Finisher ranking can now be based on points per mile(km) rather than absolute total points value. Many consider this to be the better measure of rallying skill.

## Fractional multipliers
The multiplier logic has been completely overhauled and now caters for fractional multipliers as well as integers. This enables features such as 25% (of total points scored) penalties. It continues to be possible to apply a multiplication factor to total points yielding final scores.

## Scorecard reviews
The transition away from scorecard ticking to individual bonus claiming is now complete and universally adopted. The old tickbox scorecard screen has now been replaced with an all new Scorecard Review facility providing convenient access to score explanations and individual claim history. Full access to individual claims and scorecard recalculation is made available as well as buttons to mark scorecards as "Team is happy" (in other words, reviewed and confirmed) and "Entrant is happy" (final score agreed by entrant).

## Rest bonus start/finish handling
Special rest bonus handling is provided to enable the use of separate start and finish claims rather than a single claim with two receipts. This method of handling is optional and requires the use of special bonus codings within the rally database. Finish claims are matched automatically with start claims enabling rapid validation of claims.

## Offline odo capture
Odo readings and check-out/check-in times can now be captured using offline devices such as iPads. Readings are automatically updated when connection to the database is re-established.

## EBC evidence peeking
When judging EBC claims, convenient access is now provided to supporting evidence such as various timestamps and original uninterpreted Subject line. This evidence is recorded on the claim record and is available for later inspection.

## Batched ScoreX printing
ScoreX sheets can now be printed for all finishers in a single print run, in the same order used for certificate printing.

## Finisher quicklist enhancements
The self-refreshing finisher quicklist can now be presented in order of finish time from most recent to first in, useful for rally teams remotely monitoring finishes.
It also now shows points per mile(km) and, optionally, average speed.

## Automap suppression
When importing entrant, bonus or combo details, fields are automatically mapped using column headers. This automapping is suppressed when choosing a template so that only the prescribed fields are recognised.

