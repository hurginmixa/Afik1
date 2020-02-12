#ifndef ___XALLOC_H_
#define ___XALLOC_H_

char *xstrdup(const char* s);
int xstrlen(const char* s);
void *xmalloc(unsigned int size);
void xfree(void *ptr);

#endif /* ___XALLOC_H_ */
