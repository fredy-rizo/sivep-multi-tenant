# Módulo de información ganadera

Diseño del dominio multiespecie: animales con ID único por organización,
genealogía, pesos, vacunas, eventos sanitarios, reproducción, movimientos y
resúmenes mensuales.

---

## 1. Principios

1. **Multiespecie configurable:** la especie y la raza no están codificadas en el
   esquema; cada organización puede usar el catálogo global o crear el suyo.
2. **ID único por organización:** el código del animal es único dentro del tenant,
   no globalmente. También se admiten arete y RFID.
3. **Eventos como fuente de verdad:** pesos, vacunas, tratamientos y movimientos
   se registran como eventos fechados. Los indicadores se derivan de ellos.
4. **Resumen mensual materializado:** para dashboards rápidos se mantiene
   `animal_monthly_summaries`, recalculable desde los eventos.
5. **Trazabilidad:** cada evento guarda quién lo registró y cuándo.
6. **Baja lógica:** los animales no se borran físicamente; cambian de estado.

---

## 2. Tablas

### 2.1 `species`

Catálogo de especies. `organization_id = NULL` significa catálogo global
compartido; una organización puede crear especies propias.

| Columna | Tipo | Notas |
| --- | --- | --- |
| id | bigint PK | |
| organization_id | bigint NULL FK | NULL = global |
| code | varchar(40) | `bovino`, `bufalino`, `porcino`… |
| name | varchar(120) | |
| is_active | boolean | |
| timestamps | | |

Único: `(organization_id, code)`.

### 2.2 `breeds`

| Columna | Tipo | Notas |
| --- | --- | --- |
| id | bigint PK | |
| organization_id | bigint NULL FK | NULL = global |
| species_id | bigint FK | |
| code | varchar(40) | |
| name | varchar(120) | |
| is_active | boolean | |
| timestamps | | |

Único: `(organization_id, species_id, code)`.

### 2.3 `lots` (potreros / lotes)

| Columna | Tipo | Notas |
| --- | --- | --- |
| id | bigint PK | |
| organization_id | bigint FK | obligatorio |
| code | varchar(40) | |
| name | varchar(120) | |
| location | varchar(255) NULL | |
| area_hectares | decimal(10,2) NULL | |
| capacity | int NULL | |
| notes | text NULL | |
| is_active | boolean | |
| timestamps | | |

Único: `(organization_id, code)`.

### 2.4 `animals`

| Columna | Tipo | Notas |
| --- | --- | --- |
| id | bigint PK | |
| organization_id | bigint FK | obligatorio |
| code | varchar(60) | ID único del animal dentro de la organización |
| tag | varchar(60) NULL | arete visual |
| rfid | varchar(80) NULL | identificador electrónico |
| name | varchar(120) NULL | nombre o alias |
| species_id | bigint FK | |
| breed_id | bigint FK NULL | |
| lot_id | bigint FK NULL | potrero actual |
| sex | enum(`macho`,`hembra`) | |
| birth_date | date NULL | |
| birth_weight_kg | decimal(8,2) NULL | |
| dam_id | bigint NULL FK animals | madre |
| sire_id | bigint NULL FK animals | padre |
| status | enum(`activo`,`preñada`,`lactando`,`vendido`,`fallecido`,`trasladado`) | |
| photo_path | varchar(500) NULL | |
| notes | text NULL | |
| created_by | bigint NULL FK users | |
| timestamps | | |
| deleted_at | timestamp NULL | soft delete |

Índices y restricciones:

- Único `(organization_id, code)`.
- Único `(organization_id, rfid)` cuando no es NULL.
- Índices: `(organization_id, status)`, `(organization_id, lot_id)`,
  `(organization_id, species_id)`, `(organization_id, birth_date)`.
- `dam_id` y `sire_id` no pueden ser el mismo animal ni formar ciclos directos
  (validación en aplicación).

### 2.5 `event_types`

Tipos de evento configurables. Los globales (`organization_id = NULL`) cubren los
casos comunes; la organización puede crear más.

| Columna | Tipo | Notas |
| --- | --- | --- |
| id | bigint PK | |
| organization_id | bigint NULL FK | NULL = global |
| code | varchar(60) | `pesaje`, `vacunacion`, `desparasitacion`, `tratamiento`, `celo`, `monta`, `diagnostico_gestacion`, `parto`, `destete`, `movimiento`, `venta`, `muerte` |
| name | varchar(120) | |
| category | enum(`peso`,`sanidad`,`reproduccion`,`movimiento`,`produccion`,`otro`) | |
| requires_weight | boolean | |
| requires_vaccine | boolean | |
| is_active | boolean | |
| sort_order | int | |
| timestamps | | |

Único: `(organization_id, code)`.

### 2.6 `vaccines`

Catálogo de vacunas. Puede ser global o propio de la organización.

| Columna | Tipo | Notas |
| --- | --- | --- |
| id | bigint PK | |
| organization_id | bigint NULL FK | NULL = global |
| name | varchar(150) | |
| disease | varchar(150) NULL | |
| manufacturer | varchar(150) NULL | |
| default_dose_ml | decimal(8,2) NULL | |
| interval_days | int NULL | refuerzo sugerido |
| is_active | boolean | |
| timestamps | | |

### 2.7 `animal_events`

Registro central de la vida del animal.

| Columna | Tipo | Notas |
| --- | --- | --- |
| id | bigint PK | |
| organization_id | bigint FK | |
| animal_id | bigint FK | |
| event_type_id | bigint FK | |
| event_date | date | fecha real del evento |
| weight_kg | decimal(8,2) NULL | para pesajes |
| vaccine_id | bigint FK NULL | para vacunación |
| dose_ml | decimal(8,2) NULL | |
| batch_number | varchar(80) NULL | lote de la vacuna |
| next_due_date | date NULL | próxima dosis / control |
| diagnosis | varchar(255) NULL | |
| treatment | text NULL | |
| cost | decimal(15,2) NULL | |
| notes | text NULL | |
| metadata | json NULL | datos específicos del tipo de evento |
| created_by | bigint NULL FK users | |
| timestamps | | |

Índices:

- `(organization_id, animal_id, event_date)`
- `(organization_id, event_type_id)`
- `(organization_id, next_due_date)`
- `(organization_id, vaccine_id)`

Reglas:

- Si el tipo tiene `requires_weight = true`, `weight_kg` es obligatorio.
- Si el tipo tiene `requires_vaccine = true`, `vaccine_id` es obligatorio.
- Un evento de peso actualiza el peso actual del animal si es el más reciente.
- Un evento de movimiento actualiza `animals.lot_id`.
- Un evento de venta/muerte actualiza `animals.status`.
- Un evento de parto puede crear la cría y enlazarla con `dam_id`.

### 2.8 `animal_movements` (opcional, recomendado)

Historial explícito de traslados entre potreros.

| Columna | Tipo | Notas |
| --- | --- | --- |
| id | bigint PK | |
| organization_id | bigint FK | |
| animal_id | bigint FK | |
| from_lot_id | bigint NULL FK | |
| to_lot_id | bigint NULL FK | |
| moved_at | date | |
| reason | varchar(255) NULL | |
| created_by | bigint NULL FK users | |
| timestamps | | |

También puede modelarse como evento de categoría `movimiento` con
`metadata = { from_lot_id, to_lot_id }`. Se recomienda la tabla dedicada si los
traslados son frecuentes.

### 2.9 `animal_monthly_summaries`

Resumen mensual por animal. Se recalcula desde eventos; nunca es la fuente de
verdad.

| Columna | Tipo | Notas |
| --- | --- | --- |
| id | bigint PK | |
| organization_id | bigint FK | |
| animal_id | bigint FK | |
| period | char(7) | `YYYY-MM` |
| last_weight_kg | decimal(8,2) NULL | último peso del mes |
| avg_weight_kg | decimal(8,2) NULL | promedio de pesos del mes |
| weight_gain_kg | decimal(8,2) NULL | vs. último peso del mes anterior |
| daily_gain_g | decimal(8,2) NULL | ganancia media diaria |
| events_count | int | |
| vaccines_count | int | |
| notes | text NULL | |
| timestamps | | |

Único: `(animal_id, period)`. Índice `(organization_id, period)`.

### 2.10 Relación con ventas

`sale_items` incluirá `animal_id` nullable. Al vender un animal:

- Se crea la venta y el ítem.
- Se marca `animals.status = vendido`.
- Se registra un evento de categoría `movimiento`/`venta`.
- Se impide vender un animal ya vendido o fallecido.

---

## 3. Reglas de negocio

### 3.1 Identificación

- `code` es obligatorio y único por organización.
- `rfid`, si existe, también es único por organización.
- El código no cambia una vez creado; se puede corregir con auditoría.
- Se permiten búsquedas por código, arete, RFID, nombre, raza y potrero.

### 3.2 Peso y GMD

- Los pesajes son eventos con `weight_kg`.
- El peso actual es el del evento de peso más reciente.
- GMD = `(peso_final - peso_inicial) / días` expresada en gramos/día.
- El resumen mensual compara contra el último peso del mes anterior.
- Si hay menos de dos pesajes, GMD = `null` (no cero).

### 3.3 Vacunas y sanidad

- Una vacunación exige vacuna, fecha y dosis.
- `next_due_date` alimenta el calendario de próximas dosis.
- Las alertas se generan para eventos con `next_due_date` dentro de los próximos
  30 días.
- Un tratamiento puede tener diagnóstico, medicamento, dosis y costo.
- Los eventos sanitarios son visibles para `veterinario`, `admin_granja` y
  `super_admin`; `operario` solo registra lo permitido.

### 3.4 Reproducción

- Estados reproductivos: celo, monta/inseminación, diagnóstico de gestación,
  parto, destete.
- `animals.status = preñada` se actualiza con el diagnóstico positivo.
- La fecha probable de parto se guarda en `metadata` del evento.
- Al registrar un parto se puede crear la cría y enlazar `dam_id` y `sire_id`.
- Validar que madre y padre no sean el mismo animal y que la madre sea hembra.

### 3.5 Movimientos y bajas

- Traslado entre potreros actualiza `lot_id` y deja historial.
- Venta: estado `vendido`, no se puede volver a vender.
- Muerte: estado `fallecido`, no genera más eventos productivos.
- Traslado a otra organización: estado `trasladado` y registro de auditoría.

### 3.6 Multiespecie

- Cada animal pertenece a una especie y, opcionalmente, a una raza.
- La raza debe pertenecer a la misma especie.
- Los tipos de evento y vacunas pueden ser globales o propios del tenant.
- El catálogo global se siembra con bovino, bufalino, porcino, equino, caprino y
  ovino; cada organización puede desactivar los que no use.

---

## 4. API propuesta

Prefijo `/api/v1`. Todas las rutas privadas usan `auth:sanctum` y el middleware
de organización.

### Animales

```
GET    /api/v1/animals                 # lista con filtros y paginación
POST   /api/v1/animals                 # crear animal
GET    /api/v1/animals/{animal}        # ficha completa
PUT    /api/v1/animals/{animal}        # actualizar datos
DELETE /api/v1/animals/{animal}        # baja lógica
GET    /api/v1/animals/summary         # KPIs del inventario
GET    /api/v1/animals/{animal}/timeline
GET    /api/v1/animals/{animal}/monthly?from=YYYY-MM&to=YYYY-MM
```

Filtros de lista: `search`, `species_id`, `breed_id`, `lot_id`, `status`, `sex`,
`age_min_months`, `age_max_months`, `sort`, `per_page`.

### Eventos y pesos

```
GET    /api/v1/animals/{animal}/events
POST   /api/v1/animals/{animal}/events          # evento genérico
POST   /api/v1/animals/{animal}/weights         # atajo de pesaje
POST   /api/v1/animals/{animal}/vaccinations    # atajo de vacunación
PUT    /api/v1/animals/{animal}/events/{event}
DELETE /api/v1/animals/{animal}/events/{event}
```

### Catálogos

```
GET|POST        /api/v1/species
PUT|DELETE      /api/v1/species/{species}
GET|POST        /api/v1/breeds
PUT|DELETE      /api/v1/breeds/{breed}
GET|POST        /api/v1/lots
PUT|DELETE      /api/v1/lots/{lot}
GET|POST        /api/v1/vaccines
PUT|DELETE      /api/v1/vaccines/{vaccine}
GET|POST        /api/v1/event-types
PUT|DELETE      /api/v1/event-types/{eventType}
```

### Reportes ganaderos

```
GET /api/v1/reports/livestock/inventory
GET /api/v1/reports/livestock/weights?period=YYYY-MM
GET /api/v1/reports/livestock/gmd?from=...&to=...
GET /api/v1/reports/livestock/vaccines/upcoming?days=30
GET /api/v1/reports/livestock/reproduction
GET /api/v1/reports/livestock/export          # CSV/Excel según plan
```

### Formato de respuesta

```json
{
  "data": [
    {
      "id": 12,
      "code": "A-001",
      "name": "Lucero",
      "species": { "id": 1, "name": "Bovino" },
      "breed": { "id": 3, "name": "Brahman" },
      "sex": "hembra",
      "birth_date": "2023-04-12",
      "age_months": 43,
      "current_weight_kg": 412,
      "lot": { "id": 2, "name": "Potrero Norte" },
      "status": "activo",
      "last_weight_at": "2026-10-15",
      "next_vaccine_at": "2027-03-10"
    }
  ],
  "meta": { "current_page": 1, "per_page": 20, "total": 5 },
  "links": {}
}
```

---

## 5. Vistas de la SPA

| Vista | Contenido |
| --- | --- |
| `/ganado` | KPIs, búsqueda, filtros, tabla paginada, alta de animal |
| `/ganado/:id` | Ficha, genealogía, KPIs, pestañas de resumen, pesos, vacunas y eventos |
| `/ganado/:id/evento/nuevo` | Formulario contextual según tipo de evento |
| `/ganado/reportes` | Peso promedio, GMD, calendario sanitario y reproducción |
| `/ganado/catalogos` | Especies, razas, potreros, vacunas y tipos de evento |

La UI actual de `web/` ya refleja `/ganado` y `/ganado/:id` con datos de ejemplo.

---

## 6. Seeds de demostración

- Organización demo “Granja El Cairo”.
- Especies: bovino, bufalino, porcino.
- Razas: Brahman, Angus, Normando, Murrah.
- Potreros: Norte, Sur, Este.
- Vacunas: Aftosa, Clostridial, Brucelosis.
- Tipos de evento: pesaje, vacunación, desparasitación, tratamiento, celo,
  monta, diagnóstico de gestación, parto, destete, movimiento, venta, muerte.
- 8–10 animales con 6 meses de pesos, vacunas y eventos para probar reportes.

---

## 7. Pruebas mínimas

- Un usuario de la organización A no puede ver ni editar animales de B.
- `code` y `rfid` duplicados dentro de la misma organización → 422.
- El mismo `code` en dos organizaciones distintas → permitido.
- Pesaje actualiza el peso actual y el resumen mensual.
- GMD correcto con dos o más pesajes; `null` con uno solo.
- Vacunación exige vacuna y fecha.
- Venta marca al animal como `vendido` y no permite venderlo dos veces.
- Parto crea la cría y enlaza la madre.
- Gating: sin `livestock.reports` responde 402.
