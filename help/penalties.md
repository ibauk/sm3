## Penalties

### Percentage penalty
This is controlled by the *useMagicPenalty* and *valMagicPenalty* settings held in the Rally Parameters. It provides for reducing the value of a good bonus claim by a fixed percentage, typically for minorly inaccurate claims. (Not available in virtual rallies)

### Arbitrary penalties
Penalties can be given for anything the Rallymaster deems appropriate - wrong colour socks for example. Such penalties are implemented simply using special bonuses with negative values.

### Time penalties
The ultimate time penalty is that entrants finishing after the rally finish time (shown in the 'Rally Parameters') are DNF. Point or multiplier deductions can be specified for late arrivals short of the finish time. See also [Rally time](help:rallytime).

Any number of time penalty records can be entered detailing time slots incurring different penalties. Penalties can be a number of points or a number of multipliers, a fixed value or per minute.

### Distance penalties
The ultimate distance penalty is to impose a maximum distance. Entrants exceeding the maximum distance are DNF. Lesser penalties may be incurred being either a fixed number of points or multipliers or a number of points per excess mile/km. It is also possible to specify a minimum distance with entrants not reaching the minimum being DNF.

### Category penalties
Category penalties may be specified using [compound calculation](help:compound) rules either by assigning negative values to the point/multiplier scores of the rule or by specifying a rule resulting in DNF when triggered or not triggered. For example, it is possible to specify that an entrant who fails to score at least one bonus in each of three categories is DNF.

### Speed penalties
Speeding control methods such as live tracking by members of the rally team are outside the scope of the system. ScoreMaster provides for control using average speeds. Average speed is calculated from odo readings taken between check-out and check-in, making allowance for recognised rest periods. The penalty applied will either be DNF or a fixed number of penalty points deducted from the score.

Multiple records are entered to trigger penalties at different speeds. Only one such record will be applied to an entrant’s score and it will be the one with the highest speed equal to or lower than the entrant’s calculated average speed. See also [Speed monitoring](help:speeding)

---
### Fuel penalties
Virtual rallies only. Penalties can be applied in the case of entrants running out of fuel. Such are combined with automatic refuelling.
### Magic penalties
Virtual rallies only. Magic words may be used to ensure that bonus claims are submitted in real time rather than via a scheduler.
