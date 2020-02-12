#include <stdio.h>


int main(int argc, char* argv[])
{
  FILE *f;
  int c;
  int i, a;

  f = fopen("/tmp/mailprog", "w");

  for (i=0; i<argc; i++) {
     fprintf(f, "%d %s\n", i, argv[i]);
  }
  fputs("===================\n", f);

  while(!feof(stdin)) {
    c = fgetc(stdin);
    fputc(c, f);
  }
  fclose(f);
}
