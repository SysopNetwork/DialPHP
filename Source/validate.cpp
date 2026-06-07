#include "asp.h"

GBOOL EXPORT validate_useridexists()
{
   GBOOL retval = TRUE;

   if(margc<2)
   {
       retval = FALSE;
   }

   return retval;
}

GBOOL EXPORT validate_authuser()
{
   GBOOL retval = TRUE;
   char *sep;

   if(margc<2)
   {
       retval = FALSE;
   }
   else
   {
       rstrin();
       sep = strstr(input,G_SEPERATOR);
       if(sep==NULL || *(sep+2)=='\0')
       {
           retval = FALSE;
       }
       parsin();
   }

   return retval;

}

/*
   givekey must have atleast 3 parameters

   GIVEKEY [key] [UserID, might be multiple words]
*/

GBOOL EXPORT validate_givekey()
{
   GBOOL retval = TRUE;

   if(margc<3)
   {
     retval = FALSE;
   }

   return retval;

}

/*
   takekey must have atleast 3 parameters

   TAKEKEY [key] [UserID, might be multiple words]
*/

GBOOL EXPORT validate_takekey()
{
   GBOOL retval = TRUE;

   if(margc<3)
   {
     retval = FALSE;
   }

   return retval;
}

GBOOL EXPORT validate_hasmaster()
{
   GBOOL retval = TRUE;

   if(margc<2)
   {
     retval = FALSE;
   }

   return retval;
}


GBOOL EXPORT validate_issuspended()
{
   GBOOL retval = TRUE;

   if(margc<2)
   {
     retval = FALSE;
   }

   return retval;
}

GBOOL EXPORT validate_numberofcredits()
{
   GBOOL retval = TRUE;

   if(margc<2)
   {
     retval = FALSE;
   }

   return retval;
}

GBOOL EXPORT validate_numberofdays()
{
   GBOOL retval = TRUE;

   if(margc<2)
   {
     retval = FALSE;
   }

   return retval;
}

GBOOL EXPORT validate_primaryclass()
{
   GBOOL retval = TRUE;

   if(margc<2)
   {
     retval = FALSE;
   }

   return retval;
}

GBOOL EXPORT validate_currentclass()
{
   GBOOL retval = TRUE;

   if(margc<2)
   {
     retval = FALSE;
   }

   return retval;
}

GBOOL EXPORT validate_useronline()
{
   GBOOL retval = TRUE;

   if(margc<2)
   {
     retval = FALSE;
   }

   return retval;
}


GBOOL EXPORT validate_lastlogin()
{
   GBOOL retval = TRUE;

   if(margc<2)
   {
     retval = FALSE;
   }

   return retval;
}


GBOOL EXPORT validate_creationdate()
{
   GBOOL retval = TRUE;

   if(margc<2)
   {
     retval = FALSE;
   }

   return retval;
}


/*
   haskey must have atleast 3 parameters

   HASKEY [key] [UserID, might be multiple words]
*/

GBOOL EXPORT validate_haskey()
{
   GBOOL retval = TRUE;

   if(margc<3)
   {
     retval = FALSE;
   }

   return retval;

}


/*
   givecredits must have atleast 3 parameters

   GIVECREDITS [number of credits, +/-] [UserID, might be multiple words]
*/

GBOOL EXPORT validate_givecredits()
{
   GBOOL retval = TRUE;

   if(margc<3)
   {
     retval = FALSE;
   }

   return retval;

}

/*
   givedays must have atleast 3 parameters

   GIVEDAYS [number of days, +/-] [UserID, might be multiple words]
*/

GBOOL EXPORT validate_givedays()
{
   GBOOL retval = TRUE;

   if(margc<3)
   {
     retval = FALSE;
   }

   return retval;

}

/*
   systemvariable must have atleast 2 parameters

   SYSTEMVARIABLE [Which Variable]
*/

GBOOL EXPORT validate_systemvariable()
{
   GBOOL retval = TRUE;

   if(margc<2)
   {
     retval = FALSE;
   }

   return retval;

}

/*
   switchclass must have atleast 3 parameters

   SWITCHCLASS [newclass] [UserID, might be multiple words]
*/

GBOOL EXPORT validate_switchclass()
{
   GBOOL retval = TRUE;

   if(margc<3)
   {
     retval = FALSE;
   }

   return retval;

}

/*
   DELETEUSER must have atleast 2 parameters

   DELETEUSER [UserID, might be multiple words]
*/

GBOOL EXPORT validate_deleteuser()
{
   GBOOL retval = TRUE;

   if(margc<2)
   {
     retval = FALSE;
   }

   return retval;

}


/*
   UNDELETEUSER must have atleast 2 parameters
   UNDELETEUSER [UserID, might be multiple words]
*/

GBOOL EXPORT validate_undeleteuser()
{
   GBOOL retval = TRUE;

   if(margc<2)
   {
     retval = FALSE;
   }

   return retval;

}

/*
   SUSPENDUSER must have atleast 2 parameters
   SUSPENDUSER [UserID, might be multiple words]
*/

GBOOL EXPORT validate_suspenduser()
{
   GBOOL retval = TRUE;

   if(margc<2)
   {
     retval = FALSE;
   }

   return retval;

}

/*
   UNSUSPENDUSER must have atleast 2 parameters
   UNSUSPENDUSER [UserID, might be multiple words]
*/

GBOOL EXPORT validate_unsuspenduser()
{
   GBOOL retval = TRUE;

   if(margc<2)
   {
     retval = FALSE;
   }

   return retval;

}


GBOOL EXPORT validate_updateuserfield()
{
   GBOOL retval = TRUE;

   if(margc<3)
   {
     retval = FALSE;
   }
   else
   {
       rstrin();
       if(strstr(input,G_SEPERATOR)==NULL)
       {
          retval = FALSE;
       }
       parsin();
   }

   return retval;

}

GBOOL EXPORT validate_auditmessage()
{
   GBOOL retval = TRUE;

   if(margc<2)
   {
     retval = FALSE;
   }

   return retval;

}

