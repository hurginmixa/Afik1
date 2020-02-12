
#include <iostream.h>
#include <string.h>
#include <stdlib.h>
#include "xalloc.h"

char *xstrdup(const char *s)
{
    char *p;

    if (s == NULL) {
        return NULL;
    }

    if (strlen(s) == 0) {
        return NULL;
    }

    p = strdup(s);
    if (p == NULL) {
        cerr << "Error with xstrdup\n";
    }

    return p;
}

void *xmalloc(unsigned int size)
{
    return new char[size];
}

int xstrlen(const char *s)
{
    return (s != NULL) ? (int)::strlen(s) : (-1);
}

void xfree(void *ptr)
{
    if (ptr) {
        delete (char *)ptr;
    }
}
