# Plataforma de Gestão de Parcerias - PGP

> **Instrução para IA:** Este README é um documento vivo. Sempre que realizar qualquer trabalho neste projeto, atualize as seções `## O que foi feito` e `## O que está sendo feito` antes de encerrar a conversa. Não omita etapas concluídas — o histórico completo importa para quem continuar o trabalho.

---

## Sobre o projeto

Plataforma web em **Laravel** para gestão completa de parcerias públicas entre Secretarias Municipais e OSCs (Organizações da Sociedade Civil). Cobre todo o ciclo: planejamento → proposta → análise → formalização → execução → monitoramento → prestação de contas.

**Repositório:** https://github.com/IsaacMRodrigues/gestaoParcerias  
**Stack:** Laravel (PHP), MySQL  
**Equipe:** 2 desenvolvedores

---

## Ordem de desenvolvimento planejada

1. [ ] Cadastro de usuários + perfis/permissões (Spatie Laravel Permission)
2. [ ] Cadastro institucional (Órgãos/Secretarias e OSCs)
3. [ ] Banco de Programas e Chamamentos Públicos
4. [ ] Propostas + Plano de Trabalho
5. [ ] Workflow de Análise e Aprovação
6. [ ] Formalização (geração de instrumentos + assinatura eletrônica)
7. [ ] Execução (repasses, despesas, notas fiscais)
8. [ ] Monitoramento e Fiscalização
9. [ ] Prestação de Contas
10. [ ] Integrações externas (bancária, Diário Oficial)

---

## Perfis de usuário

| Perfil | Descrição |
|---|---|
| Representante legal | Responsável pela OSC |
| Secretário da unidade gestora | Responsável pela Secretaria |
| Gestor da parceria | Acompanha a execução |
| Comissão de avaliação e monitoramento | Analisa e monitora |
| Comissão de seleção | Avalia propostas |
| Procuradoria jurídica | Emite pareceres jurídicos |
| Controle interno | Auditoria interna |
| Cadastrador de proposta | Insere propostas |
| Cadastrador de prestação de contas | Insere prestações de contas |

---

## O que foi feito

- [2026-06-16] Especificação inicial recebida (`txt.txt`) e analisada
- [2026-06-16] Repositório privado criado no GitHub e arquivos de especificação commitados

---

## O que está sendo feito

- Nenhuma tarefa em andamento no momento.

---

## Decisões técnicas registradas

- **Framework:** Laravel
- **Permissões:** Spatie Laravel Permission (a ser instalado)
- **Autenticação:** Laravel Breeze ou Fortify + MFA (a definir)
- **Integrações bancárias:** deixadas para a última fase

---

## Contexto importante

- O sistema é inspirado no Transferegov (federal) e SIGCON-SAÍDA (MG), adaptado para municípios
- Deve suportar múltiplas secretarias e múltiplas OSCs (multi-tenancy por escopo)
- LGPD, MFA e logs imutáveis são requisitos não funcionais obrigatórios
- Assinatura eletrônica é necessária em várias etapas (solução a definir: GOV.BR, D4Sign, etc.)
