# GymFlow — Documento de Design

Data: 2026-11-04 · Estado: rascunho para aprovação

Legenda: **[decidido]** = decidido pelo Gabriel/grupo · **[minha decisão]** = decisão do Claude, a validar · **[builder]** = deixado a quem implementa.

## 1. Intenção

O GymFlow é um sistema de gestão de ginásio (CRM) feito como projeto da cadeira de Engenharia Informática. Os funcionários usam-no para inscrever alunos, gerir planos e assinaturas, marcar pagamentos e controlar entradas e saídas por QR code. Os dashboards mostram o estado do ginásio. O aluno só tem uma área onde vê o seu QR code.

O que o torna útil: a portaria é automática. O funcionário lê o QR e o sistema decide sozinho se a pessoa pode entrar (pagamento em dia e dentro do horário do plano) e regista a entrada ou a saída. Esses registos alimentam os dashboards de ocupação e de horários de pico.

## 2. Objetivos, não-objetivos e anti-objetivos

**Objetivos**
- Gestão de alunos, planos, assinaturas e pagamentos (manuais).
- Inscrição feita pelo funcionário, com conta do aluno criada automaticamente e ativada por email.
- Leitura de QR pela câmara do dispositivo, com registo automático de entrada/saída e recusa de entrada quando não é permitida.
- Dashboards: alunos, alunos por plano, upgrades/downgrades (de → para), renovações e horários de pico.

**Não-objetivos (fora do âmbito por agora)**
- Gateway de pagamento. Os pagamentos são marcados à mão. [decidido]
- API. Pode vir no futuro; nada é construído para ela agora. [decidido]
- Área do aluno para além do QR code (histórico, pagamentos, perfil). [decidido]
- Mudança de plano a meio de um período pago, e cálculo proporcional (prorata). [decidido]
- Marcação de aulas, vários ginásios, app móvel.

**Anti-objetivos (seria um falhanço mesmo que "funcione")**
- Deixar entrar alguém com pagamento em atraso ou fora do horário do plano.
- Perder histórico: alunos, assinaturas, pagamentos e acessos nunca são apagados, só desativados ou terminados. [decidido, via palpite aceite]
- Obrigar o funcionário a voltar a escrever os dados de um aluno que já existe.

## 3. Restrições técnicas [decidido]

- Laravel 13 (PHP 8.4), Blade simples + Tailwind 4 + Vite. Sem Livewire/Inertia/SPA.
- MySQL local: `DB_HOST=localhost`, `DB_PORT=3306`, `DB_USERNAME=root`, `DB_PASSWORD=123456789`. Nome da base de dados: `gymflow` [minha decisão, não foi indicado].
- `QUEUE_CONNECTION=database`. O email de ativação é enviado em fila, por isso é preciso `php artisan queue:work` a correr.
- Email via MailHog: SMTP em `localhost:1025`, sem autenticação/encriptação.
- Testes com PHPUnit (convenção do projeto). Formatação com Pint.
- Para adicionar dependências é preciso a aprovação do Gabriel (regra do projeto). Ver secção 10.
- A câmara no browser só funciona em `localhost` ou HTTPS. Testar a leitura num telemóvel pela rede local obriga a ter HTTPS (por exemplo, `php artisan serve` atrás de um túnel, ou Laravel Herd com HTTPS). [minha decisão: documentar, não resolver no código]

## 4. Utilizadores e acesso

| Papel | Como é criado | O que pode fazer |
|---|---|---|
| Admin | Seeder [decidido] | Tudo, incluindo criar/desativar funcionários [decidido] |
| Funcionário | Criado pelo Admin no CRM [decidido] | Tudo exceto gerir funcionários [minha decisão] |
| Aluno | Criado na inscrição [decidido] | Só vê o seu QR code [decidido] |

- Há um único ecrã de login para todos. Depois do login, cada papel vai para a sua área (CRM ou página do QR). [minha decisão]
- Interface em português de Portugal. Código, tabelas e identificadores em inglês, seguindo as convenções do Laravel. [minha decisão]
- Nome do conceito: no código é **`Client`** (tabela `clients`) e na interface é **"Aluno"/"Alunos"**. [decidido]
- Fuso horário da aplicação: `Europe/Lisbon`. Importa para "o mesmo dia", para os horários dos planos e para os dashboards. [minha decisão]

## 5. Fluxos

### 5.1 Inscrição (aluno novo)
1. O funcionário abre o separador **Inscrições** e preenche os dados do aluno: nome, email, telemóvel, NIF e data de nascimento [palpite aceite]. Escolhe também o plano, a forma de pagamento e a periodicidade.
2. Ao gravar, o sistema cria o utilizador (papel Aluno, sem palavra-passe utilizável), o registo do aluno com um token de QR e a assinatura.
   - O pagamento pode ser feito logo ou mais tarde. [decidido] O formulário tem uma opção "Pago agora" [minha decisão]. Sem ela, a assinatura fica **Pendente** (a aguardar ativação) e a entrada é recusada até o primeiro pagamento ser marcado.
3. Vai para a fila um email com um botão "Ativar conta". O link expira ao fim de 48 h e o funcionário pode reenviá-lo a partir da ficha do aluno. [palpite aceite]
4. O aluno clica, define a palavra-passe, fica autenticado e vê o QR code.
   - *[minha decisão]* Reutilizar o mecanismo nativo de reset de palavra-passe do Laravel (password broker) para a ativação e o reenvio, com um prazo de expiração próprio de 48 h para este fluxo.

### 5.2 Nova assinatura para um aluno existente (mudança de plano ou regresso)
- Escolhe-se o aluno já existente e cria-se uma nova assinatura sem voltar a introduzir os dados. [decidido]
- Só é permitido quando o período pago da assinatura atual já acabou. Não há mudanças a meio. [decidido]
- Também não é permitido enquanto houver **dívida por regularizar** de uma assinatura anterior (ver 5.3). [decidido]
- Tal como na inscrição, o primeiro pagamento pode ser marcado logo ou mais tarde (fica Pendente).
- A nova assinatura termina a anterior. Um aluno só tem uma assinatura ativa de cada vez. [palpite aceite]
- O tipo de mudança fica gravado na nova assinatura, comparando o **preço base mensal** do plano novo com o do plano anterior [palpite aceite]:
  - mais caro = **upgrade**; mais barato = **downgrade**; mesmo preço e plano diferente = **lateral** (ex.: Manhã → Tarde).
  - a primeira assinatura de um aluno = **nova**.
  - *[minha decisão]* mesmo plano numa nova assinatura (ex.: regresso depois de uma pausa) = **renovação**.
  - *[minha decisão]* a comparação usa os preços **atuais** dos dois planos no momento em que a nova assinatura é criada, para refletir a posição de cada plano e não aumentos de preço antigos.

### 5.3 Pagamentos e estado da assinatura
- O pagamento é só um sinal. O funcionário carrega num botão "Marcar como pago" e não há faturação nem recibos. [decidido] Fica registado o pagamento (valor, data, quem registou) e a assinatura passa a valer por mais um período.
- O primeiro pagamento ativa a assinatura, e a **data de início é a data desse primeiro pagamento**. [minha decisão, a partir de "o plano fica válido somente quando ele pagar"]
- A data do próximo pagamento conta a partir da data de início/1.º pagamento, somando a duração da periodicidade (1, 3, 6 ou 12 meses), e não a partir do dia em que se paga. [decidido] Exemplo: início a 10/01, mensal → vence a 10/02, depois a 10/03, mesmo que se pague a 12/02 (dentro dos 2 dias de tolerância; depois disso a assinatura termina com dívida).
- Um pagamento que prolonga a mesma assinatura (sem ser o primeiro) conta como **renovação** no histórico e nos dashboards. [decidido]
- Estados:
  - **Pendente**: criada mas ainda sem pagamento. A entrada é recusada. [decidido]
  - **Ativa**: hoje ≤ data de fim do período pago.
  - **Em atraso**: o período pago acabou há 1 ou 2 dias. A entrada é recusada, mas se pagar neste prazo a assinatura continua no mesmo ciclo. [decidido]
  - **Terminada**: ao fim de **2 dias em atraso**, a assinatura termina automaticamente [decidido]. Também termina quando é substituída por uma nova.
- Terminação automática: um comando agendado diário marca como terminadas as assinaturas com mais de 2 dias em atraso. [minha decisão] Isto obriga a ter `php artisan schedule:work` a correr, tal como o `queue:work`.
- **Dívida**: uma assinatura terminada por atraso fica com "dívida por regularizar", no valor de um período [minha decisão sobre o valor]. O funcionário regulariza-a com um botão "Marcar dívida como paga", e enquanto não o fizer não pode criar uma nova assinatura para o aluno. [decidido]
- Assinatura Pendente que nunca chega a ser paga: *[minha decisão]* o funcionário pode cancelá-la (fica Terminada, sem dívida). Não expira sozinha.
- **Desativar aluno**: não é permitido enquanto o aluno tiver uma assinatura Ativa ou Em atraso. [decidido; incluir "Em atraso" é minha decisão] Se tiver uma assinatura Pendente, ao desativar o aluno essa assinatura é cancelada. [minha decisão]
- Valor de cada pagamento = preço base mensal × n.º de meses × (1 − desconto da periodicidade). O preço e o desconto ficam **guardados na assinatura** no momento em que é criada. Alterar o plano mais tarde não muda assinaturas existentes. [palpite aceite]

### 5.4 Leitura de QR (entrada/saída)
1. O funcionário carrega em **Ler QR**, abre-se a câmara do dispositivo e lê o QR do telemóvel do aluno.
2. O QR tem um token aleatório e opaco do aluno (não o id). É fixo e não muda entre logins. [palpite aceite]
3. O servidor decide:
   - Aluno/token desconhecido ou aluno desativado → recusado.
   - Se a última passagem válida de **hoje** foi uma entrada → regista **saída**. A saída é sempre permitida. [decidido + minha decisão: sem validar horário na saída]
   - Caso contrário é uma **entrada**: recusa se a assinatura estiver Pendente, Em atraso ou não existir assinatura Ativa, ou se a hora atual estiver fora das janelas do plano. Se não, regista a entrada.
   - Assim, a 1.ª leitura do dia é entrada, a 2.ª é saída e uma 3.ª volta a ser entrada. [decidido para 1.ª/2.ª; alternância é minha decisão]
4. As tentativas recusadas também ficam registadas, com o motivo (`pendente`, `em_atraso`, `fora_de_horario`, `sem_assinatura`, `desconhecido`). [palpite aceite]
5. Se o aluno não registar a saída, no dia seguinte a primeira leitura volta a ser entrada. [palpite aceite]
6. O ecrã mostra o resultado de forma clara e grande (nome do aluno, ENTRADA / SAÍDA / RECUSADO + motivo) e depois volta a ler.
   - *[minha decisão]* Como as bibliotecas de câmara leem o mesmo código várias vezes por segundo, o ecrã pausa a leitura enquanto mostra o resultado (~3 s). O servidor ignora uma segunda leitura do mesmo aluno feita em menos de 60 s, para não registar uma saída acidental logo a seguir à entrada.

### 5.5 Área do aluno
- Depois do login o aluno vê apenas o seu QR code (e um botão de terminar sessão). [decidido]

## 6. Planos e periodicidades

- **Planos** (editáveis na BD/CRM) [decidido]: nome, preço base mensal, ativo/inativo e uma ou mais **janelas de horário** (hora início–hora fim) em que é permitido entrar.
  - Dados iniciais (seeder): Manhã, Tarde, Parcial (tudo exceto as horas de pico) e Total (sem restrição). O preço sobe de plano para plano, com Manhã = Tarde. [decidido] Os valores concretos ficam ao critério do builder.
  - *[minha decisão]* As janelas aplicam-se a todos os dias da semana (sem regras por dia). O plano Total tem uma janela 00:00–23:59.
- **Periodicidades** (editáveis na BD/CRM) [decidido]: nome, n.º de meses e % de desconto. Seeder: Mensal 1/0%, Trimestral 3/5%, Semestral 6/7%, Anual 12/10%.
- **Formas de pagamento**: *[minha decisão]* lista fixa no código (enum): Numerário, Multibanco, MB WAY, Transferência.

## 7. Dados (esboço) [minha decisão; nomes finais ao builder]

- `users`: nome, email, password, `role` (Admin/Staff/Client), ativo.
- `clients`: `user_id`, telemóvel, NIF (único), data de nascimento, `qr_token` (único), ativo.
- `plans`: nome, `monthly_price`, ativo. `plan_time_windows`: `plan_id`, `starts_at`, `ends_at`.
- `billing_periods`: nome, `months`, `discount_percent`.
- `subscriptions`: `client_id`, `plan_id`, `billing_period_id`, `payment_method`, `monthly_price` (cópia), `discount_percent` (cópia), `starts_on` (nulo enquanto Pendente), `paid_until`, `ended_on`, `end_reason` (replaced/overdue/cancelled), `debt_settled_at`, `change_type` (new/upgrade/downgrade/lateral/renewal), `previous_subscription_id`.
- `payments`: `subscription_id`, `amount`, `type` (period/debt), `paid_at`, `period_start`, `period_end`, `recorded_by`.
- `access_logs`: `client_id` (nulo se desconhecido), `type` (entry/exit/refused), `refusal_reason`, `scanned_at`, `scanned_by`.

## 8. Ecrãs do CRM

Menu lateral [estrutura aprovada no esboço]: **Dashboard · Ler QR · Inscrições · Alunos · Planos · Assinaturas · Entradas/Saídas** e, só para o Admin, **Funcionários**.

- **Alunos**: lista com pesquisa, ficha (dados, assinatura atual e histórico, acessos), editar, desativar, reenviar email de ativação e criar nova assinatura.
- **Planos**: CRUD de planos e respetivas janelas, mais as periodicidades/descontos.
- **Assinaturas**: lista filtrável por estado (pendente/ativa/em atraso/terminada/com dívida) e ações "Marcar como pago", "Marcar dívida como paga" e "Cancelar" (só Pendentes).
- **Entradas/Saídas**: registo filtrável por data, aluno e tipo (incluindo recusas).

## 9. Dashboard [estrutura aprovada; design visual fica para depois]

Filtro de período no topo, aplicado a tudo [palpite aceite].
- Números: alunos ativos, alunos em atraso, pessoas dentro agora, entradas recusadas no período. *[minha decisão]* acrescentar também "pendentes de ativação".
- Alunos por plano (assinaturas ativas).
- Mudanças de plano no período: contagem de upgrades, downgrades, laterais e renovações, e uma matriz "de → para" entre planos.
- Horários de pico: mapa de calor dia da semana × hora com a ocupação média (pessoas dentro), calculada a partir de entradas e saídas.
  - *[builder]* Como tratar entradas sem saída no cálculo da ocupação (por exemplo, assumir uma duração máxima).

## 10. Dependências [aprovadas]

- Gerar o QR: `bacon/bacon-qr-code` (Composer), que gera SVG no servidor.
- Ler o QR: `html5-qrcode` (npm), integrado com o Vite.
- Gráficos do dashboard: feitos só com HTML/Tailwind, sem biblioteca. [minha decisão]

## 11. Testes

Testes de feature (PHPUnit) para as regras que não podem falhar: decisão de entrada/saída/recusa (atraso, horário, alternância, dia seguinte, leitura duplicada), cálculo de `paid_until` a partir da data de início, valor com desconto, classificação upgrade/downgrade/lateral/renovação, proibição de nova assinatura a meio do período ou com dívida, Pendente → Ativa no 1.º pagamento, terminação automática ao fim de 2 dias em atraso, bloqueio de desativação de aluno, fluxo de ativação por email e permissões por papel.

## 12. Deixado ao builder

Design visual, nomes finais de classes e tabelas, estrutura de controllers/serviços, valores de preço do seeder, tratamento de saídas em falta na ocupação e escolha da lib de gráficos.
