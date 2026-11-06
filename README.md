# TheSeguro

Sistema acadêmico de corretora fictícia: “o seguro” e “se der ruim, eu te seguro”. O escopo prevê consulta de veículos, cotações, propostas, contratos e assistência em um único aplicativo Laravel MVC com Blade.

## Estado atual

Projeto em desenvolvimento, com a estrutura inicial do Laravel, autenticação Breeze Blade e build dos assets configurados. Os bancos de desenvolvimento e testes estão configurados; as funcionalidades de negócio estão em implementação.

## Stack e versões verificadas

| Componente | Versão | Uso |
|---|---|---|
| PHP | 8.4.24 | Runtime com `pdo_pgsql` e `pgsql` |
| Composer | 2.10.2 | Dependências PHP |
| Laravel Framework / Blade / Eloquent | 13.40.0 | MVC, templates e persistência |
| Laravel skeleton | 13.11.0 | Origem do scaffold |
| Laravel Breeze | 2.4.2 | Scaffold de autenticação Blade |
| PostgreSQL | 15.18 | Banco de dados relacional |
| Node.js | 22.19.0 | Runtime via NVM, fixado em `.nvmrc` |
| npm | 10.9.3 | Dependências frontend |
| Vite | 8.3.6 | Build e servidor de assets |
| Laravel Vite Plugin | 3.2.1 | Integração Laravel/Vite |
| Tailwind CSS | 3.4.19 | CSS do Breeze |
| Alpine.js | 3.17.5 | Interações do Breeze |
| PostCSS / Autoprefixer | 8.5.32 / 10.6.2 | Processamento CSS |
| Tailwind Forms | 0.5.11 | Estilos de formulários |
| PHPUnit | 12.5.43 | Testes |
| Laravel Pint | 1.32.1 | Formatação PHP |

`composer.lock` e `package-lock.json` registram as versões resolvidas. Use PHP 8.4 para reproduzir esta instalação. Blade acompanha a versão do framework. Não há SPA separada.

## Instalação do ambiente

No macOS, se PHP, Composer e PostgreSQL ainda não estiverem instalados:

```bash
brew install php@8.4 composer postgresql@15
export PATH="$(brew --prefix php@8.4)/bin:$(brew --prefix postgresql@15)/bin:$PATH"
php -v
composer --version
psql --version
php -m
```

Homebrew instala as revisões disponíveis das fórmulas, não necessariamente os mesmos patches da tabela.

Com NVM já instalado, na raiz do projeto:

```bash
export NVM_DIR="$HOME/.nvm"
[ -s "$NVM_DIR/nvm.sh" ] && . "$NVM_DIR/nvm.sh"
nvm install
nvm use
node -v
npm -v
```

Se o terminal informar `node: command not found`, carregue o NVM com os comandos acima. Referências oficiais: [Node.js](https://nodejs.org/en/download), [NVM](https://github.com/nvm-sh/nvm), [Laravel](https://laravel.com/docs/13.x/installation) e [Breeze](https://github.com/laravel/breeze).

## Instalação do projeto

Após obter este repositório e entrar em sua raiz:

```bash
nvm use
composer install --no-scripts
# Copiar apenas se ainda não houver configuração local.
test -f .env || cp .env.example .env
php artisan package:discover
# Em uma instalação nova, gerar a chave uma única vez.
php artisan key:generate
npm ci
npm run build
composer check-platform-reqs
```

Não regenerar a chave de uma instalação em uso. Não executar `breeze:install` novamente em um clone: o scaffold já faz parte do projeto e o comando sobrescreve arquivos. `npm ci` reproduz o lockfile; `.npmrc` mantém scripts de instalação desativados. O build explícito foi validado nessa configuração.

## Banco de dados

A aplicação utiliza PostgreSQL na Neon, configurado no `.env`. Os testes utilizam PostgreSQL local, com credenciais próprias no `.env.testing`. O `.env.example` contém valores locais de referência e senha vazia.

| Configuração | Valor |
|---|---|
| Conexão | `pgsql` |
| Banco da aplicação (Neon) | `neondb` |
| Banco de testes (local) | `theseguro_dev` |
| Sessões e cache | Arquivos locais |
| Fila | `sync` |

As conexões foram verificadas em PostgreSQL 18.6 na Neon e 15.18 no ambiente local. A conexão Neon utiliza `DB_SSLMODE=require`, com TLS 1.3 confirmado pelo cliente PostgreSQL. Ambos os bancos estavam sem tabelas na verificação inicial. As versões principais diferem; os testes locais não comprovam equivalência completa com o ambiente remoto. A tabela de usuários contém papel (`cliente`, `corretor` ou `administrador`) e estado ativo, com e-mail único e restrição dos papéis no PostgreSQL. A estrutura de recuperação de senha foi criada por uma migration separada. Os comandos de instalação preparam a aplicação e os assets, mas não criam bancos nem executam migrations. As demais entidades de negócio ainda serão implementadas. Sessões e cache usam arquivos; não são criadas tabelas de filas ou cache.

O `phpunit.xml` fixa os testes em `127.0.0.1:5432`, banco `theseguro_dev`, usuário `theseguro`, e desativa a URL de conexão alternativa. Esse banco local é exclusivo para testes e poderá ter suas tabelas recriadas pela suíte. Mantenha as credenciais apenas nos arquivos locais de ambiente, fora do controle de versão.

## Execução local

Para visualizar o scaffold sem depender das tabelas:

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

Em outro terminal, se estiver desenvolvendo assets:

```bash
nvm use
npm run dev
```

Acesse `http://127.0.0.1:8000`. Com `npm run build` já executado, não é obrigatório manter Vite em execução para servir os assets compilados. Login, cadastro e demais operações persistentes dependem da conclusão da configuração do banco.

## Verificações realizadas

- `composer validate --strict`: manifesto válido.
- `composer check-platform-reqs`: requisitos satisfeitos no PHP 8.4.24.
- `php artisan --version`: Laravel 13.40.0.
- `php artisan route:list --except-vendor`: 20 rotas do scaffold registradas.
- `php artisan test --filter=ExampleTest`: 2 testes, 2 asserções aprovados; sem recriação de banco.
- `php artisan view:cache`: templates Blade compilados.
- `npm run build`: assets gerados com sucesso.

A validação dos fluxos de autenticação, permissões e integridade PostgreSQL está pendente. Os testes básicos verificam apenas a inicialização da aplicação.

## Contas de demonstração

As contas de demonstração serão disponibilizadas após a implementação dos perfis de cliente, corretor e administrador.

## Estrutura de usuários

A migration de usuários foi aplicada no banco da aplicação. `users` possui chave primária `bigint`, e-mail único, papel padrão `cliente` e `is_active` padrão `true`. O Laravel mantém o histórico de execução em `migrations`, com chave primária inteira e campos `migration` e `batch`.

As consultas em [estrutura-usuarios.sql](docs/consultas/estrutura-usuarios.sql) permitem inspecionar colunas e restrições no pgAdmin sem consultar dados de contas. A criação da estrutura não representa a conclusão das regras de autenticação e autorização.

A tabela `password_reset_tokens` possui `email` como chave primária, `token` obrigatório e `created_at` opcional, seguindo o suporte de recuperação de senha do Laravel. A estrutura pode ser consultada com [estrutura-recuperacao-senha.sql](docs/consultas/estrutura-recuperacao-senha.sql), sem exibir tokens. O fluxo funcional de recuperação de senha ainda depende das verificações de autenticação.
