<template>
    <div class="card">
        <form @submit.prevent="onSubmit">
            <div class="form-row">
                <div style="flex:1">
                    <label>Amount</label>
                    <input v-model.number="form.amount" type="number" step="0.01" min="0.01" required />
                </div>


                <div>
                    <label>From</label>
                    <input v-model="form.from_currency" maxlength="3" required />
                </div>


                <div>
                    <label>To</label>
                    <input v-model="form.to_currency" maxlength="3" required />
                </div>


                <div style="align-self:end">
                    <button :disabled="loading">Convert</button>
                </div>
            </div>
        </form>


        <div v-if="error" class="result error">
            <strong>Error:</strong> {{ error }}
        </div>


        <div v-if="result" class="result">
            <div><strong>{{ result.formatted_converted_amount }}</strong></div>
            <div class="small">Exchange rate: {{ result.formatted_exchange_rate }} ({{ result.exchange_rate }})</div>
            <div class="small">Duration: {{ metrics.duration_ms.toFixed(2) }} ms</div>
        </div>


        <div v-if="history.length" style="margin-top:12px">
            <h4>Recent conversions</h4>
            <ul>
                <li v-for="(h, idx) in history" :key="idx">{{ h.amount }} {{ h.from_currency }} → {{ h.to_currency }} =
                    {{ h.converted_amount }}</li>
            </ul>
        </div>
    </div>
</template>
<script setup>
const loading = ref(false)
const error = ref('')
const result = ref(null)
const history = ref([])
const metrics = reactive({ duration_ms: 0 })


async function onSubmit() {
    error.value = ''
    result.value = null
    loading.value = true
    const payload = {
        amount: form.amount,
        from_currency: form.from_currency.toUpperCase(),
        to_currency: form.to_currency.toUpperCase()
    }


    const start = performance.now()


    try {
        const res = await axios.post(`${API_BASE}/convert`, payload, { timeout: 15000 })
        const duration = performance.now() - start
        metrics.duration_ms = duration


        if (res.data && res.data.success) {
            result.value = res.data.data
            history.value.unshift({
                amount: payload.amount,
                from_currency: payload.from_currency,
                to_currency: payload.to_currency,
                converted_amount: result.value.converted_amount,
                at: new Date().toISOString()
            })
            if (history.value.length > 10) history.value.pop()
        } else {
            error.value = res.data?.message || 'Conversion failed'
        }
    } catch (e) {
        if (e.response && e.response.data) {
            error.value = e.response.data.message || JSON.stringify(e.response.data)
        } else {
            error.value = e.message
        }
    } finally {
        loading.value = false
    }
}
</script>

<style scoped>
label {
    display: block;
    font-size: 0.85rem;
    color: #333;
    margin-bottom: 4px
}

input {
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    width: 100%
}

button {
    padding: 8px 12px;
    border-radius: 6px;
    border: none;
    background: #111;
    color: #fff;
    cursor: pointer
}

button:disabled {
    opacity: 0.6;
    cursor: not-allowed
}
</style>