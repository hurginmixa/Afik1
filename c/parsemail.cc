#include <sys/dir.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <stdio.h>
#include <stdlib.h>
#include <ctype.h>
#include <iostream.h>
#include <fstream.h>
#include <unistd.h>
#include <time.h>
#include "IPQ.h"
#include "str.h"
#include "comstr.h"
#include "decode.h"
#include "tools.h"
#include "tnef/tnef.h"
#include "tnef/tnef_errors.h"

typedef vector<string> head;
typedef const char * CPChar;

string csDevice = "/var/spool/mail";
string csDomain;
string csBoxName;
string csUserID;
string csDBase;
string csDBUser;
string csDBPassword;
string csFilesDir;
string csTmpDir;
string csSQLLogName;
int dIsDomain;
int nDebug;
int dStdIn;

FILE *LogFile = NULL;

FILE *InputFile;
int InputFileLine = -1;

ofstream SqlLogStream;
string csMessageLine;
head MessageHeader;
IPQ Base;

void NextLine(int flag = 0);
bool IsNewMessage();
string HeaderField(head& Header, const string csParameterName);
string HeaderField(head& Header, const string csParameterName, int dNum, char cDelim = ';');
string HeaderField(head& Header, const string csParameterName, const string csParam);
void MoveFileToFs(const string csTmpName, string csFileName, const string csContent, const string csId, const long ldSize);
void ReadBodyAttach(const int dLevel, head& Header, const string csBoundary);
void ReadBodyText(int dLevel, head& Header, const string csBoundary);
void ReadBodyMulti(int dLevel, head& Header);
void ReadParts(const int dLevel, string strBoundary);
void ReadMessages();
void Help(int dCode);


int CheckQuote()
{
    IPQResult r;
    r = Base.exec("SELECT usr.quote        as usrquote, \
                          domain.quote     as domainquote, \
                          usr.diskusage    as usrdiskusage, \
                          domain.diskusage as domaindiskusage, \
                          domain.userquote as defaultusrquote where usr.sysnumdomain = domain.sysnum and usr.sysnum = '%s'", (CPChar)csUserID);

    if (r.NRows() != 1) {
        return 0;
    }


    double UsrQuote = string(r.value("usrquote")).asfloat();
    if (UsrQuote == 0) {
        UsrQuote = string(r.value("defaultusrquote")).asfloat();
    }
    double DomainQuote = string(r.value("domainquote")).asfloat();
    double UsrDiskUsage = string(r.value("usrdiskusage")).asfloat();
    double DomainDiskUsage = string(r.value("domaindiskusage")).asfloat();

    if (nDebug) {
        cout << GetCurrDate() << "CheckQuote : UsrQuote: '" << UsrQuote << "' DomainQuote: '" << DomainQuote << "' UsrDiskUsage: '" << UsrDiskUsage << "' DomainDiskUsage: '" << DomainDiskUsage << "'" << endl;
    }

    if (UsrDiskUsage >= UsrQuote) {
        return 0;
    }

    if (DomainDiskUsage >= DomainQuote) {
        return 0;
    }

    return 1;
}


void DeleteMsg(const string csId)
{
    int sysnum;
    IPQResult r_mes, r_fs, r_file;

    if (nDebug) {
        cout << GetCurrDate() << " DeleteMsg : Delete messsage with id  " << csId << endl;
    }

    r_mes = Base.exec("select * from msg, fld where id = '%s' and msg.sysnumfld = fld.sysnum and fld.sysnumusr = '%s'", (CPChar)csId, (CPChar)csUserID);
    if (r_mes.NRows() != 1) {
        return;
    }

    Base.exec("delete from msgbody where sysnummsg = '%s'", (CPChar)string(r_mes.value("sysnum")));
    Base.exec("delete from msgheader where sysnummsg = '%s'", (CPChar)string(r_mes.value("sysnum")));

    r_fs = Base.exec("select * from fs where ftype = 'a' and up = '%s'", (CPChar)string(r_mes.value("sysnum")));
    while(! r_fs.Eof()) {
        Base.execp("delete from fs where sysnum = '%s'", (CPChar)string(r_fs.value("sysnum")));
        r_fs.Next();
    }

    r_mes = Base.exec("delete from msg where id = '%s' and msg.sysnumfld = fld.sysnum and fld.sysnumusr = '%s'", (CPChar)csId, (CPChar)csUserID);
}


void NextLine(int flag) /*FOLD00*/
{
    string ret;
    int buf = 25500;
    char *tmp = (char*)xmalloc(buf + 1);
    char c = 0;

    if (feof(InputFile)) {
        csMessageLine = ret;
        xfree(tmp);
        return;
    }

    do {
        do {
            tmp[0] = 0;
            fgets(tmp, buf, InputFile);
            if (LogFile != NULL) {
                fputs(tmp, LogFile);
            }
            if (c != 0) {
                ret.erasenl();
            }
            ret += tmp;
        } while (!feof(InputFile) && (ret[ret.strlen() - 1] != '\n'));
	InputFileLine++;

        c = 0;
        if (!feof(InputFile) && flag == 0 && ret[0] != '\n') {
            c = fgetc(InputFile);
            ungetc(c, InputFile);
        }
    } while ((c == 9) || (c == 32));

    csMessageLine = ret;
    xfree(tmp);
}


string HeaderField(head& Header, const string csParameterName) /*fold00*/
{
    string s;
    string r;

    for(int i=0; i < Header.size(); i++) {
        if (Header[i].strpos(csParameterName + ":", 0) == 0) {
            s = Header[i].substr(csParameterName.strlen() + 1);
            s.erasenl();
            s.Trim();
            r += (r != "" ? "; " : "") + s;
        }
    }

    return r;
}


string HeaderField(head& Header, const string csParameterName, const int dNum, const char cDelim)
{
    string r = HeaderField(Header, csParameterName);

    int i = 1;
    while ((r != "") && (i < dNum)) {
        // cout << i << r.strword(0, delim).strlen() << '\n';
        r = r.substr(r.strword(0, cDelim).strlen() + 1);
        r.Trim();
        i++;
    }

    r = r.strword(0, cDelim);
    r.Trim();
    return r;
}


string HeaderField(head& Header, const string csParameterName, const string csParam) /*fold00*/
{
    int i = 1;
    string r = HeaderField(Header, csParameterName, i);
    while ((r != "") && (r.strpos(csParam + "=", 0) != 0)) {
        r = HeaderField(Header, csParameterName, ++i);
    }
    if ((r != "") && (r.strpos(csParam + "=", 0) == 0)) {
        return r.substr(csParam.strlen() + 1);
    }
    return string();
}


inline bool IsNewMessage() /*fold00*/
{
    if (dStdIn) {
      return (csMessageLine == "");
    }
    return (csMessageLine.strpos("From ") == 0) && (csMessageLine[-1] == '\n');
}


inline void CheckDate(string &s) /*fold00*/
{
    if (s.strpos("(") != -1) {
        s = s.substr(0, s.strpos("(") - 1);
    }
}


string DecodeString(const string SOURCE) /*FOLD00*/
{
    int p1, p2;
    Decode *decode;
    string S = SOURCE;
    string TMP;
    int i;

    p1 = S.strpos("=?");
    p2 = S.strpos("?=");
    while ((p1 != -1) && (p2 != -1) && (p2 > p1)) {
        TMP = S.substr(0, p1);

        i = p1 + 2;
        while (S[i] != '?') {
            i++;
        }
        i++;

        string C = S.substr(i, 2);
        if (C == "B?") {
            decode = new Base64();
        } else {
            if (C == "Q?") {
                decode = new Quoted();
            } else {
                return S;
            }
        }

        i += 2;
        while (i < S.strlen() && (S[i] != '?' || S[i+1] != '=')) {
            if (decode->ReadyPut()) {
                decode->Put(S[i++]);
                p2 = i;
            }
            if (decode->ReadyGet()) {
                TMP += decode->Get();
            }
        }
        if (i >= S.strlen()) {
            return S;
        }

        decode->Flush();
        while (decode->ReadyGet()) {
            TMP += decode->Get();
        }

        S = TMP + S.substr(p2 + 2);

        p1 = S.strpos("=?");
        p2 = S.strpos("?=");
    }

    return S;
}


string DecodeFileName(const string Name) /*fold00*/
{
    return DecodeString(Name);
}


void MoveFileToFs(const string csTmpName, string csFileName, const string csContent, const string csId, const long ldSize)
{
    char buf[1024];
    FILE *fsum = popen("md5sum " + csTmpName, "r");
    fgets(buf, 1024, fsum);
    pclose(fsum);
    string csMD5Sum = string(buf).substr(0, 32);

    csFileName.SQLSymbReplace();

    string csFileNumber;
    string csStorageNumber;

    IPQResult r1, r2, r3;
    Base.exec("BEGIN");
    Base.exec("LOCK TABLE fs, file IN ACCESS EXCLUSIVE MODE");

    r1 = Base.exec("select * from msg, fld where id = '%s' and msg.sysnumfld = fld.sysnum and fld.sysnumusr = '%s'", (CPChar)csId, (CPChar)csUserID);

    if ((r2 = Base.exec("select * from file where fsize = %d and fcrc = '%s'", ldSize, (CPChar)csMD5Sum)).NRows() != 0) {
        csFileNumber = r2.value("sysnum");
        if ( nDebug ) { cout << GetCurrDate() << " MoveFileToFs : Saved file num found " << csFileNumber << endl; }
    } else {
        r2 = Base.exec("select NextVal('file_seq') as filenum");
        csFileNumber = r2.value("filenum");

        r2 = Base.exec("SELECT sysnum from storages order by ( size - used ) desc LIMIT 1");
        csStorageNumber = r2.value("sysnum");

        // if( !link ("$TmpFileName", "$main::PROGRAM_FILES/storage${StorageNumber}/$sysnumfile") ) {
        //     if( !cp ("$TmpFileName", "$main::PROGRAM_FILES/storage${StorageNumber}/$sysnumfile") ) {
        //         DBExec("delete from file where sysnum = ${sysnumfile}");
        //         return -1;
        //     }
        // }

        string csDestPath =  csFilesDir + "storage" + csStorageNumber + "/";
        string csDestName =  csDestPath + csFileNumber;
        if (nDebug) { cout << GetCurrDate() << " MoveFileToFs : New file num allocated " << csFileNumber << " on storage " << csStorageNumber  << " destname " << csDestName << endl; }
        Base.execp("insert into file (sysnum, fsize, ftype, fcrc, nlink, numstorage) values ('%s', '%d', '%s', '%s', 0, '%s')", (CPChar)csFileNumber, ldSize, (CPChar)csContent, (CPChar)csMD5Sum, (CPChar)csStorageNumber);

        if (Copy(csTmpName, csDestPath, csFileNumber) != 0) {
            cerr << "Error : " << csBoxName << "@" << csDomain << " Dont possible do link " << csTmpName << " to " << csDestName << " line " << InputFileLine << endl;
            unlink(csTmpName);
            Base.exec("ROLLBACK");
            DeleteMsg(csId);
            exit(1);
        }
    }

    r3 = Base.exec("select NextVal('fs_seq') as fsnum");
    string csFSNumber = r3.value("fsnum");
    Base.exec("insert into fs (sysnum, ftype, up, name, owner, sysnumfile, creat) values ('%s', 'a', '%s', '%s', '%s', '%s', datetime('now'::abstime))", (CPChar)csFSNumber, (CPChar)string(r1.value("sysnum")), (CPChar)csFileName, (CPChar)csUserID, (CPChar)csFileNumber);
    if (nDebug) { cout << GetCurrDate() << " MoveFileToFs : File " << csFileName << " Saved with index " << csFSNumber << " file number " << csFileNumber << endl; }

    Base.exec("insert into billing (sysnumusr, sysnumdomain, kind, date, traficsize, sysnumfs, who, direct) values ('%s', getdomain('%s'), 'frommail', datetime('now'::abstime), '%d', '%s', '%s', 1)", (CPChar)csUserID, (CPChar)csUserID, ldSize, (CPChar)csFSNumber, (CPChar)(HeaderField(MessageHeader, "From").substr(0, 20)));
    Base.exec("COMMIT");
    // cout << string(r.Value("sysnum"));

}


void ReadBodyAttach(const int dLevel, head& Header, const string csBoundary) /*FOLD00*/
{
    if (nDebug) { cout << GetCurrDate() << " ReadBodyAttach : start with level " << dLevel << " line " << InputFileLine << endl; }

    string csId, csEncode, csContent, csFileName;
    Decode *decode;
    FILE *TMPFileStream;
    long ldSize = 0;

    csId      = HeaderField(MessageHeader, "Message-ID").URLEncode();

    csContent = HeaderField(Header, "Content-Type", 1); csContent.ToUpper();
    if (nDebug) { cout << GetCurrDate() << " ReadBodyAttach : Attach Content-type " << csContent << endl; }

    csFileName = HeaderField(Header, "Content-Type", "Name");
    if (csFileName == "") {
        csFileName = HeaderField(Header, "Content-Disposition", "FileName");
    }
    if (csFileName == "") {
        csFileName = HeaderField(Header, "Content-ID", 1);
        csFileName.LTrimSym('<'); csFileName.RTrimSym('>');
    }
    csFileName.LTrimSym('"'); csFileName.RTrimSym('"');
    if (csFileName == "") {
        csFileName = "Nonename";
    }
    csFileName = DecodeFileName(csFileName);
    if (nDebug) { cout << GetCurrDate() << " ReadBodyAttach : File name " << csFileName << endl; }


    string csTmpName;
    {
        char *TMP = (char*)xmalloc(strlen(csTmpDir) + strlen("parsemailXXXXXX") + 1);
        strcpy(TMP, csTmpDir);
        strcat(TMP, "parsemailXXXXXX");
        if ( nDebug ) { cout << GetCurrDate() << " ReadBodyAttach : PreTMP File name " << TMP << endl; }

        int dFileDesc = mkstemp(TMP);
        if (dFileDesc == -1) {
            cerr << "Error : " << csBoxName << "@" << csDomain << " Not open tmp file" << " line " << InputFileLine << endl;
            DeleteMsg(csId);
            exit(1);
        }

        csTmpName = TMP;
        if ( nDebug ) { cout << GetCurrDate() << " ReadBodyAttach : TMP File name " << csTmpName << endl; }

        TMPFileStream = fdopen(dFileDesc, "w");
        if ( !TMPFileStream ) {
            cerr << "Error : " << csBoxName << "@" << csDomain << " Not attach tmp file descriptor" << " line " << InputFileLine << endl;
            unlink(csTmpName);
            DeleteMsg(csId);
            exit(1);
        }
    }

    csEncode  = HeaderField(Header, "Content-Transfer-Encoding"); csEncode.ToUpper();
    if (csEncode == "QUOTED-PRINTABLE") {
        decode = new Quoted();
    } else if (csEncode == "BASE64") {
        decode = new Base64();
    } else {
        decode = new NoneDecode();
    }

    NextLine(1);
    while ((csMessageLine != "") && ((csBoundary == "") || ((csMessageLine != "--" + csBoundary + "\n") && (csMessageLine != "--" + csBoundary + "--\n"))) && !IsNewMessage()) {
        int i = 0;
        int c = 0;
        c = csMessageLine[i];
        while (c) {
            while ((c) && (decode->ReadyPut())) {
                decode->Put(c);
                c = csMessageLine[++i];
            }
            while (decode->ReadyGet()) {
                putc(decode->Get(), TMPFileStream);
                ldSize++;
            }
            // Body += csMessageLine[i++];
        }
        NextLine(1);
    }

    decode->Flush();
    while (decode->ReadyGet()) {
        putc(decode->Get(), TMPFileStream);
        ldSize++;
    }

    delete decode;
    fclose(TMPFileStream);

    if (csContent != "APPLICATION/MS-TNEF" ) {
        MoveFileToFs(csTmpName, csFileName, csContent, csId, ldSize);
    } else {
        tnef_object* tnef;
        FILE *TNEF;
        string csTNEFAttTmpName, csTNEFAttFileName;

        if(nDebug) {
            cout << GetCurrDate() << " ReadBodyAttach : File in TNEF Format" << endl;
        }

        if(!(TNEF = fopen(csTmpName, "rb+"))) {
            cerr << "Error : " << csBoxName << "@" << csDomain << " Not open tmp file for reading TNEF structure" << " line " << InputFileLine << "\n";
            unlink(csTmpName);
            DeleteMsg(csId);
            exit(1);
        }

        tnef = tnef_parse_file(TNEF, 0);
        if (!tnef) {
            cerr << "Error : " << csBoxName << "@" << csDomain << " " << get_tnef_error_str(TNEF_HasError) << " line " << InputFileLine << "\n";
            unlink(csTmpName);
            DeleteMsg(csId);
            exit(1);
        }

        int dNumAttaches;
        if((dNumAttaches = get_attachment_count(tnef, TNEF, 0)) == -1) {
            cerr << "Error : " << csBoxName << "@" << csDomain << " " << get_tnef_error_str(TNEF_HasError) << " line " << InputFileLine << "\n";
            unlink(csTmpName);
            DeleteMsg(csId);
            exit(1);
        }

        if (nDebug) {
            cout << GetCurrDate() << " ReadBodyAttach : TNEF File contains " << dNumAttaches << " atachmentes" << endl;
        }

        for (int i = 0; i < dNumAttaches; i++) {
            csTNEFAttTmpName = csTmpName + "." + (string)(i + 1);

            ldSize = save_attachment(tnef, TNEF, i+1, csTNEFAttTmpName, 0);
            if (TNEF_HasError) {
                cerr << "Error : " << csBoxName << "@" << csDomain << " " << get_tnef_error_str(TNEF_HasError) << " line " << InputFileLine << "\n";
                unlink(csTmpName);
                unlink(csTNEFAttTmpName);
                DeleteMsg(csId);
                exit(1);
            }

            csTNEFAttFileName = get_attachment_filename(tnef, TNEF, i+1, 0);
            if ((const char*) csTNEFAttFileName == NULL) {
                cerr << "Error : " << csBoxName << "@" << csDomain << " " << get_tnef_error_str(TNEF_HasError) << " line " << InputFileLine << "\n";
                unlink(csTmpName);
                unlink(csTNEFAttTmpName);
                DeleteMsg(csId);
                exit(1);
            }

            cout << GetCurrDate() << " Attach N " << (i + 1) << " name " << csTNEFAttFileName << endl;

            MoveFileToFs(csTNEFAttTmpName, csTNEFAttFileName, "APPLICATION/OCTET-STREAM", csId, ldSize);

            if (nDebug) { cout << GetCurrDate() << " ReadBodyAttach :  unlink TNEF TMP File " << csTNEFAttTmpName << endl; }
            unlink(csTNEFAttTmpName);
        }

        free_tnef_object(tnef);

        fclose(TNEF);
    }

    if (nDebug) { cout << GetCurrDate() << " ReadBodyAttach : unlink TMP File " << csTmpName << endl; }
    unlink(csTmpName);

    if (nDebug) { cout << GetCurrDate() << " ReadBodyAttach : Read Body Attach exit with level " << dLevel << " line " << InputFileLine << endl; }
}


void ReadBodyText(const int dLevel, head& Header, const string csBoundary) /*FOLD00*/
{
    string csBody, csId, csContent, csEncode, csCharSet;
    Decode *decode;

    if (nDebug) {
        cout << GetCurrDate() << " ReadBodyText : Read Body Text start with level " << dLevel << " line " << InputFileLine << endl;
    }


    csId      = HeaderField(MessageHeader, "Message-ID").URLEncode();
    csContent = HeaderField(Header, "Content-Type", 1); csContent.ToUpper();
    csCharSet = HeaderField(Header, "Content-Type", "charset"); csCharSet.ToUpper(); csCharSet.URLEncode();
    csEncode  = HeaderField(Header, "Content-Transfer-Encoding"); csEncode.ToUpper();

    Base.exec("update msg set content = '%s' where id = '%s' and msg.sysnumfld = fld.sysnum and fld.sysnumusr = '%s'",
                                                           (const char*)csContent, (const char*)csId, (const char*)csUserID);

    if (csEncode == "QUOTED-PRINTABLE") {
        decode = new Quoted();
    } else if (csEncode == "BASE64") {
        decode = new Base64();
    } else {
        decode = new NoneDecode();
    }

    NextLine(1);
    while ((csMessageLine != "") && ((csBoundary == "") || ((csMessageLine != "--" + csBoundary + "\n") && (csMessageLine != "--" + csBoundary + "--\n"))) && !IsNewMessage()) {
        int i = 0;
        int c = 0;
        int j = 0;
        char *buf = (char*)xmalloc(csMessageLine.strlen()+1);
        c = csMessageLine[i++];
        while (c) {
            while ((c) && (decode->ReadyPut())) {
                decode->Put(c);
                c = csMessageLine[i++];
            }
            while (decode->ReadyGet()) {
                buf[j++] = decode->Get();
            }
        }
        buf[j++] = 0;
        csBody += buf;
        xfree(buf);
        NextLine(1);
    }
    decode->Flush();
    while (decode->ReadyGet()) {
        csBody += decode->Get();
    }
    delete decode;

    csBody = csBody.URLEncode();

    IPQResult r1;
    r1 = Base.exec("select msg.* from msg, fld where id = '%s' and msg.sysnumfld = fld.sysnum and fld.sysnumusr = '%s'", (const char*)csId, (const char*)csUserID);
    if (r1.NRows() > 0) {
        if (nDebug) {
            cout << GetCurrDate() << " ReadBodyText : New part of message found old size = " << ((string)r1.value("size") != "" ? r1.value("size") : "0") << endl;
        }
        Base.exec("delete from msgbody where sysnummsg = '%s'", r1.value("sysnum"));
    }

    Base.exec("update msg set size = '%s', charset = '%s' where id = '%s' and msg.sysnumfld = fld.sysnum and fld.sysnumusr = '%s'", (CPChar)string(strlen(csBody)), (CPChar)csCharSet, (CPChar)csId, (CPChar)csUserID);


    do {
        string csTmp = csBody.substr(0, 2048);
        csBody = csBody.substr(2048);
        Base.execp("insert into msgbody (sysnum, sysnummsg, body) values (NextVal('msgbody_seq'), %s, '%s')", (const char*)r1.value("sysnum"), (const char*)csTmp);
    } while (csBody != "");

    if (nDebug) { cout << GetCurrDate() << " ReadBodyText : Read Body Text exit with level " << dLevel << " line " << InputFileLine << endl; }
}


void ReadBodyMulti(int dLevel, head& Header) /*fold00*/
{

    if (nDebug) { cout << GetCurrDate() << " ReadBodyMulty : start with level " << dLevel << " line " << InputFileLine << "\n"; }

    // string csContent  = HeaderField(Header, "Content-Type");
    string csBoundary = HeaderField(Header, "Content-Type", "BOUNDARY");
    string csId       = HeaderField(MessageHeader, "Message-ID").URLEncode();
    csBoundary.LTrimSym('"');
    csBoundary.RTrimSym('"');

    if (csBoundary == "") {
       cerr << "Error : " << csBoxName << "@" << csDomain << " Missing BOUNDARY" << " line " << InputFileLine << "\n";
       DeleteMsg(csId);
       exit(1);
    }


    while ((csMessageLine != "") && (csMessageLine != "--" + csBoundary + "--\n")) {
        while ((csMessageLine != "") && (csMessageLine.strpos("--" + csBoundary) != 0)) {
            NextLine();
        }

        if (csMessageLine == "--" + csBoundary + "--\n") {
            continue;
        }

        if (csMessageLine == "--" + csBoundary + "\n") {
            NextLine();
            ReadParts(dLevel + 1, csBoundary);
            if (nDebug) { cout << GetCurrDate() << " ReadBodyMulty : continue with level " << dLevel << " line " << InputFileLine << endl; }
        } else {
            csMessageLine = "";
        }
    }
    if (csMessageLine == "") {
        cerr << "Error : " << csBoxName << "@" << csDomain << " Missing end or body of multipart" << " line " << InputFileLine << "\n";
        DeleteMsg(csId);
        exit(1);
    }
    NextLine();

    if (nDebug) { cout << GetCurrDate() << " ReadBodyMulty : Exit with level " << dLevel << " line " << InputFileLine << "\n"; }
}


void ReadParts(const int dLevel, const string csBoundary) /*FOLD00*/
{
    head Header;
    string csTMP;
    //IPQResult r;
    IPQResult r_fld;

    if (nDebug) { cout << GetCurrDate() << " ReadParts : start with level " << dLevel << " line " << InputFileLine << endl; }


    while ((csMessageLine != "") && (csMessageLine != "\n")) {
        Header.push_back(csMessageLine);
        NextLine();
    }

    if (csMessageLine == "") {
      cerr << "Error : " << csBoxName << "@" << csDomain << " Missing end of header, line " << InputFileLine << " line " << InputFileLine << "\n";

      if (dLevel > 1) {
        DeleteMsg(HeaderField(MessageHeader, "Message-ID").URLEncode());
      }

      exit(1);
    }

    if (dLevel == 1) {
        MessageHeader = Header;
        if (nDebug) {
            cout << GetCurrDate() << " ReadParts : line " << InputFileLine << endl;
            cout << GetCurrDate() << " ReadParts : Message-ID : " << HeaderField(MessageHeader, "Message-ID") << endl;
            cout << GetCurrDate() << " ReadParts : To : " << HeaderField(MessageHeader, "To") << endl;
            cout << GetCurrDate() << " ReadParts : From : " << HeaderField(MessageHeader, "From") << endl;
            cout << GetCurrDate() << " ReadParts : Subj : " << HeaderField(MessageHeader, "Subj") << endl;
            cout << GetCurrDate() << " ReadParts : Date : " << HeaderField(MessageHeader, "Date") << endl;
        }

        if (Base.exec("select * from msg, fld, usr where msg.id = '%s' and msg.sysnumfld = fld.sysnum and fld.sysnumusr = '%s'", (const char*)HeaderField(Header, "Message-ID").URLEncode(), (const char*)csUserID).NRows() != 0) {
            //if (dStdIn) {
               if (nDebug) {
                 cerr << "Error : " << csBoxName << "@" << csDomain << " Message-ID alredy exist" << " line " << InputFileLine << endl;
               }
               exit(1);
            //}
            if (nDebug) {
               cout << GetCurrDate() << " ReadParts : Message-ID alredy exist" << endl;
               cout << GetCurrDate() << " ReadParts : exit with level " << dLevel << endl;
            }
            return;
        }

        if (HeaderField(MessageHeader, "X-Afik1-Access-Notification") != "on") {
                if ((r_fld = Base.exec("select fld.sysnum from usr, fld where fld.sysnumusr = '%s' and fld.ftype = 1", (const char*)csUserID)).NRows() == 0) {
                        cerr << "Error : " << csBoxName << "@" << csDomain << " User not in system" << " line " << InputFileLine << endl;
                        if (nDebug) { cout << GetCurrDate() << " ReadParts : exit with level " << dLevel << " line " << InputFileLine << endl; }
                        return;
                }
        } else {
                if (nDebug) {
                        cout << GetCurrDate() << " ReadParts : Notification found" << endl;
                }
                if ((r_fld = Base.exec("select fld.sysnum from usr, fld where fld.sysnumusr = '%s' and fld.ftype = 6", (const char*)csUserID)).NRows() == 0) {
                        Base.exec("insert into fld (sysnum, sysnumusr, name, sort, ftype, fnew) values (nextval('fld_seq'), '%s', 'Notification', 'd', 6, 0)", (const char*)csUserID);
                        if ((r_fld = Base.exec("select fld.sysnum from usr, fld where fld.sysnumusr = '%s' and fld.ftype = 6", (const char*)csUserID)).NRows() == 0) {
                                cerr << "Error : " << csBoxName << "@" << csDomain << " User not in system" << " line " << InputFileLine << endl;
                                if (nDebug) { cout << GetCurrDate() << " ReadParts : exit whith level " << dLevel << " line " << InputFileLine << endl; }
                                return;
                        }
                }
        }

        IPQResult r_mes = Base.exec("select NextVal('msg_seq') as maxnummsg");

        string csSysNumMsg = r_mes.value("maxnummsg");
        string csSysNumFld = r_fld.value("sysnum");
        string csId        = HeaderField(Header, "Message-ID").URLEncode();
        string csTo        = DecodeFileName(HeaderField(Header, "To")).URLEncode();
        string csFrom      = DecodeFileName(HeaderField(Header, "From")).URLEncode();
        string csSubj      = DecodeFileName(HeaderField(Header, "Subject")).URLEncode();
        string csSend      = HeaderField(Header, "Date"); CheckDate(csSend); csSend = ((csSend != "") ? ("'" + csSend + "'") : ((string)"datetime('now'::abstime)"));


        Base.execp( "insert into msg (sysnum, sysnumfld, id, addrto, addrfrom, subj, fnew, size, send, recev) values ('%s', '%s', '%s', '%s', '%s', '%s', 't', '0', %s, datetime('now'::abstime))",
                    (const char*) csSysNumMsg,
                    (const char*) csSysNumFld,
                    (const char*) csId,
                    (const char*) csTo.substr(0, 254),
                    (const char*) csFrom.substr(0,254),
                    (const char*) csSubj.substr(0, 254),
                    (const char*) csSend );

        for(int i = 0; i < Header.size(); i++) {
            string s = Header[i]; s.erasenl(); s.Trim();
            Base.execp( "insert into msgheader (sysnummsg, headerline) VALUES ('%s', '%s')",
                       (CPChar) csSysNumMsg,
                       (CPChar) s.URLEncode().substr(0, 254) );
        }

        Base.exec("update fld set fnew = fnew + 1 where sysnum = '%s'", (const char*)csSysNumFld);

        //r = Base.exec("select * from msg, fld where id = '%s' and msg.sysnumfld = fld.sysnum and fld.sysnumusr = '%s'", (const char*)HeaderField(Header, "Message-ID"), (const char*)csUserID);
    }

    if (nDebug) {
        cout << GetCurrDate() << " ReadParts : dLevel = " << dLevel << " Content-Type "        << HeaderField(Header, "Content-Type") << endl;
        cout << GetCurrDate() << " ReadParts : dLevel = " << dLevel << " Content-Disposition " << HeaderField(Header, "Content-Disposition") << endl;
    }

    csTMP = HeaderField(Header, "Content-Disposition");
    if (csTMP != "") {
        if ((csTMP.strpos("attachment", 0) == 0) || (csTMP.strpos("filename", 0) >= 0)) {
            ReadBodyAttach(dLevel + 1, Header, csBoundary);
            if (nDebug) { cout << GetCurrDate() << " ReadParts : exit with level " << dLevel << " line " << InputFileLine << endl; }
            return;
//      } else {
//          cerr << "Error : " << csBoxName << "@" << csDomain << " Invalid ""Content-Disposition""\n";
//          exit(1);
        }
    }

    csTMP = HeaderField(Header, "Content-Type", 1);
    if (csTMP.strpos("MULTIPART", 0) == 0) {
        ReadBodyMulti(dLevel + 1, Header);
        if (nDebug) { cout << GetCurrDate() << " ReadParts : exit with level " << dLevel << " line " << InputFileLine << endl; }
        return;
    }

    if ((csTMP == "" ) || ((csTMP.strpos("TEXT/", 0) == 0) && (csTMP.strpos("name", 0) == -1))) {
        ReadBodyText(dLevel + 1, Header, csBoundary);
        if (nDebug) { cout << GetCurrDate() << " ReadParts : exit with level " << dLevel << " line " << InputFileLine << endl; }
        return;
    }

    if (csTMP.strpos("MESSAGE", 0) == 0) {
        if (dLevel == 1) {
            ReadBodyText(dLevel + 1, Header, csBoundary);
        } else {
            ReadBodyAttach(dLevel + 1, Header, csBoundary);
        }
        if (nDebug) { cout << GetCurrDate() << " ReadParts : exit with level " << dLevel << " line " << InputFileLine << endl; }
        return;
    }

    csTMP = HeaderField(Header, "X-IMAP");
    if (csTMP != "") {
        ReadBodyText(dLevel + 1, Header, csBoundary);
        if (nDebug) { cout << GetCurrDate() << " ReadParts : exit with level " << dLevel << " line " << InputFileLine << endl; }
        return;
    }


    ReadBodyAttach(dLevel + 1, Header, csBoundary);
    if (nDebug) { cout << GetCurrDate() << " ReadParts : exit with level " << dLevel << " line " << InputFileLine << endl; }
    return;
}


void ReadMessages() /*FOLD00*/
{
    if (nDebug) { cout << GetCurrDate() << " ReadMessages : start\n"; }

    NextLine();

    while ((!dStdIn) && (csMessageLine != "") && (!IsNewMessage())) {
        NextLine();
    }

    while (csMessageLine != "") {
        if (nDebug) { cout << GetCurrDate() << " ReadMessages : start parsing\n"; }

        if (csMessageLine != "") {
            IPQResult r_quote;
            double totalsize;

            if (!CheckQuote()) {
                if (nDebug) { cout << GetCurrDate() << " ReadMessages : Disk's Quote surpassed\n"; }
                cerr << "Error :  Disk's Quote surpassed" << " line " << InputFileLine << endl;
                exit(1);
            }

            ReadParts(1, "");

            if (!CheckQuote()) {
                string csId      = HeaderField(MessageHeader, "Message-ID").URLEncode();
                DeleteMsg(csId);
                if (nDebug) { cout << GetCurrDate() << " ReadMessages : Disk's Quote surpassed\n"; }
                cerr << "Error :  Disk's Quote surpassed" << " line " << InputFileLine << endl;
                exit(1);
            }

        }

        while ((csMessageLine != "") && (!IsNewMessage())) {
            NextLine();
        }

        if (nDebug) {cout << GetCurrDate() << " ReadMessages : end parsing line " << InputFileLine <<  endl;}
    }
    if (nDebug) {cout << GetCurrDate() << " ReadMessages : exit line" << InputFileLine << endl;}
}


int ReadBox(const int dDelOnClose) /*fold00*/
{
    if ((csBoxName[0] ==  '.') || (csBoxName.strpos("BOGUS", 0) >= 0)) {
        return 1;
    }


    IPQResult r1;
    if (dIsDomain) {
      r1 = Base.exec("select usr.sysnum from usr, domain where usr.sysnumdomain = domain.sysnum and usr.name = '%s' and domain.name = '%s'",
                                                                                                      (const char*)csBoxName, (const char*)csDomain);
    } else {
      r1 = Base.exec("select usr.sysnum from usr where usr.mailbox = '%s'", (const char*)csBoxName);
    }

    if (r1.NRows() == 0) {
         cerr << "Error : User " << csBoxName << "@" << csDomain << " not found" << " line " << InputFileLine << endl;
         return 0;
    }

    if (r1.NRows() > 1) {
         cerr << "Error : User " << csBoxName << "@" << csDomain << " multiply user found" << " line " << InputFileLine << endl;
         return 0;
    }


    csUserID = r1.value("sysnum");

    if (nDebug) {
        cout << GetCurrDate() << " ReadBox : csBoxName:" << csBoxName << " csDomain: " << csDomain << " csUserID: " << csUserID << "\n";
    }

    if (dStdIn) {
        InputFile = stdin;
    } else  {
        InputFile = fopen(csDevice + "/" + csBoxName, "r");
        if (InputFile == NULL) {
            if (nDebug) {
                cerr << "Error : " << csBoxName << "@" << csDomain << " not opened" << " line " << InputFileLine << endl;
            }
            return 0;
        }
    }
    InputFileLine = 0;

    ReadMessages();

    if (dStdIn) {
        fclose(InputFile);
    }

    if (dDelOnClose) {
        if (nDebug) {
            cout << GetCurrDate() << " ReadBox : erase " << csDevice + "/" + csBoxName << endl;
        }
        if ((InputFile = fopen(csDevice + "/" + csBoxName, "w")) == NULL) {
            cerr << "Error : " << "dont erase " << csDevice + "/" + csBoxName << " line " << InputFileLine << endl;
        } else {
            fclose(InputFile);
        }
	InputFileLine = 0;
    }

    return 1;
}

int main(int ac, char**av) /*FOLD00*/
{
    ComStr cs(ac, av, CP('d', 0, "delete") + CP('h', 0, "help") + CP('l', 1, "log") + CP(1, 0, "stdin") + CP(2, 1, "dbase") + CP(3, 1, "dbuser") + CP(4, 1, "dbpassword") + CP(5, 0, "debug") + CP(6, 1, "domain") + CP('f', 1, "filedir") + CP('t', 1, "tmpdir") + CP(7, 1, "sqllog"));
    if (cs.IsError()) {
        cerr << "Error with parsing parametrim" << " line " << InputFileLine << endl;
        return(1);
    }

    if (cs.opt('h').IsSet()) {
        Help(0);
    }

    dStdIn       = cs.opt(1).IsSet();
    csDBase      = cs.opt(2).arg();
    csDBUser     = cs.opt(3).arg();
    csDBPassword = cs.opt(4).arg();
    nDebug       = cs.opt(5).IsSet();
    dIsDomain    = cs.opt(6).IsSet();
    csDomain     = cs.opt(6).arg();
    csFilesDir   = cs.opt('f').arg();
    csTmpDir     = cs.opt('t').arg();
    csSQLLogName = cs.opt(7).arg();

    if (nDebug) { cout << "\n\n====\n" <<  GetCurrDate() << " main : Program start\n"; }

    if (dIsDomain && csDomain == "") {
        cerr << "Error : " << "Switchs --domain request parameter" << " line " << InputFileLine << endl;
        return(1);
    }


    if (cs.opt('l').IsSet()) {
        if (nDebug) {
            cout << GetCurrDate() << " main : use LogFile " << cs.opt('l').arg() << endl;
        }
        if ((LogFile = fopen(cs.opt('l').arg(), "a")) == NULL) {
            cerr << "Error : " << "Error open LogFile " << cs.opt('l').arg() << " line " << InputFileLine << endl;
        }
    }

    if (dStdIn && cs.opt('d').IsSet()) {
        cerr << "Error : " << "Incompatibiliti switchs --delete and --stdin" << " line " << InputFileLine << endl;
        return(1);
    }

    if (dStdIn && cs.ParamCount() != 1) {
        cerr << "Error : " << "With switch --stdin need 1 name of user" << " line " << InputFileLine << endl;
        return(1);
    }

    if (csFilesDir == "") {
        cerr << "Error : " << "Not set name of directory to storage attaches" << " line " << InputFileLine << endl;
        exit(1);
    }
    if (csFilesDir[csFilesDir.strlen() - 1] != '/') {
        csFilesDir += '/';
    }

    if (csTmpDir == "") {
        csTmpDir = csFilesDir;
    }
    if (csTmpDir[csTmpDir.strlen() - 1] != '/') {
        csTmpDir += '/';
    }

    if (csSQLLogName != "") {
      SqlLogStream.open(csSQLLogName, ios::app);
      if (!SqlLogStream.is_open()) {
          cerr << "Error : Don't open SQL Log file '" << csSQLLogName << "'" << " line " << InputFileLine << endl;
          return(1);
      }
    }

    if (csDBase == "") {
        cerr << "Error : " << "Not set name of DataBase" << " line " << InputFileLine << endl;
        return(1);
    }
    Base.DebugLog = &SqlLogStream;
    if (!SqlLogStream.is_open()) {
	    Base.DebugLog = &cout;
    }
    Base.ErrorLog = &cerr;
    if (!Base.connect(csDBase, (csDBUser != "" ? (const char*)csDBUser : NULL), (csDBPassword != "" ?  (const char*)csDBPassword : NULL) )) {
        cerr << "\n Error : connect database to " << csDBase << endl;
        return(1);
    };

    if (cs.ParamCount() > 0) {
        for (int i=1; i <= cs.ParamCount(); i++) {
            csBoxName = cs.Param(i);
            if ( !ReadBox(cs.opt('d').IsSet()) ) {
                exit(1);
            }
        }
    } else {
        typedef struct dirent Dirent;
        DIR *dir;
        Dirent *dp;

        if((dir = opendir(csDevice)) == NULL) {
            cerr << "\n Error : opendir with " << csDevice << "\n";
            exit(1);
        };

        while (dp = readdir(dir)) {
            csBoxName = dp->d_name;
            ReadBox(cs.opt('d').IsSet());
        }

        closedir(dir);
    }

    if (nDebug) {
        cout << GetCurrDate() << " main : Program end" << endl;
        sleep(20);
        //cout << "... complete, for exit press 'enter'" << endl;
        //getchar();
    }

    if (LogFile) {
        fclose(LogFile);
    }

    return 0;
}

void Help(int dCode) /*fold00*/
{
    printf("Program parsing mail for Afik1 system\n"\
           "Afik1 2000\n"\
           "-----------------------------------------------------------------\n"\
           "parsemail [parameters] address, ...\n"\
           "Parameters:\n"\
           "-h, --help      This screen\n"\
           "-d, --delete    Clearing mail's queue after job\n"\
           "-l, --log       Create (Append) log-file with used messages\n"\
           "--strin         Read messages from stdin\n"\
           "--dbase         \n"\
           "--debug         Output debug's informations\n"\
           "--domain        Find user use \"username\" and \"domain\"\n"\
           "-f, --filedir   \n"\
           "--sqllog        \n\n"
          );
    exit(dCode);
}
