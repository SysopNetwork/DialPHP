class master
{

   public:
       // Constructor

       master()
       {
         this->secret = NULL;
         this->numconnects = 0;
       }

       // Setters

       void set_timeout(int val) { this->timeout = val; }
       void set_secret(char *value);
       void set_logging(int index, GBOOL value) {this->logsettings[index] = value; }
       void connect() { this->numconnects++; }
       void disconnect() {this->numconnects--; }
       void set_connects(int value) { this->numconnects = value; }
       void set_maxconnects(int value) { this->maxconnects = value; }
       void set_port(int value) { this->port = value;}

       // Getters

       char * get_secret() const    { return this->secret;  }
       int get_timeout() const { return this->timeout; }
       GBOOL log(int index) const;
       int get_connects() const { return this->numconnects; }
       int get_maxconnects() { return this->maxconnects; }
       int get_port() { return this->port;}

   private:

       char * secret;
       GBOOL logsettings[999];      // log settings
       int timeout;
       int numconnects;
       int maxconnects;
       int port;

};
