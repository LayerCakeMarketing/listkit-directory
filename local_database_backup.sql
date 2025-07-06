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

--
-- Name: postgis; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS postgis WITH SCHEMA public;


--
-- Name: EXTENSION postgis; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION postgis IS 'PostGIS geometry and geography spatial types and functions';


--
-- Name: update_location_geom(); Type: FUNCTION; Schema: public; Owner: ericslarson
--

CREATE FUNCTION public.update_location_geom() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
                BEGIN
                    NEW.geom = ST_SetSRID(ST_MakePoint(NEW.longitude, NEW.latitude), 4326);
                    RETURN NEW;
                END;
                $$;


ALTER FUNCTION public.update_location_geom() OWNER TO ericslarson;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: cache; Type: TABLE; Schema: public; Owner: ericslarson
--

CREATE TABLE public.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration integer NOT NULL
);


ALTER TABLE public.cache OWNER TO ericslarson;

--
-- Name: cache_locks; Type: TABLE; Schema: public; Owner: ericslarson
--

CREATE TABLE public.cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration integer NOT NULL
);


ALTER TABLE public.cache_locks OWNER TO ericslarson;

--
-- Name: categories; Type: TABLE; Schema: public; Owner: ericslarson
--

CREATE TABLE public.categories (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    slug character varying(255) NOT NULL,
    parent_id bigint,
    icon character varying(255),
    order_index integer DEFAULT 0 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    description text
);


ALTER TABLE public.categories OWNER TO ericslarson;

--
-- Name: categories_id_seq; Type: SEQUENCE; Schema: public; Owner: ericslarson
--

CREATE SEQUENCE public.categories_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.categories_id_seq OWNER TO ericslarson;

--
-- Name: categories_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: ericslarson
--

ALTER SEQUENCE public.categories_id_seq OWNED BY public.categories.id;


--
-- Name: claims; Type: TABLE; Schema: public; Owner: ericslarson
--

CREATE TABLE public.claims (
    id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.claims OWNER TO ericslarson;

--
-- Name: claims_id_seq; Type: SEQUENCE; Schema: public; Owner: ericslarson
--

CREATE SEQUENCE public.claims_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.claims_id_seq OWNER TO ericslarson;

--
-- Name: claims_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: ericslarson
--

ALTER SEQUENCE public.claims_id_seq OWNED BY public.claims.id;


--
-- Name: comments; Type: TABLE; Schema: public; Owner: ericslarson
--

CREATE TABLE public.comments (
    id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.comments OWNER TO ericslarson;

--
-- Name: comments_id_seq; Type: SEQUENCE; Schema: public; Owner: ericslarson
--

CREATE SEQUENCE public.comments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.comments_id_seq OWNER TO ericslarson;

--
-- Name: comments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: ericslarson
--

ALTER SEQUENCE public.comments_id_seq OWNED BY public.comments.id;


--
-- Name: directory_entries; Type: TABLE; Schema: public; Owner: ericslarson
--

CREATE TABLE public.directory_entries (
    id bigint NOT NULL,
    title character varying(255) NOT NULL,
    slug character varying(255) NOT NULL,
    description text,
    type character varying(255) DEFAULT 'physical_location'::character varying NOT NULL,
    category_id bigint,
    region_id bigint,
    tags json,
    owner_user_id bigint,
    created_by_user_id bigint NOT NULL,
    updated_by_user_id bigint,
    phone character varying(255),
    email character varying(255),
    website_url character varying(255),
    social_links json,
    featured_image character varying(255),
    gallery_images json,
    status character varying(255) DEFAULT 'draft'::character varying NOT NULL,
    is_featured boolean DEFAULT false NOT NULL,
    is_verified boolean DEFAULT false NOT NULL,
    is_claimed boolean DEFAULT false NOT NULL,
    meta_title character varying(255),
    meta_description text,
    structured_data json,
    view_count integer DEFAULT 0 NOT NULL,
    list_count integer DEFAULT 0 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    published_at timestamp(0) without time zone,
    logo_url character varying(255),
    cover_image_url character varying(255),
    facebook_url character varying(255),
    instagram_handle character varying(255),
    twitter_handle character varying(255),
    youtube_channel character varying(255),
    messenger_contact character varying(255),
    price_range character varying(255),
    takes_reservations boolean,
    accepts_credit_cards boolean,
    wifi_available boolean,
    pet_friendly boolean,
    parking_options character varying(255),
    wheelchair_accessible boolean,
    outdoor_seating boolean,
    kid_friendly boolean,
    video_urls json,
    pdf_files json,
    hours_of_operation json,
    special_hours json,
    temporarily_closed boolean DEFAULT false NOT NULL,
    open_24_7 boolean DEFAULT false NOT NULL,
    cross_streets character varying(255),
    neighborhood character varying(255),
    CONSTRAINT directory_entries_parking_options_check CHECK (((parking_options)::text = ANY ((ARRAY['street'::character varying, 'lot'::character varying, 'valet'::character varying, 'none'::character varying])::text[]))),
    CONSTRAINT directory_entries_price_range_check CHECK (((price_range)::text = ANY ((ARRAY['$'::character varying, '$$'::character varying, '$$$'::character varying, '$$$$'::character varying])::text[]))),
    CONSTRAINT directory_entries_status_check CHECK (((status)::text = ANY ((ARRAY['draft'::character varying, 'pending_review'::character varying, 'published'::character varying, 'archived'::character varying])::text[]))),
    CONSTRAINT directory_entries_type_check CHECK (((type)::text = ANY ((ARRAY['physical_location'::character varying, 'online_business'::character varying, 'service'::character varying, 'event'::character varying, 'resource'::character varying])::text[])))
);


ALTER TABLE public.directory_entries OWNER TO ericslarson;

--
-- Name: directory_entries_id_seq; Type: SEQUENCE; Schema: public; Owner: ericslarson
--

CREATE SEQUENCE public.directory_entries_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.directory_entries_id_seq OWNER TO ericslarson;

--
-- Name: directory_entries_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: ericslarson
--

ALTER SEQUENCE public.directory_entries_id_seq OWNED BY public.directory_entries.id;


--
-- Name: failed_jobs; Type: TABLE; Schema: public; Owner: ericslarson
--

CREATE TABLE public.failed_jobs (
    id bigint NOT NULL,
    uuid character varying(255) NOT NULL,
    connection text NOT NULL,
    queue text NOT NULL,
    payload text NOT NULL,
    exception text NOT NULL,
    failed_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE public.failed_jobs OWNER TO ericslarson;

--
-- Name: failed_jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: ericslarson
--

CREATE SEQUENCE public.failed_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.failed_jobs_id_seq OWNER TO ericslarson;

--
-- Name: failed_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: ericslarson
--

ALTER SEQUENCE public.failed_jobs_id_seq OWNED BY public.failed_jobs.id;


--
-- Name: job_batches; Type: TABLE; Schema: public; Owner: ericslarson
--

CREATE TABLE public.job_batches (
    id character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    total_jobs integer NOT NULL,
    pending_jobs integer NOT NULL,
    failed_jobs integer NOT NULL,
    failed_job_ids text NOT NULL,
    options text,
    cancelled_at integer,
    created_at integer NOT NULL,
    finished_at integer
);


ALTER TABLE public.job_batches OWNER TO ericslarson;

--
-- Name: jobs; Type: TABLE; Schema: public; Owner: ericslarson
--

CREATE TABLE public.jobs (
    id bigint NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    attempts smallint NOT NULL,
    reserved_at integer,
    available_at integer NOT NULL,
    created_at integer NOT NULL
);


ALTER TABLE public.jobs OWNER TO ericslarson;

--
-- Name: jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: ericslarson
--

CREATE SEQUENCE public.jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.jobs_id_seq OWNER TO ericslarson;

--
-- Name: jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: ericslarson
--

ALTER SEQUENCE public.jobs_id_seq OWNED BY public.jobs.id;


--
-- Name: list_items; Type: TABLE; Schema: public; Owner: ericslarson
--

CREATE TABLE public.list_items (
    id bigint NOT NULL,
    list_id bigint NOT NULL,
    directory_entry_id bigint,
    order_index integer DEFAULT 0 NOT NULL,
    notes text,
    affiliate_url character varying(255),
    custom_data json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    type character varying(255) DEFAULT 'directory_entry'::character varying NOT NULL,
    title character varying(255),
    content text,
    data json,
    image character varying(255),
    CONSTRAINT list_items_type_check CHECK (((type)::text = ANY ((ARRAY['directory_entry'::character varying, 'text'::character varying, 'location'::character varying, 'event'::character varying])::text[])))
);


ALTER TABLE public.list_items OWNER TO ericslarson;

--
-- Name: list_items_id_seq; Type: SEQUENCE; Schema: public; Owner: ericslarson
--

CREATE SEQUENCE public.list_items_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.list_items_id_seq OWNER TO ericslarson;

--
-- Name: list_items_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: ericslarson
--

ALTER SEQUENCE public.list_items_id_seq OWNED BY public.list_items.id;


--
-- Name: list_media; Type: TABLE; Schema: public; Owner: ericslarson
--

CREATE TABLE public.list_media (
    id bigint NOT NULL,
    list_id bigint NOT NULL,
    type character varying(255) DEFAULT 'image'::character varying NOT NULL,
    url character varying(255) NOT NULL,
    caption character varying(255),
    order_index integer DEFAULT 0 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT list_media_type_check CHECK (((type)::text = ANY ((ARRAY['image'::character varying, 'youtube'::character varying, 'rumble'::character varying])::text[])))
);


ALTER TABLE public.list_media OWNER TO ericslarson;

--
-- Name: list_media_id_seq; Type: SEQUENCE; Schema: public; Owner: ericslarson
--

CREATE SEQUENCE public.list_media_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.list_media_id_seq OWNER TO ericslarson;

--
-- Name: list_media_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: ericslarson
--

ALTER SEQUENCE public.list_media_id_seq OWNED BY public.list_media.id;


--
-- Name: lists; Type: TABLE; Schema: public; Owner: ericslarson
--

CREATE TABLE public.lists (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    description text,
    is_public boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    featured_image character varying(255),
    slug character varying(255),
    view_count integer DEFAULT 0 NOT NULL,
    settings json,
    visibility character varying(255) DEFAULT 'public'::character varying NOT NULL
);


ALTER TABLE public.lists OWNER TO ericslarson;

--
-- Name: lists_id_seq; Type: SEQUENCE; Schema: public; Owner: ericslarson
--

CREATE SEQUENCE public.lists_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.lists_id_seq OWNER TO ericslarson;

--
-- Name: lists_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: ericslarson
--

ALTER SEQUENCE public.lists_id_seq OWNED BY public.lists.id;


--
-- Name: locations; Type: TABLE; Schema: public; Owner: ericslarson
--

CREATE TABLE public.locations (
    id bigint NOT NULL,
    directory_entry_id bigint NOT NULL,
    address_line1 character varying(255),
    address_line2 character varying(255),
    city character varying(255),
    state character varying(255),
    zip_code character varying(255),
    country character varying(255) DEFAULT 'USA'::character varying NOT NULL,
    latitude numeric(10,7),
    longitude numeric(10,7),
    hours_of_operation json,
    holiday_hours json,
    is_wheelchair_accessible boolean DEFAULT false NOT NULL,
    has_parking boolean DEFAULT false NOT NULL,
    amenities json,
    place_id character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    geom public.geography(Point,4326),
    cross_streets character varying(255),
    neighborhood character varying(255)
);


ALTER TABLE public.locations OWNER TO ericslarson;

--
-- Name: locations_id_seq; Type: SEQUENCE; Schema: public; Owner: ericslarson
--

CREATE SEQUENCE public.locations_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.locations_id_seq OWNER TO ericslarson;

--
-- Name: locations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: ericslarson
--

ALTER SEQUENCE public.locations_id_seq OWNED BY public.locations.id;


--
-- Name: migrations; Type: TABLE; Schema: public; Owner: ericslarson
--

CREATE TABLE public.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


ALTER TABLE public.migrations OWNER TO ericslarson;

--
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: ericslarson
--

CREATE SEQUENCE public.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.migrations_id_seq OWNER TO ericslarson;

--
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: ericslarson
--

ALTER SEQUENCE public.migrations_id_seq OWNED BY public.migrations.id;


--
-- Name: password_reset_tokens; Type: TABLE; Schema: public; Owner: ericslarson
--

CREATE TABLE public.password_reset_tokens (
    email character varying(255) NOT NULL,
    token character varying(255) NOT NULL,
    created_at timestamp(0) without time zone
);


ALTER TABLE public.password_reset_tokens OWNER TO ericslarson;

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
    CONSTRAINT regions_type_check CHECK (((type)::text = ANY ((ARRAY['city'::character varying, 'county'::character varying, 'state'::character varying, 'custom'::character varying])::text[])))
);


ALTER TABLE public.regions OWNER TO ericslarson;

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
-- Name: sessions; Type: TABLE; Schema: public; Owner: ericslarson
--

CREATE TABLE public.sessions (
    id character varying(255) NOT NULL,
    user_id bigint,
    ip_address character varying(45),
    user_agent text,
    payload text NOT NULL,
    last_activity integer NOT NULL
);


ALTER TABLE public.sessions OWNER TO ericslarson;

--
-- Name: user_list_favorites; Type: TABLE; Schema: public; Owner: ericslarson
--

CREATE TABLE public.user_list_favorites (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    list_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.user_list_favorites OWNER TO ericslarson;

--
-- Name: user_list_favorites_id_seq; Type: SEQUENCE; Schema: public; Owner: ericslarson
--

CREATE SEQUENCE public.user_list_favorites_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.user_list_favorites_id_seq OWNER TO ericslarson;

--
-- Name: user_list_favorites_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: ericslarson
--

ALTER SEQUENCE public.user_list_favorites_id_seq OWNED BY public.user_list_favorites.id;


--
-- Name: users; Type: TABLE; Schema: public; Owner: ericslarson
--

CREATE TABLE public.users (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    email character varying(255) NOT NULL,
    email_verified_at timestamp(0) without time zone,
    password character varying(255) NOT NULL,
    remember_token character varying(100),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    username character varying(255),
    role character varying(255) DEFAULT 'user'::character varying NOT NULL,
    bio text,
    avatar character varying(255),
    cover_image character varying(255),
    social_links json,
    preferences json,
    permissions json,
    is_public boolean DEFAULT true NOT NULL,
    last_active_at timestamp(0) without time zone,
    custom_url character varying(50),
    CONSTRAINT users_role_check CHECK (((role)::text = ANY ((ARRAY['admin'::character varying, 'manager'::character varying, 'editor'::character varying, 'business_owner'::character varying, 'user'::character varying])::text[])))
);


ALTER TABLE public.users OWNER TO ericslarson;

--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: ericslarson
--

CREATE SEQUENCE public.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.users_id_seq OWNER TO ericslarson;

--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: ericslarson
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- Name: categories id; Type: DEFAULT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.categories ALTER COLUMN id SET DEFAULT nextval('public.categories_id_seq'::regclass);


--
-- Name: claims id; Type: DEFAULT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.claims ALTER COLUMN id SET DEFAULT nextval('public.claims_id_seq'::regclass);


--
-- Name: comments id; Type: DEFAULT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.comments ALTER COLUMN id SET DEFAULT nextval('public.comments_id_seq'::regclass);


--
-- Name: directory_entries id; Type: DEFAULT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.directory_entries ALTER COLUMN id SET DEFAULT nextval('public.directory_entries_id_seq'::regclass);


--
-- Name: failed_jobs id; Type: DEFAULT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.failed_jobs ALTER COLUMN id SET DEFAULT nextval('public.failed_jobs_id_seq'::regclass);


--
-- Name: jobs id; Type: DEFAULT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.jobs ALTER COLUMN id SET DEFAULT nextval('public.jobs_id_seq'::regclass);


--
-- Name: list_items id; Type: DEFAULT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.list_items ALTER COLUMN id SET DEFAULT nextval('public.list_items_id_seq'::regclass);


--
-- Name: list_media id; Type: DEFAULT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.list_media ALTER COLUMN id SET DEFAULT nextval('public.list_media_id_seq'::regclass);


--
-- Name: lists id; Type: DEFAULT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.lists ALTER COLUMN id SET DEFAULT nextval('public.lists_id_seq'::regclass);


--
-- Name: locations id; Type: DEFAULT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.locations ALTER COLUMN id SET DEFAULT nextval('public.locations_id_seq'::regclass);


--
-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);


--
-- Name: regions id; Type: DEFAULT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.regions ALTER COLUMN id SET DEFAULT nextval('public.regions_id_seq'::regclass);


--
-- Name: user_list_favorites id; Type: DEFAULT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.user_list_favorites ALTER COLUMN id SET DEFAULT nextval('public.user_list_favorites_id_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- Data for Name: cache; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.cache (key, value, expiration) FROM stdin;
\.


--
-- Data for Name: cache_locks; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.cache_locks (key, owner, expiration) FROM stdin;
\.


--
-- Data for Name: categories; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.categories (id, name, slug, parent_id, icon, order_index, created_at, updated_at, description) FROM stdin;
1	Restaurants	restaurants	\N	\N	0	2025-06-01 20:18:34	2025-06-01 20:18:34	\N
2	Italian	italian	1	\N	0	2025-06-01 20:18:34	2025-06-01 20:18:34	\N
3	Mexican	mexican	1	\N	0	2025-06-01 20:18:34	2025-06-01 20:18:34	\N
4	Asian	asian	1	\N	0	2025-06-01 20:18:34	2025-06-01 20:18:34	\N
5	American	american	1	\N	0	2025-06-01 20:18:34	2025-06-01 20:18:34	\N
6	Seafood	seafood	1	\N	0	2025-06-01 20:18:34	2025-06-01 20:18:34	\N
7	Shopping	shopping	\N	\N	0	2025-06-01 20:18:34	2025-06-01 20:18:34	\N
8	Clothing	clothing	7	\N	0	2025-06-01 20:18:34	2025-06-01 20:18:34	\N
9	Electronics	electronics	7	\N	0	2025-06-01 20:18:34	2025-06-01 20:18:34	\N
11	Home & Garden	home-garden	7	\N	0	2025-06-01 20:18:34	2025-06-01 20:18:34	\N
12	Services	services	\N	\N	0	2025-06-01 20:18:34	2025-06-01 20:18:34	\N
13	Auto Repair	auto-repair	12	\N	0	2025-06-01 20:18:34	2025-06-01 20:18:34	\N
14	Beauty & Spa	beauty-spa	12	\N	0	2025-06-01 20:18:34	2025-06-01 20:18:34	\N
16	Professional	professional	12	\N	0	2025-06-01 20:18:34	2025-06-01 20:18:34	\N
17	Entertainment	entertainment	\N	\N	0	2025-06-01 20:18:34	2025-06-01 20:18:34	\N
18	Movies	movies	17	\N	0	2025-06-01 20:18:34	2025-06-01 20:18:34	\N
19	Sports	sports	17	\N	0	2025-06-01 20:18:34	2025-06-01 20:18:34	\N
20	Music Venues	music-venues	17	\N	0	2025-06-01 20:18:34	2025-06-01 20:18:34	\N
21	Parks & Recreation	parks-recreation	17	\N	0	2025-06-01 20:18:34	2025-06-01 20:18:34	\N
22	Online	online	\N	\N	0	2025-06-01 20:18:34	2025-06-01 20:18:34	\N
23	E-commerce	e-commerce	22	\N	0	2025-06-01 20:18:34	2025-06-01 20:18:34	\N
24	Digital Services	digital-services	22	\N	0	2025-06-01 20:18:34	2025-06-01 20:18:34	\N
25	Educational	educational	22	\N	0	2025-06-01 20:18:34	2025-06-01 20:18:34	\N
26	Software	software	22	\N	0	2025-06-01 20:18:34	2025-06-01 20:18:34	\N
27	Food	food	7	\N	0	2025-06-28 00:21:06	2025-06-28 00:21:06	\N
\.


--
-- Data for Name: claims; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.claims (id, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: comments; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.comments (id, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: directory_entries; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.directory_entries (id, title, slug, description, type, category_id, region_id, tags, owner_user_id, created_by_user_id, updated_by_user_id, phone, email, website_url, social_links, featured_image, gallery_images, status, is_featured, is_verified, is_claimed, meta_title, meta_description, structured_data, view_count, list_count, created_at, updated_at, published_at, logo_url, cover_image_url, facebook_url, instagram_handle, twitter_handle, youtube_channel, messenger_contact, price_range, takes_reservations, accepts_credit_cards, wifi_available, pet_friendly, parking_options, wheelchair_accessible, outdoor_seating, kid_friendly, video_urls, pdf_files, hours_of_operation, special_hours, temporarily_closed, open_24_7, cross_streets, neighborhood) FROM stdin;
2	TechHub Electronics	techhub-electronics	Your one-stop shop for all electronics needs. Best prices guaranteed!	physical_location	9	\N	["walk-in","appointment"]	\N	1	\N	(555) 987-6543	support@techhub.com	https://techhub.com	{"facebook":"https:\\/\\/facebook.com\\/techhub-electronics","instagram":"https:\\/\\/instagram.com\\/techhub-electronics"}	\N	\N	published	t	f	f	\N	\N	\N	0	0	2025-06-01 20:18:34	2025-06-01 20:18:34	2025-06-01 20:18:34	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N
3	Serenity Spa & Wellness	serenity-spa-wellness	Relax and rejuvenate with our premium spa services.	physical_location	14	\N	["local","appointment"]	\N	1	\N	(555) 456-7890	relax@serenityspa.com	https://serenityspa.com	{"facebook":"https:\\/\\/facebook.com\\/serenity-spa-wellness","instagram":"https:\\/\\/instagram.com\\/serenity-spa-wellness"}	\N	\N	published	t	f	f	\N	\N	\N	0	0	2025-06-01 20:18:34	2025-06-01 20:18:34	2025-06-01 20:18:34	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N
4	CloudTech Solutions	cloudtech-solutions	Enterprise cloud computing solutions for modern businesses.	online_business	26	1	["digital","worldwide"]	\N	1	\N	(800) 555-2468	contact@cloudtechsolutions.com	https://cloudtechsolutions.com	{"facebook":"https:\\/\\/facebook.com\\/cloudtech-solutions","instagram":"https:\\/\\/instagram.com\\/cloudtech-solutions"}	\N	\N	published	t	t	f	\N	\N	\N	0	0	2025-06-01 20:18:34	2025-06-01 20:18:34	2025-06-01 20:18:34	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N
5	LearnPro Online Academy	learnpro-online-academy	Professional development courses and certifications online.	online_business	25	1	["digital","remote"]	\N	1	\N	\N	info@learnpro.edu	https://learnpro.edu	{"facebook":"https:\\/\\/facebook.com\\/learnpro-online-academy","instagram":"https:\\/\\/instagram.com\\/learnpro-online-academy"}	\N	\N	published	t	f	f	\N	\N	\N	0	0	2025-06-01 20:18:34	2025-06-01 20:18:34	2025-06-01 20:18:34	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N
6	Mobile Pet Grooming	mobile-pet-grooming	We come to you! Professional pet grooming at your doorstep.	service	16	1	["mobile","professional","licensed"]	\N	1	\N	(555) 321-9876	woof@mobilepetgrooming.com	https://mobilepetgrooming.com	{"facebook":"https:\\/\\/facebook.com\\/mobile-pet-grooming","instagram":"https:\\/\\/instagram.com\\/mobile-pet-grooming"}	\N	\N	published	f	t	f	\N	\N	\N	0	0	2025-06-01 20:18:34	2025-06-01 20:18:34	2025-06-01 20:18:34	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N
1	Joe's Italian Restaurant	joes-italian-restaurant	Authentic Italian cuisine in the heart of downtown. Family-owned since 1985.	physical_location	2	\N	["local","in-person","walk-in"]	\N	1	2	(555) 123-4567	info@joesitalian.com	https://joesitalian.com	{"facebook":"https:\\/\\/facebook.com\\/joes-italian-restaurant","instagram":"https:\\/\\/instagram.com\\/joes-italian-restaurant"}	\N	[]	published	t	f	f	\N	\N	\N	0	0	2025-06-01 20:18:34	2025-06-29 01:00:26	2025-06-01 20:18:34	http://localhost:8000/storage/directory-entries/logos/OXZPgSBJBQfzs22Y1cGZq3Lvn1akO3hq3JElPIEQ.jpeg	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N
8	LayerCake Marketing	layercake-marketing	Digital Solutions. We make web, mobile, social and marketing solutions that accelerate and grow your business. From conception to perfection, we craft each one of our digital products by hand.	online_business	16	\N	[]	\N	2	2	714-261-0903	eric@layercakemarketing.com	https://layercake.marketing	[]	\N	["http:\\/\\/localhost:8000\\/storage\\/directory-entries\\/gallerys\\/cw7cWYWZaU2jzej8t94dj6yNdTLG12sI8EtzDdY3.jpg","http:\\/\\/localhost:8000\\/storage\\/directory-entries\\/gallerys\\/xk7Sdg9fZ0sllt3TqC6SdIvTyBBhjBWTm5BhzBZ0.png"]	published	f	f	f	\N	\N	\N	0	0	2025-06-01 22:44:55	2025-06-29 00:50:21	\N	http://localhost:8000/storage/directory-entries/logos/a59072tzOZFkjWXKFG8GWv25dbSrWNhqYOa8iSz8.png	http://localhost:8000/storage/directory-entries/covers/rdRtO40X9QZxabjFYc1zc0cZnEHCAEhNzw735Oht.png	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N
69	The Mogul Restaurant	the-mogul-restaurant	Historic mountain lodge restaurant offering American cuisine.	physical_location	1	\N	["restaurant","american","dinner","family_friendly"]	\N	2	\N	\N	info@themogulrestaurant.com	https://themogulrestaurant.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-06-29 03:06:50	2025-06-29 03:06:50	\N	\N	https://www.facebook.com/TheMogulRestaurant	@themogulrestaurant	\N	\N	\N	$$	\N	\N	\N	\N	\N	\N	t	t	\N	\N	\N	\N	f	f	Hwy 203 & Minaret Rd	Old Mammoth
70	Roberto's Café	robertos-cafe	Casual Italian restaurant serving wood-fired pizzas and homemade pasta.	physical_location	1	\N	["italian","pizza","pasta","cafe"]	\N	2	\N	760.934.3667	info@robertoscafe.com	https://robertoscafe.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-06-29 03:06:50	2025-06-29 03:06:50	http://localhost:8000/storage/directory-entries/logos/tu6a7C6e3328SWmaE4KzC4LHv2b1Fu0AOEu06ZQ5.webp	\N	https://www.facebook.com/Roberto%27sCaf%C3%A9	\N	\N	\N	\N	$$	\N	\N	\N	\N	\N	\N	t	t	\N	\N	\N	\N	f	f	Main St & Old Mammoth Rd	Village at Mammoth
71	Good Life Café	good-life-cafe	Cozy café offering breakfast, brunch, and specialty coffee.	physical_location	1	\N	["cafe","breakfast","brunch","coffee"]	\N	2	\N	\N	info@goodlifecafe.com	https://goodlifecafe.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-06-29 03:06:50	2025-06-29 03:06:50	\N	\N	https://www.facebook.com/GoodLifeCaf%C3%A9	\N	\N	\N	\N	$$	\N	\N	\N	\N	\N	\N	t	t	\N	\N	\N	\N	f	f	Lake Mary Rd & Forest Trail	Mammoth Lakes
73	Sazon Restaurant	sazon-restaurant	Mexican and Latin American cuisine with tequila bar.	physical_location	1	\N	["mexican","latin","tequila_bar","seafood"]	\N	2	\N	\N	info@sazonrestaurant.com	https://sazonrestaurant.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-06-29 03:06:50	2025-06-29 03:06:50	\N	\N	https://www.facebook.com/SazonRestaurant	@sazonrestaurant	\N	\N	\N	$$	\N	\N	\N	\N	\N	\N	t	t	\N	\N	\N	\N	f	f	Old Mammoth Rd & Meridian Blvd	Village at Mammoth
74	Skadi	skadi	Upscale fine dining with French-inspired cuisine.	physical_location	1	\N	["fine_dining","french","seafood","steakhouse"]	\N	2	\N	\N	info@skadi.com	https://skadi.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-06-29 03:06:50	2025-06-29 03:06:50	\N	\N	https://www.facebook.com/Skadi	@skadi	\N	\N	\N	$$	\N	\N	\N	\N	\N	\N	t	t	\N	\N	\N	\N	f	f	Main St & Old Mammoth Rd	Downtown
75	Mammoth Brewing Company	mammoth-brewing-company	Craft brewery and pub with rotating beers and pub fare.	physical_location	1	\N	["brewery","beer","brewpub","bar"]	\N	2	\N	\N	info@mammothbrewingcompany.com	https://mammothbrewingcompany.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-06-29 03:06:50	2025-06-29 03:06:50	\N	\N	https://www.facebook.com/MammothBrewingCompany	@mammothbrewingcompany	\N	\N	\N	$$	\N	\N	\N	\N	\N	\N	t	t	\N	\N	\N	\N	f	f	Main St & Lakeview Blvd	Lakeshore
76	Toomey's	toomeys	Upscale bar and restaurant with craft cocktails.	physical_location	1	\N	["bar","restaurant","cocktails","upscale"]	\N	2	\N	\N	info@toomeys.com	https://toomeys.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-06-29 03:06:50	2025-06-29 03:06:50	\N	\N	https://www.facebook.com/Toomey%27s	@toomeys	\N	\N	\N	$$	\N	\N	\N	\N	\N	\N	t	t	\N	\N	\N	\N	f	f	Canyon Blvd & Main St	Village
77	Smokeyard BBQ	smokeyard-bbq	Authentic smoked barbecue with Southern sides.	physical_location	1	\N	["barbecue","bbq","smoked_meats","southern"]	\N	2	\N	\N	info@smokeyardbbq.com	https://smokeyardbbq.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-06-29 03:06:50	2025-06-29 03:06:50	\N	\N	https://www.facebook.com/SmokeyardBBQ	@smokeyardbbq	\N	\N	\N	$$	\N	\N	\N	\N	\N	\N	t	t	\N	\N	\N	\N	f	f	Old Mammoth Rd & Wood Rd	Village at Mammoth
78	Gabriella's Italian Ristorante	gabriellas-italian-ristorante	Family-owned Italian restaurant serving classic dishes.	physical_location	1	\N	["italian","fine_dining","family_friendly"]	\N	2	\N	\N	info@gabriellasitalianristorante.com	https://gabriellasitalianristorante.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-06-29 03:06:50	2025-06-29 03:06:50	\N	\N	https://www.facebook.com/Gabriella%27sItalianRistorante	@gabriellasitalianristorante	\N	\N	\N	$$	\N	\N	\N	\N	\N	\N	t	t	\N	\N	\N	\N	f	f	Main St & Majestic Pines Cir	Old Mammoth
79	Jax at Mammoth	jax-at-mammoth	Women's apparel, accessories and gifts.	physical_location	7	\N	["retail","clothing","womens_fashion"]	\N	2	\N	\N	info@jaxatmammoth.com	https://jaxatmammoth.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-06-29 03:06:50	2025-06-29 03:06:50	\N	\N	https://www.facebook.com/JaxatMammoth	@jaxatmammoth	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	t	\N	\N	\N	\N	f	f	Old Mammoth Rd & Minaret Rd	Village
80	Franz Klammer Sports	franz-klammer-sports	Ski and snowboard equipment rental and retail.	physical_location	7	\N	["retail","ski_rental","snowboard","gear"]	\N	2	\N	\N	info@franzklammersports.com	https://franzklammersports.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-06-29 03:06:50	2025-06-29 03:06:50	\N	\N	https://www.facebook.com/FranzKlammerSports	@franzklammersports	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	t	\N	\N	\N	\N	f	f	Old Mammoth Rd & Minaret Rd	Village
81	Mammoth Mountain Ski & Board	mammoth-mountain-ski-board	Ski and snowboard rentals plus retail shop.	physical_location	7	\N	["rental","ski","snowboard","gear"]	\N	2	\N	\N	info@mammothmountainski&board.com	https://mammothmountainski&board.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-06-29 03:06:50	2025-06-29 03:06:50	\N	\N	https://www.facebook.com/MammothMountainSki%26Board	@mammothmountainski&board	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	t	\N	\N	\N	\N	f	f	Hwy 203 & Minaret Rd	Mammoth Mountain
82	Volcom Store	volcom-store	Skate and snowboard apparel and accessories.	physical_location	7	\N	["retail","apparel","skate","snowboard"]	\N	2	\N	\N	info@volcomstore.com	https://volcomstore.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-06-29 03:06:50	2025-06-29 03:06:50	\N	\N	https://www.facebook.com/VolcomStore	@volcomstore	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	t	\N	\N	\N	\N	f	f	Old Mammoth Rd	Village
83	Sierra Runner	sierra-runner	Footwear and sporting goods store.	physical_location	7	\N	["retail","footwear","sports","run"]	\N	2	\N	\N	info@sierrarunner.com	https://sierrarunner.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-06-29 03:06:50	2025-06-29 03:06:50	\N	\N	https://www.facebook.com/SierraRunner	@sierrarunner	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	t	\N	\N	\N	\N	f	f	Old Mammoth Rd & Minaret Rd	Village
84	Denali Surf & Sport	denali-surf-sport	Surf, skate, and snow gear.	physical_location	7	\N	["retail","skate","board","surf"]	\N	2	\N	\N	info@denalisurf&sport.com	https://denalisurf&sport.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-06-29 03:06:50	2025-06-29 03:06:50	\N	\N	https://www.facebook.com/DenaliSurf%26Sport	@denalisurf&sport	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	t	\N	\N	\N	\N	f	f	Old Mammoth Rd & Peterson Way	Village
85	The Powder House & Gear	the-powder-house-gear	Ski, snowboard rentals and outerwear retail.	physical_location	7	\N	["retail","equipment_rental","ski","snowboard"]	\N	2	\N	\N	info@thepowderhouse&gear.com	https://thepowderhouse&gear.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-06-29 03:06:50	2025-06-29 03:06:50	\N	\N	https://www.facebook.com/ThePowderHouse%26Gear	@thepowderhouse&gear	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	t	\N	\N	\N	\N	f	f	Forest Trail & Lake Mary Rd	Mammoth Lakes
86	Mammoth Camper & RV	mammoth-camper-rv	Camper rental and outdoor gear.	physical_location	7	\N	["rental","camping","rv","gear"]	\N	2	\N	\N	info@mammothcamper&rv.com	https://mammothcamper&rv.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-06-29 03:06:50	2025-06-29 03:06:50	\N	\N	https://www.facebook.com/MammothCamper%26RV	@mammothcamper&rv	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	t	\N	\N	\N	\N	f	f	Meridian Blvd & Lakeshore Blvd	Lakeshore
87	Mountain Shop	mountain-shop	Outdoor clothing and equipment retailer.	physical_location	7	\N	["retail","outdoor","clothing","equipment"]	\N	2	\N	\N	info@mountainshop.com	https://mountainshop.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-06-29 03:06:50	2025-06-29 03:06:50	\N	\N	https://www.facebook.com/MountainShop	@mountainshop	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	t	\N	\N	\N	\N	f	f	Old Mammoth Rd & Minaret Rd	Village
88	Majestic Fine Art	majestic-fine-art	Fine art gallery featuring local artists.	physical_location	7	\N	["art","gallery","shopping","local_artists"]	\N	2	\N	\N	info@majesticfineart.com	https://majesticfineart.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-06-29 03:06:50	2025-06-29 03:06:50	\N	\N	https://www.facebook.com/MajesticFineArt	@majesticfineart	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	t	\N	\N	\N	\N	f	f	Main St & Old Mammoth Rd	Old Mammoth
90	Mammoth Lakes Welcome Center	mammoth-lakes-welcome-center	Visitor center with local information and gift shop.	physical_location	17	\N	["visitor_center","tourism","information","gifts"]	\N	2	\N	\N	info@mammothlakeswelcomecenter.com	https://mammothlakeswelcomecenter.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-06-29 03:06:50	2025-06-29 03:06:50	\N	\N	https://www.facebook.com/MammothLakesWelcomeCenter	@mammothlakeswelcomecenter	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	t	\N	\N	\N	\N	f	f	Main St & Old Mammoth Rd	Downtown
91	Canyon Cinema	canyon-cinema	Local movie theater showing new releases and classics.	physical_location	17	\N	["cinema","movies","entertainment","family"]	\N	2	\N	\N	info@canyoncinema.com	https://canyoncinema.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-06-29 03:06:50	2025-06-29 03:06:50	\N	\N	https://www.facebook.com/CanyonCinema	@canyoncinema	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	t	\N	\N	\N	\N	f	f	College Pkwy & Old Mammoth Rd	Community Center
92	Mammoth Lakes Ice Rink	mammoth-lakes-ice-rink	Outdoor ice skating rink open seasonally.	physical_location	17	\N	["ice_skating","rink","seasonal","entertainment"]	\N	2	\N	\N	info@mammothlakesicerink.com	https://mammothlakesicerink.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-06-29 03:06:50	2025-06-29 03:06:50	\N	\N	https://www.facebook.com/MammothLakesIceRink	@mammothlakesicerink	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	t	\N	\N	\N	\N	f	f	Minaret Rd & Manzanita Rd	Recreation
94	Mammoth Lakes Film Festival	mammoth-lakes-film-festival	Annual film festival showcasing independent films.	physical_location	17	\N	["festival","movies","arts","entertainment"]	\N	2	\N	\N	info@mammothlakesfilmfestival.com	https://mammothlakesfilmfestival.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-06-29 03:06:50	2025-06-29 03:06:50	\N	\N	https://www.facebook.com/MammothLakesFilmFestival	@mammothlakesfilmfestival	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	t	\N	\N	\N	\N	f	f	College Pkwy & Old Mammoth Rd	Community Center
96	Epic Discovery	epic-discovery	Adventure park with zip lines, climbing garden, and mountain coaster.	physical_location	17	\N	["adventure","zip_line","coaster","climbing"]	\N	2	\N	\N	info@epicdiscovery.com	https://epicdiscovery.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-06-29 03:06:50	2025-06-29 03:06:50	\N	\N	https://www.facebook.com/EpicDiscovery	@epicdiscovery	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	t	\N	\N	\N	\N	f	f	Hwy 203 & Minaret Rd	Mammoth Mountain
93	Sierra Star Golf Course	sierra-star-golf-course	Resort golf course with mountain views.	physical_location	19	\N	["golf","sports","recreation","entertainment"]	\N	2	2	\N	info@sierrastargolfcourse.com	https://sierrastargolfcourse.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-06-30 05:58:06	2025-06-29 03:06:50	\N	\N	https://www.facebook.com/SierraStarGolfCourse	@sierrastargolfcourse	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	t	\N	\N	\N	\N	f	f	Sierra Star Dr & Minaret Rd	Sierra Star
97	Woolly's Tube Park	woollys-tube-park	Snow tubing park with multiple lanes and lifts.	physical_location	17	\N	["tubing","winter_sports","entertainment"]	\N	2	\N	\N	info@woollystubepark.com	https://woollystubepark.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-06-29 03:06:50	2025-06-29 03:06:50	\N	\N	https://www.facebook.com/Woolly%27sTubePark	@woollystubepark	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	t	\N	\N	\N	\N	f	f	Hwy 203 & Minaret Rd	Mammoth Mountain
89	Mammoth Mountain Scenic Gondola	mammoth-mountain-scenic-gondola	Scenic gondola ride to Eagle Lodge with panoramic views.	physical_location	21	\N	["gondola","scenic","ride","views"]	\N	2	2	\N	info@mammothmountainscenicgondola.com	https://mammothmountainscenicgondola.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-06-29 03:44:16	2025-06-29 03:06:50	\N	\N	https://www.facebook.com/MammothMountainScenicGondola	@mammothmountainscenicgondola	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	t	\N	\N	\N	\N	f	f	Hwy 203 & Minaret Rd	Mammoth Mountain
72	Burgers Restaurant	burgers-restaurant	Gourmet burgers, fries, and milkshakes in a casual setting.	physical_location	5	\N	["burgers","american","casual","diner"]	\N	2	2	\N	info@burgersrestaurant.com	https://burgersrestaurant.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-06-30 05:45:50	2025-06-29 03:06:50	\N	\N	https://www.facebook.com/BurgersRestaurant	@burgersrestaurant	\N	\N	\N	$$	\N	\N	\N	\N	\N	\N	t	t	\N	\N	\N	\N	f	f	Old Mammoth Rd & Manzanita Rd	Village at Mammoth
95	Mammoth Brewing Co. Live Stage	mammoth-brewing-co-live-stage	<p>Live music venue inside Mammoth Brewing Company.</p><p><strong><em>Mammoth Brewing Co.:</em></strong><br>Sunday – Thursday: 10am – 9pm*<br>Friday &amp; Saturday: 10am – 9:30pm*</p><p><strong><em>The EATery:</em></strong><br>Daily: 11:00am – Close</p><p>*Closing times are subject to early closures during shoulder seasons</p><p><strong>Reservations:</strong> We do not take reservations.</p><p></p><h2><strong>Welcome to MBC &amp; The EATery<br></strong></h2><p>We’ve been obsessively crafting beers since 1995. We pioneer, curious and unfaltering, determined not to stop until we achieve mouth-watering perfection, often at the expense of reasonable bedtimes and our social lives. Pulling inspiration from our natural surroundings, we boldly blend the best of our local ingredients with the know-how we’ve picked up from years of brewing award-winning beer.</p><p>Our brewery, tasting room, beer garden, and retail store are located at 18 Lake Mary Road, at the corner of Main Street &amp; Minaret Road in Mammoth Lakes. The EATery is located in the tasting room, supplying amazing beer-centric food by chef Brandon Brocia. Check out The EATery menu here. Savor a pint and a bite to eat, sample a tasting flight, pick up a 6-pack, fill your growler, and enjoy the mountain views and friendly atmosphere.</p><p>Your well-behaved, leashed pupper is welcome in our outdoor beer garden. No barking, biting, or begging, and don't leave your trail buddy unattended.</p><p>Fun events include live music and Trivia Night, and we also host private events. See our Events page for more information.</p>	physical_location	5	\N	["live_music","venue","concerts","brewery"]	\N	2	2	\N	info@mammothbrewingcolivestage.com	https://mammothbrewingco.com/	\N	\N	["http:\\/\\/localhost:8000\\/storage\\/directory-entries\\/gallerys\\/lXui2uPooEfFvkR0unMgRmwF6fm4nG5Pq9yfcqTD.jpg","http:\\/\\/localhost:8000\\/storage\\/directory-entries\\/gallerys\\/T8YgxkYcUrSK6Kzaq2PJMKNVFIYBoxUCGib8i15x.jpg"]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-06-30 06:13:53	2025-06-29 03:06:50	http://localhost:8000/storage/directory-entries/logos/LvIENSxqBs2eeg3lI0wiTjoNFQA7J2TUGZ7SZXEq.webp	http://localhost:8000/storage/directory-entries/covers/gdKhU1dJWT6uRIxFnzG5MnzVPG7lWsUPu3O9k0FC.jpg	https://www.facebook.com/MammothBrewingCo.LiveStage	@mammothbrewingcolivestage	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	t	\N	\N	\N	\N	f	f	Main St & Lakeview Blvd	Lakeshore
98	Mammoth Lakes Museum	mammoth-lakes-museum	<h1>Our Hours</h1><p><strong>June-september: </strong>10 AM to 6 PM</p><p><strong>Open Monday-Sunday, closed on Wednesdays</strong></p><p><strong>We are open six days a week Memorial Weekend through the end of September.</strong></p><hr><h1>Other Information</h1><p><strong>Fees</strong>: FREE! A $5.00 donation is suggested &amp; memberships are appreciated.</p><p><strong>ADA Accessibility:&nbsp;</strong>Access is available for those who park on the lawn near the disabled placard and enter through the kitchen (back door) on the ramp.</p><p><strong>Eco-Friendly:&nbsp;</strong>Connected to the town’s bike trails in Mammoth Lakes.</p><p><strong>Pet Friendly: &nbsp;</strong>Pets should be leashed on the museum grounds per the Town of Mammoth Lakes and the U.S. Forest Service leash laws. Service animals only are welcome inside the museum. We provide kennels a.k.a “doggie dorms” outside for non-service animals. <strong>Please clean up after your pets!</strong></p>	physical_location	21	\N	["museum","history","culture","education"]	\N	2	2	(760) 934-6918	info@mammothlakesmuseum.org	https://www.mammothmuseum.org	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-06-30 06:16:18	2025-06-29 03:06:50	http://localhost:8000/storage/directory-entries/logos/R2eyXXEJllqFOz39eFE4frXx2enDrLjHtzWaDUcY.jpg	\N	https://www.facebook.com/MammothLakesMuseum	@mammothlakesmuseum	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	t	\N	\N	\N	\N	f	f	Trono Ln & Minaret Rd	Old Mammoth
\.


--
-- Data for Name: failed_jobs; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.failed_jobs (id, uuid, connection, queue, payload, exception, failed_at) FROM stdin;
\.


--
-- Data for Name: job_batches; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.job_batches (id, name, total_jobs, pending_jobs, failed_jobs, failed_job_ids, options, cancelled_at, created_at, finished_at) FROM stdin;
\.


--
-- Data for Name: jobs; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.jobs (id, queue, payload, attempts, reserved_at, available_at, created_at) FROM stdin;
\.


--
-- Data for Name: list_items; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.list_items (id, list_id, directory_entry_id, order_index, notes, affiliate_url, custom_data, created_at, updated_at, type, title, content, data, image) FROM stdin;
1	1	\N	0	\N	\N	\N	2025-06-02 04:39:50	2025-06-02 04:39:50	text	List Item 1	\N	\N	\N
2	1	\N	1	\N	\N	\N	2025-06-02 04:41:26	2025-06-02 04:41:26	event	Go to Mammoth	Ski	{"start_date":"2025-06-01T10:41","location":"Mammoth Mtn. California."}	\N
3	1	\N	2	\N	\N	\N	2025-06-02 04:44:29	2025-06-02 04:44:29	location	Home	\N	{"latitude":33.67174,"longitude":117.815283,"address":"15 New Meadow","name":"Home"}	\N
30	8	\N	0	\N	\N	\N	2025-06-28 06:12:26	2025-06-28 06:12:33	text	🏖 Beach Towel or Blanket	For lounging or drying off after a swim	\N	\N
29	8	\N	1	\N	\N	\N	2025-06-28 06:11:52	2025-06-28 06:12:33	text	🧴 Sunscreen	Broad-spectrum SPF 30+ to avoid sunburn	\N	\N
11	5	\N	0	\N	\N	\N	2025-06-27 06:07:47	2025-06-27 07:40:20	text	Go To the Beach	10 minutes from some of the best beaches in Southern California	\N	\N
16	5	2	1	\N	\N	\N	2025-06-27 06:14:54	2025-06-27 07:40:20	directory_entry	\N	\N	\N	\N
12	5	\N	2	\N	\N	\N	2025-06-27 06:07:58	2025-06-27 07:40:20	text	Wild Rivers	\N	\N	\N
13	5	\N	3	\N	\N	\N	2025-06-27 06:08:15	2025-06-27 07:40:20	text	The Spectrum	\N	\N	\N
14	5	\N	4	\N	\N	\N	2025-06-27 06:08:21	2025-06-27 07:40:20	text	The Great Park	\N	\N	\N
15	5	\N	5	\N	\N	\N	2025-06-27 06:08:27	2025-06-27 07:40:20	text	Hiking	\N	\N	\N
17	3	\N	0	\N	\N	\N	2025-06-28 05:29:27	2025-06-28 05:30:08	text	Sleeping Bag	\N	\N	\N
20	3	\N	1	\N	\N	\N	2025-06-28 05:30:03	2025-06-28 05:30:08	text	Sleeping Pad	\N	\N	\N
18	3	\N	2	\N	\N	\N	2025-06-28 05:29:34	2025-06-28 05:30:08	text	Tent	\N	\N	\N
19	3	\N	3	\N	\N	\N	2025-06-28 05:29:49	2025-06-28 05:30:08	text	Bear (food) Canister	\N	\N	\N
21	3	\N	4	\N	\N	\N	2025-06-28 05:30:36	2025-06-28 05:30:36	text	Water Purifier	\N	\N	\N
22	3	\N	5	\N	\N	\N	2025-06-28 05:30:50	2025-06-28 05:30:50	text	Water Blatter, Containers	\N	\N	\N
23	3	\N	6	\N	\N	\N	2025-06-28 05:31:05	2025-06-28 05:31:05	text	Camp Stove (light weight)	\N	\N	\N
24	3	\N	7	\N	\N	\N	2025-06-28 05:31:29	2025-06-28 05:31:29	text	Rain Poncho	\N	\N	\N
25	3	\N	8	\N	\N	\N	2025-06-28 05:31:32	2025-06-28 05:31:32	text	Layered Clothing	\N	\N	\N
26	3	\N	9	\N	\N	\N	2025-06-28 05:37:56	2025-06-28 05:37:56	location	Big Pine Creek	\N	{"latitude":37.14823138276758,"longitude":-118.32850752687487,"address":"Big Pine Creek, California 93513","name":"Big Pine Creek"}	\N
31	8	\N	3	\N	\N	\N	2025-06-28 06:13:36	2025-06-28 06:13:36	text	🕶 Sunglasses	Protect los ojos from the UV burn.	\N	\N
32	8	\N	4	\N	\N	\N	2025-06-28 06:13:55	2025-06-28 06:13:55	text	💺 Beach Chair	Foldable and lightweight for relaxed seating	\N	\N
33	8	\N	5	\N	\N	\N	2025-06-28 06:14:12	2025-06-28 06:14:12	text	🩱 Swimsuit + Change of Clothes	Dry gear for comfort after swimming	\N	\N
34	8	\N	6	\N	\N	\N	2025-06-28 06:14:31	2025-06-28 06:14:31	text	💧 Water + Snacks	Stay hydrated and avoid hangry vibes	\N	\N
28	8	\N	2	\N	\N	\N	2025-06-28 06:08:01	2025-06-28 06:15:36	text	⛱ Umbrella or Shade Tent	To stay cool and protected from UV rays	[]	\N
35	8	\N	7	\N	\N	\N	2025-06-28 06:16:12	2025-06-28 06:16:12	text	📱 Waterproof Speaker or Book	Entertainment options	\N	\N
36	8	\N	8	\N	\N	\N	2025-06-28 06:16:28	2025-06-28 06:16:28	text	🧼 Wet Wipes or Hand Sanitizer	For sandy hands and snack breaks	\N	\N
37	8	\N	9	\N	\N	\N	2025-06-28 06:16:48	2025-06-28 06:18:02	text	Baby Powder	for sand removal.	[]	\N
9	2	\N	0	\N	\N	\N	2025-06-02 04:59:09	2025-06-30 06:38:30	text	1. Suspension	\N	[]	\N
4	2	\N	1	\N	\N	\N	2025-06-02 04:54:59	2025-06-30 06:38:30	text	2. Insulation	Keep the van cool in the summer and warm in those cold winter months.	[]	\N
6	2	\N	2	\N	\N	\N	2025-06-02 04:57:30	2025-06-30 06:38:30	text	3. Plumbing	Kitchen and bath are many times an essential.	\N	\N
7	2	\N	4	\N	\N	\N	2025-06-02 04:58:53	2025-06-30 06:38:30	text	4. Bed	\N	\N	\N
8	2	\N	5	\N	\N	\N	2025-06-02 04:59:01	2025-06-30 06:38:30	text	5. Garage	\N	\N	\N
5	2	\N	3	\N	\N	\N	2025-06-02 04:56:45	2025-06-30 06:39:08	text	4. Power & Electrical	Inverter\nOff-grid power generation	[]	\N
\.


--
-- Data for Name: list_media; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.list_media (id, list_id, type, url, caption, order_index, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: lists; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.lists (id, user_id, name, description, is_public, created_at, updated_at, featured_image, slug, view_count, settings, visibility) FROM stdin;
3	2	Backpacking List	Everything you need to go solo backpacking	t	2025-06-27 05:22:30	2025-06-28 05:22:45	\N	backpacking-list	0	\N	private
1	2	Favorite places	\N	t	2025-06-02 04:39:15	2025-06-28 05:39:03	\N	favorite-places	3	\N	public
8	2	Day at the Beach	Items to bring when visiting the sand.	f	2025-06-28 06:07:38	2025-06-28 06:18:11	\N	day-at-the-beach	1	\N	private
5	2	Fun Things To Do in Irvine	\N	t	2025-06-27 05:43:35	2025-06-29 02:16:21	\N	fun-things-to-do-in-irvine	10	\N	public
7	2	Fun Day at the Park List	\N	t	2025-06-27 05:50:56	2025-06-29 02:16:31	\N	fun-day-at-the-park-list	3	\N	public
2	2	Van Build Essentials	If you’re thinking about living in a van and looking to build out your sprinter van here is a handy list of items to consider.	t	2025-06-02 04:51:35	2025-06-30 06:39:30	\N	van-build-essentials	2	\N	public
\.


--
-- Data for Name: locations; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.locations (id, directory_entry_id, address_line1, address_line2, city, state, zip_code, country, latitude, longitude, hours_of_operation, holiday_hours, is_wheelchair_accessible, has_parking, amenities, place_id, created_at, updated_at, geom, cross_streets, neighborhood) FROM stdin;
1	1	123 Main Street	\N	Los Angeles	CA	90001	USA	34.0522000	-118.2437000	{"monday":"11:00-22:00","tuesday":"11:00-22:00","wednesday":"11:00-22:00","thursday":"11:00-22:00","friday":"11:00-23:00","saturday":"11:00-23:00","sunday":"12:00-21:00"}	\N	f	t	["wifi","wheelchair_access","outdoor_seating","takeout","reservations"]	\N	2025-06-01 20:18:34	2025-06-01 20:18:34	0101000020E61000004182E2C7988F5DC0F46C567DAE064140	\N	\N
2	2	456 Technology Blvd	\N	San Francisco	CA	94105	USA	37.7749000	-122.4194000	{"monday":"10:00-20:00","tuesday":"10:00-20:00","wednesday":"10:00-20:00","thursday":"10:00-20:00","friday":"10:00-21:00","saturday":"10:00-21:00","sunday":"11:00-18:00"}	\N	t	t	["parking","delivery","takeout"]	\N	2025-06-01 20:18:34	2025-06-01 20:18:34	0101000020E610000050FC1873D79A5EC0D0D556EC2FE34240	\N	\N
3	3	789 Wellness Way	\N	Miami	FL	33101	USA	25.7617000	-80.1918000	{"monday":"09:00-19:00","tuesday":"09:00-19:00","wednesday":"09:00-19:00","thursday":"09:00-19:00","friday":"09:00-20:00","saturday":"09:00-20:00","sunday":"10:00-17:00"}	\N	t	t	["wifi","parking","reservations"]	\N	2025-06-01 20:18:34	2025-06-01 20:18:34	0101000020E6100000DCD78173460C54C0FB5C6DC5FEC23940	\N	\N
43	98	400 Trono Ln	\N	Mammoth Lakes	CA	93546	US	37.6357900	-118.9628900	\N	\N	f	f	\N	\N	2025-06-29 03:06:50	2025-06-29 03:21:25	0101000020E61000004FE960FD9FBD5DC0DA8F149161D14240	Trono Ln & Minaret Rd	Old Mammoth
14	69	10001 Minaret Rd	\N	Mammoth Lakes	CA	93546	US	0.0000000	0.0000000	\N	\N	f	f	\N	\N	2025-06-29 03:06:50	2025-06-29 03:06:50	0101000020E610000000000000000000000000000000000000	Hwy 203 & Minaret Rd	Old Mammoth
15	70	1002 Old Mammoth Rd	\N	Mammoth Lakes	CA	93546	US	0.0000000	0.0000000	\N	\N	f	f	\N	\N	2025-06-29 03:06:50	2025-06-29 03:06:50	0101000020E610000000000000000000000000000000000000	Main St & Old Mammoth Rd	Village at Mammoth
16	71	1039 Forest Trail	\N	Mammoth Lakes	CA	93546	US	0.0000000	0.0000000	\N	\N	f	f	\N	\N	2025-06-29 03:06:50	2025-06-29 03:06:50	0101000020E610000000000000000000000000000000000000	Lake Mary Rd & Forest Trail	Mammoth Lakes
17	72	114 Old Mammoth Rd	\N	Mammoth Lakes	CA	93546	US	0.0000000	0.0000000	\N	\N	f	f	\N	\N	2025-06-29 03:06:50	2025-06-29 03:06:50	0101000020E610000000000000000000000000000000000000	Old Mammoth Rd & Manzanita Rd	Village at Mammoth
18	73	678 Old Mammoth Rd	\N	Mammoth Lakes	CA	93546	US	0.0000000	0.0000000	\N	\N	f	f	\N	\N	2025-06-29 03:06:50	2025-06-29 03:06:50	0101000020E610000000000000000000000000000000000000	Old Mammoth Rd & Meridian Blvd	Village at Mammoth
19	74	2763 Main St	\N	Mammoth Lakes	CA	93546	US	0.0000000	0.0000000	\N	\N	f	f	\N	\N	2025-06-29 03:06:50	2025-06-29 03:06:50	0101000020E610000000000000000000000000000000000000	Main St & Old Mammoth Rd	Downtown
20	75	18 Lakeview Blvd	\N	Mammoth Lakes	CA	93546	US	0.0000000	0.0000000	\N	\N	f	f	\N	\N	2025-06-29 03:06:50	2025-06-29 03:06:50	0101000020E610000000000000000000000000000000000000	Main St & Lakeview Blvd	Lakeshore
21	76	120 Canyon Blvd	\N	Mammoth Lakes	CA	93546	US	0.0000000	0.0000000	\N	\N	f	f	\N	\N	2025-06-29 03:06:50	2025-06-29 03:06:50	0101000020E610000000000000000000000000000000000000	Canyon Blvd & Main St	Village
22	77	739 Old Mammoth Rd	\N	Mammoth Lakes	CA	93546	US	0.0000000	0.0000000	\N	\N	f	f	\N	\N	2025-06-29 03:06:50	2025-06-29 03:06:50	0101000020E610000000000000000000000000000000000000	Old Mammoth Rd & Wood Rd	Village at Mammoth
23	78	3150 Main St	\N	Mammoth Lakes	CA	93546	US	0.0000000	0.0000000	\N	\N	f	f	\N	\N	2025-06-29 03:06:50	2025-06-29 03:06:50	0101000020E610000000000000000000000000000000000000	Main St & Majestic Pines Cir	Old Mammoth
24	79	331 Old Mammoth Rd	\N	Mammoth Lakes	CA	93546	US	0.0000000	0.0000000	\N	\N	f	f	\N	\N	2025-06-29 03:06:50	2025-06-29 03:06:50	0101000020E610000000000000000000000000000000000000	Old Mammoth Rd & Minaret Rd	Village
25	80	150 Old Mammoth Rd	\N	Mammoth Lakes	CA	93546	US	0.0000000	0.0000000	\N	\N	f	f	\N	\N	2025-06-29 03:06:50	2025-06-29 03:06:50	0101000020E610000000000000000000000000000000000000	Old Mammoth Rd & Minaret Rd	Village
26	81	10001 Minaret Rd	\N	Mammoth Lakes	CA	93546	US	0.0000000	0.0000000	\N	\N	f	f	\N	\N	2025-06-29 03:06:50	2025-06-29 03:06:50	0101000020E610000000000000000000000000000000000000	Hwy 203 & Minaret Rd	Mammoth Mountain
27	82	150 Old Mammoth Rd	\N	Mammoth Lakes	CA	93546	US	0.0000000	0.0000000	\N	\N	f	f	\N	\N	2025-06-29 03:06:50	2025-06-29 03:06:50	0101000020E610000000000000000000000000000000000000	Old Mammoth Rd	Village
28	83	339 Old Mammoth Rd	\N	Mammoth Lakes	CA	93546	US	0.0000000	0.0000000	\N	\N	f	f	\N	\N	2025-06-29 03:06:50	2025-06-29 03:06:50	0101000020E610000000000000000000000000000000000000	Old Mammoth Rd & Minaret Rd	Village
29	84	331 Old Mammoth Rd	\N	Mammoth Lakes	CA	93546	US	0.0000000	0.0000000	\N	\N	f	f	\N	\N	2025-06-29 03:06:50	2025-06-29 03:06:50	0101000020E610000000000000000000000000000000000000	Old Mammoth Rd & Peterson Way	Village
30	85	1021 Forest Trail	\N	Mammoth Lakes	CA	93546	US	0.0000000	0.0000000	\N	\N	f	f	\N	\N	2025-06-29 03:06:50	2025-06-29 03:06:50	0101000020E610000000000000000000000000000000000000	Forest Trail & Lake Mary Rd	Mammoth Lakes
31	86	2001 Meridian Blvd	\N	Mammoth Lakes	CA	93546	US	0.0000000	0.0000000	\N	\N	f	f	\N	\N	2025-06-29 03:06:50	2025-06-29 03:06:50	0101000020E610000000000000000000000000000000000000	Meridian Blvd & Lakeshore Blvd	Lakeshore
32	87	451 Old Mammoth Rd	\N	Mammoth Lakes	CA	93546	US	0.0000000	0.0000000	\N	\N	f	f	\N	\N	2025-06-29 03:06:50	2025-06-29 03:06:50	0101000020E610000000000000000000000000000000000000	Old Mammoth Rd & Minaret Rd	Village
33	88	3151 Main St	\N	Mammoth Lakes	CA	93546	US	0.0000000	0.0000000	\N	\N	f	f	\N	\N	2025-06-29 03:06:50	2025-06-29 03:06:50	0101000020E610000000000000000000000000000000000000	Main St & Old Mammoth Rd	Old Mammoth
34	89	10001 Minaret Rd	\N	Mammoth Lakes	CA	93546	US	0.0000000	0.0000000	\N	\N	f	f	\N	\N	2025-06-29 03:06:50	2025-06-29 03:06:50	0101000020E610000000000000000000000000000000000000	Hwy 203 & Minaret Rd	Mammoth Mountain
35	90	2510 Main St	\N	Mammoth Lakes	CA	93546	US	0.0000000	0.0000000	\N	\N	f	f	\N	\N	2025-06-29 03:06:50	2025-06-29 03:06:50	0101000020E610000000000000000000000000000000000000	Main St & Old Mammoth Rd	Downtown
36	91	100 College Pkwy	\N	Mammoth Lakes	CA	93546	US	0.0000000	0.0000000	\N	\N	f	f	\N	\N	2025-06-29 03:06:50	2025-06-29 03:06:50	0101000020E610000000000000000000000000000000000000	College Pkwy & Old Mammoth Rd	Community Center
37	92	10601 Minaret Rd	\N	Mammoth Lakes	CA	93546	US	0.0000000	0.0000000	\N	\N	f	f	\N	\N	2025-06-29 03:06:50	2025-06-29 03:06:50	0101000020E610000000000000000000000000000000000000	Minaret Rd & Manzanita Rd	Recreation
38	93	Sierra Star Dr	\N	Mammoth Lakes	CA	93546	US	0.0000000	0.0000000	\N	\N	f	f	\N	\N	2025-06-29 03:06:50	2025-06-29 03:06:50	0101000020E610000000000000000000000000000000000000	Sierra Star Dr & Minaret Rd	Sierra Star
39	94	100 College Pkwy	\N	Mammoth Lakes	CA	93546	US	0.0000000	0.0000000	\N	\N	f	f	\N	\N	2025-06-29 03:06:50	2025-06-29 03:06:50	0101000020E610000000000000000000000000000000000000	College Pkwy & Old Mammoth Rd	Community Center
41	96	10001 Minaret Rd	\N	Mammoth Lakes	CA	93546	US	0.0000000	0.0000000	\N	\N	f	f	\N	\N	2025-06-29 03:06:50	2025-06-29 03:06:50	0101000020E610000000000000000000000000000000000000	Hwy 203 & Minaret Rd	Mammoth Mountain
42	97	10001 Minaret Rd	\N	Mammoth Lakes	CA	93546	US	0.0000000	0.0000000	\N	\N	f	f	\N	\N	2025-06-29 03:06:50	2025-06-29 03:06:50	0101000020E610000000000000000000000000000000000000	Hwy 203 & Minaret Rd	Mammoth Mountain
40	95	18 Lakeview Blvd	\N	Mammoth Lakes	CA	93546	US	37.6490700	-118.8936900	\N	\N	f	f	\N	\N	2025-06-29 03:06:50	2025-06-30 06:13:54	0101000020E6100000C9B08A3732B95DC04968CBB914D34240	Main St & Lakeview Blvd	Lakeshore
\.


--
-- Data for Name: migrations; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.migrations (id, migration, batch) FROM stdin;
22	0001_01_01_000000_create_users_table	1
23	0001_01_01_000001_create_cache_table	1
24	0001_01_01_000002_create_jobs_table	1
25	2025_06_01_000419_create_categories_table	1
26	2025_06_01_000421_create_regions_table	1
27	2025_06_01_000422_create_directory_entries_table	1
28	2025_06_01_000423_create_locations_table	1
29	2025_06_01_000424_create_lists_table	1
30	2025_06_01_000426_create_list_items_table	1
31	2025_06_01_000427_create_comments_table	1
32	2025_06_01_000428_create_list_media_table	1
33	2025_06_01_000429_create_claims_table	1
34	2025_06_01_000431_add_user_fields	1
35	2025_06_02_023958_update_list_items_table_for_multiple_types	2
36	2025_06_02_023958_update_lists_table_add_fields	2
37	2025_06_02_063823_create_user_list_favorites_table	3
38	2025_06_27_071850_add_custom_url_to_users_table	4
39	2025_06_28_001929_add_description_to_categories_table	5
40	2025_06_28_052039_update_lists_visibility_levels	6
41	2025_06_28_230526_add_image_fields_to_directory_entries_table	7
42	2025_06_29_011202_add_expanded_fields_to_directory_entries_table	8
\.


--
-- Data for Name: password_reset_tokens; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.password_reset_tokens (email, token, created_at) FROM stdin;
\.


--
-- Data for Name: regions; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.regions (id, name, type, parent_id, created_at, updated_at) FROM stdin;
1	California	state	\N	2025-06-01 20:18:34	2025-06-01 20:18:34
2	New York	state	\N	2025-06-01 20:18:34	2025-06-01 20:18:34
3	Texas	state	\N	2025-06-01 20:18:34	2025-06-01 20:18:34
4	Florida	state	\N	2025-06-01 20:18:34	2025-06-01 20:18:34
\.


--
-- Data for Name: sessions; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.sessions (id, user_id, ip_address, user_agent, payload, last_activity) FROM stdin;
6ZrVEDpwWnXZc82SxMJuzKSQwJwvLHJ1KtKgK2CV	2	::1	Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36	YTo1OntzOjY6Il90b2tlbiI7czo0MDoiZnVtNWxhMDhYSGNFYVc5T0lBQTByM0lzdGtqa2xmODMzRldBYVI4ZSI7czozOiJ1cmwiO2E6MDp7fXM6OToiX3ByZXZpb3VzIjthOjE6e3M6MzoidXJsIjtzOjcwOiJodHRwOi8vbG9jYWxob3N0OjgwMDAvLndlbGwta25vd24vYXBwc3BlY2lmaWMvY29tLmNocm9tZS5kZXZ0b29scy5qc29uIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6Mjt9	1751266888
J7SZCXHYihxnhrFFmLbFqZj8AlvL9u7VIWwG7g29	\N	::1	Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36	YToyOntzOjY6Il90b2tlbiI7czo0MDoiS3FHTTNzd0NsWWRHVGl6ZzQ2c1JCeHZhUUZBalJqcWlnUHlkRzR2bCI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1751299400
\.


--
-- Data for Name: spatial_ref_sys; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.spatial_ref_sys (srid, auth_name, auth_srid, srtext, proj4text) FROM stdin;
\.


--
-- Data for Name: user_list_favorites; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.user_list_favorites (id, user_id, list_id, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.users (id, name, email, email_verified_at, password, remember_token, created_at, updated_at, username, role, bio, avatar, cover_image, social_links, preferences, permissions, is_public, last_active_at, custom_url) FROM stdin;
1	Admin User	admin@example.com	\N	$2y$12$2brCLHF76fUpD6cacN4q8uLdTuIGkjAn6qo2rlCW48kR5Hzf3rm66	\N	2025-06-01 00:12:11	2025-06-27 05:55:02	adminuser	admin	\N	\N	\N	\N	\N	\N	t	\N	\N
3	Test Manager	manager@example.com	\N	$2y$12$I557C68Vv6ksBqCOaymbnubDiASW0rpMRGYJ4cXhRnV5WmNBpDi36	\N	2025-06-01 06:19:41	2025-06-27 05:55:02	testmanager	manager	\N	\N	\N	\N	\N	\N	t	\N	\N
5	Test User	user@example.com	\N	$2y$12$mhP6R.mGT3U1JnANaVSZiunZWptf4/9TcD7b84I40RkwRMYV9MOtS	\N	2025-06-01 06:19:41	2025-06-27 05:55:02	testuser	user	\N	\N	\N	\N	\N	\N	t	\N	\N
2	Eric Larson	eric@layercakemarketing.com	\N	$2y$12$Wu6hzW/sTEotWTu9YDVtTuFp6w8TpZqPwuCb/2PFB.3dmBZsI/cou	\N	2025-06-01 05:41:54	2025-06-27 07:32:48	ericlarson	admin	\N	\N	\N	\N	\N	\N	t	\N	layercake
4	Test Editor	editor@example.com	\N	$2y$12$RQ.4BuNIug3.QjdQ78d9heR3FrgjTgxAUGm0grY/BMtIxaQzVpzLK	\N	2025-06-01 06:19:41	2025-06-29 04:28:42	testeditor	editor	\N	\N	\N	\N	\N	\N	t	\N	\N
\.


--
-- Name: categories_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.categories_id_seq', 27, true);


--
-- Name: claims_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.claims_id_seq', 1, false);


--
-- Name: comments_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.comments_id_seq', 1, false);


--
-- Name: directory_entries_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.directory_entries_id_seq', 98, true);


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.failed_jobs_id_seq', 1, false);


--
-- Name: jobs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.jobs_id_seq', 1, false);


--
-- Name: list_items_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.list_items_id_seq', 37, true);


--
-- Name: list_media_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.list_media_id_seq', 1, false);


--
-- Name: lists_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.lists_id_seq', 8, true);


--
-- Name: locations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.locations_id_seq', 43, true);


--
-- Name: migrations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.migrations_id_seq', 42, true);


--
-- Name: regions_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.regions_id_seq', 4, true);


--
-- Name: user_list_favorites_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.user_list_favorites_id_seq', 1, false);


--
-- Name: users_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.users_id_seq', 6, true);


--
-- Name: cache_locks cache_locks_pkey; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.cache_locks
    ADD CONSTRAINT cache_locks_pkey PRIMARY KEY (key);


--
-- Name: cache cache_pkey; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.cache
    ADD CONSTRAINT cache_pkey PRIMARY KEY (key);


--
-- Name: categories categories_pkey; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.categories
    ADD CONSTRAINT categories_pkey PRIMARY KEY (id);


--
-- Name: categories categories_slug_unique; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.categories
    ADD CONSTRAINT categories_slug_unique UNIQUE (slug);


--
-- Name: claims claims_pkey; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.claims
    ADD CONSTRAINT claims_pkey PRIMARY KEY (id);


--
-- Name: comments comments_pkey; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.comments
    ADD CONSTRAINT comments_pkey PRIMARY KEY (id);


--
-- Name: directory_entries directory_entries_pkey; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.directory_entries
    ADD CONSTRAINT directory_entries_pkey PRIMARY KEY (id);


--
-- Name: directory_entries directory_entries_slug_unique; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.directory_entries
    ADD CONSTRAINT directory_entries_slug_unique UNIQUE (slug);


--
-- Name: failed_jobs failed_jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_uuid_unique UNIQUE (uuid);


--
-- Name: job_batches job_batches_pkey; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.job_batches
    ADD CONSTRAINT job_batches_pkey PRIMARY KEY (id);


--
-- Name: jobs jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.jobs
    ADD CONSTRAINT jobs_pkey PRIMARY KEY (id);


--
-- Name: list_items list_items_list_id_directory_entry_id_unique; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.list_items
    ADD CONSTRAINT list_items_list_id_directory_entry_id_unique UNIQUE (list_id, directory_entry_id);


--
-- Name: list_items list_items_pkey; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.list_items
    ADD CONSTRAINT list_items_pkey PRIMARY KEY (id);


--
-- Name: list_media list_media_pkey; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.list_media
    ADD CONSTRAINT list_media_pkey PRIMARY KEY (id);


--
-- Name: lists lists_pkey; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.lists
    ADD CONSTRAINT lists_pkey PRIMARY KEY (id);


--
-- Name: lists lists_slug_unique; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.lists
    ADD CONSTRAINT lists_slug_unique UNIQUE (slug);


--
-- Name: locations locations_directory_entry_id_unique; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.locations
    ADD CONSTRAINT locations_directory_entry_id_unique UNIQUE (directory_entry_id);


--
-- Name: locations locations_pkey; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.locations
    ADD CONSTRAINT locations_pkey PRIMARY KEY (id);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- Name: password_reset_tokens password_reset_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.password_reset_tokens
    ADD CONSTRAINT password_reset_tokens_pkey PRIMARY KEY (email);


--
-- Name: regions regions_pkey; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.regions
    ADD CONSTRAINT regions_pkey PRIMARY KEY (id);


--
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- Name: user_list_favorites user_list_favorites_pkey; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.user_list_favorites
    ADD CONSTRAINT user_list_favorites_pkey PRIMARY KEY (id);


--
-- Name: user_list_favorites user_list_favorites_user_id_list_id_unique; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.user_list_favorites
    ADD CONSTRAINT user_list_favorites_user_id_list_id_unique UNIQUE (user_id, list_id);


--
-- Name: users users_custom_url_unique; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_custom_url_unique UNIQUE (custom_url);


--
-- Name: users users_email_unique; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_unique UNIQUE (email);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: users users_username_unique; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_username_unique UNIQUE (username);


--
-- Name: categories_parent_id_order_index_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX categories_parent_id_order_index_index ON public.categories USING btree (parent_id, order_index);


--
-- Name: directory_entries_status_is_featured_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX directory_entries_status_is_featured_index ON public.directory_entries USING btree (status, is_featured);


--
-- Name: directory_entries_type_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX directory_entries_type_index ON public.directory_entries USING btree (type);


--
-- Name: jobs_queue_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX jobs_queue_index ON public.jobs USING btree (queue);


--
-- Name: list_items_list_id_order_index_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX list_items_list_id_order_index_index ON public.list_items USING btree (list_id, order_index);


--
-- Name: list_media_list_id_order_index_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX list_media_list_id_order_index_index ON public.list_media USING btree (list_id, order_index);


--
-- Name: lists_user_id_is_public_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX lists_user_id_is_public_index ON public.lists USING btree (user_id, is_public);


--
-- Name: locations_city_state_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX locations_city_state_index ON public.locations USING btree (city, state);


--
-- Name: locations_geom_gist; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX locations_geom_gist ON public.locations USING gist (geom);


--
-- Name: sessions_last_activity_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX sessions_last_activity_index ON public.sessions USING btree (last_activity);


--
-- Name: sessions_user_id_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX sessions_user_id_index ON public.sessions USING btree (user_id);


--
-- Name: users_custom_url_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX users_custom_url_index ON public.users USING btree (custom_url);


--
-- Name: users_role_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX users_role_index ON public.users USING btree (role);


--
-- Name: users_username_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX users_username_index ON public.users USING btree (username);


--
-- Name: locations update_location_geom_trigger; Type: TRIGGER; Schema: public; Owner: ericslarson
--

CREATE TRIGGER update_location_geom_trigger BEFORE INSERT OR UPDATE ON public.locations FOR EACH ROW WHEN (((new.latitude IS NOT NULL) AND (new.longitude IS NOT NULL))) EXECUTE FUNCTION public.update_location_geom();


--
-- Name: categories categories_parent_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.categories
    ADD CONSTRAINT categories_parent_id_foreign FOREIGN KEY (parent_id) REFERENCES public.categories(id) ON DELETE CASCADE;


--
-- Name: directory_entries directory_entries_category_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.directory_entries
    ADD CONSTRAINT directory_entries_category_id_foreign FOREIGN KEY (category_id) REFERENCES public.categories(id) ON DELETE SET NULL;


--
-- Name: directory_entries directory_entries_created_by_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.directory_entries
    ADD CONSTRAINT directory_entries_created_by_user_id_foreign FOREIGN KEY (created_by_user_id) REFERENCES public.users(id);


--
-- Name: directory_entries directory_entries_owner_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.directory_entries
    ADD CONSTRAINT directory_entries_owner_user_id_foreign FOREIGN KEY (owner_user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: directory_entries directory_entries_region_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.directory_entries
    ADD CONSTRAINT directory_entries_region_id_foreign FOREIGN KEY (region_id) REFERENCES public.regions(id) ON DELETE SET NULL;


--
-- Name: directory_entries directory_entries_updated_by_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.directory_entries
    ADD CONSTRAINT directory_entries_updated_by_user_id_foreign FOREIGN KEY (updated_by_user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: list_items list_items_directory_entry_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.list_items
    ADD CONSTRAINT list_items_directory_entry_id_foreign FOREIGN KEY (directory_entry_id) REFERENCES public.directory_entries(id) ON DELETE CASCADE;


--
-- Name: list_items list_items_list_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.list_items
    ADD CONSTRAINT list_items_list_id_foreign FOREIGN KEY (list_id) REFERENCES public.lists(id) ON DELETE CASCADE;


--
-- Name: list_media list_media_list_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.list_media
    ADD CONSTRAINT list_media_list_id_foreign FOREIGN KEY (list_id) REFERENCES public.lists(id) ON DELETE CASCADE;


--
-- Name: lists lists_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.lists
    ADD CONSTRAINT lists_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: locations locations_directory_entry_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.locations
    ADD CONSTRAINT locations_directory_entry_id_foreign FOREIGN KEY (directory_entry_id) REFERENCES public.directory_entries(id) ON DELETE CASCADE;


--
-- Name: regions regions_parent_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.regions
    ADD CONSTRAINT regions_parent_id_foreign FOREIGN KEY (parent_id) REFERENCES public.regions(id) ON DELETE CASCADE;


--
-- Name: user_list_favorites user_list_favorites_list_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.user_list_favorites
    ADD CONSTRAINT user_list_favorites_list_id_foreign FOREIGN KEY (list_id) REFERENCES public.lists(id) ON DELETE CASCADE;


--
-- Name: user_list_favorites user_list_favorites_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.user_list_favorites
    ADD CONSTRAINT user_list_favorites_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- PostgreSQL database dump complete
--

