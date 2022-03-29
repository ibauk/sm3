# Email setup

ScoreMaster's outgoing email facilities are provided by the standard package PHPMailer and configured with a set of JSON settings.

Please note that incoming emails are handled by a separate module, EBCFetch, specified outside of ScoreMaster. ScoreMaster and EBCFetch
can share a single email account or can utilise two different ones.

Most of the settings are self-explanatory but for full details please refer to the [PHPMailer guide](https://blog.mailtrap.io/phpmailer/).
If you don't know what's meant by JSON, don't worry about it but do study the syntax of the existing settings, particularly the punctuation, carefully before messing with them.

## Gmail

Interfacing to a Gmail account here also involves changing some settings in the associated GMail account. The most important setting is to be found under *Settings* -> *Accounts* -> *Security*. The **Less secure app access** option must be turned on. You should check this setting at the start of each rally as it gets reset by Google after a period of disuse. You must also disable two-step authentication as that is not yet catered for in ScoreMaster.

## Public hosting

If you're running ScoreMaster on a commercial hosting server, be sure to check that host's requirements for, particularly outgoing, email. Godaddy for example is notoriously picky about what is and is not allowed.