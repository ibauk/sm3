# Email setup

ScoreMaster's outgoing email facilities are provided by the standard package PHPMailer and configured with a set of JSON settings.

Please note that incoming emails are handled by a separate module, EBCFetch, specified outside of ScoreMaster. ScoreMaster and EBCFetch
can share a single email account or can utilise two different ones.

Most of the settings are self-explanatory but for full details please refer to the [PHPMailer guide](https://blog.mailtrap.io/phpmailer/).
If you don't know what's meant by JSON, don't worry about it but do study the syntax of the existing settings, particularly the punctuation, carefully before messing with them.

## Gmail

Interfacing to a Gmail account here also involves changing some settings in the associated GMail account. 2-factor authentication must be enabled and an '*app password*' must be created. To do that, edit the Google Account settings, [Security].

Check that all is ok before the rally.


## Public hosting

If you're running ScoreMaster on a commercial hosting server, be sure to check that host's requirements for, particularly outgoing, email. Godaddy for example is notoriously picky about what is and is not allowed.