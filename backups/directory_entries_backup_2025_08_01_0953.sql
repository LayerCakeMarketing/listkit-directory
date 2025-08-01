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
-- Name: directory_entries id; Type: DEFAULT; Schema: public; Owner: ericslarson
--

ALTER TABLE ONLY public.directory_entries ALTER COLUMN id SET DEFAULT nextval('public.directory_entries_id_seq'::regclass);


--
-- Data for Name: directory_entries; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.directory_entries (id, title, slug, description, type, category_id, region_id, tags, owner_user_id, created_by_user_id, updated_by_user_id, phone, email, website_url, social_links, featured_image, gallery_images, status, is_featured, is_verified, is_claimed, meta_title, meta_description, structured_data, view_count, list_count, created_at, updated_at, published_at, logo_url, cover_image_url, facebook_url, instagram_handle, twitter_handle, youtube_channel, messenger_contact, price_range, takes_reservations, accepts_credit_cards, wifi_available, pet_friendly, parking_options, wheelchair_accessible, outdoor_seating, kid_friendly, video_urls, pdf_files, hours_of_operation, special_hours, temporarily_closed, open_24_7, cross_streets, neighborhood, state_region_id, city_region_id, neighborhood_region_id, regions_updated_at, links, rejection_reason, rejected_at, rejected_by, approval_notes, approved_by, subscription_tier, subscription_expires_at, stripe_customer_id, stripe_subscription_id, claimed_at, ownership_transferred_at, ownership_transferred_by, likes_count, comments_count) FROM stdin;
3	Serenity Spa & Wellness	serenity-spa-wellness	Relax and rejuvenate with our premium spa services.	business_b2c	14	85	\N	\N	2	2	\N	\N	\N	\N	\N	\N	published	t	f	f	\N	\N	\N	0	0	2025-06-01 20:18:34	2025-07-30 06:53:22	2025-07-30 06:18:42	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	84	85	\N	2025-07-30 06:35:42	\N	\N	\N	\N	\N	\N	free	\N	\N	\N	\N	\N	\N	0	0
79	Jax at Mammoth	jax-at-mammoth	Women's apparel, accessories and gifts.	business_b2c	7	86	\N	\N	2	2	\N	\N	\N	\N	\N	\N	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-07-30 06:53:22	2025-07-30 06:18:42	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	82	86	\N	2025-07-30 06:35:42	\N	\N	\N	\N	\N	\N	free	\N	\N	\N	\N	\N	\N	0	0
80	Franz Klammer Sports	franz-klammer-sports	Ski and snowboard equipment rental and retail.	business_b2c	7	86	\N	\N	2	2	\N	\N	\N	\N	\N	\N	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-07-30 06:53:22	2025-07-30 06:18:42	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	82	86	\N	2025-07-30 06:35:42	\N	\N	\N	\N	\N	\N	free	\N	\N	\N	\N	\N	\N	0	0
77	Smokeyard BBQ	smokeyard-bbq	<h3>Steakhouse fare, with South African influence in a casual, chic atmosphere.&nbsp; The perfectly crafted menu of high quality food and tasty cocktails makes Smokeyard Bbq and Chop Shop a must-have destination in the heart of the Village at Mammoth.</h3><p></p><h3><strong>We have a handful of available diner reservations available- we have tables available between 4PM and 5:15PM and from 7:45 to 9:30PM.</strong></h3>	business_b2c	5	86	\N	\N	2	2	\N	\N	\N	\N	\N	\N	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-07-30 06:53:22	2025-07-30 06:18:42	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	82	86	\N	2025-07-30 06:35:42	\N	\N	\N	\N	\N	\N	free	\N	\N	\N	\N	\N	\N	0	0
98	Mammoth Lakes Museum	mammoth-lakes-museum	<h1>Our Hours</h1><p><strong>June-september: </strong>10 AM to 6 PM</p><p><strong>Open Monday-Sunday, closed on Wednesdays</strong></p><p><strong>We are open six days a week Memorial Weekend through the end of September.</strong></p><hr><h1>Other Information</h1><p><strong>Fees</strong>: FREE! A $5.00 donation is suggested &amp; memberships are appreciated.</p><p><strong>ADA Accessibility:&nbsp;</strong>Access is available for those who park on the lawn near the disabled placard and enter through the kitchen (back door) on the ramp.</p><p><strong>Eco-Friendly:&nbsp;</strong>Connected to the town’s bike trails in Mammoth Lakes.</p><p><strong>Pet Friendly: &nbsp;</strong>Pets should be leashed on the museum grounds per the Town of Mammoth Lakes and the U.S. Forest Service leash laws. Service animals only are welcome inside the museum. We provide kennels a.k.a “doggie dorms” outside for non-service animals. <strong>Please clean up after your pets!</strong></p>	business_b2c	21	86	\N	\N	2	2	\N	\N	\N	\N	\N	\N	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-07-30 06:53:22	2025-07-30 06:18:42	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	82	86	\N	2025-07-30 06:35:42	\N	\N	\N	\N	\N	\N	free	\N	\N	\N	\N	\N	\N	0	0
84	Denali Surf & Sport	denali-surf-sport	Surf, skate, and snow gear.	business_b2c	7	86	\N	\N	2	2	\N	\N	\N	\N	\N	\N	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-07-30 06:53:22	2025-07-30 06:18:42	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	82	86	\N	2025-07-30 05:34:34	\N	\N	\N	\N	\N	\N	free	\N	\N	\N	\N	\N	\N	0	0
85	The Powder House & Gear	the-powder-house-gear	Ski, snowboard rentals and outerwear retail.	business_b2c	7	86	\N	\N	2	2	\N	\N	\N	\N	\N	\N	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-07-30 06:53:22	2025-07-30 06:18:42	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	82	86	\N	2025-07-30 05:34:34	\N	\N	\N	\N	\N	\N	free	\N	\N	\N	\N	\N	\N	0	0
86	Mammoth Camper & RV	mammoth-camper-rv	Camper rental and outdoor gear.	business_b2c	7	86	\N	\N	2	2	\N	\N	\N	\N	\N	\N	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-07-30 06:53:22	2025-07-30 06:18:42	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	82	86	\N	2025-07-30 05:34:34	\N	\N	\N	\N	\N	\N	free	\N	\N	\N	\N	\N	\N	0	0
87	Mountain Shop	mountain-shop	Outdoor clothing and equipment retailer.	business_b2c	7	86	\N	\N	2	2	\N	\N	\N	\N	\N	\N	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-07-30 06:53:22	2025-07-30 06:18:42	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	82	86	\N	2025-07-30 05:34:34	\N	\N	\N	\N	\N	\N	free	\N	\N	\N	\N	\N	\N	0	0
90	Mammoth Lakes Welcome Center	mammoth-lakes-welcome-center	Visitor center with local information and gift shop.	business_b2c	17	86	\N	\N	2	2	\N	\N	\N	\N	\N	\N	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-07-30 06:53:22	2025-07-30 06:18:42	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	82	86	\N	2025-07-30 05:34:34	\N	\N	\N	\N	\N	\N	free	\N	\N	\N	\N	\N	\N	0	0
88	Majestic Fine Art	majestic-fine-art	Fine art gallery featuring local artists.	business_b2c	7	86	\N	\N	2	\N	\N	\N	\N	\N	\N	\N	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-07-30 06:53:22	2025-07-30 06:18:42	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	82	86	\N	\N	\N	\N	\N	\N	\N	\N	free	\N	\N	\N	\N	\N	\N	0	0
99	Mammoth Mountaineering Supply	mammoth-mountaineering-supply	<p>Ski Shop</p>	business_b2c	28	86	\N	\N	2	2	\N	\N	\N	\N	\N	\N	published	f	f	f	\N	\N	\N	0	0	2025-07-05 19:03:22	2025-07-30 06:53:22	2025-07-30 06:18:42	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	82	86	\N	2025-07-30 05:34:34	\N	\N	\N	\N	\N	\N	free	\N	\N	\N	\N	\N	\N	0	0
81	Mammoth Mountain Ski & Board	mammoth-mountain-ski-board	Ski and snowboard rentals plus retail shop.	business_b2c	28	86	\N	\N	2	2	\N	\N	\N	\N	\N	\N	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-07-30 06:53:22	2025-07-30 06:18:42	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	82	86	\N	2025-07-30 06:35:42	\N	\N	\N	\N	\N	\N	free	\N	\N	\N	\N	\N	\N	0	0
72	Burgers Restaurant	burgers-restaurant	Gourmet burgers, fries, and milkshakes in a casual setting.	business_b2c	5	86	\N	\N	2	2	\N	\N	\N	\N	\N	\N	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-07-30 06:53:22	2025-07-30 06:18:42	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	82	86	\N	2025-07-30 06:35:42	\N	\N	\N	\N	\N	\N	free	\N	\N	\N	\N	\N	\N	0	0
74	Skadi	skadi	Upscale fine dining with French-inspired cuisine.	business_b2c	1	86	\N	\N	2	2	\N	\N	\N	\N	\N	\N	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-07-30 06:53:22	2025-07-30 06:18:42	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	82	86	\N	2025-07-30 06:35:42	\N	\N	\N	\N	\N	\N	free	\N	\N	\N	\N	\N	\N	0	0
73	Sazon Restaurant	sazon-restaurant	Mexican and Latin American cuisine with tequila bar.	business_b2c	1	86	\N	\N	2	2	\N	\N	\N	\N	\N	\N	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-07-30 06:53:22	2025-07-30 06:18:42	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	82	86	\N	2025-07-30 06:35:42	\N	\N	\N	\N	\N	\N	free	\N	\N	\N	\N	\N	\N	0	0
2	TechHub Electronics	techhub-electronics	Your one-stop shop for all electronics needs. Best prices guaranteed!	business_b2c	9	32	\N	\N	2	2	\N	\N	\N	\N	\N	\N	published	t	f	f	\N	\N	\N	0	0	2025-06-01 20:18:34	2025-07-30 06:53:22	2025-07-30 06:18:42	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	82	32	\N	2025-07-30 06:35:42	\N	\N	\N	\N	\N	\N	free	\N	\N	\N	\N	\N	\N	0	0
97	Woolly's Tube Park	woollys-tube-park	Snow tubing park with multiple lanes and lifts.	business_b2c	17	86	\N	\N	2	2	\N	\N	\N	\N	\N	\N	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-07-30 06:53:22	2025-07-30 06:18:42	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	82	86	\N	2025-07-30 06:35:42	\N	\N	\N	\N	\N	\N	free	\N	\N	\N	\N	\N	\N	0	0
4	CloudTech Solutions	cloudtech-solutions	Enterprise cloud computing solutions for modern businesses.	online	26	88	\N	\N	2	2	\N	\N	\N	\N	\N	\N	published	t	t	f	\N	\N	\N	0	0	2025-06-01 20:18:34	2025-07-30 06:53:22	2025-07-30 06:18:42	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	82	88	\N	2025-07-30 06:36:06	\N	\N	\N	\N	\N	\N	free	\N	\N	\N	\N	\N	\N	0	0
106	Cha For Tea	cha-for-tea	<p>Local chain serving hot &amp; iced teas, plus Taiwanese dishes, in a contemporary space.</p>\n<script> do something bad </script>	business_b2c	30	6	\N	\N	2	2	\N	\N	\N	\N	\N	\N	published	f	f	f	\N	\N	\N	0	0	2025-07-20 16:21:50	2025-07-30 06:53:22	2025-07-30 06:18:42	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	82	6	\N	2025-07-30 05:34:34	\N	\N	\N	\N	\N	\N	free	\N	\N	\N	\N	\N	\N	0	0
107	The Lost Bean	the-lost-bean	<p>Organic Coffee &amp; Tea</p>	business_b2c	31	6	\N	\N	2	2	\N	\N	\N	\N	\N	\N	published	f	f	f	\N	\N	\N	0	0	2025-07-20 23:13:42	2025-07-30 06:53:22	2025-07-30 06:18:42	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	82	6	\N	2025-07-30 05:34:34	\N	\N	\N	\N	\N	\N	free	\N	\N	\N	\N	\N	\N	0	0
95	Mammoth Brewing Co. Live Stage	mammoth-brewing-co-live-stage	<p>Live music venue inside Mammoth Brewing Company.</p><p><strong><em>Mammoth Brewing Co.:</em></strong><br>Sunday – Thursday: 10am – 9pm*<br>Friday &amp; Saturday: 10am – 9:30pm*</p><p><strong><em>The EATery:</em></strong><br>Daily: 11:00am – Close</p><p>*Closing times are subject to early closures during shoulder seasons</p><p><strong>Reservations:</strong> We do not take reservations.</p><p></p><h2><strong>Welcome to MBC &amp; The EATery<br></strong></h2><p>We’ve been obsessively crafting beers since 1995. We pioneer, curious and unfaltering, determined not to stop until we achieve mouth-watering perfection, often at the expense of reasonable bedtimes and our social lives. Pulling inspiration from our natural surroundings, we boldly blend the best of our local ingredients with the know-how we’ve picked up from years of brewing award-winning beer.</p><p>Our brewery, tasting room, beer garden, and retail store are located at 18 Lake Mary Road, at the corner of Main Street &amp; Minaret Road in Mammoth Lakes. The EATery is located in the tasting room, supplying amazing beer-centric food by chef Brandon Brocia. Check out The EATery menu here. Savor a pint and a bite to eat, sample a tasting flight, pick up a 6-pack, fill your growler, and enjoy the mountain views and friendly atmosphere.</p><p>Your well-behaved, leashed pupper is welcome in our outdoor beer garden. No barking, biting, or begging, and don't leave your trail buddy unattended.</p><p>Fun events include live music and Trivia Night, and we also host private events. See our Events page for more information.</p>	business_b2c	5	86	\N	\N	2	2	\N	\N	\N	\N	\N	\N	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-07-30 06:53:22	2025-07-30 06:18:42	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	82	86	\N	2025-07-30 06:35:42	\N	\N	\N	\N	\N	\N	free	\N	\N	\N	\N	\N	\N	0	0
69	The Mogul Restaurant	the-mogul-restaurant	<p>Historic mountain lodge restaurant offering American cuisine.</p><p><strong>Open 7 days each week.<br>Closed Tuesdays May 6, 13, 20th<br>Open at 5:00pm in the Bar and 5:30pm for dinner.<br><br>Kid's Menu Available * Full Bar<br><br>Don't know where we are? Check our Location<br><br>Reservations Accepted: 760-934-3039</strong></p><p></p>	business_b2c	5	86	\N	\N	2	2	\N	\N	\N	\N	\N	\N	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-07-30 06:53:22	2025-07-30 06:18:42	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	82	86	\N	2025-07-30 06:35:42	\N	\N	\N	\N	\N	\N	free	\N	\N	\N	\N	\N	\N	0	0
70	Roberto's Café	robertos-cafe	Casual Italian restaurant serving wood-fired pizzas and homemade pasta.	business_b2c	3	86	\N	\N	2	2	\N	\N	\N	\N	\N	\N	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-07-30 06:53:22	2025-07-30 06:18:42	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	82	86	\N	2025-07-30 06:35:42	\N	\N	\N	\N	\N	\N	free	\N	\N	\N	\N	\N	\N	0	0
75	Mammoth Brewing Company	mammoth-brewing-company	Craft brewery and pub with rotating beers and pub fare.	business_b2c	1	86	\N	\N	2	2	\N	\N	\N	\N	\N	\N	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-07-30 06:53:22	2025-07-30 06:18:42	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	82	86	\N	2025-07-30 05:34:34	\N	\N	\N	\N	\N	\N	free	\N	\N	\N	\N	\N	\N	0	0
76	Toomey's	toomeys	Upscale bar and restaurant with craft cocktails.	business_b2c	1	86	\N	\N	2	2	\N	\N	\N	\N	\N	\N	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-07-30 06:53:22	2025-07-30 06:18:42	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	82	86	\N	2025-07-30 05:34:34	\N	\N	\N	\N	\N	\N	free	\N	\N	\N	\N	\N	\N	0	0
78	Gabriella's Italian Ristorante	gabriellas-italian-ristorante	Family-owned Italian restaurant serving classic dishes.	business_b2c	1	86	\N	\N	2	2	\N	\N	\N	\N	\N	\N	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-07-30 06:53:22	2025-07-30 06:18:42	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	82	86	\N	2025-07-30 05:34:34	\N	\N	\N	\N	\N	\N	free	\N	\N	\N	\N	\N	\N	0	0
82	Volcom Store	volcom-store	Skate and snowboard apparel and accessories.	business_b2c	7	86	\N	\N	2	2	\N	\N	\N	\N	\N	\N	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-07-30 06:53:22	2025-07-30 06:18:42	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	82	86	\N	2025-07-30 05:34:34	\N	\N	\N	\N	\N	\N	free	\N	\N	\N	\N	\N	\N	0	0
83	Sierra Runner	sierra-runner	Footwear and sporting goods store.	business_b2c	28	86	\N	\N	2	2	\N	\N	\N	\N	\N	\N	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-07-30 06:53:22	2025-07-30 06:18:42	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	82	86	\N	2025-07-30 05:34:34	\N	\N	\N	\N	\N	\N	free	\N	\N	\N	\N	\N	\N	0	0
89	Mammoth Mountain Scenic Gondola	mammoth-mountain-scenic-gondola	Scenic gondola ride to Eagle Lodge with panoramic views.	business_b2c	21	86	\N	\N	2	2	\N	\N	\N	\N	\N	\N	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-07-30 06:53:22	2025-07-30 06:18:42	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	82	86	\N	2025-07-30 05:34:34	\N	\N	\N	\N	\N	\N	free	\N	\N	\N	\N	\N	\N	0	0
93	Sierra Star Golf Course	sierra-star-golf-course	Resort golf course with mountain views.	business_b2c	19	86	\N	\N	2	2	\N	\N	\N	\N	\N	\N	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-07-30 06:53:22	2025-07-30 06:18:42	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	82	86	\N	2025-07-30 06:35:42	\N	\N	\N	\N	\N	\N	free	\N	\N	\N	\N	\N	\N	0	0
94	Mammoth Lakes Film Festival	mammoth-lakes-film-festival	Annual film festival showcasing independent films.	business_b2c	17	86	\N	\N	2	2	\N	\N	\N	\N	\N	\N	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-07-30 06:53:22	2025-07-30 06:18:42	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	82	86	\N	2025-07-30 06:35:42	\N	\N	\N	\N	\N	\N	free	\N	\N	\N	\N	\N	\N	0	0
92	Mammoth Lakes Ice Rink	mammoth-lakes-ice-rink	Outdoor ice skating rink open seasonally.	business_b2c	17	86	\N	\N	2	2	\N	\N	\N	\N	\N	\N	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-07-30 06:53:22	2025-07-30 06:18:42	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	82	86	\N	2025-07-30 06:35:42	\N	\N	\N	\N	\N	\N	free	\N	\N	\N	\N	\N	\N	0	0
71	Good Life Café	good-life-cafe	Cozy café offering breakfast, brunch, and specialty coffee.	business_b2c	5	86	\N	\N	2	2	\N	\N	\N	\N	\N	\N	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-07-30 06:53:22	2025-07-30 06:18:42	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	82	86	\N	2025-07-30 06:35:42	\N	\N	\N	\N	\N	\N	free	\N	\N	\N	\N	\N	\N	0	0
108	Paradise Bowls	paradise-bowls	<p>Trendy cafe dishing up acai and pitaya bowls, smoothies, and coffee with vegan options.</p>	business_b2c	30	6	\N	\N	2	2	\N	\N	\N	\N	\N	\N	published	f	f	f	\N	\N	\N	0	0	2025-07-20 23:26:03	2025-07-30 06:53:22	2025-07-30 06:18:42	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	82	6	\N	2025-07-30 05:34:18	\N	\N	\N	\N	\N	\N	free	\N	\N	\N	\N	\N	\N	0	0
104	LayerCake Marketing	layercake-marketing	<p>We make <strong>web, mobile, social and marketing solutions that accelerate and grow your business.</strong> From conception to perfection, we craft each one of our digital products by hand.</p>	online	24	6	\N	\N	2	2	\N	\N	\N	\N	\N	\N	published	f	f	f	\N	\N	\N	0	0	2025-07-20 06:51:36	2025-07-30 06:53:22	2025-07-30 06:18:42	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	82	6	\N	2025-07-30 05:34:34	\N	\N	\N	\N	\N	\N	free	\N	\N	\N	\N	\N	\N	0	0
105	Maricich Health	maricich-health	<p>A healthcare branding, advertising and marketing communications agency with a unique perspective. By leveraging our Brandtivity process, we work with clients as navigators of an evolving healthcare landscape focused on rapid innovation and engagement with diverse audiences. We believe that doing effective marketing is not enough. It has to serve a higher purpose. So when we help our clients grow by doing more good for the populations they serve, everyone wins.</p>	service	16	6	\N	\N	2	2	\N	\N	\N	\N	\N	\N	published	f	f	f	\N	\N	\N	0	0	2025-07-20 16:07:09	2025-07-30 06:53:22	2025-07-30 06:18:42	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	82	6	\N	2025-07-30 05:34:34	\N	\N	\N	\N	\N	\N	free	\N	\N	\N	\N	\N	\N	0	0
96	Epic Discovery	epic-discovery	Adventure park with zip lines, climbing garden, and mountain coaster.	business_b2c	17	86	\N	\N	2	2	\N	\N	\N	\N	\N	\N	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-07-30 06:53:22	2025-07-30 06:18:42	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	82	86	\N	2025-07-30 06:35:42	\N	\N	\N	\N	\N	\N	free	\N	\N	\N	\N	\N	\N	0	0
91	Canyon Cinema	canyon-cinema	Local movie theater showing new releases and classics.	business_b2c	17	86	\N	\N	2	2	\N	\N	\N	\N	\N	\N	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-07-30 06:53:22	2025-07-30 06:18:42	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	82	86	\N	2025-07-30 06:35:42	\N	\N	\N	\N	\N	\N	free	\N	\N	\N	\N	\N	\N	0	0
109	Beach Cities Medical Weight Loss	beach-cities-medical-weight-loss	<p>Thousands have transformed their lives with us at Beach Cities Medical Weight Loss, affectionately calling us 'My Weight Loss Place.' <strong>We deeply value each of their stories.</strong></p>	business_b2c	14	\N	\N	\N	2	2	15623754372	info@myweightlossplace.com	https://myweightlossplace.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-07-30 06:30:49	2025-07-30 06:31:49	2025-07-30 06:31:49	https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A/e73d3dcf-ec7f-4476-09e2-b361188ca000/mListCover	https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A/8dac5252-7580-40cb-05c6-7f2f0a3cec00/portrait	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	82	93	\N	\N	[]	\N	\N	\N	\N	\N	free	\N	\N	\N	\N	\N	\N	0	0
103	Handels Ice Cream	handels-ice-cream	<p>Handel's Homemade Ice Cream has been made fresh at each store since 1945. We use an abundance of only the best tasting ingredients and are proud to be recognized as the #1 ice cream on the planet by National Geographic Magazine!</p>	business_b2c	30	6	\N	\N	2	2	\N	\N	\N	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-07-20 00:41:18	2025-08-01 07:29:32	2025-07-30 06:18:42	\N	https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A/0540c4be-7192-4668-3cb3-86be86e24400/public	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	90	91	\N	2025-07-30 05:34:34	[]	\N	\N	\N	\N	\N	free	\N	\N	\N	\N	\N	\N	0	0
\.


--
-- Name: directory_entries_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.directory_entries_id_seq', 109, true);


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
-- PostgreSQL database dump complete
--

