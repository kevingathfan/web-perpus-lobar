--
-- PostgreSQL database dump
--

\restrict Y57UVICoZmo4aShzlk740AqLf4u1UIlyVj1PlAflqa3qhqbHus3Q7Ox784X6jlR

-- Dumped from database version 18.1
-- Dumped by pg_dump version 18.1

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Name: monitoring_perpus_db; Type: DATABASE; Schema: -; Owner: postgres
--

CREATE DATABASE monitoring_perpus_db WITH TEMPLATE = template0 ENCODING = 'UTF8' LOCALE_PROVIDER = libc LOCALE = 'English_Indonesia.1252';


ALTER DATABASE monitoring_perpus_db OWNER TO postgres;

\unrestrict Y57UVICoZmo4aShzlk740AqLf4u1UIlyVj1PlAflqa3qhqbHus3Q7Ox784X6jlR
\connect monitoring_perpus_db
\restrict Y57UVICoZmo4aShzlk740AqLf4u1UIlyVj1PlAflqa3qhqbHus3Q7Ox784X6jlR

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
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
-- Name: libraries; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.libraries (
    id integer NOT NULL,
    nama character varying(200) NOT NULL,
    jenis character varying(100) NOT NULL,
    lokasi text,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT libraries_jenis_check CHECK (((jenis)::text = ANY ((ARRAY['Perpustakaan Sekolah'::character varying, 'Perpustakaan Desa'::character varying, 'Perpustakaan Komunitas'::character varying, 'Perpustakaan Rumah Ibadah'::character varying, 'Perpustakaan Pondok Pesantren'::character varying])::text[])))
);


ALTER TABLE public.libraries OWNER TO postgres;

--
-- Name: libraries_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.libraries_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.libraries_id_seq OWNER TO postgres;

--
-- Name: libraries_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.libraries_id_seq OWNED BY public.libraries.id;


--
-- Name: report_periods; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.report_periods (
    id integer NOT NULL,
    bulan integer NOT NULL,
    tahun integer NOT NULL,
    status character varying(10) NOT NULL,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT report_periods_bulan_check CHECK (((bulan >= 1) AND (bulan <= 12))),
    CONSTRAINT report_periods_status_check CHECK (((status)::text = ANY ((ARRAY['aktif'::character varying, 'ditutup'::character varying])::text[])))
);


ALTER TABLE public.report_periods OWNER TO postgres;

--
-- Name: report_periods_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.report_periods_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.report_periods_id_seq OWNER TO postgres;

--
-- Name: report_periods_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.report_periods_id_seq OWNED BY public.report_periods.id;


--
-- Name: report_verifications; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.report_verifications (
    id integer NOT NULL,
    report_id integer NOT NULL,
    catatan text,
    verified_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.report_verifications OWNER TO postgres;

--
-- Name: report_verifications_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.report_verifications_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.report_verifications_id_seq OWNER TO postgres;

--
-- Name: report_verifications_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.report_verifications_id_seq OWNED BY public.report_verifications.id;


--
-- Name: reports; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.reports (
    id integer NOT NULL,
    library_id integer NOT NULL,
    period_id integer NOT NULL,
    jenis character varying(10) NOT NULL,
    status character varying(20) NOT NULL,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT reports_jenis_check CHECK (((jenis)::text = ANY ((ARRAY['IPLM'::character varying, 'TKM'::character varying])::text[]))),
    CONSTRAINT reports_status_check CHECK (((status)::text = ANY ((ARRAY['dikirim'::character varying, 'direvisi'::character varying, 'disetujui'::character varying])::text[])))
);


ALTER TABLE public.reports OWNER TO postgres;

--
-- Name: reports_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.reports_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.reports_id_seq OWNER TO postgres;

--
-- Name: reports_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.reports_id_seq OWNED BY public.reports.id;


--
-- Name: reports_iplm; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.reports_iplm (
    id integer NOT NULL,
    report_id integer NOT NULL,
    jumlah_buku integer,
    jumlah_pengunjung integer,
    jumlah_kegiatan_literasi integer,
    jumlah_tenaga_perpustakaan integer,
    skor_total integer
);


ALTER TABLE public.reports_iplm OWNER TO postgres;

--
-- Name: reports_iplm_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.reports_iplm_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.reports_iplm_id_seq OWNER TO postgres;

--
-- Name: reports_iplm_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.reports_iplm_id_seq OWNED BY public.reports_iplm.id;


--
-- Name: reports_tkm; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.reports_tkm (
    id integer NOT NULL,
    report_id integer NOT NULL,
    jumlah_pembaca integer,
    jumlah_buku_dibaca integer,
    rata_waktu_membaca integer,
    skor_total integer
);


ALTER TABLE public.reports_tkm OWNER TO postgres;

--
-- Name: reports_tkm_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.reports_tkm_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.reports_tkm_id_seq OWNER TO postgres;

--
-- Name: reports_tkm_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.reports_tkm_id_seq OWNED BY public.reports_tkm.id;


--
-- Name: users; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.users (
    id integer NOT NULL,
    nama character varying(100) NOT NULL,
    email character varying(100) NOT NULL,
    password text NOT NULL,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.users OWNER TO postgres;

--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.users_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.users_id_seq OWNER TO postgres;

--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- Name: libraries id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.libraries ALTER COLUMN id SET DEFAULT nextval('public.libraries_id_seq'::regclass);


--
-- Name: report_periods id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.report_periods ALTER COLUMN id SET DEFAULT nextval('public.report_periods_id_seq'::regclass);


--
-- Name: report_verifications id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.report_verifications ALTER COLUMN id SET DEFAULT nextval('public.report_verifications_id_seq'::regclass);


--
-- Name: reports id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.reports ALTER COLUMN id SET DEFAULT nextval('public.reports_id_seq'::regclass);


--
-- Name: reports_iplm id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.reports_iplm ALTER COLUMN id SET DEFAULT nextval('public.reports_iplm_id_seq'::regclass);


--
-- Name: reports_tkm id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.reports_tkm ALTER COLUMN id SET DEFAULT nextval('public.reports_tkm_id_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- Data for Name: libraries; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.libraries (id, nama, jenis, lokasi, created_at) FROM stdin;
1	PERPUSTAKAAN BABUSSALAM	Perpustakaan Desa	BABUSSALAM	2026-01-29 10:55:22.870683
2	PERPUSTAKAAN DESA BANYU URIP	Perpustakaan Desa	BANYU URIP	2026-01-29 10:55:22.877218
3	PERPUSTAKAAN DASAN TAPEN	Perpustakaan Desa	DASAN TAPEN	2026-01-29 10:55:22.878098
4	PERPUSTAKAAN GAPUK	Perpustakaan Desa	GAPUK	2026-01-29 10:55:22.879243
5	PERPUSTAKAAN GERUNG UTARA	Perpustakaan Desa	GERUNG UTARA	2026-01-29 10:55:22.87973
6	PERPUSTAKAAN KEBON AYU	Perpustakaan Desa	KEBON AYU	2026-01-29 10:55:22.880243
7	PERPUSTAKAAN MESANGGOK	Perpustakaan Desa	MESANGGOK	2026-01-29 10:55:22.880857
8	PERPUSTAKAAN TEMPOS	Perpustakaan Desa	TEMPOS	2026-01-29 10:55:22.881174
9	PERPUSTAKAAN JATI SELA	Perpustakaan Desa	JATISELA	2026-01-29 10:55:22.881436
10	PERPUSTAKAAN KEKERI	Perpustakaan Desa	KEKERI	2026-01-29 10:55:22.881804
11	PERPUSTAKAAN DASAN BARU	Perpustakaan Desa	DASAN BARU	2026-01-29 10:55:22.88223
12	PERPUSTAKAAN GELOGOR	Perpustakaan Desa	GELOGOR	2026-01-29 10:55:22.882622
13	PERPUSTAKAAN DESA JAGARAGA INDAH	Perpustakaan Desa	JAGARAGA INDAH	2026-01-29 10:55:22.882986
14	PERPUSTAKAAN KEDIRI	Perpustakaan Desa	KEDIRI	2026-01-29 10:55:22.88333
15	PERPUSTAKAAN KEDIRI SELATAN	Perpustakaan Desa	KEDIRI SELATAN	2026-01-29 10:55:22.884265
16	PERPUSTAKAAN LELEDE	Perpustakaan Desa	LELEDE	2026-01-29 10:55:22.884932
17	PERPUSTAKAAN OMBE BARU	Perpustakaan Desa	OMBE BARU	2026-01-29 10:55:22.885405
18	PERPUSTAKAAN RUMAK	Perpustakaan Desa	RUMAK	2026-01-29 10:55:22.885777
19	PERPUSTAKAAN GIRI SASAK	Perpustakaan Desa	GIRI SASAK	2026-01-29 10:55:22.886133
20	PERPUSTAKAAN JAGARAGA	Perpustakaan Desa	JAGARAGA	2026-01-29 10:55:22.886586
21	PERPUSTAKAAN DESA KURIPAN	Perpustakaan Desa	KURIPAN	2026-01-29 10:55:22.886932
22	PERPUSTAKAAN KURIPAN UTARA	Perpustakaan Desa	KURIPAN UTARA	2026-01-29 10:55:22.887222
23	PERPUSTAKAAN BAGIK POLAK	Perpustakaan Desa	BAGIK POLAK	2026-01-29 10:55:22.887494
24	PERPUSTAKAAN BAJUR	Perpustakaan Desa	BAJUR	2026-01-29 10:55:22.887823
25	PERPUSTAKAAN BENGKEL	Perpustakaan Desa	BENGKEL	2026-01-29 10:55:22.888205
26	PERPUSTAKAAN  KURANJI	Perpustakaan Desa	KURANJI	2026-01-29 10:55:22.888646
27	PERPUSTAKAAN KURANJI DALANG	Perpustakaan Desa	KURANJI DALANG	2026-01-29 10:55:22.888945
28	PERPUSTAKAAN LABUAPI	Perpustakaan Desa	LABUAPI	2026-01-29 10:55:22.889224
29	PERPUSTAKAAN TELAGAWARU	Perpustakaan Desa	TELAGAWARU	2026-01-29 10:55:22.889641
30	PERPUSTAKAAN LABUAN TERENG	Perpustakaan Desa	LABUAN TERENG	2026-01-29 10:55:22.890009
31	PERPUSTAKAAN  LEMBAR SELATAN	Perpustakaan Desa	LEMBAR SELATAN	2026-01-29 10:55:22.890283
32	PERPUSTAKAAN JEMBATAN GANTUNG	Perpustakaan Desa	JEMBATAN GANTUNG	2026-01-29 10:55:22.89054
33	PERPUSTAKAAN JEMBATAN KEMBAR TIMUR	Perpustakaan Desa	JEMBATAN KEMBAR TIMUR	2026-01-29 10:55:22.890948
34	PERPUSTAKAAN BATU KUMBUNG	Perpustakaan Desa	BATU KUMBUNG	2026-01-29 10:55:22.891214
35	PERPUSTAKAAN BATU MEKAR	Perpustakaan Desa	BATU MEKAR	2026-01-29 10:55:22.891477
36	PERPUSTAKAAN BUGBUG	Perpustakaan Desa	BUG-BUG	2026-01-29 10:55:22.891979
37	PERPUSTAKAAN DASAN GERIA	Perpustakaan Desa	DASAN GERIA	2026-01-29 10:55:22.892476
38	PERPUSTAKAAN LANGKO	Perpustakaan Desa	LANGKO	2026-01-29 10:55:22.893169
39	PERPUSTAKAAN SARIBAYE	Perpustakaan Desa	SARIBAYE	2026-01-29 10:55:22.893665
40	PERPUSTAKAAN SIGERONGAN	Perpustakaan Desa	SIGERONGAN	2026-01-29 10:55:22.894056
41	PERPUSTAKAAN KARANG BAYAN	Perpustakaan Desa	KARANG BAYAN	2026-01-29 10:55:22.894502
42	PERPUSTAKAAN BADRAIN	Perpustakaan Desa	BADRAIN	2026-01-29 10:55:22.895086
43	PERPUSTAKAAN BATU KUTA	Perpustakaan Desa	BATU KUTA	2026-01-29 10:55:22.895691
44	PERPUSTAKAAN DASAN TERENG	Perpustakaan Desa	DASAN TERENG	2026-01-29 10:55:22.896138
45	PERPUSTAKAAN GOLONG	Perpustakaan Desa	GOLONG	2026-01-29 10:55:22.896561
46	PERPUSTAKAAN GERIMAX INDAH	Perpustakaan Desa	GERIMAX INDAH	2026-01-29 10:55:22.897342
47	PERPUSTAKAAN KERU	Perpustakaan Desa	KERU	2026-01-29 10:55:22.897628
48	PERPUSTAKAAN KRAMA JAYA	Perpustakaan Desa	KRAMA JAYA	2026-01-29 10:55:22.897881
49	PERPUSTAKAAN LEBAH SEMPAGE	Perpustakaan Desa	LEBAH SEMPAGE	2026-01-29 10:55:22.898142
50	PERPUSTAKAAN MEKARSARI	Perpustakaan Desa	MEKARSARI	2026-01-29 10:55:22.898399
51	PERPUSTAKAAN NYIUR LEMBANG	Perpustakaan Desa	NYIUR LEMBANG	2026-01-29 10:55:22.898647
52	PERPUSTAKAAN SELAT	Perpustakaan Desa	SELAT	2026-01-29 10:55:22.898909
53	PERPUSTAKAAN SESAOT	Perpustakaan Desa	SESAOT	2026-01-29 10:55:22.89923
54	PERPUSTAKAAN NARMADA	Perpustakaan Desa	NARMADA	2026-01-29 10:55:22.899492
55	PERPUSTAKAAN DESA PERESAK	Perpustakaan Desa	PERESAK	2026-01-29 10:55:22.899737
56	PERPUSTAKAAN DESA SEDAU	Perpustakaan Desa	SEDAU	2026-01-29 10:55:22.899965
57	PERPUSTAKAAN DESA SEMBUNG	Perpustakaan Desa	SEMBUNG	2026-01-29 10:55:22.900508
58	PERPUSTAKAAN DESA SURANADI	Perpustakaan Desa	SURANADI	2026-01-29 10:55:22.901146
59	PERPUSTAKAAN BATU PUTIH	Perpustakaan Desa	BATU PUTIH	2026-01-29 10:55:22.901502
60	PERPUSTAKAAN BUWUN MAS	Perpustakaan Desa	BUWUN MAS	2026-01-29 10:55:22.902095
61	PERPUSTAKAAN SEKOTONG TENGAH	Perpustakaan Desa	SEKOTONG TENGAH	2026-01-29 10:55:22.902505
62	PERPUSTAKAAN PENIMBUNG	Perpustakaan Desa	PENIMBUNGAN	2026-01-29 10:55:22.902953
63	PERPUSTAKAAN RANJOK	Perpustakaan Desa	RANJOK	2026-01-29 10:55:22.903359
\.


--
-- Data for Name: report_periods; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.report_periods (id, bulan, tahun, status, created_at) FROM stdin;
\.


--
-- Data for Name: report_verifications; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.report_verifications (id, report_id, catatan, verified_at) FROM stdin;
\.


--
-- Data for Name: reports; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.reports (id, library_id, period_id, jenis, status, created_at) FROM stdin;
\.


--
-- Data for Name: reports_iplm; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.reports_iplm (id, report_id, jumlah_buku, jumlah_pengunjung, jumlah_kegiatan_literasi, jumlah_tenaga_perpustakaan, skor_total) FROM stdin;
\.


--
-- Data for Name: reports_tkm; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.reports_tkm (id, report_id, jumlah_pembaca, jumlah_buku_dibaca, rata_waktu_membaca, skor_total) FROM stdin;
\.


--
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.users (id, nama, email, password, created_at) FROM stdin;
\.


--
-- Name: libraries_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.libraries_id_seq', 63, true);


--
-- Name: report_periods_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.report_periods_id_seq', 1, false);


--
-- Name: report_verifications_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.report_verifications_id_seq', 1, false);


--
-- Name: reports_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.reports_id_seq', 1, false);


--
-- Name: reports_iplm_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.reports_iplm_id_seq', 1, false);


--
-- Name: reports_tkm_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.reports_tkm_id_seq', 1, false);


--
-- Name: users_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.users_id_seq', 1, false);


--
-- Name: libraries libraries_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.libraries
    ADD CONSTRAINT libraries_pkey PRIMARY KEY (id);


--
-- Name: report_periods report_periods_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.report_periods
    ADD CONSTRAINT report_periods_pkey PRIMARY KEY (id);


--
-- Name: report_verifications report_verifications_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.report_verifications
    ADD CONSTRAINT report_verifications_pkey PRIMARY KEY (id);


--
-- Name: reports_iplm reports_iplm_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.reports_iplm
    ADD CONSTRAINT reports_iplm_pkey PRIMARY KEY (id);


--
-- Name: reports reports_library_id_period_id_jenis_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.reports
    ADD CONSTRAINT reports_library_id_period_id_jenis_key UNIQUE (library_id, period_id, jenis);


--
-- Name: reports reports_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.reports
    ADD CONSTRAINT reports_pkey PRIMARY KEY (id);


--
-- Name: reports_tkm reports_tkm_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.reports_tkm
    ADD CONSTRAINT reports_tkm_pkey PRIMARY KEY (id);


--
-- Name: users users_email_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_key UNIQUE (email);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: report_verifications report_verifications_report_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.report_verifications
    ADD CONSTRAINT report_verifications_report_id_fkey FOREIGN KEY (report_id) REFERENCES public.reports(id) ON DELETE CASCADE;


--
-- Name: reports_iplm reports_iplm_report_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.reports_iplm
    ADD CONSTRAINT reports_iplm_report_id_fkey FOREIGN KEY (report_id) REFERENCES public.reports(id) ON DELETE CASCADE;


--
-- Name: reports reports_period_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.reports
    ADD CONSTRAINT reports_period_id_fkey FOREIGN KEY (period_id) REFERENCES public.report_periods(id) ON DELETE CASCADE;


--
-- Name: reports_tkm reports_tkm_report_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.reports_tkm
    ADD CONSTRAINT reports_tkm_report_id_fkey FOREIGN KEY (report_id) REFERENCES public.reports(id) ON DELETE CASCADE;


--
-- PostgreSQL database dump complete
--

\unrestrict Y57UVICoZmo4aShzlk740AqLf4u1UIlyVj1PlAflqa3qhqbHus3Q7Ox784X6jlR

