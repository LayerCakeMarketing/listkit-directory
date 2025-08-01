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
-- Name: app_notifications; Type: TABLE; Schema: public; Owner: ericslarson
--

CREATE TABLE public.app_notifications (
    id bigint NOT NULL,
    type character varying(255) NOT NULL,
    title character varying(255) NOT NULL,
    message text NOT NULL,
    sender_id bigint,
    recipient_id bigint NOT NULL,
    related_type character varying(255),
    related_id bigint,
    action_url character varying(255),
    read_at timestamp(0) without time zone,
    priority character varying(255) DEFAULT 'normal'::character varying NOT NULL,
    metadata json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT app_notifications_priority_check CHECK (((priority)::text = ANY ((ARRAY['low'::character varying, 'normal'::character varying, 'high'::character varying])::text[]))),
    CONSTRAINT app_notifications_type_check CHECK (((type)::text = ANY ((ARRAY['system'::character varying, 'claim'::character varying, 'announcement'::character varying, 'follow'::character varying, 'list'::character varying, 'channel'::character varying])::text[])))
);


ALTER TABLE public.app_notifications OWNER TO ericslarson;

--
-- Name: app_notifications_id_seq; Type: SEQUENCE; Schema: public; Owner: ericslarson
--

CREATE SEQUENCE public.app_notifications_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.app_notifications_id_seq OWNER TO ericslarson;

--
-- Name: app_notifications_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: ericslarson
--

ALTER SEQUENCE public.app_notifications_id_seq OWNED BY public.app_notifications.id;


--
-- Name: claim_documents; Type: TABLE; Schema: public; Owner: ericslarson
--

CREATE TABLE public.claim_documents (
    id bigint NOT NULL,
    claim_id bigint NOT NULL,
    document_type character varying(255) NOT NULL,
    file_path character varying(255) NOT NULL,
    file_name character varying(255) NOT NULL,
    file_size character varying(255) NOT NULL,
    mime_type character varying(255) NOT NULL,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    notes text,
    verified_at timestamp(0) without time zone,
    verified_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT claim_documents_status_check CHECK (((status)::text = ANY ((ARRAY['pending'::character varying, 'verified'::character varying, 'rejected'::character varying])::text[])))
);


ALTER TABLE public.claim_documents OWNER TO ericslarson;

--
-- Name: claim_documents_id_seq; Type: SEQUENCE; Schema: public; Owner: ericslarson
--

CREATE SEQUENCE public.claim_documents_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.claim_documents_id_seq OWNER TO ericslarson;

--
-- Name: claim_documents_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: ericslarson
--

ALTER SEQUENCE public.claim_documents_id_seq OWNED BY public.claim_documents.id;


--
-- Name: claims; Type: TABLE; Schema: public; Owner: ericslarson
--

CREATE TABLE public.claims (
    id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    place_id bigint NOT NULL,
    tier character varying(255) DEFAULT 'free'::character varying NOT NULL,
    stripe_payment_intent_id character varying(255),
    stripe_payment_status character varying(255),
    payment_amount numeric(10,2),
    payment_completed_at timestamp(0) without time zone,
    stripe_subscription_id character varying(255),
    subscription_starts_at timestamp(0) without time zone,
    verification_fee_paid boolean DEFAULT false NOT NULL,
    fee_kept boolean DEFAULT false NOT NULL,
    fee_payment_intent_id character varying(255),
    fee_refunded_at timestamp(0) without time zone,
    fee_refund_id character varying(255),
    verification_fee_amount numeric(10,2) DEFAULT 5.99 NOT NULL,
    CONSTRAINT claims_tier_check CHECK (((tier)::text = ANY ((ARRAY['free'::character varying, 'tier1'::character varying, 'tier2'::character varying])::text[])))
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
    updated_at timestamp(0) without time zone,
    user_id bigint NOT NULL,
    commentable_type character varying(255) NOT NULL,
    commentable_id bigint NOT NULL,
    content text NOT NULL,
    parent_id bigint,
    replies_count integer DEFAULT 0 NOT NULL,
    likes_count integer DEFAULT 0 NOT NULL,
    mentions json,
    deleted_at timestamp(0) without time zone
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
-- Name: likes; Type: TABLE; Schema: public; Owner: ericslarson
--

CREATE TABLE public.likes (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    likeable_type character varying(255) NOT NULL,
    likeable_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.likes OWNER TO ericslarson;

--
-- Name: likes_id_seq; Type: SEQUENCE; Schema: public; Owner: ericslarson
--

CREATE SEQUENCE public.likes_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.likes_id_seq OWNER TO ericslarson;

--
-- Name: likes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: ericslarson
--

ALTER SEQUENCE public.likes_id_seq OWNED BY public.likes.id;


--
-- Name: list_chain_items; Type: TABLE; Schema: public; Owner: ericslarson
--

CREATE TABLE public.list_chain_items (
    id bigint NOT NULL,
    list_chain_id bigint NOT NULL,
    list_id bigint NOT NULL,
    order_index integer DEFAULT 0 NOT NULL,
    label character varying(255),
    description text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.list_chain_items OWNER TO ericslarson;

--
-- Name: list_chain_items_id_seq; Type: SEQUENCE; Schema: public; Owner: ericslarson
--

CREATE SEQUENCE public.list_chain_items_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.list_chain_items_id_seq OWNER TO ericslarson;

--
-- Name: list_chain_items_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: ericslarson
--

ALTER SEQUENCE public.list_chain_items_id_seq OWNED BY public.list_chain_items.id;


--
-- Name: list_chains; Type: TABLE; Schema: public; Owner: ericslarson
--

CREATE TABLE public.list_chains (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    slug character varying(255) NOT NULL,
    description text,
    cover_image character varying(255),
    cover_cloudflare_id character varying(255),
    owner_type character varying(255) NOT NULL,
    owner_id bigint NOT NULL,
    visibility character varying(255) DEFAULT 'private'::character varying NOT NULL,
    status character varying(255) DEFAULT 'draft'::character varying NOT NULL,
    metadata json,
    views_count integer DEFAULT 0 NOT NULL,
    lists_count integer DEFAULT 0 NOT NULL,
    published_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT list_chains_status_check CHECK (((status)::text = ANY ((ARRAY['draft'::character varying, 'published'::character varying, 'archived'::character varying])::text[]))),
    CONSTRAINT list_chains_visibility_check CHECK (((visibility)::text = ANY ((ARRAY['public'::character varying, 'private'::character varying, 'unlisted'::character varying])::text[])))
);


ALTER TABLE public.list_chains OWNER TO ericslarson;

--
-- Name: list_chains_id_seq; Type: SEQUENCE; Schema: public; Owner: ericslarson
--

CREATE SEQUENCE public.list_chains_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.list_chains_id_seq OWNER TO ericslarson;

--
-- Name: list_chains_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: ericslarson
--

ALTER SEQUENCE public.list_chains_id_seq OWNED BY public.list_chains.id;


--
-- Name: reposts; Type: TABLE; Schema: public; Owner: ericslarson
--

CREATE TABLE public.reposts (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    repostable_type character varying(255) NOT NULL,
    repostable_id bigint NOT NULL,
    comment text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.reposts OWNER TO ericslarson;

--
-- Name: reposts_id_seq; Type: SEQUENCE; Schema: public; Owner: ericslarson
--

CREATE SEQUENCE public.reposts_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.reposts_id_seq OWNER TO ericslarson;

--
-- Name: reposts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: ericslarson
--

ALTER SEQUENCE public.reposts_id_seq OWNED BY public.reposts.id;


--
-- Name: verification_codes; Type: TABLE; Schema: public; Owner: ericslarson
--

CREATE TABLE public.verification_codes (
    id bigint NOT NULL,
    verifiable_type character varying(255) NOT NULL,
    verifiable_id bigint NOT NULL,
    type character varying(255) NOT NULL,
    code character varying(6) NOT NULL,
    destination character varying(255) NOT NULL,
    attempts integer DEFAULT 0 NOT NULL,
    expires_at timestamp(0) without time zone NOT NULL,
    verified_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.verification_codes OWNER TO ericslarson;

--
-- Name: verification_codes_id_seq; Type: SEQUENCE; Schema: public; Owner: ericslarson
--

CREATE SEQUENCE public.verification_codes_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.verification_codes_id_seq OWNER TO ericslarson;

--
-- Name: verification_codes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: ericslarson
--

ALTER SEQUENCE public.verification_codes_id_seq OWNED BY public.verification_codes.id;


--
-- Name: app_notifications id; Type: DEFAULT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.app_notifications ALTER COLUMN id SET DEFAULT nextval('public.app_notifications_id_seq'::regclass);


--
-- Name: claim_documents id; Type: DEFAULT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.claim_documents ALTER COLUMN id SET DEFAULT nextval('public.claim_documents_id_seq'::regclass);


--
-- Name: claims id; Type: DEFAULT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.claims ALTER COLUMN id SET DEFAULT nextval('public.claims_id_seq'::regclass);


--
-- Name: comments id; Type: DEFAULT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.comments ALTER COLUMN id SET DEFAULT nextval('public.comments_id_seq'::regclass);


--
-- Name: likes id; Type: DEFAULT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.likes ALTER COLUMN id SET DEFAULT nextval('public.likes_id_seq'::regclass);


--
-- Name: list_chain_items id; Type: DEFAULT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.list_chain_items ALTER COLUMN id SET DEFAULT nextval('public.list_chain_items_id_seq'::regclass);


--
-- Name: list_chains id; Type: DEFAULT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.list_chains ALTER COLUMN id SET DEFAULT nextval('public.list_chains_id_seq'::regclass);


--
-- Name: reposts id; Type: DEFAULT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.reposts ALTER COLUMN id SET DEFAULT nextval('public.reposts_id_seq'::regclass);


--
-- Name: verification_codes id; Type: DEFAULT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.verification_codes ALTER COLUMN id SET DEFAULT nextval('public.verification_codes_id_seq'::regclass);


--
-- Data for Name: app_notifications; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.app_notifications (id, type, title, message, sender_id, recipient_id, related_type, related_id, action_url, read_at, priority, metadata, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: claim_documents; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.claim_documents (id, claim_id, document_type, file_path, file_name, file_size, mime_type, status, notes, verified_at, verified_by, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: claims; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.claims (id, created_at, updated_at, place_id, tier, stripe_payment_intent_id, stripe_payment_status, payment_amount, payment_completed_at, stripe_subscription_id, subscription_starts_at, verification_fee_paid, fee_kept, fee_payment_intent_id, fee_refunded_at, fee_refund_id, verification_fee_amount) FROM stdin;
\.


--
-- Data for Name: comments; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.comments (id, created_at, updated_at, user_id, commentable_type, commentable_id, content, parent_id, replies_count, likes_count, mentions, deleted_at) FROM stdin;
2	2025-07-29 06:45:57	2025-07-29 06:45:57	5	App\\Models\\Post	2	I love north lake	\N	0	0	[]	\N
3	2025-07-29 06:46:52	2025-07-29 06:46:52	5	App\\Models\\Post	1	I know I love this place.	1	0	0	[]	\N
1	2025-07-29 06:45:08	2025-07-29 06:46:52	2	App\\Models\\Post	1	Brilliant	\N	1	0	[]	\N
\.


--
-- Data for Name: likes; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.likes (id, user_id, likeable_type, likeable_id, created_at, updated_at) FROM stdin;
1	2	App\\Models\\Post	1	2025-07-29 00:27:09	2025-07-29 00:27:09
2	5	App\\Models\\Post	1	2025-07-29 00:28:44	2025-07-29 00:28:44
3	2	App\\Models\\Post	2	2025-07-29 01:18:44	2025-07-29 01:18:44
4	5	App\\Models\\Post	2	2025-07-29 01:38:36	2025-07-29 01:38:36
\.


--
-- Data for Name: list_chain_items; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.list_chain_items (id, list_chain_id, list_id, order_index, label, description, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: list_chains; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.list_chains (id, name, slug, description, cover_image, cover_cloudflare_id, owner_type, owner_id, visibility, status, metadata, views_count, lists_count, published_at, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: reposts; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.reposts (id, user_id, repostable_type, repostable_id, comment, created_at, updated_at) FROM stdin;
1	2	App\\Models\\Post	1	Great Place	2025-07-29 00:27:42	2025-07-29 00:27:42
2	5	App\\Models\\Post	1	Love it	2025-07-29 00:28:52	2025-07-29 00:28:52
3	2	App\\Models\\Post	2	Checkout NorthLake	2025-07-29 06:48:34	2025-07-29 06:48:34
\.


--
-- Data for Name: verification_codes; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.verification_codes (id, verifiable_type, verifiable_id, type, code, destination, attempts, expires_at, verified_at, created_at, updated_at) FROM stdin;
\.


--
-- Name: app_notifications_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.app_notifications_id_seq', 1, false);


--
-- Name: claim_documents_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.claim_documents_id_seq', 1, false);


--
-- Name: claims_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.claims_id_seq', 1, false);


--
-- Name: comments_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.comments_id_seq', 3, true);


--
-- Name: likes_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.likes_id_seq', 4, true);


--
-- Name: list_chain_items_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.list_chain_items_id_seq', 1, false);


--
-- Name: list_chains_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.list_chains_id_seq', 1, false);


--
-- Name: reposts_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.reposts_id_seq', 3, true);


--
-- Name: verification_codes_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.verification_codes_id_seq', 1, false);


--
-- Name: app_notifications app_notifications_pkey; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.app_notifications
    ADD CONSTRAINT app_notifications_pkey PRIMARY KEY (id);


--
-- Name: claim_documents claim_documents_pkey; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.claim_documents
    ADD CONSTRAINT claim_documents_pkey PRIMARY KEY (id);


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
-- Name: likes likes_pkey; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.likes
    ADD CONSTRAINT likes_pkey PRIMARY KEY (id);


--
-- Name: likes likes_user_id_likeable_type_likeable_id_unique; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.likes
    ADD CONSTRAINT likes_user_id_likeable_type_likeable_id_unique UNIQUE (user_id, likeable_type, likeable_id);


--
-- Name: list_chain_items list_chain_items_list_chain_id_list_id_unique; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.list_chain_items
    ADD CONSTRAINT list_chain_items_list_chain_id_list_id_unique UNIQUE (list_chain_id, list_id);


--
-- Name: list_chain_items list_chain_items_pkey; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.list_chain_items
    ADD CONSTRAINT list_chain_items_pkey PRIMARY KEY (id);


--
-- Name: list_chains list_chains_pkey; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.list_chains
    ADD CONSTRAINT list_chains_pkey PRIMARY KEY (id);


--
-- Name: list_chains list_chains_slug_unique; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.list_chains
    ADD CONSTRAINT list_chains_slug_unique UNIQUE (slug);


--
-- Name: reposts reposts_pkey; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.reposts
    ADD CONSTRAINT reposts_pkey PRIMARY KEY (id);


--
-- Name: reposts reposts_user_id_repostable_type_repostable_id_unique; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.reposts
    ADD CONSTRAINT reposts_user_id_repostable_type_repostable_id_unique UNIQUE (user_id, repostable_type, repostable_id);


--
-- Name: verification_codes verification_codes_pkey; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.verification_codes
    ADD CONSTRAINT verification_codes_pkey PRIMARY KEY (id);


--
-- Name: app_notifications_recipient_id_read_at_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX app_notifications_recipient_id_read_at_index ON public.app_notifications USING btree (recipient_id, read_at);


--
-- Name: app_notifications_related_type_related_id_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX app_notifications_related_type_related_id_index ON public.app_notifications USING btree (related_type, related_id);


--
-- Name: app_notifications_type_created_at_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX app_notifications_type_created_at_index ON public.app_notifications USING btree (type, created_at);


--
-- Name: claim_documents_claim_id_status_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX claim_documents_claim_id_status_index ON public.claim_documents USING btree (claim_id, status);


--
-- Name: claims_stripe_payment_intent_id_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX claims_stripe_payment_intent_id_index ON public.claims USING btree (stripe_payment_intent_id);


--
-- Name: claims_tier_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX claims_tier_index ON public.claims USING btree (tier);


--
-- Name: comments_commentable_type_commentable_id_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX comments_commentable_type_commentable_id_index ON public.comments USING btree (commentable_type, commentable_id);


--
-- Name: comments_parent_id_created_at_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX comments_parent_id_created_at_index ON public.comments USING btree (parent_id, created_at);


--
-- Name: comments_user_id_created_at_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX comments_user_id_created_at_index ON public.comments USING btree (user_id, created_at);


--
-- Name: idx_list_chain_items_order; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX idx_list_chain_items_order ON public.list_chain_items USING btree (list_chain_id, order_index);


--
-- Name: idx_list_chains_owner; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX idx_list_chains_owner ON public.list_chains USING btree (owner_type, owner_id);


--
-- Name: idx_list_chains_slug; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX idx_list_chains_slug ON public.list_chains USING btree (slug);


--
-- Name: idx_list_chains_visibility_status; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX idx_list_chains_visibility_status ON public.list_chains USING btree (visibility, status);


--
-- Name: likes_likeable_type_likeable_id_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX likes_likeable_type_likeable_id_index ON public.likes USING btree (likeable_type, likeable_id);


--
-- Name: likes_user_id_created_at_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX likes_user_id_created_at_index ON public.likes USING btree (user_id, created_at);


--
-- Name: list_chains_owner_type_owner_id_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX list_chains_owner_type_owner_id_index ON public.list_chains USING btree (owner_type, owner_id);


--
-- Name: reposts_repostable_type_repostable_id_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX reposts_repostable_type_repostable_id_index ON public.reposts USING btree (repostable_type, repostable_id);


--
-- Name: reposts_user_id_created_at_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX reposts_user_id_created_at_index ON public.reposts USING btree (user_id, created_at);


--
-- Name: verification_codes_code_expires_at_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX verification_codes_code_expires_at_index ON public.verification_codes USING btree (code, expires_at);


--
-- Name: verification_codes_expires_at_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX verification_codes_expires_at_index ON public.verification_codes USING btree (expires_at);


--
-- Name: verification_codes_verifiable_id_verifiable_type_type_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX verification_codes_verifiable_id_verifiable_type_type_index ON public.verification_codes USING btree (verifiable_id, verifiable_type, type);


--
-- Name: verification_codes_verifiable_type_verifiable_id_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX verification_codes_verifiable_type_verifiable_id_index ON public.verification_codes USING btree (verifiable_type, verifiable_id);


--
-- Name: app_notifications app_notifications_recipient_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.app_notifications
    ADD CONSTRAINT app_notifications_recipient_id_foreign FOREIGN KEY (recipient_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: app_notifications app_notifications_sender_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.app_notifications
    ADD CONSTRAINT app_notifications_sender_id_foreign FOREIGN KEY (sender_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: claim_documents claim_documents_claim_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.claim_documents
    ADD CONSTRAINT claim_documents_claim_id_foreign FOREIGN KEY (claim_id) REFERENCES public.claims(id) ON DELETE CASCADE;


--
-- Name: claim_documents claim_documents_verified_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.claim_documents
    ADD CONSTRAINT claim_documents_verified_by_foreign FOREIGN KEY (verified_by) REFERENCES public.users(id);


--
-- Name: claims claims_place_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.claims
    ADD CONSTRAINT claims_place_id_foreign FOREIGN KEY (place_id) REFERENCES public.directory_entries(id) ON DELETE CASCADE;


--
-- Name: comments comments_parent_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.comments
    ADD CONSTRAINT comments_parent_id_foreign FOREIGN KEY (parent_id) REFERENCES public.comments(id) ON DELETE CASCADE;


--
-- Name: comments comments_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.comments
    ADD CONSTRAINT comments_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: likes likes_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.likes
    ADD CONSTRAINT likes_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: list_chain_items list_chain_items_list_chain_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.list_chain_items
    ADD CONSTRAINT list_chain_items_list_chain_id_foreign FOREIGN KEY (list_chain_id) REFERENCES public.list_chains(id) ON DELETE CASCADE;


--
-- Name: list_chain_items list_chain_items_list_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.list_chain_items
    ADD CONSTRAINT list_chain_items_list_id_foreign FOREIGN KEY (list_id) REFERENCES public.lists(id) ON DELETE CASCADE;


--
-- Name: reposts reposts_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.reposts
    ADD CONSTRAINT reposts_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- PostgreSQL database dump complete
--

