<script setup lang="ts">
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ApiError } from '@/api/client'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()

const email = ref('')
const password = ref('')
const error = ref<string | null>(null)
const loading = ref(false)

async function submit() {
  error.value = null
  loading.value = true
  try {
    await auth.login(email.value, password.value)
    await router.push((route.query.redirect as string) || '/')
  } catch (e) {
    error.value =
      e instanceof ApiError && e.status === 401
        ? 'Email ou mot de passe incorrect.'
        : 'Connexion impossible, réessayez.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <v-row justify="center">
    <v-col cols="12" sm="8" md="5">
      <v-card class="pa-4">
        <v-card-title>Connexion</v-card-title>
        <v-form @submit.prevent="submit">
          <v-text-field
            v-model="email"
            type="email"
            label="Email"
            autocomplete="username"
            required
          />
          <v-text-field
            v-model="password"
            type="password"
            label="Mot de passe"
            autocomplete="current-password"
            required
          />
          <v-alert v-if="error" type="error" density="compact" class="mb-4">{{ error }}</v-alert>
          <v-btn type="submit" color="primary" block :loading="loading">Se connecter</v-btn>
        </v-form>
      </v-card>
    </v-col>
  </v-row>
</template>
