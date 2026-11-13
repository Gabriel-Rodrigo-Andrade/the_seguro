SELECT current_database() AS banco,
       id, name, email, role, is_active
FROM public.users
WHERE email IN (
    'cliente1@theseguro.test',
    'cliente2@theseguro.test',
    'corretor@theseguro.test',
    'admin@theseguro.test'
)
ORDER BY id;
