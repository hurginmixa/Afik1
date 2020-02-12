/* 
 * tnef.h -- extract files from Microsoft TNEF format.
 * 
 */

#include <assert.h>
#include <errno.h>
#include <stdio.h>
#include <stdlib.h>
#include <sys/stat.h>

#if STDC_HEADERS
#  if HAVE_STRING_H
#    include <string.h>
#  else
#    include <strings.h>
#  endif
#else
#  if HAVE_MEMMOVE
#    define memmove(d, s, n) bcopy ((s), (d), (n))
#  endif/* HAVE_MEMMOVE */
#endif /* STDC_HEADERS */

#include <stdarg.h>

//#include "strdup.h"
//#include "ldiv.h"

#include "tnef.h"
#include "tnef_errors.h"

#include "alloc.h"
//#include "path.h"

#include "tnef_names.h"
#include "tnef_types.h"
#include "mapi_names.h"
#include "mapi_types.h"


#ifdef  __cplusplus
extern "C" {
#endif


#define chmalloc_t(_ptr, _size, _type) ((_ptr = (_type)(_size ? malloc(_size) : NULL)), _ptr || !_size)
#define chmalloc(_ptr, _size) chmalloc_t(_ptr, _size, char*)


int TNEF_HasError;

//=================================================================================
static uint32 geti32 (FILE* input)
{
    unsigned char buf[4];

    TNEF_HasError = errNoError;
    if (fread (buf, 4, 1, input) != 1)
    {
        TNEF_HasError = 1;
        return 0;
    }
    // return (uint32)GETINT32(buf);
    return (uint32)((uint8)buf[0]+((uint8)buf[1]<<8)+((uint8)buf[2]<<16)+((uint8)buf[3]<<24));
}


//=================================================================================
static uint16 geti16 (FILE *input)
{
    unsigned char buf[2];

    TNEF_HasError = errNoError;
    if (fread (buf, 2, 1, input) != 1)
    {
        TNEF_HasError = 1;
        return 0;
    }
    // return (uint16)GETINT16(buf);
    return (uint16)((uint8)buf[0]+((uint8)buf[1]<<8));
}


//=================================================================================
static uint8 geti8 (FILE *input)
{
    unsigned char buf[1];

    TNEF_HasError = errNoError;
    if (fread (buf, 1, 1, input) != 1)
    {
        TNEF_HasError = 1;
        return 0;
    }
    // return (uint8)GETINT8(buf);
    return (uint8)buf[0];
}


//=================================================================================
static size_t getsize_t(FILE *input)
{
    size_t buf;

    TNEF_HasError = errNoError;
    if (fread (&buf, sizeof(size_t), 1, input) != 1)
    {
        TNEF_HasError = 1;
        return 0;
    }
    // return (uint8)GETINT8(buf);
    return buf;
}


//=================================================================================
static tnef_object* alloc_tnef_object()
{
   tnef_object* result;

   result = (tnef_object*) malloc (sizeof(tnef_object));
   if (result) {
       memset (result, '\0', sizeof (tnef_object));
   } else {
       TNEF_HasError = errMallocMem;
   }
   
   return result;
}


//=================================================================================
void free_tnef_object(tnef_object* obj)
{
  if (!obj) {
      return;
  }
  if (obj->next != NULL) {
    free_tnef_object(obj->next);
  }
  free(obj);
}


//=================================================================================
static tnef_mapi_object* alloc_tnef_mapi_object()
{
   tnef_mapi_object* result;

   result = (tnef_mapi_object*) malloc (sizeof(tnef_mapi_object));
   if (result) {
       memset (result, '\0', sizeof (tnef_mapi_object));
   }
   
   return result;
}


//=================================================================================
void free_tnef_mapi_object(tnef_mapi_object* obj)
{
  if (!obj) {
      return;
  }
  if (obj->values != NULL) {
    free(obj->values);
  }
  free(obj);
}



//=================================================================================
long save_attachment(tnef_object* first, FILE* input, uint16 num, const char* dest_file_name, int flags)
{
  tnef_object *obj, *rend;
  uint16 count = 0;
  uint32 FullLength = 0;
  
  TNEF_HasError = errNoError;

  if (!first) { TNEF_HasError = errInvalidParameter; return 0; }

  if (!num) { TNEF_HasError = errInvalidParameter; return 0; }

  if (!dest_file_name) { TNEF_HasError = errInvalidParameter; return 0; }

  obj = first;
  while (obj->next) {
      if (obj->lvl_type == LVL_ATTACHMENT && obj->tnef_name == attATTACHRENDDATA) {
          count++;
          if (count >= num) {
              break;
          }
      }

      obj = obj->next;
  }

  if (!obj || !(obj->next) || (count < num) || (obj->lvl_type != LVL_ATTACHMENT) || obj->tnef_name != (attATTACHRENDDATA)) { return -1; }

  rend = obj;

  obj = rend->next;
  while ((obj->lvl_type != LVL_ATTACHMENT || (obj->tnef_name != attATTACHDATA && obj->tnef_name != attATTACHRENDDATA)) && obj->next) {
      obj = obj->next;
  }

  if (obj->lvl_type == LVL_ATTACHMENT && obj->tnef_name == attATTACHDATA) {
      FILE *out;
      char *buf;
      uint32 ReadSize;
      uint32 Length;
      Length = FullLength = obj->len;

      if (obj->tnef_type != szBYTE) { TNEF_HasError = errInvalidAttributType; return 0; }
      if (fseek(input, obj->tell, SEEK_SET)) { return -1; }
      if (!chmalloc(buf, TNEF_BUFFER_LIMIT * sizeof(char))) { TNEF_HasError = errMallocMem; return 0; }

      out = fopen(dest_file_name, "wb");
      if(!out) { TNEF_HasError = errInvOpenFile; return 0; }

      ReadSize = TNEF_BUFFER_LIMIT < Length ? TNEF_BUFFER_LIMIT : Length;
      while (ReadSize > 0) {
        if(fread(buf, ReadSize, 1, input) != 1) { TNEF_HasError = errUnexpEndFile; fclose(out); return 0; }

        if(fwrite(buf, ReadSize, 1, out) != 1) { TNEF_HasError = errInvalidWrite; fclose(out); return 0; }

        Length -= ReadSize;
        ReadSize = TNEF_BUFFER_LIMIT < Length ? TNEF_BUFFER_LIMIT : Length;
      }
      
      fclose(out);
  } else {
      TNEF_HasError = errInvalidAttributNumer;
  }

  return FullLength;
}


//=================================================================================
int get_attachment_count(tnef_object* first, FILE* input, int flags)
{
  tnef_object* obj;
  int count = 0;

  if (!first) {
    TNEF_HasError = errInvalidParameter;
    return -1;
  }

  obj = first;
  while (obj->next) {
      if (obj->lvl_type == LVL_ATTACHMENT && obj->tnef_name == attATTACHRENDDATA) {
          count++;
      }

      obj = obj->next;
  }

  return count;
}


//=================================================================================
static tnef_mapi_object* get_tnef_mapi_object(FILE* input)
{
  tnef_mapi_object *mapi;
  uint32 i;
  
  TNEF_HasError = errNoError;

  mapi = alloc_tnef_mapi_object();
  if(!mapi) { return NULL; }

  mapi->mapi_type = geti16(input);
  if(TNEF_HasError) { free_tnef_mapi_object(mapi); return NULL; }
  
  mapi->mapi_name = geti16(input);
  if(TNEF_HasError) { free_tnef_mapi_object(mapi); return NULL; }

  switch(mapi->mapi_type) {
      case szMAPI_SHORT:                    /* 2 bytes */
          mapi->num_values = 1;
          if( !chmalloc_t(mapi->values, mapi->num_values * sizeof(*(mapi->values)), tnef_mapi_object_struct::values_struct *)) { TNEF_HasError = errMallocMem; free_tnef_mapi_object(mapi); return NULL; }
          mapi->values[0].len = 2;
          mapi->values[0].tell = ftell(input);
          if(fseek(input, mapi->values[0].len, SEEK_CUR)) { TNEF_HasError = errUnexpEndFile; free_tnef_mapi_object(mapi); return NULL; }
          break;

      case szMAPI_INT:                      /* 4 bytes */
      case szMAPI_FLOAT:
      case szMAPI_BOOLEAN:
          mapi->num_values = 1;
          if( !chmalloc_t(mapi->values, mapi->num_values * sizeof(*(mapi->values)), tnef_mapi_object_struct::values_struct *)) { TNEF_HasError = errMallocMem; free_tnef_mapi_object(mapi); return NULL; }
          mapi->values[0].len = 4;
          mapi->values[0].tell = ftell(input);
          if(fseek(input, mapi->values[0].len, SEEK_CUR)) { TNEF_HasError = errUnexpEndFile; free_tnef_mapi_object(mapi); return NULL; }
          break;

      case szMAPI_DOUBLE:                   /* 8 bytes */
      case szMAPI_SYSTIME:
          mapi->num_values = 1;
          if( !chmalloc_t(mapi->values, mapi->num_values * sizeof(*(mapi->values)), tnef_mapi_object_struct::values_struct *)) { TNEF_HasError = errMallocMem; free_tnef_mapi_object(mapi); return NULL; }
          mapi->values[0].len = 8;
          mapi->values[0].tell = ftell(input);
          if(fseek(input, mapi->values[0].len, SEEK_CUR)) { TNEF_HasError = errUnexpEndFile; free_tnef_mapi_object(mapi); return NULL; }
          break;

      case szMAPI_STRING:
      case szMAPI_UNICODE_STRING:
      case szMAPI_BINARY:
          mapi->num_values = geti32(input);;
          if(TNEF_HasError) { free_tnef_mapi_object(mapi); return NULL; }
          if( !chmalloc_t(mapi->values, mapi->num_values * sizeof(*(mapi->values)), tnef_mapi_object_struct::values_struct *)) { TNEF_HasError = errMallocMem; free_tnef_mapi_object(mapi); return NULL; }
          for(i = 0; i < mapi->num_values; i++) {
              mapi->values[i].len = geti32(input);
              if(TNEF_HasError) { free_tnef_mapi_object(mapi); return NULL; }
              /* must pad length to 4 byte boundary */
              {
                  ldiv_t d = ldiv (mapi->values[i].len, 4L);
                  if (d.rem != 0) {
                      mapi->values[i].len += (4 - d.rem);
                  }
              }
              mapi->values[0].tell = ftell(input);
              if(fseek(input, mapi->values[0].len, SEEK_CUR)) { TNEF_HasError = errUnexpEndFile; free_tnef_mapi_object(mapi); return NULL; }
          }
  }

  return mapi;
}


//=================================================================================
char* get_attachment_filename(tnef_object* first, FILE* input, uint16 num, int flags)
{
  tnef_object *obj, *rend;
  uint16 count = 0;
  static char buf[TNEF_FILENAME_LIMIT + 1];
  uint32 ReadSize;

  TNEF_HasError = errNoError;

  buf[0] = '\0';

  if (!first) { TNEF_HasError = errInvalidParameter; return NULL; }

  if (!num) { TNEF_HasError = errInvalidParameter; return NULL; }

  obj = first;
  while (obj->next) {
      if (obj->lvl_type == LVL_ATTACHMENT && obj->tnef_name == attATTACHRENDDATA) {
          count++;
          if (count >= num) {
              break;
          }
      }

      obj = obj->next;
  }

  if (!obj || !(obj->next) || (count < num) || (obj->lvl_type != LVL_ATTACHMENT) || obj->tnef_name != (attATTACHRENDDATA)) { return NULL; }

  rend = obj;

  obj = rend->next;
  while ((obj->lvl_type != LVL_ATTACHMENT || (obj->tnef_name != attATTACHTITLE && obj->tnef_name != attATTACHRENDDATA)) && obj->next) {
      obj = obj->next;
  }

  if (obj->lvl_type == LVL_ATTACHMENT && obj->tnef_name == attATTACHTITLE) {
      if (obj->tnef_type != szSTRING && obj->tnef_type != szTEXT) { TNEF_HasError = errInvalidAttributType; return NULL; }
      if (fseek(input, obj->tell, SEEK_SET)) { TNEF_HasError = errUnexpEndFile; return NULL; }

      ReadSize = TNEF_FILENAME_LIMIT < obj->len ? TNEF_FILENAME_LIMIT : obj->len;
      if (fread(buf, ReadSize, 1, input) != 1) { TNEF_HasError = errUnexpEndFile; return NULL; }
      buf[ReadSize] = '\0';
  }

  obj = rend->next;
  while ((obj->lvl_type != LVL_ATTACHMENT || (obj->tnef_name != attATTACHMENT && obj->tnef_name != attATTACHRENDDATA)) && obj->next) {
      obj = obj->next;
  }

  if (obj->lvl_type == LVL_ATTACHMENT && obj->tnef_name == attATTACHMENT) {
      static char mapi_buf[TNEF_FILENAME_LIMIT + 1];

      mapi_buf[0] = '\0';

      if (obj->tnef_type != szBYTE) { TNEF_HasError = errInvalidAttributType; return NULL; }
      if (fseek(input, obj->tell, SEEK_SET)) { TNEF_HasError = errUnexpEndFile; return NULL; }
      {
         tnef_mapi_object *mapi;
         uint32 MAPI_Count;
         uint32 i;

         MAPI_Count = geti32(input);
         if(TNEF_HasError) { return NULL; }

         for (i = 0; i < MAPI_Count; i++) {
           mapi = get_tnef_mapi_object(input);
           if(!mapi) { return NULL; }

           if ((mapi->mapi_name == MAPI_ATTACH_FILENAME && mapi_buf[0] == '\0') || mapi->mapi_name == MAPI_ATTACH_LONG_FILENAME) {
               uint32 savseek = ftell(input);
               uint32 i;
               for(i=0; i < mapi->num_values; i++) {
                   if(fseek(input, mapi->values[i].tell, SEEK_SET)) { TNEF_HasError = errUnexpEndFile; free_tnef_mapi_object(mapi); return NULL; }
                   ReadSize = TNEF_FILENAME_LIMIT < mapi->values[i].len ? TNEF_FILENAME_LIMIT : mapi->values[i].len;
                   if (fread(mapi_buf, ReadSize, 1, input) != 1) { TNEF_HasError = errUnexpEndFile; return NULL; }
                   mapi_buf[ReadSize] = '\0';
               }
               if(fseek(input, savseek, SEEK_SET)) { free_tnef_mapi_object(mapi); TNEF_HasError = errUnexpEndFile; return NULL; }
           }

           free_tnef_mapi_object(mapi);
         }
      }

      if (mapi_buf[0] != '\0') {
          strcpy(buf, mapi_buf);
      }
  } else {
      TNEF_HasError = errInvalidAttributNumer;
      return NULL;
  }

  return buf;
}


//=================================================================================
static int get_object(FILE *input, int flags, tnef_object* result)
{
  char b;
  uint16 CheckSum = 0;

  TNEF_HasError = errNoError;

  if(!result) { TNEF_HasError = errInvalidParameter; return -1; }
  if(!input) { TNEF_HasError = errInvalidParameter; return -1; }

  b = fgetc(input);
  if (feof(input)) {
      return 0;
  }
  ungetc(b, input);

  result->lvl_type = geti8(input);
  if(TNEF_HasError) { return -1; }

  {
    uint32 tmp = geti32(input);
    if(TNEF_HasError) { return -1; }

    result->tnef_type = (tmp >> 16);
    result->tnef_name = ((tmp << 16) >> 16);
  }

  result->len = geti32(input);
  if(TNEF_HasError) { return -1; }

  result->tell = ftell(input);


  {
     char *data;
     uint32 ReadSize;
     uint32 Length = result->len;
     uint32 i;

     if(!chmalloc(data, TNEF_BUFFER_LIMIT * sizeof(char))) { TNEF_HasError = errMallocMem; return -1; }

     ReadSize = TNEF_BUFFER_LIMIT < Length ? TNEF_BUFFER_LIMIT : Length;
     while(ReadSize != 0) {
       if(fread(data, ReadSize, 1, input) != 1) { TNEF_HasError = errUnexpEndFile; free(data); return -1; }

       for(i=0; i < ReadSize; i++) {
           CheckSum += (uint8)data[i];
       }

       Length -= ReadSize;
       ReadSize = TNEF_BUFFER_LIMIT < Length ? TNEF_BUFFER_LIMIT : Length;
     }

     free(data);

     CheckSum %= 65536;
  }

  result->checksum = geti16(input);
  if(TNEF_HasError) { return -1; }

  if (CheckSum != result->checksum) { return -1; }

  return 1;
}


//=================================================================================
tnef_object* tnef_parse_file(FILE *input, int flags)
{
  tnef_object *obj, *first;
  int rez;
  uint32 sign;
  uint16 key;

  TNEF_HasError = errNoError;

  if(fseek(input, 0, SEEK_SET)) { TNEF_HasError = errUnexpEndFile; return NULL; }

  sign = geti32(input);
  if(TNEF_HasError) { return NULL; }

  if (sign != TNEF_SIGNATURE) { TNEF_HasError = errNoTNEFFile; return NULL; }
  
  key = geti16(input);
  if(TNEF_HasError) { return NULL; }

  if(!(first = obj = alloc_tnef_object())) { return NULL; }

  while ((rez = get_object(input, flags, obj)) > 0) {
    if(!(obj->next = alloc_tnef_object())) { free_tnef_object(first); return NULL; }

    obj = obj->next;
  }

  if (rez < 0) { free_tnef_object(first); return NULL; }

  return first;
}

#ifdef  __cplusplus
}
#endif
