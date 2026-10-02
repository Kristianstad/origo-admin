-- Create the application database only when it does not already exist.
SELECT 'CREATE DATABASE origo'
WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = 'origo')\gexec

SELECT 'CREATE ROLE origo_updated_readonly LOGIN'
WHERE NOT EXISTS (SELECT FROM pg_roles WHERE rolname = 'origo_updated_readonly')\gexec

GRANT CONNECT ON DATABASE origo TO origo_updated_readonly;
GRANT pg_read_all_data TO origo_updated_readonly;
ALTER ROLE origo_updated_readonly IN DATABASE origo SET default_transaction_read_only = on;
