--
-- PostgreSQL database dump
--

-- Dumped from database version 9.2.7
-- Dumped by pg_dump version 9.2.7
-- Started on 2014-10-02 17:47:48 EEST

SET statement_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SET check_function_bodies = false;
SET client_min_messages = warning;

--
-- TOC entry 170 (class 3079 OID 11740)
-- Name: plpgsql; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS plpgsql WITH SCHEMA pg_catalog;


--
-- TOC entry 1937 (class 0 OID 0)
-- Dependencies: 170
-- Name: EXTENSION plpgsql; Type: COMMENT; Schema: -; Owner: -
--

COMMENT ON EXTENSION plpgsql IS 'PL/pgSQL procedural language';


SET search_path = public, pg_catalog;

SET default_tablespace = '';

SET default_with_oids = false;

--
-- TOC entry 169 (class 1259 OID 97047)
-- Name: data_types; Type: TABLE; Schema: public; Owner: -; Tablespace:
--

CREATE TABLE data_types (
    id bigint NOT NULL,
    f_smallint smallint,
    f_boolean boolean,
    f_varchar character varying,
    f_bytea bytea,
    f_timestamp timestamp without time zone,
    f_timestamptz timestamp with time zone,
    f_json json,
    f_real real,
    f_a_smallint smallint[],
    f_a_varchar character varying[],
    f_a_boolean boolean[],
    f_a_bytea bytea[],
    f_a_real real[],
    f_a_timestamp timestamp without time zone[],
    f_a_timestamptz timestamp with time zone[],
    f_a_json json[]
);


--
-- TOC entry 168 (class 1259 OID 97045)
-- Name: data_types_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE data_types_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 1938 (class 0 OID 0)
-- Dependencies: 168
-- Name: data_types_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE data_types_id_seq OWNED BY data_types.id;


--
-- TOC entry 1822 (class 2604 OID 97050)
-- Name: id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY data_types ALTER COLUMN id SET DEFAULT nextval('data_types_id_seq'::regclass);


--
-- TOC entry 1824 (class 2606 OID 97052)
-- Name: data_types_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace:
--

ALTER TABLE ONLY data_types
    ADD CONSTRAINT data_types_pkey PRIMARY KEY (id);


-- Completed on 2014-10-02 17:47:48 EEST

--
-- PostgreSQL database dump complete
--
