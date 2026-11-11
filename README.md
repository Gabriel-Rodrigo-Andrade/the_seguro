# TheSeguro

Sistema acadêmico de corretora fictícia: “o seguro” e “se der ruim, eu te seguro”. O escopo prevê consulta de veículos, cotações, propostas, contratos e assistência em um único aplicativo Laravel MVC com Blade.

## Estado atual

Autenticação, gestão de usuários, cadastro de veículos, catálogo e cotação, envio, análise de propostas e confirmação de contratos implementados, com testes automatizados aprovados. A consulta por placa está validada com simulações; a chamada real ao fornecedor ainda não foi realizada. A interface de autenticação foi conferida manualmente; a demonstração visual da consulta simulada foi registrada.

- Cadastro público sempre cria cliente ativo, sem escolha de privilégio.
- Login, logout e edição do próprio perfil, sem exclusão física da conta.
- Administradores podem cadastrar, listar, consultar, editar, desativar e reativar usuários.
- Contas inativas não entram e têm sessões existentes invalidadas no próximo acesso.
- O último administrador ativo não pode ser desativado ou rebaixado. Alterações administrativas são serializadas em transação PostgreSQL.
- Clientes gerenciam os próprios veículos; administradores gerenciam veículos de clientes. Há busca por placa, marca e modelo, paginação e cadastro manual.
- O proprietário não pode ser trocado após o cadastro. Clientes com veículos não podem mudar de papel.
- Clientes podem cotar, salvar rascunhos e enviar propostas. Corretores e administradores analisam propostas enviadas. Clientes confirmam propostas aprovadas e consultam contratos. Assistência será implementada na próxima versão.

## Stack e versões verificadas

| Componente | Versão | Uso |
|---|---|---|
| PHP | 8.4.24 | Runtime com `pdo_pgsql` e `pgsql` |
| Composer | 2.10.2 | Dependências PHP |
| Laravel Framework / Blade / Eloquent | 13.40.0 | MVC, templates e persistência |
| Laravel skeleton | 13.11.0 | Origem do scaffold |
| Laravel Breeze | 2.4.2 | Scaffold de autenticação Blade |
| PostgreSQL | 18.6 (Neon) / 15.18 (local) | Aplicação / testes |
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

As conexões foram verificadas em PostgreSQL 18.6 na Neon e 15.18 no ambiente local. A conexão Neon utiliza `DB_SSLMODE=require`, com TLS 1.3 confirmado pelo cliente PostgreSQL. Ambos os bancos estavam sem tabelas na verificação inicial. As versões principais diferem; os testes locais não comprovam equivalência completa com o ambiente remoto. A tabela de usuários contém papel (`cliente`, `corretor` ou `administrador`) e estado ativo, com e-mail único e restrição dos papéis no PostgreSQL. A estrutura de recuperação de senha foi criada por uma migration separada. Os comandos de instalação preparam a aplicação e os assets, mas não criam bancos nem executam migrations. A tabela `vehicles` mantém veículos vinculados a clientes, com placa única. As demais entidades de negócio ainda serão implementadas. Sessões e cache usam arquivos; não são criadas tabelas de filas ou cache.

O `phpunit.xml` fixa os testes em `127.0.0.1:5432`, banco `theseguro_dev`, usuário `theseguro`, e desativa a URL de conexão alternativa. Esse banco local é exclusivo para testes e poderá ter suas tabelas recriadas pela suíte. Mantenha as credenciais apenas nos arquivos locais de ambiente, fora do controle de versão.

## Execução local

Após configurar o banco e instalar os assets:

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

Em outro terminal, se estiver desenvolvendo assets:

```bash
nvm use
npm run dev
```

Acesse `http://127.0.0.1:8000`. Com `npm run build` já executado, não é obrigatório manter Vite em execução para servir os assets compilados. A aplicação usa a conexão definida no `.env`. A interface está em português e utiliza os componentes Blade do Breeze.

## Verificações realizadas

- `composer validate --strict`: manifesto válido.
- `composer check-platform-reqs`: requisitos satisfeitos no PHP 8.4.24.
- `php artisan --version`: Laravel 13.40.0.
- `php artisan route:list --except-vendor`: rotas de autenticação, usuários e veículos; nenhuma rota de exclusão física de usuários.
- `vendor/bin/phpunit`: 112 testes, 690 asserções aprovados no PostgreSQL local.
- `vendor/bin/pint --test app tests`: formatação verificada.
- `node --test tests/Frontend/vehicle-lookup.test.mjs`: 5 testes aprovados.
- `php artisan view:cache`: templates Blade compilados.
- `npm run build`: assets gerados com sucesso.

A suíte cobre autenticação dos três perfis, recuperação e alteração de senha com notificações simuladas, bloqueio de visitante e conta inativa, privilégios no cadastro/perfil, acesso administrativo, preservação do último administrador ativo, desativação sem exclusão e reexecução dos seeders. A concorrência é protegida por bloqueio transacional; não foi realizado teste de carga concorrente. Os testes também cobrem CRUD e propriedade dos veículos, consulta sem persistência, respostas completas/parciais, erros, timeout, limite de requisições e cadastro manual após falha. Os testes JavaScript verificam aplicação explícita das sugestões, preservação dos campos, descarte de respostas atrasadas e ausência de repetição automática. A demonstração visual da consulta simulada foi registrada; não foi realizada inspeção automatizada do layout. O refinamento estético permanece planejado.

## Contas de demonstração

O `UserSeeder` cria as seguintes contas fictícias, todas ativas:

| Perfil | E-mail |
|---|---|
| Cliente 1 | `cliente1@theseguro.test` |
| Cliente 2 | `cliente2@theseguro.test` |
| Corretor | `corretor@theseguro.test` |
| Administrador | `admin@theseguro.test` |

Senha demonstrativa das quatro contas: `TheSeguro@123`. Uso exclusivo no ambiente acadêmico de demonstração. As senhas são armazenadas com hash; o seeder preserva contas existentes, identificadas pelo e-mail, sem redefinir senhas ou papéis.

A carga foi executada e os hashes foram verificados. Login e permissões foram validados por testes HTTP automatizados no banco local. A consulta [perfis-demonstracao.sql](docs/consultas/perfis-demonstracao.sql) exibe somente os dados necessários para conferir os perfis.

## Estrutura de usuários

A migration de usuários foi aplicada no banco da aplicação. `users` possui chave primária `bigint`, e-mail único, papel padrão `cliente` e `is_active` padrão `true`. O Laravel mantém o histórico de execução em `migrations`, com chave primária inteira e campos `migration` e `batch`.

As consultas em [estrutura-usuarios.sql](docs/consultas/estrutura-usuarios.sql) permitem inspecionar colunas e restrições no pgAdmin sem consultar dados de contas. As permissões são aplicadas por middleware, Form Requests e UserPolicy no servidor.

A tabela `password_reset_tokens` possui `email` como chave primária, `token` obrigatório e `created_at` opcional, seguindo o suporte de recuperação de senha do Laravel. A estrutura pode ser consultada com [estrutura-recuperacao-senha.sql](docs/consultas/estrutura-recuperacao-senha.sql), sem exibir tokens. A recuperação de senha foi validada em testes com notificações simuladas; envio real de e-mail não foi configurado.

## Preparação de uma instalação nova

Em um banco novo, exclusivo da aplicação, depois de conferir as credenciais no `.env`, aplique as migrations e opcionalmente carregue as contas e os veículos demonstrativos:

```bash
php artisan migrate
php artisan db:seed
```

Esses comandos não são necessários nos bancos que já receberam as migrations e a carga. As contas demonstrativas têm senha pública e são destinadas somente à demonstração acadêmica.

## Testes locais

Configure um PostgreSQL local exclusivo para testes. O banco usado nesta configuração é `theseguro_dev`, com usuário `theseguro`. Em uma nova instalação, prepare o arquivo de ambiente:

```bash
test -f .env.testing || cp .env.testing.example .env.testing
# Preencha DB_PASSWORD no arquivo local, sem incluí-lo no Git.
php artisan key:generate --env=testing
vendor/bin/phpunit
nvm use
node --test tests/Frontend/vehicle-lookup.test.mjs
```

Gere a chave apenas ao preparar um arquivo novo. A suíte pode recriar as tabelas desse banco local. Uma verificação anterior à inicialização dos testes recusa conexão remota, outro banco e configuração em cache; chamadas HTTP externas também são bloqueadas nos testes. A Neon não é utilizada pela suíte.

## Conferência da interface

Acesse `/login`, entre com cada perfil demonstrativo e confira o painel e o logout. Com administrador, confira `/admin/users`, cadastro, detalhes, edição e mensagens de validação. Confira `/profile` e a navegação em tela estreita. Clientes e corretores não devem acessar `/admin/users`.

## Estrutura de veículos

A tabela `vehicles` foi criada com placa única, marca, modelo, ano de fabricação e proprietário vinculado a `users`. Cor e ano-modelo são opcionais. A chave estrangeira impede excluir uma conta com veículos associados; `user_id` possui índice para consulta por proprietário.

O CRUD está disponível em `/vehicles`. Clientes acessam somente seus veículos; administradores escolhem um cliente ativo no cadastro. O corretor consulta veículos que possuem propostas enviadas ou posteriores, sem editar ou excluir. Nenhuma consulta externa foi realizada. As consultas de inspeção estão em `docs/consultas/estrutura-veiculos.sql` no ambiente local.

O `VehicleSeeder` cria dois veículos fictícios, identificados pelas placas `ABC1D23` e `DEF5678`, vinculados respectivamente aos dois clientes demonstrativos. O primeiro possui cor e ano-modelo; o segundo mantém esses campos opcionais vazios. A carga exige que os clientes estejam ativos e preserva registros existentes, sem consultar APIs.

Os testes em `VehicleDatabaseTest` verificam placa única, FK de proprietário, restrição de exclusão, preservação na reexecução e rollback quando falta um proprietário. Resultado: 5 testes e 17 asserções aprovados no PostgreSQL local. O banco demonstrativo recebeu somente dados válidos. A consulta local `docs/consultas/veiculos-proprietarios.sql` mostra a relação entre veículos e proprietários.

## Consulta de placa

A consulta auxilia o cadastro e não grava veículos. As sugestões aparecem para revisão; o botão **Aplicar sugestões revisadas** preenche marca, modelo e cor. Fabricação e ano-modelo devem ser confirmados manualmente: o campo `year` do fornecedor não distingue esses dois conceitos. Campos ausentes não apagam valores preenchidos, e a substituição de valores exige confirmação.

Configuração no `.env`:

```dotenv
FIPE_LOOKUP_MODE=mock
FIPE_API_TOKEN=
FIPE_CONNECT_TIMEOUT=3
FIPE_TIMEOUT=8
```

- `mock`: padrão, sem acesso externo e com identificação de simulação. `XYZ1A23` retorna sugestões completas fictícias; `DEF5678` demonstra uma resposta parcial.
- `disabled`: consulta indisponível; cadastro manual continua funcionando.
- `live`: requer token do fornecedor em `FIPE_API_TOKEN`, usado somente no servidor. Sem token, nenhuma consulta externa é feita. Nunca use prefixo `VITE_` para esse segredo.

Após alterar configurações, execute `php artisan config:clear`. A integração utiliza `GET /api/v2/plates/{plate}` e o header `X-Subscription-Token`. A [documentação da busca por placa](https://fipe.api.br/docs/api/busca-por-placa-alpha), consultada em novembro de 2026, informa 10 consultas bem-sucedidas por mês no plano gratuito com token; confirme as condições atuais antes do uso real. A aplicação exige token no modo live e não utiliza acesso anônimo.

O endpoint local aceita até três consultas por minuto por usuário. Não há repetição automática nem redirecionamento para outro endpoint. Falhas, ausência de dados e cota esgotada mantêm o preenchimento manual disponível. Salvar um veículo e executar seeders não chama a API. A consulta repetida da mesma placa sem alteração na página reutiliza as sugestões recebidas.

Validação realizada com `Http::fake()` e bloqueio de chamadas externas. **Chamada real pendente.** Para conferir a simulação, acesse `/vehicles/create`, informe `XYZ1A23` e clique em **Consultar placa**. Confira as sugestões antes de aplicar e informe os anos manualmente. Teste também `DEF5678` para observar campos ausentes.

## Catálogo demonstrativo

As tabelas `services`, `plans` e `plan_service` armazenam serviços, planos e sua composição. O preço anual usa `numeric(12,2)` com restrição de valor positivo; o vínculo plano/serviço é único, com chaves estrangeiras que impedem excluir registros ainda associados.

`ServiceSeeder` cria Guincho e Chaveiro. `PlanSeeder` cria Essencial (R$ 600,00 anuais, com Guincho) e Completo (R$ 960,00 anuais, com Guincho e Chaveiro). Todos são fictícios. Os seeders preservam registros existentes e não restauram automaticamente composições alteradas. A carga completa `php artisan db:seed` inclui esse catálogo; não requer API.

O catálogo está disponível em `/plans` e `/services`. Administradores criam, consultam, editam, desativam e excluem registros sem impedimentos de vínculo. Corretores consultam o catálogo. Clientes consultam somente planos ativos com pelo menos um serviço e todos os serviços ativos. As telas do catálogo foram conferidas manualmente. `vendor/bin/phpunit --filter=CatalogDatabaseTest` validou 5 testes e 24 asserções no banco local, incluindo preço positivo, vínculos únicos, FKs e reexecução dos seeders.

Planos exigem nome único, descrição, preço anual positivo com até duas casas decimais e ao menos um serviço ativo, sem repetições. O salvamento do plano e da composição é transacional. Desativar um serviço torna indisponíveis os planos que o incluem; os vínculos são preservados. Serviços associados não podem ser excluídos. Excluir um plano remove seus vínculos na mesma transação e preserva os serviços. Planos referenciados por propostas não podem ser excluídos; serviços presentes na composição de propostas enviadas também são preservados.

`CatalogManagementTest` cobre permissões, disponibilidade para clientes, CRUD administrativo, composição, validação de preço, exclusão com vínculos e rollback em falha de persistência. A suíte completa passou com 112 testes e 690 asserções no PostgreSQL local; não foi realizado teste de carga concorrente.

## Cotação e envio de propostas

Em `/proposals`, o cliente cria uma cotação usando seu veículo e um plano disponível. O servidor calcula o preço anual; campos enviados pelo navegador não definem proprietário, situação, preço ou composição. Rascunhos próprios podem ser editados ou excluídos. O detalhe mostra o preço e os serviços atuais antes da confirmação.

Ao enviar, o sistema revalida disponibilidade, propriedade e confirmação da cotação. Se preço ou composição mudarem desde a exibição, solicita nova confirmação com os valores atuais. O envio grava preço, data e uma cópia histórica dos serviços na mesma transação. Propostas enviadas não podem ser editadas ou excluídas; alterações posteriores no catálogo não reescrevem esse histórico.

Corretores consultam propostas enviadas e seus veículos; rascunhos ficam ocultos para eles. Administradores consultam propostas, mas não enviam em nome de clientes. Veículos com qualquer proposta não podem ser excluídos; após envio, seus dados não podem ser alterados. Planos referenciados e serviços presentes em históricos enviados não podem ser excluídos.

`ProposalSeeder` cria um rascunho do Cliente 1/Essencial e uma enviada do Cliente 2/Completo. Usa vínculos existentes, preserva propostas encontradas para o mesmo cliente, veículo e plano e não chama APIs. A instalação completa continua usando `php artisan migrate` e `php artisan db:seed`.

As mutações de propostas e dos registros relacionados são serializadas com bloqueio transacional da tabela `proposals`, adquirido antes dos bloqueios de linha. É uma escolha para o volume pequeno da demonstração; não houve teste de carga concorrente. A suite valida isolamento entre clientes, acesso do corretor, confirmação desatualizada/adulterada, histórico, bloqueios de exclusão e rollback do envio. A interface de propostas ainda precisa de conferência visual manual. Aprovação, recusa e confirmação da contratação estão disponíveis.

## Análise de propostas

Corretores e administradores acessam `/proposal-reviews`, uma fila paginada das propostas enviadas, ordenada pela data de envio. No detalhe, podem aprovar ou recusar; a recusa exige justificativa. Responsável e data são definidos no servidor. A decisão é revalidada dentro da transação com bloqueio; uma decisão já registrada não pode ser sobrescrita por outra requisição, inclusive com um objeto carregado anteriormente.

O cliente consulta o resultado e a justificativa na própria proposta. Preço, composição histórica e data de envio são preservados durante a análise. Clientes, visitantes e contas inativas não podem analisar propostas. Não há edição posterior da análise nesta versão.

A migration aditiva inclui `reviewed_by` (FK para usuários), `reviewed_at` e `review_notes`. `ProposalReviewSeeder` cria duas propostas fictícias dedicadas, uma aprovada e uma recusada, sem alterar as propostas anteriores. A reexecução preserva esses exemplos, identificados pelas observações demonstrativas. A carga completa de instalação inclui esse seeder.

`ProposalReviewTest` cobre permissões, fila, justificativa, identidade do analista, segunda decisão, rollback e preservação dos dados anteriores pelos seeders. A suíte completa passou com 112 testes e 690 asserções no PostgreSQL local. Build e compilação Blade aprovados; a conferência visual manual das telas de análise ainda está pendente.

## Confirmação e contratos

O cliente proprietário confirma a contratação na proposta aprovada. A operação gera um número interno `TS-` e cria o contrato junto com a mudança da proposta para `contracted`, em uma transação. Repetir a confirmação retorna o mesmo contrato. Outro cliente, corretor ou administrador não confirma em nome do proprietário. Propostas recusadas, enviadas ou em rascunho não podem ser contratadas.

Em `/contracts`, clientes consultam seus contratos; corretores e administradores consultam a lista geral. Não há edição ou exclusão de contratos. O detalhe usa o preço e os serviços históricos da proposta, sem recalcular pelo catálogo atual. Trata-se de contratação fictícia acadêmica, sem emissão de apólice real.

O início é a data local da confirmação em `America/Sao_Paulo`. O fim exclusivo é 12 meses depois, sem transbordamento de mês: 29/02/2024 termina em 28/02/2025 às 00:00. A tela também informa o último dia incluído. `contracted_at` registra o instante da confirmação; `starts_on` e `ends_on` são datas. O banco garante proposta única por contrato, número único, FK e fim posterior ao início.

`InsuranceContractSeeder` cria dois cenários separados das propostas anteriores: contrato com início um mês antes da data da primeira carga (vigente) e outro com início 13 meses antes (encerrado). Ambos têm 12 meses de vigência. Reexecutar preserva as datas e os registros existentes; o exemplo vigente pode expirar com o tempo. As propostas demonstrativas recebem histórico de envio e análise coerente com essas datas.

`InsuranceContractTest` valida propriedade, confirmação repetida, rollback, unicidade, período, ano bissexto, fronteira de meia-noite local e preservação do histórico. Suíte completa: 112 testes e 690 asserções no PostgreSQL local; build/Blade/Pint aprovados. Conferência visual manual dos contratos pendente; sem teste de carga concorrente.
