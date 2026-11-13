SELECT current_database() AS banco,
       column_name AS coluna,
       data_type AS tipo,
       character_maximum_length AS tamanho,
       is_nullable AS permite_nulo
FROM information_schema.columns
WHERE table_schema = 'public'
  AND table_name = 'password_reset_tokens'
ORDER BY ordinal_position;

SELECT current_database() AS banco,
       conname AS restricao,
       pg_get_constraintdef(oid) AS definicao
FROM pg_constraint
WHERE conrelid = 'public.password_reset_tokens'::regclass
ORDER BY conname;
