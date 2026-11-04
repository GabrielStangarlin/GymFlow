# GymFlow

CRM para ginásios: inscrição de alunos, planos com horários e periodicidades, assinaturas e pagamentos, controlo de entradas e saídas por QR code e dashboard de ocupação. Os alunos ativam a conta por email e apresentam o QR à receção; o staff lê-o com a câmara.

## Requisitos

- PHP 8.4 e Composer
- Node.js e npm
- MySQL (base de dados `gymflow`)
- MailHog (SMTP em `localhost:1025`, interface em http://localhost:8025)

## Instalação

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Ajustar no `.env` as credenciais da base de dados (`DB_DATABASE=gymflow`, `DB_USERNAME`, `DB_PASSWORD`) e o email (`MAIL_HOST=localhost`, `MAIL_PORT=1025`). Criar a base de dados `gymflow` e correr:

```bash
php artisan migrate --seed
```

## Executar

Em quatro terminais:

```bash
php artisan serve
php artisan queue:work
php artisan schedule:work
npm run dev      # ou, uma vez: npm run build
```

`composer dev` arranca o servidor, a fila e o Vite num só terminal, mas não o `schedule:work`: este tem de continuar a correr noutro terminal (`php artisan schedule:work`), pois é ele que termina automaticamente as assinaturas em atraso.

Emails (ativação de conta) em http://localhost:8025.

## Credenciais

Administrador criado pelo seeder: `admin@gymflow.test` / `password`.

Alterar a palavra-passe do administrador de seed antes de qualquer utilização fora do desenvolvimento.

## Câmara

A leitura de QR só funciona em `http://localhost` ou HTTPS. Para testar a partir de um telemóvel na rede local, usar um túnel HTTPS.

## Testes

```bash
php artisan test
```
