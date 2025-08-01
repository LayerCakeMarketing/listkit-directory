--
-- PostgreSQL database dump
--

-- Dumped from database version 14.18 (Homebrew)
-- Dumped by pg_dump version 14.18 (Homebrew)

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: region_featured_lists; Type: TABLE; Schema: public; Owner: ericslarson
--

CREATE TABLE public.region_featured_lists (
    id bigint NOT NULL,
    region_id bigint NOT NULL,
    list_id bigint NOT NULL,
    priority integer DEFAULT 0 NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.region_featured_lists OWNER TO ericslarson;

--
-- Name: region_featured_lists_id_seq; Type: SEQUENCE; Schema: public; Owner: ericslarson
--

CREATE SEQUENCE public.region_featured_lists_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.region_featured_lists_id_seq OWNER TO ericslarson;

--
-- Name: region_featured_lists_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: ericslarson
--

ALTER SEQUENCE public.region_featured_lists_id_seq OWNED BY public.region_featured_lists.id;


--
-- Name: region_featured_lists id; Type: DEFAULT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.region_featured_lists ALTER COLUMN id SET DEFAULT nextval('public.region_featured_lists_id_seq'::regclass);


--
-- Data for Name: region_featured_lists; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.region_featured_lists (id, region_id, list_id, priority, is_active, created_at, updated_at) FROM stdin;
\.


--
-- Name: region_featured_lists_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.region_featured_lists_id_seq', 1, false);


--
-- Name: region_featured_lists region_featured_lists_pkey; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.region_featured_lists
    ADD CONSTRAINT region_featured_lists_pkey PRIMARY KEY (id);


--
-- Name: region_featured_lists region_featured_lists_region_id_list_id_unique; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.region_featured_lists
    ADD CONSTRAINT region_featured_lists_region_id_list_id_unique UNIQUE (region_id, list_id);


--
-- Name: region_featured_lists_priority_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX region_featured_lists_priority_index ON public.region_featured_lists USING btree (priority);


--
-- Name: region_featured_lists_region_id_is_active_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX region_featured_lists_region_id_is_active_index ON public.region_featured_lists USING btree (region_id, is_active);


--
-- Name: region_featured_lists region_featured_lists_list_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.region_featured_lists
    ADD CONSTRAINT region_featured_lists_list_id_foreign FOREIGN KEY (list_id) REFERENCES public.lists(id) ON DELETE CASCADE;


--
-- Name: region_featured_lists region_featured_lists_region_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.region_featured_lists
    ADD CONSTRAINT region_featured_lists_region_id_foreign FOREIGN KEY (region_id) REFERENCES public.regions(id) ON DELETE CASCADE;


--
-- PostgreSQL database dump complete
--

