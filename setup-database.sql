-- Listerino/ListKit PostgreSQL Database Setup Script
-- Run this script as the postgres superuser to set up the database

-- Create the database user
CREATE USER listerino WITH PASSWORD 'localdev123';

-- Create the database
CREATE DATABASE listerino_local OWNER listerino;

-- Grant all privileges
GRANT ALL PRIVILEGES ON DATABASE listerino_local TO listerino;

-- Connect to the new database
\c listerino_local

-- Grant schema permissions
GRANT ALL ON SCHEMA public TO listerino;

-- Enable required extensions
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pgcrypto";

-- Optional: Enable PostGIS for geospatial features (if needed)
-- CREATE EXTENSION IF NOT EXISTS postgis;
-- CREATE EXTENSION IF NOT EXISTS postgis_topology;

-- Create a test connection query
SELECT 'Database setup completed successfully!' as message;

-- Display connection information
\echo ''
\echo 'Database Configuration:'
\echo '----------------------'
\echo 'Host: localhost'
\echo 'Port: 5432'
\echo 'Database: listerino_local'
\echo 'Username: listerino'
\echo 'Password: localdev123'
\echo ''
\echo 'Test connection with:'
\echo 'psql -h localhost -U listerino -d listerino_local'