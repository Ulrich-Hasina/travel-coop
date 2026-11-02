<script setup lang="ts">
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()

async function logout() {
  auth.logout()
  await router.push({ name: 'home' })
}
</script>

<template>
  <v-app>
    <v-app-bar color="primary" density="comfortable">
      <v-app-bar-title>
        <RouterLink :to="{ name: 'home' }" class="brand">Coopérative de transport</RouterLink>
      </v-app-bar-title>

      <template v-if="auth.isAuthenticated">
        <span class="text-body-2 mx-2 d-none d-sm-inline">{{ auth.email }}</span>
        <v-btn icon="mdi-logout" title="Se déconnecter" @click="logout" />
      </template>
      <v-btn v-else :to="{ name: 'login' }">Connexion</v-btn>
    </v-app-bar>

    <v-main>
      <v-container>
        <RouterView />
      </v-container>
    </v-main>
  </v-app>
</template>

<style scoped>
.brand {
  color: inherit;
  text-decoration: none;
}
</style>
