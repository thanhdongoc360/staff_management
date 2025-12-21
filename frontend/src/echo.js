import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

let echoInstance = null

export const getEcho = () => {
  const enabled = (import.meta.env.VITE_ECHO_ENABLED || 'false') === 'true'
  if (!enabled) return null
  if (echoInstance) return echoInstance

  const key = import.meta.env.VITE_PUSHER_APP_KEY
  const cluster = import.meta.env.VITE_PUSHER_APP_CLUSTER || 'ap1'
  if (!key) return null

  Pusher.logToConsole = false

  echoInstance = new Echo({
    broadcaster: 'pusher',
    key,
    cluster,
    forceTLS: true,
    authEndpoint: '/broadcasting/auth',
    auth: {
      headers: {
        Authorization: `Bearer ${localStorage.getItem('token') || ''}`
      }
    }
  })

  return echoInstance
}
