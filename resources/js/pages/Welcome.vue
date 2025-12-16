<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { computed, ref, toRefs, onMounted } from 'vue'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { ArrowRight, PlayCircle, Video, Film, FileDown, Image as ImageIcon, FileText, Link2 } from 'lucide-vue-next'
import { Separator } from '@/components/ui/separator'
import NavBarPublic from '@/components/NavBarPublic.vue'
import { useCodeCopy } from '@/composables/useCodeCopy'
import Footer from '@/components/Footer.vue'
import CategoryList from '@/components/CategoryList.vue'
import MagicMenu from '@/components/MagicMenu.vue'
import ContactSection from '@/components/ContactSection.vue'


type WelcomePage = {
    title: string
    image_path?: string | null
    content?: string | null
    meta_title?: string | null
    meta_description?: string | null
    seo_image_path?: string | null
    canonical_url?: string | null
    robots_index?: boolean
    robots_follow?: boolean
}

type PostCard = {
    title: string
    slug: string
    excerpt?: string | null
    cover_image_path?: string | null
    published_at?: string | null
    seo_image_path?: string | null // fallback
    media_type?: string
    video_path?: string | null
    download_file_path?: string | null
    category?: { name: string; color: string; slug: string } | null
}

type LinkItem = { url: string | null; label: string; active: boolean }
type Paginated<T> = { data: T[]; links: LinkItem[]; meta?: Record<string, any> }

const props = withDefaults(defineProps<{
    welcome: WelcomePage;
    latestPosts?: PostCard[] | Paginated<PostCard>;
    categories?: any[];
}>(), {
    latestPosts: () => [],
    categories: () => [],
})
const { welcome } = toRefs(props)

// Content Ref voor copy buttons
const contentRef = ref<HTMLElement | null>(null)
useCodeCopy(contentRef)

// -------- Computed Properties --------

const stripTags = (s: string) => s.replace(/<[^>]*>/g, '')
const fallbackText = 'Dit is de Welkom pagina.'

const pageTitle = computed(() => welcome.value.meta_title?.trim() || welcome.value.title || 'Welkom')
const metaDescription = computed(() => {
    const raw =
        welcome.value.meta_description?.trim() || (welcome.value.content ? stripTags(welcome.value.content) : fallbackText)
    return raw.replace(/\s+/g, ' ').slice(0, 160)
})
const robots = computed(() => {
    const index = welcome.value.robots_index ?? true
    const follow = welcome.value.robots_follow ?? true
    return `${index ? 'index' : 'noindex'},${follow ? 'follow' : 'nofollow'}`
})

const imgPath = (p?: string | null) => (p ? (p.startsWith('http') || p.startsWith('/') ? p : `/storage/${p}`) : '')
const heroImage = computed(() => imgPath(welcome.value.image_path))
const ogImage = computed(() => imgPath(welcome.value.seo_image_path) || heroImage.value)
const hasImage = computed(() => !!heroImage.value)
const imgLoaded = ref(false)

const absOg = ref('')
const currentUrl = ref('')
const siteOrigin = ref('')

onMounted(() => {
    if (ogImage.value) absOg.value = new URL(ogImage.value, window.location.origin).toString()
    currentUrl.value = window.location.href
    siteOrigin.value = window.location.origin
})

/* JSON-LD Organization Schema */
const organizationJsonLd = computed(() => JSON.stringify({
    "@context": "https://schema.org",
    "@type": "Organization",
    "name": "Duidelijkheid.com",
    "url": siteOrigin.value || 'https://duidelijkheid.com',
    "logo": (siteOrigin.value || 'https://duidelijkheid.com') + '/images/logo-icon.jpg',
    "description": metaDescription.value,
    "sameAs": []
}))

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
    if (!props.latestPosts) return []
    const all = Array.isArray(props.latestPosts) 
        ? props.latestPosts 
        : (props.latestPosts as Paginated<PostCard>).data
    return all.slice(0, 4)
})
// ... (keep remaining code until template)

// ... (In template) ... 

const cards = computed(() =>
    rawPosts.value.map((p) => ({
        ...p,
        img: imgPath(p.cover_image_path || p.seo_image_path),
    })),
)



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
</script>

<template>
    <Head>
        <title>{{ pageTitle }}</title>
        <meta name="description" :content="metaDescription" />
        <meta name="robots" :content="robots" />
        <!-- Open Graph -->
        <meta property="og:type" content="website" />
        <meta property="og:site_name" content="Duidelijkheid.com" />
        <meta property="og:title" :content="pageTitle" />
        <meta property="og:description" :content="metaDescription" />
        <meta v-if="absOg" property="og:image" :content="absOg" />
        <meta v-if="currentUrl" property="og:url" :content="currentUrl" />
        <meta property="og:locale" content="nl_NL" />
        <!-- Twitter Card -->
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" :content="pageTitle" />
        <meta name="twitter:description" :content="metaDescription" />
        <meta v-if="absOg" name="twitter:image" :content="absOg" />
        <!-- Canonical -->
        <link v-if="welcome?.canonical_url" rel="canonical" :href="welcome.canonical_url!" />
        <!-- JSON-LD Structured Data: Organization -->
        <component :is="'script'" type="application/ld+json" v-html="organizationJsonLd" />
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
                v-if="hasImage && !imgLoaded"
                class="w-full aspect-video animate-pulse rounded-xl bg-muted mb-6"
            />

            <!-- Afbeelding: Natural flow (w-full h-auto) om de hele afbeelding te tonen -->
            <figure
                v-if="hasImage"
                class="w-full overflow-hidden rounded-xl mb-10"
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
                    v-if="welcome?.content"
                    ref="contentRef"
                    v-html="welcome.content"
                    class="prose prose-zinc leading-relaxed prose-headings:font-semibold prose-headings:mb-3 prose-headings:mt-6 prose-headings:first:mt-0 prose-p:first:mt-0 prose-a:underline prose-a:underline-offset-4 prose-img:rounded-xl dark:prose-invert"
                />
                <p v-else class="text-zinc-600 leading-relaxed dark:text-zinc-400">{{ fallbackText }}</p>
            </article>

            <Separator class="mx-auto mt-8 bg-primary dark:bg-primary" />

            <!-- Categorieën Lijst -->
            <CategoryList :categories="categories" />
        </section>

        <Separator class="mx-auto mt-8 bg-primary dark:bg-primary" />

        <!-- Nieuw op de blog -->
        <section class="mt-10 w-full scroll-mt-24" id="nieuws">
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-bold tracking-tight">Nieuwste </h2>
                <Link href="/blog">
                    <Button variant="outline" class="gap-1">
                        Alle blogs
                        <ArrowRight class="h-4 w-4" />
                    </Button>
                </Link>
            </div>

            <div class="flex flex-col gap-8">
                <Card
                    v-for="p in cards"
                    :key="p.slug"
                    class="mx-auto w-full border border-zinc-200 transition hover:-translate-y-0.5 hover:shadow-lg dark:border-zinc-800 h-full flex flex-col md:flex-row"
                >
                    <div class="relative w-full md:w-[40%] md:shrink-0 aspect-[3/2] bg-muted overflow-hidden rounded-t-lg md:rounded-tr-none md:rounded-l-lg">
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

                        <!-- 3b. Fallback / Placeholder (Modern Glow Design) -->
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

                <div v-if="! cards.length" class="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground">
                    Nog geen blogberichten.
                </div>
            </div>
            <!-- Paginatie -->
             

        </section>
        <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-bold tracking-tight">Meer </h2>
                <Link href="/blog">
                    <Button variant="outline" class="gap-1">
                        blogs
                        <ArrowRight class="h-4 w-4" />
                    </Button>
                </Link>
            </div>
        <Separator class="mx-auto my-12 bg-primary dark:bg-primary" />
        
        <!-- Contact Section -->
        <section class="mt-20 scroll-mt-24 w-full" id="contact">
             <ContactSection />
        </section>

    </div>

    <Footer />
</template>

<style scoped>

</style>
