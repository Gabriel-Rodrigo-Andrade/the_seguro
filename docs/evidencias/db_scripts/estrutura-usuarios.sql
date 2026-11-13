-- Somente metadados: não consulta senhas, tokens ou dados de contas.
SELECT current_database() AS banco,
       table_name AS tabela,
       column_name AS coluna,
       data_type AS tipo,
       character_maximum_length AS tamanho,
       is_nullable AS permite_nulo,
       column_default AS valor_padrao
FROM information_schema.columns
WHERE table_schema = 'public'
  AND table_name IN ('users', 'migrations')
ORDER BY table_name, ordinal_position;

SELECT current_database() AS banco,
       relation.relname AS tabela,
       constraint_info.conname AS restricao,
       pg_get_constraintdef(constraint_info.oid) AS definicao
FROM pg_constraint AS constraint_info
JOIN pg_class AS relation ON relation.oid = constraint_info.conrelid
JOIN pg_namespace AS namespace ON namespace.oid = relation.relnamespace
WHERE namespace.nspname = 'public'
  AND relation.relname IN ('users', 'migrations')
ORDER BY relation.relname, constraint_info.conname;
