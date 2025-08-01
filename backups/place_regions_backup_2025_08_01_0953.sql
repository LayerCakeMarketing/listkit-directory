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
-- Name: place_regions; Type: TABLE; Schema: public; Owner: ericslarson
--

CREATE TABLE public.place_regions (
    id bigint NOT NULL,
    place_id bigint NOT NULL,
    region_id bigint NOT NULL,
    association_type character varying(50) NOT NULL,
    distance_meters numeric(10,2),
    confidence_score numeric(3,2),
    region_type character varying(50),
    region_level integer,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    is_featured boolean DEFAULT false NOT NULL,
    featured_order integer,
    featured_at timestamp(0) without time zone
);


ALTER TABLE public.place_regions OWNER TO ericslarson;

--
-- Name: place_regions_id_seq; Type: SEQUENCE; Schema: public; Owner: ericslarson
--

CREATE SEQUENCE public.place_regions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.place_regions_id_seq OWNER TO ericslarson;

--
-- Name: place_regions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: ericslarson
--

ALTER SEQUENCE public.place_regions_id_seq OWNED BY public.place_regions.id;


--
-- Name: place_regions id; Type: DEFAULT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.place_regions ALTER COLUMN id SET DEFAULT nextval('public.place_regions_id_seq'::regclass);


--
-- Data for Name: place_regions; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.place_regions (id, place_id, region_id, association_type, distance_meters, confidence_score, region_type, region_level, created_at, updated_at, is_featured, featured_order, featured_at) FROM stdin;
1	108	90	contained	\N	1.00	state	1	2025-07-30 05:34:18	2025-07-30 05:34:18	f	\N	\N
2	108	91	contained	\N	1.00	city	2	2025-07-30 05:34:18	2025-07-30 05:34:18	f	\N	\N
5	75	90	contained	\N	1.00	state	1	2025-07-30 05:34:34	2025-07-30 05:34:34	f	\N	\N
6	75	92	contained	\N	1.00	city	2	2025-07-30 05:34:34	2025-07-30 05:34:34	f	\N	\N
7	76	90	contained	\N	1.00	state	1	2025-07-30 05:34:34	2025-07-30 05:34:34	f	\N	\N
8	76	92	contained	\N	1.00	city	2	2025-07-30 05:34:34	2025-07-30 05:34:34	f	\N	\N
9	78	90	contained	\N	1.00	state	1	2025-07-30 05:34:34	2025-07-30 05:34:34	f	\N	\N
10	78	92	contained	\N	1.00	city	2	2025-07-30 05:34:34	2025-07-30 05:34:34	f	\N	\N
11	82	90	contained	\N	1.00	state	1	2025-07-30 05:34:34	2025-07-30 05:34:34	f	\N	\N
12	82	92	contained	\N	1.00	city	2	2025-07-30 05:34:34	2025-07-30 05:34:34	f	\N	\N
13	83	90	contained	\N	1.00	state	1	2025-07-30 05:34:34	2025-07-30 05:34:34	f	\N	\N
14	83	92	contained	\N	1.00	city	2	2025-07-30 05:34:34	2025-07-30 05:34:34	f	\N	\N
15	89	90	contained	\N	1.00	state	1	2025-07-30 05:34:34	2025-07-30 05:34:34	f	\N	\N
16	89	92	contained	\N	1.00	city	2	2025-07-30 05:34:34	2025-07-30 05:34:34	f	\N	\N
17	84	90	contained	\N	1.00	state	1	2025-07-30 05:34:34	2025-07-30 05:34:34	f	\N	\N
18	84	92	contained	\N	1.00	city	2	2025-07-30 05:34:34	2025-07-30 05:34:34	f	\N	\N
19	85	90	contained	\N	1.00	state	1	2025-07-30 05:34:34	2025-07-30 05:34:34	f	\N	\N
20	85	92	contained	\N	1.00	city	2	2025-07-30 05:34:34	2025-07-30 05:34:34	f	\N	\N
21	86	90	contained	\N	1.00	state	1	2025-07-30 05:34:34	2025-07-30 05:34:34	f	\N	\N
22	86	92	contained	\N	1.00	city	2	2025-07-30 05:34:34	2025-07-30 05:34:34	f	\N	\N
23	87	90	contained	\N	1.00	state	1	2025-07-30 05:34:34	2025-07-30 05:34:34	f	\N	\N
24	87	92	contained	\N	1.00	city	2	2025-07-30 05:34:34	2025-07-30 05:34:34	f	\N	\N
25	90	90	contained	\N	1.00	state	1	2025-07-30 05:34:34	2025-07-30 05:34:34	f	\N	\N
26	90	92	contained	\N	1.00	city	2	2025-07-30 05:34:34	2025-07-30 05:34:34	f	\N	\N
29	103	90	contained	\N	1.00	state	1	2025-07-30 05:34:34	2025-07-30 05:34:34	f	\N	\N
30	103	91	contained	\N	1.00	city	2	2025-07-30 05:34:34	2025-07-30 05:34:34	f	\N	\N
31	104	90	contained	\N	1.00	state	1	2025-07-30 05:34:34	2025-07-30 05:34:34	f	\N	\N
32	104	91	contained	\N	1.00	city	2	2025-07-30 05:34:34	2025-07-30 05:34:34	f	\N	\N
33	106	90	contained	\N	1.00	state	1	2025-07-30 05:34:34	2025-07-30 05:34:34	f	\N	\N
34	106	91	contained	\N	1.00	city	2	2025-07-30 05:34:34	2025-07-30 05:34:34	f	\N	\N
35	99	90	contained	\N	1.00	state	1	2025-07-30 05:34:34	2025-07-30 05:34:34	f	\N	\N
36	99	92	contained	\N	1.00	city	2	2025-07-30 05:34:34	2025-07-30 05:34:34	f	\N	\N
37	105	90	contained	\N	1.00	state	1	2025-07-30 05:34:34	2025-07-30 05:34:34	f	\N	\N
38	105	91	contained	\N	1.00	city	2	2025-07-30 05:34:34	2025-07-30 05:34:34	f	\N	\N
39	107	90	contained	\N	1.00	state	1	2025-07-30 05:34:34	2025-07-30 05:34:34	f	\N	\N
40	107	91	contained	\N	1.00	city	2	2025-07-30 05:34:34	2025-07-30 05:34:34	f	\N	\N
41	3	94	contained	\N	1.00	state	1	2025-07-30 06:35:42	2025-07-30 06:35:42	f	\N	\N
42	3	95	contained	\N	1.00	city	2	2025-07-30 06:35:42	2025-07-30 06:35:42	f	\N	\N
43	79	90	contained	\N	1.00	state	1	2025-07-30 06:35:42	2025-07-30 06:35:42	f	\N	\N
44	79	92	contained	\N	1.00	city	2	2025-07-30 06:35:42	2025-07-30 06:35:42	f	\N	\N
45	73	90	contained	\N	1.00	state	1	2025-07-30 06:35:42	2025-07-30 06:35:42	f	\N	\N
46	73	92	contained	\N	1.00	city	2	2025-07-30 06:35:42	2025-07-30 06:35:42	f	\N	\N
47	2	90	contained	\N	1.00	state	1	2025-07-30 06:35:42	2025-07-30 06:35:42	f	\N	\N
49	97	90	contained	\N	1.00	state	1	2025-07-30 06:35:42	2025-07-30 06:35:42	f	\N	\N
50	97	92	contained	\N	1.00	city	2	2025-07-30 06:35:42	2025-07-30 06:35:42	f	\N	\N
51	80	90	contained	\N	1.00	state	1	2025-07-30 06:35:42	2025-07-30 06:35:42	f	\N	\N
52	80	92	contained	\N	1.00	city	2	2025-07-30 06:35:42	2025-07-30 06:35:42	f	\N	\N
53	81	90	contained	\N	1.00	state	1	2025-07-30 06:35:42	2025-07-30 06:35:42	f	\N	\N
54	81	92	contained	\N	1.00	city	2	2025-07-30 06:35:42	2025-07-30 06:35:42	f	\N	\N
55	72	90	contained	\N	1.00	state	1	2025-07-30 06:35:42	2025-07-30 06:35:42	f	\N	\N
56	72	92	contained	\N	1.00	city	2	2025-07-30 06:35:42	2025-07-30 06:35:42	f	\N	\N
57	74	90	contained	\N	1.00	state	1	2025-07-30 06:35:42	2025-07-30 06:35:42	f	\N	\N
58	74	92	contained	\N	1.00	city	2	2025-07-30 06:35:42	2025-07-30 06:35:42	f	\N	\N
59	77	90	contained	\N	1.00	state	1	2025-07-30 06:35:42	2025-07-30 06:35:42	f	\N	\N
60	77	92	contained	\N	1.00	city	2	2025-07-30 06:35:42	2025-07-30 06:35:42	f	\N	\N
61	69	90	contained	\N	1.00	state	1	2025-07-30 06:35:42	2025-07-30 06:35:42	f	\N	\N
62	69	92	contained	\N	1.00	city	2	2025-07-30 06:35:42	2025-07-30 06:35:42	f	\N	\N
63	70	90	contained	\N	1.00	state	1	2025-07-30 06:35:42	2025-07-30 06:35:42	f	\N	\N
64	70	92	contained	\N	1.00	city	2	2025-07-30 06:35:42	2025-07-30 06:35:42	f	\N	\N
65	93	90	contained	\N	1.00	state	1	2025-07-30 06:35:42	2025-07-30 06:35:42	f	\N	\N
66	93	92	contained	\N	1.00	city	2	2025-07-30 06:35:42	2025-07-30 06:35:42	f	\N	\N
67	94	90	contained	\N	1.00	state	1	2025-07-30 06:35:42	2025-07-30 06:35:42	f	\N	\N
68	94	92	contained	\N	1.00	city	2	2025-07-30 06:35:42	2025-07-30 06:35:42	f	\N	\N
69	92	90	contained	\N	1.00	state	1	2025-07-30 06:35:42	2025-07-30 06:35:42	f	\N	\N
70	92	92	contained	\N	1.00	city	2	2025-07-30 06:35:42	2025-07-30 06:35:42	f	\N	\N
71	71	90	contained	\N	1.00	state	1	2025-07-30 06:35:42	2025-07-30 06:35:42	f	\N	\N
72	71	92	contained	\N	1.00	city	2	2025-07-30 06:35:42	2025-07-30 06:35:42	f	\N	\N
73	98	90	contained	\N	1.00	state	1	2025-07-30 06:35:42	2025-07-30 06:35:42	f	\N	\N
74	98	92	contained	\N	1.00	city	2	2025-07-30 06:35:42	2025-07-30 06:35:42	f	\N	\N
75	95	90	contained	\N	1.00	state	1	2025-07-30 06:35:42	2025-07-30 06:35:42	f	\N	\N
76	95	92	contained	\N	1.00	city	2	2025-07-30 06:35:42	2025-07-30 06:35:42	f	\N	\N
77	96	90	contained	\N	1.00	state	1	2025-07-30 06:35:42	2025-07-30 06:35:42	f	\N	\N
78	96	92	contained	\N	1.00	city	2	2025-07-30 06:35:42	2025-07-30 06:35:42	f	\N	\N
79	91	90	contained	\N	1.00	state	1	2025-07-30 06:35:42	2025-07-30 06:35:42	f	\N	\N
80	91	92	contained	\N	1.00	city	2	2025-07-30 06:35:42	2025-07-30 06:35:42	f	\N	\N
81	4	90	contained	\N	1.00	state	1	2025-07-30 06:36:06	2025-07-30 06:36:06	f	\N	\N
\.


--
-- Name: place_regions_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.place_regions_id_seq', 82, true);


--
-- Name: place_regions place_regions_pkey; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.place_regions
    ADD CONSTRAINT place_regions_pkey PRIMARY KEY (id);


--
-- Name: place_regions place_regions_place_id_region_id_unique; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.place_regions
    ADD CONSTRAINT place_regions_place_id_region_id_unique UNIQUE (place_id, region_id);


--
-- Name: place_regions_association_type_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX place_regions_association_type_index ON public.place_regions USING btree (association_type);


--
-- Name: place_regions_place_id_region_type_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX place_regions_place_id_region_type_index ON public.place_regions USING btree (place_id, region_type);


--
-- Name: place_regions_region_id_is_featured_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX place_regions_region_id_is_featured_index ON public.place_regions USING btree (region_id, is_featured);


--
-- Name: place_regions_region_id_place_id_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX place_regions_region_id_place_id_index ON public.place_regions USING btree (region_id, place_id);


--
-- Name: place_regions_region_id_region_type_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX place_regions_region_id_region_type_index ON public.place_regions USING btree (region_id, region_type);


--
-- Name: place_regions place_regions_place_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.place_regions
    ADD CONSTRAINT place_regions_place_id_foreign FOREIGN KEY (place_id) REFERENCES public.directory_entries(id) ON DELETE CASCADE;


--
-- Name: place_regions place_regions_region_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.place_regions
    ADD CONSTRAINT place_regions_region_id_foreign FOREIGN KEY (region_id) REFERENCES public.regions(id) ON DELETE CASCADE;


--
-- PostgreSQL database dump complete
--

