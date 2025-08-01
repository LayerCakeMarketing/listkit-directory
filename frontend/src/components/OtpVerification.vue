<template>
    <div>
        <h3 class="text-lg font-medium text-gray-900 mb-4">Enter Verification Code</h3>
        <p class="text-gray-600 mb-6">
            We've sent a 6-digit verification code to 
            <strong>{{ destination }}</strong>
        </p>

        <div class="flex justify-center space-x-2 mb-6">
            <input
                v-for="(digit, index) in 6"
                :key="index"
                v-model="code[index]"
                @input="handleInput(index)"
                @keydown="handleKeydown($event, index)"
                @paste="handlePaste"
                :ref="el => inputs[index] = el"
                type="text"
                maxlength="1"
                class="w-12 h-12 text-center text-xl font-semibold border-2 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                :class="error ? 'border-red-500' : 'border-gray-300'"
            />
        </div>

        <div v-if="error" class="mb-4 text-center">
            <p class="text-sm text-red-600">{{ error }}</p>
            <p v-if="attemptsRemaining > 0" class="text-xs text-gray-600 mt-1">
                {{ attemptsRemaining }} attempts remaining
            </p>
        </div>

        <div class="flex flex-col items-center space-y-4">
            <button
                @click="verifyCode"
                :disabled="!isComplete || verifying"
                class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
            >
                {{ verifying ? 'Verifying...' : 'Verify Code' }}
            </button>

            <div class="text-center">
                <p class="text-sm text-gray-600">
                    Didn't receive the code?
                    <button
                        @click="$emit('resend')"
                        :disabled="resending"
                        class="text-blue-600 hover:text-blue-800 font-medium"
                    >
                        {{ resending ? 'Sending...' : 'Resend Code' }}
                    </button>
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'

const props = defineProps({
    claim: {
        type: Object,
        required: true
    },
    verificationType: {
        type: String,
        required: true
    },
    destination: {
        type: String,
        required: true
    }
})

const emit = defineEmits(['verified', 'resend'])

// State
const code = ref(['', '', '', '', '', ''])
const inputs = ref([])
const verifying = ref(false)
const resending = ref(false)
const error = ref('')
const attemptsRemaining = ref(5)

// Computed
const isComplete = computed(() => {
    return code.value.every(digit => digit !== '')
})

const codeString = computed(() => {
    return code.value.join('')
})

// Methods
const handleInput = (index) => {
    const value = code.value[index]
    
    // Only allow digits
    if (value && !/^\d$/.test(value)) {
        code.value[index] = ''
        return
    }
    
    // Move to next input if value entered
    if (value && index < 5) {
        inputs.value[index + 1]?.focus()
    }
}

const handleKeydown = (event, index) => {
    // Handle backspace
    if (event.key === 'Backspace' && !code.value[index] && index > 0) {
        inputs.value[index - 1]?.focus()
    }
    
    // Handle arrow keys
    if (event.key === 'ArrowLeft' && index > 0) {
        inputs.value[index - 1]?.focus()
    }
    if (event.key === 'ArrowRight' && index < 5) {
        inputs.value[index + 1]?.focus()
    }
}

const handlePaste = (event) => {
    event.preventDefault()
    const pastedData = event.clipboardData.getData('text')
    const digits = pastedData.replace(/\D/g, '').slice(0, 6).split('')
    
    digits.forEach((digit, index) => {
        if (index < 6) {
            code.value[index] = digit
        }
    })
    
    // Focus last filled input or last input
    const lastFilledIndex = digits.length - 1
    if (lastFilledIndex >= 0 && lastFilledIndex < 6) {
        inputs.value[Math.min(lastFilledIndex + 1, 5)]?.focus()
    }
}

const verifyCode = async () => {
    verifying.value = true
    error.value = ''
    
    try {
        const response = await axios.post(`/api/claims/${props.claim.id}/verify`, {
            code: codeString.value
        })
        
        emit('verified', response.data)
    } catch (err) {
        error.value = err.response?.data?.message || 'Invalid code'
        attemptsRemaining.value = err.response?.data?.attempts_remaining || 0
        
        // Clear code on error
        code.value = ['', '', '', '', '', '']
        inputs.value[0]?.focus()
    } finally {
        verifying.value = false
    }
}

// Focus first input on mount
onMounted(() => {
    inputs.value[0]?.focus()
})
</script>