export interface Violation {
  propertyPath: string
  title?: string
  message?: string
}

export class ApiError extends Error {
  constructor(
    public status: number,
    message: string,
    public violations: Violation[] = [],
  ) {
    super(message)
    this.name = 'ApiError'
  }
}

let getToken: () => string | null = () => null
let onUnauthorized: () => void = () => {}

export function configureApi(options: {
  getToken: () => string | null
  onUnauthorized: () => void
}): void {
  getToken = options.getToken
  onUnauthorized = options.onUnauthorized
}

interface RequestOptions {
  method?: 'GET' | 'POST' | 'PATCH' | 'DELETE'
  body?: unknown
  // 'json' pour nos contrôleurs (connexion, inscription, annulation),
  // 'jsonld' pour les ressources API Platform (départs, réservations)
  format?: 'json' | 'jsonld'
}

export async function api<T>(path: string, options: RequestOptions = {}): Promise<T> {
  const mime = options.format === 'json' ? 'application/json' : 'application/ld+json'
  const headers: Record<string, string> = { Accept: mime }
  const token = getToken()
  if (token) headers.Authorization = `Bearer ${token}`
  if (options.body !== undefined) headers['Content-Type'] = mime

  const response = await fetch(path, {
    method: options.method ?? 'GET',
    headers,
    body: options.body !== undefined ? JSON.stringify(options.body) : undefined,
  })

  const data: unknown = await response.json().catch(() => null)

  if (!response.ok) {
    if (response.status === 401 && token) onUnauthorized()
    const body = (data ?? {}) as Record<string, unknown>
    throw new ApiError(
      response.status,
      errorMessage(body, response.status),
      (body.violations as Violation[] | undefined) ?? [],
    )
  }

  return data as T
}

function errorMessage(body: Record<string, unknown>, status: number): string {
  const message = body.detail ?? body.description ?? body.message
  return typeof message === 'string' ? message : `Erreur ${status}`
}
