<script setup lang="ts">
import { ref, computed } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import AdminAppLayout from '@/layouts/AdminAppLayout.vue'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import { Textarea } from '@/components/ui/textarea'
import { Tabs, TabsList, TabsTrigger, TabsContent } from '@/components/ui/tabs'
import { Alert, AlertTitle, AlertDescription } from '@/components/ui/alert'
import { Camera, Loader2, XCircle, CheckCircle2, Trash2, Info } from 'lucide-vue-next'
import { Separator } from '@/components/ui/separator'
import RichTextEditor from '@/components/RichTextEditor.vue'


import { routes } from '@/lib/routes'

// -------- Types --------
type ContactPage = {
    id?: number
    title?: string // Mapt naar hero_title
    content?: string // Mapt naar intro
    form_title?: string
    form_content?: string
    button_text?: string
    image_path?: string
    meta_title?: string | null
    meta_description?: string | null
    seo_image_path?: string | null
    canonical_url?: string | null
    robots_index?: boolean
    robots_follow?: boolean
    show_form?: boolean
    published?: boolean
    // Velden die in de controller worden gezet, maar niet direct in de Vue form komen
    recipient_email?: string | null
    additional_recipients?: string[] | null
}

const props = defineProps<{ contactPage?: ContactPage }>()
const isEdit = computed(() => !!props.contactPage?.id)

const sectionLabel = 'Contact kaart'

const safe = computed(() => ({
    title: props.contactPage?.title ?? 'Contact',
    content: props.contactPage?.content ?? '<p>Vul het formulier in.</p>',
    form_title: props.contactPage?.form_title ?? 'Contact',
    form_content: props.contactPage?.form_content ?? 'Vul het formulier in en we nemen zo snel mogelijk contact met je op.',
    button_text: props.contactPage?.button_text ?? 'Neem contact op',
    image_path: props.contactPage?.image_path ?? '',
    meta_title: props.contactPage?.meta_title ?? 'Contact',
    meta_description: props.contactPage?.meta_description ?? 'Neem contact met ons op voor vragen.',
    seo_image_path: props.contactPage?.seo_image_path ?? '',
    canonical_url: props.contactPage?.canonical_url ?? '',
    robots_index: props.contactPage?.robots_index ?? true,
    robots_follow: props.contactPage?.robots_follow ?? true,
    show_form: props.contactPage?.show_form ?? true,
    published: props.contactPage?.published ?? true,
}))

// -------- Form --------
const form = useForm({
    // Content velden (alias voor DB)
    title: safe.value.title,
    content: safe.value.content,
    form_title: safe.value.form_title,
    form_content: safe.value.form_content,
    button_text: safe.value.button_text,

    // SEO velden
    meta_title: safe.value.meta_title,
    meta_description: safe.value.meta_description,
    seo_image: null as File | null,
    canonical_url: safe.value.canonical_url,
    robots_index: safe.value.robots_index,
    robots_follow: safe.value.robots_follow,

    // Opties (worden wel in form bewaard, maar tab is weg)
    show_form: safe.value.show_form,
    published: safe.value.published,
})

// -------- UI State --------
const activeTab = ref<'content' | 'seo'>('content')
const successBar = ref('')
const errorBar = ref('')

// -------- Upload Logic Helper (Consistentie) --------
const MAX_MB = 12

function useImageUpload(
    initialPath: string | null,
    formKey: 'image' | 'seo_image'
) {
    const preview = ref<string | null>(initialPath ? `/storage/${initialPath}` : null)
    const info = ref<{ name: string; sizeBytes: number; type: string; width?: number; height?: number } | null>(null)
    const sizeMB = computed(() => (info.value ? +(info.value.sizeBytes / 1024 / 1024) : 0))
    const tooLarge = computed(() => (info.value ? sizeMB.value > MAX_MB : false))

    function clear() {
        // @ts-ignore
        form[formKey] = null
        preview.value = null
        info.value = null
        const el = document.getElementById(formKey) as HTMLInputElement | null
        if (el) el.value = ''
    }

    function handleChange(e: Event) {
        const file = (e.target as HTMLInputElement).files?.[0] ?? null
        
        // @ts-ignore
        form[formKey] = file
        if (!file) return clear()

        info.value = { name: file.name, sizeBytes: file.size, type: file.type }
        const reader = new FileReader()
        reader.onload = (ev) => {
            const dataUrl = ev.target?.result as string
            preview.value = dataUrl
            const img = new Image()
            img.onload = () => {
                if (info.value) {
                    info.value.width  = img.naturalWidth
                    info.value.height = img.naturalHeight
                }
            }
            img.src = dataUrl
        }
        reader.readAsDataURL(file)
    }

    if (initialPath) {
        info.value = { name: initialPath.split('/').pop() || 'Bestaand bestand', sizeBytes: 1, type: 'onbekend' }
    }

    return { preview, info, sizeMB, tooLarge, clear, handleChange }
}


// const heroUpload = useImageUpload(safe.value.image_path, 'image') // REMOVED
const seoUpload = useImageUpload(safe.value.seo_image_path, 'seo_image')

// -------- Counters & snippet preview --------
const titleMax     = 255
const metaTitleMax = 65
const metaDescMax  = 160
const titleCount       = computed(() => (form.title ?? '').length)
const metaTitleCount   = computed(() => (form.meta_title ?? '').length)
const metaDescCount    = computed(() => (form.meta_description ?? '').length)

const stripTags = (s: string) => s.replace(/<[^>]*>/g, '')
const previewTitle = computed(() => (form.meta_title?.trim() || form.title || '').slice(0, metaTitleMax))
const previewDesc  = computed(() => {
    const base = form.meta_description?.trim() || stripTags(form.content || '')
    return base.replace(/\s+/g, ' ').slice(0, metaDescMax)
})
const previewUrl   = computed(() =>
    form.canonical_url?.trim() ||
    (typeof window !== 'undefined' ? window.location.origin + '/contact' : 'https://voorbeeld.nl/contact')
)

// -------- Submit --------
function submit() {
    successBar.value = ''
    errorBar.value   = ''

    if (!form.title?.trim()) {
        activeTab.value = 'content'
        errorBar.value = 'Titel is verplicht.'
        return
    }
    if (seoUpload.tooLarge.value) {
        activeTab.value = 'seo'
        errorBar.value = `Afbeelding is te groot. Max ${MAX_MB} MB.`
        return
    }

    form
        .transform((data) => ({ ...data, _method: 'put' }))
        .post('/hoofdbeheerder/pages/contact', {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                errorBar.value = ''
                successBar.value = '✅ Opgeslagen!'
                if (form.seo_image) {
                    seoUpload.preview.value = '/storage/' + (form.seo_image as File).name
                    seoUpload.info.value = null
                }
                setTimeout(() => (successBar.value = ''), 2200)
            },
            onError: () => {
                errorBar.value = 'Controleer de invoer.'
            },
        })
}



</script>

<template>
    <Head :title="isEdit ? `${sectionLabel} bewerken` : `Nieuwe ${sectionLabel}`" />

    <AdminAppLayout
        :breadcrumbs="[
      { title: 'Dashboard', href: routes.admin.dashboard },
      { title: sectionLabel, href: routes.admin.contactPage },
      { title: isEdit ? 'Bewerken' : 'Aanmaken', href: '#' },
    ]"
    >
        <!-- AANGEPAST: max-w-5xl naar max-w-7xl voor meer ruimte -->
        <div class="mx-auto max-w-7xl mt-8 space-y-6">
            <Card >
                <CardHeader>
                    <CardTitle class="text-2xl font-bold">
                        {{ isEdit ? `✏️ ${sectionLabel} bewerken` : `📝 Nieuwe ${sectionLabel}` }}
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit">
                        <!-- Statusbalken -->
                        <div v-if="successBar" class="mb-4 flex items-center gap-2 rounded-lg border border-emerald-300 bg-emerald-50 p-2 text-emerald-800">
                            <CheckCircle2 class="h-5 w-5" />
                            <span>{{ successBar }}</span>
                        </div>
                        <div v-if="errorBar" class="mb-4 flex items-center gap-2 rounded-lg border border-red-300 bg-red-50 p-2 text-red-800">
                            <XCircle class="h-5 w-5" />
                            <span>{{ errorBar }}</span>
                        </div>

                        <!-- AANGEPAST: grid-cols-3 naar grid-cols-2 en 'opties' verwijderd -->
                        <Tabs v-model="activeTab" class="w-full">
                            <TabsList class="grid w-full grid-cols-2">
                                <TabsTrigger value="content">Inhoud</TabsTrigger>
                                <TabsTrigger value="seo">SEO</TabsTrigger>
                            </TabsList>

                            <!-- ============ INHOUD ============ -->
                            <TabsContent value="content" class="space-y-8 mt-6">
                                <!-- Titel -->
                                <div>
                                    <div class="flex items-center justify-between">
                                        <Label for="title" class="font-semibold">Titel</Label>
                                        <span class="text-xs text-muted-foreground">{{ titleCount }} / {{ titleMax }}</span>
                                    </div>
                                    <Input
                                        id="title"
                                        v-model="form.title"
                                        :maxlength="titleMax"
                                        placeholder="Geef de pagina een duidelijke naam…"
                                        class="mt-1"
                                    />
                                    <span v-if="form.errors.title" class="text-sm text-red-500">{{ form.errors.title }}</span>
                                </div>



                                <!-- Inhoud -->
                                <div>
                                    <Label for="content" class="font-semibold">Introductietekst</Label>
                                    <div class="mt-1">
                                        <RichTextEditor
                                            v-model="form.content"
                                            height="300px"
                                            placeholder="Schrijf de introductietekst die boven het formulier verschijnt…"
                                        />
                                    </div>
                                    <span v-if="form.errors.content" class="text-sm text-red-500">{{ form.errors.content }}</span>
                                </div>

                                <!-- Button Text -->
                                <div>
                                    <Label for="button_text" class="font-semibold">Tekst op de knop</Label>
                                    <Input
                                        id="button_text"
                                        v-model="form.button_text"
                                        placeholder="bv. Neem contact op"
                                        class="mt-1"
                                    />
                                    <span v-if="form.errors.button_text" class="text-sm text-red-500">{{ form.errors.button_text }}</span>
                                </div>

                                <Separator class="my-6" />

                                <!-- Formulier Header -->
                                <div>
                                    <h3 class="text-lg font-semibold mb-4">Formulier Titel & Beschrijving</h3>
                                    
                                    <!-- AANGEPAST: Form Title -->
                                    <div class="mb-4">
                                        <Label for="form_title" class="font-semibold">Titel (Formulier)</Label>
                                        <Input
                                            id="form_title"
                                            v-model="form.form_title"
                                            placeholder="Titel boven het formulier (bv. Contact)"
                                            class="mt-1"
                                        />
                                        <span v-if="form.errors.form_title" class="text-sm text-red-500">{{ form.errors.form_title }}</span>
                                    </div>

                                    <!-- AANGEPAST: Form Content -->
                                    <div>
                                        <Label for="form_content" class="font-semibold">Beschrijving (Formulier)</Label>
                                        <RichTextEditor
                                            v-model="form.form_content"
                                            height="200px"
                                            placeholder="Tekst boven het formulier..."
                                        />
                                        <span v-if="form.errors.form_content" class="text-sm text-red-500">{{ form.errors.form_content }}</span>
                                    </div>
                                </div>
                            </TabsContent>

                            <!-- ============ SEO ============ -->
                            <TabsContent value="seo" class="space-y-8 mt-6">
                                <Alert class="border-amber-200 bg-amber-50 text-amber-900 dark:border-amber-800 dark:bg-amber-500/10 dark:text-amber-100">
                                    <AlertTitle class="flex items-center gap-2">
                                        <Info class="h-4 w-4" /> Wat is SEO?
                                    </AlertTitle>
                                    <AlertDescription class="mt-2 text-sm leading-relaxed">
                                        <ul class="list-disc pl-5 space-y-1">
                                            <li><b>SEO titel</b>: korte, duidelijke titel (± 50–60 tekens) met je belangrijkste woorden.</li>
                                            <li><b>Meta description</b>: 1–2 zinnen (max. ~160 tekens) die nieuwsgierig maken.</li>
                                            <li><b>Canonical URL</b>: het “officiële” adres van deze pagina (handig bij dubbelen).</li>
                                            <li><b>Robots</b>: <i>Indexeren</i> = in Google tonen; <i>Links volgen</i> = linkwaarde doorgeven.</li>
                                            <li><b>Open Graph afbeelding</b>: gebruikt door WhatsApp/LinkedIn (advies 1200×630 px, &lt; {{ MAX_MB }} MB).</li>
                                        </ul>
                                    </AlertDescription>
                                </Alert>

                                <!-- Google Preview (Standardized) -->
                                <div class="rounded-lg border bg-card p-6 shadow-sm">
                                    <h3 class="mb-4 text-sm font-medium text-muted-foreground">Google Zoekresultaat Voorbeeld</h3>
                                    <div class="w-full space-y-1 font-sans">
                                        <!-- URL -->
                                        <div class="flex items-center gap-1 text-sm text-[#202124] dark:text-[#bdc1c6]">
                                            <div class="flex h-7 w-7 items-center justify-center rounded-full bg-[#f1f3f4] dark:bg-[#303134]">
                                                <img src="/images/logo-icon.jpg" class="h-4 w-4 rounded-full" alt="" onerror="this.style.display='none'" />
                                            </div>
                                            <div class="flex flex-col leading-tight">
                                                <span class="text-xs font-medium">duidelijkheid.com</span>
                                                <span class="text-xs text-[#5f6368] dark:text-[#9aa0a6]">{{ previewUrl }}</span>
                                            </div>
                                        </div>
                                        <!-- Title -->
                                        <h3 class="truncate text-xl text-[#1a0dab] hover:underline dark:text-[#8ab4f8]">
                                            {{ previewTitle || 'Pagina Titel' }}
                                        </h3>
                                        <!-- Description -->
                                        <p class="text-sm leading-snug text-[#4d5156] dark:text-[#bdc1c6]">
                                            {{ previewDesc || 'Geen omschrijving opgegeven...' }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Meta title -->
                                <div>
                                    <div class="flex items-center justify-between">
                                        <Label for="meta_title" class="font-semibold">SEO titel</Label>
                                        <span class="text-xs text-muted-foreground">{{ metaTitleCount }} / {{ metaTitleMax }}</span>
                                    </div>
                                    <Input
                                        id="meta_title"
                                        v-model="form.meta_title"
                                        :maxlength="metaTitleMax"
                                        placeholder="Zoekmachine-titel (meestal 50–60 tekens)"
                                        class="mt-1"
                                    />
                                    <span v-if="form.errors.meta_title" class="text-sm text-red-500">{{ form.errors.meta_title }}</span>
                                </div>

                                <!-- Meta description -->
                                <div>
                                    <div class="flex items-center justify-between">
                                        <Label for="meta_description" class="font-semibold">Meta description</Label>
                                        <span class="text-xs text-muted-foreground">{{ metaDescCount }} / {{ metaDescMax }}</span>
                                    </div>
                                    <Textarea
                                        id="meta_description"
                                        v-model="form.meta_description"
                                        :maxlength="metaDescMax"
                                        rows="3"
                                        placeholder="Korte beschrijving (max. ~160 tekens)"
                                        class="mt-1"
                                    />
                                    <span v-if="form.errors.meta_description" class="text-sm text-red-500">{{ form.errors.meta_description }}</span>
                                </div>

                                <!-- Canonical -->
                                <div>
                                    <Label for="canonical_url" class="font-semibold">Canonical URL</Label>
                                    <Input id="canonical_url" v-model="form.canonical_url" type="url" placeholder="https://jouwsite.nl/contact" class="mt-1" />
                                    <span v-if="form.errors.canonical_url" class="text-sm text-red-500">{{ form.errors.canonical_url }}</span>
                                </div>

                                <!-- Robots -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                    <div class="flex flex-col items-start gap-1.5 min-h-[6rem]">
                                        <div class="flex items-center gap-2">
                                            <Checkbox id="robots_index" v-model:checked="form.robots_index" />
                                            <Label for="robots_index">Indexeren toestaan</Label>
                                        </div>
                                        <p class="text-xs text-muted-foreground">Zet uit om de pagina te verbergen in zoekmachines (noindex).</p>
                                    </div>
                                    <div class="flex flex-col items-start gap-1.5 min-h-[6rem]">
                                        <div class="flex items-center gap-2">
                                            <Checkbox id="robots_follow" v-model:checked="form.robots_follow" />
                                            <Label for="robots_follow">Links volgen</Label>
                                        </div>
                                        <p class="text-xs text-muted-foreground">Zet uit als linkwaarde niet moet worden doorgegeven (nofollow).</p>
                                    </div>
                                </div>

                                <!-- Open Graph / Social afbeelding UPLOAD SECTIE -->
                                <div>
                                    <Label for="seo_image" class="font-semibold flex gap-2 items-center">
                                        <Camera class="w-4 h-4" /> Open Graph / Social afbeelding
                                    </Label>
                                    <div class="mt-4 flex flex-col gap-5">
                                        <!-- Preview BOVEN -->
                                        <div class="w-full">
                                            <img v-if="seoUpload.preview.value" :src="seoUpload.preview.value" alt="SEO afbeelding preview" class="h-60 w-full rounded-lg object-cover shadow-sm ring-1 ring-border"/>
                                            <div v-else class="flex h-32 w-full items-center justify-center rounded-lg border border-dashed text-xs text-muted-foreground bg-muted/10">Geen preview</div>
                                        </div>

                                        <!-- Input ONDER -->
                                        <div>
                                            <Input id="seo_image" type="file" accept="image/*" @change="seoUpload.handleChange" :disabled="form.processing"/>
                                            <p class="mt-2 text-xs text-muted-foreground">Aanbevolen 1200×630 px — <b>Max {{ MAX_MB }} MB</b>.</p>

                                            <!-- Bestandsinformatie/Foutmelding -->
                                            <div v-if="seoUpload.info.value || seoUpload.preview.value"
                                                 class="mt-3 text-sm rounded border p-3"
                                                 :class="seoUpload.tooLarge.value ? 'border-red-300 bg-red-50 text-red-800' : 'border-slate-200 bg-white dark:bg-zinc-900'">
                                                <div class="flex justify-between gap-4">
                                                    <div class="font-medium truncate">{{ seoUpload.info.value?.name ?? 'Bestaand bestand' }}</div>
                                                    <div>
                                                        <span :class="seoUpload.tooLarge.value ? 'font-semibold' : ''">{{ seoUpload.sizeMB.value.toFixed(2) }} MB</span>
                                                        <span v-if="seoUpload.tooLarge.value"> — te groot</span>
                                                    </div>
                                                </div>
                                                <div class="mt-1 text-xs opacity-80">
                                                    {{ seoUpload.info.value?.type ?? 'Onbekend type' }}
                                                    <span v-if="seoUpload.info.value?.width && seoUpload.info.value?.height"> • {{ seoUpload.info.value.width }}×{{ seoUpload.info.value.height }} px</span>
                                                </div>
                                                <Button type="button" variant="link" class="px-0 h-auto mt-2 text-red-600" @click="seoUpload.clear" :disabled="form.processing">
                                                    <Trash2 class="h-3.5 w-3.5 mr-1" /> Verwijder afbeelding
                                                </Button>
                                            </div>
                                            <span v-if="form.errors.seo_image" class="mt-1 text-sm text-red-500">{{ form.errors.seo_image }}</span>
                                        </div>
                                    </div>
                                </div>


                            </TabsContent>

                            <!-- De opties voor 'show_form' en 'published' blijven nu als gewone inputvelden,
                                 maar de aparte tab is verwijderd. U kunt ze naar de SEO-tab verplaatsen indien gewenst.
                                 Voor nu laat ik ze op de Content-tab, maar de Opties tab is weg. -->

                        </Tabs>

                        <Button
                            type="button"
                            size="lg"
                            class="mt-6 w-full flex justify-center items-center gap-2"
                            :disabled="form.processing || !form.title || seoUpload.tooLarge.value"
                            @click="submit"
                        >
                            <Loader2 v-if="form.processing" class="mr-2 h-5 w-5 animate-spin" />
                            {{ isEdit ? 'Bijwerken' : 'Opslaan' }}
                        </Button>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AdminAppLayout>
</template>
