-- AutoEvolve MVP schema
create extension if not exists pgcrypto;

create table if not exists public.sites (
  id uuid primary key default gen_random_uuid(),
  name text not null,
  domain text,
  created_at timestamptz not null default now()
);

insert into public.sites (id,name,domain)
values ('11111111-1111-1111-1111-111111111111','AutoEvolve',null)
on conflict (id) do nothing;

create table if not exists public.profiles (
  id uuid primary key references auth.users(id) on delete cascade,
  role text not null default 'viewer' check (role in ('viewer','editor','admin')),
  created_at timestamptz not null default now()
);

create or replace function public.handle_new_user() returns trigger language plpgsql security definer set search_path=public as $$
begin insert into public.profiles(id,role) values(new.id,'viewer') on conflict do nothing; return new; end; $$;
drop trigger if exists on_auth_user_created on auth.users;
create trigger on_auth_user_created after insert on auth.users for each row execute procedure public.handle_new_user();

create or replace function public.is_admin() returns boolean language sql stable security definer set search_path=public as $$
  select exists(select 1 from public.profiles where id=auth.uid() and role='admin');
$$;

create table if not exists public.content_items (
  id uuid primary key default gen_random_uuid(),
  site_id uuid not null references public.sites(id) on delete cascade,
  title text not null,
  slug text not null unique,
  excerpt text,
  body jsonb not null default '{"summary":"","intro":"","sections":[],"faq":[],"takeaways":[]}'::jsonb,
  primary_keyword text,
  search_intent text,
  status text not null default 'draft' check(status in ('draft','review','published','archived')),
  health_score int not null default 50 check(health_score between 0 and 100),
  seo_score int not null default 50 check(seo_score between 0 and 100),
  risk_score text not null default 'low' check(risk_score in ('low','medium','high')),
  meta_title text,
  meta_description text,
  published_at timestamptz,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);
create index if not exists content_status_idx on public.content_items(site_id,status,published_at desc);
create index if not exists content_health_idx on public.content_items(site_id,health_score);

create or replace function public.touch_updated_at() returns trigger language plpgsql as $$ begin new.updated_at=now(); return new; end; $$;
drop trigger if exists content_touch on public.content_items;
create trigger content_touch before update on public.content_items for each row execute procedure public.touch_updated_at();

create table if not exists public.content_versions (
  id uuid primary key default gen_random_uuid(),
  content_id uuid not null references public.content_items(id) on delete cascade,
  version_number int not null,
  snapshot jsonb not null,
  reason text,
  created_at timestamptz not null default now(),
  unique(content_id,version_number)
);

create table if not exists public.ai_actions (
  id uuid primary key default gen_random_uuid(),
  site_id uuid not null references public.sites(id) on delete cascade,
  content_id uuid references public.content_items(id) on delete set null,
  action_type text not null,
  risk_level text not null check(risk_level in ('low','medium','high')),
  status text not null default 'queued' check(status in ('queued','running','pending_approval','completed','failed','rejected')),
  summary text,
  payload jsonb not null default '{}'::jsonb,
  approved_at timestamptz,
  created_at timestamptz not null default now()
);
create index if not exists actions_status_idx on public.ai_actions(site_id,status,created_at desc);

create table if not exists public.opportunities (
  id uuid primary key default gen_random_uuid(),
  site_id uuid not null references public.sites(id) on delete cascade,
  topic text not null,
  keyword text,
  type text,
  reason text,
  priority int not null default 50 check(priority between 1 and 100),
  status text not null default 'new',
  created_at timestamptz not null default now()
);

create table if not exists public.metrics_daily (
  id bigserial primary key,
  site_id uuid not null references public.sites(id) on delete cascade,
  content_id uuid references public.content_items(id) on delete cascade,
  day date not null,
  impressions int not null default 0,
  clicks int not null default 0,
  sessions int not null default 0,
  conversions int not null default 0,
  revenue numeric(12,2) not null default 0,
  unique(content_id,day)
);

create table if not exists public.site_settings (
  site_id uuid primary key references public.sites(id) on delete cascade,
  autonomy_level int not null default 2 check(autonomy_level between 1 and 4),
  stale_after_days int not null default 30,
  max_actions_per_run int not null default 5,
  auto_publish_low_risk boolean not null default true,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);
insert into public.site_settings(site_id,autonomy_level,stale_after_days,max_actions_per_run)
values('11111111-1111-1111-1111-111111111111',2,30,5)
on conflict(site_id) do nothing;

alter table public.profiles enable row level security;
alter table public.content_items enable row level security;
alter table public.content_versions enable row level security;
alter table public.ai_actions enable row level security;
alter table public.opportunities enable row level security;
alter table public.metrics_daily enable row level security;
alter table public.site_settings enable row level security;

create policy "public reads published content" on public.content_items for select using(status='published' or public.is_admin());
create policy "admins manage content" on public.content_items for all using(public.is_admin()) with check(public.is_admin());
create policy "user reads own profile" on public.profiles for select using(id=auth.uid() or public.is_admin());
create policy "admins manage profiles" on public.profiles for all using(public.is_admin()) with check(public.is_admin());
create policy "admins versions" on public.content_versions for all using(public.is_admin()) with check(public.is_admin());
create policy "admins actions" on public.ai_actions for all using(public.is_admin()) with check(public.is_admin());
create policy "admins opportunities" on public.opportunities for all using(public.is_admin()) with check(public.is_admin());
create policy "admins metrics" on public.metrics_daily for all using(public.is_admin()) with check(public.is_admin());
create policy "admins settings" on public.site_settings for all using(public.is_admin()) with check(public.is_admin());

grant usage on schema public to anon, authenticated;
grant select on public.content_items to anon, authenticated;
grant select on public.profiles to authenticated;
grant select,insert,update,delete on public.content_items,public.content_versions,public.ai_actions,public.opportunities,public.metrics_daily,public.site_settings to authenticated;
