extern "C" void EXPORT init__dialphp(VOID);
void EXPORT asplog(char *logval);
struct usracc * EXPORT get_user(char *userid);
void EXPORT set_logging(VOID);

GBOOL EXPORT module_allowed(long module_id);

void EXPORT asptcp(int unum);
GBOOL aspinput(VOID);
void EXPORT aspstatus(VOID);
GBOOL EXPORT validate_request(VOID);
GBOOL EXPORT uidexists(char *userid);
int EXPORT process_request(char *req);
void EXPORT crbuf(char *buf);
GBOOL EXPORT command_exists(char *com);
void EXPORT mybyetcp(int msgnum);

int EXPORT process_useridexists(VOID);
int EXPORT process_authuser(VOID);


GBOOL EXPORT validate_useridexists(VOID);
GBOOL EXPORT validate_authuser(VOID);
GBOOL EXPORT validate_givekey();
int EXPORT process_givekey();
int EXPORT process_keytaken();
GBOOL EXPORT validate_takekey();
int EXPORT process_takekey();
int EXPORT process_hasmaster();
GBOOL EXPORT validate_hasmaster();
int EXPORT process_issuspended();
GBOOL EXPORT validate_issuspended();
int EXPORT process_numberofcredits();
GBOOL EXPORT validate_numberofcredits();
int EXPORT process_numberofdays();
GBOOL EXPORT validate_numberofdays();
int EXPORT process_primaryclass();
GBOOL EXPORT validate_primaryclass();
int EXPORT process_currentclass();
GBOOL EXPORT validate_currentclass();
GBOOL EXPORT validate_useronline();
int EXPORT process_useronline();
int EXPORT process_lastlogin();
GBOOL EXPORT validate_lastlogin();
int EXPORT process_creationdate();
GBOOL EXPORT validate_creationdate();
int EXPORT process_haskey();
GBOOL EXPORT validate_haskey();
int EXPORT process_givecredits();
GBOOL EXPORT validate_givecredits();
int EXPORT process_givedays();
GBOOL EXPORT validate_givedays();
GBOOL EXPORT validate_systemvariable();
int EXPORT process_systemvariable();
int EXPORT process_switchclass();
GBOOL EXPORT validate_switchclass();
int EXPORT process_deleteuser();
GBOOL EXPORT validate_deleteuser();
int EXPORT process_undeleteuser();
GBOOL EXPORT validate_undeleteuser();
void update_ustruct(struct usracc *u);

int EXPORT process_suspenduser();
int EXPORT process_unsuspenduser();

GBOOL EXPORT validate_suspenduser();
GBOOL EXPORT validate_unsuspenduser();

int EXPORT process_updateuserfield();
GBOOL EXPORT validate_updateuserfield();
void EXPORT logip();
void EXPORT dolog(char *comname);

int EXPORT process_auditmessage();
GBOOL EXPORT validate_auditmessage();
void EXPORT bye();
int users_online();
