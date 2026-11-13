--
-- PostgreSQL database dump
--

\restrict gWwLWqiaelEJVHKmWc9NMBIWYFMcAYxKsI0bamhDhdevdhR8INt6TI2IIF4phOt

-- Dumped from database version 15.18 (Homebrew)
-- Dumped by pg_dump version 18.6

-- Started on 2026-11-13 18:38:10 -03

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
-- TOC entry 230 (class 1259 OID 27282)
-- Name: insurance_contracts; Type: TABLE; Schema: public; Owner: theseguro
--

CREATE TABLE public.insurance_contracts (
    id bigint NOT NULL,
    proposal_id bigint NOT NULL,
    contract_number character varying(50) NOT NULL,
    contracted_at timestamp(0) without time zone NOT NULL,
    starts_on date NOT NULL,
    ends_on date NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT insurance_contracts_period_check CHECK ((ends_on > starts_on))
);


ALTER TABLE public.insurance_contracts OWNER TO theseguro;

--
-- TOC entry 229 (class 1259 OID 27281)
-- Name: insurance_contracts_id_seq; Type: SEQUENCE; Schema: public; Owner: theseguro
--

CREATE SEQUENCE public.insurance_contracts_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.insurance_contracts_id_seq OWNER TO theseguro;

--
-- TOC entry 3939 (class 0 OID 0)
-- Dependencies: 229
-- Name: insurance_contracts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: theseguro
--

ALTER SEQUENCE public.insurance_contracts_id_seq OWNED BY public.insurance_contracts.id;


--
-- TOC entry 215 (class 1259 OID 27156)
-- Name: migrations; Type: TABLE; Schema: public; Owner: theseguro
--

CREATE TABLE public.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


ALTER TABLE public.migrations OWNER TO theseguro;

--
-- TOC entry 214 (class 1259 OID 27155)
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: theseguro
--

CREATE SEQUENCE public.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.migrations_id_seq OWNER TO theseguro;

--
-- TOC entry 3940 (class 0 OID 0)
-- Dependencies: 214
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: theseguro
--

ALTER SEQUENCE public.migrations_id_seq OWNED BY public.migrations.id;


--
-- TOC entry 218 (class 1259 OID 27176)
-- Name: password_reset_tokens; Type: TABLE; Schema: public; Owner: theseguro
--

CREATE TABLE public.password_reset_tokens (
    email character varying(255) NOT NULL,
    token character varying(255) NOT NULL,
    created_at timestamp(0) without time zone
);


ALTER TABLE public.password_reset_tokens OWNER TO theseguro;

--
-- TOC entry 226 (class 1259 OID 27224)
-- Name: plan_service; Type: TABLE; Schema: public; Owner: theseguro
--

CREATE TABLE public.plan_service (
    id bigint NOT NULL,
    plan_id bigint NOT NULL,
    service_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.plan_service OWNER TO theseguro;

--
-- TOC entry 225 (class 1259 OID 27223)
-- Name: plan_service_id_seq; Type: SEQUENCE; Schema: public; Owner: theseguro
--

CREATE SEQUENCE public.plan_service_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.plan_service_id_seq OWNER TO theseguro;

--
-- TOC entry 3941 (class 0 OID 0)
-- Dependencies: 225
-- Name: plan_service_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: theseguro
--

ALTER SEQUENCE public.plan_service_id_seq OWNED BY public.plan_service.id;


--
-- TOC entry 224 (class 1259 OID 27211)
-- Name: plans; Type: TABLE; Schema: public; Owner: theseguro
--

CREATE TABLE public.plans (
    id bigint NOT NULL,
    name character varying(100) NOT NULL,
    description text NOT NULL,
    annual_price numeric(12,2) NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT plans_annual_price_positive CHECK ((annual_price > (0)::numeric))
);


ALTER TABLE public.plans OWNER TO theseguro;

--
-- TOC entry 223 (class 1259 OID 27210)
-- Name: plans_id_seq; Type: SEQUENCE; Schema: public; Owner: theseguro
--

CREATE SEQUENCE public.plans_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.plans_id_seq OWNER TO theseguro;

--
-- TOC entry 3942 (class 0 OID 0)
-- Dependencies: 223
-- Name: plans_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: theseguro
--

ALTER SEQUENCE public.plans_id_seq OWNED BY public.plans.id;


--
-- TOC entry 228 (class 1259 OID 27245)
-- Name: proposals; Type: TABLE; Schema: public; Owner: theseguro
--

CREATE TABLE public.proposals (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    vehicle_id bigint NOT NULL,
    plan_id bigint NOT NULL,
    quoted_amount numeric(12,2) NOT NULL,
    status character varying(20) DEFAULT 'draft'::character varying NOT NULL,
    services_snapshot jsonb,
    submitted_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    reviewed_by bigint,
    reviewed_at timestamp(0) without time zone,
    review_notes text,
    CONSTRAINT proposals_amount_positive CHECK ((quoted_amount > (0)::numeric)),
    CONSTRAINT proposals_status_check CHECK (((status)::text = ANY ((ARRAY['draft'::character varying, 'submitted'::character varying, 'approved'::character varying, 'rejected'::character varying, 'contracted'::character varying])::text[])))
);


ALTER TABLE public.proposals OWNER TO theseguro;

--
-- TOC entry 227 (class 1259 OID 27244)
-- Name: proposals_id_seq; Type: SEQUENCE; Schema: public; Owner: theseguro
--

CREATE SEQUENCE public.proposals_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.proposals_id_seq OWNER TO theseguro;

--
-- TOC entry 3943 (class 0 OID 0)
-- Dependencies: 227
-- Name: proposals_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: theseguro
--

ALTER SEQUENCE public.proposals_id_seq OWNED BY public.proposals.id;


--
-- TOC entry 232 (class 1259 OID 27300)
-- Name: service_requests; Type: TABLE; Schema: public; Owner: theseguro
--

CREATE TABLE public.service_requests (
    id bigint NOT NULL,
    insurance_contract_id bigint NOT NULL,
    service_id bigint NOT NULL,
    description text NOT NULL,
    location character varying(255) NOT NULL,
    status character varying(20) DEFAULT 'open'::character varying NOT NULL,
    assigned_to bigint,
    response text,
    completed_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT service_requests_status_check CHECK (((status)::text = ANY ((ARRAY['open'::character varying, 'in_progress'::character varying, 'completed'::character varying])::text[])))
);


ALTER TABLE public.service_requests OWNER TO theseguro;

--
-- TOC entry 231 (class 1259 OID 27299)
-- Name: service_requests_id_seq; Type: SEQUENCE; Schema: public; Owner: theseguro
--

CREATE SEQUENCE public.service_requests_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.service_requests_id_seq OWNER TO theseguro;

--
-- TOC entry 3944 (class 0 OID 0)
-- Dependencies: 231
-- Name: service_requests_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: theseguro
--

ALTER SEQUENCE public.service_requests_id_seq OWNED BY public.service_requests.id;


--
-- TOC entry 222 (class 1259 OID 27199)
-- Name: services; Type: TABLE; Schema: public; Owner: theseguro
--

CREATE TABLE public.services (
    id bigint NOT NULL,
    name character varying(100) NOT NULL,
    description text NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.services OWNER TO theseguro;

--
-- TOC entry 221 (class 1259 OID 27198)
-- Name: services_id_seq; Type: SEQUENCE; Schema: public; Owner: theseguro
--

CREATE SEQUENCE public.services_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.services_id_seq OWNER TO theseguro;

--
-- TOC entry 3945 (class 0 OID 0)
-- Dependencies: 221
-- Name: services_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: theseguro
--

ALTER SEQUENCE public.services_id_seq OWNED BY public.services.id;


--
-- TOC entry 217 (class 1259 OID 27163)
-- Name: users; Type: TABLE; Schema: public; Owner: theseguro
--

CREATE TABLE public.users (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    email character varying(255) NOT NULL,
    email_verified_at timestamp(0) without time zone,
    password character varying(255) NOT NULL,
    role character varying(20) DEFAULT 'cliente'::character varying NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    remember_token character varying(100),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT users_role_check CHECK (((role)::text = ANY ((ARRAY['cliente'::character varying, 'corretor'::character varying, 'administrador'::character varying])::text[])))
);


ALTER TABLE public.users OWNER TO theseguro;

--
-- TOC entry 216 (class 1259 OID 27162)
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: theseguro
--

CREATE SEQUENCE public.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.users_id_seq OWNER TO theseguro;

--
-- TOC entry 3946 (class 0 OID 0)
-- Dependencies: 216
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: theseguro
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- TOC entry 220 (class 1259 OID 27184)
-- Name: vehicles; Type: TABLE; Schema: public; Owner: theseguro
--

CREATE TABLE public.vehicles (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    license_plate character varying(7) NOT NULL,
    brand character varying(100) NOT NULL,
    model character varying(100) NOT NULL,
    manufacture_year smallint NOT NULL,
    model_year smallint,
    color character varying(50),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.vehicles OWNER TO theseguro;

--
-- TOC entry 219 (class 1259 OID 27183)
-- Name: vehicles_id_seq; Type: SEQUENCE; Schema: public; Owner: theseguro
--

CREATE SEQUENCE public.vehicles_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.vehicles_id_seq OWNER TO theseguro;

--
-- TOC entry 3947 (class 0 OID 0)
-- Dependencies: 219
-- Name: vehicles_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: theseguro
--

ALTER SEQUENCE public.vehicles_id_seq OWNED BY public.vehicles.id;


--
-- TOC entry 3725 (class 2604 OID 27285)
-- Name: insurance_contracts id; Type: DEFAULT; Schema: public; Owner: theseguro
--

ALTER TABLE ONLY public.insurance_contracts ALTER COLUMN id SET DEFAULT nextval('public.insurance_contracts_id_seq'::regclass);


--
-- TOC entry 3713 (class 2604 OID 27159)
-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: theseguro
--

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);


--
-- TOC entry 3722 (class 2604 OID 27227)
-- Name: plan_service id; Type: DEFAULT; Schema: public; Owner: theseguro
--

ALTER TABLE ONLY public.plan_service ALTER COLUMN id SET DEFAULT nextval('public.plan_service_id_seq'::regclass);


--
-- TOC entry 3720 (class 2604 OID 27214)
-- Name: plans id; Type: DEFAULT; Schema: public; Owner: theseguro
--

ALTER TABLE ONLY public.plans ALTER COLUMN id SET DEFAULT nextval('public.plans_id_seq'::regclass);


--
-- TOC entry 3723 (class 2604 OID 27248)
-- Name: proposals id; Type: DEFAULT; Schema: public; Owner: theseguro
--

ALTER TABLE ONLY public.proposals ALTER COLUMN id SET DEFAULT nextval('public.proposals_id_seq'::regclass);


--
-- TOC entry 3726 (class 2604 OID 27303)
-- Name: service_requests id; Type: DEFAULT; Schema: public; Owner: theseguro
--

ALTER TABLE ONLY public.service_requests ALTER COLUMN id SET DEFAULT nextval('public.service_requests_id_seq'::regclass);


--
-- TOC entry 3718 (class 2604 OID 27202)
-- Name: services id; Type: DEFAULT; Schema: public; Owner: theseguro
--

ALTER TABLE ONLY public.services ALTER COLUMN id SET DEFAULT nextval('public.services_id_seq'::regclass);


--
-- TOC entry 3714 (class 2604 OID 27166)
-- Name: users id; Type: DEFAULT; Schema: public; Owner: theseguro
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- TOC entry 3717 (class 2604 OID 27187)
-- Name: vehicles id; Type: DEFAULT; Schema: public; Owner: theseguro
--

ALTER TABLE ONLY public.vehicles ALTER COLUMN id SET DEFAULT nextval('public.vehicles_id_seq'::regclass);


--
-- TOC entry 3769 (class 2606 OID 27296)
-- Name: insurance_contracts insurance_contracts_contract_number_unique; Type: CONSTRAINT; Schema: public; Owner: theseguro
--

ALTER TABLE ONLY public.insurance_contracts
    ADD CONSTRAINT insurance_contracts_contract_number_unique UNIQUE (contract_number);


--
-- TOC entry 3772 (class 2606 OID 27287)
-- Name: insurance_contracts insurance_contracts_pkey; Type: CONSTRAINT; Schema: public; Owner: theseguro
--

ALTER TABLE ONLY public.insurance_contracts
    ADD CONSTRAINT insurance_contracts_pkey PRIMARY KEY (id);


--
-- TOC entry 3774 (class 2606 OID 27294)
-- Name: insurance_contracts insurance_contracts_proposal_id_unique; Type: CONSTRAINT; Schema: public; Owner: theseguro
--

ALTER TABLE ONLY public.insurance_contracts
    ADD CONSTRAINT insurance_contracts_proposal_id_unique UNIQUE (proposal_id);


--
-- TOC entry 3735 (class 2606 OID 27161)
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: public; Owner: theseguro
--

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- TOC entry 3741 (class 2606 OID 27182)
-- Name: password_reset_tokens password_reset_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: theseguro
--

ALTER TABLE ONLY public.password_reset_tokens
    ADD CONSTRAINT password_reset_tokens_pkey PRIMARY KEY (email);


--
-- TOC entry 3756 (class 2606 OID 27229)
-- Name: plan_service plan_service_pkey; Type: CONSTRAINT; Schema: public; Owner: theseguro
--

ALTER TABLE ONLY public.plan_service
    ADD CONSTRAINT plan_service_pkey PRIMARY KEY (id);


--
-- TOC entry 3759 (class 2606 OID 27241)
-- Name: plan_service plan_service_plan_id_service_id_unique; Type: CONSTRAINT; Schema: public; Owner: theseguro
--

ALTER TABLE ONLY public.plan_service
    ADD CONSTRAINT plan_service_plan_id_service_id_unique UNIQUE (plan_id, service_id);


--
-- TOC entry 3752 (class 2606 OID 27221)
-- Name: plans plans_name_unique; Type: CONSTRAINT; Schema: public; Owner: theseguro
--

ALTER TABLE ONLY public.plans
    ADD CONSTRAINT plans_name_unique UNIQUE (name);


--
-- TOC entry 3754 (class 2606 OID 27219)
-- Name: plans plans_pkey; Type: CONSTRAINT; Schema: public; Owner: theseguro
--

ALTER TABLE ONLY public.plans
    ADD CONSTRAINT plans_pkey PRIMARY KEY (id);


--
-- TOC entry 3762 (class 2606 OID 27253)
-- Name: proposals proposals_pkey; Type: CONSTRAINT; Schema: public; Owner: theseguro
--

ALTER TABLE ONLY public.proposals
    ADD CONSTRAINT proposals_pkey PRIMARY KEY (id);


--
-- TOC entry 3778 (class 2606 OID 27308)
-- Name: service_requests service_requests_pkey; Type: CONSTRAINT; Schema: public; Owner: theseguro
--

ALTER TABLE ONLY public.service_requests
    ADD CONSTRAINT service_requests_pkey PRIMARY KEY (id);


--
-- TOC entry 3748 (class 2606 OID 27209)
-- Name: services services_name_unique; Type: CONSTRAINT; Schema: public; Owner: theseguro
--

ALTER TABLE ONLY public.services
    ADD CONSTRAINT services_name_unique UNIQUE (name);


--
-- TOC entry 3750 (class 2606 OID 27207)
-- Name: services services_pkey; Type: CONSTRAINT; Schema: public; Owner: theseguro
--

ALTER TABLE ONLY public.services
    ADD CONSTRAINT services_pkey PRIMARY KEY (id);


--
-- TOC entry 3737 (class 2606 OID 27174)
-- Name: users users_email_unique; Type: CONSTRAINT; Schema: public; Owner: theseguro
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_unique UNIQUE (email);


--
-- TOC entry 3739 (class 2606 OID 27172)
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: theseguro
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- TOC entry 3743 (class 2606 OID 27197)
-- Name: vehicles vehicles_license_plate_unique; Type: CONSTRAINT; Schema: public; Owner: theseguro
--

ALTER TABLE ONLY public.vehicles
    ADD CONSTRAINT vehicles_license_plate_unique UNIQUE (license_plate);


--
-- TOC entry 3745 (class 2606 OID 27189)
-- Name: vehicles vehicles_pkey; Type: CONSTRAINT; Schema: public; Owner: theseguro
--

ALTER TABLE ONLY public.vehicles
    ADD CONSTRAINT vehicles_pkey PRIMARY KEY (id);


--
-- TOC entry 3770 (class 1259 OID 27297)
-- Name: insurance_contracts_ends_on_index; Type: INDEX; Schema: public; Owner: theseguro
--

CREATE INDEX insurance_contracts_ends_on_index ON public.insurance_contracts USING btree (ends_on);


--
-- TOC entry 3757 (class 1259 OID 27242)
-- Name: plan_service_plan_id_index; Type: INDEX; Schema: public; Owner: theseguro
--

CREATE INDEX plan_service_plan_id_index ON public.plan_service USING btree (plan_id);


--
-- TOC entry 3760 (class 1259 OID 27243)
-- Name: plan_service_service_id_index; Type: INDEX; Schema: public; Owner: theseguro
--

CREATE INDEX plan_service_service_id_index ON public.plan_service USING btree (service_id);


--
-- TOC entry 3763 (class 1259 OID 27272)
-- Name: proposals_plan_id_index; Type: INDEX; Schema: public; Owner: theseguro
--

CREATE INDEX proposals_plan_id_index ON public.proposals USING btree (plan_id);


--
-- TOC entry 3764 (class 1259 OID 27280)
-- Name: proposals_reviewed_by_index; Type: INDEX; Schema: public; Owner: theseguro
--

CREATE INDEX proposals_reviewed_by_index ON public.proposals USING btree (reviewed_by);


--
-- TOC entry 3765 (class 1259 OID 27270)
-- Name: proposals_status_created_at_index; Type: INDEX; Schema: public; Owner: theseguro
--

CREATE INDEX proposals_status_created_at_index ON public.proposals USING btree (status, created_at);


--
-- TOC entry 3766 (class 1259 OID 27269)
-- Name: proposals_user_id_status_index; Type: INDEX; Schema: public; Owner: theseguro
--

CREATE INDEX proposals_user_id_status_index ON public.proposals USING btree (user_id, status);


--
-- TOC entry 3767 (class 1259 OID 27271)
-- Name: proposals_vehicle_id_index; Type: INDEX; Schema: public; Owner: theseguro
--

CREATE INDEX proposals_vehicle_id_index ON public.proposals USING btree (vehicle_id);


--
-- TOC entry 3775 (class 1259 OID 27327)
-- Name: service_requests_assigned_to_index; Type: INDEX; Schema: public; Owner: theseguro
--

CREATE INDEX service_requests_assigned_to_index ON public.service_requests USING btree (assigned_to);


--
-- TOC entry 3776 (class 1259 OID 27325)
-- Name: service_requests_insurance_contract_id_index; Type: INDEX; Schema: public; Owner: theseguro
--

CREATE INDEX service_requests_insurance_contract_id_index ON public.service_requests USING btree (insurance_contract_id);


--
-- TOC entry 3779 (class 1259 OID 27326)
-- Name: service_requests_service_id_index; Type: INDEX; Schema: public; Owner: theseguro
--

CREATE INDEX service_requests_service_id_index ON public.service_requests USING btree (service_id);


--
-- TOC entry 3780 (class 1259 OID 27324)
-- Name: service_requests_status_created_at_index; Type: INDEX; Schema: public; Owner: theseguro
--

CREATE INDEX service_requests_status_created_at_index ON public.service_requests USING btree (status, created_at);


--
-- TOC entry 3746 (class 1259 OID 27195)
-- Name: vehicles_user_id_index; Type: INDEX; Schema: public; Owner: theseguro
--

CREATE INDEX vehicles_user_id_index ON public.vehicles USING btree (user_id);


--
-- TOC entry 3788 (class 2606 OID 27288)
-- Name: insurance_contracts insurance_contracts_proposal_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: theseguro
--

ALTER TABLE ONLY public.insurance_contracts
    ADD CONSTRAINT insurance_contracts_proposal_id_foreign FOREIGN KEY (proposal_id) REFERENCES public.proposals(id) ON DELETE RESTRICT;


--
-- TOC entry 3782 (class 2606 OID 27230)
-- Name: plan_service plan_service_plan_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: theseguro
--

ALTER TABLE ONLY public.plan_service
    ADD CONSTRAINT plan_service_plan_id_foreign FOREIGN KEY (plan_id) REFERENCES public.plans(id) ON DELETE RESTRICT;


--
-- TOC entry 3783 (class 2606 OID 27235)
-- Name: plan_service plan_service_service_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: theseguro
--

ALTER TABLE ONLY public.plan_service
    ADD CONSTRAINT plan_service_service_id_foreign FOREIGN KEY (service_id) REFERENCES public.services(id) ON DELETE RESTRICT;


--
-- TOC entry 3784 (class 2606 OID 27264)
-- Name: proposals proposals_plan_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: theseguro
--

ALTER TABLE ONLY public.proposals
    ADD CONSTRAINT proposals_plan_id_foreign FOREIGN KEY (plan_id) REFERENCES public.plans(id) ON DELETE RESTRICT;


--
-- TOC entry 3785 (class 2606 OID 27275)
-- Name: proposals proposals_reviewed_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: theseguro
--

ALTER TABLE ONLY public.proposals
    ADD CONSTRAINT proposals_reviewed_by_foreign FOREIGN KEY (reviewed_by) REFERENCES public.users(id) ON DELETE RESTRICT;


--
-- TOC entry 3786 (class 2606 OID 27254)
-- Name: proposals proposals_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: theseguro
--

ALTER TABLE ONLY public.proposals
    ADD CONSTRAINT proposals_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE RESTRICT;


--
-- TOC entry 3787 (class 2606 OID 27259)
-- Name: proposals proposals_vehicle_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: theseguro
--

ALTER TABLE ONLY public.proposals
    ADD CONSTRAINT proposals_vehicle_id_foreign FOREIGN KEY (vehicle_id) REFERENCES public.vehicles(id) ON DELETE RESTRICT;


--
-- TOC entry 3789 (class 2606 OID 27319)
-- Name: service_requests service_requests_assigned_to_foreign; Type: FK CONSTRAINT; Schema: public; Owner: theseguro
--

ALTER TABLE ONLY public.service_requests
    ADD CONSTRAINT service_requests_assigned_to_foreign FOREIGN KEY (assigned_to) REFERENCES public.users(id) ON DELETE RESTRICT;


--
-- TOC entry 3790 (class 2606 OID 27309)
-- Name: service_requests service_requests_insurance_contract_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: theseguro
--

ALTER TABLE ONLY public.service_requests
    ADD CONSTRAINT service_requests_insurance_contract_id_foreign FOREIGN KEY (insurance_contract_id) REFERENCES public.insurance_contracts(id) ON DELETE RESTRICT;


--
-- TOC entry 3791 (class 2606 OID 27314)
-- Name: service_requests service_requests_service_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: theseguro
--

ALTER TABLE ONLY public.service_requests
    ADD CONSTRAINT service_requests_service_id_foreign FOREIGN KEY (service_id) REFERENCES public.services(id) ON DELETE RESTRICT;


--
-- TOC entry 3781 (class 2606 OID 27190)
-- Name: vehicles vehicles_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: theseguro
--

ALTER TABLE ONLY public.vehicles
    ADD CONSTRAINT vehicles_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE RESTRICT;


-- Completed on 2026-11-13 18:38:10 -03

--
-- PostgreSQL database dump complete
--

\unrestrict gWwLWqiaelEJVHKmWc9NMBIWYFMcAYxKsI0bamhDhdevdhR8INt6TI2IIF4phOt

