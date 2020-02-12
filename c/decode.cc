#include <stdio.h>
#include <ctype.h>
#include <fstream.h>
#include "decode.h"
/* #include "str.h"


string toh(ulong l)
{
    char r[1024];
    int i;
    ulong k;

    //cout << '=' << l << '\n';
    i = 0;
    while ((k = (ulong(1) << i)) <= l) {
	r[i] = (l & k) ? '1' : '0';
        r[i+1] = 0;
	i++;
        // cout << k << ' ' << r << '\n';
    }

    r[i] = 0;
    return string(r);
}
                    */

void Base64::Put(char c)
{
    if (!ReadyPut()) {
        return;
    }

    int k = -1;

    if ((c >= 'A') && (c <= 'Z')) { k = c - 'A'; }
    if ((c >= 'a') && (c <= 'z')) { k = c - 'a' + 26; }
    if ((c >= '0') && (c <= '9')) { k = c - '0' + 52; }
    if (c == '+')                 { k = 62; }
    if (c == '/')                 { k = 63; }

    if (k == -1) {
	return;
    }

    l |= ulong(k) << ((3 - PutCount) * 6);
    PutCount++;

    //cout << c << k << toh(k)  << toh(un.l) << '\n';

    if (PutCount > 3) {
	GetCount = 2;
        //cout << '=' << un.b[2] << un.b[1] << un.b[0] << '\n';
    }
}

char Base64::Get()
{
    if (!ReadyGet()) {
        return 0;
    }

    char r = b[GetCount--];
    if (GetCount < 0) {
	PutCount = 0;
        l = 0;
    }

    return r;
}

void Base64::Flush()
{
    if (ReadyGet()) {
        return;
    }

    if (PutCount == 3) {
	b[0] = b[1];
	b[1] = b[2];
        GetCount = 1;
    }

    if (PutCount == 2) {
	b[0] = b[2];
        GetCount = 0;
    }

    PutCount = 4;
}

void Quoted::Put(char c)
{
    if (!ReadyPut()) {
        return;
    }


    if (b[1] == '=') {
	if (c == 10) {
  	  b[1] = 0;
	  PutCount = 0;
          return;
	}

	if (c == 13) {
          return;
	}

	if (isxdigit(c)) {
	    if (PutCount == 0) {
		b[0] = c;
		PutCount = 1;
                return;
	    } else {
		int k;
		k =  b[0] - ((b[0] >= 'A') ? ('A' - 10) : '0'); k *= 16;
		k += c    - ((c    >= 'A') ? ('A' - 10) : '0');
                c = char(k);
	    }
	}

	b[0] = c;
	GetCount = 0;

	b[1] = 0;
	PutCount = 0;

	return;
    }

    if (c == '=') {
        b[1] = c;
        PutCount = 0;
    } else {
        b[0] = c;
        GetCount = 0;
    }
}

char Quoted::Get()
{
    if (!ReadyGet()) {
        return 0;
    }

    char r = b[GetCount];
    b[GetCount] = 0;
    GetCount--;

    if (GetCount < 0) {
	PutCount = 0;
    }

    return r;
}
