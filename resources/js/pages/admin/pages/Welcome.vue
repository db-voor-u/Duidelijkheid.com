<script setup lang="ts">
// Vue & Inertia
import { ref, computed } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'

// Layout
import AdminAppLayout from '@/layouts/AdminAppLayout.vue'

// shadcn/ui
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import { Textarea } from '@/components/ui/textarea'
import { Tabs, TabsList, TabsTrigger, TabsContent } from '@/components/ui/tabs'
import { Alert, AlertTitle, AlertDescription } from '@/components/ui/alert'

// Icons
import { Camera, Loader2, XCircle, CheckCircle2, Trash2, Info } from 'lucide-vue-next'

// Editor
import RichTextEditor from '@/components/RichTextEditor.vue'
import { routes } from '@/lib/routes'

// -------- Types --------
type WelcomeType = {
    id?: number
    title?: string
    content?: string
    image_path?: string
    meta_title?: string | null
    meta_description?: string | null
    seo_image_path?: string | null
    canonical_url?: string | null
    robots_index?: boolean
    robots_follow?: boolean
}

type CategoryType = {
    id: number
    name: string
    slug: string
    color: string
}

const props = defineProps<{ welcome?: WelcomeType; categories?: CategoryType[] }>()
const isEdit = computed(() => !!props.welcome?.id)

const safe = computed(() => ({
    title: props.welcome?.title ?? '',
    content: props.welcome?.content ?? '',
    image_path: props.welcome?.image_path ?? '',
    meta_title: props.welcome?.meta_title ?? '',
    meta_description: props.welcome?.meta_description ?? '',
    seo_image_path: props.welcome?.seo_image_path ?? '',
    canonical_url: props.welcome?.canonical_url ?? '',
    robots_index: props.welcome?.robots_index ?? true,
    robots_follow: props.welcome?.robots_follow ?? true,
}))

// -------- Form --------
const form = useForm({
    // content
    title: safe.value.title,
    image: null as File | null,
    content: safe.value.content,

    // seo
    meta_title: safe.value.meta_title,
    meta_description: safe.value.meta_description,
    seo_image: null as File | null,
    canonical_url: safe.value.canonical_url,
    robots_index: safe.value.robots_index,
    robots_follow: safe.value.robots_follow,
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
        form[formKey] = null
        preview.value = null
        info.value = null
        // Reset file input element
        const el = document.getElementById(formKey) as HTMLInputElement | null
        if (el) el.value = ''
    }

    function handleChange(e: Event) {
        const file = (e.target as HTMLInputElement).files?.[0] ?? null
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

    // Als er een bestaand pad is, initialiseer de info om delete te tonen
    if (initialPath) {
        info.value = { name: initialPath.split('/').pop() || 'Bestaand bestand', sizeBytes: 1, type: 'onbekend' }
    }

    return { preview, info, sizeMB, tooLarge, clear, handleChange }
}


// Gebruik de herbruikbare functie voor beide uploads
const heroUpload = useImageUpload(safe.value.image_path, 'image')
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
    (typeof window !== 'undefined' ? window.location.origin + '/' : 'https://voorbeeld.nl/')
)

// -------- Error mapping (mooie NL meldingen) --------
const FIELD_LABELS: Record<string, string> = {
    title: 'Titel',
    content: 'Inhoud',
    image: 'Afbeelding',
    meta_title: 'SEO titel',
    meta_description: 'Meta description',
    canonical_url: 'Canonical URL',
    seo_image: 'Social afbeelding',
    robots_index: 'Indexeren',
    robots_follow: 'Links volgen',
}

function humanize(msg: string | string[] | undefined): string {
    const m = Array.isArray(msg) ? msg[0] : (msg ?? '')
    return (!m || m === 'validation.required') ? 'is verplicht.' : m
}

function summarizeErrors(errors: Record<string, string>) {
    return Object.entries(errors)
        .map(([k, v]) => `${FIELD_LABELS[k] ?? k}: ${humanize(v)}`)
        .join(' • ')
}

function switchTabForErrors(errors: Record<string, string>) {
    const contentFields = ['title', 'content', 'image']
    const hasContentErr = Object.keys(errors).some(k => contentFields.includes(k))
    activeTab.value = hasContentErr ? 'content' : 'seo'
}

// -------- Submit --------
function submit() {
    successBar.value = ''
    errorBar.value   = ''

    // snelle client checks
    if (!form.title?.trim() || !form.content?.trim()) {
        activeTab.value = 'content'
        errorBar.value = !form.title?.trim() ? 'Titel is verplicht.' : 'Inhoud is verplicht.'
        return
    }
    if (heroUpload.tooLarge.value || seoUpload.tooLarge.value) {
        activeTab.value = heroUpload.tooLarge.value ? 'content' : 'seo'
        errorBar.value = `Afbeelding is te groot. Max ${MAX_MB} MB.`
        return
    }

// BELANGRIJK: POST + _method=put voor FormData (bestanden)
    form
        .transform((data) => ({ ...data, _method: 'put' }))
        .post(routes.admin.welcomeContent, {
            forceFormData: true,       // triggert multipart/form-data
            preserveScroll: true,
            onSuccess: () => {
                errorBar.value = ''
                successBar.value = '✅ Opgeslagen!'
                // Reset de previews na succesvolle upload/save
                if (form.image) {
                    // update de path van de nieuwe file
                    // Dit is een simpele manier, maar in een echte app zou je de nieuwe path uit de backend halen
                    heroUpload.preview.value = '/storage/' + (form.image as File).name
                    heroUpload.info.value = null
                }
                if (form.seo_image) {
                    seoUpload.preview.value = '/storage/' + (form.seo_image as File).name
                    seoUpload.info.value = null
                }

                // Tijdslimiet voor succesbalk
                setTimeout(() => (successBar.value = ''), 2200)
            },
            onError: () => {
                switchTabForErrors(form.errors as Record<string, string>)
                errorBar.value = summarizeErrors(form.errors as Record<string, string>)
            },
        })
}



</script>

<template>
    <Head title="Welkom pagina bewerken" />

    <AdminAppLayout
        :breadcrumbs="[
      { title: 'Dashboard', href: '/hoofdbeheerder/dashboard' },
      { title: 'Inhoud welkom pagina', href: '/hoofdbeheerder/welcome' },
    ]"
    >
        <!-- AANGEPASTD: max-w-3xl naar max-w-5xl voor meer ruimte -->
        <div class="mx-auto max-w-5xl mt-8 space-y-6">
            <Card >
                <CardHeader>
                    <CardTitle class="text-2xl font-bold">📝 Welkom hero inhoud</CardTitle>
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
                                    <p class="mt-1 text-xs text-muted-foreground flex items-center gap-1">
                                        <Info class="h-3.5 w-3.5" />
                                        Dit is de kop op de pagina. Laat je SEO-titel leeg? Dan gebruikt Google vaak deze titel.
                                    </p>
                                    <span v-if="form.errors.title" class="text-sm text-red-500">{{ form.errors.title }}</span>
                                </div>

                                <!-- Afbeelding UPLOAD SECTIE (Hero) -->
                                <div>
                                    <Label for="image" class="font-semibold flex gap-2 items-center">
                                        <Camera class="w-4 h-4" /> Hoofdafbeelding (Hero)
                                    </Label>
                                    <div class="mt-4 flex flex-col gap-5">
                                        <!-- Preview BOVEN -->
                                        <div class="w-full max-w-4xl">
                                            <img v-if="heroUpload.preview.value" :src="heroUpload.preview.value" alt="Hoofdafbeelding preview" class="w-full h-64 object-contain bg-zinc-100 dark:bg-zinc-900 rounded-lg shadow-sm ring-1 ring-border"/>
                                            <div v-else class="flex h-32 w-full items-center justify-center rounded-lg border border-dashed text-xs text-muted-foreground bg-muted/10">Geen preview</div>
                                        </div>

                                        <!-- Input ONDER -->
                                        <div>
                                            <Input id="image" type="file" accept="image/*" @change="heroUpload.handleChange" :disabled="form.processing"/>
                                            <p class="mt-2 text-xs text-muted-foreground">
                                                Wordt getoond in origineel formaat (max. 670px breed). Upload een afbeelding van minimaal 1200px breed (bv. 1200x800) voor de beste kwaliteit. • <b>Max {{ MAX_MB }} MB</b>.
                                            </p>

                                            <!-- Bestandsinformatie/Foutmelding -->
                                            <div v-if="heroUpload.info.value || heroUpload.preview.value"
                                                 class="mt-3 text-sm rounded border p-3"
                                                 :class="heroUpload.tooLarge.value ? 'border-red-300 bg-red-50 text-red-800' : 'border-slate-200 bg-white dark:bg-zinc-900'">
                                                <div class="flex justify-between gap-4">
                                                    <div class="font-medium truncate">{{ heroUpload.info.value?.name ?? 'Bestaand bestand' }}</div>
                                                    <div>
                                                        <span :class="heroUpload.tooLarge.value ? 'font-semibold' : ''">{{ heroUpload.sizeMB.value.toFixed(2) }} MB</span>
                                                        <span v-if="heroUpload.tooLarge.value"> — te groot</span>
                                                    </div>
                                                </div>
                                                <div class="mt-1 text-xs opacity-80">
                                                    {{ heroUpload.info.value?.type ?? 'Onbekend type' }}
                                                    <span v-if="heroUpload.info.value?.width && heroUpload.info.value?.height"> • {{ heroUpload.info.value.width }}×{{ heroUpload.info.value.height }} px</span>
                                                </div>
                                                <Button type="button" variant="link" class="px-0 h-auto mt-2 text-red-600" @click="heroUpload.clear" :disabled="form.processing">
                                                    <Trash2 class="h-3.5 w-3.5 mr-1" /> Verwijder afbeelding
                                                </Button>
                                            </div>
                                            <span v-if="form.errors.image" class="mt-1 text-sm text-red-500">{{ form.errors.image }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Inhoud -->
                                <div>
                                    <Label for="content" class="font-semibold">Inhoud</Label>
                                    <div class="rounded-md border mt-1">
                                    <RichTextEditor
                                        v-model="form.content"
                                        placeholder="Schrijf de welkomsttekst…"
                                        height="260px"
                                    />
                                    </div>
                                    <p class="mt-1 text-xs text-muted-foreground flex items-center gap-1">
                                        <Info class="h-3.5 w-3.5" />
                                        De tekst wordt als HTML opgeslagen en verschijnt op de welkomstpagina.
                                    </p>
                                    <span v-if="form.errors.content" class="text-sm text-red-500">{{ form.errors.content }}</span>
                                </div>
                            <!-- ============ CATEGORIES ============ -->
                            <!-- Nieuw: Overzicht van categorieën (readonly) -->
                            <div v-if="categories && categories.length > 0" class="mt-8 pt-8 border-t">
                                <h3 class="text-lg font-semibold mb-3">Beschikbare Categorieën</h3>
                                <div class="grid gap-3 sm:grid-cols-2 md:grid-cols-3">
                                    <div v-for="cat in categories" :key="cat.id" class="flex items-center gap-3 p-3 rounded-lg border bg-muted/20">
                                        <div :class="['w-4 h-4 rounded-full shadow-sm ring-1 ring-inset ring-black/10', cat.color]"></div>
                                        <div>
                                            <div class="font-medium">{{ cat.name }}</div>
                                            <div class="text-xs text-muted-foreground truncate">{{ cat.slug }}</div>
                                        </div>
                                    </div>
                                </div>
                                <p class="mt-3 text-xs text-muted-foreground">
                                    Dit zijn de blogcategorieën die momenteel actief zijn.
                                    <a href="/hoofdbeheerder/categorieen" class="text-primary hover:underline">Beheer categorieën</a>
                                </p>
                            </div>
                            <div v-else class="mt-8 pt-8 border-t">
                                <h3 class="text-lg font-semibold mb-3">Categorieën</h3>
                                <p class="text-sm text-muted-foreground mb-2">Er zijn nog geen categorieën aangemaakt.</p>
                                <a href="/hoofdbeheerder/categorieen" class="text-primary hover:underline text-sm">Maak je eerste categorie aan &rarr;</a>
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
                                    <p class="mt-1 text-xs text-muted-foreground">Laat leeg om de gewone titel te gebruiken.</p>
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
                                    <p class="mt-1 text-xs text-muted-foreground">Schrijf een uitnodigende samenvatting. Google toont dit vaak onder de titel.</p>
                                    <span v-if="form.errors.meta_description" class="text-sm text-red-500">{{ form.errors.meta_description }}</span>
                                </div>

                                <!-- Canonical -->
                                <div>
                                    <Label for="canonical_url" class="font-semibold">Canonical URL</Label>
                                    <Input id="canonical_url" v-model="form.canonical_url" type="url" placeholder="https://voorbeeld.nl/" class="mt-1" />
                                    <p class="mt-1 text-xs text-muted-foreground">
                                        Gebruik dit als dezelfde content op meerdere URL’s staat; zo voorkom je duplicate-content.
                                    </p>
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
                        </Tabs>

                        <!-- Submit -->
                        <Button
                            type="button"
                            size="lg"
                            class="mt-6 w-full flex justify-center items-center gap-2"
                            :disabled="form.processing || !form.title || !form.content || heroUpload.tooLarge.value || seoUpload.tooLarge.value"
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
