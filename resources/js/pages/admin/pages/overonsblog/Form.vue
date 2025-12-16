<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AdminAppLayout from '@/layouts/AdminAppLayout.vue'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Button } from '@/components/ui/button'
import { Textarea } from '@/components/ui/textarea'
import { Tabs, TabsList, TabsTrigger, TabsContent } from '@/components/ui/tabs'
import { Alert, AlertTitle, AlertDescription } from '@/components/ui/alert'
import { Camera, Loader2, XCircle, CheckCircle2, Info, ArrowLeft, Youtube, Upload, Download } from 'lucide-vue-next'
import RichTextEditor from '@/components/RichTextEditor.vue'
import { routes, adminRouteFns } from '@/lib/routes'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'

const MAX_MB_IMAGE = 12
const MAX_MB_VIDEO = 50
const MAX_MB_FILE = 12

type BlogType = {
    id?: number
    title?: string
    slug?: string
    excerpt?: string | null
    content?: string
    cover_image_path?: string | null
    published_at?: string | null
    is_published?: boolean
    meta_title?: string | null
    meta_description?: string | null
    canonical_url?: string | null
    robots_index?: boolean
    robots_follow?: boolean
    og_image_path?: string | null
    media_type?: 'image' | 'youtube' | 'upload' | string
    video_path?: string | null
    download_file_path?: string | null
    category_id?: number | null
}

const props = defineProps<{
    post?: BlogType | null;
    success?: string;
    categories?: Array<{id: number, name: string, color: string}>;
    section?: string;
}>()
const isEdit = computed(() => !!props.post?. id)
const originalSlug = props.post?.slug ??  ''

const sectionLabel = computed(() => {
    switch (props.section) {
        case 'innovatie': return 'Innovatie Item';
        case 'neurodiversiteit': return 'Neurodiversiteit Item';
        default: return 'Over Ons Item';
    }
})

const pageTitle = computed(() => isEdit.value ? `✏️ ${sectionLabel.value} bewerken` : `📝 Nieuw ${sectionLabel.value}`)

const safe = computed(() => ({
    title: props.post?. title ?? '',
    slug: props.post?.slug ?? '',
    excerpt: props.post?.excerpt ??  '',
    content: props.post?. content ?? '',
    cover_image_path: props.post?.cover_image_path ?? '',
    published_at: props. post?.published_at || undefined,
    is_published: props.post?.is_published ?? false,
    meta_title: props.post?.meta_title ?? '',
    meta_description: props. post?.meta_description ?? '',
    canonical_url: props.post?.canonical_url ?? '',
    robots_index: props.post?. robots_index ?? true,
    robots_follow: props.post?.robots_follow ?? true,
    og_image_path: props.post?.og_image_path ?? '',
    media_type: props.post?.media_type ?? 'image',
    video_path: props.post?. video_path ?? null,
    download_file_path: props.post?.download_file_path ?? null,
    category_id: props.post?.category_id ?? null,
}))

const form = useForm({
    title: safe.value.title,
    slug: safe.value.slug,
    excerpt: safe.value.excerpt,
    content: safe.value.content,
    cover_image: null as File | null,
    og_image: null as File | null,
    video_upload: null as File | null,
    download_file: null as File | null,
    media_type: safe.value.media_type,
    youtube_url: safe.value.media_type === 'youtube' ?  (safe.value.video_path || undefined) : undefined,
    is_published: safe.value.is_published,
    published_at: safe.value.published_at || undefined,
    meta_title: safe.value.meta_title,
    meta_description: safe. value.meta_description,
    canonical_url: safe.value. canonical_url,
    robots_index: safe.value.robots_index,
    robots_follow: safe.value.robots_follow,
    category_id: safe.value.category_id,
})

const tab = ref<'content' | 'seo'>('content')
const successBar = ref(props.success ??  '')
const errorBar = ref('')

onMounted(() => {
    if (successBar.value) setTimeout(() => { successBar.value = "" }, 2300)
})

function useImageUpload(
    initialPath: string | null,
    formKey: 'cover_image' | 'og_image' | 'video_upload' | 'download_file'
) {
    const isVideo = formKey === 'video_upload'
    const isDownload = formKey === 'download_file'
    const MAX_MB = isVideo ? MAX_MB_VIDEO : isDownload ? MAX_MB_FILE : MAX_MB_IMAGE

    const preview = ref<string | null>(
        initialPath ?  (initialPath.startsWith('http') ?  initialPath : `/storage/${initialPath}`) : null
    )
    const info = ref<{ name: string; sizeBytes: number; type: string; w?: number; h?: number } | null>(null)
    const sizeMB = computed(() => (info.value ?  +(info.value.sizeBytes / 1024 / 1024) : 0))
    const tooLarge = computed(() => (info.value ? sizeMB.value > MAX_MB : false))

    function clear() {
        form[formKey] = null
        preview.value = null
        info. value = null
        const el = document.getElementById(formKey) as HTMLInputElement | null
        if (el) el.value = ''
        if (formKey === 'download_file') {
            form[formKey] = 'DELETE' as any
        }
    }

    function handleChange(e: Event) {
        const file = (e.target as HTMLInputElement).files?.[0] ??  null
        form[formKey] = file
        if (! file) return clear()

        info.value = { name: file.name, sizeBytes: file.size, type: file.type }

        if (! isVideo && ! isDownload) {
            const reader = new FileReader()
            reader.onload = ev => {
                const dataUrl = ev.target?.result as string
                preview.value = dataUrl
                const img = new Image()
                img. onload = () => {
                    if (info.value) {
                        info.value.w = img.naturalWidth
                        info.value. h = img.naturalHeight
                    }
                }
                img.src = dataUrl
            }
            reader.readAsDataURL(file)
        } else {
            preview.value = 'FILE_UPLOADED'
        }
    }

    if (initialPath) {
        info.value = { name: initialPath.split('/').pop() || 'Bestaand bestand', sizeBytes: 1, type: 'onbekend' }
    }

    const finalPreview = computed(() => {
        if (formKey === 'video_upload' && form.media_type === 'youtube') return form.youtube_url
        return preview.value
    })

    return { preview: finalPreview, info, sizeMB, tooLarge, clear, handleChange, MAX_MB }
}

const coverUpload = useImageUpload(safe.value.cover_image_path, 'cover_image')
const ogUpload = useImageUpload(safe.value.og_image_path, 'og_image')
const videoUpload = useImageUpload(safe.value.media_type === 'upload' ?  safe.value.video_path : null, 'video_upload')
const downloadUpload = useImageUpload(safe.value.download_file_path, 'download_file')

const titleMax = 255, slugMax = 255, excerptMax = 300, metaTitleMax = 65, metaDescMax = 160
const titleCount = computed(() => (form.title ??  '').length)
const slugCount = computed(() => (form.slug ?? '').length)
const excerptCount = computed(() => (form.excerpt ?? '').length)
const metaTitleCount = computed(() => (form.meta_title ??  '').length)
const metaDescCount = computed(() => (form.meta_description ?? '').length)

const stripTags = (s: string) => s.replace(/<[^>]*>/g, '')
const previewTitle = computed(() => (form.meta_title?. trim() || form.title || '').slice(0, metaTitleMax))
const previewDesc = computed(() => {
    const base = form.meta_description?.trim() || stripTags(form.excerpt || stripTags(form.content || ''))
    return base.replace(/\s+/g, ' ').slice(0, metaDescMax)
})
const previewUrl = computed(
    () => form.canonical_url?. trim() ||
        (typeof window !== 'undefined' ?  window.location.origin + '/over-ons-blog/' + (form.slug || 'voorbeeld') : 'https://voorbeeld.nl/over-ons-blog/artikel')
)

const FIELD_LABELS: Record<string, string> = {
    title: 'Titel', slug: 'Slug', excerpt: 'Samenvatting', content: 'Inhoud',
    cover_image: 'Omslag', og_image: 'Social afbeelding', video_upload: 'Video bestand',
    youtube_url: 'YouTube URL', download_file: 'Download bestand', media_type: 'Mediatype',
    meta_title: 'SEO titel', meta_description: 'Meta description', canonical_url: 'Canonical URL',
    robots_index: 'Indexeren', robots_follow: 'Links volgen'
}

function humanize(msg: string | string[] | undefined) {
    const m = Array.isArray(msg) ? msg[0] : msg ??  ''
    return ! m || m === 'validation. required' ? 'is verplicht.' : m
}

function summarizeErrors(errors: Record<string, string>) {
    return Object.entries(errors)
        .map(([k, v]) => `${FIELD_LABELS[k] ??  k}: ${humanize(v)}`)
        . join(' • ')
}

function switchTabForErrors(errors: Record<string, string>) {
    const contentFields = ['title', 'slug', 'excerpt', 'content', 'cover_image', 'video_upload', 'youtube_url', 'download_file']
    tab.value = Object.keys(errors).some(k => contentFields.includes(k)) ? 'content' : 'seo'
}

function submit() {
    successBar.value = ''
    errorBar.value = ''

    if (!form.title?. trim() || !form.content?.trim()) {
        tab.value = 'content'
        errorBar.value = ! form.title?.trim() ? 'Titel is verplicht.' : 'Inhoud is verplicht.'
        return
    }
    if (coverUpload.tooLarge. value || ogUpload.tooLarge.value || videoUpload.tooLarge.value || downloadUpload.tooLarge.value) {
        tab. value = (coverUpload.tooLarge.value || videoUpload.tooLarge. value || downloadUpload.tooLarge.value) ? 'content' : 'seo'
        errorBar.value = `Een bestand is te groot.  Max Afbeelding ${MAX_MB_IMAGE} MB, Max Video ${MAX_MB_VIDEO} MB, Max Bestand ${MAX_MB_FILE} MB. `
        return
    }

    const url = isEdit.value ? adminRouteFns.overonsBlogUpdate(originalSlug) : routes.admin.overonsBlogStore

    const transform = (data: any) => ({
        ... data,
        _method: isEdit.value ? 'put' : undefined,
        is_published: data.is_published ?  1 : 0,
        robots_index: data.robots_index ? 1 : 0,
        robots_follow: data. robots_follow ? 1 : 0,
        cover_image: data.media_type !== 'image' ? null : data.cover_image,
        video_upload: data.media_type !== 'upload' ? null : data.video_upload,
        youtube_url: data.media_type !== 'youtube' ? null : data.youtube_url,
        download_file: data.download_file === 'DELETE' ? 'DELETE' : data.download_file,
    })

    form.transform(transform). post(url, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            errorBar.value = ''
            successBar.value = '✅ Opgeslagen!'
            setTimeout(() => (successBar.value = ''), 2200)
            if (! isEdit.value) window.location.reload()
        },
        onError: () => {
            switchTabForErrors(form.errors as Record<string, string>)
            errorBar.value = summarizeErrors(form.errors as Record<string, string>)
        }
    })
}

const youtubeUrlModel = computed({
    get: () => form.youtube_url || '',
    set: (val: string) => { form.youtube_url = val || undefined }
})


// --- Slugify Helper ---
function slugify(text: string) {
    return text
        .toString()
        .toLowerCase()
        .normalize('NFKD') 
        .replace(/[\u0300-\u036f]/g, '') 
        .trim()
        .replace(/\s+/g, '-')
        .replace(/[^\w\-]+/g, '')
        .replace(/\-\-+/g, '-')
        .replace(/^-+/, '') 
        .replace(/-+$/, '') 
        .slice(0, 255)
}

// Auto-fill slug on title change (only for new posts)
watch(() => form.title, (newVal) => {
    if (!isEdit.value && newVal) {
        form.slug = slugify(newVal)
    }
})

</script>

<template>
    <Head :title="isEdit ? `${sectionLabel} bewerken` : `${sectionLabel} aanmaken`" />
    <AdminAppLayout
        :breadcrumbs="[
            { title: 'Dashboard', href: routes.admin.dashboard },
            { title: sectionLabel, href: '#' },
            { title: isEdit ? 'Bewerken' : 'Aanmaken', href: '#' }
        ]"
    >
        <div class="mx-auto mt-8 max-w-5xl space-y-6 p-6">
           

            <Card class="border-0 shadow-lg">
                <CardHeader>
                    <CardTitle class="text-2xl font-bold">
                        {{ pageTitle }}
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <!-- Statusbalken -->
                    <div v-if="successBar" class="mb-4 flex items-center gap-2 rounded-lg border border-emerald-300 bg-emerald-50 p-2 text-emerald-800 dark:border-emerald-800 dark:bg-emerald-900/20">
                        <CheckCircle2 class="h-5 w-5" />
                        <span>{{ successBar }}</span>
                    </div>
                    <div v-if="errorBar" class="mb-4 flex items-center gap-2 rounded-lg border border-red-300 bg-red-50 p-2 text-red-800 dark:border-red-800 dark:bg-red-900/20">
                        <XCircle class="h-5 w-5" />
                        <span>{{ errorBar }}</span>
                    </div>

                    <Tabs v-model="tab" class="w-full">
                        <TabsList class="grid w-full grid-cols-2">
                            <TabsTrigger value="content">Inhoud</TabsTrigger>
                            <TabsTrigger value="seo">SEO</TabsTrigger>
                        </TabsList>

                        <!-- Content Tab -->
                        <TabsContent value="content" class="mt-6 space-y-8">
                            <!-- Titel + slug -->
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <div class="flex items-center justify-between">
                                        <Label for="title" class="font-semibold">Titel</Label>
                                        <span class="text-xs text-muted-foreground">{{ titleCount }} / {{ titleMax }}</span>
                                    </div>
                                    <Input id="title" v-model="form.title" :maxlength="titleMax" placeholder="Titel van je artikel…" class="mt-1" />
                                    <p class="mt-1 text-xs text-muted-foreground">Korte, duidelijke titel. </p>
                                    <span v-if="form.errors.title" class="text-sm text-red-500">{{ form.errors.title }}</span>
                                </div>
                                <div>
                                    <div class="flex items-center justify-between">
                                        <Label for="slug" class="font-semibold">Slug</Label>
                                        <span class="text-xs text-muted-foreground">{{ slugCount }} / {{ slugMax }}</span>
                                    </div>
                                    <Input id="slug" v-model="form.slug" :maxlength="slugMax" placeholder="autisme-of-iets-anders" class="mt-1" />
                                </div>
                            </div>

                            <div>
                                <Label for="category_id" class="font-semibold">Categorie</Label>
                                <Select :model-value="form.category_id ? String(form.category_id) : undefined" @update:model-value="(v) => form.category_id = v === 'null' ? null : Number(v)">
                                    <SelectTrigger class="mt-1">
                                        <SelectValue placeholder="Selecteer categorie" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="null">Geen categorie</SelectItem>
                                        <SelectItem v-for="cat in categories" :key="cat.id" :value="String(cat.id)">
                                            {{ cat.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <!-- Samenvatting -->
                            <div>
                                <div class="flex items-center justify-between">
                                    <Label for="excerpt" class="font-semibold">Korte samenvatting</Label>
                                    <span class="text-xs text-muted-foreground">{{ excerptCount }} / {{ excerptMax }}</span>
                                </div>
                                <Textarea id="excerpt" v-model="form.excerpt" :maxlength="excerptMax" rows="3" class="mt-1"
                                          placeholder="1–2 zinnen die nieuwsgierig maken…" />
                                <p class="mt-1 text-xs text-muted-foreground">Wordt soms gebruikt in overzichten en als basis voor de meta-description.</p>
                                <span v-if="form.errors.excerpt" class="text-sm text-red-500">{{ form.errors.excerpt }}</span>
                            </div>

                            <!-- Media Type Select -->
                            <div class="border-t pt-8">
                                <Label for="media_type" class="font-semibold flex items-center gap-2">
                                    <Camera class="w-4 h-4" /> Omslag / Hero Media
                                </Label>
                                <p class="mt-1 mb-3 text-xs text-muted-foreground">Kies het type media dat u wilt gebruiken voor de cover van dit artikel.</p>

                                <Select v-model="form. media_type">
                                    <SelectTrigger class="w-full sm:w-1/2">
                                        <SelectValue placeholder="Kies mediatype" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="image">Afbeelding (Standaard)</SelectItem>
                                        <SelectItem value="youtube">YouTube Video (URL)</SelectItem>
                                        <SelectItem value="upload">Video Upload (MP4/WebM)</SelectItem>
                                    </SelectContent>
                                </Select>

                                <!-- Voorwaardelijke Media Velden -->
                                <div class="mt-6 space-y-6">
                                    <!-- AFBEELDING UPLOAD -->
                                    <div v-if="form.media_type === 'image'">
                                        <Label for="cover_image" class="font-semibold flex items-center gap-2">Omslagafbeelding</Label>
                                        <div class="mt-4 flex flex-col gap-5">
                                            <!-- Preview BOVEN -->
                                            <div class="w-full max-w-4xl">
                                                <img v-if="coverUpload.preview.value" :src="coverUpload.preview.value" alt="Omslag preview" class="w-full h-64 object-contain bg-zinc-100 dark:bg-zinc-900 rounded-lg shadow-sm ring-1 ring-border"/>
                                                <div v-else class="flex h-32 w-full items-center justify-center rounded-lg border border-dashed text-xs text-muted-foreground bg-muted/10">Geen preview</div>
                                            </div>

                                            <!-- Input ONDER -->
                                            <div>
                                                <Input id="cover_image" type="file" accept="image/*" @change="coverUpload.handleChange"/>
                                                <p class="mt-2 text-xs text-muted-foreground">
                                                    Wordt getoond in origineel formaat (max. 670px breed). Upload een afbeelding van minimaal 1200px breed (bv. 1200x800) voor de beste kwaliteit. • <b>Max {{ MAX_MB_IMAGE }} MB</b>.
                                                </p>
                                                <div v-if="coverUpload.info.value || coverUpload.preview.value" class="mt-3 rounded border p-3 text-sm"
                                                     :class="coverUpload.tooLarge.value ? 'border-red-300 bg-red-50 text-red-800 dark:bg-red-900/20' : 'border-slate-200 bg-white dark:bg-zinc-900'">
                                                    <div class="flex justify-between gap-4">
                                                        <div class="font-medium truncate">{{ coverUpload.info.value?.name ?? 'Bestaand bestand' }}</div>
                                                        <div>
                                                            <span :class="coverUpload.tooLarge.value ? 'font-semibold':''">{{ coverUpload.sizeMB.value.toFixed(2) }} MB</span>
                                                            <span v-if="coverUpload.tooLarge.value"> — te groot</span>
                                                        </div>
                                                    </div>
                                                    <div class="mt-1 text-xs opacity-80">
                                                        {{ coverUpload.info.value?.type ?? 'Onbekend type' }}
                                                        <span v-if="coverUpload.info.value?.w && coverUpload.info.value?.h">
                                                            • {{ coverUpload.info.value.w }}×{{ coverUpload.info.value.h }} px
                                                        </span>
                                                    </div>
                                                    <Button type="button" variant="link" class="px-0 h-auto mt-2 text-red-600" @click="coverUpload.clear">
                                                        <Trash2 class="mr-1 h-3.5 w-3.5" /> Verwijder afbeelding
                                                    </Button>
                                                </div>
                                                <span v-if="form.errors.cover_image" class="mt-1 text-sm text-red-500">{{ form.errors.cover_image }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- YOUTUBE URL -->
                                    <div v-else-if="form.media_type === 'youtube'">
                                        <Label for="youtube_url" class="font-semibold flex items-center gap-2">
                                            <Youtube class="w-4 h-4" /> YouTube URL
                                        </Label>
                                        <Input id="youtube_url" v-model="youtubeUrlModel" type="url" placeholder="https://www.youtube.com/watch?v=voorbeeld" class="mt-1" />
                                        <p v-if="form.errors.youtube_url" class="mt-1 text-sm text-red-500">{{ form.errors.youtube_url }}</p>
                                        <p class="mt-1 text-xs text-muted-foreground">Plak hier de volledige YouTube URL. </p>
                                    </div>

                                    <!-- VIDEO UPLOAD -->
                                    <div v-else-if="form.media_type === 'upload'">
                                        <Label for="video_upload" class="font-semibold flex items-center gap-2">
                                            <Upload class="w-4 h-4" /> Video Bestand
                                        </Label>
                                        <div class="mt-2">
                                            <Input id="video_upload" type="file" accept="video/mp4,video/webm,video/ogg" @change="videoUpload.handleChange" />
                                            <p class="mt-1 text-xs text-muted-foreground">Format: MP4, WebM, Ogg • <b>Max {{ MAX_MB_VIDEO }} MB</b>.</p>
                                            <div v-if="videoUpload. info.value || videoUpload. preview.value" class="mt-2 rounded border p-2 text-sm"
                                                 :class="videoUpload. tooLarge.value ? 'border-red-300 bg-red-50 text-red-800 dark:bg-red-900/20' : 'border-slate-200 bg-white dark:bg-zinc-900'">
                                                <div class="flex justify-between gap-4">
                                                    <div class="font-medium truncate">{{ videoUpload.info. value?.name ?? 'Bestaand bestand' }}</div>
                                                    <div>
                                                        <span :class="videoUpload.tooLarge.value ? 'font-semibold':''">{{ videoUpload.sizeMB. value.toFixed(2) }} MB</span>
                                                        <span v-if="videoUpload.tooLarge. value"> — te groot</span>
                                                    </div>
                                                </div>
                                                <Button type="button" variant="link" class="px-0 h-auto mt-1 text-red-600" @click="videoUpload.clear">
                                                    <Trash2 class="mr-1 h-3. 5 w-3.5" /> Verwijder bestand
                                                </Button>
                                            </div>
                                            <span v-if="form.errors. video_upload" class="mt-1 text-sm text-red-500">{{ form.errors.video_upload }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Download Bestand -->
                            <div class="border-t pt-8">
                                <Label for="download_file" class="font-semibold flex items-center gap-2">
                                    <Download class="w-4 h-4" /> Optioneel Downloadbestand
                                </Label>
                                <p class="mt-1 mb-3 text-xs text-muted-foreground">Een bestand dat de gebruiker kan downloaden (bv. PDF, DOCX).</p>

                                <div class="mt-2 grid items-start gap-4 sm:grid-cols-3">
                                    <div class="sm:col-span-2">
                                        <Input id="download_file" type="file" accept=".pdf,. doc,.docx,. zip" @change="downloadUpload.handleChange" />
                                        <p class="mt-1 text-xs text-muted-foreground">Toegestane types: PDF, DOCX, ZIP • <b>Max {{ MAX_MB_FILE }} MB</b>.</p>
                                        <div v-if="downloadUpload. info.value || downloadUpload. preview.value" class="mt-2 rounded border p-2 text-sm"
                                             :class="downloadUpload.tooLarge.value ? 'border-red-300 bg-red-50 text-red-800 dark:bg-red-900/20' : 'border-slate-200 bg-white dark:bg-zinc-900'">
                                            <div class="flex justify-between gap-4">
                                                <div class="font-medium truncate">{{ downloadUpload.info.value?. name ?? 'Bestaand bestand' }}</div>
                                                <div>
                                                    <span :class="downloadUpload.tooLarge. value ? 'font-semibold':''">{{ downloadUpload.sizeMB.value.toFixed(2) }} MB</span>
                                                    <span v-if="downloadUpload. tooLarge.value"> — te groot</span>
                                                </div>
                                            </div>
                                            <Button type="button" variant="link" class="px-0 h-auto mt-1 text-red-600" @click="downloadUpload.clear">
                                                <Trash2 class="mr-1 h-3. 5 w-3.5" /> Verwijder bestand
                                            </Button>
                                        </div>
                                        <span v-if="form. errors.download_file" class="mt-1 text-sm text-red-500">{{ form.errors.download_file }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Inhoud -->
                            <div>
                                <Label for="content" class="font-semibold">Inhoud</Label>
                                <div class="mt-1">
                                    <RichTextEditor
                                        v-model="form.content"
                                        placeholder="Schrijf je artikel…"
                                        height="320px"
                                    />
                                </div>
                                <p class="mt-1 text-xs text-muted-foreground">Content wordt als HTML opgeslagen.</p>
                                <span v-if="form.errors.content" class="text-sm text-red-500">{{ form.errors.content }}</span>
                            </div>

                            <!-- Publicatie -->
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="flex items-center gap-2">
                                    <input
                                        type="checkbox"
                                        id="is_published"
                                        v-model="form.is_published"
                                        class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:ring-offset-gray-800"
                                    />
                                    <Label for="is_published">Gepubliceerd</Label>
                                </div>
                                <div>
                                    <Label for="published_at">Publicatiedatum/tijd (optioneel)</Label>
                                    <Input id="published_at" v-model="form.published_at" type="datetime-local" class="mt-1" />
                                    <p class="mt-1 text-xs text-muted-foreground">Laat leeg voor "nu" bij publiceren.</p>
                                </div>
                            </div>
                        </TabsContent>

                        <!-- SEO Tab -->
                        <TabsContent value="seo" class="mt-6 space-y-8">
                            <Alert class="border-amber-200 bg-amber-50 text-amber-900 dark:border-amber-800 dark:bg-amber-500/10 dark:text-amber-100">
                                <AlertTitle class="flex items-center gap-2">
                                    <Info class="h-4 w-4" /> Wat is SEO?
                                </AlertTitle>
                                <AlertDescription class="mt-2 text-sm leading-relaxed">
                                    <ul class="list-disc space-y-1 pl-5">
                                        <li><b>SEO titel</b>: korte, duidelijke titel (± 50–60 tekens).</li>
                                        <li><b>Meta description</b>: 1–2 zinnen (max.  ~160 tekens) die uitnodigen tot klikken.</li>
                                        <li><b>Canonical URL</b>: "officiële" link van dit artikel.</li>
                                        <li><b>Robots</b>: <i>Indexeren</i> = tonen in Google; <i>Links volgen</i> = linkwaarde doorgeven.</li>
                                        <li><b>Open Graph</b>-afbeelding: gebruikt door WhatsApp/LinkedIn (1200×630 px).</li>
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
                                            <span class="text-xs text-[#5f6368] dark:text-[#9aa0a6]">https://duidelijkheid.com/over-ons-blog/{{ form.slug || 'url-slug' }}</span>
                                        </div>
                                    </div>
                                    <!-- Title -->
                                    <h3 class="truncate text-xl text-[#1a0dab] hover:underline dark:text-[#8ab4f8]">
                                        {{ form.meta_title || form.title || 'Pagina Titel' }}
                                    </h3>
                                    <!-- Description -->
                                    <p class="text-sm leading-snug text-[#4d5156] dark:text-[#bdc1c6]">
                                        {{ form.meta_description || form.excerpt || 'Geen omschrijving opgegeven...' }}
                                    </p>
                                </div>
                            </div>

                            <div>
                                <div class="flex items-center justify-between">
                                    <Label for="meta_title" class="font-semibold">SEO titel</Label>
                                    <span class="text-xs text-muted-foreground">{{ metaTitleCount }} / {{ metaTitleMax }}</span>
                                </div>
                                <Input id="meta_title" v-model="form.meta_title" :maxlength="metaTitleMax" placeholder="Zoekmachine-titel" class="mt-1" />
                                <p class="mt-1 text-xs text-muted-foreground">Laat leeg om de gewone titel te gebruiken.</p>
                                <span v-if="form.errors.meta_title" class="text-sm text-red-500">{{ form.errors.meta_title }}</span>
                            </div>

                            <div>
                                <div class="flex items-center justify-between">
                                    <Label for="meta_description" class="font-semibold">Meta description</Label>
                                    <span class="text-xs text-muted-foreground">{{ metaDescCount }} / {{ metaDescMax }}</span>
                                </div>
                                <Textarea id="meta_description" v-model="form.meta_description" :maxlength="metaDescMax" rows="3" class="mt-1"
                                          placeholder="Korte beschrijving (max. ~160 tekens)" />
                                <span v-if="form.errors. meta_description" class="text-sm text-red-500">{{ form.errors.meta_description }}</span>
                            </div>

                            <div>
                                <Label for="canonical_url" class="font-semibold">Canonical URL</Label>
                                <Input id="canonical_url" v-model="form. canonical_url" type="url" placeholder="https://voorbeeld.nl/over-ons-blog/…" class="mt-1" />
                                <span v-if="form.errors. canonical_url" class="text-sm text-red-500">{{ form.errors.canonical_url }}</span>
                            </div>

                            <!-- Robots -->
                            <div class="grid gap-6 sm:grid-cols-2">
                                <div class="flex flex-col items-start gap-1. 5 min-h-[6rem]">
                                    <div class="flex items-center gap-2">
                                        <input
                                            type="checkbox"
                                            id="robots_index"
                                            v-model="form.robots_index"
                                            class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:ring-offset-gray-800"
                                        />
                                        <Label for="robots_index">Indexeren toestaan</Label>
                                    </div>
                                    <p class="text-xs text-muted-foreground">Zet uit om te verbergen in zoekmachines (noindex).</p>
                                </div>
                                <div class="flex flex-col items-start gap-1.5 min-h-[6rem]">
                                    <div class="flex items-center gap-2">
                                        <input
                                            type="checkbox"
                                            id="robots_follow"
                                            v-model="form.robots_follow"
                                            class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:ring-offset-gray-800"
                                        />
                                        <Label for="robots_follow">Links volgen</Label>
                                    </div>
                                    <p class="text-xs text-muted-foreground">Zet uit om linkwaarde niet door te geven (nofollow).</p>
                                </div>
                            </div>

                            <!-- Open Graph afbeelding -->
                            <div>
                                <Label for="og_image" class="font-semibold flex items-center gap-2">
                                    <Camera class="w-4 h-4" /> Open Graph / Social afbeelding
                                </Label>
                                <div class="mt-4 flex flex-col gap-5">
                                    <!-- Preview BOVEN -->
                                    <div class="w-full">
                                        <img v-if="ogUpload.preview.value" :src="ogUpload.preview.value" class="h-60 w-full rounded-lg object-contain bg-slate-100 dark:bg-slate-800 shadow-sm ring-1 ring-border" alt="OG preview" />
                                        <div v-else class="flex h-32 w-full items-center justify-center rounded-lg border border-dashed text-xs text-muted-foreground bg-muted/10">Geen preview</div>
                                    </div>

                                    <!-- Input ONDER -->
                                    <div>
                                        <Input id="og_image" type="file" accept="image/*" @change="ogUpload.handleChange" />
                                        <p class="mt-2 text-xs text-muted-foreground">Aanbevolen 1200×630 px — <b>Max {{ MAX_MB_IMAGE }} MB</b>.</p>
                                        <div v-if="ogUpload.info.value || ogUpload.preview.value" class="mt-3 rounded border p-3 text-sm"
                                             :class="ogUpload.tooLarge.value ? 'border-red-300 bg-red-50 text-red-800 dark:bg-red-900/20' : 'border-slate-200 bg-white dark:bg-zinc-900'">
                                            <div class="flex justify-between gap-4">
                                                <div class="font-medium truncate">{{ ogUpload.info.value?.name ?? 'Bestaand bestand' }}</div>
                                                <div>
                                                    <span :class="ogUpload.tooLarge.value ?  'font-semibold':''">{{ ogUpload.sizeMB.value.toFixed(2) }} MB</span>
                                                    <span v-if="ogUpload.tooLarge.value"> — te groot</span>
                                                </div>
                                            </div>
                                            <div class="mt-1 text-xs opacity-80">
                                                {{ ogUpload.info.value?.type ?? 'Onbekend type' }}
                                                <span v-if="ogUpload.info.value?.w && ogUpload.info.value?.h">
                                                    • {{ ogUpload.info.value.w }}×{{ ogUpload.info.value.h }} px
                                                </span>
                                            </div>
                                            <Button type="button" variant="link" class="px-0 h-auto mt-2 text-red-600" @click="ogUpload.clear">
                                                <Trash2 class="mr-1 h-3.5 w-3.5" /> Verwijder afbeelding
                                            </Button>
                                        </div>
                                        <span v-if="form.errors.og_image" class="mt-1 text-sm text-red-500">{{ form.errors.og_image }}</span>
                                    </div>
                                </div>
                            </div>


                        </TabsContent>
                    </Tabs>

                    <Button type="button" size="lg" class="mt-6 w-full flex items-center justify-center gap-2"
                            :disabled="form.processing || !form.title || !form.content || coverUpload.tooLarge.value || ogUpload.tooLarge.value"
                            @click="submit">
                        <Loader2 v-if="form.processing" class="mr-2 h-5 w-5 animate-spin" />
                        {{ isEdit ? 'Bijwerken' : 'Opslaan' }}
                    </Button>
                </CardContent>
            </Card>
        </div>
    </AdminAppLayout>
</template>
