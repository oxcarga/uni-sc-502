# Diagrama entidad-relación — Pulso Solidario

Esquema MySQL definido en [`01_init.sql`](./01_init.sql).  
Se renderiza automáticamente en GitHub, GitLab y en la vista previa de Markdown de Cursor/VS Code.

## Diagrama completo

```mermaid
erDiagram
    users ||--o{ email_verification_tokens : "has"
    users ||--o| donor_profiles : "has"
    users ||--o| bank_profiles : "manages"
    donation_centers ||--o{ bank_profiles : "assigned_to"
    users ||--o{ appointments : "books"
    donation_centers ||--o{ appointments : "hosts"
    appointments ||--o| donations : "produces"
    users ||--o{ donations : "makes"
    donation_centers ||--o{ donations : "receives"
    donations ||--o{ blood_units : "yields"
    donation_centers ||--o{ blood_units : "stores"
    donation_centers ||--o{ inventory : "stock"
    medical_institutions ||--o{ requests : "submits"
    donation_centers ||--o{ requests : "fulfills"
    donation_centers ||--o{ inventory_movements : "ledger"
    donations ||--o{ inventory_movements : "optional"
    requests ||--o{ inventory_movements : "optional"
    blood_units ||--o{ inventory_movements : "optional"
    users ||--o{ inventory_movements : "optional"
    donation_centers ||--o{ alerts : "raises"
    requests ||--o{ alerts : "optional"
    donation_centers ||--o{ donation_policies : "optional"
    users ||--o{ notifications : "receives"
    users ||--o{ audit_log : "optional"
    users ||--o{ donor_achievements : "earns"
    achievements ||--o{ donor_achievements : "granted_as"

    users {
        int id PK
        varchar first_name
        varchar last_name
        varchar email UK
        varchar password_hash
        varchar role "donor|bank|admin"
        tinyint active
        tinyint email_confirmed
        timestamp email_confirmed_at
        timestamp created_at
        timestamp updated_at
    }

    email_verification_tokens {
        int id PK
        int user_id FK
        varchar token_hash UK
        timestamp expires_at
        timestamp used_at
        timestamp created_at
    }

    donor_profiles {
        int user_id PK_FK
        varchar blood_type
        date birth_date
        varchar phone
        varchar province
        varchar canton
        varchar address
        text medical_history
        tinyint eligible
        date last_donation_at
        tinyint notify_nearby
        tinyint notify_appointments
        tinyint notify_blood_match
    }

    donation_centers {
        int id PK
        varchar code UK
        varchar name
        text description
        varchar address
        varchar province
        varchar canton
        varchar region
        decimal lat
        decimal lng
        varchar contact_name
        varchar contact_phone
        varchar contact_email
        time open_time
        time close_time
        varchar open_days
        int daily_capacity
        int process_minutes
        tinyint accept_walk_ins
        tinyint active
    }

    bank_profiles {
        int user_id PK_FK
        int center_id FK
    }

    medical_institutions {
        int id PK
        varchar name
        varchar contact_name
        varchar contact_phone
        varchar contact_email
        varchar address
        tinyint active
    }

    appointments {
        int id PK
        varchar code UK
        int donor_id FK
        int center_id FK
        datetime scheduled_at
        varchar status "pending|confirmed|completed|cancelled|no_show"
        text notes
    }

    donations {
        int id PK
        int donor_id FK
        int center_id FK
        int appointment_id FK_UK "nullable"
        varchar blood_type
        int units
        datetime donated_at
        varchar certificate_code UK
    }

    blood_units {
        int id PK
        varchar code UK
        int donation_id FK
        int center_id FK
        varchar blood_type
        varchar status "available|assigned|discarded|expired"
        datetime collected_at
        date expires_at
    }

    inventory {
        int id PK
        int center_id FK
        varchar blood_type
        int units
    }

    requests {
        int id PK
        varchar code UK
        int institution_id FK
        int center_id FK "nullable"
        varchar blood_type
        int quantity
        varchar priority "low|normal|critical"
        varchar status "pending|assigned|in_transit|completed|cancelled"
        datetime requested_at
        datetime completed_at
    }

    inventory_movements {
        int id PK
        int center_id FK
        varchar type "receipt|assignment|adjustment|discard"
        varchar blood_type
        int quantity
        int donation_id FK "nullable"
        int request_id FK "nullable"
        int blood_unit_id FK "nullable"
        int user_id FK "nullable"
        varchar detail
        timestamp created_at
    }

    alerts {
        int id PK
        int center_id FK
        int request_id FK "nullable"
        varchar blood_type
        varchar priority
        varchar status "active|resolved"
        varchar message
        datetime resolved_at
    }

    donation_policies {
        int id PK
        int center_id FK "nullable = global"
        varchar key_name
        varchar value_text
        varchar description
        tinyint active
    }

    notifications {
        int id PK
        int user_id FK
        varchar type
        varchar title
        text body
        varchar related_type
        int related_id
        datetime read_at
    }

    audit_log {
        int id PK
        int user_id FK "nullable"
        varchar action
        varchar entity_type
        int entity_id
        text detail
        varchar ip_address
        timestamp created_at
    }

    achievements {
        int id PK
        varchar code UK
        varchar name
        varchar description
        varchar criteria_type
        int criteria_value
        tinyint active
    }

    donor_achievements {
        int id PK
        int user_id FK
        int achievement_id FK
        int progress
        datetime unlocked_at
    }
```

## Cardinalidades (resumen)

| Relación | Tipo |
|----------|------|
| `users` → `donor_profiles` / `bank_profiles` | 1 : 0..1 |
| `bank_profiles` → `donation_centers` | N : 1 |
| `users` (donor) ↔ `donation_centers` vía `appointments` | N : M |
| `appointments` → `donations` | 0..1 : 1 (única por cita) |
| `donations` → `blood_units` | 1 : N |
| `donation_centers` → `inventory` | 1 : N (único por `blood_type`) |
| `medical_institutions` → `requests` | 1 : N |
| `achievements` ↔ `users` vía `donor_achievements` | N : M |

**Notas**

- Los tipos de sangre son un enum (`CHECK`), no una entidad.
- Umbrales de inventario (saludable / moderado / crítico) se calculan o salen de `donation_policies`; no se guardan como color.
- `inventory_movements` es append-only: cada cambio de stock deja un movimiento.
