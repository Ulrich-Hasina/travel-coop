<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { api, ApiError } from '@/api/client'
import DepartureCard from '@/components/DepartureCard.vue'
import type { Collection, Departure, Line } from '@/types/api'

// Doit correspondre au nombre d'éléments par page de l'API (30 par défaut)
const PER_PAGE = 30

const lines = ref<Line[]>([])
const origin = ref<string | null>(null)
const destination = ref<string | null>(null)
const date = ref('')
const page = ref(1)

const departures = ref<Departure[]>([])
const total = ref(0)
const loading = ref(false)
const error = ref<string | null>(null)
let requestId = 0

const origins = computed(() => [...new Set(lines.value.map((line) => line.origin))].sort())

const destinations = computed(() => {
  const matching = lines.value.filter((line) => !origin.value || line.origin === origin.value)
  return [...new Set(matching.map((line) => line.destination))].sort()
})

const pageCount = computed(() => Math.max(1, Math.ceil(total.value / PER_PAGE)))

watch(origin, () => {
  if (destination.value && !destinations.value.includes(destination.value)) {
    destination.value = null
  }
})

function buildQuery(): string {
  const params = new URLSearchParams()
  params.set('status', 'scheduled')
  if (origin.value) params.set('line.origin', origin.value)
  if (destination.value) params.set('line.destination', destination.value)

  if (date.value) {
    // Toute la journée choisie, à l'heure locale
    const start = new Date(`${date.value}T00:00:00`)
    const end = new Date(start)
    end.setDate(end.getDate() + 1)
    params.set('departureAt[after]', start.toISOString())
    params.set('departureAt[strictly_before]', end.toISOString())
  } else {
    params.set('departureAt[after]', new Date().toISOString())
  }

  params.set('page', String(page.value))
  return params.toString()
}

async function search(resetPage = true) {
  if (resetPage) page.value = 1
  const id = ++requestId
  loading.value = true
  error.value = null

  try {
    const data = await api<Collection<Departure>>(`/api/departures?${buildQuery()}`)
    if (id !== requestId) return // une recherche plus récente a pris le relais
    departures.value = data.member
    total.value = data.totalItems
  } catch (e) {
    if (id !== requestId) return
    error.value = e instanceof ApiError ? e.message : 'Impossible de charger les départs.'
  } finally {
    if (id === requestId) loading.value = false
  }
}

function goToPage(newPage: number) {
  page.value = newPage
  search(false)
}

onMounted(async () => {
  api<Collection<Line>>('/api/lines')
    .then((data) => {
      lines.value = data.member
    })
    .catch(() => {
      // Sans les lignes, les listes restent vides mais la recherche fonctionne
    })
  await search()
})
</script>

<template>
  <h1 class="text-h5 mb-4">Rechercher un départ</h1>

  <v-form @submit.prevent="search()">
    <v-row>
      <v-col cols="12" sm="4">
        <v-select v-model="origin" :items="origins" label="Départ" clearable />
      </v-col>
      <v-col cols="12" sm="4">
        <v-select v-model="destination" :items="destinations" label="Destination" clearable />
      </v-col>
      <v-col cols="12" sm="4">
        <v-text-field v-model="date" type="date" label="Date" />
      </v-col>
    </v-row>
    <v-btn type="submit" color="primary" :loading="loading">Rechercher</v-btn>
  </v-form>

  <v-alert v-if="error" type="error" class="mt-4">{{ error }}</v-alert>

  <p v-else-if="!loading && departures.length === 0" class="mt-6">
    Aucun départ ne correspond à votre recherche.
  </p>

  <v-row class="mt-2">
    <v-col v-for="departure in departures" :key="departure.id" cols="12" md="6">
      <DepartureCard :departure="departure" />
    </v-col>
  </v-row>

  <v-pagination
    v-if="pageCount > 1"
    :model-value="page"
    :length="pageCount"
    class="mt-4"
    @update:model-value="goToPage"
  />
</template>
