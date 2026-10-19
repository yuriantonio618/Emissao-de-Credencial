# SIGEUL — Sistema de Gestão Universitária (PHP + MySQL)
### Universidade Licungo — Fase 1: Portal do Estudante

Reconstrução do protótipo em PHP + MySQL, com base de dados real a
suportar todos os módulos do painel do estudante.

## 📦 Requisitos
- PHP 8.0+ com extensão `pdo_mysql`
- MySQL 8.0+ (ou MariaDB 10.4+)
- Servidor web (Apache/Nginx) ou o servidor embutido do PHP

## 🚀 Instalação rápida

1. **Criar a base de dados** — importe o ficheiro `sql/schema.sql`:
   ```bash
   mysql -u root -p < sql/schema.sql
   ```
   Isto cria a base `sigeul`, todas as tabelas e os dados de demonstração
   (3 cursos, 7 estudantes, notas, horários, financeiro, material, etc).

2. **Configurar a ligação** — edite `config/database.php` e ajuste
   `DB_HOST`, `DB_USER` e `DB_PASS` conforme o seu ambiente (por
   omissão está pronto para XAMPP/WAMP: utilizador `root` sem senha).

3. **Colocar os ficheiros no servidor** — copie a pasta `sigeul/` para
   a raiz do seu servidor web (ex: `htdocs/` no XAMPP) ou, para um
   teste rápido, use o servidor embutido do PHP a partir da pasta do
   projecto:
   ```bash
   php -S localhost:8000
   ```
   e aceda a `http://localhost:8000/`.

## 🔑 Contas de demonstração

Todas as contas de estudante usam a senha **`estagio123`**.
A conta de administrador usa a senha **`admin2026`**.

| Nº de Estudante | Nome                        | Curso                                   |
|-----------------|-----------------------------|------------------------------------------|
| 04.0021.2023    | Ana Paula Mondlane          | Estatística e Gestão de Informação (4º)  |
| 04.0034.2023    | Carlos Eduardo Nhaca        | Estatística e Gestão de Informação (4º)  |
| 04.0057.2023    | Beatriz Fernandes Sitoe     | Estatística e Gestão de Informação (3º)  |
| 05.0012.2023    | Domingos Artur Feniasse     | Matemática (2º)                          |
| 05.0028.2023    | Rosinda Manuel Bila         | Matemática (3º)                          |
| 06.0009.2023    | Anselmo Paulo Machava       | Física (1º)                              |
| 06.0015.2023    | Suzana Amélia Macuácua      | Física (2º)                              |
| **admin**       | Administrador do Sistema    | — (painel de administração)              |

A página de autenticação (`index.php`) lista estas contas com
preenchimento automático — basta clicar.

## 🗂️ Estrutura do projecto

```
sigeul/
├── config/database.php        Ligação PDO à base de dados MySQL
├── includes/
│   ├── auth.php                Sessão, guardas de acesso, inactividade
│   ├── functions.php           Funções auxiliares (formatação, escaping...)
│   ├── header_estudante.php    Topbar + menu lateral do estudante
│   ├── header_admin.php        Topbar + menu do administrador
│   └── footer.php              Fecho do layout
├── student/
│   ├── inicio.php              Painel inicial com atalhos e indicadores
│   ├── notas.php                📊 Notas de Frequência
│   ├── exames.php               📝 Exames
│   ├── financeira.php           💰 Situação Financeira
│   ├── avaliacao.php            ⭐ Avaliação do Corpo Docente (formulário funcional)
│   ├── inscricoes.php           🗂️ Inscrições
│   ├── horarios.php             🕒 Horários (grelha semanal)
│   ├── material.php             📚 Material Académico
│   ├── emolumentos.php          🧾 Pagamento de Emolumentos (simulação de pagamento)
│   ├── curricular.php           📈 Plano Curricular (elegibilidade p/ estágio)
│   ├── perfil.php               Dados pessoais
│   ├── senha.php                Alterar senha
│   └── sessoes.php              Histórico de sessões
├── admin/dashboard.php          Dashboard estatístico (todos os cursos)
├── assets/css/style.css         Identidade visual (azul institucional + dourado)
├── sql/schema.sql               Esquema completo + dados de demonstração
├── index.php                    Página de autenticação
├── login_process.php            Processamento de login (estudante/admin)
└── logout.php                   Terminar sessão
```

## 🔒 Segurança implementada (Fase 1)
- Senhas com `password_hash()` / `password_verify()` (bcrypt).
- Todas as consultas usam *prepared statements* (PDO) — protegido
  contra SQL Injection.
- Todo o output dinâmico passa por `htmlspecialchars()` (função `e()`)
  — protegido contra XSS.
- Sessões com expiração automática por inactividade (25 minutos).
- Pastas internas (`config/`, `includes/`, `sql/`) bloqueadas por
  `.htaccess` contra acesso directo pelo browser.
- Guardas de acesso (`exigirEstudante()`, `exigirAdmin()`) em todas as
  páginas protegidas.

## 🛣️ Fase 2 — Novidades

- **🎓 Emissão de Credencial de Estágio** (`student/estagio.php`): o estudante
  preenche local, período e data de início; o sistema verifica automaticamente
  no plano curricular (tabela `notas`) se todas as disciplinas do 1º ao 3º ano
  estão aprovadas. Se sim, gera um **PDF oficial** (via biblioteca FPDF,
  vendorizada em `vendor/fpdf/`, sem necessidade de Composer) pronto a
  descarregar; caso contrário, lista as disciplinas pendentes. Todos os
  pedidos ficam guardados na tabela `pedidos_estagio` com histórico visível
  ao estudante.
- **Módulo administrativo completo**:
  - `admin/estudantes.php` — criar, editar, remover e repor senha de estudantes.
  - `admin/cursos.php` — gerir faculdades/departamentos/cursos.
  - `admin/disciplinas.php` — gerir o plano curricular por curso e ano.
  - `admin/notas.php` — lançar/corrigir notas de frequência e exame (a nota
    final é calculada automaticamente pela base de dados: 40% frequência + 60% exame).
  - `admin/pedidos_estagio.php` — listar e filtrar todos os pedidos de credencial.

### Instalar a Fase 2
Se já tinha a Fase 1 instalada, basta importar a actualização incremental:
```bash
mysql -u root -p sigeul < sql/updates/002_pedidos_estagio.sql
```
Instalações novas só precisam de `sql/schema.sql` (já inclui tudo).
Não é necessário Composer — a biblioteca **FPDF** já vem incluída em
`vendor/fpdf/` (licença livre, redistribuição permitida).

## 🩹 Correcção importante (instalação em subpasta — ex: XAMPP)

Se aceder ao sistema através de um caminho como `http://localhost/sigeul/`
(em vez da raiz do domínio), o sistema agora **detecta automaticamente**
essa subpasta através da constante `BASE_URL`, calculada em
`config/database.php` a partir do `DOCUMENT_ROOT` do Apache. Isto resolve
os erros "404 Not Found" e a folha de estilos (CSS) não carregar, que
ocorriam quando o projecto não estava na raiz do servidor. **Não é
necessário configurar nada manualmente** — basta ter os ficheiros dentro
de uma pasta (ex: `htdocs/sigeul/`) e aceder normalmente por
`http://localhost/sigeul/`.

## 🛣️ Fase 3 — Novidades

- **🔑 Recuperação de senha por e-mail** (`esqueci_senha.php` / `redefinir_senha.php`):
  gera um token seguro de utilização única, válido 1 hora, guardado em
  `reset_senha_tokens`. O envio passa pela abstracção `includes/mailer.php`
  — tenta a função `mail()` nativa do PHP e, independentemente do
  resultado, regista sempre uma cópia em `emails_log`, visível em
  **`admin/emails.php`** (útil para testar o fluxo sem SMTP configurado).
- **📎 Upload de documentos** (`student/documentos.php` / `admin/documentos.php`):
  estudantes enviam PDF/JPG/PNG (máx. 5 MB, validado por MIME real via
  `finfo`, nomes de ficheiro aleatórios). Ficam guardados em
  `uploads/documentos/`, pasta protegida por `.htaccess` contra execução
  de scripts. O administrador aprova ou rejeita com observação.
- **🔔 Notificações internas** (`student/notificacoes.php` / `admin/notificacoes.php`):
  o administrador publica avisos para **todos**, para **um curso** ou
  para **um estudante específico**; o estudante vê um sino com contador
  de não lidas no topo do portal e pode marcar como lida.
- **📊 Exportação de relatórios**:
  - `admin/exportar_estudantes.php` e `admin/exportar_pedidos.php` — CSV
    compatível com Excel (com BOM UTF-8, abre correctamente acentos).
  - `admin/exportar_pauta.php?curso_id=&ano=` — Pauta de notas em PDF
    por curso/ano (botões disponíveis em **Gestão de Disciplinas**).
  - `student/exportar_ficha.php` — Ficha Académica (histórico de notas)
    em PDF, disponível a partir do **Plano Curricular** do estudante.

### Instalar a Fase 3
```bash
mysql -u root -p sigeul < sql/updates/003_fase3.sql
```
Instalações novas só precisam do `sql/schema.sql` (já inclui tudo).
Certifique-se de que a pasta `uploads/documentos/` tem permissão de
escrita para o utilizador do servidor web (`chmod -R 775 uploads`).

## 🛣️ Próximas fases sugeridas
- Fase 4: Autenticação de dois factores, relatórios financeiros
  agregados, calendário académico interactivo e API REST para
  integração com uma futura aplicação móvel.

---
© Universidade Licungo — Módulo SIGEUL v1.0 (Fase 1)
