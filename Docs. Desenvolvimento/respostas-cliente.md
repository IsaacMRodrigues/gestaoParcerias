# Respostas do cliente — Módulos 1 (Cadastro) e 2 (Unidade Gestora)

> Esclarecimentos dados por quem especificou o sistema. Base para ajustar Módulos 1 e 2.

## Módulo 1 — Cadastro (perfis)

- **Lista de perfis oficial:** a do **Módulo 1** (a lista detalhada, ~21 perfis).
- **Múltiplos perfis:** um usuário **pode ter vários perfis** — *exceto* os marcados como **"exclusivo"** na descrição.
- **Perfis "exclusivo":** só podem ser atribuídos a funcionário **do setor correspondente** (restrição no cadastro).
- **Membros da OSC:** deixar para depois (fora do escopo atual).
- **Assinatura eletrônica:** deixar para depois (fora do escopo atual).

## Módulo 2 — Unidade Gestora (fluxo)

### Setor × Perfil
- Setor e perfil são **independentes**: um setor (seus funcionários) pode ter vários perfis.
- Exceção: perfis "exclusivo" só para funcionário do setor (definido no cadastro).

### Siglas / setores
| Sigla | Significado |
|---|---|
| **UG** | Unidade Gestora — as secretarias da Prefeitura (quem solicita a parceria) |
| **SCP** | Setor de Convênios e Parcerias |
| **SEPLAN** | Secretaria Municipal de Planejamento |
| **PJ** | Procuradoria Jurídica |
| ~~SPC~~ | Erro de digitação — é o **SCP** |

- **AP** = Termo de **Abertura de Processo**.

### Fluxo detalhado (confirmado)
| # | Setor | O que faz | Envia para |
|---|---|---|---|
| 1 | UG | Preenche Ofício + Termo de Referência e assina | SCP |
| 2 | SCP | Recebe e analisa | SEPLAN |
| 3 | SEPLAN | Recebe, analisa, emite Parecer Financeiro e assina | UG |
| 4 | UG | Confere; resolve pendências; faz a Abertura do Processo; assina AP | SCP |
| 5 | SCP | Elabora o Edital (ou justificativa de dispensa/inexigibilidade) | UG (assinar) |
| 6 | UG | Assina o edital e devolve | SCP |
| 7 | SCP | Publica no site oficial — **trâmite externo** (fora do sistema) | — fim |
