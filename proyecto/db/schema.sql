-- Pulso Solidario — esquema de base de datos (PostgreSQL / Supabase)
-- Tabla: usuarios
-- Cuentas de la plataforma. Cada usuario tiene un rol: donante, banco o admin.

create table if not exists public.usuarios (
    id            bigint generated always as identity primary key,
    nombre        text        not null,
    apellido      text        not null,
    email         text        not null unique,
    password_hash text        not null,
    rol           text        not null default 'donante'
                  check (rol in ('donante', 'banco', 'admin')),
    activo        boolean     not null default true,
    creado_en     timestamptz not null default now(),
    actualizado_en timestamptz not null default now()
);

-- Búsquedas frecuentes por correo (login) y por rol (paneles).
create index if not exists idx_usuarios_email on public.usuarios (email);
create index if not exists idx_usuarios_rol   on public.usuarios (rol);

-- Mantiene actualizado_en al día en cada UPDATE.
create or replace function public.set_actualizado_en()
returns trigger as $$
begin
    new.actualizado_en = now();
    return new;
end;
$$ language plpgsql;

drop trigger if exists trg_usuarios_actualizado_en on public.usuarios;
create trigger trg_usuarios_actualizado_en
    before update on public.usuarios
    for each row
    execute function public.set_actualizado_en();
