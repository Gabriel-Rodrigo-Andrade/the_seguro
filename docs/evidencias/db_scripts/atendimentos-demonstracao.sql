SELECT sr.id, u.name AS cliente, v.license_plate AS placa, c.contract_number,
       item.value->>'name' AS servico_contratado, sr.status,
       a.name AS responsavel, sr.description, sr.location,
       sr.created_at, sr.completed_at, sr.response,
       c.starts_on, c.ends_on AS fim_exclusivo
FROM service_requests sr
JOIN insurance_contracts c ON c.id = sr.insurance_contract_id
JOIN proposals p ON p.id = c.proposal_id
JOIN users u ON u.id = p.user_id
JOIN vehicles v ON v.id = p.vehicle_id
LEFT JOIN users a ON a.id = sr.assigned_to
LEFT JOIN LATERAL jsonb_array_elements(p.services_snapshot) item(value)
       ON (item.value->>'id')::bigint = sr.service_id
ORDER BY sr.id;
