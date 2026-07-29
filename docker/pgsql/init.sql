-- Astra OS PostgreSQL initialization script
-- This runs on first database creation

-- Create extensions
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pgcrypto";
CREATE EXTENSION IF NOT EXISTS "pg_stat_statements";

-- Create schema for Astra OS
CREATE SCHEMA IF NOT EXISTS astra_os;

-- Set default schema
SET search_path TO astra_os, public;

-- Create roles if not exists (adjust permissions per environment)
DO $$
BEGIN
    IF NOT EXISTS (SELECT FROM pg_roles WHERE rolname = 'astra_reader') THEN
        CREATE ROLE astra_reader;
    END IF;
    IF NOT EXISTS (SELECT FROM pg_roles WHERE rolname = 'astra_writer') THEN
        CREATE ROLE astra_writer;
    END IF;
END
$$;

-- Grant permissions
GRANT USAGE ON SCHEMA astra_os TO astra_reader;
GRANT USAGE ON SCHEMA astra_os TO astra_writer;
GRANT SELECT ON ALL TABLES IN SCHEMA astra_os TO astra_reader;
GRANT SELECT, INSERT, UPDATE, DELETE ON ALL TABLES IN SCHEMA astra_os TO astra_writer;

-- Create performance indexes
CREATE INDEX IF NOT EXISTS idx_gin_search ON audit_logs USING gin (to_tsvector('english', COALESCE(action, '') || ' ' || COALESCE(entity_type, '')));
