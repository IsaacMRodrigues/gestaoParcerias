# Plataforma de Gestão de Parcerias - PGP

> **Instrução para IA:** Este README é um documento vivo. Sempre que realizar qualquer trabalho neste projeto, atualize as seções `## O que foi feito` e `## O que está sendo feito` antes de encerrar a conversa. Não omita etapas concluídas — o histórico completo importa para quem continuar o trabalho.

---

## Sobre o projeto

Plataforma web em **Laravel** para gestão completa de parcerias públicas entre Secretarias Municipais e OSCs (Organizações da Sociedade Civil). Cobre todo o ciclo: planejamento → proposta → análise → formalização → execução → monitoramento → prestação de contas.

**Repositório:** https://github.com/IsaacMRodrigues/gestaoParcerias  
**Stack:** Laravel 13, MySQL, TailwindCSS, Blade  
**Pacotes principais:** Laravel Breeze (auth), Spatie Laravel Permission (perfis), simple-qrcode (validação), dompdf (PDF dos documentos)  
**Equipe:** 2 desenvolvedores

---

## Setup do projeto (para quem clonar)

```bash
composer install
npm install && npm run build
cp .env.example .env
php artisan key:generate
# Configurar DB_DATABASE, DB_USERNAME, DB_PASSWORD no .env
php artisan migrate
php artisan db:seed
```

---

## Ordem de desenvolvimento planejada

1. [x] Estrutura base Laravel + autenticação + perfis/permissões
2. [x] Cadastro de usuários (CRUD + atribuição de perfil)
3. [x] Cadastro institucional (Órgãos/Secretarias e OSCs)
4. [x] Banco de Programas e Chamamentos Públicos
5. [x] Propostas + Plano de Trabalho
6. [x] Workflow de Análise e Aprovação
7. [x] Formalização (geração de instrumentos + assinatura eletrônica)
8. [x] Portal público + auto-cadastro de OSC + upload de documentos
9. [x] Módulo Unidade Gestora — 2.1 Planejamento (Processos, Termo de Referência, trâmite entre setores)
10. [x] Módulo Unidade Gestora — 2.2 Seleção/Celebração e 2.3 Execução do Concedente (incl. Ordem de Pagamento)
11. [~] Execução financeira (repasses, despesas, notas fiscais — falta integração bancária)
12. [ ] Monitoramento e Fiscalização
13. [ ] Prestação de Contas
14. [ ] Integrações externas (bancária, Diário Oficial)

---

## Fluxograma do sistema

### Ciclo da parceria (visão macro)

Do planejamento interno até a prestação de contas. As etapas tracejadas ainda não foram implementadas (fases 11–13).

```mermaid
flowchart TD
    PLAN["1 · Planejamento<br/>Processo + Termo de Referência<br/>(trâmite UG / SCP / SEPLAN)"] --> CH["2 · Chamamento Público<br/>publicado no Portal"]
    CH --> PROP["3 · Proposta da OSC<br/>+ Plano de Trabalho (metas/etapas)"]
    PROP --> AN["4 · Análise<br/>Parecer Técnico → Jurídico → Decisão"]
    AN -->|Diligência| PROP
    AN -->|Reprovada| FIM(["Encerrado"])
    AN -->|Aprovada| SEL["5 · Seleção e Celebração<br/>checklist documental (2.2)"]
    SEL --> FORM["6 · Formalização<br/>Instrumento + Aditivos / Apostilamento (2.3)"]
    FORM --> VIG["Assinatura + Publicação no DOE<br/>= Instrumento Vigente"]
    VIG --> EXE["7 · Execução<br/>repasses, despesas, notas fiscais"]
    EXE --> MON["8 · Monitoramento e Fiscalização"]
    MON --> PC["9 · Prestação de Contas"]

    classDef futuro fill:#f8fafc,stroke:#94a3b8,stroke-dasharray:4 3,color:#64748b;
    class EXE,MON,PC futuro
```

### Trâmite interno do Planejamento (Módulo UG 2.1)

O trâmite **bifurca na análise do SCP (etapa 1)**, quando a modalidade é decidida. As etapas 0–4
são comuns às duas rotas; a partir da 5 o caminho muda (`Processo::ETAPAS` × `ETAPAS_DISPENSA`,
resolvidos por `$processo->etapas()`). O SCP pode **devolver** para a UG. Use sempre
`$processo->etapas()` — nunca a constante estática.

```mermaid
flowchart LR
    A(["Abertura"]) --> E1["UG<br/>Ofício + Termo de<br/>Referência (assinar)"]
    E1 --> E2{"SCP analisar<br/>+ definir<br/>modalidade"}
    E2 -->|rejeita| E1
    E2 -->|aprova| E3["UG<br/>Solicitar Parecer<br/>Financeiro à SEPLAN"]
    E3 --> E4["SEPLAN<br/>emitir Parecer<br/>Financeiro"]
    E4 --> E5["UG<br/>Abertura do<br/>Processo (assinar)"]
    E5 -->|Chamamento Público| C6["SCP<br/>elaborar Edital"]
    C6 --> C7["UG<br/>assinar Edital +<br/>Solicitar Parecer<br/>Jurídico (assinar)"]
    C7 --> C8["PJ<br/>emitir Parecer<br/>Jurídico (assinar)"]
    C8 --> C9["SCP<br/>publicar no site"]
    C9 --> Z(["Concluído"])
    E5 -->|Dispensa/Inexig.| D6["UG<br/>emitir e assinar<br/>Justificativa<br/>(+ Parecer CNAS)"]
    D6 --> D7["SCP<br/>publicar<br/>Justificativa"]
    D7 --> Z
```

> O número do processo segue o padrão `UG.Sequencial.Ano.Esfera` (ex.: `0206.0133.2026.01`).
> **Rota Chamamento Público (9 etapas):** a **Procuradoria Jurídica (`pj`)** entra no trâmite — no
> mesmo passo em que assina o Edital, a UG preenche e assina a **Solicitação de Parecer Jurídico**
> (Modelo VI) e encaminha à PJ, que emite o **Parecer Jurídico** e devolve à SCP para publicação.
> **Rota Dispensa/Inexigibilidade (7 etapas):** no lugar do Edital+Jurídico, a **UG emite e assina a
> Justificativa** de Dispensa/Inexigibilidade (art. 30–32 da Lei 13.019/2014) — com o **Parecer
> Técnico CNAS** opcional, só nas parcerias do SUAS — e o SCP publica.

---

## Perfis de usuário (lista oficial — Módulo 1)

São 21 perfis. Um usuário pode ter **vários**; os marcados 🔒 são **exclusivos** de um setor
(só atribuíveis a quem é lotado nele).

| Slug | Perfil | 🔒 Setor | Permissões |
|---|---|---|---|
| `administrador_setorial` | Administrador Setorial | TI | todas |
| `responsavel_unidade_gestora` | Responsável da Unidade Gestora | UG | planejamento, chamamentos, propostas, decisão, formalização |
| `analista_tecnico_scp` | Analista Técnico do SCP | SCP | planejamento, chamamentos |
| `responsavel_publicacao` | Responsável pela Publicação | SCP | chamamentos |
| `analista_orcamentario_financeiro` | Analista Orçamentário Financeiro | SEPLAN | planejamento |
| `analista_juridico` | Analista Jurídico | — | parecer jurídico, planejamento (acesso à caixa) |
| `analista_viabilidade_tecnica` | Analista de Viabilidade Técnica | — | parecer técnico |
| `analista_aditivo_apostilamento` | Analista de Aditivo e Apostilamento | — | formalização |
| `analista_prestacao_contas_previa` | Analista de Prestação de Contas Prévia | — | prestação de contas |
| `comissao_selecao` | Comissão de Seleção | Com. Seleção | propostas, parecer técnico, decisão |
| `comissao_monitoramento_avaliacao` | Comissão de Monitoramento e Avaliação | Com. Avaliação | monitoramento |
| `gestor_parceria` | Gestor da Parceria | gestor | planejamento, monitoramento |
| `cadastrador` | Cadastrador | — | chamamentos, propostas, formalização |
| `contador` | Contador | — | prestação de contas |
| `encaminhador` | Encaminhador | — | formalização |
| `operador_ordem_pagamento` | Operador de Ordem de Pagamento | — | *(futuro: pagamento)* |
| `aprovador_assinatura_eletronica` | Aprovador de Assinatura Eletrônica | — | *(futuro: assinatura)* |
| `auditor_externo` | Auditor Externo | — | todas (**somente leitura**) |
| `auditor_geral` | Auditor Geral | — | todas (**somente leitura**) |
| `analista` | Analista (em descontinuação) | — | acesso básico |
| `responsavel_legal` | Responsável Legal | OSC | só portal |

### O que cada área de permissão libera (em telas)

| Permissão | Dá acesso a |
|---|---|
| `cadastros` | Menu **Cadastros**: Usuários, Órgãos/Secretarias, OSCs |
| `planejamento` | Menu **Planejamento**: Processos, Termo de Referência, peças e **Caixa de Entrada** (trâmite) |
| `chamamentos` | Menu **Programas**: Programas, Chamamentos e **Seleção** (checklist 2.2) |
| `propostas` | Menu **Propostas**: Propostas + Plano de Trabalho (metas/etapas) |
| `pareceres_tecnico` | Emitir **Parecer Técnico** na análise da proposta |
| `pareceres_juridico` | Emitir **Parecer Jurídico** na análise da proposta |
| `pareceres_decisao` | Emitir a **Decisão/Seleção** final da proposta |
| `formalizacao` | Menu **Instrumentos**: Instrumentos, Aditivos, Apostilamento e Documentação (2.3) |
| `ordem_pagamento` | Emitir **Ordens de Pagamento** no instrumento vigente (2.3.1) |
| `execucao` | **Execução Financeira**: repasses, despesas, notas fiscais e saldo (4.4) |
| `monitoramento` | Monitoramento e Fiscalização *(módulo futuro)* |
| `prestacao_contas` | Prestação de Contas *(módulo futuro)* |

> As permissões são definidas em `RolesSeeder`, aplicadas por middleware nas rotas + `@can`
> na navegação (o usuário só vê no menu o que pode acessar). **Auditores** veem tudo mas não
> gravam (middleware `readonly`). **Responsável Legal** só acessa o portal (middleware `staff`).
> Setor de lotação do usuário em `User::LOTACOES`; exclusivos em `User::PERFIS_EXCLUSIVOS`.

---

## O que foi feito

- [2026-06-16] Especificação inicial recebida (`txt.txt`) e analisada
- [2026-06-16] Repositório privado criado no GitHub
- [2026-06-16] Projeto Laravel 13 criado com Breeze (Blade + TailwindCSS)
- [2026-06-16] Spatie Laravel Permission instalado e configurado
- [2026-06-16] `User` model atualizado com `HasRoles`
- [2026-06-16] `RolesSeeder` criado com os 9 perfis do sistema
- [2026-06-16] `.env` configurado para MySQL e locale `pt_BR`
- [2026-06-16] CRUD de usuários completo (listagem, criação, edição, remoção)
  - Campos: nome, e-mail, CPF, telefone, senha, perfil, status
  - Paginação, feedback de sucesso, confirmação de remoção
  - Link "Usuários" adicionado na navegação principal
- [2026-06-16] Cadastro institucional completo (Órgãos/Secretarias e OSCs)
  - Órgãos: nome, sigla, CNPJ, e-mail, telefone, endereço completo, status
  - OSCs: nome, tipo, CNPJ, contato, endereço, responsável legal, status
  - Componente reutilizável `x-address-fields` para bloco de endereço
  - Componente `x-flash-message` para mensagens de sessão
  - Dropdown "Cadastros" na navegação com Órgãos e OSCs

---

- [2026-06-16] Banco de Programas e Chamamentos Públicos
  - Programas: tipo de instrumento (Fomento/Colaboração/Cooperação), órgão, valor, vigência, status
  - Chamamentos: aninhados ao programa, tipo, valor, datas, status com 6 etapas
  - Chamamentos acessados por `/programas/{id}/chamamentos`
  - Navegação reorganizada: Usuários entrou no dropdown Cadastros; Programas no topo

---

- [2026-06-16] Propostas e Plano de Trabalho
  - Proposta vincula Chamamento + OSC com dados financeiros e datas
  - Botão "Submeter Proposta" muda status e registra timestamp
  - Plano de Trabalho: Metas (indicador, meta quantitativa, datas)
  - Etapas dentro de cada Meta (responsável, período, recursos)
  - Página show da proposta concentra todo o plano em uma única tela
  - Rotas aninhadas: propostas → metas → etapas

---

- [2026-06-16] Workflow de Análise e Aprovação
  - Pareceres: Técnico → Jurídico → Decisão Final (cada um desbloqueia o próximo)
  - Resultados: Aprovado / Aprovado com Ressalvas / Reprovado / Diligência
  - Transições de status automáticas na proposta via mapa de transições no model
  - Diligência criada junto ao parecer; OSC responde na página da diligência
  - Proposta volta para `em_analise` automaticamente quando todas as diligências são respondidas
  - Seção "Análise" aparece no show da proposta com botões contextuais por etapa

---

- [2026-06-16] Formalização — Instrumentos e Termos Aditivos
  - Models `Instrumento` e `Aditivo` com constantes TIPOS, STATUS, STATUS_COLORS
  - Instrumento vincula Proposta (1-para-1), registra número, tipo, objeto, valores, vigência
  - Método `dataFimVigente()` considera o último aditivo de prazo
  - Botão "Formalizar Instrumento" aparece na proposta aprovada sem instrumento
  - Botão "Ver Instrumento" aparece na proposta que já tem instrumento
  - Fluxo de status: Minuta → Assinado → Vigente (via publicação no DOE)
  - PATCH `assinar` e PATCH `publicar` para transições rápidas de status
  - Termos aditivos aninhados ao instrumento (prazo, valor, objeto, apostilamento)
  - Minuta para impressão (view sem layout, botão nativo de print)
  - "Instrumentos" adicionado na navegação principal
  - Tabelas `instrumentos` e `aditivos` migradas com sucesso

---

- [2026-06-17] Portal público, auto-cadastro de OSC e upload de documentos
  - Portal `/portal` lista chamamentos `publicado`/`em_inscricao` sem login
  - `status_efetivo` deriva "em inscrição" a partir das datas de inscrição
  - Auto-cadastro `/cadastro/osc` cria User (representante_legal) + Osc vinculados
  - Upload de documentos na proposta (admin e portal), download e remoção
  - Middleware `staff`: representante_legal só acessa o portal, não a área admin

---

- [2026-06-17] Módulo Unidade Gestora — 2.1 Planejamento (Processos)
  - `Processo` com número automático (NNNN/AAAA), vinculado à Unidade Gestora (Órgão)
  - `TermoReferencia` estruturado com as 5 seções do documento (2.1–2.5)
  - `ProcessoPeca`: Ofício, Parecer Financeiro, Abertura de Processo (texto + assinatura)
  - **Assinatura simples**: registra quem assinou e data/hora (carimbo)
  - **Trâmite real entre setores** (UG → SCP → SEPLAN → SPC) com caixa de entrada
    - Campo `setor` no usuário define em qual caixa ele recebe os processos
    - Histórico de tramitação (enviar / receber / parecer por setor)
    - Só o setor que está com o processo pode encaminhá-lo
  - Alertas automáticos de conformidade (🔴 dotação, objeto genérico, meta sem indicador,
    sem justificativa, sem valor / 🟢 apto para abertura)
  - UG conclui o planejamento com "Marcar Apto" quando não há pendências
  - Tabelas: `processos`, `termo_referencias`, `processo_pecas`, `tramitacoes` + `setor` em `users`

---

- [2026-06-17] Módulo Unidade Gestora — 2.2 Seleção/Celebração e 2.3 Execução
  - **Motor genérico de peças documentais** (`Peca`, polimórfico via `pecaable`)
    - Cada item é "modelo padrão" (texto + assinatura simples) ou "arquivo" (upload)
    - Templates por categoria em `Peca::TEMPLATES`; `sincronizar()` cria o checklist (idempotente)
    - Progresso calculado sobre itens obrigatórios preenchidos
  - **2.2 Seleção e Celebração** anexada ao Chamamento (`/chamamentos/{id}/selecao`)
    - Template escolhido pelo tipo: Chamamento Público vs Dispensa/Inexigibilidade
    - Edital, comissão, pareceres, publicações, resultados, homologação, etc.
    - Link "Seleção" na listagem de chamamentos
  - **2.3 Execução** anexada a cada Aditivo (`.../aditivos/{id}/documentacao`)
    - Apostilamento usa checklist próprio; demais aditivos usam checklist de aditivo
    - Link "Documentação" em cada aditivo no show do instrumento
  - **Adiado**: 2.3.1 Ordem de Pagamento (envolve dados bancários — integração bancária
    foi definida para a última fase do projeto)
  - Tabela: `pecas` (polimórfica)

---

- [2026-06-17] Controle granular de acesso por perfil
  - Novo perfil **Administrador** (acesso total, gerencia usuários/órgãos/OSCs)
  - 10 permissões por área definidas em `RolesSeeder` e atribuídas via matriz
  - Rotas admin agrupadas por `permission:<área>`; navegação filtrada com `@can`
  - **Controle Interno**: acesso de leitura a tudo, escrita bloqueada (middleware `readonly`)
  - Pareceres autorizados por tipo (técnico/jurídico/decisão) no controller
  - Campo `setor` no usuário (já existente) continua governando o trâmite do planejamento
  - Antes: qualquer servidor autenticado fazia tudo. Agora cada perfil vê/faz só a sua área.

---

- [2026-06-18] Numeração padronizada do Processo — `UG.Sequencial.Ano.Esfera`
  - Número do Processo passa a seguir o padrão municipal `UG.NNNN.AAAA.EE` (ex.: `0206.0133.2026.01`)
    - UG = código da Unidade Gestora (4 díg.) · Sequencial = contador contínuo e global (nunca reinicia)
      · Ano = ano de abertura do processo · Esfera = concedente (01 Município, 02 Estado, 03 União, 04 Outros)
  - Campo `codigo` adicionado ao cadastro de Órgãos; de-para das 26 UGs em `UnidadesGestorasSeeder`
  - Esfera como constante `Processo::ESFERAS`, selecionável no formulário (default Município)
  - `Processo::proximoSequencial()` + `Processo::formatarNumero()`; geração no `ProcessoController@store`
    (valida esfera e exige que a UG tenha código)
  - Migrations: `codigo` (único) em `orgaos`; `sequencial` (único) + `esfera` em `processos`
  - **A confirmar com a área:** ano usado é o de abertura do processo (não do instrumento, que ainda não
    existe nessa fase); e qual UG entra quando há fundo (ex.: FIA `0213` vs Sec. de Trabalho `0209`)

---

- [2026-06-19] Perfis do Módulo 1 (reescrita do controle de acesso)
  - Substituídos os 9 perfis antigos pelos **21 perfis oficiais do Módulo 1**
  - Usuário pode ter **vários perfis** (cadastro com seleção múltipla)
  - **Perfis exclusivos** travados por setor de lotação (`User::PERFIS_EXCLUSIVOS`)
  - Setor de lotação ampliado (`User::LOTACOES`: UG, SCP, SEPLAN, PJ, TI, Comissões, Gestoria, OSC)
  - Auditores (Externo/Geral) com acesso somente leitura; Responsável Legal só portal
  - Permissões por área inalteradas — apenas remapeadas para os novos perfis
  - Setores do trâmite (Módulo 2) corrigidos: SCP/SEPLAN/PJ (SPC era erro); fluxo atualizado
  - Respostas do cliente registradas em `Docs. Desenvolvimento/respostas-cliente.md`

---

- [2026-06-19] Módulo 2 — Trâmite guiado (fiel ao fluxo do cliente)
  - 7 etapas em `Processo::ETAPAS`: UG → SCP → SEPLAN → UG → SCP → UG → SCP (publicação)
  - Coluna `etapa` controla a posição no fluxo (setores se repetem)
  - **Encaminhar** avança automaticamente para o próximo setor (sem escolha livre)
  - **Devolver** retorna à etapa anterior exigindo motivo; **Receber** registra o recebimento
  - 1ª etapa só avança com o planejamento **apto** (alertas de conformidade)
  - Última etapa (SCP) → **Concluir** marca o processo como publicação (trâmite externo)
  - Stepper visual no topo do processo mostrando a etapa atual
  - Só o setor que está com o processo pode movimentá-lo (validação no controller)

---

- [2026-06-19] Módulo 2 alinhado às respostas da cliente + modelos (Arquivos I–V)
  - **Termo de Referência** reescrito conforme o modelo real (Arquivo II): descrição da
    realidade, justificativa, objeto, objetivos, orçamento (valor/dotação/ficha/fonte), prazo
  - 🆕 Peça **Pedido de Parecer Financeiro** (Arquivo III) — SCP, na etapa de análise
  - 🆕 Peça **Edital** — SCP elabora (editor de texto) e **UG assina** na etapa seguinte
  - Documentos abrem **pré-preenchidos com o texto-modelo** do cliente (`ProcessoPeca::MODELO`)
  - Cada peça travada por **setor + etapa**; edição e assinatura podem ser de setores diferentes
    (`podeEditarConteudo` × `podeAssinar`) — caso do Edital (SCP edita, UG assina)
  - **UG automática**: usuário ganha `orgao_id` (Secretaria); na abertura do processo a UG
    vem preenchida da lotação (fallback: seleção manual p/ quem não tem UG)
  - Modelos do cliente em `Docs. Desenvolvimento/Modelos/`; respostas em `respostaduvidas.md`

---

- [2026-06-19] Módulo 2 — editor rico, documentos modelo e refinos do fluxo
  - **Editor rico TinyMCE** (self-hosted, `license_key: gpl`, offline) substitui o textarea,
    com **suporte a tabelas** (usado no Parecer Financeiro), fonte, alinhamento, listas, etc.
    Init em `resources/js/editor.js` sobre `textarea[data-editor-rico]`
  - **Termo de Referência virou documento modelo** (peça `termo_referencia`) editável no
    editor rico, igual ao Ofício — removidos model/tabela/controller estruturados antigos
  - Todos os documentos modelo são **HTML** (`ProcessoPeca::MODELO`), pré-preenchidos e fiéis
    aos Arquivos I–V; **brasão** (`https://pmsgra.net/logo.png`) no cabeçalho em tabela (logo ao lado)
  - **Fluxo corrigido (8 etapas)**: UG (Ofício+TR) → SCP **analisa e aprova/rejeita** → UG
    **solicita o Parecer** (Pedido de Parecer) → SEPLAN (Parecer) → UG (Abertura) → SCP (Edital)
    → UG (assina Edital) → SCP (publicação externa)
  - Etapa de análise do SCP com botões **Aprovar** / **Rejeitar** (`Processo::etapaEhAnalise`)
  - **Recebimento obrigatório**: só edita/assina após “Registrar Recebimento”
    (`Processo::aguardandoRecebimento` em `podeEditarConteudo`/`podeAssinar`)
  - Alertas de conformidade são **consultivos** (não bloqueiam encaminhar)

> **Editor:** TinyMCE self-hosted via npm (`npm install`), empacotado pelo Vite — **rode
> `npm run build`** após clonar. Interface do editor em inglês (ícones universais); pacote
> pt-BR pode ser adicionado depois. O brasão vem de URL pública (precisa de internet ou baixar local).

---

- [2026-06-25] Identidade visual unificada (PGP) e UX
  - Login com gradiente indigo + marca PGP; navegação rebrandizada com badge PGP e chip de
    usuário (avatar de iniciais + perfil + setor)
  - **Dashboard real** (substitui o stub): cards de métricas por permissão, caixa de entrada
    do setor e atalhos rápidos (`x-stat-card`, `x-quick-link`)
  - Portal público enriquecido: hero, empty-state e seção "Como participar"
  - Botões/links no tema indigo; shell com título PGP e rodapé

- [2026-06-25] Ordem de Pagamento (Módulo UG 2.3.1)
  - `OrdemPagamento` (várias por instrumento vigente): documento modelo padrão com assinatura
    eletrônica (carimbo + QR + validação pública) e anexo de dados bancários
  - Validação pública (`/validar`) estendida para reconhecer OP além das peças de processo
  - Permissão `ordem_pagamento` (perfil `operador_ordem_pagamento` + Responsável da UG)

- [2026-06-25] Preenchimento automático ("puxar") dos modelos
  - `App\Support\Modelo` substitui marcadores `{{token}}` pelos dados conhecidos ao criar o
    documento: nº do processo, Unidade Gestora, responsável, data; na OP: nº da OP, instrumento,
    OSC favorecida. O conteúdo autoral/orçamentário permanece manual. Nº do Ofício é manual
    (numeração externa ao sistema).

- [2026-06-25] Execução Financeira (Módulo 4.4 — fase 11, parcial)
  - `Repasse` e `Despesa` (com **natureza de despesa** e upload de **nota fiscal**) no instrumento
  - **Painel de saldo**: total repassado, total gasto, saldo, % executado + alertas de
    inconsistência (saldo negativo, despesa sem NF) e resumo por natureza
  - Permissão `execucao` (Responsável da UG + Gestor da Parceria)
  - Falta: rendimentos, conciliação/integração bancária (fase 14), auto-relato pela OSC

- [2026-06-25] Acesso do jurídico à caixa (permissão `planejamento` ao `analista_juridico`) e
  **tela 403 amigável** (`errors/403.blade.php`) na identidade PGP, exibindo a mensagem específica
  do `abort()` quando houver.

- [2026-06-29] Módulo 2 — etapa da Procuradoria Jurídica no trâmite (8 → 9 etapas)
  - Depois do Edital, antes da publicação, o trâmite ganha o Jurídico em `Processo::ETAPAS`:
    **(7) UG** assina o Edital **e**, no mesmo passo, preenche e assina a **Solicitação de Parecer
    Jurídico** (Modelo VI, texto do Arquivo VI do cliente) e encaminha à PJ; **(8) PJ** preenche e
    assina o **Parecer Jurídico** e devolve à SCP; **(9) SCP** publica
  - Duas peças novas em `ProcessoPeca` (`solicitacao_parecer_juridico`, `parecer_juridico`) com modelo
    pré-preenchido (a da PJ é modelo-padrão em branco, a definir com a Procuradoria), assinatura
    carimbo+QR e validação pública — reaproveitam todo o motor de peças/tramitação existente
  - Migration `add_etapa_juridico_to_processos`: cria as peças nos processos já abertos e remapeia
    quem estava na antiga última etapa (7 = publicação) para a nova (8)
  - **Setup:** o usuário que atua na etapa do PJ precisa de **lotação `pj`** + perfil com permissão
    `planejamento` (ex.: `analista_juridico`)

- [2026-06-29] Módulo 2 — modalidade da seleção definida pelo SCP na análise
  - Na etapa de análise (SCP), ao **Aprovar**, o setor escolhe a **modalidade** que define o caminho
    do processo: **Chamamento Público**, **Dispensa** ou **Inexigibilidade** (`Processo::MODALIDADES`,
    com descrições em `MODALIDADES_DESC`); obrigatório para aprovar (validado em `TramitacaoController`)
  - Coluna `modalidade` em `processos`; a escolha aparece no card do fluxo e orienta o passo do Edital
    (Edital × justificativa de dispensa/inexigibilidade) e o checklist de Seleção 2.2
  - Cabeçalho (brasão) + título "PEDIDO DE PARECER FINANCEIRO" no modelo do `pedido_parecer`, que era
    o único documento financeiro sem cabeçalho — alinhado aos demais

- [2026-06-29] Checklists 2.2/2.3 — "puxar do módulo Gestão de Parcerias"
  - Itens de arquivo marcados como puxáveis (`Peca::PUXAVEIS`) agora podem ser preenchidos a partir
    dos documentos que a OSC já enviou na proposta, além do upload manual
  - Seleção 2.2 (Dispensa/Inexigibilidade): Plano de trabalho, Documentos de habilitação;
    Aditivo/Apostilamento 2.3: Manifestação da OSC, Plano atualizado, Orçamento, Extratos, etc.
  - Origem resolvida por `Peca::documentosDisponiveis()` (Chamamento → propostas; Aditivo →
    instrumento → proposta); `PecaController@puxar` copia o arquivo para a peça (rota `pecas.puxar`)
  - UI no partial compartilhado `pecas/_checklist` (vale para Seleção e Documentação do Aditivo)

- [2026-06-29] Processo — baixar peças selecionadas em PDF (individual)
  - Checkbox ao lado de cada documento preenchido na lista "Peças do Processo" + botão
    "Baixar selecionados (PDF)" → **1 documento** baixa o PDF direto; **vários** vêm num **ZIP com
    um PDF separado por documento** (download individual), nomeados na ordem oficial
  - PDF gerado no servidor com **dompdf** (`ProcessoPecaController@imprimirLote` → `pdfDaPeca`), brasão
    remoto + carimbo de assinatura + QR (SVG embutido) nos assinados; view `processos/peca-pdf`
  - `imprimirLote` valida que as peças pertencem ao processo e ignora as vazias

- [2026-06-29] Validação pública mostra uma cópia do documento
  - Em `/validar/{codigo}` (pelo QR ou pelo código), além dos metadados de autenticidade, a página
    agora exibe a **cópia fiel do documento assinado** (conteúdo HTML renderizado com brasão/tabelas)
  - `ValidacaoController@mostrar` passa o `conteudo`; vale para peça de processo e ordem de pagamento

- [2026-06-29] Onboarding de usuários com aprovação do administrador
  - **Auto-cadastro** (`/register`) reescrito para servidores internos: informa dados, **setor**,
    **Secretaria/UG** (lista de Órgãos) e **escolhe a própria senha** → cria usuário **pendente, sem
    perfil e sem login**. OSC continua pelo portal (`/cadastro/osc`)
  - **Trava de login** (`LoginRequest`/`User::podeAutenticar`): pendente, recusado ou inativo não
    autentica, com mensagem explicando o motivo (antes o login não checava `status`)
  - **Aprovação pelo admin** (permissão `cadastros`): tela `usuarios/pendentes` lista os cadastros,
    o admin **atribui os perfis** (confirma setor/UG) e **aprova**, ou **recusa** com motivo
    (`UserController@pendentes/aprovar/recusar`); badge de contagem na navegação e na lista de Usuários
  - **Subusuários da UG**: o `responsavel_unidade_gestora` cadastra usuários da sua Secretaria
    (`/meus-usuarios`, `SubusuarioController`) — herdam a UG, ficam **pendentes** e o admin define os
    perfis na aprovação
  - Migration `add_approval_to_users` (`approval_status` default `aprovado`, `approved_at/by`,
    `created_by`, `solicitacao_obs`, `rejeitado_motivo`) — usuários existentes seguem aprovados.
    Usuários criados pelo admin (CRUD) e OSCs entram já aprovados

- [2026-06-29] Interface em pt-BR + política de senha
  - `APP_LOCALE=pt_BR` (estava `en`); senha mínima **6 caracteres** (texto e/ou números, sem
    complexidade) via `Password::defaults()` no `AppServiceProvider` + ajustes em UserRequest/OscRegistro
  - Traduções: `lang/pt_BR.json` (strings do Breeze: login, perfil, etc.) e
    `lang/pt_BR/{validation,auth,passwords,pagination}.php` (mensagens do framework); rótulo
    "Dashboard" → "Painel". Mensagens de validação e telas de auth/perfil agora em português

- [2026-06-29] Refinamentos de UI do onboarding
  - **Navbar responsiva** corrigida (estava espremida): quebra de linha desligada nos itens,
    container mais largo (`max-w-screen-2xl`) e breakpoint do menu `sm` → `lg` (menu completo só
    em telas grandes; hambúrguer nas demais)
  - **Matrícula** virou campo **obrigatório** e **Função/observação** passou a exigir preenchimento
    no cadastro de subusuário da UG (`SubusuarioController` + view); migration `add_matricula_to_users`
    (`matricula` única, opcional no modelo)

- [2026-07-03] Módulo 2 — rota de **Dispensa/Inexigibilidade** no trâmite (Lei 13.019/2014, arts. 30–32)
  - Quando o SCP decide **Dispensa** ou **Inexigibilidade** na análise (etapa 1), o trâmite passa a
    seguir uma rota própria de **7 etapas** (`Processo::ETAPAS_DISPENSA`), em vez das 9 do Chamamento:
    depois da Abertura, no lugar de *Edital → Solicitação/Parecer Jurídico*, a **UG emite e assina a
    Justificativa** de Dispensa/Inexigibilidade e o **SCP publica**. Etapas 0–4 são idênticas nas duas rotas
  - `ETAPAS` virou **ciente da modalidade**: `Processo::etapas()`/`ehDispensa()` resolvem a sequência;
    todos os consumidores (`etapaInfo`, `proximoSetor`, `setorAnterior`, `totalEtapas`, `pendenciasParaAvancar`,
    `TramitacaoController@avancar/devolver`, stepper e lista de peças no `show`) passaram a usar `etapas()`
  - Duas peças novas em `ProcessoPeca`: **Justificativa de Dispensa/Inexigibilidade** (UG, etapa 5,
    modelo com cabeçalho + fundamento legal) e **Parecer Técnico (CNAS)** — opcional, só parcerias do SUAS.
    Reaproveitam o motor de assinatura (carimbo+QR), validação pública e PDF já existentes
  - Migration `add_rota_dispensa_pecas`: cria as peças nos processos abertos e **realinha** os de
    dispensa/inexigibilidade que já estavam além da rota curta (a publicação final 8→6; Edital/Jurídico 5–7→5)
  - Peças opcionais têm fonte única em `ProcessoPeca::OPCIONAIS` (não bloqueiam o avanço) + badge "opcional" no `show`

- [2026-07-03] Ponte **Processo → Seleção 2.2** para a rota de dispensa (itens 7–18 do checklist)
  - A partir da Justificativa (etapa ≥ 5) o processo de dispensa ganha o card **"Seleção 2.2 — Celebração"**
    (`processos/{processo}/selecao`), reunindo plano de trabalho, habilitação, pareceres, minuta e termo
  - Reusa **integralmente** o motor `Peca` ancorando o checklist `dispensa_inexigibilidade` **ao próprio
    Processo** (relação polimórfica `Processo::pecasSelecao()`), já que na dispensa não há Chamamento
    competitivo. `Peca::sincronizar()` ganhou parâmetro de relação (default `pecas`) para não colidir com
    as peças do trâmite. O partial `pecas/_checklist` e as rotas `pecas.*` (por id) servem sem alteração
  - Guardas: `abort 404` fora da modalidade dispensa; `abort 403` antes da etapa da Justificativa
    (`Processo::podeVerSelecao()`). O "puxar do módulo Gestão de Parcerias" fica indisponível (sem proposta
    ligada ao Processo) — o partial já degrada com aviso; upload/assinatura manuais funcionam normalmente

- [2026-07-03] Modelos oficiais VII–XI encaixados na rota de dispensa
  - **Trâmite** (`ProcessoPeca::MODELO`): a **Justificativa** (Modelo VIII, art. 30, VI / 32) e o **Parecer
    Técnico CNAS** (Modelo IX, Res. 21/2016) trocaram o texto-placeholder pelos **textos oficiais** do
    cliente (HTML com cabeçalho/brasão + tokens `{{...}}`)
  - **Seleção 2.2** ganhou pré-preenchimento: novo `Peca::MODELO` (por categoria→chave) semeado no
    `sincronizar()` — **Certidão de Autuação** (VII), **Parecer Técnico da UG p/ celebração** (X) e
    **Protocolo ao Jurídico** (XI), além de Justificativa e Parecer CNAS. Motor `Peca` não tinha modelo;
    agora as peças "modelo" nascem com o texto oficial (texto puro, pois a Seleção usa textarea simples)
  - Migration `seed_modelos_selecao_dispensa`: preenche as peças de Seleção vazias e re-semeia as peças
    do trâmite que ainda tinham placeholder — **conservador**: nunca sobrescreve conteúdo assinado ou editado

- [2026-07-09] Rota de Dispensa/Inexigibilidade conferida com o checklist oficial e commitada
  - Cruzamento com `Docs. Desenvolvimento/checklist_dispensa⁄inexigibilidade.pdf` (Lei 13.019/2014,
    arts. 30–32): itens **1–14** cobertos (trâmite 1–6 + Seleção 2.2 itens 7–14); item **15** parcial
    (upload de extrato, sem integração DOE/GovBr); itens **16–18** ainda não implementados
    (autorização de início à OSC, solicitação de dados bancários, Nota de Empenho Global)
  - Código, migrations, view `processos/selecao`, modelos VII–XI e PDFs de checklist versionados
    no GitHub (`main`)

---

## O que está sendo feito

- Fechar o checklist de dispensa: itens **16–18** (autorização à OSC, dados bancários, Nota de
  Empenho) e completar a **publicação do Termo** (item 15 — DOE / site / GovBr).
- Próximos blocos do roadmap, em ordem: **Prestação de Contas (4.6)** — apoiada nas despesas/saldo
  da Execução —, **Monitoramento (4.5)**, e **Notificações/e-mails (4.7)**.
- Pendência transversal: **trilhas de auditoria / logs imutáveis** (requisito não-funcional).
- Itens finos: campos Exercício/Prazo no Chamamento; reajuste/reequilíbrio na Formalização;
  matrícula/CNAE no Cadastro.

---

## Decisões técnicas registradas

- **Framework:** Laravel 13
- **Permissões:** Spatie Laravel Permission v8
- **Auth/UI:** Laravel Breeze (Blade) + TailwindCSS
- **Integrações bancárias:** deixadas para a última fase
- **Assinatura eletrônica:** solução a definir (GOV.BR, D4Sign, etc.)

---

## Contexto importante

- Inspirado no Transferegov (federal) e SIGCON-SAÍDA (MG), adaptado para municípios
- Multi-secretaria e multi-OSC no mesmo ambiente
- LGPD, MFA e logs imutáveis são requisitos não funcionais obrigatórios
