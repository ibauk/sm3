# ScoreMaster v3.0 final version

I am delighted to announce this final release of ScoreMaster v3.0 which represents months of work, huge amounts of testing by multiple
'guinea pigs', and some quiet reflection in a darkened room.

If you have played with any of the **v3.0 RC** versions you will be interested in the following significant changes:-

- Database has been upgraded to version 9 incorporating the necessary hooks for rally book photos to be included in the next release.
- Some settings and menus have been rearranged to prioritise 'interesting' items.
- A combo bonus import facility is now included.
- Maintenance of combo's underlying bonuses can now use a simple list format.
- The help system has been updated.
- EBC judging is reformatted to better fit smaller screen sizes.
- Category logic now caters for nine axes throughout.
- Bonus descriptions, entrant names, team names, bike descriptions all now permit the use of quotation marks.
- runsm.exe no longer 'dials home' by default.
- smpatch.exe now explains itself and asks for confirmation before execution.

As part of this release the public github repositories have been reorganised as follows:-

 Component             | GitHub                            | Language
 --------------------- | --------------------------------- | ---------------------
 Main application code | https://github.com/ibauk/sm3      | PHP, CSS, JS, MD, SQL
 Launcher              | https://github.com/ibauk/runsm    | Go 
 EBC processor         | https://github.com/ibauk/ebcfetch | Go
 Installation patcher  | https://github.com/ibauk/smpatch  | Go
 Installation creator  | https://github.com/ibauk/makesm   | Go
 