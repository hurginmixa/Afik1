#ifndef ___STR_H_
#define ___STR_H_

#include <string.h>
#include <iostream.h>
#include <stdlib.h>
#include "xalloc.h"

class string {
    private :
        char *lpStr;
        int dSize;
    public :
        string() { lpStr = NULL; dSize = 0; };
	string(const char *s);
	string(unsigned int v);
	string(const string& s);
	~string() {
            if (lpStr != NULL)
                xfree(lpStr);
	}

	string& operator=(const string& s);
	string& operator+=(const string& s);
	string& operator+=(const char c);
	string::operator const char*() const;
	const char operator[](int index);
    string substr(unsigned int pos) const;
	string substr(unsigned int pos, unsigned int len) const;
	string strstr(const string& needle, int CaseCens = 1) const;
	string strstrb(const string& needle, int CaseCens = 1) const;
	string strword(unsigned int pos = 0, char delim = ' ') const;
	string URLEncode();
    void SQLSymbReplace();
	unsigned int strlen() const;
	int strpos(const string needle, int CaseCens = 1) const;

	const char* ptr() const;
        long aslong() const;
        double asfloat() const;
	void LTrimSym(char c);
	void RTrimSym(char c);
	void LTrim() { LTrimSym('\t'); LTrimSym(' '); }
	void RTrim() { RTrimSym('\t'); RTrimSym(' '); }
	void Trim() { LTrim(); RTrim(); }
	void erasenl();
	void ToUpper();

	friend istream& operator >> (istream& is, string& s);
	friend ostream& operator << (ostream& os, string& s);
	friend string   operator +  (const string s1, const string s2);
	friend int      operator == (const string s1, const string s2);
	friend int      operator == (const string s1, const char  *s2);
	friend int      operator == (const char  *s1, const string s2);
	friend int      operator != (const string s1, const string s2);
	friend int      operator != (const string s1, const char  *s2);
	friend int      operator != (const char  *s1, const string s2);

};

unsigned int strlen(string s);
string space(int Level);

#endif /* ___STR_H_ */
