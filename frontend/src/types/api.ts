export interface Line {
  id: number
  origin: string
  destination: string
  basePrice: string
  normalDuration: number
}

export interface Departure {
  id: number
  departureAt: string
  seats: number
  price?: string
  status: 'scheduled' | 'cancelled' | 'completed'
  line: Line
  availableSeats: number
  effectivePrice: string
}

export interface Booking {
  id: number
  numberOfPersons: number
  totalPrice: string
  createdAt: string
  status: 'pending' | 'confirmed' | 'cancelled'
  departure: Departure
}

export interface Collection<T> {
  member: T[]
  totalItems: number
}