<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import { ref, computed, onMounted } from 'vue'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Button } from '@/components/ui/button'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Loader2, CheckCircle2, X } from 'lucide-vue-next'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'

type ContactForm = {
    name: string
    email: string
    phone: string
    subject: string
    message: string
    consent: boolean
    company: string
    renderedAt: number
    duration_ms: number
    referer: string
}

const startedAt = ref<number>(Date.now())
const attempted = ref(false)
const showSuccessModal = ref(false)

const form = useForm<ContactForm>({
    name: '',
    email: '',
    phone: '',
    subject: '',
    message: '',
    consent: false,
    company: '', // Honeypot
    renderedAt: 0,
    duration_ms: 0,
    referer: ''
})

const hasRequired = computed(() =>
    !!form.name?.trim() &&
    !!form.email?.trim() &&
    !!form.subject?.trim() &&
    !!form.message?.trim()
)

const sendDisabled = computed(() =>
    form.processing || !hasRequired.value || !form.consent
)

const err = ref('')

onMounted(() => {
    startedAt.value = Date.now()
    form.renderedAt = startedAt.value
    form.referer = document.referrer || ''
})

const emit = defineEmits(['submitted'])

const openPrivacy = () => {
    window.open('/privacy', '_blank')
}

function submit() {
    attempted.value = true
    err.value = ''

    if (sendDisabled.value) {
        if (!form.consent) err.value = 'Vink het privacyvakje aan om te kunnen versturen.'
        return
    }

    form.duration_ms = Math.max(0, Date.now() - startedAt.value)
    form.transform(d => ({ ...d, consent: d.consent ? 1 : 0 }))

    form.post('/contact', {
        preserveScroll: true,
        onSuccess: () => {
            showSuccessModal.value = true
            form.reset('name', 'email', 'phone', 'subject', 'message', 'consent', 'company')
            form.clearErrors()
            attempted.value = false
            startedAt.value = Date.now()
            form.renderedAt = startedAt.value

            setTimeout(() => {
                showSuccessModal.value = false
                emit('submitted')
            }, 5000)
        },
        onError: () => {
            err.value = form.errors.consent
                ? 'Vink het privacyvakje aan om te kunnen versturen.'
                : 'Versturen mislukt. Controleer je invoer.'
        },
        onFinish: () => form.transform(d => d)
    })
}
</script>

<template>
    <Dialog v-model:open="showSuccessModal">
        <DialogContent class="sm:max-w-md bg-white dark:bg-zinc-950 border-none shadow-2xl p-0 overflow-hidden">
             <div class="flex flex-col items-center text-center p-8 pt-12 space-y-6">
                 <!-- Icon Animation Bubble -->
                 <div class="relative">
                     <div class="absolute inset-0 rounded-full bg-green-100 dark:bg-green-900/30 animate-ping opacity-75"></div>
                     <div class="relative rounded-full bg-green-100 dark:bg-green-900/50 p-4">
                         <CheckCircle2 class="h-16 w-16 text-green-600 dark:text-green-400" />
                     </div>
                 </div>

                 <div class="space-y-2">
                     <DialogTitle class="text-2xl font-bold tracking-tight">Het is gelukt!</DialogTitle>
                     <DialogDescription class="text-base text-muted-foreground max-w-xs mx-auto">
                        Bedankt voor je bericht. We hebben het goed ontvangen en nemen zo snel mogelijk contact met je op.
                     </DialogDescription>
                 </div>
                 
                 <div class="w-full pt-4">
                     <div class="h-1 w-full bg-zinc-100 dark:bg-zinc-800 rounded-full overflow-hidden">
                         <div class="h-full bg-green-500 rounded-full animate-progress origin-left" style="animation-duration: 5s;"></div>
                     </div>
                     <p class="text-xs text-muted-foreground mt-2">Dit venster sluit automatisch in 5 seconden</p>
                 </div>
             </div>
        </DialogContent>
    </Dialog>

    <Card class="w-full text-left" aria-labelledby="contactform-title">

        <CardContent class="space-y-5 p-4 sm:p-6">
            
            <!-- Form Error -->
            <Alert v-if="err" variant="destructive"><AlertDescription>{{ err }}</AlertDescription></Alert>

            <!-- Active Form -->
            <form class="space-y-5" @submit.prevent="submit" novalidate>
                <!-- HONEYPOT -->
                <div class="hidden" aria-hidden="true">
                    <input type="text" name="company" v-model="form.company" tabindex="-1" autocomplete="off" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <Label for="name" class="mb-2">Naam</Label>
                        <Input id="name" v-model="form.name" placeholder="Jouw naam"
                               autocomplete="name" :aria-invalid="!!form.errors.name" />
                        <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                    </div>

                    <div>
                        <Label for="email" class="mb-2">E-mail</Label>
                        <Input id="email" v-model="form.email" type="email" placeholder="voorbeeld@email.com"
                               autocomplete="email" inputmode="email" :aria-invalid="!!form.errors.email" />
                        <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
                    </div>

                    <div>
                        <Label for="phone" class="mb-2">Telefoon (optioneel)</Label>
                        <Input id="phone" v-model="form.phone" placeholder="06-…" autocomplete="tel" inputmode="tel" />
                    </div>

                    <div>
                        <Label for="subject" class="mb-2">Onderwerp</Label>
                        <Input id="subject" v-model="form.subject" placeholder="Waar gaat het over?"
                               :aria-invalid="!!form.errors.subject" />
                        <p v-if="form.errors.subject" class="mt-1 text-sm text-red-600">{{ form.errors.subject }}</p>
                    </div>
                </div>

                <div>
                    <Label for="message" class="mb-2">Bericht</Label>
                    <Textarea id="message" v-model="form.message" rows="8" placeholder="Schrijf je bericht…"
                              :aria-invalid="!!form.errors.message" />
                    <p v-if="form.errors.message" class="mt-1 text-sm text-red-600">{{ form.errors.message }}</p>
                </div>

                <!-- CHECKBOX -->
                <div class="flex items-start gap-2">
                    <input
                        id="consent-box"
                        type="checkbox"
                        v-model="form.consent"
                        class="h-4 w-4 shrink-0 rounded border border-gray-300 text-indigo-600 focus:ring-indigo-600 dark:border-gray-600 dark:bg-gray-800 dark:checked:bg-indigo-500 dark:checked:border-indigo-500"
                        :class="{ 'border-red-600': attempted && !form.consent }"
                        :aria-invalid="(attempted && !form.consent) || !!form.errors.consent"
                    />
                    <Label for="consent-box" class="text-sm leading-6 cursor-pointer select-none">
                        Ik ga akkoord met het verwerken van mijn gegevens conform het 
                        <button 
                            type="button"
                            @click.stop.prevent="openPrivacy"
                            class="text-indigo-500 hover:text-indigo-600 underline font-medium cursor-pointer"
                        >privacybeleid</button>.
                    </Label>
                </div>
                <p v-if="(attempted && !form.consent) || form.errors.consent" class="mt-1 text-sm text-red-600">
                    Vink dit vakje aan om te kunnen versturen.
                </p>

                <Button type="submit" class="w-full justify-center bg-indigo-500 hover:bg-indigo-600 text-white"
                        :aria-busy="form.processing"
                        :disabled="sendDisabled">
                    <Loader2 v-if="form.processing" class="mr-2 h-5 w-5 animate-spin" />
                    Versturen
                </Button>
            </form>
        </CardContent>
    </Card>
</template>

<style scoped>
@keyframes progress {
    from { width: 0%; }
    to { width: 100%; }
}
.animate-progress {
    animation-name: progress;
    animation-timing-function: linear;
}
</style>
