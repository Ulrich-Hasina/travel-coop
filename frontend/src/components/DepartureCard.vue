<script setup lang="ts">
import { computed } from 'vue'
import type { Departure } from '@/types/api'
import { formatDateTime, formatDuration, formatPrice } from '@/utils/format'

const props = defineProps<{ departure: Departure }>()

const seatsLabel = computed(() => {
  const seats = props.departure.availableSeats
  if (seats === 0) return 'Complet'
  return seats === 1 ? '1 place restante' : `${seats} places restantes`
})

const seatsColor = computed(() => {
  const seats = props.departure.availableSeats
  if (seats === 0) return 'error'
  return seats <= 3 ? 'warning' : 'success'
})
</script>

<template>
  <v-card>
    <v-card-item>
      <v-card-title> {{ departure.line.origin }} → {{ departure.line.destination }} </v-card-title>
      <v-card-subtitle>{{ formatDateTime(departure.departureAt) }}</v-card-subtitle>
    </v-card-item>

    <v-card-text>
      <div v-if="departure.line.normalDuration">
        Durée : {{ formatDuration(departure.line.normalDuration) }}
      </div>
      <div class="text-h6 mt-2">
        {{ formatPrice(departure.effectivePrice) }}
        <span class="text-body-2">par personne</span>
      </div>
    </v-card-text>

    <v-card-actions>
      <v-chip :color="seatsColor" size="small">{{ seatsLabel }}</v-chip>
    </v-card-actions>
  </v-card>
</template>
