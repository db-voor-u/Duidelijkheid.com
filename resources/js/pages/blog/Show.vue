<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { computed, ref, toRefs, onMounted, onUnmounted } from 'vue'
import { Card, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Separator } from '@/components/ui/separator'
import NavBarPublic from '@/components/NavBarPublic.vue'
import {
    ChevronRight,
    ChevronLeft,
    Download,
    FileText,
    CalendarDays
} from 'lucide-vue-next'
import SocialShare from '@/components/SocialShare.vue'
import { useCodeCopy } from '@/composables/useCodeCopy'
import Footer from '@/components/Footer.vue'
import FeedbackSection from '@/components/FeedbackSection.vue'

// -------- Types --------
type Post = {
    title: string
    slug: string
    excerpt?: string | null
    content: string
    cover_image_path?: string | null
    published_at?: string | null
    meta_title?: string | null
    meta_description?: string | null
    canonical_url?: string | null
    robots_index?: boolean
    robots_follow?: boolean
    seo_image_path?: string | null
    media_type?: 'image' | 'youtube' | 'upload' | string
    video_path?: string | null
    download_file_path?: string | null
    extra_files_paths?: string[] | null
}

type PostNav = { title: string; slug: string }

const props = defineProps<{
    post: Post
    prevPost?: PostNav | null
    nextPost?: PostNav | null
}>()

const { post } = toRefs(props)

const hasPrev = computed(() => !!props.prevPost)
const hasNext = computed(() => !!props.nextPost)

const contentRef = ref<HTMLElement | null>(null)
useCodeCopy(contentRef)

/* SEO */
const pageTitle = computed(() => post.value.meta_title?.trim() || post.value.title)
const metaDescription = computed(() => (post.value.meta_description?.trim() || '').slice(0, 160))
const robots = computed(() =>
    `${post.value.robots_index !== false ? 'index' : 'noindex'},${post.value.robots_follow !== false ? 'follow' : 'nofollow'}`
)

/* Helpers */
const imgPath = (p?: string | null) => (p ? (p.startsWith('http') || p.startsWith('/') ? p : `/storage/${p}`) : '')
const ogImage = computed(() => imgPath(post.value.seo_image_path) || imgPath(post.value.cover_image_path))
const absOg = ref('');
const currentUrl = ref('')
const siteOrigin = ref('')

onMounted(() => { 
    if (ogImage.value) absOg.value = new URL(ogImage.value, window.location.origin).toString()
    currentUrl.value = window.location.href
    siteOrigin.value = window.location.origin
})

/* JSON-LD Article Schema */
const articleJsonLd = computed(() => JSON.stringify({
    "@context": "https://schema.org",
    "@type": "Article",
    "headline": pageTitle.value,
    "description": metaDescription.value,
    "image": absOg.value || undefined,
    "datePublished": post.value.published_at || undefined,
    "author": {
        "@type": "Organization",
        "name": "Duidelijkheid.com"
    },
    "publisher": {
        "@type": "Organization",
        "name": "Duidelijkheid.com",
        "logo": {
            "@type": "ImageObject",
            "url": siteOrigin.value + "/images/logo-icon.jpg"
        }
    },
    "mainEntityOfPage": {
        "@type": "WebPage",
        "@id": currentUrl.value
    }
}))

/* Media Logica */
const mediaType = computed(() => post.value.media_type || 'image')
const heroImage = computed(() => mediaType.value === 'image' ? imgPath(post.value.cover_image_path) : null)

const heroMedia = computed(() => {
    const path = post.value.video_path || heroImage.value;
    if (!path) return null;

    if (mediaType.value === 'youtube') {
        const match = path.match(/(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=|watch\?v%3D|watch\?.+&v=))([^&]*)/);
        const youtubeId = match ? match[1] : null;
        return youtubeId ? `https://www.youtube.com/embed/${youtubeId}` : null;
    }

    if (mediaType.value === 'upload') {
        return path.startsWith('http') ? path : `/storage/${path}`;
    }

    return heroImage.value;
})

const fileColors = [
    { 
        bg: 'bg-blue-100 dark:bg-blue-900/30', 
        text: 'text-blue-600 dark:text-blue-400',
        btnHover: 'hover:bg-blue-50 dark:hover:bg-blue-900/20',
        btnBorder: 'border-blue-200 dark:border-blue-800'
    },
    { 
        bg: 'bg-emerald-100 dark:bg-emerald-900/30', 
        text: 'text-emerald-600 dark:text-emerald-400',
        btnHover: 'hover:bg-emerald-50 dark:hover:bg-emerald-900/20',
        btnBorder: 'border-emerald-200 dark:border-emerald-800'
    },
    { 
        bg: 'bg-violet-100 dark:bg-violet-900/30', 
        text: 'text-violet-600 dark:text-violet-400',
        btnHover: 'hover:bg-violet-50 dark:hover:bg-violet-900/20',
        btnBorder: 'border-violet-200 dark:border-violet-800'
    },
    { 
        bg: 'bg-amber-100 dark:bg-amber-900/30', 
        text: 'text-amber-600 dark:text-amber-400',
        btnHover: 'hover:bg-amber-50 dark:hover:bg-amber-900/20',
        btnBorder: 'border-amber-200 dark:border-amber-800'
    },
    { 
        bg: 'bg-rose-100 dark:bg-rose-900/30', 
        text: 'text-rose-600 dark:text-rose-400',
        btnHover: 'hover:bg-rose-50 dark:hover:bg-rose-900/20',
        btnBorder: 'border-rose-200 dark:border-rose-800'
    },
    { 
        bg: 'bg-cyan-100 dark:bg-cyan-900/30', 
        text: 'text-cyan-600 dark:text-cyan-400',
        btnHover: 'hover:bg-cyan-50 dark:hover:bg-cyan-900/20',
        btnBorder: 'border-cyan-200 dark:border-cyan-800'
    },
]

const getFileColor = (index: number) => fileColors[index % fileColors.length]

// Download link helper
const mainDownload = computed(() => {
    if (!post.value.download_file_path) return null
    return {
        path: `/blog/${post.value.slug}/download`,
        name: post.value.download_file_path.split('/').pop() || 'Hoofdbestand'
    }
})

const extraDownloads = computed(() => {
    return (post.value.extra_files_paths || []).map(path => ({
        path: `/blog/${post.value.slug}/download?path=${encodeURIComponent(path)}`,
        name: path.split('/').pop() || 'Extra bestand'
    }))
})

const totalFilesCount = computed(() => (mainDownload.value ? 1 : 0) + extraDownloads.value.length)

/* Navigatie met toetsen */
const onKey = (e: KeyboardEvent) => {
    if (e.key === 'ArrowLeft' && props.prevPost?.slug) window.location.href = `/blog/${props.prevPost.slug}`
    if (e.key === 'ArrowRight' && props.nextPost?.slug) window.location.href = `/blog/${props.nextPost.slug}`
}
onMounted(() => window.addEventListener('keydown', onKey))
onUnmounted(() => window.removeEventListener('keydown', onKey))
</script>

<template>
    <Head>
        <title>{{ pageTitle }}</title>
        <meta name="description" :content="metaDescription" />
        <meta name="robots" :content="robots" />
        <!-- Open Graph -->
        <meta property="og:type" content="article" />
        <meta property="og:site_name" content="Duidelijkheid.com" />
        <meta property="og:title" :content="pageTitle" />
        <meta property="og:description" :content="metaDescription" />
        <meta v-if="absOg" property="og:image" :content="absOg" />
        <meta v-if="currentUrl" property="og:url" :content="currentUrl" />
        <meta property="og:locale" content="nl_NL" />
        <meta v-if="post.published_at" property="article:published_time" :content="post.published_at" />
        <!-- Twitter Card -->
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" :content="pageTitle" />
        <meta name="twitter:description" :content="metaDescription" />
        <meta v-if="absOg" name="twitter:image" :content="absOg" />
        <!-- Canonical -->
        <link v-if="post.canonical_url" rel="canonical" :href="post.canonical_url!" />
        <!-- JSON-LD Article Schema -->
        <component :is="'script'" type="application/ld+json" v-html="articleJsonLd" />
    </Head>

    <NavBarPublic />

    <div class="mx-auto min-h-screen max-w-4xl px-4 py-10 sm:px-6 lg:px-8">

        <!-- BREADCRUMB -->
        <nav class="mb-8 flex items-center text-sm text-muted-foreground">
            <Link href="/" class="hover:text-foreground transition-colors">
                Welkom
            </Link>
            <ChevronRight class="mx-2 h-4 w-4" />
            <Link href="/blog" class="hover:text-foreground transition-colors">
                Blog
            </Link>
            <ChevronRight class="mx-2 h-4 w-4" />
            <span class="font-medium text-foreground truncate">{{ post.title }}</span>
        </nav>

        <article class="space-y-8">

            <!-- TITEL -->
            <header class="space-y-4 text-left">
                <h1 class="text-balance text-3xl font-extrabold tracking-tight sm:text-4xl lg:text-5xl text-foreground">
                    {{ post.title }}
                </h1>
                
                <div class="flex flex-wrap items-center justify-start gap-4 text-sm text-muted-foreground">
                     <span v-if="post.published_at" class="flex items-center gap-1">
                        <CalendarDays class="h-4 w-4" />
                        {{ new Date(post.published_at).toLocaleDateString('nl-NL', { day: 'numeric', month: 'long', year: 'numeric' }) }}
                     </span>
                </div>
            </header>

            <!-- MEDIA HERO & SHARE WRAPPER -->
            <div class="w-full max-w-2xl">
                <figure v-if="heroMedia" class="relative w-full overflow-hidden rounded-xl mb-4 group">
                    <!-- 1. Afbeelding -->
                    <div v-if="mediaType === 'image'" class="w-full">
                        <img
                            :src="heroMedia"
                            :alt="post.title"
                            class="w-full h-auto"
                            loading="lazy"
                        />
                    </div>

                    <!-- 2. YouTube Embed -->
                    <div v-else-if="mediaType === 'youtube'" class="aspect-video w-full">
                        <iframe
                            :src="heroMedia"
                            frameborder="0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen
                            class="h-full w-full"
                            title="YouTube video player"
                        ></iframe>
                    </div>

                    <!-- 3. Video Upload -->
                    <div v-else-if="mediaType === 'upload'" class="aspect-video w-full">
                        <video
                            :src="heroMedia"
                            controls
                            preload="metadata"
                            class="w-full h-full bg-black"
                            :poster="heroImage ?? undefined"
                            :title="post.title"
                        ></video>
                    </div>
                </figure>
                
                <!-- Share Button (Aligned to right of image) -->
                <div class="flex items-center justify-end mb-8 relative z-10">
                     <SocialShare :title="post.title" />
                </div>
            </div>

            <!-- INHOUD -->
            <div
                ref="contentRef"
                class="prose prose-zinc max-w-prose leading-relaxed
                       prose-headings:font-bold prose-headings:tracking-tight prose-headings:mb-3 prose-headings:mt-6
                       prose-headings:first:mt-0 prose-p:first:mt-0
                       prose-a:text-primary prose-a:no-underline hover:prose-a:underline
                       prose-img:rounded-xl dark:prose-invert"
                v-html="post.content"
            />

            <!-- DOWNLOAD SECTIE -->
            <div v-if="totalFilesCount > 0" class="mt-10 not-prose">
                <Card class="border-dashed border-2 bg-primary/5 dark:bg-primary/10 border-primary/20 dark:border-primary/30 transition-all hover:border-primary/40">
                    <CardContent class="p-6">
                        <h3 class="font-semibold text-lg text-foreground mb-4 flex items-center gap-2">
                            <Download class="h-5 w-5" />
                            Downloads ({{ totalFilesCount }})
                        </h3>

                        <div class="space-y-3">
                            <!-- Main Download -->
                            <div v-if="mainDownload" class="flex flex-col sm:flex-row items-center justify-between gap-4 p-3 rounded-lg bg-background/50 border">
                                <div class="flex items-center gap-3 overflow-hidden w-full">
                                    <div
                                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full"
                                        :class="[getFileColor(0).bg, getFileColor(0).text]"
                                    >
                                        <FileText class="h-5 w-5" />
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-medium text-sm truncate">{{ mainDownload.name }}</p>
                                        <span class="text-xs text-muted-foreground">Bestand 1: Hoofdbestand</span>
                                    </div>
                                </div>
                                <Button
                                    as-child
                                    size="sm"
                                    variant="outline"
                                    class="w-full sm:w-auto shrink-0 gap-2 transition-colors"
                                    :class="[
                                        getFileColor(0).text,
                                        getFileColor(0).btnHover,
                                        getFileColor(0).btnBorder
                                    ]"
                                >
                                    <a :href="mainDownload.path" download>
                                        <Download class="h-3 w-3" /> Downloaden
                                    </a>
                                </Button>
                            </div>

                            <!-- Extra Files -->
                            <div v-for="(file, idx) in extraDownloads" :key="idx" class="flex flex-col sm:flex-row items-center justify-between gap-4 p-3 rounded-lg bg-background/50 border">
                                <div class="flex items-center gap-3 overflow-hidden w-full">
                                    <div
                                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full"
                                        :class="[getFileColor(idx + 1).bg, getFileColor(idx + 1).text]"
                                    >
                                        <FileText class="h-5 w-5" />
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-medium text-sm truncate">{{ file.name }}</p>
                                        <span class="text-xs text-muted-foreground">Bestand {{ idx + 2 }}: Extra bijlage</span>
                                    </div>
                                </div>
                                <Button
                                    as-child
                                    size="sm"
                                    variant="outline"
                                    class="w-full sm:w-auto shrink-0 gap-2 transition-colors"
                                    :class="[
                                        getFileColor(idx + 1).text,
                                        getFileColor(idx + 1).btnHover,
                                        getFileColor(idx + 1).btnBorder
                                    ]"
                                >
                                    <a :href="file.path" download>
                                        <Download class="h-3 w-3" /> Downloaden
                                    </a>
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>





        </article>

        <Separator class="my-12" />

        <!-- NAVIGATIE -->
        <!-- NAVIGATION (Previous / Next) ROUND BUTTONS -->
        <footer class="mb-12 mt-8">
            <div class="flex items-center justify-between gap-4">
                <!-- Vorig artikel -->
                <div class="flex-1 flex justify-start">
                    <Link
                        v-if="hasPrev"
                        :href="`/blog/${prevPost!.slug}`"
                        class="group flex items-center gap-4 text-left transition-all hover:opacity-80"
                    >
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full border-2 border-primary bg-background text-primary transition-colors group-hover:bg-primary group-hover:text-primary-foreground">
                            <ChevronLeft class="h-6 w-6" />
                        </div>
                        <div class="hidden sm:block">
                            <p class="text-xs font-medium text-muted-foreground uppercase tracking-wider">Vorig artikel</p>
                            <p class="font-bold text-foreground line-clamp-1 max-w-[150px] lg:max-w-[200px]">{{ prevPost!.title }}</p>
                        </div>
                    </Link>
                </div>

                <!-- Volgend artikel -->
                <div class="flex-1 flex justify-end">
                    <Link
                        v-if="hasNext"
                        :href="`/blog/${nextPost!.slug}`"
                        class="group flex items-center gap-4 flex-row-reverse text-right transition-all hover:opacity-80"
                    >
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full border-2 border-primary bg-background text-primary transition-colors group-hover:bg-primary group-hover:text-primary-foreground">
                            <ChevronRight class="h-6 w-6" />
                        </div>
                        <div class="hidden sm:block">
                            <p class="text-xs font-medium text-muted-foreground uppercase tracking-wider">Volgend artikel</p>
                            <p class="font-bold text-foreground line-clamp-1 max-w-[150px] lg:max-w-[200px]">{{ nextPost!.title }}</p>
                        </div>
                    </Link>
                </div>
            </div>
            
            <div class="mt-8 text-center">
                <Button as-child variant="outline">
                    <Link href="/blog#onderwerpen" :preserve-scroll="false">Terug naar Overzicht</Link>
                </Button>
            </div>
        </footer>
        
        <div class="mt-16 mb-20 p-8 bg-zinc-50 dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800">
             <FeedbackSection :article-title="post.title" />
        </div>
    </div>
    <Footer />
</template>
