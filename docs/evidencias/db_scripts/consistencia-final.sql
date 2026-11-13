SELECT 'veiculos_sem_cliente' AS verificacao, count(*) AS problemas
FROM vehicles v LEFT JOIN users u ON u.id=v.user_id WHERE u.id IS NULL OR u.role <> 'cliente'
UNION ALL SELECT 'propostas_vinculos_invalidos', count(*)
FROM proposals p LEFT JOIN users u ON u.id=p.user_id LEFT JOIN vehicles v ON v.id=p.vehicle_id LEFT JOIN plans pl ON pl.id=p.plan_id
WHERE u.id IS NULL OR v.id IS NULL OR pl.id IS NULL OR v.user_id <> p.user_id
UNION ALL SELECT 'pivot_duplicado', count(*) FROM (SELECT plan_id,service_id FROM plan_service GROUP BY plan_id,service_id HAVING count(*)>1) d
UNION ALL SELECT 'pivot_orfao', count(*) FROM plan_service ps LEFT JOIN plans p ON p.id=ps.plan_id LEFT JOIN services s ON s.id=ps.service_id WHERE p.id IS NULL OR s.id IS NULL
UNION ALL SELECT 'contrato_duplicado', count(*) FROM (SELECT proposal_id FROM insurance_contracts GROUP BY proposal_id HAVING count(*)>1) d
UNION ALL SELECT 'contrato_status_divergente', count(*) FROM insurance_contracts c LEFT JOIN proposals p ON p.id=c.proposal_id WHERE p.id IS NULL OR p.status <> 'contracted'
UNION ALL SELECT 'contratada_sem_contrato', count(*) FROM proposals p LEFT JOIN insurance_contracts c ON c.proposal_id=p.id WHERE p.status='contracted' AND c.id IS NULL
UNION ALL SELECT 'envio_sem_historico', count(*) FROM proposals WHERE status<>'draft' AND (submitted_at IS NULL OR services_snapshot IS NULL OR jsonb_typeof(services_snapshot)<>'array' OR services_snapshot='[]'::jsonb)
UNION ALL SELECT 'analise_incompleta', count(*) FROM proposals WHERE status IN ('approved','rejected','contracted') AND (reviewed_by IS NULL OR reviewed_at IS NULL OR (status='rejected' AND coalesce(trim(review_notes),'')=''))
UNION ALL SELECT 'assistencia_incoerente', count(*) FROM service_requests sr JOIN insurance_contracts c ON c.id=sr.insurance_contract_id JOIN proposals p ON p.id=c.proposal_id
WHERE NOT (p.services_snapshot @> jsonb_build_array(jsonb_build_object('id',sr.service_id)))
 OR ((sr.created_at AT TIME ZONE 'UTC') AT TIME ZONE 'America/Sao_Paulo')::date < c.starts_on
 OR ((sr.created_at AT TIME ZONE 'UTC') AT TIME ZONE 'America/Sao_Paulo')::date >= c.ends_on
 OR (sr.status='open' AND (sr.assigned_to IS NOT NULL OR sr.completed_at IS NOT NULL))
 OR (sr.status IN ('in_progress','completed') AND sr.assigned_to IS NULL)
 OR (sr.status='completed' AND (sr.completed_at IS NULL OR sr.completed_at < sr.created_at OR coalesce(trim(sr.response),'')=''));
