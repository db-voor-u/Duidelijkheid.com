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
import RichTextEditor from '@/components/RichTextEditor.vue'

type BlogPage = {
    id?: number
    title?: string | null
    content?: string | null
    image_path?: string | null
    meta_title?: string | null
    meta_description?: string | null
    seo_image_path?: string | null
    canonical_url?: string | null
    robots_index?: boolean
    robots_follow?: boolean
}
const props = defineProps<{ blogPage?: BlogPage }>()
const isEdit = computed(() => !!props.blogPage?.id)

const safe = computed(() => ({
    title: props.blogPage?.title ?? '',
    content: props.blogPage?.content ?? '',
    image_path: props.blogPage?.image_path ?? '',
    meta_title: props.blogPage?.meta_title ?? '',
    meta_description: props.blogPage?.meta_description ?? '',
    seo_image_path: props.blogPage?.seo_image_path ?? '',
    canonical_url: props.blogPage?.canonical_url ?? '',
    robots_index: props.blogPage?.robots_index ?? true,
    robots_follow: props.blogPage?.robots_follow ?? true,
}))

const form = useForm({
    title: safe.value.title,
    content: safe.value.content,
    image: null as File | null,
    meta_title: safe.value.meta_title,
    meta_description: safe.value.meta_description,
    seo_image: null as File | null,
    canonical_url: safe.value.canonical_url,
    robots_index: safe.value.robots_index,
    robots_follow: safe.value.robots_follow,
})

const activeTab = ref<'content'|'seo'>('content')
const ok = ref(''); const err = ref('')

/* ------- Upload Logic Helper (Consistentie) ------- */
const MAX_MB = 12 // FIX: Van 2MB naar 12MB

function useImageUpload(
    initialPath: string | null,
    formKey: 'image' | 'seo_image'
) {
    const preview = ref<string | null>(initialPath ? `/storage/${initialPath}` : null)
    const info = ref<{ name: string; sizeBytes: number; type: string; width?: number; height?: number } | null>(null)
    const sizeMB = computed(() => (info.value ? +(info.value.sizeBytes / 1024 / 1024) : 0)) // Computed Ref<number>
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

const heroUpload = useImageUpload(safe.value.image_path, 'image')
const seoUpload = useImageUpload(safe.value.seo_image_path, 'seo_image')

/* ------- SEO preview ------- */
const stripTags = (s:string)=>s.replace(/<[^>]*>/g,'')
const previewTitle = computed(()=> (form.meta_title?.trim() || form.title || '').slice(0,65))
const previewDesc  = computed(()=> (form.meta_description?.trim() || stripTags(form.content||'')).replace(/\s+/g,' ').slice(0,160))
const previewUrl   = computed(()=> form.canonical_url?.trim() || (typeof window!=='undefined'?window.location.origin+'/blog':'https://voorbeeld.nl/blog'))

function submit(){
    ok.value=''; err.value=''
    if(!form.title?.trim()){ activeTab.value='content'; err.value='Titel is verplicht.'; return }
    if(heroUpload.tooLarge.value || seoUpload.tooLarge.value){ activeTab.value=heroUpload.tooLarge.value?'content':'seo'; err.value=`Afbeelding is te groot (max ${MAX_MB} MB).`; return }

    form.transform(d=>({ ...d, _method:'put' }))
        .post('/hoofdbeheerder/pages/blog', {
            forceFormData:true, preserveScroll:true,
            onSuccess:()=>{
                err.value='';
                ok.value='✅ Opgeslagen';
                // Reset de previews na succesvolle upload/save
                if (form.image) {
                    heroUpload.preview.value = '/storage/' + (form.image as File).name
                    heroUpload.info.value = null
                }
                if (form.seo_image) {
                    seoUpload.preview.value = '/storage/' + (form.seo_image as File).name
                    seoUpload.info.value = null
                }
                setTimeout(()=>ok.value='',1800)
            },
            onError:()=>{ err.value='Controleer de invoer.'; activeTab.value = Object.keys(form.errors).some(k=>['title','content','image'].includes(k)) ? 'content':'seo' }
        })
}

</script>

<template>
    <Head title="Inhoud blogpagina" />
    <AdminAppLayout :breadcrumbs="[
    { title:'Dashboard', href:'/hoofdbeheerder/dashboard' },
    { title:'Inhoud blog pagina', href:'/hoofdbeheerder/pages/blog' },
  ]">
        <!-- AANGEPAST: max-w-3xl naar max-w-5xl voor meer ruimte -->
        <div class="mx-auto max-w-5xl mt-8 space-y-6">
            <Card >
                <CardHeader><CardTitle class="text-2xl font-bold">📝 Blog hero inhoud</CardTitle></CardHeader>
                <CardContent>
                    <form @submit.prevent="submit">
                        <div v-if="ok"  class="mb-4 flex items-center gap-2 rounded-lg border border-emerald-300 bg-emerald-50 p-2 text-emerald-800"><CheckCircle2 class="h-5 w-5"/><span>{{ok}}</span></div>
                        <div v-if="err" class="mb-4 flex items-center gap-2 rounded-lg border border-red-300 bg-red-50 p-2 text-red-800"><XCircle class="h-5 w-5"/><span>{{err}}</span></div>

                        <Tabs v-model="activeTab" class="w-full">
                            <TabsList class="grid w-full grid-cols-2">
                                <TabsTrigger value="content">Inhoud</TabsTrigger>
                                <TabsTrigger value="seo">SEO</TabsTrigger>
                            </TabsList>

                            <!-- INHOUD -->
                            <TabsContent value="content" class="mt-6 space-y-8">
                                <div>
                                    <Label for="title" class="font-semibold">Titel</Label>
                                    <Input id="title" v-model="form.title" placeholder="bv. Blog & nieuws" class="mt-1"/>
                                    <span v-if="form.errors.title" class="text-sm text-red-500">{{ form.errors.title }}</span>
                                </div>

                                <!-- Afbeelding UPLOAD SECTIE (Hero) -->
                                <div>
                                    <Label for="image" class="font-semibold flex items-center gap-2"><Camera class="h-4 w-4"/> Hero-afbeelding</Label>
                                    <div class="mt-4 flex flex-col gap-5">
                                        <!-- Preview BOVEN -->
                                    <div class="w-full max-w-4xl">
                                        <img v-if="heroUpload.preview.value" :src="heroUpload.preview.value" alt="Hero preview" class="w-full h-64 object-contain bg-zinc-100 dark:bg-zinc-900 rounded-lg shadow-sm ring-1 ring-border"/>
                                        <div v-else class="flex h-32 w-full items-center justify-center rounded-lg border border-dashed text-xs text-muted-foreground bg-muted/10">Geen preview</div>
                                    </div>

                                        <!-- Input ONDER -->
                                        <div>
                                            <Input id="image" type="file" accept="image/*" @change="heroUpload.handleChange" :disabled="form.processing"/>
                                            <p class="mt-2 text-xs text-muted-foreground">Advies 1600×600 px — <b>max {{ MAX_MB }} MB</b>.</p>

                                            <!-- Bestandsinformatie/Foutmelding -->
                                            <div v-if="heroUpload.info.value || heroUpload.preview.value"
                                                 class="mt-3 rounded border p-3 text-sm"
                                                 :class="heroUpload.tooLarge.value?'border-red-300 bg-red-50 text-red-800':'border-slate-200 bg-white dark:bg-zinc-900'">

                                                <div class="flex justify-between gap-4">
                                                    <div class="font-medium truncate">{{ heroUpload.info.value?.name ?? 'Bestaand bestand' }}</div>
                                                    <div>
                                                        <span :class="heroUpload.tooLarge.value ? 'font-semibold' : ''">{{ heroUpload.sizeMB.value.toFixed(2) }} MB</span>
                                                        <span v-if="heroUpload.tooLarge.value"> — te groot</span>
                                                    </div>
                                                </div>
                                                <div class="mt-1 text-xs opacity-80">
                                                    {{ heroUpload.info.value?.type ?? 'Onbekend type' }}
                                                    <span v-if="heroUpload.info.value?.width && heroUpload.info.value?.height">• {{ heroUpload.info.value.width }}×{{ heroUpload.info.value.height }} px</span>
                                                </div>
                                                <Button type="button" variant="link" class="mt-2 h-auto px-0 text-red-600" @click="heroUpload.clear" :disabled="form.processing">
                                                    <Trash2 class="mr-1 h-3.5 w-3.5"/>Verwijderen
                                                </Button>
                                            </div>
                                            <span v-if="form.errors.image" class="mt-1 text-sm text-red-500">{{ form.errors.image }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <Label for="content" class="font-semibold">Introductietekst</Label>
                                    <RichTextEditor
                                        v-model="form.content"
                                        placeholder="Schrijf de introductietekst voor de Blog pagina…"
                                        height="260px"
                                    />
                                    <span v-if="form.errors.content" class="text-sm text-red-500">{{ form.errors.content }}</span>
                                </div>
                            </TabsContent>

                            <!-- SEO -->
                            <TabsContent value="seo" class="mt-6 space-y-8">
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

                                <div>
                                    <Label for="meta_title" class="font-semibold">SEO-titel</Label>
                                    <Input id="meta_title" v-model="form.meta_title" placeholder="bv. Blog & nieuws – Duidelijkheid.com" class="mt-1"/>
                                    <span v-if="form.errors.meta_title" class="text-sm text-red-500">{{ form.errors.meta_title }}</span>
                                </div>

                                <div>
                                    <Label for="meta_description" class="font-semibold">Meta description</Label>
                                    <Textarea id="meta_description" v-model="form.meta_description" rows="3" class="mt-1" placeholder="Korte samenvatting…" />
                                    <span v-if="form.errors.meta_description" class="text-sm text-red-500">{{ form.errors.meta_description }}</span>
                                </div>

                                <div>
                                    <Label for="canonical_url" class="font-semibold">Canonical URL</Label>
                                    <Input id="canonical_url" v-model="form.canonical_url" type="url" placeholder="https://jouwsite.nl/blog" class="mt-1"/>
                                    <span v-if="form.errors.canonical_url" class="text-sm text-red-500">{{ form.errors.canonical_url }}</span>
                                </div>

                                <!-- Robots -->
                                <div class="grid gap-6 sm:grid-cols-2">
                                    <!-- FIX: min-h-[6rem] toegevoegd om hoogte consistent te houden -->
                                    <div class="flex flex-col items-start gap-1.5 min-h-[6rem]">
                                        <div class="flex items-center gap-2"><Checkbox id="robots_index" v-model:checked="form.robots_index"/><Label for="robots_index">Indexeren toestaan</Label></div>
                                        <p class="text-xs text-muted-foreground">Zet uit om de pagina te verbergen in zoekmachines (noindex).</p>
                                    </div>
                                    <!-- FIX: min-h-[6rem] toegevoegd om hoogte consistent te houden -->
                                    <div class="flex flex-col items-start gap-1.5 min-h-[6rem]">
                                        <div class="flex items-center gap-2"><Checkbox id="robots_follow" v-model:checked="form.robots_follow"/><Label for="robots_follow">Links volgen</Label></div>
                                        <p class="text-xs text-muted-foreground">Zet uit als linkwaarde niet moet worden doorgegeven (nofollow).</p>
                                    </div>
                                </div>

                                <!-- Open Graph / Social afbeelding UPLOAD SECTIE -->
                                <div>
                                    <Label for="seo_image" class="font-semibold flex items-center gap-2"><Camera class="h-4 w-4"/> Open-Graph afbeelding</Label>
                                    <div class="mt-4 flex flex-col gap-5">
                                        <!-- Preview BOVEN -->
                                        <div class="w-full">
                                            <img v-if="seoUpload.preview.value" :src="seoUpload.preview.value" alt="SEO afbeelding preview" class="h-60 w-full rounded-lg object-cover shadow-sm ring-1 ring-border"/>
                                            <div v-else class="flex h-32 w-full items-center justify-center rounded-lg border border-dashed text-xs text-muted-foreground bg-muted/10">Geen preview aanwezig</div>
                                        </div>

                                        <!-- Input ONDER -->
                                        <div>
                                            <Input id="seo_image" type="file" accept="image/*" @change="seoUpload.handleChange" :disabled="form.processing"/>
                                            <p class="mt-2 text-xs text-muted-foreground">Advies 1200×630 px — <b>max {{ MAX_MB }} MB</b>.</p>

                                            <!-- Bestandsinformatie/Foutmelding -->
                                            <div v-if="seoUpload.info.value || seoUpload.preview.value"
                                                 class="mt-3 rounded border p-3 text-sm"
                                                 :class="seoUpload.tooLarge.value?'border-red-300 bg-red-50 text-red-800':'border-slate-200 bg-white dark:bg-zinc-900'">

                                                <div class="flex justify-between gap-4">
                                                    <div class="font-medium truncate">{{ seoUpload.info.value?.name ?? 'Bestaand bestand' }}</div>
                                                    <div>
                                                        <span :class="seoUpload.tooLarge.value ? 'font-semibold' : ''">{{ seoUpload.sizeMB.value.toFixed(2) }} MB</span>
                                                        <span v-if="seoUpload.tooLarge.value"> — te groot</span>
                                                    </div>
                                                </div>
                                                <div class="mt-1 text-xs opacity-80">
                                                    {{ seoUpload.info.value?.type ?? 'Onbekend type' }}
                                                    <span v-if="seoUpload.info.value?.width && seoUpload.info.value?.height">• {{ seoUpload.info.value.width }}×{{ seoUpload.info.value.height }} px</span>
                                                </div>
                                                <Button type="button" variant="link" class="mt-2 h-auto px-0 text-red-600" @click="seoUpload.clear" :disabled="form.processing">
                                                    <Trash2 class="mr-1 h-3.5 w-3.5"/>Verwijderen
                                                </Button>
                                            </div>
                                            <span v-if="form.errors.seo_image" class="mt-1 text-sm text-red-500">{{ form.errors.seo_image }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Google preview -->
                                <div class="rounded-lg border bg-white/70 p-4 dark:bg-zinc-900/50">
                                    <p class="mb-2 text-xs text-muted-foreground">Google-voorbeeld</p>
                                    <div class="space-y-1">
                                        <div class="truncate text-lg/6 font-medium text-[#1a0dab] dark:text-[#8ab4f8]">{{ previewTitle || 'Voorbeeldtitel' }}</div>
                                        <div class="truncate text-xs text-[#006621] dark:text-[#81c995]">{{ previewUrl }}</div>
                                        <div class="text-sm text-[#545454] dark:text-zinc-300">{{ previewDesc || 'Korte beschrijving van de pagina…' }}</div>
                                    </div>
                                </div>
                            </TabsContent>
                        </Tabs>

                        <Button class="mt-6 w-full justify-center" :disabled="form.processing || !form.title || heroUpload.tooLarge.value || seoUpload.tooLarge.value" @click="submit">
                            <Loader2 v-if="form.processing" class="mr-2 h-5 w-5 animate-spin"/> {{ isEdit?'Bijwerken':'Opslaan' }}
                        </Button>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AdminAppLayout>
</template>
