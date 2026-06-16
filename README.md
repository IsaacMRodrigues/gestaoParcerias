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
2. [ ] Cadastro de usuários (CRUD + atribuição de perfil)
3. [ ] Cadastro institucional (Órgãos/Secretarias e OSCs)
4. [ ] Banco de Programas e Chamamentos Públicos
5. [ ] Propostas + Plano de Trabalho
6. [ ] Workflow de Análise e Aprovação
7. [ ] Formalização (geração de instrumentos + assinatura eletrônica)
8. [ ] Execução (repasses, despesas, notas fiscais)
9. [ ] Monitoramento e Fiscalização
10. [ ] Prestação de Contas
11. [ ] Integrações externas (bancária, Diário Oficial)

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

---

## O que está sendo feito

- Próximo passo: módulo de Cadastro de Usuários (CRUD + atribuição de perfil)

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
