POSTGRESQL_INC = -I/usr/include/pgsql/server/

all : b.so

b.o : b.c
	cc -c ${POSTGRESQL_INC} b.c

b.so : b.o
	ld -dc -dp -Bdynamic -shared -expect_unresolved -o b.so b.o
