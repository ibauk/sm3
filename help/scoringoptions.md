## Complex scoring options

This is aimed at Rallymasters & administrators configuring the system to score a particular rally.

### Compound rules by axis/category

Axis rules are applied once basic bonus scoring is complete and provide for more complex scoring strategies.

A rule of type *DNF unless triggered* will appear on score explanations with a tick if it is triggered but will not appear on score explanations at all if it's not triggered, even though that condition will result in DNF. Mostly it would be better to use a *placeholder* and a *DNF if triggered* pair as that will give more satisfying results on score explanations.

### How do I ?

The following gives examples of how to configure a ruleset to achieve certain specific scoring goals.

#### Award extra points for scoring *N* categories within an axis
- One or more rules, each of type *NZ per axis*, *Ordinary scoring rule*. 
- Set *NMin* and *NPower* to the required values.

#### Deduct points for scoring less than *N* categories within an axis
- Set a *placeholder* with an *NMin* value = *N*; 
- Set an *Ordinary scoring rule* with *NMin* = 0 and *NPower* to the **negative** value.

#### Award extra points for scoring *N* bonuses within a category
- One or more rules, each of type *Bonuses per cat*, *Ordinary scoring rule*. 
- Set *NMin* and *NPower* to the required values.

#### Award DNF if not enough categories scored
- Set a *placeholder* with an *NMin* value = *N*; 
- Set a *DNF if triggered rule* with *NMin* = 0

#### Award DNF if too many categories scored
- Set a *DNF is triggered rule* with *NMin* set to the limit