# N3 Serverside API

API REST para gerenciamento de **componentes de hardware**, **categorias** e **equipamentos**. Utiliza **MariaDB** com **Doctrine ORM** e autenticação com tokens **JWT**.

| Alunos responsáveis |
| --- |
| Vitor Hugo Wille | 
| Gabriel dos Santos de Oliveira |

## Tecnologias

| Camada | Tecnologia |
| --- | --- |
| Linguagem | PHP 8.4 |
| Armazenamento | Doctrine ORM 3 + Doctrine DBAL 4 (MariaDB 11.4) |
| Bibliotecas externas | `vlucas/phpdotenv`, `firebase/php-jwt`, `symfony/string` |
| Infraestrutura | Docker + Docker Compose |
| Testes manuais | Bruno Collection em `bruno/` |

## Modelo do banco de dados

```text
categoria
├── id_categoria (PK)
└── nome_categoria (UNIQUE)

componente
├── codigo_componente (PK)
├── nome_componente
├── desc_componente
└── id_categoria (FK -> categoria)

equipamento
├── id_equipamento (PK)
└── nome_equipamento

equipamento_componente (N:N)
├── id_equipamento (FK -> equipamento)
└── codigo_componente (FK -> componente)

usuario
├── id_usuario (PK)
├── nome_usuario (UNIQUE)
├── email (UNIQUE)
├── senha (hash)
└── criado_em
```

### Regra de negócio do equipamento

Todo equipamento precisa ter:

- pelo menos **um componente de armazenamento**;
- pelo menos **um componente de processamento**.

A validação compara o nome normalizado da categoria. Assim, nomes como `Armazenamento`, `Armazenagem`, `Processamento` e `Processadores` são reconhecidos. Tentativas de criar ou atualizar um equipamento fora dessa regra retornam **HTTP 422**.

## Autenticação

- `POST /api/auth/login` é o único endpoint público (além de `GET /api/health`).
- Todos os endpoints em `/api/*` exigem `Authorization: Bearer <token>`.
- O token é um JWT HS256 com `sub`, `nome_usuario`, `email`, `iat` e `exp`.
- O tempo de expiração é configurável em `JWT_TTL` (padrão: 3600 segundos / 1 hora.).

Usuário criado automaticamente pelo setup:

```text
usuario: admin
senha:   admin123
```

## Como executar

### 1. Variáveis de ambiente

Se ainda não existir um `.env`, crie a partir do exemplo:

```bash
cp .env.example .env
```

Preencha as credenciais do MariaDB e defina um `JWT_SECRET`.

### 2. Instale as Dependências

Se você ainda não possui o Composer instalado, [baixe-o aqui](https://getcomposer.org). Para rodá-lo, você também vai precisar que o [PHP](https://www.php.net/downloads.php) esteja instalado e com as seguintes extensões habilitadas: `pdo_mysql`, `pdo_sqlite`, `dom`, `mbstring`, `intl`.

```bash
composer install
```

### 3. Subir os containers

```bash
docker compose up --build
```

A API ficará disponível em `http://localhost:8000`.

Na inicialização, o container executa `php bin/setup.php`, que:

1. cria/atualiza o esquema do banco via `SchemaTool` do Doctrine;
2. cria as categorias padrão;
3. cria dados de demonstração (quando `SEED_DEMO=true`);
4. cria o usuário `admin` caso ainda não exista.

Para executar o setup manualmente:

```bash
docker compose exec app php bin/setup.php
```

### 4. Execução local (sem container para a aplicação)

Com um MariaDB acessível em `DB_HOST:DB_PORT`:

```bash
composer install
php bin/setup.php
php -S 0.0.0.0:8000 -t public public/index.php
```

## Endpoints

### Autenticação e saúde

| Método | Rota | Autenticação | Descrição |
| --- | --- | --- | --- |
| GET | `/` | Pública | Página HTML com resumo da API |
| POST | `/api/auth/login` | Pública | Login com usuário/e-mail e senha |
| GET | `/api/auth/me` | JWT | Dados do usuário autenticado |
| GET | `/api/health` | Pública | Verificação de disponibilidade |

### Categorias

| Método | Rota | Descrição |
| --- | --- | --- |
| GET | `/api/categorias` | Lista categorias |
| POST | `/api/categorias` | Cria categoria |
| GET | `/api/categorias/{id}` | Busca categoria por ID |
| PUT/PATCH | `/api/categorias/{id}` | Atualiza categoria |
| DELETE | `/api/categorias/{id}` | Exclui categoria sem componentes |
| GET | `/api/categorias/{id}/componentes` | **Consulta componentes por categoria** |

### Componentes

| Método | Rota | Descrição |
| --- | --- | --- |
| GET | `/api/componentes` | Lista componentes |
| GET | `/api/componentes?categoria={id}` | **Consulta componentes por categoria** |
| GET | `/api/componentes?equipamento={id}` | **Consulta componentes por equipamento** |
| POST | `/api/componentes` | Cria componente |
| GET | `/api/componentes/{id}` | Busca componente por código |
| PUT/PATCH | `/api/componentes/{id}` | Atualiza componente |
| DELETE | `/api/componentes/{id}` | Exclui componente |

### Equipamentos

| Método | Rota | Descrição |
| --- | --- | --- |
| GET | `/api/equipamentos` | Lista equipamentos com componentes |
| POST | `/api/equipamentos` | Cria equipamento |
| GET | `/api/equipamentos/{id}` | Busca equipamento por ID |
| PUT/PATCH | `/api/equipamentos/{id}` | Atualiza equipamento |
| DELETE | `/api/equipamentos/{id}` | Exclui equipamento |
| GET | `/api/equipamentos/{id}/componentes` | **Consulta componentes por equipamento** |
| GET | `/api/equipamentos/{id}/componentes?categoria={id}` | Componentes do equipamento filtrados por categoria |

## Collection do Bruno

A collection está em [`bruno/`](bruno/).

### Pela interface do Bruno

1. Abra o Bruno. Baixe-o [clicando aqui](https://usebruno.com).
2. Clique em **Open Collection** e selecione a pasta `bruno/`.
3. Selecione o ambiente **Local** (arquivo `bruno/environments/Local.bru`).
4. Execute primeiro **Auth > Login**. O script pós-resposta salva o token na variável de ambiente `token`.
5. Execute as demais pastas/requisições. A autenticação Bearer é herdada da collection.

Pastas disponíveis:

- **Auth** -> login e dados do usuário autenticado.
- **Categorias** -> CRUD completo e consulta de componentes da categoria.
- **Componentes** -> CRUD completo, consulta por categoria e por equipamento.
- **Equipamentos** -> CRUD completo e consulta de componentes do equipamento.
- **Regras de negócio** -> cenários que devem falhar (422, 409 e 401).

As variáveis `categoriaId`, `componenteId` e `equipamentoId` são atualizadas automaticamente pelos scripts pós-resposta das requisições de criação.

### Pela linha de comando (`bruno-cli`)

Pré-requisitos:

- Node.js 18 ou superior instalado;
- API rodando em `http://localhost:8000` (`docker compose up`).

O comando deve ser executado **dentro da pasta `bruno/`**, pois o CLI só roda a partir da raiz de uma collection:

```bash
cd bruno
npx --yes @usebruno/cli run --env Local
```

O `npx` baixa o `@usebruno/cli` automaticamente na primeira execução. Também é possível instalá-lo globalmente:

```bash
npm install -g @usebruno/cli
bru run --env Local
```

Saída esperada ao final da execução:

```text
Execution Summary
┌───────────────┬────────────────┐
│ Metric        │     Result     │
├───────────────┼────────────────┤
│ Status        │     ✓ PASS     │
├───────────────┼────────────────┤
│ Requests      │ 29 (29 Passed) │
├───────────────┼────────────────┤
│ Tests         │     29/29      │
├───────────────┼────────────────┤
│ Assertions    │      0/0       │
├───────────────┼────────────────┤
│ Duration (ms) │     32652      │
└───────────────┴────────────────┘
```

Para executar apenas uma pasta ou requisição, informe o caminho relativo:

```bash
bru run "Regras de negocio" --env Local
bru run "Auth/01 - Login.bru" --env Local
```

## Estrutura do projeto

```text
.
├── bin/
│   └── setup.php               # criação do schema + seed
├── bruno/                      # collection de testes
├── public/
│   └── index.php               # front controller da API
├── src/
│   ├── Controller/             # regras HTTP/CRUD
│   ├── Entity/                 # entidades Doctrine
│   ├── Http/                   # request, response e roteador
│   ├── Repository/             # consultas customizadas
│   ├── Support/                # exceções e validação de entrada
│   └── bootstrap.php           # EntityManager, phpdotenv e configuração
├── composer.json
├── docker-compose.yaml
└── Dockerfile
```

## Configuração do `.env`

| Variável | Padrão | Descrição |
| --- | --- | --- |
| `APP_ENV` | `dev` | Ambiente da aplicação |
| `APP_DEBUG` | `true` | Exibe detalhes de erros internos |
| `DB_HOST` | `127.0.0.1` | Host do MariaDB |
| `DB_PORT` | `3306` | Porta do MariaDB |
| `DB_DATABASE` | `n3_serverside` | Nome do banco |
| `DB_USERNAME` | `root` | Usuário do banco |
| `DB_PASSWORD` | vazio | Senha do banco |
| `JWT_SECRET` | — | Chave de assinatura do JWT (obrigatória) |
| `JWT_TTL` | `3600` | Validade do token em segundos |
| `SEED_DEMO` | `true` | Cria categorias/componentes/equipamentos de demonstração |
