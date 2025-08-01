<template>
  <TransitionRoot appear :show="isOpen" as="template">
    <Dialog as="div" @close="closeModal" class="relative z-50">
      <TransitionChild
        as="template"
        enter="duration-300 ease-out"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="duration-200 ease-in"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" />
      </TransitionChild>

      <div class="fixed inset-0 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4 text-center">
          <TransitionChild
            as="template"
            enter="duration-300 ease-out"
            enter-from="opacity-0 scale-95"
            enter-to="opacity-100 scale-100"
            leave="duration-200 ease-in"
            leave-from="opacity-100 scale-100"
            leave-to="opacity-0 scale-95"
          >
            <DialogPanel class="w-full max-w-2xl transform overflow-hidden rounded-2xl bg-white p-6 text-left align-middle shadow-xl transition-all">
              <!-- Header -->
              <DialogTitle as="h3" class="text-2xl font-bold leading-6 text-gray-900 mb-4">
                Claim {{ place.title }}
              </DialogTitle>

              <!-- Step Indicator -->
              <nav aria-label="Progress" class="mb-8">
                <ol class="flex items-center justify-between">
                  <li v-for="(step, index) in steps" :key="step.name" class="relative">
                    <div class="flex items-center">
                      <div
                        :class="[
                          index <= currentStep ? 'bg-indigo-600' : 'bg-gray-200',
                          'h-8 w-8 rounded-full flex items-center justify-center text-white text-sm font-semibold'
                        ]"
                      >
                        <CheckIcon v-if="index < currentStep" class="h-5 w-5" />
                        <span v-else>{{ index + 1 }}</span>
                      </div>
                      <span
                        v-if="index < steps.length - 1"
                        :class="[
                          index < currentStep ? 'bg-indigo-600' : 'bg-gray-200',
                          'ml-2 h-0.5 w-full sm:w-20'
                        ]"
                      />
                    </div>
                    <span class="mt-2 text-xs font-medium text-gray-500">{{ step.name }}</span>
                  </li>
                </ol>
              </nav>

              <!-- Step Content -->
              <div class="mt-6">
                <!-- Step 1: Verification Method -->
                <div v-if="currentStep === 0" class="space-y-4">
                  <p class="text-gray-600 mb-4">
                    Please select how you'd like to verify your ownership of this business:
                  </p>
                  
                  <RadioGroup v-model="formData.verification_method">
                    <div class="space-y-3">
                      <RadioGroupOption
                        v-for="method in verificationMethods"
                        :key="method.value"
                        :value="method.value"
                        v-slot="{ active, checked }"
                      >
                        <div
                          :class="[
                            active ? 'ring-2 ring-indigo-600 ring-offset-2' : '',
                            checked ? 'bg-indigo-50 border-indigo-600' : 'bg-white border-gray-200',
                            'relative flex cursor-pointer rounded-lg border p-4 shadow-sm focus:outline-none'
                          ]"
                        >
                          <div class="flex flex-1">
                            <div class="flex flex-col">
                              <RadioGroupLabel as="span" class="block text-sm font-medium text-gray-900">
                                {{ method.label }}
                              </RadioGroupLabel>
                              <RadioGroupDescription as="span" class="mt-1 flex items-center text-sm text-gray-500">
                                {{ method.description }}
                              </RadioGroupDescription>
                            </div>
                          </div>
                          <component
                            :is="method.icon"
                            :class="[checked ? 'text-indigo-600' : 'text-gray-400', 'h-5 w-5']"
                            aria-hidden="true"
                          />
                          <span
                            :class="[
                              active ? 'border' : 'border-2',
                              checked ? 'border-indigo-600' : 'border-transparent',
                              'pointer-events-none absolute -inset-px rounded-lg'
                            ]"
                            aria-hidden="true"
                          />
                        </div>
                      </RadioGroupOption>
                    </div>
                  </RadioGroup>

                  <!-- Dynamic fields based on verification method -->
                  <div v-if="formData.verification_method === 'email'" class="mt-4">
                    <label for="business_email" class="block text-sm font-medium text-gray-700">
                      Business Email Address
                    </label>
                    <input
                      v-model="formData.business_email"
                      type="email"
                      id="business_email"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                      placeholder="contact@business.com"
                      required
                    />
                    <p class="mt-1 text-sm text-gray-500">
                      We'll send a verification code to this email address
                    </p>
                  </div>

                  <div v-if="formData.verification_method === 'phone'" class="mt-4">
                    <label for="business_phone" class="block text-sm font-medium text-gray-700">
                      Business Phone Number
                    </label>
                    <input
                      v-model="formData.business_phone"
                      type="tel"
                      id="business_phone"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                      placeholder="+1 (555) 123-4567"
                      required
                    />
                    <p class="mt-1 text-sm text-gray-500">
                      We'll send a verification code via SMS to this number
                    </p>
                  </div>

                  <div v-if="formData.verification_method === 'document'" class="mt-4">
                    <p class="text-sm text-gray-600">
                      You'll be able to upload business documents (license, tax documents, utility bills) in the next step.
                    </p>
                  </div>
                </div>

                <!-- Step 2: Verification -->
                <div v-if="currentStep === 1" class="space-y-4">
                  <!-- Email/Phone Verification -->
                  <div v-if="['email', 'phone'].includes(formData.verification_method)">
                    <p class="text-gray-600 mb-4">
                      We've sent a verification code to your {{ formData.verification_method }}. Please enter it below:
                    </p>
                    
                    <OtpInput
                      v-model="verificationCode"
                      :length="6"
                      @complete="handleVerificationCode"
                      class="justify-center"
                    />
                    
                    <div class="mt-4 text-center">
                      <button
                        @click="resendCode"
                        :disabled="resendTimer > 0"
                        class="text-sm text-indigo-600 hover:text-indigo-500 disabled:text-gray-400"
                      >
                        {{ resendTimer > 0 ? `Resend code in ${resendTimer}s` : 'Resend code' }}
                      </button>
                    </div>
                  </div>

                  <!-- Document Upload -->
                  <div v-if="formData.verification_method === 'document'" class="space-y-4">
                    <p class="text-gray-600 mb-4">
                      Please upload documents that prove your business ownership:
                    </p>
                    
                    <DocumentUpload
                      v-model:documents="uploadedDocuments"
                      :accept="['pdf', 'jpg', 'jpeg', 'png']"
                      :max-size="10"
                      :multiple="true"
                      class="mb-4"
                    />
                    
                    <div class="text-sm text-gray-500">
                      <p class="font-medium mb-2">Accepted documents:</p>
                      <ul class="list-disc list-inside space-y-1">
                        <li>Business license</li>
                        <li>Tax documents</li>
                        <li>Utility bills</li>
                        <li>Articles of incorporation</li>
                      </ul>
                    </div>
                  </div>
                </div>

                <!-- Step 3: Additional Information -->
                <div v-if="currentStep === 2" class="space-y-4">
                  <p class="text-gray-600 mb-4">
                    Please provide any additional information to support your claim:
                  </p>
                  
                  <div>
                    <label for="verification_notes" class="block text-sm font-medium text-gray-700">
                      Additional Notes (Optional)
                    </label>
                    <textarea
                      v-model="formData.verification_notes"
                      id="verification_notes"
                      rows="4"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                      placeholder="Provide any additional context about your business ownership..."
                    />
                  </div>

                  <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
                    <div class="flex">
                      <InformationCircleIcon class="h-5 w-5 text-blue-400" aria-hidden="true" />
                      <div class="ml-3">
                        <p class="text-sm text-blue-700">
                          Your claim will be reviewed by our team within 24-48 hours. You'll receive an email notification once it's approved.
                        </p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Footer Actions -->
              <div class="mt-8 flex justify-between">
                <button
                  v-if="currentStep > 0"
                  @click="previousStep"
                  type="button"
                  class="inline-flex justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                  Back
                </button>
                <div v-else />
                
                <div class="flex space-x-3">
                  <button
                    @click="closeModal"
                    type="button"
                    class="inline-flex justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                  >
                    Cancel
                  </button>
                  
                  <button
                    @click="nextStep"
                    :disabled="!canProceed"
                    type="button"
                    class="inline-flex justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    {{ currentStep === steps.length - 1 ? 'Submit Claim' : 'Continue' }}
                  </button>
                </div>
              </div>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import { RadioGroup, RadioGroupDescription, RadioGroupLabel, RadioGroupOption } from '@headlessui/vue'
import { CheckIcon, EnvelopeIcon, PhoneIcon, DocumentTextIcon, InformationCircleIcon } from '@heroicons/vue/24/outline'
import OtpInput from './OtpInput.vue'
import DocumentUpload from './DocumentUpload.vue'
import { useToast } from '@/composables/useToast'
import { api } from '@/utils/api'

const props = defineProps({
  isOpen: {
    type: Boolean,
    required: true
  },
  place: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['close', 'success'])

const { showToast } = useToast()

// State
const currentStep = ref(0)
const isSubmitting = ref(false)
const verificationCode = ref('')
const resendTimer = ref(0)
const uploadedDocuments = ref([])
const claimId = ref(null)

const formData = ref({
  verification_method: '',
  business_email: '',
  business_phone: '',
  verification_notes: ''
})

const steps = [
  { name: 'Verification Method' },
  { name: 'Verify Ownership' },
  { name: 'Additional Info' }
]

const verificationMethods = [
  {
    value: 'email',
    label: 'Email Verification',
    description: 'Verify via business email address',
    icon: EnvelopeIcon
  },
  {
    value: 'phone',
    label: 'Phone Verification',
    description: 'Verify via SMS to business phone',
    icon: PhoneIcon
  },
  {
    value: 'document',
    label: 'Document Upload',
    description: 'Upload business license or other documents',
    icon: DocumentTextIcon
  }
]

// Computed
const canProceed = computed(() => {
  switch (currentStep.value) {
    case 0:
      if (!formData.value.verification_method) return false
      if (formData.value.verification_method === 'email') {
        return formData.value.business_email && isValidEmail(formData.value.business_email)
      }
      if (formData.value.verification_method === 'phone') {
        return formData.value.business_phone && formData.value.business_phone.length >= 10
      }
      return true
      
    case 1:
      if (['email', 'phone'].includes(formData.value.verification_method)) {
        return verificationCode.value.length === 6
      }
      if (formData.value.verification_method === 'document') {
        return uploadedDocuments.value.length > 0
      }
      return true
      
    case 2:
      return true
      
    default:
      return false
  }
})

// Methods
const closeModal = () => {
  emit('close')
  resetForm()
}

const resetForm = () => {
  currentStep.value = 0
  formData.value = {
    verification_method: '',
    business_email: '',
    business_phone: '',
    verification_notes: ''
  }
  verificationCode.value = ''
  uploadedDocuments.value = []
  claimId.value = null
}

const nextStep = async () => {
  if (currentStep.value === 0) {
    // Initiate claim
    await initiateClaim()
  } else if (currentStep.value === 1) {
    // Verify ownership
    if (['email', 'phone'].includes(formData.value.verification_method)) {
      await verifyCode()
    } else if (formData.value.verification_method === 'document') {
      await uploadDocuments()
    }
  } else if (currentStep.value === 2) {
    // Submit final claim
    await submitClaim()
  }
}

const previousStep = () => {
  if (currentStep.value > 0) {
    currentStep.value--
  }
}

const initiateClaim = async () => {
  isSubmitting.value = true
  try {
    const response = await api.post(`/places/${props.place.id}/claim`, {
      verification_method: formData.value.verification_method,
      business_email: formData.value.business_email,
      business_phone: formData.value.business_phone
    })
    
    claimId.value = response.data.claim.id
    currentStep.value++
    
    // Start resend timer for email/phone
    if (['email', 'phone'].includes(formData.value.verification_method)) {
      startResendTimer()
    }
    
    showToast('Claim initiated successfully', 'success')
  } catch (error) {
    showToast(error.response?.data?.message || 'Failed to initiate claim', 'error')
  } finally {
    isSubmitting.value = false
  }
}

const verifyCode = async () => {
  isSubmitting.value = true
  try {
    await api.post(`/claims/${claimId.value}/verify`, {
      code: verificationCode.value
    })
    
    currentStep.value++
    showToast('Verification successful', 'success')
  } catch (error) {
    showToast(error.response?.data?.message || 'Invalid verification code', 'error')
  } finally {
    isSubmitting.value = false
  }
}

const uploadDocuments = async () => {
  isSubmitting.value = true
  try {
    // Upload each document
    for (const doc of uploadedDocuments.value) {
      const formData = new FormData()
      formData.append('document', doc.file)
      formData.append('document_type', doc.type || 'other')
      
      await api.post(`/claims/${claimId.value}/documents`, formData, {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      })
    }
    
    currentStep.value++
    showToast('Documents uploaded successfully', 'success')
  } catch (error) {
    showToast(error.response?.data?.message || 'Failed to upload documents', 'error')
  } finally {
    isSubmitting.value = false
  }
}

const submitClaim = async () => {
  isSubmitting.value = true
  try {
    await api.patch(`/claims/${claimId.value}`, {
      verification_notes: formData.value.verification_notes
    })
    
    showToast('Claim submitted successfully! We\'ll review it within 24-48 hours.', 'success')
    emit('success')
    closeModal()
  } catch (error) {
    showToast(error.response?.data?.message || 'Failed to submit claim', 'error')
  } finally {
    isSubmitting.value = false
  }
}

const resendCode = async () => {
  if (resendTimer.value > 0) return
  
  try {
    await api.post(`/claims/${claimId.value}/resend-code`)
    showToast('Verification code resent', 'success')
    startResendTimer()
  } catch (error) {
    showToast(error.response?.data?.message || 'Failed to resend code', 'error')
  }
}

const startResendTimer = () => {
  resendTimer.value = 60
  const interval = setInterval(() => {
    resendTimer.value--
    if (resendTimer.value <= 0) {
      clearInterval(interval)
    }
  }, 1000)
}

const handleVerificationCode = (code) => {
  verificationCode.value = code
}

const isValidEmail = (email) => {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)
}
</script>