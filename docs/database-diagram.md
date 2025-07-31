# Diagrama Entidade-Relacionamento - Sistema Jurídico

## Entidades Principais

### 1. USUÁRIOS E PERMISSÕES
```
users
├── id (PK)
├── name
├── email (unique)
├── email_verified_at
├── password
├── oab_number (unique)
├── avatar
├── phone
├── is_active
├── last_login_at
├── created_at
└── updated_at

roles
├── id (PK)
├── name (unique)
├── guard_name
├── created_at
└── updated_at

permissions
├── id (PK)
├── name (unique)
├── guard_name
├── created_at
└── updated_at

model_has_permissions
├── permission_id (FK)
├── model_type
└── model_id

model_has_roles
├── role_id (FK)
├── model_type
└── model_id

role_has_permissions
├── permission_id (FK)
└── role_id (FK)
```

### 2. CRM - CLIENTES
```
clients
├── id (PK)
├── type (enum: individual, company)
├── name
├── document (CPF/CNPJ)
├── email
├── phone
├── address
├── city
├── state
├── zip_code
├── birth_date
├── profession
├── marital_status
├── notes
├── is_active
├── created_by (FK -> users.id)
├── created_at
└── updated_at

client_contacts
├── id (PK)
├── client_id (FK -> clients.id)
├── name
├── email
├── phone
├── position
├── is_primary
├── created_at
└── updated_at

client_tags
├── id (PK)
├── name
├── color
├── created_at
└── updated_at

client_tag_pivot
├── client_id (FK -> clients.id)
└── tag_id (FK -> client_tags.id)

client_interactions
├── id (PK)
├── client_id (FK -> clients.id)
├── user_id (FK -> users.id)
├── type (enum: call, email, meeting, other)
├── subject
├── description
├── interaction_date
├── duration_minutes
├── created_at
└── updated_at
```

### 3. PROCESSOS JURÍDICOS
```
lawsuits
├── id (PK)
├── number (unique)
├── client_id (FK -> clients.id)
├── responsible_lawyer_id (FK -> users.id)
├── court_id (FK -> courts.id)
├── subject
├── description
├── value
├── status (enum: active, suspended, finished, archived)
├── phase (enum: knowledge, execution, appeal, other)
├── instance (enum: first, second, superior, supreme)
├── distribution_date
├── estimated_end_date
├── actual_end_date
├── created_at
└── updated_at

courts
├── id (PK)
├── name
├── code
├── type (enum: federal, state, labor, electoral)
├── city
├── state
├── api_endpoint
├── created_at
└── updated_at

lawsuit_movements
├── id (PK)
├── lawsuit_id (FK -> lawsuits.id)
├── movement_date
├── description
├── type
├── source (enum: manual, pje, eproc, saj)
├── external_id
├── created_at
└── updated_at

lawsuit_parties
├── id (PK)
├── lawsuit_id (FK -> lawsuits.id)
├── client_id (FK -> clients.id)
├── type (enum: plaintiff, defendant, third_party)
├── created_at
└── updated_at
```

### 4. DOCUMENTOS
```
documents
├── id (PK)
├── lawsuit_id (FK -> lawsuits.id)
├── client_id (FK -> clients.id)
├── name
├── description
├── file_path
├── file_name
├── file_size
├── mime_type
├── version
├── is_template
├── template_data (JSON)
├── created_by (FK -> users.id)
├── created_at
└── updated_at

document_versions
├── id (PK)
├── document_id (FK -> documents.id)
├── version_number
├── file_path
├── changes_description
├── created_by (FK -> users.id)
├── created_at
└── updated_at

document_signatures
├── id (PK)
├── document_id (FK -> documents.id)
├── signer_name
├── signer_email
├── signer_document
├── signature_date
├── certificate_info (JSON)
├── signature_status (enum: pending, signed, rejected)
├── created_at
└── updated_at

document_templates
├── id (PK)
├── name
├── description
├── template_content
├── variables (JSON)
├── category
├── is_active
├── created_by (FK -> users.id)
├── created_at
└── updated_at
```

### 5. CALENDÁRIO E PRAZOS
```
calendar_events
├── id (PK)
├── title
├── description
├── start_date
├── end_date
├── all_day
├── type (enum: hearing, deadline, appointment, reminder)
├── lawsuit_id (FK -> lawsuits.id)
├── client_id (FK -> clients.id)
├── assigned_to (FK -> users.id)
├── location
├── status (enum: scheduled, completed, cancelled, postponed)
├── created_by (FK -> users.id)
├── created_at
└── updated_at

deadlines
├── id (PK)
├── lawsuit_id (FK -> lawsuits.id)
├── title
├── description
├── due_date
├── alert_days_before
├── status (enum: pending, completed, overdue)
├── completed_at
├── completed_by (FK -> users.id)
├── created_by (FK -> users.id)
├── created_at
└── updated_at

holidays
├── id (PK)
├── name
├── date
├── is_national
├── state
├── city
├── created_at
└── updated_at
```

### 6. TIME TRACKING E FATURAMENTO
```
time_entries
├── id (PK)
├── user_id (FK -> users.id)
├── lawsuit_id (FK -> lawsuits.id)
├── client_id (FK -> clients.id)
├── description
├── start_time
├── end_time
├── duration_minutes
├── hourly_rate
├── total_amount
├── is_billable
├── is_billed
├── date
├── created_at
└── updated_at

invoices
├── id (PK)
├── client_id (FK -> clients.id)
├── invoice_number (unique)
├── issue_date
├── due_date
├── subtotal
├── tax_amount
├── discount_amount
├── total_amount
├── status (enum: draft, sent, paid, overdue, cancelled)
├── payment_date
├── notes
├── created_by (FK -> users.id)
├── created_at
└── updated_at

invoice_items
├── id (PK)
├── invoice_id (FK -> invoices.id)
├── time_entry_id (FK -> time_entries.id)
├── description
├── quantity
├── unit_price
├── total_amount
├── created_at
└── updated_at

payment_methods
├── id (PK)
├── name
├── type (enum: cash, bank_transfer, credit_card, pix, boleto)
├── is_active
├── created_at
└── updated_at

payments
├── id (PK)
├── invoice_id (FK -> invoices.id)
├── payment_method_id (FK -> payment_methods.id)
├── amount
├── payment_date
├── reference
├── notes
├── created_at
└── updated_at
```

### 7. FINANCEIRO
```
accounts
├── id (PK)
├── name
├── type (enum: checking, savings, credit_card, cash)
├── bank_code
├── agency
├── account_number
├── initial_balance
├── current_balance
├── is_active
├── created_at
└── updated_at

transactions
├── id (PK)
├── account_id (FK -> accounts.id)
├── client_id (FK -> clients.id)
├── lawsuit_id (FK -> lawsuits.id)
├── invoice_id (FK -> invoices.id)
├── type (enum: income, expense, transfer)
├── category_id (FK -> transaction_categories.id)
├── description
├── amount
├── transaction_date
├── reference
├── is_reconciled
├── reconciled_at
├── created_by (FK -> users.id)
├── created_at
└── updated_at

transaction_categories
├── id (PK)
├── name
├── type (enum: income, expense)
├── parent_id (FK -> transaction_categories.id)
├── is_active
├── created_at
└── updated_at

bank_statements
├── id (PK)
├── account_id (FK -> accounts.id)
├── file_path
├── statement_date
├── processed_at
├── total_transactions
├── created_at
└── updated_at
```

### 8. NOTIFICAÇÕES E AUDITORIA
```
notifications
├── id (PK)
├── type
├── notifiable_type
├── notifiable_id
├── data (JSON)
├── read_at
├── created_at
└── updated_at

activity_log
├── id (PK)
├── log_name
├── description
├── subject_type
├── subject_id
├── event
├── causer_type
├── causer_id
├── properties (JSON)
├── batch_uuid
├── created_at
└── updated_at
```

## Relacionamentos Principais

1. **Users → Lawsuits**: 1:N (responsible_lawyer_id)
2. **Clients → Lawsuits**: 1:N
3. **Lawsuits → Documents**: 1:N
4. **Lawsuits → Time_entries**: 1:N
5. **Clients → Invoices**: 1:N
6. **Invoices → Invoice_items**: 1:N
7. **Time_entries → Invoice_items**: 1:1
8. **Accounts → Transactions**: 1:N
9. **Users → Time_entries**: 1:N
10. **Lawsuits → Calendar_events**: 1:N

## Índices Recomendados

```sql
-- Performance indexes
CREATE INDEX idx_lawsuits_client_id ON lawsuits(client_id);
CREATE INDEX idx_lawsuits_responsible_lawyer_id ON lawsuits(responsible_lawyer_id);
CREATE INDEX idx_lawsuits_status ON lawsuits(status);
CREATE INDEX idx_documents_lawsuit_id ON documents(lawsuit_id);
CREATE INDEX idx_time_entries_user_id ON time_entries(user_id);
CREATE INDEX idx_time_entries_date ON time_entries(date);
CREATE INDEX idx_transactions_account_id ON transactions(account_id);
CREATE INDEX idx_transactions_date ON transactions(transaction_date);
CREATE INDEX idx_calendar_events_start_date ON calendar_events(start_date);
CREATE INDEX idx_deadlines_due_date ON deadlines(due_date);
```