#ifndef ___DECODE_H_
#define ___DECODE_H_

#include <stdlib.h>

class Decode {
    protected:
       int GetCount;
       int PutCount;
    public:
	Decode() {} ;
	virtual void Put(char c) {};
	virtual int ReadyPut() {return 1;};

        virtual char Get() {return 0;};
        virtual int ReadyGet() {return 1;};

        virtual void Flush() {};
};


class NoneDecode : public Decode {
    protected:
        char b;
    public:
	NoneDecode() {GetCount = -1;} ;
	virtual void Put(char c) {
	    if (ReadyPut()) {
		b = c;
                GetCount = 0;
	    }
	};
	inline virtual int ReadyPut() { return GetCount == -1; };

        virtual char Get() {
	    if (ReadyGet()) {
		GetCount = -1;
                return b;
	    } else {
                return 0;
	    }
	};
        inline virtual int ReadyGet() { return GetCount != -1; };

        inline virtual void Flush() {};
};


class Base64 : public Decode {
    protected:
       union {
	   char b[3];
           ulong l;
       };

    public:
        Base64() { GetCount = -1; PutCount = 0; l = 0; };
	virtual void Put(char c);
	inline virtual int ReadyPut() { return PutCount <= 3; };

	virtual char Get();
	inline virtual int ReadyGet() { return GetCount >= 0; };

	virtual void Flush();
};

class Quoted : public Decode {
    protected:
       char b[2];

    public:
        Quoted() { GetCount = -1; PutCount = 0; };
	virtual void Put(char c);
	inline virtual int ReadyPut() { return GetCount < 0; };

	virtual char Get();
	inline virtual int ReadyGet() { return GetCount >= 0; };
        virtual void Flush() {};
};

#endif /* ___DECODE_H_ */
