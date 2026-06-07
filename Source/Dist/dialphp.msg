LEVEL4 {DialPHP}

   We want to make sure that an incoming REQUEST for
   information doesn't tie up the line indefinately so
   we have a timeout set.  120 seconds is the default.

TOUT {Timeout before closing connection? 120} N 1 600

   The PHP component communicates with the MBBS server
   via a PORT on the MBBS Server.  Ports can be anything from
   1 to 65000 (or so...) however A LOT (and I mean A LOT) of the
   ports are spoken for for some reason or another (even if they're
   not enabled on your machine yet there are specs for them) so you
   shoul take something 30000 or better.  We suggest you leave
   it at the default.

THEPORT {Port to use for MBBS/PHP communication? 3425} N 1 32000

   Specify maximum number of concurrent PHP auth
   requests to the system.  Default is 50.

MAXCON {Maximum concurrent connections? 50} N 1 256

   In order for the .dll to process requests it 
   needs to receive the "Secret String".  This
   prevents just anyone from using your installed
   component.  Well, they can use it but it's not
   going to return any legitimate answer if they
   don't pass the correct string.  Secret password
   is passed as the first argument to each component
   call.

SECRETS {CHANGE THIS KEY} S 40

LOG000 {Logging on? YES} B
LOG001 {Log User Exists requests? YES} B
LOG002 {Log Authenticate User requests? YES} B
LOG003 {Log Give Key requests? YES} B
LOG004 {Log Take Key requests? YES} B
LOG005 {Log HasMaster requets? YES} B
LOG006 {Log IsSuspended requests? YES} B
LOG007 {Log Number credits requests? YES} B
LOG008 {Log number of days requests? YES} B
LOG009 {Log primary (expiry) class requests? YES} B
LOG010 {Log current class requests? YES} B
LOG011 {Log user online requests? YES} B
LOG012 {Log last login requests? YES} B
LOG013 {Log creation date requests? YES} B
LOG014 {Log User has a key requests? YES} B
LOG015 {Log Give credits requests? YES} B
LOG016 {Log Give days requests? YES} B
LOG017 {Log System Variables requests? YES} B
LOG018 {Log switch class requests? YES} B
LOG019 {Log delete user requests? YES} B
LOG020 {Log undelete requests? YES} B
LOG021 {Log suspend user requests? YES} B
LOG022 {Log unsuspend user requests? YES} B
LOG023 {Log update user field request? YES} B
LOG024 {Log Audit Trail print request? YES} B
LOG025 {Currently unused NO} B
LOG026 {Currently unused NO} B
LOG027 {Currently unused NO} B
LOG028 {Currently unused NO} B
LOG029 {Currently unused NO} B
LOG030 {Currently unused NO} B
LOG031 {Currently unused NO} B
LOG032 {Currently unused NO} B
LOG033 {Currently unused NO} B
LOG034 {Currently unused NO} B
LOG035 {Currently unused NO} B
LOG036 {Currently unused NO} B

LEVEL6 {DialPHP}

 DO NOT MODIFY!
 This is a response string in the DialPHP protocol
 and is expected EXACTLY as it is given in the
 default MSG.  If you modify this string, your
 module will not work correctly.

BSECRET {Bad secret, goodbye.õõ
} T Bad secret, goodbye

 DO NOT MODIFY!
 This is a response string in the DialPHP protocol
 and is expected EXACTLY as it is given in the
 default MSG.  If you modify this string, your
 module will not work correctly.

MEXPIRE {Module is expired, goodbye.õõ
} T Module expired, goodbye

 DO NOT MODIFY!
 This is a response string in the DialPHP protocol
 and is expected EXACTLY as it is given in the
 default MSG.  If you modify this string, your
 module will not work correctly.

BADVERB {Malformed request syntax, goodbye.õõ
} T Bad syntax on request

 DO NOT MODIFY!
 This is a response string in the DialPHP protocol
 and is expected EXACTLY as it is given in the
 default MSG.  If you modify this string, your
 module will not work correctly.

TOOMANY {Sorry, already too many requests.  Try again later!õõ
} T Too many, byenow

 DO NOT MODIFY!
 This is a response string in the DialPHP protocol
 and is expected EXACTLY as it is given in the
 default MSG.  If you modify this string, your
 module will not work correctly.

TIMEOUT {Timeout! Closing connection.õõ
} T Timeout, closing connection

 DO NOT MODIFY!
 This is a response string in the DialPHP protocol
 and is expected EXACTLY as it is given in the
 default MSG.  If you modify this string, your
 module will not work correctly.

NOSUCHC {No such command.  Goodbye.õõ
} T No such command

 DO NOT MODIFY!
 This is a response string in the DialPHP protocol
 and is expected EXACTLY as it is given in the
 default MSG.  If you modify this string, your
 module will not work correctly.

GOODBYE {Goodbye.õõ
} T Goodbye!

