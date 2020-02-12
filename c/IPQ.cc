/*

  interface objects for access to PostGresSql

*/

#include "IPQ.h"
#include <libpq-fe.h>
#include <stdarg.h>
#include <string.h>
#include <iostream.h>
#include "tools.h"

char* PG_HOST    = NULL;
char* PG_PORT    = NULL;
char* PG_OPTIONS = NULL;
char* PG_TTY     = NULL;


IPQ::IPQ(const char *a_pghost, const char *a_pgport , const char *a_pgoptions, const char *a_pgtty)
{
    DebugLog    = NULL;
    ErrorLog    = NULL;
    Corr        = 0;
    pghost      = a_pghost;
    pgport      = a_pgport;
    pgoptions   = a_pgoptions;
    pgtty       = a_pgtty;
    dbName      = NULL;
}


IPQ::~IPQ()
{
    if (Corr) {
        PQfinish(Conn);
    }
}


int IPQ::connect(const char* a_dbName, const char* a_dbUser, const char* a_dbPassword)
{
    dbName = a_dbName;

    if (Corr) {
        PQfinish(Conn);
    }

/*/
    Conn = PQsetdb(pghost, pgport, "user=afik1", pgtty, dbName);
/*/
    string ConnectStr = "dbname=" + string(a_dbName);

    if (a_dbUser) { ConnectStr += string(" user=") + string(a_dbUser); }
    if (a_dbPassword) { ConnectStr += string(" password=") + string(a_dbPassword); }
    
    if (DebugLog) {
    	*DebugLog << "ConnectStr : " << (const char *)ConnectStr << endl;
    }
    Conn = PQconnectdb(ConnectStr);
/**/    
    if (PQstatus(Conn) == CONNECTION_BAD)
    {
        if (ErrorLog) {
            *ErrorLog << "Connection to database '" << dbName << "' failed." << endl;
            *ErrorLog << "Error : " << PQerrorMessage(Conn) << endl;
        }
        if (DebugLog) {
            *DebugLog << GetCurrDate() << " Connection to database '" << dbName << "' failed." << endl;
            *DebugLog << GetCurrDate() << " Error : " << PQerrorMessage(Conn) << endl;
        }
        Corr = 0;
        return Corr;
    }

   Corr = 1;
   return Corr;
}


IPQResult IPQ::exec(const char* CMD, ...)
{
    IPQResult r;
    va_list ap;
    va_start(ap, CMD);

    r = execv(CMD, ap);
    va_end(ap);

    return r;
}

IPQResult IPQ::execp(const char* CMD, ...)
{
    IPQResult r;
    va_list ap;
    va_start(ap, CMD);

    ostream *SavDebugLog = DebugLog;
    ostream *SavErrorLog = ErrorLog;
    DebugLog = NULL;
    ErrorLog = NULL;

    r = execv(CMD, ap);
    while (strstr(LastError, "dupl") != NULL) {
      r = execv(CMD, ap);
    }

    if ( SavDebugLog ) {
        *SavDebugLog << GetCurrDate() << "> '" << LastCommand << "'" << endl;
    }

    if (*LastError != 0) {
        if (DebugLog) {
            *DebugLog << GetCurrDate() << " Error with : " << LastCommand << endl;
            *DebugLog << GetCurrDate() << " command failed. type error : " << PQresStatus(LastExecStatus) << endl;
            *DebugLog << GetCurrDate() << " " << LastError << endl;
        }
	
	if (ErrorLog) {
            *ErrorLog << "Error with : " << LastCommand << endl;
            *ErrorLog << "command failed. type error : " << PQresStatus(LastExecStatus) << endl;
            *ErrorLog << LastError << endl;
        }
    }

    va_end(ap);

    DebugLog = SavDebugLog;
    ErrorLog = SavErrorLog;

    return r;
}

IPQResult IPQ::execv(const char* CMD, va_list ap)
{
    PGresult *res;
    vsprintf(LastCommand, CMD, ap);
    IPQResult r;
    r.DebugLog = DebugLog;
    r.ErrorLog = ErrorLog;

    if (!Corr) {
        return r;
    }

    if ( DebugLog ) {
        *DebugLog << GetCurrDate() << "> '" << LastCommand << "'" << endl;
    }

    res = PQexec(Conn, LastCommand);
    if (res) {
        strcpy(LastError, PQresultErrorMessage(res));
        LastExecStatus = PQresultStatus(res);
    } else {
        strcpy(LastError, "ERROR: No result");
        LastExecStatus = PGRES_FATAL_ERROR;
    }

    if (!res || (LastExecStatus != PGRES_COMMAND_OK && LastExecStatus != PGRES_TUPLES_OK))
    {
        if (DebugLog) {
          *DebugLog << GetCurrDate() << " Error with : " << LastCommand << endl;
          *DebugLog << GetCurrDate() << " command failed. type error : " << PQresStatus(LastExecStatus) << endl;
          *DebugLog << GetCurrDate() << " " << LastError << endl;
        }
   	     
        if (ErrorLog) {
          *ErrorLog << "Error with : " << LastCommand << endl;
          *ErrorLog << "command failed. type error : " << PQresStatus(LastExecStatus) << endl;
          *ErrorLog << LastError << endl;
        }
        if (res) {
          PQclear(res);
        }
    } else {
        r.SetResult(res);
        LastError[0] = 0;
    }

    return r;
}


IPQResult::IPQResult(PGresult *a_res)
{
    DebugLog = NULL;
    ErrorLog = NULL;
    res = a_res;
    Corr = res != NULL ? 1 : 0;
    CurrentRow = 0;
    Betachon = new int(1);
    // printf("cons %X %X %d\n", this, Betachon, *Betachon);
}


IPQResult::IPQResult(const IPQResult& r)
{
    CopyResult(r);
}


IPQResult::~IPQResult()
{
    FreeResult();
}

IPQResult& IPQResult::operator= (const IPQResult r)
{
    FreeResult();
    CopyResult(r);
    return *this;
}

void IPQResult::CopyResult(const IPQResult& r)
{
    res = r.res;
    Corr = r.Corr;
    CurrentRow = r.CurrentRow;
    Betachon = r.Betachon;
    (*Betachon)++;
    DebugLog = r.DebugLog;
    ErrorLog = r.ErrorLog;
    // printf("copy %X %X %X %d\n", this, &r, Betachon, *Betachon);
}

void IPQResult::SetResult(PGresult *r)
{
    FreeResult();

    res = r;
    Corr = res != NULL ? 1 : 0;
    CurrentRow = 0;
    Betachon = new int(1);
    // printf("set %X %X %d\n", this, Betachon, *Betachon);
}

void IPQResult::FreeResult()
{
    // printf("destr %X %X %d\n", this, Betachon, *Betachon);

    if (!(--(*Betachon))) {
       // printf("clear %X\n", Betachon);
       delete Betachon;
       if (Corr) {
           PQclear(res);
       }
    }
}

int IPQResult::NFields()
{
    if (!Corr) {
        return 0;
    }

    return PQnfields(res);
}

int IPQResult::NRows()
{
    if (!Corr) {
        return 0;
    }

    return PQntuples(res);
}

const char* IPQResult::FName(const unsigned int N)
{
    if (!Corr) {
        return "";
    }

    return PQfname(res, N);
}

const char* IPQResult::val(const unsigned int N)
{
    static char Sp = 0;
    char *r;

    if (!Corr) {
      return &Sp;
    }

    if (N >= NFields()) {
      return &Sp;
    }

    if (CurrentRow >= NRows()) {
      return &Sp;
    }

    if (r = PQgetvalue(res, CurrentRow, N)) {
      return r;
    } else {
      return &Sp;
    }
}

const char* IPQResult::value(const char* Name)
{
    const char* r;

    int i;
    for (i=0; i < NFields(); i++) {
      if (strcmp(Name, FName(i)) == 0) {
        r = val(i);
        return r;
      }
    }


    if (DebugLog) {
        *DebugLog << "Error with field name '" << Name << "'. Field not found." << endl;
    }
    if (ErrorLog) {
        *ErrorLog << "Error with field name '" << Name << "'. Field not found." << endl;
    }

    return "";
}

void IPQResult::ToRow(const unsigned int N)
{
    if (!this->Eof()) {
      CurrentRow = N;
    }
}
