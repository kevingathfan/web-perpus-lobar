--
-- PostgreSQL database dump
--

\restrict wHSQxwQXYTz56dHmYGjk1K9rbwocMlccVlolChnjGvzlWNTXrReriXtOfzJ1ogW

-- Dumped from database version 18.1
-- Dumped by pg_dump version 18.1

-- Started on 2026-02-04 13:31:57

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

DROP DATABASE monitoring_perpus_db;
--
-- TOC entry 5192 (class 1262 OID 16822)
-- Name: monitoring_perpus_db; Type: DATABASE; Schema: -; Owner: postgres
--

CREATE DATABASE monitoring_perpus_db WITH TEMPLATE = template0 ENCODING = 'UTF8' LOCALE_PROVIDER = libc LOCALE = 'English_Indonesia.1252';


ALTER DATABASE monitoring_perpus_db OWNER TO postgres;

\unrestrict wHSQxwQXYTz56dHmYGjk1K9rbwocMlccVlolChnjGvzlWNTXrReriXtOfzJ1ogW
\connect monitoring_perpus_db
\restrict wHSQxwQXYTz56dHmYGjk1K9rbwocMlccVlolChnjGvzlWNTXrReriXtOfzJ1ogW

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
-- TOC entry 232 (class 1259 OID 24578)
-- Name: libraries; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.libraries (
    id integer NOT NULL,
    nama character varying(200) NOT NULL,
    jenis character varying(100) NOT NULL,
    lokasi text,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    kategori character varying(50)
);


ALTER TABLE public.libraries OWNER TO postgres;

--
-- TOC entry 231 (class 1259 OID 24577)
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
-- TOC entry 5193 (class 0 OID 0)
-- Dependencies: 231
-- Name: libraries_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.libraries_id_seq OWNED BY public.libraries.id;


--
-- TOC entry 240 (class 1259 OID 24657)
-- Name: master_kategori; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.master_kategori (
    id integer NOT NULL,
    kategori character varying(50) NOT NULL,
    sub_kategori character varying(100) NOT NULL
);


ALTER TABLE public.master_kategori OWNER TO postgres;

--
-- TOC entry 239 (class 1259 OID 24656)
-- Name: master_kategori_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.master_kategori_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.master_kategori_id_seq OWNER TO postgres;

--
-- TOC entry 5194 (class 0 OID 0)
-- Dependencies: 239
-- Name: master_kategori_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.master_kategori_id_seq OWNED BY public.master_kategori.id;


--
-- TOC entry 234 (class 1259 OID 24597)
-- Name: master_pertanyaan; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.master_pertanyaan (
    id integer NOT NULL,
    jenis_kuesioner character varying(10) NOT NULL,
    kategori_bagian character varying(100),
    teks_pertanyaan text NOT NULL,
    tipe_input character varying(20) NOT NULL,
    opsi_jawaban text,
    urutan integer DEFAULT 0,
    is_active integer DEFAULT 1,
    keterangan text,
    pilihan_opsi text
);


ALTER TABLE public.master_pertanyaan OWNER TO postgres;

--
-- TOC entry 233 (class 1259 OID 24596)
-- Name: master_pertanyaan_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.master_pertanyaan_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.master_pertanyaan_id_seq OWNER TO postgres;

--
-- TOC entry 5195 (class 0 OID 0)
-- Dependencies: 233
-- Name: master_pertanyaan_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.master_pertanyaan_id_seq OWNED BY public.master_pertanyaan.id;


--
-- TOC entry 245 (class 1259 OID 24698)
-- Name: password_reset_email_logs; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.password_reset_email_logs (
    id integer NOT NULL,
    email character varying(150) NOT NULL,
    status character varying(20) NOT NULL,
    error_message text,
    created_at timestamp without time zone DEFAULT now() NOT NULL,
    token_hash character varying(255),
    expires_at timestamp without time zone
);


ALTER TABLE public.password_reset_email_logs OWNER TO postgres;

--
-- TOC entry 244 (class 1259 OID 24697)
-- Name: password_reset_email_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.password_reset_email_logs_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.password_reset_email_logs_id_seq OWNER TO postgres;

--
-- TOC entry 5196 (class 0 OID 0)
-- Dependencies: 244
-- Name: password_reset_email_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.password_reset_email_logs_id_seq OWNED BY public.password_reset_email_logs.id;


--
-- TOC entry 249 (class 1259 OID 24725)
-- Name: password_reset_logs; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.password_reset_logs (
    id integer NOT NULL,
    user_id integer NOT NULL,
    ip_address character varying(64),
    created_at timestamp without time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.password_reset_logs OWNER TO postgres;

--
-- TOC entry 248 (class 1259 OID 24724)
-- Name: password_reset_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.password_reset_logs_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.password_reset_logs_id_seq OWNER TO postgres;

--
-- TOC entry 5197 (class 0 OID 0)
-- Dependencies: 248
-- Name: password_reset_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.password_reset_logs_id_seq OWNED BY public.password_reset_logs.id;


--
-- TOC entry 247 (class 1259 OID 24712)
-- Name: password_resets; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.password_resets (
    id integer NOT NULL,
    user_id integer NOT NULL,
    token_hash character varying(255) NOT NULL,
    expires_at timestamp without time zone NOT NULL,
    used_at timestamp without time zone,
    created_at timestamp without time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.password_resets OWNER TO postgres;

--
-- TOC entry 246 (class 1259 OID 24711)
-- Name: password_resets_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.password_resets_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.password_resets_id_seq OWNER TO postgres;

--
-- TOC entry 5198 (class 0 OID 0)
-- Dependencies: 246
-- Name: password_resets_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.password_resets_id_seq OWNED BY public.password_resets.id;


--
-- TOC entry 243 (class 1259 OID 24674)
-- Name: pengaduan; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.pengaduan (
    id integer NOT NULL,
    nama character varying(100),
    kontak character varying(100),
    pesan text,
    tanggal date DEFAULT CURRENT_DATE,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    is_important boolean DEFAULT false,
    is_done boolean DEFAULT false
);


ALTER TABLE public.pengaduan OWNER TO postgres;

--
-- TOC entry 242 (class 1259 OID 24673)
-- Name: pengaduan_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.pengaduan_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.pengaduan_id_seq OWNER TO postgres;

--
-- TOC entry 5199 (class 0 OID 0)
-- Dependencies: 242
-- Name: pengaduan_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.pengaduan_id_seq OWNED BY public.pengaduan.id;


--
-- TOC entry 222 (class 1259 OID 16853)
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
-- TOC entry 221 (class 1259 OID 16852)
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
-- TOC entry 5200 (class 0 OID 0)
-- Dependencies: 221
-- Name: report_periods_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.report_periods_id_seq OWNED BY public.report_periods.id;


--
-- TOC entry 230 (class 1259 OID 16922)
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
-- TOC entry 229 (class 1259 OID 16921)
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
-- TOC entry 5201 (class 0 OID 0)
-- Dependencies: 229
-- Name: report_verifications_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.report_verifications_id_seq OWNED BY public.report_verifications.id;


--
-- TOC entry 224 (class 1259 OID 16867)
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
-- TOC entry 223 (class 1259 OID 16866)
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
-- TOC entry 5202 (class 0 OID 0)
-- Dependencies: 223
-- Name: reports_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.reports_id_seq OWNED BY public.reports.id;


--
-- TOC entry 226 (class 1259 OID 16894)
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
-- TOC entry 225 (class 1259 OID 16893)
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
-- TOC entry 5203 (class 0 OID 0)
-- Dependencies: 225
-- Name: reports_iplm_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.reports_iplm_id_seq OWNED BY public.reports_iplm.id;


--
-- TOC entry 228 (class 1259 OID 16908)
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
-- TOC entry 227 (class 1259 OID 16907)
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
-- TOC entry 5204 (class 0 OID 0)
-- Dependencies: 227
-- Name: reports_tkm_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.reports_tkm_id_seq OWNED BY public.reports_tkm.id;


--
-- TOC entry 241 (class 1259 OID 24666)
-- Name: settings; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.settings (
    setting_key character varying(50) NOT NULL,
    setting_value character varying(100) DEFAULT NULL::character varying
);


ALTER TABLE public.settings OWNER TO postgres;

--
-- TOC entry 238 (class 1259 OID 24623)
-- Name: trans_detail; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.trans_detail (
    id integer NOT NULL,
    header_id integer NOT NULL,
    pertanyaan_id integer NOT NULL,
    jawaban text
);


ALTER TABLE public.trans_detail OWNER TO postgres;

--
-- TOC entry 237 (class 1259 OID 24622)
-- Name: trans_detail_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.trans_detail_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.trans_detail_id_seq OWNER TO postgres;

--
-- TOC entry 5205 (class 0 OID 0)
-- Dependencies: 237
-- Name: trans_detail_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.trans_detail_id_seq OWNED BY public.trans_detail.id;


--
-- TOC entry 236 (class 1259 OID 24612)
-- Name: trans_header; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.trans_header (
    id integer NOT NULL,
    library_id integer,
    jenis_kuesioner character varying(10) NOT NULL,
    periode_bulan character varying(2),
    periode_tahun character varying(4),
    tanggal_isi timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.trans_header OWNER TO postgres;

--
-- TOC entry 235 (class 1259 OID 24611)
-- Name: trans_header_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.trans_header_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.trans_header_id_seq OWNER TO postgres;

--
-- TOC entry 5206 (class 0 OID 0)
-- Dependencies: 235
-- Name: trans_header_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.trans_header_id_seq OWNED BY public.trans_header.id;


--
-- TOC entry 220 (class 1259 OID 16824)
-- Name: users; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.users (
    id integer NOT NULL,
    nama character varying(100) NOT NULL,
    email character varying(100) NOT NULL,
    password text NOT NULL,
    created_at timestamp without time zone DEFAULT (now())::timestamp without time zone
);


ALTER TABLE public.users OWNER TO postgres;

--
-- TOC entry 219 (class 1259 OID 16823)
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
-- TOC entry 5207 (class 0 OID 0)
-- Dependencies: 219
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- TOC entry 4940 (class 2604 OID 24581)
-- Name: libraries id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.libraries ALTER COLUMN id SET DEFAULT nextval('public.libraries_id_seq'::regclass);


--
-- TOC entry 4948 (class 2604 OID 24660)
-- Name: master_kategori id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.master_kategori ALTER COLUMN id SET DEFAULT nextval('public.master_kategori_id_seq'::regclass);


--
-- TOC entry 4942 (class 2604 OID 24600)
-- Name: master_pertanyaan id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.master_pertanyaan ALTER COLUMN id SET DEFAULT nextval('public.master_pertanyaan_id_seq'::regclass);


--
-- TOC entry 4955 (class 2604 OID 24701)
-- Name: password_reset_email_logs id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.password_reset_email_logs ALTER COLUMN id SET DEFAULT nextval('public.password_reset_email_logs_id_seq'::regclass);


--
-- TOC entry 4959 (class 2604 OID 24728)
-- Name: password_reset_logs id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.password_reset_logs ALTER COLUMN id SET DEFAULT nextval('public.password_reset_logs_id_seq'::regclass);


--
-- TOC entry 4957 (class 2604 OID 24715)
-- Name: password_resets id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.password_resets ALTER COLUMN id SET DEFAULT nextval('public.password_resets_id_seq'::regclass);


--
-- TOC entry 4950 (class 2604 OID 24677)
-- Name: pengaduan id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pengaduan ALTER COLUMN id SET DEFAULT nextval('public.pengaduan_id_seq'::regclass);


--
-- TOC entry 4932 (class 2604 OID 16856)
-- Name: report_periods id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.report_periods ALTER COLUMN id SET DEFAULT nextval('public.report_periods_id_seq'::regclass);


--
-- TOC entry 4938 (class 2604 OID 16925)
-- Name: report_verifications id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.report_verifications ALTER COLUMN id SET DEFAULT nextval('public.report_verifications_id_seq'::regclass);


--
-- TOC entry 4934 (class 2604 OID 16870)
-- Name: reports id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.reports ALTER COLUMN id SET DEFAULT nextval('public.reports_id_seq'::regclass);


--
-- TOC entry 4936 (class 2604 OID 16897)
-- Name: reports_iplm id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.reports_iplm ALTER COLUMN id SET DEFAULT nextval('public.reports_iplm_id_seq'::regclass);


--
-- TOC entry 4937 (class 2604 OID 16911)
-- Name: reports_tkm id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.reports_tkm ALTER COLUMN id SET DEFAULT nextval('public.reports_tkm_id_seq'::regclass);


--
-- TOC entry 4947 (class 2604 OID 24626)
-- Name: trans_detail id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.trans_detail ALTER COLUMN id SET DEFAULT nextval('public.trans_detail_id_seq'::regclass);


--
-- TOC entry 4945 (class 2604 OID 24615)
-- Name: trans_header id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.trans_header ALTER COLUMN id SET DEFAULT nextval('public.trans_header_id_seq'::regclass);


--
-- TOC entry 4930 (class 2604 OID 16827)
-- Name: users id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- TOC entry 5169 (class 0 OID 24578)
-- Dependencies: 232
-- Data for Name: libraries; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.libraries (id, nama, jenis, lokasi, created_at, kategori) FROM stdin;
1	PERPUSTAKAAN BABUSSALAM	Perpustakaan Desa	BABUSSALAM	2026-01-29 10:55:22.870683	Umum
2	PERPUSTAKAAN DESA BANYU URIP	Perpustakaan Desa	BANYU URIP	2026-01-29 10:55:22.877218	Umum
3	PERPUSTAKAAN DASAN TAPEN	Perpustakaan Desa	DASAN TAPEN	2026-01-29 10:55:22.878098	Umum
4	PERPUSTAKAAN GAPUK	Perpustakaan Desa	GAPUK	2026-01-29 10:55:22.879243	Umum
5	PERPUSTAKAAN GERUNG UTARA	Perpustakaan Desa	GERUNG UTARA	2026-01-29 10:55:22.87973	Umum
6	PERPUSTAKAAN KEBON AYU	Perpustakaan Desa	KEBON AYU	2026-01-29 10:55:22.880243	Umum
7	PERPUSTAKAAN MESANGGOK	Perpustakaan Desa	MESANGGOK	2026-01-29 10:55:22.880857	Umum
8	PERPUSTAKAAN TEMPOS	Perpustakaan Desa	TEMPOS	2026-01-29 10:55:22.881174	Umum
9	PERPUSTAKAAN JATI SELA	Perpustakaan Desa	JATISELA	2026-01-29 10:55:22.881436	Umum
10	PERPUSTAKAAN KEKERI	Perpustakaan Desa	KEKERI	2026-01-29 10:55:22.881804	Umum
11	PERPUSTAKAAN DASAN BARU	Perpustakaan Desa	DASAN BARU	2026-01-29 10:55:22.88223	Umum
12	PERPUSTAKAAN GELOGOR	Perpustakaan Desa	GELOGOR	2026-01-29 10:55:22.882622	Umum
13	PERPUSTAKAAN DESA JAGARAGA INDAH	Perpustakaan Desa	JAGARAGA INDAH	2026-01-29 10:55:22.882986	Umum
14	PERPUSTAKAAN KEDIRI	Perpustakaan Desa	KEDIRI	2026-01-29 10:55:22.88333	Umum
15	PERPUSTAKAAN KEDIRI SELATAN	Perpustakaan Desa	KEDIRI SELATAN	2026-01-29 10:55:22.884265	Umum
16	PERPUSTAKAAN LELEDE	Perpustakaan Desa	LELEDE	2026-01-29 10:55:22.884932	Umum
17	PERPUSTAKAAN OMBE BARU	Perpustakaan Desa	OMBE BARU	2026-01-29 10:55:22.885405	Umum
18	PERPUSTAKAAN RUMAK	Perpustakaan Desa	RUMAK	2026-01-29 10:55:22.885777	Umum
19	PERPUSTAKAAN GIRI SASAK	Perpustakaan Desa	GIRI SASAK	2026-01-29 10:55:22.886133	Umum
20	PERPUSTAKAAN JAGARAGA	Perpustakaan Desa	JAGARAGA	2026-01-29 10:55:22.886586	Umum
21	PERPUSTAKAAN DESA KURIPAN	Perpustakaan Desa	KURIPAN	2026-01-29 10:55:22.886932	Umum
22	PERPUSTAKAAN KURIPAN UTARA	Perpustakaan Desa	KURIPAN UTARA	2026-01-29 10:55:22.887222	Umum
23	PERPUSTAKAAN BAGIK POLAK	Perpustakaan Desa	BAGIK POLAK	2026-01-29 10:55:22.887494	Umum
24	PERPUSTAKAAN BAJUR	Perpustakaan Desa	BAJUR	2026-01-29 10:55:22.887823	Umum
25	PERPUSTAKAAN BENGKEL	Perpustakaan Desa	BENGKEL	2026-01-29 10:55:22.888205	Umum
26	PERPUSTAKAAN  KURANJI	Perpustakaan Desa	KURANJI	2026-01-29 10:55:22.888646	Umum
27	PERPUSTAKAAN KURANJI DALANG	Perpustakaan Desa	KURANJI DALANG	2026-01-29 10:55:22.888945	Umum
28	PERPUSTAKAAN LABUAPI	Perpustakaan Desa	LABUAPI	2026-01-29 10:55:22.889224	Umum
29	PERPUSTAKAAN TELAGAWARU	Perpustakaan Desa	TELAGAWARU	2026-01-29 10:55:22.889641	Umum
30	PERPUSTAKAAN LABUAN TERENG	Perpustakaan Desa	LABUAN TERENG	2026-01-29 10:55:22.890009	Umum
31	PERPUSTAKAAN  LEMBAR SELATAN	Perpustakaan Desa	LEMBAR SELATAN	2026-01-29 10:55:22.890283	Umum
32	PERPUSTAKAAN JEMBATAN GANTUNG	Perpustakaan Desa	JEMBATAN GANTUNG	2026-01-29 10:55:22.89054	Umum
33	PERPUSTAKAAN JEMBATAN KEMBAR TIMUR	Perpustakaan Desa	JEMBATAN KEMBAR TIMUR	2026-01-29 10:55:22.890948	Umum
34	PERPUSTAKAAN BATU KUMBUNG	Perpustakaan Desa	BATU KUMBUNG	2026-01-29 10:55:22.891214	Umum
35	PERPUSTAKAAN BATU MEKAR	Perpustakaan Desa	BATU MEKAR	2026-01-29 10:55:22.891477	Umum
36	PERPUSTAKAAN BUGBUG	Perpustakaan Desa	BUG-BUG	2026-01-29 10:55:22.891979	Umum
37	PERPUSTAKAAN DASAN GERIA	Perpustakaan Desa	DASAN GERIA	2026-01-29 10:55:22.892476	Umum
38	PERPUSTAKAAN LANGKO	Perpustakaan Desa	LANGKO	2026-01-29 10:55:22.893169	Umum
39	PERPUSTAKAAN SARIBAYE	Perpustakaan Desa	SARIBAYE	2026-01-29 10:55:22.893665	Umum
40	PERPUSTAKAAN SIGERONGAN	Perpustakaan Desa	SIGERONGAN	2026-01-29 10:55:22.894056	Umum
41	PERPUSTAKAAN KARANG BAYAN	Perpustakaan Desa	KARANG BAYAN	2026-01-29 10:55:22.894502	Umum
42	PERPUSTAKAAN BADRAIN	Perpustakaan Desa	BADRAIN	2026-01-29 10:55:22.895086	Umum
43	PERPUSTAKAAN BATU KUTA	Perpustakaan Desa	BATU KUTA	2026-01-29 10:55:22.895691	Umum
44	PERPUSTAKAAN DASAN TERENG	Perpustakaan Desa	DASAN TERENG	2026-01-29 10:55:22.896138	Umum
45	PERPUSTAKAAN GOLONG	Perpustakaan Desa	GOLONG	2026-01-29 10:55:22.896561	Umum
46	PERPUSTAKAAN GERIMAX INDAH	Perpustakaan Desa	GERIMAX INDAH	2026-01-29 10:55:22.897342	Umum
47	PERPUSTAKAAN KERU	Perpustakaan Desa	KERU	2026-01-29 10:55:22.897628	Umum
48	PERPUSTAKAAN KRAMA JAYA	Perpustakaan Desa	KRAMA JAYA	2026-01-29 10:55:22.897881	Umum
49	PERPUSTAKAAN LEBAH SEMPAGE	Perpustakaan Desa	LEBAH SEMPAGE	2026-01-29 10:55:22.898142	Umum
50	PERPUSTAKAAN MEKARSARI	Perpustakaan Desa	MEKARSARI	2026-01-29 10:55:22.898399	Umum
51	PERPUSTAKAAN NYIUR LEMBANG	Perpustakaan Desa	NYIUR LEMBANG	2026-01-29 10:55:22.898647	Umum
52	PERPUSTAKAAN SELAT	Perpustakaan Desa	SELAT	2026-01-29 10:55:22.898909	Umum
53	PERPUSTAKAAN SESAOT	Perpustakaan Desa	SESAOT	2026-01-29 10:55:22.89923	Umum
54	PERPUSTAKAAN NARMADA	Perpustakaan Desa	NARMADA	2026-01-29 10:55:22.899492	Umum
55	PERPUSTAKAAN DESA PERESAK	Perpustakaan Desa	PERESAK	2026-01-29 10:55:22.899737	Umum
56	PERPUSTAKAAN DESA SEDAU	Perpustakaan Desa	SEDAU	2026-01-29 10:55:22.899965	Umum
57	PERPUSTAKAAN DESA SEMBUNG	Perpustakaan Desa	SEMBUNG	2026-01-29 10:55:22.900508	Umum
58	PERPUSTAKAAN DESA SURANADI	Perpustakaan Desa	SURANADI	2026-01-29 10:55:22.901146	Umum
59	PERPUSTAKAAN BATU PUTIH	Perpustakaan Desa	BATU PUTIH	2026-01-29 10:55:22.901502	Umum
60	PERPUSTAKAAN BUWUN MAS	Perpustakaan Desa	BUWUN MAS	2026-01-29 10:55:22.902095	Umum
61	PERPUSTAKAAN SEKOTONG TENGAH	Perpustakaan Desa	SEKOTONG TENGAH	2026-01-29 10:55:22.902505	Umum
62	PERPUSTAKAAN PENIMBUNG	Perpustakaan Desa	PENIMBUNGAN	2026-01-29 10:55:22.902953	Umum
63	PERPUSTAKAAN RANJOK	Perpustakaan Desa	RANJOK	2026-01-29 10:55:22.903359	Umum
\.


--
-- TOC entry 5177 (class 0 OID 24657)
-- Dependencies: 240
-- Data for Name: master_kategori; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.master_kategori (id, kategori, sub_kategori) FROM stdin;
1	Umum	Perpustakaan Desa
2	Umum	Perpustakaan Komunitas
3	Umum	Taman Baca Masyarakat
5	Sekolah	Perpustakaan SD
6	Sekolah	Perpustakaan SMP
7	Sekolah	Perpustakaan SMA
8	Sekolah	Perpustakaan SMK
11	Khusus	Perpustakaan Rumah Ibadah
12	Khusus	Perpustakaan Pondok Pesantren
\.


--
-- TOC entry 5171 (class 0 OID 24597)
-- Dependencies: 234
-- Data for Name: master_pertanyaan; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.master_pertanyaan (id, jenis_kuesioner, kategori_bagian, teks_pertanyaan, tipe_input, opsi_jawaban, urutan, is_active, keterangan, pilihan_opsi) FROM stdin;
2	IPLM	I. DATA JENIS PERPUSTAKAAN	Subjenis Perpustakaan	text	\N	2	1	\N	\N
8	IPLM	II. DATA DEMOGRAFI	Nama Institusi/Sekolah/OPD/TBM/Lainnya	text	\N	8	1	\N	\N
9	IPLM	II. DATA DEMOGRAFI	Nama Perpustakaan	text	\N	9	1	\N	\N
10	IPLM	II. DATA DEMOGRAFI	Alamat Institusi/Sekolah/OPD/TBM/Lainnya	textarea	\N	10	1	\N	\N
11	IPLM	II. DATA DEMOGRAFI	Provinsi Asal	text	\N	11	1	\N	\N
12	IPLM	II. DATA DEMOGRAFI	Kabupaten/Kota Asal	text	\N	12	1	\N	\N
13	IPLM	II. DATA DEMOGRAFI	Nama Lengkap Pengisi Kuesioner	text	\N	13	1	\N	\N
14	IPLM	II. DATA DEMOGRAFI	Kontak Pengisi Kuesioner (Whatsapp Aktif)	text	\N	14	1	\N	\N
15	IPLM	III. DIMENSI KOLEKSI	Jumlah Judul Koleksi Tercetak	number	\N	15	1	\N	\N
16	IPLM	III. DIMENSI KOLEKSI	Jumlah Eksemplar Koleksi Tercetak	number	\N	16	1	\N	\N
17	IPLM	III. DIMENSI KOLEKSI	Jumlah Judul Koleksi Digital	number	\N	17	1	\N	\N
18	IPLM	III. DIMENSI KOLEKSI	Jumlah Eksemplar Koleksi Digital	number	\N	18	1	\N	\N
19	IPLM	III. DIMENSI KOLEKSI	Penambahan Jumlah Judul Koleksi Tercetak dalam 1 Tahun Terakhir	number	\N	19	1	\N	\N
20	IPLM	III. DIMENSI KOLEKSI	Penambahan Jumlah Eksemplar Koleksi Tercetak dalam 1 Tahun Terakhir	number	\N	20	1	\N	\N
21	IPLM	III. DIMENSI KOLEKSI	Penambahan Jumlah Judul Koleksi Digital dalam 1 Tahun Terakhir	number	\N	21	1	\N	\N
22	IPLM	III. DIMENSI KOLEKSI	Penambahan Jumlah Eksemplar Koleksi Digital dalam 1 Tahun Terakhir	number	\N	22	1	\N	\N
25	IPLM	III. DIMENSI KOLEKSI	Total Jumlah Anggaran Pengembangan Koleksi Tercetak dan Digital dalam 1 Tahun Terakhir	number	\N	25	1	\N	\N
26	IPLM	IV. DIMENSI TENAGA PERPUSTAKAAN	Jumlah Tenaga Perpustakaan Memiliki Kualifikasi Pendidikan Ilmu Perpustakaan (Orang)	number	\N	26	1	\N	\N
27	IPLM	IV. DIMENSI TENAGA PERPUSTAKAAN	Jumlah Tenaga Perpustakaan Tidak Memiliki Kualifikasi Pendidikan Ilmu Perpustakaan (Orang)	number	\N	27	1	\N	\N
28	IPLM	IV. DIMENSI TENAGA PERPUSTAKAAN	Jumlah Tenaga Perpustakaan yang Mengikuti PKB/Diklat dalam 1 Tahun Terakhir (Orang)	number	\N	28	1	\N	\N
29	IPLM	IV. DIMENSI TENAGA PERPUSTAKAAN	Jumlah Anggaran Pengembangan Keprofesian (Diklat) Tenaga dalam 1 Tahun Terakhir	number	\N	29	1	\N	\N
30	IPLM	V. DIMENSI PELAYANAN	Jumlah Peserta Kegiatan Literasi/Budaya Baca dalam 1 Tahun Terakhir (Orang)	number	\N	30	1	\N	\N
31	IPLM	V. DIMENSI PELAYANAN	Jumlah Pemustaka (Luring/Daring) dalam 1 Tahun Terakhir (Orang)	number	\N	31	1	\N	\N
32	IPLM	V. DIMENSI PELAYANAN	Jumlah Pemustaka yang Menggunakan Fasilitas TIK dalam 1 Tahun Terakhir (Orang)	number	\N	32	1	\N	\N
33	IPLM	V. DIMENSI PELAYANAN	Jumlah Judul Koleksi Tercetak Yang Dimanfaatkan Dalam 1 Tahun Terakhir	number	\N	33	1	\N	\N
34	IPLM	V. DIMENSI PELAYANAN	Jumlah Eksemplar Koleksi Tercetak yang Dimanfaatkan dalam 1 Tahun Terakhir	number	\N	34	1	\N	\N
35	IPLM	V. DIMENSI PELAYANAN	Jumlah Judul Koleksi Digital yang Dimanfaatkan dalam 1 Tahun Terakhir	number	\N	35	1	\N	\N
36	IPLM	V. DIMENSI PELAYANAN	Jumlah Eksemplar Koleksi Digital yang Dimanfaatkan dalam 1 Tahun Terakhir	number	\N	36	1	\N	\N
37	IPLM	VI. DIMENSI PENYELENGGARAAN	Jumlah Kegiatan Penguatan Budaya Baca dalam 1 Tahun Terakhir	number	\N	37	1	\N	\N
38	IPLM	VI. DIMENSI PENYELENGGARAAN	Jumlah Kegiatan Kolaborasi/Kerja Sama dengan Pihak Eksternal dalam 1 Tahun Terakhir	number	\N	38	1	\N	\N
39	IPLM	VI. DIMENSI PENYELENGGARAAN	Jumlah Variasi Layanan yang Tersedia (Fisik Dan Digital)	number	\N	39	1	\N	\N
40	IPLM	VI. DIMENSI PENYELENGGARAAN	Jumlah Dokumen Kebijakan dan Prosedur Pelayanan Perpustakaan	number	\N	40	1	\N	\N
42	IPLM	VI. DIMENSI PENYELENGGARAAN	Jumlah Anggaran Peningkatan Pelayanan dan Pengelolaan dalam 1 Tahun Terakhir	number	\N	42	1	\N	\N
46	TKM	I. DATA DEMOGRAFI	Pekerjaan	text	\N	4	1	\N	\N
47	TKM	I. DATA DEMOGRAFI	Provinsi Asal	text	\N	5	1	\N	\N
48	TKM	I. DATA DEMOGRAFI	Kabupaten/Kota Asal	text	\N	6	1	\N	\N
50	TKM	II. KEBIASAAN MEMBACA	Tujuan utama membaca bagi saya adalah (Pengetahuan/Tugas/Kesenangan/Isi Waktu)	text	\N	8	1	\N	\N
51	TKM	II. KEBIASAAN MEMBACA	Berapa jarak rumah Anda ke perpustakaan terdekat?	text	\N	9	1	\N	\N
52	TKM	III. PRA MEMBACA	Saya membaca buku karena saya merasa senang saat membaca	likert	\N	10	1	\N	\N
53	TKM	III. PRA MEMBACA	Saya membaca buku yang menarik minat saya tanpa paksaan orang lain	likert	\N	11	1	\N	\N
54	TKM	III. PRA MEMBACA	Saya membaca untuk mencapai tujuan tertentu (misal: menambah pengetahuan)	likert	\N	12	1	\N	\N
55	TKM	III. PRA MEMBACA	Saya membaca untuk memahami informasi penting sebelum mengambil keputusan	likert	\N	13	1	\N	\N
56	TKM	III. PRA MEMBACA	Saya percaya bahwa saya dapat memahami teks walaupun topiknya baru bagi saya	likert	\N	14	1	\N	\N
57	TKM	III. PRA MEMBACA	Saya merasa percaya diri saat menceritakan kembali isi bacaan	likert	\N	15	1	\N	\N
58	TKM	III. PRA MEMBACA	Saya membaca karena didorong oleh orang lain	likert	\N	16	1	\N	\N
59	TKM	III. PRA MEMBACA	Saya membaca untuk memenuhi tugas atau kewajiban	likert	\N	17	1	\N	\N
60	TKM	III. PRA MEMBACA	Saya memiliki koleksi buku di rumah yang selalu tersedia untuk dibaca	likert	\N	18	1	\N	\N
61	TKM	III. PRA MEMBACA	Saya dapat mengunduh e-book dengan mudah dari internet kalau mau membaca	likert	\N	19	1	\N	\N
62	TKM	III. PRA MEMBACA	Dalam sebulan terakhir, berapa buku tercetak yang Anda baca?	text	\N	20	1	\N	\N
6	IPLM	II. DATA DEMOGRAFI	Nomor Pokok Perpustakaan (NPP) 	text	\N	6	1	(Jika belum memiliki NPP, isi dengan 0)	\N
4	IPLM	I. DATA JENIS PERPUSTAKAAN	Jumlah Siswa	number	\N	4	1	(Diisi jika Perpustakaan Sekolah, perpustakaan lainnya isi dengan 0)	\N
7	IPLM	II. DATA DEMOGRAFI	Nomor Pokok Sekolah Nasional (NPSN)	text	\N	7	1	(Diisi jika Perpustakaan Sekolah, perpustakaan lainnya isi dengan 0)	\N
24	IPLM	III. DIMENSI KOLEKSI	Jumlah Anggaran Pengembangan Koleksi dari Dana Non BOS 	number	\N	24	1	(Diisi jika Perpustakaan Sekolah, perpustakaan lainnya isi dengan 0)	\N
41	IPLM	VI. DIMENSI PENYELENGGARAAN	Jumlah Peraturan Daerah Tentang Perpustakaan 	number	\N	41	1	(Diisi jika Perpustakaan Umum Provinsi/Kabupaten/Kota, perpustakaan lainnya isi dengan 0)	\N
49	TKM	II. KEBIASAAN MEMBACA	Apakah Anda memiliki waktu khusus untuk membaca? (Ya/Tidak)	radio	\N	7	1		Ya,Tidak
44	TKM	I. DATA DEMOGRAFI	Jenis Kelamin (L/P)	radio	\N	2	1		Laki-laki, Perempuan
1	IPLM	I. DATA JENIS PERPUSTAKAAN	Jenis Perpustakaan	text	\N	1	1		
63	TKM	III. PRA MEMBACA	Dalam sebulan terakhir, berapa buku digital yang Anda baca?	text	\N	21	1	\N	\N
64	TKM	III. PRA MEMBACA	Berapa lama durasi Anda membaca buku tercetak sekali duduk?	text	\N	22	1	\N	\N
65	TKM	III. PRA MEMBACA	Berapa lama durasi Anda membaca buku digital sekali duduk?	text	\N	23	1	\N	\N
66	TKM	III. PRA MEMBACA	Saya memiliki pencahayaan yang cukup saat membaca	likert	\N	24	1	\N	\N
68	TKM	III. PRA MEMBACA	Saya sering melihat anggota keluarga membaca sehingga saya terinspirasi	likert	\N	26	1	\N	\N
69	TKM	III. PRA MEMBACA	Saya dan keluarga sering membaca bersama	likert	\N	27	1	\N	\N
70	TKM	III. PRA MEMBACA	Tokoh publik (Influencer, Duta Baca, dll) merekomendasikan bacaan yang sesuai minat saya	likert	\N	28	1	\N	\N
71	TKM	III. PRA MEMBACA	Tokoh publik memberikan dorongan dan arahan agar saya membaca	likert	\N	29	1	\N	\N
72	TKM	III. PRA MEMBACA	Saya sering berdiskusi dengan teman tentang buku yang kami baca	likert	\N	30	1	\N	\N
73	TKM	III. PRA MEMBACA	Teman saya memberi saya rekomendasi buku yang menarik	likert	\N	31	1	\N	\N
74	TKM	III. PRA MEMBACA	Saya mengikuti kegiatan literasi (misal: klub buku, workshop)	likert	\N	32	1	\N	\N
76	TKM	III. PRA MEMBACA	Orang di lingkungan saya menghargai kebiasaan membaca	likert	\N	34	1	\N	\N
77	TKM	III. PRA MEMBACA	Media lokal mempromosikan kegiatan membaca	likert	\N	35	1	\N	\N
78	TKM	III. PRA MEMBACA	Acara budaya di tempat saya sering melibatkan kegiatan membaca	likert	\N	36	1	\N	\N
79	TKM	III. PRA MEMBACA	Saya menjadi anggota/pengurus organisasi pembaca/literasi	likert	\N	37	1	\N	\N
80	TKM	III. PRA MEMBACA	Saya membantu penyelenggaraan acara literasi di lingkungan saya	likert	\N	38	1	\N	\N
81	TKM	III. PRA MEMBACA	Saya menggunakan perpustakaan digital untuk membaca materi budaya	likert	\N	39	1	\N	\N
82	TKM	III. PRA MEMBACA	Saya mengikuti tur atau acara budaya yang melibatkan kegiatan literasi	likert	\N	40	1	\N	\N
83	TKM	IV. SAAT MEMBACA	Saya dapat mempertahankan fokus saat membaca hingga selesai	likert	\N	41	1	\N	\N
84	TKM	IV. SAAT MEMBACA	Saya memilih tempat tenang untuk membaca	likert	\N	42	1	\N	\N
85	TKM	IV. SAAT MEMBACA	Saya membuat catatan atau menandai bagian penting dalam teks	likert	\N	43	1	\N	\N
86	TKM	IV. SAAT MEMBACA	Saya membuat ringkasan setelah selesai membaca	likert	\N	44	1	\N	\N
87	TKM	IV. SAAT MEMBACA	Saya sering bertanya pada diri sendiri untuk memeriksa pemahaman	likert	\N	45	1	\N	\N
89	TKM	IV. SAAT MEMBACA	Saya dapat menilai diri sendiri tingkat pemahaman saya tentang isi bacaan	likert	\N	47	1	\N	\N
90	TKM	IV. SAAT MEMBACA	Saya meminta bantuan orang lain jika menemukan bagian sulit	likert	\N	48	1	\N	\N
91	TKM	IV. SAAT MEMBACA	Saya tertarik membaca opini/pendapat orang lain tentang isi sebuah buku	likert	\N	49	1	\N	\N
92	TKM	IV. SAAT MEMBACA	Saya tertarik untuk bertukar pikiran terkait buku yang saya baca dengan orang lain	likert	\N	50	1	\N	\N
93	TKM	IV. SAAT MEMBACA	Saya suka membaca bersama teman atau kelompok	likert	\N	51	1	\N	\N
94	TKM	IV. SAAT MEMBACA	Saya merencanakan waktu membaca bersama	likert	\N	52	1	\N	\N
95	TKM	IV. SAAT MEMBACA	Saya membahas arti kata sulit dalam teks bersama teman	likert	\N	53	1	\N	\N
96	TKM	IV. SAAT MEMBACA	Saya mencatat dan mencari arti kata sulit dalam kamus/ensiklopedia	likert	\N	54	1	\N	\N
98	TKM	IV. SAAT MEMBACA	Saya menulis laporan berdasarkan hasil baca	likert	\N	56	1	\N	\N
99	TKM	V. PASCA MEMBACA	Setelah membaca, saya merasa pengetahuan saya bertambah	likert	\N	57	1	\N	\N
100	TKM	V. PASCA MEMBACA	Saya merasa menjadi lebih senang setelah saya membaca	likert	\N	58	1	\N	\N
101	TKM	V. PASCA MEMBACA	Membaca membantu saya berkomunikasi lebih baik dengan orang lain	likert	\N	59	1	\N	\N
102	TKM	V. PASCA MEMBACA	Saya membaca untuk memahami perspektif orang lain	likert	\N	60	1	\N	\N
103	TKM	V. PASCA MEMBACA	Membaca membuat saya lebih memahami perasaan diri sendiri atau tokoh	likert	\N	61	1	\N	\N
104	TKM	V. PASCA MEMBACA	Saya termotivasi setelah membaca cerita inspiratif	likert	\N	62	1	\N	\N
105	TKM	V. PASCA MEMBACA	Saya percaya mampu meningkatkan kemampuan membaca	likert	\N	63	1	\N	\N
106	TKM	V. PASCA MEMBACA	Saya percaya diri saat membaca topik baru	likert	\N	64	1	\N	\N
107	TKM	V. PASCA MEMBACA	Saya menganggap membaca sebagai kegiatan bernilai dan bermanfaat	likert	\N	65	1	\N	\N
108	TKM	V. PASCA MEMBACA	Saya bersedia meluangkan waktu membaca setiap hari	likert	\N	66	1	\N	\N
109	TKM	VI. INTERAKSI DENGAN PERPUSTAKAAN	Saya datang ke perpustakaan untuk mencari informasi terpercaya	likert	\N	67	1	\N	\N
110	TKM	VI. INTERAKSI DENGAN PERPUSTAKAAN	Saya mencari bahan bacaan di perpustakaan untuk minat pribadi	likert	\N	68	1	\N	\N
111	TKM	VI. INTERAKSI DENGAN PERPUSTAKAAN	Saya datang ke perpustakaan setiap bulan selama 3 bulan terakhir	likert	\N	69	1	\N	\N
112	TKM	VI. INTERAKSI DENGAN PERPUSTAKAAN	Saya menggunakan fasilitas perpustakaan lebih dari satu kali dalam 3 bulan terakhir	likert	\N	70	1	\N	\N
114	TKM	VI. INTERAKSI DENGAN PERPUSTAKAAN	Saya merasa nyaman bertanya kepada pustakawan	likert	\N	72	1	\N	\N
115	TKM	VI. INTERAKSI DENGAN PERPUSTAKAAN	Saya melihat katalog online untuk menemukan materi	likert	\N	73	1	\N	\N
116	TKM	VI. INTERAKSI DENGAN PERPUSTAKAAN	Saya meminjam atau mengunduh buku dari koleksi perpustakaan	likert	\N	74	1	\N	\N
117	TKM	VI. INTERAKSI DENGAN PERPUSTAKAAN	Saya membagikan informasi perpustakaan kepada teman/rekan kerja	likert	\N	75	1	\N	\N
118	TKM	VI. INTERAKSI DENGAN PERPUSTAKAAN	Saya mengintegrasikan bacaan perpustakaan dalam presentasi	likert	\N	76	1	\N	\N
119	TKM	VI. INTERAKSI DENGAN PERPUSTAKAAN	Saya sering merekomendasikan buku perpustakaan kepada orang lain	likert	\N	77	1	\N	\N
120	TKM	VI. INTERAKSI DENGAN PERPUSTAKAAN	Saya menulis ulasan singkat tentang buku yang saya baca	likert	\N	78	1	\N	\N
121	TKM	VI. INTERAKSI DENGAN PERPUSTAKAAN	Saya puas dengan kualitas layanan perpustakaan yang saya gunakan	likert	\N	79	1	\N	\N
122	TKM	VI. INTERAKSI DENGAN PERPUSTAKAAN	Saya akan merekomendasikan perpustakaan ini kepada orang lain	likert	\N	80	1	\N	\N
67	TKM	III. PRA MEMBACA	Saya mudah menemukan tempat yang nyaman untuk membaca	likert	\N	25	1		
97	TKM	IV. SAAT MEMBACA	Saya menggunakan bacaan untuk memecahkan masalah sehari-hari	likert	\N	55	1		
88	TKM	IV. SAAT MEMBACA	Saya berdiskusi tentang teks yang saya baca dengan orang lain setelah membaca	likert	\N	46	1		
3	IPLM	I. DATA JENIS PERPUSTAKAAN	Jumlah Guru dan Tenaga Kependidikan 	number	\N	3	1	(Diisi jika Perpustakaan Sekolah, perpustakaan lainnya isi dengan 0)	\N
5	IPLM	I. DATA JENIS PERPUSTAKAAN	Jumlah Karyawan	number	\N	5	1	(Diisi jika Perpustakaan Khusus, perpustakaan lainnya isi dengan 0)	\N
23	IPLM	III. DIMENSI KOLEKSI	Jumlah Anggaran Pengembangan Koleksi dari Dana BOS 	number	\N	23	1	(Diisi jika Perpustakaan Sekolah, perpustakaan lainnya isi dengan 0)	\N
45	TKM	I. DATA DEMOGRAFI	Pendidikan Terakhir	text	\N	3	1	SD Tidak Tamat,\r\nSD/MI,\r\nSMP/MTs,\r\nSMA/SMK/MA,\r\nDiploma-D1/D2/D3,\r\nSarjana-D4/S1,\r\nMagister-S2\r\nDoktor-S3\r\n	\N
43	TKM	I. DATA DEMOGRAFI	Usia (Tahun)	select	\N	1	1		15-28 Tahun, 29-42 Tahun, 43-56 Tahun, Lebih dari 56 Tahun
75	TKM	III. PRA MEMBACA	Saya sering berpartisipasi dalam tantangan membaca (reading challenge)	likert	\N	33	1		
113	TKM	VI. INTERAKSI DENGAN PERPUSTAKAAN	Pustakawan membantu saya menemukan bahan bacaan yang sesuai	likert	\N	71	1		
\.


--
-- TOC entry 5182 (class 0 OID 24698)
-- Dependencies: 245
-- Data for Name: password_reset_email_logs; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.password_reset_email_logs (id, email, status, error_message, created_at, token_hash, expires_at) FROM stdin;
1	gathfank@gmail.com	failed	250-SIZE 35882577\r\n	2026-02-04 13:08:01.657997	\N	\N
2	gathfank@gmail.com	failed	250-SIZE 35882577\r\n	2026-02-04 13:08:02.387302	\N	\N
3	gathfank@gmail.com	sent	\N	2026-02-04 13:10:51.099829	\N	\N
4	gathfank@gmail.com	sent	\N	2026-02-04 13:10:54.818217	\N	\N
5	gathfank@gmail.com	sent	\N	2026-02-04 13:14:36.689921	\N	\N
6	gathfank@gmail.com	sent	\N	2026-02-04 13:14:40.446922	\N	\N
7	gathfank@gmail.com	sent	\N	2026-02-04 13:20:51.251562	\N	\N
8	gathfank@gmail.com	sent	\N	2026-02-04 13:22:47.086764	\N	\N
9	gathfank@gmail.com	sent	\N	2026-02-04 13:25:21.233484	\N	\N
10	gathfank@gmail.com	sent	\N	2026-02-04 13:26:04.947396	\N	\N
11	gathfank@gmail.com	sent	\N	2026-02-04 13:26:11.235382	\N	\N
12	gathfank@gmail.com	sent	\N	2026-02-04 13:29:29.760404	e034eff8223606fe9df683ffb04984a6aab8e8424be1644c4dafa4838814409f	2026-02-04 14:29:26.052642
13	gathfank@gmail.com	sent	\N	2026-02-04 13:29:45.283776	aebcb34e1d2084cd1727725f117ded67e6d4cf5f74f11aef905d3a1a325d5804	2026-02-04 14:29:41.555022
\.


--
-- TOC entry 5186 (class 0 OID 24725)
-- Dependencies: 249
-- Data for Name: password_reset_logs; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.password_reset_logs (id, user_id, ip_address, created_at) FROM stdin;
1	4	::1	2026-02-04 13:29:58.771666
\.


--
-- TOC entry 5184 (class 0 OID 24712)
-- Dependencies: 247
-- Data for Name: password_resets; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.password_resets (id, user_id, token_hash, expires_at, used_at, created_at) FROM stdin;
12	4	e034eff8223606fe9df683ffb04984a6aab8e8424be1644c4dafa4838814409f	2026-02-04 14:29:26.052642	\N	2026-02-04 13:29:26.052642
\.


--
-- TOC entry 5180 (class 0 OID 24674)
-- Dependencies: 243
-- Data for Name: pengaduan; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.pengaduan (id, nama, kontak, pesan, tanggal, created_at, is_important, is_done) FROM stdin;
8	ole	01287308127	saya butuh buku biologi yang lebih update	2026-02-04	2026-02-04 11:54:25.132526	t	t
7	kevin	1922941213	terlalu berisik	2026-02-04	2026-02-04 11:51:33.531664	t	t
6	roman	19201242142	kurang nyaman tempat bacanya banyak debu dan laba laba	2026-02-04	2026-02-04 11:50:53.615493	f	f
\.


--
-- TOC entry 5159 (class 0 OID 16853)
-- Dependencies: 222
-- Data for Name: report_periods; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.report_periods (id, bulan, tahun, status, created_at) FROM stdin;
\.


--
-- TOC entry 5167 (class 0 OID 16922)
-- Dependencies: 230
-- Data for Name: report_verifications; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.report_verifications (id, report_id, catatan, verified_at) FROM stdin;
\.


--
-- TOC entry 5161 (class 0 OID 16867)
-- Dependencies: 224
-- Data for Name: reports; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.reports (id, library_id, period_id, jenis, status, created_at) FROM stdin;
\.


--
-- TOC entry 5163 (class 0 OID 16894)
-- Dependencies: 226
-- Data for Name: reports_iplm; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.reports_iplm (id, report_id, jumlah_buku, jumlah_pengunjung, jumlah_kegiatan_literasi, jumlah_tenaga_perpustakaan, skor_total) FROM stdin;
\.


--
-- TOC entry 5165 (class 0 OID 16908)
-- Dependencies: 228
-- Data for Name: reports_tkm; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.reports_tkm (id, report_id, jumlah_pembaca, jumlah_buku_dibaca, rata_waktu_membaca, skor_total) FROM stdin;
\.


--
-- TOC entry 5178 (class 0 OID 24666)
-- Dependencies: 241
-- Data for Name: settings; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.settings (setting_key, setting_value) FROM stdin;
tkm_start	2026-02-03 07:52
tkm_end	2026-02-03 07:53
status_tkm	buka
tkm_mode	manual
status_iplm	buka
iplm_mode	manual
iplm_kontak_pertanyaan_id	14
iplm_autofill_jenis_id	1
iplm_autofill_subjenis_id	2
iplm_autofill_nama_id	9
iplm_start	2026-02-02T21:25
iplm_end	2026-02-02T21:26
\.


--
-- TOC entry 5175 (class 0 OID 24623)
-- Dependencies: 238
-- Data for Name: trans_detail; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.trans_detail (id, header_id, pertanyaan_id, jawaban) FROM stdin;
1	1	1	13213
2	1	2	1313
3	1	3	1231
4	1	4	321
5	1	5	123
6	1	6	3123
7	1	7	312
8	1	8	312
9	1	9	412
10	1	10	412
11	1	11	214
12	1	12	421
13	1	13	412
14	1	14	412
15	1	15	12321
16	1	16	2312
17	1	17	213
18	1	18	3123
19	1	19	312
20	1	20	23
21	1	21	312
22	1	22	321
23	1	23	12323
24	1	24	412
25	1	25	41211
26	1	26	31
27	1	27	412
28	1	28	213
29	1	29	412
30	1	30	321
31	1	31	321
32	1	32	8787
33	1	33	87
34	1	34	87
35	1	35	87
36	1	36	87
37	1	37	87
38	1	38	78
39	1	39	78
40	1	40	87
41	1	41	87
42	1	42	87
43	2	1	58578
44	2	2	58
45	2	3	585
46	2	4	85
47	2	5	87
48	2	6	5858
49	2	7	5857
50	2	8	5857
51	2	9	85
52	2	10	587
53	2	11	587
54	2	12	87
55	2	13	578
56	2	14	57
57	2	15	875
58	2	16	875
59	2	17	587
60	2	18	857
61	2	19	758
62	2	20	7
63	2	21	5857
64	2	22	578
65	2	23	857
66	2	24	578
67	2	25	78
68	2	26	588
69	2	27	85
70	2	28	875
71	2	29	57
72	2	30	875
73	2	31	57
74	2	32	857
75	2	33	857
76	2	34	5
77	2	35	54
78	2	36	564
79	2	37	85
80	2	38	7
81	2	39	575
82	2	40	86
83	2	41	96
84	2	42	65
85	4	43	6565
86	4	44	65
87	4	45	65
88	4	46	6
89	4	47	56
90	4	48	565
91	4	49	65
92	4	50	65
93	4	51	65
94	4	52	1
95	4	53	1
96	4	54	1
97	4	55	1
98	4	56	1
99	4	57	1
100	4	58	1
101	4	59	1
102	4	60	1
103	4	61	1
104	4	62	565
105	4	63	65
106	4	64	65
107	4	65	65
108	4	66	1
109	4	67	1
110	4	68	1
111	4	69	1
112	4	70	1
113	4	71	1
114	4	72	1
115	4	73	1
116	4	74	1
117	4	75	1
118	4	76	1
119	4	77	1
120	4	78	1
121	4	79	1
122	4	80	1
123	4	81	1
124	4	82	1
125	4	83	1
126	4	84	1
127	4	85	1
128	4	86	1
129	4	87	1
130	4	88	1
131	4	89	1
132	4	90	1
133	4	91	1
134	4	92	1
135	4	93	1
136	4	94	1
137	4	95	1
138	4	96	1
139	4	97	1
140	4	98	1
141	4	99	1
142	4	100	1
143	4	101	1
144	4	102	1
145	4	103	1
146	4	104	1
147	4	105	1
148	4	106	1
149	4	107	1
150	4	108	1
151	4	109	1
152	4	110	1
153	4	111	1
154	4	112	1
155	4	113	1
156	4	114	1
157	4	115	1
158	4	116	1
159	4	117	1
160	4	118	1
161	4	119	1
162	4	120	1
163	4	121	1
164	4	122	1
165	5	43	15-28 Tahun
166	5	44	Laki-laki
167	5	45	SD
168	5	46	Nganggur
169	5	47	NTB
170	5	48	Sumbawa
171	5	49	Tidak
172	5	50	Belajar
173	5	51	1KM
174	5	52	2
175	5	53	4
176	5	54	4
177	5	55	3
178	5	56	3
179	5	57	3
180	5	58	1
181	5	59	3
182	5	60	3
183	5	61	3
184	5	62	3
185	5	63	10
186	5	64	30 menit
187	5	65	30 menit
188	5	66	3
189	5	67	3
190	5	68	3
191	5	69	3
192	5	70	3
193	5	71	3
194	5	72	3
195	5	73	3
196	5	74	1
197	5	75	1
198	5	76	3
199	5	77	3
200	5	78	3
201	5	79	3
202	5	80	3
203	5	81	3
204	5	82	3
205	5	83	3
206	5	84	3
207	5	85	3
208	5	86	3
209	5	87	3
210	5	88	3
211	5	89	3
212	5	90	3
213	5	91	3
214	5	92	3
215	5	93	3
216	5	94	3
217	5	95	3
218	5	96	3
219	5	97	3
220	5	98	3
221	5	99	3
222	5	100	3
223	5	101	3
224	5	102	3
225	5	103	3
226	5	104	3
227	5	105	3
228	5	106	3
229	5	107	3
230	5	108	3
231	5	109	3
232	5	110	3
233	5	111	3
234	5	112	3
235	5	113	3
236	5	114	3
237	5	115	3
238	5	116	3
239	5	117	3
240	5	118	3
241	5	119	3
242	5	120	3
243	5	121	3
244	5	122	3
245	6	43	15-28 Tahun
246	6	44	Laki-laki
247	6	45	SD
248	6	46	Pegawai BUMN
249	6	47	ntb
250	6	48	mataram
251	6	49	Ya
252	6	50	yfYUFi
253	6	51	ug
254	6	52	1
255	6	53	1
256	6	54	1
257	6	55	2
258	6	56	2
259	6	57	2
260	6	58	2
261	6	59	3
262	6	60	3
263	6	61	3
264	6	62	n
265	6	63	jhiu
266	6	64	h
267	6	65	jbiug
268	6	66	3
269	6	67	3
270	6	68	2
271	6	69	3
272	6	70	3
273	6	71	2
274	6	72	2
275	6	73	3
276	6	74	3
277	6	75	2
278	6	76	3
279	6	77	4
280	6	78	3
281	6	79	3
282	6	80	2
283	6	81	2
284	6	82	3
285	6	83	3
286	6	84	4
287	6	85	3
288	6	86	2
289	6	87	3
290	6	88	4
291	6	89	3
292	6	90	4
293	6	91	4
294	6	92	3
295	6	93	4
296	6	94	2
297	6	95	3
298	6	96	4
299	6	97	3
300	6	98	4
301	6	99	3
302	6	100	4
303	6	101	4
304	6	102	3
305	6	103	4
306	6	104	4
307	6	105	3
308	6	106	4
309	6	107	4
310	6	108	3
311	6	109	4
312	6	110	3
313	6	111	4
314	6	112	4
315	6	113	3
316	6	114	4
317	6	115	3
318	6	116	3
319	6	117	4
320	6	118	3
321	6	119	4
322	6	120	4
323	6	121	3
324	6	122	3
\.


--
-- TOC entry 5173 (class 0 OID 24612)
-- Dependencies: 236
-- Data for Name: trans_header; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.trans_header (id, library_id, jenis_kuesioner, periode_bulan, periode_tahun, tanggal_isi) FROM stdin;
1	26	IPLM	02	2026	2026-02-03 08:22:53.847214
2	26	IPLM	02	2026	2026-02-03 08:23:22.882812
4	\N	TKM	02	2026	2026-02-03 08:32:44.650753
5	\N	TKM	02	2026	2026-02-03 11:54:56.840178
6	\N	TKM	02	2026	2026-02-03 13:06:49.904497
\.


--
-- TOC entry 5157 (class 0 OID 16824)
-- Dependencies: 220
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.users (id, nama, email, password, created_at) FROM stdin;
1	admin	admin@mail.com	$2y$10$8gml0lCVate6CFL0N2xGFuHnnWT4Zb1zrFzbdTtrevODoeLLtnKHa	2026-02-04 12:09:58.765034
4	kevin	gathfank@gmail.com	$2y$10$GPQ3/WkNQFc96DMWfGBZyObmiThFjcqxd9uv3/kbdAXYNGD4Ovy4C	2026-02-04 13:07:51.412428
\.


--
-- TOC entry 5208 (class 0 OID 0)
-- Dependencies: 231
-- Name: libraries_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.libraries_id_seq', 71, true);


--
-- TOC entry 5209 (class 0 OID 0)
-- Dependencies: 239
-- Name: master_kategori_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.master_kategori_id_seq', 13, true);


--
-- TOC entry 5210 (class 0 OID 0)
-- Dependencies: 233
-- Name: master_pertanyaan_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.master_pertanyaan_id_seq', 122, true);


--
-- TOC entry 5211 (class 0 OID 0)
-- Dependencies: 244
-- Name: password_reset_email_logs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.password_reset_email_logs_id_seq', 13, true);


--
-- TOC entry 5212 (class 0 OID 0)
-- Dependencies: 248
-- Name: password_reset_logs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.password_reset_logs_id_seq', 1, true);


--
-- TOC entry 5213 (class 0 OID 0)
-- Dependencies: 246
-- Name: password_resets_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.password_resets_id_seq', 13, true);


--
-- TOC entry 5214 (class 0 OID 0)
-- Dependencies: 242
-- Name: pengaduan_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.pengaduan_id_seq', 8, true);


--
-- TOC entry 5215 (class 0 OID 0)
-- Dependencies: 221
-- Name: report_periods_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.report_periods_id_seq', 1, false);


--
-- TOC entry 5216 (class 0 OID 0)
-- Dependencies: 229
-- Name: report_verifications_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.report_verifications_id_seq', 1, false);


--
-- TOC entry 5217 (class 0 OID 0)
-- Dependencies: 223
-- Name: reports_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.reports_id_seq', 1, false);


--
-- TOC entry 5218 (class 0 OID 0)
-- Dependencies: 225
-- Name: reports_iplm_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.reports_iplm_id_seq', 1, false);


--
-- TOC entry 5219 (class 0 OID 0)
-- Dependencies: 227
-- Name: reports_tkm_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.reports_tkm_id_seq', 1, false);


--
-- TOC entry 5220 (class 0 OID 0)
-- Dependencies: 237
-- Name: trans_detail_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.trans_detail_id_seq', 324, true);


--
-- TOC entry 5221 (class 0 OID 0)
-- Dependencies: 235
-- Name: trans_header_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.trans_header_id_seq', 6, true);


--
-- TOC entry 5222 (class 0 OID 0)
-- Dependencies: 219
-- Name: users_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.users_id_seq', 6, true);


--
-- TOC entry 4984 (class 2606 OID 24590)
-- Name: libraries libraries_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.libraries
    ADD CONSTRAINT libraries_pkey PRIMARY KEY (id);


--
-- TOC entry 4992 (class 2606 OID 24665)
-- Name: master_kategori master_kategori_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.master_kategori
    ADD CONSTRAINT master_kategori_pkey PRIMARY KEY (id);


--
-- TOC entry 4986 (class 2606 OID 24610)
-- Name: master_pertanyaan master_pertanyaan_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.master_pertanyaan
    ADD CONSTRAINT master_pertanyaan_pkey PRIMARY KEY (id);


--
-- TOC entry 4998 (class 2606 OID 24710)
-- Name: password_reset_email_logs password_reset_email_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.password_reset_email_logs
    ADD CONSTRAINT password_reset_email_logs_pkey PRIMARY KEY (id);


--
-- TOC entry 5002 (class 2606 OID 24734)
-- Name: password_reset_logs password_reset_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.password_reset_logs
    ADD CONSTRAINT password_reset_logs_pkey PRIMARY KEY (id);


--
-- TOC entry 5000 (class 2606 OID 24723)
-- Name: password_resets password_resets_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.password_resets
    ADD CONSTRAINT password_resets_pkey PRIMARY KEY (id);


--
-- TOC entry 4996 (class 2606 OID 24684)
-- Name: pengaduan pengaduan_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pengaduan
    ADD CONSTRAINT pengaduan_pkey PRIMARY KEY (id);


--
-- TOC entry 4972 (class 2606 OID 16865)
-- Name: report_periods report_periods_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.report_periods
    ADD CONSTRAINT report_periods_pkey PRIMARY KEY (id);


--
-- TOC entry 4982 (class 2606 OID 16932)
-- Name: report_verifications report_verifications_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.report_verifications
    ADD CONSTRAINT report_verifications_pkey PRIMARY KEY (id);


--
-- TOC entry 4978 (class 2606 OID 16901)
-- Name: reports_iplm reports_iplm_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.reports_iplm
    ADD CONSTRAINT reports_iplm_pkey PRIMARY KEY (id);


--
-- TOC entry 4974 (class 2606 OID 16882)
-- Name: reports reports_library_id_period_id_jenis_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.reports
    ADD CONSTRAINT reports_library_id_period_id_jenis_key UNIQUE (library_id, period_id, jenis);


--
-- TOC entry 4976 (class 2606 OID 16880)
-- Name: reports reports_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.reports
    ADD CONSTRAINT reports_pkey PRIMARY KEY (id);


--
-- TOC entry 4980 (class 2606 OID 16915)
-- Name: reports_tkm reports_tkm_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.reports_tkm
    ADD CONSTRAINT reports_tkm_pkey PRIMARY KEY (id);


--
-- TOC entry 4994 (class 2606 OID 24672)
-- Name: settings settings_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.settings
    ADD CONSTRAINT settings_pkey PRIMARY KEY (setting_key);


--
-- TOC entry 4990 (class 2606 OID 24633)
-- Name: trans_detail trans_detail_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.trans_detail
    ADD CONSTRAINT trans_detail_pkey PRIMARY KEY (id);


--
-- TOC entry 4988 (class 2606 OID 24621)
-- Name: trans_header trans_header_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.trans_header
    ADD CONSTRAINT trans_header_pkey PRIMARY KEY (id);


--
-- TOC entry 4966 (class 2606 OID 16838)
-- Name: users users_email_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_key UNIQUE (email);


--
-- TOC entry 4968 (class 2606 OID 24688)
-- Name: users users_nama_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_nama_unique UNIQUE (nama);


--
-- TOC entry 4970 (class 2606 OID 16836)
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- TOC entry 5006 (class 2606 OID 16933)
-- Name: report_verifications report_verifications_report_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.report_verifications
    ADD CONSTRAINT report_verifications_report_id_fkey FOREIGN KEY (report_id) REFERENCES public.reports(id) ON DELETE CASCADE;


--
-- TOC entry 5004 (class 2606 OID 16902)
-- Name: reports_iplm reports_iplm_report_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.reports_iplm
    ADD CONSTRAINT reports_iplm_report_id_fkey FOREIGN KEY (report_id) REFERENCES public.reports(id) ON DELETE CASCADE;


--
-- TOC entry 5003 (class 2606 OID 16888)
-- Name: reports reports_period_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.reports
    ADD CONSTRAINT reports_period_id_fkey FOREIGN KEY (period_id) REFERENCES public.report_periods(id) ON DELETE CASCADE;


--
-- TOC entry 5005 (class 2606 OID 16916)
-- Name: reports_tkm reports_tkm_report_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.reports_tkm
    ADD CONSTRAINT reports_tkm_report_id_fkey FOREIGN KEY (report_id) REFERENCES public.reports(id) ON DELETE CASCADE;


--
-- TOC entry 5007 (class 2606 OID 24634)
-- Name: trans_detail trans_detail_header_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.trans_detail
    ADD CONSTRAINT trans_detail_header_id_fkey FOREIGN KEY (header_id) REFERENCES public.trans_header(id) ON DELETE CASCADE;


--
-- TOC entry 5008 (class 2606 OID 24639)
-- Name: trans_detail trans_detail_pertanyaan_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.trans_detail
    ADD CONSTRAINT trans_detail_pertanyaan_id_fkey FOREIGN KEY (pertanyaan_id) REFERENCES public.master_pertanyaan(id);


-- Completed on 2026-02-04 13:31:58

--
-- PostgreSQL database dump complete
--

\unrestrict wHSQxwQXYTz56dHmYGjk1K9rbwocMlccVlolChnjGvzlWNTXrReriXtOfzJ1ogW

