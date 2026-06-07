#include "asp.h"

/*

  Given a specific request, for example UserIDExists, make
  sure that the correct number of parameters are there

*/

GBOOL EXPORT validate_request()
{
   GBOOL retval = TRUE;

   if(sameas(margv[0],REQUEST_USERIDEXISTS))
   {
       retval = validate_useridexists();
   }
   else if(sameas(margv[0],REQUEST_AUTHUSER))
   {
       retval = validate_authuser();
   }
   else if(sameas(margv[0],REQUEST_GIVEKEY))
   {
       retval = validate_givekey();
   }
   else if(sameas(margv[0],REQUEST_TAKEKEY))
   {
       retval = validate_takekey();
   }
   else if(sameas(margv[0],REQUEST_HASMASTER))
   {
       retval = validate_hasmaster();
   }
   else if(sameas(margv[0],REQUEST_ISSUSPENDED))
   {
       retval = validate_issuspended();
   }
   else if(sameas(margv[0],REQUEST_NUMBEROFCREDITS))
   {
       retval = validate_numberofcredits();
   }
   else if(sameas(margv[0],REQUEST_NUMBEROFDAYS))
   {
       retval = validate_numberofdays();
   }
   else if(sameas(margv[0],REQUEST_PRIMARYCLASS))
   {
       retval = validate_primaryclass();
   }
   else if(sameas(margv[0],REQUEST_CURRENTCLASS))
   {
       retval = validate_currentclass();
   }
   else if(sameas(margv[0],REQUEST_USERONLINE))
   {
       retval = validate_useronline();
   }
   else if(sameas(margv[0],REQUEST_LASTLOGIN))
   {
       retval = validate_lastlogin();
   }
   else if(sameas(margv[0],REQUEST_CREATIONDATE))
   {
       retval = validate_lastlogin();
   }
   else if(sameas(margv[0],REQUEST_HASKEY))
   {
       retval = validate_haskey();
   }
   else if(sameas(margv[0],REQUEST_GIVECREDITS))
   {
       retval = validate_givecredits();
   }
   else if(sameas(margv[0],REQUEST_GIVEDAYS))
   {
       retval = validate_givedays();
   }
   else if(sameas(margv[0],REQUEST_SYSTEMVARIABLE))
   {
       retval = validate_systemvariable();
   }
   else if(sameas(margv[0],REQUEST_SWITCHCLASS))
   {
       retval = validate_switchclass();
   }
   else if(sameas(margv[0],REQUEST_DELETEUSER))
   {
       retval = validate_deleteuser();
   }
   else if(sameas(margv[0],REQUEST_UNDELETEUSER))
   {
       retval = validate_undeleteuser();
   }
   else if(sameas(margv[0],REQUEST_UNSUSPENDUSER))
   {
       retval = validate_unsuspenduser();
   }
   else if(sameas(margv[0],REQUEST_SUSPENDUSER))
   {
       retval = validate_suspenduser();
   }
   else if(sameas(margv[0],REQUEST_UPDATEUSERFIELD))
   {
       retval = validate_updateuserfield();
   }
   else if(sameas(margv[0],REQUEST_AUDITMESSAGE))
   {
       retval = validate_auditmessage();
   }

   return retval;

}

GBOOL EXPORT command_exists(char *com)
{
   GBOOL retval = FALSE;

   if(sameas(REQUEST_USERIDEXISTS,com))
   {
       retval = TRUE;
   }
   else if(sameas(REQUEST_AUTHUSER,com))
   {
       retval = TRUE;
   }
   else if(sameas(REQUEST_GIVEKEY,com))
   {
       retval = TRUE;
   }
   else if(sameas(REQUEST_TAKEKEY,com))
   {
       retval = TRUE;
   }
   else if(sameas(REQUEST_HASMASTER,com))
   {
       retval = TRUE;
   }
   else if(sameas(REQUEST_ISSUSPENDED,com))
   {
       retval = TRUE;
   }
   else if(sameas(REQUEST_NUMBEROFCREDITS,com))
   {
       retval = TRUE;
   }
   else if(sameas(REQUEST_NUMBEROFDAYS,com))
   {
       retval = TRUE;
   }
   else if(sameas(REQUEST_PRIMARYCLASS,com))
   {
       retval = TRUE;
   }
   else if(sameas(REQUEST_CURRENTCLASS,com))
   {
       retval = TRUE;
   }
   else if(sameas(REQUEST_USERONLINE,com))
   {
       retval = TRUE;
   }
   else if(sameas(REQUEST_LASTLOGIN,com))
   {
       retval = TRUE;
   }
   else if(sameas(REQUEST_CREATIONDATE,com))
   {
       retval = TRUE;
   }
   else if(sameas(REQUEST_HASKEY,com))
   {
       retval = TRUE;
   }
   else if(sameas(REQUEST_GIVECREDITS,com))
   {
       retval = TRUE;
   }
   else if(sameas(REQUEST_GIVEDAYS,com))
   {
       retval = TRUE;
   }
   else if(sameas(REQUEST_SYSTEMVARIABLE,com))
   {
       retval = TRUE;
   }
   else if(sameas(REQUEST_SWITCHCLASS,com))
   {
       retval = TRUE;
   }
   else if(sameas(REQUEST_DELETEUSER,com))
   {
       retval = TRUE;
   }
   else if(sameas(REQUEST_UNDELETEUSER,com))
   {
       retval = TRUE;
   }
   else if(sameas(REQUEST_SUSPENDUSER,com))
   {
       retval = TRUE;
   }
   else if(sameas(REQUEST_UNSUSPENDUSER,com))
   {
       retval = TRUE;
   }
   else if(sameas(REQUEST_UPDATEUSERFIELD,com))
   {
       retval = TRUE;
   }
   else if(sameas(REQUEST_AUDITMESSAGE,com))
   {
       retval = TRUE;
   }

   return retval;

}
