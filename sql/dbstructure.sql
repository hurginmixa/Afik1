--
-- PostgreSQL database dump
--

SET client_encoding = 'SQL_ASCII';
SET check_function_bodies = false;
SET client_min_messages = warning;

--
-- Name: SCHEMA public; Type: COMMENT; Schema: -; Owner: postgres
--

COMMENT ON SCHEMA public IS 'Standard public schema';


SET search_path = public, pg_catalog;

--
-- Name: plpgsql_call_handler(); Type: FUNCTION; Schema: public; Owner: apache
--

CREATE FUNCTION plpgsql_call_handler() RETURNS language_handler
    AS '$libdir/plpgsql', 'plpgsql_call_handler'
    LANGUAGE c;


ALTER FUNCTION public.plpgsql_call_handler() OWNER TO apache;

--
-- Name: plpgsql_validator(oid); Type: FUNCTION; Schema: public; Owner: apache
--

CREATE FUNCTION plpgsql_validator(oid) RETURNS void
    AS '$libdir/plpgsql', 'plpgsql_validator'
    LANGUAGE c;


ALTER FUNCTION public.plpgsql_validator(oid) OWNER TO apache;

--
-- Name: plpgsql; Type: PROCEDURAL LANGUAGE; Schema: public; Owner: 
--

CREATE TRUSTED PROCEDURAL LANGUAGE plpgsql HANDLER plpgsql_call_handler VALIDATOR plpgsql_validator;


--
-- Name: datetime(abstime); Type: FUNCTION; Schema: public; Owner: apache
--

CREATE FUNCTION datetime(abstime) RETURNS abstime
    AS $_$select $1 as result;$_$
    LANGUAGE sql;


ALTER FUNCTION public.datetime(abstime) OWNER TO apache;

--
-- Name: get_acc(character varying, bigint); Type: FUNCTION; Schema: public; Owner: apache
--

CREATE FUNCTION get_acc(character varying, bigint) RETURNS integer
    AS $_$
DECLARE
   address alias for $1;
   fs_id alias for $2;
   fs_sysnum INT8;
   fs_rec RECORD;
   i INTEGER := 0;
BEGIN

   fs_sysnum := fs_id;

   SELECT into fs_rec acc.username, acc.access, fs.up, fs.sysnum, acc.sysnum as sysnumacc from fs left join acc on fs.sysnum = acc.sysnumfs and acc.username = address and (acc.expdate is NULL or acc.expdate >= 'now') where fs.sysnum = fs_sysnum;
   IF NOT FOUND THEN  RETURN -1; END IF;

   WHILE ((fs_rec.username <> address) OR (fs_rec.username IS NULL)) AND (fs_rec.up <> 0) AND (fs_rec.up IS NOT NULL) LOOP
       -- RAISE NOTICE 'username % up % sysnum %', fs_rec.username, fs_rec.up, fs_rec.sysnum;
       fs_sysnum := fs_rec.up;

       SELECT into fs_rec acc.username, acc.access, fs.up, fs.sysnum, acc.sysnum as sysnumacc from fs left join acc on fs.sysnum = acc.sysnumfs and acc.username = address and (acc.expdate is NULL or acc.expdate >= 'now') where fs.sysnum = fs_sysnum;
       IF NOT FOUND THEN  RETURN -1; END IF;
   END LOOP;

   IF ((fs_rec.username IS NOT NULL) AND (fs_rec.username = address)) THEN
       RAISE NOTICE 'get_acc(): fs_id % username % sysnum % access % sysnumacc %', fs_id, fs_rec.username, fs_rec.sysnum, fs_rec.access, fs_rec.sysnumacc;
       RETURN fs_rec.sysnumacc;
   END IF;

   RETURN 0;
END;
$_$
    LANGUAGE plpgsql;


ALTER FUNCTION public.get_acc(character varying, bigint) OWNER TO apache;

--
-- Name: getdomain(bigint); Type: FUNCTION; Schema: public; Owner: apache
--

CREATE FUNCTION getdomain(bigint) RETURNS integer
    AS $_$SELECT sysnumdomain FROM usr WHERE sysnum = $1;$_$
    LANGUAGE sql;


ALTER FUNCTION public.getdomain(bigint) OWNER TO apache;

--
-- Name: getpermission(character varying, bigint); Type: FUNCTION; Schema: public; Owner: apache
--

CREATE FUNCTION getpermission(character varying, bigint) RETURNS integer
    AS $_$
DECLARE
   address alias for $1;
   fs_id alias for $2;
   acc_rec RECORD;

BEGIN
     select into acc_rec * from acc where sysnum in (select get_acc(address, fs_id));
     IF NOT FOUND THEN  RETURN -1; END IF;


     RAISE NOTICE 'getpermission(): username % sysnum % access %', acc_rec.username, acc_rec.sysnum, acc_rec.access;
     IF acc_rec.access = 'n' THEN
       RETURN 0;
     ELSIF acc_rec.access = 'r' THEN
       RETURN 1;
     ELSIF acc_rec.access = 'u' THEN
       RETURN 2;
     ELSIF  acc_rec.access = 'w' THEN
       RETURN 3;
     END IF;

     RETURN 0;
END;
$_$
    LANGUAGE plpgsql;


ALTER FUNCTION public.getpermission(character varying, bigint) OWNER TO apache;

--
-- Name: gettree(bigint); Type: FUNCTION; Schema: public; Owner: apache
--

CREATE FUNCTION gettree(bigint) RETURNS character varying
    AS $_$
DECLARE
   fs_id alias for $1;
   fs_sysnum INT8; 
   fs_rec RECORD;
   PATH VARCHAR := '';
BEGIN
   fs_sysnum := fs_id;
    
   SELECT INTO fs_rec name, up, ftype from fs where sysnum = fs_sysnum;
   IF NOT FOUND THEN
     RAISE NOTICE 'gettree not found fs.sysnum %', fs_sysnum;
     RETURN NULL; 
   END IF;


   RAISE NOTICE 'ftype %', fs_rec.ftype;

   IF (fs_rec.ftype = 'a') THEN
     RAISE NOTICE 'Attachment';
     RETURN fs_rec.name;
   END IF;

   path := fs_rec.name;
   WHILE (fs_rec.up <> 0) AND (fs_rec.up IS NOT NULL) LOOP
      fs_sysnum := fs_rec.up;
      SELECT INTO fs_rec name, up from fs where sysnum = fs_sysnum and ftype = 'f';
      IF NOT FOUND THEN
        RETURN NULL;
      END IF;
      path := fs_rec.name || '/' || path;
   END LOOP;

   RETURN '/' || path;
END;
$_$
    LANGUAGE plpgsql;


ALTER FUNCTION public.gettree(bigint) OWNER TO apache;

--
-- Name: maxnumbilling(); Type: FUNCTION; Schema: public; Owner: apache
--

CREATE FUNCTION maxnumbilling() RETURNS bigint
    AS $$select max(sysnum) from billing;$$
    LANGUAGE sql;


ALTER FUNCTION public.maxnumbilling() OWNER TO apache;

--
-- Name: nextnumaddress(); Type: FUNCTION; Schema: public; Owner: apache
--

CREATE FUNCTION nextnumaddress() RETURNS bigint
    AS $$select nextval('address_seq') as result;$$
    LANGUAGE sql;


ALTER FUNCTION public.nextnumaddress() OWNER TO apache;

--
-- Name: nextnumdomain(); Type: FUNCTION; Schema: public; Owner: apache
--

CREATE FUNCTION nextnumdomain() RETURNS bigint
    AS $$select nextval('domain_seq') as result;$$
    LANGUAGE sql;


ALTER FUNCTION public.nextnumdomain() OWNER TO apache;

--
-- Name: nextnumfile(); Type: FUNCTION; Schema: public; Owner: apache
--

CREATE FUNCTION nextnumfile() RETURNS bigint
    AS $$select nextval('file_seq') as result;$$
    LANGUAGE sql;


ALTER FUNCTION public.nextnumfile() OWNER TO apache;

--
-- Name: nextnumfld(); Type: FUNCTION; Schema: public; Owner: apache
--

CREATE FUNCTION nextnumfld() RETURNS bigint
    AS $$select nextval('fld_seq') as result;$$
    LANGUAGE sql;


ALTER FUNCTION public.nextnumfld() OWNER TO apache;

--
-- Name: nextnumfs(); Type: FUNCTION; Schema: public; Owner: apache
--

CREATE FUNCTION nextnumfs() RETURNS bigint
    AS $$select nextval('fs_seq') as result;$$
    LANGUAGE sql;


ALTER FUNCTION public.nextnumfs() OWNER TO apache;

--
-- Name: nextnummsg(); Type: FUNCTION; Schema: public; Owner: apache
--

CREATE FUNCTION nextnummsg() RETURNS bigint
    AS $$select nextval('msg_seq') as result;$$
    LANGUAGE sql;


ALTER FUNCTION public.nextnummsg() OWNER TO apache;

--
-- Name: nextnummsgbody(); Type: FUNCTION; Schema: public; Owner: apache
--

CREATE FUNCTION nextnummsgbody() RETURNS bigint
    AS $$select nextval('msgbody_seq') as result;$$
    LANGUAGE sql;


ALTER FUNCTION public.nextnummsgbody() OWNER TO apache;

--
-- Name: nextnumusr(); Type: FUNCTION; Schema: public; Owner: apache
--

CREATE FUNCTION nextnumusr() RETURNS bigint
    AS $$select nextval('usr_seq') as result;$$
    LANGUAGE sql;


ALTER FUNCTION public.nextnumusr() OWNER TO apache;

--
-- Name: pastename(character varying, character varying, bigint, bigint); Type: FUNCTION; Schema: public; Owner: apache
--

CREATE FUNCTION pastename(character varying, character varying, bigint, bigint) RETURNS character varying
    AS $_$
DECLARE
    filename alias for $1;
    fileext alias for $2;
    usr_id alias for $3;
    fs_id alias for $4;
    tmpname VARCHAR;
    fs_rec RECORD;
    vers_num INT8;
BEGIN
  tmpname := filename || fileext;
  RAISE NOTICE 'tmpname %', tmpname;

  SELECT INTO fs_rec * FROM fs WHERE fs.ftype = 'f' AND fs.up = fs_id AND fs.owner = usr_id AND fs.name = tmpname;
  IF NOT FOUND THEN
    return tmpname;
  END IF;

  vers_num := 1;
  tmpname := filename || '[' || text(vers_num) || ']' || fileext;
  RAISE NOTICE 'tmpname %', tmpname;
  SELECT INTO fs_rec * FROM fs WHERE fs.ftype = 'f' AND fs.up = fs_id AND fs.owner = usr_id AND fs.name = tmpname;
  WHILE FOUND LOOP
    vers_num := vers_num + 1;
    tmpname := filename || '[' || text(vers_num) || ']' || fileext;
    RAISE NOTICE 'tmpname %', tmpname;
    SELECT INTO fs_rec * FROM fs WHERE fs.ftype = 'f' AND fs.up = fs_id AND fs.owner = usr_id AND fs.name = tmpname;
  END LOOP;

  RETURN tmpname;
END;
$_$
    LANGUAGE plpgsql;


ALTER FUNCTION public.pastename(character varying, character varying, bigint, bigint) OWNER TO apache;

--
-- Name: sign(integer); Type: FUNCTION; Schema: public; Owner: apache
--

CREATE FUNCTION sign(integer) RETURNS integer
    AS $_$select case when $1 <> 0 then 1 else 0 end as result;$_$
    LANGUAGE sql;


ALTER FUNCTION public.sign(integer) OWNER TO apache;

--
-- Name: sign(boolean); Type: FUNCTION; Schema: public; Owner: apache
--

CREATE FUNCTION sign(boolean) RETURNS integer
    AS $_$select case when $1 = true then 1 else 0 end as result;$_$
    LANGUAGE sql;


ALTER FUNCTION public.sign(boolean) OWNER TO apache;

--
-- Name: trproc_delete_file(); Type: FUNCTION; Schema: public; Owner: apache
--

CREATE FUNCTION trproc_delete_file() RETURNS "trigger"
    AS $$
BEGIN
   IF OLD.nlink <> 0 THEN
     RAISE NOTICE 'invalid erase. % link(s)', OLD.nlink;
     RETURN NULL;
   END IF;
   UPDATE storages SET used = used - OLD.fsize WHERE storages.sysnum = OLD.numstorage;
   RETURN OLD;
END;
$$
    LANGUAGE plpgsql;


ALTER FUNCTION public.trproc_delete_file() OWNER TO apache;

--
-- Name: trproc_delete_fs(); Type: FUNCTION; Schema: public; Owner: apache
--

CREATE FUNCTION trproc_delete_fs() RETURNS "trigger"
    AS $$
BEGIN
   UPDATE usr SET diskusage = diskusage - COALESCE((SELECT fsize from file where sysnum = OLD.sysnumfile), 0) where usr.sysnum = OLD.owner;
   UPDATE domain SET diskusage = diskusage - COALESCE((SELECT fsize from file where sysnum = OLD.sysnumfile), 0) where usr.sysnum = OLD.owner and usr.sysnumdomain = domain.sysnum;
   UPDATE file set NLINK = NLINK -  1, lastmodify = 'now' where sysnum = OLD.sysnumfile;
   DELETE from acc  where sysnumfs = OLD.sysnum;
   DELETE from Clip where sysnumfs = OLD.sysnum;

   RETURN OLD;
END;
$$
    LANGUAGE plpgsql;


ALTER FUNCTION public.trproc_delete_fs() OWNER TO apache;

--
-- Name: trproc_delete_msg(); Type: FUNCTION; Schema: public; Owner: apache
--

CREATE FUNCTION trproc_delete_msg() RETURNS "trigger"
    AS $$
BEGIN
   UPDATE usr SET diskusage = diskusage - COALESCE(OLD.size, 0) where OLD.sysnumfld = fld.sysnum and fld.sysnumusr = usr.sysnum;
   UPDATE domain SET diskusage = diskusage - COALESCE(OLD.size, 0) where OLD.sysnumfld = fld.sysnum and fld.sysnumusr = usr.sysnum and usr.sysnumdomain = domain.sysnum;
   DELETE FROM fs WHERE ftype = 'a' and up = OLD.sysnum;
   DELETE FROM msgbody WHERE sysnummsg = OLD.sysnum;
   DELETE FROM msgflag WHERE sysnummsg = OLD.sysnum;
   DELETE FROM msgheader WHERE sysnummsg = OLD.sysnum;
   RETURN OLD;
END;
$$
    LANGUAGE plpgsql;


ALTER FUNCTION public.trproc_delete_msg() OWNER TO apache;

--
-- Name: trproc_insert_billing(); Type: FUNCTION; Schema: public; Owner: apache
--

CREATE FUNCTION trproc_insert_billing() RETURNS "trigger"
    AS $$
DECLARE
   rec RECORD;
BEGIN
   RAISE NOTICE 'sysnumusr %', NEW.sysnumusr;


   SELECT INTO rec usr.sysnum AS usrsysnum, usr.name AS usrname, domain.sysnum AS domainsysnum, domain.name AS domainname FROM usr, domain WHERE usr.sysnumdomain = domain.sysnum AND usr.sysnum = NEW.sysnumusr;
   IF FOUND THEN
       RAISE NOTICE 'sysnumdomain %', rec.domainsysnum;

       NEW.sysnumdomain = rec.domainsysnum;
       NEW.namedomain   = rec.domainname;
       NEW.nameusr      = rec.usrname;
   END IF;

  RETURN NEW;
END;
$$
    LANGUAGE plpgsql;


ALTER FUNCTION public.trproc_insert_billing() OWNER TO apache;

--
-- Name: trproc_insert_file(); Type: FUNCTION; Schema: public; Owner: apache
--

CREATE FUNCTION trproc_insert_file() RETURNS "trigger"
    AS $$
BEGIN
   UPDATE storages SET used = used + NEW.fsize WHERE storages.sysnum = NEW.numstorage;
   RETURN NEW;
END;
$$
    LANGUAGE plpgsql;


ALTER FUNCTION public.trproc_insert_file() OWNER TO apache;

--
-- Name: trproc_insert_fs(); Type: FUNCTION; Schema: public; Owner: apache
--

CREATE FUNCTION trproc_insert_fs() RETURNS "trigger"
    AS $$
BEGIN
   UPDATE usr SET diskusage = diskusage + COALESCE((SELECT fsize from file where sysnum = NEW.sysnumfile), 0) where usr.sysnum = NEW.owner;
   UPDATE domain SET diskusage = diskusage + COALESCE((SELECT fsize from file where sysnum = NEW.sysnumfile), 0) where usr.sysnum = NEW.owner and usr.sysnumdomain = domain.sysnum;
   UPDATE file set NLINK = NLINK +  1, lastmodify = 'now' where sysnum = NEW.sysnumfile;
   RETURN NEW;
END;
$$
    LANGUAGE plpgsql;


ALTER FUNCTION public.trproc_insert_fs() OWNER TO apache;

--
-- Name: trproc_insert_msg(); Type: FUNCTION; Schema: public; Owner: apache
--

CREATE FUNCTION trproc_insert_msg() RETURNS "trigger"
    AS $$
BEGIN
   UPDATE usr SET diskusage = diskusage + COALESCE(NEW.size, 0) where NEW.sysnumfld = fld.sysnum and fld.sysnumusr = usr.sysnum;
   UPDATE domain SET diskusage = diskusage + COALESCE(NEW.size, 0) where NEW.sysnumfld = fld.sysnum and fld.sysnumusr = usr.sysnum and usr.sysnumdomain = domain.sysnum;

   RETURN NEW;
END;
$$
    LANGUAGE plpgsql;


ALTER FUNCTION public.trproc_insert_msg() OWNER TO apache;

--
-- Name: trproc_update_file(); Type: FUNCTION; Schema: public; Owner: apache
--

CREATE FUNCTION trproc_update_file() RETURNS "trigger"
    AS $$
BEGIN
   IF (NEW.fsize <> OLD.fsize OR NEW.numstorage <> OLD.numstorage) THEN
      RAISE NOTICE 'trproc_update_file % %', NEW.fsize, OLD.fsize;
      UPDATE storages SET used = used - OLD.fsize WHERE storages.sysnum = OLD.numstorage;
      UPDATE storages SET used = used + NEW.fsize WHERE storages.sysnum = NEW.numstorage;
      UPDATE usr SET diskusage = diskusage - OLD.fsize + NEW.fsize WHERE file.sysnum = OLD.sysnum AND fs.sysnumfile = file.sysnum AND fs.owner = usr.sysnum;
      UPDATE domain SET diskusage = diskusage - OLD.fsize + NEW.fsize WHERE file.sysnum = OLD.sysnum AND fs.sysnumfile = file.sysnum AND fs.owner = usr.sysnum AND usr.sysnumdomain = domain.sysnum;
   END IF;
   IF (NEW.sysnum <> OLD.sysnum) THEN
      RAISE NOTICE 'trproc_update_file % %', NEW.sysnum, OLD.sysnum;
      UPDATE fs SET sysnumfile = NEW.sysnum WHERE sysnumfile = OLD.sysnum;
   END IF;
   RETURN OLD;
END;
$$
    LANGUAGE plpgsql;


ALTER FUNCTION public.trproc_update_file() OWNER TO apache;

--
-- Name: trproc_update_fs(); Type: FUNCTION; Schema: public; Owner: apache
--

CREATE FUNCTION trproc_update_fs() RETURNS "trigger"
    AS $$
BEGIN
   IF (NEW.sysnumfile <> OLD.sysnumfile OR NEW.owner <> OLD.owner) THEN
      UPDATE usr SET diskusage = diskusage - COALESCE((SELECT fsize from file where sysnum = OLD.sysnumfile), 0) where usr.sysnum = OLD.owner;
      UPDATE domain SET diskusage = diskusage - COALESCE((SELECT fsize from file where sysnum = OLD.sysnumfile), 0) where usr.sysnum = OLD.owner and usr.sysnumdomain = domain.sysnum;
      UPDATE file set NLINK = NLINK -  1, lastmodify = 'now' where sysnum = OLD.sysnumfile;

      UPDATE usr SET diskusage = diskusage + COALESCE((SELECT fsize from file where sysnum = NEW.sysnumfile), 0) where usr.sysnum = NEW.owner;
      UPDATE domain SET diskusage = diskusage + COALESCE((SELECT fsize from file where sysnum = NEW.sysnumfile), 0) where usr.sysnum = NEW.owner and usr.sysnumdomain = domain.sysnum;
      UPDATE file set NLINK = NLINK +  1, lastmodify = 'now' where sysnum = NEW.sysnumfile;
   END IF;
   RETURN NULL;
END;
$$
    LANGUAGE plpgsql;


ALTER FUNCTION public.trproc_update_fs() OWNER TO apache;

--
-- Name: trproc_update_msg(); Type: FUNCTION; Schema: public; Owner: apache
--

CREATE FUNCTION trproc_update_msg() RETURNS "trigger"
    AS $$
BEGIN
   IF (NEW.size <> OLD.size OR NEW.sysnumfld <> OLD.sysnumfld) THEN

   RAISE NOTICE 'trproc_update_msg';


      UPDATE usr SET diskusage = diskusage - COALESCE(OLD.size, 0) where OLD.sysnumfld = fld.sysnum and fld.sysnumusr = usr.sysnum;
      UPDATE domain SET diskusage = diskusage - COALESCE(OLD.size, 0) where OLD.sysnumfld = fld.sysnum and fld.sysnumusr = usr.sysnum and usr.sysnumdomain = domain.sysnum;

      UPDATE usr SET diskusage = diskusage + COALESCE(NEW.size, 0) where NEW.sysnumfld = fld.sysnum and fld.sysnumusr = usr.sysnum;
      UPDATE domain SET diskusage = diskusage + COALESCE(NEW.size, 0) where NEW.sysnumfld = fld.sysnum and fld.sysnumusr = usr.sysnum and usr.sysnumdomain = domain.sysnum;
   END IF;
   RETURN NULL;
END;
$$
    LANGUAGE plpgsql;


ALTER FUNCTION public.trproc_update_msg() OWNER TO apache;

SET default_tablespace = '';

SET default_with_oids = true;

--
-- Name: acc; Type: TABLE; Schema: public; Owner: apache; Tablespace: 
--

CREATE TABLE acc (
    sysnumfs bigint,
    username character varying(150),
    "access" character(1),
    expdate timestamp with time zone,
    access_tracking character(1) DEFAULT 0,
    sysnum bigint DEFAULT nextval('acc_seq'::text) NOT NULL,
    hash character(32),
    created timestamp with time zone DEFAULT '2004-10-06 01:01:07.978841'::timestamp without time zone
);


ALTER TABLE public.acc OWNER TO apache;

--
-- Name: acc_seq; Type: SEQUENCE; Schema: public; Owner: apache
--

CREATE SEQUENCE acc_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.acc_seq OWNER TO apache;

--
-- Name: address; Type: TABLE; Schema: public; Owner: apache; Tablespace: 
--

CREATE TABLE address (
    sysnum bigint DEFAULT nextval('address_seq'::text) NOT NULL,
    sysnumusr bigint,
    name character varying(50) DEFAULT ''::character varying,
    middlename character varying(50) DEFAULT ''::character varying,
    lastname character varying(50) DEFAULT ''::character varying,
    company character varying(50) DEFAULT ''::character varying,
    title character varying(50) DEFAULT ''::character varying,
    mailto character varying(50) DEFAULT ''::character varying,
    home_address character varying(50) DEFAULT ''::character varying,
    home_city character varying(50) DEFAULT ''::character varying,
    home_state character varying(50) DEFAULT ''::character varying,
    home_country character varying(50) DEFAULT ''::character varying,
    home_zip character varying(50) DEFAULT ''::character varying,
    home_phone character varying(50) DEFAULT ''::character varying,
    home_phone1 character varying(50) DEFAULT ''::character varying,
    home_phone2 character varying(50) DEFAULT ''::character varying,
    home_fax character varying(50) DEFAULT ''::character varying,
    home_mphone character varying(50) DEFAULT ''::character varying,
    home_icq character varying(50) DEFAULT ''::character varying,
    home_page character varying(50) DEFAULT ''::character varying,
    biss_address character varying(50) DEFAULT ''::character varying,
    biss_office character varying(50) DEFAULT ''::character varying,
    biss_city character varying(50) DEFAULT ''::character varying,
    biss_state character varying(50) DEFAULT ''::character varying,
    biss_country character varying(50) DEFAULT ''::character varying,
    biss_zip character varying(50) DEFAULT ''::character varying,
    biss_phone character varying(50) DEFAULT ''::character varying,
    biss_phone1 character varying(50) DEFAULT ''::character varying,
    biss_phone2 character varying(50) DEFAULT ''::character varying,
    biss_fax character varying(50) DEFAULT ''::character varying,
    biss_mphone character varying(50) DEFAULT ''::character varying,
    biss_page character varying(50) DEFAULT ''::character varying,
    profession character varying(50) DEFAULT ''::character varying
);


ALTER TABLE public.address OWNER TO apache;

--
-- Name: address_seq; Type: SEQUENCE; Schema: public; Owner: apache
--

CREATE SEQUENCE address_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.address_seq OWNER TO apache;

--
-- Name: billing; Type: TABLE; Schema: public; Owner: apache; Tablespace: 
--

CREATE TABLE billing (
    sysnumusr bigint NOT NULL,
    date timestamp without time zone DEFAULT '2006-05-18 00:01:33.827805'::timestamp without time zone NOT NULL,
    traficsize bigint DEFAULT 0,
    sysnumfs bigint DEFAULT 0,
    who character(20),
    kind character(15),
    direct integer DEFAULT 0 NOT NULL,
    sysnum bigint DEFAULT nextval('billing_seq'::text) NOT NULL,
    ip inet,
    namedomain character varying(30) DEFAULT ''::character varying,
    nameusr character varying(30) DEFAULT ''::character varying,
    sysnumdomain bigint DEFAULT 0 NOT NULL
);


ALTER TABLE public.billing OWNER TO apache;

--
-- Name: billing_seq; Type: SEQUENCE; Schema: public; Owner: apache
--

CREATE SEQUENCE billing_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.billing_seq OWNER TO apache;

--
-- Name: chat; Type: TABLE; Schema: public; Owner: apache; Tablespace: 
--

CREATE TABLE chat (
    send timestamp with time zone,
    usrfrom bigint,
    usrto bigint,
    message character varying(2048)
);


ALTER TABLE public.chat OWNER TO apache;

--
-- Name: clip; Type: TABLE; Schema: public; Owner: apache; Tablespace: 
--

CREATE TABLE clip (
    "owner" character varying(150),
    sysnumfs bigint,
    ftype character(1)
);


ALTER TABLE public.clip OWNER TO apache;

--
-- Name: datebook; Type: TABLE; Schema: public; Owner: apache; Tablespace: 
--

CREATE TABLE datebook (
    sysnum bigint DEFAULT nextval('datebook_seq'::text) NOT NULL,
    sysnumusr bigint DEFAULT 0,
    begindate timestamp without time zone DEFAULT '2006-05-18 00:01:33.966663'::timestamp without time zone,
    enddate timestamp without time zone DEFAULT '2006-05-18 00:01:33.966663'::timestamp without time zone,
    subject character varying(128),
    memo text,
    complete boolean DEFAULT false
);


ALTER TABLE public.datebook OWNER TO apache;

--
-- Name: datebook_seq; Type: SEQUENCE; Schema: public; Owner: apache
--

CREATE SEQUENCE datebook_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.datebook_seq OWNER TO apache;

--
-- Name: domain; Type: TABLE; Schema: public; Owner: apache; Tablespace: 
--

CREATE TABLE "domain" (
    sysnum integer DEFAULT nextval('domain_seq'::text) NOT NULL,
    name character varying(30),
    diskusage bigint DEFAULT 0,
    signup character(1) DEFAULT 0,
    "quote" bigint DEFAULT 10737418240::bigint,
    userquote bigint DEFAULT (1048576)::bigint,
    trialsignup character(1) DEFAULT 0,
    showdomainaddress character(1) DEFAULT 0,
    maxusrnum bigint DEFAULT 0,
    admin character varying(30) DEFAULT ''::character varying,
    coment character varying(50)
);


ALTER TABLE public."domain" OWNER TO apache;

--
-- Name: domain_seq; Type: SEQUENCE; Schema: public; Owner: apache
--

CREATE SEQUENCE domain_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.domain_seq OWNER TO apache;

--
-- Name: file; Type: TABLE; Schema: public; Owner: apache; Tablespace: 
--

CREATE TABLE file (
    sysnum bigint DEFAULT nextval('file_seq'::text) NOT NULL,
    fsize integer DEFAULT 0,
    ftype character varying(32) DEFAULT ''::character varying,
    fcrc character varying(32) DEFAULT ''::character varying,
    url character varying(128),
    nlink bigint DEFAULT 0,
    nseq integer DEFAULT 0 NOT NULL,
    numstorage integer DEFAULT 0,
    lastmodify timestamp with time zone DEFAULT '2006-05-18 00:01:33.664+03'::timestamp with time zone
);


ALTER TABLE public.file OWNER TO apache;

--
-- Name: file_seq; Type: SEQUENCE; Schema: public; Owner: apache
--

CREATE SEQUENCE file_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.file_seq OWNER TO apache;

--
-- Name: fld; Type: TABLE; Schema: public; Owner: apache; Tablespace: 
--

CREATE TABLE fld (
    sysnum bigint DEFAULT nextval('fld_seq'::text) NOT NULL,
    sysnumusr integer,
    name character varying(30),
    sort character(1),
    ftype integer,
    fnew integer DEFAULT 0
);


ALTER TABLE public.fld OWNER TO apache;

--
-- Name: fld_seq; Type: SEQUENCE; Schema: public; Owner: apache
--

CREATE SEQUENCE fld_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.fld_seq OWNER TO apache;

--
-- Name: fs; Type: TABLE; Schema: public; Owner: apache; Tablespace: 
--

CREATE TABLE fs (
    sysnum bigint DEFAULT nextval('fs_seq'::text) NOT NULL,
    ftype character(1) DEFAULT 'f'::bpchar,
    up bigint DEFAULT 0 NOT NULL,
    name character varying(150) DEFAULT 'noname'::character varying,
    "owner" integer DEFAULT 0,
    sysnumfile bigint DEFAULT 0,
    creat timestamp with time zone DEFAULT '2006-05-18 00:01:33.725369+03'::timestamp with time zone,
    name_charset character varying(15),
    rem character varying(200)
);


ALTER TABLE public.fs OWNER TO apache;

--
-- Name: fs_seq; Type: SEQUENCE; Schema: public; Owner: apache
--

CREATE SEQUENCE fs_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.fs_seq OWNER TO apache;

--
-- Name: grpaddress; Type: TABLE; Schema: public; Owner: apache; Tablespace: 
--

CREATE TABLE grpaddress (
    sysnumusr integer,
    name character varying(50),
    sysnumaddress bigint DEFAULT 0,
    ftype character(1) DEFAULT 'f'::bpchar,
    sysnum bigint DEFAULT nextval('grpaddress_seq'::text) NOT NULL
);


ALTER TABLE public.grpaddress OWNER TO apache;

--
-- Name: grpaddress_seq; Type: SEQUENCE; Schema: public; Owner: apache
--

CREATE SEQUENCE grpaddress_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.grpaddress_seq OWNER TO apache;

--
-- Name: infservis; Type: TABLE; Schema: public; Owner: apache; Tablespace: 
--

CREATE TABLE infservis (
    sysnum bigint DEFAULT nextval('infservis_seq'::text) NOT NULL,
    tread bigint DEFAULT 0 NOT NULL,
    name character varying(20) DEFAULT ''::character varying NOT NULL,
    value text,
    nseq bigint DEFAULT 0 NOT NULL
);


ALTER TABLE public.infservis OWNER TO apache;

--
-- Name: infservis_seq; Type: SEQUENCE; Schema: public; Owner: apache
--

CREATE SEQUENCE infservis_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.infservis_seq OWNER TO apache;

--
-- Name: msg; Type: TABLE; Schema: public; Owner: apache; Tablespace: 
--

CREATE TABLE msg (
    sysnum bigint DEFAULT nextval('msg_seq'::text) NOT NULL,
    sysnumfld bigint,
    id character varying(150),
    content character varying(100),
    addrto character varying(255),
    addrfrom character varying(255),
    subj character varying(300),
    size bigint,
    fnew boolean,
    send timestamp with time zone,
    recev timestamp with time zone,
    charset character varying(20) DEFAULT ''::character varying,
    flag bigint DEFAULT 0
);


ALTER TABLE public.msg OWNER TO apache;

--
-- Name: msg_seq; Type: SEQUENCE; Schema: public; Owner: apache
--

CREATE SEQUENCE msg_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.msg_seq OWNER TO apache;

--
-- Name: msgbody; Type: TABLE; Schema: public; Owner: apache; Tablespace: 
--

CREATE TABLE msgbody (
    sysnum bigint NOT NULL,
    sysnummsg bigint,
    body text
);


ALTER TABLE public.msgbody OWNER TO apache;

--
-- Name: msgbody_seq; Type: SEQUENCE; Schema: public; Owner: apache
--

CREATE SEQUENCE msgbody_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.msgbody_seq OWNER TO apache;

--
-- Name: msgflag; Type: TABLE; Schema: public; Owner: apache; Tablespace: 
--

CREATE TABLE msgflag (
    sysnummsg bigint NOT NULL,
    name character varying(20) NOT NULL,
    value character varying(200),
    nset integer DEFAULT 0 NOT NULL
);


ALTER TABLE public.msgflag OWNER TO apache;

--
-- Name: msgheader; Type: TABLE; Schema: public; Owner: apache; Tablespace: 
--

CREATE TABLE msgheader (
    sysnum bigint DEFAULT nextval('msgheader_seq'::text) NOT NULL,
    sysnummsg bigint,
    headerline character varying(255)
);


ALTER TABLE public.msgheader OWNER TO apache;

--
-- Name: msgheader_seq; Type: SEQUENCE; Schema: public; Owner: apache
--

CREATE SEQUENCE msgheader_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.msgheader_seq OWNER TO apache;

--
-- Name: payment; Type: TABLE; Schema: public; Owner: apache; Tablespace: 
--

CREATE TABLE payment (
    sysnum bigint DEFAULT nextval('payment_seq'::text) NOT NULL,
    txn_id character varying(127),
    item_name character varying(127),
    item_number character varying(127),
    payment_status character varying(127),
    mc_gross character varying(127),
    mc_currency character varying(127),
    receiver_email character varying(127),
    payer_email character varying(127),
    post_result character varying(127),
    txn_type character varying(127)
);


ALTER TABLE public.payment OWNER TO apache;

--
-- Name: payment_seq; Type: SEQUENCE; Schema: public; Owner: apache
--

CREATE SEQUENCE payment_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.payment_seq OWNER TO apache;

--
-- Name: storages; Type: TABLE; Schema: public; Owner: apache; Tablespace: 
--

CREATE TABLE storages (
    sysnum integer,
    size bigint,
    used bigint
);


ALTER TABLE public.storages OWNER TO apache;

--
-- Name: usr; Type: TABLE; Schema: public; Owner: apache; Tablespace: 
--

CREATE TABLE usr (
    sysnum integer DEFAULT nextval('usr_seq'::text) NOT NULL,
    sysnumdomain integer,
    name character varying(30),
    "password" character varying(30),
    lev integer,
    country integer DEFAULT 0,
    creat timestamp with time zone,
    mod timestamp with time zone,
    diskusage bigint DEFAULT 0,
    "quote" bigint DEFAULT 0,
    lastenter timestamp with time zone
);


ALTER TABLE public.usr OWNER TO apache;

--
-- Name: usr_seq; Type: SEQUENCE; Schema: public; Owner: apache
--

CREATE SEQUENCE usr_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.usr_seq OWNER TO apache;

--
-- Name: usr_ua; Type: TABLE; Schema: public; Owner: apache; Tablespace: 
--

CREATE TABLE usr_ua (
    sysnumusr bigint NOT NULL,
    name character varying(30) NOT NULL,
    value character varying(120),
    nset integer DEFAULT 0 NOT NULL,
    sysnum bigint DEFAULT nextval('usr_ua_seq'::text) NOT NULL
);


ALTER TABLE public.usr_ua OWNER TO apache;

--
-- Name: usr_ua_seq; Type: SEQUENCE; Schema: public; Owner: apache
--

CREATE SEQUENCE usr_ua_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.usr_ua_seq OWNER TO apache;

--
-- Name: acc_pkey; Type: CONSTRAINT; Schema: public; Owner: apache; Tablespace: 
--

ALTER TABLE ONLY acc
    ADD CONSTRAINT acc_pkey PRIMARY KEY (sysnum);


ALTER INDEX public.acc_pkey OWNER TO apache;

--
-- Name: address_pkey; Type: CONSTRAINT; Schema: public; Owner: apache; Tablespace: 
--

ALTER TABLE ONLY address
    ADD CONSTRAINT address_pkey PRIMARY KEY (sysnum);


ALTER INDEX public.address_pkey OWNER TO apache;

--
-- Name: billing_pkey; Type: CONSTRAINT; Schema: public; Owner: apache; Tablespace: 
--

ALTER TABLE ONLY billing
    ADD CONSTRAINT billing_pkey PRIMARY KEY (sysnum);


ALTER INDEX public.billing_pkey OWNER TO apache;

--
-- Name: datebook_pkey; Type: CONSTRAINT; Schema: public; Owner: apache; Tablespace: 
--

ALTER TABLE ONLY datebook
    ADD CONSTRAINT datebook_pkey PRIMARY KEY (sysnum);


ALTER INDEX public.datebook_pkey OWNER TO apache;

--
-- Name: domain_pkey; Type: CONSTRAINT; Schema: public; Owner: apache; Tablespace: 
--

ALTER TABLE ONLY "domain"
    ADD CONSTRAINT domain_pkey PRIMARY KEY (sysnum);


ALTER INDEX public.domain_pkey OWNER TO apache;

--
-- Name: file_pkey; Type: CONSTRAINT; Schema: public; Owner: apache; Tablespace: 
--

ALTER TABLE ONLY file
    ADD CONSTRAINT file_pkey PRIMARY KEY (sysnum, nseq);


ALTER INDEX public.file_pkey OWNER TO apache;

--
-- Name: fld_pkey; Type: CONSTRAINT; Schema: public; Owner: apache; Tablespace: 
--

ALTER TABLE ONLY fld
    ADD CONSTRAINT fld_pkey PRIMARY KEY (sysnum);


ALTER INDEX public.fld_pkey OWNER TO apache;

--
-- Name: fs_pkey; Type: CONSTRAINT; Schema: public; Owner: apache; Tablespace: 
--

ALTER TABLE ONLY fs
    ADD CONSTRAINT fs_pkey PRIMARY KEY (sysnum);


ALTER INDEX public.fs_pkey OWNER TO apache;

--
-- Name: infservis_pkey; Type: CONSTRAINT; Schema: public; Owner: apache; Tablespace: 
--

ALTER TABLE ONLY infservis
    ADD CONSTRAINT infservis_pkey PRIMARY KEY (sysnum);


ALTER INDEX public.infservis_pkey OWNER TO apache;

--
-- Name: msg_pkey; Type: CONSTRAINT; Schema: public; Owner: apache; Tablespace: 
--

ALTER TABLE ONLY msg
    ADD CONSTRAINT msg_pkey PRIMARY KEY (sysnum);


ALTER INDEX public.msg_pkey OWNER TO apache;

--
-- Name: msgbody_pkey; Type: CONSTRAINT; Schema: public; Owner: apache; Tablespace: 
--

ALTER TABLE ONLY msgbody
    ADD CONSTRAINT msgbody_pkey PRIMARY KEY (sysnum);


ALTER INDEX public.msgbody_pkey OWNER TO apache;

--
-- Name: msgflag_pkey; Type: CONSTRAINT; Schema: public; Owner: apache; Tablespace: 
--

ALTER TABLE ONLY msgflag
    ADD CONSTRAINT msgflag_pkey PRIMARY KEY (sysnummsg, name, nset);


ALTER INDEX public.msgflag_pkey OWNER TO apache;

--
-- Name: msgheader_pkey; Type: CONSTRAINT; Schema: public; Owner: apache; Tablespace: 
--

ALTER TABLE ONLY msgheader
    ADD CONSTRAINT msgheader_pkey PRIMARY KEY (sysnum);


ALTER INDEX public.msgheader_pkey OWNER TO apache;

--
-- Name: usr_pkey; Type: CONSTRAINT; Schema: public; Owner: apache; Tablespace: 
--

ALTER TABLE ONLY usr
    ADD CONSTRAINT usr_pkey PRIMARY KEY (sysnum);


ALTER INDEX public.usr_pkey OWNER TO apache;

--
-- Name: usr_ua_pkey; Type: CONSTRAINT; Schema: public; Owner: apache; Tablespace: 
--

ALTER TABLE ONLY usr_ua
    ADD CONSTRAINT usr_ua_pkey PRIMARY KEY (sysnumusr, name, nset);


ALTER INDEX public.usr_ua_pkey OWNER TO apache;

--
-- Name: acc_key1; Type: INDEX; Schema: public; Owner: apache; Tablespace: 
--

CREATE INDEX acc_key1 ON acc USING btree (sysnumfs);


ALTER INDEX public.acc_key1 OWNER TO apache;

--
-- Name: acc_key2; Type: INDEX; Schema: public; Owner: apache; Tablespace: 
--

CREATE INDEX acc_key2 ON acc USING btree (username);


ALTER INDEX public.acc_key2 OWNER TO apache;

--
-- Name: acc_key3; Type: INDEX; Schema: public; Owner: apache; Tablespace: 
--

CREATE INDEX acc_key3 ON acc USING btree ("access", username, sysnumfs);


ALTER INDEX public.acc_key3 OWNER TO apache;

--
-- Name: address_key1; Type: INDEX; Schema: public; Owner: apache; Tablespace: 
--

CREATE INDEX address_key1 ON address USING btree (sysnumusr, sysnum);


ALTER INDEX public.address_key1 OWNER TO apache;

--
-- Name: address_key2; Type: INDEX; Schema: public; Owner: apache; Tablespace: 
--

CREATE INDEX address_key2 ON address USING btree (name, sysnum);


ALTER INDEX public.address_key2 OWNER TO apache;

--
-- Name: billing_key1; Type: INDEX; Schema: public; Owner: apache; Tablespace: 
--

CREATE INDEX billing_key1 ON billing USING btree (date);


ALTER INDEX public.billing_key1 OWNER TO apache;

--
-- Name: billing_key2; Type: INDEX; Schema: public; Owner: apache; Tablespace: 
--

CREATE INDEX billing_key2 ON billing USING btree (sysnumusr, date);


ALTER INDEX public.billing_key2 OWNER TO apache;

--
-- Name: datebook_sysnumusr; Type: INDEX; Schema: public; Owner: apache; Tablespace: 
--

CREATE UNIQUE INDEX datebook_sysnumusr ON datebook USING btree (sysnumusr, sysnum);


ALTER INDEX public.datebook_sysnumusr OWNER TO apache;

--
-- Name: domain_key1; Type: INDEX; Schema: public; Owner: apache; Tablespace: 
--

CREATE UNIQUE INDEX domain_key1 ON "domain" USING btree (name);


ALTER INDEX public.domain_key1 OWNER TO apache;

--
-- Name: file_key1; Type: INDEX; Schema: public; Owner: apache; Tablespace: 
--

CREATE INDEX file_key1 ON file USING btree (fsize, ftype, fcrc);


ALTER INDEX public.file_key1 OWNER TO apache;

--
-- Name: file_key2; Type: INDEX; Schema: public; Owner: apache; Tablespace: 
--

CREATE INDEX file_key2 ON file USING btree (fsize, fcrc);


ALTER INDEX public.file_key2 OWNER TO apache;

--
-- Name: fld_key1; Type: INDEX; Schema: public; Owner: apache; Tablespace: 
--

CREATE INDEX fld_key1 ON fld USING btree (sysnumusr);


ALTER INDEX public.fld_key1 OWNER TO apache;

--
-- Name: fld_key2; Type: INDEX; Schema: public; Owner: apache; Tablespace: 
--

CREATE UNIQUE INDEX fld_key2 ON fld USING btree (name, sysnumusr);


ALTER INDEX public.fld_key2 OWNER TO apache;

--
-- Name: fs_name; Type: INDEX; Schema: public; Owner: apache; Tablespace: 
--

CREATE UNIQUE INDEX fs_name ON fs USING btree (name, sysnum);


ALTER INDEX public.fs_name OWNER TO apache;

--
-- Name: fs_owner; Type: INDEX; Schema: public; Owner: apache; Tablespace: 
--

CREATE INDEX fs_owner ON fs USING btree ("owner");


ALTER INDEX public.fs_owner OWNER TO apache;

--
-- Name: fs_sysnumfile; Type: INDEX; Schema: public; Owner: apache; Tablespace: 
--

CREATE INDEX fs_sysnumfile ON fs USING btree (sysnumfile);


ALTER INDEX public.fs_sysnumfile OWNER TO apache;

--
-- Name: fs_up; Type: INDEX; Schema: public; Owner: apache; Tablespace: 
--

CREATE INDEX fs_up ON fs USING btree (up, ftype);


ALTER INDEX public.fs_up OWNER TO apache;

--
-- Name: infservis_key1; Type: INDEX; Schema: public; Owner: apache; Tablespace: 
--

CREATE UNIQUE INDEX infservis_key1 ON infservis USING btree (tread, name, nseq);


ALTER INDEX public.infservis_key1 OWNER TO apache;

--
-- Name: infservis_key2; Type: INDEX; Schema: public; Owner: apache; Tablespace: 
--

CREATE UNIQUE INDEX infservis_key2 ON infservis USING btree (name, value, nseq, sysnum);


ALTER INDEX public.infservis_key2 OWNER TO apache;

--
-- Name: msg_key1; Type: INDEX; Schema: public; Owner: apache; Tablespace: 
--

CREATE INDEX msg_key1 ON msg USING btree (sysnumfld);


ALTER INDEX public.msg_key1 OWNER TO apache;

--
-- Name: msgbody_key1; Type: INDEX; Schema: public; Owner: apache; Tablespace: 
--

CREATE INDEX msgbody_key1 ON msgbody USING btree (sysnummsg, sysnum);


ALTER INDEX public.msgbody_key1 OWNER TO apache;

--
-- Name: usr_key1; Type: INDEX; Schema: public; Owner: apache; Tablespace: 
--

CREATE INDEX usr_key1 ON usr USING btree (sysnumdomain);


ALTER INDEX public.usr_key1 OWNER TO apache;

--
-- Name: usr_key2; Type: INDEX; Schema: public; Owner: apache; Tablespace: 
--

CREATE UNIQUE INDEX usr_key2 ON usr USING btree (name, sysnumdomain);


ALTER INDEX public.usr_key2 OWNER TO apache;

--
-- Name: tr_delete_file; Type: TRIGGER; Schema: public; Owner: apache
--

CREATE TRIGGER tr_delete_file
    BEFORE DELETE ON file
    FOR EACH ROW
    EXECUTE PROCEDURE trproc_delete_file();


--
-- Name: tr_delete_fs; Type: TRIGGER; Schema: public; Owner: apache
--

CREATE TRIGGER tr_delete_fs
    BEFORE DELETE ON fs
    FOR EACH ROW
    EXECUTE PROCEDURE trproc_delete_fs();


--
-- Name: tr_delete_msg; Type: TRIGGER; Schema: public; Owner: apache
--

CREATE TRIGGER tr_delete_msg
    BEFORE DELETE ON msg
    FOR EACH ROW
    EXECUTE PROCEDURE trproc_delete_msg();


--
-- Name: tr_insert_billing; Type: TRIGGER; Schema: public; Owner: apache
--

CREATE TRIGGER tr_insert_billing
    BEFORE INSERT ON billing
    FOR EACH ROW
    EXECUTE PROCEDURE trproc_insert_billing();


--
-- Name: tr_insert_file; Type: TRIGGER; Schema: public; Owner: apache
--

CREATE TRIGGER tr_insert_file
    AFTER INSERT ON file
    FOR EACH ROW
    EXECUTE PROCEDURE trproc_insert_file();


--
-- Name: tr_insert_fs; Type: TRIGGER; Schema: public; Owner: apache
--

CREATE TRIGGER tr_insert_fs
    AFTER INSERT ON fs
    FOR EACH ROW
    EXECUTE PROCEDURE trproc_insert_fs();


--
-- Name: tr_insert_msg; Type: TRIGGER; Schema: public; Owner: apache
--

CREATE TRIGGER tr_insert_msg
    AFTER INSERT ON msg
    FOR EACH ROW
    EXECUTE PROCEDURE trproc_insert_msg();


--
-- Name: tr_update_file; Type: TRIGGER; Schema: public; Owner: apache
--

CREATE TRIGGER tr_update_file
    AFTER UPDATE ON file
    FOR EACH ROW
    EXECUTE PROCEDURE trproc_update_file();


--
-- Name: tr_update_fs; Type: TRIGGER; Schema: public; Owner: apache
--

CREATE TRIGGER tr_update_fs
    AFTER UPDATE ON fs
    FOR EACH ROW
    EXECUTE PROCEDURE trproc_update_fs();


--
-- Name: tr_update_msg; Type: TRIGGER; Schema: public; Owner: apache
--

CREATE TRIGGER tr_update_msg
    AFTER UPDATE ON msg
    FOR EACH ROW
    EXECUTE PROCEDURE trproc_update_msg();


--
-- Name: public; Type: ACL; Schema: -; Owner: postgres
--

REVOKE ALL ON SCHEMA public FROM PUBLIC;
REVOKE ALL ON SCHEMA public FROM postgres;
GRANT ALL ON SCHEMA public TO postgres;
GRANT ALL ON SCHEMA public TO PUBLIC;


--
-- PostgreSQL database dump complete
--

