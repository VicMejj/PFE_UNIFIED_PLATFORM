import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_LARAVEL_API_URL
})

// Add token to requests
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('laravel_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

export const getDocuments = async () => {
  const { data } = await api.get('/documents')
  return data
}

export const uploadDocument = async (formData) => {
  const { data } = await api.post('/documents', formData, {
    headers: {
      'Content-Type': 'multipart/form-data'
    }
  })
  return data
}

export const downloadDocument = async (id) => {
  return api.get(`/documents/${id}/download`, {
    responseType: 'blob'
  })
}

export const deleteDocument = async (id) => {
  const { data } = await api.delete(`/documents/${id}`)
  return data
}
