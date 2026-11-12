
-- Extensions & helpers --
create extension if not exists pgcrypto with schema extensions;

-- keeps updated_at current on every UPDATE
create or replace function public.set_updated_at()
returns trigger language plpgsql as $$
begin
  new.updated_at = now();
  return new;
end $$;

-- Lookup / reference tables --
create table public.location (
  location_id        int generated always as identity primary key,
  city_municipality  varchar(80) not null,
  province           varchar(80) not null,
  unique (city_municipality, province)
);

create table public.soil_type (
  soil_type_id  int generated always as identity primary key,
  soil_type     varchar(50) not null unique
);

create table public.irrigation_type (
  irrigation_type_id  int generated always as identity primary key,
  irrigation_type     varchar(50) not null unique
);

create table public.crop_category (
  category_id    int generated always as identity primary key,
  category_name  varchar(50) not null unique
);

create table public.growing_season (
  season_id    int generated always as identity primary key,
  season_name  varchar(30) not null unique
);

create table public.planting_status (
  status_id    int generated always as identity primary key,
  status_name  varchar(30) not null unique
);

create table public.quality_grade (
  grade_id    int generated always as identity primary key,
  grade_name  varchar(30) not null unique
);

create table public.harvest_remark (
  remark_id           int primary key,            
  remark_description  varchar(100) not null unique
);

-- Core tables --
create table public.farmer (
  farmer_id        int generated always as identity primary key,
  first_name       varchar(50) not null,
  last_name        varchar(50) not null,
  contact_number   varchar(20) not null unique
                   check (contact_number ~ '^09[0-9]{9}$'),
  location_id      int not null references public.location(location_id)
                   on update cascade on delete restrict,
  date_registered  date not null default current_date,
  created_at       timestamptz not null default now(),
  updated_at       timestamptz not null default now()
);

create table public.farm (
  farm_id             int generated always as identity primary key,
  farmer_id           int not null references public.farmer(farmer_id)
                      on update cascade on delete restrict,
  farm_name           varchar(100) not null,
  location_id         int not null references public.location(location_id)
                      on update cascade on delete restrict,
  area_hectares       numeric(8,2) not null check (area_hectares > 0),
  soil_type_id        int not null references public.soil_type(soil_type_id)
                      on update cascade on delete restrict,
  irrigation_type_id  int not null references public.irrigation_type(irrigation_type_id)
                      on update cascade on delete restrict,
  created_at          timestamptz not null default now(),
  updated_at          timestamptz not null default now(),
  unique (farmer_id, farm_name)
);

create table public.crop (
  crop_id              int generated always as identity primary key,
  crop_name            varchar(80) not null,
  variety              varchar(80) not null,
  category_id          int not null references public.crop_category(category_id)
                       on update cascade on delete restrict,
  growing_period_days  int not null check (growing_period_days > 0),
  season_id            int not null references public.growing_season(season_id)
                       on update cascade on delete restrict,
  created_at           timestamptz not null default now(),
  updated_at           timestamptz not null default now(),
  unique (crop_name, variety)
);

create table public.planting (
  planting_id            int generated always as identity primary key,
  farm_id                int not null references public.farm(farm_id)
                         on update cascade on delete restrict,
  crop_id                int not null references public.crop(crop_id)
                         on update cascade on delete restrict,
  planting_date          date not null,
  area_planted           numeric(8,2) not null check (area_planted > 0),
  seed_quantity          numeric(8,2) not null check (seed_quantity >= 0),
  expected_harvest_date  date not null,
  status_id              int not null references public.planting_status(status_id)
                         on update cascade on delete restrict,
  created_at             timestamptz not null default now(),
  updated_at             timestamptz not null default now(),
  check (expected_harvest_date > planting_date)
);

create table public.harvest (
  harvest_id             int generated always as identity primary key,
  planting_id            int not null references public.planting(planting_id)
                         on update cascade on delete restrict,
  harvest_date           date not null,
  quantity_kg            numeric(10,2) not null check (quantity_kg > 0),
  selling_price_per_kg   numeric(10,2) not null check (selling_price_per_kg >= 0),
  grade_id               int not null references public.quality_grade(grade_id)
                         on update cascade on delete restrict,
  remark_id              int not null references public.harvest_remark(remark_id)
                         on update cascade on delete restrict,
  created_at             timestamptz not null default now(),
  updated_at             timestamptz not null default now()
);

-- Authentication tables --
create table public.user_role (
  role_id    int generated always as identity primary key,
  role_name  varchar(30) not null unique       
);

create table public.app_user (
  user_id        int generated always as identity primary key,
  username       varchar(50) not null unique,
  password_hash  text not null,                 
  full_name      varchar(100) not null,
  role_id        int not null references public.user_role(role_id)
                 on update cascade on delete restrict,
  is_active      boolean not null default true,
  last_login     timestamptz,
  created_at     timestamptz not null default now(),
  updated_at     timestamptz not null default now()
);

-- updated_at triggers --
do $$
declare t text;
begin
  foreach t in array array['farmer','farm','crop','planting','harvest','app_user']
  loop
    execute format(
      'create trigger trg_%1$s_updated_at before update on public.%1$s
       for each row execute function public.set_updated_at()', t);
  end loop;
end $$;

-- Helpful indexes on foreign keys --
create index on public.farmer   (location_id);
create index on public.farm     (farmer_id);
create index on public.farm     (location_id);
create index on public.farm     (irrigation_type_id);
create index on public.crop     (category_id);
create index on public.planting (farm_id);
create index on public.planting (crop_id);
create index on public.planting (status_id);
create index on public.harvest  (planting_id);
create index on public.harvest  (grade_id);

-- Lookup seed data --
insert into public.harvest_remark (remark_id, remark_description) values
  (1,'Good Harvest'), (2,'Acceptable Quality'), (3,'Low Yield'),
  (4,'Poor Quality'), (5,'Damaged Harvest');

insert into public.quality_grade (grade_name) values ('Grade A'),('Grade B'),('Grade C');
insert into public.planting_status (status_name) values ('Planned'),('Growing'),('Harvested'),('Failed');
insert into public.soil_type (soil_type) values ('Loam'),('Clay Loam'),('Sandy Loam'),('Clay');
insert into public.irrigation_type (irrigation_type) values ('Rainfed'),('Irrigated'),('Drip Irrigation');
insert into public.crop_category (category_name) values ('Cereal'),('Vegetable'),('Fruit'),('Legume'),('Root Crop');
insert into public.growing_season (season_name) values ('Wet Season'),('Dry Season'),('Year Round');

insert into public.location (city_municipality, province) values
  ('Cebu City','Cebu'),('Carcar City','Cebu'),('Danao City','Cebu'),('Argao','Cebu'),
  ('Bogo City','Cebu'),('Toledo City','Cebu'),('Naga City','Cebu'),('Minglanilla','Cebu'),
  ('Liloan','Cebu'),('Compostela','Cebu'),('Talisay City','Cebu'),('Consolacion','Cebu'),
  ('Dalaguete','Cebu'),('Barili','Cebu'),('Sibonga','Cebu');

insert into public.user_role (role_name) values ('Administrator'),('Staff');

--  7. Login helpers (password hashing with bcrypt) --
create or replace function public.create_app_user(
  p_username text, p_password text, p_full_name text, p_role_id int)
returns int
language plpgsql security definer
set search_path = public, extensions
as $$
declare new_id int;
begin
  if length(p_password) < 8 then
    raise exception 'Password must be at least 8 characters';
  end if;
  insert into app_user (username, password_hash, full_name, role_id)
  values (lower(trim(p_username)), crypt(p_password, gen_salt('bf', 10)),
          p_full_name, p_role_id)
  returning user_id into new_id;
  return new_id;
end $$;

-- Verify a login: returns the user row (no hash) only if the password matches.
create or replace function public.verify_login(p_username text, p_password text)
returns table (user_id int, username varchar, full_name varchar, role_name varchar)
language plpgsql security definer
set search_path = public, extensions
as $$
begin
  return query
  select u.user_id, u.username, u.full_name, r.role_name
  from app_user u
  join user_role r on r.role_id = u.role_id
  where u.username = lower(trim(p_username))
    and u.is_active
    and u.password_hash = crypt(p_password, u.password_hash);

  if found then
    update app_user set last_login = now()
    where app_user.username = lower(trim(p_username));
  end if;
end $$;

-- Only the server (service role) may create users; the login check is callable by the app.
revoke execute on function public.create_app_user(text,text,text,int) from public, anon, authenticated;
grant  execute on function public.create_app_user(text,text,text,int) to service_role;
revoke execute on function public.verify_login(text,text) from public;
grant  execute on function public.verify_login(text,text) to anon, authenticated, service_role;

-- create accounts for testing
select public.create_app_user('admin', '082006', 'System Administrator', 1);
select public.create_app_user('staff', '082006', 'Staff User', 2);

-- Row Level Security --
do $$
declare t text;
begin
  foreach t in array array['location','soil_type','irrigation_type','crop_category',
    'growing_season','planting_status','quality_grade','harvest_remark',
    'farmer','farm','crop','planting','harvest','user_role','app_user']
  loop
    execute format('alter table public.%I enable row level security', t);
  end loop;
end $$;