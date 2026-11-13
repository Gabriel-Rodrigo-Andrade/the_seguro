SELECT c.contract_number, u.name AS cliente, v.license_plate AS placa,
       p.id AS proposta, p.status, p.quoted_amount, c.contracted_at,
       c.starts_on, c.ends_on AS fim_exclusivo,
       CASE WHEN (CURRENT_TIMESTAMP AT TIME ZONE 'America/Sao_Paulo')::date >= c.starts_on
             AND (CURRENT_TIMESTAMP AT TIME ZONE 'America/Sao_Paulo')::date < c.ends_on
            THEN 'Vigente' ELSE 'Fora da vigência' END AS vigencia,
       p.services_snapshot
FROM insurance_contracts c
JOIN proposals p ON p.id = c.proposal_id
JOIN users u ON u.id = p.user_id
JOIN vehicles v ON v.id = p.vehicle_id
ORDER BY c.id;
