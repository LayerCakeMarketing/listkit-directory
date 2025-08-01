<template>
  <div class="flex gap-2" :class="containerClass">
    <input
      v-for="(input, index) in inputs"
      :key="index"
      ref="inputRefs"
      v-model="inputs[index]"
      type="text"
      inputmode="numeric"
      pattern="[0-9]"
      maxlength="1"
      class="w-12 h-12 text-center text-lg font-semibold border-2 rounded-lg focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:outline-none transition-colors"
      :class="[
        inputs[index] ? 'border-gray-400' : 'border-gray-300',
        inputClass
      ]"
      @input="handleInput(index, $event)"
      @keydown="handleKeyDown(index, $event)"
      @paste="handlePaste"
      :disabled="disabled"
      :placeholder="placeholder"
    />
  </div>
</template>

<script setup>
import { ref, watch, nextTick } from 'vue'

const props = defineProps({
  modelValue: {
    type: String,
    default: ''
  },
  length: {
    type: Number,
    default: 6
  },
  disabled: {
    type: Boolean,
    default: false
  },
  placeholder: {
    type: String,
    default: '·'
  },
  containerClass: {
    type: String,
    default: ''
  },
  inputClass: {
    type: String,
    default: ''
  }
})

const emit = defineEmits(['update:modelValue', 'complete'])

const inputRefs = ref([])
const inputs = ref(Array(props.length).fill(''))

// Initialize inputs from modelValue
watch(() => props.modelValue, (newValue) => {
  if (newValue) {
    const chars = newValue.split('').slice(0, props.length)
    inputs.value = [...chars, ...Array(props.length - chars.length).fill('')]
  } else {
    inputs.value = Array(props.length).fill('')
  }
}, { immediate: true })

// Update modelValue when inputs change
watch(inputs, (newInputs) => {
  const value = newInputs.join('')
  emit('update:modelValue', value)
  
  if (value.length === props.length && !newInputs.includes('')) {
    emit('complete', value)
  }
}, { deep: true })

const focusInput = (index) => {
  nextTick(() => {
    if (inputRefs.value[index]) {
      inputRefs.value[index].focus()
    }
  })
}

const handleInput = (index, event) => {
  const value = event.target.value
  
  // Only allow single digit
  if (value.length > 1) {
    inputs.value[index] = value[0]
    event.target.value = value[0]
  }
  
  // Only allow numbers
  if (!/^\d*$/.test(value)) {
    inputs.value[index] = ''
    event.target.value = ''
    return
  }
  
  // Move to next input if value entered
  if (value && index < props.length - 1) {
    focusInput(index + 1)
  }
}

const handleKeyDown = (index, event) => {
  // Handle backspace
  if (event.key === 'Backspace') {
    if (!inputs.value[index] && index > 0) {
      // If current input is empty, move to previous input
      focusInput(index - 1)
      event.preventDefault()
    }
  }
  
  // Handle arrow keys
  if (event.key === 'ArrowLeft' && index > 0) {
    focusInput(index - 1)
    event.preventDefault()
  }
  
  if (event.key === 'ArrowRight' && index < props.length - 1) {
    focusInput(index + 1)
    event.preventDefault()
  }
}

const handlePaste = (event) => {
  event.preventDefault()
  const pastedData = event.clipboardData.getData('text')
  const digits = pastedData.replace(/\D/g, '').slice(0, props.length)
  
  if (digits) {
    const newInputs = digits.split('')
    for (let i = 0; i < newInputs.length && i < props.length; i++) {
      inputs.value[i] = newInputs[i]
    }
    
    // Focus the next empty input or the last input
    const nextEmptyIndex = inputs.value.findIndex(val => !val)
    const focusIndex = nextEmptyIndex !== -1 ? nextEmptyIndex : props.length - 1
    focusInput(Math.min(focusIndex, props.length - 1))
  }
}
</script>