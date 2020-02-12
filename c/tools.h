#ifndef ___TOOLS_H_
#define ___TOOLS_H_

#include <iostream.h>
#include <fstream.h>
#include <stdlib.h>
#include <string.h>
#include <stdio.h>
#include <time.h>
#include <unistd.h>
#include <sys/types.h>
#include <sys/stat.h>
#include "str.h"

string GetCurrDate();
int Copy(const string &csSrc, const string &csDestPath, const string &csDestName);

#endif //___TOOLS_H_
