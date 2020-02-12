#include <stdio.h>
#include <stdlib.h>
#include <sys/socket.h>
#include <netinet/in.h>
#include <time.h>
#include "str.h"


class conv {
	private:
		char* szStor;
		string * pcsSrc;
	public:
		conv(string &csSrc, int dSize = 0)
		{
			puts("conv const");
			szStor = new char[dSize + 1];
			strcpy(szStor, csSrc);
			pcsSrc = &csSrc;
		}

		~conv()
		{
			puts ("conv dest");
			puts(szStor);
			*pcsSrc = szStor;
			delete szStor;
		}
		operator char *() { return szStor; }
};

void st(char * mixa)
{
	puts(mixa);
	strcpy(mixa, "nunu");
}

int main()
{
   string s = "mixa's hu;r'g'""in;";
   strcpy(conv(s, 200), "12345");
   puts((conv)s);

   return 1;
}


