This contains the php.ini customised for sm

genphp is a .generate script used to configure PHP sources ready for compilation

On Linux, obtain a copy of the relevant sources from php.net/distributions.
You will probably need to do:-
* sudo apt install libxml-dev
* sudo apt install libcurl4-openssl-dev
* sudo apt install unixodbc unixodbc-dev

You might also need to do something excessive with iODBC

in order to get generate to work its magic.

On a Raspberry Pi, the *make* will take quite some time so don't wait until the last minute.

