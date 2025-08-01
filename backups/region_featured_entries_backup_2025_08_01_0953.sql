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
-- Name: region_featured_entries; Type: TABLE; Schema: public; Owner: ericslarson
--

CREATE TABLE public.region_featured_entries (
    id bigint NOT NULL,
    region_id bigint NOT NULL,
    directory_entry_id bigint NOT NULL,
    priority integer DEFAULT 0 NOT NULL,
    label character varying(255),
    tagline character varying(255),
    custom_data jsonb,
    featured_until date,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.region_featured_entries OWNER TO ericslarson;

--
-- Name: region_featured_entries_id_seq; Type: SEQUENCE; Schema: public; Owner: ericslarson
--

CREATE SEQUENCE public.region_featured_entries_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.region_featured_entries_id_seq OWNER TO ericslarson;

--
-- Name: region_featured_entries_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: ericslarson
--

ALTER SEQUENCE public.region_featured_entries_id_seq OWNED BY public.region_featured_entries.id;


--
-- Name: region_featured_entries id; Type: DEFAULT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.region_featured_entries ALTER COLUMN id SET DEFAULT nextval('public.region_featured_entries_id_seq'::regclass);


--
-- Data for Name: region_featured_entries; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.region_featured_entries (id, region_id, directory_entry_id, priority, label, tagline, custom_data, featured_until, is_active, created_at, updated_at) FROM stdin;
1	86	89	0	\N	\N	\N	\N	t	2025-07-30 01:11:21	2025-07-30 01:11:21
2	86	90	0	\N	\N	\N	\N	t	2025-07-30 01:11:25	2025-07-30 01:11:25
3	86	91	0	\N	\N	\N	\N	t	2025-07-30 01:11:26	2025-07-30 01:11:26
4	86	92	0	\N	\N	\N	\N	t	2025-07-30 01:11:27	2025-07-30 01:11:27
5	86	93	0	\N	\N	\N	\N	t	2025-07-30 01:11:28	2025-07-30 01:11:28
6	86	94	0	\N	\N	\N	\N	t	2025-07-30 01:11:30	2025-07-30 01:11:30
7	86	95	0	\N	\N	\N	\N	t	2025-07-30 01:11:30	2025-07-30 01:11:30
8	86	96	0	\N	\N	\N	\N	t	2025-07-30 01:11:33	2025-07-30 01:11:33
9	86	97	0	\N	\N	\N	\N	t	2025-07-30 01:11:34	2025-07-30 01:11:34
\.


--
-- Name: region_featured_entries_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.region_featured_entries_id_seq', 9, true);


--
-- Name: region_featured_entries region_featured_entries_pkey; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.region_featured_entries
    ADD CONSTRAINT region_featured_entries_pkey PRIMARY KEY (id);


--
-- Name: region_featured_entries region_featured_entries_region_id_directory_entry_id_unique; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.region_featured_entries
    ADD CONSTRAINT region_featured_entries_region_id_directory_entry_id_unique UNIQUE (region_id, directory_entry_id);


--
-- Name: region_featured_entries_featured_until_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX region_featured_entries_featured_until_index ON public.region_featured_entries USING btree (featured_until);


--
-- Name: region_featured_entries_region_id_is_active_priority_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX region_featured_entries_region_id_is_active_priority_index ON public.region_featured_entries USING btree (region_id, is_active, priority);


--
-- Name: region_featured_entries_region_id_priority_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX region_featured_entries_region_id_priority_index ON public.region_featured_entries USING btree (region_id, priority);


--
-- Name: region_featured_entries region_featured_entries_directory_entry_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.region_featured_entries
    ADD CONSTRAINT region_featured_entries_directory_entry_id_foreign FOREIGN KEY (directory_entry_id) REFERENCES public.directory_entries(id) ON DELETE CASCADE;


--
-- Name: region_featured_entries region_featured_entries_region_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.region_featured_entries
    ADD CONSTRAINT region_featured_entries_region_id_foreign FOREIGN KEY (region_id) REFERENCES public.regions(id) ON DELETE CASCADE;


--
-- PostgreSQL database dump complete
--

