SELECT current_database() AS banco, column_name AS coluna,
       data_type AS tipo, character_maximum_length AS tamanho,
       is_nullable AS permite_nulo, column_default AS valor_padrao
FROM information_schema.columns
WHERE table_schema = 'public' AND table_name = 'plan_service'
ORDER BY ordinal_position;

SELECT conname AS restricao, pg_get_constraintdef(oid) AS definicao
FROM pg_constraint
WHERE conrelid = 'public.plan_service'::regclass
ORDER BY conname;

SELECT indexname AS indice, indexdef AS definicao
FROM pg_indexes
WHERE schemaname = 'public' AND tablename = 'plan_service'
ORDER BY indexname;
