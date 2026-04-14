<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { Upload, AlertTriangle, CheckCircle, AlertCircle } from 'lucide-vue-next'
import Card from '@/components/ui/Card.vue'
import CardHeader from '@/components/ui/CardHeader.vue'
import CardTitle from '@/components/ui/CardTitle.vue'
import CardDescription from '@/components/ui/CardDescription.vue'
import CardContent from '@/components/ui/CardContent.vue'
import Button from '@/components/ui/Button.vue'
import Badge from '@/components/ui/Badge.vue'
import { djangoAiApi } from '@/api/django/ai'
import * as insuranceApi from '@/api/laravel/insurance'

interface ClassificationResult {
  category: string
  confidence: number
  medical_specialty?: string
}

interface OCRResult {
  extracted_data?: Record<string, string | number>
  raw_text?: string
}

interface FraudResult {
  fraud_score: number
  risk_tier: string
  flags?: string[]
}

interface ProcessedDocument {
  filename: string
  timestamp: Date
  classification: ClassificationResult
  ocr: OCRResult
  fraud: FraudResult | null
}

const documents = ref<ProcessedDocument[]>([])
const uploadedFile = ref<File | null>(null)
const fileName = ref('')
const isProcessing = ref(false)
const feedback = ref('')
const errorMessage = ref('')
const processingResults = ref<ProcessedDocument | null>(null)

const getFraudRiskColor = (score: number) => {
  if (score < 0.3) return 'success'
  if (score < 0.7) return 'warning'
  return 'destructive'
}

const getFraudRiskLabel = (score: number) => {
  if (score < 0.3) return 'Low Risk'
  if (score < 0.7) return 'Medium Risk'
  return 'High Risk'
}

const normalizeClassification = (classification: any): ClassificationResult => {
  const confidenceRaw = Number(
    classification?.confidence ??
    classification?.confidence_score ??
    classification?.score ??
    0
  )
  const confidence = Number.isFinite(confidenceRaw)
    ? (confidenceRaw > 1 ? confidenceRaw / 100 : confidenceRaw)
    : 0

  return {
    category:
      classification?.category ??
      classification?.document_category ??
      classification?.document_type ??
      'Unknown',
    confidence: Math.max(0, Math.min(1, confidence)),
    medical_specialty: classification?.medical_specialty ?? classification?.specialty,
  }
}

const normalizeOcr = (ocr: any): OCRResult => {
  if (!ocr || typeof ocr !== 'object') {
    return { extracted_data: {}, raw_text: 'No text extracted' }
  }

  const extractedData =
    ocr.extracted_data ??
    ocr.data ??
    {
      provider_name: ocr.provider_name,
      service_date: ocr.service_date,
      total_amount: ocr.total_amount,
      invoice_number: ocr.invoice_number,
      contact_email: ocr.contact_email,
    }

  const cleanedData = Object.fromEntries(
    Object.entries(extractedData || {}).filter(([, value]) => value !== undefined && value !== null && value !== '')
  ) as Record<string, string | number>

  return {
    extracted_data: cleanedData,
    raw_text: ocr.raw_text ?? ocr.text ?? 'No text extracted',
  }
}

const normalizeFraud = (fraud: any): FraudResult | null => {
  if (!fraud || typeof fraud !== 'object') return null
  const scoreRaw = Number(fraud.fraud_score ?? fraud.score ?? 0)
  const score = Number.isFinite(scoreRaw) ? (scoreRaw > 1 ? scoreRaw / 100 : scoreRaw) : 0
  return {
    fraud_score: Math.max(0, Math.min(1, score)),
    risk_tier: fraud.risk_tier ?? fraud.risk_level ?? 'unknown',
    flags: Array.isArray(fraud.flags) ? fraud.flags : [],
  }
}

const handleFileSelect = (event: Event) => {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  if (file) {
    uploadedFile.value = file
    fileName.value = file.name
  }
}

const processDocument = async () => {
  if (!uploadedFile.value) {
    errorMessage.value = 'Please select a document to process.'
    return
  }

  isProcessing.value = true
  errorMessage.value = ''
  feedback.value = 'Processing document...'
  processingResults.value = null

  try {
    // Step 1: Classify document
    const classification = normalizeClassification(await djangoAiApi.classifyDocument(uploadedFile.value))
    feedback.value = `Document classified as: ${classification.category}`

    // Step 2: Extract text via OCR
    const ocr = normalizeOcr(await djangoAiApi.processOCR(uploadedFile.value))
    feedback.value = `Extracted ${Object.keys(ocr.extracted_data || {}).length} data fields`

    // Step 3: Detect fraud (for insurance documents)
    let fraud_result: FraudResult | null = null
    if (classification.category?.includes('Invoice') || classification.category?.includes('Claim')) {
      const fraudData = normalizeFraud(await djangoAiApi.detectFraud({
        claim_amount: parseFloat(String(ocr.extracted_data?.amount ?? 0)),
        claims_30_days: 1,
        days_since_last_claim: 30,
        provider_id: 'auto'
      }))
      fraud_result = fraudData
      feedback.value = `Fraud analysis: ${getFraudRiskLabel(fraud_result?.fraud_score ?? 0)}`
    }

    // Compile results
    processingResults.value = {
      filename: fileName.value,
      timestamp: new Date(),
      classification,
      ocr: {
        extracted_data: ocr.extracted_data || {},
        raw_text: (ocr.raw_text || 'No text extracted').substring(0, 200)
      },
      fraud: fraud_result
    }

    documents.value.unshift(processingResults.value)
    uploadedFile.value = null
    fileName.value = ''
    feedback.value = 'Document processed successfully!'
  } catch (err: any) {
    console.error('Document processing error:', err)
    errorMessage.value = err.response?.data?.detail || 'Failed to process document.'
  } finally {
    isProcessing.value = false
  }
}

onMounted(async () => {
  try {
    const response: any = await insuranceApi.getClaimDocuments()
    documents.value = ((response.data || response || []) as any[]).slice(0, 10).map((doc: any) => ({
      ...doc,
      classification: normalizeClassification(doc.classification ?? doc),
      ocr: normalizeOcr(doc.ocr ?? doc),
      fraud: normalizeFraud(doc.fraud ?? doc.fraud_result ?? doc.fraud_analysis),
      timestamp: doc.timestamp ? new Date(doc.timestamp) : new Date(doc.created_at || Date.now())
    }))
  } catch (err) {
    console.error('Failed to fetch documents', err)
  }
})
</script>

<template>
  <div class="space-y-6">
    <div>
      <h2 class="text-3xl font-bold tracking-tight">Document Processing</h2>
      <p class="text-gray-500 dark:text-gray-400">AI-powered OCR, classification, and fraud detection for insurance documents.</p>
    </div>

    <div v-if="feedback" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-300">
      {{ feedback }}
    </div>

    <div v-if="errorMessage" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-900/60 dark:bg-rose-950/40 dark:text-rose-300">
      {{ errorMessage }}
    </div>

    <!-- UPLOAD SECTION -->
    <Card>
      <CardHeader>
        <CardTitle class="flex items-center gap-2">
          <Upload class="w-5 h-5" />
          Upload Document
        </CardTitle>
        <CardDescription>Upload a document for AI-powered analysis (PDF, JPG, PNG, etc.)</CardDescription>
      </CardHeader>
      <CardContent class="space-y-4">
        <div class="border-2 border-dashed rounded-lg p-8 text-center hover:border-blue-500 transition">
          <input
            type="file"
            @change="handleFileSelect"
            class="hidden"
            id="file-input"
            :disabled="isProcessing"
          />
          <label for="file-input" class="cursor-pointer block">
            <Upload class="w-12 h-12 mx-auto mb-3 text-gray-400" />
            <p class="font-semibold text-gray-700 dark:text-gray-300">
              {{ fileName || 'Click to upload or drag and drop' }}
            </p>
            <p class="text-sm text-gray-500">PDF, JPG, PNG, or other document formats</p>
          </label>
        </div>

        <Button
          :disabled="!uploadedFile || isProcessing"
          class="w-full bg-blue-600 hover:bg-blue-700"
          @click="processDocument"
        >
          {{ isProcessing ? 'Processing...' : 'Process Document' }}
        </Button>
      </CardContent>
    </Card>

    <!-- PROCESSING RESULTS -->
    <div v-if="processingResults" class="grid gap-6">
      <!-- CLASSIFICATION RESULT -->
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <CheckCircle class="w-5 h-5 text-blue-600" />
            Document Classification
          </CardTitle>
        </CardHeader>
        <CardContent class="grid gap-4">
          <div>
            <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">Category</p>
            <p class="text-lg font-bold">{{ processingResults.classification.category }}</p>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">Confidence</p>
              <Badge variant="success">
                {{ (processingResults.classification.confidence * 100).toFixed(1) }}%
              </Badge>
            </div>
            <div v-if="processingResults.classification.medical_specialty">
              <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">Specialty</p>
              <Badge variant="secondary">{{ processingResults.classification.medical_specialty }}</Badge>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- OCR RESULTS -->
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <CheckCircle class="w-5 h-5 text-green-600" />
            Extracted Data (OCR)
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div v-if="processingResults.ocr.extracted_data && Object.keys(processingResults.ocr.extracted_data).length > 0" class="space-y-3">
            <div v-for="(value, key) in processingResults.ocr.extracted_data" :key="String(key)" class="flex items-center justify-between border-b pb-2">
              <p class="text-sm font-semibold text-gray-600 dark:text-gray-400 capitalize">{{ String(key).replace(/_/g, ' ') }}</p>
              <p class="text-sm font-mono">{{ value }}</p>
            </div>
          </div>
          <div v-else class="text-gray-500">
            No structured data extracted. Raw text preview:
            <p class="text-xs mt-2 p-2 bg-gray-100 dark:bg-gray-900 rounded">{{ processingResults.ocr.raw_text }}</p>
          </div>
        </CardContent>
      </Card>

      <!-- FRAUD DETECTION -->
      <Card v-if="processingResults.fraud">
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <AlertTriangle class="w-5 h-5 text-amber-600" />
            Fraud Risk Analysis
          </CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">Fraud Score</p>
              <p class="text-2xl font-bold">{{ (processingResults.fraud.fraud_score * 100).toFixed(1) }}%</p>
            </div>
            <div>
              <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">Risk Tier</p>
              <Badge :variant="getFraudRiskColor(processingResults.fraud.fraud_score)">
                {{ getFraudRiskLabel(processingResults.fraud.fraud_score) }}
              </Badge>
            </div>
          </div>

          <div v-if="processingResults.fraud.flags && processingResults.fraud.flags.length > 0">
            <p class="text-sm font-semibold text-gray-600 dark:text-gray-400 mb-2">Alerts</p>
            <div class="space-y-2">
              <div v-for="(flag, idx) in processingResults.fraud.flags" :key="idx" class="flex items-center gap-2 text-sm text-rose-700 dark:text-rose-300 bg-rose-50 dark:bg-rose-950/30 p-2 rounded">
                <AlertCircle class="w-4 h-4 flex-shrink-0" />
                {{ flag }}
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- HISTORY -->
    <Card v-if="documents.length > 0">
      <CardHeader>
        <CardTitle>Recently Processed Documents</CardTitle>
      </CardHeader>
      <CardContent>
        <div class="space-y-3">
          <div v-for="(doc, idx) in documents" :key="idx" class="border rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-900/20">
            <div class="flex items-start justify-between">
              <div class="flex-1">
                <p class="font-semibold">{{ doc.filename || `Document ${idx + 1}` }}</p>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                  Category:
                  <span class="font-mono">{{ doc.classification?.category || 'Unknown' }}</span>
                </p>
                <p v-if="doc.timestamp" class="text-xs text-gray-500">{{ new Date(doc.timestamp).toLocaleString() }}</p>
              </div>
              <div class="flex flex-col items-end gap-2">
                <Badge v-if="doc.classification" variant="secondary">
                  {{ (doc.classification.confidence * 100).toFixed(0) }}% confidence
                </Badge>
                <Badge
                  v-if="doc.fraud"
                  :variant="getFraudRiskColor(doc.fraud.fraud_score)"
                >
                  {{ getFraudRiskLabel(doc.fraud.fraud_score) }}
                </Badge>
              </div>
            </div>
          </div>
        </div>
      </CardContent>
    </Card>
  </div>
</template>
