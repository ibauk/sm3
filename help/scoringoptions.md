## Complex scoring options

Set rules can be applied at three levels during scoring: those affecting individual bonus scores; those generating a score in respect of a sequence of bonuses; those generating a score in respect of set membership.

## Individual bonus modification
The points value of an individual bonus can be varied depending on the number of bonuses scored in its set or category. The trigger value *T* can be either the number of categories scored within a set or the number of bonuses scored within a single category of a set.

If the number of bonuses scored so far, including the current one is equal or greater than *T*, the value of the current bonus will either by multiplied by the specified value or be increased by the specified number of points.

Several layers of *T* can be specified with only the most significant achieved being applied.

### Compound rules by set/category

Set rules are applied once basic bonus scoring is complete and provide for more complex scoring strategies.

A rule of type *DNF unless triggered* will appear on score explanations with a tick if it is triggered but will not appear on score explanations at all if it's not triggered, even though that condition will result in DNF. Mostly it would be better to use a *placeholder* and a *DNF if triggered* pair as that will give more satisfying results on score explanations.

### How do I ?

The following gives examples of how to configure a ruleset to achieve certain specific scoring goals.

#### Award extra points for scoring *N* categories within an set
- One or more rules, each of type *NZ per set*, *Affects compound set score*, *Ordinary scoring rule*. 
- Set *NMin* and *NPower* to the required values.

#### Deduct points for scoring less than *N* categories within an set
- Set a *placeholder* with an *NMin* value = *N*; 
- Set an *Ordinary scoring rule* with *NMin* = 0 and *NPower* to the **negative** value.

#### Award extra points for scoring *N* bonuses within a category
- One or more rules, each of type *Bonuses per cat*, *Affects compound set score*, *Ordinary scoring rule*. 
- Set *NMin* and *NPower* to the required values.

#### Award DNF if not enough categories scored
- Set a *placeholder* with an *NMin* value = *N*; 
- Set a *DNF if triggered rule* with *NMin* = 0

#### Award DNF if too many categories scored
- Set a *DNF is triggered rule* with *NMin* set to the limit