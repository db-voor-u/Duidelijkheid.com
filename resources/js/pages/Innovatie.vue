<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3'
import { computed, ref, toRefs, onMounted } from 'vue'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import { Separator } from '@/components/ui/separator'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { ArrowRight, PlayCircle, Video, Film, FileDown, Image as ImageIcon, FileText, Link2, ChevronLeft, ChevronRight } from 'lucide-vue-next'
import NavBarPublic from '@/components/NavBarPublic.vue'
import Footer from '@/components/Footer.vue'
import ContactForm from "@/components/ContactForm.vue";
import CategoryList from '@/components/CategoryList.vue'
import MagicMenu from '@/components/MagicMenu.vue'
import ContactSection from '@/components/ContactSection.vue'

// -------- Types --------

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

// -------- Page Data --------
type InnovatiePage = {
    title?: string
    content?: string
    image_path?: string
    meta_title?: string | null
    meta_description?: string | null
}

const props = withDefaults(defineProps<{
    latestPosts?: PostCard[] | Paginated<PostCard>;
    categories?: { name: string; slug: string; color: string }[];
    page?: InnovatiePage
}>(), {
    latestPosts: () => [],
})
const { categories } = toRefs(props);

const pageTitle = computed(() => props.page?.meta_title || "Innovatie")
const metaDescription = computed(() => props.page?.meta_description || "Gedreven door Innovatie. Wij bouwen razendsnelle, schaalbare applicaties met de allernieuwste technologie.")

const imgPath = (p?: string | null) => (p ? (p.startsWith('http') || p.startsWith('/') ? p : `/storage/${p}`) : '')

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
    if (Array.isArray(props.latestPosts)) return props.latestPosts
    return (props.latestPosts as Paginated<PostCard>).data
})

const cards = computed(() =>
    rawPosts.value.map((p) => ({
        ...p,
        img: imgPath(p.cover_image_path),
        excerpt:
            p.excerpt && p.excerpt.trim().length
                ? p.excerpt
                : ''
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
    if (!props.latestPosts || Array.isArray(props.latestPosts)) return []
    return (props.latestPosts as Paginated<PostCard>).links ?? []
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

// -------- Video helpers --------
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
    const p = props.latestPosts as any;
    return p?.meta?.current_page ?? p?.current_page ?? 1;
})

const articlesTitle = computed(() => currentPage.value > 1 ? 'Meer artikelen zien' : 'Alle artikelen')

// Get selected category from URL
const inertiaPage = usePage()
const selectedCategorySlug = computed(() => {
    const url = inertiaPage.url as string
    const urlWithoutHash = url.split('#')[0]
    const match = urlWithoutHash.match(/[?&]category=([^&]+)/)
    return match ? decodeURIComponent(match[1]) : null
})

const selectedCategory = computed(() => {
    if (!selectedCategorySlug.value || !props.categories) return null
    return props.categories.find(c => c.slug === selectedCategorySlug.value) || null
})


// -------- Tech Stack Data --------
const baseStack = [
    {
        name: 'Linux',
        url: 'https://www.linux.org/',
        img: 'https://upload.wikimedia.org/wikipedia/commons/a/af/Tux.png'
    },
    {
        name: 'MySQL',
        url: 'https://www.mysql.com/',
        img: 'https://labs.mysql.com/common/logos/mysql-logo.svg'
    },
    {
        name: 'Laravel',
        url: 'https://laravel.com/',
        img: 'https://upload.wikimedia.org/wikipedia/commons/9/9a/Laravel.svg'
    },
    {
        name: 'Vue.js',
        url: 'https://vuejs.org/',
        img: 'https://upload.wikimedia.org/wikipedia/commons/9/95/Vue.js_Logo_2.svg'
    },
    {
        name: 'Inertia',
        url: 'https://inertiajs.com/',
        img: 'https://avatars.githubusercontent.com/u/47703742?s=200&v=4'
    },
    {
        name: 'Tailwind',
        url: 'https://tailwindcss.com/',
        img: 'https://upload.wikimedia.org/wikipedia/commons/d/d5/Tailwind_CSS_Logo.svg'
    },
    {
        name: 'TypeScript',
        url: 'https://www.typescriptlang.org/',
        img: 'https://upload.wikimedia.org/wikipedia/commons/4/4c/Typescript_logo_2020.svg'
    },
    {
        name: 'Shadcn/UI',
        url: 'https://ui.shadcn.com/',
        img: 'https://github.com/shadcn.png'
    }
]

// Duplicate to create that "full circle" effect with 12 items
const techStack = computed(() => [...baseStack, ...baseStack])

// ... (rest of methods)


</script>

<template>
    <Head>
        <title>{{ pageTitle }}</title>
        <meta name="description" :content="metaDescription" />
        <meta property="og:title" :content="pageTitle" />
        <meta property="og:description" :content="metaDescription" />
    </Head>

    <NavBarPublic />

    <div class="mx-auto min-h-screen max-w-4xl px-4 py-10 sm:px-6 lg:px-8">
        
        <!-- Tech Stack Animation Section -->
        <section class="mt-24 mb-24 w-full overflow-hidden flex flex-col items-center">
            <div class="mx-auto max-w-4xl text-center mb-0 px-4 z-10 relative">
                <h1 v-if="props.page?.title" class="text-3xl font-extrabold tracking-tight sm:text-4xl bg-gradient-to-r from-zinc-900 to-zinc-600 bg-clip-text text-transparent dark:from-white dark:to-zinc-400">
                    {{ props.page.title }}
                </h1>
                <div v-if="props.page?.content" class="mt-4 text-lg text-zinc-700 dark:text-zinc-300 max-w-2xl mx-auto prose dark:prose-invert" v-html="props.page.content"></div>
                
                <div v-if="props.page?.image_path" class="mt-8 relative w-full h-auto max-w-3xl mx-auto">
                     <img :src="imgPath(props.page.image_path)" alt="Hero Image" class="rounded-xl shadow-2xl border border-white/20" />
                </div>
            </div>

            <!-- 3D Carousel Container -->
            <div class="perspective-container group relative mx-auto flex h-[400px] -mt-32 w-full max-w-5xl items-center justify-center" style="perspective: 1000px;">
                
                <!-- Rotating Axis (Centered 0x0 wrapper) -->
                <div 
                    class="carousel-rotator absolute animate-carousel group-hover:[animation-play-state:paused]" 
                    style="transform-style: preserve-3d;"
                >
                    
                    <!-- Items -->
                    <template v-for="(tech, i) in techStack" :key="i">
                        <!-- Positioning Wrapper (Handles 3D Rotation) -->
                        <div 
                            class="absolute left-0 top-0 flex items-center justify-center"
                            :style="{
                                transform: `rotateY(${i * (360 / techStack.length)}deg) translateZ(380px)`
                            }"
                        >
                            <!-- Visual Card (Handles Look) -->
                            <div 
                                class="relative flex h-24 w-36 flex-col items-center justify-center gap-2 overflow-hidden rounded-xl border border-black/5 bg-white/70 shadow-xl backdrop-blur-md transition-all duration-500 hover:scale-110 hover:border-primary/60 hover:bg-white/90 hover:shadow-primary/30 dark:border-white/10 dark:bg-zinc-900/70 dark:hover:bg-zinc-800/90"
                            >
                                <a :href="tech.url" target="_blank" rel="noopener noreferrer" class="group flex flex-col items-center gap-1 text-center z-10">
                                    <div class="relative flex h-14 w-14 items-center justify-center transition-transform duration-300 group-hover:scale-110">
                                        <img :src="tech.img" :alt="tech.name" class="h-10 w-10 object-contain drop-shadow-sm" />
                                    </div>
                                    <span class="text-xs font-bold uppercase tracking-wider text-zinc-700 transition-colors group-hover:text-black dark:text-zinc-300 dark:group-hover:text-white">{{ tech.name }}</span>
                                </a>
                            </div>
                        </div>
                    </template>
                </div>
                
                <!-- Gradient Overlay for smooth fade at edges -->
                <div class="pointer-events-none absolute inset-0 bg-gradient-to-r from-background via-transparent to-background dark:from-background"></div>
            </div>
        </section>
        
        <Separator class="mx-auto mt-8 bg-primary dark:bg-primary" />
        
        <section id="onderwerpen" class="scroll-mt-20">
            <CategoryList :categories="categories" baseUrl="/innovatie" />
        </section>

        <Separator class="mx-auto mt-8 bg-primary dark:bg-primary" />

        <!-- Blogs over innovatie -->
        <section class="mt-10 w-full scroll-mt-24" id="innovatie">
            <div class="mb-6 flex items-center gap-3">
                <h2 class="text-xl font-bold tracking-tight">{{ articlesTitle }}</h2>
                <!-- Selected Category Badge -->
                <div v-if="selectedCategory" class="inline-flex items-center gap-1.5 rounded-full bg-zinc-100 px-3 py-1 text-sm font-medium text-zinc-800 dark:bg-zinc-800 dark:text-zinc-200">
                    <span :class="['w-2 h-2 rounded-full', selectedCategory.color]"></span>
                    {{ selectedCategory.name }}
                </div>
            </div>

            <div v-if="cards.length" class="flex flex-col gap-8">
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
                                    <Link :href="`/innovatie/${p.slug}`">
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

            <div v-else class="rounded-lg border border-dashed p-8 text-center text-sm">
                Nog geen blogberichten.
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
                        <Link v-if="l._type === 'prev'" :href="l.url ? `${l.url}#innovatie` : '#'" :aria-disabled="!l.url">
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
                        <Link v-else-if="l._type === 'page'" :href="l.url ? `${l.url}#innovatie` : '#'" :aria-current="l.active ?  'page' : undefined">
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
                        <Link v-else :href="l.url ? `${l.url}#innovatie` : '#'" :aria-disabled="!l.url">
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

<style scoped>
@keyframes rotate-carousel {
  from {
    transform: rotateY(0deg);
  }
  to {
    transform: rotateY(-360deg);
  }
}

.animate-carousel {
  animation: rotate-carousel 40s infinite linear;
}


</style>
