#include "windows.h"
#include "gcomm.h"
#include "majorbbs.h"
#include "dialphp.h"
#include "master.h"
#include "protos.h"
#include "macros.h"
#include "fcntl.h"
#include "share.h"
#include "answer.h"
#include "tcpip.h"

#define PRODUCT  "DialPHP v1.0.0"

#define BAD_PASSWORD         9

#define STATUS_ERROR         0
#define PROCESSED            1

extern HMCVFILE dialmsg;
extern master *g_mymaster;
extern char g_buffer1[1000];
extern int g_aspstate;
extern char G_SEPERATOR[3];

typedef struct aspchannel {

   long starttime;
   GBOOL online;
   INT dialsock;

}aspchannel;


extern aspchannel *asparray;

#define ASPEND               4
#define ASPCONNECTED         1
#define SECRETREQUESTED      2
#define SECRETCORRECT        3

#define SGOOD "Secret is good, What is your wish my master?%c%c"
#define UCON  "You're connected to BBSREG # %s.  Please enter your secret string to continue.%c%c"
#define AIS   "Answer is : %s%c%c"

// Request commands

#define REQUEST_USERIDEXISTS       "USERIDEXISTS"
#define REQUEST_AUTHUSER           "AUTHUSER"
#define REQUEST_GIVEKEY            "GIVEKEY"
#define REQUEST_TAKEKEY            "TAKEKEY"
#define REQUEST_HASMASTER          "HASMASTER"
#define REQUEST_ISSUSPENDED        "ISSUSPENDED"
#define REQUEST_NUMBEROFCREDITS    "NUMBEROFCREDITS"
#define REQUEST_NUMBEROFDAYS       "NUMBEROFDAYS"
#define REQUEST_PRIMARYCLASS       "PRIMARYCLASS"
#define REQUEST_CURRENTCLASS       "CURRENTCLASS"
#define REQUEST_USERONLINE         "USERONLINE"
#define REQUEST_LASTLOGIN          "LASTLOGIN"
#define REQUEST_CREATIONDATE       "CREATIONDATE"
#define REQUEST_HASKEY             "HASKEY"
#define REQUEST_GIVECREDITS        "GIVECREDITS"
#define REQUEST_GIVEDAYS           "GIVEDAYS"
#define REQUEST_SYSTEMVARIABLE     "SYSTEMVARIABLE"
#define REQUEST_SWITCHCLASS        "SWITCHCLASS"
#define REQUEST_DELETEUSER         "DELETEUSER"
#define REQUEST_UNDELETEUSER       "UNDELETEUSER"
#define REQUEST_SUSPENDUSER        "SUSPENDUSER"
#define REQUEST_UNSUSPENDUSER      "UNSUSPENDUSER"
#define REQUEST_UPDATEUSERFIELD    "UPDATEUSERFIELD"
#define REQUEST_AUDITMESSAGE       "AUDITMESSAGE"

// Defines that go into AIS things

#define ANSWER_NOSUCHUSER          "No such user"
#define ANSWER_BADPASSWORD         "Password is incorrect"
#define ANSWER_PASSWORDOK          "Password is correct"
#define ANSWER_KEYGIVEN            "Key given"
#define ANSWER_KEYTAKEN            "Key taken"
#define ANSWER_OK                  "Ok"
#define ANSWER_UNABLETOCOMPLY      "Unable to comply"
#define ANSWER_NEGATIVEDAYS        "Action would place user at negative days"
#define ANSWER_NOSUCHVAR           "No such system variable exists"
#define ANSWER_NOSUCHCLASS         "No such class"
#define ANSWER_USERISPROTECTED     "User is protected"
#define ANSWER_USERISDELETED       "User is deleted"
#define ANSWER_USERISUNDELETED     "User is undeleted"
#define ANSWER_USERISSUSPENDED     "User is suspended"
#define ANSWER_USERISUNSUSPENDED   "User is unsuspended"
#define ANSWER_BADFIELD            "Bad field value on update user"
#define ANSWER_USERFIELDUPDATED     "User field updated"
