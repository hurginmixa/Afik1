#ifndef SOCK_H
#define SOCK_H

#include <iostream.h>
#include <stdio.h>
#include <errno.h>
#include <unistd.h>
#include <memory.h>
#include <string.h>
#include <sys/types.h>
#include <sys/socket.h>
#include <arpa/inet.h>
#include <netinet/in.h>
#include <netdb.h>
#include <sys/sysinfo.h>

class sock {
    private :
	int sid;
	int rc;
	
	int constr_name( struct sockaddr_in& addr, const char* hostnm, int port)
	{
	    addr.sin_family = AF_INET;
	    if (!hostnm)
                addr.sin_addr.s_addr = htonl ( INADDR_ANY );
	    else {
		struct hostent *hp = gethostbyname(hostnm);
		if (hp == 0) {
		    perror("gethostbyname");
		    return -1;
		}
		memcpy((char*)&addr.sin_addr, (char*)hp->h_addr, hp->h_length);
	    }
	    addr.sin_port = htons(port);
	    return sizeof(addr);
	};


	int constr_name(struct sockaddr& addr, const char* Pathnm)
	{
	    addr.sa_family = AF_INET;
	    strcpy(addr.sa_data, Pathnm);
	    return sizeof(addr.sa_family) + strlen(Pathnm) + 1;
	}


	const char *ip2name(const struct in_addr in, int needname = 0)
	{
	    u_long laddr;
	    static char IPADDR[1024];
	    struct hostent *hp;

	    strcpy(IPADDR, inet_ntoa(in));
	    laddr = inet_addr(IPADDR);
	    if (laddr == INADDR_NONE) {
		return IPADDR;
	    }

	    hp = gethostbyaddr((char*)&laddr, sizeof(laddr), AF_INET);
	    if (hp == NULL) {
		return IPADDR;
	    }

	    for (char **p = hp->h_addr_list; *p != 0; p++) {
		memcpy((char*)&in, *p, sizeof(in));
	    }

	    if (hp->h_name && needname) {
                strcpy(IPADDR, hp->h_name);
	    }

	    return IPADDR;
	}


   public :

       sock()
       {
	   if ((rc = sid = socket(PF_INET, SOCK_STREAM, 0)) < 0)
	       perror("socket");
       }


       ~sock()
       {
           // shutdown();
	   // rc = close(sid);
       }


       int fd()
       {
	   return sid;
       }


       int good()
       {
	   return sid >= 0 && rc >= 0;
       }


       int bind(const char* name, const int port, const int BACKLOG_NUM = 1) {
	   struct sockaddr_in addr;
	   socklen_t len = constr_name(addr, name, port);
	   if ((rc = ::bind(sid, (struct sockaddr*)&addr, len)) < 0)
	       perror ("bind");

	   if(rc > -1 && (rc = listen(sid, BACKLOG_NUM)) < 0)
	       perror("listen");

	   return rc;
       };


       int accept(char* name = NULL, int* port_p = NULL)
       {
	   if (!name) {
	       rc = ::accept(sid, 0, 0);
	   } else {
	       struct sockaddr_in addr;
	       socklen_t size = sizeof(addr);
	       if ((rc = ::accept(sid, (struct sockaddr*)&addr, &size)) > -1) {
		   if (name) strcpy(name, ip2name(addr.sin_addr));
		   if (port_p) *port_p = ntohs(addr.sin_port);
	       }
	   }

	   return rc;
       }

       int connect (const char* hostnm, int port)
       {
	   struct sockaddr_in addr;
	   socklen_t len = constr_name(addr, hostnm, port);
	   if ((rc = ::connect(sid, (struct sockaddr*)&addr, len)) < 0)
	       perror("connect");

	   return rc;
       }


       int write(const char* buf, int len, int flag = 0)
       {
	   return rc = ::send(sid, buf, len, flag);
       }


       int read(char* buf, int len, int flag = 0)
       {
	   return rc = ::recv(sid, buf, len, flag);
       }


       int isconnected()
       {
	   struct sockaddr_in addr;
	   socklen_t len;
	   if ((rc = ::getpeername(sid, (struct sockaddr*)&addr, &len)) < 0) {
	       if (errno == ENOTCONN) {
		   return 0;
	       }
	       perror ("getpeeraddr");
               return 0;
	   } else {
	       return 1;
	   }
       }


       int shutdown( int mode = 2 )
       {
           return rc = ::shutdown(sid, mode);
       }


       int getport()
       {
	   struct sockaddr_in addr;
	   socklen_t len;
	   if ((rc = ::getsockname(sid, (struct sockaddr*)&addr, &len)) < 0) {
	       perror ("getport");
	       return 0;
	   } else {
	       return addr.sin_port;
	   }
       }


       const char* getaddr()
       {
	   struct sockaddr_in addr;
	   socklen_t len;
	   if ((rc = ::getsockname(sid, (struct sockaddr*)&addr, &len)) < 0) {
	       perror ("getaddr");
               return "";
	   } else {
	       return ip2name(addr.sin_addr);
	   }
       }

       int getpeerport()
       {
	   struct sockaddr_in addr;
	   socklen_t len;
	   if ((rc = ::getpeername(sid, (struct sockaddr*)&addr, &len)) < 0) {
	       perror ("getpeerport");
               return 0;
	   } else {
	       return addr.sin_port;
	   }
       }

       const char* getpeeraddr()
       {
	   struct sockaddr_in addr;
	   socklen_t len;
	   if ((rc = ::getpeername(sid, (struct sockaddr*)&addr, &len)) < 0) {
	       perror ("getpeeraddr");
               return "";
	   } else {
	       return ip2name(addr.sin_addr);
	   }
       }
};

#endif