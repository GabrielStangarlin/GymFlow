# GymFlow — Tarefas por fazer

Lista de trabalho a dividir pelo grupo. Nada disto está implementado ainda.

**Estado atual (ponto de partida):**
- Frontend: Blade + Tailwind CSS v4 (via Vite), JS vanilla. Sem jQuery nem bibliotecas de UI.
- Estilos escritos diretamente nas views (`rounded border px-2 py-1`, etc.), repetidos página a página.
- Tabelas HTML simples com paginação no servidor (`paginate()` em `ClientController`, `SubscriptionController`, `AccessLogController`).
- Dashboard (`resources/views/crm/dashboard.blade.php`) com gráficos feitos à mão em `div`s e tabelas.
- Todas as ações (pagar, cancelar, ativar/desativar, filtros) são formulários com reload completo da página. A única exceção é o leitor de QR (`resources/js/scanner.js`), que já usa `fetch`.

> Qualquer biblioteca nova (npm ou composer) deve ser acordada com o grupo antes de ser instalada.

---

## 1. Reestruturação visual

### 1.1 Design system
Objetivo: deixar de repetir classes Tailwind em cada view e ter uma identidade visual consistente.

- [ ] Definir tokens no bloco `@theme` de `resources/css/app.css`: cores (primária, sucesso, aviso, erro, neutros), tipografia, espaçamentos, raios e sombras.
- [ ] Criar Blade components reutilizáveis em `resources/views/components/`:
  - `x-button` (variantes: primário, secundário, perigo; tamanhos)
  - `x-input`, `x-select`, `x-label`, `x-form-error`
  - `x-card` / `x-section`
  - `x-badge` (estados de assinatura: ativa, pendente, em atraso, terminada)
  - `x-alert` (mensagens de sucesso/erro da sessão)
  - `x-modal` (para substituir os `confirm()` nativos do browser)
  - `x-stat-card` (os cartões de números do dashboard)
- [ ] Rever o layout `layouts/crm.blade.php`: navegação lateral ou topo, estado ativo do menu, versão mobile.
- [ ] Criar uma página interna (só em ambiente local) que mostra todos os componentes, para servir de referência ao grupo.

**Critério de conclusão:** nenhuma view usa classes soltas para botões, inputs ou cartões; usa sempre os componentes.

### 1.2 Otimização das páginas
Aplicar o design system a todas as views e corrigir problemas de usabilidade.

- [ ] Migrar cada view para os novos componentes (lista em `resources/views/crm/**`, `auth/**`, `client/qr.blade.php`).
- [ ] Garantir que todas as páginas funcionam em ecrãs pequenos (tabelas com scroll horizontal ou layout em cartões).
- [ ] Uniformizar títulos, breadcrumbs e posição dos botões de ação.
- [ ] Estados vazios ("Sem alunos", "Sem registos") com o mesmo aspeto em todo o lado.
- [ ] Acessibilidade básica: `label` associado a cada input, contraste suficiente, foco visível, navegação por teclado.
- [ ] Rever a área do aluno (`client/qr.blade.php`) e o ecrã de leitura de QR (`crm/scan/show.blade.php`), que são usados em mobile/tablet.

### 1.3 Passar tabelas a DataTables
Tabelas afetadas: alunos, assinaturas, entradas/saídas, planos, funcionários e as tabelas da ficha do aluno (`clients/show`).

- [ ] **Escolher a biblioteca.** Nota: o DataTables "oficial" depende de jQuery, que o projeto não usa. Alternativas sem jQuery a avaliar:
  - DataTables 2 (aceita-se adicionar jQuery só para isto)
  - Simple-DataTables (vanilla JS, API parecida)
  - Grid.js ou Tabulator
- [ ] **Decidir client-side vs server-side:**
  - Client-side: mais simples, mas carrega todos os registos de uma vez. Aceitável para planos e funcionários (poucos registos).
  - Server-side: necessário para alunos, assinaturas e entradas/saídas, que crescem com o tempo e já estão paginados no servidor. Implica endpoints JSON que aceitem pesquisa, ordenação e paginação (pode usar o pacote `yajra/laravel-datatables` ou ser escrito à mão com Eloquent API Resources).
- [ ] Manter os filtros que já existem (ex.: `AccessLogFilterRequest`) a funcionar em conjunto com a pesquisa da tabela.
- [ ] Traduzir a interface da tabela para português (pesquisar, "a mostrar X de Y", sem resultados, etc.).
- [ ] Estilizar a tabela com os tokens do design system (não usar o tema por defeito da biblioteca).
- [ ] Colunas de ações (pagar, cancelar, editar) continuam a funcionar dentro da tabela — liga-se ao ponto 2.

**Critério de conclusão:** todas as listagens têm pesquisa, ordenação e paginação sem recarregar a página, e os testes de feature dos controllers continuam a passar.

### 1.4 Melhorar o dashboard
- [ ] Escolher uma biblioteca de gráficos: **Chart.js** (leve, simples) ou **ApexCharts** (mais interativa, tooltips e zoom de raiz).
- [ ] Substituir os gráficos feitos à mão:
  - "Alunos por plano" → gráfico de barras horizontais ou donut
  - "Mudanças de plano" → manter os totais em `x-stat-card` e mostrar a matriz como heatmap
  - "Horas de pico" (mapa de calor dia × hora) → heatmap da biblioteca
- [ ] Os dados continuam a vir do `DashboardStats`; expor um endpoint JSON (ex.: `GET /crm/dashboard/dados?from=&to=`) para os gráficos e para o filtro de datas funcionar sem reload (ponto 2).
- [ ] Rever os cartões de topo (ativos, em atraso, pendentes, dentro agora, recusas): ícones, cor por estado, link para a listagem filtrada.
- [ ] Opcional: atualizar o "Dentro agora" de X em X segundos.

---

## 2. Pedidos assíncronos (AJAX) ao backend

Objetivo: as ações comuns deixam de recarregar a página inteira.

### 2.1 Base comum
- [ ] Criar um helper JS único (ex.: `resources/js/http.js`) baseado em `fetch` (sem nova dependência), que:
  - envia o token CSRF (`<meta name="csrf-token">` no layout)
  - envia `Accept: application/json`
  - trata erros de forma uniforme: 422 (validação → mostrar erros nos campos), 403, 419 (sessão expirada → pedir login), 500
- [ ] Usar o `scanner.js` como referência, já que é o único sítio que faz isto hoje.
- [ ] Componente de notificação (toast) para mostrar sucesso/erro após cada ação.

### 2.2 Backend
- [ ] Controllers passam a responder JSON quando o pedido é AJAX (`$request->expectsJson()`), mantendo a resposta normal (redirect) para não partir nada. Os Form Requests já devolvem 422 em JSON automaticamente.
- [ ] Usar Eloquent API Resources para as respostas com dados.
- [ ] Testes de feature para cada endpoint: resposta JSON, validação (422) e permissões (staff vs admin).

### 2.3 Ações a converter (por prioridade)
1. [ ] Assinaturas: marcar como pago, regularizar dívida, cancelar (`subscriptions/_actions.blade.php`) — atualizar só a linha/estado.
2. [ ] Alunos: ativar/desativar, reenviar email de ativação.
3. [ ] Funcionários: ativar/desativar.
4. [ ] Filtros (entradas/saídas, dashboard) sem reload — ligado às DataTables e aos gráficos.
5. [ ] Formulários de criação/edição (inscrição, planos, alunos, periodicidades): submeter por AJAX e mostrar erros inline. Menor prioridade; o ganho é pequeno.

**Critério de conclusão:** nenhuma das ações 1–4 recarrega a página, os erros aparecem ao utilizador e os testes passam.

---

## Dependências entre tarefas

```
1.1 Design system ──► 1.2 Otimização das páginas
        │
        ├──► 1.3 DataTables ──┐
        └──► 1.4 Dashboard ───┤
                              ▼
2.1 Base AJAX ──► 2.2 Backend JSON ──► 2.3 Ações
```

- O **1.1** deve ser feito primeiro (ou pelo menos os componentes base: botão, input, card, badge), senão cada pessoa inventa o seu estilo.
- O **2.1 / 2.2** pode avançar em paralelo com o 1.1, porque é sobretudo JS e backend.
- **1.3** e **1.4** dependem dos endpoints JSON do ponto 2.2.

## Sugestão de divisão

| Frente | Tarefas | Perfil |
|---|---|---|
| A | 1.1 Design system + 1.2 Otimização das páginas | Frontend / CSS |
| B | 1.3 DataTables | Frontend + algum backend |
| C | 1.4 Dashboard | Frontend (gráficos) |
| D | 2.1 + 2.2 + 2.3 AJAX | Backend + JS |

## Decisões a tomar em grupo antes de começar
- [ ] Biblioteca de tabelas (com ou sem jQuery).
- [ ] Biblioteca de gráficos (Chart.js ou ApexCharts).
- [ ] Paleta de cores e tipografia do design system.
- [ ] Convenção de nomes dos endpoints JSON (mesmas rotas com `expectsJson()` ou rotas separadas).
