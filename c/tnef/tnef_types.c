/* This file generated by mknames.awk do not edit! */
#include <stdio.h>
#include <string.h>
#include "sizes.h"
#include "tnef_types.h"

char* get_tnef_type_str(uint16 d)
{
    char* str = NULL;
    switch(d) {
    case szTRIPLES:
        str=strdup("triples");
        break;
    case szSTRING:
        str=strdup("string");
        break;
    case szTEXT:
        str=strdup("text");
        break;
    case szDATE:
        str=strdup("date");
        break;
    case szSHORT:
        str=strdup("short");
        break;
    case szLONG:
        str=strdup("long");
        break;
    case szBYTE:
        str=strdup("byte");
        break;
    case szWORD:
        str=strdup("word");
        break;
    case szDWORD:
        str=strdup("dword");
        break;
    case szMAX:
        str=strdup("max");
        break;
    default:
        {
             char buf[10];
#if (SIZEOF_INT == 4)
            sprintf(buf,"%04x",d);
#else
            sprintf(buf,"%04hx", d);
#endif
            str=strdup(buf);
        }
        break;
    }
    return str;
}
