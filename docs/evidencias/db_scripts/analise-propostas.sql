SELECT p.id, c.name AS cliente, v.license_plate AS placa, p.status,
       p.quoted_amount, r.name AS analista, r.role AS perfil_analista,
       p.reviewed_at, p.review_notes, p.services_snapshot
FROM proposals p
JOIN users c ON c.id = p.user_id
JOIN vehicles v ON v.id = p.vehicle_id
LEFT JOIN users r ON r.id = p.reviewed_by
ORDER BY p.id;
