import axios from 'axios'
const API_BASE = 'http://localhost:8000/api/convert'


export const apiClient = axios.create({
    baseURL: API_BASE,
    timeout: 15000,
    headers: { 'Content-Type': 'application/json' }
})