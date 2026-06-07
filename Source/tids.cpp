#include "asp.h"

void EXPORT asplog(char *logval)
{
   char *buff, *t;
   int x, length;
   FILE *fptr;

   if(access("dialphp",0)!=0)
   {
       mkdir("dialphp");
   }

   t = (char *)malloc(_MAX_PATH);
   sprintf(t,".\\dialphp\\%s.log",ncdate(today()));

   length = strlen(t);

   for(x=0;x<length;x++)
   {
       if(t[x]=='/')
       {
         t[x] = '-';
       }
   }

   fptr = fopen(t,"at+");

   free(t);

   if(fptr==NULL)
   {
       return;
   }

   buff = (char *)malloc(10000);

   sprintf(buff,"%s %s - %s\n",ncdate(today()), nctime(now()), logval);

   fprintf(fptr,buff);

   fclose(fptr);

   free(buff);

}

struct usracc * EXPORT get_user(char *userid)
{
    GBOOL foundit;
    int x;
    struct usracc *temp;

    foundit = FALSE;

    for(x=0;x<nterms;x++)
    {
      if(sameas(uacoff(x)->userid,userid))
      {
        foundit = TRUE;
        break; // this x has the struct we need
      }
    }

    temp = (struct usracc *)malloc(sizeof(struct usracc));

    if(foundit==TRUE)
    {
       memcpy(temp,uacoff(x),sizeof(struct usracc));
    }
    else
    {
       dfaSetBlk(accbb);
       if(!dfaAcqEQ(temp,userid,0))
       {
           dfaRstBlk();
           return NULL;
       }
       dfaRstBlk();
    }

    return temp;

}

GBOOL EXPORT module_allowed(long module_id)
{
    (void)module_id;

    return TRUE;
}

/*
   Adds a carriage return  (13?) to the end of each buffer.
*/

void EXPORT crbuf(char *buf)
{

   buf[strlen(buf)] = 13;
   buf[strlen(buf)+1] = 0;

}

/*

   Print something to a user's channel then drop immediately.  Otherwise
   the Server likes to delay before dropping. Not sure why, maybe it
   doesn't really believe that this person is online or something.

   Must call setmbk(dialmsg) before this call or you will get the
   message from someone else's message block

   Update:

   I had byetcp(msgnum) and then imdrop but it turned out that the
   byetcp shit wasn't getting sent before they were dropped.  Now
   I use byendl(...), which by the way has a variable argument list
   allowing for usage like printf or prf(..).  byendl seems to do it
   though!

*/

void EXPORT mybyetcp(int msgnum)
{
   char *mess;
   setmbk(dialmsg);
   mess = getmsg(msgnum);
   send(clskt,mess,strlen(mess),0);
   usrptr->substt = ASPEND;
   //free(mess);
   asparray[usrnum].online = 0;

   rstchn(); // reset channel
}

GBOOL EXPORT uidexists(char *userid)
{
   GBOOL retval;
   struct usracc *acc;

   acc = get_user(userid);

   if(acc==NULL)
   {
       retval = FALSE;
   }
   else
   {
       retval = TRUE;
       free(acc);
   }

   return retval;

}

/*
   When we update some information about a user, we need to save
   it back to disk or (if they're online) copy it to the in memory
   array.
*/

void update_ustruct(struct usracc *u)
{
   int x;

   if(!onsysn(u->userid,1)) // User's not online
   {
     dfaSetBlk(accbb);
     if(dfaAcqEQ(NULL,u->userid,0))
     {
        dfaUpdate(u);
     }
   }
   else
   {
     for(x=0;x<nterms;x++)
     {
       if(sameas(uacoff(x)->userid,u->userid))
       {
         memcpy(uacoff(x),u,sizeof(struct usracc));
         break;
       }
     }
   }

}

void EXPORT logip()
{

  sprintf(g_buffer1,"Incoming PHP Request, IP is %s",inet_ntoa(tcpipinf[usrnum].inaddr));
  asplog(g_buffer1);

}

void EXPORT dolog(char *comname)
{

  logip();
  rstrin();
  sprintf(g_buffer1,"%s request: %s",comname,input);
  asplog(g_buffer1);
  parsin();

}

// count users online

int users_online()
{
   int x, sum;

   sum = 0;

   for(x=0;x<nterms;x++)
   {
     if (usroff(x)->usrcls>BBSPRV)
     {
       if(!(usroff(x)->flags&INVISB))
       {
         sum++;
       }
     }
   }

   return sum;

}
