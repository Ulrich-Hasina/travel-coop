import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import { api } from '@/api/client'

const TOKEN_KEY = 'coop_token'

interface TokenPayload {
  exp: number
  roles: string[]
  username: string
}

function decode(token: string): TokenPayload | null {
  try {
    const part = token.split('.')[1]
    if (!part) return null
    return JSON.parse(atob(part.replace(/-/g, '+').replace(/_/g, '/'))) as TokenPayload
  } catch {
    return null
  }
}

export const useAuthStore = defineStore('auth', () => {
  const token = ref<string | null>(localStorage.getItem(TOKEN_KEY))

  const payload = computed(() => (token.value ? decode(token.value) : null))
  const isAuthenticated = computed(() => !!payload.value && payload.value.exp * 1000 > Date.now())
  const email = computed(() => payload.value?.username ?? null)
  const isAdmin = computed(() => payload.value?.roles.includes('ROLE_ADMIN') ?? false)

  async function login(emailValue: string, password: string): Promise<void> {
    const data = await api<{ token: string }>('/api/login_check', {
      method: 'POST',
      body: { email: emailValue, password },
      format: 'json',
    })
    token.value = data.token
    localStorage.setItem(TOKEN_KEY, data.token)
  }

  function logout(): void {
    token.value = null
    localStorage.removeItem(TOKEN_KEY)
  }

  return { token, isAuthenticated, email, isAdmin, login, logout }
})
