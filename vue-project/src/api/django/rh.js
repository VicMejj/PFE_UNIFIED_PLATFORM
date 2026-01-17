import axios from 'axios'

const API = import.meta.env.VITE_DJANGO_API_URL

export const getEmployees = async () => {
  const { data } = await axios.get(`${API}/rh/employees`)
  return data
}
