# TheSeguro

Sistema acadêmico de corretora fictícia: “o seguro” e “se der ruim, eu te seguro”. O escopo prevê consulta de veículos, cotações, propostas, contratos e assistência em um único aplicativo Laravel MVC com Blade.

## Estado atual

Projeto em desenvolvimento, com a estrutura inicial do Laravel, autenticação Breeze Blade e build dos assets configurados. A configuração do banco e as funcionalidades de negócio estão em implementação.

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

A configuração padrão utiliza PostgreSQL em `127.0.0.1:5432`. Ajuste as credenciais no arquivo `.env` conforme seu ambiente.

| Configuração | Valor |
|---|---|
| Conexão | `pgsql` |
| Banco de desenvolvimento | `theseguro_dev` |
| Banco de testes | `theseguro_test` |
| Sessões e cache | Arquivos locais |
| Fila | `sync` |

A configuração da conexão e a adaptação das migrations ao domínio estão pendentes. Os comandos de instalação preparam a aplicação e os assets, mas não criam bancos nem executam migrations. As migrations atuais são as originais do framework e ainda não representam o modelo de dados final.

Os testes de integração utilizam um banco separado, definido em `phpunit.xml`. Mantenha as credenciais apenas nos arquivos locais de ambiente, fora do controle de versão.

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
