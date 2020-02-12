#ifndef ___COMSTR_H
#define ___COMSTR_H

/*

  example
  ComStr cs(argc, argv, CP('m') + CP('d', 0, "delete"));

  parameter argc, argv - variable what in function main;
  list definitions
    1 parameter char of parameter for use in commandline -d
    2 kind of parameter (options)
      0 - without argument
      1 - need argument
      2 - posible argument
    3 name of parameter for use in commandline --delete (options)


*/


#include <iostream.h>
#include <getopt.h>
#include <stdarg.h>
#include <vector.h>
#include "xalloc.h"


class ComStr;
class CPList;
class CP;


void s(struct option *s)
{
    while(s->val != 0) {
        cout << s->val << "<>" << (int)s->name  << "<>" << s->name << "\n";
        s++;
    }
}

class CPList {
    friend class ComStr;
    private :
      vector<CP> v;
    public :
      CPList& operator+ (CP arg);
      size_t size() const;
};


class ComStr {
    private:
      vector<CP> v;
      vector<const char*> nv;
      int errors;

    public:
      ComStr(int , char * const [], CPList list = CPList());
      CP opt(unsigned const int);
      CP opt(const char* const str);
      int ParamCount();
      const char* const Param(unsigned int);
      int IsError() { return errors; };
      const char* const Program;
};


class CP {
    friend class ComStr;
    private :
      int set;
      char ch;
      int has_arg;
      char* name;

      char *optarg;
      char **savarg;
      void copy(const CP& cs);
    public :
      CP(char, int = 0, const char* = NULL, char** = NULL);
      CP(const CP& cs);
      CP& operator=(const CP& cs);
      ~CP();
      int IsSet() { return set; };
      const char* const arg() { return optarg; };
      const int argnum() { return atoi(optarg); };
      friend CPList operator+(const CP a1, const CP a2);
      operator CPList();
};


ComStr::ComStr(int ac, char * const av[], CPList list) : errors(0), Program(av[0]), v(), nv()
{
    int i;
    int c;

    vector<char> opt;
    int longoptnum = 0;
    struct option *longopt, *longopt_list;

    longopt_list = (struct option*)malloc((list.v.size() + 1) * sizeof(struct option));
    longopt = longopt_list;

    // cout << list.v.size() << "\n";
    for(i = 0; i < list.v.size(); i++) {
		v.insert(v.end(), list.v[i]);

	    longoptnum++;

		if (v[v.size() - 1].ch == 0) {
	            v[v.size() - 1].ch = longoptnum;
		} else {
		    opt.insert(opt.end(), v[v.size()-1].ch);
		    if (v[v.size() - 1].has_arg == 1) {
			opt.insert(opt.end(), ':');
		    }
		}

		if (v[v.size() - 1].name) {
	  	  longopt->name    = xstrdup(v[v.size() - 1].name);
		  longopt->has_arg = v[v.size() - 1].has_arg;
		  longopt->flag    = NULL;
		  longopt->val     = v[v.size() - 1].ch;

	          // cout << (int)longopt->name << longopt->name << "\n";

		  longopt++;
	  	}
    }

    opt.insert(opt.end(), 0);

    char optstring[128];
    for(i = 0; i < opt.size(); i++)
	{
	  optstring[i] = opt[i];
    }

    longopt->name    = NULL;
    longopt->has_arg = 0;
    longopt->flag    = NULL;
    longopt->val     = 0;
    // s(longopt_list);


    //char sss = opt.front();

    // parsing parameters
    while ((c = getopt_long(ac, av, optstring, longopt_list, NULL)) != EOF)
	{
		for(i = 0; i < v.size(); i++)
		{
		    if (v[i].ch == c)
			{
				// cout << i << '\n';
		        v[i].set = 1;
				v[i].optarg=xstrdup(optarg);
				if (v[i].savarg)
				{
					*v[i].savarg = xstrdup(optarg);
				}
				break;
		    }
		}

		if (i == v.size())
		{
		    errors = 1;
		}
    }

    // parsing non-option parameter
    while (optind < ac) {
        nv.insert(nv.end(), av[optind++]);
    }

    free(longopt_list);
}


CP ComStr::opt(unsigned int const ch)
{
    CP Empty(0);
    int i;

    for(i=0; i < v.size(); i++) {
	if (v[i].ch == ch) {
	    return v[i];
	}
    }

    return Empty;
}

CP ComStr::opt(const char* const str)
{
    CP Empty(0);
    int i;

    if (! str) {
		return Empty;
    }

    for(i=0; i < v.size(); i++) {
	if ((v[i].name) && (!strcmp(v[i].name, str))) {
	    return v[i];
	}
    }

    return Empty;
}


int ComStr::ParamCount()
{
    return nv.size();
}


const char* const ComStr::Param(unsigned int i)
{
    if (i == 0) {
	return Program;
    }


    if (i > ParamCount()) {
	return NULL;
    } else {
	return nv[i - 1];
    }
}


CPList& CPList::operator+( CP arg )
{
    v.insert(v.end(), arg);
    return *this;
}

size_t CPList::size() const
{
    return this->v.size();
}

CP::CP(char p1, int p2, const char* p3, char** p4) : ch(p1), has_arg(p2), set(0), optarg(NULL), savarg(p4)
{
    // cout << "cr\n";
    name = xstrdup(p3);
}


CP::CP(const CP& cs)
{
    // cout << "copy\n";
    copy(cs);
}

CP::~CP()
{
    // cout << "de\n";
    if (name   != NULL) free(name);
    if (optarg != NULL) free(optarg);
}


CP& CP::operator=(const CP& cs)
{
    if (name   != NULL) free(name);
    if (optarg != NULL) free(optarg);

    copy(cs);

    return *this;
}


void CP::copy(const CP& cs)
{
    // cout << "copy\n";
    set     = cs.set;
    name    = xstrdup(cs.name);
    has_arg = cs.has_arg;
    ch      = cs.ch;
    optarg  = xstrdup(cs.optarg);
    // cout << "copy from " << (int)cs.name << " to " << (int)name << " " << name << "\n";
}

CPList operator+(const CP a1, const CP a2) // friend CP
{
    CPList r;
    return r + a1 + a2;
}


CP::operator CPList()
{
    CPList r;
    return r + *this;
}

#endif

