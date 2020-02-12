/* This file generated by mknames.awk do not edit! */
#include <stdio.h>
#include <string.h>
#include "sizes.h"
#include "mapi_types.h"

char* get_mapi_type_str(uint16 d)
{
    char* str = NULL;
    switch(d) {
    case szMAPI_NULL:
        str=strdup("MAPI null property");
        break;
    case szMAPI_SHORT:
        str=strdup("MAPI short (16 bits)");
        break;
    case szMAPI_INT:
        str=strdup("MAPI integer (32 bits)");
        break;
    case szMAPI_FLOAT:
        str=strdup("MAPI float (4 bytes)");
        break;
    case szMAPI_DOUBLE:
        str=strdup("MAPI double");
        break;
    case szMAPI_CURRENCY:
        str=strdup("MAPI currency (64 bits)");
        break;
    case szMAPI_APPTIME:
        str=strdup("MAPI application time");
        break;
    case szMAPI_ERROR:
        str=strdup("MAPI error (32 bits)");
        break;
    case szMAPI_BOOLEAN:
        str=strdup("MAPI boolean (16 bits)");
        break;
    case szMAPI_OBJECT:
        str=strdup("MAPI embedded object");
        break;
    case szMAPI_INT8BYTE:
        str=strdup("MAPI 8 byte signed int");
        break;
    case szMAPI_STRING:
        str=strdup("MAPI string");
        break;
    case szMAPI_UNICODE_STRING:
        str=strdup("MAPI unicode-string");
        break;
    case szMAPI_SYSTIME:
        str=strdup("MAPI time");
        break;
    case szMAPI_CLSID:
        str=strdup("MAPI OLE GUID");
        break;
    case szMAPI_BINARY:
        str=strdup("MAPI binary");
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