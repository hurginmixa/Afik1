/* 
 * tnef.h -- extract files from Microsoft TNEF format.
 * 
 */


#ifndef TNEF_H
#define TNEF_H


#include <stdio.h>

#ifdef  __cplusplus
extern "C" {
#endif

/* TNEF signature.  Equivalent to the magic cookie for a TNEF file. */
#define TNEF_SIGNATURE   0x223e9f78

/* Object types */
#define LVL_MESSAGE      0x01
#define LVL_ATTACHMENT   0x02

/* Defines uint[8,16,32] */                                                                                                         
#include "sizes.h"                                                                                                                  

/* Object referens */
typedef struct tnef_object_struct tnef_object;

struct tnef_object_struct {
  uint8 lvl_type;
  uint16 tnef_type;
  uint16 tnef_name;
  uint32 len;
  uint16 checksum;
  uint32 tell;

  tnef_object *next;
};


/* MAPI Object referens */
typedef struct tnef_mapi_object_struct tnef_mapi_object;

struct tnef_mapi_object_struct {
  uint16 mapi_type;
  uint16 mapi_name;
  uint32 num_values;
  struct values_struct {
    uint32 len;
    uint32 tell;
  } *values;
};


/* Limit to tnef's data size */
#define TNEF_BUFFER_LIMIT 16384

/* Limit to tnef's file name size */
#define TNEF_FILENAME_LIMIT 255


/* Main entrance point to tnef processing */
extern int TNEF_HasError;
extern tnef_object* tnef_parse_file(FILE *input, int flags);
extern void free_tnef_object(tnef_object* obj);
extern int get_attachment_count(tnef_object* first, FILE* input, int flags);
extern char* get_attachment_filename(tnef_object* first, FILE* input, uint16 num, int flags);
extern long save_attachment(tnef_object* first, FILE* input, uint16 num, const char* dest_file_name, int flags);


#ifdef  __cplusplus
}
#endif

#endif /* !TNEF_H */

