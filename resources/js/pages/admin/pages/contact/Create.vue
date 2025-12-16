<script setup lang="ts">
import { ref } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import AdminAppLayout from '@/layouts/AdminAppLayout.vue'
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Button } from '@/components/ui/button'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Dialog, DialogContent, DialogTitle, DialogDescription } from '@/components/ui/dialog'
import { Loader2, ArrowLeft, Trash2, CheckCircle2 } from 'lucide-vue-next'
import { routes } from "@/lib/routes";

// QuillEditor imports
import RichTextEditor from '@/components/RichTextEditor.vue'
import 'quill-emoji/dist/quill-emoji.css'
// @ts-ignore
import * as Emoji from 'quill-emoji'

// Register Emoji module
import { Quill } from '@vueup/vue-quill' // Keep Quill import for registration
Quill.register('modules/emoji', Emoji)

const props = defineProps<{
    prefill?: {
        name?: string
        email?: string
        subject?: string
        body?: string
    }
}>()

const breadcrumbs = [
    { title: 'Contactberichten', href: routes.admin.contact },
    { title: 'Nieuw bericht', href: '/hoofdbeheerder/contact/nieuw' },
]

const form = useForm({
    name: props.prefill?.name || '',
    email: props.prefill?.email || '',
    subject: props.prefill?.subject || '',
    body: props.prefill?.body || '',
    cc: '',
    bcc: '',
    attachment: null as File | null,
})

const fileInput = ref<HTMLInputElement | null>(null)
const successOpen = ref(false)

function clearAttachment() {
    form.attachment = null
    if (fileInput.value) fileInput.value.value = ''
}

const err = ref('')

function submit(){
    err.value=''
    if(!form.email || !form.subject || !form.body){ 
        err.value='Vul ten minste e-mail, onderwerp en inhoud in.'
        return 
    }

    form.post(routes.admin.contactSend,{
        preserveScroll: true,
        onSuccess:()=>{ 
            successOpen.value = true
            form.reset() 
            // Automatisch terug naar overzicht na 3 seconden
            setTimeout(() => {
                router.visit(routes.admin.contact)
            }, 3000)
        },
        onError: (errors: any) => { 
            err.value = errors.email || errors.message || 'Versturen mislukt. Controleer de invoer.' 
        }
    })
}

// -------- Toolbar Options --------
const toolbarOptions = [
    [{ 'header': [1, 2, 3, false] }],
    ['bold', 'italic', 'underline', 'link'],
    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
    ['emoji'], // Add emoji button
    ['clean']
]

// Modules config for the component
// We enable the emoji modules here via options.modules
const editorOptions = {
    modules: {
        'emoji-toolbar': true,
        'emoji-shortname': true,
        'emoji-textarea': false // We don't want textarea mode usually in rich editor
    }
}
</script>

<template>
    <Head title="Nieuw bericht – Admin" />

    <AdminAppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-8 space-y-8">
            <Card class="border shadow-sm">
                <CardHeader class="border-b bg-muted/40 pb-4">
                    <CardTitle class="text-xl">Nieuw bericht opstellen</CardTitle>
                    <CardDescription>Stuur een e-mail naar een externe contactpersoon</CardDescription>
                </CardHeader>
                <CardContent class="space-y-6 pt-6">
                    
                    <div v-if="err || (form.errors as any).message" class="rounded-md bg-destructive/15 p-3 text-sm text-destructive font-medium flex items-center gap-2">
                        <AlertDescription>{{ (form.errors as any).message || err }}</AlertDescription>
                    </div>

                    <!-- Ontvanger Grid -->
                    <div class="grid gap-5 md:grid-cols-2">
                        <div class="space-y-1.5">
                            <Label for="name">Naam ontvanger</Label>
                            <Input id="name" v-model="form.name" placeholder="Optioneel" />
                            <p v-if="form.errors.name" class="text-xs text-destructive">{{ form.errors.name }}</p>
                        </div>
                        <div class="space-y-1.5">
                            <Label for="email">E-mailadres <span class="text-destructive">*</span></Label>
                            <Input id="email" v-model="form.email" type="email" placeholder="naam@voorbeeld.nl" :class="{'border-destructive': form.errors.email}" />
                            <p v-if="form.errors.email" class="text-xs text-destructive">{{ form.errors.email }}</p>
                        </div>
                    </div>

                    <!-- CC / BCC Grid -->
                    <div class="grid gap-5 md:grid-cols-2">
                        <div class="space-y-1.5">
                            <Label for="cc">CC</Label>
                            <Input id="cc" v-model="form.cc" placeholder="Optioneel" />
                        </div>
                        <div class="space-y-1.5">
                            <Label for="bcc">BCC</Label>
                            <Input id="bcc" v-model="form.bcc" placeholder="Optioneel" />
                        </div>
                    </div>

                    <!-- Subject -->
                    <div class="space-y-1.5">
                        <Label for="subject">Onderwerp <span class="text-destructive">*</span></Label>
                        <Input id="subject" v-model="form.subject" placeholder="Onderwerp van het bericht" :class="{'border-destructive': form.errors.subject}" />
                        <p v-if="form.errors.subject" class="text-xs text-destructive">{{ form.errors.subject }}</p>
                    </div>

                    <!-- Attachment -->
                    <div class="rounded-lg border border-dashed p-4">
                        <Label for="attachment" class="mb-2 block text-sm font-medium">Bijlage toevoegen</Label>
                        <div class="flex items-center gap-2">
                            <Input
                                id="attachment"
                                ref="fileInput"
                                type="file"
                                class="cursor-pointer file:cursor-pointer file:text-primary file:font-medium"
                                @input="form.attachment = ($event.target as HTMLInputElement).files?.[0] || null"
                            />
                            <Button
                                v-if="form.attachment"
                                type="button"
                                variant="destructive"
                                size="icon"
                                class="shrink-0"
                                @click="clearAttachment"
                                title="Verwijder bijlage"
                            >
                                <Trash2 class="h-4 w-4" />
                            </Button>
                        </div>
                        <p v-if="form.errors.attachment" class="mt-1 text-xs text-destructive">{{ form.errors.attachment }}</p>
                    </div>

                    <!-- Body -->
                    <div class="space-y-1.5">
                        <Label for="body">Bericht <span class="text-destructive">*</span></Label>
                        <div class="overflow-hidden rounded-md border focus-within:ring-2 focus-within:ring-ring focus-within:ring-offset-2">
                            <RichTextEditor
                                v-model="form.body"
                                placeholder="Typ uw bericht..."
                                height="250px"
                            />
                        </div>
                         <p v-if="form.errors.body" class="text-xs text-destructive">{{ form.errors.body }}</p>
                    </div>

                    <div class="flex justify-end pt-4">
                        <Button
                            size="lg"
                            class="min-w-[150px]"
                            :disabled="form.processing || !form.email || !form.subject || !form.body"
                            @click="submit"
                        >
                            <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                            {{ form.processing ? 'Verzenden...' : 'Verstuur bericht' }}
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Success Dialog -->
        <Dialog v-model:open="successOpen">
            <DialogContent class="sm:max-w-[400px] text-center">
                 <div class="flex flex-col items-center justify-center py-6 space-y-4 text-green-600 animate-in fade-in zoom-in duration-300">
                    <CheckCircle2 class="h-16 w-16" />
                    <DialogTitle class="text-2xl font-bold">Verzonden!</DialogTitle>
                    <DialogDescription class="text-foreground/70">
                        Je bericht is succesvol verstuurd.<br>
                        Je wordt teruggestuurd naar het overzicht...
                    </DialogDescription>
                </div>
            </DialogContent>
        </Dialog>
    </AdminAppLayout>
</template>
