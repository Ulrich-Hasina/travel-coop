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
  <main>
    <h1>Connexion</h1>
    <form @submit.prevent="submit">
      <label>Email <input v-model="email" type="email" required autocomplete="username" /></label>
      <label>
        Mot de passe
        <input v-model="password" type="password" required autocomplete="current-password" />
      </label>
      <p v-if="error" role="alert">{{ error }}</p>
      <button type="submit" :disabled="loading">Se connecter</button>
    </form>
  </main>
</template>
