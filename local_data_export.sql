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
-- Data for Name: claims; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.claims (id, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.users (id, name, email, email_verified_at, password, remember_token, created_at, updated_at, username, role, bio, avatar, cover_image, social_links, preferences, permissions, is_public, last_active_at, custom_url, display_title, profile_description, location, website, birth_date, profile_settings, show_activity, show_followers, show_following, profile_views, page_title, custom_css, theme_settings, profile_color, show_join_date, show_location, show_website, avatar_cloudflare_id, cover_cloudflare_id, page_logo_cloudflare_id, phone, page_logo_option, avatar_updated_at, cover_updated_at, default_region_id) FROM stdin;
1	Admin User	admin@example.com	\N	$2y$12$2brCLHF76fUpD6cacN4q8uLdTuIGkjAn6qo2rlCW48kR5Hzf3rm66	\N	2025-06-01 00:12:11	2025-06-27 05:55:02	adminuser	admin	\N	\N	\N	\N	\N	\N	t	\N	\N	\N	\N	\N	\N	\N	\N	t	t	t	0	\N	\N	\N	\N	t	t	t	\N	\N	\N	\N	initials	\N	\N	\N
3	Test Manager	manager@example.com	\N	$2y$12$I557C68Vv6ksBqCOaymbnubDiASW0rpMRGYJ4cXhRnV5WmNBpDi36	\N	2025-06-01 06:19:41	2025-06-27 05:55:02	testmanager	manager	\N	\N	\N	\N	\N	\N	t	\N	\N	\N	\N	\N	\N	\N	\N	t	t	t	0	\N	\N	\N	\N	t	t	t	\N	\N	\N	\N	initials	\N	\N	\N
4	Test Editor	editor@example.com	\N	$2y$12$RQ.4BuNIug3.QjdQ78d9heR3FrgjTgxAUGm0grY/BMtIxaQzVpzLK	\N	2025-06-01 06:19:41	2025-06-29 04:28:42	testeditor	editor	\N	\N	\N	\N	\N	\N	t	\N	\N	\N	\N	\N	\N	\N	\N	t	t	t	0	\N	\N	\N	\N	t	t	t	\N	\N	\N	\N	initials	\N	\N	\N
5	Luggie Riggatoni	info@layercakemarketing.com	\N	$2y$12$5lhe8o6CQ5aeVVikioBqg.G8Zvoc3EhgVjfGGneGf.YP.u.w8f.dK	\N	2025-06-01 06:19:41	2025-07-04 19:40:56	luggie	user	\N	\N	\N	{"twitter":null,"instagram":null,"github":null}	\N	\N	t	\N	Luiggi	\N	\N	\N	\N	\N	\N	t	t	t	2	Chi dorme non piglia pesci	\N	\N	#3B82F6	t	t	t	a62e4df8-0b2d-45f7-c390-a0710117e500	0ad5ac52-2e93-495d-3e40-95cb2bfcf400	\N	\N	profile	\N	\N	\N
2	Eric Larson	eric@layercakemarketing.com	\N	$2y$12$PoUVCjvWdxCJD7svQKugbeqND5C7SZEWWnYbmFgTntEM398Qi79R.	\N	2025-06-01 05:41:54	2025-07-15 05:13:36	ericlarson	admin	Creative developer, entrepreneur, husband, dad and independent thinker.	\N	\N	{"twitter":null,"instagram":null,"github":null}	\N	\N	t	\N	layercake	The OG. Listerino	\N	Irvine, California	https://layercake.marketing	\N	\N	t	t	t	11	Mr. Listerino	\N	\N	#3B82F6	t	t	t	7407e954-66b7-42ce-3064-f8d3c2b1eb00	dc07d3b8-76b1-4823-5fcb-d779e9b8ae00	9f4de8bf-e04b-4e1d-5621-b5f5cb839100	(714) 261-0903	custom	2025-07-15 05:11:22	2025-07-15 05:13:36	\N
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
37	e82c143c-7cf5-4505-5425-75431d434900	avatar.jpg	2	avatar	App\\Models\\User	2	{"type":"avatar","tracked_retroactively":true,"original_metadata":[]}	\N	\N	\N	\N	2025-07-06 05:30:56	2025-07-06 06:03:21	2025-07-06 06:03:21
38	299fb4f9-855a-483d-e8a9-57225e527b00	cover.jpg	2	cover	App\\Models\\User	2	{"type":"cover","tracked_retroactively":true,"original_metadata":[]}	\N	\N	\N	\N	2025-07-06 05:30:56	2025-07-06 06:03:21	2025-07-06 06:03:21
39	f6ce134b-9d17-473d-3d00-6c7ec1ffa500	logo.jpg	2	logo	App\\Models\\User	2	{"type":"page_logo","tracked_retroactively":true,"original_metadata":[]}	\N	\N	\N	\N	2025-07-06 05:30:56	2025-07-06 06:03:21	2025-07-06 06:03:21
40	bce90390-18bd-4d04-72db-dc44c3d6a000	kalen-emsley-Bkci_8qcdvQ-unsplash.jpg	2	cover	App\\Models\\User	2	{"type":"cover","uploaded_via":"profile_editor"}	3997081	\N	\N	\N	2025-07-06 06:06:26	2025-07-06 06:06:26	2025-07-06 06:06:26
41	9f4de8bf-e04b-4e1d-5621-b5f5cb839100	cake_icon_light.png	2	logo	App\\Models\\User	2	{"type":"page_logo","uploaded_via":"profile_editor"}	72311	\N	\N	\N	2025-07-06 06:06:47	2025-07-06 06:06:47	2025-07-06 06:06:47
42	94b1aed2-aea8-4748-5711-b923d9b3bd00	Eric_sheishi_beachsm.jpg	2	avatar	App\\Models\\User	2	{"type":"avatar","uploaded_via":"profile_editor"}	4353338	\N	\N	\N	2025-07-06 06:07:08	2025-07-06 06:07:08	2025-07-06 06:07:08
43	bcdbd7cd-648c-4aa3-99c7-18246d9eaa00	Eric_sheishi_beachsm.jpg	2	avatar	App\\Models\\User	2	{"type":"avatar","uploaded_via":"profile_editor"}	4353338	\N	\N	\N	2025-07-06 06:07:38	2025-07-06 06:07:38	2025-07-06 06:07:38
44	1032362d-b0b9-448e-87de-ff11c3b38000	Eric_sheishi_beachsm_crop.png	2	avatar	App\\Models\\User	2	{"type":"avatar","uploaded_via":"profile_editor"}	1234133	\N	\N	\N	2025-07-06 17:26:05	2025-07-06 17:26:05	2025-07-06 17:26:05
45	fcc30001-0e19-4b36-7b4f-0b05a1e5dd00	nevada_night.jpg	2	cover	App\\Models\\DirectoryEntry	\N	{"id":"fcc30001-0e19-4b36-7b4f-0b05a1e5dd00","filename":"nevada_night.jpg","uploaded":"2025-07-09T00:50:48.902Z","requireSignedURLs":false,"variants":["https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/fcc30001-0e19-4b36-7b4f-0b05a1e5dd00\\/public"]}	\N	\N	\N	\N	2025-07-09 00:50:49	2025-07-09 00:50:49	2025-07-09 00:50:49
46	b84e6434-277e-4e2a-1f1f-2708bfe7c400	nevada_night.jpg	2	\N	\N	\N	{"id":"b84e6434-277e-4e2a-1f1f-2708bfe7c400","filename":"nevada_night.jpg","uploaded":"2025-07-09T04:48:54.591Z","requireSignedURLs":false,"variants":["https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/b84e6434-277e-4e2a-1f1f-2708bfe7c400\\/public"]}	\N	\N	\N	\N	2025-07-09 04:48:56	2025-07-09 04:48:56	2025-07-09 04:48:56
47	27d80f27-91af-4a4a-d849-a54efc5abb00	mammoth_lakes.jpg	2	\N	\N	\N	{"id":"27d80f27-91af-4a4a-d849-a54efc5abb00","filename":"mammoth_lakes.jpg","uploaded":"2025-07-09T05:00:54.042Z","requireSignedURLs":false,"variants":["https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/27d80f27-91af-4a4a-d849-a54efc5abb00\\/public"]}	\N	\N	\N	\N	2025-07-09 05:00:54	2025-07-09 05:00:54	2025-07-09 05:00:54
48	01feae79-cc58-4f36-0088-ad4d1dbeec00	mammoth_lakes.jpg	2	\N	\N	\N	{"id":"01feae79-cc58-4f36-0088-ad4d1dbeec00","filename":"mammoth_lakes.jpg","uploaded":"2025-07-09T05:33:45.833Z","requireSignedURLs":false,"variants":["https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/01feae79-cc58-4f36-0088-ad4d1dbeec00\\/public"]}	\N	\N	\N	\N	2025-07-09 05:33:46	2025-07-09 05:33:46	2025-07-09 05:33:46
49	7de389f5-bf24-4a43-1801-00844ff05e00	mammoth_lakes.jpg	2	\N	\N	\N	{"id":"7de389f5-bf24-4a43-1801-00844ff05e00","filename":"mammoth_lakes.jpg","uploaded":"2025-07-09T05:39:48.003Z","requireSignedURLs":false,"variants":["https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/7de389f5-bf24-4a43-1801-00844ff05e00\\/public"]}	\N	\N	\N	\N	2025-07-09 05:39:48	2025-07-09 05:39:48	2025-07-09 05:39:48
50	ff0b527a-5067-47f5-b9a8-1cb290e24c00	mammoth_lakes.jpg	2	\N	\N	\N	{"id":"ff0b527a-5067-47f5-b9a8-1cb290e24c00","filename":"mammoth_lakes.jpg","uploaded":"2025-07-09T05:40:05.943Z","requireSignedURLs":false,"variants":["https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/ff0b527a-5067-47f5-b9a8-1cb290e24c00\\/public"]}	\N	\N	\N	\N	2025-07-09 05:40:06	2025-07-09 05:40:06	2025-07-09 05:40:06
51	f9ade7ed-a7a3-41a3-97dd-54b2e422e200	mammoth_lakes.jpg	2	\N	\N	\N	{"id":"f9ade7ed-a7a3-41a3-97dd-54b2e422e200","filename":"mammoth_lakes.jpg","uploaded":"2025-07-09T05:43:37.354Z","requireSignedURLs":false,"variants":["https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/f9ade7ed-a7a3-41a3-97dd-54b2e422e200\\/public"]}	\N	\N	\N	\N	2025-07-09 05:43:38	2025-07-09 05:43:38	2025-07-09 05:43:38
52	7f0857ee-0e57-4877-0935-c53e5fbd8500	nevada_night.jpg	2	region_cover	region	5	{"id":"7f0857ee-0e57-4877-0935-c53e5fbd8500","filename":"nevada_night.jpg","uploaded":"2025-07-09T06:18:08.788Z","requireSignedURLs":false,"variants":["https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/7f0857ee-0e57-4877-0935-c53e5fbd8500\\/public"]}	\N	\N	\N	\N	2025-07-09 06:18:09	2025-07-09 06:18:09	2025-07-09 06:18:09
53	f5a38466-e59c-4ae7-0e3c-1a1d171ae200	California.jpg	2	region_cover	region	1	{"id":"f5a38466-e59c-4ae7-0e3c-1a1d171ae200","filename":"California.jpg","uploaded":"2025-07-09T06:32:07.979Z","requireSignedURLs":false,"variants":["https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/f5a38466-e59c-4ae7-0e3c-1a1d171ae200\\/public"]}	\N	\N	\N	\N	2025-07-09 06:32:09	2025-07-09 06:32:09	2025-07-09 06:32:09
54	ff05195b-69c2-450a-96f9-2b4f937b8e00	4bb92024-777c-4f17-b345-0b4c7f54dbee.png	2	logo	App\\Models\\DirectoryEntry	71	{"id":"ff05195b-69c2-450a-96f9-2b4f937b8e00","filename":"4bb92024-777c-4f17-b345-0b4c7f54dbee.png","uploaded":"2025-07-09T06:39:41.901Z","requireSignedURLs":false,"variants":["https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/ff05195b-69c2-450a-96f9-2b4f937b8e00\\/public"]}	\N	\N	\N	\N	2025-07-09 06:39:42	2025-07-09 06:39:42	2025-07-09 06:39:42
55	b00d0d8d-0555-4c0c-9fb2-850d88ccb500	1-5639cd24.jpeg	2	cover	App\\Models\\DirectoryEntry	71	{"id":"b00d0d8d-0555-4c0c-9fb2-850d88ccb500","filename":"1-5639cd24.jpeg","uploaded":"2025-07-09T06:41:47.691Z","requireSignedURLs":false,"variants":["https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/b00d0d8d-0555-4c0c-9fb2-850d88ccb500\\/public"]}	\N	\N	\N	\N	2025-07-09 06:41:48	2025-07-09 06:41:48	2025-07-09 06:41:48
56	390b433e-f1df-4970-a687-263f5eb4a300	4bb92024-777c-4f17-b345-0b4c7f54dbee.png	2	logo	App\\Models\\DirectoryEntry	71	{"id":"390b433e-f1df-4970-a687-263f5eb4a300","filename":"4bb92024-777c-4f17-b345-0b4c7f54dbee.png","uploaded":"2025-07-09T06:41:55.381Z","requireSignedURLs":false,"variants":["https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/390b433e-f1df-4970-a687-263f5eb4a300\\/public"]}	\N	\N	\N	\N	2025-07-09 06:41:56	2025-07-09 06:41:56	2025-07-09 06:41:56
57	cc0cf4d2-e933-45db-8325-a71e7142b900	getty-images-dP7MgFCuNHY-unsplash.jpg	5	cover	App\\Models\\UserList	9	{"id":"cc0cf4d2-e933-45db-8325-a71e7142b900","filename":"getty-images-dP7MgFCuNHY-unsplash.jpg","uploaded":"2025-07-10T06:42:00.820Z","requireSignedURLs":false,"variants":["https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/cc0cf4d2-e933-45db-8325-a71e7142b900\\/public"]}	\N	\N	\N	\N	2025-07-10 06:42:01	2025-07-10 06:42:01	2025-07-10 06:42:01
58	ef34f3b4-292e-4799-fcfe-459af026ee00	houston-max-K82z6_8y_TU-unsplash.jpg	2	cover	App\\Models\\UserList	7	{"id":"ef34f3b4-292e-4799-fcfe-459af026ee00","filename":"houston-max-K82z6_8y_TU-unsplash.jpg","uploaded":"2025-07-14T02:18:47.369Z","requireSignedURLs":false,"variants":["https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/ef34f3b4-292e-4799-fcfe-459af026ee00\\/public","https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/ef34f3b4-292e-4799-fcfe-459af026ee00\\/portrait","https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/ef34f3b4-292e-4799-fcfe-459af026ee00\\/thumbnail","https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/ef34f3b4-292e-4799-fcfe-459af026ee00\\/widecover","https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/ef34f3b4-292e-4799-fcfe-459af026ee00\\/square","https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/ef34f3b4-292e-4799-fcfe-459af026ee00\\/cover","https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/ef34f3b4-292e-4799-fcfe-459af026ee00\\/mListCover"]}	\N	\N	\N	\N	2025-07-14 02:18:49	2025-07-14 02:18:49	2025-07-14 02:18:49
59	7407e954-66b7-42ce-3064-f8d3c2b1eb00	Eric_sheishi_beachsm_crop.png	2	avatar	App\\Models\\User	2	{"entity_id":2,"entity_type":"User","user_name":"Eric Larson","context":"avatar","id":"7407e954-66b7-42ce-3064-f8d3c2b1eb00","filename":"Eric_sheishi_beachsm_crop.png","uploaded":"2025-07-15T05:11:21.509Z","requireSignedURLs":false,"variants":["https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/7407e954-66b7-42ce-3064-f8d3c2b1eb00\\/widecover","https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/7407e954-66b7-42ce-3064-f8d3c2b1eb00\\/thumbnail","https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/7407e954-66b7-42ce-3064-f8d3c2b1eb00\\/public","https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/7407e954-66b7-42ce-3064-f8d3c2b1eb00\\/mListCover","https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/7407e954-66b7-42ce-3064-f8d3c2b1eb00\\/square","https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/7407e954-66b7-42ce-3064-f8d3c2b1eb00\\/cover","https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/7407e954-66b7-42ce-3064-f8d3c2b1eb00\\/portrait"]}	\N	\N	\N	\N	2025-07-15 05:11:22	2025-07-15 05:11:22	2025-07-15 05:11:22
60	dc07d3b8-76b1-4823-5fcb-d779e9b8ae00	mountain_cover.jpg	2	cover	App\\Models\\User	2	{"entity_id":2,"entity_type":"User","user_name":"Eric Larson","context":"cover","id":"dc07d3b8-76b1-4823-5fcb-d779e9b8ae00","filename":"mountain_cover.jpg","uploaded":"2025-07-15T05:13:35.775Z","requireSignedURLs":false,"variants":["https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/dc07d3b8-76b1-4823-5fcb-d779e9b8ae00\\/cover","https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/dc07d3b8-76b1-4823-5fcb-d779e9b8ae00\\/widecover","https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/dc07d3b8-76b1-4823-5fcb-d779e9b8ae00\\/portrait","https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/dc07d3b8-76b1-4823-5fcb-d779e9b8ae00\\/thumbnail","https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/dc07d3b8-76b1-4823-5fcb-d779e9b8ae00\\/public","https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/dc07d3b8-76b1-4823-5fcb-d779e9b8ae00\\/mListCover","https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/dc07d3b8-76b1-4823-5fcb-d779e9b8ae00\\/square"]}	\N	\N	\N	\N	2025-07-15 05:13:36	2025-07-15 05:13:36	2025-07-15 05:13:36
\.


--
-- Data for Name: comments; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.comments (id, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: regions; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.regions (id, name, type, parent_id, created_at, updated_at, level, slug, metadata, cached_place_count, boundaries, boundaries_simplified, centroid, cover_image, intro_text, data_points, is_featured, meta_title, meta_description, custom_fields, display_priority, cloudflare_image_id, facts, state_symbols, geojson, polygon_coordinates, full_name, abbreviation, alternate_names, boundary, center_point, area_sq_km, is_user_defined, created_by_user_id, cache_updated_at) FROM stdin;
32	San Francisco	city	31	2025-07-12 06:52:22	2025-07-12 16:03:03	2	san-francisco	\N	1	\N	\N	\N	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	San Francisco	\N	\N	\N	\N	\N	f	\N	2025-07-12 16:03:03
34	Miami	city	33	2025-07-12 06:52:22	2025-07-12 16:03:03	2	miami	\N	1	\N	\N	\N	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	Miami	\N	\N	\N	\N	\N	f	\N	2025-07-12 16:03:03
35	Mammoth Lakes	city	31	2025-07-12 06:52:22	2025-07-12 16:03:04	2	mammoth-lakes	\N	31	\N	\N	\N	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	Mammoth Lakes	\N	\N	\N	\N	\N	f	\N	2025-07-12 16:03:04
2	New York	state	\N	2025-06-01 20:18:34	2025-06-01 20:18:34	1	new-york	\N	0	\N	\N	\N	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	New York	\N	\N	\N	\N	\N	f	\N	\N
3	Texas	state	\N	2025-06-01 20:18:34	2025-06-01 20:18:34	1	texas	\N	0	\N	\N	\N	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	Texas	\N	\N	\N	\N	\N	f	\N	\N
4	Florida	state	\N	2025-06-01 20:18:34	2025-06-01 20:18:34	1	florida	\N	0	\N	\N	\N	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	Florida	\N	\N	\N	\N	\N	f	\N	\N
1	California	state	\N	2025-06-01 20:18:34	2025-07-10 05:43:43	1	california	{"capital": "Sacramento", "population": 39237836, "abbreviation": "CA", "area_sq_miles": 163696}	0	\N	\N	\N	https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A/f5a38466-e59c-4ae7-0e3c-1a1d171ae200/public	California is the most populous state in the United States and the third-largest by area.	[]	t	\N	\N	\N	1	\N	\N	\N	\N	\N	California	\N	\N	\N	\N	\N	f	\N	\N
6	Irvine	city	1	2025-07-08 23:00:35	2025-07-10 05:43:43	2	irvine	{"population": 307670}	0	\N	\N	\N	\N	A master-planned city in Orange County known for its safety and education.	[]	t	\N	\N	\N	3	\N	\N	\N	\N	\N	Irvine	\N	\N	\N	\N	\N	f	\N	\N
9	Mammoth Lakes	city	1	2025-07-09 05:04:54	2025-07-09 05:44:02	2	mammoth-lakes	[]	0	\N	\N	\N	https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A/f9ade7ed-a7a3-41a3-97dd-54b2e422e200/public	Mammoth Lakes, California is a scenic mountain town nestled in the Eastern Sierra, known for its year-round outdoor adventure and stunning alpine beauty. In winter, it offers world-class skiing at Mammoth Mountain; in summer, visitors enjoy hiking, fishing, and natural hot springs. With a small-town vibe, vibrant village center, and breathtaking landscapes, it's a perfect getaway for nature lovers and adventure seekers alike.	[]	f	\N	\N	\N	0	\N	\N	\N	\N	\N	Mammoth Lakes	\N	\N	\N	\N	\N	f	\N	\N
8	Woodbridge	neighborhood	6	2025-07-08 23:09:09	2025-07-10 05:43:43	3	woodbridge	[]	0	\N	\N	\N	\N	\N	[]	f	\N	\N	\N	19	\N	\N	\N	\N	\N	Woodbridge	\N	\N	\N	\N	\N	f	\N	\N
14	Santa Monica	neighborhood	11	2025-07-10 05:43:43	2025-07-10 05:43:43	3	santa-monica	[]	0	\N	\N	\N	\N	\N	\N	f	\N	\N	\N	19	\N	\N	\N	\N	\N	Santa Monica	\N	\N	\N	\N	\N	f	\N	\N
15	Venice Beach	neighborhood	11	2025-07-10 05:43:43	2025-07-10 05:43:43	3	venice-beach	[]	0	\N	\N	\N	\N	\N	\N	f	\N	\N	\N	13	\N	\N	\N	\N	\N	Venice Beach	\N	\N	\N	\N	\N	f	\N	\N
16	Downtown LA	neighborhood	11	2025-07-10 05:43:43	2025-07-10 05:43:43	3	downtown-la	[]	0	\N	\N	\N	\N	\N	\N	f	\N	\N	\N	15	\N	\N	\N	\N	\N	Downtown LA	\N	\N	\N	\N	\N	f	\N	\N
17	San Francisco	city	1	2025-07-10 05:43:43	2025-07-10 05:43:43	2	san-francisco	{"population": 873965}	0	\N	\N	\N	\N	Known for the Golden Gate Bridge, cable cars, and tech innovation.	\N	t	\N	\N	\N	5	\N	\N	\N	\N	\N	San Francisco	\N	\N	\N	\N	\N	f	\N	\N
18	Mission District	neighborhood	17	2025-07-10 05:43:43	2025-07-10 05:43:43	3	mission-district	[]	0	\N	\N	\N	\N	\N	\N	f	\N	\N	\N	7	\N	\N	\N	\N	\N	Mission District	\N	\N	\N	\N	\N	f	\N	\N
19	Castro	neighborhood	17	2025-07-10 05:43:43	2025-07-10 05:43:43	3	castro	[]	0	\N	\N	\N	\N	\N	\N	f	\N	\N	\N	13	\N	\N	\N	\N	\N	Castro	\N	\N	\N	\N	\N	f	\N	\N
20	Chinatown	neighborhood	17	2025-07-10 05:43:43	2025-07-10 05:43:43	3	chinatown	[]	0	\N	\N	\N	\N	\N	\N	f	\N	\N	\N	14	\N	\N	\N	\N	\N	Chinatown	\N	\N	\N	\N	\N	f	\N	\N
21	Fisherman's Wharf	neighborhood	17	2025-07-10 05:43:43	2025-07-10 05:43:43	3	fishermans-wharf	[]	0	\N	\N	\N	\N	\N	\N	f	\N	\N	\N	19	\N	\N	\N	\N	\N	Fisherman's Wharf	\N	\N	\N	\N	\N	f	\N	\N
22	Haight-Ashbury	neighborhood	17	2025-07-10 05:43:43	2025-07-10 05:43:43	3	haight-ashbury	[]	0	\N	\N	\N	\N	\N	\N	f	\N	\N	\N	19	\N	\N	\N	\N	\N	Haight-Ashbury	\N	\N	\N	\N	\N	f	\N	\N
23	San Diego	city	1	2025-07-10 05:43:43	2025-07-10 05:43:43	2	san-diego	{"population": 1386932}	0	\N	\N	\N	\N	Known for its beaches, parks, and warm climate year-round.	\N	t	\N	\N	\N	1	\N	\N	\N	\N	\N	San Diego	\N	\N	\N	\N	\N	f	\N	\N
24	Sacramento	city	1	2025-07-10 05:43:43	2025-07-10 05:43:43	2	sacramento	{"population": 513624}	0	\N	\N	\N	\N	The capital city of California.	\N	t	\N	\N	\N	1	\N	\N	\N	\N	\N	Sacramento	\N	\N	\N	\N	\N	f	\N	\N
25	San Jose	city	1	2025-07-10 05:43:43	2025-07-10 05:43:43	2	san-jose	{"population": 1021795}	0	\N	\N	\N	\N	The heart of Silicon Valley and the largest city in Northern California.	\N	t	\N	\N	\N	3	\N	\N	\N	\N	\N	San Jose	\N	\N	\N	\N	\N	f	\N	\N
26	Oakland	city	1	2025-07-10 05:43:43	2025-07-10 05:43:43	2	oakland	{"population": 433031}	0	\N	\N	\N	\N	A major West Coast port city in the San Francisco Bay Area.	\N	t	\N	\N	\N	10	\N	\N	\N	\N	\N	Oakland	\N	\N	\N	\N	\N	f	\N	\N
27	Northwood	neighborhood	6	2025-07-10 05:43:43	2025-07-10 05:43:43	3	northwood	[]	0	\N	\N	\N	\N	\N	\N	f	\N	\N	\N	8	\N	\N	\N	\N	\N	Northwood	\N	\N	\N	\N	\N	f	\N	\N
28	University Park	neighborhood	6	2025-07-10 05:43:43	2025-07-10 05:43:43	3	university-park	[]	0	\N	\N	\N	\N	\N	\N	f	\N	\N	\N	7	\N	\N	\N	\N	\N	University Park	\N	\N	\N	\N	\N	f	\N	\N
29	Turtle Rock	neighborhood	6	2025-07-10 05:43:43	2025-07-10 05:43:43	3	turtle-rock	[]	0	\N	\N	\N	\N	\N	\N	f	\N	\N	\N	12	\N	\N	\N	\N	\N	Turtle Rock	\N	\N	\N	\N	\N	f	\N	\N
30	Westpark	neighborhood	6	2025-07-10 05:43:43	2025-07-10 05:43:43	3	westpark	[]	0	\N	\N	\N	\N	\N	\N	f	\N	\N	\N	8	\N	\N	\N	\N	\N	Westpark	\N	\N	\N	\N	\N	f	\N	\N
33	FL	state	\N	2025-07-12 06:52:22	2025-07-12 16:03:03	1	fl	\N	1	\N	\N	\N	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	Florida	FL	\N	\N	\N	\N	f	\N	2025-07-12 16:03:03
5	Nevada	state	\N	2025-07-08 22:41:15	2025-07-09 06:18:14	1	nevada	[]	0	0103000020E6100000010000000B000000CEE9213612045EC0F3A73CA3B5F94440048D948BE3FD5DC0FB5E94B7DB7F4340661B8ACB09A75CC07F5B9600D37441408E74BAB798AB5CC05650076811E04140976B46106BB05CC0E7818DC0DFFC4140DA879E8F10A25CC077084E43350A4240A10E3DBF4E925CC0D6D750F028FE414050858E0950885CC0FF57BDC625024240C181F60ECA815CC0B95F1A9379144240A88B605848815CC0B64018FE67004540CEE9213612045EC0F3A73CA3B5F94440	0103000020E6100000010000000B000000CEE9213612045EC0F3A73CA3B5F94440048D948BE3FD5DC0FB5E94B7DB7F4340661B8ACB09A75CC07F5B9600D37441408E74BAB798AB5CC05650076811E04140976B46106BB05CC0E7818DC0DFFC4140DA879E8F10A25CC077084E43350A4240A10E3DBF4E925CC0D6D750F028FE414050858E0950885CC0FF57BDC625024240C181F60ECA815CC0B95F1A9379144240A88B605848815CC0B64018FE67004540CEE9213612045EC0F3A73CA3B5F94440	0101000020E6100000ADFF608E08295DC0D999108F4CA74340	https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A/7f0857ee-0e57-4877-0935-c53e5fbd8500/public	<p>Nevada, known as the Silver State, offers a dynamic mix of natural beauty, adventure, and entertainment. Home to the vibrant city of Las Vegas, it’s a global destination for world-class dining, shows, nightlife, and casinos. Beyond the lights, Nevada’s diverse landscapes include Red Rock Canyon, Lake Tahoe, the Sierra Nevada, and the expansive Great Basin—perfect for hiking, skiing, boating, and off-road exploration.</p><p>Reno, “The Biggest Little City in the World,” provides a growing arts scene and proximity to the outdoor haven of Lake Tahoe. Carson City, the state capital, offers a quieter, historic charm. Nevada’s vast open spaces are ideal for stargazing, rockhounding, and discovering old mining towns like Virginia City. The state also boasts a no-state-income-tax policy, drawing residents and businesses alike.</p><p>Whether you’re seeking high-energy city life or the peace of desert solitude, Nevada delivers with a unique blend of Western heritage, modern luxury, and wide-open freedom.</p>	{"population": "3,104,614", "hotel-casino-revenue": "31.5 Billion"}	f	\N	\N	\N	0	\N	\N	\N	\N	\N	Nevada	\N	\N	\N	\N	\N	f	\N	\N
11	Los Angeles	city	1	2025-07-10 05:43:43	2025-07-10 05:43:43	2	los-angeles	{"population": 3898747}	0	\N	\N	\N	\N	The second-largest city in the United States and the entertainment capital of the world.	\N	t	\N	\N	\N	5	\N	\N	\N	\N	\N	Los Angeles	\N	\N	\N	\N	\N	f	\N	\N
12	Hollywood	neighborhood	11	2025-07-10 05:43:43	2025-07-10 05:43:43	3	hollywood	[]	0	\N	\N	\N	\N	\N	\N	f	\N	\N	\N	18	\N	\N	\N	\N	\N	Hollywood	\N	\N	\N	\N	\N	f	\N	\N
31	CA	state	\N	2025-07-12 06:52:22	2025-07-12 16:03:04	1	ca	\N	32	\N	\N	\N	\N	\N	\N	f	\N	\N	\N	0	\N	\N	\N	\N	\N	California	CA	\N	\N	\N	\N	f	\N	2025-07-12 16:03:04
13	Beverly Hills	neighborhood	11	2025-07-10 05:43:43	2025-07-15 05:19:07	3	beverly-hills	[]	0	\N	\N	\N	http://localhost:8001/images/default-region-cover.jpg	\N	\N	f	\N	\N	\N	20	\N	[]	{"bird": [], "fish": [], "flag": [], "seal": [], "song": [], "tree": [], "flower": [], "mammal": [], "resources": []}	\N	\N	Beverly Hills	\N	\N	\N	\N	\N	f	\N	\N
\.


--
-- Data for Name: directory_entries; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.directory_entries (id, title, slug, description, type, category_id, region_id, tags, owner_user_id, created_by_user_id, updated_by_user_id, phone, email, website_url, social_links, featured_image, gallery_images, status, is_featured, is_verified, is_claimed, meta_title, meta_description, structured_data, view_count, list_count, created_at, updated_at, published_at, logo_url, cover_image_url, facebook_url, instagram_handle, twitter_handle, youtube_channel, messenger_contact, price_range, takes_reservations, accepts_credit_cards, wifi_available, pet_friendly, parking_options, wheelchair_accessible, outdoor_seating, kid_friendly, video_urls, pdf_files, hours_of_operation, special_hours, temporarily_closed, open_24_7, cross_streets, neighborhood, state_region_id, city_region_id, neighborhood_region_id, regions_updated_at, coordinates, state_name, city_name, neighborhood_name, popularity_score) FROM stdin;
102	Test Restaurant	test-restaurant	A test restaurant for category filtering	business_b2c	1	\N	\N	\N	1	\N	\N	\N	\N	\N	\N	\N	published	f	f	f	\N	\N	\N	0	0	2025-07-14 20:21:36	2025-07-14 20:21:36	2025-07-14 20:21:36	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	31	32	\N	\N	\N	\N	\N	\N	0
2	TechHub Electronics	techhub-electronics	Your one-stop shop for all electronics needs. Best prices guaranteed!	business_b2c	9	\N	["walk-in","appointment"]	\N	1	\N	(555) 987-6543	support@techhub.com	https://techhub.com	{"facebook":"https:\\/\\/facebook.com\\/techhub-electronics","instagram":"https:\\/\\/instagram.com\\/techhub-electronics"}	\N	\N	published	t	f	f	\N	\N	\N	0	0	2025-06-01 20:18:34	2025-07-12 16:03:03	2025-06-01 20:18:34	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	31	32	\N	2025-07-12 16:03:03	0101000020E610000050FC1873D79A5EC0D0D556EC2FE34240	California	San Francisco	\N	0
3	Serenity Spa & Wellness	serenity-spa-wellness	Relax and rejuvenate with our premium spa services.	business_b2c	14	\N	["local","appointment"]	\N	1	\N	(555) 456-7890	relax@serenityspa.com	https://serenityspa.com	{"facebook":"https:\\/\\/facebook.com\\/serenity-spa-wellness","instagram":"https:\\/\\/instagram.com\\/serenity-spa-wellness"}	\N	\N	published	t	f	f	\N	\N	\N	0	0	2025-06-01 20:18:34	2025-07-12 16:03:03	2025-06-01 20:18:34	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	33	34	\N	2025-07-12 16:03:03	0101000020E6100000DCD78173460C54C0FB5C6DC5FEC23940	Florida	Miami	\N	0
73	Sazon Restaurant	sazon-restaurant	Mexican and Latin American cuisine with tequila bar.	business_b2c	1	\N	["mexican","latin","tequila_bar","seafood"]	\N	2	\N	\N	info@sazonrestaurant.com	https://sazonrestaurant.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-07-12 16:03:03	2025-06-29 03:06:50	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	31	35	\N	2025-07-12 16:03:03	0101000020E610000000000000000000000000000000000000	California	Mammoth Lakes	\N	0
6	Mobile Pet Grooming	mobile-pet-grooming	We come to you! Professional pet grooming at your doorstep.	service	16	1	["mobile","professional","licensed"]	\N	1	\N	(555) 321-9876	woof@mobilepetgrooming.com	https://mobilepetgrooming.com	{"facebook":"https:\\/\\/facebook.com\\/mobile-pet-grooming","instagram":"https:\\/\\/instagram.com\\/mobile-pet-grooming"}	\N	\N	published	f	t	f	\N	\N	\N	0	0	2025-06-01 20:18:34	2025-06-01 20:18:34	2025-06-01 20:18:34	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	1	\N	\N	2025-07-08 14:38:17	\N	California	\N	\N	0
4	CloudTech Solutions	cloudtech-solutions	Enterprise cloud computing solutions for modern businesses.	online	26	1	["digital","worldwide"]	\N	1	\N	(800) 555-2468	contact@cloudtechsolutions.com	https://cloudtechsolutions.com	{"facebook":"https:\\/\\/facebook.com\\/cloudtech-solutions","instagram":"https:\\/\\/instagram.com\\/cloudtech-solutions"}	\N	\N	published	t	t	f	\N	\N	\N	0	0	2025-06-01 20:18:34	2025-06-01 20:18:34	2025-06-01 20:18:34	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	1	\N	\N	2025-07-08 14:38:17	\N	California	\N	\N	0
79	Jax at Mammoth	jax-at-mammoth	Women's apparel, accessories and gifts.	business_b2c	7	\N	["retail","clothing","womens_fashion"]	\N	2	\N	\N	info@jaxatmammoth.com	https://jaxatmammoth.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-07-12 16:03:03	2025-06-29 03:06:50	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	31	35	\N	2025-07-12 16:03:03	0101000020E610000000000000000000000000000000000000	California	Mammoth Lakes	\N	0
80	Franz Klammer Sports	franz-klammer-sports	Ski and snowboard equipment rental and retail.	business_b2c	7	\N	["retail","ski_rental","snowboard","gear"]	\N	2	\N	\N	info@franzklammersports.com	https://franzklammersports.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-07-12 16:03:03	2025-06-29 03:06:50	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	31	35	\N	2025-07-12 16:03:03	0101000020E610000000000000000000000000000000000000	California	Mammoth Lakes	\N	0
81	Mammoth Mountain Ski & Board	mammoth-mountain-ski-board	Ski and snowboard rentals plus retail shop.	business_b2c	7	\N	["rental","ski","snowboard","gear"]	\N	2	\N	\N	info@mammothmountainski&board.com	https://mammothmountainski&board.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-07-12 16:03:03	2025-06-29 03:06:50	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	31	35	\N	2025-07-12 16:03:03	0101000020E610000000000000000000000000000000000000	California	Mammoth Lakes	\N	0
74	Skadi	skadi	Upscale fine dining with French-inspired cuisine.	business_b2c	1	\N	["fine_dining","french","seafood","steakhouse"]	\N	2	\N	\N	info@skadi.com	https://skadi.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-07-12 16:03:03	2025-06-29 03:06:50	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	31	35	\N	2025-07-12 16:03:03	0101000020E610000000000000000000000000000000000000	California	Mammoth Lakes	\N	0
75	Mammoth Brewing Company	mammoth-brewing-company	Craft brewery and pub with rotating beers and pub fare.	business_b2c	1	\N	["brewery","beer","brewpub","bar"]	\N	2	\N	\N	info@mammothbrewingcompany.com	https://mammothbrewingcompany.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-07-12 16:03:03	2025-06-29 03:06:50	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	31	35	\N	2025-07-12 16:03:03	0101000020E610000000000000000000000000000000000000	California	Mammoth Lakes	\N	0
76	Toomey's	toomeys	Upscale bar and restaurant with craft cocktails.	business_b2c	1	\N	["bar","restaurant","cocktails","upscale"]	\N	2	\N	\N	info@toomeys.com	https://toomeys.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-07-12 16:03:03	2025-06-29 03:06:50	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	31	35	\N	2025-07-12 16:03:03	0101000020E610000000000000000000000000000000000000	California	Mammoth Lakes	\N	0
89	Mammoth Mountain Scenic Gondola	mammoth-mountain-scenic-gondola	Scenic gondola ride to Eagle Lodge with panoramic views.	business_b2c	21	\N	["gondola","scenic","ride","views"]	\N	2	2	\N	info@mammothmountainscenicgondola.com	https://mammothmountainscenicgondola.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-07-12 16:03:03	2025-06-29 03:06:50	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	31	35	\N	2025-07-12 16:03:03	0101000020E610000000000000000000000000000000000000	California	Mammoth Lakes	\N	0
8	LayerCake Marketing	layercake-marketing	Digital Solutions. We make web, mobile, social and marketing solutions that accelerate and grow your business. From conception to perfection, we craft each one of our digital products by hand.	online	16	\N	[]	\N	2	2	714-261-0903	eric@layercakemarketing.com	https://layercake.marketing	[]	\N	["http:\\/\\/localhost:8000\\/storage\\/directory-entries\\/gallerys\\/cw7cWYWZaU2jzej8t94dj6yNdTLG12sI8EtzDdY3.jpg","http:\\/\\/localhost:8000\\/storage\\/directory-entries\\/gallerys\\/xk7Sdg9fZ0sllt3TqC6SdIvTyBBhjBWTm5BhzBZ0.png"]	published	f	f	f	\N	\N	\N	0	0	2025-06-01 22:44:55	2025-06-29 00:50:21	\N	http://localhost:8000/storage/directory-entries/logos/a59072tzOZFkjWXKFG8GWv25dbSrWNhqYOa8iSz8.png	http://localhost:8000/storage/directory-entries/covers/rdRtO40X9QZxabjFYc1zc0cZnEHCAEhNzw735Oht.png	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	0
5	LearnPro Online Academy	learnpro-online-academy	Professional development courses and certifications online.	online	25	1	["digital","remote"]	\N	1	\N	\N	info@learnpro.edu	https://learnpro.edu	{"facebook":"https:\\/\\/facebook.com\\/learnpro-online-academy","instagram":"https:\\/\\/instagram.com\\/learnpro-online-academy"}	\N	\N	published	t	f	f	\N	\N	\N	0	0	2025-06-01 20:18:34	2025-06-01 20:18:34	2025-06-01 20:18:34	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	1	\N	\N	2025-07-08 14:38:17	\N	California	\N	\N	0
77	Smokeyard BBQ	smokeyard-bbq	<h3>Steakhouse fare, with South African influence in a casual, chic atmosphere.&nbsp; The perfectly crafted menu of high quality food and tasty cocktails makes Smokeyard Bbq and Chop Shop a must-have destination in the heart of the Village at Mammoth.</h3><p></p><h3><strong>We have a handful of available diner reservations available- we have tables available between 4PM and 5:15PM and from 7:45 to 9:30PM.</strong></h3>	business_b2c	5	\N	["barbecue","bbq","smoked_meats","southern"]	\N	2	2	760 934 3300	info@smokeyard.com	https://www.smokeyard.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-07-12 16:03:03	2025-06-29 03:06:50	https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A/33a56bd9-766a-4cc8-24b2-b862acf1e400/public	https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A/64c5191b-22b9-4f42-20ec-2f3d4fc43d00/public	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	31	35	\N	2025-07-12 16:03:03	0101000020E6100000E92B483316BF5DC033A7CB6262D34240	California	Mammoth Lakes	\N	0
69	The Mogul Restaurant	the-mogul-restaurant	<p>Historic mountain lodge restaurant offering American cuisine.</p><p><strong>Open 7 days each week.<br>Closed Tuesdays May 6, 13, 20th<br>Open at 5:00pm in the Bar and 5:30pm for dinner.<br><br>Kid's Menu Available * Full Bar<br><br>Don't know where we are? Check our Location<br><br>Reservations Accepted: 760-934-3039</strong></p><p></p>	business_b2c	5	\N	["restaurant","american","dinner","family_friendly"]	\N	2	2	760-934-3039	Carey@TheMogul.com	https://themogul.com	\N	\N	["https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/01eb7d9f-7528-4ae4-ac00-9c1ed3164500\\/public","https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/867d130e-08fe-4004-05aa-f7a2453a3200\\/public","https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/3236af5a-4ef5-43ef-6a7e-067eb13f2400\\/public","https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/3b6c3e06-cae5-4b98-7fb6-bb875b835800\\/public"]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-07-12 16:03:03	2025-06-29 03:06:50	https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A/3deaecf0-7aed-43b6-c744-ca0a6ad08200/public	https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A/5ace8ab2-8ee6-4d28-4163-a35c2ca8ec00/public	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	31	35	\N	2025-07-12 16:03:03	0101000020E6100000F25EB532E1BD5DC0DFC325C79DD24240	California	Mammoth Lakes	\N	0
70	Roberto's Café	robertos-cafe	Casual Italian restaurant serving wood-fired pizzas and homemade pasta.	business_b2c	3	\N	["italian","pizza","pasta","cafe"]	\N	2	2	760.934.3667	info@robertoscafe.com	https://robertoscafe.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-07-12 16:03:03	2025-06-29 03:06:50	http://localhost:8000/storage/directory-entries/logos/tu6a7C6e3328SWmaE4KzC4LHv2b1Fu0AOEu06ZQ5.webp	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	31	35	\N	2025-07-12 16:03:03	0101000020E610000000000000000000000000000000000000	California	Mammoth Lakes	\N	0
97	Woolly's Tube Park	woollys-tube-park	Snow tubing park with multiple lanes and lifts.	business_b2c	17	\N	["tubing","winter_sports","entertainment"]	\N	2	\N	\N	info@woollystubepark.com	https://woollystubepark.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-07-12 16:03:04	2025-06-29 03:06:50	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	31	35	\N	2025-07-12 16:03:04	0101000020E610000000000000000000000000000000000000	California	Mammoth Lakes	\N	0
99	Mammoth Mountaineering Supply	mammoth-mountaineering-supply	<p>Ski Shop</p>	business_b2c	28	\N	\N	\N	2	\N	(760) 934-4191	dave@mammothgear.com	http://mammothgear.com	\N	\N	\N	published	f	f	f	\N	\N	\N	0	0	2025-07-05 19:03:22	2025-07-12 16:03:04	2025-07-05 19:03:22	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	31	35	\N	2025-07-12 16:03:04	0101000020E61000000742B28009BE5DC01DC9E53FA4D34240	California	Mammoth Lakes	\N	0
71	Good Life Café	good-life-cafe	Cozy café offering breakfast, brunch, and specialty coffee.	business_b2c	5	\N	["cafe","breakfast","brunch","coffee"]	\N	2	2	\N	info@goodlifecafe.com	https://goodlifecafe.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-07-12 16:03:03	2025-06-29 03:06:50	https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A/390b433e-f1df-4970-a687-263f5eb4a300/public	https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A/b00d0d8d-0555-4c0c-9fb2-850d88ccb500/public	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	31	35	\N	2025-07-12 16:03:03	0101000020E610000000000000000000000000000000000000	California	Mammoth Lakes	\N	0
72	Burgers Restaurant	burgers-restaurant	Gourmet burgers, fries, and milkshakes in a casual setting.	business_b2c	5	\N	["burgers","american","casual","diner"]	\N	2	2	\N	info@burgersrestaurant.com	https://burgersrestaurant.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-07-12 16:03:03	2025-06-29 03:06:50	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	31	35	\N	2025-07-12 16:03:03	0101000020E610000000000000000000000000000000000000	California	Mammoth Lakes	\N	0
78	Gabriella's Italian Ristorante	gabriellas-italian-ristorante	Family-owned Italian restaurant serving classic dishes.	business_b2c	1	\N	["italian","fine_dining","family_friendly"]	\N	2	\N	\N	info@gabriellasitalianristorante.com	https://gabriellasitalianristorante.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-07-12 16:03:03	2025-06-29 03:06:50	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	31	35	\N	2025-07-12 16:03:03	0101000020E610000000000000000000000000000000000000	California	Mammoth Lakes	\N	0
82	Volcom Store	volcom-store	Skate and snowboard apparel and accessories.	business_b2c	7	\N	["retail","apparel","skate","snowboard"]	\N	2	\N	\N	info@volcomstore.com	https://volcomstore.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-07-12 16:03:03	2025-06-29 03:06:50	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	31	35	\N	2025-07-12 16:03:03	0101000020E610000000000000000000000000000000000000	California	Mammoth Lakes	\N	0
83	Sierra Runner	sierra-runner	Footwear and sporting goods store.	business_b2c	28	\N	["retail","footwear","sports","run"]	\N	2	2	\N	info@sierrarunner.com	https://sierrarunner.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-07-12 16:03:03	2025-06-29 03:06:50	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	31	35	\N	2025-07-12 16:03:03	0101000020E610000000000000000000000000000000000000	California	Mammoth Lakes	\N	0
84	Denali Surf & Sport	denali-surf-sport	Surf, skate, and snow gear.	business_b2c	7	\N	["retail","skate","board","surf"]	\N	2	\N	\N	info@denalisurf&sport.com	https://denalisurf&sport.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-07-12 16:03:03	2025-06-29 03:06:50	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	31	35	\N	2025-07-12 16:03:03	0101000020E610000000000000000000000000000000000000	California	Mammoth Lakes	\N	0
85	The Powder House & Gear	the-powder-house-gear	Ski, snowboard rentals and outerwear retail.	business_b2c	7	\N	["retail","equipment_rental","ski","snowboard"]	\N	2	\N	\N	info@thepowderhouse&gear.com	https://thepowderhouse&gear.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-07-12 16:03:03	2025-06-29 03:06:50	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	31	35	\N	2025-07-12 16:03:03	0101000020E610000000000000000000000000000000000000	California	Mammoth Lakes	\N	0
86	Mammoth Camper & RV	mammoth-camper-rv	Camper rental and outdoor gear.	business_b2c	7	\N	["rental","camping","rv","gear"]	\N	2	\N	\N	info@mammothcamper&rv.com	https://mammothcamper&rv.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-07-12 16:03:03	2025-06-29 03:06:50	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	31	35	\N	2025-07-12 16:03:03	0101000020E610000000000000000000000000000000000000	California	Mammoth Lakes	\N	0
87	Mountain Shop	mountain-shop	Outdoor clothing and equipment retailer.	business_b2c	7	\N	["retail","outdoor","clothing","equipment"]	\N	2	\N	\N	info@mountainshop.com	https://mountainshop.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-07-12 16:03:03	2025-06-29 03:06:50	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	31	35	\N	2025-07-12 16:03:03	0101000020E610000000000000000000000000000000000000	California	Mammoth Lakes	\N	0
88	Majestic Fine Art	majestic-fine-art	Fine art gallery featuring local artists.	business_b2c	7	\N	["art","gallery","shopping","local_artists"]	\N	2	\N	\N	info@majesticfineart.com	https://majesticfineart.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-07-12 16:03:03	2025-06-29 03:06:50	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	31	35	\N	2025-07-12 16:03:03	0101000020E610000000000000000000000000000000000000	California	Mammoth Lakes	\N	0
90	Mammoth Lakes Welcome Center	mammoth-lakes-welcome-center	Visitor center with local information and gift shop.	business_b2c	17	\N	["visitor_center","tourism","information","gifts"]	\N	2	\N	\N	info@mammothlakeswelcomecenter.com	https://mammothlakeswelcomecenter.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-07-12 16:03:03	2025-06-29 03:06:50	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	31	35	\N	2025-07-12 16:03:03	0101000020E610000000000000000000000000000000000000	California	Mammoth Lakes	\N	0
91	Canyon Cinema	canyon-cinema	Local movie theater showing new releases and classics.	business_b2c	17	\N	["cinema","movies","entertainment","family"]	\N	2	\N	\N	info@canyoncinema.com	https://canyoncinema.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-07-12 16:03:03	2025-06-29 03:06:50	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	31	35	\N	2025-07-12 16:03:03	0101000020E610000000000000000000000000000000000000	California	Mammoth Lakes	\N	0
92	Mammoth Lakes Ice Rink	mammoth-lakes-ice-rink	Outdoor ice skating rink open seasonally.	business_b2c	17	\N	["ice_skating","rink","seasonal","entertainment"]	\N	2	\N	\N	info@mammothlakesicerink.com	https://mammothlakesicerink.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-07-12 16:03:03	2025-06-29 03:06:50	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	31	35	\N	2025-07-12 16:03:03	0101000020E610000000000000000000000000000000000000	California	Mammoth Lakes	\N	0
93	Sierra Star Golf Course	sierra-star-golf-course	Resort golf course with mountain views.	business_b2c	19	\N	["golf","sports","recreation","entertainment"]	\N	2	2	\N	info@sierrastargolfcourse.com	https://sierrastargolfcourse.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-07-12 16:03:03	2025-06-29 03:06:50	\N	https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A/5479c8b9-82de-466d-eaae-190ac302ea00/public	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	31	35	\N	2025-07-12 16:03:03	0101000020E610000000000000000000000000000000000000	California	Mammoth Lakes	\N	0
94	Mammoth Lakes Film Festival	mammoth-lakes-film-festival	Annual film festival showcasing independent films.	business_b2c	17	\N	["festival","movies","arts","entertainment"]	\N	2	\N	\N	info@mammothlakesfilmfestival.com	https://mammothlakesfilmfestival.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-07-12 16:03:04	2025-06-29 03:06:50	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	31	35	\N	2025-07-12 16:03:04	0101000020E610000000000000000000000000000000000000	California	Mammoth Lakes	\N	0
95	Mammoth Brewing Co. Live Stage	mammoth-brewing-co-live-stage	<p>Live music venue inside Mammoth Brewing Company.</p><p><strong><em>Mammoth Brewing Co.:</em></strong><br>Sunday – Thursday: 10am – 9pm*<br>Friday &amp; Saturday: 10am – 9:30pm*</p><p><strong><em>The EATery:</em></strong><br>Daily: 11:00am – Close</p><p>*Closing times are subject to early closures during shoulder seasons</p><p><strong>Reservations:</strong> We do not take reservations.</p><p></p><h2><strong>Welcome to MBC &amp; The EATery<br></strong></h2><p>We’ve been obsessively crafting beers since 1995. We pioneer, curious and unfaltering, determined not to stop until we achieve mouth-watering perfection, often at the expense of reasonable bedtimes and our social lives. Pulling inspiration from our natural surroundings, we boldly blend the best of our local ingredients with the know-how we’ve picked up from years of brewing award-winning beer.</p><p>Our brewery, tasting room, beer garden, and retail store are located at 18 Lake Mary Road, at the corner of Main Street &amp; Minaret Road in Mammoth Lakes. The EATery is located in the tasting room, supplying amazing beer-centric food by chef Brandon Brocia. Check out The EATery menu here. Savor a pint and a bite to eat, sample a tasting flight, pick up a 6-pack, fill your growler, and enjoy the mountain views and friendly atmosphere.</p><p>Your well-behaved, leashed pupper is welcome in our outdoor beer garden. No barking, biting, or begging, and don't leave your trail buddy unattended.</p><p>Fun events include live music and Trivia Night, and we also host private events. See our Events page for more information.</p>	business_b2c	5	\N	["live_music","venue","concerts","brewery"]	\N	2	2	\N	info@mammothbrewingcolivestage.com	https://mammothbrewingco.com/	\N	\N	["http:\\/\\/localhost:8000\\/storage\\/directory-entries\\/gallerys\\/lXui2uPooEfFvkR0unMgRmwF6fm4nG5Pq9yfcqTD.jpg","http:\\/\\/localhost:8000\\/storage\\/directory-entries\\/gallerys\\/T8YgxkYcUrSK6Kzaq2PJMKNVFIYBoxUCGib8i15x.jpg"]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-07-12 16:03:04	2025-06-29 03:06:50	http://localhost:8000/storage/directory-entries/logos/LvIENSxqBs2eeg3lI0wiTjoNFQA7J2TUGZ7SZXEq.webp	http://localhost:8000/storage/directory-entries/covers/gdKhU1dJWT6uRIxFnzG5MnzVPG7lWsUPu3O9k0FC.jpg	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	31	35	\N	2025-07-12 16:03:04	0101000020E6100000C9B08A3732B95DC04968CBB914D34240	California	Mammoth Lakes	\N	0
96	Epic Discovery	epic-discovery	Adventure park with zip lines, climbing garden, and mountain coaster.	business_b2c	17	\N	["adventure","zip_line","coaster","climbing"]	\N	2	\N	\N	info@epicdiscovery.com	https://epicdiscovery.com	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-07-12 16:03:04	2025-06-29 03:06:50	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	31	35	\N	2025-07-12 16:03:04	0101000020E610000000000000000000000000000000000000	California	Mammoth Lakes	\N	0
98	Mammoth Lakes Museum	mammoth-lakes-museum	<h1>Our Hours</h1><p><strong>June-september: </strong>10 AM to 6 PM</p><p><strong>Open Monday-Sunday, closed on Wednesdays</strong></p><p><strong>We are open six days a week Memorial Weekend through the end of September.</strong></p><hr><h1>Other Information</h1><p><strong>Fees</strong>: FREE! A $5.00 donation is suggested &amp; memberships are appreciated.</p><p><strong>ADA Accessibility:&nbsp;</strong>Access is available for those who park on the lawn near the disabled placard and enter through the kitchen (back door) on the ramp.</p><p><strong>Eco-Friendly:&nbsp;</strong>Connected to the town’s bike trails in Mammoth Lakes.</p><p><strong>Pet Friendly: &nbsp;</strong>Pets should be leashed on the museum grounds per the Town of Mammoth Lakes and the U.S. Forest Service leash laws. Service animals only are welcome inside the museum. We provide kennels a.k.a “doggie dorms” outside for non-service animals. <strong>Please clean up after your pets!</strong></p>	business_b2c	21	\N	["museum","history","culture","education"]	\N	2	2	(760) 934-6918	info@mammothlakesmuseum.org	https://www.mammothmuseum.org	\N	\N	[]	published	f	f	f	\N	\N	\N	0	0	2025-06-29 03:06:50	2025-07-12 16:03:04	2025-06-29 03:06:50	http://localhost:8000/storage/directory-entries/logos/R2eyXXEJllqFOz39eFE4frXx2enDrLjHtzWaDUcY.jpg	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	31	35	\N	2025-07-12 16:03:04	0101000020E61000004FE960FD9FBD5DC0DA8F149161D14240	California	Mammoth Lakes	\N	0
\.


--
-- Data for Name: directory_entry_follows; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.directory_entry_follows (id, user_id, directory_entry_id, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: home_page_settings; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.home_page_settings (id, hero_title, hero_subtitle, hero_image_path, cta_text, cta_link, featured_places, testimonials, custom_scripts, created_at, updated_at) FROM stdin;
1	Discover Amazing Places Near You	Find the best restaurants, services, and experiences in your local community	\N	Start Exploring	/places	[]	[{"quote":"This directory helped me find the perfect restaurant for our anniversary dinner. The reviews and photos were incredibly helpful!","author":"Sarah Johnson","company":"Happy Customer"},{"quote":"As a small business owner, this platform has been invaluable for connecting with new customers in our area.","author":"Mike Chen","company":"Chen's Bakery"},{"quote":"I love how easy it is to create lists of my favorite places and share them with friends.","author":"Emily Rodriguez","company":"Local Explorer"}]		2025-07-14 03:05:55	2025-07-14 03:05:55
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
-- Data for Name: lists; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.lists (id, user_id, name, description, created_at, updated_at, featured_image, view_count, settings, is_featured, featured_image_cloudflare_id, category_id, visibility, is_draft, published_at, scheduled_for, gallery_images, is_pinned, pinned_at, status, status_reason, status_changed_at, status_changed_by, type, is_region_specific, region_id, is_category_specific, place_ids, order_index, slug) FROM stdin;
14	2	White Water Rafting the American River	\N	2025-07-06 00:37:28	2025-07-10 03:48:01	https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A/9ec2cba4-529e-4703-af57-b769c5609d00/public	5	\N	f	9ec2cba4-529e-4703-af57-b769c5609d00	1	public	f	2025-07-06 00:37:28	\N	[{"id":"9ec2cba4-529e-4703-af57-b769c5609d00","url":"https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/9ec2cba4-529e-4703-af57-b769c5609d00\\/public","filename":"07-09-2021_SFA_HB_SW_I00010020.jpg"},{"id":"9d10e557-5d31-43f4-7efb-4cc267042500","url":"https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/9d10e557-5d31-43f4-7efb-4cc267042500\\/public","filename":"fav_07-09-2021_SFA_SC_SW_I00070037.jpg"}]	f	\N	active	\N	\N	\N	user	f	\N	f	\N	0	white-water-rafting-the-american-river
10	2	The Top 10 things to do in Woodbridge, (Irvine, California) neighborhood.	Here are the top 10 things to do in Woodbridge, Irvine, CA—perfect for enjoying lakeside fun, sports, local eats, and scenic strolls 🌿:	2025-07-02 23:58:37	2025-07-10 06:39:31	https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A/59a65937-7db6-4ad1-5e59-7dcc44a4a200/public	35	\N	f	59a65937-7db6-4ad1-5e59-7dcc44a4a200	1	public	f	\N	\N	[{"id":"59a65937-7db6-4ad1-5e59-7dcc44a4a200","url":"https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/59a65937-7db6-4ad1-5e59-7dcc44a4a200\\/public","filename":"IMG_1799.jpeg"},{"id":"14a7fda6-4811-49a7-3b57-a4ea2395d600","url":"https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/14a7fda6-4811-49a7-3b57-a4ea2395d600\\/public","filename":"IMG_2303.jpeg"}]	f	\N	active	\N	\N	\N	user	f	\N	f	\N	0	the-top-10-things-to-do-in-woodbridge-irvine-california-neighborhood
9	5	Luiggi's Famous Artisan Pizza Recipe	\N	2025-07-02 16:23:53	2025-07-10 06:42:44	https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A/cc0cf4d2-e933-45db-8325-a71e7142b900/public	30	\N	f	cc0cf4d2-e933-45db-8325-a71e7142b900	7	public	f	\N	\N	[{"id":"cc0cf4d2-e933-45db-8325-a71e7142b900","url":"https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/cc0cf4d2-e933-45db-8325-a71e7142b900\\/public","filename":"getty-images-dP7MgFCuNHY-unsplash.jpg"}]	f	\N	active	\N	\N	\N	user	f	\N	f	\N	0	luiggis-famous-artisan-pizza-recipe
7	2	Fun Day at the Park List	\N	2025-06-27 05:50:56	2025-07-14 02:18:51	https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A/ef34f3b4-292e-4799-fcfe-459af026ee00/thumbnail	6	\N	f	ef34f3b4-292e-4799-fcfe-459af026ee00	1	public	f	\N	\N	[{"id":"ef34f3b4-292e-4799-fcfe-459af026ee00","url":"https:\\/\\/imagedelivery.net\\/nCX0WluV4kb4MYRWgWWi4A\\/ef34f3b4-292e-4799-fcfe-459af026ee00\\/thumbnail","filename":"houston-max-K82z6_8y_TU-unsplash.jpg"}]	f	\N	active	\N	\N	\N	user	f	\N	f	\N	0	fun-day-at-the-park-list
16	1	Best Restaurants in Town	Editor's picks for the best dining experiences	2025-07-15 01:11:39	2025-07-15 01:13:41	\N	0	\N	f	\N	1	public	f	2025-07-15 01:11:39	\N	\N	f	\N	active	\N	\N	\N	user	f	\N	f	\N	0	best-restaurants-in-town
17	1	Top Places to Visit	Must-see places handpicked by our editors	2025-07-15 01:13:52	2025-07-15 05:07:46	\N	1	\N	f	\N	\N	public	f	2025-07-15 01:13:52	\N	\N	f	\N	active	\N	\N	\N	user	f	\N	f	\N	0	top-places-to-visit
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
-- Data for Name: locations; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.locations (id, directory_entry_id, address_line1, address_line2, city, state, zip_code, country, latitude, longitude, hours_of_operation, holiday_hours, is_wheelchair_accessible, has_parking, amenities, place_id, created_at, updated_at, geom, cross_streets, neighborhood) FROM stdin;
2	2	456 Technology Blvd	\N	San Francisco	CA	94105	USA	37.7749000	-122.4194000	{"monday":"10:00-20:00","tuesday":"10:00-20:00","wednesday":"10:00-20:00","thursday":"10:00-20:00","friday":"10:00-21:00","saturday":"10:00-21:00","sunday":"11:00-18:00"}	\N	t	t	["parking","delivery","takeout"]	\N	2025-06-01 20:18:34	2025-06-01 20:18:34	0101000020E610000050FC1873D79A5EC0D0D556EC2FE34240	\N	\N
3	3	789 Wellness Way	\N	Miami	FL	33101	USA	25.7617000	-80.1918000	{"monday":"09:00-19:00","tuesday":"09:00-19:00","wednesday":"09:00-19:00","thursday":"09:00-19:00","friday":"09:00-20:00","saturday":"09:00-20:00","sunday":"10:00-17:00"}	\N	t	t	["wifi","parking","reservations"]	\N	2025-06-01 20:18:34	2025-06-01 20:18:34	0101000020E6100000DCD78173460C54C0FB5C6DC5FEC23940	\N	\N
43	98	400 Trono Ln	\N	Mammoth Lakes	CA	93546	US	37.6357900	-118.9628900	\N	\N	f	f	\N	\N	2025-06-29 03:06:50	2025-06-29 03:21:25	0101000020E61000004FE960FD9FBD5DC0DA8F149161D14240	\N	\N
15	70	1002 Old Mammoth Rd	\N	Mammoth Lakes	CA	93546	US	0.0000000	0.0000000	\N	\N	f	f	\N	\N	2025-06-29 03:06:50	2025-06-29 03:06:50	0101000020E610000000000000000000000000000000000000	\N	\N
17	72	114 Old Mammoth Rd	\N	Mammoth Lakes	CA	93546	US	0.0000000	0.0000000	\N	\N	f	f	\N	\N	2025-06-29 03:06:50	2025-06-29 03:06:50	0101000020E610000000000000000000000000000000000000	\N	\N
18	73	678 Old Mammoth Rd	\N	Mammoth Lakes	CA	93546	US	0.0000000	0.0000000	\N	\N	f	f	\N	\N	2025-06-29 03:06:50	2025-06-29 03:06:50	0101000020E610000000000000000000000000000000000000	\N	\N
19	74	2763 Main St	\N	Mammoth Lakes	CA	93546	US	0.0000000	0.0000000	\N	\N	f	f	\N	\N	2025-06-29 03:06:50	2025-06-29 03:06:50	0101000020E610000000000000000000000000000000000000	\N	\N
20	75	18 Lakeview Blvd	\N	Mammoth Lakes	CA	93546	US	0.0000000	0.0000000	\N	\N	f	f	\N	\N	2025-06-29 03:06:50	2025-06-29 03:06:50	0101000020E610000000000000000000000000000000000000	\N	\N
21	76	120 Canyon Blvd	\N	Mammoth Lakes	CA	93546	US	0.0000000	0.0000000	\N	\N	f	f	\N	\N	2025-06-29 03:06:50	2025-06-29 03:06:50	0101000020E610000000000000000000000000000000000000	\N	\N
23	78	3150 Main St	\N	Mammoth Lakes	CA	93546	US	0.0000000	0.0000000	\N	\N	f	f	\N	\N	2025-06-29 03:06:50	2025-06-29 03:06:50	0101000020E610000000000000000000000000000000000000	\N	\N
22	77	1111 Forest Trail Road #201	\N	Mammoth Lakes	CA	93546	US	37.6514400	-118.9857300	\N	\N	f	f	\N	\N	2025-06-29 03:06:50	2025-07-04 23:16:57	0101000020E6100000E92B483316BF5DC033A7CB6262D34240	\N	\N
14	69	1528 Tavern Rd	\N	Mammoth Lakes	CA	93546	US	37.6454400	-118.9668700	\N	\N	f	f	\N	\N	2025-06-29 03:06:50	2025-07-05 21:40:03	0101000020E6100000F25EB532E1BD5DC0DFC325C79DD24240	\N	\N
16	71	126 Old Mammoth Road	\N	Mammoth Lakes	CA	93546	US	0.0000000	0.0000000	\N	\N	f	f	\N	\N	2025-06-29 03:06:50	2025-07-09 06:39:19	0101000020E610000000000000000000000000000000000000	\N	\N
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

COPY public.login_page_settings (id, background_image_path, welcome_message, custom_css, social_login_enabled, created_at, updated_at) FROM stdin;
1	\N	Welcome Back! Sign in to your account to continue exploring and managing your favorite places.		t	2025-07-14 03:05:55	2025-07-14 03:05:55
\.


--
-- Data for Name: pages; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.pages (id, title, slug, content, status, meta_title, meta_description, created_at, updated_at) FROM stdin;
1	About Us	about-us	<h2>Our Story</h2>\n<p>Welcome to our directory platform! We are dedicated to helping people discover the best places in their local communities.</p>\n\n<h3>Our Mission</h3>\n<p>Our mission is to connect people with amazing local businesses, services, and experiences. We believe in supporting local communities by making it easier for people to find what they need close to home.</p>\n\n<h3>What We Do</h3>\n<ul>\n<li>Curate comprehensive listings of local businesses</li>\n<li>Provide detailed information and reviews</li>\n<li>Enable easy search and discovery</li>\n<li>Support local business growth</li>\n</ul>\n\n<h3>Our Values</h3>\n<p>We are committed to:</p>\n<ul>\n<li><strong>Accuracy</strong> - Providing reliable, up-to-date information</li>\n<li><strong>Community</strong> - Supporting local businesses and neighborhoods</li>\n<li><strong>Innovation</strong> - Continuously improving our platform</li>\n<li><strong>Trust</strong> - Building lasting relationships with users and businesses</li>\n</ul>	published	About Us - Learn More About Our Directory Platform	Discover our mission to connect people with the best local businesses and services in their communities.	2025-07-14 03:05:55	2025-07-14 03:05:55
2	Privacy Policy	privacy-policy	<h2>Privacy Policy</h2>\n<p>Last updated: July 14, 2025</p>\n\n<h3>Information We Collect</h3>\n<p>We collect information you provide directly to us, such as when you create an account, submit a listing, or contact us.</p>\n\n<h3>How We Use Your Information</h3>\n<p>We use the information we collect to:</p>\n<ul>\n<li>Provide, maintain, and improve our services</li>\n<li>Process transactions and send related information</li>\n<li>Send you technical notices and support messages</li>\n<li>Respond to your comments and questions</li>\n</ul>\n\n<h3>Information Sharing</h3>\n<p>We do not sell, trade, or otherwise transfer your personal information to third parties without your consent, except as described in this policy.</p>\n\n<h3>Data Security</h3>\n<p>We implement appropriate technical and organizational measures to protect your personal information against unauthorized access, alteration, disclosure, or destruction.</p>\n\n<h3>Contact Us</h3>\n<p>If you have questions about this Privacy Policy, please contact us at privacy@example.com.</p>	published	Privacy Policy - How We Protect Your Information	Learn about our commitment to protecting your privacy and how we handle your personal information.	2025-07-14 03:05:55	2025-07-14 03:05:55
3	Terms of Service	terms-of-service	<h2>Terms of Service</h2>\n<p>By using our platform, you agree to these terms. Please read them carefully.</p>\n\n<h3>Use of Our Services</h3>\n<p>You must follow any policies made available to you within the services. You may use our services only as permitted by law.</p>\n\n<h3>Your Account</h3>\n<p>You may need an account to use some of our services. You are responsible for keeping your account secure.</p>\n\n<h3>Content</h3>\n<p>Our services display content that is not ours. This content is the sole responsibility of the entity that makes it available.</p>\n\n<h3>Prohibited Uses</h3>\n<p>You may not use our services to:</p>\n<ul>\n<li>Violate any laws or regulations</li>\n<li>Infringe on intellectual property rights</li>\n<li>Distribute spam or malicious content</li>\n<li>Attempt to gain unauthorized access to our systems</li>\n</ul>\n\n<h3>Termination</h3>\n<p>We may suspend or terminate your access to our services at any time for any reason.</p>	published	Terms of Service - Rules for Using Our Platform	Read our terms of service to understand the rules and guidelines for using our directory platform.	2025-07-14 03:05:55	2025-07-14 03:05:55
4	Contact Us	contact-us	<h2>Get in Touch</h2>\n<p>We're here to help! Whether you have questions, feedback, or need assistance, we'd love to hear from you.</p>\n\n<h3>Contact Information</h3>\n<p><strong>Email:</strong> support@example.com<br>\n<strong>Phone:</strong> (555) 123-4567<br>\n<strong>Hours:</strong> Monday - Friday, 9:00 AM - 5:00 PM EST</p>\n\n<h3>Office Location</h3>\n<p>123 Main Street<br>\nSuite 100<br>\nAnytown, ST 12345</p>\n\n<h3>For Businesses</h3>\n<p>If you're a business owner looking to claim or update your listing, please email us at business@example.com.</p>\n\n<h3>For Media Inquiries</h3>\n<p>Members of the press can reach our media team at press@example.com.</p>	published	Contact Us - Get in Touch with Our Team	Contact our support team for help with listings, account questions, or general inquiries.	2025-07-14 03:05:55	2025-07-14 03:05:55
5	How It Works	how-it-works	<h2>How Our Directory Works</h2>\n<p>Finding the perfect place has never been easier. Here's how to make the most of our platform.</p>\n\n<h3>For Visitors</h3>\n<h4>1. Search or Browse</h4>\n<p>Use our search bar to find specific businesses or browse by category and location.</p>\n\n<h4>2. Explore Listings</h4>\n<p>View detailed information including hours, contact details, photos, and reviews.</p>\n\n<h4>3. Save Favorites</h4>\n<p>Create lists of your favorite places to easily find them later.</p>\n\n<h3>For Business Owners</h3>\n<h4>1. Claim Your Listing</h4>\n<p>Search for your business and claim your free listing.</p>\n\n<h4>2. Add Details</h4>\n<p>Complete your profile with photos, descriptions, and business information.</p>\n\n<h4>3. Engage with Customers</h4>\n<p>Respond to reviews and keep your information up to date.</p>	published	How It Works - Using Our Directory Platform	Learn how to search for places, save favorites, and make the most of our directory platform.	2025-07-14 03:05:55	2025-07-14 03:05:55
6	Test Page	test-page-1752462585	Test content	draft	\N	\N	2025-07-14 03:09:45	2025-07-14 03:09:45
\.


--
-- Data for Name: password_reset_tokens; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.password_reset_tokens (email, token, created_at) FROM stdin;
\.


--
-- Data for Name: pinned_lists; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.pinned_lists (id, user_id, list_id, sort_order, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: place_regions; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.place_regions (id, place_id, region_id, association_type, distance_meters, confidence_score, region_type, region_level, created_at, updated_at, is_featured, featured_order, featured_at) FROM stdin;
1	2	31	contained	\N	1.00	state	1	2025-07-12 16:03:03	2025-07-12 16:03:03	f	\N	\N
2	2	32	contained	\N	1.00	city	2	2025-07-12 16:03:03	2025-07-12 16:03:03	f	\N	\N
3	3	33	contained	\N	1.00	state	1	2025-07-12 16:03:03	2025-07-12 16:03:03	f	\N	\N
4	3	34	contained	\N	1.00	city	2	2025-07-12 16:03:03	2025-07-12 16:03:03	f	\N	\N
5	69	31	contained	\N	1.00	state	1	2025-07-12 16:03:03	2025-07-12 16:03:03	f	\N	\N
6	69	35	contained	\N	1.00	city	2	2025-07-12 16:03:03	2025-07-12 16:03:03	f	\N	\N
7	70	31	contained	\N	1.00	state	1	2025-07-12 16:03:03	2025-07-12 16:03:03	f	\N	\N
8	70	35	contained	\N	1.00	city	2	2025-07-12 16:03:03	2025-07-12 16:03:03	f	\N	\N
9	71	31	contained	\N	1.00	state	1	2025-07-12 16:03:03	2025-07-12 16:03:03	f	\N	\N
10	71	35	contained	\N	1.00	city	2	2025-07-12 16:03:03	2025-07-12 16:03:03	f	\N	\N
11	72	31	contained	\N	1.00	state	1	2025-07-12 16:03:03	2025-07-12 16:03:03	f	\N	\N
12	72	35	contained	\N	1.00	city	2	2025-07-12 16:03:03	2025-07-12 16:03:03	f	\N	\N
13	73	31	contained	\N	1.00	state	1	2025-07-12 16:03:03	2025-07-12 16:03:03	f	\N	\N
14	73	35	contained	\N	1.00	city	2	2025-07-12 16:03:03	2025-07-12 16:03:03	f	\N	\N
15	74	31	contained	\N	1.00	state	1	2025-07-12 16:03:03	2025-07-12 16:03:03	f	\N	\N
16	74	35	contained	\N	1.00	city	2	2025-07-12 16:03:03	2025-07-12 16:03:03	f	\N	\N
17	75	31	contained	\N	1.00	state	1	2025-07-12 16:03:03	2025-07-12 16:03:03	f	\N	\N
18	75	35	contained	\N	1.00	city	2	2025-07-12 16:03:03	2025-07-12 16:03:03	f	\N	\N
19	76	31	contained	\N	1.00	state	1	2025-07-12 16:03:03	2025-07-12 16:03:03	f	\N	\N
20	76	35	contained	\N	1.00	city	2	2025-07-12 16:03:03	2025-07-12 16:03:03	f	\N	\N
21	77	31	contained	\N	1.00	state	1	2025-07-12 16:03:03	2025-07-12 16:03:03	f	\N	\N
22	77	35	contained	\N	1.00	city	2	2025-07-12 16:03:03	2025-07-12 16:03:03	f	\N	\N
23	78	31	contained	\N	1.00	state	1	2025-07-12 16:03:03	2025-07-12 16:03:03	f	\N	\N
24	78	35	contained	\N	1.00	city	2	2025-07-12 16:03:03	2025-07-12 16:03:03	f	\N	\N
25	79	31	contained	\N	1.00	state	1	2025-07-12 16:03:03	2025-07-12 16:03:03	f	\N	\N
26	79	35	contained	\N	1.00	city	2	2025-07-12 16:03:03	2025-07-12 16:03:03	f	\N	\N
27	80	31	contained	\N	1.00	state	1	2025-07-12 16:03:03	2025-07-12 16:03:03	f	\N	\N
28	80	35	contained	\N	1.00	city	2	2025-07-12 16:03:03	2025-07-12 16:03:03	f	\N	\N
29	81	31	contained	\N	1.00	state	1	2025-07-12 16:03:03	2025-07-12 16:03:03	f	\N	\N
30	81	35	contained	\N	1.00	city	2	2025-07-12 16:03:03	2025-07-12 16:03:03	f	\N	\N
31	82	31	contained	\N	1.00	state	1	2025-07-12 16:03:03	2025-07-12 16:03:03	f	\N	\N
32	82	35	contained	\N	1.00	city	2	2025-07-12 16:03:03	2025-07-12 16:03:03	f	\N	\N
33	83	31	contained	\N	1.00	state	1	2025-07-12 16:03:03	2025-07-12 16:03:03	f	\N	\N
34	83	35	contained	\N	1.00	city	2	2025-07-12 16:03:03	2025-07-12 16:03:03	f	\N	\N
35	84	31	contained	\N	1.00	state	1	2025-07-12 16:03:03	2025-07-12 16:03:03	f	\N	\N
36	84	35	contained	\N	1.00	city	2	2025-07-12 16:03:03	2025-07-12 16:03:03	f	\N	\N
37	85	31	contained	\N	1.00	state	1	2025-07-12 16:03:03	2025-07-12 16:03:03	f	\N	\N
38	85	35	contained	\N	1.00	city	2	2025-07-12 16:03:03	2025-07-12 16:03:03	f	\N	\N
39	86	31	contained	\N	1.00	state	1	2025-07-12 16:03:03	2025-07-12 16:03:03	f	\N	\N
40	86	35	contained	\N	1.00	city	2	2025-07-12 16:03:03	2025-07-12 16:03:03	f	\N	\N
41	87	31	contained	\N	1.00	state	1	2025-07-12 16:03:03	2025-07-12 16:03:03	f	\N	\N
42	87	35	contained	\N	1.00	city	2	2025-07-12 16:03:03	2025-07-12 16:03:03	f	\N	\N
43	88	31	contained	\N	1.00	state	1	2025-07-12 16:03:03	2025-07-12 16:03:03	f	\N	\N
44	88	35	contained	\N	1.00	city	2	2025-07-12 16:03:03	2025-07-12 16:03:03	f	\N	\N
45	89	31	contained	\N	1.00	state	1	2025-07-12 16:03:03	2025-07-12 16:03:03	f	\N	\N
46	89	35	contained	\N	1.00	city	2	2025-07-12 16:03:03	2025-07-12 16:03:03	f	\N	\N
47	90	31	contained	\N	1.00	state	1	2025-07-12 16:03:03	2025-07-12 16:03:03	f	\N	\N
48	90	35	contained	\N	1.00	city	2	2025-07-12 16:03:03	2025-07-12 16:03:03	f	\N	\N
49	91	31	contained	\N	1.00	state	1	2025-07-12 16:03:03	2025-07-12 16:03:03	f	\N	\N
50	91	35	contained	\N	1.00	city	2	2025-07-12 16:03:03	2025-07-12 16:03:03	f	\N	\N
51	92	31	contained	\N	1.00	state	1	2025-07-12 16:03:03	2025-07-12 16:03:03	f	\N	\N
52	92	35	contained	\N	1.00	city	2	2025-07-12 16:03:03	2025-07-12 16:03:03	f	\N	\N
53	93	31	contained	\N	1.00	state	1	2025-07-12 16:03:03	2025-07-12 16:03:03	f	\N	\N
54	93	35	contained	\N	1.00	city	2	2025-07-12 16:03:03	2025-07-12 16:03:03	f	\N	\N
55	94	31	contained	\N	1.00	state	1	2025-07-12 16:03:04	2025-07-12 16:03:04	f	\N	\N
56	94	35	contained	\N	1.00	city	2	2025-07-12 16:03:04	2025-07-12 16:03:04	f	\N	\N
57	95	31	contained	\N	1.00	state	1	2025-07-12 16:03:04	2025-07-12 16:03:04	f	\N	\N
58	95	35	contained	\N	1.00	city	2	2025-07-12 16:03:04	2025-07-12 16:03:04	f	\N	\N
59	96	31	contained	\N	1.00	state	1	2025-07-12 16:03:04	2025-07-12 16:03:04	f	\N	\N
60	96	35	contained	\N	1.00	city	2	2025-07-12 16:03:04	2025-07-12 16:03:04	f	\N	\N
61	97	31	contained	\N	1.00	state	1	2025-07-12 16:03:04	2025-07-12 16:03:04	f	\N	\N
62	97	35	contained	\N	1.00	city	2	2025-07-12 16:03:04	2025-07-12 16:03:04	f	\N	\N
63	98	31	contained	\N	1.00	state	1	2025-07-12 16:03:04	2025-07-12 16:03:04	f	\N	\N
64	98	35	contained	\N	1.00	city	2	2025-07-12 16:03:04	2025-07-12 16:03:04	f	\N	\N
65	99	31	contained	\N	1.00	state	1	2025-07-12 16:03:04	2025-07-12 16:03:04	f	\N	\N
66	99	35	contained	\N	1.00	city	2	2025-07-12 16:03:04	2025-07-12 16:03:04	f	\N	\N
\.


--
-- Data for Name: posts; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.posts (id, user_id, content, media, media_type, cloudflare_image_id, cloudflare_video_id, is_tacked, tacked_at, expires_in_days, expires_at, likes_count, replies_count, shares_count, views_count, metadata, created_at, updated_at, deleted_at) FROM stdin;
1	2	Happy Kiddo	[{"url":"https:\\/\\/ik.imagekit.io\\/listerinolistkit\\/posts\\/images\\/IMG_0542_ShTFYD9bM.jpeg","fileId":"687602945c7cd75eb88177f9","metadata":{"name":"IMG_0542_ShTFYD9bM.jpeg","size":1720714,"width":1945,"height":1459,"thumbnailUrl":"https:\\/\\/ik.imagekit.io\\/listerinolistkit\\/tr:n-ik_ml_thumbnail\\/posts\\/images\\/IMG_0542_ShTFYD9bM.jpeg"}},{"url":"https:\\/\\/ik.imagekit.io\\/listerinolistkit\\/posts\\/images\\/IMG_0579_C_gwTV35d.jpeg","fileId":"687602965c7cd75eb8817d0a","metadata":{"name":"IMG_0579_C_gwTV35d.jpeg","size":1211896,"width":1945,"height":1459,"thumbnailUrl":"https:\\/\\/ik.imagekit.io\\/listerinolistkit\\/tr:n-ik_ml_thumbnail\\/posts\\/images\\/IMG_0579_C_gwTV35d.jpeg"}},{"url":"https:\\/\\/ik.imagekit.io\\/listerinolistkit\\/posts\\/images\\/IMG_1533_x_zv-tZCA.jpg","fileId":"687602975c7cd75eb88191dd","metadata":{"name":"IMG_1533_x_zv-tZCA.jpg","size":959580,"width":1459,"height":1945,"thumbnailUrl":"https:\\/\\/ik.imagekit.io\\/listerinolistkit\\/tr:n-ik_ml_thumbnail\\/posts\\/images\\/IMG_1533_x_zv-tZCA.jpg"}}]	images	\N	\N	f	\N	30	2025-08-14 07:26:26	0	0	0	0	\N	2025-07-15 07:26:26	2025-07-15 07:26:26	\N
\.


--
-- Data for Name: region_featured_entries; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.region_featured_entries (id, region_id, directory_entry_id, priority, label, tagline, custom_data, featured_until, is_active, created_at, updated_at) FROM stdin;
1	32	102	10	Top Pick	\N	\N	\N	t	2025-07-15 02:18:02	2025-07-15 02:18:02
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
-- Data for Name: settings; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.settings (id, key, value, type, "group", description, created_at, updated_at) FROM stdin;
2	site_name	Listerino	string	general	The name of your site	2025-07-07 00:14:37	2025-07-15 06:45:00
4	site_tagline	Your Local Directory	string	general	A short tagline for your site	2025-07-15 06:45:00	2025-07-15 06:45:00
5	contact_email	contact@listerino.com	string	general	Primary contact email address	2025-07-15 06:45:00	2025-07-15 06:45:00
6	timezone	America/Los_Angeles	string	general	Default timezone for the site	2025-07-15 06:45:00	2025-07-15 06:45:00
7	meta_description	Discover the best places, businesses, and services in your area	string	seo	Default meta description for search engines	2025-07-15 06:45:00	2025-07-15 06:45:00
8	meta_keywords	directory, local business, places, services, listings	string	seo	Default meta keywords (comma-separated)	2025-07-15 06:45:00	2025-07-15 06:45:00
9	google_analytics_id		string	seo	Google Analytics tracking ID (e.g., G-XXXXXXXXXX)	2025-07-15 06:45:00	2025-07-15 06:45:00
10	robots_txt	User-agent: *\nAllow: /	string	seo	Content for robots.txt file	2025-07-15 06:45:00	2025-07-15 06:45:00
11	og_image		string	social	Default Open Graph image URL for social media sharing	2025-07-15 06:45:00	2025-07-15 06:45:00
12	twitter_handle		string	social	Twitter/X handle (without @)	2025-07-15 06:45:00	2025-07-15 06:45:00
13	facebook_url		string	social	Facebook page URL	2025-07-15 06:45:00	2025-07-15 06:45:00
14	instagram_url		string	social	Instagram profile URL	2025-07-15 06:45:00	2025-07-15 06:45:00
15	linkedin_url		string	social	LinkedIn company page URL	2025-07-15 06:45:00	2025-07-15 06:45:00
16	primary_color	#3B82F6	string	appearance	Primary brand color (hex code)	2025-07-15 06:45:00	2025-07-15 06:45:00
17	secondary_color	#1E40AF	string	appearance	Secondary brand color (hex code)	2025-07-15 06:45:00	2025-07-15 06:45:00
18	logo_url	/images/listerino_logo.svg	string	appearance	Site logo URL	2025-07-15 06:45:00	2025-07-15 06:45:00
19	favicon_url	/favicon.ico	string	appearance	Site favicon URL	2025-07-15 06:45:00	2025-07-15 06:45:00
20	custom_css		string	appearance	Custom CSS to be injected into all pages	2025-07-15 06:45:00	2025-07-15 06:45:00
21	require_email_verification	true	boolean	security	Require email verification for new users	2025-07-15 06:45:00	2025-07-15 06:45:00
22	enable_social_login	false	boolean	security	Enable social media login options	2025-07-15 06:45:00	2025-07-15 06:45:00
23	password_min_length	8	integer	security	Minimum password length for users	2025-07-15 06:45:00	2025-07-15 06:45:00
24	max_upload_size	10	integer	features	Maximum file upload size in MB	2025-07-15 06:45:00	2025-07-15 06:45:00
25	items_per_page	20	integer	features	Default number of items per page in listings	2025-07-15 06:45:00	2025-07-15 06:45:00
26	enable_comments	true	boolean	features	Enable comments on places and lists	2025-07-15 06:45:00	2025-07-15 06:45:00
27	enable_reviews	true	boolean	features	Enable reviews on places	2025-07-15 06:45:00	2025-07-15 06:45:00
28	enable_user_profiles	true	boolean	features	Enable public user profiles	2025-07-15 06:45:00	2025-07-15 06:45:00
29	enable_posts	true	boolean	features	Enable user posts/status updates	2025-07-15 06:45:00	2025-07-15 06:45:00
30	email_from_address	noreply@listerino.com	string	email	Default "from" email address	2025-07-15 06:45:00	2025-07-15 06:45:00
31	email_from_name	Listerino	string	email	Default "from" name for emails	2025-07-15 06:45:00	2025-07-15 06:45:00
32	send_welcome_email	true	boolean	email	Send welcome email to new users	2025-07-15 06:45:00	2025-07-15 06:45:00
33	api_rate_limit	60	integer	api	API rate limit per minute	2025-07-15 06:45:00	2025-07-15 06:45:00
34	enable_public_api	false	boolean	api	Enable public API access	2025-07-15 06:45:00	2025-07-15 06:45:00
3	maintenance_mode	false	boolean	maintenance	Enable maintenance mode	2025-07-07 00:14:37	2025-07-15 06:45:00
35	maintenance_message	We are currently performing scheduled maintenance. Please check back soon.	string	maintenance	Message to display during maintenance mode	2025-07-15 06:45:00	2025-07-15 06:45:00
1	allow_registration	false	boolean	security	Allow new users to register	2025-07-07 00:14:37	2025-07-15 07:27:31
\.


--
-- Data for Name: spatial_ref_sys; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.spatial_ref_sys (srid, auth_name, auth_srid, srtext, proj4text) FROM stdin;
\.


--
-- Data for Name: tags; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.tags (id, name, slug, description, color, is_active, created_at, updated_at) FROM stdin;
1	family-friendly	family-friendly	\N	#10B981	t	2025-07-04 02:36:40	2025-07-04 02:36:40
2	outdoor	outdoor	\N	#059669	t	2025-07-04 02:36:40	2025-07-04 02:36:40
3	budget	budget	\N	#F59E0B	t	2025-07-04 02:36:40	2025-07-04 02:36:40
4	luxury	luxury	\N	#8B5CF6	t	2025-07-04 02:36:40	2025-07-04 02:36:40
5	romantic	romantic	\N	#EC4899	t	2025-07-04 02:36:40	2025-07-04 02:36:40
6	adventure	adventure	\N	#EF4444	t	2025-07-04 02:36:40	2025-07-04 02:36:40
7	educational	educational	\N	#3B82F6	t	2025-07-04 02:36:40	2025-07-04 02:36:40
8	seasonal	seasonal	\N	#6B7280	t	2025-07-04 02:36:40	2025-07-04 02:36:40
9	Tennis	tennis	\N	#6B7280	t	2025-07-04 05:35:30	2025-07-04 05:35:30
12	Italian	italian	\N	#6B7280	t	2025-07-04 23:26:46	2025-07-04 23:26:46
\.


--
-- Data for Name: taggables; Type: TABLE DATA; Schema: public; Owner: ericslarson
--

COPY public.taggables (id, tag_id, taggable_type, taggable_id, created_at, updated_at) FROM stdin;
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
57	2	updated_profile	App\\Models\\User	2	\N	t	2025-07-09 05:24:45	2025-07-09 05:24:45
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
-- Name: categories_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.categories_id_seq', 29, true);


--
-- Name: claims_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.claims_id_seq', 1, false);


--
-- Name: cloudflare_images_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.cloudflare_images_id_seq', 60, true);


--
-- Name: comments_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.comments_id_seq', 1, false);


--
-- Name: directory_entries_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.directory_entries_id_seq', 102, true);


--
-- Name: directory_entry_follows_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.directory_entry_follows_id_seq', 1, false);


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.failed_jobs_id_seq', 1, false);


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
-- Name: list_categories_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.list_categories_id_seq', 10, true);


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

SELECT pg_catalog.setval('public.lists_id_seq', 17, true);


--
-- Name: locations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.locations_id_seq', 44, true);


--
-- Name: login_page_settings_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.login_page_settings_id_seq', 1, false);


--
-- Name: migrations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.migrations_id_seq', 94, true);


--
-- Name: pages_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.pages_id_seq', 6, true);


--
-- Name: pinned_lists_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.pinned_lists_id_seq', 1, false);


--
-- Name: place_regions_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.place_regions_id_seq', 66, true);


--
-- Name: posts_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.posts_id_seq', 1, true);


--
-- Name: region_featured_entries_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.region_featured_entries_id_seq', 1, true);


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

SELECT pg_catalog.setval('public.regions_id_seq', 35, true);


--
-- Name: settings_id_seq; Type: SEQUENCE SET; Schema: public; Owner: ericslarson
--

SELECT pg_catalog.setval('public.settings_id_seq', 35, true);


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

SELECT pg_catalog.setval('public.user_activities_id_seq', 57, true);


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
-- PostgreSQL database dump complete
--

