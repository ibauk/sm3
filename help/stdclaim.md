# Standard bonus claim format

The convention adopted by IBA UK for all bonus claims is that they consist of a single photo and four data fields 
(Rider number, Bonus code, Odometer reading, Claim time).

As far as the system is concerned. Rider number and Bonus code are essential while the others are optional. [EBC processing](help:ebc) also caters for an optional fifth field containing further, rally dependent, data.

## Rider number

is the unique number assigned to a rally entrant. The system will accept prefixed or postfixed letters as decoration but will otherwise ignore them.

## Bonus code

is the unique identifier for an ordinary or special bonus. Bonus codes consist of uppercase letters, digits and '-'.

## Odometer reading

is the integer value of an odometer reading in miles or kilometres depending on the individual bike.

## Claim time

records the moment at which the bonus claim is submitted. If entered by hand, a simple four digit 24 hour clock reading is enough and
is assumed to be in the same timezone as the rally itself. The claim time can also consist of a standard ISO8601 date/time with timezone info.
