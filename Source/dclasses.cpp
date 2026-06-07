#include "asp.h"

// Master classes (note, a lot of them are defined inline in the class def)

void master::set_secret(char *sval)
{

   if(this->secret!=NULL)
   {
       delete [] this->secret;
   }

   this->secret = new char[strlen(sval)+1];
   strcpy(this->secret,sval);

}

//
// Use this function to see if we should be logging a
// particular request.  Fiorst we check the master
// on off (located at index 0) then we check
// the individual one (provided master is set to TRUE)
//

GBOOL master::log(int index) const
{
   GBOOL retval;

   if(this->logsettings[0]==FALSE)
   {
       retval = FALSE;
   }
   else
   {
       retval = this->logsettings[index];
   }

   return retval;

}
