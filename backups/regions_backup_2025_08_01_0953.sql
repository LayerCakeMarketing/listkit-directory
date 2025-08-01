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
-- Name: regions; Type: TABLE; Schema: public; Owner: ericslarson
--

CREATE TABLE public.regions (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    type character varying(255) DEFAULT 'custom'::character varying NOT NULL,
    parent_id bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    level integer DEFAULT 0 NOT NULL,
    slug character varying(255),
    metadata jsonb,
    cached_place_count integer DEFAULT 0 NOT NULL,
    cover_image character varying(255),
    intro_text text,
    data_points jsonb,
    is_featured boolean DEFAULT false NOT NULL,
    meta_title character varying(255),
    meta_description text,
    custom_fields jsonb,
    display_priority integer DEFAULT 0 NOT NULL,
    cloudflare_image_id character varying(255),
    facts jsonb,
    state_symbols jsonb,
    geojson jsonb,
    polygon_coordinates text,
    full_name character varying(255),
    abbreviation character varying(10),
    alternate_names jsonb,
    boundary public.geometry(Polygon,4326),
    center_point public.geometry(Point,4326),
    area_sq_km numeric(10,2),
    is_user_defined boolean DEFAULT false NOT NULL,
    created_by_user_id bigint,
    cache_updated_at timestamp(0) without time zone,
    CONSTRAINT regions_type_check CHECK (((type)::text = ANY ((ARRAY['state'::character varying, 'city'::character varying, 'county'::character varying, 'neighborhood'::character varying, 'district'::character varying, 'custom'::character varying])::text[])))
);


ALTER TABLE public.regions OWNER TO ericslarson;

--
-- Name: COLUMN regions.alternate_names; Type: COMMENT; Schema: public; Owner: ericslarson
--

COMMENT ON COLUMN public.regions.alternate_names IS 'Array of alternate names/spellings';


--
-- Name: regions_id_seq; Type: SEQUENCE; Schema: public; Owner: ericslarson
--

CREATE SEQUENCE public.regions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.regions_id_seq OWNER TO ericslarson;

--
-- Name: regions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: ericslarson
--

ALTER SEQUENCE public.regions_id_seq OWNED BY public.regions.id;


--
-- Name: regions id; Type: DEFAULT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.regions ALTER COLUMN id SET DEFAULT nextval('public.regions_id_seq'::regclass);


--
-- Data for Name: regions; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.regions (id, name, type, parent_id, created_at, updated_at, level, slug, metadata, cached_place_count, cover_image, intro_text, data_points, is_featured, meta_title, meta_description, custom_fields, display_priority, cloudflare_image_id, facts, state_symbols, geojson, polygon_coordinates, full_name, abbreviation, alternate_names, boundary, center_point, area_sq_km, is_user_defined, created_by_user_id, cache_updated_at) FROM stdin;
2	New York	state	\N	2025-06-01 20:18:34	2025-06-01 20:18:34	1	new-york	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	\N	\N
3	Texas	state	\N	2025-06-01 20:18:34	2025-06-01 20:18:34	1	texas	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	\N	\N
5	Nevada	state	\N	2025-07-08 22:41:15	2025-07-19 23:26:13	1	nevada	[]	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	\N	\N
8	Woodbridge	neighborhood	6	2025-07-08 23:09:09	2025-07-20 23:34:51	3	woodbridge	[]	3	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	\N	\N
11	Los Angeles	city	82	2025-07-10 05:43:43	2025-07-19 23:15:54	2	los-angeles	{"population": 3898747}	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	\N	\N
12	Hollywood	neighborhood	11	2025-07-10 05:43:43	2025-07-10 05:43:43	3	hollywood	[]	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	\N	\N
14	Santa Monica	neighborhood	11	2025-07-10 05:43:43	2025-07-10 05:43:43	3	santa-monica	[]	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	\N	\N
15	Venice Beach	neighborhood	11	2025-07-10 05:43:43	2025-07-10 05:43:43	3	venice-beach	[]	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	\N	\N
16	Downtown LA	neighborhood	11	2025-07-10 05:43:43	2025-07-10 05:43:43	3	downtown-la	[]	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	\N	\N
18	Mission District	neighborhood	32	2025-07-10 05:43:43	2025-07-19 23:15:54	3	mission-district	[]	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	\N	\N
19	Castro	neighborhood	32	2025-07-10 05:43:43	2025-07-19 23:15:54	3	castro	[]	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	\N	\N
20	Chinatown	neighborhood	32	2025-07-10 05:43:43	2025-07-19 23:15:54	3	chinatown	[]	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	\N	\N
22	Haight-Ashbury	neighborhood	32	2025-07-10 05:43:43	2025-07-19 23:15:54	3	haight-ashbury	[]	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	\N	\N
23	San Diego	city	82	2025-07-10 05:43:43	2025-07-19 23:15:54	2	san-diego	{"population": 1386932}	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	\N	\N
24	Sacramento	city	82	2025-07-10 05:43:43	2025-07-19 23:15:54	2	sacramento	{"population": 513624}	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	\N	\N
25	San Jose	city	82	2025-07-10 05:43:43	2025-07-19 23:15:54	2	san-jose	{"population": 1021795}	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	\N	\N
26	Oakland	city	82	2025-07-10 05:43:43	2025-07-19 23:15:54	2	oakland	{"population": 433031}	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	\N	\N
27	Northwood	neighborhood	6	2025-07-10 05:43:43	2025-07-10 05:43:43	3	northwood	[]	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	\N	\N
28	University Park	neighborhood	6	2025-07-10 05:43:43	2025-07-10 05:43:43	3	university-park	[]	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	\N	\N
29	Turtle Rock	neighborhood	6	2025-07-10 05:43:43	2025-07-10 05:43:43	3	turtle-rock	[]	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	\N	\N
30	Westpark	neighborhood	6	2025-07-10 05:43:43	2025-07-10 05:43:43	3	westpark	[]	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	\N	\N
36	Alabama	state	\N	2025-07-19 22:58:44	2025-07-19 22:58:44	1	alabama	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	\N	\N
37	Alaska	state	\N	2025-07-19 22:58:44	2025-07-19 22:58:44	1	alaska	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	\N	\N
38	Arizona	state	\N	2025-07-19 22:58:44	2025-07-19 22:58:44	1	arizona	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	\N	\N
39	Arkansas	state	\N	2025-07-19 22:58:44	2025-07-19 22:58:44	1	arkansas	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	\N	\N
40	Colorado	state	\N	2025-07-19 22:58:44	2025-07-19 22:58:44	1	colorado	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	\N	\N
41	Connecticut	state	\N	2025-07-19 22:58:44	2025-07-19 22:58:44	1	connecticut	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	\N	\N
42	Delaware	state	\N	2025-07-19 22:58:44	2025-07-19 22:58:44	1	delaware	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	\N	\N
43	Georgia	state	\N	2025-07-19 22:58:44	2025-07-19 22:58:44	1	georgia	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	\N	\N
44	Hawaii	state	\N	2025-07-19 22:58:44	2025-07-19 22:58:44	1	hawaii	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	\N	\N
45	Idaho	state	\N	2025-07-19 22:58:44	2025-07-19 22:58:44	1	idaho	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	\N	\N
46	Illinois	state	\N	2025-07-19 22:58:44	2025-07-19 22:58:44	1	illinois	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	\N	\N
47	Indiana	state	\N	2025-07-19 22:58:44	2025-07-19 22:58:44	1	indiana	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	\N	\N
48	Iowa	state	\N	2025-07-19 22:58:44	2025-07-19 22:58:44	1	iowa	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	\N	\N
49	Kansas	state	\N	2025-07-19 22:58:44	2025-07-19 22:58:44	1	kansas	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	\N	\N
50	Kentucky	state	\N	2025-07-19 22:58:44	2025-07-19 22:58:44	1	kentucky	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	\N	\N
51	Louisiana	state	\N	2025-07-19 22:58:44	2025-07-19 22:58:44	1	louisiana	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	\N	\N
52	Maine	state	\N	2025-07-19 22:58:44	2025-07-19 22:58:44	1	maine	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	\N	\N
53	Maryland	state	\N	2025-07-19 22:58:44	2025-07-19 22:58:44	1	maryland	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	\N	\N
54	Massachusetts	state	\N	2025-07-19 22:58:44	2025-07-19 22:58:44	1	massachusetts	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	\N	\N
55	Michigan	state	\N	2025-07-19 22:58:44	2025-07-19 22:58:44	1	michigan	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	\N	\N
56	Minnesota	state	\N	2025-07-19 22:58:44	2025-07-19 22:58:44	1	minnesota	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	\N	\N
57	Mississippi	state	\N	2025-07-19 22:58:44	2025-07-19 22:58:44	1	mississippi	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	\N	\N
58	Missouri	state	\N	2025-07-19 22:58:44	2025-07-19 22:58:44	1	missouri	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	\N	\N
59	Montana	state	\N	2025-07-19 22:58:44	2025-07-19 22:58:44	1	montana	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	\N	\N
60	Nebraska	state	\N	2025-07-19 22:58:44	2025-07-19 22:58:44	1	nebraska	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	\N	\N
61	New Hampshire	state	\N	2025-07-19 22:58:44	2025-07-19 22:58:44	1	new-hampshire	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	\N	\N
62	New Jersey	state	\N	2025-07-19 22:58:44	2025-07-19 22:58:44	1	new-jersey	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	\N	\N
63	New Mexico	state	\N	2025-07-19 22:58:44	2025-07-19 22:58:44	1	new-mexico	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	\N	\N
64	North Carolina	state	\N	2025-07-19 22:58:44	2025-07-19 22:58:44	1	north-carolina	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	\N	\N
65	North Dakota	state	\N	2025-07-19 22:58:44	2025-07-19 22:58:44	1	north-dakota	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	\N	\N
66	Ohio	state	\N	2025-07-19 22:58:44	2025-07-19 22:58:44	1	ohio	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	\N	\N
67	Oklahoma	state	\N	2025-07-19 22:58:44	2025-07-19 22:58:44	1	oklahoma	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	\N	\N
68	Oregon	state	\N	2025-07-19 22:58:44	2025-07-19 22:58:44	1	oregon	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	\N	\N
69	Pennsylvania	state	\N	2025-07-19 22:58:44	2025-07-19 22:58:44	1	pennsylvania	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	\N	\N
70	Rhode Island	state	\N	2025-07-19 22:58:44	2025-07-19 22:58:44	1	rhode-island	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	\N	\N
71	South Carolina	state	\N	2025-07-19 22:58:44	2025-07-19 22:58:44	1	south-carolina	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	\N	\N
72	South Dakota	state	\N	2025-07-19 22:58:44	2025-07-19 22:58:44	1	south-dakota	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	\N	\N
73	Tennessee	state	\N	2025-07-19 22:58:44	2025-07-19 22:58:44	1	tennessee	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	\N	\N
74	Utah	state	\N	2025-07-19 22:58:44	2025-07-19 22:58:44	1	utah	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	\N	\N
75	Vermont	state	\N	2025-07-19 22:58:44	2025-07-19 22:58:44	1	vermont	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	\N	\N
76	Virginia	state	\N	2025-07-19 22:58:44	2025-07-19 22:58:44	1	virginia	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	\N	\N
77	Washington	state	\N	2025-07-19 22:58:44	2025-07-19 22:58:44	1	washington	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	\N	\N
78	West Virginia	state	\N	2025-07-19 22:58:44	2025-07-19 22:58:44	1	west-virginia	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	\N	\N
79	Wisconsin	state	\N	2025-07-19 22:58:44	2025-07-19 22:58:44	1	wisconsin	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	\N	\N
80	Wyoming	state	\N	2025-07-19 22:58:44	2025-07-19 22:58:44	1	wyoming	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	\N	\N
81	District of Columbia	state	\N	2025-07-19 22:58:44	2025-07-19 22:58:44	1	district-of-columbia	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	\N	\N
87	Juneau	city	37	2025-07-20 00:10:51	2025-07-20 00:10:51	2	juneau	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	\N	\N
89	Crossroads	neighborhood	6	2025-07-20 23:26:03	2025-07-21 03:08:37	3	crossroads	\N	1	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	\N	\N
90	California	state	\N	2025-07-30 05:34:18	2025-08-01 07:29:32	1	california	\N	1	https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A/ae719210-d976-40de-5a7b-1e6e4e7aef00/cover	\N	\N	f	\N	\N	\N	0	ae719210-d976-40de-5a7b-1e6e4e7aef00	{"climate": "Mostly Mediterranean on the coast; desert in the southeast; alpine in mountains", "terrain": "Incredibly varied: beaches, valleys, deserts, forests, and mountains", "timezone": "Pacific Time Zone (PT) — UTC -8 (Standard), UTC -7 (Daylight)", "area_size": "163,696 sq mi (3rd largest state in land area)", "elevation": "Ranges from -282 ft (Badwater Basin) to 14,505 ft (Mount Whitney)", "known_for": "Beaches, Silicon Valley, Hollywood, Yosemite, Napa Valley, Redwoods", "zip_codes": "Ranges from 90001 (LA) to 96162 (Tahoe area)", "local_food": "Fish tacos, sourdough bread, In-N-Out Burger, California-style pizza", "population": "~38.9 million (as of 2024, largest U.S. state by population)", "vibe_style": "Diverse — from laid-back beach towns to fast-paced urban innovation hubs", "annual_event": "Coachella Valley Music & Arts Festival (April)", "commute_time": "~30 minutes", "famous_people": "Steve Jobs, Billie Eilish, Clint Eastwood", "top_industries": "Technology, entertainment, agriculture, tourism, aerospace, renewable energy", "transportation": "Air: LAX, SFO, SAN, SJC Rail: Amtrak, Caltrain, Metrolink Public Transit: BART, Metro, MUNI, LA Metro Freeways: Extensive car-based infrastructure", "famous_landmark": "Golden Gate Bridge", "historical_fact": "The California Gold Rush (1848–1855) triggered massive westward migration.", "distance_to_city": "San Francisco, Los Angeles, San Diego, Sacramento", "established_year": "1850 (admitted as the 31st U.S. state on September 9, 1850)", "filming_location": "Countless movies/TV shows — notable: Once upon a time in Hollywood, Baywatch, Top Gun", "median_home_price": "$800,000 (as of 2024, varies widely by region)", "walkability_score": "Depends by city; San Francisco ~88, LA ~68, state average much lower"}	{"motto": "Eureka", "capital": "Sacramento", "nickname": "The Golden State"}	\N	\N	California	CA	\N	\N	\N	\N	f	\N	2025-07-30 06:36:06
91	Irvine	city	90	2025-07-30 05:34:18	2025-08-01 07:34:29	2	irvine	\N	1	https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A/0dcd9ef8-4e4c-4522-54f3-3a99263c1600/public	\N	\N	f	\N	\N	\N	0	0dcd9ef8-4e4c-4522-54f3-3a99263c1600	[]	\N	\N	\N	Irvine	\N	\N	\N	\N	\N	f	\N	2025-07-30 05:34:34
93	Long Beach	city	82	2025-07-30 06:30:49	2025-07-30 06:31:19	2	long-beach	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	\N	\N
94	Florida	state	\N	2025-07-30 06:35:42	2025-07-30 06:35:42	1	florida	\N	1	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	Florida	FL	\N	\N	\N	\N	f	\N	2025-07-30 06:35:42
95	Miami	city	94	2025-07-30 06:35:42	2025-07-30 06:35:42	2	miami	\N	1	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	Miami	\N	\N	\N	\N	\N	f	\N	2025-07-30 06:35:42
86	Mammoth Lakes	city	82	2025-07-19 23:00:11	2025-07-30 06:38:00	2	mammoth-lakes	\N	31	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	\N	\N
92	Mammoth Lakes	city	90	2025-07-30 05:34:34	2025-07-30 06:35:42	2	mammoth-lakes	\N	30	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	Mammoth Lakes	\N	\N	\N	\N	\N	f	\N	2025-07-30 06:35:42
84	Florida	state	\N	2025-07-19 23:00:11	2025-07-30 06:38:00	1	florida	\N	1	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	\N	\N
85	Miami	city	84	2025-07-19 23:00:11	2025-07-30 06:38:00	2	miami	\N	1	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	\N	\N
82	California	state	\N	2025-07-19 23:00:11	2025-08-01 07:29:32	1	california	\N	40	https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A/7d8ac7ea-8dcb-4ae4-8560-137022ad5800/cover	\N	\N	f	\N	\N	\N	0	7d8ac7ea-8dcb-4ae4-8560-137022ad5800	{"established_year": "1850 (admitted as the 31st U.S. state on September 9, 1850)"}	[]	\N	\N	\N	\N	\N	\N	\N	\N	f	\N	\N
6	Irvine	city	82	2025-07-08 23:00:35	2025-08-01 07:29:32	2	irvine	{"population": 307670}	6	\N	Irvine, California is a master-planned city in the heart of Orange County, known for its clean design, top-rated schools, and thriving tech and business sectors. With over 300 sunny days a year, miles of bike paths, and proximity to beaches and mountains, Irvine offers a balanced lifestyle of innovation, education, and outdoor living. It’s home to the University of California, Irvine (UCI), global headquarters, and one of the safest communities in the U.S.	\N	f	\N	\N	\N	0	\N	[]	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	\N	\N
32	San Francisco	city	82	2025-07-12 06:52:22	2025-07-30 06:38:00	2	san-francisco	\N	1	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	\N	\N
88	San Fransisco	city	82	2025-07-20 15:39:56	2025-07-30 06:38:00	2	san-fransisco	\N	1	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	\N	\N
\.


--
-- Name: regions_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.regions_id_seq', 97, true);


--
-- Name: regions regions_pkey; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.regions
    ADD CONSTRAINT regions_pkey PRIMARY KEY (id);


--
-- Name: idx_regions_boundary; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX idx_regions_boundary ON public.regions USING gist (boundary);


--
-- Name: idx_regions_center_point; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX idx_regions_center_point ON public.regions USING gist (center_point);


--
-- Name: regions_display_priority_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX regions_display_priority_index ON public.regions USING btree (display_priority);


--
-- Name: regions_facts_gin; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX regions_facts_gin ON public.regions USING gin (facts);


--
-- Name: regions_full_name_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX regions_full_name_index ON public.regions USING btree (full_name);


--
-- Name: regions_is_featured_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX regions_is_featured_index ON public.regions USING btree (is_featured);


--
-- Name: regions_level_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX regions_level_index ON public.regions USING btree (level);


--
-- Name: regions_parent_id_level_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX regions_parent_id_level_index ON public.regions USING btree (parent_id, level);


--
-- Name: regions_slug_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX regions_slug_index ON public.regions USING btree (slug);


--
-- Name: regions_slug_level_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX regions_slug_level_index ON public.regions USING btree (slug, level);


--
-- Name: regions_slug_parent_id_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX regions_slug_parent_id_index ON public.regions USING btree (slug, parent_id);


--
-- Name: regions_state_symbols_gin; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX regions_state_symbols_gin ON public.regions USING gin (state_symbols);


--
-- Name: regions_type_level_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX regions_type_level_index ON public.regions USING btree (type, level);


--
-- Name: regions regions_created_by_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.regions
    ADD CONSTRAINT regions_created_by_user_id_foreign FOREIGN KEY (created_by_user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: regions regions_parent_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.regions
    ADD CONSTRAINT regions_parent_id_foreign FOREIGN KEY (parent_id) REFERENCES public.regions(id) ON DELETE CASCADE;


--
-- PostgreSQL database dump complete
--

