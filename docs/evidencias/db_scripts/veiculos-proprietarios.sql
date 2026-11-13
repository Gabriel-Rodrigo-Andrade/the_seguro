SELECT current_database() AS banco, v.id, v.license_plate AS placa,
       v.brand AS marca, v.model AS modelo,
       v.manufacture_year AS ano_fabricacao, v.model_year AS ano_modelo,
       v.color AS cor, u.name AS proprietario, u.email, u.role AS perfil
FROM public.vehicles AS v
JOIN public.users AS u ON u.id = v.user_id
WHERE v.license_plate IN ('ABC1D23', 'DEF5678')
ORDER BY v.license_plate;
