SELECT table_name FROM information_schema.tables
WHERE table_schema='public' AND table_type='BASE TABLE' ORDER BY table_name;
SELECT t.relname AS tabela, c.conname AS restricao, pg_get_constraintdef(c.oid) AS definicao
FROM pg_constraint c JOIN pg_class t ON t.oid=c.conrelid
JOIN pg_namespace n ON n.oid=t.relnamespace
WHERE n.nspname='public' AND c.contype IN ('p','f','u','c') ORDER BY t.relname,c.conname;
SELECT migration,batch FROM migrations ORDER BY id;
