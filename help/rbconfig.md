# Rest bonus configuration

Rest bonuses are claimed using a pair of EBC claims marking the start and finish of a rest period. The claims are submitted in the same way as any other EBC claim with the four fields and a single photo. The claims are subject to some extra handling in the EBC judging screen and this relies on some special coding in the database.

## Start bonus
A rest period is started by submission of a claim for a start bonus. This claim is reusable and worth zero points.

The bonus code itself can be anything but it must belong to a [bonus group](help:bonusgroups) which must be identified in [Rally settings](help:rpsettings) as *restBonusStartGroup=name*. This means that it is possible to have more than one bonus representing the start of a rest period.

## Rest claim bonus
A rest period started as above must be claimed using a rest claim bonus. Multiple bonuses can be specified, each with its own points value, categories, etc and with a specified number of minutes of rest. The minutes can be overridden at claim time by the rally team.

Each bonus must belong to at least one of the bonus groups named in the rally setting *restBonusGroups=name[,name]...*. Each bonus can only be claimed once and each entrant may only claim bonuses from a single bonus group.

## Example configuration
A rally uses a start code of "RB0" and provides for a maximum of seven hours of rest which might be taken as a single period of seven hours (RB7) or two periods of two (RB2) and five (RB5) hours.

Bonus RB0 is included in the group "RBStart". RB7 is included in the group "RBOne". RB2 & RB5 are included in the group "RBTwo".

The rally setting flags are:-

- restBonusStartGroup=RBStart
- restBonusGroups=RBOne,RBTwo

An entrant will be able to claim either RB2 and RB5 or RB7, in each case providing that enough time has elapsed between the RB0 and final claim.

The scoring judge can technically accept bad claims anyway but will have been actively discouraged from doing so. Claims filed directly to the claims log or scorecards are not subject to any vetting.
