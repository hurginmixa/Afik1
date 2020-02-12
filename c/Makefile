POSTGRESQL_INC = -I/usr/include/pgsql


all:
	cd tnef;\
	${MAKE}
	${MAKE} prog;


prog: parsemail pr



pr: pr.o decode.o str.o xalloc.o tnef/tnef.o tnef/tnef_errors.o tools.o
	c++ -g -o pr pr.o decode.o str.o xalloc.o tnef/tnef.o tnef/tnef_errors.o tools.o

pr.o: pr.cc str.h decode.h IPQ.h sock.h tnef/tnef.h tools.h
	c++  -Wno-deprecated -c -g $<



parsemail: parsemail.o IPQ.o str.o xalloc.o decode.o tnef/tnef.o tnef/tnef_errors.o tools.o
	c++ -lpq -g -o parsemail parsemail.o str.o xalloc.o IPQ.o decode.o tnef/tnef.o tnef/tnef_errors.o tools.o


parsemail.o: parsemail.cc IPQ.h comstr.h str.h decode.h tnef/tnef.h tnef/tnef_errors.h tools.h
	c++  -Wno-deprecated -c -g $(POSTGRESQL_INC) $<



tools.o: tools.cc tools.h
	c++  -Wno-deprecated -c -g $<

decode.o: decode.cc decode.h
	c++  -Wno-deprecated -c -g $<

xalloc.o: xalloc.cc xalloc.h
	c++  -Wno-deprecated -c -g $<

str.h: xalloc.h
	touch str.h

str.o: str.cc str.h
	c++  -Wno-deprecated -c -g $<

comstr.h: xalloc.h
	touch comstr.h

decode.h: str.h
	touch decode.h

IPQ.o: IPQ.cc IPQ.h tools.h
	c++  -Wno-deprecated -c -g $(POSTGRESQL_INC) $<



clean:
	cd tnef;\
	make clean
	rm -f parsemail.o parsemail str.o  xalloc.o decode.o tools.o IPQ.o pr.o pr;
