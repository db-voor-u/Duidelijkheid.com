<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3'
import { computed, ref, onMounted, toRefs } from 'vue'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { ChevronLeft, ChevronRight, PlayCircle, Video, Film, ArrowRight, FileDown, Image as ImageIcon, FileText, Link2 } from 'lucide-vue-next'
import NavBarPublic from '@/components/NavBarPublic.vue'
import Footer from '@/components/Footer.vue'
import { Separator } from '@/components/ui/separator';
import MagicMenu from '@/components/MagicMenu.vue'
import ContactSection from '@/components/ContactSection.vue'
import CategoryList from '@/components/CategoryList.vue'

/* -------- Types -------- */
type Post = {
    title: string
    slug: string
    excerpt?: string | null
    cover_image_path?: string | null
    seo_image_path?: string | null
    published_at?: string | null
    media_type?: 'image' | 'youtube' | 'upload' | string
    video_path?: string | null
    download_file_path?: string | null
    category?: {
        name: string
        color: string
        slug: string
    } | null
}

type BlogPage = {
    title?: string | null
    content?: string | null
    image_path?: string | null
    meta_title?: string | null
    meta_description?: string | null
    seo_image_path?: string | null
    canonical_url?: string | null
    robots_index?: boolean | null
    robots_follow?: boolean | null
}

type LinkItem = { url: string | null; label: string; active: boolean }
type Paginated<T> = { data: T[]; links: LinkItem[]; meta?: Record<string, any> }

/* -------- Props -------- */
const props = defineProps<{
    posts: Paginated<Post>
    categories?: { name: string; slug: string; color: string }[]
    blogPage?: BlogPage | null
}>()

const { categories } = toRefs(props);

/* -------- Helpers -------- */
const items     = computed(() => props.posts?.data  ??  [])
const pageLinks = computed(() => props.posts?.links ?? [])
const total     = computed(() => (props.posts?.meta?.total ?? items.value.length))

const stripTags = (s: string) => s.replace(/<[^>]*>/g, '')
const imgPath = (p?: string | null) =>
    p ? (p.startsWith('http') || p.startsWith('/') ? p : `/storage/${p}`) : ''

/* ====== SEO/meta op basis van blogPage ====== */
const pageTitle = computed(() =>
    (props.blogPage?. meta_title?.trim() || props.blogPage?.title || 'Blog')
)

const metaDescription = computed(() => {
    const raw =
        props.blogPage?.meta_description?.trim()
        || (props.blogPage?. content ? stripTags(props. blogPage.content) : '')
    return raw.replace(/\s+/g, ' ').slice(0, 160)
})

const robots = computed(() => {
    const index  = props.blogPage?.robots_index ?? true
    const follow = props.blogPage?. robots_follow ?? true
    return `${index ? 'index' : 'noindex'},${follow ? 'follow' : 'nofollow'}`
})

const heroImage = computed(() => imgPath(props.blogPage?.image_path))
const ogImage   = computed(() => imgPath(props.blogPage?.seo_image_path) || heroImage.value)
const hasHero   = computed(() => !!heroImage.value)
const imgLoaded = ref(false)

const absOg = ref('')
onMounted(() => {
    if (ogImage.value) {
        absOg.value = new URL(ogImage. value, window.location.origin).toString()
    }
})

const fmtDate = (iso?: string | null) => {
    if (!iso) return '—'
    const s = iso.includes(' ') ? iso. replace(' ', 'T') : iso
    const d = new Date(s)
    if (Number.isNaN(+d)) return '—'
    return new Intl.DateTimeFormat('nl-NL', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit', minute: '2-digit'
    }).format(d)
}

/* -------- Paginatie labels → prev/page/next -------- */
const decodeEntities = (html?: string) => {
    if (!html) return ''
    const el = document.createElement('textarea')
    el. innerHTML = html
    return el.value
}

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

/* -------- Video / YouTube helpers -------- */
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
    const p = props.posts as any
    return p?.meta?.current_page ?? p?.current_page ?? 1
})

// Get selected category from URL
const page = usePage()
const selectedCategorySlug = computed(() => {
    const url = page.url as string
    // Remove hash fragment before matching
    const urlWithoutHash = url.split('#')[0]
    const match = urlWithoutHash.match(/[?&]category=([^&]+)/)
    return match ? decodeURIComponent(match[1]) : null
})

const selectedCategory = computed(() => {
    if (!selectedCategorySlug.value || !props.categories) return null
    return props.categories.find(c => c.slug === selectedCategorySlug.value) || null
})

const articlesTitle = computed(() => {
    if (currentPage.value > 1) return 'Meer artikelen zien'
    return 'Alle artikelen'
})



</script>

<template>
    <!-- SEO -->
    <Head>
        <title>{{ pageTitle }}</title>
        <meta name="description" :content="metaDescription" />
        <meta name="robots" :content="robots" />
        <meta property="og:title" :content="pageTitle" />
        <meta property="og:description" :content="metaDescription" />
        <meta v-if="absOg" property="og:image" :content="absOg" />
        <link v-if="blogPage?.canonical_url" rel="canonical" :href="blogPage?.canonical_url || ''" />
    </Head>

    <NavBarPublic />

    <div class="mx-auto min-h-screen max-w-4xl px-4 py-10 sm:px-6 lg:px-8">
        <!-- Header -->
        <section class="w-full">
            <h1 class="mb-6 text-left text-3xl font-extrabold tracking-tight sm:text-4xl">
                {{ pageTitle }}
            </h1>

            <!-- Skeleton terwijl image laadt -->
            <div
                v-if="hasHero && !imgLoaded"
                class="w-full aspect-video animate-pulse rounded-xl bg-muted mb-6"
            />

            <!-- Hero afbeelding: Natural flow (w-full h-auto) -->
            <figure
                v-if="hasHero"
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

            <!-- Intro-tekst -->
            <article class="prose prose-zinc max-w-prose leading-relaxed prose-headings:font-semibold prose-headings:mb-3 prose-headings:mt-6 prose-headings:first:mt-0 prose-p:first:mt-0 prose-a:underline prose-a:underline-offset-4 prose-img:rounded-xl dark:prose-invert">
                <div
                    v-if="blogPage?.content"
                    v-html="blogPage.content"
                />
            </article>
        </section>

        <section id="onderwerpen" class="scroll-mt-20">
            <Separator class="mx-auto mt-8 bg-primary dark:bg-primary" />
            
            <CategoryList :categories="categories" />

            <Separator class="mx-auto mt-8 bg-primary dark:bg-primary" />
        </section>


        <!-- Cards -->
        <section class="mt-8 scroll-mt-24" id="artikelen">
            <div class="mb-6 flex items-center gap-3">
                <h2 class="text-xl font-bold tracking-tight">{{ articlesTitle }}</h2>
                <!-- Selected Category Badge -->
                <div v-if="selectedCategory" class="inline-flex items-center gap-1.5 rounded-full bg-zinc-100 px-3 py-1 text-sm font-medium text-zinc-800 dark:bg-zinc-800 dark:text-zinc-200">
                    <span :class="['w-2 h-2 rounded-full', selectedCategory.color]"></span>
                    {{ selectedCategory.name }}
                </div>
            </div>

            <div v-if="items.length" class="flex flex-col gap-8">
                <Card v-for="p in items" :key="p.slug" class="mx-auto w-full border border-zinc-200 transition hover:-translate-y-0.5 hover:shadow-lg dark:border-zinc-800 h-full flex flex-col md:flex-row">
                    <div class="relative w-full md:w-[40%] md:shrink-0 aspect-[3/2] bg-muted overflow-hidden rounded-t-lg md:rounded-tr-none md:rounded-l-lg">
                        <!-- 1. Custom Image (Cover or SEO) -->
                        <img
                            v-if="p.cover_image_path || p.seo_image_path"
                            :src="imgPath(p.cover_image_path) || imgPath(p.seo_image_path)"
                            :alt="p.title"
                            class="h-full w-full object-cover transition-transform duration-500 hover:scale-105"
                            loading="lazy"
                        />

                        <!-- 2. YouTube Thumbnail (als geen custom image, maar wel YouTube link) -->
                        <img
                            v-else-if="p.media_type === 'youtube' && getYoutubeThumbnail(p.video_path)"
                            :src="getYoutubeThumbnail(p.video_path)!"
                            :alt="p.title"
                            class="h-full w-full object-cover transition-transform duration-500 hover:scale-105"
                            loading="lazy"
                        />

                        <!-- 3a. Uploaded Video Thumbnail (render video content) -->
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

                        <!-- 3b. Fallback / Placeholder (Modern Glow Design) - ALleen als er ECHT niets is -->
                        <div v-else class="group relative flex h-full w-full flex-col items-center justify-center overflow-hidden bg-zinc-950">
                            <!-- Glowing Orbs -->
                            <div class="absolute -left-10 -top-10 h-40 w-40 rounded-full bg-indigo-500/20 blur-3xl transition-all duration-700 group-hover:scale-150 group-hover:bg-indigo-500/30" />
                            <div class="absolute -bottom-10 -right-10 h-40 w-40 rounded-full bg-purple-500/20 blur-3xl transition-all duration-700 group-hover:scale-150 group-hover:bg-purple-500/30" />
                            
                            <!-- Central Icon (Decorative) -->
                            <div class="relative z-10 opacity-30 transition-opacity duration-300 group-hover:opacity-50">
                                <Film v-if="p.media_type === 'youtube'" class="h-16 w-16 text-white" />
                                <Video v-else class="h-16 w-16 text-white" />
                            </div>
                        </div>

                        <!-- Overlay Icon (Glassmorphism Play Button - Always visible on video content) -->
                        <div
                            v-if="p.media_type === 'youtube' || p.media_type === 'upload'"
                            class="absolute inset-0 flex items-center justify-center bg-black/10 transition-colors duration-300 group-hover:bg-black/30"
                        >
                            <div class="relative flex h-16 w-16 items-center justify-center rounded-full bg-white/10 shadow-2xl backdrop-blur-md ring-1 ring-white/20 transition-all duration-300 group-hover:scale-110 group-hover:bg-white/20">
                                <!-- Inner glow -->
                                <div class="absolute inset-0 rounded-full bg-indigo-500/20 opacity-0 blur-md transition-opacity duration-300 group-hover:opacity-100" />
                                <PlayCircle class="relative h-8 w-8 text-white drop-shadow-lg" />
                            </div>
                        </div>




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
                                    <div v-if="p.category">
                                        <Link :href="`/blog?category=${p.category.slug}`" class="inline-flex items-center gap-1.5 rounded-md bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-800 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700 transition">
                                            <span :class="['w-1.5 h-1.5 rounded-full', p.category.color]"></span>
                                            {{ p.category.name }}
                                        </Link>
                                    </div>
                                    <div v-else></div>
                                    
                                    <!-- Arrow Button (Right) -->
                                    <Link :href="`/blog/${p.slug}`">
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

            <div v-else class="rounded-lg border border-dashed p-10 text-center text-muted-foreground">
                Nog geen publicaties.
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
                        <Link v-if="l._type === 'prev'" :href="l.url ? `${l.url}#artikelen` : '#'" :aria-disabled="!l.url">
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
                        <Link v-else-if="l._type === 'page'" :href="l.url ? `${l.url}#artikelen` : '#'" :aria-current="l.active ?  'page' : undefined">
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
                        <Link v-else :href="l.url ? `${l.url}#artikelen` : '#'" :aria-disabled="!l.url">
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

        <Separator class="mx-auto my-12 bg-primary dark:bg-primary" />
        
        <section class="mt-20 scroll-mt-24 w-full" id="contact">
             <ContactSection />
        </section>
    </div>

    <Footer />
</template>
