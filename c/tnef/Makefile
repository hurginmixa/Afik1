#!/usr/bin/make

all: tnef.o tnef_errors.o tnef_names.o tnef_types.o mapi_names.o mapi_types.o
Makefile: all
clean: erase

tnef.o : tnef.c tnef.h tnef_errors.h tnef_names.h tnef_types.h mapi_names.h mapi_types.h sizes.h
	c++ -c -g -Wno-deprecated $<


tnef_errors.o : tnef_errors.c tnef_errors.h sizes.h
	c++ -c -g -Wno-deprecated $<

tnef_errors.c tnef_errors.h : errors.data mkdata.awk
	TAG="tnef_error";\
	export TAG;\
	awk -f mkdata.awk errors.data

tnef_names.o : tnef_names.c tnef_names.h sizes.h
	c++ -c -g -Wno-deprecated $<

tnef_names.c tnef_names.h: names.data mkdata.awk
	TAG="tnef_name";\
	export TAG;\
	awk -f mkdata.awk names.data

tnef_types.o : tnef_types.c tnef_types.h sizes.h
	c++ -c -g -Wno-deprecated $<

tnef_types.c tnef_types.h: types.data mkdata.awk
	TAG="tnef_type";\
	export TAG;\
	awk -f mkdata.awk types.data

mapi_names.o : mapi_names.c mapi_names.h sizes.h
	c++ -c -g -Wno-deprecated $<

mapi_names.c mapi_names.h: mapi_names.data mkdata.awk
	TAG="mapi_name";\
	export TAG;\
	awk -f mkdata.awk mapi_names.data

mapi_types.o : mapi_types.c mapi_types.h sizes.h
	c++ -c -g -Wno-deprecated $<

mapi_types.c mapi_types.h: mapi_types.data mkdata.awk
	TAG="mapi_type";\
	export TAG;\
	awk -f mkdata.awk mapi_types.data

#########################################################################################
erase:
	rm -f tnef.o tnef_types.c tnef_types.h tnef_names.c tnef_names.h tnef_errors.c tnef_errors.h mapi_names.c mapi_names.h mapi_types.c mapi_types.h
	rm -f mapi_names.o mapi_types.o rm tnef_errors.o tnef_names.o tnef_types.o