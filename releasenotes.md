# ScoreMaster v3.1

I am delighted to announce the release of ScoreMaster v3.1 incorporating the following significant changes:-

- Database has been upgraded to version 10 incorporating full use of bonus images, "waffle" and coordinates.
- EBC fetcher now more tolerant of email content layout resulting in fewer rejects needing manual handling.
- EBC judging now offers side-by-side claim vs rally book image comparison.
- Bonus maintenance now caters for rally book images, waffle and coordinates.

Minor changes include:-

- Pillion names are now shown in lists and claims
- Negative odo readings are prevented
- Odo setting and country are defaulted to rally settings during entrant import
- Bonus claim method defaults to EBC for entrant imports because, let's face it, that is the future

## Rally book images
Each bonus (not combo) can have a single image associated with it. This is used for side-by-side comparison with electronic bonus claim images and is also used by the [RBook rally book generator](https://github.com/ibauk/rbook). The images, in .JPG or .PNG format, must be stored in the **images/bonuses** folder with their filename being stored in the *Image* field on the bonus record.

## Waffle and coordinates
These fields, maintained for bonus records, are used by RBook for rally book production. 

Coordinates used to identify geographical locations may be in any format, they are not used in any way by ScoreMaster.

Waffle holds arbitrary text used for non-scoring purposes by RBook. This differs from the *Notes* field which holds information important for scoring purposes. For example "*The sign must be point at you*" is a requirement to score the points. "*This is the oldest pub in England*" is just information (waffle).