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
    description text,
    svg_icon text,
    cover_image_cloudflare_id character varying(255),
    cover_image_url character varying(255),
    quotes json
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
-- Name: channel_followers; Type: TABLE; Schema: public; Owner: ericslarson
--

CREATE TABLE public.channel_followers (
    id bigint NOT NULL,
    channel_id bigint NOT NULL,
    user_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.channel_followers OWNER TO ericslarson;

--
-- Name: channel_followers_id_seq; Type: SEQUENCE; Schema: public; Owner: ericslarson
--

CREATE SEQUENCE public.channel_followers_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.channel_followers_id_seq OWNER TO ericslarson;

--
-- Name: channel_followers_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: ericslarson
--

ALTER SEQUENCE public.channel_followers_id_seq OWNED BY public.channel_followers.id;


--
-- Name: channels; Type: TABLE; Schema: public; Owner: ericslarson
--

CREATE TABLE public.channels (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    slug character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    description text,
    avatar_image character varying(255),
    banner_image character varying(255),
    is_public boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    avatar_cloudflare_id character varying(255),
    banner_cloudflare_id character varying(255)
);


ALTER TABLE public.channels OWNER TO ericslarson;

--
-- Name: channels_id_seq; Type: SEQUENCE; Schema: public; Owner: ericslarson
--

CREATE SEQUENCE public.channels_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.channels_id_seq OWNER TO ericslarson;

--
-- Name: channels_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: ericslarson
--

ALTER SEQUENCE public.channels_id_seq OWNED BY public.channels.id;


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
-- Name: cloudflare_images; Type: TABLE; Schema: public; Owner: ericslarson
--

CREATE TABLE public.cloudflare_images (
    id bigint NOT NULL,
    cloudflare_id character varying(255) NOT NULL,
    filename character varying(255) NOT NULL,
    user_id bigint,
    context character varying(255),
    entity_type character varying(255),
    entity_id bigint,
    metadata json,
    file_size bigint,
    width integer,
    height integer,
    mime_type character varying(255),
    uploaded_at timestamp(0) without time zone NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.cloudflare_images OWNER TO ericslarson;

--
-- Name: cloudflare_images_id_seq; Type: SEQUENCE; Schema: public; Owner: ericslarson
--

CREATE SEQUENCE public.cloudflare_images_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.cloudflare_images_id_seq OWNER TO ericslarson;

--
-- Name: cloudflare_images_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: ericslarson
--

ALTER SEQUENCE public.cloudflare_images_id_seq OWNED BY public.cloudflare_images.id;


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
    state_region_id bigint,
    city_region_id bigint,
    neighborhood_region_id bigint,
    regions_updated_at timestamp(0) without time zone,
    links json,
    rejection_reason text,
    rejected_at timestamp(0) without time zone,
    rejected_by bigint,
    approval_notes text,
    approved_by bigint,
    subscription_tier character varying(255) DEFAULT 'free'::character varying NOT NULL,
    subscription_expires_at timestamp(0) without time zone,
    stripe_customer_id character varying(255),
    stripe_subscription_id character varying(255),
    claimed_at timestamp(0) without time zone,
    ownership_transferred_at timestamp(0) without time zone,
    ownership_transferred_by bigint,
    likes_count integer DEFAULT 0 NOT NULL,
    comments_count integer DEFAULT 0 NOT NULL,
    CONSTRAINT directory_entries_parking_options_check CHECK (((parking_options)::text = ANY (ARRAY[('street'::character varying)::text, ('lot'::character varying)::text, ('valet'::character varying)::text, ('none'::character varying)::text]))),
    CONSTRAINT directory_entries_price_range_check CHECK (((price_range)::text = ANY (ARRAY[('$'::character varying)::text, ('$$'::character varying)::text, ('$$$'::character varying)::text, ('$$$$'::character varying)::text]))),
    CONSTRAINT directory_entries_status_check CHECK (((status)::text = ANY (ARRAY[('draft'::character varying)::text, ('pending_review'::character varying)::text, ('published'::character varying)::text, ('archived'::character varying)::text]))),
    CONSTRAINT directory_entries_subscription_tier_check CHECK (((subscription_tier)::text = ANY ((ARRAY['free'::character varying, 'tier1'::character varying, 'tier2'::character varying])::text[]))),
    CONSTRAINT directory_entries_type_check CHECK (((type)::text = ANY ((ARRAY['business_b2b'::character varying, 'business_b2c'::character varying, 'religious_org'::character varying, 'point_of_interest'::character varying, 'area_of_interest'::character varying, 'service'::character varying, 'online'::character varying])::text[])))
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
-- Name: directory_entry_follows; Type: TABLE; Schema: public; Owner: ericslarson
--

CREATE TABLE public.directory_entry_follows (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    directory_entry_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.directory_entry_follows OWNER TO ericslarson;

--
-- Name: directory_entry_follows_id_seq; Type: SEQUENCE; Schema: public; Owner: ericslarson
--

CREATE SEQUENCE public.directory_entry_follows_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.directory_entry_follows_id_seq OWNER TO ericslarson;

--
-- Name: directory_entry_follows_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: ericslarson
--

ALTER SEQUENCE public.directory_entry_follows_id_seq OWNED BY public.directory_entry_follows.id;


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
-- Name: follows; Type: TABLE; Schema: public; Owner: ericslarson
--

CREATE TABLE public.follows (
    id bigint NOT NULL,
    follower_id bigint NOT NULL,
    followable_id bigint NOT NULL,
    followable_type character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.follows OWNER TO ericslarson;

--
-- Name: follows_id_seq; Type: SEQUENCE; Schema: public; Owner: ericslarson
--

CREATE SEQUENCE public.follows_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.follows_id_seq OWNER TO ericslarson;

--
-- Name: follows_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: ericslarson
--

ALTER SEQUENCE public.follows_id_seq OWNED BY public.follows.id;


--
-- Name: home_page_settings; Type: TABLE; Schema: public; Owner: ericslarson
--

CREATE TABLE public.home_page_settings (
    id bigint NOT NULL,
    hero_title character varying(255),
    hero_subtitle character varying(255),
    hero_image_path character varying(255),
    cta_text character varying(255),
    cta_link character varying(255),
    featured_places json,
    testimonials json,
    custom_scripts text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.home_page_settings OWNER TO ericslarson;

--
-- Name: home_page_settings_id_seq; Type: SEQUENCE; Schema: public; Owner: ericslarson
--

CREATE SEQUENCE public.home_page_settings_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.home_page_settings_id_seq OWNER TO ericslarson;

--
-- Name: home_page_settings_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: ericslarson
--

ALTER SEQUENCE public.home_page_settings_id_seq OWNED BY public.home_page_settings.id;


--
-- Name: image_uploads; Type: TABLE; Schema: public; Owner: ericslarson
--

CREATE TABLE public.image_uploads (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    type character varying(255) NOT NULL,
    entity_id bigint,
    temp_path character varying(255) NOT NULL,
    original_name character varying(255) NOT NULL,
    file_size integer NOT NULL,
    mime_type character varying(255) NOT NULL,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    cloudflare_id character varying(255),
    image_record_id bigint,
    error_message text,
    completed_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT image_uploads_status_check CHECK (((status)::text = ANY (ARRAY[('pending'::character varying)::text, ('processing'::character varying)::text, ('completed'::character varying)::text, ('failed'::character varying)::text]))),
    CONSTRAINT image_uploads_type_check CHECK (((type)::text = ANY (ARRAY[('avatar'::character varying)::text, ('cover'::character varying)::text, ('page_logo'::character varying)::text, ('list_image'::character varying)::text, ('entry_logo'::character varying)::text])))
);


ALTER TABLE public.image_uploads OWNER TO ericslarson;

--
-- Name: image_uploads_id_seq; Type: SEQUENCE; Schema: public; Owner: ericslarson
--

CREATE SEQUENCE public.image_uploads_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.image_uploads_id_seq OWNER TO ericslarson;

--
-- Name: image_uploads_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: ericslarson
--

ALTER SEQUENCE public.image_uploads_id_seq OWNED BY public.image_uploads.id;


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
-- Name: list_categories; Type: TABLE; Schema: public; Owner: ericslarson
--

CREATE TABLE public.list_categories (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    slug character varying(255) NOT NULL,
    description text,
    color character varying(7) DEFAULT '#3B82F6'::character varying NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    sort_order integer DEFAULT 0 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    svg_icon text,
    cover_image_cloudflare_id character varying(255),
    cover_image_url character varying(255),
    quotes json
);


ALTER TABLE public.list_categories OWNER TO ericslarson;

--
-- Name: list_categories_id_seq; Type: SEQUENCE; Schema: public; Owner: ericslarson
--

CREATE SEQUENCE public.list_categories_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.list_categories_id_seq OWNER TO ericslarson;

--
-- Name: list_categories_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: ericslarson
--

ALTER SEQUENCE public.list_categories_id_seq OWNED BY public.list_categories.id;


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
    item_image_cloudflare_id character varying(255),
    CONSTRAINT list_items_type_check CHECK (((type)::text = ANY (ARRAY[('directory_entry'::character varying)::text, ('text'::character varying)::text, ('location'::character varying)::text, ('event'::character varying)::text])))
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
    CONSTRAINT list_media_type_check CHECK (((type)::text = ANY (ARRAY[('image'::character varying)::text, ('youtube'::character varying)::text, ('rumble'::character varying)::text])))
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
    user_id bigint,
    name character varying(255) NOT NULL,
    description text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    featured_image character varying(255),
    slug character varying(255),
    view_count integer DEFAULT 0 NOT NULL,
    settings json,
    is_featured boolean DEFAULT false NOT NULL,
    featured_image_cloudflare_id character varying(255),
    category_id bigint,
    visibility character varying(255) DEFAULT 'public'::character varying NOT NULL,
    is_draft boolean DEFAULT false NOT NULL,
    published_at timestamp(0) without time zone,
    scheduled_for timestamp(0) without time zone,
    gallery_images json,
    is_pinned boolean DEFAULT false NOT NULL,
    pinned_at timestamp(0) without time zone,
    status character varying(255) DEFAULT 'active'::character varying NOT NULL,
    status_reason text,
    status_changed_at timestamp(0) without time zone,
    status_changed_by bigint,
    type character varying(255) DEFAULT 'user'::character varying NOT NULL,
    is_region_specific boolean DEFAULT false NOT NULL,
    region_id bigint,
    is_category_specific boolean DEFAULT false NOT NULL,
    place_ids json,
    order_index integer DEFAULT 0 NOT NULL,
    channel_id bigint,
    owner_type character varying(255) NOT NULL,
    owner_id bigint NOT NULL,
    likes_count integer DEFAULT 0 NOT NULL,
    comments_count integer DEFAULT 0 NOT NULL,
    reposts_count integer DEFAULT 0 NOT NULL,
    CONSTRAINT lists_status_check CHECK (((status)::text = ANY ((ARRAY['active'::character varying, 'on_hold'::character varying, 'draft'::character varying])::text[]))),
    CONSTRAINT lists_visibility_check CHECK (((visibility)::text = ANY (ARRAY[('public'::character varying)::text, ('unlisted'::character varying)::text, ('private'::character varying)::text])))
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
-- Name: local_page_settings; Type: TABLE; Schema: public; Owner: ericslarson
--

CREATE TABLE public.local_page_settings (
    id bigint NOT NULL,
    region_id bigint,
    page_type character varying(255) DEFAULT 'index'::character varying NOT NULL,
    title character varying(255),
    intro_text text,
    meta_description text,
    featured_lists json,
    featured_places json,
    content_sections json,
    settings json,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.local_page_settings OWNER TO ericslarson;

--
-- Name: local_page_settings_id_seq; Type: SEQUENCE; Schema: public; Owner: ericslarson
--

CREATE SEQUENCE public.local_page_settings_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.local_page_settings_id_seq OWNER TO ericslarson;

--
-- Name: local_page_settings_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: ericslarson
--

ALTER SEQUENCE public.local_page_settings_id_seq OWNED BY public.local_page_settings.id;


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
-- Name: login_page_settings; Type: TABLE; Schema: public; Owner: ericslarson
--

CREATE TABLE public.login_page_settings (
    id bigint NOT NULL,
    background_image_path character varying(255),
    welcome_message text,
    custom_css text,
    social_login_enabled boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    background_image_id character varying(255)
);


ALTER TABLE public.login_page_settings OWNER TO ericslarson;

--
-- Name: login_page_settings_id_seq; Type: SEQUENCE; Schema: public; Owner: ericslarson
--

CREATE SEQUENCE public.login_page_settings_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.login_page_settings_id_seq OWNER TO ericslarson;

--
-- Name: login_page_settings_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: ericslarson
--

ALTER SEQUENCE public.login_page_settings_id_seq OWNED BY public.login_page_settings.id;


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
-- Name: notifications; Type: TABLE; Schema: public; Owner: ericslarson
--

CREATE TABLE public.notifications (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    type character varying(255) NOT NULL,
    title character varying(255),
    message text,
    data json,
    notifiable_type character varying(255) NOT NULL,
    notifiable_id bigint NOT NULL,
    is_read boolean DEFAULT false NOT NULL,
    read_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.notifications OWNER TO ericslarson;

--
-- Name: notifications_id_seq; Type: SEQUENCE; Schema: public; Owner: ericslarson
--

CREATE SEQUENCE public.notifications_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.notifications_id_seq OWNER TO ericslarson;

--
-- Name: notifications_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: ericslarson
--

ALTER SEQUENCE public.notifications_id_seq OWNED BY public.notifications.id;


--
-- Name: pages; Type: TABLE; Schema: public; Owner: ericslarson
--

CREATE TABLE public.pages (
    id bigint NOT NULL,
    title character varying(255) NOT NULL,
    slug character varying(255) NOT NULL,
    content text,
    status character varying(255) DEFAULT 'draft'::character varying NOT NULL,
    meta_title character varying(255),
    meta_description text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT pages_status_check CHECK (((status)::text = ANY ((ARRAY['draft'::character varying, 'published'::character varying])::text[])))
);


ALTER TABLE public.pages OWNER TO ericslarson;

--
-- Name: pages_id_seq; Type: SEQUENCE; Schema: public; Owner: ericslarson
--

CREATE SEQUENCE public.pages_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.pages_id_seq OWNER TO ericslarson;

--
-- Name: pages_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: ericslarson
--

ALTER SEQUENCE public.pages_id_seq OWNED BY public.pages.id;


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
-- Name: personal_access_tokens; Type: TABLE; Schema: public; Owner: ericslarson
--

CREATE TABLE public.personal_access_tokens (
    id bigint NOT NULL,
    tokenable_type character varying(255) NOT NULL,
    tokenable_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    token character varying(64) NOT NULL,
    abilities text,
    last_used_at timestamp(0) without time zone,
    expires_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.personal_access_tokens OWNER TO ericslarson;

--
-- Name: personal_access_tokens_id_seq; Type: SEQUENCE; Schema: public; Owner: ericslarson
--

CREATE SEQUENCE public.personal_access_tokens_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.personal_access_tokens_id_seq OWNER TO ericslarson;

--
-- Name: personal_access_tokens_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: ericslarson
--

ALTER SEQUENCE public.personal_access_tokens_id_seq OWNED BY public.personal_access_tokens.id;


--
-- Name: pinned_lists; Type: TABLE; Schema: public; Owner: ericslarson
--

CREATE TABLE public.pinned_lists (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    list_id bigint NOT NULL,
    sort_order integer DEFAULT 0 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.pinned_lists OWNER TO ericslarson;

--
-- Name: pinned_lists_id_seq; Type: SEQUENCE; Schema: public; Owner: ericslarson
--

CREATE SEQUENCE public.pinned_lists_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.pinned_lists_id_seq OWNER TO ericslarson;

--
-- Name: pinned_lists_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: ericslarson
--

ALTER SEQUENCE public.pinned_lists_id_seq OWNED BY public.pinned_lists.id;


--
-- Name: place_managers; Type: TABLE; Schema: public; Owner: ericslarson
--

CREATE TABLE public.place_managers (
    id bigint NOT NULL,
    place_id bigint NOT NULL,
    manageable_type character varying(255) NOT NULL,
    manageable_id bigint NOT NULL,
    role character varying(255) DEFAULT 'manager'::character varying NOT NULL,
    permissions json,
    is_active boolean DEFAULT true NOT NULL,
    accepted_at timestamp(0) without time zone,
    invited_at timestamp(0) without time zone,
    invited_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.place_managers OWNER TO ericslarson;

--
-- Name: place_managers_id_seq; Type: SEQUENCE; Schema: public; Owner: ericslarson
--

CREATE SEQUENCE public.place_managers_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.place_managers_id_seq OWNER TO ericslarson;

--
-- Name: place_managers_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: ericslarson
--

ALTER SEQUENCE public.place_managers_id_seq OWNED BY public.place_managers.id;


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
-- Name: posts; Type: TABLE; Schema: public; Owner: ericslarson
--

CREATE TABLE public.posts (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    content text NOT NULL,
    media json,
    media_type character varying(255),
    cloudflare_image_id character varying(255),
    cloudflare_video_id character varying(255),
    is_tacked boolean DEFAULT false NOT NULL,
    tacked_at timestamp(0) without time zone,
    expires_in_days integer,
    expires_at timestamp(0) without time zone,
    likes_count integer DEFAULT 0 NOT NULL,
    replies_count integer DEFAULT 0 NOT NULL,
    shares_count integer DEFAULT 0 NOT NULL,
    views_count integer DEFAULT 0 NOT NULL,
    metadata json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    comments_count integer DEFAULT 0 NOT NULL,
    reposts_count integer DEFAULT 0 NOT NULL
);


ALTER TABLE public.posts OWNER TO ericslarson;

--
-- Name: posts_id_seq; Type: SEQUENCE; Schema: public; Owner: ericslarson
--

CREATE SEQUENCE public.posts_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.posts_id_seq OWNER TO ericslarson;

--
-- Name: posts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: ericslarson
--

ALTER SEQUENCE public.posts_id_seq OWNED BY public.posts.id;


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
-- Name: region_featured_metadata; Type: TABLE; Schema: public; Owner: ericslarson
--

CREATE TABLE public.region_featured_metadata (
    id bigint NOT NULL,
    region_id bigint NOT NULL,
    featured_title character varying(255),
    featured_description text,
    max_featured_places integer DEFAULT 10 NOT NULL,
    show_featured_section boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.region_featured_metadata OWNER TO ericslarson;

--
-- Name: region_featured_metadata_id_seq; Type: SEQUENCE; Schema: public; Owner: ericslarson
--

CREATE SEQUENCE public.region_featured_metadata_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.region_featured_metadata_id_seq OWNER TO ericslarson;

--
-- Name: region_featured_metadata_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: ericslarson
--

ALTER SEQUENCE public.region_featured_metadata_id_seq OWNED BY public.region_featured_metadata.id;


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
-- Name: registration_waitlists; Type: TABLE; Schema: public; Owner: ericslarson
--

CREATE TABLE public.registration_waitlists (
    id bigint NOT NULL,
    email character varying(255) NOT NULL,
    name character varying(255),
    message text,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    invitation_token character varying(255),
    invited_at timestamp(0) without time zone,
    registered_at timestamp(0) without time zone,
    metadata json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.registration_waitlists OWNER TO ericslarson;

--
-- Name: registration_waitlists_id_seq; Type: SEQUENCE; Schema: public; Owner: ericslarson
--

CREATE SEQUENCE public.registration_waitlists_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.registration_waitlists_id_seq OWNER TO ericslarson;

--
-- Name: registration_waitlists_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: ericslarson
--

ALTER SEQUENCE public.registration_waitlists_id_seq OWNED BY public.registration_waitlists.id;


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
-- Name: saved_items; Type: TABLE; Schema: public; Owner: ericslarson
--

CREATE TABLE public.saved_items (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    saveable_type character varying(255) NOT NULL,
    saveable_id bigint NOT NULL,
    notes character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.saved_items OWNER TO ericslarson;

--
-- Name: saved_items_id_seq; Type: SEQUENCE; Schema: public; Owner: ericslarson
--

CREATE SEQUENCE public.saved_items_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.saved_items_id_seq OWNER TO ericslarson;

--
-- Name: saved_items_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: ericslarson
--

ALTER SEQUENCE public.saved_items_id_seq OWNED BY public.saved_items.id;


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
-- Name: settings; Type: TABLE; Schema: public; Owner: ericslarson
--

CREATE TABLE public.settings (
    id bigint NOT NULL,
    key character varying(255) NOT NULL,
    value text,
    type character varying(255) DEFAULT 'string'::character varying NOT NULL,
    "group" character varying(255) DEFAULT 'general'::character varying NOT NULL,
    description character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.settings OWNER TO ericslarson;

--
-- Name: settings_id_seq; Type: SEQUENCE; Schema: public; Owner: ericslarson
--

CREATE SEQUENCE public.settings_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.settings_id_seq OWNER TO ericslarson;

--
-- Name: settings_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: ericslarson
--

ALTER SEQUENCE public.settings_id_seq OWNED BY public.settings.id;


--
-- Name: taggables; Type: TABLE; Schema: public; Owner: ericslarson
--

CREATE TABLE public.taggables (
    id bigint NOT NULL,
    tag_id bigint NOT NULL,
    taggable_type character varying(255) NOT NULL,
    taggable_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.taggables OWNER TO ericslarson;

--
-- Name: taggables_id_seq; Type: SEQUENCE; Schema: public; Owner: ericslarson
--

CREATE SEQUENCE public.taggables_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.taggables_id_seq OWNER TO ericslarson;

--
-- Name: taggables_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: ericslarson
--

ALTER SEQUENCE public.taggables_id_seq OWNED BY public.taggables.id;


--
-- Name: tags; Type: TABLE; Schema: public; Owner: ericslarson
--

CREATE TABLE public.tags (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    slug character varying(255) NOT NULL,
    description text,
    color character varying(7) DEFAULT '#6B7280'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    type character varying(255) DEFAULT 'general'::character varying NOT NULL,
    usage_count integer DEFAULT 0 NOT NULL,
    places_count integer DEFAULT 0 NOT NULL,
    lists_count integer DEFAULT 0 NOT NULL,
    posts_count integer DEFAULT 0 NOT NULL,
    is_featured boolean DEFAULT false NOT NULL,
    is_system boolean DEFAULT false NOT NULL,
    created_by bigint
);


ALTER TABLE public.tags OWNER TO ericslarson;

--
-- Name: tags_id_seq; Type: SEQUENCE; Schema: public; Owner: ericslarson
--

CREATE SEQUENCE public.tags_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.tags_id_seq OWNER TO ericslarson;

--
-- Name: tags_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: ericslarson
--

ALTER SEQUENCE public.tags_id_seq OWNED BY public.tags.id;


--
-- Name: uploaded_images; Type: TABLE; Schema: public; Owner: ericslarson
--

CREATE TABLE public.uploaded_images (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    cloudflare_id character varying(255) NOT NULL,
    type character varying(255) NOT NULL,
    entity_id bigint,
    original_name character varying(255) NOT NULL,
    file_size integer NOT NULL,
    mime_type character varying(255) NOT NULL,
    variants json,
    meta json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT uploaded_images_type_check CHECK (((type)::text = ANY (ARRAY[('avatar'::character varying)::text, ('cover'::character varying)::text, ('page_logo'::character varying)::text, ('list_image'::character varying)::text, ('entry_logo'::character varying)::text])))
);


ALTER TABLE public.uploaded_images OWNER TO ericslarson;

--
-- Name: uploaded_images_id_seq; Type: SEQUENCE; Schema: public; Owner: ericslarson
--

CREATE SEQUENCE public.uploaded_images_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.uploaded_images_id_seq OWNER TO ericslarson;

--
-- Name: uploaded_images_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: ericslarson
--

ALTER SEQUENCE public.uploaded_images_id_seq OWNED BY public.uploaded_images.id;


--
-- Name: user_activities; Type: TABLE; Schema: public; Owner: ericslarson
--

CREATE TABLE public.user_activities (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    activity_type character varying(255) NOT NULL,
    subject_type character varying(255) NOT NULL,
    subject_id bigint NOT NULL,
    metadata json,
    is_public boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.user_activities OWNER TO ericslarson;

--
-- Name: user_activities_id_seq; Type: SEQUENCE; Schema: public; Owner: ericslarson
--

CREATE SEQUENCE public.user_activities_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.user_activities_id_seq OWNER TO ericslarson;

--
-- Name: user_activities_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: ericslarson
--

ALTER SEQUENCE public.user_activities_id_seq OWNED BY public.user_activities.id;


--
-- Name: user_follows; Type: TABLE; Schema: public; Owner: ericslarson
--

CREATE TABLE public.user_follows (
    id bigint NOT NULL,
    follower_id bigint NOT NULL,
    following_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.user_follows OWNER TO ericslarson;

--
-- Name: user_follows_id_seq; Type: SEQUENCE; Schema: public; Owner: ericslarson
--

CREATE SEQUENCE public.user_follows_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.user_follows_id_seq OWNER TO ericslarson;

--
-- Name: user_follows_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: ericslarson
--

ALTER SEQUENCE public.user_follows_id_seq OWNED BY public.user_follows.id;


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
-- Name: user_list_shares; Type: TABLE; Schema: public; Owner: ericslarson
--

CREATE TABLE public.user_list_shares (
    id bigint NOT NULL,
    list_id bigint NOT NULL,
    user_id bigint NOT NULL,
    shared_by bigint NOT NULL,
    permission character varying(255) DEFAULT 'view'::character varying NOT NULL,
    expires_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT user_list_shares_permission_check CHECK (((permission)::text = ANY (ARRAY[('view'::character varying)::text, ('edit'::character varying)::text])))
);


ALTER TABLE public.user_list_shares OWNER TO ericslarson;

--
-- Name: user_list_shares_id_seq; Type: SEQUENCE; Schema: public; Owner: ericslarson
--

CREATE SEQUENCE public.user_list_shares_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.user_list_shares_id_seq OWNER TO ericslarson;

--
-- Name: user_list_shares_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: ericslarson
--

ALTER SEQUENCE public.user_list_shares_id_seq OWNED BY public.user_list_shares.id;


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
    display_title character varying(255),
    profile_description text,
    location character varying(255),
    website character varying(255),
    birth_date date,
    profile_settings json,
    show_activity boolean DEFAULT true NOT NULL,
    show_followers boolean DEFAULT true NOT NULL,
    show_following boolean DEFAULT true NOT NULL,
    profile_views integer DEFAULT 0 NOT NULL,
    page_title character varying(255),
    custom_css text,
    theme_settings json,
    profile_color character varying(255),
    show_join_date boolean DEFAULT true NOT NULL,
    show_location boolean DEFAULT true NOT NULL,
    show_website boolean DEFAULT true NOT NULL,
    avatar_cloudflare_id character varying(255),
    cover_cloudflare_id character varying(255),
    page_logo_cloudflare_id character varying(255),
    phone character varying(20),
    page_logo_option character varying(20) DEFAULT 'initials'::character varying NOT NULL,
    avatar_updated_at timestamp(0) without time zone,
    cover_updated_at timestamp(0) without time zone,
    default_region_id bigint,
    firstname character varying(255) NOT NULL,
    lastname character varying(255) NOT NULL,
    gender character varying(255),
    birthdate date,
    stripe_customer_id character varying(255),
    CONSTRAINT users_gender_check CHECK (((gender)::text = ANY ((ARRAY['male'::character varying, 'female'::character varying, 'prefer_not_to_say'::character varying])::text[]))),
    CONSTRAINT users_role_check CHECK (((role)::text = ANY (ARRAY[('admin'::character varying)::text, ('manager'::character varying)::text, ('editor'::character varying)::text, ('business_owner'::character varying)::text, ('user'::character varying)::text])))
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
-- Name: categories id; Type: DEFAULT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.categories ALTER COLUMN id SET DEFAULT nextval('public.categories_id_seq'::regclass);


--
-- Name: channel_followers id; Type: DEFAULT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.channel_followers ALTER COLUMN id SET DEFAULT nextval('public.channel_followers_id_seq'::regclass);


--
-- Name: channels id; Type: DEFAULT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.channels ALTER COLUMN id SET DEFAULT nextval('public.channels_id_seq'::regclass);


--
-- Name: claim_documents id; Type: DEFAULT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.claim_documents ALTER COLUMN id SET DEFAULT nextval('public.claim_documents_id_seq'::regclass);


--
-- Name: claims id; Type: DEFAULT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.claims ALTER COLUMN id SET DEFAULT nextval('public.claims_id_seq'::regclass);


--
-- Name: cloudflare_images id; Type: DEFAULT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.cloudflare_images ALTER COLUMN id SET DEFAULT nextval('public.cloudflare_images_id_seq'::regclass);


--
-- Name: comments id; Type: DEFAULT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.comments ALTER COLUMN id SET DEFAULT nextval('public.comments_id_seq'::regclass);


--
-- Name: directory_entries id; Type: DEFAULT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.directory_entries ALTER COLUMN id SET DEFAULT nextval('public.directory_entries_id_seq'::regclass);


--
-- Name: directory_entry_follows id; Type: DEFAULT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.directory_entry_follows ALTER COLUMN id SET DEFAULT nextval('public.directory_entry_follows_id_seq'::regclass);


--
-- Name: failed_jobs id; Type: DEFAULT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.failed_jobs ALTER COLUMN id SET DEFAULT nextval('public.failed_jobs_id_seq'::regclass);


--
-- Name: follows id; Type: DEFAULT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.follows ALTER COLUMN id SET DEFAULT nextval('public.follows_id_seq'::regclass);


--
-- Name: home_page_settings id; Type: DEFAULT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.home_page_settings ALTER COLUMN id SET DEFAULT nextval('public.home_page_settings_id_seq'::regclass);


--
-- Name: image_uploads id; Type: DEFAULT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.image_uploads ALTER COLUMN id SET DEFAULT nextval('public.image_uploads_id_seq'::regclass);


--
-- Name: jobs id; Type: DEFAULT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.jobs ALTER COLUMN id SET DEFAULT nextval('public.jobs_id_seq'::regclass);


--
-- Name: likes id; Type: DEFAULT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.likes ALTER COLUMN id SET DEFAULT nextval('public.likes_id_seq'::regclass);


--
-- Name: list_categories id; Type: DEFAULT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.list_categories ALTER COLUMN id SET DEFAULT nextval('public.list_categories_id_seq'::regclass);


--
-- Name: list_chain_items id; Type: DEFAULT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.list_chain_items ALTER COLUMN id SET DEFAULT nextval('public.list_chain_items_id_seq'::regclass);


--
-- Name: list_chains id; Type: DEFAULT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.list_chains ALTER COLUMN id SET DEFAULT nextval('public.list_chains_id_seq'::regclass);


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
-- Name: local_page_settings id; Type: DEFAULT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.local_page_settings ALTER COLUMN id SET DEFAULT nextval('public.local_page_settings_id_seq'::regclass);


--
-- Name: locations id; Type: DEFAULT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.locations ALTER COLUMN id SET DEFAULT nextval('public.locations_id_seq'::regclass);


--
-- Name: login_page_settings id; Type: DEFAULT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.login_page_settings ALTER COLUMN id SET DEFAULT nextval('public.login_page_settings_id_seq'::regclass);


--
-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);


--
-- Name: notifications id; Type: DEFAULT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.notifications ALTER COLUMN id SET DEFAULT nextval('public.notifications_id_seq'::regclass);


--
-- Name: pages id; Type: DEFAULT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.pages ALTER COLUMN id SET DEFAULT nextval('public.pages_id_seq'::regclass);


--
-- Name: personal_access_tokens id; Type: DEFAULT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.personal_access_tokens ALTER COLUMN id SET DEFAULT nextval('public.personal_access_tokens_id_seq'::regclass);


--
-- Name: pinned_lists id; Type: DEFAULT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.pinned_lists ALTER COLUMN id SET DEFAULT nextval('public.pinned_lists_id_seq'::regclass);


--
-- Name: place_managers id; Type: DEFAULT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.place_managers ALTER COLUMN id SET DEFAULT nextval('public.place_managers_id_seq'::regclass);


--
-- Name: place_regions id; Type: DEFAULT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.place_regions ALTER COLUMN id SET DEFAULT nextval('public.place_regions_id_seq'::regclass);


--
-- Name: posts id; Type: DEFAULT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.posts ALTER COLUMN id SET DEFAULT nextval('public.posts_id_seq'::regclass);


--
-- Name: region_featured_entries id; Type: DEFAULT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.region_featured_entries ALTER COLUMN id SET DEFAULT nextval('public.region_featured_entries_id_seq'::regclass);


--
-- Name: region_featured_lists id; Type: DEFAULT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.region_featured_lists ALTER COLUMN id SET DEFAULT nextval('public.region_featured_lists_id_seq'::regclass);


--
-- Name: region_featured_metadata id; Type: DEFAULT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.region_featured_metadata ALTER COLUMN id SET DEFAULT nextval('public.region_featured_metadata_id_seq'::regclass);


--
-- Name: regions id; Type: DEFAULT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.regions ALTER COLUMN id SET DEFAULT nextval('public.regions_id_seq'::regclass);


--
-- Name: registration_waitlists id; Type: DEFAULT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.registration_waitlists ALTER COLUMN id SET DEFAULT nextval('public.registration_waitlists_id_seq'::regclass);


--
-- Name: reposts id; Type: DEFAULT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.reposts ALTER COLUMN id SET DEFAULT nextval('public.reposts_id_seq'::regclass);


--
-- Name: saved_items id; Type: DEFAULT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.saved_items ALTER COLUMN id SET DEFAULT nextval('public.saved_items_id_seq'::regclass);


--
-- Name: settings id; Type: DEFAULT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.settings ALTER COLUMN id SET DEFAULT nextval('public.settings_id_seq'::regclass);


--
-- Name: taggables id; Type: DEFAULT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.taggables ALTER COLUMN id SET DEFAULT nextval('public.taggables_id_seq'::regclass);


--
-- Name: tags id; Type: DEFAULT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.tags ALTER COLUMN id SET DEFAULT nextval('public.tags_id_seq'::regclass);


--
-- Name: uploaded_images id; Type: DEFAULT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.uploaded_images ALTER COLUMN id SET DEFAULT nextval('public.uploaded_images_id_seq'::regclass);


--
-- Name: user_activities id; Type: DEFAULT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.user_activities ALTER COLUMN id SET DEFAULT nextval('public.user_activities_id_seq'::regclass);


--
-- Name: user_follows id; Type: DEFAULT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.user_follows ALTER COLUMN id SET DEFAULT nextval('public.user_follows_id_seq'::regclass);


--
-- Name: user_list_favorites id; Type: DEFAULT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.user_list_favorites ALTER COLUMN id SET DEFAULT nextval('public.user_list_favorites_id_seq'::regclass);


--
-- Name: user_list_shares id; Type: DEFAULT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.user_list_shares ALTER COLUMN id SET DEFAULT nextval('public.user_list_shares_id_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


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

COPY public.categories (id, name, slug, parent_id, icon, order_index, created_at, updated_at, description, svg_icon, cover_image_cloudflare_id, cover_image_url, quotes) FROM stdin;
1	Restaurants	restaurants	\N	\N	0	2025-06-01 20:18:34	2025-06-01 20:18:34	\N	\N	\N	\N	\N
2	Italian	italian	1	\N	0	2025-06-01 20:18:34	2025-06-01 20:18:34	\N	\N	\N	\N	\N
3	Mexican	mexican	1	\N	0	2025-06-01 20:18:34	2025-06-01 20:18:34	\N	\N	\N	\N	\N
4	Asian	asian	1	\N	0	2025-06-01 20:18:34	2025-06-01 20:18:34	\N	\N	\N	\N	\N
5	American	american	1	\N	0	2025-06-01 20:18:34	2025-06-01 20:18:34	\N	\N	\N	\N	\N
6	Seafood	seafood	1	\N	0	2025-06-01 20:18:34	2025-06-01 20:18:34	\N	\N	\N	\N	\N
7	Shopping	shopping	\N	\N	0	2025-06-01 20:18:34	2025-06-01 20:18:34	\N	\N	\N	\N	\N
8	Clothing	clothing	7	\N	0	2025-06-01 20:18:34	2025-06-01 20:18:34	\N	\N	\N	\N	\N
9	Electronics	electronics	7	\N	0	2025-06-01 20:18:34	2025-06-01 20:18:34	\N	\N	\N	\N	\N
11	Home & Garden	home-garden	7	\N	0	2025-06-01 20:18:34	2025-06-01 20:18:34	\N	\N	\N	\N	\N
13	Auto Repair	auto-repair	12	\N	0	2025-06-01 20:18:34	2025-06-01 20:18:34	\N	\N	\N	\N	\N
14	Beauty & Spa	beauty-spa	12	\N	0	2025-06-01 20:18:34	2025-06-01 20:18:34	\N	\N	\N	\N	\N
16	Professional	professional	12	\N	0	2025-06-01 20:18:34	2025-06-01 20:18:34	\N	\N	\N	\N	\N
17	Entertainment	entertainment	\N	\N	0	2025-06-01 20:18:34	2025-06-01 20:18:34	\N	\N	\N	\N	\N
18	Movies	movies	17	\N	0	2025-06-01 20:18:34	2025-06-01 20:18:34	\N	\N	\N	\N	\N
19	Sports	sports	17	\N	0	2025-06-01 20:18:34	2025-06-01 20:18:34	\N	\N	\N	\N	\N
20	Music Venues	music-venues	17	\N	0	2025-06-01 20:18:34	2025-06-01 20:18:34	\N	\N	\N	\N	\N
21	Parks & Recreation	parks-recreation	17	\N	0	2025-06-01 20:18:34	2025-06-01 20:18:34	\N	\N	\N	\N	\N
22	Online	online	\N	\N	0	2025-06-01 20:18:34	2025-06-01 20:18:34	\N	\N	\N	\N	\N
23	E-commerce	e-commerce	22	\N	0	2025-06-01 20:18:34	2025-06-01 20:18:34	\N	\N	\N	\N	\N
24	Digital Services	digital-services	22	\N	0	2025-06-01 20:18:34	2025-06-01 20:18:34	\N	\N	\N	\N	\N
25	Educational	educational	22	\N	0	2025-06-01 20:18:34	2025-06-01 20:18:34	\N	\N	\N	\N	\N
26	Software	software	22	\N	0	2025-06-01 20:18:34	2025-06-01 20:18:34	\N	\N	\N	\N	\N
27	Food	food	7	\N	0	2025-06-28 00:21:06	2025-06-28 00:21:06	\N	\N	\N	\N	\N
28	Sporting Goods	sporting-goods	7	⚽	0	2025-07-05 02:52:53	2025-07-05 02:52:53	\N	\N	\N	\N	\N
29	Test Category	test-category-with-svg	\N	🎨	0	2025-07-06 07:36:43	2025-07-06 07:36:43	A test category with SVG icon and quotes	<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"/></svg>	\N	\N	["Innovation distinguishes between a leader and a follower.","The best way to predict the future is to invent it.","Quality is not an act, it is a habit."]
12	Services	services	\N	\N	0	2025-06-01 20:18:34	2025-07-06 07:40:13	\N	\N	\N	\N	["Good service is good business. \\u2014 Siebel Ad","Make a customer, not a sale.\\" \\u2014 Katherine Barchetti","\\"People will forget what you said, people will forget what you did, but people will never forget how you made them feel.\\" \\u2014 Maya Angelou"]
\.


--
-- Data for Name: channel_followers; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.channel_followers (id, channel_id, user_id, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: channels; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.channels (id, user_id, slug, name, description, avatar_image, banner_image, is_public, created_at, updated_at, avatar_cloudflare_id, banner_cloudflare_id) FROM stdin;
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
-- Data for Name: cloudflare_images; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.cloudflare_images (id, cloudflare_id, filename, user_id, context, entity_type, entity_id, metadata, file_size, width, height, mime_type, uploaded_at, created_at, updated_at) FROM stdin;
6	3deaecf0-7aed-43b6-c744-ca0a6ad08200	mogulbig.gif	2	logo	App\\Models\\DirectoryEntry	69	{"id":"3deaecf0-7aed-43b6-c744-ca0a6ad08200","filename":"mogulbig.gif","uploaded":"2025-07-05T21:34:25.658Z","requireSignedURLs":false,"variants":["https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/3deaecf0-7aed-43b6-c744-ca0a6ad08200\\/public"]}	\N	\N	\N	\N	2025-07-05 21:34:26	2025-07-05 21:34:26	2025-07-05 21:34:26
7	5ace8ab2-8ee6-4d28-4163-a35c2ca8ec00	building-summer.jpg	2	cover	App\\Models\\DirectoryEntry	69	{"id":"5ace8ab2-8ee6-4d28-4163-a35c2ca8ec00","filename":"building-summer.jpg","uploaded":"2025-07-05T21:37:28.947Z","requireSignedURLs":false,"variants":["https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/5ace8ab2-8ee6-4d28-4163-a35c2ca8ec00\\/public"]}	\N	\N	\N	\N	2025-07-05 21:37:29	2025-07-05 21:37:29	2025-07-05 21:37:29
8	01eb7d9f-7528-4ae4-ac00-9c1ed3164500	348s.jpg	2	gallery	App\\Models\\DirectoryEntry	69	{"id":"01eb7d9f-7528-4ae4-ac00-9c1ed3164500","filename":"348s.jpg","uploaded":"2025-07-05T21:39:47.867Z","requireSignedURLs":false,"variants":["https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/01eb7d9f-7528-4ae4-ac00-9c1ed3164500\\/public"]}	\N	\N	\N	\N	2025-07-05 21:39:48	2025-07-05 21:39:48	2025-07-05 21:39:48
9	867d130e-08fe-4004-05aa-f7a2453a3200	caption.jpg	2	gallery	App\\Models\\DirectoryEntry	69	{"id":"867d130e-08fe-4004-05aa-f7a2453a3200","filename":"caption.jpg","uploaded":"2025-07-05T21:39:47.855Z","requireSignedURLs":false,"variants":["https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/867d130e-08fe-4004-05aa-f7a2453a3200\\/public"]}	\N	\N	\N	\N	2025-07-05 21:39:48	2025-07-05 21:39:48	2025-07-05 21:39:48
10	3236af5a-4ef5-43ef-6a7e-067eb13f2400	mogul2-banner.jpg	2	gallery	App\\Models\\DirectoryEntry	69	{"id":"3236af5a-4ef5-43ef-6a7e-067eb13f2400","filename":"mogul2-banner.jpg","uploaded":"2025-07-05T21:39:48.098Z","requireSignedURLs":false,"variants":["https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/3236af5a-4ef5-43ef-6a7e-067eb13f2400\\/public"]}	\N	\N	\N	\N	2025-07-05 21:39:49	2025-07-05 21:39:49	2025-07-05 21:39:49
11	3b6c3e06-cae5-4b98-7fb6-bb875b835800	dining-area.jpg	2	gallery	App\\Models\\DirectoryEntry	69	{"id":"3b6c3e06-cae5-4b98-7fb6-bb875b835800","filename":"dining-area.jpg","uploaded":"2025-07-05T21:39:48.234Z","requireSignedURLs":false,"variants":["https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/3b6c3e06-cae5-4b98-7fb6-bb875b835800\\/public"]}	\N	\N	\N	\N	2025-07-05 21:39:49	2025-07-05 21:39:49	2025-07-05 21:39:49
24	59a65937-7db6-4ad1-5e59-7dcc44a4a200	IMG_1799.jpeg	2	cover	App\\Models\\UserList	10	{"id":"59a65937-7db6-4ad1-5e59-7dcc44a4a200","filename":"IMG_1799.jpeg","uploaded":"2025-07-06T00:13:55.257Z","requireSignedURLs":false,"variants":["https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/59a65937-7db6-4ad1-5e59-7dcc44a4a200\\/public"]}	\N	\N	\N	\N	2025-07-06 00:13:56	2025-07-06 00:13:56	2025-07-06 00:13:56
25	14a7fda6-4811-49a7-3b57-a4ea2395d600	IMG_2303.jpeg	2	cover	App\\Models\\UserList	10	{"id":"14a7fda6-4811-49a7-3b57-a4ea2395d600","filename":"IMG_2303.jpeg","uploaded":"2025-07-06T00:13:55.267Z","requireSignedURLs":false,"variants":["https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/14a7fda6-4811-49a7-3b57-a4ea2395d600\\/public"]}	\N	\N	\N	\N	2025-07-06 00:13:56	2025-07-06 00:13:56	2025-07-06 00:13:56
33	9ec2cba4-529e-4703-af57-b769c5609d00	07-09-2021_SFA_HB_SW_I00010020.jpg	2	cover	App\\Models\\UserList	14	{"id":"9ec2cba4-529e-4703-af57-b769c5609d00","filename":"07-09-2021_SFA_HB_SW_I00010020.jpg","uploaded":"2025-07-06T00:36:59.717Z","requireSignedURLs":false,"variants":["https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/9ec2cba4-529e-4703-af57-b769c5609d00\\/public"]}	\N	\N	\N	\N	2025-07-06 00:37:00	2025-07-06 00:37:00	2025-07-06 05:37:25
34	9d10e557-5d31-43f4-7efb-4cc267042500	fav_07-09-2021_SFA_SC_SW_I00070037.jpg	2	cover	App\\Models\\UserList	14	{"id":"9d10e557-5d31-43f4-7efb-4cc267042500","filename":"fav_07-09-2021_SFA_SC_SW_I00070037.jpg","uploaded":"2025-07-06T00:36:59.994Z","requireSignedURLs":false,"variants":["https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/9d10e557-5d31-43f4-7efb-4cc267042500\\/public"]}	\N	\N	\N	\N	2025-07-06 00:37:00	2025-07-06 00:37:00	2025-07-06 05:37:25
35	a62e4df8-0b2d-45f7-c390-a0710117e500	avatar.jpg	5	avatar	App\\Models\\User	5	{"type":"avatar","tracked_retroactively":true,"original_metadata":[]}	\N	\N	\N	\N	2025-07-04 19:40:56	2025-07-06 06:03:20	2025-07-06 06:03:20
36	0ad5ac52-2e93-495d-3e40-95cb2bfcf400	cover.jpg	5	cover	App\\Models\\User	5	{"type":"cover","tracked_retroactively":true,"original_metadata":[]}	\N	\N	\N	\N	2025-07-04 19:40:56	2025-07-06 06:03:21	2025-07-06 06:03:21
41	9f4de8bf-e04b-4e1d-5621-b5f5cb839100	cake_icon_light.png	2	logo	App\\Models\\User	2	{"type":"page_logo","uploaded_via":"profile_editor"}	72311	\N	\N	\N	2025-07-06 06:06:47	2025-07-06 06:06:47	2025-07-06 06:06:47
45	05e4929f-00ad-4231-5032-a719f82a2700	whale_geo.svg	2	cover	login_page	1	{"id":"05e4929f-00ad-4231-5032-a719f82a2700","filename":"whale_geo.svg","uploaded":"2025-07-28T21:36:34.991Z","requireSignedURLs":false,"variants":["https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/05e4929f-00ad-4231-5032-a719f82a2700\\/portrait","https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/05e4929f-00ad-4231-5032-a719f82a2700\\/mListCover","https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/05e4929f-00ad-4231-5032-a719f82a2700\\/widecover","https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/05e4929f-00ad-4231-5032-a719f82a2700\\/square","https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/05e4929f-00ad-4231-5032-a719f82a2700\\/thumbnail","https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/05e4929f-00ad-4231-5032-a719f82a2700\\/public","https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/05e4929f-00ad-4231-5032-a719f82a2700\\/lgformat","https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/05e4929f-00ad-4231-5032-a719f82a2700\\/cover"]}	\N	\N	\N	\N	2025-07-28 21:36:35	2025-07-28 21:36:35	2025-07-28 21:36:35
46	0c3689f7-eb8e-4b76-ae8c-f74b6041e600	Eric_sheishi_beachsm.jpg	2	avatar	App\\Models\\User	2	{"entity_id":2,"entity_type":"User","user_name":"Eric Larson","context":"avatar","id":"0c3689f7-eb8e-4b76-ae8c-f74b6041e600","filename":"Eric_sheishi_beachsm.jpg","uploaded":"2025-07-28T22:05:35.694Z","requireSignedURLs":false,"variants":["https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/0c3689f7-eb8e-4b76-ae8c-f74b6041e600\\/mListCover","https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/0c3689f7-eb8e-4b76-ae8c-f74b6041e600\\/widecover","https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/0c3689f7-eb8e-4b76-ae8c-f74b6041e600\\/cover","https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/0c3689f7-eb8e-4b76-ae8c-f74b6041e600\\/public","https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/0c3689f7-eb8e-4b76-ae8c-f74b6041e600\\/thumbnail","https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/0c3689f7-eb8e-4b76-ae8c-f74b6041e600\\/portrait","https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/0c3689f7-eb8e-4b76-ae8c-f74b6041e600\\/square","https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/0c3689f7-eb8e-4b76-ae8c-f74b6041e600\\/lgformat"]}	\N	\N	\N	\N	2025-07-28 22:05:40	2025-07-28 22:05:40	2025-07-28 22:05:40
47	32e0f547-29d3-472d-cf8d-38f9b7cea000	mountain_cover.jpg	2	cover	App\\Models\\User	2	{"entity_id":2,"entity_type":"User","user_name":"Eric Larson","context":"cover","id":"32e0f547-29d3-472d-cf8d-38f9b7cea000","filename":"mountain_cover.jpg","uploaded":"2025-07-28T22:06:35.284Z","requireSignedURLs":false,"variants":["https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/32e0f547-29d3-472d-cf8d-38f9b7cea000\\/portrait","https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/32e0f547-29d3-472d-cf8d-38f9b7cea000\\/mListCover","https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/32e0f547-29d3-472d-cf8d-38f9b7cea000\\/lgformat","https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/32e0f547-29d3-472d-cf8d-38f9b7cea000\\/public","https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/32e0f547-29d3-472d-cf8d-38f9b7cea000\\/square","https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/32e0f547-29d3-472d-cf8d-38f9b7cea000\\/cover","https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/32e0f547-29d3-472d-cf8d-38f9b7cea000\\/thumbnail","https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/32e0f547-29d3-472d-cf8d-38f9b7cea000\\/widecover"]}	\N	\N	\N	\N	2025-07-28 22:06:36	2025-07-28 22:06:36	2025-07-28 22:06:36
48	da4b7f07-3ba3-45e9-9e13-216c625b0500	eric_larson_sm.jpg	5	avatar	App\\Models\\User	5	{"entity_id":5,"entity_type":"User","user_name":"Luggie Riggatoni","context":"avatar","id":"da4b7f07-3ba3-45e9-9e13-216c625b0500","filename":"eric_larson_sm.jpg","uploaded":"2025-07-29T01:17:01.995Z","requireSignedURLs":false,"variants":["https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/da4b7f07-3ba3-45e9-9e13-216c625b0500\\/widecover","https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/da4b7f07-3ba3-45e9-9e13-216c625b0500\\/portrait","https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/da4b7f07-3ba3-45e9-9e13-216c625b0500\\/mListCover","https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/da4b7f07-3ba3-45e9-9e13-216c625b0500\\/thumbnail","https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/da4b7f07-3ba3-45e9-9e13-216c625b0500\\/cover","https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/da4b7f07-3ba3-45e9-9e13-216c625b0500\\/public","https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/da4b7f07-3ba3-45e9-9e13-216c625b0500\\/lgformat","https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/da4b7f07-3ba3-45e9-9e13-216c625b0500\\/square"]}	\N	\N	\N	\N	2025-07-29 01:17:08	2025-07-29 01:17:08	2025-07-29 01:17:08
49	640efb16-720c-430c-7959-2b6665c57b00	IMG_5770.jpg	5	cover	App\\Models\\User	5	{"entity_id":5,"entity_type":"User","user_name":"Luggie Riggatoni","context":"cover","id":"640efb16-720c-430c-7959-2b6665c57b00","filename":"IMG_5770.jpg","uploaded":"2025-07-29T01:17:24.456Z","requireSignedURLs":false,"variants":["https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/640efb16-720c-430c-7959-2b6665c57b00\\/cover","https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/640efb16-720c-430c-7959-2b6665c57b00\\/mListCover","https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/640efb16-720c-430c-7959-2b6665c57b00\\/portrait","https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/640efb16-720c-430c-7959-2b6665c57b00\\/widecover","https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/640efb16-720c-430c-7959-2b6665c57b00\\/square","https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/640efb16-720c-430c-7959-2b6665c57b00\\/lgformat","https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/640efb16-720c-430c-7959-2b6665c57b00\\/thumbnail","https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/640efb16-720c-430c-7959-2b6665c57b00\\/public"]}	\N	\N	\N	\N	2025-07-29 01:17:29	2025-07-29 01:17:29	2025-07-29 01:17:29
\.


--
-- Data for Name: comments; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.comments (id, created_at, updated_at, user_id, commentable_type, commentable_id, content, parent_id, replies_count, likes_count, mentions, deleted_at) FROM stdin;
\.


--
-- Data for Name: directory_entries; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.directory_entries (id, title, slug, description, type, category_id, region_id, tags, owner_user_id, created_by_user_id, updated_by_user_id, phone, email, website_url, social_links, featured_image, gallery_images, status, is_featured, is_verified, is_claimed, meta_title, meta_description, structured_data, view_count, list_count, created_at, updated_at, published_at, logo_url, cover_image_url, facebook_url, instagram_handle, twitter_handle, youtube_channel, messenger_contact, price_range, takes_reservations, accepts_credit_cards, wifi_available, pet_friendly, parking_options, wheelchair_accessible, outdoor_seating, kid_friendly, video_urls, pdf_files, hours_of_operation, special_hours, temporarily_closed, open_24_7, cross_streets, neighborhood, state_region_id, city_region_id, neighborhood_region_id, regions_updated_at, links, rejection_reason, rejected_at, rejected_by, approval_notes, approved_by, subscription_tier, subscription_expires_at, stripe_customer_id, stripe_subscription_id, claimed_at, ownership_transferred_at, ownership_transferred_by, likes_count, comments_count) FROM stdin;
2	TechHub Electronics	techhub-electronics	Your one-stop shop for all electronics needs. Best prices guaranteed!	business_b2c	9	\N	["walk-in","appointment"]	\N	1	\N	(555) 987-6543	support@techhub.com	https://techhub.com	{"facebook":"https:\\/\\/facebook.com\\/techhub-electronics","instagram":"https:\\/\\/instagram.com\\/techhub-electronics"}	\N	\N	published	t	f	f	\N	\N	\N	0	0	2025-06-01 20:18:34	2025-06-01 20:18:34	2025-06-01 20:18:34	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	free	\N	\N	\N	\N	\N	\N	0	0
3	Serenity Spa & Wellness	serenity-spa-wellness	Relax and rejuvenate with our premium spa services.	business_b2c	14	\N	["local","appointment"]	\N	1	\N	(555) 456-7890	relax@serenityspa.com	https://serenityspa.com	{"facebook":"https:\\/\\/facebook.com\\/serenity-spa-wellness","instagram":"https:\\/\\/instagram.com\\/serenity-spa-wellness"}	\N	\N	published	t	f	f	\N	\N	\N	0	0	2025-06-01 20:18:34	2025-06-01 20:18:34	2025-06-01 20:18:34	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	free	\N	\N	\N	\N	\N	\N	0	0
70	Roberto's Café	robertos-cafe	Casual Italian restaurant serving wood-fired pizzas and homemade pasta.	business_b2c	1	\N	["italian","pizza","pasta","cafe"]	\N	2	\N	760.934.3667	info@robertoscafe.com	https://robertoscafe.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-06-29 03:06:50	2025-06-29 03:06:50	http://localhost:8000/storage/directory-entries/logos/tu6a7C6e3328SWmaE4KzC4LHv2b1Fu0AOEu06ZQ5.webp	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	free	\N	\N	\N	\N	\N	\N	0	0
88	Majestic Fine Art	majestic-fine-art	Fine art gallery featuring local artists.	business_b2c	7	\N	["art","gallery","shopping","local_artists"]	\N	2	\N	\N	info@majesticfineart.com	https://majesticfineart.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-06-29 03:06:50	2025-06-29 03:06:50	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	free	\N	\N	\N	\N	\N	\N	0	0
90	Mammoth Lakes Welcome Center	mammoth-lakes-welcome-center	Visitor center with local information and gift shop.	business_b2c	17	\N	["visitor_center","tourism","information","gifts"]	\N	2	\N	\N	info@mammothlakeswelcomecenter.com	https://mammothlakeswelcomecenter.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-06-29 03:06:50	2025-06-29 03:06:50	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	free	\N	\N	\N	\N	\N	\N	0	0
99	Mammoth Mountaineering Supply	mammoth-mountaineering-supply	<p>Ski Shop</p>	business_b2c	28	\N	\N	\N	2	\N	(760) 934-4191	dave@mammothgear.com	http://mammothgear.com	\N	\N	\N	published	f	f	f	\N	\N	\N	0	0	2025-07-05 19:03:22	2025-07-05 19:03:22	2025-07-05 19:03:22	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	free	\N	\N	\N	\N	\N	\N	0	0
6	Mobile Pet Grooming	mobile-pet-grooming	We come to you! Professional pet grooming at your doorstep.	service	16	1	["mobile","professional","licensed"]	\N	1	\N	(555) 321-9876	woof@mobilepetgrooming.com	https://mobilepetgrooming.com	{"facebook":"https:\\/\\/facebook.com\\/mobile-pet-grooming","instagram":"https:\\/\\/instagram.com\\/mobile-pet-grooming"}	\N	\N	published	f	t	f	\N	\N	\N	0	0	2025-06-01 20:18:34	2025-06-01 20:18:34	2025-06-01 20:18:34	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	1	\N	\N	2025-07-28 14:02:58	\N	\N	\N	\N	\N	\N	free	\N	\N	\N	\N	\N	\N	0	0
73	Sazon Restaurant	sazon-restaurant	Mexican and Latin American cuisine with tequila bar.	business_b2c	1	\N	["mexican","latin","tequila_bar","seafood"]	\N	2	\N	\N	info@sazonrestaurant.com	https://sazonrestaurant.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-06-29 03:06:50	2025-06-29 03:06:50	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	free	\N	\N	\N	\N	\N	\N	0	0
74	Skadi	skadi	Upscale fine dining with French-inspired cuisine.	business_b2c	1	\N	["fine_dining","french","seafood","steakhouse"]	\N	2	\N	\N	info@skadi.com	https://skadi.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-06-29 03:06:50	2025-06-29 03:06:50	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	free	\N	\N	\N	\N	\N	\N	0	0
75	Mammoth Brewing Company	mammoth-brewing-company	Craft brewery and pub with rotating beers and pub fare.	business_b2c	1	\N	["brewery","beer","brewpub","bar"]	\N	2	\N	\N	info@mammothbrewingcompany.com	https://mammothbrewingcompany.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-06-29 03:06:50	2025-06-29 03:06:50	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	free	\N	\N	\N	\N	\N	\N	0	0
76	Toomey's	toomeys	Upscale bar and restaurant with craft cocktails.	business_b2c	1	\N	["bar","restaurant","cocktails","upscale"]	\N	2	\N	\N	info@toomeys.com	https://toomeys.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-06-29 03:06:50	2025-06-29 03:06:50	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	free	\N	\N	\N	\N	\N	\N	0	0
78	Gabriella's Italian Ristorante	gabriellas-italian-ristorante	Family-owned Italian restaurant serving classic dishes.	business_b2c	1	\N	["italian","fine_dining","family_friendly"]	\N	2	\N	\N	info@gabriellasitalianristorante.com	https://gabriellasitalianristorante.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-06-29 03:06:50	2025-06-29 03:06:50	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	free	\N	\N	\N	\N	\N	\N	0	0
71	Good Life Café	good-life-cafe	Cozy café offering breakfast, brunch, and specialty coffee.	business_b2c	5	\N	["cafe","breakfast","brunch","coffee"]	\N	2	2	\N	info@goodlifecafe.com	https://goodlifecafe.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-07-05 19:17:39	2025-06-29 03:06:50	\N	https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A/38672e5c-71b8-4616-0542-775684bee600/public	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	free	\N	\N	\N	\N	\N	\N	0	0
79	Jax at Mammoth	jax-at-mammoth	Women's apparel, accessories and gifts.	business_b2c	7	\N	["retail","clothing","womens_fashion"]	\N	2	\N	\N	info@jaxatmammoth.com	https://jaxatmammoth.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-06-29 03:06:50	2025-06-29 03:06:50	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	free	\N	\N	\N	\N	\N	\N	0	0
80	Franz Klammer Sports	franz-klammer-sports	Ski and snowboard equipment rental and retail.	business_b2c	7	\N	["retail","ski_rental","snowboard","gear"]	\N	2	\N	\N	info@franzklammersports.com	https://franzklammersports.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-06-29 03:06:50	2025-06-29 03:06:50	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	free	\N	\N	\N	\N	\N	\N	0	0
81	Mammoth Mountain Ski & Board	mammoth-mountain-ski-board	Ski and snowboard rentals plus retail shop.	business_b2c	7	\N	["rental","ski","snowboard","gear"]	\N	2	\N	\N	info@mammothmountainski&board.com	https://mammothmountainski&board.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-06-29 03:06:50	2025-06-29 03:06:50	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	free	\N	\N	\N	\N	\N	\N	0	0
82	Volcom Store	volcom-store	Skate and snowboard apparel and accessories.	business_b2c	7	\N	["retail","apparel","skate","snowboard"]	\N	2	\N	\N	info@volcomstore.com	https://volcomstore.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-06-29 03:06:50	2025-06-29 03:06:50	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	free	\N	\N	\N	\N	\N	\N	0	0
84	Denali Surf & Sport	denali-surf-sport	Surf, skate, and snow gear.	business_b2c	7	\N	["retail","skate","board","surf"]	\N	2	\N	\N	info@denalisurf&sport.com	https://denalisurf&sport.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-06-29 03:06:50	2025-06-29 03:06:50	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	free	\N	\N	\N	\N	\N	\N	0	0
85	The Powder House & Gear	the-powder-house-gear	Ski, snowboard rentals and outerwear retail.	business_b2c	7	\N	["retail","equipment_rental","ski","snowboard"]	\N	2	\N	\N	info@thepowderhouse&gear.com	https://thepowderhouse&gear.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-06-29 03:06:50	2025-06-29 03:06:50	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	free	\N	\N	\N	\N	\N	\N	0	0
86	Mammoth Camper & RV	mammoth-camper-rv	Camper rental and outdoor gear.	business_b2c	7	\N	["rental","camping","rv","gear"]	\N	2	\N	\N	info@mammothcamper&rv.com	https://mammothcamper&rv.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-06-29 03:06:50	2025-06-29 03:06:50	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	free	\N	\N	\N	\N	\N	\N	0	0
87	Mountain Shop	mountain-shop	Outdoor clothing and equipment retailer.	business_b2c	7	\N	["retail","outdoor","clothing","equipment"]	\N	2	\N	\N	info@mountainshop.com	https://mountainshop.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-06-29 03:06:50	2025-06-29 03:06:50	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	free	\N	\N	\N	\N	\N	\N	0	0
91	Canyon Cinema	canyon-cinema	Local movie theater showing new releases and classics.	business_b2c	17	\N	["cinema","movies","entertainment","family"]	\N	2	\N	\N	info@canyoncinema.com	https://canyoncinema.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-06-29 03:06:50	2025-06-29 03:06:50	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	free	\N	\N	\N	\N	\N	\N	0	0
92	Mammoth Lakes Ice Rink	mammoth-lakes-ice-rink	Outdoor ice skating rink open seasonally.	business_b2c	17	\N	["ice_skating","rink","seasonal","entertainment"]	\N	2	\N	\N	info@mammothlakesicerink.com	https://mammothlakesicerink.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-06-29 03:06:50	2025-06-29 03:06:50	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	free	\N	\N	\N	\N	\N	\N	0	0
94	Mammoth Lakes Film Festival	mammoth-lakes-film-festival	Annual film festival showcasing independent films.	business_b2c	17	\N	["festival","movies","arts","entertainment"]	\N	2	\N	\N	info@mammothlakesfilmfestival.com	https://mammothlakesfilmfestival.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-06-29 03:06:50	2025-06-29 03:06:50	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	free	\N	\N	\N	\N	\N	\N	0	0
96	Epic Discovery	epic-discovery	Adventure park with zip lines, climbing garden, and mountain coaster.	business_b2c	17	\N	["adventure","zip_line","coaster","climbing"]	\N	2	\N	\N	info@epicdiscovery.com	https://epicdiscovery.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-06-29 03:06:50	2025-06-29 03:06:50	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	free	\N	\N	\N	\N	\N	\N	0	0
93	Sierra Star Golf Course	sierra-star-golf-course	Resort golf course with mountain views.	business_b2c	19	\N	["golf","sports","recreation","entertainment"]	\N	2	2	\N	info@sierrastargolfcourse.com	https://sierrastargolfcourse.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-07-03 00:25:17	2025-06-29 03:06:50	\N	https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A/5479c8b9-82de-466d-eaae-190ac302ea00/public	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	free	\N	\N	\N	\N	\N	\N	0	0
83	Sierra Runner	sierra-runner	Footwear and sporting goods store.	business_b2c	28	\N	["retail","footwear","sports","run"]	\N	2	2	\N	info@sierrarunner.com	https://sierrarunner.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-07-05 02:53:33	2025-06-29 03:06:50	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	free	\N	\N	\N	\N	\N	\N	0	0
97	Woolly's Tube Park	woollys-tube-park	Snow tubing park with multiple lanes and lifts.	business_b2c	17	\N	["tubing","winter_sports","entertainment"]	\N	2	\N	\N	info@woollystubepark.com	https://woollystubepark.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-06-29 03:06:50	2025-06-29 03:06:50	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	free	\N	\N	\N	\N	\N	\N	0	0
89	Mammoth Mountain Scenic Gondola	mammoth-mountain-scenic-gondola	Scenic gondola ride to Eagle Lodge with panoramic views.	business_b2c	21	\N	["gondola","scenic","ride","views"]	\N	2	2	\N	info@mammothmountainscenicgondola.com	https://mammothmountainscenicgondola.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-06-29 03:44:16	2025-06-29 03:06:50	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	free	\N	\N	\N	\N	\N	\N	0	0
72	Burgers Restaurant	burgers-restaurant	Gourmet burgers, fries, and milkshakes in a casual setting.	business_b2c	5	\N	["burgers","american","casual","diner"]	\N	2	2	\N	info@burgersrestaurant.com	https://burgersrestaurant.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-06-30 05:45:50	2025-06-29 03:06:50	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	free	\N	\N	\N	\N	\N	\N	0	0
95	Mammoth Brewing Co. Live Stage	mammoth-brewing-co-live-stage	<p>Live music venue inside Mammoth Brewing Company.</p><p><strong><em>Mammoth Brewing Co.:</em></strong><br>Sunday – Thursday: 10am – 9pm*<br>Friday &amp; Saturday: 10am – 9:30pm*</p><p><strong><em>The EATery:</em></strong><br>Daily: 11:00am – Close</p><p>*Closing times are subject to early closures during shoulder seasons</p><p><strong>Reservations:</strong> We do not take reservations.</p><p></p><h2><strong>Welcome to MBC &amp; The EATery<br></strong></h2><p>We’ve been obsessively crafting beers since 1995. We pioneer, curious and unfaltering, determined not to stop until we achieve mouth-watering perfection, often at the expense of reasonable bedtimes and our social lives. Pulling inspiration from our natural surroundings, we boldly blend the best of our local ingredients with the know-how we’ve picked up from years of brewing award-winning beer.</p><p>Our brewery, tasting room, beer garden, and retail store are located at 18 Lake Mary Road, at the corner of Main Street &amp; Minaret Road in Mammoth Lakes. The EATery is located in the tasting room, supplying amazing beer-centric food by chef Brandon Brocia. Check out The EATery menu here. Savor a pint and a bite to eat, sample a tasting flight, pick up a 6-pack, fill your growler, and enjoy the mountain views and friendly atmosphere.</p><p>Your well-behaved, leashed pupper is welcome in our outdoor beer garden. No barking, biting, or begging, and don't leave your trail buddy unattended.</p><p>Fun events include live music and Trivia Night, and we also host private events. See our Events page for more information.</p>	business_b2c	5	\N	["live_music","venue","concerts","brewery"]	\N	2	2	\N	info@mammothbrewingcolivestage.com	https://mammothbrewingco.com/	\N	\N	["http:\\/\\/localhost:8000\\/storage\\/directory-entries\\/gallerys\\/lXui2uPooEfFvkR0unMgRmwF6fm4nG5Pq9yfcqTD.jpg","http:\\/\\/localhost:8000\\/storage\\/directory-entries\\/gallerys\\/T8YgxkYcUrSK6Kzaq2PJMKNVFIYBoxUCGib8i15x.jpg"]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-06-30 06:13:53	2025-06-29 03:06:50	http://localhost:8000/storage/directory-entries/logos/LvIENSxqBs2eeg3lI0wiTjoNFQA7J2TUGZ7SZXEq.webp	http://localhost:8000/storage/directory-entries/covers/gdKhU1dJWT6uRIxFnzG5MnzVPG7lWsUPu3O9k0FC.jpg	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	free	\N	\N	\N	\N	\N	\N	0	0
98	Mammoth Lakes Museum	mammoth-lakes-museum	<h1>Our Hours</h1><p><strong>June-september: </strong>10 AM to 6 PM</p><p><strong>Open Monday-Sunday, closed on Wednesdays</strong></p><p><strong>We are open six days a week Memorial Weekend through the end of September.</strong></p><hr><h1>Other Information</h1><p><strong>Fees</strong>: FREE! A $5.00 donation is suggested &amp; memberships are appreciated.</p><p><strong>ADA Accessibility:&nbsp;</strong>Access is available for those who park on the lawn near the disabled placard and enter through the kitchen (back door) on the ramp.</p><p><strong>Eco-Friendly:&nbsp;</strong>Connected to the town’s bike trails in Mammoth Lakes.</p><p><strong>Pet Friendly: &nbsp;</strong>Pets should be leashed on the museum grounds per the Town of Mammoth Lakes and the U.S. Forest Service leash laws. Service animals only are welcome inside the museum. We provide kennels a.k.a “doggie dorms” outside for non-service animals. <strong>Please clean up after your pets!</strong></p>	business_b2c	21	\N	["museum","history","culture","education"]	\N	2	2	(760) 934-6918	info@mammothlakesmuseum.org	https://www.mammothmuseum.org	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-06-30 06:16:18	2025-06-29 03:06:50	http://localhost:8000/storage/directory-entries/logos/R2eyXXEJllqFOz39eFE4frXx2enDrLjHtzWaDUcY.jpg	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	free	\N	\N	\N	\N	\N	\N	0	0
77	Smokeyard BBQ	smokeyard-bbq	<h3>Steakhouse fare, with South African influence in a casual, chic atmosphere.&nbsp; The perfectly crafted menu of high quality food and tasty cocktails makes Smokeyard Bbq and Chop Shop a must-have destination in the heart of the Village at Mammoth.</h3><p></p><h3><strong>We have a handful of available diner reservations available- we have tables available between 4PM and 5:15PM and from 7:45 to 9:30PM.</strong></h3>	business_b2c	5	\N	["barbecue","bbq","smoked_meats","southern"]	\N	2	2	760 934 3300	info@smokeyard.com	https://www.smokeyard.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-07-04 23:16:56	2025-06-29 03:06:50	https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A/33a56bd9-766a-4cc8-24b2-b862acf1e400/public	https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A/64c5191b-22b9-4f42-20ec-2f3d4fc43d00/public	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	free	\N	\N	\N	\N	\N	\N	0	0
69	The Mogul Restaurant	the-mogul-restaurant	<p>Historic mountain lodge restaurant offering American cuisine.</p><p><strong>Open 7 days each week.<br>Closed Tuesdays May 6, 13, 20th<br>Open at 5:00pm in the Bar and 5:30pm for dinner.<br><br>Kid's Menu Available * Full Bar<br><br>Don't know where we are? Check our Location<br><br>Reservations Accepted: 760-934-3039</strong></p><p></p>	business_b2c	5	\N	["restaurant","american","dinner","family_friendly"]	\N	2	2	760-934-3039	Carey@TheMogul.com	https://themogul.com	\N	\N	["https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/01eb7d9f-7528-4ae4-ac00-9c1ed3164500\\/public","https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/867d130e-08fe-4004-05aa-f7a2453a3200\\/public","https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/3236af5a-4ef5-43ef-6a7e-067eb13f2400\\/public","https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/3b6c3e06-cae5-4b98-7fb6-bb875b835800\\/public"]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-07-05 21:40:03	2025-06-29 03:06:50	https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A/3deaecf0-7aed-43b6-c744-ca0a6ad08200/public	https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A/5ace8ab2-8ee6-4d28-4163-a35c2ca8ec00/public	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	free	\N	\N	\N	\N	\N	\N	0	0
8	LayerCake Marketing	layercake-marketing	Digital Solutions. We make web, mobile, social and marketing solutions that accelerate and grow your business. From conception to perfection, we craft each one of our digital products by hand.	online	16	\N	[]	\N	2	2	714-261-0903	eric@layercakemarketing.com	https://layercake.marketing	[]	\N	["http:\\/\\/localhost:8000\\/storage\\/directory-entries\\/gallerys\\/cw7cWYWZaU2jzej8t94dj6yNdTLG12sI8EtzDdY3.jpg","http:\\/\\/localhost:8000\\/storage\\/directory-entries\\/gallerys\\/xk7Sdg9fZ0sllt3TqC6SdIvTyBBhjBWTm5BhzBZ0.png"]	published	f	f	f	\N	\N	\N	0	0	2025-06-01 22:44:55	2025-06-29 00:50:21	\N	http://localhost:8000/storage/directory-entries/logos/a59072tzOZFkjWXKFG8GWv25dbSrWNhqYOa8iSz8.png	http://localhost:8000/storage/directory-entries/covers/rdRtO40X9QZxabjFYc1zc0cZnEHCAEhNzw735Oht.png	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	free	\N	\N	\N	\N	\N	\N	0	0
4	CloudTech Solutions	cloudtech-solutions	Enterprise cloud computing solutions for modern businesses.	online	26	1	["digital","worldwide"]	\N	1	\N	(800) 555-2468	contact@cloudtechsolutions.com	https://cloudtechsolutions.com	{"facebook":"https:\\/\\/facebook.com\\/cloudtech-solutions","instagram":"https:\\/\\/instagram.com\\/cloudtech-solutions"}	\N	\N	published	t	t	f	\N	\N	\N	0	0	2025-06-01 20:18:34	2025-06-01 20:18:34	2025-06-01 20:18:34	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	1	\N	\N	2025-07-28 14:02:58	\N	\N	\N	\N	\N	\N	free	\N	\N	\N	\N	\N	\N	0	0
5	LearnPro Online Academy	learnpro-online-academy	Professional development courses and certifications online.	online	25	1	["digital","remote"]	\N	1	\N	\N	info@learnpro.edu	https://learnpro.edu	{"facebook":"https:\\/\\/facebook.com\\/learnpro-online-academy","instagram":"https:\\/\\/instagram.com\\/learnpro-online-academy"}	\N	\N	published	t	f	f	\N	\N	\N	0	0	2025-06-01 20:18:34	2025-06-01 20:18:34	2025-06-01 20:18:34	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	1	\N	\N	2025-07-28 14:02:58	\N	\N	\N	\N	\N	\N	free	\N	\N	\N	\N	\N	\N	0	0
\.


--
-- Data for Name: directory_entry_follows; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.directory_entry_follows (id, user_id, directory_entry_id, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: failed_jobs; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.failed_jobs (id, uuid, connection, queue, payload, exception, failed_at) FROM stdin;
\.


--
-- Data for Name: follows; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.follows (id, follower_id, followable_id, followable_type, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: home_page_settings; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.home_page_settings (id, hero_title, hero_subtitle, hero_image_path, cta_text, cta_link, featured_places, testimonials, custom_scripts, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: image_uploads; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.image_uploads (id, user_id, type, entity_id, temp_path, original_name, file_size, mime_type, status, cloudflare_id, image_record_id, error_message, completed_at, created_at, updated_at) FROM stdin;
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
-- Data for Name: likes; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.likes (id, user_id, likeable_type, likeable_id, created_at, updated_at) FROM stdin;
1	2	App\\Models\\Post	1	2025-07-29 00:27:09	2025-07-29 00:27:09
2	5	App\\Models\\Post	1	2025-07-29 00:28:44	2025-07-29 00:28:44
3	2	App\\Models\\Post	2	2025-07-29 01:18:44	2025-07-29 01:18:44
4	5	App\\Models\\Post	2	2025-07-29 01:38:36	2025-07-29 01:38:36
\.


--
-- Data for Name: list_categories; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.list_categories (id, name, slug, description, color, is_active, sort_order, created_at, updated_at, svg_icon, cover_image_cloudflare_id, cover_image_url, quotes) FROM stdin;
1	Travel & Places	travel-places	\N	#3B82F6	t	1	2025-07-04 02:36:29	2025-07-04 02:36:29	\N	\N	\N	\N
2	Food & Restaurants	food-restaurants	\N	#EF4444	t	2	2025-07-04 02:36:29	2025-07-04 02:36:29	\N	\N	\N	\N
3	Entertainment	entertainment	\N	#8B5CF6	t	3	2025-07-04 02:36:29	2025-07-04 02:36:29	\N	\N	\N	\N
4	Shopping	shopping	\N	#F59E0B	t	4	2025-07-04 02:36:29	2025-07-04 02:36:29	\N	\N	\N	\N
5	Fitness	fitness	\N	#3B82F6	t	0	2025-07-04 15:25:53	2025-07-04 15:25:53	\N	\N	\N	\N
6	Workout	workout	\N	#3B82F6	t	0	2025-07-04 15:26:05	2025-07-04 15:26:05	\N	\N	\N	\N
7	Recipe	recipe	\N	#ff9200	t	0	2025-07-04 15:26:28	2025-07-04 15:26:28	\N	\N	\N	\N
8	Automotive	automotive	\N	#3B82F6	t	0	2025-07-05 18:55:17	2025-07-05 18:55:17	\N	\N	\N	\N
9	How To	how-to	\N	#3B82F6	t	0	2025-07-06 06:00:56	2025-07-06 06:00:56	\N	\N	\N	\N
10	Test List Category	test-list-category-svg	A test list category with SVG and quotes	#FF6B6B	t	1	2025-07-06 07:36:43	2025-07-06 07:36:43	<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>	\N	\N	["Lists help organize our thoughts and actions.","A goal without a plan is just a wish.","Success is the sum of small efforts repeated daily."]
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
-- Data for Name: list_items; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.list_items (id, list_id, directory_entry_id, order_index, notes, affiliate_url, custom_data, created_at, updated_at, type, title, content, data, image, item_image_cloudflare_id) FROM stdin;
44	9	\N	6	\N	\N	\N	2025-07-02 16:27:10	2025-07-02 16:27:10	text	Instructions Overview	\N	\N	\N	\N
45	9	\N	7	\N	\N	\N	2025-07-02 16:27:27	2025-07-02 16:27:27	text	1. Make Dough:	Mix flour, sugar, salt, and yeast. Add water and olive oil. Knead 8–10 mins.\nLet rise 1–1.5 hours until doubled.	\N	\N	\N
46	9	\N	8	\N	\N	\N	2025-07-02 16:27:51	2025-07-02 16:27:51	text	2. Make Sauce:	Sauté garlic in olive oil, add tomatoes and spices. Simmer for 20 mins. Blend slightly if desired.	\N	\N	\N
47	9	\N	9	\N	\N	\N	2025-07-02 16:28:23	2025-07-02 16:28:23	text	3. Assemble Pizza:	Preheat oven to 500°F (or as hot as possible), ideally with a pizza stone.\nStretch dough thin. Add sauce, cheese, toppings.\nBake 7–10 mins until crust is golden and cheese is bubbly.	\N	\N	\N
48	9	\N	10	\N	\N	\N	2025-07-02 16:28:41	2025-07-02 16:28:41	text	4. Top & Serve:	Add finishing touches. Slice and enjoy immediately.	\N	\N	\N
9	2	\N	0	\N	\N	\N	2025-06-02 04:59:09	2025-06-30 06:38:30	text	1. Suspension	\N	[]	\N	\N
4	2	\N	1	\N	\N	\N	2025-06-02 04:54:59	2025-06-30 06:38:30	text	2. Insulation	Keep the van cool in the summer and warm in those cold winter months.	[]	\N	\N
6	2	\N	2	\N	\N	\N	2025-06-02 04:57:30	2025-06-30 06:38:30	text	3. Plumbing	Kitchen and bath are many times an essential.	\N	\N	\N
7	2	\N	4	\N	\N	\N	2025-06-02 04:58:53	2025-06-30 06:38:30	text	4. Bed	\N	\N	\N	\N
8	2	\N	5	\N	\N	\N	2025-06-02 04:59:01	2025-06-30 06:38:30	text	5. Garage	\N	\N	\N	\N
5	2	\N	3	\N	\N	\N	2025-06-02 04:56:45	2025-06-30 06:39:08	text	4. Power & Electrical	Inverter\nOff-grid power generation	[]	\N	\N
38	9	\N	0	\N	\N	\N	2025-07-02 16:24:39	2025-07-02 16:24:39	text	Pizza Dough (Makes 2 medium pizzas)	Ingredients:\n3 ½ cups all-purpose flour (or 00 flour for chewier crust)\n1 tsp sugar\n2 ¼ tsp active dry yeast (1 packet)\n2 tsp salt\n1 ¼ cups warm water (110°F)\n1 tbsp olive oil	\N	\N	\N
39	9	\N	1	\N	\N	\N	2025-07-02 16:25:05	2025-07-02 16:25:05	text	Tomato Sauce	Ingredients:\n1 can (14 oz) San Marzano tomatoes\n2 cloves garlic, minced\n1 tbsp olive oil\n1 tsp dried oregano\n½ tsp red pepper flakes (optional)\nSalt and pepper to taste	\N	\N	\N
40	9	\N	2	\N	\N	\N	2025-07-02 16:25:22	2025-07-02 16:25:22	text	Cheeses	Pick 2–3 for depth of flavor:\nFresh mozzarella (sliced or torn)\nAged provolone (shredded)\nParmesan or Pecorino Romano (grated)\nGoat cheese (crumbled)\nSmoked gouda (shredded – adds a savory twist)	\N	\N	\N
41	9	\N	3	\N	\N	\N	2025-07-02 16:26:02	2025-07-02 16:26:02	text	Interesting Toppings (Choose a combo or mix + match):	\N	\N	\N	\N
42	9	\N	4	\N	\N	\N	2025-07-02 16:26:15	2025-07-02 16:26:15	text	Proteins:	Prosciutto\nHot honey-drizzled soppressata\nSmoked pulled chicken\nAnchovies (for bold flavor)	\N	\N	\N
43	9	\N	5	\N	\N	\N	2025-07-02 16:26:47	2025-07-02 16:26:47	text	Vegetables:	Roasted garlic cloves\nCharred corn kernels\nArtichoke hearts\nWild mushrooms (shiitake, oyster, or cremini)\nThinly sliced red onion or shallots\nPickled jalapeños	\N	\N	\N
50	10	\N	1	\N	\N	\N	2025-07-02 23:59:16	2025-07-04 05:36:48	text	Stroll or Jog Around North & South Lake	Enjoy peaceful lakeside trails, wooden bridges, ducks, turtles, and well-kept landscaping—a classic Woodbridge experience .	[]	\N	\N
51	10	\N	2	\N	\N	\N	2025-07-02 23:59:26	2025-07-04 05:36:55	text	Rent Paddle Boats, Canoes & Kayaks	Head to the beach-style lagoons next to each lake. Rentals include pedal boats, kayaks, canoes, and hydro-bikes—fun family activity	[]	\N	\N
52	10	\N	3	\N	\N	\N	2025-07-02 23:59:46	2025-07-04 05:37:02	text	Relax at the Lake Beach Clubs	Each lake has a lagoon with sand, shade areas, docks, and snack stands. Ideal summer hangouts, especially near South Lake with its snack shop	[]	\N	\N
53	10	\N	4	\N	\N	\N	2025-07-03 00:00:17	2025-07-04 05:37:20	text	Splash at Community Pools with Diving Board	Woodbridge offers 22 pools and 13 wader pools across the community. Stone Creek and Blue Lake feature full lifeguard coverage and diving boards	[]	\N	\N
54	10	\N	5	\N	\N	\N	2025-07-03 00:00:27	2025-07-04 05:37:44	text	Explore Woodbridge Village Center	A lakeside shopping and dining hub with AMC theater, yoga studios, cafés, and seasonal community events—all with scenic lake views	[]	\N	\N
55	10	\N	6	\N	\N	\N	2025-07-03 00:00:45	2025-07-04 05:37:51	text	Bike or Walk the San Diego Creek Trail	Start near Woodbridge and connect to miles of quiet bike paths that link to the Great Park and beyond	[]	\N	\N
56	10	\N	7	\N	\N	\N	2025-07-03 00:00:54	2025-07-04 05:37:57	text	Play Volleyball at Lakeside Courts	Sand volleyball courts are situated near the beach clubs—great for casual games or watching local tournaments	[]	\N	\N
57	10	\N	8	\N	\N	\N	2025-07-03 00:01:35	2025-07-04 05:38:10	text	Enjoy Nearby Parks	Heritage Community Park: playgrounds, basketball courts, and a scenic pond \n\nWilliam R. Mason Park: large green expanse, lake, walking and bike trails, bird watching	[]	\N	\N
58	10	\N	9	\N	\N	\N	2025-07-03 00:01:44	2025-07-04 05:38:18	text	Connect with Community Events	The Woodbridge Village Association hosts seasonal gatherings—4th of July fireworks, parades, dance shows, outdoor yoga, and more	[]	\N	\N
49	10	\N	0	\N	\N	\N	2025-07-02 23:58:55	2025-07-04 19:36:55	text	Play Tennis or Pickleball at Woodbridge Tennis Club	With 24 courts (many lit for night play) and both drop-in and league options, it’s a hub for casual players and competitive types alike. Plus, youth camps and adult clinics make it great all around	[]	\N	fe2ec3d0-2469-4cfe-f0a3-f5a3e8595000
\.


--
-- Data for Name: list_media; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.list_media (id, list_id, type, url, caption, order_index, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: lists; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.lists (id, user_id, name, description, created_at, updated_at, featured_image, slug, view_count, settings, is_featured, featured_image_cloudflare_id, category_id, visibility, is_draft, published_at, scheduled_for, gallery_images, is_pinned, pinned_at, status, status_reason, status_changed_at, status_changed_by, type, is_region_specific, region_id, is_category_specific, place_ids, order_index, channel_id, owner_type, owner_id, likes_count, comments_count, reposts_count) FROM stdin;
7	2	Fun Day at the Park List	\N	2025-06-27 05:50:56	2025-07-04 14:37:10	\N	fun-day-at-the-park-list	5	\N	f	\N	1	public	f	\N	\N	\N	f	\N	active	\N	\N	\N	user	f	\N	f	\N	0	\N	App\\Models\\User	2	0	0	0
2	2	Van Build Essentials	If you’re thinking about living in a van and looking to build out your sprinter van here is a handy list of items to consider.	2025-06-02 04:51:35	2025-07-04 06:02:46	\N	van-build-essentials	4	\N	f	\N	\N	public	f	\N	\N	\N	f	\N	active	\N	\N	\N	user	f	\N	f	\N	0	\N	App\\Models\\User	2	0	0	0
10	2	The Top 10 things to do in Woodbridge, (Irvine, California) neighborhood.	Here are the top 10 things to do in Woodbridge, Irvine, CA—perfect for enjoying lakeside fun, sports, local eats, and scenic strolls 🌿:	2025-07-02 23:58:37	2025-07-06 00:14:16	https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A/59a65937-7db6-4ad1-5e59-7dcc44a4a200/public	the-top-10-things-to-do-in-woodbridge-irvine-california-neighborhood	24	\N	f	59a65937-7db6-4ad1-5e59-7dcc44a4a200	1	public	f	\N	\N	[{"id":"59a65937-7db6-4ad1-5e59-7dcc44a4a200","url":"https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/59a65937-7db6-4ad1-5e59-7dcc44a4a200\\/public","filename":"IMG_1799.jpeg"},{"id":"14a7fda6-4811-49a7-3b57-a4ea2395d600","url":"https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/14a7fda6-4811-49a7-3b57-a4ea2395d600\\/public","filename":"IMG_2303.jpeg"}]	f	\N	active	\N	\N	\N	user	f	\N	f	\N	0	\N	App\\Models\\User	2	0	0	0
14	2	White Water Rafting the American River	\N	2025-07-06 00:37:28	2025-07-06 00:37:28	https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A/9ec2cba4-529e-4703-af57-b769c5609d00/public	white-water-rafting-the-american-river	0	\N	f	9ec2cba4-529e-4703-af57-b769c5609d00	1	public	f	2025-07-06 00:37:28	\N	[{"id":"9ec2cba4-529e-4703-af57-b769c5609d00","url":"https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/9ec2cba4-529e-4703-af57-b769c5609d00\\/public","filename":"07-09-2021_SFA_HB_SW_I00010020.jpg"},{"id":"9d10e557-5d31-43f4-7efb-4cc267042500","url":"https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/9d10e557-5d31-43f4-7efb-4cc267042500\\/public","filename":"fav_07-09-2021_SFA_SC_SW_I00070037.jpg"}]	f	\N	active	\N	\N	\N	user	f	\N	f	\N	0	\N	App\\Models\\User	2	0	0	0
9	5	Luiggi's Famous Artisan Pizza Recipe	\N	2025-07-02 16:23:53	2025-07-06 06:25:46	https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A/b02d3919-c6b6-40a9-9f7a-864c1f13ac00/public	luiggis-famous-artisan-pizza-recipe	19	\N	f	\N	7	public	f	\N	2025-07-08 08:15:00	\N	f	\N	active	\N	\N	\N	user	f	\N	f	\N	0	\N	App\\Models\\User	5	0	0	0
\.


--
-- Data for Name: local_page_settings; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.local_page_settings (id, region_id, page_type, title, intro_text, meta_description, featured_lists, featured_places, content_sections, settings, is_active, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: locations; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.locations (id, directory_entry_id, address_line1, address_line2, city, state, zip_code, country, latitude, longitude, hours_of_operation, holiday_hours, is_wheelchair_accessible, has_parking, amenities, place_id, created_at, updated_at, geom, cross_streets, neighborhood) FROM stdin;
2	2	456 Technology Blvd	\N	San Francisco	CA	94105	USA	37.7749000	-122.4194000	{"monday":"10:00-20:00","tuesday":"10:00-20:00","wednesday":"10:00-20:00","thursday":"10:00-20:00","friday":"10:00-21:00","saturday":"10:00-21:00","sunday":"11:00-18:00"}	\N	t	t	["parking","delivery","takeout"]	\N	2025-06-01 20:18:34	2025-06-01 20:18:34	0101000020E610000050FC1873D79A5EC0D0D556EC2FE34240	\N	\N
3	3	789 Wellness Way	\N	Miami	FL	33101	USA	25.7617000	-80.1918000	{"monday":"09:00-19:00","tuesday":"09:00-19:00","wednesday":"09:00-19:00","thursday":"09:00-19:00","friday":"09:00-20:00","saturday":"09:00-20:00","sunday":"10:00-17:00"}	\N	t	t	["wifi","parking","reservations"]	\N	2025-06-01 20:18:34	2025-06-01 20:18:34	0101000020E6100000DCD78173460C54C0FB5C6DC5FEC23940	\N	\N
43	98	400 Trono Ln	\N	Mammoth Lakes	CA	93546	US	37.6357900	-118.9628900	\N	\N	f	f	\N	\N	2025-06-29 03:06:50	2025-06-29 03:21:25	0101000020E61000004FE960FD9FBD5DC0DA8F149161D14240	\N	\N
15	70	1002 Old Mammoth Rd	\N	Mammoth Lakes	CA	93546	US	0.0000000	0.0000000	\N	\N	f	f	\N	\N	2025-06-29 03:06:50	2025-06-29 03:06:50	0101000020E610000000000000000000000000000000000000	\N	\N
16	71	1039 Forest Trail	\N	Mammoth Lakes	CA	93546	US	0.0000000	0.0000000	\N	\N	f	f	\N	\N	2025-06-29 03:06:50	2025-06-29 03:06:50	0101000020E610000000000000000000000000000000000000	\N	\N
17	72	114 Old Mammoth Rd	\N	Mammoth Lakes	CA	93546	US	0.0000000	0.0000000	\N	\N	f	f	\N	\N	2025-06-29 03:06:50	2025-06-29 03:06:50	0101000020E610000000000000000000000000000000000000	\N	\N
18	73	678 Old Mammoth Rd	\N	Mammoth Lakes	CA	93546	US	0.0000000	0.0000000	\N	\N	f	f	\N	\N	2025-06-29 03:06:50	2025-06-29 03:06:50	0101000020E610000000000000000000000000000000000000	\N	\N
19	74	2763 Main St	\N	Mammoth Lakes	CA	93546	US	0.0000000	0.0000000	\N	\N	f	f	\N	\N	2025-06-29 03:06:50	2025-06-29 03:06:50	0101000020E610000000000000000000000000000000000000	\N	\N
20	75	18 Lakeview Blvd	\N	Mammoth Lakes	CA	93546	US	0.0000000	0.0000000	\N	\N	f	f	\N	\N	2025-06-29 03:06:50	2025-06-29 03:06:50	0101000020E610000000000000000000000000000000000000	\N	\N
21	76	120 Canyon Blvd	\N	Mammoth Lakes	CA	93546	US	0.0000000	0.0000000	\N	\N	f	f	\N	\N	2025-06-29 03:06:50	2025-06-29 03:06:50	0101000020E610000000000000000000000000000000000000	\N	\N
23	78	3150 Main St	\N	Mammoth Lakes	CA	93546	US	0.0000000	0.0000000	\N	\N	f	f	\N	\N	2025-06-29 03:06:50	2025-06-29 03:06:50	0101000020E610000000000000000000000000000000000000	\N	\N
22	77	1111 Forest Trail Road #201	\N	Mammoth Lakes	CA	93546	US	37.6514400	-118.9857300	\N	\N	f	f	\N	\N	2025-06-29 03:06:50	2025-07-04 23:16:57	0101000020E6100000E92B483316BF5DC033A7CB6262D34240	\N	\N
14	69	1528 Tavern Rd	\N	Mammoth Lakes	CA	93546	US	37.6454400	-118.9668700	\N	\N	f	f	\N	\N	2025-06-29 03:06:50	2025-07-05 21:40:03	0101000020E6100000F25EB532E1BD5DC0DFC325C79DD24240	\N	\N
24	79	331 Old Mammoth Rd	\N	Mammoth Lakes	CA	93546	US	0.0000000	0.0000000	\N	\N	f	f	\N	\N	2025-06-29 03:06:50	2025-06-29 03:06:50	0101000020E610000000000000000000000000000000000000	\N	\N
25	80	150 Old Mammoth Rd	\N	Mammoth Lakes	CA	93546	US	0.0000000	0.0000000	\N	\N	f	f	\N	\N	2025-06-29 03:06:50	2025-06-29 03:06:50	0101000020E610000000000000000000000000000000000000	\N	\N
26	81	10001 Minaret Rd	\N	Mammoth Lakes	CA	93546	US	0.0000000	0.0000000	\N	\N	f	f	\N	\N	2025-06-29 03:06:50	2025-06-29 03:06:50	0101000020E610000000000000000000000000000000000000	\N	\N
27	82	150 Old Mammoth Rd	\N	Mammoth Lakes	CA	93546	US	0.0000000	0.0000000	\N	\N	f	f	\N	\N	2025-06-29 03:06:50	2025-06-29 03:06:50	0101000020E610000000000000000000000000000000000000	\N	\N
28	83	339 Old Mammoth Rd	\N	Mammoth Lakes	CA	93546	US	0.0000000	0.0000000	\N	\N	f	f	\N	\N	2025-06-29 03:06:50	2025-06-29 03:06:50	0101000020E610000000000000000000000000000000000000	\N	\N
29	84	331 Old Mammoth Rd	\N	Mammoth Lakes	CA	93546	US	0.0000000	0.0000000	\N	\N	f	f	\N	\N	2025-06-29 03:06:50	2025-06-29 03:06:50	0101000020E610000000000000000000000000000000000000	\N	\N
30	85	1021 Forest Trail	\N	Mammoth Lakes	CA	93546	US	0.0000000	0.0000000	\N	\N	f	f	\N	\N	2025-06-29 03:06:50	2025-06-29 03:06:50	0101000020E610000000000000000000000000000000000000	\N	\N
31	86	2001 Meridian Blvd	\N	Mammoth Lakes	CA	93546	US	0.0000000	0.0000000	\N	\N	f	f	\N	\N	2025-06-29 03:06:50	2025-06-29 03:06:50	0101000020E610000000000000000000000000000000000000	\N	\N
32	87	451 Old Mammoth Rd	\N	Mammoth Lakes	CA	93546	US	0.0000000	0.0000000	\N	\N	f	f	\N	\N	2025-06-29 03:06:50	2025-06-29 03:06:50	0101000020E610000000000000000000000000000000000000	\N	\N
33	88	3151 Main St	\N	Mammoth Lakes	CA	93546	US	0.0000000	0.0000000	\N	\N	f	f	\N	\N	2025-06-29 03:06:50	2025-06-29 03:06:50	0101000020E610000000000000000000000000000000000000	\N	\N
34	89	10001 Minaret Rd	\N	Mammoth Lakes	CA	93546	US	0.0000000	0.0000000	\N	\N	f	f	\N	\N	2025-06-29 03:06:50	2025-06-29 03:06:50	0101000020E610000000000000000000000000000000000000	\N	\N
35	90	2510 Main St	\N	Mammoth Lakes	CA	93546	US	0.0000000	0.0000000	\N	\N	f	f	\N	\N	2025-06-29 03:06:50	2025-06-29 03:06:50	0101000020E610000000000000000000000000000000000000	\N	\N
36	91	100 College Pkwy	\N	Mammoth Lakes	CA	93546	US	0.0000000	0.0000000	\N	\N	f	f	\N	\N	2025-06-29 03:06:50	2025-06-29 03:06:50	0101000020E610000000000000000000000000000000000000	\N	\N
37	92	10601 Minaret Rd	\N	Mammoth Lakes	CA	93546	US	0.0000000	0.0000000	\N	\N	f	f	\N	\N	2025-06-29 03:06:50	2025-06-29 03:06:50	0101000020E610000000000000000000000000000000000000	\N	\N
38	93	Sierra Star Dr	\N	Mammoth Lakes	CA	93546	US	0.0000000	0.0000000	\N	\N	f	f	\N	\N	2025-06-29 03:06:50	2025-06-29 03:06:50	0101000020E610000000000000000000000000000000000000	\N	\N
39	94	100 College Pkwy	\N	Mammoth Lakes	CA	93546	US	0.0000000	0.0000000	\N	\N	f	f	\N	\N	2025-06-29 03:06:50	2025-06-29 03:06:50	0101000020E610000000000000000000000000000000000000	\N	\N
41	96	10001 Minaret Rd	\N	Mammoth Lakes	CA	93546	US	0.0000000	0.0000000	\N	\N	f	f	\N	\N	2025-06-29 03:06:50	2025-06-29 03:06:50	0101000020E610000000000000000000000000000000000000	\N	\N
42	97	10001 Minaret Rd	\N	Mammoth Lakes	CA	93546	US	0.0000000	0.0000000	\N	\N	f	f	\N	\N	2025-06-29 03:06:50	2025-06-29 03:06:50	0101000020E610000000000000000000000000000000000000	\N	\N
40	95	18 Lakeview Blvd	\N	Mammoth Lakes	CA	93546	US	37.6490700	-118.8936900	\N	\N	f	f	\N	\N	2025-06-29 03:06:50	2025-06-30 06:13:54	0101000020E6100000C9B08A3732B95DC04968CBB914D34240	\N	\N
44	99	361 Old Mammoth Road	\N	Mammoth Lakes	CA	93546	USA	37.6534500	-118.9693300	\N	\N	f	f	\N	\N	2025-07-05 19:03:22	2025-07-05 19:03:22	0101000020E61000000742B28009BE5DC01DC9E53FA4D34240	\N	\N
\.


--
-- Data for Name: login_page_settings; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.login_page_settings (id, background_image_path, welcome_message, custom_css, social_login_enabled, created_at, updated_at, background_image_id) FROM stdin;
1	\N	\N	\N	t	2025-07-28 21:03:32	2025-07-28 21:36:40	05e4929f-00ad-4231-5032-a719f82a2700
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
44	2025_06_29_011202_add_expanded_fields_to_directory_entries_table	8
45	2025_07_02_074837_enhance_user_profiles_and_add_following_system	8
46	2025_07_02_155347_add_page_title_to_users_table	9
47	2025_07_02_195136_create_uploaded_images_table	10
48	2025_07_02_211019_add_cloudflare_image_fields_to_users_table	11
49	2025_07_03_004357_add_cloudflare_image_fields_to_list_items_table	12
50	2025_07_03_004722_add_featured_image_cloudflare_id_to_lists_table	13
51	2025_07_03_225808_add_page_logo_cloudflare_id_to_users_table	14
52	2025_07_03_230703_add_phone_to_users_table	15
53	2025_07_04_001105_add_page_logo_option_to_users_table	16
54	2025_07_04_002850_add_page_logo_to_uploaded_images_enum	17
55	2025_07_04_022721_create_list_categories_table	18
56	2025_07_04_022727_create_tags_table	18
57	2025_07_04_022733_create_taggables_table	19
58	2025_07_04_022820_add_category_id_to_lists_table	19
59	2025_07_04_070909_add_visibility_and_draft_to_user_lists_table	20
60	2025_07_04_070930_create_user_list_shares_table	20
61	2025_07_05_173641_create_cloudflare_images_table	21
62	2025_07_05_235137_add_gallery_images_to_lists_table	22
63	2025_07_06_063604_add_svg_cover_quotes_to_categories	23
64	2025_07_06_063611_add_svg_cover_quotes_to_list_categories	23
65	2025_07_07_000003_create_settings_table	24
66	2025_07_08_000001_enhance_regions_table_with_spatial_data	24
67	2025_07_08_000002_add_region_hierarchy_to_directory_entries	24
68	2025_07_08_000003_add_featured_content_to_regions	24
69	2025_07_08_000004_create_region_featured_entries_table	24
70	2025_07_08_230450_update_regions_type_enum	24
71	2025_07_09_151046_add_routing_performance_indexes	24
72	2025_07_09_222257_add_pinned_columns_to_lists_table	24
73	2025_07_10_195103_update_directory_entry_types	24
74	2025_07_11_000001_add_cloudflare_image_id_to_regions_table	24
75	2025_07_11_063427_create_region_featured_lists_table	24
76	2025_07_11_173249_add_facts_and_geodata_to_regions_table	24
77	2025_07_11_211556_create_posts_table	24
78	2025_07_12_061520_add_status_to_lists_table	24
79	2025_07_12_160059_create_place_regions_table	24
80	2025_07_13_160302_add_image_timestamps_to_users_table	24
81	2025_07_13_add_full_name_to_regions_table	24
82	2025_07_14_021118_create_pages_table	24
83	2025_07_14_021143_create_login_page_settings_table	24
84	2025_07_14_021211_create_home_page_settings_table	24
85	2025_07_14_add_default_region_to_users_table	24
86	2025_07_14_create_place_region_featured_table	24
87	2025_07_15_000002_add_curated_list_fields_to_lists_table	24
88	2025_07_16_000003_update_tags_table_structure	24
89	2025_07_16_062225_create_registration_waitlists_table	24
90	2025_07_16_070340_add_background_image_id_to_login_page_settings_table	24
91	2025_07_17_154759_create_follows_table	24
92	2025_07_17_181020_create_saved_items_table	24
93	2025_07_17_194717_create_channels_table	24
94	2025_07_17_194753_create_channel_followers_table	24
95	2025_07_17_194816_add_channel_id_to_user_lists_table	24
96	2025_07_17_214110_add_cloudflare_ids_to_channels_table	24
97	2025_07_17_233353_create_personal_access_tokens_table	24
98	2025_07_18_020000_add_polymorphic_owner_to_lists	24
99	2025_07_19_202413_create_notifications_table	24
100	2025_07_19_225629_ensure_region_slugs_are_url_friendly	24
101	2025_07_20_012719_add_links_to_directory_entries_table	24
102	2025_07_20_040831_update_users_table_split_name_add_fields	24
103	2025_07_20_054528_update_gender_enum_remove_other	24
104	2025_07_20_153829_create_place_managers_table	24
105	2025_07_20_154243_migrate_existing_places_to_place_managers	24
106	2025_07_20_165215_add_rejection_fields_to_places_table	24
107	2025_07_20_200000_add_approval_fields_to_places_table	24
108	2025_07_20_230804_make_notifications_columns_nullable	24
109	2025_07_23_053717_create_local_page_settings_table	24
110	2025_07_24_053441_add_ownership_fields_to_directory_entries_table	24
111	2025_07_24_053452_create_verification_codes_table	24
112	2025_07_24_053504_create_claim_documents_table	24
113	2025_07_25_fix_claims_table_if_needed	24
114	2025_07_26_021537_add_tier_and_payment_to_claims_table	24
115	2025_07_26_022218_add_stripe_customer_id_to_users_table	24
116	2025_07_26_062942_add_verification_fee_fields_to_claims_table	24
117	2025_07_27_051849_create_app_notifications_table	24
118	2025_07_27_144826_create_list_chains_table	24
119	2025_07_27_144829_create_list_chain_items_table	24
120	2025_07_28_000001_create_likes_table	25
121	2025_07_28_000002_update_comments_table_for_polymorphic	25
122	2025_07_28_000003_create_reposts_table	25
123	2025_07_28_000004_add_interaction_counts_to_models	25
\.


--
-- Data for Name: notifications; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.notifications (id, user_id, type, title, message, data, notifiable_type, notifiable_id, is_read, read_at, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: pages; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.pages (id, title, slug, content, status, meta_title, meta_description, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: password_reset_tokens; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.password_reset_tokens (email, token, created_at) FROM stdin;
\.


--
-- Data for Name: personal_access_tokens; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.personal_access_tokens (id, tokenable_type, tokenable_id, name, token, abilities, last_used_at, expires_at, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: pinned_lists; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.pinned_lists (id, user_id, list_id, sort_order, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: place_managers; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.place_managers (id, place_id, manageable_type, manageable_id, role, permissions, is_active, accepted_at, invited_at, invited_by, created_at, updated_at) FROM stdin;
1	2	App\\Models\\User	1	owner	\N	t	2025-06-01 20:18:34	\N	\N	2025-07-28 21:02:57	2025-07-28 21:02:57
2	3	App\\Models\\User	1	owner	\N	t	2025-06-01 20:18:34	\N	\N	2025-07-28 21:02:57	2025-07-28 21:02:57
3	4	App\\Models\\User	1	owner	\N	t	2025-06-01 20:18:34	\N	\N	2025-07-28 21:02:57	2025-07-28 21:02:57
4	5	App\\Models\\User	1	owner	\N	t	2025-06-01 20:18:34	\N	\N	2025-07-28 21:02:57	2025-07-28 21:02:57
5	6	App\\Models\\User	1	owner	\N	t	2025-06-01 20:18:34	\N	\N	2025-07-28 21:02:57	2025-07-28 21:02:57
6	8	App\\Models\\User	2	owner	\N	t	2025-06-01 22:44:55	\N	\N	2025-07-28 21:02:57	2025-07-28 21:02:57
7	69	App\\Models\\User	2	owner	\N	t	2025-06-29 03:06:50	\N	\N	2025-07-28 21:02:57	2025-07-28 21:02:57
8	70	App\\Models\\User	2	owner	\N	t	2025-06-29 03:06:50	\N	\N	2025-07-28 21:02:57	2025-07-28 21:02:57
9	71	App\\Models\\User	2	owner	\N	t	2025-06-29 03:06:50	\N	\N	2025-07-28 21:02:57	2025-07-28 21:02:57
10	72	App\\Models\\User	2	owner	\N	t	2025-06-29 03:06:50	\N	\N	2025-07-28 21:02:57	2025-07-28 21:02:57
11	73	App\\Models\\User	2	owner	\N	t	2025-06-29 03:06:50	\N	\N	2025-07-28 21:02:57	2025-07-28 21:02:57
12	74	App\\Models\\User	2	owner	\N	t	2025-06-29 03:06:50	\N	\N	2025-07-28 21:02:57	2025-07-28 21:02:57
13	75	App\\Models\\User	2	owner	\N	t	2025-06-29 03:06:50	\N	\N	2025-07-28 21:02:57	2025-07-28 21:02:57
14	76	App\\Models\\User	2	owner	\N	t	2025-06-29 03:06:50	\N	\N	2025-07-28 21:02:57	2025-07-28 21:02:57
15	77	App\\Models\\User	2	owner	\N	t	2025-06-29 03:06:50	\N	\N	2025-07-28 21:02:57	2025-07-28 21:02:57
16	78	App\\Models\\User	2	owner	\N	t	2025-06-29 03:06:50	\N	\N	2025-07-28 21:02:57	2025-07-28 21:02:57
17	79	App\\Models\\User	2	owner	\N	t	2025-06-29 03:06:50	\N	\N	2025-07-28 21:02:57	2025-07-28 21:02:57
18	80	App\\Models\\User	2	owner	\N	t	2025-06-29 03:06:50	\N	\N	2025-07-28 21:02:57	2025-07-28 21:02:57
19	81	App\\Models\\User	2	owner	\N	t	2025-06-29 03:06:50	\N	\N	2025-07-28 21:02:57	2025-07-28 21:02:57
20	82	App\\Models\\User	2	owner	\N	t	2025-06-29 03:06:50	\N	\N	2025-07-28 21:02:57	2025-07-28 21:02:57
21	83	App\\Models\\User	2	owner	\N	t	2025-06-29 03:06:50	\N	\N	2025-07-28 21:02:57	2025-07-28 21:02:57
22	84	App\\Models\\User	2	owner	\N	t	2025-06-29 03:06:50	\N	\N	2025-07-28 21:02:57	2025-07-28 21:02:57
23	85	App\\Models\\User	2	owner	\N	t	2025-06-29 03:06:50	\N	\N	2025-07-28 21:02:57	2025-07-28 21:02:57
24	86	App\\Models\\User	2	owner	\N	t	2025-06-29 03:06:50	\N	\N	2025-07-28 21:02:57	2025-07-28 21:02:57
25	87	App\\Models\\User	2	owner	\N	t	2025-06-29 03:06:50	\N	\N	2025-07-28 21:02:57	2025-07-28 21:02:57
26	88	App\\Models\\User	2	owner	\N	t	2025-06-29 03:06:50	\N	\N	2025-07-28 21:02:57	2025-07-28 21:02:57
27	89	App\\Models\\User	2	owner	\N	t	2025-06-29 03:06:50	\N	\N	2025-07-28 21:02:57	2025-07-28 21:02:57
28	90	App\\Models\\User	2	owner	\N	t	2025-06-29 03:06:50	\N	\N	2025-07-28 21:02:57	2025-07-28 21:02:57
29	91	App\\Models\\User	2	owner	\N	t	2025-06-29 03:06:50	\N	\N	2025-07-28 21:02:57	2025-07-28 21:02:57
30	92	App\\Models\\User	2	owner	\N	t	2025-06-29 03:06:50	\N	\N	2025-07-28 21:02:57	2025-07-28 21:02:57
31	93	App\\Models\\User	2	owner	\N	t	2025-06-29 03:06:50	\N	\N	2025-07-28 21:02:57	2025-07-28 21:02:57
32	94	App\\Models\\User	2	owner	\N	t	2025-06-29 03:06:50	\N	\N	2025-07-28 21:02:57	2025-07-28 21:02:57
33	95	App\\Models\\User	2	owner	\N	t	2025-06-29 03:06:50	\N	\N	2025-07-28 21:02:57	2025-07-28 21:02:57
34	96	App\\Models\\User	2	owner	\N	t	2025-06-29 03:06:50	\N	\N	2025-07-28 21:02:57	2025-07-28 21:02:57
35	97	App\\Models\\User	2	owner	\N	t	2025-06-29 03:06:50	\N	\N	2025-07-28 21:02:57	2025-07-28 21:02:57
36	98	App\\Models\\User	2	owner	\N	t	2025-06-29 03:06:50	\N	\N	2025-07-28 21:02:57	2025-07-28 21:02:57
37	99	App\\Models\\User	2	owner	\N	t	2025-07-05 19:03:22	\N	\N	2025-07-28 21:02:57	2025-07-28 21:02:57
\.


--
-- Data for Name: place_regions; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.place_regions (id, place_id, region_id, association_type, distance_meters, confidence_score, region_type, region_level, created_at, updated_at, is_featured, featured_order, featured_at) FROM stdin;
\.


--
-- Data for Name: posts; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.posts (id, user_id, content, media, media_type, cloudflare_image_id, cloudflare_video_id, is_tacked, tacked_at, expires_in_days, expires_at, likes_count, replies_count, shares_count, views_count, metadata, created_at, updated_at, deleted_at, comments_count, reposts_count) FROM stdin;
1	2	It's a Beautiful day	[{"url":"https:\\/\\/ik.imagekit.io\\/listerinolistkit\\/posts\\/images\\/cha-for-tea_8-FDFrX27.jpg","fileId":"688815535c7cd75eb8ec5366","metadata":{"name":"cha-for-tea_8-FDFrX27.jpg","size":229981,"width":1360,"height":1020,"thumbnailUrl":"https:\\/\\/ik.imagekit.io\\/listerinolistkit\\/tr:n-ik_ml_thumbnail\\/posts\\/images\\/cha-for-tea_8-FDFrX27.jpg"}}]	images	\N	\N	f	\N	30	2025-08-28 00:27:02	2	0	0	0	\N	2025-07-29 00:27:02	2025-07-29 00:28:52	\N	0	2
2	5	WoodBridge	[{"url":"https:\\/\\/ik.imagekit.io\\/listerinolistkit\\/posts\\/images\\/474104878_621377576952975_9032310979614471081_n_KvEExIQw4.jpg","fileId":"688820d05c7cd75eb8360e97","metadata":{"name":"474104878_621377576952975_9032310979614471081_n_KvEExIQw4.jpg","size":134698,"width":1200,"height":628,"thumbnailUrl":"https:\\/\\/ik.imagekit.io\\/listerinolistkit\\/tr:n-ik_ml_thumbnail\\/posts\\/images\\/474104878_621377576952975_9032310979614471081_n_KvEExIQw4.jpg"}}]	images	\N	\N	f	\N	30	2025-08-28 01:16:11	2	0	0	0	\N	2025-07-29 01:16:11	2025-07-29 01:38:36	\N	0	0
\.


--
-- Data for Name: region_featured_entries; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.region_featured_entries (id, region_id, directory_entry_id, priority, label, tagline, custom_data, featured_until, is_active, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: region_featured_lists; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.region_featured_lists (id, region_id, list_id, priority, is_active, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: region_featured_metadata; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.region_featured_metadata (id, region_id, featured_title, featured_description, max_featured_places, show_featured_section, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: regions; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.regions (id, name, type, parent_id, created_at, updated_at, level, slug, metadata, cached_place_count, cover_image, intro_text, data_points, is_featured, meta_title, meta_description, custom_fields, display_priority, cloudflare_image_id, facts, state_symbols, geojson, polygon_coordinates, full_name, abbreviation, alternate_names, boundary, center_point, area_sq_km, is_user_defined, created_by_user_id, cache_updated_at) FROM stdin;
1	California	state	\N	2025-06-01 20:18:34	2025-06-01 20:18:34	1	california	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	California	\N	\N	\N	\N	\N	f	\N	\N
2	New York	state	\N	2025-06-01 20:18:34	2025-06-01 20:18:34	1	new-york	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	New York	\N	\N	\N	\N	\N	f	\N	\N
3	Texas	state	\N	2025-06-01 20:18:34	2025-06-01 20:18:34	1	texas	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	Texas	\N	\N	\N	\N	\N	f	\N	\N
4	Florida	state	\N	2025-06-01 20:18:34	2025-06-01 20:18:34	1	florida	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	Florida	\N	\N	\N	\N	\N	f	\N	\N
5	Alabama	state	\N	2025-07-28 21:02:57	2025-07-28 21:02:57	1	alabama	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	Alabama	AL	\N	\N	\N	\N	f	\N	\N
6	Alaska	state	\N	2025-07-28 21:02:57	2025-07-28 21:02:57	1	alaska	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	Alaska	AK	\N	\N	\N	\N	f	\N	\N
7	Arizona	state	\N	2025-07-28 21:02:57	2025-07-28 21:02:57	1	arizona	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	Arizona	AZ	\N	\N	\N	\N	f	\N	\N
8	Arkansas	state	\N	2025-07-28 21:02:57	2025-07-28 21:02:57	1	arkansas	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	Arkansas	AR	\N	\N	\N	\N	f	\N	\N
9	Colorado	state	\N	2025-07-28 21:02:57	2025-07-28 21:02:57	1	colorado	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	Colorado	CO	\N	\N	\N	\N	f	\N	\N
10	Connecticut	state	\N	2025-07-28 21:02:57	2025-07-28 21:02:57	1	connecticut	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	Connecticut	CT	\N	\N	\N	\N	f	\N	\N
11	Delaware	state	\N	2025-07-28 21:02:57	2025-07-28 21:02:57	1	delaware	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	Delaware	DE	\N	\N	\N	\N	f	\N	\N
12	Georgia	state	\N	2025-07-28 21:02:57	2025-07-28 21:02:57	1	georgia	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	Georgia	GA	\N	\N	\N	\N	f	\N	\N
13	Hawaii	state	\N	2025-07-28 21:02:57	2025-07-28 21:02:57	1	hawaii	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	Hawaii	HI	\N	\N	\N	\N	f	\N	\N
14	Idaho	state	\N	2025-07-28 21:02:57	2025-07-28 21:02:57	1	idaho	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	Idaho	ID	\N	\N	\N	\N	f	\N	\N
15	Illinois	state	\N	2025-07-28 21:02:57	2025-07-28 21:02:57	1	illinois	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	Illinois	IL	\N	\N	\N	\N	f	\N	\N
16	Indiana	state	\N	2025-07-28 21:02:57	2025-07-28 21:02:57	1	indiana	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	Indiana	IN	\N	\N	\N	\N	f	\N	\N
17	Iowa	state	\N	2025-07-28 21:02:57	2025-07-28 21:02:57	1	iowa	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	Iowa	IA	\N	\N	\N	\N	f	\N	\N
18	Kansas	state	\N	2025-07-28 21:02:57	2025-07-28 21:02:57	1	kansas	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	Kansas	KS	\N	\N	\N	\N	f	\N	\N
19	Kentucky	state	\N	2025-07-28 21:02:57	2025-07-28 21:02:57	1	kentucky	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	Kentucky	KY	\N	\N	\N	\N	f	\N	\N
20	Louisiana	state	\N	2025-07-28 21:02:57	2025-07-28 21:02:57	1	louisiana	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	Louisiana	LA	\N	\N	\N	\N	f	\N	\N
21	Maine	state	\N	2025-07-28 21:02:57	2025-07-28 21:02:57	1	maine	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	Maine	ME	\N	\N	\N	\N	f	\N	\N
22	Maryland	state	\N	2025-07-28 21:02:57	2025-07-28 21:02:57	1	maryland	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	Maryland	MD	\N	\N	\N	\N	f	\N	\N
23	Massachusetts	state	\N	2025-07-28 21:02:57	2025-07-28 21:02:57	1	massachusetts	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	Massachusetts	MA	\N	\N	\N	\N	f	\N	\N
24	Michigan	state	\N	2025-07-28 21:02:57	2025-07-28 21:02:57	1	michigan	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	Michigan	MI	\N	\N	\N	\N	f	\N	\N
25	Minnesota	state	\N	2025-07-28 21:02:57	2025-07-28 21:02:57	1	minnesota	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	Minnesota	MN	\N	\N	\N	\N	f	\N	\N
26	Mississippi	state	\N	2025-07-28 21:02:57	2025-07-28 21:02:57	1	mississippi	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	Mississippi	MS	\N	\N	\N	\N	f	\N	\N
27	Missouri	state	\N	2025-07-28 21:02:57	2025-07-28 21:02:57	1	missouri	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	Missouri	MO	\N	\N	\N	\N	f	\N	\N
28	Montana	state	\N	2025-07-28 21:02:57	2025-07-28 21:02:57	1	montana	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	Montana	MT	\N	\N	\N	\N	f	\N	\N
29	Nebraska	state	\N	2025-07-28 21:02:57	2025-07-28 21:02:57	1	nebraska	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	Nebraska	NE	\N	\N	\N	\N	f	\N	\N
30	Nevada	state	\N	2025-07-28 21:02:57	2025-07-28 21:02:57	1	nevada	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	Nevada	NV	\N	\N	\N	\N	f	\N	\N
31	New Hampshire	state	\N	2025-07-28 21:02:57	2025-07-28 21:02:57	1	new-hampshire	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	New Hampshire	NH	\N	\N	\N	\N	f	\N	\N
32	New Jersey	state	\N	2025-07-28 21:02:57	2025-07-28 21:02:57	1	new-jersey	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	New Jersey	NJ	\N	\N	\N	\N	f	\N	\N
33	New Mexico	state	\N	2025-07-28 21:02:57	2025-07-28 21:02:57	1	new-mexico	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	New Mexico	NM	\N	\N	\N	\N	f	\N	\N
34	North Carolina	state	\N	2025-07-28 21:02:57	2025-07-28 21:02:57	1	north-carolina	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	North Carolina	NC	\N	\N	\N	\N	f	\N	\N
35	North Dakota	state	\N	2025-07-28 21:02:57	2025-07-28 21:02:57	1	north-dakota	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	North Dakota	ND	\N	\N	\N	\N	f	\N	\N
36	Ohio	state	\N	2025-07-28 21:02:57	2025-07-28 21:02:57	1	ohio	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	Ohio	OH	\N	\N	\N	\N	f	\N	\N
37	Oklahoma	state	\N	2025-07-28 21:02:57	2025-07-28 21:02:57	1	oklahoma	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	Oklahoma	OK	\N	\N	\N	\N	f	\N	\N
38	Oregon	state	\N	2025-07-28 21:02:57	2025-07-28 21:02:57	1	oregon	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	Oregon	OR	\N	\N	\N	\N	f	\N	\N
39	Pennsylvania	state	\N	2025-07-28 21:02:57	2025-07-28 21:02:57	1	pennsylvania	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	Pennsylvania	PA	\N	\N	\N	\N	f	\N	\N
40	Rhode Island	state	\N	2025-07-28 21:02:57	2025-07-28 21:02:57	1	rhode-island	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	Rhode Island	RI	\N	\N	\N	\N	f	\N	\N
41	South Carolina	state	\N	2025-07-28 21:02:57	2025-07-28 21:02:57	1	south-carolina	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	South Carolina	SC	\N	\N	\N	\N	f	\N	\N
42	South Dakota	state	\N	2025-07-28 21:02:57	2025-07-28 21:02:57	1	south-dakota	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	South Dakota	SD	\N	\N	\N	\N	f	\N	\N
43	Tennessee	state	\N	2025-07-28 21:02:57	2025-07-28 21:02:57	1	tennessee	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	Tennessee	TN	\N	\N	\N	\N	f	\N	\N
44	Utah	state	\N	2025-07-28 21:02:57	2025-07-28 21:02:57	1	utah	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	Utah	UT	\N	\N	\N	\N	f	\N	\N
45	Vermont	state	\N	2025-07-28 21:02:57	2025-07-28 21:02:57	1	vermont	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	Vermont	VT	\N	\N	\N	\N	f	\N	\N
46	Virginia	state	\N	2025-07-28 21:02:57	2025-07-28 21:02:57	1	virginia	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	Virginia	VA	\N	\N	\N	\N	f	\N	\N
47	Washington	state	\N	2025-07-28 21:02:57	2025-07-28 21:02:57	1	washington	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	Washington	WA	\N	\N	\N	\N	f	\N	\N
48	West Virginia	state	\N	2025-07-28 21:02:57	2025-07-28 21:02:57	1	west-virginia	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	West Virginia	WV	\N	\N	\N	\N	f	\N	\N
49	Wisconsin	state	\N	2025-07-28 21:02:57	2025-07-28 21:02:57	1	wisconsin	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	Wisconsin	WI	\N	\N	\N	\N	f	\N	\N
50	Wyoming	state	\N	2025-07-28 21:02:57	2025-07-28 21:02:57	1	wyoming	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	Wyoming	WY	\N	\N	\N	\N	f	\N	\N
51	District of Columbia	state	\N	2025-07-28 21:02:57	2025-07-28 21:02:57	1	district-of-columbia	\N	0	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	District of Columbia	DC	\N	\N	\N	\N	f	\N	\N
\.


--
-- Data for Name: registration_waitlists; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.registration_waitlists (id, email, name, message, status, invitation_token, invited_at, registered_at, metadata, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: reposts; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.reposts (id, user_id, repostable_type, repostable_id, comment, created_at, updated_at) FROM stdin;
1	2	App\\Models\\Post	1	Great Place	2025-07-29 00:27:42	2025-07-29 00:27:42
2	5	App\\Models\\Post	1	Love it	2025-07-29 00:28:52	2025-07-29 00:28:52
\.


--
-- Data for Name: saved_items; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.saved_items (id, user_id, saveable_type, saveable_id, notes, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: sessions; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.sessions (id, user_id, ip_address, user_agent, payload, last_activity) FROM stdin;
wNilmsi3H1cXwoa77PCwd9zEVq9jmBNjC4BMN5v4	2	127.0.0.1	Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:141.0) Gecko/20100101 Firefox/141.0	YTo0OntzOjY6Il90b2tlbiI7czo0MDoieHBFOFQxbXo1WG05MW5XMThIYmFRcWlpTlgyUkl2UDR6VUQ4ZzhPUSI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MjtzOjE3OiJwYXNzd29yZF9oYXNoX3dlYiI7czo2MDoiJDJ5JDEyJFBvVVZDanZXZHhDSkQ3c3ZRS3VnYmVxTkQ1QzdTWkVXV25ZYm1GZ1RudEVNMzk4UWk3OVIuIjt9	1753752038
ulrQquLNWd0Nixwh0FybdTmNxp43w6WiGeHdD7Dc	\N	127.0.0.1	Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:141.0) Gecko/20100101 Firefox/141.0	YToyOntzOjY6Il90b2tlbiI7czo0MDoiNFV2Rkd0cDRERU56OGZ1MUNoQ24zdFo1MDBYWkhnT01DZEZRQndtWiI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1753753035
kTA9PN6u59yqQJOZUNMHgd1WgI7kjkcrjI8G4ibE	\N	127.0.0.1	Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:141.0) Gecko/20100101 Firefox/141.0	YToyOntzOjY6Il90b2tlbiI7czo0MDoiRGo2RW0wSEtPeFhjM01mVjhrbE91TjA1NHhBR1MxZG1LTnhjcjFnViI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1753753035
9W1mV5RnnrfP0yYfgSCge9oF5p7KBajuIhGLRBn9	\N	127.0.0.1	Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36	YToyOntzOjY6Il90b2tlbiI7czo0MDoiZDlBekhyMU1MSXU5dms0eHFScnpaOEZLVUh3ZTBxSXF5S1BPTHh4MiI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1753753061
FZhlz4SdaBejpp4Ljw8yHk42sFZqczJEsnfPB75G	5	127.0.0.1	Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36	YTo0OntzOjY6Il90b2tlbiI7czo0MDoiOWxFZjAyTlZIeHhyWjRtT3gxZ2hURUFFaUplZVJQRFlBS0RyV3BQQSI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6NTtzOjE3OiJwYXNzd29yZF9oYXNoX3dlYiI7czo2MDoiJDJ5JDEyJDVsaGU4bzZDUTVhZVZWaWtpb0JxZy5HOFp2b2MzRWhnVmpmR0duZUdmLllQLnUudzhmLmRLIjt9	1753753231
R9ygOEEgmX07XVnyFOlaUTVavlqvEmN3Te86SNMe	\N	127.0.0.1	Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:141.0) Gecko/20100101 Firefox/141.0	YToyOntzOjY6Il90b2tlbiI7czo0MDoiVVhEZktjY29SZkRuc253U1ZvNVlacmRpVHRtQ2JGQlFjNXFzQXNDayI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1753749000
qJcEHkxxzZnfZPluNv0hqOF5g2yq69CUaHwSsYFy	\N	127.0.0.1	Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:141.0) Gecko/20100101 Firefox/141.0	YToyOntzOjY6Il90b2tlbiI7czo0MDoicExzMVJVbVp1a3IzbXBpY3VLRmsweHRkQjZrSFE1eHJFSkhhOTZCOSI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1753749000
p2Cxf6gYbIpX6z4Y3W5wAykc7zZZbsqDDnzqvf5f	\N	127.0.0.1	Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:141.0) Gecko/20100101 Firefox/141.0	YToyOntzOjY6Il90b2tlbiI7czo0MDoiOG9tQ1NrME9hcUhMTzFMUGVMa2tkRTVGWTJ3NWVtYmgxd2toQlZUWiI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1753748774
5DMlI9PplRPrkvxlpfuYvpmG2wFzUYZYCiX0ttWA	\N	127.0.0.1	Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:141.0) Gecko/20100101 Firefox/141.0	YToyOntzOjY6Il90b2tlbiI7czo0MDoiVkxKMm1sQ1hGZEdzVks1ZWFxMlR4U2lWOHNha01odTB3WVFheTQ0YiI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1753748774
\.


--
-- Data for Name: settings; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.settings (id, key, value, type, "group", description, created_at, updated_at) FROM stdin;
1	allow_registration	true	boolean	security	Allow new users to register	2025-07-28 21:02:57	2025-07-28 21:02:57
2	site_name	Listerino	string	general	Site name displayed in headers and emails	2025-07-28 21:02:57	2025-07-28 21:02:57
3	maintenance_mode	false	boolean	general	Enable maintenance mode	2025-07-28 21:02:57	2025-07-28 21:02:57
\.


--
-- Data for Name: spatial_ref_sys; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.spatial_ref_sys (srid, auth_name, auth_srid, srtext, proj4text) FROM stdin;
\.


--
-- Data for Name: taggables; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.taggables (id, tag_id, taggable_type, taggable_id, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: tags; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.tags (id, name, slug, description, color, created_at, updated_at, type, usage_count, places_count, lists_count, posts_count, is_featured, is_system, created_by) FROM stdin;
1	family-friendly	family-friendly	\N	#10B981	2025-07-04 02:36:40	2025-07-04 02:36:40	general	0	0	0	0	f	f	\N
2	outdoor	outdoor	\N	#059669	2025-07-04 02:36:40	2025-07-04 02:36:40	general	0	0	0	0	f	f	\N
3	budget	budget	\N	#F59E0B	2025-07-04 02:36:40	2025-07-04 02:36:40	general	0	0	0	0	f	f	\N
4	luxury	luxury	\N	#8B5CF6	2025-07-04 02:36:40	2025-07-04 02:36:40	general	0	0	0	0	f	f	\N
5	romantic	romantic	\N	#EC4899	2025-07-04 02:36:40	2025-07-04 02:36:40	general	0	0	0	0	f	f	\N
6	adventure	adventure	\N	#EF4444	2025-07-04 02:36:40	2025-07-04 02:36:40	general	0	0	0	0	f	f	\N
7	educational	educational	\N	#3B82F6	2025-07-04 02:36:40	2025-07-04 02:36:40	general	0	0	0	0	f	f	\N
8	seasonal	seasonal	\N	#6B7280	2025-07-04 02:36:40	2025-07-04 02:36:40	general	0	0	0	0	f	f	\N
9	Tennis	tennis	\N	#6B7280	2025-07-04 05:35:30	2025-07-04 05:35:30	general	0	0	0	0	f	f	\N
12	Italian	italian	\N	#6B7280	2025-07-04 23:26:46	2025-07-04 23:26:46	general	0	0	0	0	f	f	\N
\.


--
-- Data for Name: uploaded_images; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.uploaded_images (id, user_id, cloudflare_id, type, entity_id, original_name, file_size, mime_type, variants, meta, created_at, updated_at) FROM stdin;
1	2	a76abccd-e5d1-414b-bd3f-fe67a32a0500	avatar	1	eric_larson_sm.jpg	1966158	image/jpeg	["https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/a76abccd-e5d1-414b-bd3f-fe67a32a0500\\/public"]	{"entity_id":"1","original_name":"eric_larson_sm.jpg","type":"avatar","user_id":2}	2025-07-02 22:34:16	2025-07-02 22:34:16
2	2	4a615cd5-e61c-44f8-a046-b2120c011000	avatar	2	eric_larson_sm.jpg	1966158	image/jpeg	["https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/4a615cd5-e61c-44f8-a046-b2120c011000\\/public"]	{"entity_id":"2","original_name":"eric_larson_sm.jpg","type":"avatar","user_id":2}	2025-07-02 22:38:50	2025-07-02 22:38:50
3	2	b66813e0-7b70-4169-e057-3e0bc66c7c00	avatar	2	eric_larson_sm.jpg	1966158	image/jpeg	["https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/b66813e0-7b70-4169-e057-3e0bc66c7c00\\/public"]	{"entity_id":"2","original_name":"eric_larson_sm.jpg","type":"avatar","user_id":2}	2025-07-02 22:39:26	2025-07-02 22:39:26
4	2	5bf2437b-1f08-489b-523c-2aebc9c79400	cover	2	running_sm.jpg	1998536	image/jpeg	["https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/5bf2437b-1f08-489b-523c-2aebc9c79400\\/public"]	{"entity_id":"2","original_name":"running_sm.jpg","type":"cover","user_id":2}	2025-07-02 22:39:57	2025-07-02 22:39:57
5	2	027c0b76-737e-46c5-f2e3-4dda07a37000	avatar	1	eric_larson_sm.jpg	1966158	image/jpeg	["https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/027c0b76-737e-46c5-f2e3-4dda07a37000\\/public"]	{"entity_id":"1","original_name":"eric_larson_sm.jpg","type":"avatar","user_id":2}	2025-07-02 22:45:59	2025-07-02 22:45:59
6	2	2b34b5de-1a62-437d-b767-1a601b2bbc72	avatar	1	Eric_sheishi_beachsm.jpg	4353338	image/unknown	\N	\N	2025-07-02 23:26:43	2025-07-02 23:26:43
7	2	01e1a679-9852-4d04-8979-7ba9d4f0dbaf	avatar	1	Eric_sheishi_beachsm.jpg	4353338	image/unknown	\N	\N	2025-07-02 23:30:39	2025-07-02 23:30:39
8	2	3e9df3ec-f8b0-4510-ad94-a2e365e77f00	avatar	1	Eric_sheishi_beachsm.jpg	4353338	image/unknown	\N	\N	2025-07-02 23:42:40	2025-07-02 23:42:40
9	2	88726598-267f-4c58-2528-0a3c64899e00	list_image	49	OIP.Kh7GkmMaSRkFuE8WQS6NYQHaEK.jpeg	19963	image/unknown	\N	\N	2025-07-03 00:19:35	2025-07-03 00:19:35
10	2	f0f9799a-e768-427c-ae86-79a29486c600	list_image	52	OIP.9tDLjRZp-GuRcFHJFhIAEgHaE8.jpeg	27263	image/unknown	\N	\N	2025-07-03 00:20:07	2025-07-03 00:20:07
11	2	5479c8b9-82de-466d-eaae-190ac302ea00	cover	93	37111318455_0886300bfb_k.jpg	887276	image/unknown	\N	\N	2025-07-03 00:25:14	2025-07-03 00:25:14
12	2	fd90dcfa-b70c-4603-856a-9bb9f93d0800	cover	10	woodbridge.jpg	1832701	image/unknown	\N	\N	2025-07-03 04:00:56	2025-07-03 04:00:56
13	2	7b9e4601-5fb0-4bf1-9bf9-b2e901589d00	cover	2	Astronaut-full2.png	57978	image/unknown	\N	\N	2025-07-03 21:14:01	2025-07-03 21:14:01
14	2	5ed6b640-1e71-4b20-92c3-abbc3a330200	avatar	2	cake_icon.png	74258	image/unknown	\N	\N	2025-07-03 21:14:33	2025-07-03 21:14:33
15	2	da2e5880-cd4e-4201-0277-74ca38358600	avatar	2	eric_larson_sm.jpg	1966158	image/unknown	\N	\N	2025-07-03 21:24:43	2025-07-03 21:24:43
16	2	0b4c287a-bc02-4d16-4317-88fbef95ee00	avatar	2	Eric_sheishi_beachsm.jpg	4353338	image/unknown	\N	\N	2025-07-03 23:16:13	2025-07-03 23:16:13
17	2	37f99e82-734d-4a33-fd80-a44341c3e000	avatar	2	Eric_sheishi_beachsm.jpg	4353338	image/unknown	\N	\N	2025-07-03 23:18:33	2025-07-03 23:18:33
18	2	28d9df7b-72d6-41ce-55b8-0ffcc1caba00	avatar	2	eric_larson_sm.jpg	1966158	image/unknown	\N	\N	2025-07-03 23:42:52	2025-07-03 23:42:52
22	2	c52e4308-66ea-4215-0952-b840e724ce00	page_logo	2	cake_icon_light.png	110920	image/unknown	\N	\N	2025-07-04 00:40:51	2025-07-04 00:40:51
23	2	629b183e-2ca2-467a-abf7-e1be6fe5a200	page_logo	2	cake_icon_light.png	110920	image/unknown	\N	\N	2025-07-04 01:13:55	2025-07-04 01:13:55
24	2	a2c5bf62-f95f-4c37-d996-51d3c9804c00	page_logo	2	cake_icon_light.png	71995	image/unknown	\N	\N	2025-07-04 01:24:27	2025-07-04 01:24:27
25	5	b02d3919-c6b6-40a9-9f7a-864c1f13ac00	cover	9	martin-katler-ajKKNh0yfJU-unsplash.jpg	6889329	image/unknown	\N	\N	2025-07-04 14:42:04	2025-07-04 14:42:04
26	5	a62e4df8-0b2d-45f7-c390-a0710117e500	avatar	5	listerino_profile.png	91578	image/unknown	\N	\N	2025-07-04 14:46:12	2025-07-04 14:46:12
27	5	0ad5ac52-2e93-495d-3e40-95cb2bfcf400	cover	5	unsplash-community-W05bcx1V8uo-unsplash.jpg	4964160	image/unknown	\N	\N	2025-07-04 15:19:51	2025-07-04 15:19:51
28	2	fe2ec3d0-2469-4cfe-f0a3-f5a3e8595000	list_image	49	OIP.Kh7GkmMaSRkFuE8WQS6NYQHaEK.jpeg	19963	image/unknown	\N	\N	2025-07-04 19:36:53	2025-07-04 19:36:53
29	2	63d1af02-0f3c-4f94-ba1f-3ad2248aa800	entry_logo	77	SY+LOGO+copy.png	53293	image/unknown	\N	\N	2025-07-04 23:11:29	2025-07-04 23:11:29
30	2	33a56bd9-766a-4cc8-24b2-b862acf1e400	entry_logo	77	SY+LOGO+copy.png	53293	image/unknown	\N	\N	2025-07-04 23:12:16	2025-07-04 23:12:16
31	2	64c5191b-22b9-4f42-20ec-2f3d4fc43d00	cover	77	SMOKEYARD+-99+copy.jpg	461290	image/unknown	\N	\N	2025-07-04 23:14:29	2025-07-04 23:14:29
32	2	ab8b01c5-0878-445a-fb9f-18c4428b4a00	cover	8	houston-max-K82z6_8y_TU-unsplash.jpg	1270508	image/unknown	\N	\N	2025-07-05 18:17:34	2025-07-05 18:17:34
33	2	316ffcba-515b-4df5-b6d7-276401584500	cover	1	marcus-lenk-gI-xTuBrG2E-unsplash.jpg	2784617	image/unknown	\N	\N	2025-07-05 18:29:29	2025-07-05 18:29:29
34	2	8b5c7225-04dd-429a-2b14-192df3911600	cover	71	hans-isaacson-sYpq2Fhy4mY-unsplash.jpg	3331186	image/unknown	\N	\N	2025-07-05 19:17:03	2025-07-05 19:17:03
35	2	38672e5c-71b8-4616-0542-775684bee600	cover	71	hans-isaacson-sYpq2Fhy4mY-unsplash.jpg	3331186	image/unknown	\N	\N	2025-07-05 19:17:28	2025-07-05 19:17:28
36	2	1f92439e-40be-48de-b6b0-bd2adcf54300	cover	13	07-09-2021_SFA_HB_SW_I00010020.jpg	1640590	image/unknown	\N	\N	2025-07-05 23:03:53	2025-07-05 23:03:53
37	2	e82c143c-7cf5-4505-5425-75431d434900	avatar	2	Eric_sheishi_beachsm.jpg	4353338	image/unknown	\N	\N	2025-07-05 23:55:40	2025-07-05 23:55:40
38	2	f6ce134b-9d17-473d-3d00-6c7ec1ffa500	page_logo	2	cake_icon_light.png	72311	image/unknown	\N	\N	2025-07-06 05:28:50	2025-07-06 05:28:50
39	2	299fb4f9-855a-483d-e8a9-57225e527b00	cover	2	IMG_2400.jpeg	5055058	image/unknown	\N	\N	2025-07-06 05:30:47	2025-07-06 05:30:47
\.


--
-- Data for Name: user_activities; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.user_activities (id, user_id, activity_type, subject_type, subject_id, metadata, is_public, created_at, updated_at) FROM stdin;
1	2	updated_profile	App\\Models\\User	2	\N	t	2025-07-02 14:12:26	2025-07-02 14:12:26
2	2	updated_profile	App\\Models\\User	2	\N	t	2025-07-02 14:16:08	2025-07-02 14:16:08
3	2	updated_profile	App\\Models\\User	2	\N	t	2025-07-02 15:59:12	2025-07-02 15:59:12
4	2	updated_profile	App\\Models\\User	2	\N	t	2025-07-02 15:59:38	2025-07-02 15:59:38
5	2	updated_profile	App\\Models\\User	2	\N	t	2025-07-02 16:02:53	2025-07-02 16:02:53
6	2	updated_profile	App\\Models\\User	2	\N	t	2025-07-02 16:08:39	2025-07-02 16:08:39
7	5	updated_profile	App\\Models\\User	5	\N	t	2025-07-02 16:18:30	2025-07-02 16:18:30
8	5	followed_user	App\\Models\\User	2	\N	t	2025-07-02 16:19:13	2025-07-02 16:19:13
9	5	followed_user	App\\Models\\User	2	\N	t	2025-07-02 16:19:51	2025-07-02 16:19:51
10	2	updated_profile	App\\Models\\User	2	\N	t	2025-07-02 21:24:47	2025-07-02 21:24:47
11	2	updated_profile	App\\Models\\User	2	\N	t	2025-07-02 21:26:48	2025-07-02 21:26:48
12	2	updated_profile	App\\Models\\User	2	\N	t	2025-07-02 21:27:19	2025-07-02 21:27:19
13	2	updated_profile	App\\Models\\User	2	\N	t	2025-07-02 21:29:53	2025-07-02 21:29:53
14	2	updated_profile	App\\Models\\User	2	\N	t	2025-07-02 21:31:58	2025-07-02 21:31:58
15	2	updated_profile	App\\Models\\User	2	\N	t	2025-07-02 22:38:54	2025-07-02 22:38:54
16	2	updated_profile	App\\Models\\User	2	\N	t	2025-07-02 22:40:04	2025-07-02 22:40:04
17	2	updated_profile	App\\Models\\User	2	\N	t	2025-07-02 22:41:52	2025-07-02 22:41:52
18	2	updated_profile	App\\Models\\User	2	\N	t	2025-07-03 21:14:56	2025-07-03 21:14:56
19	2	updated_profile	App\\Models\\User	2	\N	t	2025-07-03 21:18:46	2025-07-03 21:18:46
20	2	updated_profile	App\\Models\\User	2	\N	t	2025-07-03 21:24:45	2025-07-03 21:24:45
21	2	updated_profile	App\\Models\\User	2	\N	t	2025-07-03 23:16:15	2025-07-03 23:16:15
22	2	updated_profile	App\\Models\\User	2	\N	t	2025-07-03 23:18:39	2025-07-03 23:18:39
23	2	updated_profile	App\\Models\\User	2	\N	t	2025-07-03 23:21:29	2025-07-03 23:21:29
24	2	updated_profile	App\\Models\\User	2	\N	t	2025-07-03 23:42:57	2025-07-03 23:42:57
25	2	updated_profile	App\\Models\\User	2	\N	t	2025-07-03 23:44:56	2025-07-03 23:44:56
26	2	updated_profile	App\\Models\\User	2	\N	t	2025-07-03 23:44:58	2025-07-03 23:44:58
27	2	updated_profile	App\\Models\\User	2	\N	t	2025-07-03 23:45:04	2025-07-03 23:45:04
28	2	updated_profile	App\\Models\\User	2	\N	t	2025-07-03 23:46:55	2025-07-03 23:46:55
29	2	updated_profile	App\\Models\\User	2	\N	t	2025-07-03 23:46:57	2025-07-03 23:46:57
30	2	updated_profile	App\\Models\\User	2	\N	t	2025-07-03 23:46:57	2025-07-03 23:46:57
31	2	updated_profile	App\\Models\\User	2	\N	t	2025-07-03 23:46:58	2025-07-03 23:46:58
32	2	updated_profile	App\\Models\\User	2	\N	t	2025-07-03 23:47:13	2025-07-03 23:47:13
33	2	updated_profile	App\\Models\\User	2	\N	t	2025-07-03 23:47:14	2025-07-03 23:47:14
34	2	updated_profile	App\\Models\\User	2	\N	t	2025-07-03 23:47:15	2025-07-03 23:47:15
35	2	updated_profile	App\\Models\\User	2	\N	t	2025-07-04 00:08:08	2025-07-04 00:08:08
36	2	updated_profile	App\\Models\\User	2	\N	t	2025-07-04 00:19:39	2025-07-04 00:19:39
37	2	updated_profile	App\\Models\\User	2	\N	t	2025-07-04 00:24:23	2025-07-04 00:24:23
38	2	updated_profile	App\\Models\\User	2	\N	t	2025-07-04 00:40:58	2025-07-04 00:40:58
39	2	updated_profile	App\\Models\\User	2	\N	t	2025-07-04 00:50:30	2025-07-04 00:50:30
40	2	updated_profile	App\\Models\\User	2	\N	t	2025-07-04 00:54:58	2025-07-04 00:54:58
41	2	updated_profile	App\\Models\\User	2	\N	t	2025-07-04 01:13:25	2025-07-04 01:13:25
42	2	updated_profile	App\\Models\\User	2	\N	t	2025-07-04 01:14:08	2025-07-04 01:14:08
43	2	updated_profile	App\\Models\\User	2	\N	t	2025-07-04 01:21:34	2025-07-04 01:21:34
44	2	updated_profile	App\\Models\\User	2	\N	t	2025-07-04 01:21:51	2025-07-04 01:21:51
45	2	updated_profile	App\\Models\\User	2	\N	t	2025-07-04 01:24:33	2025-07-04 01:24:33
46	2	followed_user	App\\Models\\User	5	\N	t	2025-07-04 02:10:12	2025-07-04 02:10:12
47	5	updated_profile	App\\Models\\User	5	\N	t	2025-07-04 14:46:17	2025-07-04 14:46:17
48	5	updated_profile	App\\Models\\User	5	\N	t	2025-07-04 14:49:44	2025-07-04 14:49:44
49	5	updated_profile	App\\Models\\User	5	\N	t	2025-07-04 15:21:11	2025-07-04 15:21:11
50	5	updated_profile	App\\Models\\User	5	\N	t	2025-07-04 15:21:36	2025-07-04 15:21:36
51	5	updated_profile	App\\Models\\User	5	\N	t	2025-07-04 15:22:40	2025-07-04 15:22:40
52	2	updated_profile	App\\Models\\User	2	\N	t	2025-07-05 23:55:43	2025-07-05 23:55:43
53	2	updated_profile	App\\Models\\User	2	\N	t	2025-07-06 05:30:56	2025-07-06 05:30:56
54	2	updated_profile	App\\Models\\User	2	\N	t	2025-07-06 06:07:19	2025-07-06 06:07:19
55	2	updated_profile	App\\Models\\User	2	\N	t	2025-07-06 06:07:41	2025-07-06 06:07:41
56	2	updated_profile	App\\Models\\User	2	\N	t	2025-07-06 17:26:08	2025-07-06 17:26:08
\.


--
-- Data for Name: user_follows; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.user_follows (id, follower_id, following_id, created_at, updated_at) FROM stdin;
2	5	2	2025-07-02 16:19:51	2025-07-02 16:19:51
3	2	5	2025-07-04 02:10:12	2025-07-04 02:10:12
\.


--
-- Data for Name: user_list_favorites; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.user_list_favorites (id, user_id, list_id, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: user_list_shares; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.user_list_shares (id, list_id, user_id, shared_by, permission, expires_at, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.users (id, name, email, email_verified_at, password, remember_token, created_at, updated_at, username, role, bio, avatar, cover_image, social_links, preferences, permissions, is_public, last_active_at, custom_url, display_title, profile_description, location, website, birth_date, profile_settings, show_activity, show_followers, show_following, profile_views, page_title, custom_css, theme_settings, profile_color, show_join_date, show_location, show_website, avatar_cloudflare_id, cover_cloudflare_id, page_logo_cloudflare_id, phone, page_logo_option, avatar_updated_at, cover_updated_at, default_region_id, firstname, lastname, gender, birthdate, stripe_customer_id) FROM stdin;
1	Admin User	admin@example.com	\N	$2y$12$2brCLHF76fUpD6cacN4q8uLdTuIGkjAn6qo2rlCW48kR5Hzf3rm66	\N	2025-06-01 00:12:11	2025-06-27 05:55:02	adminuser	admin	\N	\N	\N	\N	\N	\N	t	\N	\N	\N	\N	\N	\N	\N	\N	t	t	t	0	\N	\N	\N	\N	t	t	t	\N	\N	\N	\N	initials	\N	\N	\N	Admin	User	\N	\N	\N
3	Test Manager	manager@example.com	\N	$2y$12$I557C68Vv6ksBqCOaymbnubDiASW0rpMRGYJ4cXhRnV5WmNBpDi36	\N	2025-06-01 06:19:41	2025-06-27 05:55:02	testmanager	manager	\N	\N	\N	\N	\N	\N	t	\N	\N	\N	\N	\N	\N	\N	\N	t	t	t	0	\N	\N	\N	\N	t	t	t	\N	\N	\N	\N	initials	\N	\N	\N	Test	Manager	\N	\N	\N
4	Test Editor	editor@example.com	\N	$2y$12$RQ.4BuNIug3.QjdQ78d9heR3FrgjTgxAUGm0grY/BMtIxaQzVpzLK	\N	2025-06-01 06:19:41	2025-06-29 04:28:42	testeditor	editor	\N	\N	\N	\N	\N	\N	t	\N	\N	\N	\N	\N	\N	\N	\N	t	t	t	0	\N	\N	\N	\N	t	t	t	\N	\N	\N	\N	initials	\N	\N	\N	Test	Editor	\N	\N	\N
2	Eric Larson	eric@layercakemarketing.com	\N	$2y$12$PoUVCjvWdxCJD7svQKugbeqND5C7SZEWWnYbmFgTntEM398Qi79R.	\N	2025-06-01 05:41:54	2025-07-28 22:06:35	ericlarson	admin	Creative developer, entrepreneur, husband, dad and independent thinker.	\N	\N	{"twitter":null,"instagram":null,"github":null}	\N	\N	t	\N	layercake	The OG. Listerino	\N	Irvine, California	https://layercake.marketing	\N	\N	t	t	t	11	Mr. Listerino	\N	\N	#3B82F6	t	t	t	0c3689f7-eb8e-4b76-ae8c-f74b6041e600	32e0f547-29d3-472d-cf8d-38f9b7cea000	9f4de8bf-e04b-4e1d-5621-b5f5cb839100	(714) 261-0903	custom	2025-07-28 22:05:36	2025-07-28 22:06:35	\N	Eric	Larson	\N	\N	\N
5	Luggie Riggatoni	info@layercakemarketing.com	\N	$2y$12$5lhe8o6CQ5aeVVikioBqg.G8Zvoc3EhgVjfGGneGf.YP.u.w8f.dK	\N	2025-06-01 06:19:41	2025-07-29 01:17:24	luggie	user	\N	\N	\N	{"twitter":null,"instagram":null,"github":null}	\N	\N	t	\N	Luiggi	\N	\N	\N	\N	\N	\N	t	t	t	2	Chi dorme non piglia pesci	\N	\N	#3B82F6	t	t	t	da4b7f07-3ba3-45e9-9e13-216c625b0500	640efb16-720c-430c-7959-2b6665c57b00	\N	\N	profile	2025-07-29 01:17:02	2025-07-29 01:17:24	\N	Luggie	Riggatoni	\N	\N	\N
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
-- Name: categories_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.categories_id_seq', 29, true);


--
-- Name: channel_followers_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.channel_followers_id_seq', 1, false);


--
-- Name: channels_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.channels_id_seq', 1, false);


--
-- Name: claim_documents_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.claim_documents_id_seq', 1, false);


--
-- Name: claims_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.claims_id_seq', 1, false);


--
-- Name: cloudflare_images_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.cloudflare_images_id_seq', 49, true);


--
-- Name: comments_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.comments_id_seq', 1, false);


--
-- Name: directory_entries_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.directory_entries_id_seq', 99, true);


--
-- Name: directory_entry_follows_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.directory_entry_follows_id_seq', 1, false);


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.failed_jobs_id_seq', 1, false);


--
-- Name: follows_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.follows_id_seq', 1, false);


--
-- Name: home_page_settings_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.home_page_settings_id_seq', 1, false);


--
-- Name: image_uploads_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.image_uploads_id_seq', 1, false);


--
-- Name: jobs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.jobs_id_seq', 1, false);


--
-- Name: likes_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.likes_id_seq', 4, true);


--
-- Name: list_categories_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.list_categories_id_seq', 10, true);


--
-- Name: list_chain_items_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.list_chain_items_id_seq', 1, false);


--
-- Name: list_chains_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.list_chains_id_seq', 1, false);


--
-- Name: list_items_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.list_items_id_seq', 60, true);


--
-- Name: list_media_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.list_media_id_seq', 1, false);


--
-- Name: lists_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.lists_id_seq', 14, true);


--
-- Name: local_page_settings_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.local_page_settings_id_seq', 1, false);


--
-- Name: locations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.locations_id_seq', 44, true);


--
-- Name: login_page_settings_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.login_page_settings_id_seq', 1, true);


--
-- Name: migrations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.migrations_id_seq', 123, true);


--
-- Name: notifications_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.notifications_id_seq', 1, false);


--
-- Name: pages_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.pages_id_seq', 1, false);


--
-- Name: personal_access_tokens_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.personal_access_tokens_id_seq', 1, false);


--
-- Name: pinned_lists_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.pinned_lists_id_seq', 1, false);


--
-- Name: place_managers_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.place_managers_id_seq', 37, true);


--
-- Name: place_regions_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.place_regions_id_seq', 1, false);


--
-- Name: posts_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.posts_id_seq', 2, true);


--
-- Name: region_featured_entries_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.region_featured_entries_id_seq', 1, false);


--
-- Name: region_featured_lists_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.region_featured_lists_id_seq', 1, false);


--
-- Name: region_featured_metadata_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.region_featured_metadata_id_seq', 1, false);


--
-- Name: regions_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.regions_id_seq', 51, true);


--
-- Name: registration_waitlists_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.registration_waitlists_id_seq', 1, false);


--
-- Name: reposts_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.reposts_id_seq', 2, true);


--
-- Name: saved_items_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.saved_items_id_seq', 1, false);


--
-- Name: settings_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.settings_id_seq', 3, true);


--
-- Name: taggables_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.taggables_id_seq', 3, true);


--
-- Name: tags_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.tags_id_seq', 12, true);


--
-- Name: uploaded_images_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.uploaded_images_id_seq', 39, true);


--
-- Name: user_activities_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.user_activities_id_seq', 56, true);


--
-- Name: user_follows_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.user_follows_id_seq', 3, true);


--
-- Name: user_list_favorites_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.user_list_favorites_id_seq', 1, false);


--
-- Name: user_list_shares_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.user_list_shares_id_seq', 1, false);


--
-- Name: users_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.users_id_seq', 6, true);


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
-- Name: channel_followers channel_followers_channel_id_user_id_unique; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.channel_followers
    ADD CONSTRAINT channel_followers_channel_id_user_id_unique UNIQUE (channel_id, user_id);


--
-- Name: channel_followers channel_followers_pkey; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.channel_followers
    ADD CONSTRAINT channel_followers_pkey PRIMARY KEY (id);


--
-- Name: channels channels_pkey; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.channels
    ADD CONSTRAINT channels_pkey PRIMARY KEY (id);


--
-- Name: channels channels_slug_unique; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.channels
    ADD CONSTRAINT channels_slug_unique UNIQUE (slug);


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
-- Name: cloudflare_images cloudflare_images_cloudflare_id_unique; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.cloudflare_images
    ADD CONSTRAINT cloudflare_images_cloudflare_id_unique UNIQUE (cloudflare_id);


--
-- Name: cloudflare_images cloudflare_images_pkey; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.cloudflare_images
    ADD CONSTRAINT cloudflare_images_pkey PRIMARY KEY (id);


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
-- Name: directory_entry_follows directory_entry_follows_pkey; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.directory_entry_follows
    ADD CONSTRAINT directory_entry_follows_pkey PRIMARY KEY (id);


--
-- Name: directory_entry_follows directory_entry_follows_user_id_directory_entry_id_unique; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.directory_entry_follows
    ADD CONSTRAINT directory_entry_follows_user_id_directory_entry_id_unique UNIQUE (user_id, directory_entry_id);


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
-- Name: follows follows_follower_id_followable_id_followable_type_unique; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.follows
    ADD CONSTRAINT follows_follower_id_followable_id_followable_type_unique UNIQUE (follower_id, followable_id, followable_type);


--
-- Name: follows follows_pkey; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.follows
    ADD CONSTRAINT follows_pkey PRIMARY KEY (id);


--
-- Name: home_page_settings home_page_settings_pkey; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.home_page_settings
    ADD CONSTRAINT home_page_settings_pkey PRIMARY KEY (id);


--
-- Name: image_uploads image_uploads_pkey; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.image_uploads
    ADD CONSTRAINT image_uploads_pkey PRIMARY KEY (id);


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
-- Name: list_categories list_categories_pkey; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.list_categories
    ADD CONSTRAINT list_categories_pkey PRIMARY KEY (id);


--
-- Name: list_categories list_categories_slug_unique; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.list_categories
    ADD CONSTRAINT list_categories_slug_unique UNIQUE (slug);


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
-- Name: local_page_settings local_page_settings_pkey; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.local_page_settings
    ADD CONSTRAINT local_page_settings_pkey PRIMARY KEY (id);


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
-- Name: login_page_settings login_page_settings_pkey; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.login_page_settings
    ADD CONSTRAINT login_page_settings_pkey PRIMARY KEY (id);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- Name: notifications notifications_pkey; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.notifications
    ADD CONSTRAINT notifications_pkey PRIMARY KEY (id);


--
-- Name: pages pages_pkey; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.pages
    ADD CONSTRAINT pages_pkey PRIMARY KEY (id);


--
-- Name: pages pages_slug_unique; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.pages
    ADD CONSTRAINT pages_slug_unique UNIQUE (slug);


--
-- Name: password_reset_tokens password_reset_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.password_reset_tokens
    ADD CONSTRAINT password_reset_tokens_pkey PRIMARY KEY (email);


--
-- Name: personal_access_tokens personal_access_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.personal_access_tokens
    ADD CONSTRAINT personal_access_tokens_pkey PRIMARY KEY (id);


--
-- Name: personal_access_tokens personal_access_tokens_token_unique; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.personal_access_tokens
    ADD CONSTRAINT personal_access_tokens_token_unique UNIQUE (token);


--
-- Name: pinned_lists pinned_lists_pkey; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.pinned_lists
    ADD CONSTRAINT pinned_lists_pkey PRIMARY KEY (id);


--
-- Name: pinned_lists pinned_lists_user_id_list_id_unique; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.pinned_lists
    ADD CONSTRAINT pinned_lists_user_id_list_id_unique UNIQUE (user_id, list_id);


--
-- Name: place_managers place_managers_pkey; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.place_managers
    ADD CONSTRAINT place_managers_pkey PRIMARY KEY (id);


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
-- Name: posts posts_pkey; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.posts
    ADD CONSTRAINT posts_pkey PRIMARY KEY (id);


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
-- Name: region_featured_metadata region_featured_metadata_pkey; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.region_featured_metadata
    ADD CONSTRAINT region_featured_metadata_pkey PRIMARY KEY (id);


--
-- Name: region_featured_metadata region_featured_metadata_region_id_unique; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.region_featured_metadata
    ADD CONSTRAINT region_featured_metadata_region_id_unique UNIQUE (region_id);


--
-- Name: regions regions_pkey; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.regions
    ADD CONSTRAINT regions_pkey PRIMARY KEY (id);


--
-- Name: registration_waitlists registration_waitlists_email_unique; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.registration_waitlists
    ADD CONSTRAINT registration_waitlists_email_unique UNIQUE (email);


--
-- Name: registration_waitlists registration_waitlists_invitation_token_unique; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.registration_waitlists
    ADD CONSTRAINT registration_waitlists_invitation_token_unique UNIQUE (invitation_token);


--
-- Name: registration_waitlists registration_waitlists_pkey; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.registration_waitlists
    ADD CONSTRAINT registration_waitlists_pkey PRIMARY KEY (id);


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
-- Name: saved_items saved_items_pkey; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.saved_items
    ADD CONSTRAINT saved_items_pkey PRIMARY KEY (id);


--
-- Name: saved_items saved_items_user_id_saveable_id_saveable_type_unique; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.saved_items
    ADD CONSTRAINT saved_items_user_id_saveable_id_saveable_type_unique UNIQUE (user_id, saveable_id, saveable_type);


--
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- Name: settings settings_key_unique; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.settings
    ADD CONSTRAINT settings_key_unique UNIQUE (key);


--
-- Name: settings settings_pkey; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.settings
    ADD CONSTRAINT settings_pkey PRIMARY KEY (id);


--
-- Name: taggables taggables_pkey; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.taggables
    ADD CONSTRAINT taggables_pkey PRIMARY KEY (id);


--
-- Name: taggables taggables_tag_id_taggable_id_taggable_type_unique; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.taggables
    ADD CONSTRAINT taggables_tag_id_taggable_id_taggable_type_unique UNIQUE (tag_id, taggable_id, taggable_type);


--
-- Name: tags tags_pkey; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.tags
    ADD CONSTRAINT tags_pkey PRIMARY KEY (id);


--
-- Name: tags tags_slug_unique; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.tags
    ADD CONSTRAINT tags_slug_unique UNIQUE (slug);


--
-- Name: place_managers unique_place_manager; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.place_managers
    ADD CONSTRAINT unique_place_manager UNIQUE (place_id, manageable_id, manageable_type);


--
-- Name: uploaded_images uploaded_images_cloudflare_id_unique; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.uploaded_images
    ADD CONSTRAINT uploaded_images_cloudflare_id_unique UNIQUE (cloudflare_id);


--
-- Name: uploaded_images uploaded_images_pkey; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.uploaded_images
    ADD CONSTRAINT uploaded_images_pkey PRIMARY KEY (id);


--
-- Name: user_activities user_activities_pkey; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.user_activities
    ADD CONSTRAINT user_activities_pkey PRIMARY KEY (id);


--
-- Name: user_follows user_follows_follower_id_following_id_unique; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.user_follows
    ADD CONSTRAINT user_follows_follower_id_following_id_unique UNIQUE (follower_id, following_id);


--
-- Name: user_follows user_follows_pkey; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.user_follows
    ADD CONSTRAINT user_follows_pkey PRIMARY KEY (id);


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
-- Name: user_list_shares user_list_shares_list_id_user_id_unique; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.user_list_shares
    ADD CONSTRAINT user_list_shares_list_id_user_id_unique UNIQUE (list_id, user_id);


--
-- Name: user_list_shares user_list_shares_pkey; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.user_list_shares
    ADD CONSTRAINT user_list_shares_pkey PRIMARY KEY (id);


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
-- Name: users users_stripe_customer_id_unique; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_stripe_customer_id_unique UNIQUE (stripe_customer_id);


--
-- Name: users users_username_unique; Type: CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_username_unique UNIQUE (username);


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
-- Name: categories_parent_id_order_index_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX categories_parent_id_order_index_index ON public.categories USING btree (parent_id, order_index);


--
-- Name: categories_slug_parent_id_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX categories_slug_parent_id_index ON public.categories USING btree (slug, parent_id);


--
-- Name: channel_followers_channel_id_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX channel_followers_channel_id_index ON public.channel_followers USING btree (channel_id);


--
-- Name: channel_followers_user_id_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX channel_followers_user_id_index ON public.channel_followers USING btree (user_id);


--
-- Name: channels_slug_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX channels_slug_index ON public.channels USING btree (slug);


--
-- Name: channels_user_id_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX channels_user_id_index ON public.channels USING btree (user_id);


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
-- Name: cloudflare_images_context_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX cloudflare_images_context_index ON public.cloudflare_images USING btree (context);


--
-- Name: cloudflare_images_entity_type_entity_id_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX cloudflare_images_entity_type_entity_id_index ON public.cloudflare_images USING btree (entity_type, entity_id);


--
-- Name: cloudflare_images_uploaded_at_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX cloudflare_images_uploaded_at_index ON public.cloudflare_images USING btree (uploaded_at);


--
-- Name: cloudflare_images_user_id_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX cloudflare_images_user_id_index ON public.cloudflare_images USING btree (user_id);


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
-- Name: directory_entries_city_region_id_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX directory_entries_city_region_id_index ON public.directory_entries USING btree (city_region_id);


--
-- Name: directory_entries_city_region_id_neighborhood_region_id_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX directory_entries_city_region_id_neighborhood_region_id_index ON public.directory_entries USING btree (city_region_id, neighborhood_region_id);


--
-- Name: directory_entries_city_region_id_status_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX directory_entries_city_region_id_status_index ON public.directory_entries USING btree (city_region_id, status);


--
-- Name: directory_entries_likes_count_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX directory_entries_likes_count_index ON public.directory_entries USING btree (likes_count);


--
-- Name: directory_entries_neighborhood_region_id_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX directory_entries_neighborhood_region_id_index ON public.directory_entries USING btree (neighborhood_region_id);


--
-- Name: directory_entries_neighborhood_region_id_status_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX directory_entries_neighborhood_region_id_status_index ON public.directory_entries USING btree (neighborhood_region_id, status);


--
-- Name: directory_entries_owner_user_id_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX directory_entries_owner_user_id_index ON public.directory_entries USING btree (owner_user_id);


--
-- Name: directory_entries_slug_category_id_status_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX directory_entries_slug_category_id_status_index ON public.directory_entries USING btree (slug, category_id, status);


--
-- Name: directory_entries_state_region_id_city_region_id_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX directory_entries_state_region_id_city_region_id_index ON public.directory_entries USING btree (state_region_id, city_region_id);


--
-- Name: directory_entries_state_region_id_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX directory_entries_state_region_id_index ON public.directory_entries USING btree (state_region_id);


--
-- Name: directory_entries_state_region_id_status_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX directory_entries_state_region_id_status_index ON public.directory_entries USING btree (state_region_id, status);


--
-- Name: directory_entries_status_is_featured_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX directory_entries_status_is_featured_index ON public.directory_entries USING btree (status, is_featured);


--
-- Name: directory_entries_subscription_expires_at_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX directory_entries_subscription_expires_at_index ON public.directory_entries USING btree (subscription_expires_at);


--
-- Name: directory_entries_subscription_tier_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX directory_entries_subscription_tier_index ON public.directory_entries USING btree (subscription_tier);


--
-- Name: directory_entries_type_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX directory_entries_type_index ON public.directory_entries USING btree (type);


--
-- Name: directory_entry_follows_directory_entry_id_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX directory_entry_follows_directory_entry_id_index ON public.directory_entry_follows USING btree (directory_entry_id);


--
-- Name: directory_entry_follows_user_id_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX directory_entry_follows_user_id_index ON public.directory_entry_follows USING btree (user_id);


--
-- Name: follows_followable_id_followable_type_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX follows_followable_id_followable_type_index ON public.follows USING btree (followable_id, followable_type);


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
-- Name: idx_regions_boundary; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX idx_regions_boundary ON public.regions USING gist (boundary);


--
-- Name: idx_regions_center_point; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX idx_regions_center_point ON public.regions USING gist (center_point);


--
-- Name: image_uploads_status_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX image_uploads_status_index ON public.image_uploads USING btree (status);


--
-- Name: image_uploads_user_id_status_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX image_uploads_user_id_status_index ON public.image_uploads USING btree (user_id, status);


--
-- Name: jobs_queue_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX jobs_queue_index ON public.jobs USING btree (queue);


--
-- Name: likes_likeable_type_likeable_id_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX likes_likeable_type_likeable_id_index ON public.likes USING btree (likeable_type, likeable_id);


--
-- Name: likes_user_id_created_at_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX likes_user_id_created_at_index ON public.likes USING btree (user_id, created_at);


--
-- Name: list_categories_is_active_sort_order_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX list_categories_is_active_sort_order_index ON public.list_categories USING btree (is_active, sort_order);


--
-- Name: list_chains_owner_type_owner_id_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX list_chains_owner_type_owner_id_index ON public.list_chains USING btree (owner_type, owner_id);


--
-- Name: list_items_list_id_order_index_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX list_items_list_id_order_index_index ON public.list_items USING btree (list_id, order_index);


--
-- Name: list_media_list_id_order_index_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX list_media_list_id_order_index_index ON public.list_media USING btree (list_id, order_index);


--
-- Name: lists_category_id_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX lists_category_id_index ON public.lists USING btree (category_id);


--
-- Name: lists_channel_id_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX lists_channel_id_index ON public.lists USING btree (channel_id);


--
-- Name: lists_likes_count_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX lists_likes_count_index ON public.lists USING btree (likes_count);


--
-- Name: lists_order_index_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX lists_order_index_index ON public.lists USING btree (order_index);


--
-- Name: lists_owner_id_owner_type_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX lists_owner_id_owner_type_index ON public.lists USING btree (owner_id, owner_type);


--
-- Name: lists_owner_type_owner_id_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX lists_owner_type_owner_id_index ON public.lists USING btree (owner_type, owner_id);


--
-- Name: lists_reposts_count_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX lists_reposts_count_index ON public.lists USING btree (reposts_count);


--
-- Name: lists_status_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX lists_status_index ON public.lists USING btree (status);


--
-- Name: lists_type_is_category_specific_category_id_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX lists_type_is_category_specific_category_id_index ON public.lists USING btree (type, is_category_specific, category_id);


--
-- Name: lists_type_is_featured_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX lists_type_is_featured_index ON public.lists USING btree (type, is_featured);


--
-- Name: lists_type_is_region_specific_region_id_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX lists_type_is_region_specific_region_id_index ON public.lists USING btree (type, is_region_specific, region_id);


--
-- Name: lists_user_id_is_pinned_pinned_at_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX lists_user_id_is_pinned_pinned_at_index ON public.lists USING btree (user_id, is_pinned, pinned_at);


--
-- Name: lists_user_id_visibility_is_pinned_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX lists_user_id_visibility_is_pinned_index ON public.lists USING btree (user_id, visibility, is_pinned);


--
-- Name: lists_visibility_is_draft_published_at_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX lists_visibility_is_draft_published_at_index ON public.lists USING btree (visibility, is_draft, published_at);


--
-- Name: local_page_settings_page_type_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX local_page_settings_page_type_index ON public.local_page_settings USING btree (page_type);


--
-- Name: local_page_settings_region_id_page_type_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX local_page_settings_region_id_page_type_index ON public.local_page_settings USING btree (region_id, page_type);


--
-- Name: locations_city_state_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX locations_city_state_index ON public.locations USING btree (city, state);


--
-- Name: locations_geom_gist; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX locations_geom_gist ON public.locations USING gist (geom);


--
-- Name: notifications_notifiable_type_notifiable_id_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX notifications_notifiable_type_notifiable_id_index ON public.notifications USING btree (notifiable_type, notifiable_id);


--
-- Name: notifications_type_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX notifications_type_index ON public.notifications USING btree (type);


--
-- Name: notifications_user_id_created_at_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX notifications_user_id_created_at_index ON public.notifications USING btree (user_id, created_at);


--
-- Name: notifications_user_id_is_read_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX notifications_user_id_is_read_index ON public.notifications USING btree (user_id, is_read);


--
-- Name: pages_slug_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX pages_slug_index ON public.pages USING btree (slug);


--
-- Name: pages_status_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX pages_status_index ON public.pages USING btree (status);


--
-- Name: personal_access_tokens_tokenable_type_tokenable_id_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX personal_access_tokens_tokenable_type_tokenable_id_index ON public.personal_access_tokens USING btree (tokenable_type, tokenable_id);


--
-- Name: pinned_lists_user_id_sort_order_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX pinned_lists_user_id_sort_order_index ON public.pinned_lists USING btree (user_id, sort_order);


--
-- Name: place_managers_manageable_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX place_managers_manageable_index ON public.place_managers USING btree (manageable_type, manageable_id);


--
-- Name: place_managers_manageable_type_manageable_id_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX place_managers_manageable_type_manageable_id_index ON public.place_managers USING btree (manageable_type, manageable_id);


--
-- Name: place_managers_role_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX place_managers_role_index ON public.place_managers USING btree (role);


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
-- Name: posts_deleted_at_expires_at_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX posts_deleted_at_expires_at_index ON public.posts USING btree (deleted_at, expires_at);


--
-- Name: posts_expires_at_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX posts_expires_at_index ON public.posts USING btree (expires_at);


--
-- Name: posts_user_id_created_at_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX posts_user_id_created_at_index ON public.posts USING btree (user_id, created_at);


--
-- Name: posts_user_id_is_tacked_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX posts_user_id_is_tacked_index ON public.posts USING btree (user_id, is_tacked);


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
-- Name: region_featured_lists_priority_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX region_featured_lists_priority_index ON public.region_featured_lists USING btree (priority);


--
-- Name: region_featured_lists_region_id_is_active_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX region_featured_lists_region_id_is_active_index ON public.region_featured_lists USING btree (region_id, is_active);


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
-- Name: registration_waitlists_created_at_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX registration_waitlists_created_at_index ON public.registration_waitlists USING btree (created_at);


--
-- Name: registration_waitlists_status_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX registration_waitlists_status_index ON public.registration_waitlists USING btree (status);


--
-- Name: reposts_repostable_type_repostable_id_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX reposts_repostable_type_repostable_id_index ON public.reposts USING btree (repostable_type, repostable_id);


--
-- Name: reposts_user_id_created_at_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX reposts_user_id_created_at_index ON public.reposts USING btree (user_id, created_at);


--
-- Name: saved_items_created_at_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX saved_items_created_at_index ON public.saved_items USING btree (created_at);


--
-- Name: saved_items_saveable_type_saveable_id_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX saved_items_saveable_type_saveable_id_index ON public.saved_items USING btree (saveable_type, saveable_id);


--
-- Name: saved_items_user_id_saveable_type_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX saved_items_user_id_saveable_type_index ON public.saved_items USING btree (user_id, saveable_type);


--
-- Name: sessions_last_activity_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX sessions_last_activity_index ON public.sessions USING btree (last_activity);


--
-- Name: sessions_user_id_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX sessions_user_id_index ON public.sessions USING btree (user_id);


--
-- Name: settings_group_key_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX settings_group_key_index ON public.settings USING btree ("group", key);


--
-- Name: taggables_taggable_type_taggable_id_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX taggables_taggable_type_taggable_id_index ON public.taggables USING btree (taggable_type, taggable_id);


--
-- Name: tags_slug_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX tags_slug_index ON public.tags USING btree (slug);


--
-- Name: tags_type_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX tags_type_index ON public.tags USING btree (type);


--
-- Name: tags_usage_count_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX tags_usage_count_index ON public.tags USING btree (usage_count);


--
-- Name: uploaded_images_type_entity_id_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX uploaded_images_type_entity_id_index ON public.uploaded_images USING btree (type, entity_id);


--
-- Name: uploaded_images_user_id_type_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX uploaded_images_user_id_type_index ON public.uploaded_images USING btree (user_id, type);


--
-- Name: user_activities_subject_type_subject_id_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX user_activities_subject_type_subject_id_index ON public.user_activities USING btree (subject_type, subject_id);


--
-- Name: user_activities_user_id_activity_type_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX user_activities_user_id_activity_type_index ON public.user_activities USING btree (user_id, activity_type);


--
-- Name: user_activities_user_id_is_public_created_at_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX user_activities_user_id_is_public_created_at_index ON public.user_activities USING btree (user_id, is_public, created_at);


--
-- Name: user_follows_follower_id_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX user_follows_follower_id_index ON public.user_follows USING btree (follower_id);


--
-- Name: user_follows_following_id_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX user_follows_following_id_index ON public.user_follows USING btree (following_id);


--
-- Name: user_list_shares_expires_at_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX user_list_shares_expires_at_index ON public.user_list_shares USING btree (expires_at);


--
-- Name: user_list_shares_user_id_list_id_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX user_list_shares_user_id_list_id_index ON public.user_list_shares USING btree (user_id, list_id);


--
-- Name: users_custom_url_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX users_custom_url_index ON public.users USING btree (custom_url);


--
-- Name: users_firstname_lastname_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX users_firstname_lastname_index ON public.users USING btree (firstname, lastname);


--
-- Name: users_role_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX users_role_index ON public.users USING btree (role);


--
-- Name: users_stripe_customer_id_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX users_stripe_customer_id_index ON public.users USING btree (stripe_customer_id);


--
-- Name: users_username_index; Type: INDEX; Schema: public; Owner: ericslarson
--

CREATE INDEX users_username_index ON public.users USING btree (username);


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
-- Name: locations update_location_geom_trigger; Type: TRIGGER; Schema: public; Owner: ericslarson
--

CREATE TRIGGER update_location_geom_trigger BEFORE INSERT OR UPDATE ON public.locations FOR EACH ROW WHEN (((new.latitude IS NOT NULL) AND (new.longitude IS NOT NULL))) EXECUTE FUNCTION public.update_location_geom();


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
-- Name: categories categories_parent_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.categories
    ADD CONSTRAINT categories_parent_id_foreign FOREIGN KEY (parent_id) REFERENCES public.categories(id) ON DELETE CASCADE;


--
-- Name: channel_followers channel_followers_channel_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.channel_followers
    ADD CONSTRAINT channel_followers_channel_id_foreign FOREIGN KEY (channel_id) REFERENCES public.channels(id) ON DELETE CASCADE;


--
-- Name: channel_followers channel_followers_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.channel_followers
    ADD CONSTRAINT channel_followers_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: channels channels_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.channels
    ADD CONSTRAINT channels_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


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
-- Name: cloudflare_images cloudflare_images_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.cloudflare_images
    ADD CONSTRAINT cloudflare_images_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE SET NULL;


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
-- Name: directory_entries directory_entries_approved_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.directory_entries
    ADD CONSTRAINT directory_entries_approved_by_foreign FOREIGN KEY (approved_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: directory_entries directory_entries_category_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.directory_entries
    ADD CONSTRAINT directory_entries_category_id_foreign FOREIGN KEY (category_id) REFERENCES public.categories(id) ON DELETE SET NULL;


--
-- Name: directory_entries directory_entries_city_region_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.directory_entries
    ADD CONSTRAINT directory_entries_city_region_id_foreign FOREIGN KEY (city_region_id) REFERENCES public.regions(id) ON DELETE SET NULL;


--
-- Name: directory_entries directory_entries_created_by_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.directory_entries
    ADD CONSTRAINT directory_entries_created_by_user_id_foreign FOREIGN KEY (created_by_user_id) REFERENCES public.users(id);


--
-- Name: directory_entries directory_entries_neighborhood_region_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.directory_entries
    ADD CONSTRAINT directory_entries_neighborhood_region_id_foreign FOREIGN KEY (neighborhood_region_id) REFERENCES public.regions(id) ON DELETE SET NULL;


--
-- Name: directory_entries directory_entries_owner_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.directory_entries
    ADD CONSTRAINT directory_entries_owner_user_id_foreign FOREIGN KEY (owner_user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: directory_entries directory_entries_ownership_transferred_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.directory_entries
    ADD CONSTRAINT directory_entries_ownership_transferred_by_foreign FOREIGN KEY (ownership_transferred_by) REFERENCES public.users(id);


--
-- Name: directory_entries directory_entries_region_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.directory_entries
    ADD CONSTRAINT directory_entries_region_id_foreign FOREIGN KEY (region_id) REFERENCES public.regions(id) ON DELETE SET NULL;


--
-- Name: directory_entries directory_entries_rejected_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.directory_entries
    ADD CONSTRAINT directory_entries_rejected_by_foreign FOREIGN KEY (rejected_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: directory_entries directory_entries_state_region_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.directory_entries
    ADD CONSTRAINT directory_entries_state_region_id_foreign FOREIGN KEY (state_region_id) REFERENCES public.regions(id) ON DELETE SET NULL;


--
-- Name: directory_entries directory_entries_updated_by_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.directory_entries
    ADD CONSTRAINT directory_entries_updated_by_user_id_foreign FOREIGN KEY (updated_by_user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: directory_entry_follows directory_entry_follows_directory_entry_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.directory_entry_follows
    ADD CONSTRAINT directory_entry_follows_directory_entry_id_foreign FOREIGN KEY (directory_entry_id) REFERENCES public.directory_entries(id) ON DELETE CASCADE;


--
-- Name: directory_entry_follows directory_entry_follows_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.directory_entry_follows
    ADD CONSTRAINT directory_entry_follows_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: follows follows_follower_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.follows
    ADD CONSTRAINT follows_follower_id_foreign FOREIGN KEY (follower_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: image_uploads image_uploads_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.image_uploads
    ADD CONSTRAINT image_uploads_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


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
-- Name: lists lists_category_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.lists
    ADD CONSTRAINT lists_category_id_foreign FOREIGN KEY (category_id) REFERENCES public.list_categories(id) ON DELETE SET NULL;


--
-- Name: lists lists_channel_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.lists
    ADD CONSTRAINT lists_channel_id_foreign FOREIGN KEY (channel_id) REFERENCES public.channels(id) ON DELETE SET NULL;


--
-- Name: lists lists_region_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.lists
    ADD CONSTRAINT lists_region_id_foreign FOREIGN KEY (region_id) REFERENCES public.regions(id) ON DELETE CASCADE;


--
-- Name: lists lists_status_changed_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.lists
    ADD CONSTRAINT lists_status_changed_by_foreign FOREIGN KEY (status_changed_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: lists lists_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.lists
    ADD CONSTRAINT lists_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: local_page_settings local_page_settings_region_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.local_page_settings
    ADD CONSTRAINT local_page_settings_region_id_foreign FOREIGN KEY (region_id) REFERENCES public.regions(id) ON DELETE CASCADE;


--
-- Name: locations locations_directory_entry_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.locations
    ADD CONSTRAINT locations_directory_entry_id_foreign FOREIGN KEY (directory_entry_id) REFERENCES public.directory_entries(id) ON DELETE CASCADE;


--
-- Name: notifications notifications_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.notifications
    ADD CONSTRAINT notifications_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: pinned_lists pinned_lists_list_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.pinned_lists
    ADD CONSTRAINT pinned_lists_list_id_foreign FOREIGN KEY (list_id) REFERENCES public.lists(id) ON DELETE CASCADE;


--
-- Name: pinned_lists pinned_lists_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.pinned_lists
    ADD CONSTRAINT pinned_lists_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: place_managers place_managers_invited_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.place_managers
    ADD CONSTRAINT place_managers_invited_by_foreign FOREIGN KEY (invited_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: place_managers place_managers_place_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.place_managers
    ADD CONSTRAINT place_managers_place_id_foreign FOREIGN KEY (place_id) REFERENCES public.directory_entries(id) ON DELETE CASCADE;


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
-- Name: posts posts_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.posts
    ADD CONSTRAINT posts_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


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
-- Name: region_featured_metadata region_featured_metadata_region_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.region_featured_metadata
    ADD CONSTRAINT region_featured_metadata_region_id_foreign FOREIGN KEY (region_id) REFERENCES public.regions(id) ON DELETE CASCADE;


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
-- Name: reposts reposts_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.reposts
    ADD CONSTRAINT reposts_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: saved_items saved_items_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.saved_items
    ADD CONSTRAINT saved_items_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: taggables taggables_tag_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.taggables
    ADD CONSTRAINT taggables_tag_id_foreign FOREIGN KEY (tag_id) REFERENCES public.tags(id) ON DELETE CASCADE;


--
-- Name: tags tags_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.tags
    ADD CONSTRAINT tags_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: uploaded_images uploaded_images_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.uploaded_images
    ADD CONSTRAINT uploaded_images_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: user_activities user_activities_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.user_activities
    ADD CONSTRAINT user_activities_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: user_follows user_follows_follower_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.user_follows
    ADD CONSTRAINT user_follows_follower_id_foreign FOREIGN KEY (follower_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: user_follows user_follows_following_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.user_follows
    ADD CONSTRAINT user_follows_following_id_foreign FOREIGN KEY (following_id) REFERENCES public.users(id) ON DELETE CASCADE;


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
-- Name: user_list_shares user_list_shares_list_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.user_list_shares
    ADD CONSTRAINT user_list_shares_list_id_foreign FOREIGN KEY (list_id) REFERENCES public.lists(id) ON DELETE CASCADE;


--
-- Name: user_list_shares user_list_shares_shared_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.user_list_shares
    ADD CONSTRAINT user_list_shares_shared_by_foreign FOREIGN KEY (shared_by) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: user_list_shares user_list_shares_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.user_list_shares
    ADD CONSTRAINT user_list_shares_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: users users_default_region_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_default_region_id_foreign FOREIGN KEY (default_region_id) REFERENCES public.regions(id) ON DELETE SET NULL;


--
-- PostgreSQL database dump complete
--

