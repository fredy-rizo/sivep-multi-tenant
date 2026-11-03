// Datos de ejemplo para previsualizar la UX del módulo ganadero mientras el
// backend Laravel no está conectado. Se eliminarán al integrar /api/animals.
export const demoAnimals = [
  {
    id: 'A-001',
    code: 'A-001',
    name: 'Lucero',
    species: 'Bovino',
    breed: 'Brahman',
    sex: 'Hembra',
    birthDate: '2023-04-12',
    weight: 412,
    lot: 'Potrero Norte',
    status: 'Activo',
    sire: 'Tornado',
    dam: 'Nube',
    lastWeightAt: '2026-10-15',
    lastVaccine: 'Aftosa',
    nextVaccineAt: '2027-03-10',
    photo: '',
  },
  {
    id: 'A-002',
    code: 'A-002',
    name: 'Tornado',
    species: 'Bovino',
    breed: 'Angus',
    sex: 'Macho',
    birthDate: '2022-11-03',
    weight: 535,
    lot: 'Potrero Sur',
    status: 'Activo',
    sire: 'Relámpago',
    dam: 'Canela',
    lastWeightAt: '2026-10-12',
    lastVaccine: 'Clostridial',
    nextVaccineAt: '2027-04-12',
    photo: '',
  },
  {
    id: 'A-003',
    code: 'A-003',
    name: 'Canela',
    species: 'Bovino',
    breed: 'Brahman',
    sex: 'Hembra',
    birthDate: '2024-02-20',
    weight: 318,
    lot: 'Potrero Norte',
    status: 'Activo',
    sire: 'Tornado',
    dam: 'Lucero',
    lastWeightAt: '2026-10-15',
    lastVaccine: 'Aftosa',
    nextVaccineAt: '2027-03-10',
    photo: '',
  },
  {
    id: 'A-004',
    code: 'A-004',
    name: 'Nube',
    species: 'Bovino',
    breed: 'Normando',
    sex: 'Hembra',
    birthDate: '2021-08-15',
    weight: 470,
    lot: 'Potrero Este',
    status: 'Preñada',
    sire: 'Tornado',
    dam: '—',
    lastWeightAt: '2026-10-10',
    lastVaccine: 'Brucelosis',
    nextVaccineAt: '2027-01-20',
    photo: '',
  },
  {
    id: 'A-005',
    code: 'A-005',
    name: 'Rayo',
    species: 'Bufalino',
    breed: 'Murrah',
    sex: 'Macho',
    birthDate: '2023-09-01',
    weight: 405,
    lot: 'Potrero Sur',
    status: 'Activo',
    sire: '—',
    dam: '—',
    lastWeightAt: '2026-09-28',
    lastVaccine: 'Aftosa',
    nextVaccineAt: '2027-02-28',
    photo: '',
  },
]

export const demoSpecies = ['Bovino', 'Bufalino', 'Porcino', 'Equino']
export const demoStatuses = ['Activo', 'Preñada', 'Vendido', 'Fallecido']

export function getDemoAnimal(id) {
  return demoAnimals.find((animal) => animal.id === id) ?? null
}

export function demoWeightHistory(animalId) {
  const base = {
    'A-001': [372, 381, 390, 398, 405, 412],
    'A-002': [498, 506, 514, 521, 528, 535],
    'A-003': [272, 281, 291, 300, 309, 318],
    'A-004': [452, 458, 462, 466, 468, 470],
    'A-005': [365, 372, 380, 388, 397, 405],
  }[animalId] ?? [0, 0, 0, 0, 0, 0]

  const months = ['may', 'jun', 'jul', 'ago', 'sep', 'oct']
  return months.map((month, index) => ({
    month,
    weight: base[index],
  }))
}

export function demoVaccines(animalId) {
  return [
    {
      id: `${animalId}-v1`,
      vaccine: 'Aftosa',
      appliedAt: '2026-09-10',
      nextAt: '2027-03-10',
      batch: 'AF-2026-09',
      veterinarian: 'Dr. Andrés Rojas',
    },
    {
      id: `${animalId}-v2`,
      vaccine: 'Clostridial',
      appliedAt: '2026-06-18',
      nextAt: '2027-06-18',
      batch: 'CL-2026-06',
      veterinarian: 'Dra. Laura Méndez',
    },
  ]
}

export function demoEvents(animalId) {
  return [
    {
      id: `${animalId}-e1`,
      type: 'Pesaje',
      date: '2026-10-15',
      detail: 'Peso registrado: 412 kg',
      user: 'Carlos Pérez',
    },
    {
      id: `${animalId}-e2`,
      type: 'Vacunación',
      date: '2026-09-10',
      detail: 'Aftosa · lote AF-2026-09',
      user: 'Dr. Andrés Rojas',
    },
    {
      id: `${animalId}-e3`,
      type: 'Movimiento',
      date: '2026-08-22',
      detail: 'Traslado a Potrero Norte',
      user: 'Carlos Pérez',
    },
    {
      id: `${animalId}-e4`,
      type: 'Sanidad',
      date: '2026-07-05',
      detail: 'Desparasitación preventiva',
      user: 'Dra. Laura Méndez',
    },
  ]
}
