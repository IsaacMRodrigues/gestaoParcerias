# Plataforma de Gestão de Parcerias - PGP

> **Instrução para IA:** Este README é um documento vivo. Sempre que realizar qualquer trabalho neste projeto, atualize as seções `## O que foi feito` e `## O que está sendo feito` antes de encerrar a conversa. Não omita etapas concluídas — o histórico completo importa para quem continuar o trabalho.

---

## Sobre o projeto

Plataforma web em **Laravel** para gestão completa de parcerias públicas entre Secretarias Municipais e OSCs (Organizações da Sociedade Civil). Cobre todo o ciclo: planejamento → proposta → análise → formalização → execução → monitoramento → prestação de contas.

**Repositório:** https://github.com/IsaacMRodrigues/gestaoParcerias  
**Stack:** Laravel 13, MySQL, TailwindCSS, Blade  
**Pacotes principais:** Laravel Breeze (auth), Spatie Laravel Permission (perfis)  
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
10. [~] Módulo Unidade Gestora — 2.2 Seleção/Celebração e 2.3 Execução do Concedente (falta Ordem de Pagamento)
11. [ ] Execução (repasses, despesas, notas fiscais)
12. [ ] Monitoramento e Fiscalização
13. [ ] Prestação de Contas
14. [ ] Integrações externas (bancária, Diário Oficial)

---

## Perfis de usuário

| Slug | Descrição |
|---|---|
| `representante_legal` | Responsável pela OSC |
| `secretario_unidade_gestora` | Responsável pela Secretaria |
| `gestor_parceria` | Acompanha a execução |
| `comissao_avaliacao_monitoramento` | Analisa e monitora |
| `comissao_selecao` | Avalia propostas |
| `procuradoria_juridica` | Emite pareceres jurídicos |
| `controle_interno` | Auditoria interna |
| `cadastrador_proposta` | Insere propostas |
| `cadastrador_prestacao_contas` | Insere prestações de contas |

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

## O que está sendo feito

- Próximo passo: Ordem de Pagamento + Execução financeira (repasses, despesas, notas fiscais)
  quando a fase bancária for liberada; depois Monitoramento e Prestação de Contas.

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
