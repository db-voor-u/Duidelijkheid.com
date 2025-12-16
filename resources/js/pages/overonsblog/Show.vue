<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { computed, ref, toRefs, onMounted, onUnmounted } from 'vue'
import { Card, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { ChevronLeft, ChevronRight, CalendarDays, Download, FileText } from 'lucide-vue-next'
import SocialShare from '@/components/SocialShare.vue'
import { Separator } from '@/components/ui/separator'
import NavBarPublic from '@/components/NavBarPublic.vue'
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
}

type PostNav = { title: string; slug: string }

const props = defineProps<{
    post: Post
    prevPost?: PostNav | null
    nextPost?: PostNav | null
    section?: 'innovatie' | 'over_ons'
}>()

const { post } = toRefs(props)

const hasPrev = computed(() => !!props.prevPost)
const hasNext = computed(() => !!props.nextPost)
const isInnovatie = computed(() => props.section === 'innovatie')
const baseRoute = computed(() => isInnovatie.value ? '/innovatie' : '/over-ons')
const postPrefix = computed(() => isInnovatie.value ? '/innovatie/' : '/over-ons-blog/')
const sectionName = computed(() => isInnovatie.value ? 'Innovatie' : 'Over ons')

// Content Ref voor copy buttons
const contentRef = ref<HTMLElement | null>(null)
useCodeCopy(contentRef)

/* SEO */
const pageTitle = computed(() => post.value.meta_title?.trim() || post.value.title)
const metaDescription = computed(() => (post.value.meta_description?.trim() || '').slice(0, 160))
const robots = computed(() =>
    `${post.value.robots_index !== false ? 'index' : 'noindex'},${post.value.robots_follow !== false ? 'follow' : 'nofollow'}`
)

const imgPath = (p?: string | null) => (p ? (p.startsWith('http') || p.startsWith('/') ? p : `/storage/${p}`) : '')
const ogImage = computed(() => imgPath(post.value.seo_image_path) || imgPath(post.value.cover_image_path))
const absOg = ref('')

onMounted(() => {
    if (ogImage.value) {
        absOg.value = new URL(ogImage.value, window.location.origin).toString()
    }
})

/* Media Logic */
const mediaType = computed(() => post.value.media_type || 'image')

const heroImage = computed(() => {
    return mediaType.value === 'image' ? imgPath(post.value.cover_image_path) : null
})

const heroMedia = computed(() => {
    const path = post.value.video_path || heroImage.value
    if (!path) return null

    if (mediaType.value === 'youtube' && path) {
        const youtubeId = path.match(/youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=|watch\?v%3D|watch\?.+&v=)([^&]*)/)?.[1]
        return youtubeId ? `https://www.youtube.com/embed/${youtubeId}` : null
    }

    if (mediaType.value === 'upload' && path) {
        return path.startsWith('http') ? path : `/storage/${path}`
    }

    return heroImage.value
})

const downloadLink = computed(() => {
    if (post.value.download_file_path) {
        return `/storage/${post.value.download_file_path}`
    }
    return null
})

/* Date formatter (NL) */
const fmtDate = (iso?: string | null) => {
    if (!iso) return '—'
    const s = iso.includes(' ') ? iso.replace(' ', 'T') : iso
    const d = new Date(s)
    if (Number.isNaN(+d)) return '—'
    return new Intl.DateTimeFormat('nl-NL', { day: '2-digit', month: 'short', year: 'numeric' }).format(d)
}

/* Arrow keys for previous/next */
const onKey = (e: KeyboardEvent) => {
    if (e.key === 'ArrowLeft' && props.prevPost?.slug) {
        window.location.href = `${postPrefix.value}${props.prevPost.slug}`
    }
    if (e.key === 'ArrowRight' && props.nextPost?.slug) {
        window.location.href = `${postPrefix.value}${props.nextPost.slug}`
    }
}

onMounted(() => window.addEventListener('keydown', onKey))
onUnmounted(() => window.removeEventListener('keydown', onKey))
</script>

<template>
    <Head>
        <title>{{ pageTitle }}</title>
        <meta name="description" :content="metaDescription" />
        <meta name="robots" :content="robots" />
        <meta property="og:title" :content="pageTitle" />
        <meta property="og:description" :content="metaDescription" />
        <meta v-if="absOg" property="og:image" :content="absOg" />
        <link v-if="post.canonical_url" rel="canonical" :href="post.canonical_url" />
    </Head>

    <NavBarPublic />

    <!-- Main container -->
    <div class="mx-auto min-h-screen max-w-4xl px-4 py-10 sm:px-6 lg:px-8">

        <nav class="mb-8 flex items-center text-sm text-muted-foreground">
            <Link href="/" class="hover:text-foreground transition-colors">
                Welkom
            </Link>
            <ChevronRight class="mx-2 h-4 w-4" />
            <Link :href="baseRoute" class="hover:text-foreground transition-colors">
                {{ sectionName }}
            </Link>
            <ChevronRight class="mx-2 h-4 w-4" />
            <span class="font-medium text-foreground truncate">{{ post.title }}</span>
        </nav>

        <article class="space-y-8">

            <!-- TITLE -->
            <header class="space-y-4 text-left">
                <h1 class="text-balance text-3xl font-extrabold tracking-tight sm:text-4xl lg:text-5xl text-foreground">
                    {{ post.title }}
                </h1>
                <div class="flex items-center justify-start gap-4 text-sm text-muted-foreground">
                    <div class="flex items-center gap-2">
                        <CalendarDays class="h-4 w-4" />
                        <time :datetime="post.published_at || ''">{{ fmtDate(post.published_at) }}</time>
                    </div>
                </div>
            </header>

            <!-- MEDIA / HERO SECTION -->
             <div class="w-full max-w-2xl">
                <figure v-if="heroMedia" class="relative w-full overflow-hidden rounded-xl mb-4">
                    <!-- 1. Image (Default) -->
                    <template v-if="mediaType === 'image'">
                        <img
                            :src="heroMedia"
                            :alt="post.title"
                            class="w-full h-auto"
                            loading="lazy"
                        />
                    </template>

                    <!-- 2. YouTube Embed -->
                    <template v-else-if="mediaType === 'youtube'">
                        <div class="aspect-video w-full">
                            <iframe
                                :src="heroMedia"
                                frameborder="0"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                allowfullscreen
                                class="h-full w-full"
                                title="YouTube video player"
                            ></iframe>
                        </div>
                    </template>

                    <!-- 3. Video Upload -->
                    <template v-else-if="mediaType === 'upload'">
                        <video
                            :src="heroMedia"
                            controls
                            preload="metadata"
                            class="w-full h-auto max-h-[500px] bg-black"
                            :poster="heroImage || undefined"
                            :title="post.title"
                        ></video>
                    </template>
                </figure>
                
                <!-- Share Button (Below image) -->
                <div class="flex items-center justify-end mb-8 relative z-10">
                    <SocialShare :title="post.title" />
                </div>
            </div>

            <!-- ARTICLE CONTENT -->
            <div
                ref="contentRef"
                class="prose prose-zinc max-w-prose leading-relaxed
                       prose-headings:font-bold prose-headings:tracking-tight prose-headings:mb-3 prose-headings:mt-6
                       prose-headings:first:mt-0 prose-p:first:mt-0
                       prose-a:text-primary prose-a:no-underline hover:prose-a:underline
                       prose-img:rounded-xl dark:prose-invert"
                v-html="post.content"
            />

            <!-- DOWNLOAD SECTION (Only visible if there's a file) -->
            <div v-if="downloadLink" class="mt-12">
                <Card class="border-dashed border-2 bg-muted/30 transition-colors hover:bg-muted/50">
                    <CardContent class="flex flex-col sm:flex-row items-center justify-between gap-4 p-6">
                        <div class="flex items-center gap-4 text-center sm:text-left">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary">
                                <FileText class="h-6 w-6" />
                            </div>
                            <div class="space-y-0.5">
                                <h3 class="font-semibold text-base text-foreground">Download bijlage</h3>
                                <p class="text-sm text-muted-foreground">
                                    Dit artikel bevat een extra bestand (PDF, document of archief).
                                </p>
                            </div>
                        </div>

                        <Button as-child size="lg" class="w-full sm:w-auto gap-2 shadow-sm">
                            <a :href="downloadLink" target="_blank" rel="noopener noreferrer">
                                <Download class="h-4 w-4" />
                                Downloaden
                            </a>
                        </Button>
                    </CardContent>
                </Card>
            </div>





        </article>

        <Separator class="mx-auto mt-8 max-w-6xl bg-primary dark:bg-primary" />

        <!-- NAVIGATION (Previous / Next) ROUND BUTTONS -->
        <footer class="mb-12 mt-8">
            <div class="flex items-center justify-between gap-4">
                <!-- Vorig artikel -->
                <div class="flex-1 flex justify-start">
                    <Link
                        v-if="hasPrev"
                        :href="`${postPrefix}${prevPost!.slug}`"
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
                        :href="`${postPrefix}${nextPost!.slug}`"
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

            <div class="mt-12 text-center">
                <Button as-child variant="outline">
                    <Link :href="`${baseRoute}#onderwerpen`" :preserve-scroll="false">Terug naar {{ sectionName }}</Link>
                </Button>
            </div>
        </footer>
        
        <div class="mt-16 mb-20 p-8 bg-zinc-50 dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800">
            <FeedbackSection :article-title="post.title" />
        </div>
    </div>
    <Footer />
</template>
