#include <stdio.h>
#include <string.h>
#include <ctype.h>
#include "str.h"
#include "xalloc.h"

string::string(const char *s)
{
    lpStr = xstrdup(s);
    dSize = xstrlen(lpStr) + 1;
};


string::string(unsigned int v)
{
    char tmp[255];
    sprintf(tmp, "%d", v);
    lpStr = xstrdup(tmp);
    dSize = xstrlen(lpStr) + 1;
}


string::string(const string& s)
{
    lpStr = xstrdup(s.lpStr);
    dSize = s.dSize;
}


string::operator const char*() const
{
    static const char FILL = 0;

    if (lpStr == NULL) {
        return &FILL;
    }
    return lpStr;
}

string string::substr(unsigned int pos) const
{
    if (lpStr == NULL) {
      return string();
    }

    if (pos >= ::strlen(lpStr)) {
      return string();
    }

    return this->substr(pos, this->strlen() - pos);
}

string string::substr(unsigned int pos, unsigned int len) const
{
    char *r;
    string res;

    if (lpStr == NULL) {
	return string();
    };


    if (pos >= ::strlen(lpStr)) {
	return string();
    }

    if (len == 0) {
	return string();
    }

    if (pos + len > ::strlen(lpStr)) {
    return string(&lpStr[pos]);
    }

    r = (char*)xmalloc(len + 1);
    strncpy(r, &lpStr[pos], len);
    r[len] = 0;
    res = r;
    xfree(r);
    return res;
}


string string::strstr(const string& needle, int CaseCens) const
{
    if ((lpStr == NULL) || (needle.lpStr == NULL)) {
	return string();
    }

    int i = strpos(needle, CaseCens);
    if (i == -1) {
	return string();
    }

    return string(&lpStr[i]);
}


string string::strstrb(const string& needle, int CaseCens) const
{
    if ((lpStr == NULL) || (needle.lpStr == NULL)) {
      return string();
    }

    int i = strpos(needle, CaseCens);
    if (i == -1) {
      return string();
    }

    char *r = &lpStr[i];
    return string(&r[::strlen(needle.lpStr)]);
}


string string::strword(unsigned int pos, char delim) const
{
    if (lpStr == NULL) {
      return string();
    }

    if (pos >= ::strlen(lpStr)) {
      return string();
    }

    char *s = &lpStr[pos]; // begin word
    char *r = s;         // end word;

    while ((*r != 0) && (*r == delim)) {
      r++;
      s++;
    }

    while ((*r != 0) && (*r != delim)) {
      r++;
    }

    return substr(pos, r - s);
}


static int URLSimbol(unsigned char c)
{
    if ((c >= 'A') && (c <= 'Z')) {
      return 1;
    }
    if ((c >= 'a') && (c <= 'z')) {
      return 1;
    }
    if ((c >= '0') && (c <= '9')) {
      return 1;
    }
    if (c == ' ') {
      return 1;
    }
  //  if (c == '\n') {
  //    return 1;
  //  }
  //  if (c == '\r') {
  //    return 1;
  //  }

    return 0;
}

string string::URLEncode()
{
    if (lpStr == NULL) {
      return *this;
    }

    int i, k;
    int C = ::strlen(lpStr);

    for(i=0; lpStr[i]; i++) {
      if (!URLSimbol(lpStr[i])) {
            C+=2;
      }
    }

    char *New = (char*)xmalloc(C + 1);

    i = 0;
    k = 0;
    while(lpStr[i]) {
      if (lpStr[i] == 32) {
	    New[k++] = '+'; i++;
	    continue;
      }
      if (URLSimbol(lpStr[i])) {
        New[k++] = lpStr[i++];
	    continue;
      }
      {
       unsigned int c;
	    New[k++] = '%';

        c=((unsigned char)lpStr[i]) / 16;
	    New[k++] = char(c + (c < 10 ? '0' : ('A' - 10)));

        c=((unsigned char)lpStr[i]) % 16;
	    New[k++] = char(c + (c < 10 ? '0' : ('A' - 10)));

	    i++;
        continue;
      }
    }

    New[k++] = 0;
    return New;
}

void string::SQLSymbReplace()
{
    if (lpStr == NULL) {
      return;
    }

    int dQuotCount;
    char *lpPtr;

    dQuotCount = 0;
    lpPtr = strchr(lpStr, '\'');
    while ( lpPtr != NULL) {
      dQuotCount ++;
      lpPtr = strchr(++lpPtr, '\'');
    }

    lpPtr = (char*)xmalloc(dSize + dQuotCount + 1);

    int i = 0;
    int j = 0;
    while(lpStr[i] != 0) {
      switch(lpStr[i]) {
        case ';'  :
        case '/'  :
        case '\"' :
        case '`'  :
        case ','  :
        case '\\' : lpPtr[j++] = ' ';          break;
        case '\'' : lpPtr[j++] = lpStr[i];
        default   : lpPtr[j++] = lpStr[i];     break;
      }
      i++;
    }
    lpPtr[j++] = 0;

    xfree(lpStr);
    lpStr = xstrdup(lpPtr);
    xfree(lpPtr);
    dSize = xstrlen(lpStr) + 1;
}

void string::LTrimSym(char c)
{
    if (lpStr == NULL) {
	return;
    }

    char *p, *s;

    p = s = lpStr;
    while (*p && *p == c) {
	p++;
    }
    lpStr = xstrdup(p);
    dSize = xstrlen(lpStr) + 1;
    xfree(s);
}

void string::RTrimSym(char c)
{
    if (lpStr == NULL) {
	return;
    }

    char *s;
    int i;

    s = lpStr;
    i = ::strlen(lpStr)-1;
    while (i > 0 && lpStr[i] == c) {
        lpStr[i] = 0;
	i--;
    }
    lpStr = xstrdup(lpStr);
    dSize = xstrlen(lpStr) + 1;
    xfree(s);
}


void string::ToUpper()
{
    char *p;

    if (lpStr == NULL) {
	return;
    }

    p = lpStr;
    while(*p) {
	*p = ::toupper(*p);
        p++;
    }

}


void string::erasenl()
{
    RTrimSym('\n');
    RTrimSym('\r');
}


unsigned int string::strlen() const
{
    if (lpStr == NULL) {
	return 0;
    } else {
    return ::strlen(lpStr);
    }
}


int string::strpos(const string needle, int CaseCens) const
{
    int (*fcomp)(const char *s1, const char *s2, size_t s);
    fcomp = CaseCens == 1 ? ::strncmp : ::strncasecmp;


    if ((lpStr == NULL) || (needle.lpStr == NULL)) {
        return -1;
    }

    int len = ::strlen(needle.lpStr);
    const char *p = lpStr;
    while (len <= ::strlen(p)) {
        if (fcomp(needle.lpStr, p, len) == 0) {
                return p - lpStr;
        }

        p++;
    }

    return -1;
}


long string::aslong() const
{
    if ( ! lpStr ) {
        return 0;
    }

    return atol(lpStr);
}


double string::asfloat() const
{
    if ( ! lpStr ) {
        return 0;
    }

    return atof(lpStr);
}


string& string::operator=(const string& s)
{
    char *tmp;
    tmp = xstrdup(s.lpStr);

    if (lpStr != NULL) {
      xfree(lpStr);
    }

    lpStr = tmp;
    dSize = xstrlen(lpStr) + 1;

    return *this;
}


string& string::operator+=(const string& s)
{
    char *tmp;
    string res;

    if (s.lpStr == NULL) {
      return *this;
    }

    if (lpStr == NULL) {
        lpStr = xstrdup(s.lpStr);
        dSize = xstrlen(lpStr) + 1;
        return *this;
    }

    dSize = ::strlen(lpStr) + ::strlen(s.lpStr) + 1;
    tmp = (char*)xmalloc(dSize);
    strcpy(tmp, lpStr);
    strcat(tmp, s.lpStr);

    xfree(lpStr);
    lpStr = tmp;

    return *this;
}


string& string::operator+=(const char c)
{
    char *TMP;
    if (lpStr == NULL) {
        dSize = 21;
    lpStr = (char*)xmalloc(dSize);
    lpStr[0] = c;
    lpStr[1] = 0;
        return *this;
    }

    if ((::strlen(lpStr) + 1) == dSize) {
    dSize = xstrlen(lpStr) + 21;
    TMP = (char*)xmalloc(dSize);
    strcpy(TMP, lpStr);
    xfree(lpStr);
        lpStr = TMP;
    }

    int l = ::strlen(lpStr);
    lpStr[l] = c;
    lpStr[l+1] = 0;
}


string operator+ (const string s1, const string s2)
{
    string res;

    res += s1;
    res += s2;
    return res;
}


const char string::operator[](int index)
{
    if (index < 0) {
    index += ::strlen(lpStr);
    }

    if ((lpStr != NULL) && (index < ::strlen(lpStr))) {
    return lpStr[index];
    } else {
	return 0;
    }
}

istream& operator >> (istream& is, string& s)
{
    if (s.lpStr != NULL) {
        xfree(s.lpStr);
    }

    char *TMP = (char*)xmalloc(1024 * 10);
    is >> TMP;
    s.lpStr = xstrdup(TMP);
    s.dSize = xstrlen(s.lpStr) + 1;
    xfree(TMP);

    return is;
}

ostream& operator << (ostream& os, string& s)
{
    os << s.lpStr;
    return os;
}

int operator == (const string s1, const string s2)
{
    if ((s1.lpStr == NULL) && (s2.lpStr == NULL)) { return 1; }
    if ((s1.lpStr == NULL) || (s2.lpStr == NULL)) { return 0; }
    return strcmp(s1.lpStr, s2.lpStr) == 0;
}

int operator == (const string s1, const char *s2)
{
    if ((s1.lpStr == NULL) && ((s2 == NULL) || (s2[0] == 0))) { return 1; }
    if ((s1.lpStr == NULL) || ((s2 == NULL) || (s2[0] == 0))) { return 0; }
    return strcmp(s1.lpStr, s2) == 0;
}

int operator == (const char *s1, const string s2)
{
    if (((s1 == NULL) || (s1[0] == 0)) && (s2.lpStr == NULL)) { return 1; }
    if (((s1 == NULL) || (s1[0] == 0)) || (s2.lpStr == NULL)) { return 0; }
    return strcmp(s1, s2.lpStr) == 0;
}

int operator != (const string s1, const string s2)
{
    if ((s1.lpStr == NULL) && (s2.lpStr == NULL)) { return 0; }
    if ((s1.lpStr == NULL) || (s2.lpStr == NULL)) { return 1; }
    return strcmp(s1.lpStr, s2.lpStr) != 0;
}

int operator != (const string s1, const char *s2)
{
    if ((s1.lpStr == NULL) && ((s2 == NULL) || (s2[0] == 0))) { return 0; }
    if ((s1.lpStr == NULL) || ((s2 == NULL) || (s2[0] == 0))) { return 1; }
    return strcmp(s1.lpStr, s2) != 0;
}

int operator != (const char *s1, const string s2)
{
    if (((s1 == NULL) || (s1[0] == 0)) && (s2.lpStr == NULL)) { return 0; }
    if (((s1 == NULL) || (s1[0] == 0)) || (s2.lpStr == NULL)) { return 1; }
    return strcmp(s1, s2.lpStr) != 0;
}


unsigned int strlen(string s)
{
    return s.strlen();
}

string space(int Level)
{
    string s;
    for (int i=0; i<Level; i++) {
        s += " ";
    }
    return s;
}

