#ifndef ___IPQ_H
#define ___IPQ_H
/*

  interface objects for access to PostGresSql

*/

#include <libpq-fe.h>
#include <stdarg.h>
#include <iostream.h>
#include <fstream.h>

class IPQ;
class IPQResult;


extern char* PG_HOST;
extern char* PG_PORT;
extern char* PG_OPTIONS;
extern char* PG_TTY;


class IPQ {
    private:
        int Corr;
        PGconn *Conn;
        const char *dbName;
        const char *pghost, *pgport, *pgoptions, *pgtty;
        char LastError[256];
        char LastCommand[1024*200];
        ExecStatusType LastExecStatus;
    public:
        ostream *DebugLog;
        ostream *ErrorLog;
        IPQ(const char *a_pghost = PG_HOST, const char *a_pgport = PG_PORT, const char *a_pgoptions = PG_OPTIONS, const char *a_pgtty = PG_TTY);
        ~IPQ();
        int connect(const char* a_dbName, const char* a_dbUser = NULL, const char* a_dbPassword = NULL );
        IPQResult exec(const char* CMD, ...);
        IPQResult execp(const char* CMD, ...);
        IPQResult execp1(const char* CMD, ...);
        IPQResult execv(const char* CMD, va_list ap);
        int Correct() { return Corr; };
        const char* lasterror() { return LastError; };
        const PGconn* conn() { return Conn; };
};


class IPQResult {
    friend class IPQ;
    private:
        int Corr;
        PGresult *res;
        int CurrentRow;
        int *Betachon;
        void FreeResult();
        void CopyResult(const IPQResult& r);
        void SetResult(PGresult *r);
    public:
        ostream *DebugLog;
        ostream *ErrorLog;
        IPQResult(PGresult *a_res = NULL);
        IPQResult(const IPQResult& r);
        ~IPQResult();
        int Correct() { return Corr; };
        IPQResult& operator= (const IPQResult r);

        int Eof() { return CurrentRow >= NRows(); }
        int NFields();
        int NRows();
        const char* FName(const unsigned int N);
        const char* val(const unsigned int N);
        const char* value(const char* Name);
        void ToRow(const unsigned int N);
        void Next(void) { ToRow(CurrentRow + 1); };
        void Top(void)  { ToRow(0); };
};

#endif

