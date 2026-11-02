// À remplacer par le code ISO de la monnaie de la coopérative (EUR, XOF, MGA...)
const CURRENCY = 'EUR'

const dateTimeFormat = new Intl.DateTimeFormat('fr-FR', {
  dateStyle: 'full',
  timeStyle: 'short',
})

const priceFormat = new Intl.NumberFormat('fr-FR', {
  style: 'currency',
  currency: CURRENCY,
  minimumFractionDigits: 0,
  maximumFractionDigits: 0,
})

export function formatDateTime(iso: string): string {
  return dateTimeFormat.format(new Date(iso))
}

export function formatPrice(amount: string): string {
  return priceFormat.format(Number(amount))
}

export function formatDuration(minutes: number): string {
  const hours = Math.floor(minutes / 60)
  const rest = minutes % 60
  return rest === 0 ? `${hours} h` : `${hours} h ${String(rest).padStart(2, '0')}`
}
