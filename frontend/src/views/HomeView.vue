<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { api } from '@/api/client'
import type { Collection, Departure } from '@/types/api'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const departures = ref<Departure[]>([])

onMounted(async () => {
  const now = encodeURIComponent(new Date().toISOString())
  const data = await api<Collection<Departure>>(
    `/api/departures?status=scheduled&departureAt[after]=${now}`,
  )
  departures.value = data.member
})
</script>

<template>
  <main>
    <p v-if="auth.isAuthenticated">
      Connecté : {{ auth.email }} <button @click="auth.logout()">Se déconnecter</button>
    </p>
    <p v-else><RouterLink :to="{ name: 'login' }">Se connecter</RouterLink></p>

    <h1>Départs à venir</h1>
    <ul>
      <li v-for="d in departures" :key="d.id">
        {{ d.line.origin }} → {{ d.line.destination }} · {{ d.departureAt }} ·
        {{ d.availableSeats }} places · {{ d.effectivePrice }}
      </li>
    </ul>
  </main>
</template>