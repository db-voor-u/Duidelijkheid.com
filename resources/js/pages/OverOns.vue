<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3'
import { computed, ref, toRefs, onMounted } from 'vue'
import NavBarPublic from '@/components/NavBarPublic.vue'
import { useCodeCopy } from '@/composables/useCodeCopy'
import Footer from '@/components/Footer.vue'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Separator } from '@/components/ui/separator'
import { Badge } from '@/components/ui/badge'
import { ArrowRight, PlayCircle, Video, Film, FileDown, Image as ImageIcon, FileText, Link2, ChevronLeft, ChevronRight } from 'lucide-vue-next'
import MagicMenu from '@/components/MagicMenu.vue'
import ContactSection from '@/components/ContactSection.vue'
import CategoryList from '@/components/CategoryList.vue'

// -------- Types --------
type OverOnsPage = {
    title?: string
    content?: string
    image_path?: string | null
    meta_title?: string | null
    meta_description?: string | null
    seo_image_path?: string | null
    canonical_url?: string | null
    robots_index?: boolean
    robots_follow?: boolean
    published?: boolean
}

type PostCard = {
    title: string
    slug: string
    excerpt?: string | null
    cover_image_path?: string | null
    published_at?: string | null
    media_type?: 'image' | 'youtube' | 'upload' | string
    video_path?: string | null
    download_file_path?: string | null
    category?: { name: string; color: string } | null
}

type LinkItem = { url: string | null; label: string; active: boolean }
type Paginated<T> = { data: T[]; links: LinkItem[]; meta?: Record<string, any> }

const props = withDefaults(defineProps<{
    overOns?: OverOnsPage;
    posts?: PostCard[] | Paginated<PostCard>;
    overOnsCategories?: any[];
    otherCategories?: any[];
}>(), {
    overOns: () => ({ title: 'Over Ons', image_path: '', content: '' }),
    posts: () => [],
    overOnsCategories: () => [],
    otherCategories: () => [],
})
const { overOns } = toRefs(props)

// Content Ref voor copy buttons
const contentRef = ref<HTMLElement | null>(null)
useCodeCopy(contentRef)

const showForm = ref(false);

// -------- Computed Properties --------

const stripTags = (s: string) => s.replace(/<[^>]*>/g, '')
const fallbackText =
    'Dit is de Over Ons pagina. Vul deze in via de beheeromgeving om de organisatie te introduceren.'

const pageTitle = computed(() => overOns.value.meta_title?.trim() || overOns.value.title || 'Over Ons')
const metaDescription = computed(() => {
    const raw =
        overOns.value.meta_description?.trim() || (overOns.value.content ? stripTags(overOns.value.content) : fallbackText)
    return raw.replace(/\s+/g, ' ').slice(0, 160)
})
const robots = computed(() => {
    const index = overOns.value.robots_index ?? true
    const follow = overOns.value.robots_follow ?? true
    return `${index ? 'index' : 'noindex'},${follow ? 'follow' : 'nofollow'}`
})

const imgPath = (p?: string | null) => (p ? (p.startsWith('http') || p.startsWith('/') ? p : `/storage/${p}`) : '')
const heroImage = computed(() => imgPath(overOns.value.image_path))
const ogImage = computed(() => imgPath(overOns.value.seo_image_path) || heroImage.value)
const hasImage = computed(() => !!heroImage.value)
const imgLoaded = ref(false)

const absOg = ref('')
onMounted(() => {
    if (ogImage.value) absOg.value = new URL(ogImage.value, window.location.origin).toString()
})

// Toon de pagina alleen als deze is gepubliceerd
const isPublished = computed(() => overOns.value.published !== false);

// -------- Blog Helpers --------
const fmtDate = (iso?: string | null) => {
    if (!iso) return '—'
    const s = iso.includes(' ') ? iso.replace(' ', 'T') : iso
    const d = new Date(s)
    if (Number.isNaN(+d)) return '—'
    return new Intl.DateTimeFormat('nl-NL', { 
        day: '2-digit', month: 'short', year: 'numeric',
        hour: '2-digit', minute: '2-digit'
    }).format(d)
}

const rawPosts = computed(() => {
    if (!props.posts) return []
    if (Array.isArray(props.posts)) return props.posts
    return (props.posts as Paginated<PostCard>).data
})

const cards = computed(() =>
    rawPosts.value.map((p) => ({
        ...p,
        img: imgPath(p.cover_image_path),
    })),
)

// -------- Pagination helpers --------
const decodeEntities = (html?: string) => {
    if (!html) return ''
    const el = document.createElement('textarea')
    el. innerHTML = html
    return el.value
}

const pageLinks = computed(() => {
    if (!props.posts || Array.isArray(props.posts)) return []
    return (props.posts as Paginated<PostCard>).links ?? []
})

const normLinks = computed(() => {
    const links = pageLinks.value.map((l) => {
        const raw = decodeEntities(l.label)
        const low = raw.toLowerCase()
        const type =
            low.includes('previous') || raw.includes('«')
                ? 'prev'
                : low.includes('next') || raw.includes('»')
                    ? 'next'
                    : 'page'
        return { ...l, _type: type, _label: raw }
    })

    // Only show Previous/Next arrows if there are more than 3 pages
    const pageCount = links.filter(l => l._type === 'page').length
    if (pageCount <= 3) {
        return links.filter(l => l._type === 'page')
    }

    return links
})

const getYoutubeId = (url?: string | null) => {
    if (!url) return null
    const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|&v=)([^#&?]*).*/
    const match = url.match(regExp)
    return (match && match[2].length === 11) ? match[2] : null
}
const getYoutubeThumbnail = (url?: string | null) => {
    const id = getYoutubeId(url)
    return id ? `https://img.youtube.com/vi/${id}/hqdefault.jpg` : null
}

const currentPage = computed(() => {
    const p = props.posts as any;
    return p?.meta?.current_page ?? p?.current_page ?? 1;
})

const articlesTitle = computed(() => currentPage.value > 1 ? 'Meer artikelen zien' : 'Alle artikelen')

// Get selected category from URL
const pageUrl = usePage()
const selectedCategorySlug = computed(() => {
    const url = pageUrl.url as string
    const urlWithoutHash = url.split('#')[0]
    const match = urlWithoutHash.match(/[?&]category=([^&]+)/)
    return match ? decodeURIComponent(match[1]) : null
})

const selectedCategory = computed(() => {
    if (!selectedCategorySlug.value) return null
    // Check in overOns categories first, then other categories
    const allCats = [...(props.overOnsCategories || []), ...(props.otherCategories || [])]
    return allCats.find(c => c.slug === selectedCategorySlug.value) || null
})



</script>

<template>
    <Head>
        <title>{{ pageTitle }}</title>
        <meta name="description" :content="metaDescription" />
        <meta name="robots" :content="robots" />
        <meta property="og:title" :content="pageTitle" />
        <meta property="og:description" :content="metaDescription" />
        <meta v-if="absOg" property="og:image" :content="absOg" />
        <link v-if="overOns?.canonical_url" rel="canonical" :href="overOns.canonical_url!" />
    </Head>

    <NavBarPublic />

    <div class="mx-auto min-h-screen max-w-4xl px-4 py-10 sm:px-6 lg:px-8">
        <div v-if="!isPublished" class="text-center p-12 text-muted-foreground">
            Deze pagina is momenteel niet gepubliceerd.
        </div>

        <div v-else>
            <!-- Header -->
            <section class="w-full">
                <h1 class="mb-6 text-left text-3xl font-extrabold tracking-tight sm:text-4xl">
                    {{ pageTitle }}
                </h1>

                <!-- Skeleton terwijl image laadt -->
                <div
                    v-if="hasImage && !imgLoaded"
                    class="w-full aspect-video animate-pulse rounded-xl bg-muted mb-6"
                />

                <!-- Afbeelding: Natural flow (w-full h-auto) -->
                <figure
                    v-if="hasImage"
                    class="w-full max-w-2xl overflow-hidden rounded-xl mb-10"
                >
                    <img
                        :src="heroImage"
                        :alt="pageTitle"
                        loading="lazy"
                        class="w-full h-auto"
                        :class="imgLoaded ? 'opacity-100' : 'opacity-0'"
                        @load="imgLoaded = true"
                    />
                </figure>

                <!-- Tekst: smalle kolom, links uitgelijnd -->
                <article class="max-w-prose">
                    <div
                        v-if="overOns?.content"
                        ref="contentRef"
                        v-html="overOns.content"
                        class="prose prose-zinc leading-relaxed prose-headings:font-semibold prose-headings:mb-3 prose-headings:mt-6 prose-headings:first:mt-0 prose-p:first:mt-0 prose-a:underline prose-a:underline-offset-4 prose-img:rounded-xl dark:prose-invert"
                    />
                    <p v-else class="text-zinc-600 leading-relaxed dark:text-zinc-400">{{ fallbackText }}</p>
                </article>
            </section>
                <Separator class="mx-auto mt-8 bg-primary dark:bg-primary" />
        
        <!-- Onderwerpen Sectie -->
        <section id="onderwerpen" class="scroll-mt-20">
            <!-- 1. Specifieke Over Ons Onderwerpen -->
            <CategoryList 
                v-if="overOnsCategories?.length" 
                :categories="overOnsCategories" 
                baseUrl="/over-ons" 
                title="Over Ons Onderwerpen" 
            />

            <!-- 2. Andere Onderwerpen (Gescheiden) -->
            <CategoryList 
                v-if="otherCategories?.length" 
                :categories="otherCategories" 
                baseUrl="/blog" 
                title="Andere Onderwerpen" 
            />
        </section>

        <Separator class="mx-auto mt-8 bg-primary dark:bg-primary" />
             

            <!-- Blogs Section -->
            <section class="w-full scroll-mt-24 mt-10" id="content">
            <div class="mb-6 flex items-center gap-3">
                <h2 class="text-xl font-bold tracking-tight">{{ articlesTitle }}</h2>
                <!-- Selected Category Badge -->
                <div v-if="selectedCategory" class="inline-flex items-center gap-1.5 rounded-full bg-zinc-100 px-3 py-1 text-sm font-medium text-zinc-800 dark:bg-zinc-800 dark:text-zinc-200">
                    <span :class="['w-2 h-2 rounded-full', selectedCategory.color]"></span>
                    {{ selectedCategory.name }}
                </div>
            </div>
                
                <div v-if="cards.length" class="flex flex-col gap-8">
                    <!-- Cards Loop -->
                    <Card
                        v-for="p in cards"
                        :key="p.slug"
                        class="mx-auto w-full border border-zinc-200 transition hover:-translate-y-0.5 hover:shadow-lg dark:border-zinc-800 h-full flex flex-col md:flex-row !overflow-visible"
                    >
                        <!-- Media Preview -->
                        <div class="relative w-full md:w-[40%] md:shrink-0 aspect-[3/2] bg-muted group overflow-hidden rounded-t-lg md:rounded-tr-none md:rounded-l-lg">
                             <!-- 1. Custom Image -->
                            <img v-if="p.img" :src="p.img" :alt="p.title" class="h-full w-full object-cover transition-transform duration-500 hover:scale-105" />

                            <!-- 2. YouTube Thumbnail -->
                            <img
                                v-else-if="p.media_type === 'youtube' && getYoutubeThumbnail(p.video_path)"
                                :src="getYoutubeThumbnail(p.video_path)!"
                                :alt="p.title"
                                class="h-full w-full object-cover transition-transform duration-500 hover:scale-105"
                            />

                            <!-- 3a. Uploaded Video Thumbnail -->
                            <div 
                                v-else-if="p.media_type === 'upload' && p.video_path" 
                                class="relative h-full w-full bg-black"
                            >
                                <video
                                    :src="imgPath(p.video_path)"
                                    class="h-full w-full object-cover opacity-90"
                                    muted
                                    playsinline
                                    loop
                                    onmouseover="this.play()"
                                    onmouseout="this.pause()"
                                />
                            </div>

                            <!-- 3b. Fallback -->
                            <div v-else class="flex h-full w-full items-center justify-center bg-zinc-100 dark:bg-zinc-800 text-muted-foreground">
                                <span class="text-xs">Geen media</span>
                            </div>

                            <!-- Play Overlay for Videos -->
                            <div
                                v-if="p.media_type === 'youtube' || p.media_type === 'upload'"
                                class="absolute inset-0 flex items-center justify-center bg-black/10 transition-colors duration-300 group-hover:bg-black/30 pointer-events-none"
                            >
                                <div class="relative flex h-16 w-16 items-center justify-center rounded-full bg-white/20 shadow-2xl backdrop-blur-md ring-1 ring-white/30">
                                    <PlayCircle class="h-8 w-8 text-white drop-shadow-lg" />
                                </div>
                            </div>
                            


                            <!-- Date Badge -->

                        </div>

                    <div class="flex flex-col flex-grow">
                        <CardHeader class="pb-3 pt-5">
                            <div class="flex items-start justify-between gap-2">
                                <CardTitle class="line-clamp-2 text-xl leading-tight min-h-[3.2rem]">{{ p.title }}</CardTitle>
                                    <!-- Magic Menu -->
                                    <div class="relative ml-auto shrink-0 z-30">
                                        <MagicMenu 
                                            :published-at="p.published_at"
                                            :media-type="p.media_type"
                                            :download-file-path="(p as any).download_file_path"
                                        />
                                    </div>
                            </div>
                        </CardHeader>

                        <CardContent class="space-y-4 pb-6 flex-grow flex flex-col justify-between">
                            <p class="line-clamp-3 text-sm leading-relaxed text-muted-foreground">{{ p.excerpt }}</p>
                            
                            <div>
                                <Separator class="my-4 bg-primary dark:bg-primary" />
                                
                                <div class="flex items-center justify-between pt-2 gap-2">
                                    <!-- Category Badge (Left) -->
                                    <div v-if="p.category" class="inline-flex items-center gap-1.5 rounded-md bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-800 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700 transition">
                                        <span :class="['w-1.5 h-1.5 rounded-full', p.category.color]"></span>
                                        {{ p.category.name }}
                                    </div>
                                    <div v-else></div>

                                    <!-- Arrow Button (Right) -->
                                    <Link :href="`/over-ons-blog/${p.slug}`">
                                        <Button size="icon" class="rounded-full">
                                            <ArrowRight class="h-4 w-4" />
                                        </Button>
                                    </Link>
                                </div>
                            </div>
                        </CardContent>
                    </div>
                    </Card>
                </div>
                <div v-else class="text-muted-foreground italic">
                    Nog geen items gevonden.
                </div>

                <!-- Paginatie -->
                <nav
                    v-if="normLinks.length"
                    class="mt-10 flex w-full justify-center"
                    role="navigation"
                    aria-label="Paginatie"
                >
                    <ul class="inline-flex items-center gap-2">
                        <li v-for="(l,i) in normLinks" :key="i">
                            <!-- Vorige -->
                            <Link v-if="l._type === 'prev'" :href="l.url ? `${l.url}#content` : '#'" :aria-disabled="!l.url">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    class="gap-1"
                                    :class="!l.url ? 'pointer-events-none opacity-50' : ''"
                                >
                                    <ChevronLeft class="h-4 w-4" />
                                    <span class="hidden sm:inline">Vorige</span>
                                </Button>
                            </Link>

                            <!-- Pagina -->
                            <Link v-else-if="l._type === 'page'" :href="l.url ? `${l.url}#content` : '#'" :aria-current="l.active ?  'page' : undefined">
                                <Button
                                    size="sm"
                                    :variant="l.active ?  'default' : 'outline'"
                                    class="min-w-9"
                                    :class="! l.url && !l.active ? 'pointer-events-none opacity-50' : ''"
                                >
                                    {{ l._label }}
                                </Button>
                            </Link>

                            <!-- Volgende -->
                            <Link v-else :href="l.url ? `${l.url}#content` : '#'" :aria-disabled="!l.url">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    class="gap-1"
                                    :class="!l. url ? 'pointer-events-none opacity-50' : ''"
                                >
                                    <span class="hidden sm:inline">Volgende</span>
                                    <ChevronRight class="h-4 w-4" />
                                </Button>
                            </Link>
                        </li>
                    </ul>
                </nav>
            </section>
            
            <Separator class="mx-auto mt-8 bg-primary dark:bg-primary" />

              <!-- Contact Section (MOET onderaan) -->
            <section class="mt-20 scroll-mt-24 mb-20" id="contact">
                 <ContactSection />
            </section>

         
        </div>
    </div>
    <Footer />
</template>

<style scoped>

</style>
