SELECT p.id, u.name AS cliente, v.license_plate AS placa, pl.name AS plano,
       p.quoted_amount AS preco_enviado_ou_rascunho, p.status, p.submitted_at,
       p.services_snapshot
FROM proposals p
JOIN users u ON u.id = p.user_id
JOIN vehicles v ON v.id = p.vehicle_id
JOIN plans pl ON pl.id = p.plan_id
ORDER BY p.id;
