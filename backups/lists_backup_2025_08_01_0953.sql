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
-- Name: lists id; Type: DEFAULT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.lists ALTER COLUMN id SET DEFAULT nextval('public.lists_id_seq'::regclass);


--
-- Data for Name: lists; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.lists (id, user_id, name, description, created_at, updated_at, featured_image, slug, view_count, settings, is_featured, featured_image_cloudflare_id, category_id, visibility, is_draft, published_at, scheduled_for, gallery_images, is_pinned, pinned_at, status, status_reason, status_changed_at, status_changed_by, type, is_region_specific, region_id, is_category_specific, place_ids, order_index, channel_id, owner_type, owner_id, likes_count, comments_count, reposts_count) FROM stdin;
15	2	Test List	\N	2025-07-29 16:24:29	2025-07-29 21:16:32	\N	test-list	0	\N	f	\N	1	public	f	2025-07-29 16:24:29	\N	[]	f	\N	active	\N	\N	\N	user	f	\N	f	\N	0	\N	App\\Models\\User	2	1	0	0
16	2	Top 10 80's Video Grames	Based on cultural impact, sales, innovation, and legacy across arcades, consoles, and computers:	2025-07-30 04:58:48	2025-07-30 04:58:48	\N	top-10-80s-video-grames	0	\N	f	\N	\N	public	f	\N	\N	\N	f	\N	active	\N	\N	\N	user	f	\N	f	\N	0	\N	App\\Models\\User	2	0	0	0
17	2	Tips for the Best DYI Car Wash	\N	2025-07-30 04:58:48	2025-07-30 04:58:48	\N	tips-for-the-best-dyi-car-wash	0	\N	f	\N	\N	public	f	\N	\N	\N	f	\N	active	\N	\N	\N	user	f	\N	f	\N	0	\N	App\\Models\\User	2	0	0	0
19	2	The Top 10 things to do in Woodbridge, (Irvine, California) neighborhood.	Here are the top 10 things to do in Woodbridge, Irvine, CA—perfect for enjoying lakeside fun, sports, local eats, and scenic strolls 🌿:	2025-07-30 04:58:48	2025-07-30 04:58:48	\N	the-top-10-things-to-do-in-woodbridge-irvine-california-neighborhood	0	\N	f	\N	\N	public	f	\N	\N	\N	f	\N	active	\N	\N	\N	user	f	\N	f	\N	0	\N	App\\Models\\User	2	0	0	0
20	2	White Water Rafting the American River	\N	2025-07-30 04:58:48	2025-07-30 04:58:48	\N	white-water-rafting-the-american-river	0	\N	f	\N	\N	public	f	\N	\N	\N	f	\N	active	\N	\N	\N	user	f	\N	f	\N	0	\N	App\\Models\\User	2	0	0	0
21	1	Best Restaurants in Town	Editor's picks for the best dining experiences	2025-07-30 04:58:48	2025-07-30 04:58:48	\N	best-restaurants-in-town	0	\N	f	\N	\N	public	f	\N	\N	\N	f	\N	active	\N	\N	\N	user	f	\N	f	\N	0	\N	App\\Models\\User	1	0	0	0
22	1	Top Places to Visit	Must-see places handpicked by our editors	2025-07-30 04:58:48	2025-07-30 04:58:48	\N	top-places-to-visit	0	\N	f	\N	\N	public	f	\N	\N	\N	f	\N	active	\N	\N	\N	user	f	\N	f	\N	0	\N	App\\Models\\User	1	0	0	0
23	5	Luiggi's Famous Artisan Pizza Recipe	\N	2025-07-30 04:58:48	2025-08-01 06:01:15	\N	luiggis-famous-artisan-pizza-recipe	1	\N	f	\N	\N	public	f	\N	\N	\N	f	\N	active	\N	\N	\N	user	f	\N	f	\N	0	\N	App\\Models\\User	5	0	0	0
18	2	Fun Day at the Park List	\N	2025-07-30 04:58:48	2025-08-01 06:52:58	https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A/25dcaf46-efac-4e36-abed-544494e47200/lgformat	fun-day-at-the-park-list	0	\N	f	25dcaf46-efac-4e36-abed-544494e47200	1	public	f	\N	\N	[{"id":"25dcaf46-efac-4e36-abed-544494e47200","url":"https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/25dcaf46-efac-4e36-abed-544494e47200\\/lgformat","filename":"dining-area.jpg"}]	f	\N	active	\N	\N	\N	user	f	\N	f	\N	0	\N	App\\Models\\User	2	0	0	0
\.


--
-- Name: lists_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.lists_id_seq', 23, true);


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
-- PostgreSQL database dump complete
--

