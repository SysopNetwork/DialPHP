#include "asp.h"

HMCVFILE dialmsg;
master *g_mymaster;
char g_buffer1[1000];
int g_aspstate;
aspchannel *asparray;
char G_SEPERATOR[3];

struct module block={         // module interface block
     "",                      // name used to refer to this module
     NULL,                    // user logon supplemental routine
     aspinput,                // input routine if selected
     aspstatus,               // status-input routine if selected
     NULL,                    // "injoth" routine for this module
     NULL,                    // user logoff supplemental routine
     NULL,                    // hangup (lost carrier) routine
     NULL,                    // midnight cleanup routine
     NULL,                    // delete-account routine
     NULL                     // finish-up (sys shutdown) routine
};

/****************************************************************************/
/* Initialize Module                                                        */
/****************************************************************************/

extern "C" void EXPORT init__dialphp()
{

   char *thesecret;

   //
   // This seperator is used as a way of showing where two pieces of
   // data begin and end.  We can't tokenize all command requests by
   // spaces because some things, for example userid, may have spaces
   // in them
   //

   G_SEPERATOR[0] = 37;
   G_SEPERATOR[1] = 37;
   G_SEPERATOR[2] = 0;

   asparray = (aspchannel *)alczer(nterms*sizeof(aspchannel));

   asplog("-------------------------------------------");
   asplog("DialPHP initializing...");

   dialmsg=opnmsg("DIALPHP.MCV");

   g_mymaster = new master();

   stzcpy(block.descrp,gmdnam("DIALPHP.MDF"),MNMSIZ);
   g_aspstate = register_module(&block);

   thesecret = stgopt(SECRETS);

   if(strlen(thesecret)>40)
   {
       catastro("Secret word max is 40 characters! See SECRETS");
   }

   g_mymaster->set_port(numopt(THEPORT,1,32000));

   init__tcpip();
   regtcpsvr("PHP Authentication",g_mymaster->get_port(),5,asptcp);

   set_logging();

   g_mymaster->set_secret(thesecret);
   free(thesecret);

   g_mymaster->set_timeout(numopt(TOUT,1,600));
   g_mymaster->set_maxconnects(numopt(MAXCON,1,256));

   asplog("Initialization complete.");
   asplog("----------------------------------");

   shocst(PRODUCT,"PHP Authentication TCP server listening on port %d",g_mymaster->get_port());
}

void EXPORT set_logging()
{
   setmbk(dialmsg);

   g_mymaster->set_logging(0,ynopt(LOG000));
   g_mymaster->set_logging(1,ynopt(LOG001));
   g_mymaster->set_logging(2,ynopt(LOG002));
   g_mymaster->set_logging(3,ynopt(LOG003));
   g_mymaster->set_logging(4,ynopt(LOG004));
   g_mymaster->set_logging(5,ynopt(LOG005));
   g_mymaster->set_logging(6,ynopt(LOG006));
   g_mymaster->set_logging(7,ynopt(LOG007));
   g_mymaster->set_logging(8,ynopt(LOG008));
   g_mymaster->set_logging(9,ynopt(LOG009));
   g_mymaster->set_logging(10,ynopt(LOG010));
   g_mymaster->set_logging(11,ynopt(LOG011));
   g_mymaster->set_logging(12,ynopt(LOG012));
   g_mymaster->set_logging(13,ynopt(LOG013));
   g_mymaster->set_logging(14,ynopt(LOG014));
   g_mymaster->set_logging(15,ynopt(LOG015));
   g_mymaster->set_logging(16,ynopt(LOG016));
   g_mymaster->set_logging(17,ynopt(LOG017));
   g_mymaster->set_logging(18,ynopt(LOG018));
   g_mymaster->set_logging(19,ynopt(LOG019));
   g_mymaster->set_logging(20,ynopt(LOG020));
   g_mymaster->set_logging(21,ynopt(LOG021));
   g_mymaster->set_logging(22,ynopt(LOG022));
   g_mymaster->set_logging(23,ynopt(LOG023));
   g_mymaster->set_logging(24,ynopt(LOG024));
   g_mymaster->set_logging(25,ynopt(LOG025));
   g_mymaster->set_logging(26,ynopt(LOG026));
   g_mymaster->set_logging(27,ynopt(LOG027));
   g_mymaster->set_logging(28,ynopt(LOG028));
   g_mymaster->set_logging(29,ynopt(LOG029));
   g_mymaster->set_logging(30,ynopt(LOG030));
   g_mymaster->set_logging(31,ynopt(LOG031));
   g_mymaster->set_logging(32,ynopt(LOG032));
   g_mymaster->set_logging(33,ynopt(LOG033));
   g_mymaster->set_logging(34,ynopt(LOG034));
   g_mymaster->set_logging(35,ynopt(LOG035));
   g_mymaster->set_logging(36,ynopt(LOG036));

}

/*
   Handles initial connections on the registered PORT (probably 600?).

   Reject if too many requests already.

*/

void EXPORT asptcp(int unum)
{

   struct tcpipinf *tip;

   (void)unum;

   if(g_mymaster->get_maxconnects()==g_mymaster->get_connects())
   {
      clrprf();
      setmbk(dialmsg);
      mybyetcp(TOOMANY);
   }
   else
   {

     tip = &tcpipinf[usrnum];

     usrptr->usrcls = BBSPRV;
     usrptr->state = g_aspstate;
     usrptr->substt = SECRETREQUESTED;
     usrptr->flags |= NOGLOB+NOINJO;
     usaptr->ansifl = 0;

     btuech(usrnum,0);

     asparray[usrnum].starttime = hrtval();
     asparray[usrnum].online = TRUE;

     //sktnfy(TNFRECV,clskt,tcpinc,tip,usrnum);
     sktnfy(TNFRECV,clskt,(void (__cdecl *)(void))tcpinc,tip,usrnum);
     sprintf(usaptr->userid,"(%s) ASP",inet_ntoa(tip->inaddr));

     // UCON is not in the MSG, it's defined
     // the two 3's at the end of the string signify we're
     // done transmitting

     clrprf();
     sprintf(g_buffer1,UCON,bturno,245,245);
     send(clskt,g_buffer1,strlen(g_buffer1),0);
     //status = CMDOK;
     btuinj(usrnum,CYCLE);

   }

}

GBOOL EXPORT aspinput()
{
   GBOOL dogoodbye = TRUE;

   if(asparray[usrnum].online!=1)
   {
      return TRUE;
   }

   switch(usrptr->substt)
   {
       case SECRETREQUESTED:

            // If this substate is called on as input, they better
            // have the [correct] secret string in the input buffer or
            // they're outta here!

            rstrin();
            if(!sameas(input,g_mymaster->get_secret()))
            {
               clrprf();
               //asparray[usrnum].online = FALSE;
               setmbk(dialmsg);
               mybyetcp(BSECRET);
            }
            else
            {
               sprintf(g_buffer1,SGOOD,245,245);
               send(clskt,g_buffer1,strlen(g_buffer1),0);
               usrptr->substt = SECRETCORRECT;
            }
            break;
       case SECRETCORRECT:
            // They've gotten this far which means they passed the correct
            // secret string.  If we're at this substate, we should check
            // the input buffer for a request and we should answer it.

            // Nothing in the buffer? Bah, goodbye

            if(margc==0)
            {
               asparray[usrnum].online = FALSE;
               clrprf();
               setmbk(dialmsg);
               mybyetcp(BADVERB);
               dogoodbye = FALSE;
            }
            else if(!command_exists(margv[0]))
            {
               asparray[usrnum].online = FALSE;
               clrprf();
               setmbk(dialmsg);
               mybyetcp(NOSUCHC);
               dogoodbye = FALSE;
            }
            else if(!validate_request())
            {
               asparray[usrnum].online = FALSE;
               clrprf();
               setmbk(dialmsg);
               mybyetcp(BADVERB);
               dogoodbye = FALSE;
            }
            else if(sameas(margv[0],REQUEST_USERIDEXISTS))
            {
                  if(g_mymaster->log(1))
                  {
                    dolog("USERIDEXISTS");
                  }
                  process_request(REQUEST_USERIDEXISTS);
            }
            else if(sameas(margv[0],REQUEST_AUTHUSER))
            {
                  if(g_mymaster->log(2))
                  {
                    dolog("AUTHUSER");
                  }
                  process_request(REQUEST_AUTHUSER);
            }
            else if(sameas(margv[0],REQUEST_GIVEKEY))
            {
                  if(g_mymaster->log(3))
                  {
                    dolog("GIVEKEY");
                  }
                  process_request(REQUEST_GIVEKEY);
            }
            else if(sameas(margv[0],REQUEST_TAKEKEY))
            {
                  if(g_mymaster->log(4))
                  {
                    dolog("TAKEKEY");
                  }
                  process_request(REQUEST_TAKEKEY);
            }
            else if(sameas(margv[0],REQUEST_HASMASTER))
            {
                  if(g_mymaster->log(5))
                  {
                    dolog("HASMASTER");
                  }
                  process_request(REQUEST_HASMASTER);
            }
            else if(sameas(margv[0],REQUEST_ISSUSPENDED))
            {
                  if(g_mymaster->log(6))
                  {
                    dolog("ISSUSPENDED");
                  }
                  process_request(REQUEST_ISSUSPENDED);
            }
            else if(sameas(margv[0],REQUEST_NUMBEROFCREDITS))
            {
                  if(g_mymaster->log(7))
                  {
                    dolog("NUMBEROFCREDITS");
                  }
                  process_request(REQUEST_NUMBEROFCREDITS);
            }
            else if(sameas(margv[0],REQUEST_NUMBEROFDAYS))
            {
                  if(g_mymaster->log(8))
                  {
                    dolog("NUMBEROFDAYS");
                  }
                  process_request(REQUEST_NUMBEROFDAYS);
            }
            else if(sameas(margv[0],REQUEST_PRIMARYCLASS))
            {
                  if(g_mymaster->log(9))
                  {
                    dolog("PRIMARYCLASS");
                  }
                  process_request(REQUEST_PRIMARYCLASS);
            }
            else if(sameas(margv[0],REQUEST_CURRENTCLASS))
            {
                  if(g_mymaster->log(10))
                  {
                    dolog("CURRENTCLASS");
                  }
                  process_request(REQUEST_CURRENTCLASS);
            }
            else if(sameas(margv[0],REQUEST_USERONLINE))
            {
                  if(g_mymaster->log(11))
                  {
                    dolog("USERONLINE");
                  }
                  process_request(REQUEST_USERONLINE);
            }
            else if(sameas(margv[0],REQUEST_LASTLOGIN))
            {
                  if(g_mymaster->log(12))
                  {
                    dolog("LASTLOGIN");
                  }
                  process_request(REQUEST_LASTLOGIN);
            }
            else if(sameas(margv[0],REQUEST_CREATIONDATE))
            {
                  if(g_mymaster->log(13))
                  {
                    dolog("CREATIONDATE");
                  }
                  process_request(REQUEST_CREATIONDATE);
            }
            else if(sameas(margv[0],REQUEST_HASKEY))
            {
                  if(g_mymaster->log(14))
                  {
                    dolog("HASKEY");
                  }
                  process_request(REQUEST_HASKEY);
            }
            else if(sameas(margv[0],REQUEST_GIVECREDITS))
            {
                  if(g_mymaster->log(15))
                  {
                    dolog("GIVECREDITS");
                  }
                  process_request(REQUEST_GIVECREDITS);
            }
            else if(sameas(margv[0],REQUEST_GIVEDAYS))
            {
                  if(g_mymaster->log(16))
                  {
                    dolog("GIVEDAYS");
                  }
                  process_request(REQUEST_GIVEDAYS);
            }
            else if(sameas(margv[0],REQUEST_SYSTEMVARIABLE))
            {
                  if(g_mymaster->log(17))
                  {
                    dolog("SYSTEMVARIABLES");
                  }
                  process_request(REQUEST_SYSTEMVARIABLE);
            }
            else if(sameas(margv[0],REQUEST_SWITCHCLASS))
            {
                  if(g_mymaster->log(18))
                  {
                    dolog("SWITCHCLASS");
                  }
                  process_request(REQUEST_SWITCHCLASS);
            }
            else if(sameas(margv[0],REQUEST_DELETEUSER))
            {
                  if(g_mymaster->log(19))
                  {
                    dolog("DELETEUSER");
                  }
                  process_request(REQUEST_DELETEUSER);
            }
            else if(sameas(margv[0],REQUEST_UNDELETEUSER))
            {
                  if(g_mymaster->log(20))
                  {
                    dolog("UNDELETEUSER");
                  }
                  process_request(REQUEST_UNDELETEUSER);
            }
            else if(sameas(margv[0],REQUEST_SUSPENDUSER))
            {
                  if(g_mymaster->log(21))
                  {
                    dolog("SUSPENDUSER");
                  }
                  process_request(REQUEST_SUSPENDUSER);
            }
            else if(sameas(margv[0],REQUEST_UNSUSPENDUSER))
            {
                  if(g_mymaster->log(22))
                  {
                    dolog("UNSUSPENDUSER");
                  }
                  process_request(REQUEST_UNSUSPENDUSER);
            }
            else if(sameas(margv[0],REQUEST_UPDATEUSERFIELD))
            {
                  if(g_mymaster->log(23))
                  {
                    dolog("UPDATEUSERFIELD");
                  }
                  process_request(REQUEST_UPDATEUSERFIELD);
            }
            else if(sameas(margv[0],REQUEST_AUDITMESSAGE))
            {
                  if(g_mymaster->log(23))
                  {
                    dolog("AUDITMESSAGE");
                  }
                  process_request(REQUEST_AUDITMESSAGE);
            }

            if(dogoodbye==TRUE)
            {
               asparray[usrnum].online = FALSE;
               clrprf();
               setmbk(dialmsg);
               mybyetcp(GOODBYE);
            }

            break;
   }

   return TRUE;
}

void EXPORT aspstatus()
{
   BOOL keepgoing = TRUE;
   long hrts, seconds;

   if(status == RING)
   {
     rstchn();
     return;
   }

   if(status != CYCLE)
   {
     return;
   }

   setmbk(dialmsg);

   if(usrptr->substt<1)
      return;

   hrts = hrtval() - asparray[usrnum].starttime;

   // now hrts has number of ticks

   seconds = hrts/65535L;

   if(seconds>g_mymaster->get_timeout())
   {
       clrprf();
       setmbk(dialmsg);
       mybyetcp(TIMEOUT);
       keepgoing = FALSE;
       asparray[usrnum].online = FALSE;
   }
   else if(usrptr->substt==ASPEND)
   {
      if((btuoba(usrnum)==OUTSIZ-1)&&(tcpipinf[usrnum].outsnk.bufcnt==0))
      {
        byenow(0);
        keepgoing=FALSE;
      }
   }

   if(keepgoing)
   {
       btuinj(usrnum,CYCLE);
   }


}
