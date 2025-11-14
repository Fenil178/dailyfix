--
-- PostgreSQL database dump
--

-- Dumped from database version 17.4
-- Dumped by pg_dump version 17.5

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
-- Name: auth; Type: SCHEMA; Schema: -; Owner: supabase_admin
--

CREATE SCHEMA auth;


ALTER SCHEMA auth OWNER TO supabase_admin;

--
-- Name: extensions; Type: SCHEMA; Schema: -; Owner: postgres
--

CREATE SCHEMA extensions;


ALTER SCHEMA extensions OWNER TO postgres;

--
-- Name: graphql; Type: SCHEMA; Schema: -; Owner: supabase_admin
--

CREATE SCHEMA graphql;


ALTER SCHEMA graphql OWNER TO supabase_admin;

--
-- Name: graphql_public; Type: SCHEMA; Schema: -; Owner: supabase_admin
--

CREATE SCHEMA graphql_public;


ALTER SCHEMA graphql_public OWNER TO supabase_admin;

--
-- Name: pgbouncer; Type: SCHEMA; Schema: -; Owner: pgbouncer
--

CREATE SCHEMA pgbouncer;


ALTER SCHEMA pgbouncer OWNER TO pgbouncer;

--
-- Name: realtime; Type: SCHEMA; Schema: -; Owner: supabase_admin
--

CREATE SCHEMA realtime;


ALTER SCHEMA realtime OWNER TO supabase_admin;

--
-- Name: storage; Type: SCHEMA; Schema: -; Owner: supabase_admin
--

CREATE SCHEMA storage;


ALTER SCHEMA storage OWNER TO supabase_admin;

--
-- Name: vault; Type: SCHEMA; Schema: -; Owner: supabase_admin
--

CREATE SCHEMA vault;


ALTER SCHEMA vault OWNER TO supabase_admin;

--
-- Name: pg_graphql; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS pg_graphql WITH SCHEMA graphql;


--
-- Name: EXTENSION pg_graphql; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION pg_graphql IS 'pg_graphql: GraphQL support';


--
-- Name: pg_stat_statements; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS pg_stat_statements WITH SCHEMA extensions;


--
-- Name: EXTENSION pg_stat_statements; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION pg_stat_statements IS 'track planning and execution statistics of all SQL statements executed';


--
-- Name: pgcrypto; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS pgcrypto WITH SCHEMA extensions;


--
-- Name: EXTENSION pgcrypto; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION pgcrypto IS 'cryptographic functions';


--
-- Name: supabase_vault; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS supabase_vault WITH SCHEMA vault;


--
-- Name: EXTENSION supabase_vault; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION supabase_vault IS 'Supabase Vault Extension';


--
-- Name: uuid-ossp; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS "uuid-ossp" WITH SCHEMA extensions;


--
-- Name: EXTENSION "uuid-ossp"; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION "uuid-ossp" IS 'generate universally unique identifiers (UUIDs)';


--
-- Name: aal_level; Type: TYPE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TYPE auth.aal_level AS ENUM (
    'aal1',
    'aal2',
    'aal3'
);


ALTER TYPE auth.aal_level OWNER TO supabase_auth_admin;

--
-- Name: code_challenge_method; Type: TYPE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TYPE auth.code_challenge_method AS ENUM (
    's256',
    'plain'
);


ALTER TYPE auth.code_challenge_method OWNER TO supabase_auth_admin;

--
-- Name: factor_status; Type: TYPE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TYPE auth.factor_status AS ENUM (
    'unverified',
    'verified'
);


ALTER TYPE auth.factor_status OWNER TO supabase_auth_admin;

--
-- Name: factor_type; Type: TYPE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TYPE auth.factor_type AS ENUM (
    'totp',
    'webauthn',
    'phone'
);


ALTER TYPE auth.factor_type OWNER TO supabase_auth_admin;

--
-- Name: oauth_authorization_status; Type: TYPE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TYPE auth.oauth_authorization_status AS ENUM (
    'pending',
    'approved',
    'denied',
    'expired'
);


ALTER TYPE auth.oauth_authorization_status OWNER TO supabase_auth_admin;

--
-- Name: oauth_client_type; Type: TYPE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TYPE auth.oauth_client_type AS ENUM (
    'public',
    'confidential'
);


ALTER TYPE auth.oauth_client_type OWNER TO supabase_auth_admin;

--
-- Name: oauth_registration_type; Type: TYPE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TYPE auth.oauth_registration_type AS ENUM (
    'dynamic',
    'manual'
);


ALTER TYPE auth.oauth_registration_type OWNER TO supabase_auth_admin;

--
-- Name: oauth_response_type; Type: TYPE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TYPE auth.oauth_response_type AS ENUM (
    'code'
);


ALTER TYPE auth.oauth_response_type OWNER TO supabase_auth_admin;

--
-- Name: one_time_token_type; Type: TYPE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TYPE auth.one_time_token_type AS ENUM (
    'confirmation_token',
    'reauthentication_token',
    'recovery_token',
    'email_change_token_new',
    'email_change_token_current',
    'phone_change_token'
);


ALTER TYPE auth.one_time_token_type OWNER TO supabase_auth_admin;

--
-- Name: account_status; Type: TYPE; Schema: public; Owner: postgres
--

CREATE TYPE public.account_status AS ENUM (
    'active',
    'suspended'
);


ALTER TYPE public.account_status OWNER TO postgres;

--
-- Name: booking_status; Type: TYPE; Schema: public; Owner: postgres
--

CREATE TYPE public.booking_status AS ENUM (
    'pending',
    'confirmed',
    'in_progress',
    'completed',
    'cancelled'
);


ALTER TYPE public.booking_status OWNER TO postgres;

--
-- Name: user_role; Type: TYPE; Schema: public; Owner: postgres
--

CREATE TYPE public.user_role AS ENUM (
    'customer',
    'worker',
    'admin'
);


ALTER TYPE public.user_role OWNER TO postgres;

--
-- Name: action; Type: TYPE; Schema: realtime; Owner: supabase_admin
--

CREATE TYPE realtime.action AS ENUM (
    'INSERT',
    'UPDATE',
    'DELETE',
    'TRUNCATE',
    'ERROR'
);


ALTER TYPE realtime.action OWNER TO supabase_admin;

--
-- Name: equality_op; Type: TYPE; Schema: realtime; Owner: supabase_admin
--

CREATE TYPE realtime.equality_op AS ENUM (
    'eq',
    'neq',
    'lt',
    'lte',
    'gt',
    'gte',
    'in'
);


ALTER TYPE realtime.equality_op OWNER TO supabase_admin;

--
-- Name: user_defined_filter; Type: TYPE; Schema: realtime; Owner: supabase_admin
--

CREATE TYPE realtime.user_defined_filter AS (
	column_name text,
	op realtime.equality_op,
	value text
);


ALTER TYPE realtime.user_defined_filter OWNER TO supabase_admin;

--
-- Name: wal_column; Type: TYPE; Schema: realtime; Owner: supabase_admin
--

CREATE TYPE realtime.wal_column AS (
	name text,
	type_name text,
	type_oid oid,
	value jsonb,
	is_pkey boolean,
	is_selectable boolean
);


ALTER TYPE realtime.wal_column OWNER TO supabase_admin;

--
-- Name: wal_rls; Type: TYPE; Schema: realtime; Owner: supabase_admin
--

CREATE TYPE realtime.wal_rls AS (
	wal jsonb,
	is_rls_enabled boolean,
	subscription_ids uuid[],
	errors text[]
);


ALTER TYPE realtime.wal_rls OWNER TO supabase_admin;

--
-- Name: buckettype; Type: TYPE; Schema: storage; Owner: supabase_storage_admin
--

CREATE TYPE storage.buckettype AS ENUM (
    'STANDARD',
    'ANALYTICS'
);


ALTER TYPE storage.buckettype OWNER TO supabase_storage_admin;

--
-- Name: email(); Type: FUNCTION; Schema: auth; Owner: supabase_auth_admin
--

CREATE FUNCTION auth.email() RETURNS text
    LANGUAGE sql STABLE
    AS $$
  select 
  coalesce(
    nullif(current_setting('request.jwt.claim.email', true), ''),
    (nullif(current_setting('request.jwt.claims', true), '')::jsonb ->> 'email')
  )::text
$$;


ALTER FUNCTION auth.email() OWNER TO supabase_auth_admin;

--
-- Name: FUNCTION email(); Type: COMMENT; Schema: auth; Owner: supabase_auth_admin
--

COMMENT ON FUNCTION auth.email() IS 'Deprecated. Use auth.jwt() -> ''email'' instead.';


--
-- Name: jwt(); Type: FUNCTION; Schema: auth; Owner: supabase_auth_admin
--

CREATE FUNCTION auth.jwt() RETURNS jsonb
    LANGUAGE sql STABLE
    AS $$
  select 
    coalesce(
        nullif(current_setting('request.jwt.claim', true), ''),
        nullif(current_setting('request.jwt.claims', true), '')
    )::jsonb
$$;


ALTER FUNCTION auth.jwt() OWNER TO supabase_auth_admin;

--
-- Name: role(); Type: FUNCTION; Schema: auth; Owner: supabase_auth_admin
--

CREATE FUNCTION auth.role() RETURNS text
    LANGUAGE sql STABLE
    AS $$
  select 
  coalesce(
    nullif(current_setting('request.jwt.claim.role', true), ''),
    (nullif(current_setting('request.jwt.claims', true), '')::jsonb ->> 'role')
  )::text
$$;


ALTER FUNCTION auth.role() OWNER TO supabase_auth_admin;

--
-- Name: FUNCTION role(); Type: COMMENT; Schema: auth; Owner: supabase_auth_admin
--

COMMENT ON FUNCTION auth.role() IS 'Deprecated. Use auth.jwt() -> ''role'' instead.';


--
-- Name: uid(); Type: FUNCTION; Schema: auth; Owner: supabase_auth_admin
--

CREATE FUNCTION auth.uid() RETURNS uuid
    LANGUAGE sql STABLE
    AS $$
  select 
  coalesce(
    nullif(current_setting('request.jwt.claim.sub', true), ''),
    (nullif(current_setting('request.jwt.claims', true), '')::jsonb ->> 'sub')
  )::uuid
$$;


ALTER FUNCTION auth.uid() OWNER TO supabase_auth_admin;

--
-- Name: FUNCTION uid(); Type: COMMENT; Schema: auth; Owner: supabase_auth_admin
--

COMMENT ON FUNCTION auth.uid() IS 'Deprecated. Use auth.jwt() -> ''sub'' instead.';


--
-- Name: grant_pg_cron_access(); Type: FUNCTION; Schema: extensions; Owner: supabase_admin
--

CREATE FUNCTION extensions.grant_pg_cron_access() RETURNS event_trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
  IF EXISTS (
    SELECT
    FROM pg_event_trigger_ddl_commands() AS ev
    JOIN pg_extension AS ext
    ON ev.objid = ext.oid
    WHERE ext.extname = 'pg_cron'
  )
  THEN
    grant usage on schema cron to postgres with grant option;

    alter default privileges in schema cron grant all on tables to postgres with grant option;
    alter default privileges in schema cron grant all on functions to postgres with grant option;
    alter default privileges in schema cron grant all on sequences to postgres with grant option;

    alter default privileges for user supabase_admin in schema cron grant all
        on sequences to postgres with grant option;
    alter default privileges for user supabase_admin in schema cron grant all
        on tables to postgres with grant option;
    alter default privileges for user supabase_admin in schema cron grant all
        on functions to postgres with grant option;

    grant all privileges on all tables in schema cron to postgres with grant option;
    revoke all on table cron.job from postgres;
    grant select on table cron.job to postgres with grant option;
  END IF;
END;
$$;


ALTER FUNCTION extensions.grant_pg_cron_access() OWNER TO supabase_admin;

--
-- Name: FUNCTION grant_pg_cron_access(); Type: COMMENT; Schema: extensions; Owner: supabase_admin
--

COMMENT ON FUNCTION extensions.grant_pg_cron_access() IS 'Grants access to pg_cron';


--
-- Name: grant_pg_graphql_access(); Type: FUNCTION; Schema: extensions; Owner: supabase_admin
--

CREATE FUNCTION extensions.grant_pg_graphql_access() RETURNS event_trigger
    LANGUAGE plpgsql
    AS $_$
DECLARE
    func_is_graphql_resolve bool;
BEGIN
    func_is_graphql_resolve = (
        SELECT n.proname = 'resolve'
        FROM pg_event_trigger_ddl_commands() AS ev
        LEFT JOIN pg_catalog.pg_proc AS n
        ON ev.objid = n.oid
    );

    IF func_is_graphql_resolve
    THEN
        -- Update public wrapper to pass all arguments through to the pg_graphql resolve func
        DROP FUNCTION IF EXISTS graphql_public.graphql;
        create or replace function graphql_public.graphql(
            "operationName" text default null,
            query text default null,
            variables jsonb default null,
            extensions jsonb default null
        )
            returns jsonb
            language sql
        as $$
            select graphql.resolve(
                query := query,
                variables := coalesce(variables, '{}'),
                "operationName" := "operationName",
                extensions := extensions
            );
        $$;

        -- This hook executes when `graphql.resolve` is created. That is not necessarily the last
        -- function in the extension so we need to grant permissions on existing entities AND
        -- update default permissions to any others that are created after `graphql.resolve`
        grant usage on schema graphql to postgres, anon, authenticated, service_role;
        grant select on all tables in schema graphql to postgres, anon, authenticated, service_role;
        grant execute on all functions in schema graphql to postgres, anon, authenticated, service_role;
        grant all on all sequences in schema graphql to postgres, anon, authenticated, service_role;
        alter default privileges in schema graphql grant all on tables to postgres, anon, authenticated, service_role;
        alter default privileges in schema graphql grant all on functions to postgres, anon, authenticated, service_role;
        alter default privileges in schema graphql grant all on sequences to postgres, anon, authenticated, service_role;

        -- Allow postgres role to allow granting usage on graphql and graphql_public schemas to custom roles
        grant usage on schema graphql_public to postgres with grant option;
        grant usage on schema graphql to postgres with grant option;
    END IF;

END;
$_$;


ALTER FUNCTION extensions.grant_pg_graphql_access() OWNER TO supabase_admin;

--
-- Name: FUNCTION grant_pg_graphql_access(); Type: COMMENT; Schema: extensions; Owner: supabase_admin
--

COMMENT ON FUNCTION extensions.grant_pg_graphql_access() IS 'Grants access to pg_graphql';


--
-- Name: grant_pg_net_access(); Type: FUNCTION; Schema: extensions; Owner: supabase_admin
--

CREATE FUNCTION extensions.grant_pg_net_access() RETURNS event_trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
  IF EXISTS (
    SELECT 1
    FROM pg_event_trigger_ddl_commands() AS ev
    JOIN pg_extension AS ext
    ON ev.objid = ext.oid
    WHERE ext.extname = 'pg_net'
  )
  THEN
    IF NOT EXISTS (
      SELECT 1
      FROM pg_roles
      WHERE rolname = 'supabase_functions_admin'
    )
    THEN
      CREATE USER supabase_functions_admin NOINHERIT CREATEROLE LOGIN NOREPLICATION;
    END IF;

    GRANT USAGE ON SCHEMA net TO supabase_functions_admin, postgres, anon, authenticated, service_role;

    IF EXISTS (
      SELECT FROM pg_extension
      WHERE extname = 'pg_net'
      -- all versions in use on existing projects as of 2025-02-20
      -- version 0.12.0 onwards don't need these applied
      AND extversion IN ('0.2', '0.6', '0.7', '0.7.1', '0.8', '0.10.0', '0.11.0')
    ) THEN
      ALTER function net.http_get(url text, params jsonb, headers jsonb, timeout_milliseconds integer) SECURITY DEFINER;
      ALTER function net.http_post(url text, body jsonb, params jsonb, headers jsonb, timeout_milliseconds integer) SECURITY DEFINER;

      ALTER function net.http_get(url text, params jsonb, headers jsonb, timeout_milliseconds integer) SET search_path = net;
      ALTER function net.http_post(url text, body jsonb, params jsonb, headers jsonb, timeout_milliseconds integer) SET search_path = net;

      REVOKE ALL ON FUNCTION net.http_get(url text, params jsonb, headers jsonb, timeout_milliseconds integer) FROM PUBLIC;
      REVOKE ALL ON FUNCTION net.http_post(url text, body jsonb, params jsonb, headers jsonb, timeout_milliseconds integer) FROM PUBLIC;

      GRANT EXECUTE ON FUNCTION net.http_get(url text, params jsonb, headers jsonb, timeout_milliseconds integer) TO supabase_functions_admin, postgres, anon, authenticated, service_role;
      GRANT EXECUTE ON FUNCTION net.http_post(url text, body jsonb, params jsonb, headers jsonb, timeout_milliseconds integer) TO supabase_functions_admin, postgres, anon, authenticated, service_role;
    END IF;
  END IF;
END;
$$;


ALTER FUNCTION extensions.grant_pg_net_access() OWNER TO supabase_admin;

--
-- Name: FUNCTION grant_pg_net_access(); Type: COMMENT; Schema: extensions; Owner: supabase_admin
--

COMMENT ON FUNCTION extensions.grant_pg_net_access() IS 'Grants access to pg_net';


--
-- Name: pgrst_ddl_watch(); Type: FUNCTION; Schema: extensions; Owner: supabase_admin
--

CREATE FUNCTION extensions.pgrst_ddl_watch() RETURNS event_trigger
    LANGUAGE plpgsql
    AS $$
DECLARE
  cmd record;
BEGIN
  FOR cmd IN SELECT * FROM pg_event_trigger_ddl_commands()
  LOOP
    IF cmd.command_tag IN (
      'CREATE SCHEMA', 'ALTER SCHEMA'
    , 'CREATE TABLE', 'CREATE TABLE AS', 'SELECT INTO', 'ALTER TABLE'
    , 'CREATE FOREIGN TABLE', 'ALTER FOREIGN TABLE'
    , 'CREATE VIEW', 'ALTER VIEW'
    , 'CREATE MATERIALIZED VIEW', 'ALTER MATERIALIZED VIEW'
    , 'CREATE FUNCTION', 'ALTER FUNCTION'
    , 'CREATE TRIGGER'
    , 'CREATE TYPE', 'ALTER TYPE'
    , 'CREATE RULE'
    , 'COMMENT'
    )
    -- don't notify in case of CREATE TEMP table or other objects created on pg_temp
    AND cmd.schema_name is distinct from 'pg_temp'
    THEN
      NOTIFY pgrst, 'reload schema';
    END IF;
  END LOOP;
END; $$;


ALTER FUNCTION extensions.pgrst_ddl_watch() OWNER TO supabase_admin;

--
-- Name: pgrst_drop_watch(); Type: FUNCTION; Schema: extensions; Owner: supabase_admin
--

CREATE FUNCTION extensions.pgrst_drop_watch() RETURNS event_trigger
    LANGUAGE plpgsql
    AS $$
DECLARE
  obj record;
BEGIN
  FOR obj IN SELECT * FROM pg_event_trigger_dropped_objects()
  LOOP
    IF obj.object_type IN (
      'schema'
    , 'table'
    , 'foreign table'
    , 'view'
    , 'materialized view'
    , 'function'
    , 'trigger'
    , 'type'
    , 'rule'
    )
    AND obj.is_temporary IS false -- no pg_temp objects
    THEN
      NOTIFY pgrst, 'reload schema';
    END IF;
  END LOOP;
END; $$;


ALTER FUNCTION extensions.pgrst_drop_watch() OWNER TO supabase_admin;

--
-- Name: set_graphql_placeholder(); Type: FUNCTION; Schema: extensions; Owner: supabase_admin
--

CREATE FUNCTION extensions.set_graphql_placeholder() RETURNS event_trigger
    LANGUAGE plpgsql
    AS $_$
    DECLARE
    graphql_is_dropped bool;
    BEGIN
    graphql_is_dropped = (
        SELECT ev.schema_name = 'graphql_public'
        FROM pg_event_trigger_dropped_objects() AS ev
        WHERE ev.schema_name = 'graphql_public'
    );

    IF graphql_is_dropped
    THEN
        create or replace function graphql_public.graphql(
            "operationName" text default null,
            query text default null,
            variables jsonb default null,
            extensions jsonb default null
        )
            returns jsonb
            language plpgsql
        as $$
            DECLARE
                server_version float;
            BEGIN
                server_version = (SELECT (SPLIT_PART((select version()), ' ', 2))::float);

                IF server_version >= 14 THEN
                    RETURN jsonb_build_object(
                        'errors', jsonb_build_array(
                            jsonb_build_object(
                                'message', 'pg_graphql extension is not enabled.'
                            )
                        )
                    );
                ELSE
                    RETURN jsonb_build_object(
                        'errors', jsonb_build_array(
                            jsonb_build_object(
                                'message', 'pg_graphql is only available on projects running Postgres 14 onwards.'
                            )
                        )
                    );
                END IF;
            END;
        $$;
    END IF;

    END;
$_$;


ALTER FUNCTION extensions.set_graphql_placeholder() OWNER TO supabase_admin;

--
-- Name: FUNCTION set_graphql_placeholder(); Type: COMMENT; Schema: extensions; Owner: supabase_admin
--

COMMENT ON FUNCTION extensions.set_graphql_placeholder() IS 'Reintroduces placeholder function for graphql_public.graphql';


--
-- Name: get_auth(text); Type: FUNCTION; Schema: pgbouncer; Owner: supabase_admin
--

CREATE FUNCTION pgbouncer.get_auth(p_usename text) RETURNS TABLE(username text, password text)
    LANGUAGE plpgsql SECURITY DEFINER
    AS $_$
begin
    raise debug 'PgBouncer auth request: %', p_usename;

    return query
    select 
        rolname::text, 
        case when rolvaliduntil < now() 
            then null 
            else rolpassword::text 
        end 
    from pg_authid 
    where rolname=$1 and rolcanlogin;
end;
$_$;


ALTER FUNCTION pgbouncer.get_auth(p_usename text) OWNER TO supabase_admin;

--
-- Name: apply_rls(jsonb, integer); Type: FUNCTION; Schema: realtime; Owner: supabase_admin
--

CREATE FUNCTION realtime.apply_rls(wal jsonb, max_record_bytes integer DEFAULT (1024 * 1024)) RETURNS SETOF realtime.wal_rls
    LANGUAGE plpgsql
    AS $$
declare
-- Regclass of the table e.g. public.notes
entity_ regclass = (quote_ident(wal ->> 'schema') || '.' || quote_ident(wal ->> 'table'))::regclass;

-- I, U, D, T: insert, update ...
action realtime.action = (
    case wal ->> 'action'
        when 'I' then 'INSERT'
        when 'U' then 'UPDATE'
        when 'D' then 'DELETE'
        else 'ERROR'
    end
);

-- Is row level security enabled for the table
is_rls_enabled bool = relrowsecurity from pg_class where oid = entity_;

subscriptions realtime.subscription[] = array_agg(subs)
    from
        realtime.subscription subs
    where
        subs.entity = entity_;

-- Subscription vars
roles regrole[] = array_agg(distinct us.claims_role::text)
    from
        unnest(subscriptions) us;

working_role regrole;
claimed_role regrole;
claims jsonb;

subscription_id uuid;
subscription_has_access bool;
visible_to_subscription_ids uuid[] = '{}';

-- structured info for wal's columns
columns realtime.wal_column[];
-- previous identity values for update/delete
old_columns realtime.wal_column[];

error_record_exceeds_max_size boolean = octet_length(wal::text) > max_record_bytes;

-- Primary jsonb output for record
output jsonb;

begin
perform set_config('role', null, true);

columns =
    array_agg(
        (
            x->>'name',
            x->>'type',
            x->>'typeoid',
            realtime.cast(
                (x->'value') #>> '{}',
                coalesce(
                    (x->>'typeoid')::regtype, -- null when wal2json version <= 2.4
                    (x->>'type')::regtype
                )
            ),
            (pks ->> 'name') is not null,
            true
        )::realtime.wal_column
    )
    from
        jsonb_array_elements(wal -> 'columns') x
        left join jsonb_array_elements(wal -> 'pk') pks
            on (x ->> 'name') = (pks ->> 'name');

old_columns =
    array_agg(
        (
            x->>'name',
            x->>'type',
            x->>'typeoid',
            realtime.cast(
                (x->'value') #>> '{}',
                coalesce(
                    (x->>'typeoid')::regtype, -- null when wal2json version <= 2.4
                    (x->>'type')::regtype
                )
            ),
            (pks ->> 'name') is not null,
            true
        )::realtime.wal_column
    )
    from
        jsonb_array_elements(wal -> 'identity') x
        left join jsonb_array_elements(wal -> 'pk') pks
            on (x ->> 'name') = (pks ->> 'name');

for working_role in select * from unnest(roles) loop

    -- Update `is_selectable` for columns and old_columns
    columns =
        array_agg(
            (
                c.name,
                c.type_name,
                c.type_oid,
                c.value,
                c.is_pkey,
                pg_catalog.has_column_privilege(working_role, entity_, c.name, 'SELECT')
            )::realtime.wal_column
        )
        from
            unnest(columns) c;

    old_columns =
            array_agg(
                (
                    c.name,
                    c.type_name,
                    c.type_oid,
                    c.value,
                    c.is_pkey,
                    pg_catalog.has_column_privilege(working_role, entity_, c.name, 'SELECT')
                )::realtime.wal_column
            )
            from
                unnest(old_columns) c;

    if action <> 'DELETE' and count(1) = 0 from unnest(columns) c where c.is_pkey then
        return next (
            jsonb_build_object(
                'schema', wal ->> 'schema',
                'table', wal ->> 'table',
                'type', action
            ),
            is_rls_enabled,
            -- subscriptions is already filtered by entity
            (select array_agg(s.subscription_id) from unnest(subscriptions) as s where claims_role = working_role),
            array['Error 400: Bad Request, no primary key']
        )::realtime.wal_rls;

    -- The claims role does not have SELECT permission to the primary key of entity
    elsif action <> 'DELETE' and sum(c.is_selectable::int) <> count(1) from unnest(columns) c where c.is_pkey then
        return next (
            jsonb_build_object(
                'schema', wal ->> 'schema',
                'table', wal ->> 'table',
                'type', action
            ),
            is_rls_enabled,
            (select array_agg(s.subscription_id) from unnest(subscriptions) as s where claims_role = working_role),
            array['Error 401: Unauthorized']
        )::realtime.wal_rls;

    else
        output = jsonb_build_object(
            'schema', wal ->> 'schema',
            'table', wal ->> 'table',
            'type', action,
            'commit_timestamp', to_char(
                ((wal ->> 'timestamp')::timestamptz at time zone 'utc'),
                'YYYY-MM-DD"T"HH24:MI:SS.MS"Z"'
            ),
            'columns', (
                select
                    jsonb_agg(
                        jsonb_build_object(
                            'name', pa.attname,
                            'type', pt.typname
                        )
                        order by pa.attnum asc
                    )
                from
                    pg_attribute pa
                    join pg_type pt
                        on pa.atttypid = pt.oid
                where
                    attrelid = entity_
                    and attnum > 0
                    and pg_catalog.has_column_privilege(working_role, entity_, pa.attname, 'SELECT')
            )
        )
        -- Add "record" key for insert and update
        || case
            when action in ('INSERT', 'UPDATE') then
                jsonb_build_object(
                    'record',
                    (
                        select
                            jsonb_object_agg(
                                -- if unchanged toast, get column name and value from old record
                                coalesce((c).name, (oc).name),
                                case
                                    when (c).name is null then (oc).value
                                    else (c).value
                                end
                            )
                        from
                            unnest(columns) c
                            full outer join unnest(old_columns) oc
                                on (c).name = (oc).name
                        where
                            coalesce((c).is_selectable, (oc).is_selectable)
                            and ( not error_record_exceeds_max_size or (octet_length((c).value::text) <= 64))
                    )
                )
            else '{}'::jsonb
        end
        -- Add "old_record" key for update and delete
        || case
            when action = 'UPDATE' then
                jsonb_build_object(
                        'old_record',
                        (
                            select jsonb_object_agg((c).name, (c).value)
                            from unnest(old_columns) c
                            where
                                (c).is_selectable
                                and ( not error_record_exceeds_max_size or (octet_length((c).value::text) <= 64))
                        )
                    )
            when action = 'DELETE' then
                jsonb_build_object(
                    'old_record',
                    (
                        select jsonb_object_agg((c).name, (c).value)
                        from unnest(old_columns) c
                        where
                            (c).is_selectable
                            and ( not error_record_exceeds_max_size or (octet_length((c).value::text) <= 64))
                            and ( not is_rls_enabled or (c).is_pkey ) -- if RLS enabled, we can't secure deletes so filter to pkey
                    )
                )
            else '{}'::jsonb
        end;

        -- Create the prepared statement
        if is_rls_enabled and action <> 'DELETE' then
            if (select 1 from pg_prepared_statements where name = 'walrus_rls_stmt' limit 1) > 0 then
                deallocate walrus_rls_stmt;
            end if;
            execute realtime.build_prepared_statement_sql('walrus_rls_stmt', entity_, columns);
        end if;

        visible_to_subscription_ids = '{}';

        for subscription_id, claims in (
                select
                    subs.subscription_id,
                    subs.claims
                from
                    unnest(subscriptions) subs
                where
                    subs.entity = entity_
                    and subs.claims_role = working_role
                    and (
                        realtime.is_visible_through_filters(columns, subs.filters)
                        or (
                          action = 'DELETE'
                          and realtime.is_visible_through_filters(old_columns, subs.filters)
                        )
                    )
        ) loop

            if not is_rls_enabled or action = 'DELETE' then
                visible_to_subscription_ids = visible_to_subscription_ids || subscription_id;
            else
                -- Check if RLS allows the role to see the record
                perform
                    -- Trim leading and trailing quotes from working_role because set_config
                    -- doesn't recognize the role as valid if they are included
                    set_config('role', trim(both '"' from working_role::text), true),
                    set_config('request.jwt.claims', claims::text, true);

                execute 'execute walrus_rls_stmt' into subscription_has_access;

                if subscription_has_access then
                    visible_to_subscription_ids = visible_to_subscription_ids || subscription_id;
                end if;
            end if;
        end loop;

        perform set_config('role', null, true);

        return next (
            output,
            is_rls_enabled,
            visible_to_subscription_ids,
            case
                when error_record_exceeds_max_size then array['Error 413: Payload Too Large']
                else '{}'
            end
        )::realtime.wal_rls;

    end if;
end loop;

perform set_config('role', null, true);
end;
$$;


ALTER FUNCTION realtime.apply_rls(wal jsonb, max_record_bytes integer) OWNER TO supabase_admin;

--
-- Name: broadcast_changes(text, text, text, text, text, record, record, text); Type: FUNCTION; Schema: realtime; Owner: supabase_admin
--

CREATE FUNCTION realtime.broadcast_changes(topic_name text, event_name text, operation text, table_name text, table_schema text, new record, old record, level text DEFAULT 'ROW'::text) RETURNS void
    LANGUAGE plpgsql
    AS $$
DECLARE
    -- Declare a variable to hold the JSONB representation of the row
    row_data jsonb := '{}'::jsonb;
BEGIN
    IF level = 'STATEMENT' THEN
        RAISE EXCEPTION 'function can only be triggered for each row, not for each statement';
    END IF;
    -- Check the operation type and handle accordingly
    IF operation = 'INSERT' OR operation = 'UPDATE' OR operation = 'DELETE' THEN
        row_data := jsonb_build_object('old_record', OLD, 'record', NEW, 'operation', operation, 'table', table_name, 'schema', table_schema);
        PERFORM realtime.send (row_data, event_name, topic_name);
    ELSE
        RAISE EXCEPTION 'Unexpected operation type: %', operation;
    END IF;
EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Failed to process the row: %', SQLERRM;
END;

$$;


ALTER FUNCTION realtime.broadcast_changes(topic_name text, event_name text, operation text, table_name text, table_schema text, new record, old record, level text) OWNER TO supabase_admin;

--
-- Name: build_prepared_statement_sql(text, regclass, realtime.wal_column[]); Type: FUNCTION; Schema: realtime; Owner: supabase_admin
--

CREATE FUNCTION realtime.build_prepared_statement_sql(prepared_statement_name text, entity regclass, columns realtime.wal_column[]) RETURNS text
    LANGUAGE sql
    AS $$
      /*
      Builds a sql string that, if executed, creates a prepared statement to
      tests retrive a row from *entity* by its primary key columns.
      Example
          select realtime.build_prepared_statement_sql('public.notes', '{"id"}'::text[], '{"bigint"}'::text[])
      */
          select
      'prepare ' || prepared_statement_name || ' as
          select
              exists(
                  select
                      1
                  from
                      ' || entity || '
                  where
                      ' || string_agg(quote_ident(pkc.name) || '=' || quote_nullable(pkc.value #>> '{}') , ' and ') || '
              )'
          from
              unnest(columns) pkc
          where
              pkc.is_pkey
          group by
              entity
      $$;


ALTER FUNCTION realtime.build_prepared_statement_sql(prepared_statement_name text, entity regclass, columns realtime.wal_column[]) OWNER TO supabase_admin;

--
-- Name: cast(text, regtype); Type: FUNCTION; Schema: realtime; Owner: supabase_admin
--

CREATE FUNCTION realtime."cast"(val text, type_ regtype) RETURNS jsonb
    LANGUAGE plpgsql IMMUTABLE
    AS $$
    declare
      res jsonb;
    begin
      execute format('select to_jsonb(%L::'|| type_::text || ')', val)  into res;
      return res;
    end
    $$;


ALTER FUNCTION realtime."cast"(val text, type_ regtype) OWNER TO supabase_admin;

--
-- Name: check_equality_op(realtime.equality_op, regtype, text, text); Type: FUNCTION; Schema: realtime; Owner: supabase_admin
--

CREATE FUNCTION realtime.check_equality_op(op realtime.equality_op, type_ regtype, val_1 text, val_2 text) RETURNS boolean
    LANGUAGE plpgsql IMMUTABLE
    AS $$
      /*
      Casts *val_1* and *val_2* as type *type_* and check the *op* condition for truthiness
      */
      declare
          op_symbol text = (
              case
                  when op = 'eq' then '='
                  when op = 'neq' then '!='
                  when op = 'lt' then '<'
                  when op = 'lte' then '<='
                  when op = 'gt' then '>'
                  when op = 'gte' then '>='
                  when op = 'in' then '= any'
                  else 'UNKNOWN OP'
              end
          );
          res boolean;
      begin
          execute format(
              'select %L::'|| type_::text || ' ' || op_symbol
              || ' ( %L::'
              || (
                  case
                      when op = 'in' then type_::text || '[]'
                      else type_::text end
              )
              || ')', val_1, val_2) into res;
          return res;
      end;
      $$;


ALTER FUNCTION realtime.check_equality_op(op realtime.equality_op, type_ regtype, val_1 text, val_2 text) OWNER TO supabase_admin;

--
-- Name: is_visible_through_filters(realtime.wal_column[], realtime.user_defined_filter[]); Type: FUNCTION; Schema: realtime; Owner: supabase_admin
--

CREATE FUNCTION realtime.is_visible_through_filters(columns realtime.wal_column[], filters realtime.user_defined_filter[]) RETURNS boolean
    LANGUAGE sql IMMUTABLE
    AS $_$
    /*
    Should the record be visible (true) or filtered out (false) after *filters* are applied
    */
        select
            -- Default to allowed when no filters present
            $2 is null -- no filters. this should not happen because subscriptions has a default
            or array_length($2, 1) is null -- array length of an empty array is null
            or bool_and(
                coalesce(
                    realtime.check_equality_op(
                        op:=f.op,
                        type_:=coalesce(
                            col.type_oid::regtype, -- null when wal2json version <= 2.4
                            col.type_name::regtype
                        ),
                        -- cast jsonb to text
                        val_1:=col.value #>> '{}',
                        val_2:=f.value
                    ),
                    false -- if null, filter does not match
                )
            )
        from
            unnest(filters) f
            join unnest(columns) col
                on f.column_name = col.name;
    $_$;


ALTER FUNCTION realtime.is_visible_through_filters(columns realtime.wal_column[], filters realtime.user_defined_filter[]) OWNER TO supabase_admin;

--
-- Name: list_changes(name, name, integer, integer); Type: FUNCTION; Schema: realtime; Owner: supabase_admin
--

CREATE FUNCTION realtime.list_changes(publication name, slot_name name, max_changes integer, max_record_bytes integer) RETURNS SETOF realtime.wal_rls
    LANGUAGE sql
    SET log_min_messages TO 'fatal'
    AS $$
      with pub as (
        select
          concat_ws(
            ',',
            case when bool_or(pubinsert) then 'insert' else null end,
            case when bool_or(pubupdate) then 'update' else null end,
            case when bool_or(pubdelete) then 'delete' else null end
          ) as w2j_actions,
          coalesce(
            string_agg(
              realtime.quote_wal2json(format('%I.%I', schemaname, tablename)::regclass),
              ','
            ) filter (where ppt.tablename is not null and ppt.tablename not like '% %'),
            ''
          ) w2j_add_tables
        from
          pg_publication pp
          left join pg_publication_tables ppt
            on pp.pubname = ppt.pubname
        where
          pp.pubname = publication
        group by
          pp.pubname
        limit 1
      ),
      w2j as (
        select
          x.*, pub.w2j_add_tables
        from
          pub,
          pg_logical_slot_get_changes(
            slot_name, null, max_changes,
            'include-pk', 'true',
            'include-transaction', 'false',
            'include-timestamp', 'true',
            'include-type-oids', 'true',
            'format-version', '2',
            'actions', pub.w2j_actions,
            'add-tables', pub.w2j_add_tables
          ) x
      )
      select
        xyz.wal,
        xyz.is_rls_enabled,
        xyz.subscription_ids,
        xyz.errors
      from
        w2j,
        realtime.apply_rls(
          wal := w2j.data::jsonb,
          max_record_bytes := max_record_bytes
        ) xyz(wal, is_rls_enabled, subscription_ids, errors)
      where
        w2j.w2j_add_tables <> ''
        and xyz.subscription_ids[1] is not null
    $$;


ALTER FUNCTION realtime.list_changes(publication name, slot_name name, max_changes integer, max_record_bytes integer) OWNER TO supabase_admin;

--
-- Name: quote_wal2json(regclass); Type: FUNCTION; Schema: realtime; Owner: supabase_admin
--

CREATE FUNCTION realtime.quote_wal2json(entity regclass) RETURNS text
    LANGUAGE sql IMMUTABLE STRICT
    AS $$
      select
        (
          select string_agg('' || ch,'')
          from unnest(string_to_array(nsp.nspname::text, null)) with ordinality x(ch, idx)
          where
            not (x.idx = 1 and x.ch = '"')
            and not (
              x.idx = array_length(string_to_array(nsp.nspname::text, null), 1)
              and x.ch = '"'
            )
        )
        || '.'
        || (
          select string_agg('' || ch,'')
          from unnest(string_to_array(pc.relname::text, null)) with ordinality x(ch, idx)
          where
            not (x.idx = 1 and x.ch = '"')
            and not (
              x.idx = array_length(string_to_array(nsp.nspname::text, null), 1)
              and x.ch = '"'
            )
          )
      from
        pg_class pc
        join pg_namespace nsp
          on pc.relnamespace = nsp.oid
      where
        pc.oid = entity
    $$;


ALTER FUNCTION realtime.quote_wal2json(entity regclass) OWNER TO supabase_admin;

--
-- Name: send(jsonb, text, text, boolean); Type: FUNCTION; Schema: realtime; Owner: supabase_admin
--

CREATE FUNCTION realtime.send(payload jsonb, event text, topic text, private boolean DEFAULT true) RETURNS void
    LANGUAGE plpgsql
    AS $$
DECLARE
  generated_id uuid;
  final_payload jsonb;
BEGIN
  BEGIN
    -- Generate a new UUID for the id
    generated_id := gen_random_uuid();

    -- Check if payload has an 'id' key, if not, add the generated UUID
    IF payload ? 'id' THEN
      final_payload := payload;
    ELSE
      final_payload := jsonb_set(payload, '{id}', to_jsonb(generated_id));
    END IF;

    -- Set the topic configuration
    EXECUTE format('SET LOCAL realtime.topic TO %L', topic);

    -- Attempt to insert the message
    INSERT INTO realtime.messages (id, payload, event, topic, private, extension)
    VALUES (generated_id, final_payload, event, topic, private, 'broadcast');
  EXCEPTION
    WHEN OTHERS THEN
      -- Capture and notify the error
      RAISE WARNING 'ErrorSendingBroadcastMessage: %', SQLERRM;
  END;
END;
$$;


ALTER FUNCTION realtime.send(payload jsonb, event text, topic text, private boolean) OWNER TO supabase_admin;

--
-- Name: subscription_check_filters(); Type: FUNCTION; Schema: realtime; Owner: supabase_admin
--

CREATE FUNCTION realtime.subscription_check_filters() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
    /*
    Validates that the user defined filters for a subscription:
    - refer to valid columns that the claimed role may access
    - values are coercable to the correct column type
    */
    declare
        col_names text[] = coalesce(
                array_agg(c.column_name order by c.ordinal_position),
                '{}'::text[]
            )
            from
                information_schema.columns c
            where
                format('%I.%I', c.table_schema, c.table_name)::regclass = new.entity
                and pg_catalog.has_column_privilege(
                    (new.claims ->> 'role'),
                    format('%I.%I', c.table_schema, c.table_name)::regclass,
                    c.column_name,
                    'SELECT'
                );
        filter realtime.user_defined_filter;
        col_type regtype;

        in_val jsonb;
    begin
        for filter in select * from unnest(new.filters) loop
            -- Filtered column is valid
            if not filter.column_name = any(col_names) then
                raise exception 'invalid column for filter %', filter.column_name;
            end if;

            -- Type is sanitized and safe for string interpolation
            col_type = (
                select atttypid::regtype
                from pg_catalog.pg_attribute
                where attrelid = new.entity
                      and attname = filter.column_name
            );
            if col_type is null then
                raise exception 'failed to lookup type for column %', filter.column_name;
            end if;

            -- Set maximum number of entries for in filter
            if filter.op = 'in'::realtime.equality_op then
                in_val = realtime.cast(filter.value, (col_type::text || '[]')::regtype);
                if coalesce(jsonb_array_length(in_val), 0) > 100 then
                    raise exception 'too many values for `in` filter. Maximum 100';
                end if;
            else
                -- raises an exception if value is not coercable to type
                perform realtime.cast(filter.value, col_type);
            end if;

        end loop;

        -- Apply consistent order to filters so the unique constraint on
        -- (subscription_id, entity, filters) can't be tricked by a different filter order
        new.filters = coalesce(
            array_agg(f order by f.column_name, f.op, f.value),
            '{}'
        ) from unnest(new.filters) f;

        return new;
    end;
    $$;


ALTER FUNCTION realtime.subscription_check_filters() OWNER TO supabase_admin;

--
-- Name: to_regrole(text); Type: FUNCTION; Schema: realtime; Owner: supabase_admin
--

CREATE FUNCTION realtime.to_regrole(role_name text) RETURNS regrole
    LANGUAGE sql IMMUTABLE
    AS $$ select role_name::regrole $$;


ALTER FUNCTION realtime.to_regrole(role_name text) OWNER TO supabase_admin;

--
-- Name: topic(); Type: FUNCTION; Schema: realtime; Owner: supabase_realtime_admin
--

CREATE FUNCTION realtime.topic() RETURNS text
    LANGUAGE sql STABLE
    AS $$
select nullif(current_setting('realtime.topic', true), '')::text;
$$;


ALTER FUNCTION realtime.topic() OWNER TO supabase_realtime_admin;

--
-- Name: add_prefixes(text, text); Type: FUNCTION; Schema: storage; Owner: supabase_storage_admin
--

CREATE FUNCTION storage.add_prefixes(_bucket_id text, _name text) RETURNS void
    LANGUAGE plpgsql SECURITY DEFINER
    AS $$
DECLARE
    prefixes text[];
BEGIN
    prefixes := "storage"."get_prefixes"("_name");

    IF array_length(prefixes, 1) > 0 THEN
        INSERT INTO storage.prefixes (name, bucket_id)
        SELECT UNNEST(prefixes) as name, "_bucket_id" ON CONFLICT DO NOTHING;
    END IF;
END;
$$;


ALTER FUNCTION storage.add_prefixes(_bucket_id text, _name text) OWNER TO supabase_storage_admin;

--
-- Name: can_insert_object(text, text, uuid, jsonb); Type: FUNCTION; Schema: storage; Owner: supabase_storage_admin
--

CREATE FUNCTION storage.can_insert_object(bucketid text, name text, owner uuid, metadata jsonb) RETURNS void
    LANGUAGE plpgsql
    AS $$
BEGIN
  INSERT INTO "storage"."objects" ("bucket_id", "name", "owner", "metadata") VALUES (bucketid, name, owner, metadata);
  -- hack to rollback the successful insert
  RAISE sqlstate 'PT200' using
  message = 'ROLLBACK',
  detail = 'rollback successful insert';
END
$$;


ALTER FUNCTION storage.can_insert_object(bucketid text, name text, owner uuid, metadata jsonb) OWNER TO supabase_storage_admin;

--
-- Name: delete_leaf_prefixes(text[], text[]); Type: FUNCTION; Schema: storage; Owner: supabase_storage_admin
--

CREATE FUNCTION storage.delete_leaf_prefixes(bucket_ids text[], names text[]) RETURNS void
    LANGUAGE plpgsql SECURITY DEFINER
    AS $$
DECLARE
    v_rows_deleted integer;
BEGIN
    LOOP
        WITH candidates AS (
            SELECT DISTINCT
                t.bucket_id,
                unnest(storage.get_prefixes(t.name)) AS name
            FROM unnest(bucket_ids, names) AS t(bucket_id, name)
        ),
        uniq AS (
             SELECT
                 bucket_id,
                 name,
                 storage.get_level(name) AS level
             FROM candidates
             WHERE name <> ''
             GROUP BY bucket_id, name
        ),
        leaf AS (
             SELECT
                 p.bucket_id,
                 p.name,
                 p.level
             FROM storage.prefixes AS p
                  JOIN uniq AS u
                       ON u.bucket_id = p.bucket_id
                           AND u.name = p.name
                           AND u.level = p.level
             WHERE NOT EXISTS (
                 SELECT 1
                 FROM storage.objects AS o
                 WHERE o.bucket_id = p.bucket_id
                   AND o.level = p.level + 1
                   AND o.name COLLATE "C" LIKE p.name || '/%'
             )
             AND NOT EXISTS (
                 SELECT 1
                 FROM storage.prefixes AS c
                 WHERE c.bucket_id = p.bucket_id
                   AND c.level = p.level + 1
                   AND c.name COLLATE "C" LIKE p.name || '/%'
             )
        )
        DELETE
        FROM storage.prefixes AS p
            USING leaf AS l
        WHERE p.bucket_id = l.bucket_id
          AND p.name = l.name
          AND p.level = l.level;

        GET DIAGNOSTICS v_rows_deleted = ROW_COUNT;
        EXIT WHEN v_rows_deleted = 0;
    END LOOP;
END;
$$;


ALTER FUNCTION storage.delete_leaf_prefixes(bucket_ids text[], names text[]) OWNER TO supabase_storage_admin;

--
-- Name: delete_prefix(text, text); Type: FUNCTION; Schema: storage; Owner: supabase_storage_admin
--

CREATE FUNCTION storage.delete_prefix(_bucket_id text, _name text) RETURNS boolean
    LANGUAGE plpgsql SECURITY DEFINER
    AS $$
BEGIN
    -- Check if we can delete the prefix
    IF EXISTS(
        SELECT FROM "storage"."prefixes"
        WHERE "prefixes"."bucket_id" = "_bucket_id"
          AND level = "storage"."get_level"("_name") + 1
          AND "prefixes"."name" COLLATE "C" LIKE "_name" || '/%'
        LIMIT 1
    )
    OR EXISTS(
        SELECT FROM "storage"."objects"
        WHERE "objects"."bucket_id" = "_bucket_id"
          AND "storage"."get_level"("objects"."name") = "storage"."get_level"("_name") + 1
          AND "objects"."name" COLLATE "C" LIKE "_name" || '/%'
        LIMIT 1
    ) THEN
    -- There are sub-objects, skip deletion
    RETURN false;
    ELSE
        DELETE FROM "storage"."prefixes"
        WHERE "prefixes"."bucket_id" = "_bucket_id"
          AND level = "storage"."get_level"("_name")
          AND "prefixes"."name" = "_name";
        RETURN true;
    END IF;
END;
$$;


ALTER FUNCTION storage.delete_prefix(_bucket_id text, _name text) OWNER TO supabase_storage_admin;

--
-- Name: delete_prefix_hierarchy_trigger(); Type: FUNCTION; Schema: storage; Owner: supabase_storage_admin
--

CREATE FUNCTION storage.delete_prefix_hierarchy_trigger() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE
    prefix text;
BEGIN
    prefix := "storage"."get_prefix"(OLD."name");

    IF coalesce(prefix, '') != '' THEN
        PERFORM "storage"."delete_prefix"(OLD."bucket_id", prefix);
    END IF;

    RETURN OLD;
END;
$$;


ALTER FUNCTION storage.delete_prefix_hierarchy_trigger() OWNER TO supabase_storage_admin;

--
-- Name: enforce_bucket_name_length(); Type: FUNCTION; Schema: storage; Owner: supabase_storage_admin
--

CREATE FUNCTION storage.enforce_bucket_name_length() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
begin
    if length(new.name) > 100 then
        raise exception 'bucket name "%" is too long (% characters). Max is 100.', new.name, length(new.name);
    end if;
    return new;
end;
$$;


ALTER FUNCTION storage.enforce_bucket_name_length() OWNER TO supabase_storage_admin;

--
-- Name: extension(text); Type: FUNCTION; Schema: storage; Owner: supabase_storage_admin
--

CREATE FUNCTION storage.extension(name text) RETURNS text
    LANGUAGE plpgsql IMMUTABLE
    AS $$
DECLARE
    _parts text[];
    _filename text;
BEGIN
    SELECT string_to_array(name, '/') INTO _parts;
    SELECT _parts[array_length(_parts,1)] INTO _filename;
    RETURN reverse(split_part(reverse(_filename), '.', 1));
END
$$;


ALTER FUNCTION storage.extension(name text) OWNER TO supabase_storage_admin;

--
-- Name: filename(text); Type: FUNCTION; Schema: storage; Owner: supabase_storage_admin
--

CREATE FUNCTION storage.filename(name text) RETURNS text
    LANGUAGE plpgsql
    AS $$
DECLARE
_parts text[];
BEGIN
	select string_to_array(name, '/') into _parts;
	return _parts[array_length(_parts,1)];
END
$$;


ALTER FUNCTION storage.filename(name text) OWNER TO supabase_storage_admin;

--
-- Name: foldername(text); Type: FUNCTION; Schema: storage; Owner: supabase_storage_admin
--

CREATE FUNCTION storage.foldername(name text) RETURNS text[]
    LANGUAGE plpgsql IMMUTABLE
    AS $$
DECLARE
    _parts text[];
BEGIN
    -- Split on "/" to get path segments
    SELECT string_to_array(name, '/') INTO _parts;
    -- Return everything except the last segment
    RETURN _parts[1 : array_length(_parts,1) - 1];
END
$$;


ALTER FUNCTION storage.foldername(name text) OWNER TO supabase_storage_admin;

--
-- Name: get_level(text); Type: FUNCTION; Schema: storage; Owner: supabase_storage_admin
--

CREATE FUNCTION storage.get_level(name text) RETURNS integer
    LANGUAGE sql IMMUTABLE STRICT
    AS $$
SELECT array_length(string_to_array("name", '/'), 1);
$$;


ALTER FUNCTION storage.get_level(name text) OWNER TO supabase_storage_admin;

--
-- Name: get_prefix(text); Type: FUNCTION; Schema: storage; Owner: supabase_storage_admin
--

CREATE FUNCTION storage.get_prefix(name text) RETURNS text
    LANGUAGE sql IMMUTABLE STRICT
    AS $_$
SELECT
    CASE WHEN strpos("name", '/') > 0 THEN
             regexp_replace("name", '[\/]{1}[^\/]+\/?$', '')
         ELSE
             ''
        END;
$_$;


ALTER FUNCTION storage.get_prefix(name text) OWNER TO supabase_storage_admin;

--
-- Name: get_prefixes(text); Type: FUNCTION; Schema: storage; Owner: supabase_storage_admin
--

CREATE FUNCTION storage.get_prefixes(name text) RETURNS text[]
    LANGUAGE plpgsql IMMUTABLE STRICT
    AS $$
DECLARE
    parts text[];
    prefixes text[];
    prefix text;
BEGIN
    -- Split the name into parts by '/'
    parts := string_to_array("name", '/');
    prefixes := '{}';

    -- Construct the prefixes, stopping one level below the last part
    FOR i IN 1..array_length(parts, 1) - 1 LOOP
            prefix := array_to_string(parts[1:i], '/');
            prefixes := array_append(prefixes, prefix);
    END LOOP;

    RETURN prefixes;
END;
$$;


ALTER FUNCTION storage.get_prefixes(name text) OWNER TO supabase_storage_admin;

--
-- Name: get_size_by_bucket(); Type: FUNCTION; Schema: storage; Owner: supabase_storage_admin
--

CREATE FUNCTION storage.get_size_by_bucket() RETURNS TABLE(size bigint, bucket_id text)
    LANGUAGE plpgsql STABLE
    AS $$
BEGIN
    return query
        select sum((metadata->>'size')::bigint) as size, obj.bucket_id
        from "storage".objects as obj
        group by obj.bucket_id;
END
$$;


ALTER FUNCTION storage.get_size_by_bucket() OWNER TO supabase_storage_admin;

--
-- Name: list_multipart_uploads_with_delimiter(text, text, text, integer, text, text); Type: FUNCTION; Schema: storage; Owner: supabase_storage_admin
--

CREATE FUNCTION storage.list_multipart_uploads_with_delimiter(bucket_id text, prefix_param text, delimiter_param text, max_keys integer DEFAULT 100, next_key_token text DEFAULT ''::text, next_upload_token text DEFAULT ''::text) RETURNS TABLE(key text, id text, created_at timestamp with time zone)
    LANGUAGE plpgsql
    AS $_$
BEGIN
    RETURN QUERY EXECUTE
        'SELECT DISTINCT ON(key COLLATE "C") * from (
            SELECT
                CASE
                    WHEN position($2 IN substring(key from length($1) + 1)) > 0 THEN
                        substring(key from 1 for length($1) + position($2 IN substring(key from length($1) + 1)))
                    ELSE
                        key
                END AS key, id, created_at
            FROM
                storage.s3_multipart_uploads
            WHERE
                bucket_id = $5 AND
                key ILIKE $1 || ''%'' AND
                CASE
                    WHEN $4 != '''' AND $6 = '''' THEN
                        CASE
                            WHEN position($2 IN substring(key from length($1) + 1)) > 0 THEN
                                substring(key from 1 for length($1) + position($2 IN substring(key from length($1) + 1))) COLLATE "C" > $4
                            ELSE
                                key COLLATE "C" > $4
                            END
                    ELSE
                        true
                END AND
                CASE
                    WHEN $6 != '''' THEN
                        id COLLATE "C" > $6
                    ELSE
                        true
                    END
            ORDER BY
                key COLLATE "C" ASC, created_at ASC) as e order by key COLLATE "C" LIMIT $3'
        USING prefix_param, delimiter_param, max_keys, next_key_token, bucket_id, next_upload_token;
END;
$_$;


ALTER FUNCTION storage.list_multipart_uploads_with_delimiter(bucket_id text, prefix_param text, delimiter_param text, max_keys integer, next_key_token text, next_upload_token text) OWNER TO supabase_storage_admin;

--
-- Name: list_objects_with_delimiter(text, text, text, integer, text, text); Type: FUNCTION; Schema: storage; Owner: supabase_storage_admin
--

CREATE FUNCTION storage.list_objects_with_delimiter(bucket_id text, prefix_param text, delimiter_param text, max_keys integer DEFAULT 100, start_after text DEFAULT ''::text, next_token text DEFAULT ''::text) RETURNS TABLE(name text, id uuid, metadata jsonb, updated_at timestamp with time zone)
    LANGUAGE plpgsql
    AS $_$
BEGIN
    RETURN QUERY EXECUTE
        'SELECT DISTINCT ON(name COLLATE "C") * from (
            SELECT
                CASE
                    WHEN position($2 IN substring(name from length($1) + 1)) > 0 THEN
                        substring(name from 1 for length($1) + position($2 IN substring(name from length($1) + 1)))
                    ELSE
                        name
                END AS name, id, metadata, updated_at
            FROM
                storage.objects
            WHERE
                bucket_id = $5 AND
                name ILIKE $1 || ''%'' AND
                CASE
                    WHEN $6 != '''' THEN
                    name COLLATE "C" > $6
                ELSE true END
                AND CASE
                    WHEN $4 != '''' THEN
                        CASE
                            WHEN position($2 IN substring(name from length($1) + 1)) > 0 THEN
                                substring(name from 1 for length($1) + position($2 IN substring(name from length($1) + 1))) COLLATE "C" > $4
                            ELSE
                                name COLLATE "C" > $4
                            END
                    ELSE
                        true
                END
            ORDER BY
                name COLLATE "C" ASC) as e order by name COLLATE "C" LIMIT $3'
        USING prefix_param, delimiter_param, max_keys, next_token, bucket_id, start_after;
END;
$_$;


ALTER FUNCTION storage.list_objects_with_delimiter(bucket_id text, prefix_param text, delimiter_param text, max_keys integer, start_after text, next_token text) OWNER TO supabase_storage_admin;

--
-- Name: lock_top_prefixes(text[], text[]); Type: FUNCTION; Schema: storage; Owner: supabase_storage_admin
--

CREATE FUNCTION storage.lock_top_prefixes(bucket_ids text[], names text[]) RETURNS void
    LANGUAGE plpgsql SECURITY DEFINER
    AS $$
DECLARE
    v_bucket text;
    v_top text;
BEGIN
    FOR v_bucket, v_top IN
        SELECT DISTINCT t.bucket_id,
            split_part(t.name, '/', 1) AS top
        FROM unnest(bucket_ids, names) AS t(bucket_id, name)
        WHERE t.name <> ''
        ORDER BY 1, 2
        LOOP
            PERFORM pg_advisory_xact_lock(hashtextextended(v_bucket || '/' || v_top, 0));
        END LOOP;
END;
$$;


ALTER FUNCTION storage.lock_top_prefixes(bucket_ids text[], names text[]) OWNER TO supabase_storage_admin;

--
-- Name: objects_delete_cleanup(); Type: FUNCTION; Schema: storage; Owner: supabase_storage_admin
--

CREATE FUNCTION storage.objects_delete_cleanup() RETURNS trigger
    LANGUAGE plpgsql SECURITY DEFINER
    AS $$
DECLARE
    v_bucket_ids text[];
    v_names      text[];
BEGIN
    IF current_setting('storage.gc.prefixes', true) = '1' THEN
        RETURN NULL;
    END IF;

    PERFORM set_config('storage.gc.prefixes', '1', true);

    SELECT COALESCE(array_agg(d.bucket_id), '{}'),
           COALESCE(array_agg(d.name), '{}')
    INTO v_bucket_ids, v_names
    FROM deleted AS d
    WHERE d.name <> '';

    PERFORM storage.lock_top_prefixes(v_bucket_ids, v_names);
    PERFORM storage.delete_leaf_prefixes(v_bucket_ids, v_names);

    RETURN NULL;
END;
$$;


ALTER FUNCTION storage.objects_delete_cleanup() OWNER TO supabase_storage_admin;

--
-- Name: objects_insert_prefix_trigger(); Type: FUNCTION; Schema: storage; Owner: supabase_storage_admin
--

CREATE FUNCTION storage.objects_insert_prefix_trigger() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    PERFORM "storage"."add_prefixes"(NEW."bucket_id", NEW."name");
    NEW.level := "storage"."get_level"(NEW."name");

    RETURN NEW;
END;
$$;


ALTER FUNCTION storage.objects_insert_prefix_trigger() OWNER TO supabase_storage_admin;

--
-- Name: objects_update_cleanup(); Type: FUNCTION; Schema: storage; Owner: supabase_storage_admin
--

CREATE FUNCTION storage.objects_update_cleanup() RETURNS trigger
    LANGUAGE plpgsql SECURITY DEFINER
    AS $$
DECLARE
    -- NEW - OLD (destinations to create prefixes for)
    v_add_bucket_ids text[];
    v_add_names      text[];

    -- OLD - NEW (sources to prune)
    v_src_bucket_ids text[];
    v_src_names      text[];
BEGIN
    IF TG_OP <> 'UPDATE' THEN
        RETURN NULL;
    END IF;

    -- 1) Compute NEW−OLD (added paths) and OLD−NEW (moved-away paths)
    WITH added AS (
        SELECT n.bucket_id, n.name
        FROM new_rows n
        WHERE n.name <> '' AND position('/' in n.name) > 0
        EXCEPT
        SELECT o.bucket_id, o.name FROM old_rows o WHERE o.name <> ''
    ),
    moved AS (
         SELECT o.bucket_id, o.name
         FROM old_rows o
         WHERE o.name <> ''
         EXCEPT
         SELECT n.bucket_id, n.name FROM new_rows n WHERE n.name <> ''
    )
    SELECT
        -- arrays for ADDED (dest) in stable order
        COALESCE( (SELECT array_agg(a.bucket_id ORDER BY a.bucket_id, a.name) FROM added a), '{}' ),
        COALESCE( (SELECT array_agg(a.name      ORDER BY a.bucket_id, a.name) FROM added a), '{}' ),
        -- arrays for MOVED (src) in stable order
        COALESCE( (SELECT array_agg(m.bucket_id ORDER BY m.bucket_id, m.name) FROM moved m), '{}' ),
        COALESCE( (SELECT array_agg(m.name      ORDER BY m.bucket_id, m.name) FROM moved m), '{}' )
    INTO v_add_bucket_ids, v_add_names, v_src_bucket_ids, v_src_names;

    -- Nothing to do?
    IF (array_length(v_add_bucket_ids, 1) IS NULL) AND (array_length(v_src_bucket_ids, 1) IS NULL) THEN
        RETURN NULL;
    END IF;

    -- 2) Take per-(bucket, top) locks: ALL prefixes in consistent global order to prevent deadlocks
    DECLARE
        v_all_bucket_ids text[];
        v_all_names text[];
    BEGIN
        -- Combine source and destination arrays for consistent lock ordering
        v_all_bucket_ids := COALESCE(v_src_bucket_ids, '{}') || COALESCE(v_add_bucket_ids, '{}');
        v_all_names := COALESCE(v_src_names, '{}') || COALESCE(v_add_names, '{}');

        -- Single lock call ensures consistent global ordering across all transactions
        IF array_length(v_all_bucket_ids, 1) IS NOT NULL THEN
            PERFORM storage.lock_top_prefixes(v_all_bucket_ids, v_all_names);
        END IF;
    END;

    -- 3) Create destination prefixes (NEW−OLD) BEFORE pruning sources
    IF array_length(v_add_bucket_ids, 1) IS NOT NULL THEN
        WITH candidates AS (
            SELECT DISTINCT t.bucket_id, unnest(storage.get_prefixes(t.name)) AS name
            FROM unnest(v_add_bucket_ids, v_add_names) AS t(bucket_id, name)
            WHERE name <> ''
        )
        INSERT INTO storage.prefixes (bucket_id, name)
        SELECT c.bucket_id, c.name
        FROM candidates c
        ON CONFLICT DO NOTHING;
    END IF;

    -- 4) Prune source prefixes bottom-up for OLD−NEW
    IF array_length(v_src_bucket_ids, 1) IS NOT NULL THEN
        -- re-entrancy guard so DELETE on prefixes won't recurse
        IF current_setting('storage.gc.prefixes', true) <> '1' THEN
            PERFORM set_config('storage.gc.prefixes', '1', true);
        END IF;

        PERFORM storage.delete_leaf_prefixes(v_src_bucket_ids, v_src_names);
    END IF;

    RETURN NULL;
END;
$$;


ALTER FUNCTION storage.objects_update_cleanup() OWNER TO supabase_storage_admin;

--
-- Name: objects_update_level_trigger(); Type: FUNCTION; Schema: storage; Owner: supabase_storage_admin
--

CREATE FUNCTION storage.objects_update_level_trigger() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    -- Ensure this is an update operation and the name has changed
    IF TG_OP = 'UPDATE' AND (NEW."name" <> OLD."name" OR NEW."bucket_id" <> OLD."bucket_id") THEN
        -- Set the new level
        NEW."level" := "storage"."get_level"(NEW."name");
    END IF;
    RETURN NEW;
END;
$$;


ALTER FUNCTION storage.objects_update_level_trigger() OWNER TO supabase_storage_admin;

--
-- Name: objects_update_prefix_trigger(); Type: FUNCTION; Schema: storage; Owner: supabase_storage_admin
--

CREATE FUNCTION storage.objects_update_prefix_trigger() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE
    old_prefixes TEXT[];
BEGIN
    -- Ensure this is an update operation and the name has changed
    IF TG_OP = 'UPDATE' AND (NEW."name" <> OLD."name" OR NEW."bucket_id" <> OLD."bucket_id") THEN
        -- Retrieve old prefixes
        old_prefixes := "storage"."get_prefixes"(OLD."name");

        -- Remove old prefixes that are only used by this object
        WITH all_prefixes as (
            SELECT unnest(old_prefixes) as prefix
        ),
        can_delete_prefixes as (
             SELECT prefix
             FROM all_prefixes
             WHERE NOT EXISTS (
                 SELECT 1 FROM "storage"."objects"
                 WHERE "bucket_id" = OLD."bucket_id"
                   AND "name" <> OLD."name"
                   AND "name" LIKE (prefix || '%')
             )
         )
        DELETE FROM "storage"."prefixes" WHERE name IN (SELECT prefix FROM can_delete_prefixes);

        -- Add new prefixes
        PERFORM "storage"."add_prefixes"(NEW."bucket_id", NEW."name");
    END IF;
    -- Set the new level
    NEW."level" := "storage"."get_level"(NEW."name");

    RETURN NEW;
END;
$$;


ALTER FUNCTION storage.objects_update_prefix_trigger() OWNER TO supabase_storage_admin;

--
-- Name: operation(); Type: FUNCTION; Schema: storage; Owner: supabase_storage_admin
--

CREATE FUNCTION storage.operation() RETURNS text
    LANGUAGE plpgsql STABLE
    AS $$
BEGIN
    RETURN current_setting('storage.operation', true);
END;
$$;


ALTER FUNCTION storage.operation() OWNER TO supabase_storage_admin;

--
-- Name: prefixes_delete_cleanup(); Type: FUNCTION; Schema: storage; Owner: supabase_storage_admin
--

CREATE FUNCTION storage.prefixes_delete_cleanup() RETURNS trigger
    LANGUAGE plpgsql SECURITY DEFINER
    AS $$
DECLARE
    v_bucket_ids text[];
    v_names      text[];
BEGIN
    IF current_setting('storage.gc.prefixes', true) = '1' THEN
        RETURN NULL;
    END IF;

    PERFORM set_config('storage.gc.prefixes', '1', true);

    SELECT COALESCE(array_agg(d.bucket_id), '{}'),
           COALESCE(array_agg(d.name), '{}')
    INTO v_bucket_ids, v_names
    FROM deleted AS d
    WHERE d.name <> '';

    PERFORM storage.lock_top_prefixes(v_bucket_ids, v_names);
    PERFORM storage.delete_leaf_prefixes(v_bucket_ids, v_names);

    RETURN NULL;
END;
$$;


ALTER FUNCTION storage.prefixes_delete_cleanup() OWNER TO supabase_storage_admin;

--
-- Name: prefixes_insert_trigger(); Type: FUNCTION; Schema: storage; Owner: supabase_storage_admin
--

CREATE FUNCTION storage.prefixes_insert_trigger() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    PERFORM "storage"."add_prefixes"(NEW."bucket_id", NEW."name");
    RETURN NEW;
END;
$$;


ALTER FUNCTION storage.prefixes_insert_trigger() OWNER TO supabase_storage_admin;

--
-- Name: search(text, text, integer, integer, integer, text, text, text); Type: FUNCTION; Schema: storage; Owner: supabase_storage_admin
--

CREATE FUNCTION storage.search(prefix text, bucketname text, limits integer DEFAULT 100, levels integer DEFAULT 1, offsets integer DEFAULT 0, search text DEFAULT ''::text, sortcolumn text DEFAULT 'name'::text, sortorder text DEFAULT 'asc'::text) RETURNS TABLE(name text, id uuid, updated_at timestamp with time zone, created_at timestamp with time zone, last_accessed_at timestamp with time zone, metadata jsonb)
    LANGUAGE plpgsql
    AS $$
declare
    can_bypass_rls BOOLEAN;
begin
    SELECT rolbypassrls
    INTO can_bypass_rls
    FROM pg_roles
    WHERE rolname = coalesce(nullif(current_setting('role', true), 'none'), current_user);

    IF can_bypass_rls THEN
        RETURN QUERY SELECT * FROM storage.search_v1_optimised(prefix, bucketname, limits, levels, offsets, search, sortcolumn, sortorder);
    ELSE
        RETURN QUERY SELECT * FROM storage.search_legacy_v1(prefix, bucketname, limits, levels, offsets, search, sortcolumn, sortorder);
    END IF;
end;
$$;


ALTER FUNCTION storage.search(prefix text, bucketname text, limits integer, levels integer, offsets integer, search text, sortcolumn text, sortorder text) OWNER TO supabase_storage_admin;

--
-- Name: search_legacy_v1(text, text, integer, integer, integer, text, text, text); Type: FUNCTION; Schema: storage; Owner: supabase_storage_admin
--

CREATE FUNCTION storage.search_legacy_v1(prefix text, bucketname text, limits integer DEFAULT 100, levels integer DEFAULT 1, offsets integer DEFAULT 0, search text DEFAULT ''::text, sortcolumn text DEFAULT 'name'::text, sortorder text DEFAULT 'asc'::text) RETURNS TABLE(name text, id uuid, updated_at timestamp with time zone, created_at timestamp with time zone, last_accessed_at timestamp with time zone, metadata jsonb)
    LANGUAGE plpgsql STABLE
    AS $_$
declare
    v_order_by text;
    v_sort_order text;
begin
    case
        when sortcolumn = 'name' then
            v_order_by = 'name';
        when sortcolumn = 'updated_at' then
            v_order_by = 'updated_at';
        when sortcolumn = 'created_at' then
            v_order_by = 'created_at';
        when sortcolumn = 'last_accessed_at' then
            v_order_by = 'last_accessed_at';
        else
            v_order_by = 'name';
        end case;

    case
        when sortorder = 'asc' then
            v_sort_order = 'asc';
        when sortorder = 'desc' then
            v_sort_order = 'desc';
        else
            v_sort_order = 'asc';
        end case;

    v_order_by = v_order_by || ' ' || v_sort_order;

    return query execute
        'with folders as (
           select path_tokens[$1] as folder
           from storage.objects
             where objects.name ilike $2 || $3 || ''%''
               and bucket_id = $4
               and array_length(objects.path_tokens, 1) <> $1
           group by folder
           order by folder ' || v_sort_order || '
     )
     (select folder as "name",
            null as id,
            null as updated_at,
            null as created_at,
            null as last_accessed_at,
            null as metadata from folders)
     union all
     (select path_tokens[$1] as "name",
            id,
            updated_at,
            created_at,
            last_accessed_at,
            metadata
     from storage.objects
     where objects.name ilike $2 || $3 || ''%''
       and bucket_id = $4
       and array_length(objects.path_tokens, 1) = $1
     order by ' || v_order_by || ')
     limit $5
     offset $6' using levels, prefix, search, bucketname, limits, offsets;
end;
$_$;


ALTER FUNCTION storage.search_legacy_v1(prefix text, bucketname text, limits integer, levels integer, offsets integer, search text, sortcolumn text, sortorder text) OWNER TO supabase_storage_admin;

--
-- Name: search_v1_optimised(text, text, integer, integer, integer, text, text, text); Type: FUNCTION; Schema: storage; Owner: supabase_storage_admin
--

CREATE FUNCTION storage.search_v1_optimised(prefix text, bucketname text, limits integer DEFAULT 100, levels integer DEFAULT 1, offsets integer DEFAULT 0, search text DEFAULT ''::text, sortcolumn text DEFAULT 'name'::text, sortorder text DEFAULT 'asc'::text) RETURNS TABLE(name text, id uuid, updated_at timestamp with time zone, created_at timestamp with time zone, last_accessed_at timestamp with time zone, metadata jsonb)
    LANGUAGE plpgsql STABLE
    AS $_$
declare
    v_order_by text;
    v_sort_order text;
begin
    case
        when sortcolumn = 'name' then
            v_order_by = 'name';
        when sortcolumn = 'updated_at' then
            v_order_by = 'updated_at';
        when sortcolumn = 'created_at' then
            v_order_by = 'created_at';
        when sortcolumn = 'last_accessed_at' then
            v_order_by = 'last_accessed_at';
        else
            v_order_by = 'name';
        end case;

    case
        when sortorder = 'asc' then
            v_sort_order = 'asc';
        when sortorder = 'desc' then
            v_sort_order = 'desc';
        else
            v_sort_order = 'asc';
        end case;

    v_order_by = v_order_by || ' ' || v_sort_order;

    return query execute
        'with folders as (
           select (string_to_array(name, ''/''))[level] as name
           from storage.prefixes
             where lower(prefixes.name) like lower($2 || $3) || ''%''
               and bucket_id = $4
               and level = $1
           order by name ' || v_sort_order || '
     )
     (select name,
            null as id,
            null as updated_at,
            null as created_at,
            null as last_accessed_at,
            null as metadata from folders)
     union all
     (select path_tokens[level] as "name",
            id,
            updated_at,
            created_at,
            last_accessed_at,
            metadata
     from storage.objects
     where lower(objects.name) like lower($2 || $3) || ''%''
       and bucket_id = $4
       and level = $1
     order by ' || v_order_by || ')
     limit $5
     offset $6' using levels, prefix, search, bucketname, limits, offsets;
end;
$_$;


ALTER FUNCTION storage.search_v1_optimised(prefix text, bucketname text, limits integer, levels integer, offsets integer, search text, sortcolumn text, sortorder text) OWNER TO supabase_storage_admin;

--
-- Name: search_v2(text, text, integer, integer, text, text, text, text); Type: FUNCTION; Schema: storage; Owner: supabase_storage_admin
--

CREATE FUNCTION storage.search_v2(prefix text, bucket_name text, limits integer DEFAULT 100, levels integer DEFAULT 1, start_after text DEFAULT ''::text, sort_order text DEFAULT 'asc'::text, sort_column text DEFAULT 'name'::text, sort_column_after text DEFAULT ''::text) RETURNS TABLE(key text, name text, id uuid, updated_at timestamp with time zone, created_at timestamp with time zone, last_accessed_at timestamp with time zone, metadata jsonb)
    LANGUAGE plpgsql STABLE
    AS $_$
DECLARE
    sort_col text;
    sort_ord text;
    cursor_op text;
    cursor_expr text;
    sort_expr text;
BEGIN
    -- Validate sort_order
    sort_ord := lower(sort_order);
    IF sort_ord NOT IN ('asc', 'desc') THEN
        sort_ord := 'asc';
    END IF;

    -- Determine cursor comparison operator
    IF sort_ord = 'asc' THEN
        cursor_op := '>';
    ELSE
        cursor_op := '<';
    END IF;
    
    sort_col := lower(sort_column);
    -- Validate sort column  
    IF sort_col IN ('updated_at', 'created_at') THEN
        cursor_expr := format(
            '($5 = '''' OR ROW(date_trunc(''milliseconds'', %I), name COLLATE "C") %s ROW(COALESCE(NULLIF($6, '''')::timestamptz, ''epoch''::timestamptz), $5))',
            sort_col, cursor_op
        );
        sort_expr := format(
            'COALESCE(date_trunc(''milliseconds'', %I), ''epoch''::timestamptz) %s, name COLLATE "C" %s',
            sort_col, sort_ord, sort_ord
        );
    ELSE
        cursor_expr := format('($5 = '''' OR name COLLATE "C" %s $5)', cursor_op);
        sort_expr := format('name COLLATE "C" %s', sort_ord);
    END IF;

    RETURN QUERY EXECUTE format(
        $sql$
        SELECT * FROM (
            (
                SELECT
                    split_part(name, '/', $4) AS key,
                    name,
                    NULL::uuid AS id,
                    updated_at,
                    created_at,
                    NULL::timestamptz AS last_accessed_at,
                    NULL::jsonb AS metadata
                FROM storage.prefixes
                WHERE name COLLATE "C" LIKE $1 || '%%'
                    AND bucket_id = $2
                    AND level = $4
                    AND %s
                ORDER BY %s
                LIMIT $3
            )
            UNION ALL
            (
                SELECT
                    split_part(name, '/', $4) AS key,
                    name,
                    id,
                    updated_at,
                    created_at,
                    last_accessed_at,
                    metadata
                FROM storage.objects
                WHERE name COLLATE "C" LIKE $1 || '%%'
                    AND bucket_id = $2
                    AND level = $4
                    AND %s
                ORDER BY %s
                LIMIT $3
            )
        ) obj
        ORDER BY %s
        LIMIT $3
        $sql$,
        cursor_expr,    -- prefixes WHERE
        sort_expr,      -- prefixes ORDER BY
        cursor_expr,    -- objects WHERE
        sort_expr,      -- objects ORDER BY
        sort_expr       -- final ORDER BY
    )
    USING prefix, bucket_name, limits, levels, start_after, sort_column_after;
END;
$_$;


ALTER FUNCTION storage.search_v2(prefix text, bucket_name text, limits integer, levels integer, start_after text, sort_order text, sort_column text, sort_column_after text) OWNER TO supabase_storage_admin;

--
-- Name: update_updated_at_column(); Type: FUNCTION; Schema: storage; Owner: supabase_storage_admin
--

CREATE FUNCTION storage.update_updated_at_column() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    NEW.updated_at = now();
    RETURN NEW; 
END;
$$;


ALTER FUNCTION storage.update_updated_at_column() OWNER TO supabase_storage_admin;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: audit_log_entries; Type: TABLE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TABLE auth.audit_log_entries (
    instance_id uuid,
    id uuid NOT NULL,
    payload json,
    created_at timestamp with time zone,
    ip_address character varying(64) DEFAULT ''::character varying NOT NULL
);


ALTER TABLE auth.audit_log_entries OWNER TO supabase_auth_admin;

--
-- Name: TABLE audit_log_entries; Type: COMMENT; Schema: auth; Owner: supabase_auth_admin
--

COMMENT ON TABLE auth.audit_log_entries IS 'Auth: Audit trail for user actions.';


--
-- Name: flow_state; Type: TABLE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TABLE auth.flow_state (
    id uuid NOT NULL,
    user_id uuid,
    auth_code text NOT NULL,
    code_challenge_method auth.code_challenge_method NOT NULL,
    code_challenge text NOT NULL,
    provider_type text NOT NULL,
    provider_access_token text,
    provider_refresh_token text,
    created_at timestamp with time zone,
    updated_at timestamp with time zone,
    authentication_method text NOT NULL,
    auth_code_issued_at timestamp with time zone
);


ALTER TABLE auth.flow_state OWNER TO supabase_auth_admin;

--
-- Name: TABLE flow_state; Type: COMMENT; Schema: auth; Owner: supabase_auth_admin
--

COMMENT ON TABLE auth.flow_state IS 'stores metadata for pkce logins';


--
-- Name: identities; Type: TABLE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TABLE auth.identities (
    provider_id text NOT NULL,
    user_id uuid NOT NULL,
    identity_data jsonb NOT NULL,
    provider text NOT NULL,
    last_sign_in_at timestamp with time zone,
    created_at timestamp with time zone,
    updated_at timestamp with time zone,
    email text GENERATED ALWAYS AS (lower((identity_data ->> 'email'::text))) STORED,
    id uuid DEFAULT gen_random_uuid() NOT NULL
);


ALTER TABLE auth.identities OWNER TO supabase_auth_admin;

--
-- Name: TABLE identities; Type: COMMENT; Schema: auth; Owner: supabase_auth_admin
--

COMMENT ON TABLE auth.identities IS 'Auth: Stores identities associated to a user.';


--
-- Name: COLUMN identities.email; Type: COMMENT; Schema: auth; Owner: supabase_auth_admin
--

COMMENT ON COLUMN auth.identities.email IS 'Auth: Email is a generated column that references the optional email property in the identity_data';


--
-- Name: instances; Type: TABLE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TABLE auth.instances (
    id uuid NOT NULL,
    uuid uuid,
    raw_base_config text,
    created_at timestamp with time zone,
    updated_at timestamp with time zone
);


ALTER TABLE auth.instances OWNER TO supabase_auth_admin;

--
-- Name: TABLE instances; Type: COMMENT; Schema: auth; Owner: supabase_auth_admin
--

COMMENT ON TABLE auth.instances IS 'Auth: Manages users across multiple sites.';


--
-- Name: mfa_amr_claims; Type: TABLE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TABLE auth.mfa_amr_claims (
    session_id uuid NOT NULL,
    created_at timestamp with time zone NOT NULL,
    updated_at timestamp with time zone NOT NULL,
    authentication_method text NOT NULL,
    id uuid NOT NULL
);


ALTER TABLE auth.mfa_amr_claims OWNER TO supabase_auth_admin;

--
-- Name: TABLE mfa_amr_claims; Type: COMMENT; Schema: auth; Owner: supabase_auth_admin
--

COMMENT ON TABLE auth.mfa_amr_claims IS 'auth: stores authenticator method reference claims for multi factor authentication';


--
-- Name: mfa_challenges; Type: TABLE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TABLE auth.mfa_challenges (
    id uuid NOT NULL,
    factor_id uuid NOT NULL,
    created_at timestamp with time zone NOT NULL,
    verified_at timestamp with time zone,
    ip_address inet NOT NULL,
    otp_code text,
    web_authn_session_data jsonb
);


ALTER TABLE auth.mfa_challenges OWNER TO supabase_auth_admin;

--
-- Name: TABLE mfa_challenges; Type: COMMENT; Schema: auth; Owner: supabase_auth_admin
--

COMMENT ON TABLE auth.mfa_challenges IS 'auth: stores metadata about challenge requests made';


--
-- Name: mfa_factors; Type: TABLE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TABLE auth.mfa_factors (
    id uuid NOT NULL,
    user_id uuid NOT NULL,
    friendly_name text,
    factor_type auth.factor_type NOT NULL,
    status auth.factor_status NOT NULL,
    created_at timestamp with time zone NOT NULL,
    updated_at timestamp with time zone NOT NULL,
    secret text,
    phone text,
    last_challenged_at timestamp with time zone,
    web_authn_credential jsonb,
    web_authn_aaguid uuid,
    last_webauthn_challenge_data jsonb
);


ALTER TABLE auth.mfa_factors OWNER TO supabase_auth_admin;

--
-- Name: TABLE mfa_factors; Type: COMMENT; Schema: auth; Owner: supabase_auth_admin
--

COMMENT ON TABLE auth.mfa_factors IS 'auth: stores metadata about factors';


--
-- Name: COLUMN mfa_factors.last_webauthn_challenge_data; Type: COMMENT; Schema: auth; Owner: supabase_auth_admin
--

COMMENT ON COLUMN auth.mfa_factors.last_webauthn_challenge_data IS 'Stores the latest WebAuthn challenge data including attestation/assertion for customer verification';


--
-- Name: oauth_authorizations; Type: TABLE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TABLE auth.oauth_authorizations (
    id uuid NOT NULL,
    authorization_id text NOT NULL,
    client_id uuid NOT NULL,
    user_id uuid,
    redirect_uri text NOT NULL,
    scope text NOT NULL,
    state text,
    resource text,
    code_challenge text,
    code_challenge_method auth.code_challenge_method,
    response_type auth.oauth_response_type DEFAULT 'code'::auth.oauth_response_type NOT NULL,
    status auth.oauth_authorization_status DEFAULT 'pending'::auth.oauth_authorization_status NOT NULL,
    authorization_code text,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    expires_at timestamp with time zone DEFAULT (now() + '00:03:00'::interval) NOT NULL,
    approved_at timestamp with time zone,
    CONSTRAINT oauth_authorizations_authorization_code_length CHECK ((char_length(authorization_code) <= 255)),
    CONSTRAINT oauth_authorizations_code_challenge_length CHECK ((char_length(code_challenge) <= 128)),
    CONSTRAINT oauth_authorizations_expires_at_future CHECK ((expires_at > created_at)),
    CONSTRAINT oauth_authorizations_redirect_uri_length CHECK ((char_length(redirect_uri) <= 2048)),
    CONSTRAINT oauth_authorizations_resource_length CHECK ((char_length(resource) <= 2048)),
    CONSTRAINT oauth_authorizations_scope_length CHECK ((char_length(scope) <= 4096)),
    CONSTRAINT oauth_authorizations_state_length CHECK ((char_length(state) <= 4096))
);


ALTER TABLE auth.oauth_authorizations OWNER TO supabase_auth_admin;

--
-- Name: oauth_clients; Type: TABLE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TABLE auth.oauth_clients (
    id uuid NOT NULL,
    client_secret_hash text,
    registration_type auth.oauth_registration_type NOT NULL,
    redirect_uris text NOT NULL,
    grant_types text NOT NULL,
    client_name text,
    client_uri text,
    logo_uri text,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL,
    deleted_at timestamp with time zone,
    client_type auth.oauth_client_type DEFAULT 'confidential'::auth.oauth_client_type NOT NULL,
    CONSTRAINT oauth_clients_client_name_length CHECK ((char_length(client_name) <= 1024)),
    CONSTRAINT oauth_clients_client_uri_length CHECK ((char_length(client_uri) <= 2048)),
    CONSTRAINT oauth_clients_logo_uri_length CHECK ((char_length(logo_uri) <= 2048))
);


ALTER TABLE auth.oauth_clients OWNER TO supabase_auth_admin;

--
-- Name: oauth_consents; Type: TABLE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TABLE auth.oauth_consents (
    id uuid NOT NULL,
    user_id uuid NOT NULL,
    client_id uuid NOT NULL,
    scopes text NOT NULL,
    granted_at timestamp with time zone DEFAULT now() NOT NULL,
    revoked_at timestamp with time zone,
    CONSTRAINT oauth_consents_revoked_after_granted CHECK (((revoked_at IS NULL) OR (revoked_at >= granted_at))),
    CONSTRAINT oauth_consents_scopes_length CHECK ((char_length(scopes) <= 2048)),
    CONSTRAINT oauth_consents_scopes_not_empty CHECK ((char_length(TRIM(BOTH FROM scopes)) > 0))
);


ALTER TABLE auth.oauth_consents OWNER TO supabase_auth_admin;

--
-- Name: one_time_tokens; Type: TABLE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TABLE auth.one_time_tokens (
    id uuid NOT NULL,
    user_id uuid NOT NULL,
    token_type auth.one_time_token_type NOT NULL,
    token_hash text NOT NULL,
    relates_to text NOT NULL,
    created_at timestamp without time zone DEFAULT now() NOT NULL,
    updated_at timestamp without time zone DEFAULT now() NOT NULL,
    CONSTRAINT one_time_tokens_token_hash_check CHECK ((char_length(token_hash) > 0))
);


ALTER TABLE auth.one_time_tokens OWNER TO supabase_auth_admin;

--
-- Name: refresh_tokens; Type: TABLE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TABLE auth.refresh_tokens (
    instance_id uuid,
    id bigint NOT NULL,
    token character varying(255),
    user_id character varying(255),
    revoked boolean,
    created_at timestamp with time zone,
    updated_at timestamp with time zone,
    parent character varying(255),
    session_id uuid
);


ALTER TABLE auth.refresh_tokens OWNER TO supabase_auth_admin;

--
-- Name: TABLE refresh_tokens; Type: COMMENT; Schema: auth; Owner: supabase_auth_admin
--

COMMENT ON TABLE auth.refresh_tokens IS 'Auth: Store of tokens used to refresh JWT tokens once they expire.';


--
-- Name: refresh_tokens_id_seq; Type: SEQUENCE; Schema: auth; Owner: supabase_auth_admin
--

CREATE SEQUENCE auth.refresh_tokens_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE auth.refresh_tokens_id_seq OWNER TO supabase_auth_admin;

--
-- Name: refresh_tokens_id_seq; Type: SEQUENCE OWNED BY; Schema: auth; Owner: supabase_auth_admin
--

ALTER SEQUENCE auth.refresh_tokens_id_seq OWNED BY auth.refresh_tokens.id;


--
-- Name: saml_providers; Type: TABLE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TABLE auth.saml_providers (
    id uuid NOT NULL,
    sso_provider_id uuid NOT NULL,
    entity_id text NOT NULL,
    metadata_xml text NOT NULL,
    metadata_url text,
    attribute_mapping jsonb,
    created_at timestamp with time zone,
    updated_at timestamp with time zone,
    name_id_format text,
    CONSTRAINT "entity_id not empty" CHECK ((char_length(entity_id) > 0)),
    CONSTRAINT "metadata_url not empty" CHECK (((metadata_url = NULL::text) OR (char_length(metadata_url) > 0))),
    CONSTRAINT "metadata_xml not empty" CHECK ((char_length(metadata_xml) > 0))
);


ALTER TABLE auth.saml_providers OWNER TO supabase_auth_admin;

--
-- Name: TABLE saml_providers; Type: COMMENT; Schema: auth; Owner: supabase_auth_admin
--

COMMENT ON TABLE auth.saml_providers IS 'Auth: Manages SAML Identity Provider connections.';


--
-- Name: saml_relay_states; Type: TABLE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TABLE auth.saml_relay_states (
    id uuid NOT NULL,
    sso_provider_id uuid NOT NULL,
    request_id text NOT NULL,
    for_email text,
    redirect_to text,
    created_at timestamp with time zone,
    updated_at timestamp with time zone,
    flow_state_id uuid,
    CONSTRAINT "request_id not empty" CHECK ((char_length(request_id) > 0))
);


ALTER TABLE auth.saml_relay_states OWNER TO supabase_auth_admin;

--
-- Name: TABLE saml_relay_states; Type: COMMENT; Schema: auth; Owner: supabase_auth_admin
--

COMMENT ON TABLE auth.saml_relay_states IS 'Auth: Contains SAML Relay State information for each Service Provider initiated login.';


--
-- Name: schema_migrations; Type: TABLE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TABLE auth.schema_migrations (
    version character varying(255) NOT NULL
);


ALTER TABLE auth.schema_migrations OWNER TO supabase_auth_admin;

--
-- Name: TABLE schema_migrations; Type: COMMENT; Schema: auth; Owner: supabase_auth_admin
--

COMMENT ON TABLE auth.schema_migrations IS 'Auth: Manages updates to the auth system.';


--
-- Name: sessions; Type: TABLE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TABLE auth.sessions (
    id uuid NOT NULL,
    user_id uuid NOT NULL,
    created_at timestamp with time zone,
    updated_at timestamp with time zone,
    factor_id uuid,
    aal auth.aal_level,
    not_after timestamp with time zone,
    refreshed_at timestamp without time zone,
    user_agent text,
    ip inet,
    tag text,
    oauth_client_id uuid,
    refresh_token_hmac_key text,
    refresh_token_counter bigint
);


ALTER TABLE auth.sessions OWNER TO supabase_auth_admin;

--
-- Name: TABLE sessions; Type: COMMENT; Schema: auth; Owner: supabase_auth_admin
--

COMMENT ON TABLE auth.sessions IS 'Auth: Stores session data associated to a user.';


--
-- Name: COLUMN sessions.not_after; Type: COMMENT; Schema: auth; Owner: supabase_auth_admin
--

COMMENT ON COLUMN auth.sessions.not_after IS 'Auth: Not after is a nullable column that contains a timestamp after which the session should be regarded as expired.';


--
-- Name: COLUMN sessions.refresh_token_hmac_key; Type: COMMENT; Schema: auth; Owner: supabase_auth_admin
--

COMMENT ON COLUMN auth.sessions.refresh_token_hmac_key IS 'Holds a HMAC-SHA256 key used to sign refresh tokens for this session.';


--
-- Name: COLUMN sessions.refresh_token_counter; Type: COMMENT; Schema: auth; Owner: supabase_auth_admin
--

COMMENT ON COLUMN auth.sessions.refresh_token_counter IS 'Holds the ID (counter) of the last issued refresh token.';


--
-- Name: sso_domains; Type: TABLE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TABLE auth.sso_domains (
    id uuid NOT NULL,
    sso_provider_id uuid NOT NULL,
    domain text NOT NULL,
    created_at timestamp with time zone,
    updated_at timestamp with time zone,
    CONSTRAINT "domain not empty" CHECK ((char_length(domain) > 0))
);


ALTER TABLE auth.sso_domains OWNER TO supabase_auth_admin;

--
-- Name: TABLE sso_domains; Type: COMMENT; Schema: auth; Owner: supabase_auth_admin
--

COMMENT ON TABLE auth.sso_domains IS 'Auth: Manages SSO email address domain mapping to an SSO Identity Provider.';


--
-- Name: sso_providers; Type: TABLE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TABLE auth.sso_providers (
    id uuid NOT NULL,
    resource_id text,
    created_at timestamp with time zone,
    updated_at timestamp with time zone,
    disabled boolean,
    CONSTRAINT "resource_id not empty" CHECK (((resource_id = NULL::text) OR (char_length(resource_id) > 0)))
);


ALTER TABLE auth.sso_providers OWNER TO supabase_auth_admin;

--
-- Name: TABLE sso_providers; Type: COMMENT; Schema: auth; Owner: supabase_auth_admin
--

COMMENT ON TABLE auth.sso_providers IS 'Auth: Manages SSO identity provider information; see saml_providers for SAML.';


--
-- Name: COLUMN sso_providers.resource_id; Type: COMMENT; Schema: auth; Owner: supabase_auth_admin
--

COMMENT ON COLUMN auth.sso_providers.resource_id IS 'Auth: Uniquely identifies a SSO provider according to a user-chosen resource ID (case insensitive), useful in infrastructure as code.';


--
-- Name: users; Type: TABLE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TABLE auth.users (
    instance_id uuid,
    id uuid NOT NULL,
    aud character varying(255),
    role character varying(255),
    email character varying(255),
    encrypted_password character varying(255),
    email_confirmed_at timestamp with time zone,
    invited_at timestamp with time zone,
    confirmation_token character varying(255),
    confirmation_sent_at timestamp with time zone,
    recovery_token character varying(255),
    recovery_sent_at timestamp with time zone,
    email_change_token_new character varying(255),
    email_change character varying(255),
    email_change_sent_at timestamp with time zone,
    last_sign_in_at timestamp with time zone,
    raw_app_meta_data jsonb,
    raw_user_meta_data jsonb,
    is_super_admin boolean,
    created_at timestamp with time zone,
    updated_at timestamp with time zone,
    phone text DEFAULT NULL::character varying,
    phone_confirmed_at timestamp with time zone,
    phone_change text DEFAULT ''::character varying,
    phone_change_token character varying(255) DEFAULT ''::character varying,
    phone_change_sent_at timestamp with time zone,
    confirmed_at timestamp with time zone GENERATED ALWAYS AS (LEAST(email_confirmed_at, phone_confirmed_at)) STORED,
    email_change_token_current character varying(255) DEFAULT ''::character varying,
    email_change_confirm_status smallint DEFAULT 0,
    banned_until timestamp with time zone,
    reauthentication_token character varying(255) DEFAULT ''::character varying,
    reauthentication_sent_at timestamp with time zone,
    is_sso_user boolean DEFAULT false NOT NULL,
    deleted_at timestamp with time zone,
    is_anonymous boolean DEFAULT false NOT NULL,
    CONSTRAINT users_email_change_confirm_status_check CHECK (((email_change_confirm_status >= 0) AND (email_change_confirm_status <= 2)))
);


ALTER TABLE auth.users OWNER TO supabase_auth_admin;

--
-- Name: TABLE users; Type: COMMENT; Schema: auth; Owner: supabase_auth_admin
--

COMMENT ON TABLE auth.users IS 'Auth: Stores user login data within a secure schema.';


--
-- Name: COLUMN users.is_sso_user; Type: COMMENT; Schema: auth; Owner: supabase_auth_admin
--

COMMENT ON COLUMN auth.users.is_sso_user IS 'Auth: Set this column to true when the account comes from SSO. These accounts can have duplicate emails.';


--
-- Name: bookings; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.bookings (
    id bigint NOT NULL,
    customer_id bigint NOT NULL,
    worker_id bigint NOT NULL,
    service_details text NOT NULL,
    booking_time timestamp with time zone NOT NULL,
    status public.booking_status DEFAULT 'pending'::public.booking_status NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    final_cost numeric(10,2),
    payment_status text DEFAULT 'unpaid'::text NOT NULL,
    worker_latitude numeric(10,8),
    worker_longitude numeric(11,8),
    predefined_cost numeric(10,2),
    customer_offer numeric(10,2),
    cost_status text DEFAULT 'pending'::text NOT NULL,
    work_completed_by_worker boolean DEFAULT false,
    rejection_reason text,
    applied_offer_id integer,
    discount_amount numeric(10,2),
    applied_admin_offer_id integer,
    combo_id integer,
    confirmed_at timestamp without time zone,
    cancellation_reason text,
    sub_service_item_id bigint,
    worker_earning numeric(10,2),
    platform_fee numeric(10,2),
    hourly_reminder_sent boolean DEFAULT false,
    daily_reminder_sent boolean DEFAULT false,
    CONSTRAINT check_different_users CHECK ((customer_id <> worker_id))
);


ALTER TABLE public.bookings OWNER TO postgres;

--
-- Name: TABLE bookings; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON TABLE public.bookings IS 'Tracks service appointments between customers and workers.';


--
-- Name: COLUMN bookings.combo_id; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.bookings.combo_id IS 'Identifier for the service combo used for this booking, if any.';


--
-- Name: bookings_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.bookings ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.bookings_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: combo_items; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.combo_items (
    combo_id integer NOT NULL,
    sub_service_item_id bigint NOT NULL
);


ALTER TABLE public.combo_items OWNER TO postgres;

--
-- Name: TABLE combo_items; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON TABLE public.combo_items IS 'Links service combos to the specific sub-service items included in them.';


--
-- Name: notifications; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.notifications (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    actor_id bigint,
    message text NOT NULL,
    link text,
    is_read boolean DEFAULT false NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.notifications OWNER TO postgres;

--
-- Name: notifications_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.notifications ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.notifications_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: payouts; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.payouts (
    id bigint NOT NULL,
    wallet_id bigint NOT NULL,
    amount numeric(10,2) NOT NULL,
    status character varying(50) DEFAULT 'pending'::character varying NOT NULL,
    requested_at timestamp with time zone DEFAULT now() NOT NULL,
    processed_at timestamp with time zone
);


ALTER TABLE public.payouts OWNER TO postgres;

--
-- Name: TABLE payouts; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON TABLE public.payouts IS 'Tracks payout requests for workers.';


--
-- Name: payouts_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.payouts ALTER COLUMN id ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.payouts_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: platform_payouts; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.platform_payouts (
    id bigint NOT NULL,
    amount numeric(10,2) NOT NULL,
    requested_by_admin_id bigint,
    requested_at timestamp with time zone DEFAULT now(),
    status character varying(50) DEFAULT 'pending'::character varying NOT NULL
);


ALTER TABLE public.platform_payouts OWNER TO postgres;

--
-- Name: platform_payouts_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.platform_payouts_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.platform_payouts_id_seq OWNER TO postgres;

--
-- Name: platform_payouts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.platform_payouts_id_seq OWNED BY public.platform_payouts.id;


--
-- Name: reviews; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.reviews (
    id bigint NOT NULL,
    booking_id bigint NOT NULL,
    reviewer_id bigint NOT NULL,
    worker_id bigint NOT NULL,
    rating integer NOT NULL,
    comment text,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    CONSTRAINT reviews_rating_check CHECK (((rating >= 1) AND (rating <= 5)))
);


ALTER TABLE public.reviews OWNER TO postgres;

--
-- Name: reviews_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.reviews ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.reviews_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: service_combos; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.service_combos (
    id integer NOT NULL,
    name character varying(255) NOT NULL,
    description text,
    combo_price numeric(10,2) NOT NULL,
    is_active boolean DEFAULT true,
    created_by_admin_id bigint,
    created_at timestamp with time zone DEFAULT now(),
    updated_at timestamp with time zone DEFAULT now(),
    CONSTRAINT combo_price_positive CHECK ((combo_price >= (0)::numeric))
);


ALTER TABLE public.service_combos OWNER TO postgres;

--
-- Name: TABLE service_combos; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON TABLE public.service_combos IS 'Stores details about service package deals (combos).';


--
-- Name: COLUMN service_combos.combo_price; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.service_combos.combo_price IS 'The final discounted price for the entire combo package.';


--
-- Name: service_combos_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.service_combos_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.service_combos_id_seq OWNER TO postgres;

--
-- Name: service_combos_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.service_combos_id_seq OWNED BY public.service_combos.id;


--
-- Name: services; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.services (
    id bigint NOT NULL,
    name text NOT NULL,
    icon text NOT NULL,
    slug text NOT NULL
);


ALTER TABLE public.services OWNER TO postgres;

--
-- Name: TABLE services; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON TABLE public.services IS 'Stores the main service categories offered, e.g., "Home Services".';


--
-- Name: services_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.services ALTER COLUMN id ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.services_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: sub_service_items_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.sub_service_items_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.sub_service_items_id_seq OWNER TO postgres;

--
-- Name: sub_service_items; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.sub_service_items (
    id bigint DEFAULT nextval('public.sub_service_items_id_seq'::regclass) NOT NULL,
    sub_service_id bigint NOT NULL,
    name text NOT NULL,
    icon text NOT NULL,
    slug text NOT NULL
);


ALTER TABLE public.sub_service_items OWNER TO postgres;

--
-- Name: sub_services; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.sub_services (
    id bigint NOT NULL,
    service_id bigint NOT NULL,
    name text NOT NULL,
    icon text NOT NULL,
    slug text NOT NULL
);


ALTER TABLE public.sub_services OWNER TO postgres;

--
-- Name: TABLE sub_services; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON TABLE public.sub_services IS 'Stores specific sub-services under a main category, e.g., "Plumber".';


--
-- Name: sub_services_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.sub_services ALTER COLUMN id ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.sub_services_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: transactions; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.transactions (
    id bigint NOT NULL,
    wallet_id bigint NOT NULL,
    booking_id bigint,
    type character varying(50) NOT NULL,
    amount numeric(10,2) NOT NULL,
    description text,
    created_at timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.transactions OWNER TO postgres;

--
-- Name: TABLE transactions; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON TABLE public.transactions IS 'Detailed transaction history for wallets/bookings.';


--
-- Name: transactions_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.transactions ALTER COLUMN id ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.transactions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: user_coupon_usage; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.user_coupon_usage (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    offer_id integer NOT NULL,
    sub_service_item_id bigint NOT NULL,
    booking_id bigint,
    used_at timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.user_coupon_usage OWNER TO postgres;

--
-- Name: user_coupon_usage_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.user_coupon_usage_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.user_coupon_usage_id_seq OWNER TO postgres;

--
-- Name: user_coupon_usage_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.user_coupon_usage_id_seq OWNED BY public.user_coupon_usage.id;


--
-- Name: users; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.users (
    id bigint NOT NULL,
    full_name character varying(255) NOT NULL,
    email character varying(255) NOT NULL,
    password character varying(255) NOT NULL,
    phone character varying(20),
    role public.user_role NOT NULL,
    profile_image character varying(255),
    account_status public.account_status DEFAULT 'active'::public.account_status NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    latitude numeric(10,8),
    longitude numeric(11,8),
    address_line1 text,
    address_line2 text,
    city character varying(100),
    pincode character varying(10),
    state character varying(100),
    otp_code character varying(6),
    otp_expires_at timestamp without time zone,
    auth_user_id uuid
);


ALTER TABLE public.users OWNER TO postgres;

--
-- Name: TABLE users; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON TABLE public.users IS 'Stores all users, both customers and workers.';


--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.users ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: wallets; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.wallets (
    id bigint NOT NULL,
    worker_id bigint NOT NULL,
    balance numeric(10,2) DEFAULT 0.00 NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.wallets OWNER TO postgres;

--
-- Name: TABLE wallets; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON TABLE public.wallets IS 'Stores worker wallet balances for payouts.';


--
-- Name: wallets_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.wallets ALTER COLUMN id ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.wallets_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: worker_availability; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.worker_availability (
    id integer NOT NULL,
    user_id integer NOT NULL,
    date date NOT NULL,
    time_slot time without time zone NOT NULL,
    created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.worker_availability OWNER TO postgres;

--
-- Name: worker_availability_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.worker_availability_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.worker_availability_id_seq OWNER TO postgres;

--
-- Name: worker_availability_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.worker_availability_id_seq OWNED BY public.worker_availability.id;


--
-- Name: worker_keys; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.worker_keys (
    id integer NOT NULL,
    access_key character varying(6) NOT NULL,
    is_used boolean DEFAULT false NOT NULL,
    used_by_worker_id bigint,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    status public.account_status DEFAULT 'active'::public.account_status NOT NULL,
    deleted_at timestamp with time zone
);


ALTER TABLE public.worker_keys OWNER TO postgres;

--
-- Name: worker_keys_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.worker_keys_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.worker_keys_id_seq OWNER TO postgres;

--
-- Name: worker_keys_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.worker_keys_id_seq OWNED BY public.worker_keys.id;


--
-- Name: worker_offers; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.worker_offers (
    id integer NOT NULL,
    worker_id bigint NOT NULL,
    coupon_code character varying(50) NOT NULL,
    discount_type character varying(20) NOT NULL,
    discount_value numeric(10,2) NOT NULL,
    min_booking_amount numeric(10,2) DEFAULT 0.00,
    valid_from timestamp with time zone,
    valid_until timestamp with time zone,
    max_uses integer,
    uses_count integer DEFAULT 0 NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    CONSTRAINT worker_offers_discount_type_check CHECK (((discount_type)::text = ANY ((ARRAY['percentage'::character varying, 'fixed'::character varying])::text[]))),
    CONSTRAINT worker_offers_discount_value_check CHECK ((discount_value > (0)::numeric))
);


ALTER TABLE public.worker_offers OWNER TO postgres;

--
-- Name: worker_offers_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.worker_offers_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.worker_offers_id_seq OWNER TO postgres;

--
-- Name: worker_offers_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.worker_offers_id_seq OWNED BY public.worker_offers.id;


--
-- Name: worker_profiles; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.worker_profiles (
    user_id bigint NOT NULL,
    bio text,
    experience_years integer,
    hourly_rate numeric(10,2),
    is_verified boolean DEFAULT false,
    CONSTRAINT worker_profiles_experience_years_check CHECK ((experience_years >= 0)),
    CONSTRAINT worker_profiles_hourly_rate_check CHECK ((hourly_rate >= 0.00))
);


ALTER TABLE public.worker_profiles OWNER TO postgres;

--
-- Name: TABLE worker_profiles; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON TABLE public.worker_profiles IS 'Stores additional details for users with the worker role.';


--
-- Name: worker_services; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.worker_services (
    user_id bigint NOT NULL,
    sub_service_id bigint NOT NULL
);


ALTER TABLE public.worker_services OWNER TO postgres;

--
-- Name: worker_sub_service_items; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.worker_sub_service_items (
    user_id bigint NOT NULL,
    sub_service_item_id bigint NOT NULL,
    price numeric(10,2) DEFAULT 0.00 NOT NULL
);


ALTER TABLE public.worker_sub_service_items OWNER TO postgres;

--
-- Name: messages; Type: TABLE; Schema: realtime; Owner: supabase_realtime_admin
--

CREATE TABLE realtime.messages (
    topic text NOT NULL,
    extension text NOT NULL,
    payload jsonb,
    event text,
    private boolean DEFAULT false,
    updated_at timestamp without time zone DEFAULT now() NOT NULL,
    inserted_at timestamp without time zone DEFAULT now() NOT NULL,
    id uuid DEFAULT gen_random_uuid() NOT NULL
)
PARTITION BY RANGE (inserted_at);


ALTER TABLE realtime.messages OWNER TO supabase_realtime_admin;

--
-- Name: schema_migrations; Type: TABLE; Schema: realtime; Owner: supabase_admin
--

CREATE TABLE realtime.schema_migrations (
    version bigint NOT NULL,
    inserted_at timestamp(0) without time zone
);


ALTER TABLE realtime.schema_migrations OWNER TO supabase_admin;

--
-- Name: subscription; Type: TABLE; Schema: realtime; Owner: supabase_admin
--

CREATE TABLE realtime.subscription (
    id bigint NOT NULL,
    subscription_id uuid NOT NULL,
    entity regclass NOT NULL,
    filters realtime.user_defined_filter[] DEFAULT '{}'::realtime.user_defined_filter[] NOT NULL,
    claims jsonb NOT NULL,
    claims_role regrole GENERATED ALWAYS AS (realtime.to_regrole((claims ->> 'role'::text))) STORED NOT NULL,
    created_at timestamp without time zone DEFAULT timezone('utc'::text, now()) NOT NULL
);


ALTER TABLE realtime.subscription OWNER TO supabase_admin;

--
-- Name: subscription_id_seq; Type: SEQUENCE; Schema: realtime; Owner: supabase_admin
--

ALTER TABLE realtime.subscription ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME realtime.subscription_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: buckets; Type: TABLE; Schema: storage; Owner: supabase_storage_admin
--

CREATE TABLE storage.buckets (
    id text NOT NULL,
    name text NOT NULL,
    owner uuid,
    created_at timestamp with time zone DEFAULT now(),
    updated_at timestamp with time zone DEFAULT now(),
    public boolean DEFAULT false,
    avif_autodetection boolean DEFAULT false,
    file_size_limit bigint,
    allowed_mime_types text[],
    owner_id text,
    type storage.buckettype DEFAULT 'STANDARD'::storage.buckettype NOT NULL
);


ALTER TABLE storage.buckets OWNER TO supabase_storage_admin;

--
-- Name: COLUMN buckets.owner; Type: COMMENT; Schema: storage; Owner: supabase_storage_admin
--

COMMENT ON COLUMN storage.buckets.owner IS 'Field is deprecated, use owner_id instead';


--
-- Name: buckets_analytics; Type: TABLE; Schema: storage; Owner: supabase_storage_admin
--

CREATE TABLE storage.buckets_analytics (
    id text NOT NULL,
    type storage.buckettype DEFAULT 'ANALYTICS'::storage.buckettype NOT NULL,
    format text DEFAULT 'ICEBERG'::text NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE storage.buckets_analytics OWNER TO supabase_storage_admin;

--
-- Name: migrations; Type: TABLE; Schema: storage; Owner: supabase_storage_admin
--

CREATE TABLE storage.migrations (
    id integer NOT NULL,
    name character varying(100) NOT NULL,
    hash character varying(40) NOT NULL,
    executed_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE storage.migrations OWNER TO supabase_storage_admin;

--
-- Name: objects; Type: TABLE; Schema: storage; Owner: supabase_storage_admin
--

CREATE TABLE storage.objects (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    bucket_id text,
    name text,
    owner uuid,
    created_at timestamp with time zone DEFAULT now(),
    updated_at timestamp with time zone DEFAULT now(),
    last_accessed_at timestamp with time zone DEFAULT now(),
    metadata jsonb,
    path_tokens text[] GENERATED ALWAYS AS (string_to_array(name, '/'::text)) STORED,
    version text,
    owner_id text,
    user_metadata jsonb,
    level integer
);


ALTER TABLE storage.objects OWNER TO supabase_storage_admin;

--
-- Name: COLUMN objects.owner; Type: COMMENT; Schema: storage; Owner: supabase_storage_admin
--

COMMENT ON COLUMN storage.objects.owner IS 'Field is deprecated, use owner_id instead';


--
-- Name: prefixes; Type: TABLE; Schema: storage; Owner: supabase_storage_admin
--

CREATE TABLE storage.prefixes (
    bucket_id text NOT NULL,
    name text NOT NULL COLLATE pg_catalog."C",
    level integer GENERATED ALWAYS AS (storage.get_level(name)) STORED NOT NULL,
    created_at timestamp with time zone DEFAULT now(),
    updated_at timestamp with time zone DEFAULT now()
);


ALTER TABLE storage.prefixes OWNER TO supabase_storage_admin;

--
-- Name: s3_multipart_uploads; Type: TABLE; Schema: storage; Owner: supabase_storage_admin
--

CREATE TABLE storage.s3_multipart_uploads (
    id text NOT NULL,
    in_progress_size bigint DEFAULT 0 NOT NULL,
    upload_signature text NOT NULL,
    bucket_id text NOT NULL,
    key text NOT NULL COLLATE pg_catalog."C",
    version text NOT NULL,
    owner_id text,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    user_metadata jsonb
);


ALTER TABLE storage.s3_multipart_uploads OWNER TO supabase_storage_admin;

--
-- Name: s3_multipart_uploads_parts; Type: TABLE; Schema: storage; Owner: supabase_storage_admin
--

CREATE TABLE storage.s3_multipart_uploads_parts (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    upload_id text NOT NULL,
    size bigint DEFAULT 0 NOT NULL,
    part_number integer NOT NULL,
    bucket_id text NOT NULL,
    key text NOT NULL COLLATE pg_catalog."C",
    etag text NOT NULL,
    owner_id text,
    version text NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE storage.s3_multipart_uploads_parts OWNER TO supabase_storage_admin;

--
-- Name: refresh_tokens id; Type: DEFAULT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.refresh_tokens ALTER COLUMN id SET DEFAULT nextval('auth.refresh_tokens_id_seq'::regclass);


--
-- Name: platform_payouts id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.platform_payouts ALTER COLUMN id SET DEFAULT nextval('public.platform_payouts_id_seq'::regclass);


--
-- Name: service_combos id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.service_combos ALTER COLUMN id SET DEFAULT nextval('public.service_combos_id_seq'::regclass);


--
-- Name: user_coupon_usage id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.user_coupon_usage ALTER COLUMN id SET DEFAULT nextval('public.user_coupon_usage_id_seq'::regclass);


--
-- Name: worker_availability id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.worker_availability ALTER COLUMN id SET DEFAULT nextval('public.worker_availability_id_seq'::regclass);


--
-- Name: worker_keys id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.worker_keys ALTER COLUMN id SET DEFAULT nextval('public.worker_keys_id_seq'::regclass);


--
-- Name: worker_offers id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.worker_offers ALTER COLUMN id SET DEFAULT nextval('public.worker_offers_id_seq'::regclass);


--
-- Data for Name: audit_log_entries; Type: TABLE DATA; Schema: auth; Owner: supabase_auth_admin
--

COPY auth.audit_log_entries (instance_id, id, payload, created_at, ip_address) FROM stdin;
\.


--
-- Data for Name: flow_state; Type: TABLE DATA; Schema: auth; Owner: supabase_auth_admin
--

COPY auth.flow_state (id, user_id, auth_code, code_challenge_method, code_challenge, provider_type, provider_access_token, provider_refresh_token, created_at, updated_at, authentication_method, auth_code_issued_at) FROM stdin;
\.


--
-- Data for Name: identities; Type: TABLE DATA; Schema: auth; Owner: supabase_auth_admin
--

COPY auth.identities (provider_id, user_id, identity_data, provider, last_sign_in_at, created_at, updated_at, id) FROM stdin;
\.


--
-- Data for Name: instances; Type: TABLE DATA; Schema: auth; Owner: supabase_auth_admin
--

COPY auth.instances (id, uuid, raw_base_config, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: mfa_amr_claims; Type: TABLE DATA; Schema: auth; Owner: supabase_auth_admin
--

COPY auth.mfa_amr_claims (session_id, created_at, updated_at, authentication_method, id) FROM stdin;
\.


--
-- Data for Name: mfa_challenges; Type: TABLE DATA; Schema: auth; Owner: supabase_auth_admin
--

COPY auth.mfa_challenges (id, factor_id, created_at, verified_at, ip_address, otp_code, web_authn_session_data) FROM stdin;
\.


--
-- Data for Name: mfa_factors; Type: TABLE DATA; Schema: auth; Owner: supabase_auth_admin
--

COPY auth.mfa_factors (id, user_id, friendly_name, factor_type, status, created_at, updated_at, secret, phone, last_challenged_at, web_authn_credential, web_authn_aaguid, last_webauthn_challenge_data) FROM stdin;
\.


--
-- Data for Name: oauth_authorizations; Type: TABLE DATA; Schema: auth; Owner: supabase_auth_admin
--

COPY auth.oauth_authorizations (id, authorization_id, client_id, user_id, redirect_uri, scope, state, resource, code_challenge, code_challenge_method, response_type, status, authorization_code, created_at, expires_at, approved_at) FROM stdin;
\.


--
-- Data for Name: oauth_clients; Type: TABLE DATA; Schema: auth; Owner: supabase_auth_admin
--

COPY auth.oauth_clients (id, client_secret_hash, registration_type, redirect_uris, grant_types, client_name, client_uri, logo_uri, created_at, updated_at, deleted_at, client_type) FROM stdin;
\.


--
-- Data for Name: oauth_consents; Type: TABLE DATA; Schema: auth; Owner: supabase_auth_admin
--

COPY auth.oauth_consents (id, user_id, client_id, scopes, granted_at, revoked_at) FROM stdin;
\.


--
-- Data for Name: one_time_tokens; Type: TABLE DATA; Schema: auth; Owner: supabase_auth_admin
--

COPY auth.one_time_tokens (id, user_id, token_type, token_hash, relates_to, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: refresh_tokens; Type: TABLE DATA; Schema: auth; Owner: supabase_auth_admin
--

COPY auth.refresh_tokens (instance_id, id, token, user_id, revoked, created_at, updated_at, parent, session_id) FROM stdin;
\.


--
-- Data for Name: saml_providers; Type: TABLE DATA; Schema: auth; Owner: supabase_auth_admin
--

COPY auth.saml_providers (id, sso_provider_id, entity_id, metadata_xml, metadata_url, attribute_mapping, created_at, updated_at, name_id_format) FROM stdin;
\.


--
-- Data for Name: saml_relay_states; Type: TABLE DATA; Schema: auth; Owner: supabase_auth_admin
--

COPY auth.saml_relay_states (id, sso_provider_id, request_id, for_email, redirect_to, created_at, updated_at, flow_state_id) FROM stdin;
\.


--
-- Data for Name: schema_migrations; Type: TABLE DATA; Schema: auth; Owner: supabase_auth_admin
--

COPY auth.schema_migrations (version) FROM stdin;
20171026211738
20171026211808
20171026211834
20180103212743
20180108183307
20180119214651
20180125194653
00
20210710035447
20210722035447
20210730183235
20210909172000
20210927181326
20211122151130
20211124214934
20211202183645
20220114185221
20220114185340
20220224000811
20220323170000
20220429102000
20220531120530
20220614074223
20220811173540
20221003041349
20221003041400
20221011041400
20221020193600
20221021073300
20221021082433
20221027105023
20221114143122
20221114143410
20221125140132
20221208132122
20221215195500
20221215195800
20221215195900
20230116124310
20230116124412
20230131181311
20230322519590
20230402418590
20230411005111
20230508135423
20230523124323
20230818113222
20230914180801
20231027141322
20231114161723
20231117164230
20240115144230
20240214120130
20240306115329
20240314092811
20240427152123
20240612123726
20240729123726
20240802193726
20240806073726
20241009103726
20250717082212
20250731150234
20250804100000
20250901200500
20250903112500
20250904133000
20250925093508
20251007112900
\.


--
-- Data for Name: sessions; Type: TABLE DATA; Schema: auth; Owner: supabase_auth_admin
--

COPY auth.sessions (id, user_id, created_at, updated_at, factor_id, aal, not_after, refreshed_at, user_agent, ip, tag, oauth_client_id, refresh_token_hmac_key, refresh_token_counter) FROM stdin;
\.


--
-- Data for Name: sso_domains; Type: TABLE DATA; Schema: auth; Owner: supabase_auth_admin
--

COPY auth.sso_domains (id, sso_provider_id, domain, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: sso_providers; Type: TABLE DATA; Schema: auth; Owner: supabase_auth_admin
--

COPY auth.sso_providers (id, resource_id, created_at, updated_at, disabled) FROM stdin;
\.


--
-- Data for Name: users; Type: TABLE DATA; Schema: auth; Owner: supabase_auth_admin
--

COPY auth.users (instance_id, id, aud, role, email, encrypted_password, email_confirmed_at, invited_at, confirmation_token, confirmation_sent_at, recovery_token, recovery_sent_at, email_change_token_new, email_change, email_change_sent_at, last_sign_in_at, raw_app_meta_data, raw_user_meta_data, is_super_admin, created_at, updated_at, phone, phone_confirmed_at, phone_change, phone_change_token, phone_change_sent_at, email_change_token_current, email_change_confirm_status, banned_until, reauthentication_token, reauthentication_sent_at, is_sso_user, deleted_at, is_anonymous) FROM stdin;
\.


--
-- Data for Name: bookings; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.bookings (id, customer_id, worker_id, service_details, booking_time, status, created_at, final_cost, payment_status, worker_latitude, worker_longitude, predefined_cost, customer_offer, cost_status, work_completed_by_worker, rejection_reason, applied_offer_id, discount_amount, applied_admin_offer_id, combo_id, confirmed_at, cancellation_reason, sub_service_item_id, worker_earning, platform_fee, hourly_reminder_sent, daily_reminder_sent) FROM stdin;
97	1	21	Service: Clothes\nItem: Washing\nAddress: C-1/501, Sai Milan Residency, Opposite jalaram international school, Palanpore canal road, adajan, Surat, Gujarat, 395009	2025-11-06 10:30:00+00	completed	2025-11-06 09:46:01.351568+00	450.00	paid	\N	\N	\N	\N	pending	t	\N	\N	\N	\N	\N	2025-11-06 09:46:42.19897	\N	5	400.00	50.00	f	t
1	1	14	Work Details: Bike cleaning\nAddress: Adajan, Surat	2025-08-21 15:30:00+00	confirmed	2025-08-20 15:00:32.54813+00	\N	unpaid	\N	\N	\N	\N	pending	f	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f
2	1	3	Work Details: Bike wash\nAddress: Pal, Surat	2025-08-23 13:30:00+00	confirmed	2025-08-20 18:11:09.665157+00	\N	unpaid	\N	\N	\N	\N	pending	f	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f
51	1	22	Service: Shoes\nItem: Running\nAddress: C-1/501, Sai Milan Residency, Opposite jalaram international school, Palanpore canal road, adajan, Surat, Gujarat, 395009	2025-10-24 13:30:00+00	completed	2025-10-24 10:11:28.693135+00	150.00	paid	\N	\N	\N	\N	pending	t	\N	4	20.00	\N	\N	\N	\N	\N	\N	\N	f	f
78	1	22	Service: Clothes\nItem: Dry Clean\nAddress: C-1/501, Sai Milan Residency, Opposite jalaram international school, Palanpore canal road, adajan, Surat, Gujarat, 395009	2025-10-31 12:30:00+00	completed	2025-10-31 11:26:57.901594+00	200.00	paid	\N	\N	\N	\N	pending	t	\N	\N	\N	\N	\N	2025-10-31 11:55:53.784317	\N	6	\N	\N	f	f
93	1	21	Service: Clothes\nItem: Dry Clean\nAddress: C-1/501, Sai Milan Residency, Opposite jalaram international school, Palanpore canal road, adajan, Surat, Gujarat, 395009	2025-11-04 04:30:00+00	completed	2025-11-03 18:14:11.835583+00	450.00	paid	\N	\N	\N	\N	pending	t	\N	\N	\N	\N	\N	2025-11-03 18:20:32.820789	\N	6	400.00	50.00	f	f
94	1	22	Service: Clothes\nItem: Dry Clean\nAddress: C-1/501, Sai Milan Residency, Opposite jalaram international school, Palanpore canal road, adajan, Surat, Gujarat, 395009	2025-11-04 08:30:00+00	completed	2025-11-04 06:01:23.692652+00	250.00	paid	\N	\N	\N	\N	pending	t	\N	\N	\N	\N	\N	2025-11-04 06:28:57.000876	\N	6	200.00	50.00	f	f
95	1	22	Service: Shoes\nItem: Running\nAddress: C-1/501, Sai Milan Residency, Opposite jalaram international school, Palanpore canal road, adajan, Surat, Gujarat, 395009	2025-11-05 04:30:00+00	completed	2025-11-04 16:01:55.513187+00	200.00	paid	\N	\N	\N	\N	pending	t	\N	\N	\N	\N	\N	2025-11-04 17:53:48.67089	\N	8	150.00	50.00	f	f
96	1	21	Service: Clothes\nItem: Dry Clean\nAddress: C-1/501, Sai Milan Residency, Opposite jalaram international school, Palanpore canal road, adajan, Surat, Gujarat, 395009	2025-11-06 10:30:00+00	completed	2025-11-06 08:35:39.061266+00	450.00	paid	\N	\N	\N	\N	pending	t	\N	\N	\N	\N	\N	2025-11-06 08:37:10.001508	\N	6	400.00	50.00	t	t
3	1	11	Work Details: Bike repair\nAddress: Katargam, Surat	2025-08-22 20:00:00+00	cancelled	2025-08-20 18:22:25.590372+00	\N	unpaid	\N	\N	\N	\N	pending	f	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f
4	1	14	Work Details: Car cleaning\nAddress: Surat	2025-08-23 20:00:00+00	confirmed	2025-08-20 18:33:15.134815+00	\N	unpaid	\N	\N	\N	\N	pending	f	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f
5	1	14	Work Details: Car\nAddress: Surat	2025-08-26 20:00:00+00	confirmed	2025-08-20 18:36:18.152578+00	\N	unpaid	\N	\N	\N	\N	pending	f	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f
7	1	11	Work Details: bike repair\nAddress: Surat	2025-08-30 20:00:00+00	cancelled	2025-08-21 12:49:57.156465+00	\N	unpaid	\N	\N	\N	\N	pending	f	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f
8	1	3	Work Details: Car Washing\nAddress: Shiv Shakti Society, Bhavnagar	2025-08-25 06:30:00+00	confirmed	2025-08-21 16:40:58.668966+00	\N	unpaid	\N	\N	\N	\N	pending	f	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f
14	1	21	Service: Clothes\nItem: Washing\nAddress: C-1/501, Sai Milan Residency, Opposite jalaram international school, Palanpore canal road, adajan, Surat, Gujarat, 395009	2025-09-30 13:30:00+00	confirmed	2025-09-29 17:14:27.833622+00	\N	unpaid	\N	\N	\N	\N	pending	f	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f
9	1	3	Work Details: Car\nAddress: Krishna Nagar, Bhavnagar	2025-08-27 14:30:00+00	confirmed	2025-08-21 16:49:10.657749+00	\N	unpaid	\N	\N	\N	\N	pending	f	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f
32	1	21	Service: Clothes\nItem: Dry Clean\nAddress: C-1/501, Sai Milan Residency, Opposite jalaram international school, Palanpore canal road, adajan, Surat, Gujarat, 395009	2025-10-18 05:30:00+00	completed	2025-10-18 05:14:52.199154+00	400.00	paid	\N	\N	\N	\N	pending	t	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f
10	1	3	Work Details: Car\nAddress: surat	2025-08-21 07:30:00+00	confirmed	2025-08-21 17:00:46.857854+00	\N	unpaid	\N	\N	\N	\N	pending	f	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f
20	1	21	Service: Home Cleaning\nItem: Washing Utensils\nAddress: C-1/501, Sai Milan Residency, Opposite jalaram international school, Palanpore canal road, adajan, Surat, Gujarat, 395009	2025-10-02 04:30:00+00	confirmed	2025-10-01 17:07:04.869216+00	\N	unpaid	\N	\N	\N	\N	pending	f	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f
21	1	21	Service: Clothes\nItem: Washing\nAddress: C-1/501, Sai Milan Residency, Opposite jalaram international school, Palanpore canal road, adajan, Surat, Gujarat, 395009	2025-10-02 14:30:00+00	cancelled	2025-10-01 17:10:45.02402+00	\N	unpaid	\N	\N	\N	\N	pending	f	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f
15	1	21	Service: Home Cleaning\nItem: Sweeping\nAddress: C-1/501, Sai Milan Residency, Opposite jalaram international school, Palanpore canal road, adajan, Surat, Gujarat, 395009	2025-10-01 04:30:00+00	completed	2025-09-29 17:26:44.084732+00	\N	paid	\N	\N	\N	\N	pending	f	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f
12	1	4	Work Details: I want to repair AC!\nAddress: C-1/501, Sai Milan Residency, Opposite jalaram international school, Palanpore canal road, adajan, Surat, Gujarat, 395009	2025-09-29 15:30:00+00	completed	2025-09-29 15:05:25.449578+00	\N	paid	\N	\N	\N	\N	pending	f	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f
13	1	21	Service: Clothes\nItem: Dry Clean\nAddress: C-1/501, Sai Milan Residency, Opposite jalaram international school, Palanpore canal road, adajan, Surat, Gujarat, 395009	2025-09-30 05:30:00+00	completed	2025-09-29 15:15:21.958879+00	\N	unpaid	\N	\N	\N	\N	pending	f	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f
22	1	21	Service: Clothes\nItem: Dry Clean\nAddress: C-1/501, Sai Milan Residency, Opposite jalaram international school, Palanpore canal road, adajan, Surat, Gujarat, 395009	2025-10-02 14:30:00+00	cancelled	2025-10-01 17:12:04.654504+00	\N	unpaid	\N	\N	\N	\N	pending	f	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f
16	1	21	Service: Home Cleaning\nItem: Sweeping\nAddress: C-1/501, Sai Milan Residency, Opposite jalaram international school, Palanpore canal road, adajan, Surat, Gujarat, 395009	2025-10-02 05:30:00+00	cancelled	2025-09-30 13:27:02.509927+00	\N	unpaid	\N	\N	\N	\N	pending	f	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f
11	1	21	Service: Home Cleaning\nItem: Sweeping\nAddress: C-1/501, Sai Milan Residency, Opposite jalaram international school, Palanpore canal road, adajan, Surat, Gujarat, 395009	2025-09-29 14:30:00+00	completed	2025-09-29 13:55:49.327299+00	\N	paid	\N	\N	\N	\N	pending	f	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f
17	1	21	Service: Home Cleaning\nItem: Sweeping\nAddress: C-1/501, Sai Milan Residency, Opposite jalaram international school, Palanpore canal road, adajan, Surat, Gujarat, 395009	2025-10-01 14:30:00+00	confirmed	2025-09-30 14:21:08.972019+00	\N	unpaid	\N	\N	\N	\N	pending	f	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f
18	1	21	Service: Clothes\nItem: Dry Clean\nAddress: C-1/501, Sai Milan Residency, Opposite jalaram international school, Palanpore canal road, adajan, Surat, Gujarat, 395009	2025-10-01 04:30:00+00	cancelled	2025-09-30 15:10:20.55109+00	\N	unpaid	\N	\N	\N	\N	pending	f	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f
19	1	21	Service: Home Cleaning\nItem: Washing Utensils\nAddress: C-1/501, Sai Milan Residency, Opposite jalaram international school, Palanpore canal road, adajan, Surat, Gujarat, 395009	2025-10-01 13:30:00+00	confirmed	2025-09-30 16:24:10.772214+00	\N	unpaid	\N	\N	\N	\N	pending	f	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f
24	1	21	Service: Home Cleaning\nItem: Washing Utensils\nAddress: C-1/501, Sai Milan Residency, Opposite jalaram international school, Palanpore canal road, adajan, Surat, Gujarat, 395009	2025-10-02 14:30:00+00	confirmed	2025-10-01 17:21:29.105309+00	\N	unpaid	\N	\N	\N	\N	pending	f	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f
23	1	21	Service: Home Cleaning\nItem: Washing Utensils\nAddress: C-1/501, Sai Milan Residency, Opposite jalaram international school, Palanpore canal road, adajan, Surat, Gujarat, 395009	2025-10-02 14:30:00+00	cancelled	2025-10-01 17:20:50.288641+00	\N	unpaid	\N	\N	\N	\N	pending	f	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f
25	1	21	Service: Clothes\nItem: Washing\nAddress: C-1/501, Sai Milan Residency, Opposite jalaram international school, Palanpore canal road, adajan, Surat, Gujarat, 395009	2025-10-02 13:30:00+00	cancelled	2025-10-02 13:04:56.354655+00	\N	unpaid	\N	\N	\N	\N	pending	f	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f
29	1	21	Service: Bike\nItem: Repair\nAddress: C-1/501, Sai Milan Residency, Opposite jalaram international school, Palanpore canal road, adajan, Surat, Gujarat, 395009	2025-10-12 04:30:00+00	completed	2025-10-11 17:54:53.352846+00	800.00	paid	\N	\N	\N	\N	pending	t	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f
31	1	21	Service: Clothes\nItem: Dry Clean\nAddress: C-1/501, Sai Milan Residency, Opposite jalaram international school, Palanpore canal road, adajan, Surat, Gujarat, 395009	2025-10-13 06:30:00+00	completed	2025-10-13 06:29:36.977952+00	400.00	paid	\N	\N	\N	\N	pending	t	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f
28	1	21	Service: Bike\nItem: Washing\nAddress: C-1/501, Sai Milan Residency, Opposite jalaram international school, Palanpore canal road, adajan, Surat, Gujarat, 395009	2025-10-12 03:30:00+00	completed	2025-10-11 16:34:49.173792+00	400.00	paid	\N	\N	\N	\N	pending	t	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f
27	1	21	Service: Clothes\nItem: Dry Clean\nAddress: C-1/501, Sai Milan Residency, Opposite jalaram international school, Palanpore canal road, adajan, Surat, Gujarat, 395009	2025-10-11 12:30:00+00	completed	2025-10-11 12:19:19.160657+00	300.00	paid	\N	\N	\N	\N	pending	t	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f
30	1	21	Service: Bike\nItem: Repair\nAddress: C-1/501, Sai Milan Residency, Opposite jalaram international school, Palanpore canal road, adajan, Surat, Gujarat, 395009	2025-10-12 04:30:00+00	completed	2025-10-11 18:15:17.567517+00	800.00	paid	\N	\N	\N	\N	pending	t	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f
33	1	21	Service: Clothes\nItem: Dry Clean\nAddress: C-1/501, Sai Milan Residency, Opposite jalaram international school, Palanpore canal road, adajan, Surat, Gujarat, 395009	2025-10-18 07:30:00+00	cancelled	2025-10-18 06:34:40.502885+00	400.00	unpaid	\N	\N	\N	\N	pending	f	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f
34	1	21	Service: Clothes\nItem: Dry Clean\nAddress: C-1/501, Sai Milan Residency, Opposite jalaram international school, Palanpore canal road, adajan, Surat, Gujarat, 395009	2025-10-18 07:30:00+00	completed	2025-10-18 06:35:50.741859+00	400.00	paid	\N	\N	\N	\N	pending	t	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f
35	1	21	Service: Home Cleaning\nItem: Sweeping\nAddress: C-1/501, Sai Milan Residency, Opposite jalaram international school, Palanpore canal road, adajan, Surat, Gujarat, 395009	2025-10-18 08:30:00+00	completed	2025-10-18 08:01:33.651839+00	300.00	paid	\N	\N	\N	\N	pending	t	\N	1	50.00	\N	\N	\N	\N	\N	\N	\N	f	f
52	1	21	Service: Home Cleaning\nItem: Sweeping\nAddress: C-1/501, Sai Milan Residency, Opposite jalaram international school, Palanpore canal road, adajan, Surat, Gujarat, 395009	2025-10-25 11:30:00+00	completed	2025-10-25 09:30:56.923956+00	300.00	paid	\N	\N	\N	\N	pending	t	\N	3	35.00	\N	\N	2025-10-25 15:50:44	\N	\N	\N	\N	f	f
45	1	21	Service: Home Cleaning\nItem: Sweeping\nAddress: C-1/501, Sai Milan Residency, Opposite jalaram international school, Palanpore canal road, adajan, Surat, Gujarat, 395009	2025-10-23 12:30:00+00	completed	2025-10-23 09:12:39.71435+00	300.00	paid	\N	\N	300.00	\N	pending	t	\N	3	35.00	\N	\N	\N	\N	\N	\N	\N	f	f
42	1	21	Service: Home Cleaning\nItem: Washing Utensils\nAddress: C-1/501, Sai Milan Residency, Opposite jalaram international school, Palanpore canal road, adajan, Surat, Gujarat, 395009	2025-10-20 07:30:00+00	completed	2025-10-20 07:10:49.222588+00	300.00	paid	\N	\N	\N	\N	pending	t	\N	1	50.00	\N	\N	\N	\N	\N	\N	\N	f	f
39	1	21	Service: Home Cleaning\nItem: Sweeping\nAddress: C-1/501, Sai Milan Residency, Opposite jalaram international school, Palanpore canal road, adajan, Surat, Gujarat, 395009	2025-10-19 12:30:00+00	completed	2025-10-19 12:07:00.161281+00	300.00	paid	\N	\N	\N	\N	pending	t	\N	3	35.00	\N	\N	\N	\N	\N	\N	\N	f	f
36	1	21	Service: Clothes\nItem: Dry Clean\nAddress: C-1/501, Sai Milan Residency, Opposite jalaram international school, Palanpore canal road, adajan, Surat, Gujarat, 395009	2025-10-19 05:30:00+00	completed	2025-10-19 04:58:43.913505+00	400.00	paid	\N	\N	\N	\N	pending	t	\N	1	50.00	\N	\N	\N	\N	\N	\N	\N	f	f
47	1	21	Service: Home Cleaning\nItem: Sweeping\nAddress: C-1/501, Sai Milan Residency, Opposite jalaram international school, Palanpore canal road, adajan, Surat, Gujarat, 395009	2025-10-24 04:30:00+00	completed	2025-10-23 18:24:23.477435+00	300.00	paid	\N	\N	\N	\N	pending	t	\N	3	35.00	\N	\N	\N	\N	\N	\N	\N	f	f
37	1	21	Service: Home Cleaning\nItem: Washing Utensils\nAddress: C-1/501, Sai Milan Residency, Opposite jalaram international school, Palanpore canal road, adajan, Surat, Gujarat, 395009	2025-10-19 06:30:00+00	completed	2025-10-19 05:27:12.849197+00	300.00	paid	\N	\N	\N	\N	pending	t	\N	1	50.00	\N	\N	\N	\N	\N	\N	\N	f	f
40	1	21	Service: Home Cleaning\nItem: Sweeping\nAddress: C-1/501, Sai Milan Residency, Opposite jalaram international school, Palanpore canal road, adajan, Surat, Gujarat, 395009	2025-10-19 14:30:00+00	completed	2025-10-19 13:31:39.210274+00	300.00	paid	\N	\N	\N	\N	pending	t	\N	1	50.00	\N	\N	\N	\N	\N	\N	\N	f	f
44	1	21	Service: Home Cleaning\nItem: Sweeping\nAddress: C-1/501, Sai Milan Residency, Opposite jalaram international school, Palanpore canal road, adajan, Surat, Gujarat, 395009	2025-10-22 12:30:00+00	cancelled	2025-10-22 07:43:28.417044+00	300.00	unpaid	\N	\N	\N	\N	pending	f	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f
43	23	21	Service: Home Cleaning\nItem: Washing Utensils\nAddress: 20,Jay Shivam Soc; Part - 2, Cozway Road, Surat, Gujarat, 395004	2025-10-20 12:30:00+00	completed	2025-10-20 08:07:36.860614+00	300.00	paid	\N	\N	\N	\N	pending	t	\N	3	35.00	\N	\N	\N	\N	\N	\N	\N	f	f
41	1	21	Service: Clothes\nItem: Dry Clean\nAddress: C-1/501, Sai Milan Residency, Opposite jalaram international school, Palanpore canal road, adajan, Surat, Gujarat, 395009	2025-10-19 14:30:00+00	completed	2025-10-19 13:38:05.403527+00	400.00	paid	\N	\N	\N	\N	pending	t	\N	3	35.00	\N	\N	\N	\N	\N	\N	\N	f	f
38	1	21	Service: Clothes\nItem: Washing\nAddress: C-1/501, Sai Milan Residency, Opposite jalaram international school, Palanpore canal road, adajan, Surat, Gujarat, 395009	2025-10-19 12:30:00+00	completed	2025-10-19 10:59:09.456605+00	400.00	paid	\N	\N	\N	\N	pending	t	\N	3	35.00	\N	\N	\N	\N	\N	\N	\N	f	f
50	1	22	Service: Clothes\nItem: Dry Clean\nAddress: C-1/501, Sai Milan Residency, Opposite jalaram international school, Palanpore canal road, adajan, Surat, Gujarat, 395009	2025-10-24 11:30:00+00	completed	2025-10-24 09:44:54.226177+00	200.00	paid	\N	\N	\N	\N	pending	t	\N	4	20.00	\N	\N	\N	\N	\N	\N	\N	f	f
48	1	22	Service: Shoes\nItem: Sneakers\nAddress: C-1/501, Sai Milan Residency, Opposite jalaram international school, Palanpore canal road, adajan, Surat, Gujarat, 395009	2025-10-24 09:30:00+00	completed	2025-10-24 08:30:04.450775+00	350.00	paid	\N	\N	\N	\N	pending	t	\N	4	20.00	\N	\N	\N	\N	\N	\N	\N	f	f
49	1	22	Service: Clothes\nItem: Dry Clean\nAddress: C-1/501, Sai Milan Residency, Opposite jalaram international school, Palanpore canal road, adajan, Surat, Gujarat, 395009	2025-10-24 11:30:00+00	completed	2025-10-24 09:09:03.871618+00	200.00	paid	\N	\N	\N	\N	pending	t	\N	4	20.00	\N	\N	\N	\N	\N	\N	\N	f	f
46	1	21	Service: Home Cleaning\nItem: Sweeping\nAddress: C-1/501, Sai Milan Residency, Opposite jalaram international school, Palanpore canal road, adajan, Surat, Gujarat, 395009	2025-10-24 03:30:00+00	completed	2025-10-23 15:22:29.52538+00	300.00	paid	\N	\N	\N	\N	pending	t	\N	3	35.00	\N	\N	\N	\N	\N	\N	\N	f	f
60	1	21	Service: Home Cleaning\nItem: Washing Utensils\nAddress: C-1/501, Sai Milan Residency, Opposite jalaram international school, Palanpore canal road, adajan, Surat, Gujarat, 395009	2025-10-26 13:30:00+00	completed	2025-10-26 12:38:09.75846+00	300.00	paid	\N	\N	\N	\N	pending	t	\N	\N	\N	\N	\N	2025-10-26 18:15:37	\N	4	\N	\N	f	f
58	1	22	Service: Shoes\nItem: Running\nAddress: C-1/501, Sai Milan Residency, Opposite jalaram international school, Palanpore canal road, adajan, Surat, Gujarat, 395009	2025-10-25 16:30:00+00	completed	2025-10-25 16:48:35.819799+00	150.00	paid	\N	\N	\N	\N	pending	t	\N	4	20.00	\N	\N	2025-10-25 21:18:33	\N	\N	\N	\N	f	f
57	1	22	Service: Shoes\nItem: Running\nAddress: C-1/501, Sai Milan Residency, Opposite jalaram international school, Palanpore canal road, adajan, Surat, Gujarat, 395009	2025-10-25 15:30:00+00	completed	2025-10-25 14:26:36.393896+00	150.00	paid	\N	\N	\N	\N	pending	t	\N	\N	\N	\N	\N	2025-10-25 19:57:52	\N	\N	\N	\N	f	f
59	1	21	Service: Home Cleaning\nItem: Washing Utensils\nAddress: C-1/501, Sai Milan Residency, Opposite jalaram international school, Palanpore canal road, adajan, Surat, Gujarat, 395009	2025-10-26 13:30:00+00	completed	2025-10-26 12:30:52.484706+00	300.00	paid	\N	\N	\N	\N	pending	t	\N	5	25.00	\N	\N	2025-10-26 18:01:21	\N	4	\N	\N	f	f
61	1	22	Service: Shoes\nItem: Sneakers\nAddress: C-1/501, Sai Milan Residency, Opposite jalaram international school, Palanpore canal road, adajan, Surat, Gujarat, 395009	2025-10-26 15:30:00+00	cancelled	2025-10-26 13:56:46.010678+00	350.00	unpaid	\N	\N	\N	\N	pending	f	\N	\N	\N	\N	\N	2025-10-26 19:48:20	I do not want to	7	\N	\N	f	f
62	1	21	Service: Clothes\nItem: Dry Clean\nAddress: C-1/501, Sai Milan Residency, Opposite jalaram international school, Palanpore canal road, adajan, Surat, Gujarat, 395009	2025-10-27 04:30:00+00	cancelled	2025-10-26 15:16:14.576715+00	400.00	unpaid	\N	\N	\N	\N	pending	f	I do not want	5	25.00	\N	\N	\N	\N	6	\N	\N	f	f
63	1	21	Service: Clothes\nItem: Dry Clean\nAddress: C-1/501, Sai Milan Residency, Opposite jalaram international school, Palanpore canal road, adajan, Surat, Gujarat, 395009	2025-10-27 04:30:00+00	completed	2025-10-26 16:09:48.868124+00	400.00	paid	\N	\N	\N	\N	pending	t	\N	\N	\N	\N	\N	2025-10-26 16:10:09.124071	\N	6	\N	\N	f	f
64	1	21	Service: Clothes\nItem: Washing\nAddress: C-1/501, Sai Milan Residency, Opposite jalaram international school, Palanpore canal road, adajan, Surat, Gujarat, 395009	2025-10-27 04:30:00+00	cancelled	2025-10-26 16:15:55.839201+00	400.00	unpaid	\N	\N	\N	\N	pending	f	I do not want	\N	\N	\N	\N	\N	\N	5	\N	\N	f	f
73	1	22	Service: Shoes\nItem: Running\nAddress: C-1/501, Sai Milan Residency, Opposite jalaram international school, Palanpore canal road, adajan, Surat, Gujarat, 395009	2025-10-26 15:30:00+00	completed	2025-10-26 19:45:17.435715+00	150.00	paid	\N	\N	\N	\N	pending	t	\N	6	30.00	\N	\N	2025-10-26 19:15:42	\N	8	\N	\N	f	f
65	1	21	Service: Clothes\nItem: Washing\nAddress: C-1/501, Sai Milan Residency, Opposite jalaram international school, Palanpore canal road, adajan, Surat, Gujarat, 395009	2025-10-27 05:30:00+00	completed	2025-10-26 16:16:35.561217+00	400.00	paid	\N	\N	\N	\N	pending	t	\N	\N	\N	\N	\N	2025-10-26 16:16:55.218734	\N	5	\N	\N	f	f
72	1	22	Service: Clothes\nItem: Washing\nAddress: C-1/501, Sai Milan Residency, Opposite jalaram international school, Palanpore canal road, adajan, Surat, Gujarat, 395009	2025-10-26 15:30:00+00	completed	2025-10-26 19:27:48.117062+00	150.00	paid	\N	\N	\N	\N	pending	t	\N	6	30.00	\N	\N	2025-10-26 18:58:15	\N	5	\N	\N	f	f
70	1	21	Service: Clothes\nItem: Washing\nAddress: C-1/501, Sai Milan Residency, Opposite jalaram international school, Palanpore canal road, adajan, Surat, Gujarat, 395009	2025-10-26 14:30:00+00	completed	2025-10-26 19:03:33.691717+00	400.00	paid	\N	\N	\N	\N	pending	t	\N	5	25.00	\N	\N	2025-10-26 18:33:56	\N	5	\N	\N	f	f
66	1	22	Service: Clothes\nItem: Washing\nAddress: C-1/501, Sai Milan Residency, Opposite jalaram international school, Palanpore canal road, adajan, Surat, Gujarat, 395009	2025-10-26 13:30:00+00	in_progress	2025-10-26 17:57:51.613396+00	150.00	unpaid	\N	\N	\N	\N	pending	t	\N	\N	\N	\N	\N	2025-10-26 17:29:20	\N	5	\N	\N	f	f
67	1	21	Service: Clothes\nItem: Dry Clean\nAddress: C-1/501, Sai Milan Residency, Opposite jalaram international school, Palanpore canal road, adajan, Surat, Gujarat, 395009	2025-10-26 13:30:00+00	completed	2025-10-26 18:05:16.8367+00	400.00	paid	\N	\N	\N	\N	pending	t	\N	\N	\N	\N	\N	2025-10-26 17:36:51	\N	6	\N	\N	f	f
68	1	21	Service: Clothes\nItem: Dry Clean\nAddress: C-1/501, Sai Milan Residency, Opposite jalaram international school, Palanpore canal road, adajan, Surat, Gujarat, 395009	2025-10-26 13:30:00+00	completed	2025-10-26 18:20:46.609381+00	400.00	paid	\N	\N	\N	\N	pending	t	\N	\N	\N	\N	\N	2025-10-26 17:51:20	\N	6	\N	\N	f	f
83	1	21	Service: Clothes\nItem: Dry Clean\nAddress: C-1/501, Sai Milan Residency, Opposite jalaram international school, Palanpore canal road, adajan, Surat, Gujarat, 395009	2025-11-01 07:30:00+00	completed	2025-11-01 07:13:58.877858+00	400.00	paid	\N	\N	\N	\N	pending	t	\N	\N	\N	\N	\N	2025-11-01 07:15:10.709654	\N	6	\N	\N	f	f
76	1	22	Service: Shoes\nItem: Sneakers\nAddress: C-1/501, Sai Milan Residency, Opposite jalaram international school, Palanpore canal road, adajan, Surat, Gujarat, 395009	2025-10-31 11:30:00+00	completed	2025-10-31 09:41:31.20456+00	350.00	paid	\N	\N	\N	\N	pending	t	\N	6	30.00	\N	\N	2025-10-31 09:45:21.322716	\N	7	\N	\N	f	f
74	1	21	Service: Clothes\nItem: Dry Clean\nAddress: C-1/501, Sai Milan Residency, Opposite jalaram international school, Palanpore canal road, adajan, Surat, Gujarat, 395009	2025-10-27 13:30:00+00	completed	2025-10-27 16:23:42.597005+00	400.00	paid	\N	\N	\N	\N	pending	t	\N	\N	\N	\N	\N	2025-10-27 16:24:23.443536	\N	6	\N	\N	f	f
69	1	21	Service: Clothes\nItem: Washing\nAddress: C-1/501, Sai Milan Residency, Opposite jalaram international school, Palanpore canal road, adajan, Surat, Gujarat, 395009	2025-10-26 13:30:00+00	completed	2025-10-26 18:46:17.186618+00	400.00	paid	\N	\N	\N	\N	pending	t	\N	\N	\N	\N	\N	2025-10-26 18:17:12	\N	5	\N	\N	f	f
71	1	22	Service: Clothes\nItem: Dry Clean\nAddress: C-1/501, Sai Milan Residency, Opposite jalaram international school, Palanpore canal road, adajan, Surat, Gujarat, 395009	2025-10-26 15:30:00+00	completed	2025-10-26 19:21:56.944906+00	200.00	paid	\N	\N	\N	\N	pending	t	\N	6	30.00	\N	\N	2025-10-26 18:52:18	\N	6	\N	\N	f	f
81	1	21	Service: Clothes\nItem: Dry Clean\nAddress: C-1/501, Sai Milan Residency, Opposite jalaram international school, Palanpore canal road, adajan, Surat, Gujarat, 395009	2025-10-31 04:30:00+00	completed	2025-10-31 19:18:59.617552+00	400.00	paid	\N	\N	\N	\N	pending	t	\N	\N	\N	\N	\N	2025-10-31 19:19:55.318473	\N	6	\N	\N	f	f
75	1	21	Service: Home Cleaning\nItem: Sweeping\nAddress: C-1/501, Sai Milan Residency, Opposite jalaram international school, Palanpore canal road, adajan, Surat, Gujarat, 395009	2025-10-31 08:30:00+00	completed	2025-10-31 07:45:30.484848+00	300.00	paid	\N	\N	\N	\N	pending	t	\N	5	25.00	\N	\N	2025-10-31 08:12:45.780488	\N	3	\N	\N	f	f
79	1	21	Service: Home Cleaning\nItem: Sweeping\nAddress: C-1/501, Sai Milan Residency, Opposite jalaram international school, Palanpore canal road, adajan, Surat, Gujarat, 395009	2025-10-31 12:30:00+00	completed	2025-10-31 12:02:35.442995+00	300.00	paid	\N	\N	\N	\N	pending	t	\N	\N	\N	\N	\N	2025-10-31 12:03:17.676286	\N	3	\N	\N	f	f
77	1	22	Service: Shoes\nItem: Sneakers\nAddress: C-1/501, Sai Milan Residency, Opposite jalaram international school, Palanpore canal road, adajan, Surat, Gujarat, 395009	2025-10-31 11:30:00+00	completed	2025-10-31 10:45:26.248187+00	350.00	paid	\N	\N	\N	\N	pending	t	\N	\N	\N	\N	\N	2025-10-31 10:47:35.324345	\N	7	\N	\N	f	f
80	1	21	Service: Home Cleaning\nItem: Washing Utensils\nAddress: C-1/501, Sai Milan Residency, Opposite jalaram international school, Palanpore canal road, adajan, Surat, Gujarat, 395009	2025-10-31 13:30:00+00	cancelled	2025-10-31 12:44:21.56275+00	300.00	unpaid	\N	\N	\N	\N	pending	f	\N	\N	\N	\N	\N	\N	Booked bymistake.	4	\N	\N	f	f
82	1	21	Service: Clothes\nItem: Washing\nAddress: C-1/501, Sai Milan Residency, Opposite jalaram international school, Palanpore canal road, adajan, Surat, Gujarat, 395009	2025-10-31 05:30:00+00	cancelled	2025-10-31 19:24:18.116328+00	400.00	unpaid	\N	\N	\N	\N	pending	f	\N	7	200.00	\N	\N	\N	My Mistake, So Sorry	5	\N	\N	f	f
86	24	21	Service: Home Cleaning\nItem: Washing Utensils\nAddress: H-2, Florida Bunglows, Motavarachha, Surat, Surat, Gujarat, 394101	2025-11-02 12:30:00+00	completed	2025-11-02 11:45:32.651275+00	350.00	paid	\N	\N	\N	\N	pending	t	\N	\N	\N	\N	\N	2025-11-02 11:46:12.186747	\N	4	300.00	50.00	f	f
84	1	21	Service: Bike\nItem: Washing\nAddress: C-1/501, Sai Milan Residency, Opposite jalaram international school, Palanpore canal road, adajan, Surat, Gujarat, 395009	2025-11-01 15:30:00+00	completed	2025-11-01 15:22:36.409574+00	450.00	paid	\N	\N	\N	\N	pending	t	\N	\N	\N	\N	\N	2025-11-01 15:28:20.806772	\N	1	400.00	50.00	f	f
88	1	21	Service: Clothes\nItem: Dry Clean\nAddress: C-1/501, Sai Milan Residency, Opposite jalaram international school, Palanpore canal road, adajan, Surat, Gujarat, 395009	2025-11-03 10:30:00+00	completed	2025-11-03 08:39:22.541787+00	450.00	paid	\N	\N	\N	\N	pending	t	\N	\N	\N	\N	\N	2025-11-03 08:40:42.128944	\N	6	400.00	50.00	f	f
85	24	21	Service: Bike\nItem: Washing\nAddress: H-2, Florida Bunglows, Motavarachha, Surat, Surat, Gujarat, 394101	2025-11-02 08:30:00+00	completed	2025-11-02 07:49:37.290483+00	450.00	paid	\N	\N	\N	\N	pending	t	\N	7	270.00	\N	\N	2025-11-02 07:50:09.915079	\N	1	400.00	50.00	f	f
90	1	21	Service: Clothes\nItem: Washing\nAddress: C-1/501, Sai Milan Residency, Opposite jalaram international school, Palanpore canal road, adajan, Surat, Gujarat, 395009	2025-11-03 15:30:00+00	completed	2025-11-03 14:19:38.996347+00	450.00	paid	\N	\N	\N	\N	pending	t	\N	\N	\N	\N	\N	2025-11-03 14:20:11.368077	\N	5	400.00	50.00	f	f
91	1	21	Service: Clothes\nItem: Dry Clean\nAddress: C-1/501, Sai Milan Residency, Opposite jalaram international school, Palanpore canal road, adajan, Surat, Gujarat, 395009	2025-11-03 15:30:00+00	completed	2025-11-03 14:37:08.989454+00	450.00	paid	\N	\N	\N	\N	pending	t	\N	\N	\N	\N	\N	2025-11-03 14:37:27.894171	\N	6	400.00	50.00	f	f
92	1	21	Service: Clothes\nItem: Dry Clean\nAddress: C-1/501, Sai Milan Residency, Opposite jalaram international school, Palanpore canal road, adajan, Surat, Gujarat, 395009	2025-11-03 15:30:00+00	cancelled	2025-11-03 14:41:54.167495+00	450.00	unpaid	\N	\N	\N	\N	pending	f	\N	\N	\N	\N	\N	\N	By mistake Anna	6	400.00	50.00	f	f
\.


--
-- Data for Name: combo_items; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.combo_items (combo_id, sub_service_item_id) FROM stdin;
\.


--
-- Data for Name: notifications; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.notifications (id, user_id, actor_id, message, link, is_read, created_at) FROM stdin;
98	20	1	New 1-star review by Fenil Pastagia  for booking.	booking-details.php?id=97	t	2025-11-06 09:53:18.54666+00
42	20	1	Payment received for booking #93 from Fenil Pastagia  (₹450.00).	booking-details.php?id=93	t	2025-11-03 18:21:26.395346+00
18	20	1	New booking (#91) from Fenil Pastagia  for worker ID 21.	booking-details.php?id=91	t	2025-11-03 14:37:09.314628+00
44	20	1	New 5-star review by Fenil Pastagia  for booking #93.	booking-details.php?id=93	t	2025-11-03 18:21:42.004857+00
20	20	21	Worker Veer Thakkar confirmed booking #91.	booking-details.php?id=91	t	2025-11-03 14:37:27.894171+00
22	20	21	Worker Veer Thakkar started job #91.	booking-details.php?id=91	t	2025-11-03 14:37:49.809227+00
46	20	1	New booking (#94) from Fenil Pastagia  for worker ID 22.	booking-details.php?id=94	t	2025-11-04 06:01:24.038413+00
24	20	21	Worker Veer Thakkar completed job #91.	booking-details.php?id=91	t	2025-11-03 14:38:03.351974+00
26	20	1	Payment received for booking #91 from Fenil Pastagia  (₹450.00).	booking-details.php?id=91	t	2025-11-03 14:39:32.538456+00
48	20	22	Worker Hemant Sharma confirmed booking #94.	booking-details.php?id=94	t	2025-11-04 06:28:57.000876+00
28	20	1	New 4-star review by Fenil Pastagia  for booking #91.	booking-details.php?id=91	t	2025-11-03 14:40:06.009213+00
30	20	1	New booking (#92) from Fenil Pastagia  for worker ID 21.	booking-details.php?id=92	t	2025-11-03 14:41:54.412048+00
50	20	22	Worker Hemant Sharma started job #94.	booking-details.php?id=94	t	2025-11-04 15:52:33.088145+00
32	20	1	Customer Fenil Pastagia  cancelled booking #92.	booking-details.php?id=92	t	2025-11-03 14:47:23.793502+00
34	20	1	New booking (#93) from Fenil Pastagia  for worker ID 21.	booking-details.php?id=93	t	2025-11-03 18:14:13.740561+00
36	20	21	Worker Veer Thakkar confirmed booking #93.	booking-details.php?id=93	t	2025-11-03 18:20:32.820789+00
38	20	21	Worker Veer Thakkar started job #93.	booking-details.php?id=93	t	2025-11-03 18:20:49.920947+00
40	20	21	Worker Veer Thakkar completed job #93.	booking-details.php?id=93	t	2025-11-03 18:21:04.756438+00
52	20	22	Worker Hemant Sharma completed job #94.	booking-details.php?id=94	t	2025-11-04 15:52:39.236892+00
54	20	1	Payment received for booking #94 from Fenil Pastagia  (₹250.00).	booking-details.php?id=94	t	2025-11-04 15:53:24.851285+00
56	20	1	New 4-star review by Fenil Pastagia  for booking #94.	booking-details.php?id=94	t	2025-11-04 15:53:36.691121+00
58	20	1	New booking (#95) from Fenil Pastagia  for worker ID 22.	booking-details.php?id=95	t	2025-11-04 16:01:55.848215+00
70	20	1	New booking (#96) from Fenil Pastagia  for worker ID 21.	booking-details.php?id=96	t	2025-11-06 08:35:47.29201+00
60	20	22	Worker Hemant Sharma confirmed booking #95.	booking-details.php?id=95	t	2025-11-04 17:53:48.67089+00
62	20	22	Worker Hemant Sharma started job #95.	booking-details.php?id=95	t	2025-11-04 18:26:57.52858+00
82	20	1	Payment received for booking from Fenil Pastagia  (₹450.00).	booking-details.php?id=96	t	2025-11-06 09:42:21.588862+00
64	20	22	Worker Hemant Sharma completed job #95.	booking-details.php?id=95	t	2025-11-04 18:31:02.232675+00
66	20	1	Payment received for booking #95 from Fenil Pastagia  (₹200.00).	booking-details.php?id=95	t	2025-11-04 18:31:26.086286+00
68	20	1	New 4-star review by Fenil Pastagia  for booking #95.	booking-details.php?id=95	t	2025-11-04 18:31:58.887763+00
84	20	1	New 3-star review by Fenil Pastagia  for booking #96.	booking-details.php?id=96	t	2025-11-06 09:43:13.459119+00
72	20	21	Worker Veer Thakkar confirmed booking #96.	booking-details.php?id=96	t	2025-11-06 08:37:10.001508+00
94	20	21	Worker Veer Thakkar completed job.	booking-details.php?id=97	t	2025-11-06 09:49:56.888604+00
78	20	21	Worker Veer Thakkar started job.	booking-details.php?id=96	t	2025-11-06 09:32:07.20943+00
80	20	21	Worker Veer Thakkar completed job #96.	booking-details.php?id=96	t	2025-11-06 09:33:20.577928+00
86	20	1	New booking (#97) from Fenil Pastagia  for worker ID 21.	booking-details.php?id=97	t	2025-11-06 09:46:06.462247+00
96	20	1	Payment received for booking from Fenil Pastagia  (₹450.00).	booking-details.php?id=97	t	2025-11-06 09:52:45.303937+00
88	20	21	Worker Veer Thakkar confirmed booking.	booking-details.php?id=97	t	2025-11-06 09:46:42.19897+00
92	20	21	Worker Veer Thakkar started job.	booking-details.php?id=97	t	2025-11-06 09:49:29.473155+00
\.


--
-- Data for Name: payouts; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.payouts (id, wallet_id, amount, status, requested_at, processed_at) FROM stdin;
1	1	1500.00	pending	2025-10-11 17:59:05.427704+00	\N
2	1	1200.00	pending	2025-10-13 06:33:06.608701+00	\N
3	1	8220.00	pending	2025-10-31 07:37:55.324528+00	\N
4	1	1375.00	pending	2025-11-01 13:44:39.766054+00	\N
5	1	530.00	pending	2025-11-02 11:43:51.832453+00	\N
\.


--
-- Data for Name: platform_payouts; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.platform_payouts (id, amount, requested_by_admin_id, requested_at, status) FROM stdin;
2	100.00	20	2025-11-02 11:26:00.305598+00	processed
3	50.00	20	2025-11-02 12:00:01.38673+00	processed
\.


--
-- Data for Name: reviews; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.reviews (id, booking_id, reviewer_id, worker_id, rating, comment, created_at) FROM stdin;
1	11	1	21	4	very good experience	2025-09-29 15:44:35.054064+00
2	12	1	4	3	nice	2025-09-29 19:55:02.589242+00
3	15	1	21	4	good	2025-09-29 20:05:28.223525+00
4	29	1	21	3	nice	2025-10-12 11:23:34.40741+00
5	30	1	21	4	good	2025-10-12 11:59:16.754601+00
6	31	1	21	3		2025-10-16 17:51:47.331622+00
7	27	1	21	5		2025-10-16 17:52:32.582185+00
8	28	1	21	2	wrost	2025-10-16 17:52:42.501835+00
9	32	1	21	5		2025-10-18 05:18:04.685782+00
10	34	1	21	4		2025-10-18 06:39:09.241874+00
11	35	1	21	4	Great Service\nPolite behavior 	2025-10-19 04:59:19.750675+00
12	36	1	21	3	Worker wasn't co-operative and rude. Overall his service was fabulous.	2025-10-19 05:28:26.189014+00
13	37	1	21	4		2025-10-19 12:07:13.066105+00
14	38	1	21	5	Worker came on time and completed work before time, his speed and dedication towards work was tremendous.	2025-10-19 12:10:30.596446+00
15	41	1	21	4	Best Ever Experience! Best platform for service booking	2025-10-20 07:11:24.837705+00
16	40	1	21	3	Nice Platform with nice service provider	2025-10-20 07:11:52.831947+00
17	39	1	21	4		2025-10-20 07:12:02.163853+00
18	42	1	21	4		2025-10-20 07:17:20.883183+00
19	47	1	21	4	Worker is good at his job. \nAlso, the DailyFix platform is solving our daily needs efficiently.	2025-10-24 07:48:10.978048+00
20	48	1	22	4	Great job, used modern technology to clean my sneakers. Now, my shoes are looking as a new one.	2025-10-24 09:08:05.241023+00
21	49	1	22	3		2025-10-24 09:45:07.669644+00
22	50	1	22	4	Good Job, my clothes have been dry cleaned perfectly.	2025-10-24 10:12:50.831261+00
23	52	1	21	4	Fantastic service. 	2025-10-25 11:59:50.770626+00
24	57	1	22	3	Decent Service	2025-10-25 14:28:56.825617+00
25	58	1	22	4	Fine 	2025-10-25 16:56:50.919727+00
26	59	1	21	2		2025-10-26 12:32:17.773491+00
27	60	1	21	3		2025-10-26 13:47:34.861664+00
28	63	1	21	5	Best	2025-10-26 16:11:39.818276+00
29	65	1	21	1	wrost	2025-10-26 16:17:29.601481+00
30	67	1	21	4		2025-10-26 18:19:58.340472+00
31	68	1	21	4		2025-10-26 18:43:58.956136+00
32	69	1	21	3		2025-10-26 19:02:21.85186+00
33	70	1	21	2		2025-10-26 19:19:05.434976+00
34	72	1	22	4		2025-10-26 19:44:37.564164+00
35	74	1	21	3		2025-10-27 16:25:44.27124+00
36	75	1	21	4		2025-10-31 09:37:03.891913+00
37	76	1	22	4		2025-10-31 10:44:22.682563+00
38	77	1	22	5		2025-10-31 10:56:08.223339+00
39	78	1	22	2	Decent	2025-10-31 11:59:24.407158+00
40	79	1	21	4		2025-10-31 12:04:05.695872+00
41	81	1	21	5	Best	2025-10-31 19:20:57.993195+00
42	83	1	21	2		2025-11-01 07:17:17.382454+00
43	84	1	21	2	Good at work 	2025-11-01 15:46:51.629647+00
44	85	24	21	4	Excellent Service.	2025-11-02 07:59:58.11555+00
45	86	24	21	4		2025-11-02 11:47:34.234925+00
46	88	1	21	3	Not good not bad	2025-11-03 08:42:04.293849+00
47	90	1	21	2	worst	2025-11-03 14:22:04.966954+00
48	91	1	21	4	Nice experience	2025-11-03 14:40:05.814057+00
49	93	1	21	5		2025-11-03 18:21:41.499906+00
50	94	1	22	4		2025-11-04 15:53:36.471007+00
51	95	1	22	4		2025-11-04 18:31:58.722882+00
52	96	1	21	3		2025-11-06 09:43:07.809117+00
53	97	1	21	1	worst	2025-11-06 09:53:13.401744+00
\.


--
-- Data for Name: service_combos; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.service_combos (id, name, description, combo_price, is_active, created_by_admin_id, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: services; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.services (id, name, icon, slug) FROM stdin;
2	Home Services	fas fa-tools	home-services
3	Vehicle Services	fas fa-car	vehicle-services
6	Washing Machine Services	fas fa-washing-machine	washing-machine-services
8	Elevator Services	fas fa-elevator	elevator-services
1	Cleaning Services	fas fa-broom	cleaning-services
11	Laundry Service	fa-solid fa-hands-bubbles	laundry-service
5	Refrigerator Services	fa-solid fa-refrigerator	refrigerator-services
4	Cooling Services	fa-solid fa-wind	cooling-services
7	Water Purifier Services	fa-solid fa-droplet	water-purifier-services
\.


--
-- Data for Name: sub_service_items; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.sub_service_items (id, sub_service_id, name, icon, slug) FROM stdin;
1	5	Washing	fas fa-motorcycle	washing
2	5	Repair	fa-solid fa-screwdriver-wrench	repair
3	1	Sweeping	fas fa-broom	sweeping
4	1	Washing Utensils	fa-solid fa-utensils	washing-utensils
5	21	Washing	fa-solid fa-soap	washing
6	21	Dry Clean	fa-solid fa-wind	dry-clean
7	22	Sneakers	fa-solid fa-shoe-prints	sneakers
8	22	Running	fa-solid fa-shoe-prints	running
\.


--
-- Data for Name: sub_services; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.sub_services (id, service_id, name, icon, slug) FROM stdin;
1	1	Home Cleaning	fas fa-home	home-cleaning
2	1	Solar Cleaning	fas fa-solar-panel	solar-cleaning
3	2	Electrician	fas fa-bolt	electrician
4	2	Plumber	fas fa-faucet	plumber
9	4	AC Repair	fas fa-screwdriver	ac-repair
10	4	AC Cleaning	fas fa-wind	ac-cleaning
11	4	Fan Repair	fas fa-wrench	fan-repair
12	4	Fan Cleaning	fas fa-fan	fan-cleaning
13	5	Refrigerator Repair	fas fa-wrench	refrigerator-repair
14	5	Refrigerator Cleaning	fas fa-hand-sparkles	refrigerator-cleaning
15	6	Washing Machine Repair	fas fa-tools	washing-machine-repair
16	6	Washing Machine Cleaning	fas fa-soap	washing-machine-cleaning
17	7	RO Repair	fas fa-cogs	ro-repair
18	7	RO Cleaning	fas fa-filter	ro-cleaning
19	8	Elevator Cleaning	fas fa-broom	elevator-cleaning
20	8	Elevator Repair	fas fa-tools	elevator-repair
21	11	Clothes	fa-solid fa-shirt	clothes
22	11	Shoes	fa-solid fa-shoe-prints	shoes
5	3	Bike	fas fa-motorcycle	bike
7	3	Car	fas fa-car	car
\.


--
-- Data for Name: transactions; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.transactions (id, wallet_id, booking_id, type, amount, description, created_at) FROM stdin;
1	1	27	credit	300.00	Payment for Booking #27	2025-10-11 15:54:18.001272+00
2	1	28	credit	400.00	Payment for Booking #28	2025-10-11 16:41:59.316593+00
3	1	29	credit	800.00	Payment for Booking #29	2025-10-11 17:58:18.53873+00
4	1	\N	debit	1500.00	Payout Request #1	2025-10-11 17:59:05.427704+00
5	1	30	credit	800.00	Payment for Booking #30	2025-10-11 18:16:11.134206+00
6	1	31	credit	400.00	Payment for Booking #31	2025-10-13 06:32:30.10881+00
7	1	\N	debit	1200.00	Payout Request #2	2025-10-13 06:33:06.608701+00
8	1	32	credit	400.00	Payment for Booking #32	2025-10-18 05:16:21.303018+00
9	1	32	credit	400.00	Payment for Booking #32	2025-10-18 05:16:28.603419+00
10	1	34	credit	400.00	Payment for Booking #34	2025-10-18 06:38:20.69812+00
11	1	35	credit	250.00	Payment received for Booking #35 (Original: ₹300.00, Discount: ₹50.00)	2025-10-18 09:22:25.3709+00
12	1	36	credit	350.00	Payment received for Booking #36 (Original: ₹400.00, Discount: ₹50.00)	2025-10-19 05:02:45.988515+00
13	1	37	credit	250.00	Payment received for Booking #37 (Original: ₹300.00, Discount: ₹50.00)	2025-10-19 06:08:51.526964+00
14	1	38	credit	365.00	Payment received for Booking #38 (Original: ₹400.00, Discount: ₹35.00)	2025-10-19 11:24:36.010239+00
15	1	39	credit	265.00	Payment received for Booking #39 (Original: ₹300.00, Discount: ₹35.00)	2025-10-19 12:53:48.772976+00
16	1	40	credit	250.00	Payment received for Booking #40 (Original: ₹300.00, Discount: ₹50.00)	2025-10-19 13:37:24.840041+00
17	1	41	credit	365.00	Payment received for Booking #41 (Original: ₹400.00, Discount: ₹35.00)	2025-10-19 13:39:26.890776+00
18	1	42	credit	250.00	Payment received for Booking #42 (Original: ₹300.00, Discount: ₹50.00)	2025-10-20 07:14:00.969554+00
19	1	43	credit	265.00	Payment received for Booking #43 (Original: ₹300.00, Discount: ₹35.00)	2025-10-23 05:49:22.528647+00
20	1	47	credit	265.00	Payment received for Booking #47 (Original: ₹300.00, Discount: ₹35.00)	2025-10-24 07:46:20.774993+00
21	1	46	credit	265.00	Payment received for Booking #46 (Original: ₹300.00, Discount: ₹35.00)	2025-10-24 07:52:24.708781+00
22	1	45	credit	265.00	Payment received for Booking #45 (Original: ₹300.00, Discount: ₹35.00)	2025-10-24 08:21:17.762496+00
23	2	48	credit	330.00	Payment received for Booking #48 (Original: ₹350.00, Discount: ₹20.00)	2025-10-24 09:05:34.301241+00
24	2	49	credit	180.00	Payment received for Booking #49 (Original: ₹200.00, Discount: ₹20.00)	2025-10-24 09:35:53.040332+00
25	2	50	credit	180.00	Payment received for Booking #50 (Original: ₹200.00, Discount: ₹20.00)	2025-10-24 10:02:52.115167+00
26	2	51	credit	130.00	Payment received for Booking #51 (Original: ₹150.00, Discount: ₹20.00)	2025-10-24 10:27:20.629861+00
27	1	52	credit	265.00	Payment received for Booking #52 (Original: ₹300.00, Discount: ₹35.00)	2025-10-25 11:34:39.730456+00
28	2	57	credit	150.00	Payment received for Booking #57	2025-10-25 14:28:38.808491+00
29	2	58	credit	130.00	Payment received for Booking #58 (Original: ₹150.00, Discount: ₹20.00)	2025-10-25 16:50:04.402962+00
30	1	59	credit	275.00	Payment received for Booking #59 (Original: ₹300.00, Discount: ₹25.00)	2025-10-26 12:32:00.453376+00
31	1	60	credit	300.00	Payment received for Booking #60	2025-10-26 13:46:32.038403+00
32	1	63	credit	400.00	Payment received for Booking #63	2025-10-26 16:11:23.766696+00
33	1	65	credit	400.00	Payment received for Booking #65	2025-10-26 16:17:20.478857+00
34	1	67	credit	400.00	Payment received for Booking #67	2025-10-26 18:19:50.551654+00
35	1	68	credit	400.00	Payment received for Booking #68	2025-10-26 18:43:50.169878+00
36	1	69	credit	400.00	Payment received for Booking #69	2025-10-26 19:02:14.678902+00
37	1	70	credit	375.00	Payment received for Booking #70 (Original: ₹400.00, Discount: ₹25.00)	2025-10-26 19:18:57.806475+00
38	2	71	credit	170.00	Payment received for Booking #71 (Original: ₹200.00, Discount: ₹30.00)	2025-10-26 19:27:15.006072+00
39	2	72	credit	120.00	Payment received for Booking #72 (Original: ₹150.00, Discount: ₹30.00)	2025-10-26 19:44:27.222365+00
40	2	73	credit	120.00	Payment received for Booking #73 (Original: ₹150.00, Discount: ₹30.00)	2025-10-26 20:47:12.675277+00
41	1	74	credit	400.00	Payment received for Booking #74	2025-10-27 16:25:15.177651+00
42	1	\N	debit	8220.00	Payout Request #3	2025-10-31 07:37:55.324528+00
43	1	75	credit	275.00	Payment received for Booking #75 (Original: ₹300.00, Discount: ₹25.00)	2025-10-31 09:36:39.984906+00
44	2	76	credit	320.00	Payment received for Booking #76 (Original: ₹350.00, Discount: ₹30.00)	2025-10-31 10:44:14.869338+00
45	2	77	credit	350.00	Payment received for Booking #77	2025-10-31 10:55:46.024603+00
46	2	78	credit	200.00	Payment received for Booking #78	2025-10-31 11:59:07.740407+00
47	1	79	credit	300.00	Payment received for Booking #79	2025-10-31 12:03:48.13533+00
48	1	81	credit	400.00	Payment received for Booking #81	2025-10-31 19:20:48.568026+00
49	1	83	credit	400.00	Payment received for Booking #83	2025-11-01 07:16:53.979639+00
50	1	\N	debit	1375.00	Payout Request #4	2025-11-01 13:44:39.766054+00
51	1	84	credit	400.00	Payment for Booking #84	2025-11-01 15:45:54.40496+00
52	1	85	credit	130.00	Payment for Booking #85 (Worker Price: ₹400.00, Discount: ₹270.00)	2025-11-02 07:59:29.323642+00
53	1	\N	debit	530.00	Payout Request #5	2025-11-02 11:43:51.832453+00
54	1	86	credit	300.00	Payment for Booking #86	2025-11-02 11:47:16.548731+00
55	1	88	credit	400.00	Payment for Booking #88	2025-11-03 08:41:42.667263+00
56	1	90	credit	400.00	Payment for Booking #90	2025-11-03 14:21:34.488484+00
57	1	91	credit	400.00	Payment for Booking #91	2025-11-03 14:39:32.538456+00
58	1	93	credit	400.00	Payment for Booking #93	2025-11-03 18:21:26.395346+00
59	2	94	credit	200.00	Payment for Booking #94	2025-11-04 15:53:24.851285+00
60	2	95	credit	150.00	Payment for Booking #95	2025-11-04 18:31:26.086286+00
61	1	96	credit	400.00	Payment for Booking #96	2025-11-06 09:42:21.588862+00
62	1	97	credit	400.00	Payment for Booking #97	2025-11-06 09:52:45.303937+00
\.


--
-- Data for Name: user_coupon_usage; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.user_coupon_usage (id, user_id, offer_id, sub_service_item_id, booking_id, used_at) FROM stdin;
1	1	5	4	59	2025-10-26 12:30:52.484706+00
2	1	5	6	62	2025-10-26 15:16:14.576715+00
4	1	5	5	70	2025-10-26 19:15:17.25843+00
6	1	6	6	71	2025-10-26 19:23:09.192718+00
8	1	6	5	72	2025-10-26 19:29:32.929932+00
37	1	6	8	73	2025-10-26 20:46:14.513491+00
38	1	5	3	75	2025-10-31 07:45:30.484848+00
40	1	6	7	76	2025-10-31 10:44:10.055982+00
41	1	7	5	82	2025-10-31 19:24:18.116328+00
43	24	7	1	85	2025-11-02 07:56:21.703977+00
44	1	7	6	\N	2025-11-03 08:24:55.527119+00
\.


--
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.users (id, full_name, email, password, phone, role, profile_image, account_status, created_at, latitude, longitude, address_line1, address_line2, city, pincode, state, otp_code, otp_expires_at, auth_user_id) FROM stdin;
7	Swayam Shah	swayam@gmail.com	$2y$10$EKVBlEHgag1sAKIl0Cmp8Oor/rLjv7OgJnjKtHzTHffBhSinsknym	9623001236	customer	\N	active	2025-08-16 09:48:01.576568+00	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N
8	Ankit Verma	ankit@gmail.com	$2y$10$h0lmMCHM3ae9qv342gWjZ.BJQ69as.7JvPDJa5QBWjb5YOl3oFRhq	8523001456	customer	\N	active	2025-08-16 09:52:37.721337+00	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N
9	Sushant Rajput	sushant@gmail.com	$2y$10$oWRFuH4Qgcgu6HSAPcCx1uDvV/k8qYWMvqF2dSgZz41xQHApe.jrC	9852110036	customer	\N	active	2025-08-16 09:56:45.35922+00	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N
21	Veer Thakkar	veer@gmail.com	$2y$10$Hf5QHQMiWXlGvJyhNQ4Jpe2BkUuAHIAm9WOK34S/t./di/XBQ3r9q	9632015877	worker	uploads/profile_images/user_21_1762001199.png	active	2025-09-28 13:16:38.414164+00	21.22064510	72.89456170	G-90, Shital Residency	Yogi chowk	Surat	395006	Gujarat	\N	\N	\N
23	jay	jay1509@gmail.com	$2y$10$IW5WX/sdoLsU0aGYTqlcYeue.EPxTJXsLCF/ud9D2BVB3MSrYW9sS	6352121293	customer	uploads/profile_images/68f5ed6bca016.jpg	active	2025-10-20 08:06:14.508593+00	22.28881053	70.76566453	20,Jay Shivam Soc; Part - 2	Cozway Road	Surat	395004	Gujarat	\N	\N	\N
20	Admin	admin@dailyfix.com	$2y$10$9Y52WOIkRx0SJHJ82NXYXOYRLEt/pgwVwm56RlEOF6zLPIGBN9GXm	\N	admin	\N	active	2025-09-20 09:59:34.499611+00	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N
24	Yashvi Rana	yashvi@gmail.com	$2y$10$hHOg9jXuw8odCQseuDcrU.fdJhBcKOV4k6lJYdpTAd2/9cfg8gpNW	9632014782	customer	uploads/profile_images/6904acf553c4c.jpg	active	2025-10-31 12:35:01.925419+00	21.21139906	72.82916210	H-2, Florida Bunglows	Motavarachha, Surat	Surat	394101	Gujarat	\N	\N	\N
3	Virat Kohli	virat@gmail.com	$2y$10$xCaKDIuIIXcrgmgbhG1qauii9eg.I6xoVBRhpdIFMbfIc6fMkP4Fq	9567845678	worker	worker/uploads/689f09ae6e9e93.79883047.jpg	active	2025-08-15 10:19:25.929927+00	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N
4	Meet Patel	meet@gmail.com	$2y$10$Fc52.M4rjTo1VYJ8Twozte8/tB.L7.SDLLJGMQn800kTVTTFWbHUC	8623014565	worker	worker/uploads/68a02953e7d0c8.06014632.jpg	active	2025-08-16 06:46:40.089848+00	21.23536635	72.85583496	A-201, Skylar Heights,	Motavarachha	Surat	394101	Gujarat	\N	\N	\N
5	Hitesh Shah	hitesh@gmail.com	$2y$10$QZgi4HWyj5TVuCEfpIq.o.tLBq35nguAGeV90xiI06UMgdzLGY2Di	6932012369	worker	worker/uploads/68a04602ad52c3.52796117.jpg	active	2025-08-16 08:49:03.588917+00	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N
6	Rahul Vora	rahul@gmail.com	$2y$10$o5ghyaUlqZkfCHYgKwlFN.Y3atdc/8jkNg6cCW0bvqfGi51c8sUii	9632012365	customer	customer/uploads/68a0525b8283f3.07493807.jpg	active	2025-08-16 09:41:44.250926+00	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N
10	Aditi Patel	aditi@gmail.com	$2y$10$0aB6BMI7DRHkFcAovv9ED.jNIz0mSQquJ8EpURzvNLoQv.qn1FQ7K	9874100023	customer	customer/uploads/68a065cbd58185.96856248.jpg	active	2025-08-16 11:04:40.74063+00	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N
11	Rohan Desai	rohan@gmail.com	$2y$10$nVqwaKveVe5Mfg8BAEFtaeKHzwTNQwE/r58P3bptk/SacGFay.2c.	8523698741	worker	worker/uploads/68a0671d71e664.67024751.jpg	active	2025-08-16 11:10:18.349969+00	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N
12	Digvesh Rathi	digvesh@gmail.com	$2y$10$1RJVdcmwyqNsovlfCor3m.fd/37Vr70k3HgKmQeqiNxJAmoITwoDK	9852001423	customer	customer/uploads/68a06a0b3a4c22.03167999.png	active	2025-08-16 11:22:48.144741+00	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N
17	Rudra Shah	rudra@gmail.com	$2y$10$RNmrvbr0r6pmADrz4Xt8MOo.NpZdHA0rHCb/a7uz/8EsYLtDmXzyO	9123546789	customer	uploads/profile_images/68a760fa384d7.png	active	2025-08-21 18:10:02.379476+00	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N
14	Jay Parmar	jay@gmail.com	$2y$10$ppm4pfQmN/myqpLhkBdAZuuUjItBBGqa5rs/f8r/eFuWNjFsIrxdK	9678657898	worker	worker/uploads/68a5e1d8beb549.59203232.jpg	active	2025-08-20 14:55:20.430197+00	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N
18	Rupesh Patel	rupesh@gmail.com	$2y$10$ZLkkPrvfOKjGu9ldMOs9fe/r0mVK.vk6ucMEwDCQWngZ1cS.hvZWS	9235467896	worker	uploads/profile_images/68a7619b016ed.jpg	active	2025-08-21 18:12:43.168886+00	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N
22	Hemant Sharma	hemant@gmail.com	$2y$10$sreN4.DgD8U9MSaZIY8XSOBtZiFwKs67/przmb4WIfPmzyJfFI.eO	9853201478	worker	uploads/profile_images/68eaa49e7192b.jpg	active	2025-10-11 18:40:22.808899+00	21.23831114	72.83171587	J-10, WeCare Laudry, Sumeru Complex	Laxmikant Ashram Road	Surat	305994	Gujarat	\N	\N	\N
1	Fenil Pastagia 	17fenill@gmail.com	$2y$10$UuPLUH4YyaSChm/ETqTxN.9Qsu8BhJGn2Hayg9/VbiGP7wUYge17m	9924976503	customer	customer/uploads/689ef2dd50d8f0.56531819.jpg	active	2025-08-15 08:42:04.605469+00	21.17795584	72.83668498	C-1/501, Sai Milan Residency	Opposite jalaram international school, Palanpore canal road, adajan	Surat	395009	Gujarat	301575	2025-10-13 08:35:26	\N
\.


--
-- Data for Name: wallets; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.wallets (id, worker_id, balance, created_at, updated_at) FROM stdin;
2	22	2730.00	2025-10-24 09:05:34.301241+00	2025-10-24 09:05:34.301241+00
1	21	2700.00	2025-10-11 12:57:28.098856+00	2025-11-02 11:43:51.832453+00
\.


--
-- Data for Name: worker_availability; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.worker_availability (id, user_id, date, time_slot, created_at) FROM stdin;
347	21	2025-09-30	10:00:00	2025-09-30 15:47:07.977515+00
350	21	2025-10-04	10:00:00	2025-09-30 15:47:07.995331+00
351	21	2025-09-30	11:00:00	2025-09-30 15:47:07.977515+00
352	21	2025-10-04	11:00:00	2025-09-30 15:47:07.995331+00
357	21	2025-09-30	17:00:00	2025-09-30 15:47:07.977515+00
361	21	2025-10-04	17:00:00	2025-09-30 15:47:07.995331+00
362	21	2025-09-30	18:00:00	2025-09-30 15:47:07.977515+00
367	21	2025-10-04	18:00:00	2025-09-30 15:47:07.995331+00
368	21	2025-09-30	19:00:00	2025-09-30 15:47:07.977515+00
372	21	2025-10-04	19:00:00	2025-09-30 15:47:07.995331+00
374	21	2025-09-30	20:00:00	2025-09-30 15:47:07.977515+00
378	21	2025-10-04	20:00:00	2025-09-30 15:47:07.995331+00
442	21	2025-10-11	10:00:00	2025-10-11 12:18:46.039766+00
443	21	2025-10-11	11:00:00	2025-10-11 12:18:46.039766+00
322	4	2025-09-28	09:00:00	2025-09-28 14:06:24.54987+00
444	21	2025-10-11	14:00:00	2025-10-11 12:18:46.039766+00
327	4	2025-09-28	11:00:00	2025-09-28 14:06:24.54987+00
330	4	2025-09-28	20:00:00	2025-09-28 14:06:24.54987+00
338	4	2025-09-30	09:00:00	2025-09-28 14:08:17.314321+00
339	4	2025-09-30	11:00:00	2025-09-28 14:08:17.314321+00
340	4	2025-09-30	15:00:00	2025-09-28 14:08:17.314321+00
341	4	2025-09-30	20:00:00	2025-09-28 14:08:17.314321+00
445	21	2025-10-11	17:00:00	2025-10-11 12:18:46.039766+00
446	21	2025-10-11	18:00:00	2025-10-11 12:18:46.039766+00
447	21	2025-10-11	19:00:00	2025-10-11 12:18:46.039766+00
448	21	2025-10-11	20:00:00	2025-10-11 12:18:46.039766+00
449	21	2025-10-11	21:00:00	2025-10-11 12:18:46.039766+00
450	21	2025-10-11	22:00:00	2025-10-11 12:18:46.039766+00
150	4	2025-09-24	09:00:00	2025-09-20 12:34:36.774442+00
151	4	2025-09-24	10:00:00	2025-09-20 12:34:36.774442+00
152	4	2025-09-24	11:00:00	2025-09-20 12:34:36.774442+00
153	4	2025-09-25	09:00:00	2025-09-20 12:34:36.803045+00
154	4	2025-09-20	09:00:00	2025-09-20 12:34:36.809386+00
155	4	2025-09-23	09:00:00	2025-09-20 12:34:36.82439+00
156	4	2025-09-25	10:00:00	2025-09-20 12:34:36.803045+00
157	4	2025-09-20	10:00:00	2025-09-20 12:34:36.809386+00
400	4	2025-10-02	09:00:00	2025-10-01 15:36:29.215619+00
402	4	2025-10-03	09:00:00	2025-10-01 15:36:29.221974+00
401	4	2025-10-01	09:00:00	2025-10-01 15:36:29.19684+00
404	4	2025-10-03	11:00:00	2025-10-01 15:36:29.221974+00
405	4	2025-10-01	11:00:00	2025-10-01 15:36:29.19684+00
406	4	2025-10-02	11:00:00	2025-10-01 15:36:29.215619+00
461	21	2025-10-21	09:00:00	2025-10-18 05:14:30.685539+00
158	4	2025-09-20	11:00:00	2025-09-20 12:34:36.809386+00
159	4	2025-09-23	10:00:00	2025-09-20 12:34:36.82439+00
161	4	2025-09-25	11:00:00	2025-09-20 12:34:36.803045+00
160	4	2025-09-22	09:00:00	2025-09-20 12:34:36.833706+00
162	4	2025-09-21	09:00:00	2025-09-20 12:34:36.833719+00
163	4	2025-09-23	11:00:00	2025-09-20 12:34:36.82439+00
164	4	2025-09-22	10:00:00	2025-09-20 12:34:36.833706+00
165	4	2025-09-21	10:00:00	2025-09-20 12:34:36.833719+00
166	4	2025-09-22	11:00:00	2025-09-20 12:34:36.833706+00
167	4	2025-09-21	11:00:00	2025-09-20 12:34:36.833719+00
168	4	2025-09-26	09:00:00	2025-09-20 12:34:37.140077+00
169	4	2025-09-26	10:00:00	2025-09-20 12:34:37.140077+00
170	4	2025-09-26	11:00:00	2025-09-20 12:34:37.140077+00
410	4	2025-10-01	17:00:00	2025-10-01 15:36:29.19684+00
411	4	2025-10-03	17:00:00	2025-10-01 15:36:29.221974+00
412	4	2025-10-02	17:00:00	2025-10-01 15:36:29.215619+00
414	4	2025-10-01	20:00:00	2025-10-01 15:36:29.19684+00
416	4	2025-10-03	20:00:00	2025-10-01 15:36:29.221974+00
418	4	2025-10-02	20:00:00	2025-10-01 15:36:29.215619+00
465	21	2025-10-21	10:00:00	2025-10-18 05:14:30.685539+00
430	4	2025-10-06	11:00:00	2025-10-04 18:12:19.256617+00
434	4	2025-10-06	18:00:00	2025-10-04 18:12:19.256617+00
437	4	2025-10-05	11:00:00	2025-10-04 18:12:19.304729+00
439	4	2025-10-05	18:00:00	2025-10-04 18:12:19.304729+00
466	21	2025-10-23	09:00:00	2025-10-18 05:14:30.793653+00
470	21	2025-10-21	11:00:00	2025-10-18 05:14:30.685539+00
471	21	2025-10-23	10:00:00	2025-10-18 05:14:30.793653+00
476	21	2025-10-21	12:00:00	2025-10-18 05:14:30.685539+00
477	21	2025-10-23	11:00:00	2025-10-18 05:14:30.793653+00
482	21	2025-10-21	13:00:00	2025-10-18 05:14:30.685539+00
486	21	2025-10-21	18:00:00	2025-10-18 05:14:30.685539+00
487	21	2025-10-23	12:00:00	2025-10-18 05:14:30.793653+00
490	21	2025-10-21	19:00:00	2025-10-18 05:14:30.685539+00
493	21	2025-10-23	13:00:00	2025-10-18 05:14:30.793653+00
496	21	2025-10-23	18:00:00	2025-10-18 05:14:30.793653+00
498	21	2025-10-23	19:00:00	2025-10-18 05:14:30.793653+00
508	21	2025-10-18	09:00:00	2025-10-18 08:00:42.837988+00
509	21	2025-10-18	10:00:00	2025-10-18 08:00:42.837988+00
510	21	2025-10-18	11:00:00	2025-10-18 08:00:42.837988+00
511	21	2025-10-18	12:00:00	2025-10-18 08:00:42.837988+00
512	21	2025-10-18	13:00:00	2025-10-18 08:00:42.837988+00
513	21	2025-10-18	14:00:00	2025-10-18 08:00:42.837988+00
514	21	2025-10-18	18:00:00	2025-10-18 08:00:42.837988+00
515	21	2025-10-18	19:00:00	2025-10-18 08:00:42.837988+00
526	22	2025-10-24	10:00:00	2025-10-24 08:22:59.022434+00
531	22	2025-10-24	11:00:00	2025-10-24 08:22:59.022434+00
532	22	2025-10-28	10:00:00	2025-10-24 08:22:59.039411+00
537	22	2025-10-24	12:00:00	2025-10-24 08:22:59.022434+00
539	22	2025-10-28	11:00:00	2025-10-24 08:22:59.039411+00
543	22	2025-10-24	15:00:00	2025-10-24 08:22:59.022434+00
548	22	2025-10-28	12:00:00	2025-10-24 08:22:59.039411+00
549	22	2025-10-24	17:00:00	2025-10-24 08:22:59.022434+00
554	22	2025-10-28	15:00:00	2025-10-24 08:22:59.039411+00
556	22	2025-10-24	18:00:00	2025-10-24 08:22:59.022434+00
560	22	2025-10-28	17:00:00	2025-10-24 08:22:59.039411+00
562	22	2025-10-24	19:00:00	2025-10-24 08:22:59.022434+00
221	21	2025-09-29	10:00:00	2025-09-28 13:18:07.367208+00
566	22	2025-10-28	18:00:00	2025-10-24 08:22:59.039411+00
567	22	2025-10-24	21:00:00	2025-10-24 08:22:59.022434+00
570	22	2025-10-28	19:00:00	2025-10-24 08:22:59.039411+00
225	21	2025-09-29	11:00:00	2025-09-28 13:18:07.367208+00
571	22	2025-10-28	21:00:00	2025-10-24 08:22:59.039411+00
227	21	2025-09-28	10:00:00	2025-09-28 13:18:07.392457+00
581	21	2025-10-30	10:00:00	2025-10-25 09:29:46.782251+00
584	21	2025-10-30	11:00:00	2025-10-25 09:29:46.782251+00
230	21	2025-09-29	17:00:00	2025-09-28 13:18:07.367208+00
588	21	2025-10-25	10:00:00	2025-10-25 09:29:46.803321+00
590	21	2025-10-30	12:00:00	2025-10-25 09:29:46.782251+00
594	21	2025-10-25	11:00:00	2025-10-25 09:29:46.803321+00
595	21	2025-10-30	13:00:00	2025-10-25 09:29:46.782251+00
235	21	2025-09-29	18:00:00	2025-09-28 13:18:07.367208+00
236	21	2025-09-28	11:00:00	2025-09-28 13:18:07.392457+00
600	21	2025-10-25	12:00:00	2025-10-25 09:29:46.803321+00
601	21	2025-10-30	14:00:00	2025-10-25 09:29:46.782251+00
606	21	2025-10-30	17:00:00	2025-10-25 09:29:46.782251+00
607	21	2025-10-25	13:00:00	2025-10-25 09:29:46.803321+00
241	21	2025-09-29	19:00:00	2025-09-28 13:18:07.367208+00
612	21	2025-10-30	18:00:00	2025-10-25 09:29:46.782251+00
243	21	2025-09-28	17:00:00	2025-09-28 13:18:07.392457+00
613	21	2025-10-25	14:00:00	2025-10-25 09:29:46.803321+00
618	21	2025-10-30	19:00:00	2025-10-25 09:29:46.782251+00
619	21	2025-10-25	17:00:00	2025-10-25 09:29:46.803321+00
247	21	2025-09-29	20:00:00	2025-09-28 13:18:07.367208+00
248	21	2025-09-28	18:00:00	2025-09-28 13:18:07.392457+00
624	21	2025-10-30	20:00:00	2025-10-25 09:29:46.782251+00
250	21	2025-09-28	19:00:00	2025-09-28 13:18:07.392457+00
451	21	2025-10-12	09:00:00	2025-10-11 16:34:33.178052+00
452	21	2025-10-12	10:00:00	2025-10-11 16:34:33.178052+00
353	21	2025-10-05	10:00:00	2025-09-30 15:47:08.001006+00
360	21	2025-10-05	11:00:00	2025-09-30 15:47:08.001006+00
472	21	2025-10-22	09:00:00	2025-10-18 05:14:30.87386+00
365	21	2025-10-05	17:00:00	2025-09-30 15:47:08.001006+00
478	21	2025-10-22	10:00:00	2025-10-18 05:14:30.87386+00
488	21	2025-10-22	11:00:00	2025-10-18 05:14:30.87386+00
494	21	2025-10-22	12:00:00	2025-10-18 05:14:30.87386+00
497	21	2025-10-22	13:00:00	2025-10-18 05:14:30.87386+00
499	21	2025-10-22	18:00:00	2025-10-18 05:14:30.87386+00
342	4	2025-09-29	09:00:00	2025-09-29 15:04:30.547022+00
343	4	2025-09-29	11:00:00	2025-09-29 15:04:30.547022+00
344	4	2025-09-29	12:00:00	2025-09-29 15:04:30.547022+00
345	4	2025-09-29	20:00:00	2025-09-29 15:04:30.547022+00
346	4	2025-09-29	21:00:00	2025-09-29 15:04:30.547022+00
500	21	2025-10-22	19:00:00	2025-10-18 05:14:30.87386+00
373	21	2025-10-05	18:00:00	2025-09-30 15:47:08.001006+00
516	21	2025-10-19	09:00:00	2025-10-19 13:30:51.959584+00
379	21	2025-10-05	19:00:00	2025-09-30 15:47:08.001006+00
381	21	2025-10-05	20:00:00	2025-09-30 15:47:08.001006+00
389	21	2025-10-06	10:00:00	2025-09-30 15:47:15.41401+00
390	21	2025-10-06	11:00:00	2025-09-30 15:47:15.41401+00
391	21	2025-10-06	17:00:00	2025-09-30 15:47:15.41401+00
392	21	2025-10-06	18:00:00	2025-09-30 15:47:15.41401+00
393	21	2025-10-06	19:00:00	2025-09-30 15:47:15.41401+00
394	21	2025-10-06	20:00:00	2025-09-30 15:47:15.41401+00
517	21	2025-10-19	10:00:00	2025-10-19 13:30:51.959584+00
518	21	2025-10-19	11:00:00	2025-10-19 13:30:51.959584+00
519	21	2025-10-19	12:00:00	2025-10-19 13:30:51.959584+00
520	21	2025-10-19	13:00:00	2025-10-19 13:30:51.959584+00
521	21	2025-10-19	18:00:00	2025-10-19 13:30:51.959584+00
522	21	2025-10-19	19:00:00	2025-10-19 13:30:51.959584+00
523	21	2025-10-19	20:00:00	2025-10-19 13:30:51.959584+00
527	22	2025-10-29	10:00:00	2025-10-24 08:22:59.039933+00
432	4	2025-10-07	11:00:00	2025-10-04 18:12:19.277736+00
435	4	2025-10-07	18:00:00	2025-10-04 18:12:19.277736+00
440	4	2025-10-10	11:00:00	2025-10-04 18:12:19.638402+00
441	4	2025-10-10	18:00:00	2025-10-04 18:12:19.638402+00
533	22	2025-10-29	11:00:00	2025-10-24 08:22:59.039933+00
538	22	2025-10-29	12:00:00	2025-10-24 08:22:59.039933+00
545	22	2025-10-29	15:00:00	2025-10-24 08:22:59.039933+00
552	22	2025-10-29	17:00:00	2025-10-24 08:22:59.039933+00
558	22	2025-10-29	18:00:00	2025-10-24 08:22:59.039933+00
564	22	2025-10-29	19:00:00	2025-10-24 08:22:59.039933+00
568	22	2025-10-29	21:00:00	2025-10-24 08:22:59.039933+00
572	22	2025-10-30	10:00:00	2025-10-24 08:22:59.456612+00
573	22	2025-10-30	11:00:00	2025-10-24 08:22:59.456612+00
574	22	2025-10-30	12:00:00	2025-10-24 08:22:59.456612+00
575	22	2025-10-30	15:00:00	2025-10-24 08:22:59.456612+00
576	22	2025-10-30	17:00:00	2025-10-24 08:22:59.456612+00
577	22	2025-10-30	18:00:00	2025-10-24 08:22:59.456612+00
578	22	2025-10-30	19:00:00	2025-10-24 08:22:59.456612+00
579	22	2025-10-30	21:00:00	2025-10-24 08:22:59.456612+00
582	21	2025-10-28	10:00:00	2025-10-25 09:29:46.782704+00
585	21	2025-10-28	11:00:00	2025-10-25 09:29:46.782704+00
589	21	2025-10-29	10:00:00	2025-10-25 09:29:46.805948+00
592	21	2025-10-28	12:00:00	2025-10-25 09:29:46.782704+00
596	21	2025-10-29	11:00:00	2025-10-25 09:29:46.805948+00
598	21	2025-10-28	13:00:00	2025-10-25 09:29:46.782704+00
602	21	2025-10-29	12:00:00	2025-10-25 09:29:46.805948+00
604	21	2025-10-28	14:00:00	2025-10-25 09:29:46.782704+00
608	21	2025-10-29	13:00:00	2025-10-25 09:29:46.805948+00
609	21	2025-10-28	17:00:00	2025-10-25 09:29:46.782704+00
614	21	2025-10-29	14:00:00	2025-10-25 09:29:46.805948+00
615	21	2025-10-28	18:00:00	2025-10-25 09:29:46.782704+00
621	21	2025-10-28	19:00:00	2025-10-25 09:29:46.782704+00
623	21	2025-10-29	17:00:00	2025-10-25 09:29:46.805948+00
626	21	2025-10-28	20:00:00	2025-10-25 09:29:46.782704+00
629	21	2025-10-29	18:00:00	2025-10-25 09:29:46.805948+00
631	21	2025-10-29	19:00:00	2025-10-25 09:29:46.805948+00
633	21	2025-10-29	20:00:00	2025-10-25 09:29:46.805948+00
662	22	2025-11-02	10:00:00	2025-10-31 09:40:34.375251+00
664	22	2025-11-05	10:00:00	2025-10-31 09:40:34.381259+00
667	22	2025-11-02	11:00:00	2025-10-31 09:40:34.375251+00
671	22	2025-11-05	11:00:00	2025-10-31 09:40:34.381259+00
670	22	2025-11-01	10:00:00	2025-10-31 09:40:34.404043+00
673	22	2025-11-02	12:00:00	2025-10-31 09:40:34.375251+00
676	22	2025-11-01	11:00:00	2025-10-31 09:40:34.404043+00
677	22	2025-11-05	12:00:00	2025-10-31 09:40:34.381259+00
679	22	2025-11-02	14:00:00	2025-10-31 09:40:34.375251+00
682	22	2025-11-01	12:00:00	2025-10-31 09:40:34.404043+00
683	22	2025-11-05	14:00:00	2025-10-31 09:40:34.381259+00
686	22	2025-11-02	15:00:00	2025-10-31 09:40:34.375251+00
689	22	2025-11-01	14:00:00	2025-10-31 09:40:34.404043+00
690	22	2025-11-05	15:00:00	2025-10-31 09:40:34.381259+00
691	22	2025-11-02	17:00:00	2025-10-31 09:40:34.375251+00
695	22	2025-11-01	15:00:00	2025-10-31 09:40:34.404043+00
696	22	2025-11-05	17:00:00	2025-10-31 09:40:34.381259+00
697	22	2025-11-02	18:00:00	2025-10-31 09:40:34.375251+00
700	22	2025-11-02	19:00:00	2025-10-31 09:40:34.375251+00
702	22	2025-11-01	17:00:00	2025-10-31 09:40:34.404043+00
704	22	2025-11-05	18:00:00	2025-10-31 09:40:34.381259+00
707	22	2025-11-02	21:00:00	2025-10-31 09:40:34.375251+00
709	22	2025-11-01	18:00:00	2025-10-31 09:40:34.404043+00
710	22	2025-11-05	19:00:00	2025-10-31 09:40:34.381259+00
712	22	2025-11-01	19:00:00	2025-10-31 09:40:34.404043+00
713	22	2025-11-05	21:00:00	2025-10-31 09:40:34.381259+00
714	22	2025-11-01	21:00:00	2025-10-31 09:40:34.404043+00
252	21	2025-09-28	20:00:00	2025-09-28 13:18:07.392457+00
453	21	2025-10-13	10:00:00	2025-10-13 06:29:00.977933+00
454	21	2025-10-13	11:00:00	2025-10-13 06:29:00.977933+00
455	21	2025-10-13	12:00:00	2025-10-13 06:29:00.977933+00
456	21	2025-10-13	13:00:00	2025-10-13 06:29:00.977933+00
457	21	2025-10-13	18:00:00	2025-10-13 06:29:00.977933+00
458	21	2025-10-13	19:00:00	2025-10-13 06:29:00.977933+00
348	21	2025-10-01	10:00:00	2025-09-30 15:47:07.99174+00
355	21	2025-10-01	11:00:00	2025-09-30 15:47:07.99174+00
356	21	2025-10-02	10:00:00	2025-09-30 15:47:08.026707+00
358	21	2025-10-01	17:00:00	2025-09-30 15:47:07.99174+00
363	21	2025-10-02	11:00:00	2025-09-30 15:47:08.026707+00
366	21	2025-10-01	18:00:00	2025-09-30 15:47:07.99174+00
369	21	2025-10-02	17:00:00	2025-09-30 15:47:08.026707+00
370	21	2025-10-01	19:00:00	2025-09-30 15:47:07.99174+00
375	21	2025-10-02	18:00:00	2025-09-30 15:47:08.026707+00
377	21	2025-10-01	20:00:00	2025-09-30 15:47:07.99174+00
380	21	2025-10-02	19:00:00	2025-09-30 15:47:08.026707+00
382	21	2025-10-02	20:00:00	2025-09-30 15:47:08.026707+00
395	21	2025-10-03	10:00:00	2025-09-30 16:04:57.38118+00
396	21	2025-10-03	11:00:00	2025-09-30 16:04:57.38118+00
397	21	2025-10-03	17:00:00	2025-09-30 16:04:57.38118+00
398	21	2025-10-03	18:00:00	2025-09-30 16:04:57.38118+00
399	21	2025-10-03	19:00:00	2025-09-30 16:04:57.38118+00
462	21	2025-10-20	09:00:00	2025-10-18 05:14:30.685371+00
468	21	2025-10-20	10:00:00	2025-10-18 05:14:30.685371+00
474	21	2025-10-20	11:00:00	2025-10-18 05:14:30.685371+00
481	21	2025-10-20	12:00:00	2025-10-18 05:14:30.685371+00
429	4	2025-10-09	11:00:00	2025-10-04 18:12:19.243589+00
428	4	2025-10-04	11:00:00	2025-10-04 18:12:19.224749+00
431	4	2025-10-09	18:00:00	2025-10-04 18:12:19.243589+00
433	4	2025-10-04	18:00:00	2025-10-04 18:12:19.224749+00
436	4	2025-10-08	11:00:00	2025-10-04 18:12:19.292737+00
438	4	2025-10-08	18:00:00	2025-10-04 18:12:19.292737+00
485	21	2025-10-20	13:00:00	2025-10-18 05:14:30.685371+00
491	21	2025-10-20	18:00:00	2025-10-18 05:14:30.685371+00
495	21	2025-10-20	19:00:00	2025-10-18 05:14:30.685371+00
501	21	2025-10-24	09:00:00	2025-10-18 05:14:31.462455+00
502	21	2025-10-24	10:00:00	2025-10-18 05:14:31.462455+00
503	21	2025-10-24	11:00:00	2025-10-18 05:14:31.462455+00
504	21	2025-10-24	12:00:00	2025-10-18 05:14:31.462455+00
505	21	2025-10-24	13:00:00	2025-10-18 05:14:31.462455+00
506	21	2025-10-24	18:00:00	2025-10-18 05:14:31.462455+00
507	21	2025-10-24	19:00:00	2025-10-18 05:14:31.462455+00
524	22	2025-10-26	10:00:00	2025-10-24 08:22:59.0197+00
528	22	2025-10-27	10:00:00	2025-10-24 08:22:59.025115+00
530	22	2025-10-26	11:00:00	2025-10-24 08:22:59.0197+00
534	22	2025-10-27	11:00:00	2025-10-24 08:22:59.025115+00
536	22	2025-10-26	12:00:00	2025-10-24 08:22:59.0197+00
541	22	2025-10-27	12:00:00	2025-10-24 08:22:59.025115+00
542	22	2025-10-26	15:00:00	2025-10-24 08:22:59.0197+00
544	22	2025-10-26	17:00:00	2025-10-24 08:22:59.0197+00
547	22	2025-10-27	15:00:00	2025-10-24 08:22:59.025115+00
550	22	2025-10-26	18:00:00	2025-10-24 08:22:59.0197+00
553	22	2025-10-27	17:00:00	2025-10-24 08:22:59.025115+00
555	22	2025-10-26	19:00:00	2025-10-24 08:22:59.0197+00
559	22	2025-10-27	18:00:00	2025-10-24 08:22:59.025115+00
561	22	2025-10-26	21:00:00	2025-10-24 08:22:59.0197+00
565	22	2025-10-27	19:00:00	2025-10-24 08:22:59.025115+00
569	22	2025-10-27	21:00:00	2025-10-24 08:22:59.025115+00
580	21	2025-10-26	10:00:00	2025-10-25 09:29:46.760403+00
583	21	2025-10-27	10:00:00	2025-10-25 09:29:46.785502+00
586	21	2025-10-27	11:00:00	2025-10-25 09:29:46.785502+00
587	21	2025-10-26	11:00:00	2025-10-25 09:29:46.760403+00
591	21	2025-10-27	12:00:00	2025-10-25 09:29:46.785502+00
593	21	2025-10-26	12:00:00	2025-10-25 09:29:46.760403+00
597	21	2025-10-27	13:00:00	2025-10-25 09:29:46.785502+00
599	21	2025-10-26	13:00:00	2025-10-25 09:29:46.760403+00
603	21	2025-10-27	14:00:00	2025-10-25 09:29:46.785502+00
605	21	2025-10-26	14:00:00	2025-10-25 09:29:46.760403+00
610	21	2025-10-27	17:00:00	2025-10-25 09:29:46.785502+00
611	21	2025-10-26	17:00:00	2025-10-25 09:29:46.760403+00
616	21	2025-10-27	18:00:00	2025-10-25 09:29:46.785502+00
617	21	2025-10-26	18:00:00	2025-10-25 09:29:46.760403+00
620	21	2025-10-27	19:00:00	2025-10-25 09:29:46.785502+00
622	21	2025-10-26	19:00:00	2025-10-25 09:29:46.760403+00
625	21	2025-10-25	18:00:00	2025-10-25 09:29:46.803321+00
627	21	2025-10-27	20:00:00	2025-10-25 09:29:46.785502+00
628	21	2025-10-26	20:00:00	2025-10-25 09:29:46.760403+00
630	21	2025-10-25	19:00:00	2025-10-25 09:29:46.803321+00
632	21	2025-10-25	20:00:00	2025-10-25 09:29:46.803321+00
643	22	2025-10-25	10:00:00	2025-10-25 16:36:13.462308+00
644	22	2025-10-25	11:00:00	2025-10-25 16:36:13.462308+00
645	22	2025-10-25	12:00:00	2025-10-25 16:36:13.462308+00
646	22	2025-10-25	15:00:00	2025-10-25 16:36:13.462308+00
647	22	2025-10-25	17:00:00	2025-10-25 16:36:13.462308+00
648	22	2025-10-25	18:00:00	2025-10-25 16:36:13.462308+00
649	22	2025-10-25	19:00:00	2025-10-25 16:36:13.462308+00
650	22	2025-10-25	21:00:00	2025-10-25 16:36:13.462308+00
651	22	2025-10-25	22:00:00	2025-10-25 16:36:13.462308+00
661	22	2025-10-31	10:00:00	2025-10-31 09:40:34.372776+00
663	22	2025-11-04	10:00:00	2025-10-31 09:40:34.378217+00
665	22	2025-11-03	10:00:00	2025-10-31 09:40:34.38086+00
666	22	2025-10-31	11:00:00	2025-10-31 09:40:34.372776+00
668	22	2025-11-04	11:00:00	2025-10-31 09:40:34.378217+00
669	22	2025-11-03	11:00:00	2025-10-31 09:40:34.38086+00
672	22	2025-10-31	12:00:00	2025-10-31 09:40:34.372776+00
674	22	2025-11-04	12:00:00	2025-10-31 09:40:34.378217+00
675	22	2025-11-03	12:00:00	2025-10-31 09:40:34.38086+00
678	22	2025-10-31	14:00:00	2025-10-31 09:40:34.372776+00
680	22	2025-11-04	14:00:00	2025-10-31 09:40:34.378217+00
681	22	2025-11-03	14:00:00	2025-10-31 09:40:34.38086+00
684	22	2025-10-31	15:00:00	2025-10-31 09:40:34.372776+00
685	22	2025-11-04	15:00:00	2025-10-31 09:40:34.378217+00
687	22	2025-10-31	17:00:00	2025-10-31 09:40:34.372776+00
688	22	2025-11-03	15:00:00	2025-10-31 09:40:34.38086+00
692	22	2025-11-04	17:00:00	2025-10-31 09:40:34.378217+00
693	22	2025-10-31	18:00:00	2025-10-31 09:40:34.372776+00
694	22	2025-11-03	17:00:00	2025-10-31 09:40:34.38086+00
698	22	2025-11-04	18:00:00	2025-10-31 09:40:34.378217+00
699	22	2025-10-31	19:00:00	2025-10-31 09:40:34.372776+00
701	22	2025-11-03	18:00:00	2025-10-31 09:40:34.38086+00
703	22	2025-11-04	19:00:00	2025-10-31 09:40:34.378217+00
705	22	2025-10-31	21:00:00	2025-10-31 09:40:34.372776+00
706	22	2025-11-04	21:00:00	2025-10-31 09:40:34.378217+00
708	22	2025-11-03	19:00:00	2025-10-31 09:40:34.38086+00
711	22	2025-11-03	21:00:00	2025-10-31 09:40:34.38086+00
715	22	2025-11-06	10:00:00	2025-10-31 09:40:34.867876+00
716	22	2025-11-06	11:00:00	2025-10-31 09:40:34.867876+00
717	22	2025-11-06	12:00:00	2025-10-31 09:40:34.867876+00
718	22	2025-11-06	14:00:00	2025-10-31 09:40:34.867876+00
719	22	2025-11-06	15:00:00	2025-10-31 09:40:34.867876+00
720	22	2025-11-06	17:00:00	2025-10-31 09:40:34.867876+00
721	22	2025-11-06	18:00:00	2025-10-31 09:40:34.867876+00
722	22	2025-11-06	19:00:00	2025-10-31 09:40:34.867876+00
723	22	2025-11-06	21:00:00	2025-10-31 09:40:34.867876+00
813	21	2025-11-03	10:00:00	2025-10-31 18:02:20.076745+00
814	21	2025-11-01	10:00:00	2025-10-31 18:02:19.995137+00
815	21	2025-11-03	11:00:00	2025-10-31 18:02:20.076745+00
816	21	2025-11-02	10:00:00	2025-10-31 18:02:20.177655+00
817	21	2025-10-31	10:00:00	2025-10-31 18:02:20.178953+00
818	21	2025-11-04	10:00:00	2025-10-31 18:02:20.17906+00
819	21	2025-11-01	11:00:00	2025-10-31 18:02:19.995137+00
820	21	2025-11-03	12:00:00	2025-10-31 18:02:20.076745+00
821	21	2025-11-02	11:00:00	2025-10-31 18:02:20.177655+00
822	21	2025-10-31	11:00:00	2025-10-31 18:02:20.178953+00
823	21	2025-11-04	11:00:00	2025-10-31 18:02:20.17906+00
824	21	2025-11-01	12:00:00	2025-10-31 18:02:19.995137+00
825	21	2025-11-03	13:00:00	2025-10-31 18:02:20.076745+00
826	21	2025-11-02	12:00:00	2025-10-31 18:02:20.177655+00
827	21	2025-10-31	12:00:00	2025-10-31 18:02:20.178953+00
828	21	2025-11-04	12:00:00	2025-10-31 18:02:20.17906+00
829	21	2025-11-01	13:00:00	2025-10-31 18:02:19.995137+00
830	21	2025-11-05	10:00:00	2025-10-31 18:02:20.280166+00
831	21	2025-11-03	14:00:00	2025-10-31 18:02:20.076745+00
832	21	2025-11-02	13:00:00	2025-10-31 18:02:20.177655+00
833	21	2025-11-04	13:00:00	2025-10-31 18:02:20.17906+00
834	21	2025-11-03	16:00:00	2025-10-31 18:02:20.076745+00
835	21	2025-10-31	13:00:00	2025-10-31 18:02:20.178953+00
836	21	2025-11-01	14:00:00	2025-10-31 18:02:19.995137+00
837	21	2025-11-05	11:00:00	2025-10-31 18:02:20.280166+00
838	21	2025-11-02	14:00:00	2025-10-31 18:02:20.177655+00
839	21	2025-11-04	14:00:00	2025-10-31 18:02:20.17906+00
840	21	2025-11-03	17:00:00	2025-10-31 18:02:20.076745+00
841	21	2025-11-02	16:00:00	2025-10-31 18:02:20.177655+00
842	21	2025-10-31	14:00:00	2025-10-31 18:02:20.178953+00
843	21	2025-11-01	16:00:00	2025-10-31 18:02:19.995137+00
844	21	2025-11-05	12:00:00	2025-10-31 18:02:20.280166+00
845	21	2025-11-03	18:00:00	2025-10-31 18:02:20.076745+00
846	21	2025-11-04	16:00:00	2025-10-31 18:02:20.17906+00
847	21	2025-11-02	17:00:00	2025-10-31 18:02:20.177655+00
848	21	2025-10-31	16:00:00	2025-10-31 18:02:20.178953+00
849	21	2025-11-01	17:00:00	2025-10-31 18:02:19.995137+00
850	21	2025-11-05	13:00:00	2025-10-31 18:02:20.280166+00
851	21	2025-11-03	19:00:00	2025-10-31 18:02:20.076745+00
852	21	2025-11-04	17:00:00	2025-10-31 18:02:20.17906+00
853	21	2025-11-02	18:00:00	2025-10-31 18:02:20.177655+00
854	21	2025-11-01	18:00:00	2025-10-31 18:02:19.995137+00
855	21	2025-11-05	14:00:00	2025-10-31 18:02:20.280166+00
856	21	2025-10-31	17:00:00	2025-10-31 18:02:20.178953+00
857	21	2025-11-04	18:00:00	2025-10-31 18:02:20.17906+00
858	21	2025-11-03	20:00:00	2025-10-31 18:02:20.076745+00
859	21	2025-11-02	19:00:00	2025-10-31 18:02:20.177655+00
860	21	2025-11-01	19:00:00	2025-10-31 18:02:19.995137+00
861	21	2025-11-05	16:00:00	2025-10-31 18:02:20.280166+00
862	21	2025-10-31	18:00:00	2025-10-31 18:02:20.178953+00
863	21	2025-11-04	19:00:00	2025-10-31 18:02:20.17906+00
864	21	2025-11-03	21:00:00	2025-10-31 18:02:20.076745+00
865	21	2025-11-02	20:00:00	2025-10-31 18:02:20.177655+00
866	21	2025-10-31	19:00:00	2025-10-31 18:02:20.178953+00
867	21	2025-11-04	20:00:00	2025-10-31 18:02:20.17906+00
868	21	2025-11-01	20:00:00	2025-10-31 18:02:19.995137+00
869	21	2025-11-05	17:00:00	2025-10-31 18:02:20.280166+00
870	21	2025-11-02	21:00:00	2025-10-31 18:02:20.177655+00
871	21	2025-10-31	20:00:00	2025-10-31 18:02:20.178953+00
872	21	2025-11-04	21:00:00	2025-10-31 18:02:20.17906+00
873	21	2025-11-01	21:00:00	2025-10-31 18:02:19.995137+00
874	21	2025-11-05	18:00:00	2025-10-31 18:02:20.280166+00
875	21	2025-10-31	21:00:00	2025-10-31 18:02:20.178953+00
876	21	2025-11-05	19:00:00	2025-10-31 18:02:20.280166+00
877	21	2025-11-05	20:00:00	2025-10-31 18:02:20.280166+00
878	21	2025-11-05	21:00:00	2025-10-31 18:02:20.280166+00
879	21	2025-11-06	10:00:00	2025-10-31 18:02:21.137525+00
880	21	2025-11-06	11:00:00	2025-10-31 18:02:21.137525+00
881	21	2025-11-06	12:00:00	2025-10-31 18:02:21.137525+00
882	21	2025-11-06	13:00:00	2025-10-31 18:02:21.137525+00
883	21	2025-11-06	14:00:00	2025-10-31 18:02:21.137525+00
884	21	2025-11-06	16:00:00	2025-10-31 18:02:21.137525+00
885	21	2025-11-06	17:00:00	2025-10-31 18:02:21.137525+00
886	21	2025-11-06	18:00:00	2025-10-31 18:02:21.137525+00
887	21	2025-11-06	19:00:00	2025-10-31 18:02:21.137525+00
888	21	2025-11-06	20:00:00	2025-10-31 18:02:21.137525+00
889	21	2025-11-06	21:00:00	2025-10-31 18:02:21.137525+00
\.


--
-- Data for Name: worker_keys; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.worker_keys (id, access_key, is_used, used_by_worker_id, created_at, status, deleted_at) FROM stdin;
1	F1N6MJ	t	18	2025-08-21 17:13:21.31666+00	active	\N
2	A2B3C4	t	21	2025-08-21 17:13:21.31666+00	active	\N
7	Q9TPL6	t	22	2025-10-11 18:27:26.206986+00	active	\N
8	V413CL	f	\N	2025-10-11 19:33:24.330107+00	active	\N
\.


--
-- Data for Name: worker_offers; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.worker_offers (id, worker_id, coupon_code, discount_type, discount_value, min_booking_amount, valid_from, valid_until, max_uses, uses_count, is_active, created_at) FROM stdin;
5	21	FLAT25	fixed	25.00	0.00	\N	\N	\N	4	t	2025-10-26 12:29:00.222755+00
7	21	IND60	percentage	60.00	0.00	\N	2025-11-04 00:00:00+00	\N	3	t	2025-10-31 19:22:57.252947+00
4	22	FLAT20	fixed	20.00	0.00	\N	2025-10-28 23:59:00+00	5	5	t	2025-10-24 08:23:57.717424+00
3	21	FLAT35	fixed	35.00	0.00	\N	2025-10-25 23:59:00+00	\N	9	t	2025-10-19 11:07:19.561536+00
1	21	FLAT50	fixed	50.00	0.00	\N	2025-10-20 23:59:00+00	\N	2	t	2025-10-18 07:59:23.534494+00
6	22	FLAT30	fixed	30.00	0.00	\N	\N	\N	4	t	2025-10-26 19:21:20.241447+00
\.


--
-- Data for Name: worker_profiles; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.worker_profiles (user_id, bio, experience_years, hourly_rate, is_verified) FROM stdin;
3	Hello! I'm Virat, and I love making vehicles sparkle. For me, cleaning a car or bike is about restoring its beauty and making it look its absolute best. I use the best techniques to 'cover drive' away dirt and grime from every nook and cranny. You can trust me to be reliable, professional, and passionate about giving your ride the care it deserves.	7	200.00	f
5	I am Washing Machine Repairer and a cleaner, my shop name is "Ashu Washing Machine Services" in Varachha, Surat , we provide all types of services related to the washing machine. All technical and manufacturing errors can be solved.	4	350.00	f
11	I am Rohan Desai, i own a garage named "Rohan Bike and Car Garage" , we provide all kind of services related to bike and car. Let it be oil, horn, engine, spare parts- we take care of everything.	2	600.00	f
14	My Name is Jay, I lived in surat, and I provide service of bike and car cleaning!	5	250.00	f
18	My Name is Rupesh	4	200.00	f
4	I was working as an employee at Llyod AC where i was working as a AC repairer and has been expertise in technological aspects of all types of ACs. So, i have 3+ years of experience and looking forward to serve you.	8	600.00	f
22	I provide a Laundry Service Provider. \r\nI have a Drier and a Washing Machine to wash clothes and shoes with modern technology.	5	250.00	f
21	I provide Home Cleaning and Vehicle related services	6	300.00	f
\.


--
-- Data for Name: worker_services; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.worker_services (user_id, sub_service_id) FROM stdin;
22	21
22	22
21	1
21	12
21	21
21	5
21	7
4	10
4	9
\.


--
-- Data for Name: worker_sub_service_items; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.worker_sub_service_items (user_id, sub_service_item_id, price) FROM stdin;
21	3	300.00
21	4	300.00
21	6	400.00
21	5	400.00
21	2	800.00
21	1	400.00
22	6	200.00
22	5	150.00
22	8	150.00
22	7	350.00
\.


--
-- Data for Name: schema_migrations; Type: TABLE DATA; Schema: realtime; Owner: supabase_admin
--

COPY realtime.schema_migrations (version, inserted_at) FROM stdin;
20211116024918	2025-08-09 08:23:12
20211116045059	2025-08-09 08:23:13
20211116050929	2025-08-09 08:23:14
20211116051442	2025-08-09 08:23:15
20211116212300	2025-08-09 08:23:16
20211116213355	2025-08-09 08:23:16
20211116213934	2025-08-09 08:23:17
20211116214523	2025-08-09 08:23:18
20211122062447	2025-08-09 08:23:19
20211124070109	2025-08-09 08:23:19
20211202204204	2025-08-09 08:23:20
20211202204605	2025-08-09 08:23:21
20211210212804	2025-08-09 08:23:23
20211228014915	2025-08-09 08:23:24
20220107221237	2025-08-09 08:23:24
20220228202821	2025-08-09 08:23:25
20220312004840	2025-08-09 08:23:26
20220603231003	2025-08-09 08:23:27
20220603232444	2025-08-09 08:23:27
20220615214548	2025-08-09 08:23:28
20220712093339	2025-08-09 08:23:29
20220908172859	2025-08-09 08:23:29
20220916233421	2025-08-09 08:23:30
20230119133233	2025-08-09 08:23:31
20230128025114	2025-08-09 08:23:32
20230128025212	2025-08-09 08:23:32
20230227211149	2025-08-09 08:23:33
20230228184745	2025-08-09 08:23:34
20230308225145	2025-08-09 08:23:34
20230328144023	2025-08-09 08:23:35
20231018144023	2025-08-09 08:23:36
20231204144023	2025-08-09 08:23:37
20231204144024	2025-08-09 08:23:38
20231204144025	2025-08-09 08:23:38
20240108234812	2025-08-09 08:23:39
20240109165339	2025-08-09 08:23:40
20240227174441	2025-08-09 08:23:41
20240311171622	2025-08-09 08:23:42
20240321100241	2025-08-09 08:23:43
20240401105812	2025-08-09 08:23:45
20240418121054	2025-08-09 08:23:46
20240523004032	2025-08-09 08:23:48
20240618124746	2025-08-09 08:23:49
20240801235015	2025-08-09 08:23:50
20240805133720	2025-08-09 08:23:50
20240827160934	2025-08-09 08:23:51
20240919163303	2025-08-09 08:23:52
20240919163305	2025-08-09 08:23:53
20241019105805	2025-08-09 08:23:53
20241030150047	2025-08-09 08:23:56
20241108114728	2025-08-09 08:23:57
20241121104152	2025-08-09 08:23:57
20241130184212	2025-08-09 08:23:58
20241220035512	2025-08-09 08:23:59
20241220123912	2025-08-09 08:23:59
20241224161212	2025-08-09 08:24:00
20250107150512	2025-08-09 08:24:01
20250110162412	2025-08-09 08:24:01
20250123174212	2025-08-09 08:24:02
20250128220012	2025-08-09 08:24:03
20250506224012	2025-08-09 08:24:03
20250523164012	2025-08-09 08:24:04
20250714121412	2025-08-09 08:24:04
20250905041441	2025-09-28 07:25:21
20251103001201	2025-11-13 10:20:27
\.


--
-- Data for Name: subscription; Type: TABLE DATA; Schema: realtime; Owner: supabase_admin
--

COPY realtime.subscription (id, subscription_id, entity, filters, claims, created_at) FROM stdin;
\.


--
-- Data for Name: buckets; Type: TABLE DATA; Schema: storage; Owner: supabase_storage_admin
--

COPY storage.buckets (id, name, owner, created_at, updated_at, public, avif_autodetection, file_size_limit, allowed_mime_types, owner_id, type) FROM stdin;
\.


--
-- Data for Name: buckets_analytics; Type: TABLE DATA; Schema: storage; Owner: supabase_storage_admin
--

COPY storage.buckets_analytics (id, type, format, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: migrations; Type: TABLE DATA; Schema: storage; Owner: supabase_storage_admin
--

COPY storage.migrations (id, name, hash, executed_at) FROM stdin;
0	create-migrations-table	e18db593bcde2aca2a408c4d1100f6abba2195df	2025-08-09 08:23:11.761194
1	initialmigration	6ab16121fbaa08bbd11b712d05f358f9b555d777	2025-08-09 08:23:11.790727
2	storage-schema	5c7968fd083fcea04050c1b7f6253c9771b99011	2025-08-09 08:23:11.802845
3	pathtoken-column	2cb1b0004b817b29d5b0a971af16bafeede4b70d	2025-08-09 08:23:12.07756
4	add-migrations-rls	427c5b63fe1c5937495d9c635c263ee7a5905058	2025-08-09 08:23:12.798344
5	add-size-functions	79e081a1455b63666c1294a440f8ad4b1e6a7f84	2025-08-09 08:23:12.898899
6	change-column-name-in-get-size	f93f62afdf6613ee5e7e815b30d02dc990201044	2025-08-09 08:23:12.904974
7	add-rls-to-buckets	e7e7f86adbc51049f341dfe8d30256c1abca17aa	2025-08-09 08:23:12.910787
8	add-public-to-buckets	fd670db39ed65f9d08b01db09d6202503ca2bab3	2025-08-09 08:23:12.959778
9	fix-search-function	3a0af29f42e35a4d101c259ed955b67e1bee6825	2025-08-09 08:23:12.965331
10	search-files-search-function	68dc14822daad0ffac3746a502234f486182ef6e	2025-08-09 08:23:12.979661
11	add-trigger-to-auto-update-updated_at-column	7425bdb14366d1739fa8a18c83100636d74dcaa2	2025-08-09 08:23:12.985614
12	add-automatic-avif-detection-flag	8e92e1266eb29518b6a4c5313ab8f29dd0d08df9	2025-08-09 08:23:13.041409
13	add-bucket-custom-limits	cce962054138135cd9a8c4bcd531598684b25e7d	2025-08-09 08:23:13.046752
14	use-bytes-for-max-size	941c41b346f9802b411f06f30e972ad4744dad27	2025-08-09 08:23:13.052236
15	add-can-insert-object-function	934146bc38ead475f4ef4b555c524ee5d66799e5	2025-08-09 08:23:13.255381
16	add-version	76debf38d3fd07dcfc747ca49096457d95b1221b	2025-08-09 08:23:13.272768
17	drop-owner-foreign-key	f1cbb288f1b7a4c1eb8c38504b80ae2a0153d101	2025-08-09 08:23:13.277858
18	add_owner_id_column_deprecate_owner	e7a511b379110b08e2f214be852c35414749fe66	2025-08-09 08:23:13.283445
19	alter-default-value-objects-id	02e5e22a78626187e00d173dc45f58fa66a4f043	2025-08-09 08:23:13.298331
20	list-objects-with-delimiter	cd694ae708e51ba82bf012bba00caf4f3b6393b7	2025-08-09 08:23:13.305338
21	s3-multipart-uploads	8c804d4a566c40cd1e4cc5b3725a664a9303657f	2025-08-09 08:23:13.312743
22	s3-multipart-uploads-big-ints	9737dc258d2397953c9953d9b86920b8be0cdb73	2025-08-09 08:23:13.37111
23	optimize-search-function	9d7e604cddc4b56a5422dc68c9313f4a1b6f132c	2025-08-09 08:23:13.401899
24	operation-function	8312e37c2bf9e76bbe841aa5fda889206d2bf8aa	2025-08-09 08:23:13.407521
25	custom-metadata	d974c6057c3db1c1f847afa0e291e6165693b990	2025-08-09 08:23:13.412779
26	objects-prefixes	ef3f7871121cdc47a65308e6702519e853422ae2	2025-10-02 12:44:15.581428
27	search-v2	33b8f2a7ae53105f028e13e9fcda9dc4f356b4a2	2025-10-02 12:44:15.668845
28	object-bucket-name-sorting	ba85ec41b62c6a30a3f136788227ee47f311c436	2025-10-02 12:44:15.675039
29	create-prefixes	a7b1a22c0dc3ab630e3055bfec7ce7d2045c5b7b	2025-10-02 12:44:15.684737
30	update-object-levels	6c6f6cc9430d570f26284a24cf7b210599032db7	2025-10-02 12:44:15.688664
31	objects-level-index	33f1fef7ec7fea08bb892222f4f0f5d79bab5eb8	2025-10-02 12:44:15.692282
32	backward-compatible-index-on-objects	2d51eeb437a96868b36fcdfb1ddefdf13bef1647	2025-10-02 12:44:15.696079
33	backward-compatible-index-on-prefixes	fe473390e1b8c407434c0e470655945b110507bf	2025-10-02 12:44:15.699766
34	optimize-search-function-v1	82b0e469a00e8ebce495e29bfa70a0797f7ebd2c	2025-10-02 12:44:15.700777
35	add-insert-trigger-prefixes	63bb9fd05deb3dc5e9fa66c83e82b152f0caf589	2025-10-02 12:44:15.704847
36	optimise-existing-functions	81cf92eb0c36612865a18016a38496c530443899	2025-10-02 12:44:15.707469
37	add-bucket-name-length-trigger	3944135b4e3e8b22d6d4cbb568fe3b0b51df15c1	2025-10-02 12:44:15.717166
38	iceberg-catalog-flag-on-buckets	19a8bd89d5dfa69af7f222a46c726b7c41e462c5	2025-10-02 12:44:15.720464
39	add-search-v2-sort-support	39cf7d1e6bf515f4b02e41237aba845a7b492853	2025-10-02 12:44:15.735108
40	fix-prefix-race-conditions-optimized	fd02297e1c67df25a9fc110bf8c8a9af7fb06d1f	2025-10-02 12:44:15.73918
41	add-object-level-update-trigger	44c22478bf01744b2129efc480cd2edc9a7d60e9	2025-10-02 12:44:15.746802
42	rollback-prefix-triggers	f2ab4f526ab7f979541082992593938c05ee4b47	2025-10-02 12:44:15.750833
43	fix-object-level	ab837ad8f1c7d00cc0b7310e989a23388ff29fc6	2025-10-02 12:44:15.757465
\.


--
-- Data for Name: objects; Type: TABLE DATA; Schema: storage; Owner: supabase_storage_admin
--

COPY storage.objects (id, bucket_id, name, owner, created_at, updated_at, last_accessed_at, metadata, version, owner_id, user_metadata, level) FROM stdin;
\.


--
-- Data for Name: prefixes; Type: TABLE DATA; Schema: storage; Owner: supabase_storage_admin
--

COPY storage.prefixes (bucket_id, name, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: s3_multipart_uploads; Type: TABLE DATA; Schema: storage; Owner: supabase_storage_admin
--

COPY storage.s3_multipart_uploads (id, in_progress_size, upload_signature, bucket_id, key, version, owner_id, created_at, user_metadata) FROM stdin;
\.


--
-- Data for Name: s3_multipart_uploads_parts; Type: TABLE DATA; Schema: storage; Owner: supabase_storage_admin
--

COPY storage.s3_multipart_uploads_parts (id, upload_id, size, part_number, bucket_id, key, etag, owner_id, version, created_at) FROM stdin;
\.


--
-- Data for Name: secrets; Type: TABLE DATA; Schema: vault; Owner: supabase_admin
--

COPY vault.secrets (id, name, description, secret, key_id, nonce, created_at, updated_at) FROM stdin;
\.


--
-- Name: refresh_tokens_id_seq; Type: SEQUENCE SET; Schema: auth; Owner: supabase_auth_admin
--

SELECT pg_catalog.setval('auth.refresh_tokens_id_seq', 1, false);


--
-- Name: bookings_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.bookings_id_seq', 97, true);


--
-- Name: notifications_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.notifications_id_seq', 98, true);


--
-- Name: payouts_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.payouts_id_seq', 5, true);


--
-- Name: platform_payouts_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.platform_payouts_id_seq', 3, true);


--
-- Name: reviews_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.reviews_id_seq', 53, true);


--
-- Name: service_combos_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.service_combos_id_seq', 1, false);


--
-- Name: services_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.services_id_seq', 11, true);


--
-- Name: sub_service_items_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.sub_service_items_id_seq', 8, true);


--
-- Name: sub_services_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.sub_services_id_seq', 22, true);


--
-- Name: transactions_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.transactions_id_seq', 62, true);


--
-- Name: user_coupon_usage_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.user_coupon_usage_id_seq', 44, true);


--
-- Name: users_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.users_id_seq', 24, true);


--
-- Name: wallets_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.wallets_id_seq', 2, true);


--
-- Name: worker_availability_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.worker_availability_id_seq', 889, true);


--
-- Name: worker_keys_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.worker_keys_id_seq', 8, true);


--
-- Name: worker_offers_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.worker_offers_id_seq', 7, true);


--
-- Name: subscription_id_seq; Type: SEQUENCE SET; Schema: realtime; Owner: supabase_admin
--

SELECT pg_catalog.setval('realtime.subscription_id_seq', 1, false);


--
-- Name: mfa_amr_claims amr_id_pk; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.mfa_amr_claims
    ADD CONSTRAINT amr_id_pk PRIMARY KEY (id);


--
-- Name: audit_log_entries audit_log_entries_pkey; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.audit_log_entries
    ADD CONSTRAINT audit_log_entries_pkey PRIMARY KEY (id);


--
-- Name: flow_state flow_state_pkey; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.flow_state
    ADD CONSTRAINT flow_state_pkey PRIMARY KEY (id);


--
-- Name: identities identities_pkey; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.identities
    ADD CONSTRAINT identities_pkey PRIMARY KEY (id);


--
-- Name: identities identities_provider_id_provider_unique; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.identities
    ADD CONSTRAINT identities_provider_id_provider_unique UNIQUE (provider_id, provider);


--
-- Name: instances instances_pkey; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.instances
    ADD CONSTRAINT instances_pkey PRIMARY KEY (id);


--
-- Name: mfa_amr_claims mfa_amr_claims_session_id_authentication_method_pkey; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.mfa_amr_claims
    ADD CONSTRAINT mfa_amr_claims_session_id_authentication_method_pkey UNIQUE (session_id, authentication_method);


--
-- Name: mfa_challenges mfa_challenges_pkey; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.mfa_challenges
    ADD CONSTRAINT mfa_challenges_pkey PRIMARY KEY (id);


--
-- Name: mfa_factors mfa_factors_last_challenged_at_key; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.mfa_factors
    ADD CONSTRAINT mfa_factors_last_challenged_at_key UNIQUE (last_challenged_at);


--
-- Name: mfa_factors mfa_factors_pkey; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.mfa_factors
    ADD CONSTRAINT mfa_factors_pkey PRIMARY KEY (id);


--
-- Name: oauth_authorizations oauth_authorizations_authorization_code_key; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.oauth_authorizations
    ADD CONSTRAINT oauth_authorizations_authorization_code_key UNIQUE (authorization_code);


--
-- Name: oauth_authorizations oauth_authorizations_authorization_id_key; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.oauth_authorizations
    ADD CONSTRAINT oauth_authorizations_authorization_id_key UNIQUE (authorization_id);


--
-- Name: oauth_authorizations oauth_authorizations_pkey; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.oauth_authorizations
    ADD CONSTRAINT oauth_authorizations_pkey PRIMARY KEY (id);


--
-- Name: oauth_clients oauth_clients_pkey; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.oauth_clients
    ADD CONSTRAINT oauth_clients_pkey PRIMARY KEY (id);


--
-- Name: oauth_consents oauth_consents_pkey; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.oauth_consents
    ADD CONSTRAINT oauth_consents_pkey PRIMARY KEY (id);


--
-- Name: oauth_consents oauth_consents_user_client_unique; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.oauth_consents
    ADD CONSTRAINT oauth_consents_user_client_unique UNIQUE (user_id, client_id);


--
-- Name: one_time_tokens one_time_tokens_pkey; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.one_time_tokens
    ADD CONSTRAINT one_time_tokens_pkey PRIMARY KEY (id);


--
-- Name: refresh_tokens refresh_tokens_pkey; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.refresh_tokens
    ADD CONSTRAINT refresh_tokens_pkey PRIMARY KEY (id);


--
-- Name: refresh_tokens refresh_tokens_token_unique; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.refresh_tokens
    ADD CONSTRAINT refresh_tokens_token_unique UNIQUE (token);


--
-- Name: saml_providers saml_providers_entity_id_key; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.saml_providers
    ADD CONSTRAINT saml_providers_entity_id_key UNIQUE (entity_id);


--
-- Name: saml_providers saml_providers_pkey; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.saml_providers
    ADD CONSTRAINT saml_providers_pkey PRIMARY KEY (id);


--
-- Name: saml_relay_states saml_relay_states_pkey; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.saml_relay_states
    ADD CONSTRAINT saml_relay_states_pkey PRIMARY KEY (id);


--
-- Name: schema_migrations schema_migrations_pkey; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.schema_migrations
    ADD CONSTRAINT schema_migrations_pkey PRIMARY KEY (version);


--
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- Name: sso_domains sso_domains_pkey; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.sso_domains
    ADD CONSTRAINT sso_domains_pkey PRIMARY KEY (id);


--
-- Name: sso_providers sso_providers_pkey; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.sso_providers
    ADD CONSTRAINT sso_providers_pkey PRIMARY KEY (id);


--
-- Name: users users_phone_key; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.users
    ADD CONSTRAINT users_phone_key UNIQUE (phone);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: bookings bookings_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.bookings
    ADD CONSTRAINT bookings_pkey PRIMARY KEY (id);


--
-- Name: combo_items combo_items_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.combo_items
    ADD CONSTRAINT combo_items_pkey PRIMARY KEY (combo_id, sub_service_item_id);


--
-- Name: notifications notifications_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.notifications
    ADD CONSTRAINT notifications_pkey PRIMARY KEY (id);


--
-- Name: payouts payouts_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.payouts
    ADD CONSTRAINT payouts_pkey PRIMARY KEY (id);


--
-- Name: platform_payouts platform_payouts_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.platform_payouts
    ADD CONSTRAINT platform_payouts_pkey PRIMARY KEY (id);


--
-- Name: reviews reviews_booking_id_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.reviews
    ADD CONSTRAINT reviews_booking_id_key UNIQUE (booking_id);


--
-- Name: reviews reviews_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.reviews
    ADD CONSTRAINT reviews_pkey PRIMARY KEY (id);


--
-- Name: service_combos service_combos_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.service_combos
    ADD CONSTRAINT service_combos_pkey PRIMARY KEY (id);


--
-- Name: services services_name_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.services
    ADD CONSTRAINT services_name_key UNIQUE (name);


--
-- Name: services services_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.services
    ADD CONSTRAINT services_pkey PRIMARY KEY (id);


--
-- Name: services services_slug_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.services
    ADD CONSTRAINT services_slug_key UNIQUE (slug);


--
-- Name: sub_service_items sub_service_items_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sub_service_items
    ADD CONSTRAINT sub_service_items_pkey PRIMARY KEY (id);


--
-- Name: sub_services sub_services_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sub_services
    ADD CONSTRAINT sub_services_pkey PRIMARY KEY (id);


--
-- Name: sub_services sub_services_service_id_name_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sub_services
    ADD CONSTRAINT sub_services_service_id_name_key UNIQUE (service_id, name);


--
-- Name: sub_services sub_services_slug_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sub_services
    ADD CONSTRAINT sub_services_slug_key UNIQUE (slug);


--
-- Name: transactions transactions_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.transactions
    ADD CONSTRAINT transactions_pkey PRIMARY KEY (id);


--
-- Name: worker_offers unique_worker_coupon_code; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.worker_offers
    ADD CONSTRAINT unique_worker_coupon_code UNIQUE (worker_id, coupon_code);


--
-- Name: user_coupon_usage user_coupon_usage_booking_id_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.user_coupon_usage
    ADD CONSTRAINT user_coupon_usage_booking_id_key UNIQUE (booking_id);


--
-- Name: user_coupon_usage user_coupon_usage_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.user_coupon_usage
    ADD CONSTRAINT user_coupon_usage_pkey PRIMARY KEY (id);


--
-- Name: user_coupon_usage user_coupon_usage_user_id_offer_id_sub_service_item_id_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.user_coupon_usage
    ADD CONSTRAINT user_coupon_usage_user_id_offer_id_sub_service_item_id_key UNIQUE (user_id, offer_id, sub_service_item_id);


--
-- Name: users users_auth_user_id_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_auth_user_id_key UNIQUE (auth_user_id);


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
-- Name: wallets wallets_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.wallets
    ADD CONSTRAINT wallets_pkey PRIMARY KEY (id);


--
-- Name: worker_availability worker_availability_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.worker_availability
    ADD CONSTRAINT worker_availability_pkey PRIMARY KEY (id);


--
-- Name: worker_availability worker_availability_user_id_date_time_slot_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.worker_availability
    ADD CONSTRAINT worker_availability_user_id_date_time_slot_key UNIQUE (user_id, date, time_slot);


--
-- Name: worker_keys worker_keys_access_key_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.worker_keys
    ADD CONSTRAINT worker_keys_access_key_key UNIQUE (access_key);


--
-- Name: worker_keys worker_keys_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.worker_keys
    ADD CONSTRAINT worker_keys_pkey PRIMARY KEY (id);


--
-- Name: worker_offers worker_offers_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.worker_offers
    ADD CONSTRAINT worker_offers_pkey PRIMARY KEY (id);


--
-- Name: worker_profiles worker_profiles_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.worker_profiles
    ADD CONSTRAINT worker_profiles_pkey PRIMARY KEY (user_id);


--
-- Name: worker_services worker_services_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.worker_services
    ADD CONSTRAINT worker_services_pkey PRIMARY KEY (user_id, sub_service_id);


--
-- Name: worker_sub_service_items worker_sub_service_items_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.worker_sub_service_items
    ADD CONSTRAINT worker_sub_service_items_pkey PRIMARY KEY (user_id, sub_service_item_id);


--
-- Name: messages messages_pkey; Type: CONSTRAINT; Schema: realtime; Owner: supabase_realtime_admin
--

ALTER TABLE ONLY realtime.messages
    ADD CONSTRAINT messages_pkey PRIMARY KEY (id, inserted_at);


--
-- Name: subscription pk_subscription; Type: CONSTRAINT; Schema: realtime; Owner: supabase_admin
--

ALTER TABLE ONLY realtime.subscription
    ADD CONSTRAINT pk_subscription PRIMARY KEY (id);


--
-- Name: schema_migrations schema_migrations_pkey; Type: CONSTRAINT; Schema: realtime; Owner: supabase_admin
--

ALTER TABLE ONLY realtime.schema_migrations
    ADD CONSTRAINT schema_migrations_pkey PRIMARY KEY (version);


--
-- Name: buckets_analytics buckets_analytics_pkey; Type: CONSTRAINT; Schema: storage; Owner: supabase_storage_admin
--

ALTER TABLE ONLY storage.buckets_analytics
    ADD CONSTRAINT buckets_analytics_pkey PRIMARY KEY (id);


--
-- Name: buckets buckets_pkey; Type: CONSTRAINT; Schema: storage; Owner: supabase_storage_admin
--

ALTER TABLE ONLY storage.buckets
    ADD CONSTRAINT buckets_pkey PRIMARY KEY (id);


--
-- Name: migrations migrations_name_key; Type: CONSTRAINT; Schema: storage; Owner: supabase_storage_admin
--

ALTER TABLE ONLY storage.migrations
    ADD CONSTRAINT migrations_name_key UNIQUE (name);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: storage; Owner: supabase_storage_admin
--

ALTER TABLE ONLY storage.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- Name: objects objects_pkey; Type: CONSTRAINT; Schema: storage; Owner: supabase_storage_admin
--

ALTER TABLE ONLY storage.objects
    ADD CONSTRAINT objects_pkey PRIMARY KEY (id);


--
-- Name: prefixes prefixes_pkey; Type: CONSTRAINT; Schema: storage; Owner: supabase_storage_admin
--

ALTER TABLE ONLY storage.prefixes
    ADD CONSTRAINT prefixes_pkey PRIMARY KEY (bucket_id, level, name);


--
-- Name: s3_multipart_uploads_parts s3_multipart_uploads_parts_pkey; Type: CONSTRAINT; Schema: storage; Owner: supabase_storage_admin
--

ALTER TABLE ONLY storage.s3_multipart_uploads_parts
    ADD CONSTRAINT s3_multipart_uploads_parts_pkey PRIMARY KEY (id);


--
-- Name: s3_multipart_uploads s3_multipart_uploads_pkey; Type: CONSTRAINT; Schema: storage; Owner: supabase_storage_admin
--

ALTER TABLE ONLY storage.s3_multipart_uploads
    ADD CONSTRAINT s3_multipart_uploads_pkey PRIMARY KEY (id);


--
-- Name: audit_logs_instance_id_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX audit_logs_instance_id_idx ON auth.audit_log_entries USING btree (instance_id);


--
-- Name: confirmation_token_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE UNIQUE INDEX confirmation_token_idx ON auth.users USING btree (confirmation_token) WHERE ((confirmation_token)::text !~ '^[0-9 ]*$'::text);


--
-- Name: email_change_token_current_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE UNIQUE INDEX email_change_token_current_idx ON auth.users USING btree (email_change_token_current) WHERE ((email_change_token_current)::text !~ '^[0-9 ]*$'::text);


--
-- Name: email_change_token_new_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE UNIQUE INDEX email_change_token_new_idx ON auth.users USING btree (email_change_token_new) WHERE ((email_change_token_new)::text !~ '^[0-9 ]*$'::text);


--
-- Name: factor_id_created_at_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX factor_id_created_at_idx ON auth.mfa_factors USING btree (user_id, created_at);


--
-- Name: flow_state_created_at_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX flow_state_created_at_idx ON auth.flow_state USING btree (created_at DESC);


--
-- Name: identities_email_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX identities_email_idx ON auth.identities USING btree (email text_pattern_ops);


--
-- Name: INDEX identities_email_idx; Type: COMMENT; Schema: auth; Owner: supabase_auth_admin
--

COMMENT ON INDEX auth.identities_email_idx IS 'Auth: Ensures indexed queries on the email column';


--
-- Name: identities_user_id_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX identities_user_id_idx ON auth.identities USING btree (user_id);


--
-- Name: idx_auth_code; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX idx_auth_code ON auth.flow_state USING btree (auth_code);


--
-- Name: idx_user_id_auth_method; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX idx_user_id_auth_method ON auth.flow_state USING btree (user_id, authentication_method);


--
-- Name: mfa_challenge_created_at_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX mfa_challenge_created_at_idx ON auth.mfa_challenges USING btree (created_at DESC);


--
-- Name: mfa_factors_user_friendly_name_unique; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE UNIQUE INDEX mfa_factors_user_friendly_name_unique ON auth.mfa_factors USING btree (friendly_name, user_id) WHERE (TRIM(BOTH FROM friendly_name) <> ''::text);


--
-- Name: mfa_factors_user_id_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX mfa_factors_user_id_idx ON auth.mfa_factors USING btree (user_id);


--
-- Name: oauth_auth_pending_exp_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX oauth_auth_pending_exp_idx ON auth.oauth_authorizations USING btree (expires_at) WHERE (status = 'pending'::auth.oauth_authorization_status);


--
-- Name: oauth_clients_deleted_at_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX oauth_clients_deleted_at_idx ON auth.oauth_clients USING btree (deleted_at);


--
-- Name: oauth_consents_active_client_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX oauth_consents_active_client_idx ON auth.oauth_consents USING btree (client_id) WHERE (revoked_at IS NULL);


--
-- Name: oauth_consents_active_user_client_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX oauth_consents_active_user_client_idx ON auth.oauth_consents USING btree (user_id, client_id) WHERE (revoked_at IS NULL);


--
-- Name: oauth_consents_user_order_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX oauth_consents_user_order_idx ON auth.oauth_consents USING btree (user_id, granted_at DESC);


--
-- Name: one_time_tokens_relates_to_hash_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX one_time_tokens_relates_to_hash_idx ON auth.one_time_tokens USING hash (relates_to);


--
-- Name: one_time_tokens_token_hash_hash_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX one_time_tokens_token_hash_hash_idx ON auth.one_time_tokens USING hash (token_hash);


--
-- Name: one_time_tokens_user_id_token_type_key; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE UNIQUE INDEX one_time_tokens_user_id_token_type_key ON auth.one_time_tokens USING btree (user_id, token_type);


--
-- Name: reauthentication_token_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE UNIQUE INDEX reauthentication_token_idx ON auth.users USING btree (reauthentication_token) WHERE ((reauthentication_token)::text !~ '^[0-9 ]*$'::text);


--
-- Name: recovery_token_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE UNIQUE INDEX recovery_token_idx ON auth.users USING btree (recovery_token) WHERE ((recovery_token)::text !~ '^[0-9 ]*$'::text);


--
-- Name: refresh_tokens_instance_id_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX refresh_tokens_instance_id_idx ON auth.refresh_tokens USING btree (instance_id);


--
-- Name: refresh_tokens_instance_id_user_id_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX refresh_tokens_instance_id_user_id_idx ON auth.refresh_tokens USING btree (instance_id, user_id);


--
-- Name: refresh_tokens_parent_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX refresh_tokens_parent_idx ON auth.refresh_tokens USING btree (parent);


--
-- Name: refresh_tokens_session_id_revoked_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX refresh_tokens_session_id_revoked_idx ON auth.refresh_tokens USING btree (session_id, revoked);


--
-- Name: refresh_tokens_updated_at_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX refresh_tokens_updated_at_idx ON auth.refresh_tokens USING btree (updated_at DESC);


--
-- Name: saml_providers_sso_provider_id_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX saml_providers_sso_provider_id_idx ON auth.saml_providers USING btree (sso_provider_id);


--
-- Name: saml_relay_states_created_at_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX saml_relay_states_created_at_idx ON auth.saml_relay_states USING btree (created_at DESC);


--
-- Name: saml_relay_states_for_email_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX saml_relay_states_for_email_idx ON auth.saml_relay_states USING btree (for_email);


--
-- Name: saml_relay_states_sso_provider_id_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX saml_relay_states_sso_provider_id_idx ON auth.saml_relay_states USING btree (sso_provider_id);


--
-- Name: sessions_not_after_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX sessions_not_after_idx ON auth.sessions USING btree (not_after DESC);


--
-- Name: sessions_oauth_client_id_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX sessions_oauth_client_id_idx ON auth.sessions USING btree (oauth_client_id);


--
-- Name: sessions_user_id_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX sessions_user_id_idx ON auth.sessions USING btree (user_id);


--
-- Name: sso_domains_domain_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE UNIQUE INDEX sso_domains_domain_idx ON auth.sso_domains USING btree (lower(domain));


--
-- Name: sso_domains_sso_provider_id_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX sso_domains_sso_provider_id_idx ON auth.sso_domains USING btree (sso_provider_id);


--
-- Name: sso_providers_resource_id_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE UNIQUE INDEX sso_providers_resource_id_idx ON auth.sso_providers USING btree (lower(resource_id));


--
-- Name: sso_providers_resource_id_pattern_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX sso_providers_resource_id_pattern_idx ON auth.sso_providers USING btree (resource_id text_pattern_ops);


--
-- Name: unique_phone_factor_per_user; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE UNIQUE INDEX unique_phone_factor_per_user ON auth.mfa_factors USING btree (user_id, phone);


--
-- Name: user_id_created_at_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX user_id_created_at_idx ON auth.sessions USING btree (user_id, created_at);


--
-- Name: users_email_partial_key; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE UNIQUE INDEX users_email_partial_key ON auth.users USING btree (email) WHERE (is_sso_user = false);


--
-- Name: INDEX users_email_partial_key; Type: COMMENT; Schema: auth; Owner: supabase_auth_admin
--

COMMENT ON INDEX auth.users_email_partial_key IS 'Auth: A partial unique index that applies only when is_sso_user is false';


--
-- Name: users_instance_id_email_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX users_instance_id_email_idx ON auth.users USING btree (instance_id, lower((email)::text));


--
-- Name: users_instance_id_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX users_instance_id_idx ON auth.users USING btree (instance_id);


--
-- Name: users_is_anonymous_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX users_is_anonymous_idx ON auth.users USING btree (is_anonymous);


--
-- Name: idx_bookings_sub_service_item_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_bookings_sub_service_item_id ON public.bookings USING btree (sub_service_item_id);


--
-- Name: idx_user_coupon_usage_booking; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_user_coupon_usage_booking ON public.user_coupon_usage USING btree (booking_id);


--
-- Name: idx_user_coupon_usage_user_offer; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_user_coupon_usage_user_offer ON public.user_coupon_usage USING btree (user_id, offer_id);


--
-- Name: idx_users_auth_user_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_users_auth_user_id ON public.users USING btree (auth_user_id);


--
-- Name: idx_worker_offers_coupon_code; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_worker_offers_coupon_code ON public.worker_offers USING btree (coupon_code);


--
-- Name: idx_worker_offers_worker_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_worker_offers_worker_id ON public.worker_offers USING btree (worker_id);


--
-- Name: ix_realtime_subscription_entity; Type: INDEX; Schema: realtime; Owner: supabase_admin
--

CREATE INDEX ix_realtime_subscription_entity ON realtime.subscription USING btree (entity);


--
-- Name: messages_inserted_at_topic_index; Type: INDEX; Schema: realtime; Owner: supabase_realtime_admin
--

CREATE INDEX messages_inserted_at_topic_index ON ONLY realtime.messages USING btree (inserted_at DESC, topic) WHERE ((extension = 'broadcast'::text) AND (private IS TRUE));


--
-- Name: subscription_subscription_id_entity_filters_key; Type: INDEX; Schema: realtime; Owner: supabase_admin
--

CREATE UNIQUE INDEX subscription_subscription_id_entity_filters_key ON realtime.subscription USING btree (subscription_id, entity, filters);


--
-- Name: bname; Type: INDEX; Schema: storage; Owner: supabase_storage_admin
--

CREATE UNIQUE INDEX bname ON storage.buckets USING btree (name);


--
-- Name: bucketid_objname; Type: INDEX; Schema: storage; Owner: supabase_storage_admin
--

CREATE UNIQUE INDEX bucketid_objname ON storage.objects USING btree (bucket_id, name);


--
-- Name: idx_multipart_uploads_list; Type: INDEX; Schema: storage; Owner: supabase_storage_admin
--

CREATE INDEX idx_multipart_uploads_list ON storage.s3_multipart_uploads USING btree (bucket_id, key, created_at);


--
-- Name: idx_name_bucket_level_unique; Type: INDEX; Schema: storage; Owner: supabase_storage_admin
--

CREATE UNIQUE INDEX idx_name_bucket_level_unique ON storage.objects USING btree (name COLLATE "C", bucket_id, level);


--
-- Name: idx_objects_bucket_id_name; Type: INDEX; Schema: storage; Owner: supabase_storage_admin
--

CREATE INDEX idx_objects_bucket_id_name ON storage.objects USING btree (bucket_id, name COLLATE "C");


--
-- Name: idx_objects_lower_name; Type: INDEX; Schema: storage; Owner: supabase_storage_admin
--

CREATE INDEX idx_objects_lower_name ON storage.objects USING btree ((path_tokens[level]), lower(name) text_pattern_ops, bucket_id, level);


--
-- Name: idx_prefixes_lower_name; Type: INDEX; Schema: storage; Owner: supabase_storage_admin
--

CREATE INDEX idx_prefixes_lower_name ON storage.prefixes USING btree (bucket_id, level, ((string_to_array(name, '/'::text))[level]), lower(name) text_pattern_ops);


--
-- Name: name_prefix_search; Type: INDEX; Schema: storage; Owner: supabase_storage_admin
--

CREATE INDEX name_prefix_search ON storage.objects USING btree (name text_pattern_ops);


--
-- Name: objects_bucket_id_level_idx; Type: INDEX; Schema: storage; Owner: supabase_storage_admin
--

CREATE UNIQUE INDEX objects_bucket_id_level_idx ON storage.objects USING btree (bucket_id, level, name COLLATE "C");


--
-- Name: subscription tr_check_filters; Type: TRIGGER; Schema: realtime; Owner: supabase_admin
--

CREATE TRIGGER tr_check_filters BEFORE INSERT OR UPDATE ON realtime.subscription FOR EACH ROW EXECUTE FUNCTION realtime.subscription_check_filters();


--
-- Name: buckets enforce_bucket_name_length_trigger; Type: TRIGGER; Schema: storage; Owner: supabase_storage_admin
--

CREATE TRIGGER enforce_bucket_name_length_trigger BEFORE INSERT OR UPDATE OF name ON storage.buckets FOR EACH ROW EXECUTE FUNCTION storage.enforce_bucket_name_length();


--
-- Name: objects objects_delete_delete_prefix; Type: TRIGGER; Schema: storage; Owner: supabase_storage_admin
--

CREATE TRIGGER objects_delete_delete_prefix AFTER DELETE ON storage.objects FOR EACH ROW EXECUTE FUNCTION storage.delete_prefix_hierarchy_trigger();


--
-- Name: objects objects_insert_create_prefix; Type: TRIGGER; Schema: storage; Owner: supabase_storage_admin
--

CREATE TRIGGER objects_insert_create_prefix BEFORE INSERT ON storage.objects FOR EACH ROW EXECUTE FUNCTION storage.objects_insert_prefix_trigger();


--
-- Name: objects objects_update_create_prefix; Type: TRIGGER; Schema: storage; Owner: supabase_storage_admin
--

CREATE TRIGGER objects_update_create_prefix BEFORE UPDATE ON storage.objects FOR EACH ROW WHEN (((new.name <> old.name) OR (new.bucket_id <> old.bucket_id))) EXECUTE FUNCTION storage.objects_update_prefix_trigger();


--
-- Name: prefixes prefixes_create_hierarchy; Type: TRIGGER; Schema: storage; Owner: supabase_storage_admin
--

CREATE TRIGGER prefixes_create_hierarchy BEFORE INSERT ON storage.prefixes FOR EACH ROW WHEN ((pg_trigger_depth() < 1)) EXECUTE FUNCTION storage.prefixes_insert_trigger();


--
-- Name: prefixes prefixes_delete_hierarchy; Type: TRIGGER; Schema: storage; Owner: supabase_storage_admin
--

CREATE TRIGGER prefixes_delete_hierarchy AFTER DELETE ON storage.prefixes FOR EACH ROW EXECUTE FUNCTION storage.delete_prefix_hierarchy_trigger();


--
-- Name: objects update_objects_updated_at; Type: TRIGGER; Schema: storage; Owner: supabase_storage_admin
--

CREATE TRIGGER update_objects_updated_at BEFORE UPDATE ON storage.objects FOR EACH ROW EXECUTE FUNCTION storage.update_updated_at_column();


--
-- Name: identities identities_user_id_fkey; Type: FK CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.identities
    ADD CONSTRAINT identities_user_id_fkey FOREIGN KEY (user_id) REFERENCES auth.users(id) ON DELETE CASCADE;


--
-- Name: mfa_amr_claims mfa_amr_claims_session_id_fkey; Type: FK CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.mfa_amr_claims
    ADD CONSTRAINT mfa_amr_claims_session_id_fkey FOREIGN KEY (session_id) REFERENCES auth.sessions(id) ON DELETE CASCADE;


--
-- Name: mfa_challenges mfa_challenges_auth_factor_id_fkey; Type: FK CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.mfa_challenges
    ADD CONSTRAINT mfa_challenges_auth_factor_id_fkey FOREIGN KEY (factor_id) REFERENCES auth.mfa_factors(id) ON DELETE CASCADE;


--
-- Name: mfa_factors mfa_factors_user_id_fkey; Type: FK CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.mfa_factors
    ADD CONSTRAINT mfa_factors_user_id_fkey FOREIGN KEY (user_id) REFERENCES auth.users(id) ON DELETE CASCADE;


--
-- Name: oauth_authorizations oauth_authorizations_client_id_fkey; Type: FK CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.oauth_authorizations
    ADD CONSTRAINT oauth_authorizations_client_id_fkey FOREIGN KEY (client_id) REFERENCES auth.oauth_clients(id) ON DELETE CASCADE;


--
-- Name: oauth_authorizations oauth_authorizations_user_id_fkey; Type: FK CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.oauth_authorizations
    ADD CONSTRAINT oauth_authorizations_user_id_fkey FOREIGN KEY (user_id) REFERENCES auth.users(id) ON DELETE CASCADE;


--
-- Name: oauth_consents oauth_consents_client_id_fkey; Type: FK CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.oauth_consents
    ADD CONSTRAINT oauth_consents_client_id_fkey FOREIGN KEY (client_id) REFERENCES auth.oauth_clients(id) ON DELETE CASCADE;


--
-- Name: oauth_consents oauth_consents_user_id_fkey; Type: FK CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.oauth_consents
    ADD CONSTRAINT oauth_consents_user_id_fkey FOREIGN KEY (user_id) REFERENCES auth.users(id) ON DELETE CASCADE;


--
-- Name: one_time_tokens one_time_tokens_user_id_fkey; Type: FK CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.one_time_tokens
    ADD CONSTRAINT one_time_tokens_user_id_fkey FOREIGN KEY (user_id) REFERENCES auth.users(id) ON DELETE CASCADE;


--
-- Name: refresh_tokens refresh_tokens_session_id_fkey; Type: FK CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.refresh_tokens
    ADD CONSTRAINT refresh_tokens_session_id_fkey FOREIGN KEY (session_id) REFERENCES auth.sessions(id) ON DELETE CASCADE;


--
-- Name: saml_providers saml_providers_sso_provider_id_fkey; Type: FK CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.saml_providers
    ADD CONSTRAINT saml_providers_sso_provider_id_fkey FOREIGN KEY (sso_provider_id) REFERENCES auth.sso_providers(id) ON DELETE CASCADE;


--
-- Name: saml_relay_states saml_relay_states_flow_state_id_fkey; Type: FK CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.saml_relay_states
    ADD CONSTRAINT saml_relay_states_flow_state_id_fkey FOREIGN KEY (flow_state_id) REFERENCES auth.flow_state(id) ON DELETE CASCADE;


--
-- Name: saml_relay_states saml_relay_states_sso_provider_id_fkey; Type: FK CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.saml_relay_states
    ADD CONSTRAINT saml_relay_states_sso_provider_id_fkey FOREIGN KEY (sso_provider_id) REFERENCES auth.sso_providers(id) ON DELETE CASCADE;


--
-- Name: sessions sessions_oauth_client_id_fkey; Type: FK CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.sessions
    ADD CONSTRAINT sessions_oauth_client_id_fkey FOREIGN KEY (oauth_client_id) REFERENCES auth.oauth_clients(id) ON DELETE CASCADE;


--
-- Name: sessions sessions_user_id_fkey; Type: FK CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.sessions
    ADD CONSTRAINT sessions_user_id_fkey FOREIGN KEY (user_id) REFERENCES auth.users(id) ON DELETE CASCADE;


--
-- Name: sso_domains sso_domains_sso_provider_id_fkey; Type: FK CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.sso_domains
    ADD CONSTRAINT sso_domains_sso_provider_id_fkey FOREIGN KEY (sso_provider_id) REFERENCES auth.sso_providers(id) ON DELETE CASCADE;


--
-- Name: bookings bookings_applied_offer_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.bookings
    ADD CONSTRAINT bookings_applied_offer_id_fkey FOREIGN KEY (applied_offer_id) REFERENCES public.worker_offers(id) ON DELETE RESTRICT;


--
-- Name: bookings bookings_combo_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.bookings
    ADD CONSTRAINT bookings_combo_id_fkey FOREIGN KEY (combo_id) REFERENCES public.service_combos(id) ON DELETE SET NULL;


--
-- Name: bookings bookings_customer_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.bookings
    ADD CONSTRAINT bookings_customer_id_fkey FOREIGN KEY (customer_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: bookings bookings_sub_service_item_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.bookings
    ADD CONSTRAINT bookings_sub_service_item_id_fkey FOREIGN KEY (sub_service_item_id) REFERENCES public.sub_service_items(id) ON DELETE SET NULL;


--
-- Name: bookings bookings_worker_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.bookings
    ADD CONSTRAINT bookings_worker_id_fkey FOREIGN KEY (worker_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: combo_items combo_items_combo_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.combo_items
    ADD CONSTRAINT combo_items_combo_id_fkey FOREIGN KEY (combo_id) REFERENCES public.service_combos(id) ON DELETE CASCADE;


--
-- Name: combo_items combo_items_sub_service_item_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.combo_items
    ADD CONSTRAINT combo_items_sub_service_item_id_fkey FOREIGN KEY (sub_service_item_id) REFERENCES public.sub_service_items(id) ON DELETE CASCADE;


--
-- Name: worker_services fk_sub_service; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.worker_services
    ADD CONSTRAINT fk_sub_service FOREIGN KEY (sub_service_id) REFERENCES public.sub_services(id) ON DELETE CASCADE;


--
-- Name: worker_keys fk_used_by_worker; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.worker_keys
    ADD CONSTRAINT fk_used_by_worker FOREIGN KEY (used_by_worker_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: worker_services fk_user; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.worker_services
    ADD CONSTRAINT fk_user FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: notifications notifications_actor_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.notifications
    ADD CONSTRAINT notifications_actor_id_fkey FOREIGN KEY (actor_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: notifications notifications_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.notifications
    ADD CONSTRAINT notifications_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: payouts payouts_wallet_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.payouts
    ADD CONSTRAINT payouts_wallet_id_fkey FOREIGN KEY (wallet_id) REFERENCES public.wallets(id) ON DELETE CASCADE;


--
-- Name: platform_payouts platform_payouts_requested_by_admin_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.platform_payouts
    ADD CONSTRAINT platform_payouts_requested_by_admin_id_fkey FOREIGN KEY (requested_by_admin_id) REFERENCES public.users(id);


--
-- Name: reviews reviews_booking_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.reviews
    ADD CONSTRAINT reviews_booking_id_fkey FOREIGN KEY (booking_id) REFERENCES public.bookings(id) ON DELETE CASCADE;


--
-- Name: reviews reviews_reviewer_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.reviews
    ADD CONSTRAINT reviews_reviewer_id_fkey FOREIGN KEY (reviewer_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: reviews reviews_worker_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.reviews
    ADD CONSTRAINT reviews_worker_id_fkey FOREIGN KEY (worker_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: service_combos service_combos_created_by_admin_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.service_combos
    ADD CONSTRAINT service_combos_created_by_admin_id_fkey FOREIGN KEY (created_by_admin_id) REFERENCES public.users(id);


--
-- Name: sub_service_items sub_service_items_sub_service_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sub_service_items
    ADD CONSTRAINT sub_service_items_sub_service_id_fkey FOREIGN KEY (sub_service_id) REFERENCES public.sub_services(id) ON DELETE CASCADE;


--
-- Name: sub_services sub_services_service_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sub_services
    ADD CONSTRAINT sub_services_service_id_fkey FOREIGN KEY (service_id) REFERENCES public.services(id) ON DELETE CASCADE;


--
-- Name: transactions transactions_booking_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.transactions
    ADD CONSTRAINT transactions_booking_id_fkey FOREIGN KEY (booking_id) REFERENCES public.bookings(id) ON DELETE SET NULL;


--
-- Name: transactions transactions_wallet_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.transactions
    ADD CONSTRAINT transactions_wallet_id_fkey FOREIGN KEY (wallet_id) REFERENCES public.wallets(id) ON DELETE CASCADE;


--
-- Name: user_coupon_usage user_coupon_usage_booking_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.user_coupon_usage
    ADD CONSTRAINT user_coupon_usage_booking_id_fkey FOREIGN KEY (booking_id) REFERENCES public.bookings(id) ON DELETE SET NULL;


--
-- Name: user_coupon_usage user_coupon_usage_offer_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.user_coupon_usage
    ADD CONSTRAINT user_coupon_usage_offer_id_fkey FOREIGN KEY (offer_id) REFERENCES public.worker_offers(id) ON DELETE CASCADE;


--
-- Name: user_coupon_usage user_coupon_usage_sub_service_item_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.user_coupon_usage
    ADD CONSTRAINT user_coupon_usage_sub_service_item_id_fkey FOREIGN KEY (sub_service_item_id) REFERENCES public.sub_service_items(id) ON DELETE CASCADE;


--
-- Name: user_coupon_usage user_coupon_usage_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.user_coupon_usage
    ADD CONSTRAINT user_coupon_usage_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: users users_auth_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_auth_user_id_fkey FOREIGN KEY (auth_user_id) REFERENCES auth.users(id) ON DELETE SET NULL;


--
-- Name: wallets wallets_worker_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.wallets
    ADD CONSTRAINT wallets_worker_id_fkey FOREIGN KEY (worker_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: worker_availability worker_availability_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.worker_availability
    ADD CONSTRAINT worker_availability_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: worker_offers worker_offers_worker_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.worker_offers
    ADD CONSTRAINT worker_offers_worker_id_fkey FOREIGN KEY (worker_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: worker_profiles worker_profiles_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.worker_profiles
    ADD CONSTRAINT worker_profiles_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: worker_sub_service_items worker_sub_service_items_item_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.worker_sub_service_items
    ADD CONSTRAINT worker_sub_service_items_item_id_fkey FOREIGN KEY (sub_service_item_id) REFERENCES public.sub_service_items(id) ON DELETE CASCADE;


--
-- Name: worker_sub_service_items worker_sub_service_items_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.worker_sub_service_items
    ADD CONSTRAINT worker_sub_service_items_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: objects objects_bucketId_fkey; Type: FK CONSTRAINT; Schema: storage; Owner: supabase_storage_admin
--

ALTER TABLE ONLY storage.objects
    ADD CONSTRAINT "objects_bucketId_fkey" FOREIGN KEY (bucket_id) REFERENCES storage.buckets(id);


--
-- Name: prefixes prefixes_bucketId_fkey; Type: FK CONSTRAINT; Schema: storage; Owner: supabase_storage_admin
--

ALTER TABLE ONLY storage.prefixes
    ADD CONSTRAINT "prefixes_bucketId_fkey" FOREIGN KEY (bucket_id) REFERENCES storage.buckets(id);


--
-- Name: s3_multipart_uploads s3_multipart_uploads_bucket_id_fkey; Type: FK CONSTRAINT; Schema: storage; Owner: supabase_storage_admin
--

ALTER TABLE ONLY storage.s3_multipart_uploads
    ADD CONSTRAINT s3_multipart_uploads_bucket_id_fkey FOREIGN KEY (bucket_id) REFERENCES storage.buckets(id);


--
-- Name: s3_multipart_uploads_parts s3_multipart_uploads_parts_bucket_id_fkey; Type: FK CONSTRAINT; Schema: storage; Owner: supabase_storage_admin
--

ALTER TABLE ONLY storage.s3_multipart_uploads_parts
    ADD CONSTRAINT s3_multipart_uploads_parts_bucket_id_fkey FOREIGN KEY (bucket_id) REFERENCES storage.buckets(id);


--
-- Name: s3_multipart_uploads_parts s3_multipart_uploads_parts_upload_id_fkey; Type: FK CONSTRAINT; Schema: storage; Owner: supabase_storage_admin
--

ALTER TABLE ONLY storage.s3_multipart_uploads_parts
    ADD CONSTRAINT s3_multipart_uploads_parts_upload_id_fkey FOREIGN KEY (upload_id) REFERENCES storage.s3_multipart_uploads(id) ON DELETE CASCADE;


--
-- Name: audit_log_entries; Type: ROW SECURITY; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE auth.audit_log_entries ENABLE ROW LEVEL SECURITY;

--
-- Name: flow_state; Type: ROW SECURITY; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE auth.flow_state ENABLE ROW LEVEL SECURITY;

--
-- Name: identities; Type: ROW SECURITY; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE auth.identities ENABLE ROW LEVEL SECURITY;

--
-- Name: instances; Type: ROW SECURITY; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE auth.instances ENABLE ROW LEVEL SECURITY;

--
-- Name: mfa_amr_claims; Type: ROW SECURITY; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE auth.mfa_amr_claims ENABLE ROW LEVEL SECURITY;

--
-- Name: mfa_challenges; Type: ROW SECURITY; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE auth.mfa_challenges ENABLE ROW LEVEL SECURITY;

--
-- Name: mfa_factors; Type: ROW SECURITY; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE auth.mfa_factors ENABLE ROW LEVEL SECURITY;

--
-- Name: one_time_tokens; Type: ROW SECURITY; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE auth.one_time_tokens ENABLE ROW LEVEL SECURITY;

--
-- Name: refresh_tokens; Type: ROW SECURITY; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE auth.refresh_tokens ENABLE ROW LEVEL SECURITY;

--
-- Name: saml_providers; Type: ROW SECURITY; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE auth.saml_providers ENABLE ROW LEVEL SECURITY;

--
-- Name: saml_relay_states; Type: ROW SECURITY; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE auth.saml_relay_states ENABLE ROW LEVEL SECURITY;

--
-- Name: schema_migrations; Type: ROW SECURITY; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE auth.schema_migrations ENABLE ROW LEVEL SECURITY;

--
-- Name: sessions; Type: ROW SECURITY; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE auth.sessions ENABLE ROW LEVEL SECURITY;

--
-- Name: sso_domains; Type: ROW SECURITY; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE auth.sso_domains ENABLE ROW LEVEL SECURITY;

--
-- Name: sso_providers; Type: ROW SECURITY; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE auth.sso_providers ENABLE ROW LEVEL SECURITY;

--
-- Name: users; Type: ROW SECURITY; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE auth.users ENABLE ROW LEVEL SECURITY;

--
-- Name: bookings; Type: ROW SECURITY; Schema: public; Owner: postgres
--

ALTER TABLE public.bookings ENABLE ROW LEVEL SECURITY;

--
-- Name: combo_items; Type: ROW SECURITY; Schema: public; Owner: postgres
--

ALTER TABLE public.combo_items ENABLE ROW LEVEL SECURITY;

--
-- Name: payouts; Type: ROW SECURITY; Schema: public; Owner: postgres
--

ALTER TABLE public.payouts ENABLE ROW LEVEL SECURITY;

--
-- Name: platform_payouts; Type: ROW SECURITY; Schema: public; Owner: postgres
--

ALTER TABLE public.platform_payouts ENABLE ROW LEVEL SECURITY;

--
-- Name: reviews; Type: ROW SECURITY; Schema: public; Owner: postgres
--

ALTER TABLE public.reviews ENABLE ROW LEVEL SECURITY;

--
-- Name: service_combos; Type: ROW SECURITY; Schema: public; Owner: postgres
--

ALTER TABLE public.service_combos ENABLE ROW LEVEL SECURITY;

--
-- Name: services; Type: ROW SECURITY; Schema: public; Owner: postgres
--

ALTER TABLE public.services ENABLE ROW LEVEL SECURITY;

--
-- Name: sub_service_items; Type: ROW SECURITY; Schema: public; Owner: postgres
--

ALTER TABLE public.sub_service_items ENABLE ROW LEVEL SECURITY;

--
-- Name: sub_services; Type: ROW SECURITY; Schema: public; Owner: postgres
--

ALTER TABLE public.sub_services ENABLE ROW LEVEL SECURITY;

--
-- Name: transactions; Type: ROW SECURITY; Schema: public; Owner: postgres
--

ALTER TABLE public.transactions ENABLE ROW LEVEL SECURITY;

--
-- Name: user_coupon_usage; Type: ROW SECURITY; Schema: public; Owner: postgres
--

ALTER TABLE public.user_coupon_usage ENABLE ROW LEVEL SECURITY;

--
-- Name: users; Type: ROW SECURITY; Schema: public; Owner: postgres
--

ALTER TABLE public.users ENABLE ROW LEVEL SECURITY;

--
-- Name: wallets; Type: ROW SECURITY; Schema: public; Owner: postgres
--

ALTER TABLE public.wallets ENABLE ROW LEVEL SECURITY;

--
-- Name: worker_availability; Type: ROW SECURITY; Schema: public; Owner: postgres
--

ALTER TABLE public.worker_availability ENABLE ROW LEVEL SECURITY;

--
-- Name: worker_keys; Type: ROW SECURITY; Schema: public; Owner: postgres
--

ALTER TABLE public.worker_keys ENABLE ROW LEVEL SECURITY;

--
-- Name: worker_offers; Type: ROW SECURITY; Schema: public; Owner: postgres
--

ALTER TABLE public.worker_offers ENABLE ROW LEVEL SECURITY;

--
-- Name: worker_profiles; Type: ROW SECURITY; Schema: public; Owner: postgres
--

ALTER TABLE public.worker_profiles ENABLE ROW LEVEL SECURITY;

--
-- Name: worker_services; Type: ROW SECURITY; Schema: public; Owner: postgres
--

ALTER TABLE public.worker_services ENABLE ROW LEVEL SECURITY;

--
-- Name: worker_sub_service_items; Type: ROW SECURITY; Schema: public; Owner: postgres
--

ALTER TABLE public.worker_sub_service_items ENABLE ROW LEVEL SECURITY;

--
-- Name: messages; Type: ROW SECURITY; Schema: realtime; Owner: supabase_realtime_admin
--

ALTER TABLE realtime.messages ENABLE ROW LEVEL SECURITY;

--
-- Name: buckets; Type: ROW SECURITY; Schema: storage; Owner: supabase_storage_admin
--

ALTER TABLE storage.buckets ENABLE ROW LEVEL SECURITY;

--
-- Name: buckets_analytics; Type: ROW SECURITY; Schema: storage; Owner: supabase_storage_admin
--

ALTER TABLE storage.buckets_analytics ENABLE ROW LEVEL SECURITY;

--
-- Name: migrations; Type: ROW SECURITY; Schema: storage; Owner: supabase_storage_admin
--

ALTER TABLE storage.migrations ENABLE ROW LEVEL SECURITY;

--
-- Name: objects; Type: ROW SECURITY; Schema: storage; Owner: supabase_storage_admin
--

ALTER TABLE storage.objects ENABLE ROW LEVEL SECURITY;

--
-- Name: prefixes; Type: ROW SECURITY; Schema: storage; Owner: supabase_storage_admin
--

ALTER TABLE storage.prefixes ENABLE ROW LEVEL SECURITY;

--
-- Name: s3_multipart_uploads; Type: ROW SECURITY; Schema: storage; Owner: supabase_storage_admin
--

ALTER TABLE storage.s3_multipart_uploads ENABLE ROW LEVEL SECURITY;

--
-- Name: s3_multipart_uploads_parts; Type: ROW SECURITY; Schema: storage; Owner: supabase_storage_admin
--

ALTER TABLE storage.s3_multipart_uploads_parts ENABLE ROW LEVEL SECURITY;

--
-- Name: supabase_realtime; Type: PUBLICATION; Schema: -; Owner: postgres
--

CREATE PUBLICATION supabase_realtime WITH (publish = 'insert, update, delete, truncate');


ALTER PUBLICATION supabase_realtime OWNER TO postgres;

--
-- Name: supabase_realtime notifications; Type: PUBLICATION TABLE; Schema: public; Owner: postgres
--

ALTER PUBLICATION supabase_realtime ADD TABLE ONLY public.notifications;


--
-- Name: SCHEMA auth; Type: ACL; Schema: -; Owner: supabase_admin
--

GRANT USAGE ON SCHEMA auth TO anon;
GRANT USAGE ON SCHEMA auth TO authenticated;
GRANT USAGE ON SCHEMA auth TO service_role;
GRANT ALL ON SCHEMA auth TO supabase_auth_admin;
GRANT ALL ON SCHEMA auth TO dashboard_user;
GRANT USAGE ON SCHEMA auth TO postgres;


--
-- Name: SCHEMA extensions; Type: ACL; Schema: -; Owner: postgres
--

GRANT USAGE ON SCHEMA extensions TO anon;
GRANT USAGE ON SCHEMA extensions TO authenticated;
GRANT USAGE ON SCHEMA extensions TO service_role;
GRANT ALL ON SCHEMA extensions TO dashboard_user;


--
-- Name: SCHEMA public; Type: ACL; Schema: -; Owner: pg_database_owner
--

GRANT USAGE ON SCHEMA public TO postgres;
GRANT USAGE ON SCHEMA public TO anon;
GRANT USAGE ON SCHEMA public TO authenticated;
GRANT USAGE ON SCHEMA public TO service_role;


--
-- Name: SCHEMA realtime; Type: ACL; Schema: -; Owner: supabase_admin
--

GRANT USAGE ON SCHEMA realtime TO postgres;
GRANT USAGE ON SCHEMA realtime TO anon;
GRANT USAGE ON SCHEMA realtime TO authenticated;
GRANT USAGE ON SCHEMA realtime TO service_role;
GRANT ALL ON SCHEMA realtime TO supabase_realtime_admin;


--
-- Name: SCHEMA storage; Type: ACL; Schema: -; Owner: supabase_admin
--

GRANT USAGE ON SCHEMA storage TO postgres WITH GRANT OPTION;
GRANT USAGE ON SCHEMA storage TO anon;
GRANT USAGE ON SCHEMA storage TO authenticated;
GRANT USAGE ON SCHEMA storage TO service_role;
GRANT ALL ON SCHEMA storage TO supabase_storage_admin;
GRANT ALL ON SCHEMA storage TO dashboard_user;


--
-- Name: SCHEMA vault; Type: ACL; Schema: -; Owner: supabase_admin
--

GRANT USAGE ON SCHEMA vault TO postgres WITH GRANT OPTION;
GRANT USAGE ON SCHEMA vault TO service_role;


--
-- Name: FUNCTION email(); Type: ACL; Schema: auth; Owner: supabase_auth_admin
--

GRANT ALL ON FUNCTION auth.email() TO dashboard_user;


--
-- Name: FUNCTION jwt(); Type: ACL; Schema: auth; Owner: supabase_auth_admin
--

GRANT ALL ON FUNCTION auth.jwt() TO postgres;
GRANT ALL ON FUNCTION auth.jwt() TO dashboard_user;


--
-- Name: FUNCTION role(); Type: ACL; Schema: auth; Owner: supabase_auth_admin
--

GRANT ALL ON FUNCTION auth.role() TO dashboard_user;


--
-- Name: FUNCTION uid(); Type: ACL; Schema: auth; Owner: supabase_auth_admin
--

GRANT ALL ON FUNCTION auth.uid() TO dashboard_user;


--
-- Name: FUNCTION armor(bytea); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.armor(bytea) FROM postgres;
GRANT ALL ON FUNCTION extensions.armor(bytea) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.armor(bytea) TO dashboard_user;


--
-- Name: FUNCTION armor(bytea, text[], text[]); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.armor(bytea, text[], text[]) FROM postgres;
GRANT ALL ON FUNCTION extensions.armor(bytea, text[], text[]) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.armor(bytea, text[], text[]) TO dashboard_user;


--
-- Name: FUNCTION crypt(text, text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.crypt(text, text) FROM postgres;
GRANT ALL ON FUNCTION extensions.crypt(text, text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.crypt(text, text) TO dashboard_user;


--
-- Name: FUNCTION dearmor(text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.dearmor(text) FROM postgres;
GRANT ALL ON FUNCTION extensions.dearmor(text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.dearmor(text) TO dashboard_user;


--
-- Name: FUNCTION decrypt(bytea, bytea, text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.decrypt(bytea, bytea, text) FROM postgres;
GRANT ALL ON FUNCTION extensions.decrypt(bytea, bytea, text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.decrypt(bytea, bytea, text) TO dashboard_user;


--
-- Name: FUNCTION decrypt_iv(bytea, bytea, bytea, text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.decrypt_iv(bytea, bytea, bytea, text) FROM postgres;
GRANT ALL ON FUNCTION extensions.decrypt_iv(bytea, bytea, bytea, text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.decrypt_iv(bytea, bytea, bytea, text) TO dashboard_user;


--
-- Name: FUNCTION digest(bytea, text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.digest(bytea, text) FROM postgres;
GRANT ALL ON FUNCTION extensions.digest(bytea, text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.digest(bytea, text) TO dashboard_user;


--
-- Name: FUNCTION digest(text, text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.digest(text, text) FROM postgres;
GRANT ALL ON FUNCTION extensions.digest(text, text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.digest(text, text) TO dashboard_user;


--
-- Name: FUNCTION encrypt(bytea, bytea, text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.encrypt(bytea, bytea, text) FROM postgres;
GRANT ALL ON FUNCTION extensions.encrypt(bytea, bytea, text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.encrypt(bytea, bytea, text) TO dashboard_user;


--
-- Name: FUNCTION encrypt_iv(bytea, bytea, bytea, text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.encrypt_iv(bytea, bytea, bytea, text) FROM postgres;
GRANT ALL ON FUNCTION extensions.encrypt_iv(bytea, bytea, bytea, text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.encrypt_iv(bytea, bytea, bytea, text) TO dashboard_user;


--
-- Name: FUNCTION gen_random_bytes(integer); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.gen_random_bytes(integer) FROM postgres;
GRANT ALL ON FUNCTION extensions.gen_random_bytes(integer) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.gen_random_bytes(integer) TO dashboard_user;


--
-- Name: FUNCTION gen_random_uuid(); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.gen_random_uuid() FROM postgres;
GRANT ALL ON FUNCTION extensions.gen_random_uuid() TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.gen_random_uuid() TO dashboard_user;


--
-- Name: FUNCTION gen_salt(text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.gen_salt(text) FROM postgres;
GRANT ALL ON FUNCTION extensions.gen_salt(text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.gen_salt(text) TO dashboard_user;


--
-- Name: FUNCTION gen_salt(text, integer); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.gen_salt(text, integer) FROM postgres;
GRANT ALL ON FUNCTION extensions.gen_salt(text, integer) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.gen_salt(text, integer) TO dashboard_user;


--
-- Name: FUNCTION grant_pg_cron_access(); Type: ACL; Schema: extensions; Owner: supabase_admin
--

REVOKE ALL ON FUNCTION extensions.grant_pg_cron_access() FROM supabase_admin;
GRANT ALL ON FUNCTION extensions.grant_pg_cron_access() TO supabase_admin WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.grant_pg_cron_access() TO dashboard_user;


--
-- Name: FUNCTION grant_pg_graphql_access(); Type: ACL; Schema: extensions; Owner: supabase_admin
--

GRANT ALL ON FUNCTION extensions.grant_pg_graphql_access() TO postgres WITH GRANT OPTION;


--
-- Name: FUNCTION grant_pg_net_access(); Type: ACL; Schema: extensions; Owner: supabase_admin
--

REVOKE ALL ON FUNCTION extensions.grant_pg_net_access() FROM supabase_admin;
GRANT ALL ON FUNCTION extensions.grant_pg_net_access() TO supabase_admin WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.grant_pg_net_access() TO dashboard_user;


--
-- Name: FUNCTION hmac(bytea, bytea, text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.hmac(bytea, bytea, text) FROM postgres;
GRANT ALL ON FUNCTION extensions.hmac(bytea, bytea, text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.hmac(bytea, bytea, text) TO dashboard_user;


--
-- Name: FUNCTION hmac(text, text, text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.hmac(text, text, text) FROM postgres;
GRANT ALL ON FUNCTION extensions.hmac(text, text, text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.hmac(text, text, text) TO dashboard_user;


--
-- Name: FUNCTION pg_stat_statements(showtext boolean, OUT userid oid, OUT dbid oid, OUT toplevel boolean, OUT queryid bigint, OUT query text, OUT plans bigint, OUT total_plan_time double precision, OUT min_plan_time double precision, OUT max_plan_time double precision, OUT mean_plan_time double precision, OUT stddev_plan_time double precision, OUT calls bigint, OUT total_exec_time double precision, OUT min_exec_time double precision, OUT max_exec_time double precision, OUT mean_exec_time double precision, OUT stddev_exec_time double precision, OUT rows bigint, OUT shared_blks_hit bigint, OUT shared_blks_read bigint, OUT shared_blks_dirtied bigint, OUT shared_blks_written bigint, OUT local_blks_hit bigint, OUT local_blks_read bigint, OUT local_blks_dirtied bigint, OUT local_blks_written bigint, OUT temp_blks_read bigint, OUT temp_blks_written bigint, OUT shared_blk_read_time double precision, OUT shared_blk_write_time double precision, OUT local_blk_read_time double precision, OUT local_blk_write_time double precision, OUT temp_blk_read_time double precision, OUT temp_blk_write_time double precision, OUT wal_records bigint, OUT wal_fpi bigint, OUT wal_bytes numeric, OUT jit_functions bigint, OUT jit_generation_time double precision, OUT jit_inlining_count bigint, OUT jit_inlining_time double precision, OUT jit_optimization_count bigint, OUT jit_optimization_time double precision, OUT jit_emission_count bigint, OUT jit_emission_time double precision, OUT jit_deform_count bigint, OUT jit_deform_time double precision, OUT stats_since timestamp with time zone, OUT minmax_stats_since timestamp with time zone); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.pg_stat_statements(showtext boolean, OUT userid oid, OUT dbid oid, OUT toplevel boolean, OUT queryid bigint, OUT query text, OUT plans bigint, OUT total_plan_time double precision, OUT min_plan_time double precision, OUT max_plan_time double precision, OUT mean_plan_time double precision, OUT stddev_plan_time double precision, OUT calls bigint, OUT total_exec_time double precision, OUT min_exec_time double precision, OUT max_exec_time double precision, OUT mean_exec_time double precision, OUT stddev_exec_time double precision, OUT rows bigint, OUT shared_blks_hit bigint, OUT shared_blks_read bigint, OUT shared_blks_dirtied bigint, OUT shared_blks_written bigint, OUT local_blks_hit bigint, OUT local_blks_read bigint, OUT local_blks_dirtied bigint, OUT local_blks_written bigint, OUT temp_blks_read bigint, OUT temp_blks_written bigint, OUT shared_blk_read_time double precision, OUT shared_blk_write_time double precision, OUT local_blk_read_time double precision, OUT local_blk_write_time double precision, OUT temp_blk_read_time double precision, OUT temp_blk_write_time double precision, OUT wal_records bigint, OUT wal_fpi bigint, OUT wal_bytes numeric, OUT jit_functions bigint, OUT jit_generation_time double precision, OUT jit_inlining_count bigint, OUT jit_inlining_time double precision, OUT jit_optimization_count bigint, OUT jit_optimization_time double precision, OUT jit_emission_count bigint, OUT jit_emission_time double precision, OUT jit_deform_count bigint, OUT jit_deform_time double precision, OUT stats_since timestamp with time zone, OUT minmax_stats_since timestamp with time zone) FROM postgres;
GRANT ALL ON FUNCTION extensions.pg_stat_statements(showtext boolean, OUT userid oid, OUT dbid oid, OUT toplevel boolean, OUT queryid bigint, OUT query text, OUT plans bigint, OUT total_plan_time double precision, OUT min_plan_time double precision, OUT max_plan_time double precision, OUT mean_plan_time double precision, OUT stddev_plan_time double precision, OUT calls bigint, OUT total_exec_time double precision, OUT min_exec_time double precision, OUT max_exec_time double precision, OUT mean_exec_time double precision, OUT stddev_exec_time double precision, OUT rows bigint, OUT shared_blks_hit bigint, OUT shared_blks_read bigint, OUT shared_blks_dirtied bigint, OUT shared_blks_written bigint, OUT local_blks_hit bigint, OUT local_blks_read bigint, OUT local_blks_dirtied bigint, OUT local_blks_written bigint, OUT temp_blks_read bigint, OUT temp_blks_written bigint, OUT shared_blk_read_time double precision, OUT shared_blk_write_time double precision, OUT local_blk_read_time double precision, OUT local_blk_write_time double precision, OUT temp_blk_read_time double precision, OUT temp_blk_write_time double precision, OUT wal_records bigint, OUT wal_fpi bigint, OUT wal_bytes numeric, OUT jit_functions bigint, OUT jit_generation_time double precision, OUT jit_inlining_count bigint, OUT jit_inlining_time double precision, OUT jit_optimization_count bigint, OUT jit_optimization_time double precision, OUT jit_emission_count bigint, OUT jit_emission_time double precision, OUT jit_deform_count bigint, OUT jit_deform_time double precision, OUT stats_since timestamp with time zone, OUT minmax_stats_since timestamp with time zone) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.pg_stat_statements(showtext boolean, OUT userid oid, OUT dbid oid, OUT toplevel boolean, OUT queryid bigint, OUT query text, OUT plans bigint, OUT total_plan_time double precision, OUT min_plan_time double precision, OUT max_plan_time double precision, OUT mean_plan_time double precision, OUT stddev_plan_time double precision, OUT calls bigint, OUT total_exec_time double precision, OUT min_exec_time double precision, OUT max_exec_time double precision, OUT mean_exec_time double precision, OUT stddev_exec_time double precision, OUT rows bigint, OUT shared_blks_hit bigint, OUT shared_blks_read bigint, OUT shared_blks_dirtied bigint, OUT shared_blks_written bigint, OUT local_blks_hit bigint, OUT local_blks_read bigint, OUT local_blks_dirtied bigint, OUT local_blks_written bigint, OUT temp_blks_read bigint, OUT temp_blks_written bigint, OUT shared_blk_read_time double precision, OUT shared_blk_write_time double precision, OUT local_blk_read_time double precision, OUT local_blk_write_time double precision, OUT temp_blk_read_time double precision, OUT temp_blk_write_time double precision, OUT wal_records bigint, OUT wal_fpi bigint, OUT wal_bytes numeric, OUT jit_functions bigint, OUT jit_generation_time double precision, OUT jit_inlining_count bigint, OUT jit_inlining_time double precision, OUT jit_optimization_count bigint, OUT jit_optimization_time double precision, OUT jit_emission_count bigint, OUT jit_emission_time double precision, OUT jit_deform_count bigint, OUT jit_deform_time double precision, OUT stats_since timestamp with time zone, OUT minmax_stats_since timestamp with time zone) TO dashboard_user;


--
-- Name: FUNCTION pg_stat_statements_info(OUT dealloc bigint, OUT stats_reset timestamp with time zone); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.pg_stat_statements_info(OUT dealloc bigint, OUT stats_reset timestamp with time zone) FROM postgres;
GRANT ALL ON FUNCTION extensions.pg_stat_statements_info(OUT dealloc bigint, OUT stats_reset timestamp with time zone) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.pg_stat_statements_info(OUT dealloc bigint, OUT stats_reset timestamp with time zone) TO dashboard_user;


--
-- Name: FUNCTION pg_stat_statements_reset(userid oid, dbid oid, queryid bigint, minmax_only boolean); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.pg_stat_statements_reset(userid oid, dbid oid, queryid bigint, minmax_only boolean) FROM postgres;
GRANT ALL ON FUNCTION extensions.pg_stat_statements_reset(userid oid, dbid oid, queryid bigint, minmax_only boolean) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.pg_stat_statements_reset(userid oid, dbid oid, queryid bigint, minmax_only boolean) TO dashboard_user;


--
-- Name: FUNCTION pgp_armor_headers(text, OUT key text, OUT value text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.pgp_armor_headers(text, OUT key text, OUT value text) FROM postgres;
GRANT ALL ON FUNCTION extensions.pgp_armor_headers(text, OUT key text, OUT value text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.pgp_armor_headers(text, OUT key text, OUT value text) TO dashboard_user;


--
-- Name: FUNCTION pgp_key_id(bytea); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.pgp_key_id(bytea) FROM postgres;
GRANT ALL ON FUNCTION extensions.pgp_key_id(bytea) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.pgp_key_id(bytea) TO dashboard_user;


--
-- Name: FUNCTION pgp_pub_decrypt(bytea, bytea); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.pgp_pub_decrypt(bytea, bytea) FROM postgres;
GRANT ALL ON FUNCTION extensions.pgp_pub_decrypt(bytea, bytea) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.pgp_pub_decrypt(bytea, bytea) TO dashboard_user;


--
-- Name: FUNCTION pgp_pub_decrypt(bytea, bytea, text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.pgp_pub_decrypt(bytea, bytea, text) FROM postgres;
GRANT ALL ON FUNCTION extensions.pgp_pub_decrypt(bytea, bytea, text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.pgp_pub_decrypt(bytea, bytea, text) TO dashboard_user;


--
-- Name: FUNCTION pgp_pub_decrypt(bytea, bytea, text, text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.pgp_pub_decrypt(bytea, bytea, text, text) FROM postgres;
GRANT ALL ON FUNCTION extensions.pgp_pub_decrypt(bytea, bytea, text, text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.pgp_pub_decrypt(bytea, bytea, text, text) TO dashboard_user;


--
-- Name: FUNCTION pgp_pub_decrypt_bytea(bytea, bytea); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.pgp_pub_decrypt_bytea(bytea, bytea) FROM postgres;
GRANT ALL ON FUNCTION extensions.pgp_pub_decrypt_bytea(bytea, bytea) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.pgp_pub_decrypt_bytea(bytea, bytea) TO dashboard_user;


--
-- Name: FUNCTION pgp_pub_decrypt_bytea(bytea, bytea, text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.pgp_pub_decrypt_bytea(bytea, bytea, text) FROM postgres;
GRANT ALL ON FUNCTION extensions.pgp_pub_decrypt_bytea(bytea, bytea, text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.pgp_pub_decrypt_bytea(bytea, bytea, text) TO dashboard_user;


--
-- Name: FUNCTION pgp_pub_decrypt_bytea(bytea, bytea, text, text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.pgp_pub_decrypt_bytea(bytea, bytea, text, text) FROM postgres;
GRANT ALL ON FUNCTION extensions.pgp_pub_decrypt_bytea(bytea, bytea, text, text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.pgp_pub_decrypt_bytea(bytea, bytea, text, text) TO dashboard_user;


--
-- Name: FUNCTION pgp_pub_encrypt(text, bytea); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.pgp_pub_encrypt(text, bytea) FROM postgres;
GRANT ALL ON FUNCTION extensions.pgp_pub_encrypt(text, bytea) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.pgp_pub_encrypt(text, bytea) TO dashboard_user;


--
-- Name: FUNCTION pgp_pub_encrypt(text, bytea, text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.pgp_pub_encrypt(text, bytea, text) FROM postgres;
GRANT ALL ON FUNCTION extensions.pgp_pub_encrypt(text, bytea, text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.pgp_pub_encrypt(text, bytea, text) TO dashboard_user;


--
-- Name: FUNCTION pgp_pub_encrypt_bytea(bytea, bytea); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.pgp_pub_encrypt_bytea(bytea, bytea) FROM postgres;
GRANT ALL ON FUNCTION extensions.pgp_pub_encrypt_bytea(bytea, bytea) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.pgp_pub_encrypt_bytea(bytea, bytea) TO dashboard_user;


--
-- Name: FUNCTION pgp_pub_encrypt_bytea(bytea, bytea, text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.pgp_pub_encrypt_bytea(bytea, bytea, text) FROM postgres;
GRANT ALL ON FUNCTION extensions.pgp_pub_encrypt_bytea(bytea, bytea, text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.pgp_pub_encrypt_bytea(bytea, bytea, text) TO dashboard_user;


--
-- Name: FUNCTION pgp_sym_decrypt(bytea, text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.pgp_sym_decrypt(bytea, text) FROM postgres;
GRANT ALL ON FUNCTION extensions.pgp_sym_decrypt(bytea, text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.pgp_sym_decrypt(bytea, text) TO dashboard_user;


--
-- Name: FUNCTION pgp_sym_decrypt(bytea, text, text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.pgp_sym_decrypt(bytea, text, text) FROM postgres;
GRANT ALL ON FUNCTION extensions.pgp_sym_decrypt(bytea, text, text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.pgp_sym_decrypt(bytea, text, text) TO dashboard_user;


--
-- Name: FUNCTION pgp_sym_decrypt_bytea(bytea, text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.pgp_sym_decrypt_bytea(bytea, text) FROM postgres;
GRANT ALL ON FUNCTION extensions.pgp_sym_decrypt_bytea(bytea, text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.pgp_sym_decrypt_bytea(bytea, text) TO dashboard_user;


--
-- Name: FUNCTION pgp_sym_decrypt_bytea(bytea, text, text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.pgp_sym_decrypt_bytea(bytea, text, text) FROM postgres;
GRANT ALL ON FUNCTION extensions.pgp_sym_decrypt_bytea(bytea, text, text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.pgp_sym_decrypt_bytea(bytea, text, text) TO dashboard_user;


--
-- Name: FUNCTION pgp_sym_encrypt(text, text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.pgp_sym_encrypt(text, text) FROM postgres;
GRANT ALL ON FUNCTION extensions.pgp_sym_encrypt(text, text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.pgp_sym_encrypt(text, text) TO dashboard_user;


--
-- Name: FUNCTION pgp_sym_encrypt(text, text, text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.pgp_sym_encrypt(text, text, text) FROM postgres;
GRANT ALL ON FUNCTION extensions.pgp_sym_encrypt(text, text, text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.pgp_sym_encrypt(text, text, text) TO dashboard_user;


--
-- Name: FUNCTION pgp_sym_encrypt_bytea(bytea, text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.pgp_sym_encrypt_bytea(bytea, text) FROM postgres;
GRANT ALL ON FUNCTION extensions.pgp_sym_encrypt_bytea(bytea, text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.pgp_sym_encrypt_bytea(bytea, text) TO dashboard_user;


--
-- Name: FUNCTION pgp_sym_encrypt_bytea(bytea, text, text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.pgp_sym_encrypt_bytea(bytea, text, text) FROM postgres;
GRANT ALL ON FUNCTION extensions.pgp_sym_encrypt_bytea(bytea, text, text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.pgp_sym_encrypt_bytea(bytea, text, text) TO dashboard_user;


--
-- Name: FUNCTION pgrst_ddl_watch(); Type: ACL; Schema: extensions; Owner: supabase_admin
--

GRANT ALL ON FUNCTION extensions.pgrst_ddl_watch() TO postgres WITH GRANT OPTION;


--
-- Name: FUNCTION pgrst_drop_watch(); Type: ACL; Schema: extensions; Owner: supabase_admin
--

GRANT ALL ON FUNCTION extensions.pgrst_drop_watch() TO postgres WITH GRANT OPTION;


--
-- Name: FUNCTION set_graphql_placeholder(); Type: ACL; Schema: extensions; Owner: supabase_admin
--

GRANT ALL ON FUNCTION extensions.set_graphql_placeholder() TO postgres WITH GRANT OPTION;


--
-- Name: FUNCTION uuid_generate_v1(); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.uuid_generate_v1() FROM postgres;
GRANT ALL ON FUNCTION extensions.uuid_generate_v1() TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.uuid_generate_v1() TO dashboard_user;


--
-- Name: FUNCTION uuid_generate_v1mc(); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.uuid_generate_v1mc() FROM postgres;
GRANT ALL ON FUNCTION extensions.uuid_generate_v1mc() TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.uuid_generate_v1mc() TO dashboard_user;


--
-- Name: FUNCTION uuid_generate_v3(namespace uuid, name text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.uuid_generate_v3(namespace uuid, name text) FROM postgres;
GRANT ALL ON FUNCTION extensions.uuid_generate_v3(namespace uuid, name text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.uuid_generate_v3(namespace uuid, name text) TO dashboard_user;


--
-- Name: FUNCTION uuid_generate_v4(); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.uuid_generate_v4() FROM postgres;
GRANT ALL ON FUNCTION extensions.uuid_generate_v4() TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.uuid_generate_v4() TO dashboard_user;


--
-- Name: FUNCTION uuid_generate_v5(namespace uuid, name text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.uuid_generate_v5(namespace uuid, name text) FROM postgres;
GRANT ALL ON FUNCTION extensions.uuid_generate_v5(namespace uuid, name text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.uuid_generate_v5(namespace uuid, name text) TO dashboard_user;


--
-- Name: FUNCTION uuid_nil(); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.uuid_nil() FROM postgres;
GRANT ALL ON FUNCTION extensions.uuid_nil() TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.uuid_nil() TO dashboard_user;


--
-- Name: FUNCTION uuid_ns_dns(); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.uuid_ns_dns() FROM postgres;
GRANT ALL ON FUNCTION extensions.uuid_ns_dns() TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.uuid_ns_dns() TO dashboard_user;


--
-- Name: FUNCTION uuid_ns_oid(); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.uuid_ns_oid() FROM postgres;
GRANT ALL ON FUNCTION extensions.uuid_ns_oid() TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.uuid_ns_oid() TO dashboard_user;


--
-- Name: FUNCTION uuid_ns_url(); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.uuid_ns_url() FROM postgres;
GRANT ALL ON FUNCTION extensions.uuid_ns_url() TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.uuid_ns_url() TO dashboard_user;


--
-- Name: FUNCTION uuid_ns_x500(); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.uuid_ns_x500() FROM postgres;
GRANT ALL ON FUNCTION extensions.uuid_ns_x500() TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.uuid_ns_x500() TO dashboard_user;


--
-- Name: FUNCTION graphql("operationName" text, query text, variables jsonb, extensions jsonb); Type: ACL; Schema: graphql_public; Owner: supabase_admin
--

GRANT ALL ON FUNCTION graphql_public.graphql("operationName" text, query text, variables jsonb, extensions jsonb) TO postgres;
GRANT ALL ON FUNCTION graphql_public.graphql("operationName" text, query text, variables jsonb, extensions jsonb) TO anon;
GRANT ALL ON FUNCTION graphql_public.graphql("operationName" text, query text, variables jsonb, extensions jsonb) TO authenticated;
GRANT ALL ON FUNCTION graphql_public.graphql("operationName" text, query text, variables jsonb, extensions jsonb) TO service_role;


--
-- Name: FUNCTION get_auth(p_usename text); Type: ACL; Schema: pgbouncer; Owner: supabase_admin
--

REVOKE ALL ON FUNCTION pgbouncer.get_auth(p_usename text) FROM PUBLIC;
GRANT ALL ON FUNCTION pgbouncer.get_auth(p_usename text) TO pgbouncer;
GRANT ALL ON FUNCTION pgbouncer.get_auth(p_usename text) TO postgres;


--
-- Name: FUNCTION apply_rls(wal jsonb, max_record_bytes integer); Type: ACL; Schema: realtime; Owner: supabase_admin
--

GRANT ALL ON FUNCTION realtime.apply_rls(wal jsonb, max_record_bytes integer) TO postgres;
GRANT ALL ON FUNCTION realtime.apply_rls(wal jsonb, max_record_bytes integer) TO dashboard_user;
GRANT ALL ON FUNCTION realtime.apply_rls(wal jsonb, max_record_bytes integer) TO anon;
GRANT ALL ON FUNCTION realtime.apply_rls(wal jsonb, max_record_bytes integer) TO authenticated;
GRANT ALL ON FUNCTION realtime.apply_rls(wal jsonb, max_record_bytes integer) TO service_role;
GRANT ALL ON FUNCTION realtime.apply_rls(wal jsonb, max_record_bytes integer) TO supabase_realtime_admin;


--
-- Name: FUNCTION broadcast_changes(topic_name text, event_name text, operation text, table_name text, table_schema text, new record, old record, level text); Type: ACL; Schema: realtime; Owner: supabase_admin
--

GRANT ALL ON FUNCTION realtime.broadcast_changes(topic_name text, event_name text, operation text, table_name text, table_schema text, new record, old record, level text) TO postgres;
GRANT ALL ON FUNCTION realtime.broadcast_changes(topic_name text, event_name text, operation text, table_name text, table_schema text, new record, old record, level text) TO dashboard_user;


--
-- Name: FUNCTION build_prepared_statement_sql(prepared_statement_name text, entity regclass, columns realtime.wal_column[]); Type: ACL; Schema: realtime; Owner: supabase_admin
--

GRANT ALL ON FUNCTION realtime.build_prepared_statement_sql(prepared_statement_name text, entity regclass, columns realtime.wal_column[]) TO postgres;
GRANT ALL ON FUNCTION realtime.build_prepared_statement_sql(prepared_statement_name text, entity regclass, columns realtime.wal_column[]) TO dashboard_user;
GRANT ALL ON FUNCTION realtime.build_prepared_statement_sql(prepared_statement_name text, entity regclass, columns realtime.wal_column[]) TO anon;
GRANT ALL ON FUNCTION realtime.build_prepared_statement_sql(prepared_statement_name text, entity regclass, columns realtime.wal_column[]) TO authenticated;
GRANT ALL ON FUNCTION realtime.build_prepared_statement_sql(prepared_statement_name text, entity regclass, columns realtime.wal_column[]) TO service_role;
GRANT ALL ON FUNCTION realtime.build_prepared_statement_sql(prepared_statement_name text, entity regclass, columns realtime.wal_column[]) TO supabase_realtime_admin;


--
-- Name: FUNCTION "cast"(val text, type_ regtype); Type: ACL; Schema: realtime; Owner: supabase_admin
--

GRANT ALL ON FUNCTION realtime."cast"(val text, type_ regtype) TO postgres;
GRANT ALL ON FUNCTION realtime."cast"(val text, type_ regtype) TO dashboard_user;
GRANT ALL ON FUNCTION realtime."cast"(val text, type_ regtype) TO anon;
GRANT ALL ON FUNCTION realtime."cast"(val text, type_ regtype) TO authenticated;
GRANT ALL ON FUNCTION realtime."cast"(val text, type_ regtype) TO service_role;
GRANT ALL ON FUNCTION realtime."cast"(val text, type_ regtype) TO supabase_realtime_admin;


--
-- Name: FUNCTION check_equality_op(op realtime.equality_op, type_ regtype, val_1 text, val_2 text); Type: ACL; Schema: realtime; Owner: supabase_admin
--

GRANT ALL ON FUNCTION realtime.check_equality_op(op realtime.equality_op, type_ regtype, val_1 text, val_2 text) TO postgres;
GRANT ALL ON FUNCTION realtime.check_equality_op(op realtime.equality_op, type_ regtype, val_1 text, val_2 text) TO dashboard_user;
GRANT ALL ON FUNCTION realtime.check_equality_op(op realtime.equality_op, type_ regtype, val_1 text, val_2 text) TO anon;
GRANT ALL ON FUNCTION realtime.check_equality_op(op realtime.equality_op, type_ regtype, val_1 text, val_2 text) TO authenticated;
GRANT ALL ON FUNCTION realtime.check_equality_op(op realtime.equality_op, type_ regtype, val_1 text, val_2 text) TO service_role;
GRANT ALL ON FUNCTION realtime.check_equality_op(op realtime.equality_op, type_ regtype, val_1 text, val_2 text) TO supabase_realtime_admin;


--
-- Name: FUNCTION is_visible_through_filters(columns realtime.wal_column[], filters realtime.user_defined_filter[]); Type: ACL; Schema: realtime; Owner: supabase_admin
--

GRANT ALL ON FUNCTION realtime.is_visible_through_filters(columns realtime.wal_column[], filters realtime.user_defined_filter[]) TO postgres;
GRANT ALL ON FUNCTION realtime.is_visible_through_filters(columns realtime.wal_column[], filters realtime.user_defined_filter[]) TO dashboard_user;
GRANT ALL ON FUNCTION realtime.is_visible_through_filters(columns realtime.wal_column[], filters realtime.user_defined_filter[]) TO anon;
GRANT ALL ON FUNCTION realtime.is_visible_through_filters(columns realtime.wal_column[], filters realtime.user_defined_filter[]) TO authenticated;
GRANT ALL ON FUNCTION realtime.is_visible_through_filters(columns realtime.wal_column[], filters realtime.user_defined_filter[]) TO service_role;
GRANT ALL ON FUNCTION realtime.is_visible_through_filters(columns realtime.wal_column[], filters realtime.user_defined_filter[]) TO supabase_realtime_admin;


--
-- Name: FUNCTION list_changes(publication name, slot_name name, max_changes integer, max_record_bytes integer); Type: ACL; Schema: realtime; Owner: supabase_admin
--

GRANT ALL ON FUNCTION realtime.list_changes(publication name, slot_name name, max_changes integer, max_record_bytes integer) TO postgres;
GRANT ALL ON FUNCTION realtime.list_changes(publication name, slot_name name, max_changes integer, max_record_bytes integer) TO dashboard_user;
GRANT ALL ON FUNCTION realtime.list_changes(publication name, slot_name name, max_changes integer, max_record_bytes integer) TO anon;
GRANT ALL ON FUNCTION realtime.list_changes(publication name, slot_name name, max_changes integer, max_record_bytes integer) TO authenticated;
GRANT ALL ON FUNCTION realtime.list_changes(publication name, slot_name name, max_changes integer, max_record_bytes integer) TO service_role;
GRANT ALL ON FUNCTION realtime.list_changes(publication name, slot_name name, max_changes integer, max_record_bytes integer) TO supabase_realtime_admin;


--
-- Name: FUNCTION quote_wal2json(entity regclass); Type: ACL; Schema: realtime; Owner: supabase_admin
--

GRANT ALL ON FUNCTION realtime.quote_wal2json(entity regclass) TO postgres;
GRANT ALL ON FUNCTION realtime.quote_wal2json(entity regclass) TO dashboard_user;
GRANT ALL ON FUNCTION realtime.quote_wal2json(entity regclass) TO anon;
GRANT ALL ON FUNCTION realtime.quote_wal2json(entity regclass) TO authenticated;
GRANT ALL ON FUNCTION realtime.quote_wal2json(entity regclass) TO service_role;
GRANT ALL ON FUNCTION realtime.quote_wal2json(entity regclass) TO supabase_realtime_admin;


--
-- Name: FUNCTION send(payload jsonb, event text, topic text, private boolean); Type: ACL; Schema: realtime; Owner: supabase_admin
--

GRANT ALL ON FUNCTION realtime.send(payload jsonb, event text, topic text, private boolean) TO postgres;
GRANT ALL ON FUNCTION realtime.send(payload jsonb, event text, topic text, private boolean) TO dashboard_user;


--
-- Name: FUNCTION subscription_check_filters(); Type: ACL; Schema: realtime; Owner: supabase_admin
--

GRANT ALL ON FUNCTION realtime.subscription_check_filters() TO postgres;
GRANT ALL ON FUNCTION realtime.subscription_check_filters() TO dashboard_user;
GRANT ALL ON FUNCTION realtime.subscription_check_filters() TO anon;
GRANT ALL ON FUNCTION realtime.subscription_check_filters() TO authenticated;
GRANT ALL ON FUNCTION realtime.subscription_check_filters() TO service_role;
GRANT ALL ON FUNCTION realtime.subscription_check_filters() TO supabase_realtime_admin;


--
-- Name: FUNCTION to_regrole(role_name text); Type: ACL; Schema: realtime; Owner: supabase_admin
--

GRANT ALL ON FUNCTION realtime.to_regrole(role_name text) TO postgres;
GRANT ALL ON FUNCTION realtime.to_regrole(role_name text) TO dashboard_user;
GRANT ALL ON FUNCTION realtime.to_regrole(role_name text) TO anon;
GRANT ALL ON FUNCTION realtime.to_regrole(role_name text) TO authenticated;
GRANT ALL ON FUNCTION realtime.to_regrole(role_name text) TO service_role;
GRANT ALL ON FUNCTION realtime.to_regrole(role_name text) TO supabase_realtime_admin;


--
-- Name: FUNCTION topic(); Type: ACL; Schema: realtime; Owner: supabase_realtime_admin
--

GRANT ALL ON FUNCTION realtime.topic() TO postgres;
GRANT ALL ON FUNCTION realtime.topic() TO dashboard_user;


--
-- Name: FUNCTION _crypto_aead_det_decrypt(message bytea, additional bytea, key_id bigint, context bytea, nonce bytea); Type: ACL; Schema: vault; Owner: supabase_admin
--

GRANT ALL ON FUNCTION vault._crypto_aead_det_decrypt(message bytea, additional bytea, key_id bigint, context bytea, nonce bytea) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION vault._crypto_aead_det_decrypt(message bytea, additional bytea, key_id bigint, context bytea, nonce bytea) TO service_role;


--
-- Name: FUNCTION create_secret(new_secret text, new_name text, new_description text, new_key_id uuid); Type: ACL; Schema: vault; Owner: supabase_admin
--

GRANT ALL ON FUNCTION vault.create_secret(new_secret text, new_name text, new_description text, new_key_id uuid) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION vault.create_secret(new_secret text, new_name text, new_description text, new_key_id uuid) TO service_role;


--
-- Name: FUNCTION update_secret(secret_id uuid, new_secret text, new_name text, new_description text, new_key_id uuid); Type: ACL; Schema: vault; Owner: supabase_admin
--

GRANT ALL ON FUNCTION vault.update_secret(secret_id uuid, new_secret text, new_name text, new_description text, new_key_id uuid) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION vault.update_secret(secret_id uuid, new_secret text, new_name text, new_description text, new_key_id uuid) TO service_role;


--
-- Name: TABLE audit_log_entries; Type: ACL; Schema: auth; Owner: supabase_auth_admin
--

GRANT ALL ON TABLE auth.audit_log_entries TO dashboard_user;
GRANT INSERT,REFERENCES,DELETE,TRIGGER,TRUNCATE,MAINTAIN,UPDATE ON TABLE auth.audit_log_entries TO postgres;
GRANT SELECT ON TABLE auth.audit_log_entries TO postgres WITH GRANT OPTION;


--
-- Name: TABLE flow_state; Type: ACL; Schema: auth; Owner: supabase_auth_admin
--

GRANT INSERT,REFERENCES,DELETE,TRIGGER,TRUNCATE,MAINTAIN,UPDATE ON TABLE auth.flow_state TO postgres;
GRANT SELECT ON TABLE auth.flow_state TO postgres WITH GRANT OPTION;
GRANT ALL ON TABLE auth.flow_state TO dashboard_user;


--
-- Name: TABLE identities; Type: ACL; Schema: auth; Owner: supabase_auth_admin
--

GRANT INSERT,REFERENCES,DELETE,TRIGGER,TRUNCATE,MAINTAIN,UPDATE ON TABLE auth.identities TO postgres;
GRANT SELECT ON TABLE auth.identities TO postgres WITH GRANT OPTION;
GRANT ALL ON TABLE auth.identities TO dashboard_user;


--
-- Name: TABLE instances; Type: ACL; Schema: auth; Owner: supabase_auth_admin
--

GRANT ALL ON TABLE auth.instances TO dashboard_user;
GRANT INSERT,REFERENCES,DELETE,TRIGGER,TRUNCATE,MAINTAIN,UPDATE ON TABLE auth.instances TO postgres;
GRANT SELECT ON TABLE auth.instances TO postgres WITH GRANT OPTION;


--
-- Name: TABLE mfa_amr_claims; Type: ACL; Schema: auth; Owner: supabase_auth_admin
--

GRANT INSERT,REFERENCES,DELETE,TRIGGER,TRUNCATE,MAINTAIN,UPDATE ON TABLE auth.mfa_amr_claims TO postgres;
GRANT SELECT ON TABLE auth.mfa_amr_claims TO postgres WITH GRANT OPTION;
GRANT ALL ON TABLE auth.mfa_amr_claims TO dashboard_user;


--
-- Name: TABLE mfa_challenges; Type: ACL; Schema: auth; Owner: supabase_auth_admin
--

GRANT INSERT,REFERENCES,DELETE,TRIGGER,TRUNCATE,MAINTAIN,UPDATE ON TABLE auth.mfa_challenges TO postgres;
GRANT SELECT ON TABLE auth.mfa_challenges TO postgres WITH GRANT OPTION;
GRANT ALL ON TABLE auth.mfa_challenges TO dashboard_user;


--
-- Name: TABLE mfa_factors; Type: ACL; Schema: auth; Owner: supabase_auth_admin
--

GRANT INSERT,REFERENCES,DELETE,TRIGGER,TRUNCATE,MAINTAIN,UPDATE ON TABLE auth.mfa_factors TO postgres;
GRANT SELECT ON TABLE auth.mfa_factors TO postgres WITH GRANT OPTION;
GRANT ALL ON TABLE auth.mfa_factors TO dashboard_user;


--
-- Name: TABLE oauth_authorizations; Type: ACL; Schema: auth; Owner: supabase_auth_admin
--

GRANT ALL ON TABLE auth.oauth_authorizations TO postgres;
GRANT ALL ON TABLE auth.oauth_authorizations TO dashboard_user;


--
-- Name: TABLE oauth_clients; Type: ACL; Schema: auth; Owner: supabase_auth_admin
--

GRANT ALL ON TABLE auth.oauth_clients TO postgres;
GRANT ALL ON TABLE auth.oauth_clients TO dashboard_user;


--
-- Name: TABLE oauth_consents; Type: ACL; Schema: auth; Owner: supabase_auth_admin
--

GRANT ALL ON TABLE auth.oauth_consents TO postgres;
GRANT ALL ON TABLE auth.oauth_consents TO dashboard_user;


--
-- Name: TABLE one_time_tokens; Type: ACL; Schema: auth; Owner: supabase_auth_admin
--

GRANT INSERT,REFERENCES,DELETE,TRIGGER,TRUNCATE,MAINTAIN,UPDATE ON TABLE auth.one_time_tokens TO postgres;
GRANT SELECT ON TABLE auth.one_time_tokens TO postgres WITH GRANT OPTION;
GRANT ALL ON TABLE auth.one_time_tokens TO dashboard_user;


--
-- Name: TABLE refresh_tokens; Type: ACL; Schema: auth; Owner: supabase_auth_admin
--

GRANT ALL ON TABLE auth.refresh_tokens TO dashboard_user;
GRANT INSERT,REFERENCES,DELETE,TRIGGER,TRUNCATE,MAINTAIN,UPDATE ON TABLE auth.refresh_tokens TO postgres;
GRANT SELECT ON TABLE auth.refresh_tokens TO postgres WITH GRANT OPTION;


--
-- Name: SEQUENCE refresh_tokens_id_seq; Type: ACL; Schema: auth; Owner: supabase_auth_admin
--

GRANT ALL ON SEQUENCE auth.refresh_tokens_id_seq TO dashboard_user;
GRANT ALL ON SEQUENCE auth.refresh_tokens_id_seq TO postgres;


--
-- Name: TABLE saml_providers; Type: ACL; Schema: auth; Owner: supabase_auth_admin
--

GRANT INSERT,REFERENCES,DELETE,TRIGGER,TRUNCATE,MAINTAIN,UPDATE ON TABLE auth.saml_providers TO postgres;
GRANT SELECT ON TABLE auth.saml_providers TO postgres WITH GRANT OPTION;
GRANT ALL ON TABLE auth.saml_providers TO dashboard_user;


--
-- Name: TABLE saml_relay_states; Type: ACL; Schema: auth; Owner: supabase_auth_admin
--

GRANT INSERT,REFERENCES,DELETE,TRIGGER,TRUNCATE,MAINTAIN,UPDATE ON TABLE auth.saml_relay_states TO postgres;
GRANT SELECT ON TABLE auth.saml_relay_states TO postgres WITH GRANT OPTION;
GRANT ALL ON TABLE auth.saml_relay_states TO dashboard_user;


--
-- Name: TABLE sessions; Type: ACL; Schema: auth; Owner: supabase_auth_admin
--

GRANT INSERT,REFERENCES,DELETE,TRIGGER,TRUNCATE,MAINTAIN,UPDATE ON TABLE auth.sessions TO postgres;
GRANT SELECT ON TABLE auth.sessions TO postgres WITH GRANT OPTION;
GRANT ALL ON TABLE auth.sessions TO dashboard_user;


--
-- Name: TABLE sso_domains; Type: ACL; Schema: auth; Owner: supabase_auth_admin
--

GRANT INSERT,REFERENCES,DELETE,TRIGGER,TRUNCATE,MAINTAIN,UPDATE ON TABLE auth.sso_domains TO postgres;
GRANT SELECT ON TABLE auth.sso_domains TO postgres WITH GRANT OPTION;
GRANT ALL ON TABLE auth.sso_domains TO dashboard_user;


--
-- Name: TABLE sso_providers; Type: ACL; Schema: auth; Owner: supabase_auth_admin
--

GRANT INSERT,REFERENCES,DELETE,TRIGGER,TRUNCATE,MAINTAIN,UPDATE ON TABLE auth.sso_providers TO postgres;
GRANT SELECT ON TABLE auth.sso_providers TO postgres WITH GRANT OPTION;
GRANT ALL ON TABLE auth.sso_providers TO dashboard_user;


--
-- Name: TABLE users; Type: ACL; Schema: auth; Owner: supabase_auth_admin
--

GRANT ALL ON TABLE auth.users TO dashboard_user;
GRANT INSERT,REFERENCES,DELETE,TRIGGER,TRUNCATE,MAINTAIN,UPDATE ON TABLE auth.users TO postgres;
GRANT SELECT ON TABLE auth.users TO postgres WITH GRANT OPTION;


--
-- Name: TABLE pg_stat_statements; Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON TABLE extensions.pg_stat_statements FROM postgres;
GRANT ALL ON TABLE extensions.pg_stat_statements TO postgres WITH GRANT OPTION;
GRANT ALL ON TABLE extensions.pg_stat_statements TO dashboard_user;


--
-- Name: TABLE pg_stat_statements_info; Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON TABLE extensions.pg_stat_statements_info FROM postgres;
GRANT ALL ON TABLE extensions.pg_stat_statements_info TO postgres WITH GRANT OPTION;
GRANT ALL ON TABLE extensions.pg_stat_statements_info TO dashboard_user;


--
-- Name: TABLE bookings; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.bookings TO anon;
GRANT ALL ON TABLE public.bookings TO authenticated;
GRANT ALL ON TABLE public.bookings TO service_role;


--
-- Name: SEQUENCE bookings_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.bookings_id_seq TO anon;
GRANT ALL ON SEQUENCE public.bookings_id_seq TO authenticated;
GRANT ALL ON SEQUENCE public.bookings_id_seq TO service_role;


--
-- Name: TABLE combo_items; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.combo_items TO anon;
GRANT ALL ON TABLE public.combo_items TO authenticated;
GRANT ALL ON TABLE public.combo_items TO service_role;


--
-- Name: TABLE notifications; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.notifications TO anon;
GRANT ALL ON TABLE public.notifications TO authenticated;
GRANT ALL ON TABLE public.notifications TO service_role;


--
-- Name: SEQUENCE notifications_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.notifications_id_seq TO anon;
GRANT ALL ON SEQUENCE public.notifications_id_seq TO authenticated;
GRANT ALL ON SEQUENCE public.notifications_id_seq TO service_role;


--
-- Name: TABLE payouts; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.payouts TO anon;
GRANT ALL ON TABLE public.payouts TO authenticated;
GRANT ALL ON TABLE public.payouts TO service_role;


--
-- Name: SEQUENCE payouts_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.payouts_id_seq TO anon;
GRANT ALL ON SEQUENCE public.payouts_id_seq TO authenticated;
GRANT ALL ON SEQUENCE public.payouts_id_seq TO service_role;


--
-- Name: TABLE platform_payouts; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.platform_payouts TO anon;
GRANT ALL ON TABLE public.platform_payouts TO authenticated;
GRANT ALL ON TABLE public.platform_payouts TO service_role;


--
-- Name: SEQUENCE platform_payouts_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.platform_payouts_id_seq TO anon;
GRANT ALL ON SEQUENCE public.platform_payouts_id_seq TO authenticated;
GRANT ALL ON SEQUENCE public.platform_payouts_id_seq TO service_role;


--
-- Name: TABLE reviews; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.reviews TO anon;
GRANT ALL ON TABLE public.reviews TO authenticated;
GRANT ALL ON TABLE public.reviews TO service_role;


--
-- Name: SEQUENCE reviews_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.reviews_id_seq TO anon;
GRANT ALL ON SEQUENCE public.reviews_id_seq TO authenticated;
GRANT ALL ON SEQUENCE public.reviews_id_seq TO service_role;


--
-- Name: TABLE service_combos; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.service_combos TO anon;
GRANT ALL ON TABLE public.service_combos TO authenticated;
GRANT ALL ON TABLE public.service_combos TO service_role;


--
-- Name: SEQUENCE service_combos_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.service_combos_id_seq TO anon;
GRANT ALL ON SEQUENCE public.service_combos_id_seq TO authenticated;
GRANT ALL ON SEQUENCE public.service_combos_id_seq TO service_role;


--
-- Name: TABLE services; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.services TO anon;
GRANT ALL ON TABLE public.services TO authenticated;
GRANT ALL ON TABLE public.services TO service_role;


--
-- Name: SEQUENCE services_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.services_id_seq TO anon;
GRANT ALL ON SEQUENCE public.services_id_seq TO authenticated;
GRANT ALL ON SEQUENCE public.services_id_seq TO service_role;


--
-- Name: SEQUENCE sub_service_items_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.sub_service_items_id_seq TO anon;
GRANT ALL ON SEQUENCE public.sub_service_items_id_seq TO authenticated;
GRANT ALL ON SEQUENCE public.sub_service_items_id_seq TO service_role;


--
-- Name: TABLE sub_service_items; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.sub_service_items TO anon;
GRANT ALL ON TABLE public.sub_service_items TO authenticated;
GRANT ALL ON TABLE public.sub_service_items TO service_role;


--
-- Name: TABLE sub_services; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.sub_services TO anon;
GRANT ALL ON TABLE public.sub_services TO authenticated;
GRANT ALL ON TABLE public.sub_services TO service_role;


--
-- Name: SEQUENCE sub_services_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.sub_services_id_seq TO anon;
GRANT ALL ON SEQUENCE public.sub_services_id_seq TO authenticated;
GRANT ALL ON SEQUENCE public.sub_services_id_seq TO service_role;


--
-- Name: TABLE transactions; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.transactions TO anon;
GRANT ALL ON TABLE public.transactions TO authenticated;
GRANT ALL ON TABLE public.transactions TO service_role;


--
-- Name: SEQUENCE transactions_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.transactions_id_seq TO anon;
GRANT ALL ON SEQUENCE public.transactions_id_seq TO authenticated;
GRANT ALL ON SEQUENCE public.transactions_id_seq TO service_role;


--
-- Name: TABLE user_coupon_usage; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.user_coupon_usage TO anon;
GRANT ALL ON TABLE public.user_coupon_usage TO authenticated;
GRANT ALL ON TABLE public.user_coupon_usage TO service_role;


--
-- Name: SEQUENCE user_coupon_usage_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.user_coupon_usage_id_seq TO anon;
GRANT ALL ON SEQUENCE public.user_coupon_usage_id_seq TO authenticated;
GRANT ALL ON SEQUENCE public.user_coupon_usage_id_seq TO service_role;


--
-- Name: TABLE users; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.users TO anon;
GRANT ALL ON TABLE public.users TO authenticated;
GRANT ALL ON TABLE public.users TO service_role;


--
-- Name: SEQUENCE users_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.users_id_seq TO anon;
GRANT ALL ON SEQUENCE public.users_id_seq TO authenticated;
GRANT ALL ON SEQUENCE public.users_id_seq TO service_role;


--
-- Name: TABLE wallets; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.wallets TO anon;
GRANT ALL ON TABLE public.wallets TO authenticated;
GRANT ALL ON TABLE public.wallets TO service_role;


--
-- Name: SEQUENCE wallets_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.wallets_id_seq TO anon;
GRANT ALL ON SEQUENCE public.wallets_id_seq TO authenticated;
GRANT ALL ON SEQUENCE public.wallets_id_seq TO service_role;


--
-- Name: TABLE worker_availability; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.worker_availability TO anon;
GRANT ALL ON TABLE public.worker_availability TO authenticated;
GRANT ALL ON TABLE public.worker_availability TO service_role;


--
-- Name: SEQUENCE worker_availability_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.worker_availability_id_seq TO anon;
GRANT ALL ON SEQUENCE public.worker_availability_id_seq TO authenticated;
GRANT ALL ON SEQUENCE public.worker_availability_id_seq TO service_role;


--
-- Name: TABLE worker_keys; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.worker_keys TO anon;
GRANT ALL ON TABLE public.worker_keys TO authenticated;
GRANT ALL ON TABLE public.worker_keys TO service_role;


--
-- Name: SEQUENCE worker_keys_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.worker_keys_id_seq TO anon;
GRANT ALL ON SEQUENCE public.worker_keys_id_seq TO authenticated;
GRANT ALL ON SEQUENCE public.worker_keys_id_seq TO service_role;


--
-- Name: TABLE worker_offers; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.worker_offers TO anon;
GRANT ALL ON TABLE public.worker_offers TO authenticated;
GRANT ALL ON TABLE public.worker_offers TO service_role;


--
-- Name: SEQUENCE worker_offers_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.worker_offers_id_seq TO anon;
GRANT ALL ON SEQUENCE public.worker_offers_id_seq TO authenticated;
GRANT ALL ON SEQUENCE public.worker_offers_id_seq TO service_role;


--
-- Name: TABLE worker_profiles; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.worker_profiles TO anon;
GRANT ALL ON TABLE public.worker_profiles TO authenticated;
GRANT ALL ON TABLE public.worker_profiles TO service_role;


--
-- Name: TABLE worker_services; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.worker_services TO anon;
GRANT ALL ON TABLE public.worker_services TO authenticated;
GRANT ALL ON TABLE public.worker_services TO service_role;


--
-- Name: TABLE worker_sub_service_items; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.worker_sub_service_items TO anon;
GRANT ALL ON TABLE public.worker_sub_service_items TO authenticated;
GRANT ALL ON TABLE public.worker_sub_service_items TO service_role;


--
-- Name: TABLE messages; Type: ACL; Schema: realtime; Owner: supabase_realtime_admin
--

GRANT ALL ON TABLE realtime.messages TO postgres;
GRANT ALL ON TABLE realtime.messages TO dashboard_user;
GRANT SELECT,INSERT,UPDATE ON TABLE realtime.messages TO anon;
GRANT SELECT,INSERT,UPDATE ON TABLE realtime.messages TO authenticated;
GRANT SELECT,INSERT,UPDATE ON TABLE realtime.messages TO service_role;


--
-- Name: TABLE schema_migrations; Type: ACL; Schema: realtime; Owner: supabase_admin
--

GRANT ALL ON TABLE realtime.schema_migrations TO postgres;
GRANT ALL ON TABLE realtime.schema_migrations TO dashboard_user;
GRANT SELECT ON TABLE realtime.schema_migrations TO anon;
GRANT SELECT ON TABLE realtime.schema_migrations TO authenticated;
GRANT SELECT ON TABLE realtime.schema_migrations TO service_role;
GRANT ALL ON TABLE realtime.schema_migrations TO supabase_realtime_admin;


--
-- Name: TABLE subscription; Type: ACL; Schema: realtime; Owner: supabase_admin
--

GRANT ALL ON TABLE realtime.subscription TO postgres;
GRANT ALL ON TABLE realtime.subscription TO dashboard_user;
GRANT SELECT ON TABLE realtime.subscription TO anon;
GRANT SELECT ON TABLE realtime.subscription TO authenticated;
GRANT SELECT ON TABLE realtime.subscription TO service_role;
GRANT ALL ON TABLE realtime.subscription TO supabase_realtime_admin;


--
-- Name: SEQUENCE subscription_id_seq; Type: ACL; Schema: realtime; Owner: supabase_admin
--

GRANT ALL ON SEQUENCE realtime.subscription_id_seq TO postgres;
GRANT ALL ON SEQUENCE realtime.subscription_id_seq TO dashboard_user;
GRANT USAGE ON SEQUENCE realtime.subscription_id_seq TO anon;
GRANT USAGE ON SEQUENCE realtime.subscription_id_seq TO authenticated;
GRANT USAGE ON SEQUENCE realtime.subscription_id_seq TO service_role;
GRANT ALL ON SEQUENCE realtime.subscription_id_seq TO supabase_realtime_admin;


--
-- Name: TABLE buckets; Type: ACL; Schema: storage; Owner: supabase_storage_admin
--

GRANT ALL ON TABLE storage.buckets TO anon;
GRANT ALL ON TABLE storage.buckets TO authenticated;
GRANT ALL ON TABLE storage.buckets TO service_role;
GRANT ALL ON TABLE storage.buckets TO postgres WITH GRANT OPTION;


--
-- Name: TABLE buckets_analytics; Type: ACL; Schema: storage; Owner: supabase_storage_admin
--

GRANT ALL ON TABLE storage.buckets_analytics TO service_role;
GRANT ALL ON TABLE storage.buckets_analytics TO authenticated;
GRANT ALL ON TABLE storage.buckets_analytics TO anon;


--
-- Name: TABLE objects; Type: ACL; Schema: storage; Owner: supabase_storage_admin
--

GRANT ALL ON TABLE storage.objects TO anon;
GRANT ALL ON TABLE storage.objects TO authenticated;
GRANT ALL ON TABLE storage.objects TO service_role;
GRANT ALL ON TABLE storage.objects TO postgres WITH GRANT OPTION;


--
-- Name: TABLE prefixes; Type: ACL; Schema: storage; Owner: supabase_storage_admin
--

GRANT ALL ON TABLE storage.prefixes TO service_role;
GRANT ALL ON TABLE storage.prefixes TO authenticated;
GRANT ALL ON TABLE storage.prefixes TO anon;


--
-- Name: TABLE s3_multipart_uploads; Type: ACL; Schema: storage; Owner: supabase_storage_admin
--

GRANT ALL ON TABLE storage.s3_multipart_uploads TO service_role;
GRANT SELECT ON TABLE storage.s3_multipart_uploads TO authenticated;
GRANT SELECT ON TABLE storage.s3_multipart_uploads TO anon;


--
-- Name: TABLE s3_multipart_uploads_parts; Type: ACL; Schema: storage; Owner: supabase_storage_admin
--

GRANT ALL ON TABLE storage.s3_multipart_uploads_parts TO service_role;
GRANT SELECT ON TABLE storage.s3_multipart_uploads_parts TO authenticated;
GRANT SELECT ON TABLE storage.s3_multipart_uploads_parts TO anon;


--
-- Name: TABLE secrets; Type: ACL; Schema: vault; Owner: supabase_admin
--

GRANT SELECT,REFERENCES,DELETE,TRUNCATE ON TABLE vault.secrets TO postgres WITH GRANT OPTION;
GRANT SELECT,DELETE ON TABLE vault.secrets TO service_role;


--
-- Name: TABLE decrypted_secrets; Type: ACL; Schema: vault; Owner: supabase_admin
--

GRANT SELECT,REFERENCES,DELETE,TRUNCATE ON TABLE vault.decrypted_secrets TO postgres WITH GRANT OPTION;
GRANT SELECT,DELETE ON TABLE vault.decrypted_secrets TO service_role;


--
-- Name: DEFAULT PRIVILEGES FOR SEQUENCES; Type: DEFAULT ACL; Schema: auth; Owner: supabase_auth_admin
--

ALTER DEFAULT PRIVILEGES FOR ROLE supabase_auth_admin IN SCHEMA auth GRANT ALL ON SEQUENCES TO postgres;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_auth_admin IN SCHEMA auth GRANT ALL ON SEQUENCES TO dashboard_user;


--
-- Name: DEFAULT PRIVILEGES FOR FUNCTIONS; Type: DEFAULT ACL; Schema: auth; Owner: supabase_auth_admin
--

ALTER DEFAULT PRIVILEGES FOR ROLE supabase_auth_admin IN SCHEMA auth GRANT ALL ON FUNCTIONS TO postgres;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_auth_admin IN SCHEMA auth GRANT ALL ON FUNCTIONS TO dashboard_user;


--
-- Name: DEFAULT PRIVILEGES FOR TABLES; Type: DEFAULT ACL; Schema: auth; Owner: supabase_auth_admin
--

ALTER DEFAULT PRIVILEGES FOR ROLE supabase_auth_admin IN SCHEMA auth GRANT ALL ON TABLES TO postgres;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_auth_admin IN SCHEMA auth GRANT ALL ON TABLES TO dashboard_user;


--
-- Name: DEFAULT PRIVILEGES FOR SEQUENCES; Type: DEFAULT ACL; Schema: extensions; Owner: supabase_admin
--

ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA extensions GRANT ALL ON SEQUENCES TO postgres WITH GRANT OPTION;


--
-- Name: DEFAULT PRIVILEGES FOR FUNCTIONS; Type: DEFAULT ACL; Schema: extensions; Owner: supabase_admin
--

ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA extensions GRANT ALL ON FUNCTIONS TO postgres WITH GRANT OPTION;


--
-- Name: DEFAULT PRIVILEGES FOR TABLES; Type: DEFAULT ACL; Schema: extensions; Owner: supabase_admin
--

ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA extensions GRANT ALL ON TABLES TO postgres WITH GRANT OPTION;


--
-- Name: DEFAULT PRIVILEGES FOR SEQUENCES; Type: DEFAULT ACL; Schema: graphql; Owner: supabase_admin
--

ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA graphql GRANT ALL ON SEQUENCES TO postgres;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA graphql GRANT ALL ON SEQUENCES TO anon;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA graphql GRANT ALL ON SEQUENCES TO authenticated;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA graphql GRANT ALL ON SEQUENCES TO service_role;


--
-- Name: DEFAULT PRIVILEGES FOR FUNCTIONS; Type: DEFAULT ACL; Schema: graphql; Owner: supabase_admin
--

ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA graphql GRANT ALL ON FUNCTIONS TO postgres;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA graphql GRANT ALL ON FUNCTIONS TO anon;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA graphql GRANT ALL ON FUNCTIONS TO authenticated;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA graphql GRANT ALL ON FUNCTIONS TO service_role;


--
-- Name: DEFAULT PRIVILEGES FOR TABLES; Type: DEFAULT ACL; Schema: graphql; Owner: supabase_admin
--

ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA graphql GRANT ALL ON TABLES TO postgres;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA graphql GRANT ALL ON TABLES TO anon;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA graphql GRANT ALL ON TABLES TO authenticated;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA graphql GRANT ALL ON TABLES TO service_role;


--
-- Name: DEFAULT PRIVILEGES FOR SEQUENCES; Type: DEFAULT ACL; Schema: graphql_public; Owner: supabase_admin
--

ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA graphql_public GRANT ALL ON SEQUENCES TO postgres;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA graphql_public GRANT ALL ON SEQUENCES TO anon;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA graphql_public GRANT ALL ON SEQUENCES TO authenticated;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA graphql_public GRANT ALL ON SEQUENCES TO service_role;


--
-- Name: DEFAULT PRIVILEGES FOR FUNCTIONS; Type: DEFAULT ACL; Schema: graphql_public; Owner: supabase_admin
--

ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA graphql_public GRANT ALL ON FUNCTIONS TO postgres;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA graphql_public GRANT ALL ON FUNCTIONS TO anon;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA graphql_public GRANT ALL ON FUNCTIONS TO authenticated;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA graphql_public GRANT ALL ON FUNCTIONS TO service_role;


--
-- Name: DEFAULT PRIVILEGES FOR TABLES; Type: DEFAULT ACL; Schema: graphql_public; Owner: supabase_admin
--

ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA graphql_public GRANT ALL ON TABLES TO postgres;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA graphql_public GRANT ALL ON TABLES TO anon;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA graphql_public GRANT ALL ON TABLES TO authenticated;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA graphql_public GRANT ALL ON TABLES TO service_role;


--
-- Name: DEFAULT PRIVILEGES FOR SEQUENCES; Type: DEFAULT ACL; Schema: public; Owner: postgres
--

ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA public GRANT ALL ON SEQUENCES TO postgres;
ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA public GRANT ALL ON SEQUENCES TO anon;
ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA public GRANT ALL ON SEQUENCES TO authenticated;
ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA public GRANT ALL ON SEQUENCES TO service_role;


--
-- Name: DEFAULT PRIVILEGES FOR SEQUENCES; Type: DEFAULT ACL; Schema: public; Owner: supabase_admin
--

ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA public GRANT ALL ON SEQUENCES TO postgres;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA public GRANT ALL ON SEQUENCES TO anon;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA public GRANT ALL ON SEQUENCES TO authenticated;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA public GRANT ALL ON SEQUENCES TO service_role;


--
-- Name: DEFAULT PRIVILEGES FOR FUNCTIONS; Type: DEFAULT ACL; Schema: public; Owner: postgres
--

ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA public GRANT ALL ON FUNCTIONS TO postgres;
ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA public GRANT ALL ON FUNCTIONS TO anon;
ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA public GRANT ALL ON FUNCTIONS TO authenticated;
ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA public GRANT ALL ON FUNCTIONS TO service_role;


--
-- Name: DEFAULT PRIVILEGES FOR FUNCTIONS; Type: DEFAULT ACL; Schema: public; Owner: supabase_admin
--

ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA public GRANT ALL ON FUNCTIONS TO postgres;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA public GRANT ALL ON FUNCTIONS TO anon;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA public GRANT ALL ON FUNCTIONS TO authenticated;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA public GRANT ALL ON FUNCTIONS TO service_role;


--
-- Name: DEFAULT PRIVILEGES FOR TABLES; Type: DEFAULT ACL; Schema: public; Owner: postgres
--

ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA public GRANT ALL ON TABLES TO postgres;
ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA public GRANT ALL ON TABLES TO anon;
ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA public GRANT ALL ON TABLES TO authenticated;
ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA public GRANT ALL ON TABLES TO service_role;


--
-- Name: DEFAULT PRIVILEGES FOR TABLES; Type: DEFAULT ACL; Schema: public; Owner: supabase_admin
--

ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA public GRANT ALL ON TABLES TO postgres;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA public GRANT ALL ON TABLES TO anon;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA public GRANT ALL ON TABLES TO authenticated;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA public GRANT ALL ON TABLES TO service_role;


--
-- Name: DEFAULT PRIVILEGES FOR SEQUENCES; Type: DEFAULT ACL; Schema: realtime; Owner: supabase_admin
--

ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA realtime GRANT ALL ON SEQUENCES TO postgres;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA realtime GRANT ALL ON SEQUENCES TO dashboard_user;


--
-- Name: DEFAULT PRIVILEGES FOR FUNCTIONS; Type: DEFAULT ACL; Schema: realtime; Owner: supabase_admin
--

ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA realtime GRANT ALL ON FUNCTIONS TO postgres;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA realtime GRANT ALL ON FUNCTIONS TO dashboard_user;


--
-- Name: DEFAULT PRIVILEGES FOR TABLES; Type: DEFAULT ACL; Schema: realtime; Owner: supabase_admin
--

ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA realtime GRANT ALL ON TABLES TO postgres;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA realtime GRANT ALL ON TABLES TO dashboard_user;


--
-- Name: DEFAULT PRIVILEGES FOR SEQUENCES; Type: DEFAULT ACL; Schema: storage; Owner: postgres
--

ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA storage GRANT ALL ON SEQUENCES TO postgres;
ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA storage GRANT ALL ON SEQUENCES TO anon;
ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA storage GRANT ALL ON SEQUENCES TO authenticated;
ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA storage GRANT ALL ON SEQUENCES TO service_role;


--
-- Name: DEFAULT PRIVILEGES FOR FUNCTIONS; Type: DEFAULT ACL; Schema: storage; Owner: postgres
--

ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA storage GRANT ALL ON FUNCTIONS TO postgres;
ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA storage GRANT ALL ON FUNCTIONS TO anon;
ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA storage GRANT ALL ON FUNCTIONS TO authenticated;
ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA storage GRANT ALL ON FUNCTIONS TO service_role;


--
-- Name: DEFAULT PRIVILEGES FOR TABLES; Type: DEFAULT ACL; Schema: storage; Owner: postgres
--

ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA storage GRANT ALL ON TABLES TO postgres;
ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA storage GRANT ALL ON TABLES TO anon;
ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA storage GRANT ALL ON TABLES TO authenticated;
ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA storage GRANT ALL ON TABLES TO service_role;


--
-- Name: issue_graphql_placeholder; Type: EVENT TRIGGER; Schema: -; Owner: supabase_admin
--

CREATE EVENT TRIGGER issue_graphql_placeholder ON sql_drop
         WHEN TAG IN ('DROP EXTENSION')
   EXECUTE FUNCTION extensions.set_graphql_placeholder();


ALTER EVENT TRIGGER issue_graphql_placeholder OWNER TO supabase_admin;

--
-- Name: issue_pg_cron_access; Type: EVENT TRIGGER; Schema: -; Owner: supabase_admin
--

CREATE EVENT TRIGGER issue_pg_cron_access ON ddl_command_end
         WHEN TAG IN ('CREATE EXTENSION')
   EXECUTE FUNCTION extensions.grant_pg_cron_access();


ALTER EVENT TRIGGER issue_pg_cron_access OWNER TO supabase_admin;

--
-- Name: issue_pg_graphql_access; Type: EVENT TRIGGER; Schema: -; Owner: supabase_admin
--

CREATE EVENT TRIGGER issue_pg_graphql_access ON ddl_command_end
         WHEN TAG IN ('CREATE FUNCTION')
   EXECUTE FUNCTION extensions.grant_pg_graphql_access();


ALTER EVENT TRIGGER issue_pg_graphql_access OWNER TO supabase_admin;

--
-- Name: issue_pg_net_access; Type: EVENT TRIGGER; Schema: -; Owner: supabase_admin
--

CREATE EVENT TRIGGER issue_pg_net_access ON ddl_command_end
         WHEN TAG IN ('CREATE EXTENSION')
   EXECUTE FUNCTION extensions.grant_pg_net_access();


ALTER EVENT TRIGGER issue_pg_net_access OWNER TO supabase_admin;

--
-- Name: pgrst_ddl_watch; Type: EVENT TRIGGER; Schema: -; Owner: supabase_admin
--

CREATE EVENT TRIGGER pgrst_ddl_watch ON ddl_command_end
   EXECUTE FUNCTION extensions.pgrst_ddl_watch();


ALTER EVENT TRIGGER pgrst_ddl_watch OWNER TO supabase_admin;

--
-- Name: pgrst_drop_watch; Type: EVENT TRIGGER; Schema: -; Owner: supabase_admin
--

CREATE EVENT TRIGGER pgrst_drop_watch ON sql_drop
   EXECUTE FUNCTION extensions.pgrst_drop_watch();


ALTER EVENT TRIGGER pgrst_drop_watch OWNER TO supabase_admin;

--
-- PostgreSQL database dump complete
--

