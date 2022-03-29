## Combination bonuses

Combination bonuses are scored automatically when their underlying ordinary, special or combination bonuses are scored. Combos are shown separately on the scorecard. Combos can be marked as *Compulsory* and failure to score a compulsory bonus results in DNF.

Each combo specifies a number of underlying bonuses (including other combos). Normally, all listed bonuses must be scored in order to score the combo but it is also possible to specify multiple values depending on the number of underlying bonuses scored. In this case the field *MinTicks* specifies the minimum number of ticks needed to score this combo and the value field holds a comma separated list of the points value \(multipliers if using [compound scoring](help:compound)) applicable, starting with *MinTicks*.

### Example variable combination
Underlying bonus list = 01,02,03,04,05,06  
MinTicks = 3  
Value = 400,500,600,1000

Bonuses ticked | Number ticked | Combo score
---            | :---:         | ---:
01,02          | 2             | no score
01,04,05       | 3             | 400
02,03,05,06    | 4             | 500
01,02,03,04,05,06 | 6          | 1000