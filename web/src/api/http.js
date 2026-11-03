import axios from 'axios'

export const API_URL = import.meta.env.VITE_API_URL || '/api'

const http = axios.create({
  baseURL: API_URL,
  withCredentials: true,
  withXSRFToken: true,
  xsrfCookieName: 'XSRF-TOKEN',
  xsrfHeaderName: 'X-XSRF-TOKEN',
  headers: {
    Accept: 'application/json',
  },
})

// Sanctum SPA: obtiene la cookie XSRF-TOKEN antes de cualquier petición que
// modifique estado (login, POST, PUT, DELETE...).
export async function fetchCsrfCookie() {
  await axios.get('/sanctum/csrf-cookie', {
    withCredentials: true,
    headers: { Accept: 'application/json' },
  })
}

http.interceptors.response.use(
  (response) => response,
  (error) => {
    // El guard del router y los stores deciden qué hacer con el 401.
    // Aquí solo normalizamos el mensaje para que las vistas no repitan lógica.
    if (!error.response?.data?.message && error.response?.data?.error) {
      error.response.data.message = error.response.data.error
    }
    return Promise.reject(error)
  },
)

export default http
