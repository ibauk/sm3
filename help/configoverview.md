## Overview of rally configuration
In each of the following, initial rally setup consists of entering the rally title and other basic details, start and finish dates & times and customising finisher certificates. In most cases, entrant details will be simply imported from a spreadsheet. Ordinary bonus details may also be imported from a spreadsheet.
### Simplest rally
This rally consists of a large number of ordinary bonuses. Entrants visit whichever they fancy and accrue points accordingly. There are no penalties for excessive speed, excess mileage or late finish, no compulsory bonuses or minimum miles.

Configuration is very simple: enter the bonus details, import or enter the entrant details. 

### Regular rally
In addition to ordinary bonuses, this rally includes several fixed combinations, compulsory bonuses, call-in bonuses and penalties for late arrival at rally HQ.

Ordinary bonus setup needs to flag some entries as being compulsory. Combinations need to be setup with a list of their underlying bonuses. Specials need to be setup for each call-in bonus. Speed and/or time penalty records need to be setup.

### Bigger rally
This has a very large number of ordinary bonuses, including several compulsory ones. Each bonus belongs to an activity category (swimming, ski jumping, horse riding, etc), visiting multiple bonuses in certain categories scores additional points. There are extra bonuses available for ferry crossings (500 points per 30 minutes up to 4 hours), sleep (1-8 hours). One compulsory call-in bonus and penalties of 50 points per minute in the last hour of the rally.

Category records need to be setup for each scoring category (non-scoring categories don’t need to be setup). Ordinary bonuses need to be setup with their associated activity category and flagged as compulsory or not. Two special groups need to be setup for ferry and sleep bonuses. A further special representing the call-in bonus needs to be marked compulsory. Speed and time penalty records need to be setup. Category calculation records need to be setup for each scoring category.

### Complex rally
This rally has everything included in “Bigger rally” above but also includes the following:-

“Bonuses belong to one of five categories. Scoring four bonuses in a row from four different categories earns triple the listed value of the fourth bonus in that string of four. Any individual bonus will only apply to one string of four. The string counter resets upon completion of each string of four. In order to earn the string multiplier, riders must visit and claim the four bonuses sequentially without visiting or claiming a bonus of the same category as any other bonus in the string.”

Setup is the same as for “Bigger rally” but a final special needs to be setup catering for the rule specified above. It must be flagged as having a variable points value.

### Impossible rally
The Rallymaster for this one has such a devious and convoluted mind that only custom software could cater for it. Setup for this rally is easy, just set the scoring method to ‘manual’.

Scoring is a matter of entering the entrant’s score and setting his finisher status. When all entrants scored, click [Print Finisher Certificates].

### Virtual rally
Virtual rallies are run using computers only, no motorbikes. Their purpose is to practise route planning, learn about new places and while away some time.

The setup is similar to the “Simplest rally” but with the additional steps of choosing a tank range (a distance in miles/kilometres), a stopped time in minutes for bonus stops, deciding whether to use “magic words” to control bonus claiming and choosing penalties for speeding, fuel outages or magic word infringements.
