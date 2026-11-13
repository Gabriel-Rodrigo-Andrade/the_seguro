WITH reference AS (SELECT DATE '2026-11-12' AS local_date)
SELECT (SELECT count(*) FROM users) AS usuarios,
       (SELECT count(*) FROM vehicles) AS veiculos,
       (SELECT count(*) FROM proposals) AS propostas,
       (SELECT count(*) FROM insurance_contracts c, reference r WHERE c.starts_on<=r.local_date AND c.ends_on>r.local_date) AS contratos_vigentes,
       (SELECT count(*) FROM insurance_contracts c, reference r WHERE c.starts_on<=r.local_date AND c.ends_on>r.local_date AND c.ends_on<=r.local_date+30) AS proximos_30_dias,
       (SELECT count(*) FROM service_requests) AS atendimentos;
SELECT status, count(*) AS quantidade FROM proposals GROUP BY status ORDER BY status;
SELECT status, count(*) AS quantidade FROM service_requests GROUP BY status ORDER BY status;

-- Dados próprios do Cliente 1: troque apenas o e-mail fictício para conferir outro.
WITH customer AS (SELECT id FROM users WHERE email='cliente1@theseguro.test'),
reference AS (SELECT DATE '2026-11-12' AS local_date)
SELECT (SELECT count(*) FROM proposals p JOIN customer u ON p.user_id=u.id) AS propostas,
       (SELECT count(*) FROM insurance_contracts c JOIN proposals p ON p.id=c.proposal_id JOIN customer u ON u.id=p.user_id CROSS JOIN reference r WHERE c.starts_on<=r.local_date AND c.ends_on>r.local_date) AS contratos_vigentes,
       (SELECT count(*) FROM service_requests sr JOIN insurance_contracts c ON c.id=sr.insurance_contract_id JOIN proposals p ON p.id=c.proposal_id JOIN customer u ON u.id=p.user_id WHERE sr.status='open') AS assistencias_abertas;
