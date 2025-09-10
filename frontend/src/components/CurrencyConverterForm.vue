<template>
  <form @submit.prevent="convertCurrency">
    <input type="number" v-model.number="amount" placeholder="Amount" min="1" step="1" required />

    <select v-model="fromCurrency" required>
      <option value="" disabled>Select From Currency</option>
      <option v-for="currency in currencies" :key="currency" :value="currency">
        {{ currency }}
      </option>
    </select>

    <select v-model="toCurrency" required>
      <option value="" disabled>Select To Currency</option>
      <option v-for="currency in currencies" :key="currency" :value="currency">
        {{ currency }}
      </option>
    </select>

    <button :disabled="loading">
      {{ loading ? 'Converting...' : 'Convert' }}
    </button>

    <div v-if="error" class="result" style="color:red;">
      {{ error }}
    </div>

    <div v-if="result" class="result">
      {{ result.amount }} {{ result.from_currency }} → {{ result.converted_amount }} {{ result.to_currency }}
      <br />
      Exchange Rate: {{ result.exchange_rate }}
    </div>
  </form>
</template>

<script setup>
import { ref } from 'vue'

// Default amount 100, only positive integers
const amount = ref(100)
const fromCurrency = ref('')
const toCurrency = ref('')
const result = ref(null)
const error = ref('')
const loading = ref(false)

// List of currencies (extend as needed)
const currencies = [
  'USD', 'EUR', 'GBP', 'JPY', 'CHF', 'AUD', 'CAD'
]

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || '';

async function convertCurrency() {
  result.value = null
  error.value = ''
  loading.value = true

  if (fromCurrency.value && toCurrency.value && fromCurrency.value === toCurrency.value) {
      error.value = 'Source and destination currencies cannot be the same'; 
      loading.value = false     
  }
  else{  
    try {
      const response = await fetch(`http://localhost:8000/api/convert`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          amount: amount.value,
          from_currency: fromCurrency.value,
          to_currency: toCurrency.value
        }),
      })

      const data = await response.json()
      if (!response.ok) {
        error.value = 'Currency conversion is currently unavailable. Please try again later.';
      }
      else {
        result.value = data.data
      }

    } catch (error) {
      error.value = 'Currency conversion is currently unavailable. Please try again later.';                
      console.error('Conversion error:', error);
    } finally {
      loading.value = false
    }
  }
}
</script>
