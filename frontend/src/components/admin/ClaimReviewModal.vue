<template>
    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <!-- Header -->
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-semibold text-gray-900">Review Claim</h2>
                    <button
                        @click="$emit('close')"
                        class="text-gray-400 hover:text-gray-500"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Content -->
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Business Information -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Business Information</h3>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Business Name</label>
                                <p class="mt-1 text-sm text-gray-900">{{ claim.place?.title }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Address</label>
                                <p class="mt-1 text-sm text-gray-900">
                                    {{ claim.place?.location?.address_line1 }}<br>
                                    {{ claim.place?.location?.city }}, {{ claim.place?.location?.state }} {{ claim.place?.location?.postal_code }}
                                </p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Category</label>
                                <p class="mt-1 text-sm text-gray-900">{{ claim.place?.category?.name || 'N/A' }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Current Status</label>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                      :class="getStatusBadgeClass(claim.status)">
                                    {{ claim.status }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Claimant Information -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Claimant Information</h3>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Name</label>
                                <p class="mt-1 text-sm text-gray-900">{{ claim.user?.name || 'N/A' }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Email</label>
                                <p class="mt-1 text-sm text-gray-900">{{ claim.user?.email }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Joined</label>
                                <p class="mt-1 text-sm text-gray-900">{{ formatDate(claim.user?.created_at) }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Total Claims</label>
                                <p class="mt-1 text-sm text-gray-900">{{ claim.user?.claims_count || 1 }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Verification Details -->
                <div class="mt-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Verification Details</h3>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Method</label>
                                <p class="mt-1 text-sm text-gray-900 capitalize">{{ claim.verification_method }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Submitted</label>
                                <p class="mt-1 text-sm text-gray-900">{{ formatDate(claim.created_at) }}</p>
                            </div>
                            <div v-if="claim.business_email">
                                <label class="block text-sm font-medium text-gray-500">Business Email</label>
                                <p class="mt-1 text-sm text-gray-900">{{ claim.business_email }}</p>
                            </div>
                            <div v-if="claim.business_phone">
                                <label class="block text-sm font-medium text-gray-500">Business Phone</label>
                                <p class="mt-1 text-sm text-gray-900">{{ claim.business_phone }}</p>
                            </div>
                            <div v-if="claim.verified_at">
                                <label class="block text-sm font-medium text-gray-500">Verified At</label>
                                <p class="mt-1 text-sm text-gray-900">{{ formatDate(claim.verified_at) }}</p>
                            </div>
                        </div>
                        
                        <div v-if="claim.verification_notes" class="mt-4">
                            <label class="block text-sm font-medium text-gray-500">Verification Notes</label>
                            <p class="mt-1 text-sm text-gray-900">{{ claim.verification_notes }}</p>
                        </div>
                    </div>
                </div>

                <!-- Payment & Subscription Details -->
                <div class="mt-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Payment & Subscription</h3>
                    <div class="bg-blue-50 rounded-lg p-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-600">Selected Tier</label>
                                <p class="mt-1 text-sm font-semibold" :class="{
                                    'text-gray-700': claim.tier === 'free',
                                    'text-indigo-700': claim.tier === 'tier1',
                                    'text-purple-700': claim.tier === 'tier2'
                                }">
                                    {{ getTierLabel(claim.tier) }}
                                </p>
                            </div>
                            <div v-if="claim.verification_fee_paid">
                                <label class="block text-sm font-medium text-gray-600">Verification Fee</label>
                                <p class="mt-1 text-sm text-gray-900">
                                    ${{ claim.verification_fee_amount || '5.99' }}
                                    <span v-if="claim.fee_kept" class="text-green-600 font-medium">(Tipped ☕)</span>
                                    <span v-else-if="claim.fee_refunded_at" class="text-blue-600">(Refunded)</span>
                                    <span v-else class="text-gray-500">(Refundable)</span>
                                </p>
                            </div>
                            <div v-if="claim.stripe_subscription_id">
                                <label class="block text-sm font-medium text-gray-600">Subscription ID</label>
                                <p class="mt-1 text-sm text-gray-900 font-mono text-xs">{{ claim.stripe_subscription_id }}</p>
                            </div>
                            <div v-if="claim.fee_payment_intent_id">
                                <label class="block text-sm font-medium text-gray-600">Payment Intent</label>
                                <p class="mt-1 text-sm text-gray-900 font-mono text-xs">{{ claim.fee_payment_intent_id }}</p>
                            </div>
                        </div>
                        
                        <!-- Payment Verification Button -->
                        <div class="mt-4 pt-4 border-t border-blue-200">
                            <button
                                @click="loadPaymentDetails"
                                :disabled="loadingPayment"
                                class="text-sm bg-white text-blue-600 px-3 py-1.5 rounded border border-blue-300 hover:bg-blue-50 disabled:opacity-50"
                            >
                                {{ loadingPayment ? 'Loading...' : 'Verify Payment Details' }}
                            </button>
                        </div>
                        
                        <!-- Payment Details -->
                        <div v-if="paymentDetails" class="mt-4 space-y-3">
                            <div v-if="paymentDetails.payment_intent" class="bg-white rounded p-3">
                                <h5 class="text-sm font-medium text-gray-700 mb-2">Payment Intent</h5>
                                <dl class="text-xs space-y-1">
                                    <div class="flex justify-between">
                                        <dt class="text-gray-500">Amount:</dt>
                                        <dd class="font-medium">${{ paymentDetails.payment_intent.amount }}</dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-gray-500">Status:</dt>
                                        <dd class="font-medium capitalize">{{ paymentDetails.payment_intent.status }}</dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-gray-500">Created:</dt>
                                        <dd>{{ paymentDetails.payment_intent.created }}</dd>
                                    </div>
                                </dl>
                                <a 
                                    v-if="paymentDetails.charge?.receipt_url"
                                    :href="paymentDetails.charge.receipt_url"
                                    target="_blank"
                                    class="mt-2 inline-block text-xs text-blue-600 hover:text-blue-800"
                                >
                                    View Receipt →
                                </a>
                            </div>
                            
                            <div v-if="paymentDetails.subscription" class="bg-white rounded p-3">
                                <h5 class="text-sm font-medium text-gray-700 mb-2">Subscription</h5>
                                <dl class="text-xs space-y-1">
                                    <div class="flex justify-between">
                                        <dt class="text-gray-500">Status:</dt>
                                        <dd class="font-medium capitalize">{{ paymentDetails.subscription.status }}</dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-gray-500">Current Period:</dt>
                                        <dd>{{ paymentDetails.subscription.current_period_start }} - {{ paymentDetails.subscription.current_period_end }}</dd>
                                    </div>
                                </dl>
                            </div>
                            
                            <div v-if="paymentDetails.payment_error || paymentDetails.subscription_error" class="bg-red-50 rounded p-3">
                                <p class="text-xs text-red-600">{{ paymentDetails.payment_error || paymentDetails.subscription_error }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Documents -->
                <div v-if="claim.documents && claim.documents.length > 0" class="mt-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Uploaded Documents</h3>
                    <div class="space-y-2">
                        <div
                            v-for="doc in claim.documents"
                            :key="doc.id"
                            class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
                        >
                            <div class="flex items-center">
                                <svg class="w-8 h-8 text-gray-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ doc.file_name }}</p>
                                    <p class="text-xs text-gray-500">
                                        {{ doc.document_type }} • {{ doc.human_file_size }}
                                        <span v-if="doc.status === 'verified'" class="text-green-600 ml-2">✓ Verified</span>
                                        <span v-else-if="doc.status === 'rejected'" class="text-red-600 ml-2">✗ Rejected</span>
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <a
                                    :href="`/api/admin/claims/documents/${doc.id}/download`"
                                    target="_blank"
                                    class="text-blue-600 hover:text-blue-800 text-sm"
                                >
                                    Download
                                </a>
                                <div v-if="doc.status === 'pending' && claim.status === 'pending'" class="flex space-x-1">
                                    <button
                                        @click="verifyDocument(doc.id, 'verified')"
                                        class="text-green-600 hover:text-green-800 text-sm"
                                    >
                                        Verify
                                    </button>
                                    <span class="text-gray-400">|</span>
                                    <button
                                        @click="verifyDocument(doc.id, 'rejected')"
                                        class="text-red-600 hover:text-red-800 text-sm"
                                    >
                                        Reject
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Section -->
                <div v-if="claim.status === 'pending'" class="mt-6 pt-6 border-t border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Review Action</h3>
                    
                    <div v-if="actionType === null" class="space-y-3">
                        <div class="flex space-x-3">
                            <button
                                @click="actionType = 'approve'"
                                class="flex-1 bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700"
                            >
                                Approve Claim
                            </button>
                            <button
                                @click="actionType = 'reject'"
                                class="flex-1 bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700"
                            >
                                Reject Claim
                            </button>
                        </div>
                        <button
                            @click="actionType = 'unclaim'"
                            class="w-full bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700"
                        >
                            Remove Claim (Unclaim)
                        </button>
                    </div>

                    <!-- Approval Form -->
                    <div v-else-if="actionType === 'approve'" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Approval Notes (Optional)</label>
                            <textarea
                                v-model="approvalNotes"
                                rows="3"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Any notes about this approval..."
                            ></textarea>
                        </div>
                        <div class="flex space-x-3">
                            <button
                                @click="approveClaim"
                                :disabled="processing"
                                class="flex-1 bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 disabled:opacity-50"
                            >
                                {{ processing ? 'Processing...' : 'Confirm Approval' }}
                            </button>
                            <button
                                @click="actionType = null"
                                class="px-4 py-2 text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200"
                            >
                                Cancel
                            </button>
                        </div>
                    </div>

                    <!-- Rejection Form -->
                    <div v-else-if="actionType === 'reject'" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Rejection Reason <span class="text-red-500">*</span></label>
                            <textarea
                                v-model="rejectionReason"
                                rows="3"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Please provide a reason for rejection..."
                                required
                            ></textarea>
                        </div>
                        <div class="flex space-x-3">
                            <button
                                @click="rejectClaim"
                                :disabled="!rejectionReason || processing"
                                class="flex-1 bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 disabled:opacity-50"
                            >
                                {{ processing ? 'Processing...' : 'Confirm Rejection' }}
                            </button>
                            <button
                                @click="actionType = null"
                                class="px-4 py-2 text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200"
                            >
                                Cancel
                            </button>
                        </div>
                    </div>

                    <!-- Unclaim Form -->
                    <div v-else-if="actionType === 'unclaim'" class="space-y-4">
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3">
                            <p class="text-sm text-yellow-800">
                                <strong>Warning:</strong> This will remove the claim without rejection, allowing the business to be claimed again.
                                <span v-if="claim.verification_fee_paid && !claim.fee_kept">The verification fee will be refunded.</span>
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Reason for Removal (Optional)</label>
                            <textarea
                                v-model="unclaimReason"
                                rows="3"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Reason for removing this claim..."
                            ></textarea>
                        </div>
                        <div class="flex space-x-3">
                            <button
                                @click="unclaimBusiness"
                                :disabled="processing"
                                class="flex-1 bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 disabled:opacity-50"
                            >
                                {{ processing ? 'Processing...' : 'Confirm Removal' }}
                            </button>
                            <button
                                @click="actionType = null"
                                class="px-4 py-2 text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200"
                            >
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Previous Actions -->
                <div v-else class="mt-6 pt-6 border-t border-gray-200">
                    <div v-if="claim.status === 'approved'" class="bg-green-50 border border-green-200 rounded-lg p-4">
                        <h4 class="text-sm font-medium text-green-800">Approved</h4>
                        <p class="mt-1 text-sm text-green-700">
                            Approved by {{ claim.approved_by?.name }} on {{ formatDate(claim.approved_at) }}
                        </p>
                        <p v-if="claim.metadata?.approval_notes" class="mt-2 text-sm text-green-700">
                            Notes: {{ claim.metadata.approval_notes }}
                        </p>
                    </div>
                    
                    <div v-else-if="claim.status === 'rejected'" class="bg-red-50 border border-red-200 rounded-lg p-4">
                        <h4 class="text-sm font-medium text-red-800">Rejected</h4>
                        <p class="mt-1 text-sm text-red-700">
                            Rejected by {{ claim.rejected_by?.name }} on {{ formatDate(claim.rejected_at) }}
                        </p>
                        <p v-if="claim.rejection_reason" class="mt-2 text-sm text-red-700">
                            Reason: {{ claim.rejection_reason }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import axios from 'axios'

const props = defineProps({
    claim: {
        type: Object,
        required: true
    }
})

const emit = defineEmits(['close', 'approved', 'rejected', 'unclaimed'])

// State
const actionType = ref(null)
const approvalNotes = ref('')
const rejectionReason = ref('')
const unclaimReason = ref('')
const processing = ref(false)
const paymentDetails = ref(null)
const loadingPayment = ref(false)

// Methods
const getStatusBadgeClass = (status) => {
    const classes = {
        pending: 'bg-yellow-100 text-yellow-800',
        approved: 'bg-green-100 text-green-800',
        rejected: 'bg-red-100 text-red-800',
        expired: 'bg-gray-100 text-gray-800'
    }
    return classes[status] || 'bg-gray-100 text-gray-800'
}

const formatDate = (date) => {
    if (!date) return 'N/A'
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const approveClaim = async () => {
    processing.value = true
    
    try {
        await axios.post(`/api/admin/claims/${props.claim.id}/approve`, {
            notes: approvalNotes.value
        })
        
        emit('approved')
    } catch (error) {
        console.error('Failed to approve claim:', error)
        alert('Failed to approve claim. Please try again.')
    } finally {
        processing.value = false
    }
}

const rejectClaim = async () => {
    processing.value = true
    
    try {
        await axios.post(`/api/admin/claims/${props.claim.id}/reject`, {
            reason: rejectionReason.value
        })
        
        emit('rejected')
    } catch (error) {
        console.error('Failed to reject claim:', error)
        alert('Failed to reject claim. Please try again.')
    } finally {
        processing.value = false
    }
}

const verifyDocument = async (documentId, status) => {
    try {
        await axios.post(`/api/admin/claims/documents/${documentId}/verify`, {
            status,
            notes: status === 'rejected' ? 'Document does not match business records' : null
        })
        
        // Refresh claim data
        // In a real app, you'd either refresh the claim data or update the document status locally
        alert(`Document ${status} successfully`)
    } catch (error) {
        console.error('Failed to verify document:', error)
        alert('Failed to verify document. Please try again.')
    }
}

const unclaimBusiness = async () => {
    processing.value = true
    
    try {
        await axios.post(`/api/admin/claims/${props.claim.id}/unclaim`, {
            reason: unclaimReason.value || 'Admin removed claim'
        })
        
        emit('unclaimed')
    } catch (error) {
        console.error('Failed to unclaim business:', error)
        alert('Failed to remove claim. Please try again.')
    } finally {
        processing.value = false
    }
}

const loadPaymentDetails = async () => {
    loadingPayment.value = true
    
    try {
        const response = await axios.get(`/api/admin/claims/${props.claim.id}/payment-details`)
        paymentDetails.value = response.data
    } catch (error) {
        console.error('Failed to load payment details:', error)
        alert('Failed to load payment details.')
    } finally {
        loadingPayment.value = false
    }
}

const getTierLabel = (tier) => {
    const labels = {
        'free': 'Free',
        'tier1': 'Professional ($9.99/mo)',
        'tier2': 'Premium ($19.99/mo)'
    }
    return labels[tier] || tier
}
</script>