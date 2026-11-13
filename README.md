# TheSeguro

Sistema de demonstração de uma corretora fictícia, desenvolvido em Laravel com Blade e PostgreSQL. Reúne cadastro de veículos, consulta de placa, planos, propostas, contratação e assistência veicular, com acesso separado para clientes, corretores e administradores.

## Funcionalidades

- Autenticação, cadastro público de clientes e edição do próprio perfil.
- Gestão administrativa de usuários, com ativação e desativação de contas.
- Cadastro de veículos e consulta de dados por placa pela FipeAPI.
- Catálogo de planos e serviços, com preço anual e composição de coberturas.
- Cotação, rascunho e envio de propostas pelo cliente.
- Aprovação ou recusa de propostas por corretores e administradores.
- Confirmação da contratação pelo cliente e consulta de contratos.
- Solicitação, acompanhamento e conclusão de assistências.
- Painel por perfil, com filtros, contratos vigentes e próximos vencimentos.

## Perfis de acesso

| Perfil | Acesso |
|---|---|
| Cliente | Próprios veículos, propostas, contratos e assistências; planos disponíveis |
| Corretor | Catálogo, propostas enviadas e seus veículos, análise de propostas, contratos e atendimentos |
| Administrador | Gestão de usuários e catálogo, veículos de clientes, análise de propostas, contratos e atendimentos |

O cadastro público sempre cria um cliente ativo. Contas inativas não podem acessar o sistema. O último administrador ativo não pode ser desativado ou ter seu papel alterado. As permissões e a propriedade dos registros são verificadas no servidor.

## Stack e versões verificadas

| Componente | Versão | Uso |
|---|---|---|
| PHP | 8.4.24 | Runtime com `pdo_pgsql` e `pgsql` |
| Composer | 2.10.2 | Dependências PHP |
| Laravel Framework / Blade / Eloquent | 13.40.0 | MVC, templates e persistência |
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

## Preparação do ambiente

No macOS, instale PHP, Composer e PostgreSQL com Homebrew:

```bash
brew install php@8.4 composer postgresql@15
export PATH="$(brew --prefix php@8.4)/bin:$(brew --prefix postgresql@15)/bin:$PATH"
php -v
composer --version
psql --version
php -m
```

As fórmulas do Homebrew instalam os patches disponíveis, que podem diferir da tabela. Confirme que o PHP possui suporte a PostgreSQL (`pdo_pgsql`).

Com [NVM](https://github.com/nvm-sh/nvm) instalado, carregue-o no terminal:

```bash
export NVM_DIR="$HOME/.nvm"
[ -s "$NVM_DIR/nvm.sh" ] && . "$NVM_DIR/nvm.sh"
```

## Instalação

```bash
git clone https://github.com/Gabriel-Rodrigo-Andrade/the_seguro.git
cd the_seguro
nvm install
nvm use
composer install --no-scripts
test -f .env || cp .env.example .env
php artisan package:discover
php artisan key:generate
npm ci
npm run build
composer check-platform-reqs
```

Gere `APP_KEY` somente na instalação inicial; não substitua a chave de uma aplicação em uso. O scaffold de autenticação já está no repositório, portanto não é necessário executar `breeze:install`. O arquivo `.npmrc` desativa scripts de instalação; o build é executado explicitamente.

### Banco da aplicação

Crie o banco no projeto e na branch desejados na Neon. Configure o `.env` com os dados fornecidos pelo painel:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=seu-host.neon.tech
DB_PORT=5432
DB_DATABASE=neondb
DB_USERNAME=seu_usuario
DB_PASSWORD=sua_senha
DB_SSLMODE=require
```

Substitua os valores de exemplo pelas credenciais da conexão. O `.env.example` aponta inicialmente para o banco local usado nos testes: altere a conexão da aplicação antes de executar migrations. O banco `theseguro_dev` local deve ficar reservado à suíte de testes.

A aplicação usa sessões e cache em arquivos, com fila `sync`; Redis e workers de fila não são necessários nesta configuração. Credenciais e chaves ficam nos arquivos de ambiente, fora do Git.

Depois de configurar o banco:

```bash
php artisan config:clear
php artisan migrate
```

Para carregar os dados fictícios de demonstração:

```bash
php artisan db:seed
```

A carga inclui quatro usuários, dois veículos, dois serviços, dois planos, propostas em diferentes situações, dois contratos e três solicitações de assistência. Os seeders preservam os registros demonstrativos existentes. As datas dos contratos são relativas à primeira carga; o exemplo vigente pode expirar com o tempo.

### Execução local

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

Acesse `http://127.0.0.1:8000`. Para desenvolver os assets, abra outro terminal:

```bash
nvm use
npm run dev
```

Com os assets compilados por `npm run build`, não é necessário manter o servidor Vite em execução.

## Contas de demonstração

| Perfil | E-mail |
|---|---|
| Cliente 1 | `cliente1@theseguro.test` |
| Cliente 2 | `cliente2@theseguro.test` |
| Corretor | `corretor@theseguro.test` |
| Administrador | `admin@theseguro.test` |

Senha das contas demonstrativas: `TheSeguro@123`.

Essas contas são exclusivas para demonstração. As senhas são armazenadas com hash. Executar o seeder novamente não redefine senhas nem papéis de contas existentes.

## Consulta de placa

A integração usa a [FipeAPI](https://fipe.api.br/docs/api/busca-por-placa-alpha) para sugerir marca, modelo, cor e ano do veículo. A consulta não cadastra nem altera veículos automaticamente.

Para usar o fornecedor real, configure no `.env`:

```dotenv
FIPE_LOOKUP_MODE=live
FIPE_API_TOKEN=seu_token
FIPE_CONNECT_TIMEOUT=3
FIPE_TIMEOUT=8
```

Substitua `seu_token` pelo token da sua conta e execute:

```bash
php artisan config:clear
```

| Modo | Comportamento |
|---|---|
| `live` | Consulta real com token configurado no servidor |
| `mock` | Dados fictícios identificados como simulação, sem acesso externo |
| `disabled` | Consulta desativada; cadastro manual disponível |

O modo padrão do projeto é `mock`. Nesse modo, `XYZ1A23` retorna sugestões fictícias completas e `DEF5678` retorna uma resposta parcial. O token nunca deve ser colocado em variáveis `VITE_*` ou no código frontend.

No cadastro em `/vehicles/create`, informe a placa e clique em **Consultar placa**. Revise o retorno e use **Aplicar sugestões revisadas** para preencher marca, modelo e cor. Confirme manualmente fabricação e ano-modelo: o campo `year` do fornecedor não distingue esses conceitos. Campos ausentes não apagam valores já preenchidos; substituir dados exige confirmação.

A requisição usa `GET https://fipe.parallelum.com.br/api/v2/plates/{plate}` com o cabeçalho `X-Subscription-Token`. Conforme a documentação consultada em 13/11/2026, o plano gratuito com token permite 10 consultas bem-sucedidas por mês. O endpoint está em fase Alpha; disponibilidade, cobertura e condições podem mudar.

A aplicação limita a três consultas por minuto por usuário, sem repetição automática. Esse limite não substitui a cota mensal compartilhada do fornecedor. A resposta pode informar a cota restante; quando os cabeçalhos não estão disponíveis, ela fica desconhecida. Falhas mantêm o cadastro manual disponível. Salvar veículos e executar seeders não consulta a API.

A consulta real pelo serviço Laravel foi validada em 13/11/2026, com HTTP 200 e retorno normalizado em aproximadamente 463 ms. Os testes automatizados usam respostas simuladas e não consomem a cota externa.

## Fluxo de utilização

### Veículos e catálogo

Clientes gerenciam somente seus veículos. Administradores escolhem um cliente ativo no cadastro; o proprietário não pode ser trocado depois. Placas são únicas. Cor e ano-modelo são opcionais; fabricação é obrigatória.

Administradores gerenciam `/plans` e `/services`. Clientes consultam planos ativos que tenham ao menos um serviço e todos os serviços ativos. Corretores consultam o catálogo. A carga inicial oferece Essencial, por R$ 600,00 anuais com Guincho, e Completo, por R$ 960,00 anuais com Guincho e Chaveiro.

Desativar um serviço torna indisponíveis os planos que o incluem. Planos referenciados por propostas e serviços vinculados a planos ou presentes no histórico de propostas enviadas não podem ser excluídos.

### Propostas e análise

Em `/proposals`, o cliente escolhe um veículo próprio e um plano disponível. O servidor define o preço anual. Rascunhos podem ser editados ou excluídos pelo proprietário.

Ao enviar, o sistema revalida o preço e a composição apresentados. Se houver alteração, solicita nova confirmação. O envio grava preço, data e uma cópia dos serviços contratados; mudanças posteriores no catálogo não alteram esse histórico. Propostas enviadas não podem ser editadas ou excluídas.

Veículos com propostas não podem ser excluídos. Depois do envio de uma proposta, seus dados também não podem ser alterados. Rascunhos não ficam disponíveis para corretores.

Corretores e administradores acessam `/proposal-reviews` para analisar propostas enviadas. A recusa exige justificativa. Analista e data são definidos no servidor, e uma decisão registrada não pode ser sobrescrita. O cliente acompanha o resultado na própria proposta.

### Contratos

Somente o cliente proprietário confirma uma proposta aprovada. A operação cria o contrato e muda a proposta para `contracted` na mesma transação. Repetir a confirmação retorna o contrato existente.

Em `/contracts`, clientes consultam os próprios contratos; corretores e administradores consultam a lista geral. Não há edição ou exclusão. Preço e serviços são obtidos do histórico da proposta.

A vigência começa na data da confirmação em `America/Sao_Paulo` e termina 12 meses depois, com fim exclusivo e sem transbordamento de mês. Por exemplo, um contrato iniciado em 29/02/2024 termina em 28/02/2025 às 00:00. A interface mostra também o último dia incluído.

### Assistência

Em `/assistance`, o cliente abre uma solicitação para um contrato próprio vigente e um serviço incluído no histórico da contratação. Relato e local não podem ser alterados após o envio. Alterações posteriores no catálogo não removem os serviços contratados.

O atendimento segue `open → in_progress → completed`. Corretores e administradores assumem solicitações; a conclusão exige uma resposta. O corretor conclui somente atendimentos que assumiu, enquanto o administrador pode concluir qualquer atendimento em andamento. Não é possível pular estados ou reabrir uma solicitação concluída.

A vigência é exigida na abertura. Um atendimento aberto durante a vigência pode ser concluído após o vencimento do contrato.

### Painel

O `/dashboard` reúne informações conforme o perfil. Clientes acompanham seus registros, corretores veem propostas enviadas e assistências abertas ou em andamento, e administradores também acompanham usuários e veículos.

Os filtros de situação são independentes por categoria. O período considera datas inclusivas em São Paulo, usando a criação dos registros ou a confirmação dos contratos. Vigência e vencimentos usam a data atual; próximos vencimentos incluem contratos vigentes com fim em até 30 dias.

Os indicadores contam todos os resultados; cada lista exibe até cinco registros. **Ver todos** abre a listagem completa sem transportar os filtros do painel.

## Testes

Os testes usam exclusivamente PostgreSQL local em `127.0.0.1:5432`, banco `theseguro_dev`, usuário `theseguro`. Não use esse banco para dados que precisem ser preservados.

No macOS, inicie o PostgreSQL:

```bash
brew services start postgresql@15
psql postgres
```

Com uma conta autorizada a criar usuários e bancos, execute somente se ainda não existirem:

```sql
CREATE ROLE theseguro LOGIN;
\password theseguro
CREATE DATABASE theseguro_dev OWNER theseguro;
\q
```

Prepare o ambiente de testes:

```bash
test -f .env.testing || cp .env.testing.example .env.testing
```

Preencha `DB_PASSWORD` em `.env.testing`. Ao criar esse arquivo pela primeira vez, gere sua chave:

```bash
php artisan key:generate --env=testing
```

Execute as verificações:

```bash
php artisan config:clear
vendor/bin/phpunit
nvm use
node --test tests/Frontend/vehicle-lookup.test.mjs
vendor/bin/pint --test app tests
composer validate --strict
composer check-platform-reqs
php artisan view:cache
npm run build
```

A configuração recusa banco remoto, banco diferente de `theseguro_dev` e configuração em cache. Requisições HTTP externas são bloqueadas nos testes. A suíte pode recriar tabelas do banco local.

Última validação completa, em 12/11/2026: **129 testes PHP, 826 asserções e 5 testes JavaScript aprovados**, além de build, compilação Blade, formatação e requisitos Composer. A cobertura inclui permissões, isolamento entre clientes, validações, histórico das propostas, transições, rollback, vigência e comportamento da consulta de placa.

### Reconstrução do banco de testes

Para apagar as tabelas locais de testes e recriar a estrutura com dados demonstrativos:

```bash
php scripts/rebuild-testing.php
```

O script fixa o ambiente `testing` e o banco `theseguro_dev` em `127.0.0.1:5432`, rejeitando configurações divergentes antes de executar `migrate:fresh --seed`. A reconstrução não utiliza o banco Neon. Não execute esse comando para preservar dados locais de testes.

## Limitações

- Contratos, valores e assistências são demonstrativos, sem emissão de apólice ou despacho real.
- O envio real de e-mails precisa ser configurado; a recuperação de senha é testada com notificações simuladas.
- A FipeAPI depende da disponibilidade e da cota do fornecedor; o cadastro manual permanece disponível.
- As alterações críticas usam transações e bloqueios. Não foi realizado teste de carga concorrente.
- A validação visual não cobre integralmente todas as telas e resoluções.
- As versões PostgreSQL local e Neon diferem; os testes locais não garantem equivalência completa entre os ambientes.
- A execução documentada usa o servidor local do Laravel. A publicação em servidor público não faz parte deste guia.
