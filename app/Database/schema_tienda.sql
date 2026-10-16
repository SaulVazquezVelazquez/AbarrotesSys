--
-- PostgreSQL database dump
--



-- Dumped from database version 16.13 (Homebrew)
-- Dumped by pg_dump version 16.13 (Homebrew)

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
-- Name: unaccent; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS unaccent WITH SCHEMA public;


--
-- Name: EXTENSION unaccent; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION unaccent IS 'text search dictionary that removes accents';


SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: auditoria; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.auditoria (
    id_auditoria integer NOT NULL,
    id_usuario integer,
    accion character varying(60) NOT NULL,
    entidad character varying(60),
    id_entidad integer,
    detalle text,
    fecha_creacion timestamp without time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.auditoria OWNER TO postgres;

--
-- Name: auditoria_id_auditoria_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.auditoria_id_auditoria_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.auditoria_id_auditoria_seq OWNER TO postgres;

--
-- Name: auditoria_id_auditoria_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.auditoria_id_auditoria_seq OWNED BY public.auditoria.id_auditoria;


--
-- Name: clientes; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.clientes (
    id_cliente integer NOT NULL,
    nombre_completo character varying(150) NOT NULL,
    telefono character varying(30) NOT NULL,
    direccion character varying(255),
    foto character varying(255),
    identificacion character varying(255),
    pin_hash character varying(255),
    limite_credito numeric(10,2) DEFAULT 0 NOT NULL,
    plazo_dias integer DEFAULT 15 NOT NULL,
    estatus character varying(20) DEFAULT 'ACTIVO'::character varying NOT NULL,
    motivo_bloqueo character varying(255),
    notas text,
    fecha_creacion timestamp without time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.clientes OWNER TO postgres;

--
-- Name: clientes_id_cliente_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.clientes_id_cliente_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.clientes_id_cliente_seq OWNER TO postgres;

--
-- Name: clientes_id_cliente_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.clientes_id_cliente_seq OWNED BY public.clientes.id_cliente;


--
-- Name: configuracion; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.configuracion (
    clave character varying(60) NOT NULL,
    valor character varying(255) NOT NULL
);


ALTER TABLE public.configuracion OWNER TO postgres;

--
-- Name: contactos_referencia; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.contactos_referencia (
    id_contacto integer NOT NULL,
    id_cliente integer NOT NULL,
    nombre character varying(150) NOT NULL,
    telefono character varying(30),
    relacion character varying(80)
);


ALTER TABLE public.contactos_referencia OWNER TO postgres;

--
-- Name: contactos_referencia_id_contacto_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.contactos_referencia_id_contacto_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.contactos_referencia_id_contacto_seq OWNER TO postgres;

--
-- Name: contactos_referencia_id_contacto_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.contactos_referencia_id_contacto_seq OWNED BY public.contactos_referencia.id_contacto;


--
-- Name: detalle_solicitud_compra; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.detalle_solicitud_compra (
    id_detalle integer NOT NULL,
    id_solicitud integer NOT NULL,
    id_producto integer,
    nombre_producto character varying(150) NOT NULL,
    cantidad numeric(10,2) DEFAULT 1 NOT NULL,
    precio_unitario numeric(10,2) NOT NULL,
    subtotal numeric(10,2) NOT NULL
);


ALTER TABLE public.detalle_solicitud_compra OWNER TO postgres;

--
-- Name: detalle_solicitud_compra_id_detalle_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.detalle_solicitud_compra_id_detalle_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.detalle_solicitud_compra_id_detalle_seq OWNER TO postgres;

--
-- Name: detalle_solicitud_compra_id_detalle_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.detalle_solicitud_compra_id_detalle_seq OWNED BY public.detalle_solicitud_compra.id_detalle;


--
-- Name: detalle_venta; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.detalle_venta (
    id_detalle integer NOT NULL,
    id_venta integer NOT NULL,
    id_producto integer,
    descripcion_libre character varying(255),
    cantidad numeric(12,3) DEFAULT 1 NOT NULL,
    precio_unitario numeric(10,2) NOT NULL,
    subtotal numeric(10,2) NOT NULL
);


ALTER TABLE public.detalle_venta OWNER TO postgres;

--
-- Name: detalle_venta_id_detalle_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.detalle_venta_id_detalle_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.detalle_venta_id_detalle_seq OWNER TO postgres;

--
-- Name: detalle_venta_id_detalle_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.detalle_venta_id_detalle_seq OWNED BY public.detalle_venta.id_detalle;


--
-- Name: mensajes_cobranza; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.mensajes_cobranza (
    id_mensaje integer NOT NULL,
    id_cliente integer NOT NULL,
    id_venta integer,
    tipo character varying(30) NOT NULL,
    canal character varying(20) DEFAULT 'WHATSAPP'::character varying NOT NULL,
    texto_final text,
    monto_reportado numeric(10,2),
    fecha_programada timestamp without time zone NOT NULL,
    estatus character varying(20) DEFAULT 'PENDIENTE'::character varying NOT NULL,
    fecha_envio timestamp without time zone
);


ALTER TABLE public.mensajes_cobranza OWNER TO postgres;

--
-- Name: mensajes_cobranza_id_mensaje_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.mensajes_cobranza_id_mensaje_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.mensajes_cobranza_id_mensaje_seq OWNER TO postgres;

--
-- Name: mensajes_cobranza_id_mensaje_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.mensajes_cobranza_id_mensaje_seq OWNED BY public.mensajes_cobranza.id_mensaje;


--
-- Name: pagos_abonos; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.pagos_abonos (
    id_pago integer NOT NULL,
    id_cliente integer NOT NULL,
    id_venta integer,
    monto numeric(10,2) NOT NULL,
    metodo_pago character varying(30) DEFAULT 'EFECTIVO'::character varying NOT NULL,
    referencia_pago character varying(150),
    ruta_comprobante character varying(255),
    id_usuario integer,
    fecha_pago timestamp without time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.pagos_abonos OWNER TO postgres;

--
-- Name: pagos_abonos_id_pago_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.pagos_abonos_id_pago_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.pagos_abonos_id_pago_seq OWNER TO postgres;

--
-- Name: pagos_abonos_id_pago_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.pagos_abonos_id_pago_seq OWNED BY public.pagos_abonos.id_pago;


--
-- Name: plantillas_cobranza; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.plantillas_cobranza (
    id_plantilla integer NOT NULL,
    clave character varying(40) NOT NULL,
    cuerpo text NOT NULL,
    canal character varying(20) DEFAULT 'WHATSAPP'::character varying NOT NULL
);


ALTER TABLE public.plantillas_cobranza OWNER TO postgres;

--
-- Name: plantillas_cobranza_id_plantilla_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.plantillas_cobranza_id_plantilla_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.plantillas_cobranza_id_plantilla_seq OWNER TO postgres;

--
-- Name: plantillas_cobranza_id_plantilla_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.plantillas_cobranza_id_plantilla_seq OWNED BY public.plantillas_cobranza.id_plantilla;


--
-- Name: pos_cortes; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.pos_cortes (
    id_corte integer NOT NULL,
    id_usuario integer,
    fecha timestamp without time zone DEFAULT now() NOT NULL,
    efectivo_esperado numeric(12,2) NOT NULL,
    efectivo_contado numeric(12,2) NOT NULL,
    total numeric(12,2) NOT NULL,
    abarrotes numeric(12,2) NOT NULL,
    verduleria numeric(12,2) NOT NULL,
    desglose_json text NOT NULL
);


ALTER TABLE public.pos_cortes OWNER TO postgres;

--
-- Name: pos_cortes_id_corte_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.pos_cortes_id_corte_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.pos_cortes_id_corte_seq OWNER TO postgres;

--
-- Name: pos_cortes_id_corte_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.pos_cortes_id_corte_seq OWNED BY public.pos_cortes.id_corte;


--
-- Name: pos_detalle; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.pos_detalle (
    id_detalle integer NOT NULL,
    id_venta integer NOT NULL,
    id_producto integer NOT NULL,
    cantidad numeric(12,3) NOT NULL,
    precio_unitario numeric(12,2) NOT NULL,
    subtotal numeric(12,2) NOT NULL,
    departamento character varying(20) NOT NULL
);


ALTER TABLE public.pos_detalle OWNER TO postgres;

--
-- Name: pos_detalle_id_detalle_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.pos_detalle_id_detalle_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.pos_detalle_id_detalle_seq OWNER TO postgres;

--
-- Name: pos_detalle_id_detalle_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.pos_detalle_id_detalle_seq OWNED BY public.pos_detalle.id_detalle;


--
-- Name: pos_entradas; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.pos_entradas (
    id_entrada integer NOT NULL,
    id_producto integer NOT NULL,
    id_usuario integer,
    empaques numeric(12,3) NOT NULL,
    unidades_por_empaque numeric(12,3) NOT NULL,
    cantidad_base numeric(12,3) NOT NULL,
    fecha timestamp without time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.pos_entradas OWNER TO postgres;

--
-- Name: pos_entradas_id_entrada_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.pos_entradas_id_entrada_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.pos_entradas_id_entrada_seq OWNER TO postgres;

--
-- Name: pos_entradas_id_entrada_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.pos_entradas_id_entrada_seq OWNED BY public.pos_entradas.id_entrada;


--
-- Name: pos_esperas; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.pos_esperas (
    id_espera integer NOT NULL,
    id_usuario integer,
    referencia character varying(80),
    items_json text NOT NULL,
    fecha_creacion timestamp without time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.pos_esperas OWNER TO postgres;

--
-- Name: pos_esperas_id_espera_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.pos_esperas_id_espera_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.pos_esperas_id_espera_seq OWNER TO postgres;

--
-- Name: pos_esperas_id_espera_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.pos_esperas_id_espera_seq OWNED BY public.pos_esperas.id_espera;


--
-- Name: pos_mermas; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.pos_mermas (
    id_merma integer NOT NULL,
    id_producto integer NOT NULL,
    id_usuario integer,
    cantidad numeric(12,3) NOT NULL,
    motivo character varying(200),
    fecha timestamp without time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.pos_mermas OWNER TO postgres;

--
-- Name: pos_mermas_id_merma_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.pos_mermas_id_merma_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.pos_mermas_id_merma_seq OWNER TO postgres;

--
-- Name: pos_mermas_id_merma_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.pos_mermas_id_merma_seq OWNED BY public.pos_mermas.id_merma;


--
-- Name: pos_pagos; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.pos_pagos (
    id_pago integer NOT NULL,
    id_venta integer NOT NULL,
    metodo character varying(20) NOT NULL,
    monto numeric(12,2) NOT NULL
);


ALTER TABLE public.pos_pagos OWNER TO postgres;

--
-- Name: pos_pagos_id_pago_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.pos_pagos_id_pago_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.pos_pagos_id_pago_seq OWNER TO postgres;

--
-- Name: pos_pagos_id_pago_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.pos_pagos_id_pago_seq OWNED BY public.pos_pagos.id_pago;


--
-- Name: pos_ventas; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.pos_ventas (
    id_venta integer NOT NULL,
    id_usuario integer,
    id_cliente integer,
    fecha_venta timestamp without time zone DEFAULT now() NOT NULL,
    total numeric(12,2) NOT NULL,
    subtotal_abarrotes numeric(12,2) DEFAULT 0 NOT NULL,
    subtotal_verduleria numeric(12,2) DEFAULT 0 NOT NULL,
    metodo_pago character varying(20) NOT NULL,
    efectivo_recibido numeric(12,2) DEFAULT 0 NOT NULL,
    cambio numeric(12,2) DEFAULT 0 NOT NULL
);


ALTER TABLE public.pos_ventas OWNER TO postgres;

--
-- Name: pos_ventas_id_venta_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.pos_ventas_id_venta_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.pos_ventas_id_venta_seq OWNER TO postgres;

--
-- Name: pos_ventas_id_venta_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.pos_ventas_id_venta_seq OWNED BY public.pos_ventas.id_venta;


--
-- Name: producto_imagenes; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.producto_imagenes (
    id_imagen integer NOT NULL,
    id_producto integer NOT NULL,
    ruta_imagen character varying(255) NOT NULL,
    orden integer DEFAULT 0 NOT NULL,
    fecha_creacion timestamp without time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.producto_imagenes OWNER TO postgres;

--
-- Name: producto_imagenes_id_imagen_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.producto_imagenes_id_imagen_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.producto_imagenes_id_imagen_seq OWNER TO postgres;

--
-- Name: producto_imagenes_id_imagen_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.producto_imagenes_id_imagen_seq OWNED BY public.producto_imagenes.id_imagen;


--
-- Name: productos; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.productos (
    id_producto integer NOT NULL,
    nombre character varying(150) NOT NULL,
    descripcion text,
    ruta_imagen character varying(255),
    categoria character varying(80),
    precio numeric(10,2) NOT NULL,
    stock numeric(12,3),
    activo smallint DEFAULT 1 NOT NULL,
    fecha_creacion timestamp without time zone DEFAULT now() NOT NULL,
    codigo_barras character varying(32),
    plu character varying(8),
    departamento character varying(20) DEFAULT 'ABARROTES'::character varying NOT NULL,
    unidad_base character varying(12) DEFAULT 'PZA'::character varying NOT NULL,
    unidades_por_empaque numeric(10,3) DEFAULT 1 NOT NULL,
    stock_minimo numeric(12,3) DEFAULT 0 NOT NULL
);


ALTER TABLE public.productos OWNER TO postgres;

--
-- Name: productos_id_producto_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.productos_id_producto_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.productos_id_producto_seq OWNER TO postgres;

--
-- Name: productos_id_producto_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.productos_id_producto_seq OWNED BY public.productos.id_producto;


--
-- Name: promesas_pago; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.promesas_pago (
    id_promesa integer NOT NULL,
    id_cliente integer NOT NULL,
    id_venta integer,
    monto_prometido numeric(10,2) NOT NULL,
    fecha_prometida date NOT NULL,
    estatus character varying(20) DEFAULT 'PENDIENTE'::character varying NOT NULL,
    notas character varying(255),
    fecha_creacion timestamp without time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.promesas_pago OWNER TO postgres;

--
-- Name: promesas_pago_id_promesa_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.promesas_pago_id_promesa_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.promesas_pago_id_promesa_seq OWNER TO postgres;

--
-- Name: promesas_pago_id_promesa_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.promesas_pago_id_promesa_seq OWNED BY public.promesas_pago.id_promesa;


--
-- Name: seguimientos_cobranza; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.seguimientos_cobranza (
    id_seguimiento integer NOT NULL,
    id_cliente integer NOT NULL,
    id_venta integer,
    id_usuario integer,
    resultado character varying(30) NOT NULL,
    detalle text,
    proxima_fecha date,
    fecha_creacion timestamp without time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.seguimientos_cobranza OWNER TO postgres;

--
-- Name: seguimientos_cobranza_id_seguimiento_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.seguimientos_cobranza_id_seguimiento_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.seguimientos_cobranza_id_seguimiento_seq OWNER TO postgres;

--
-- Name: seguimientos_cobranza_id_seguimiento_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.seguimientos_cobranza_id_seguimiento_seq OWNED BY public.seguimientos_cobranza.id_seguimiento;


--
-- Name: solicitudes_compra; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.solicitudes_compra (
    id_solicitud integer NOT NULL,
    id_cliente integer,
    nombre_cliente character varying(150) NOT NULL,
    telefono character varying(30) NOT NULL,
    email character varying(150),
    notas text,
    total_estimado numeric(10,2) DEFAULT 0 NOT NULL,
    estatus character varying(20) DEFAULT 'PENDIENTE'::character varying NOT NULL,
    motivo_rechazo character varying(255),
    id_venta integer,
    ip_origen character varying(60),
    token_seguimiento character varying(64),
    fecha_creacion timestamp without time zone DEFAULT now() NOT NULL,
    fecha_resolucion timestamp without time zone
);


ALTER TABLE public.solicitudes_compra OWNER TO postgres;

--
-- Name: solicitudes_compra_id_solicitud_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.solicitudes_compra_id_solicitud_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.solicitudes_compra_id_solicitud_seq OWNER TO postgres;

--
-- Name: solicitudes_compra_id_solicitud_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.solicitudes_compra_id_solicitud_seq OWNED BY public.solicitudes_compra.id_solicitud;


--
-- Name: usuarios; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.usuarios (
    id_usuario integer NOT NULL,
    nombre_completo character varying(150) NOT NULL,
    usuario character varying(60) NOT NULL,
    password_hash character varying(255) NOT NULL,
    rol character varying(20) DEFAULT 'CAJERO'::character varying NOT NULL,
    activo smallint DEFAULT 1 NOT NULL,
    fecha_creacion timestamp without time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.usuarios OWNER TO postgres;

--
-- Name: usuarios_id_usuario_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.usuarios_id_usuario_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.usuarios_id_usuario_seq OWNER TO postgres;

--
-- Name: usuarios_id_usuario_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.usuarios_id_usuario_seq OWNED BY public.usuarios.id_usuario;


--
-- Name: ventas; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.ventas (
    id_venta integer NOT NULL,
    id_cliente integer NOT NULL,
    id_usuario integer,
    fecha_venta timestamp without time zone DEFAULT now() NOT NULL,
    fecha_limite_pago date,
    subtotal numeric(10,2) DEFAULT 0 NOT NULL,
    total numeric(10,2) DEFAULT 0 NOT NULL,
    saldo_pendiente numeric(10,2) DEFAULT 0 NOT NULL,
    estatus character varying(20) DEFAULT 'PENDIENTE'::character varying NOT NULL,
    metodo_confirmacion character varying(20) DEFAULT 'NINGUNO'::character varying,
    confirmacion_valor text,
    ip_registro character varying(60),
    fecha_creacion timestamp without time zone DEFAULT now() NOT NULL,
    pickup_at timestamp without time zone,
    pedido_estatus character varying(20) DEFAULT 'NO_APLICA'::character varying NOT NULL,
    notas_pedido character varying(500)
);


ALTER TABLE public.ventas OWNER TO postgres;

--
-- Name: ventas_id_venta_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.ventas_id_venta_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.ventas_id_venta_seq OWNER TO postgres;

--
-- Name: ventas_id_venta_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.ventas_id_venta_seq OWNED BY public.ventas.id_venta;


--
-- Name: vista_aging; Type: VIEW; Schema: public; Owner: postgres
--

CREATE VIEW public.vista_aging AS
 SELECT v.id_venta,
    c.id_cliente,
    c.nombre_completo,
    c.telefono,
    v.saldo_pendiente,
    v.fecha_limite_pago,
    (CURRENT_DATE - v.fecha_limite_pago) AS dias_atraso,
        CASE
            WHEN (v.fecha_limite_pago >= CURRENT_DATE) THEN 'AL_CORRIENTE'::text
            WHEN (((CURRENT_DATE - v.fecha_limite_pago) >= 1) AND ((CURRENT_DATE - v.fecha_limite_pago) <= 15)) THEN '1-15_DIAS'::text
            WHEN (((CURRENT_DATE - v.fecha_limite_pago) >= 16) AND ((CURRENT_DATE - v.fecha_limite_pago) <= 30)) THEN '16-30_DIAS'::text
            ELSE 'MAS_30_DIAS'::text
        END AS rango_atraso
   FROM (public.ventas v
     JOIN public.clientes c ON ((c.id_cliente = v.id_cliente)))
  WHERE (((v.estatus)::text = ANY ((ARRAY['PENDIENTE'::character varying, 'PARCIAL'::character varying, 'VENCIDA'::character varying])::text[])) AND (v.saldo_pendiente > (0)::numeric));


ALTER VIEW public.vista_aging OWNER TO postgres;

--
-- Name: vista_cartera_por_cobrar; Type: VIEW; Schema: public; Owner: postgres
--

CREATE VIEW public.vista_cartera_por_cobrar AS
 SELECT fecha_limite_pago AS dia,
    sum(saldo_pendiente) AS total_por_cobrar
   FROM public.ventas
  WHERE ((estatus)::text = ANY ((ARRAY['PENDIENTE'::character varying, 'PARCIAL'::character varying, 'VENCIDA'::character varying])::text[]))
  GROUP BY fecha_limite_pago
  ORDER BY fecha_limite_pago;


ALTER VIEW public.vista_cartera_por_cobrar OWNER TO postgres;

--
-- Name: vista_flujo_caja; Type: VIEW; Schema: public; Owner: postgres
--

CREATE VIEW public.vista_flujo_caja AS
 SELECT date(fecha_pago) AS dia,
    sum(monto) AS total_cobrado
   FROM public.pagos_abonos
  GROUP BY (date(fecha_pago))
  ORDER BY (date(fecha_pago)) DESC;


ALTER VIEW public.vista_flujo_caja OWNER TO postgres;

--
-- Name: vista_saldo_cliente; Type: VIEW; Schema: public; Owner: postgres
--

CREATE VIEW public.vista_saldo_cliente AS
 SELECT c.id_cliente,
    c.nombre_completo,
    c.telefono,
    c.limite_credito,
    c.estatus,
    COALESCE(sum(v.saldo_pendiente) FILTER (WHERE ((v.estatus)::text = ANY ((ARRAY['PENDIENTE'::character varying, 'PARCIAL'::character varying, 'VENCIDA'::character varying])::text[]))), (0)::numeric) AS saldo_actual,
    (c.limite_credito - COALESCE(sum(v.saldo_pendiente) FILTER (WHERE ((v.estatus)::text = ANY ((ARRAY['PENDIENTE'::character varying, 'PARCIAL'::character varying, 'VENCIDA'::character varying])::text[]))), (0)::numeric)) AS credito_disponible,
    min(v.fecha_limite_pago) FILTER (WHERE ((v.estatus)::text = ANY ((ARRAY['PENDIENTE'::character varying, 'PARCIAL'::character varying, 'VENCIDA'::character varying])::text[]))) AS proxima_fecha_limite,
        CASE
            WHEN ((c.estatus)::text = 'BLOQUEADO'::text) THEN 'ROJO'::text
            WHEN (min(v.fecha_limite_pago) FILTER (WHERE ((v.estatus)::text = ANY ((ARRAY['PENDIENTE'::character varying, 'PARCIAL'::character varying, 'VENCIDA'::character varying])::text[]))) < CURRENT_DATE) THEN 'ROJO'::text
            WHEN (min(v.fecha_limite_pago) FILTER (WHERE ((v.estatus)::text = ANY ((ARRAY['PENDIENTE'::character varying, 'PARCIAL'::character varying, 'VENCIDA'::character varying])::text[]))) <= (CURRENT_DATE + '3 days'::interval)) THEN 'AMARILLO'::text
            WHEN (COALESCE(sum(v.saldo_pendiente) FILTER (WHERE ((v.estatus)::text = ANY ((ARRAY['PENDIENTE'::character varying, 'PARCIAL'::character varying, 'VENCIDA'::character varying])::text[]))), (0)::numeric) >= (c.limite_credito * 0.9)) THEN 'AMARILLO'::text
            ELSE 'VERDE'::text
        END AS semaforo
   FROM (public.clientes c
     LEFT JOIN public.ventas v ON ((v.id_cliente = c.id_cliente)))
  GROUP BY c.id_cliente, c.nombre_completo, c.telefono, c.limite_credito, c.estatus;


ALTER VIEW public.vista_saldo_cliente OWNER TO postgres;

--
-- Name: vista_scoring_cliente; Type: VIEW; Schema: public; Owner: postgres
--

CREATE VIEW public.vista_scoring_cliente AS
 SELECT c.id_cliente,
    c.nombre_completo,
    count(v.id_venta) FILTER (WHERE ((v.estatus)::text = 'PAGADA'::text)) AS ventas_pagadas,
    count(v.id_venta) FILTER (WHERE ((v.estatus)::text = 'VENCIDA'::text)) AS ventas_vencidas,
    round(((100.0 * (count(v.id_venta) FILTER (WHERE ((v.estatus)::text = 'PAGADA'::text)))::numeric) / (NULLIF(count(v.id_venta) FILTER (WHERE ((v.estatus)::text = ANY ((ARRAY['PAGADA'::character varying, 'VENCIDA'::character varying])::text[]))), 0))::numeric), 1) AS porcentaje_puntualidad
   FROM (public.clientes c
     LEFT JOIN public.ventas v ON ((v.id_cliente = c.id_cliente)))
  GROUP BY c.id_cliente, c.nombre_completo;


ALTER VIEW public.vista_scoring_cliente OWNER TO postgres;

--
-- Name: auditoria id_auditoria; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.auditoria ALTER COLUMN id_auditoria SET DEFAULT nextval('public.auditoria_id_auditoria_seq'::regclass);


--
-- Name: clientes id_cliente; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.clientes ALTER COLUMN id_cliente SET DEFAULT nextval('public.clientes_id_cliente_seq'::regclass);


--
-- Name: contactos_referencia id_contacto; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.contactos_referencia ALTER COLUMN id_contacto SET DEFAULT nextval('public.contactos_referencia_id_contacto_seq'::regclass);


--
-- Name: detalle_solicitud_compra id_detalle; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.detalle_solicitud_compra ALTER COLUMN id_detalle SET DEFAULT nextval('public.detalle_solicitud_compra_id_detalle_seq'::regclass);


--
-- Name: detalle_venta id_detalle; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.detalle_venta ALTER COLUMN id_detalle SET DEFAULT nextval('public.detalle_venta_id_detalle_seq'::regclass);


--
-- Name: mensajes_cobranza id_mensaje; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mensajes_cobranza ALTER COLUMN id_mensaje SET DEFAULT nextval('public.mensajes_cobranza_id_mensaje_seq'::regclass);


--
-- Name: pagos_abonos id_pago; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pagos_abonos ALTER COLUMN id_pago SET DEFAULT nextval('public.pagos_abonos_id_pago_seq'::regclass);


--
-- Name: plantillas_cobranza id_plantilla; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.plantillas_cobranza ALTER COLUMN id_plantilla SET DEFAULT nextval('public.plantillas_cobranza_id_plantilla_seq'::regclass);


--
-- Name: pos_cortes id_corte; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pos_cortes ALTER COLUMN id_corte SET DEFAULT nextval('public.pos_cortes_id_corte_seq'::regclass);


--
-- Name: pos_detalle id_detalle; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pos_detalle ALTER COLUMN id_detalle SET DEFAULT nextval('public.pos_detalle_id_detalle_seq'::regclass);


--
-- Name: pos_entradas id_entrada; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pos_entradas ALTER COLUMN id_entrada SET DEFAULT nextval('public.pos_entradas_id_entrada_seq'::regclass);


--
-- Name: pos_esperas id_espera; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pos_esperas ALTER COLUMN id_espera SET DEFAULT nextval('public.pos_esperas_id_espera_seq'::regclass);


--
-- Name: pos_mermas id_merma; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pos_mermas ALTER COLUMN id_merma SET DEFAULT nextval('public.pos_mermas_id_merma_seq'::regclass);


--
-- Name: pos_pagos id_pago; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pos_pagos ALTER COLUMN id_pago SET DEFAULT nextval('public.pos_pagos_id_pago_seq'::regclass);


--
-- Name: pos_ventas id_venta; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pos_ventas ALTER COLUMN id_venta SET DEFAULT nextval('public.pos_ventas_id_venta_seq'::regclass);


--
-- Name: producto_imagenes id_imagen; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.producto_imagenes ALTER COLUMN id_imagen SET DEFAULT nextval('public.producto_imagenes_id_imagen_seq'::regclass);


--
-- Name: productos id_producto; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.productos ALTER COLUMN id_producto SET DEFAULT nextval('public.productos_id_producto_seq'::regclass);


--
-- Name: promesas_pago id_promesa; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.promesas_pago ALTER COLUMN id_promesa SET DEFAULT nextval('public.promesas_pago_id_promesa_seq'::regclass);


--
-- Name: seguimientos_cobranza id_seguimiento; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.seguimientos_cobranza ALTER COLUMN id_seguimiento SET DEFAULT nextval('public.seguimientos_cobranza_id_seguimiento_seq'::regclass);


--
-- Name: solicitudes_compra id_solicitud; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.solicitudes_compra ALTER COLUMN id_solicitud SET DEFAULT nextval('public.solicitudes_compra_id_solicitud_seq'::regclass);


--
-- Name: usuarios id_usuario; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.usuarios ALTER COLUMN id_usuario SET DEFAULT nextval('public.usuarios_id_usuario_seq'::regclass);


--
-- Name: ventas id_venta; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ventas ALTER COLUMN id_venta SET DEFAULT nextval('public.ventas_id_venta_seq'::regclass);


--
-- Name: auditoria auditoria_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.auditoria
    ADD CONSTRAINT auditoria_pkey PRIMARY KEY (id_auditoria);


--
-- Name: clientes clientes_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.clientes
    ADD CONSTRAINT clientes_pkey PRIMARY KEY (id_cliente);


--
-- Name: clientes clientes_telefono_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.clientes
    ADD CONSTRAINT clientes_telefono_key UNIQUE (telefono);


--
-- Name: configuracion configuracion_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.configuracion
    ADD CONSTRAINT configuracion_pkey PRIMARY KEY (clave);


--
-- Name: contactos_referencia contactos_referencia_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.contactos_referencia
    ADD CONSTRAINT contactos_referencia_pkey PRIMARY KEY (id_contacto);


--
-- Name: detalle_solicitud_compra detalle_solicitud_compra_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.detalle_solicitud_compra
    ADD CONSTRAINT detalle_solicitud_compra_pkey PRIMARY KEY (id_detalle);


--
-- Name: detalle_venta detalle_venta_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.detalle_venta
    ADD CONSTRAINT detalle_venta_pkey PRIMARY KEY (id_detalle);


--
-- Name: mensajes_cobranza mensajes_cobranza_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mensajes_cobranza
    ADD CONSTRAINT mensajes_cobranza_pkey PRIMARY KEY (id_mensaje);


--
-- Name: pagos_abonos pagos_abonos_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pagos_abonos
    ADD CONSTRAINT pagos_abonos_pkey PRIMARY KEY (id_pago);


--
-- Name: plantillas_cobranza plantillas_cobranza_clave_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.plantillas_cobranza
    ADD CONSTRAINT plantillas_cobranza_clave_key UNIQUE (clave);


--
-- Name: plantillas_cobranza plantillas_cobranza_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.plantillas_cobranza
    ADD CONSTRAINT plantillas_cobranza_pkey PRIMARY KEY (id_plantilla);


--
-- Name: pos_cortes pos_cortes_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pos_cortes
    ADD CONSTRAINT pos_cortes_pkey PRIMARY KEY (id_corte);


--
-- Name: pos_detalle pos_detalle_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pos_detalle
    ADD CONSTRAINT pos_detalle_pkey PRIMARY KEY (id_detalle);


--
-- Name: pos_entradas pos_entradas_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pos_entradas
    ADD CONSTRAINT pos_entradas_pkey PRIMARY KEY (id_entrada);


--
-- Name: pos_esperas pos_esperas_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pos_esperas
    ADD CONSTRAINT pos_esperas_pkey PRIMARY KEY (id_espera);


--
-- Name: pos_mermas pos_mermas_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pos_mermas
    ADD CONSTRAINT pos_mermas_pkey PRIMARY KEY (id_merma);


--
-- Name: pos_pagos pos_pagos_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pos_pagos
    ADD CONSTRAINT pos_pagos_pkey PRIMARY KEY (id_pago);


--
-- Name: pos_ventas pos_ventas_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pos_ventas
    ADD CONSTRAINT pos_ventas_pkey PRIMARY KEY (id_venta);


--
-- Name: producto_imagenes producto_imagenes_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.producto_imagenes
    ADD CONSTRAINT producto_imagenes_pkey PRIMARY KEY (id_imagen);


--
-- Name: productos productos_codigo_barras_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.productos
    ADD CONSTRAINT productos_codigo_barras_key UNIQUE (codigo_barras);


--
-- Name: productos productos_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.productos
    ADD CONSTRAINT productos_pkey PRIMARY KEY (id_producto);


--
-- Name: promesas_pago promesas_pago_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.promesas_pago
    ADD CONSTRAINT promesas_pago_pkey PRIMARY KEY (id_promesa);


--
-- Name: seguimientos_cobranza seguimientos_cobranza_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.seguimientos_cobranza
    ADD CONSTRAINT seguimientos_cobranza_pkey PRIMARY KEY (id_seguimiento);


--
-- Name: solicitudes_compra solicitudes_compra_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.solicitudes_compra
    ADD CONSTRAINT solicitudes_compra_pkey PRIMARY KEY (id_solicitud);


--
-- Name: solicitudes_compra solicitudes_compra_token_seguimiento_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.solicitudes_compra
    ADD CONSTRAINT solicitudes_compra_token_seguimiento_key UNIQUE (token_seguimiento);


--
-- Name: usuarios usuarios_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.usuarios
    ADD CONSTRAINT usuarios_pkey PRIMARY KEY (id_usuario);


--
-- Name: usuarios usuarios_usuario_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.usuarios
    ADD CONSTRAINT usuarios_usuario_key UNIQUE (usuario);


--
-- Name: ventas ventas_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ventas
    ADD CONSTRAINT ventas_pkey PRIMARY KEY (id_venta);


--
-- Name: idx_clientes_telefono; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_clientes_telefono ON public.clientes USING btree (telefono);


--
-- Name: idx_mensajes_estatus_fecha; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_mensajes_estatus_fecha ON public.mensajes_cobranza USING btree (estatus, fecha_programada);


--
-- Name: idx_pagos_cliente; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_pagos_cliente ON public.pagos_abonos USING btree (id_cliente);


--
-- Name: idx_pagos_venta; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_pagos_venta ON public.pagos_abonos USING btree (id_venta);


--
-- Name: idx_producto_imagenes_producto; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_producto_imagenes_producto ON public.producto_imagenes USING btree (id_producto, orden, id_imagen);


--
-- Name: idx_productos_plu; Type: INDEX; Schema: public; Owner: postgres
--

CREATE UNIQUE INDEX idx_productos_plu ON public.productos USING btree (plu) WHERE (plu IS NOT NULL);


--
-- Name: idx_seguimientos_cliente_fecha; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_seguimientos_cliente_fecha ON public.seguimientos_cobranza USING btree (id_cliente, fecha_creacion DESC);


--
-- Name: idx_seguimientos_proxima_fecha; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_seguimientos_proxima_fecha ON public.seguimientos_cobranza USING btree (proxima_fecha) WHERE (proxima_fecha IS NOT NULL);


--
-- Name: idx_solicitudes_estatus; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_solicitudes_estatus ON public.solicitudes_compra USING btree (estatus);


--
-- Name: idx_solicitudes_token_seguimiento; Type: INDEX; Schema: public; Owner: postgres
--

CREATE UNIQUE INDEX idx_solicitudes_token_seguimiento ON public.solicitudes_compra USING btree (token_seguimiento) WHERE (token_seguimiento IS NOT NULL);


--
-- Name: idx_ventas_cliente; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_ventas_cliente ON public.ventas USING btree (id_cliente);


--
-- Name: idx_ventas_estatus; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_ventas_estatus ON public.ventas USING btree (estatus);


--
-- Name: idx_ventas_fecha_limite; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_ventas_fecha_limite ON public.ventas USING btree (fecha_limite_pago);


--
-- Name: auditoria auditoria_id_usuario_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.auditoria
    ADD CONSTRAINT auditoria_id_usuario_fkey FOREIGN KEY (id_usuario) REFERENCES public.usuarios(id_usuario);


--
-- Name: contactos_referencia contactos_referencia_id_cliente_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.contactos_referencia
    ADD CONSTRAINT contactos_referencia_id_cliente_fkey FOREIGN KEY (id_cliente) REFERENCES public.clientes(id_cliente) ON DELETE CASCADE;


--
-- Name: detalle_solicitud_compra detalle_solicitud_compra_id_producto_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.detalle_solicitud_compra
    ADD CONSTRAINT detalle_solicitud_compra_id_producto_fkey FOREIGN KEY (id_producto) REFERENCES public.productos(id_producto);


--
-- Name: detalle_solicitud_compra detalle_solicitud_compra_id_solicitud_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.detalle_solicitud_compra
    ADD CONSTRAINT detalle_solicitud_compra_id_solicitud_fkey FOREIGN KEY (id_solicitud) REFERENCES public.solicitudes_compra(id_solicitud) ON DELETE CASCADE;


--
-- Name: detalle_venta detalle_venta_id_producto_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.detalle_venta
    ADD CONSTRAINT detalle_venta_id_producto_fkey FOREIGN KEY (id_producto) REFERENCES public.productos(id_producto);


--
-- Name: detalle_venta detalle_venta_id_venta_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.detalle_venta
    ADD CONSTRAINT detalle_venta_id_venta_fkey FOREIGN KEY (id_venta) REFERENCES public.ventas(id_venta) ON DELETE CASCADE;


--
-- Name: mensajes_cobranza mensajes_cobranza_id_cliente_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mensajes_cobranza
    ADD CONSTRAINT mensajes_cobranza_id_cliente_fkey FOREIGN KEY (id_cliente) REFERENCES public.clientes(id_cliente);


--
-- Name: mensajes_cobranza mensajes_cobranza_id_venta_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mensajes_cobranza
    ADD CONSTRAINT mensajes_cobranza_id_venta_fkey FOREIGN KEY (id_venta) REFERENCES public.ventas(id_venta);


--
-- Name: pagos_abonos pagos_abonos_id_cliente_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pagos_abonos
    ADD CONSTRAINT pagos_abonos_id_cliente_fkey FOREIGN KEY (id_cliente) REFERENCES public.clientes(id_cliente);


--
-- Name: pagos_abonos pagos_abonos_id_usuario_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pagos_abonos
    ADD CONSTRAINT pagos_abonos_id_usuario_fkey FOREIGN KEY (id_usuario) REFERENCES public.usuarios(id_usuario);


--
-- Name: pagos_abonos pagos_abonos_id_venta_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pagos_abonos
    ADD CONSTRAINT pagos_abonos_id_venta_fkey FOREIGN KEY (id_venta) REFERENCES public.ventas(id_venta);


--
-- Name: pos_cortes pos_cortes_id_usuario_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pos_cortes
    ADD CONSTRAINT pos_cortes_id_usuario_fkey FOREIGN KEY (id_usuario) REFERENCES public.usuarios(id_usuario);


--
-- Name: pos_detalle pos_detalle_id_producto_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pos_detalle
    ADD CONSTRAINT pos_detalle_id_producto_fkey FOREIGN KEY (id_producto) REFERENCES public.productos(id_producto);


--
-- Name: pos_detalle pos_detalle_id_venta_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pos_detalle
    ADD CONSTRAINT pos_detalle_id_venta_fkey FOREIGN KEY (id_venta) REFERENCES public.pos_ventas(id_venta) ON DELETE CASCADE;


--
-- Name: pos_entradas pos_entradas_id_producto_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pos_entradas
    ADD CONSTRAINT pos_entradas_id_producto_fkey FOREIGN KEY (id_producto) REFERENCES public.productos(id_producto);


--
-- Name: pos_entradas pos_entradas_id_usuario_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pos_entradas
    ADD CONSTRAINT pos_entradas_id_usuario_fkey FOREIGN KEY (id_usuario) REFERENCES public.usuarios(id_usuario);


--
-- Name: pos_esperas pos_esperas_id_usuario_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pos_esperas
    ADD CONSTRAINT pos_esperas_id_usuario_fkey FOREIGN KEY (id_usuario) REFERENCES public.usuarios(id_usuario);


--
-- Name: pos_mermas pos_mermas_id_producto_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pos_mermas
    ADD CONSTRAINT pos_mermas_id_producto_fkey FOREIGN KEY (id_producto) REFERENCES public.productos(id_producto);


--
-- Name: pos_mermas pos_mermas_id_usuario_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pos_mermas
    ADD CONSTRAINT pos_mermas_id_usuario_fkey FOREIGN KEY (id_usuario) REFERENCES public.usuarios(id_usuario);


--
-- Name: pos_pagos pos_pagos_id_venta_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pos_pagos
    ADD CONSTRAINT pos_pagos_id_venta_fkey FOREIGN KEY (id_venta) REFERENCES public.pos_ventas(id_venta) ON DELETE CASCADE;


--
-- Name: pos_ventas pos_ventas_id_cliente_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pos_ventas
    ADD CONSTRAINT pos_ventas_id_cliente_fkey FOREIGN KEY (id_cliente) REFERENCES public.clientes(id_cliente);


--
-- Name: pos_ventas pos_ventas_id_usuario_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pos_ventas
    ADD CONSTRAINT pos_ventas_id_usuario_fkey FOREIGN KEY (id_usuario) REFERENCES public.usuarios(id_usuario);


--
-- Name: producto_imagenes producto_imagenes_id_producto_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.producto_imagenes
    ADD CONSTRAINT producto_imagenes_id_producto_fkey FOREIGN KEY (id_producto) REFERENCES public.productos(id_producto) ON DELETE CASCADE;


--
-- Name: promesas_pago promesas_pago_id_cliente_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.promesas_pago
    ADD CONSTRAINT promesas_pago_id_cliente_fkey FOREIGN KEY (id_cliente) REFERENCES public.clientes(id_cliente);


--
-- Name: promesas_pago promesas_pago_id_venta_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.promesas_pago
    ADD CONSTRAINT promesas_pago_id_venta_fkey FOREIGN KEY (id_venta) REFERENCES public.ventas(id_venta);


--
-- Name: seguimientos_cobranza seguimientos_cobranza_id_cliente_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.seguimientos_cobranza
    ADD CONSTRAINT seguimientos_cobranza_id_cliente_fkey FOREIGN KEY (id_cliente) REFERENCES public.clientes(id_cliente) ON DELETE CASCADE;


--
-- Name: seguimientos_cobranza seguimientos_cobranza_id_usuario_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.seguimientos_cobranza
    ADD CONSTRAINT seguimientos_cobranza_id_usuario_fkey FOREIGN KEY (id_usuario) REFERENCES public.usuarios(id_usuario);


--
-- Name: seguimientos_cobranza seguimientos_cobranza_id_venta_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.seguimientos_cobranza
    ADD CONSTRAINT seguimientos_cobranza_id_venta_fkey FOREIGN KEY (id_venta) REFERENCES public.ventas(id_venta);


--
-- Name: solicitudes_compra solicitudes_compra_id_cliente_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.solicitudes_compra
    ADD CONSTRAINT solicitudes_compra_id_cliente_fkey FOREIGN KEY (id_cliente) REFERENCES public.clientes(id_cliente);


--
-- Name: solicitudes_compra solicitudes_compra_id_venta_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.solicitudes_compra
    ADD CONSTRAINT solicitudes_compra_id_venta_fkey FOREIGN KEY (id_venta) REFERENCES public.ventas(id_venta);


--
-- Name: ventas ventas_id_cliente_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ventas
    ADD CONSTRAINT ventas_id_cliente_fkey FOREIGN KEY (id_cliente) REFERENCES public.clientes(id_cliente);


--
-- Name: ventas ventas_id_usuario_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ventas
    ADD CONSTRAINT ventas_id_usuario_fkey FOREIGN KEY (id_usuario) REFERENCES public.usuarios(id_usuario);


--
-- PostgreSQL database dump complete
--
