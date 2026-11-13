SELECT p.name AS plano, p.annual_price AS preco_anual,
       p.is_active AS plano_ativo, s.name AS servico,
       s.description AS descricao_servico, s.is_active AS servico_ativo
FROM plans p
JOIN plan_service ps ON ps.plan_id = p.id
JOIN services s ON s.id = ps.service_id
ORDER BY p.name, s.name;
