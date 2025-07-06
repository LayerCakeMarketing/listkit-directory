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
-- Name: EXTENSION postgis; Type: COMMENT; Schema: -; Owner: -
--

COMMENT ON EXTENSION postgis IS 'PostGIS geometry and geography spatial types and functions';


--
-- Name: update_location_geom(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.update_location_geom() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
                BEGIN
                    NEW.geom = ST_SetSRID(ST_MakePoint(NEW.longitude, NEW.latitude), 4326);
                    RETURN NEW;
                END;
                $$;


SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: cache; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration integer NOT NULL
);


--
-- Name: cache_locks; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration integer NOT NULL
);


--
-- Name: categories; Type: TABLE; Schema: public; Owner: -
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


--
-- Name: categories_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.categories_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: categories_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.categories_id_seq OWNED BY public.categories.id;


--
-- Name: claims; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.claims (
    id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: claims_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.claims_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: claims_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.claims_id_seq OWNED BY public.claims.id;


--
-- Name: comments; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.comments (
    id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: comments_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.comments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: comments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.comments_id_seq OWNED BY public.comments.id;


--
-- Name: directory_entries; Type: TABLE; Schema: public; Owner: -
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


--
-- Name: directory_entries_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.directory_entries_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: directory_entries_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.directory_entries_id_seq OWNED BY public.directory_entries.id;


--
-- Name: failed_jobs; Type: TABLE; Schema: public; Owner: -
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


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.failed_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.failed_jobs_id_seq OWNED BY public.failed_jobs.id;


--
-- Name: job_batches; Type: TABLE; Schema: public; Owner: -
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


--
-- Name: jobs; Type: TABLE; Schema: public; Owner: -
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


--
-- Name: jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.jobs_id_seq OWNED BY public.jobs.id;


--
-- Name: list_items; Type: TABLE; Schema: public; Owner: -
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


--
-- Name: list_items_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.list_items_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: list_items_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.list_items_id_seq OWNED BY public.list_items.id;


--
-- Name: list_media; Type: TABLE; Schema: public; Owner: -
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


--
-- Name: list_media_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.list_media_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: list_media_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.list_media_id_seq OWNED BY public.list_media.id;


--
-- Name: lists; Type: TABLE; Schema: public; Owner: -
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


--
-- Name: lists_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.lists_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: lists_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.lists_id_seq OWNED BY public.lists.id;


--
-- Name: locations; Type: TABLE; Schema: public; Owner: -
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


--
-- Name: locations_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.locations_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: locations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.locations_id_seq OWNED BY public.locations.id;


--
-- Name: migrations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


--
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.migrations_id_seq OWNED BY public.migrations.id;


--
-- Name: password_reset_tokens; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.password_reset_tokens (
    email character varying(255) NOT NULL,
    token character varying(255) NOT NULL,
    created_at timestamp(0) without time zone
);


--
-- Name: regions; Type: TABLE; Schema: public; Owner: -
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


--
-- Name: regions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.regions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: regions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.regions_id_seq OWNED BY public.regions.id;


--
-- Name: sessions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.sessions (
    id character varying(255) NOT NULL,
    user_id bigint,
    ip_address character varying(45),
    user_agent text,
    payload text NOT NULL,
    last_activity integer NOT NULL
);


--
-- Name: user_list_favorites; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.user_list_favorites (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    list_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: user_list_favorites_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.user_list_favorites_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: user_list_favorites_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.user_list_favorites_id_seq OWNED BY public.user_list_favorites.id;


--
-- Name: users; Type: TABLE; Schema: public; Owner: -
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


--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- Name: categories id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.categories ALTER COLUMN id SET DEFAULT nextval('public.categories_id_seq'::regclass);


--
-- Name: claims id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.claims ALTER COLUMN id SET DEFAULT nextval('public.claims_id_seq'::regclass);


--
-- Name: comments id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.comments ALTER COLUMN id SET DEFAULT nextval('public.comments_id_seq'::regclass);


--
-- Name: directory_entries id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.directory_entries ALTER COLUMN id SET DEFAULT nextval('public.directory_entries_id_seq'::regclass);


--
-- Name: failed_jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs ALTER COLUMN id SET DEFAULT nextval('public.failed_jobs_id_seq'::regclass);


--
-- Name: jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jobs ALTER COLUMN id SET DEFAULT nextval('public.jobs_id_seq'::regclass);


--
-- Name: list_items id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.list_items ALTER COLUMN id SET DEFAULT nextval('public.list_items_id_seq'::regclass);


--
-- Name: list_media id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.list_media ALTER COLUMN id SET DEFAULT nextval('public.list_media_id_seq'::regclass);


--
-- Name: lists id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.lists ALTER COLUMN id SET DEFAULT nextval('public.lists_id_seq'::regclass);


--
-- Name: locations id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.locations ALTER COLUMN id SET DEFAULT nextval('public.locations_id_seq'::regclass);


--
-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);


--
-- Name: regions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.regions ALTER COLUMN id SET DEFAULT nextval('public.regions_id_seq'::regclass);


--
-- Name: user_list_favorites id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_list_favorites ALTER COLUMN id SET DEFAULT nextval('public.user_list_favorites_id_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- Name: cache_locks cache_locks_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache_locks
    ADD CONSTRAINT cache_locks_pkey PRIMARY KEY (key);


--
-- Name: cache cache_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache
    ADD CONSTRAINT cache_pkey PRIMARY KEY (key);


--
-- Name: categories categories_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.categories
    ADD CONSTRAINT categories_pkey PRIMARY KEY (id);


--
-- Name: categories categories_slug_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.categories
    ADD CONSTRAINT categories_slug_unique UNIQUE (slug);


--
-- Name: claims claims_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.claims
    ADD CONSTRAINT claims_pkey PRIMARY KEY (id);


--
-- Name: comments comments_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.comments
    ADD CONSTRAINT comments_pkey PRIMARY KEY (id);


--
-- Name: directory_entries directory_entries_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.directory_entries
    ADD CONSTRAINT directory_entries_pkey PRIMARY KEY (id);


--
-- Name: directory_entries directory_entries_slug_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.directory_entries
    ADD CONSTRAINT directory_entries_slug_unique UNIQUE (slug);


--
-- Name: failed_jobs failed_jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_uuid_unique UNIQUE (uuid);


--
-- Name: job_batches job_batches_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.job_batches
    ADD CONSTRAINT job_batches_pkey PRIMARY KEY (id);


--
-- Name: jobs jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jobs
    ADD CONSTRAINT jobs_pkey PRIMARY KEY (id);


--
-- Name: list_items list_items_list_id_directory_entry_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.list_items
    ADD CONSTRAINT list_items_list_id_directory_entry_id_unique UNIQUE (list_id, directory_entry_id);


--
-- Name: list_items list_items_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.list_items
    ADD CONSTRAINT list_items_pkey PRIMARY KEY (id);


--
-- Name: list_media list_media_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.list_media
    ADD CONSTRAINT list_media_pkey PRIMARY KEY (id);


--
-- Name: lists lists_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.lists
    ADD CONSTRAINT lists_pkey PRIMARY KEY (id);


--
-- Name: lists lists_slug_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.lists
    ADD CONSTRAINT lists_slug_unique UNIQUE (slug);


--
-- Name: locations locations_directory_entry_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.locations
    ADD CONSTRAINT locations_directory_entry_id_unique UNIQUE (directory_entry_id);


--
-- Name: locations locations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.locations
    ADD CONSTRAINT locations_pkey PRIMARY KEY (id);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- Name: password_reset_tokens password_reset_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.password_reset_tokens
    ADD CONSTRAINT password_reset_tokens_pkey PRIMARY KEY (email);


--
-- Name: regions regions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.regions
    ADD CONSTRAINT regions_pkey PRIMARY KEY (id);


--
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- Name: user_list_favorites user_list_favorites_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_list_favorites
    ADD CONSTRAINT user_list_favorites_pkey PRIMARY KEY (id);


--
-- Name: user_list_favorites user_list_favorites_user_id_list_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_list_favorites
    ADD CONSTRAINT user_list_favorites_user_id_list_id_unique UNIQUE (user_id, list_id);


--
-- Name: users users_custom_url_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_custom_url_unique UNIQUE (custom_url);


--
-- Name: users users_email_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_unique UNIQUE (email);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: users users_username_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_username_unique UNIQUE (username);


--
-- Name: categories_parent_id_order_index_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX categories_parent_id_order_index_index ON public.categories USING btree (parent_id, order_index);


--
-- Name: directory_entries_status_is_featured_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX directory_entries_status_is_featured_index ON public.directory_entries USING btree (status, is_featured);


--
-- Name: directory_entries_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX directory_entries_type_index ON public.directory_entries USING btree (type);


--
-- Name: jobs_queue_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX jobs_queue_index ON public.jobs USING btree (queue);


--
-- Name: list_items_list_id_order_index_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX list_items_list_id_order_index_index ON public.list_items USING btree (list_id, order_index);


--
-- Name: list_media_list_id_order_index_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX list_media_list_id_order_index_index ON public.list_media USING btree (list_id, order_index);


--
-- Name: lists_user_id_is_public_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX lists_user_id_is_public_index ON public.lists USING btree (user_id, is_public);


--
-- Name: locations_city_state_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX locations_city_state_index ON public.locations USING btree (city, state);


--
-- Name: locations_geom_gist; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX locations_geom_gist ON public.locations USING gist (geom);


--
-- Name: sessions_last_activity_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_last_activity_index ON public.sessions USING btree (last_activity);


--
-- Name: sessions_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_user_id_index ON public.sessions USING btree (user_id);


--
-- Name: users_custom_url_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX users_custom_url_index ON public.users USING btree (custom_url);


--
-- Name: users_role_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX users_role_index ON public.users USING btree (role);


--
-- Name: users_username_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX users_username_index ON public.users USING btree (username);


--
-- Name: locations update_location_geom_trigger; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_location_geom_trigger BEFORE INSERT OR UPDATE ON public.locations FOR EACH ROW WHEN (((new.latitude IS NOT NULL) AND (new.longitude IS NOT NULL))) EXECUTE FUNCTION public.update_location_geom();


--
-- Name: categories categories_parent_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.categories
    ADD CONSTRAINT categories_parent_id_foreign FOREIGN KEY (parent_id) REFERENCES public.categories(id) ON DELETE CASCADE;


--
-- Name: directory_entries directory_entries_category_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.directory_entries
    ADD CONSTRAINT directory_entries_category_id_foreign FOREIGN KEY (category_id) REFERENCES public.categories(id) ON DELETE SET NULL;


--
-- Name: directory_entries directory_entries_created_by_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.directory_entries
    ADD CONSTRAINT directory_entries_created_by_user_id_foreign FOREIGN KEY (created_by_user_id) REFERENCES public.users(id);


--
-- Name: directory_entries directory_entries_owner_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.directory_entries
    ADD CONSTRAINT directory_entries_owner_user_id_foreign FOREIGN KEY (owner_user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: directory_entries directory_entries_region_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.directory_entries
    ADD CONSTRAINT directory_entries_region_id_foreign FOREIGN KEY (region_id) REFERENCES public.regions(id) ON DELETE SET NULL;


--
-- Name: directory_entries directory_entries_updated_by_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.directory_entries
    ADD CONSTRAINT directory_entries_updated_by_user_id_foreign FOREIGN KEY (updated_by_user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: list_items list_items_directory_entry_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.list_items
    ADD CONSTRAINT list_items_directory_entry_id_foreign FOREIGN KEY (directory_entry_id) REFERENCES public.directory_entries(id) ON DELETE CASCADE;


--
-- Name: list_items list_items_list_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.list_items
    ADD CONSTRAINT list_items_list_id_foreign FOREIGN KEY (list_id) REFERENCES public.lists(id) ON DELETE CASCADE;


--
-- Name: list_media list_media_list_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.list_media
    ADD CONSTRAINT list_media_list_id_foreign FOREIGN KEY (list_id) REFERENCES public.lists(id) ON DELETE CASCADE;


--
-- Name: lists lists_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.lists
    ADD CONSTRAINT lists_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: locations locations_directory_entry_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.locations
    ADD CONSTRAINT locations_directory_entry_id_foreign FOREIGN KEY (directory_entry_id) REFERENCES public.directory_entries(id) ON DELETE CASCADE;


--
-- Name: regions regions_parent_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.regions
    ADD CONSTRAINT regions_parent_id_foreign FOREIGN KEY (parent_id) REFERENCES public.regions(id) ON DELETE CASCADE;


--
-- Name: user_list_favorites user_list_favorites_list_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_list_favorites
    ADD CONSTRAINT user_list_favorites_list_id_foreign FOREIGN KEY (list_id) REFERENCES public.lists(id) ON DELETE CASCADE;


--
-- Name: user_list_favorites user_list_favorites_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_list_favorites
    ADD CONSTRAINT user_list_favorites_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- PostgreSQL database dump complete
--

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
-- Data for Name: migrations; Type: TABLE DATA; Schema: public; Owner: -
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
43	2025_06_29_011202_add_expanded_fields_to_directory_entries_table	8
\.


--
-- Name: migrations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.migrations_id_seq', 43, true);


--
-- PostgreSQL database dump complete
--

