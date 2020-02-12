#include <errno.h>
#include <string.h>
#include "tools.h"

string GetCurrDate()
{
   struct tm t;
   time_t t2;
   char s[1024];

   t2 = time(NULL);
   memmove(&t, localtime(&t2), sizeof(struct tm));
   strftime(s, sizeof(s), "%Y/%m/%d %H:%M:%S", &t);
   return s;
}

int Copy(const string &csSrc, const string &csDestPath, const string &csDestName)
{
    struct stat SrcStat;
    struct stat DestStat;

    string csDest = csDestPath + csDestName;

    if ( stat (csSrc, &SrcStat) == -1) {
        return errno;
    }

    if ( stat (csDestPath, &DestStat) == -1) {
        return errno;
    }

    if ( SrcStat.st_dev == DestStat.st_dev) {

        if (stat (csDest, &DestStat) == -1) {
            return link(csSrc, csDest);
        }


        if ( SrcStat.st_ino == DestStat.st_ino) {
            return 0;
        } else {
            if ( unlink(csDest) == -1) {
                return -1;
            }
            return link(csSrc, csDest);
        }
    }


    FILE *hInpFile, *hOutFile;
    int nResult = 0;

    try {
        hInpFile = fopen(csSrc, "r");
        if ( !hInpFile ) { throw string("not open input file") + " " + strerror(errno); }

        try {
            hOutFile = fopen(csDest, "w");
            if ( !hOutFile ) { throw string("not open output file"); }

            try {

                char cpBuf[1024 * 100];
                size_t stRead_Size;
                long lTotalRead = 0;

                while ((stRead_Size = fread(cpBuf, 1, sizeof(cpBuf), hInpFile)) != 0) {
                    lTotalRead += stRead_Size;

                    size_t stWrite_Size;
                    stWrite_Size = fwrite(cpBuf, 1, stRead_Size, hOutFile);
                    if ( stWrite_Size != stRead_Size) {
                        throw string("write error");
                    }
                }

                if (lTotalRead != SrcStat.st_size) {
                    throw string("read error");
                }
            }
            catch (string Mes) { cout << Mes << endl; nResult = errno; }

            fclose(hOutFile);

            if ( nResult) {
                unlink(csDest);
            } else {
                chmod(csDest, SrcStat.st_mode);
                chown(csDest, DestStat.st_uid, DestStat.st_gid);
            }
        }
        catch (string Mes) { cout << Mes << endl; nResult = errno; }

        fclose(hInpFile);
    }
    catch (string Mes) { cout << Mes << endl; nResult = errno; }

    cout << "nResult " << nResult << endl;

    return nResult;
}
