--
-- PostgreSQL database dump
--

SET statement_timeout = 0;
SET lock_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SET check_function_bodies = false;
SET client_min_messages = warning;

--
-- Name: plpgsql; Type: EXTENSION; Schema: -; Owner: 
--

CREATE EXTENSION IF NOT EXISTS plpgsql WITH SCHEMA pg_catalog;


--
-- Name: EXTENSION plpgsql; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION plpgsql IS 'PL/pgSQL procedural language';


SET search_path = public, pg_catalog;

--
-- Name: from_unixtime(integer); Type: FUNCTION; Schema: public; Owner: chatbox
--

CREATE FUNCTION from_unixtime(integer) RETURNS timestamp without time zone
    LANGUAGE sql
    AS $_$
 SELECT to_timestamp($1)::timestamp AS result
$_$;


ALTER FUNCTION public.from_unixtime(integer) OWNER TO chatbox;

--
-- Name: unix_timestamp(timestamp without time zone); Type: FUNCTION; Schema: public; Owner: chatbox
--

CREATE FUNCTION unix_timestamp(timestamp without time zone) RETURNS bigint
    LANGUAGE sql
    AS $_$
SELECT EXTRACT(EPOCH FROM $1)::bigint AS result;
$_$;


ALTER FUNCTION public.unix_timestamp(timestamp without time zone) OWNER TO chatbox;

--
-- Name: update_queries(); Type: FUNCTION; Schema: public; Owner: chatbox
--

CREATE FUNCTION update_queries() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    NEW.timestamp := CURRENT_TIMESTAMP; 
    RETURN NEW;
END;
$$;


ALTER FUNCTION public.update_queries() OWNER TO chatbox;

--
-- Name: update_requests(); Type: FUNCTION; Schema: public; Owner: chatbox
--

CREATE FUNCTION update_requests() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    NEW.timestamp := CURRENT_TIMESTAMP; 
    RETURN NEW;
END;
$$;


ALTER FUNCTION public.update_requests() OWNER TO chatbox;

SET default_tablespace = '';

SET default_with_oids = false;

--
-- Name: accounts; Type: TABLE; Schema: public; Owner: chatbox; Tablespace: 
--

CREATE TABLE accounts (
    id integer NOT NULL,
    username text NOT NULL,
    hash text NOT NULL
);


ALTER TABLE public.accounts OWNER TO chatbox;

--
-- Name: accounts_id_seq1; Type: SEQUENCE; Schema: public; Owner: chatbox
--

CREATE SEQUENCE accounts_id_seq1
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.accounts_id_seq1 OWNER TO chatbox;

--
-- Name: accounts_id_seq1; Type: SEQUENCE OWNED BY; Schema: public; Owner: chatbox
--

ALTER SEQUENCE accounts_id_seq1 OWNED BY accounts.id;


--
-- Name: hours_of_day; Type: TABLE; Schema: public; Owner: chatbox; Tablespace: 
--

CREATE TABLE hours_of_day (
    hour integer DEFAULT 0 NOT NULL
);


ALTER TABLE public.hours_of_day OWNER TO chatbox;

--
-- Name: invisible_users; Type: TABLE; Schema: public; Owner: chatbox; Tablespace: 
--

CREATE TABLE invisible_users (
    id integer NOT NULL,
    "timestamp" timestamp without time zone DEFAULT now() NOT NULL,
    users integer NOT NULL
);


ALTER TABLE public.invisible_users OWNER TO chatbox;

--
-- Name: invisible_users_id_seq; Type: SEQUENCE; Schema: public; Owner: chatbox
--

CREATE SEQUENCE invisible_users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.invisible_users_id_seq OWNER TO chatbox;

--
-- Name: invisible_users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: chatbox
--

ALTER SEQUENCE invisible_users_id_seq OWNED BY invisible_users.id;


--
-- Name: online_users; Type: TABLE; Schema: public; Owner: chatbox; Tablespace: 
--

CREATE TABLE online_users (
    id integer NOT NULL,
    "timestamp" timestamp without time zone DEFAULT now() NOT NULL,
    "user" integer NOT NULL
);


ALTER TABLE public.online_users OWNER TO chatbox;

--
-- Name: online_users_id_seq; Type: SEQUENCE; Schema: public; Owner: chatbox
--

CREATE SEQUENCE online_users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.online_users_id_seq OWNER TO chatbox;

--
-- Name: online_users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: chatbox
--

ALTER SEQUENCE online_users_id_seq OWNED BY online_users.id;


--
-- Name: periods; Type: TABLE; Schema: public; Owner: chatbox; Tablespace: 
--

CREATE TABLE periods (
    name character varying(50) NOT NULL,
    query text NOT NULL,
    title text NOT NULL
);


ALTER TABLE public.periods OWNER TO chatbox;

--
-- Name: queries; Type: TABLE; Schema: public; Owner: chatbox; Tablespace: 
--

CREATE TABLE queries (
    id integer NOT NULL,
    request integer NOT NULL,
    "timestamp" timestamp without time zone DEFAULT now() NOT NULL,
    query text NOT NULL,
    parameters text NOT NULL,
    execution_time double precision NOT NULL
);


ALTER TABLE public.queries OWNER TO chatbox;

--
-- Name: queries_id_seq1; Type: SEQUENCE; Schema: public; Owner: chatbox
--

CREATE SEQUENCE queries_id_seq1
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.queries_id_seq1 OWNER TO chatbox;

--
-- Name: queries_id_seq1; Type: SEQUENCE OWNED BY; Schema: public; Owner: chatbox
--

ALTER SEQUENCE queries_id_seq1 OWNED BY queries.id;


--
-- Name: requests; Type: TABLE; Schema: public; Owner: chatbox; Tablespace: 
--

CREATE TABLE requests (
    id integer NOT NULL,
    "timestamp" timestamp without time zone DEFAULT now() NOT NULL,
    url text NOT NULL,
    ip text NOT NULL,
    request_time double precision NOT NULL,
    browser text NOT NULL,
    username text DEFAULT ''::text
);


ALTER TABLE public.requests OWNER TO chatbox;

--
-- Name: requests_id_seq1; Type: SEQUENCE; Schema: public; Owner: chatbox
--

CREATE SEQUENCE requests_id_seq1
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.requests_id_seq1 OWNER TO chatbox;

--
-- Name: requests_id_seq1; Type: SEQUENCE OWNED BY; Schema: public; Owner: chatbox
--

ALTER SEQUENCE requests_id_seq1 OWNED BY requests.id;


--
-- Name: settings; Type: TABLE; Schema: public; Owner: chatbox; Tablespace: 
--

CREATE TABLE settings (
    key character varying(50) NOT NULL,
    value text NOT NULL
);


ALTER TABLE public.settings OWNER TO chatbox;

--
-- Name: shout_revisions; Type: TABLE; Schema: public; Owner: chatbox; Tablespace: 
--

CREATE TABLE shout_revisions (
    id integer NOT NULL,
    epoch integer NOT NULL,
    revision integer NOT NULL,
    replaced timestamp without time zone NOT NULL,
    text text NOT NULL,
    date timestamp without time zone NOT NULL,
    "user" integer NOT NULL
);


ALTER TABLE public.shout_revisions OWNER TO chatbox;

--
-- Name: shout_smilies; Type: TABLE; Schema: public; Owner: chatbox; Tablespace: 
--

CREATE TABLE shout_smilies (
    shout_id integer NOT NULL,
    shout_epoch integer NOT NULL,
    smiley integer NOT NULL,
    count integer NOT NULL
);


ALTER TABLE public.shout_smilies OWNER TO chatbox;

--
-- Name: shout_words; Type: TABLE; Schema: public; Owner: chatbox; Tablespace: 
--

CREATE TABLE shout_words (
    shout_id integer NOT NULL,
    shout_epoch integer NOT NULL,
    word integer NOT NULL,
    count integer NOT NULL
);


ALTER TABLE public.shout_words OWNER TO chatbox;

--
-- Name: shouts; Type: TABLE; Schema: public; Owner: chatbox; Tablespace: 
--

CREATE TABLE shouts (
    id integer NOT NULL,
    epoch integer NOT NULL,
    date timestamp without time zone NOT NULL,
    "user" integer NOT NULL,
    message text NOT NULL,
    deleted smallint DEFAULT 0 NOT NULL,
    hour integer NOT NULL,
    day integer NOT NULL,
    month integer NOT NULL,
    year integer NOT NULL
);


ALTER TABLE public.shouts OWNER TO chatbox;

--
-- Name: smilies; Type: TABLE; Schema: public; Owner: chatbox; Tablespace: 
--

CREATE TABLE smilies (
    id integer NOT NULL,
    filename character varying(100) NOT NULL
);


ALTER TABLE public.smilies OWNER TO chatbox;

--
-- Name: smilies_id_seq1; Type: SEQUENCE; Schema: public; Owner: chatbox
--

CREATE SEQUENCE smilies_id_seq1
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.smilies_id_seq1 OWNER TO chatbox;

--
-- Name: smilies_id_seq1; Type: SEQUENCE OWNED BY; Schema: public; Owner: chatbox
--

ALTER SEQUENCE smilies_id_seq1 OWNED BY smilies.id;


--
-- Name: user_categories; Type: TABLE; Schema: public; Owner: chatbox; Tablespace: 
--

CREATE TABLE user_categories (
    id integer NOT NULL,
    name text NOT NULL,
    color text NOT NULL
);


ALTER TABLE public.user_categories OWNER TO chatbox;

--
-- Name: user_categories_id_seq1; Type: SEQUENCE; Schema: public; Owner: chatbox
--

CREATE SEQUENCE user_categories_id_seq1
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.user_categories_id_seq1 OWNER TO chatbox;

--
-- Name: user_categories_id_seq1; Type: SEQUENCE OWNED BY; Schema: public; Owner: chatbox
--

ALTER SEQUENCE user_categories_id_seq1 OWNED BY user_categories.id;


--
-- Name: users; Type: TABLE; Schema: public; Owner: chatbox; Tablespace: 
--

CREATE TABLE users (
    id integer NOT NULL,
    name text NOT NULL,
    category integer NOT NULL
);


ALTER TABLE public.users OWNER TO chatbox;

--
-- Name: words; Type: TABLE; Schema: public; Owner: chatbox; Tablespace: 
--

CREATE TABLE words (
    id integer NOT NULL,
    word character varying(100) NOT NULL
);


ALTER TABLE public.words OWNER TO chatbox;

--
-- Name: user_credentials; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE user_credentials (
	    id integer NOT NULL,
	    password text NOT NULL,
	    cookie text NOT NULL,
	    securitytoken text NOT NULL,
	    access_token text NOT NULL
);

ALTER TABLE public.user_credentials OWNER TO user_credentials;

--
-- Name: words_id_seq1; Type: SEQUENCE; Schema: public; Owner: chatbox
--

CREATE SEQUENCE words_id_seq1
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.words_id_seq1 OWNER TO chatbox;

--
-- Name: words_id_seq1; Type: SEQUENCE OWNED BY; Schema: public; Owner: chatbox
--

ALTER SEQUENCE words_id_seq1 OWNED BY words.id;


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: chatbox
--

ALTER TABLE ONLY accounts ALTER COLUMN id SET DEFAULT nextval('accounts_id_seq1'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: chatbox
--

ALTER TABLE ONLY invisible_users ALTER COLUMN id SET DEFAULT nextval('invisible_users_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: chatbox
--

ALTER TABLE ONLY online_users ALTER COLUMN id SET DEFAULT nextval('online_users_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: chatbox
--

ALTER TABLE ONLY queries ALTER COLUMN id SET DEFAULT nextval('queries_id_seq1'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: chatbox
--

ALTER TABLE ONLY requests ALTER COLUMN id SET DEFAULT nextval('requests_id_seq1'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: chatbox
--

ALTER TABLE ONLY smilies ALTER COLUMN id SET DEFAULT nextval('smilies_id_seq1'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: chatbox
--

ALTER TABLE ONLY user_categories ALTER COLUMN id SET DEFAULT nextval('user_categories_id_seq1'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: chatbox
--

ALTER TABLE ONLY words ALTER COLUMN id SET DEFAULT nextval('words_id_seq1'::regclass);


--
-- Name: accounts_pkey; Type: CONSTRAINT; Schema: public; Owner: chatbox; Tablespace: 
--

ALTER TABLE ONLY accounts
    ADD CONSTRAINT accounts_pkey PRIMARY KEY (id);


--
-- Name: hours_of_day_pkey; Type: CONSTRAINT; Schema: public; Owner: chatbox; Tablespace: 
--

ALTER TABLE ONLY hours_of_day
    ADD CONSTRAINT hours_of_day_pkey PRIMARY KEY (hour);


--
-- Name: invisible_users_pkey; Type: CONSTRAINT; Schema: public; Owner: chatbox; Tablespace: 
--

ALTER TABLE ONLY invisible_users
    ADD CONSTRAINT invisible_users_pkey PRIMARY KEY (id);


--
-- Name: online_users_pkey; Type: CONSTRAINT; Schema: public; Owner: chatbox; Tablespace: 
--

ALTER TABLE ONLY online_users
    ADD CONSTRAINT online_users_pkey PRIMARY KEY (id);


--
-- Name: periods_pkey; Type: CONSTRAINT; Schema: public; Owner: chatbox; Tablespace: 
--

ALTER TABLE ONLY periods
    ADD CONSTRAINT periods_pkey PRIMARY KEY (name);


--
-- Name: settings_pkey; Type: CONSTRAINT; Schema: public; Owner: chatbox; Tablespace: 
--

ALTER TABLE ONLY settings
    ADD CONSTRAINT settings_pkey PRIMARY KEY (key);


--
-- Name: shout_revisions_pkey; Type: CONSTRAINT; Schema: public; Owner: chatbox; Tablespace: 
--

ALTER TABLE ONLY shout_revisions
    ADD CONSTRAINT shout_revisions_pkey PRIMARY KEY (id, epoch, revision);


--
-- Name: shout_smilies_pkey; Type: CONSTRAINT; Schema: public; Owner: chatbox; Tablespace: 
--

ALTER TABLE ONLY shout_smilies
    ADD CONSTRAINT shout_smilies_pkey PRIMARY KEY (shout_id, shout_epoch, smiley);


--
-- Name: shout_words_pkey; Type: CONSTRAINT; Schema: public; Owner: chatbox; Tablespace: 
--

ALTER TABLE ONLY shout_words
    ADD CONSTRAINT shout_words_pkey PRIMARY KEY (shout_id, shout_epoch, word);


--
-- Name: shouts_pkey; Type: CONSTRAINT; Schema: public; Owner: chatbox; Tablespace: 
--

ALTER TABLE ONLY shouts
    ADD CONSTRAINT shouts_pkey PRIMARY KEY (id, epoch);


--
-- Name: smilies_pkey; Type: CONSTRAINT; Schema: public; Owner: chatbox; Tablespace: 
--

ALTER TABLE ONLY smilies
    ADD CONSTRAINT smilies_pkey PRIMARY KEY (id);


--
-- Name: user_categories_pkey; Type: CONSTRAINT; Schema: public; Owner: chatbox; Tablespace: 
--

ALTER TABLE ONLY user_categories
    ADD CONSTRAINT user_categories_pkey PRIMARY KEY (id);


--
-- Name: users_pkey; Type: CONSTRAINT; Schema: public; Owner: chatbox; Tablespace: 
--

ALTER TABLE ONLY users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: words_pkey; Type: CONSTRAINT; Schema: public; Owner: chatbox; Tablespace: 
--

ALTER TABLE ONLY words
    ADD CONSTRAINT words_pkey PRIMARY KEY (id);


--
-- Name: user_credentials_pkey; Type: CONSTRAINT; Schema: public; Owner: chatbox; Tablespace: 
--

ALTER TABLE ONLY user_credentials
    ADD CONSTRAINT user_credentials_pkey PRIMARY KEY (id);


--
-- Name: shout_revisions_id_epoch_idx; Type: INDEX; Schema: public; Owner: chatbox; Tablespace: 
--

CREATE INDEX shout_revisions_id_epoch_idx ON shout_revisions USING btree (id, epoch);


--
-- Name: shout_smilies_shout_id_shout_epoch_idx; Type: INDEX; Schema: public; Owner: chatbox; Tablespace: 
--

CREATE INDEX shout_smilies_shout_id_shout_epoch_idx ON shout_smilies USING btree (shout_id, shout_epoch);


--
-- Name: shout_smilies_smiley_idx; Type: INDEX; Schema: public; Owner: chatbox; Tablespace: 
--

CREATE INDEX shout_smilies_smiley_idx ON shout_smilies USING btree (smiley);


--
-- Name: shout_words_shout_id_shout_epoch_idx; Type: INDEX; Schema: public; Owner: chatbox; Tablespace: 
--

CREATE INDEX shout_words_shout_id_shout_epoch_idx ON shout_words USING btree (shout_id, shout_epoch);


--
-- Name: shout_words_word_idx; Type: INDEX; Schema: public; Owner: chatbox; Tablespace: 
--

CREATE INDEX shout_words_word_idx ON shout_words USING btree (word);


--
-- Name: shouts_day_idx; Type: INDEX; Schema: public; Owner: chatbox; Tablespace: 
--

CREATE INDEX shouts_day_idx ON shouts USING btree (day);


--
-- Name: shouts_hour_idx; Type: INDEX; Schema: public; Owner: chatbox; Tablespace: 
--

CREATE INDEX shouts_hour_idx ON shouts USING btree (hour);


--
-- Name: shouts_month_idx; Type: INDEX; Schema: public; Owner: chatbox; Tablespace: 
--

CREATE INDEX shouts_month_idx ON shouts USING btree (month);


--
-- Name: shouts_year_idx; Type: INDEX; Schema: public; Owner: chatbox; Tablespace: 
--

CREATE INDEX shouts_year_idx ON shouts USING btree (year);


--
-- Name: online_users_user_fkey; Type: FK CONSTRAINT; Schema: public; Owner: chatbox
--

ALTER TABLE ONLY online_users
    ADD CONSTRAINT online_users_user_fkey FOREIGN KEY ("user") REFERENCES users(id);


--
-- Name: shout_revisions_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: chatbox
--

ALTER TABLE ONLY shout_revisions
    ADD CONSTRAINT shout_revisions_id_fkey FOREIGN KEY (id, epoch) REFERENCES shouts(id, epoch) ON DELETE CASCADE;


--
-- Name: shout_smilies_shout_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: chatbox
--

ALTER TABLE ONLY shout_smilies
    ADD CONSTRAINT shout_smilies_shout_id_fkey FOREIGN KEY (shout_id, shout_epoch) REFERENCES shouts(id, epoch) ON DELETE CASCADE;


--
-- Name: shout_smilies_smiley_fkey; Type: FK CONSTRAINT; Schema: public; Owner: chatbox
--

ALTER TABLE ONLY shout_smilies
    ADD CONSTRAINT shout_smilies_smiley_fkey FOREIGN KEY (smiley) REFERENCES smilies(id);


--
-- Name: shout_words_shout_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: chatbox
--

ALTER TABLE ONLY shout_words
    ADD CONSTRAINT shout_words_shout_id_fkey FOREIGN KEY (shout_id, shout_epoch) REFERENCES shouts(id, epoch) ON DELETE CASCADE;


--
-- Name: shout_words_word_fkey; Type: FK CONSTRAINT; Schema: public; Owner: chatbox
--

ALTER TABLE ONLY shout_words
    ADD CONSTRAINT shout_words_word_fkey FOREIGN KEY (word) REFERENCES words(id);


--
-- Name: shouts_user_fkey; Type: FK CONSTRAINT; Schema: public; Owner: chatbox
--

ALTER TABLE ONLY shouts
    ADD CONSTRAINT shouts_user_fkey FOREIGN KEY ("user") REFERENCES users(id);


--
-- Name: users_category_fkey; Type: FK CONSTRAINT; Schema: public; Owner: chatbox
--

ALTER TABLE ONLY users
    ADD CONSTRAINT users_category_fkey FOREIGN KEY (category) REFERENCES user_categories(id);


--
-- Name: user_credentials_fkey; Type: FK CONSTRAINT; Schema: public; Owner: chatbox
--

ALTER TABLE ONLY user_categories
    ADD CONSTRAINT user_categories_fkey FOREIGN KEY (id) REFERENCES users(id);


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

