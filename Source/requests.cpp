#include "asp.h"

/*
   After we've validated a request, we process it here.

   Returns the new usrptr->substt for the current channel.
   This return value is typically 0, indicating that the request
   has been fulfilled (meaning, this routine has passed the
   answer string to the channel's socket and we're done)

   A return value of REQUEST_ERROR (-100) means we had a problem
   processing the request inside of the individual processing
   function.

   It returns new substate in case someday we have to
   process multiple requests or something before disconnecting
   the user.

*/

int EXPORT process_request(char *req)
{
   int retval = 0;

   if(sameas(req,REQUEST_USERIDEXISTS))
   {
       retval = process_useridexists();
   }
   else if(sameas(req,REQUEST_AUTHUSER))
   {
       retval = process_authuser();
   }
   else if(sameas(req,REQUEST_GIVEKEY))
   {
       retval = process_givekey();
   }
   else if(sameas(req,REQUEST_TAKEKEY))
   {
       retval = process_takekey();
   }
   else if(sameas(req,REQUEST_HASMASTER))
   {
       retval = process_hasmaster();
   }
   else if(sameas(req,REQUEST_ISSUSPENDED))
   {
       retval = process_issuspended();
   }
   else if(sameas(req,REQUEST_NUMBEROFCREDITS))
   {
       retval = process_numberofcredits();
   }
   else if(sameas(req,REQUEST_NUMBEROFDAYS))
   {
       retval = process_numberofdays();
   }
   else if(sameas(req,REQUEST_PRIMARYCLASS))
   {
       retval = process_primaryclass();
   }
   else if(sameas(req,REQUEST_CURRENTCLASS))
   {
       retval = process_currentclass();
   }
   else if(sameas(req,REQUEST_USERONLINE))
   {
       retval = process_useronline();
   }
   else if(sameas(req,REQUEST_LASTLOGIN))
   {
       retval = process_lastlogin();
   }
   else if(sameas(req,REQUEST_CREATIONDATE))
   {
       retval = process_creationdate();
   }
   else if(sameas(req,REQUEST_HASKEY))
   {
       retval = process_haskey();
   }
   else if(sameas(req,REQUEST_GIVECREDITS))
   {
       retval = process_givecredits();
   }
   else if(sameas(req,REQUEST_GIVEDAYS))
   {
       retval = process_givedays();
   }
   else if(sameas(req,REQUEST_SYSTEMVARIABLE))
   {
       retval = process_systemvariable();
   }
   else if(sameas(req,REQUEST_SWITCHCLASS))
   {
       retval = process_switchclass();
   }
   else if(sameas(req,REQUEST_DELETEUSER))
   {
       retval = process_deleteuser();
   }
   else if(sameas(req,REQUEST_UNDELETEUSER))
   {
       retval = process_undeleteuser();
   }
   else if(sameas(req,REQUEST_SUSPENDUSER))
   {
       retval = process_suspenduser();
   }
   else if(sameas(req,REQUEST_UNSUSPENDUSER))
   {
       retval = process_unsuspenduser();
   }
   else if(sameas(req,REQUEST_UPDATEUSERFIELD))
   {
       retval = process_updateuserfield();
   }
   else if(sameas(req,REQUEST_AUDITMESSAGE))
   {
       retval = process_auditmessage();
   }

   return retval;

}

int EXPORT process_useridexists()
{
   int x, length, retval=0;

   rstrin();
   length = strlen(input);

   for(x=0;x<length;x++)
   {
     if(input[x] == ' ')
     {
       break;
     }
   }

   if(uidexists(&input[x+1]))
   {
     sprintf(g_buffer1,AIS,"YES",245,245);
   }
   else
   {
     sprintf(g_buffer1,AIS,"NO",245,245);
   }

   clrprf();
    send(clskt,g_buffer1,strlen(g_buffer1),0);

   return retval;


}

/*
   #define ANSWER_NOSUCHUSER  "No such user"
   #define ANSWER_BADPASSWORD "Password is incorrect"
   #define ANSWER_PASSWORDOK  "Password is correct"
*/

int EXPORT process_authuser()
{
   char *buff, *t1, *newdata;
   int l, length, retval=0;
   struct usracc *u;

   l = strlen(margv[0])+1;

   rstrin();

   newdata = strstr(input,G_SEPERATOR);
   *newdata = 0;
   newdata = newdata+2;

   length = strlen(input);

   buff = (char *)malloc(1000);

   u = get_user(&input[l]);


   if(u==NULL)
   {
       sprintf(g_buffer1,AIS,ANSWER_NOSUCHUSER,245,245);
   }
   else {
       //t1 = strstr(&input[x+1],G_SEPERATOR);

       if(newdata==NULL)
       {
          sprintf(g_buffer1,AIS,ANSWER_NOSUCHUSER,245,245);
       }
       else
       {
          if(!sameas(newdata,u->psword))
          {
             sprintf(g_buffer1,AIS,ANSWER_BADPASSWORD,245,245);
          }
          else
          {
             sprintf(g_buffer1,AIS,ANSWER_PASSWORDOK,245,245);
          }
       }
   }

   free(buff);
   clrprf();
   send(clskt,g_buffer1,strlen(g_buffer1),0);

   return retval;

}


/*
   #define ANSWER_NOSUCHUSER  "No such user"
   #define ANSWER_KEYGIVEN    "Key given"
*/

int EXPORT process_givekey()
{
   char *buff;
   int retval=0, l;
   struct usracc *u;

   rstrin();
   parsin();

   buff = (char *)malloc(1000);

   strcpy(buff,margv[1]);

   l = strlen(margv[0])+strlen(margv[1])+2;

   rstrin();

   u = get_user(&input[l]);

   if(u==NULL)
   {
       sprintf(g_buffer1,AIS,ANSWER_NOSUCHUSER,245,245);
   }
   else {
       sprintf(g_buffer1,AIS,ANSWER_KEYGIVEN,245,245);
       givkey(&input[l],buff);
   }

   free(buff);
   clrprf();
   send(clskt,g_buffer1,strlen(g_buffer1),0);

   return retval;

}

/*
   #define ANSWER_NOSUCHUSER  "No such user"
   #define ANSWER_KEYTAKEN    "Key taken"
*/

int EXPORT process_takekey()
{
   char *buff;
   int retval=0, l;
   struct usracc *u;

   rstrin();
   parsin();

   buff = (char *)malloc(1000);

   strcpy(buff,margv[1]);

   l = strlen(margv[0])+strlen(margv[1])+2;

   rstrin();

   u = get_user(&input[l]);

   if(u==NULL)
   {
       sprintf(g_buffer1,AIS,ANSWER_NOSUCHUSER,245,245);
   }
   else {
       sprintf(g_buffer1,AIS,ANSWER_KEYTAKEN,245,245);
       rmvkey(&input[l],buff);
   }

   free(buff);
   clrprf();
   send(clskt,g_buffer1,strlen(g_buffer1),0);

   return retval;

}

/*
   #define ANSWER_NOSUCHUSER  "No such user"
*/

int EXPORT process_hasmaster()
{
   char *buff;
   int retval=0, l;
   struct usracc *u;

   rstrin();
   parsin();

   buff = (char *)malloc(1000);

   strcpy(buff,margv[1]);

   l = strlen(margv[0])+1;

   rstrin();

   u = get_user(&input[l]);

   if(u==NULL)
   {
       sprintf(g_buffer1,AIS,ANSWER_NOSUCHUSER,245,245);
   }
   else {
       if(u->flags&HASMST)
       {
          sprintf(g_buffer1,AIS,"YES",245,245);
       }
       else
       {
          sprintf(g_buffer1,AIS,"NO",245,245);
       }
   }

   free(buff);
   clrprf();
   send(clskt,g_buffer1,strlen(g_buffer1),0);

   return retval;

}

/*
   #define ANSWER_NOSUCHUSER  "No such user"
*/

int EXPORT process_issuspended()
{
   char *buff;
   int retval=0, l;
   struct usracc *u;

   rstrin();
   parsin();

   buff = (char *)malloc(1000);

   strcpy(buff,margv[1]);

   l = strlen(margv[0])+1;

   rstrin();

   u = get_user(&input[l]);

   if(u==NULL)
   {
       sprintf(g_buffer1,AIS,ANSWER_NOSUCHUSER,245,245);
   }
   else {
       if(u->flags&SUSPEN)
       {
          sprintf(g_buffer1,AIS,"YES",245,245);
       }
       else
       {
          sprintf(g_buffer1,AIS,"NO",245,245);
       }
   }

   free(buff);
   clrprf();
   send(clskt,g_buffer1,strlen(g_buffer1),0);

   return retval;

}

/*
   #define ANSWER_NOSUCHUSER  "No such user"
*/

int EXPORT process_numberofcredits()
{
   char *buff;
   int retval=0, l;
   struct usracc *u;

   rstrin();
   parsin();

   buff = (char *)malloc(1000);

   strcpy(buff,margv[1]);

   l = strlen(margv[0])+1;

   rstrin();

   u = get_user(&input[l]);

   if(u==NULL)
   {
       sprintf(g_buffer1,AIS,ANSWER_NOSUCHUSER,245,245);
   }
   else {
       sprintf(g_buffer1,AIS,ltoa(u->creds),245,245);
   }

   free(buff);
   clrprf();
   send(clskt,g_buffer1,strlen(g_buffer1),0);

   return retval;

}

/*
   #define ANSWER_NOSUCHUSER  "No such user"
*/

int EXPORT process_numberofdays()
{
   char *buff;
   int retval=0, l;
   struct usracc *u;

   rstrin();
   parsin();

   buff = (char *)malloc(1000);

   strcpy(buff,margv[1]);

   l = strlen(margv[0])+1;

   rstrin();

   u = get_user(&input[l]);

   if(u==NULL)
   {
       sprintf(g_buffer1,AIS,ANSWER_NOSUCHUSER,245,245);
   }
   else {
       sprintf(g_buffer1,AIS,ltoa(u->daystt),245,245);
   }

   free(buff);
   clrprf();
   send(clskt,g_buffer1,strlen(g_buffer1),0);

   return retval;

}

/*
   #define ANSWER_NOSUCHUSER  "No such user"
*/

int EXPORT process_primaryclass()
{
   char *buff;
   int retval=0, l;
   struct usracc *u;

   rstrin();
   parsin();

   buff = (char *)malloc(1000);

   strcpy(buff,margv[1]);

   l = strlen(margv[0])+1;

   rstrin();

   u = get_user(&input[l]);

   if(u==NULL)
   {
       sprintf(g_buffer1,AIS,ANSWER_NOSUCHUSER,245,245);
   }
   else {
       sprintf(g_buffer1,AIS,u->prmcls,245,245);
   }

   free(buff);
   clrprf();
   send(clskt,g_buffer1,strlen(g_buffer1),0);

   return retval;

}

/*
   #define ANSWER_NOSUCHUSER  "No such user"
*/

int EXPORT process_currentclass()
{
   char *buff;
   int retval=0, l;
   struct usracc *u;

   rstrin();
   parsin();

   buff = (char *)malloc(1000);

   strcpy(buff,margv[1]);

   l = strlen(margv[0])+1;

   rstrin();

   u = get_user(&input[l]);

   if(u==NULL)
   {
       sprintf(g_buffer1,AIS,ANSWER_NOSUCHUSER,245,245);
   }
   else {
       sprintf(g_buffer1,AIS,u->curcls,245,245);
   }

   free(buff);
   clrprf();
   send(clskt,g_buffer1,strlen(g_buffer1),0);

   return retval;

}

/*
   #define ANSWER_NOSUCHUSER  "No such user"
*/

int EXPORT process_useronline()
{
   char *buff;
   int retval=0, l;
   struct usracc *u;

   rstrin();
   parsin();

   buff = (char *)malloc(1000);

   strcpy(buff,margv[1]);

   l = strlen(margv[0])+1;

   rstrin();

   u = get_user(&input[l]);

   if(u==NULL)
   {
       sprintf(g_buffer1,AIS,ANSWER_NOSUCHUSER,245,245);
   }
   else {
       if(onsysn(u->userid,1))
       {
          sprintf(g_buffer1,AIS,"YES",245,245);
       }
       else
       {
          sprintf(g_buffer1,AIS,"NO",245,245);
       }
   }

   free(buff);
   clrprf();
   send(clskt,g_buffer1,strlen(g_buffer1),0);

   return retval;

}

/*
   #define ANSWER_NOSUCHUSER  "No such user"
*/

int EXPORT process_lastlogin()
{
   char *buff;
   int retval=0, l;
   struct usracc *u;

   rstrin();
   parsin();

   buff = (char *)malloc(1000);

   strcpy(buff,margv[1]);

   l = strlen(margv[0])+1;

   rstrin();

   u = get_user(&input[l]);

   if(u==NULL)
   {
       sprintf(g_buffer1,AIS,ANSWER_NOSUCHUSER,245,245);
   }
   else {
       sprintf(g_buffer1,AIS,ncdatel(u->usedat),245,245);
   }

   free(buff);
   clrprf();
   send(clskt,g_buffer1,strlen(g_buffer1),0);

   return retval;

}

/*
   #define ANSWER_NOSUCHUSER  "No such user"
*/

int EXPORT process_creationdate()
{
   char *buff;
   int retval=0, l;
   struct usracc *u;

   rstrin();
   parsin();

   buff = (char *)malloc(1000);

   strcpy(buff,margv[1]);

   l = strlen(margv[0])+1;

   rstrin();

   u = get_user(&input[l]);

   if(u==NULL)
   {
       sprintf(g_buffer1,AIS,ANSWER_NOSUCHUSER,245,245);
   }
   else {
       sprintf(g_buffer1,AIS,ncdatel(u->credat),245,245);
   }

   free(buff);
   clrprf();
   send(clskt,g_buffer1,strlen(g_buffer1),0);

   return retval;

}


/*
   #define ANSWER_NOSUCHUSER  "No such user"
*/

int EXPORT process_haskey()
{
   char *buff;
   int retval=0, l;
   struct usracc *u;

   rstrin();
   parsin();

   buff = (char *)malloc(1000);

   strcpy(buff,margv[1]);

   l = strlen(margv[0])+strlen(margv[1])+2;

   rstrin();

   u = get_user(&input[l]);

   if(u==NULL)
   {
       sprintf(g_buffer1,AIS,ANSWER_NOSUCHUSER,245,245);
   }
   else {
       if(uhskey(&input[l],buff))
       {
           sprintf(g_buffer1,AIS,"YES",245,245);
       }
       else
       {
           sprintf(g_buffer1,AIS,"NO",245,245);
       }
   }

   free(buff);
   clrprf();
   send(clskt,g_buffer1,strlen(g_buffer1),0);

   return retval;

}

/*
   #define ANSWER_NOSUCHUSER  "No such user"
   #define ANSWER_KEYGIVEN    "Key given"
*/

int EXPORT process_givecredits()
{
   char *buff;
   int retval=0, l;
   struct usracc *u;

   rstrin();
   parsin();

   buff = (char *)malloc(1000);

   strcpy(buff,margv[1]);

   l = strlen(margv[0])+strlen(margv[1])+2;

   rstrin();

   u = get_user(&input[l]);

   if(u==NULL)
   {
       sprintf(g_buffer1,AIS,ANSWER_NOSUCHUSER,245,245);
   }
   else {
       addcrd(&input[l],buff,TRUE);
       sprintf(g_buffer1,AIS,ANSWER_OK,245,245);
   }

   free(buff);
   clrprf();
   send(clskt,g_buffer1,strlen(g_buffer1),0);

   return retval;

}


/*
   #define ANSWER_NOSUCHUSER  "No such user"
   #define ANSWER_KEYGIVEN    "Key given"
*/

int EXPORT process_systemvariable()
{
   GBOOL didit=TRUE;
   int retval=0;
   long answer;

   switch(atol(margv[1]))
   {
       case 1: // downloads
            answer = sv.dwnlds;
            break;
       case 2: // uploads
            answer = sv.uplds;
            break;
       case 3: // total messages
            answer = sv.msgtot;
            break;
       case 4: // Highest forum message number used
            answer = sv.hisign;
            break;
       case 5: // total number of accounts
            answer = sv2.numact;
            break;
       case 6: // number of femmes
            answer = sv2.numfem;
            break;
       case 7: // number of males
            answer = sv2.numact - sv2.numfem;
            break;
       case 8: // number corporate users
            answer = sv2.numcor;
            break;
       case 9: // number of ansi users
            answer = sv2.numans;
            break;
       case 10: // credits posted so far
            answer = sv2.paidpst;
            break;
       case 11: // number crdits given away
            answer = sv2.freepst;
            break;
       case 12: // total calls to date
            answer = sv2.totcalls;
            break;
       case 13: // number of users online
            answer = users_online();
            break;
       default:
            didit = FALSE;
            break;
   }

   if(didit==TRUE)
   {
      sprintf(g_buffer1,AIS,ltoa(answer),245,245);
   }
   else
   {
      sprintf(g_buffer1,AIS,ANSWER_NOSUCHVAR,245,245);
   }

   clrprf();
   send(clskt,g_buffer1,strlen(g_buffer1),0);

   return retval;

}


/*
   #define ANSWER_NOSUCHUSER  "No such user"
*/

int EXPORT process_givedays()
{
   char *buff;
   int retval=0, l,days;
   struct usracc *u;
   struct clstab *clsptr;

   rstrin();
   parsin();

   buff = (char *)malloc(1000);

   strcpy(buff,margv[1]);
   days = atol(buff);

   l = strlen(margv[0])+strlen(margv[1])+2;

   rstrin();

   u = get_user(&input[l]);

   if(u==NULL)
   {
       sprintf(g_buffer1,AIS,ANSWER_NOSUCHUSER,245,245);
   }
   else {
       clsptr = fndcls(u->curcls);
       if(clsptr!=NULL)
       {
          swtcls(u,1,u->curcls,3,u->daystt+days);
          sprintf(g_buffer1,AIS,ANSWER_OK,245,245);
       }
       else if(!(clsptr->flags&HASCRD&&clsptr->flags&NOCRED))
       {
          swtcls(u,1,u->curcls,3,u->daystt+days);
          sprintf(g_buffer1,AIS,ANSWER_OK,245,245);
       }
       else
       {
          sprintf(g_buffer1,AIS,ANSWER_UNABLETOCOMPLY,245,245);
       }
   }

   free(buff);
   clrprf();
   send(clskt,g_buffer1,strlen(g_buffer1),0);

   return retval;

}

/*
   #define ANSWER_NOSUCHUSER  "No such user"
   #define ANSWER_NOSUCHCLASS "No such class"
*/

int EXPORT process_switchclass()
{
   char *buff;
   int retval=0, l;
   struct usracc *u;
   struct clstab *clsptr;

   rstrin();
   parsin();

   buff = (char *)malloc(1000);

   strcpy(buff,margv[1]);

   l = strlen(margv[0])+strlen(margv[1])+2;

   rstrin();

   u = get_user(&input[l]);

   if(u==NULL)
   {
       sprintf(g_buffer1,AIS,ANSWER_NOSUCHUSER,245,245);
   }
   else {
       clsptr = fndcls(buff);
       if(clsptr==NULL)
       {
          sprintf(g_buffer1,AIS,ANSWER_NOSUCHCLASS,245,245);
       }
       else if(clsptr!=NULL)
       {
          swtcls(u,1,buff,3,u->daystt);
          sprintf(g_buffer1,AIS,ANSWER_OK,245,245);
       }
       else if(!(clsptr->flags&HASCRD&&clsptr->flags&NOCRED))
       {
          swtcls(u,1,buff,3,u->daystt);
          sprintf(g_buffer1,AIS,ANSWER_OK,245,245);
       }
       else
       {
          sprintf(g_buffer1,AIS,ANSWER_UNABLETOCOMPLY,245,245);
       }
   }

   free(buff);
   clrprf();
   send(clskt,g_buffer1,strlen(g_buffer1),0);

   return retval;

}

/*
   #define ANSWER_NOSUCHUSER  "No such user"
   #define ANSWER_USERPROTECTED "User is protected"
   #define ANSWER_USERISDELETED "User is deleted"
*/

int EXPORT process_deleteuser()
{
   char *buff;
   int retval=0, l;
   struct usracc *u;

   rstrin();
   parsin();

   buff = (char *)malloc(1000);

   strcpy(buff,margv[1]);

   l = strlen(margv[0])+1;

   rstrin();

   u = get_user(&input[l]);

   if(u==NULL)
   {
       sprintf(g_buffer1,AIS,ANSWER_NOSUCHUSER,245,245);
   }
   else {
       if(u->flags&UNDAXS)
       {
         sprintf(g_buffer1,AIS,ANSWER_USERISPROTECTED,245,245);
       }
       else if(u->flags&DELTAG)
       {
         sprintf(g_buffer1,AIS,ANSWER_USERISDELETED,245,245);
         update_ustruct(u);
       }
       else
       {
         u->flags |= DELTAG;
         sprintf(g_buffer1,AIS,ANSWER_USERISDELETED,245,245);
         update_ustruct(u);
       }
   }

   free(buff);
   clrprf();
   send(clskt,g_buffer1,strlen(g_buffer1),0);

   return retval;

}

/*
   #define ANSWER_NOSUCHUSER  "No such user"
   #define ANSWER_USERISUNDELETED "User is undeleted"
*/

int EXPORT process_undeleteuser()
{
   char *buff;
   int retval=0, l;
   struct usracc *u;

   rstrin();
   parsin();

   buff = (char *)malloc(1000);

   strcpy(buff,margv[1]);

   l = strlen(margv[0])+1;

   rstrin();

   u = get_user(&input[l]);

   if(u==NULL)
   {
       sprintf(g_buffer1,AIS,ANSWER_NOSUCHUSER,245,245);
   }
   else {
       if(u->flags&DELTAG)
       {
         u->flags&=~DELTAG;
         sprintf(g_buffer1,AIS,ANSWER_USERISUNDELETED,245,245);
         update_ustruct(u);
       }
       else
       {
         sprintf(g_buffer1,AIS,ANSWER_USERISUNDELETED,245,245);
       }
   }

   free(buff);
   clrprf();
   send(clskt,g_buffer1,strlen(g_buffer1),0);

   return retval;

}


/*
   #define ANSWER_NOSUCHUSER  "No such user"
*/

int EXPORT process_suspenduser()
{
   char *buff;
   int retval=0, l;
   struct usracc *u;

   rstrin();
   parsin();

   buff = (char *)malloc(1000);

   strcpy(buff,margv[1]);

   l = strlen(margv[0])+1;

   rstrin();

   u = get_user(&input[l]);

   if(u==NULL)
   {
       sprintf(g_buffer1,AIS,ANSWER_NOSUCHUSER,245,245);
   }
   else {
       if(u->flags&SUSPEN)
       {
         sprintf(g_buffer1,AIS,ANSWER_USERISSUSPENDED,245,245);
         update_ustruct(u);
       }
       else
       {
         u->flags |= SUSPEN;
         sprintf(g_buffer1,AIS,ANSWER_USERISSUSPENDED,245,245);
         update_ustruct(u);
       }
   }

   free(buff);
   clrprf();
   send(clskt,g_buffer1,strlen(g_buffer1),0);

   return retval;

}

/*
   #define ANSWER_NOSUCHUSER  "No such user"
*/

int EXPORT process_unsuspenduser()
{
   char *buff;
   int retval=0, l;
   struct usracc *u;

   rstrin();
   parsin();

   buff = (char *)malloc(1000);

   strcpy(buff,margv[1]);

   l = strlen(margv[0])+1;

   rstrin();

   u = get_user(&input[l]);

   if(u==NULL)
   {
       sprintf(g_buffer1,AIS,ANSWER_NOSUCHUSER,245,245);
   }
   else {
       if(u->flags&SUSPEN)
       {
         u->flags&=~SUSPEN;
         sprintf(g_buffer1,AIS,ANSWER_USERISUNSUSPENDED,245,245);
         update_ustruct(u);
       }
       else
       {
         sprintf(g_buffer1,AIS,ANSWER_USERISUNSUSPENDED,245,245);
       }
   }

   free(buff);
   clrprf();
   send(clskt,g_buffer1,strlen(g_buffer1),0);

   return retval;

}


/*
   #define ANSWER_NOSUCHUSER  "No such user"
   #define ANSWER_BADFIELD    "Bad field value on update user"
*/

int EXPORT process_updateuserfield()
{
   GBOOL didit = TRUE;
   char *buff, *uid, *newdata;
   int retval=0, field, l;
   struct usracc *u;

   field = atoi(margv[1]);

   l = strlen(margv[0])+strlen(margv[1])+2;

   rstrin();

   newdata = strstr(input,G_SEPERATOR);
   *newdata = 0;
   newdata = newdata+2;

   uid = &input[l];

   buff = (char *)malloc(1000);

   u = get_user(uid);

   if(u==NULL)
   {
       sprintf(g_buffer1,AIS,ANSWER_NOSUCHUSER,245,245);
   }
   else
   {
       switch(field)
       {
               case 1:  // username
                    stzcpy(u->usrnam,newdata,UIDSIZ);
                    break;
               case 2:  // password
                    stzcpy(u->psword,newdata,PSWSIZ);
                    break;
               case 3:  // add line 1
                    stzcpy(u->usrad1,newdata,NADSIZ);
                    break;
               case 4:  // add line 2
                    stzcpy(u->usrad2,newdata,NADSIZ);
                    break;
               case 5:  // add line 3
                    stzcpy(u->usrad3,newdata,NADSIZ);
                    break;
               case 6:  // add line 4
                    stzcpy(u->usrad4,newdata,NADSIZ);
                    break;
               case 7:  // Phone
                    stzcpy(u->usrpho,newdata,PHOSIZ);
                    break;
               case 8:  // system type
                    u->systyp = atoi(newdata);
                    break;
               case 9:  // screen width
                    u->scnwid = atoi(newdata);
                    break;
               case 10: // scnbrk
                    u->scnbrk = atoi(newdata);
                    break;
               case 11: // fse
                    u->scnfse = atoi(newdata);
                    break;
               case 12: // age
                    u->age = atoi(newdata);
                    break;
               case 13: // sex
                    if(sameas(newdata,"Male")||sameas(newdata,"M"))
                    {
                      u->sex = 'M';
                    }
                    else if(sameas(newdata,"Female")||sameas(newdata,"F"))
                    {
                      u->sex = 'F';
                    }
                    break;
               case 14: // classified ads
                    u->csicnt = atoi(newdata);
                    break;
               default:
                    didit = FALSE;
                    break;
       }

       if(didit==TRUE)
       {
          update_ustruct(u);
          sprintf(g_buffer1,AIS,ANSWER_USERFIELDUPDATED,245,245);
       }
       else if (didit==FALSE)
       {
          sprintf(g_buffer1,AIS,ANSWER_BADFIELD,245,245);
       }
   }

   free(buff);
   clrprf();
   send(clskt,g_buffer1,strlen(g_buffer1),0);

   return retval;

}

int EXPORT process_auditmessage()
{
   char *buff;
   int retval=0, l;

   rstrin();
   parsin();

   buff = (char *)malloc(1000);

   strcpy(buff,margv[1]);

   l = strlen(margv[0])+1;

   rstrin();

   shocst(PRODUCT,&input[l]);

   free(buff);
   clrprf();
   send(clskt,g_buffer1,strlen(g_buffer1),0);

   return retval;

}

