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

type InnovatiePage = {
    id?: number
    title?: string | null
    content?: string | null
    image_path?: string | null
    published?: boolean
    meta_title?: string | null
    meta_description?: string | null
    seo_image_path?: string | null
    canonical_url?: string | null
    robots_index?: boolean
    robots_follow?: boolean
}
const props = defineProps<{ page?: InnovatiePage }>()
const isEdit = computed(() => !!props.page?.id)

const safe = computed(() => ({
    title: props.page?.title ?? '',
    content: props.page?.content ?? '',
    image_path: props.page?.image_path ?? '',
    published: props.page?.published ?? true,
    meta_title: props.page?.meta_title ?? '',
    meta_description: props.page?.meta_description ?? '',
    seo_image_path: props.page?.seo_image_path ?? '',
    canonical_url: props.page?.canonical_url ?? '',
    robots_index: props.page?.robots_index ?? true,
    robots_follow: props.page?.robots_follow ?? true,
}))

const form = useForm({
    title: safe.value.title,
    content: safe.value.content,
    image: null as File | null,
    published: safe.value.published,
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
const previewUrl   = computed(()=> form.canonical_url?.trim() || (typeof window!=='undefined'?window.location.origin+'/innovatie':'https://voorbeeld.nl/innovatie'))

function submit(){
    ok.value=''; err.value=''
    if(!form.title?.trim()){ activeTab.value='content'; err.value='Titel is verplicht.'; return }
    if(heroUpload.tooLarge.value || seoUpload.tooLarge.value){ activeTab.value=heroUpload.tooLarge.value?'content':'seo'; err.value=`Afbeelding is te groot (max ${MAX_MB} MB).`; return }

    form.transform(d=>({ ...d, _method:'put', published: d.published ? 1 : 0 }))
        .post('/hoofdbeheerder/pages/innovatie', {
            forceFormData:true, preserveScroll:true,
            onSuccess:()=>{
                err.value='';
                ok.value='✅ Opgeslagen';
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
    <Head title="Innovatie hero inhoud" />
    <AdminAppLayout :breadcrumbs="[
    { title:'Dashboard', href:'/hoofdbeheerder/dashboard' },
    { title:'Pagina\'s', href:'#' },
    { title:'Innovatie hero inhoud', href:'/hoofdbeheerder/pages/innovatie' },
  ]">
        <div class="mx-auto max-w-5xl mt-8 space-y-6">
            <Card >
                <CardHeader><CardTitle class="text-2xl font-bold">💡 Innovatie hero inhoud</CardTitle></CardHeader>
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
                                    <Input id="title" v-model="form.title" placeholder="bv. Gedreven door Innovatie" class="mt-1"/>
                                    <span v-if="form.errors.title" class="text-sm text-red-500">{{ form.errors.title }}</span>
                                </div>

                                <!-- Afbeelding UPLOAD SECTIE (Hero) -->
                                <div>
                                    <Label for="image" class="font-semibold flex items-center gap-2"><Camera class="h-4 w-4"/> Hero-afbeelding</Label>
                                    <div class="mt-4 flex flex-col gap-5">
                                        <div class="w-full max-w-4xl">
                                            <img v-if="heroUpload.preview.value" :src="heroUpload.preview.value" alt="Hero preview" class="w-full h-64 object-contain bg-zinc-100 dark:bg-zinc-900 rounded-lg shadow-sm ring-1 ring-border"/>
                                            <div v-else class="flex h-32 w-full items-center justify-center rounded-lg border border-dashed text-xs text-muted-foreground bg-muted/10">Geen preview</div>
                                        </div>

                                        <div>
                                            <Input id="image" type="file" accept="image/*" @change="heroUpload.handleChange" :disabled="form.processing"/>
                                            <p class="mt-2 text-xs text-muted-foreground">Advies 1600×600 px — <b>max {{ MAX_MB }} MB</b>.</p>

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
                                        placeholder="Schrijf de introductietekst voor de Innovatie pagina…"
                                        height="260px"
                                    />
                                    <span v-if="form.errors.content" class="text-sm text-red-500">{{ form.errors.content }}</span>
                                </div>

                                <div class="flex items-center gap-2 pt-4">
                                    <Checkbox id="published" v-model:checked="form.published" />
                                    <Label for="published">Gepubliceerd</Label>
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
                                            <li><b>SEO titel</b>: korte, duidelijke titel (± 50–60 tekens).</li>
                                            <li><b>Meta description</b>: 1–2 zinnen (max. ~160 tekens).</li>
                                            <li><b>Canonical URL</b>: "officiële" link van deze pagina.</li>
                                        </ul>
                                    </AlertDescription>
                                </Alert>

                                <div>
                                    <Label for="meta_title" class="font-semibold">SEO-titel</Label>
                                    <Input id="meta_title" v-model="form.meta_title" placeholder="bv. Innovatie – Duidelijkheid.com" class="mt-1"/>
                                    <span v-if="form.errors.meta_title" class="text-sm text-red-500">{{ form.errors.meta_title }}</span>
                                </div>

                                <div>
                                    <Label for="meta_description" class="font-semibold">Meta description</Label>
                                    <Textarea id="meta_description" v-model="form.meta_description" rows="3" class="mt-1" placeholder="Korte samenvatting…" />
                                    <span v-if="form.errors.meta_description" class="text-sm text-red-500">{{ form.errors.meta_description }}</span>
                                </div>

                                <div>
                                    <Label for="canonical_url" class="font-semibold">Canonical URL</Label>
                                    <Input id="canonical_url" v-model="form.canonical_url" type="url" placeholder="https://jouwsite.nl/innovatie" class="mt-1"/>
                                    <span v-if="form.errors.canonical_url" class="text-sm text-red-500">{{ form.errors.canonical_url }}</span>
                                </div>

                                <div class="grid gap-6 sm:grid-cols-2">
                                    <div class="flex flex-col items-start gap-1.5 min-h-[6rem]">
                                        <div class="flex items-center gap-2"><Checkbox id="robots_index" v-model:checked="form.robots_index"/><Label for="robots_index">Indexeren toestaan</Label></div>
                                        <p class="text-xs text-muted-foreground">Zet uit voor noindex.</p>
                                    </div>
                                    <div class="flex flex-col items-start gap-1.5 min-h-[6rem]">
                                        <div class="flex items-center gap-2"><Checkbox id="robots_follow" v-model:checked="form.robots_follow"/><Label for="robots_follow">Links volgen</Label></div>
                                        <p class="text-xs text-muted-foreground">Zet uit voor nofollow.</p>
                                    </div>
                                </div>

                                <div>
                                    <Label for="seo_image" class="font-semibold flex items-center gap-2"><Camera class="h-4 w-4"/> Open-Graph afbeelding</Label>
                                    <div class="mt-4 flex flex-col gap-5">
                                        <div class="w-full">
                                            <img v-if="seoUpload.preview.value" :src="seoUpload.preview.value" alt="SEO afbeelding preview" class="h-60 w-full rounded-lg object-cover shadow-sm ring-1 ring-border"/>
                                            <div v-else class="flex h-32 w-full items-center justify-center rounded-lg border border-dashed text-xs text-muted-foreground bg-muted/10">Geen preview aanwezig</div>
                                        </div>

                                        <div>
                                            <Input id="seo_image" type="file" accept="image/*" @change="seoUpload.handleChange" :disabled="form.processing"/>
                                            <p class="mt-2 text-xs text-muted-foreground">Advies 1200×630 px — <b>max {{ MAX_MB }} MB</b>.</p>

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
