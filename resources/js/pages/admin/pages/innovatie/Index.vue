<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, onMounted, onUnmounted, computed } from 'vue'
import AdminAppLayout from '@/layouts/AdminAppLayout.vue'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { routes, adminRouteFns, blogRouteFns } from '@/lib/routes'
import { MoreVertical, Pencil, Trash2, Globe, Plus, ChevronLeft, ChevronRight, Download, Camera, Upload, Youtube } from 'lucide-vue-next'

type PostRow = {
    id: number
    title: string
    slug: string
    is_published: boolean
    published_at?: string | null
    created_at: string
    media_type?: 'image' | 'youtube' | 'upload' | string
    video_path?: string | null
    download_file_path?: string | null
    category?: {
        name: string
        color: string
        type?: string
    } | null
}

const props = defineProps<{
    stats: { total: number; published: number; drafts: number; per_page: number }
    posts: { data: PostRow[]; links: Array<{ url: string | null; label: string; active: boolean }> }
    filters: { search?: string }
}>()

const q = ref(props.filters?.search ?? '')

// STATIC TITLES
const pageTitle = "Innovatie Beheer"
const pageDesc = "Beheer hier de innovatie artikelen."

function doSearch() {
    router.get('/hoofdbeheerder/innovatie', { search: q.value }, { preserveState: true, replace: true })
}

// Menu Logic
const menu = ref<{ open: boolean; x: number; y: number; slug: string; publicUrl: string }>({
    open: false, x: 0, y: 0, slug: '', publicUrl: ''
})

function getPublicUrl(row: PostRow) {
    return `/innovatie/${row.slug}`
}

function openRowMenu(e: MouseEvent, row: PostRow) {
    const r = (e.currentTarget as HTMLElement).getBoundingClientRect()
    menu.value = {
        open: true,
        x: r.right - 176,
        y: r.bottom + 6,
        slug: row.slug,
        publicUrl: getPublicUrl(row)
    }
}

function closeMenu() { menu.value.open = false }
function onKey(e: KeyboardEvent) { if (e.key === 'Escape') closeMenu() }
onMounted(() => window.addEventListener('keydown', onKey))
onUnmounted(() => window.removeEventListener('keydown', onKey))

function destroyPost(slug: string) {
    if (!confirm('Weet je zeker dat je dit Innovatie artikel wilt verwijderen?')) return
    router.delete(`/hoofdbeheerder/innovatie/${slug}`, {
        preserveScroll: true,
        onFinish: closeMenu
    })
}

function formatDatum(dt: string | null) {
    if (!dt) return '—'
    const d = new Date(dt)
    return d.toLocaleDateString('nl-NL', { day: '2-digit', month: '2-digit', year: 'numeric' })
        + ' ' + d.toLocaleTimeString('nl-NL', { hour: '2-digit', minute: '2-digit' })
}

function mediaTypeBadge(type?: string) {
    switch (type) {
        case 'youtube':
            return { text: 'YouTube', variant: 'destructive' as const, icon: Youtube }
        case 'upload':
            return { text: 'Video Upload', variant: 'destructive' as const, icon: Upload }
        case 'image':
            return { text: 'Afbeelding', variant: 'default' as const, icon: Camera }
        default:
            return { text: 'Geen cover', variant: 'secondary' as const, icon: Camera }
    }
}

function decodeEntities(html?: string) {
    if (!html) return ''
    const el = document.createElement('textarea')
    el.innerHTML = html
    return el.value
}

const normLinks = computed(() => (props.posts.links ??  []).map(l => {
    const raw = decodeEntities(l.label?. toString())
    const low = raw.toLowerCase()
    const type =
        low.includes('pagination. previous') || low.includes('previous') || raw.includes('«')
            ? 'prev'
            : low.includes('pagination.next') || low.includes('next') || raw.includes('»')
                ? 'next'
                : 'page'
    const text = type === 'prev' ?  'Vorige' : type === 'next' ? 'Volgende' : raw
    return { ... l, _type: type, _text: text }
}))
</script>

<template>
    <Head title="Over Ons Blog's" />

    <AdminAppLayout
        :breadcrumbs="[
            { title: 'Dashboard', href: routes.admin.dashboard },
            { title: pageTitle, href: routes.admin.overonsBlog }
        ]"
    >
        <div class="space-y-6 p-6 lg:p-8">
            <div>
                <h1 class="text-3xl font-bold tracking-tight">{{ pageTitle }}</h1>
                <p class="mt-2 text-muted-foreground">
                    {{ pageDesc }}
                </p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <Card class="transition-shadow hover:shadow-lg">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Totaal Blog's</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold">{{ stats.total }}</div>
                        <p class="text-xs text-muted-foreground">Alle artikelen</p>
                    </CardContent>
                </Card>

                <Card class="transition-shadow hover:shadow-lg">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Gepubliceerd</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold">{{ stats.published }}</div>
                        <p class="text-xs text-muted-foreground">Zichtbaar voor iedereen</p>
                    </CardContent>
                </Card>

                <Card class="transition-shadow hover:shadow-lg">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Concepten</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold">{{ stats.drafts }}</div>
                        <p class="text-xs text-muted-foreground">Nog niet gepubliceerd</p>
                    </CardContent>
                </Card>

                <Card class="transition-shadow hover:shadow-lg">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Per pagina</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold">{{ stats.per_page }}</div>
                        <p class="text-xs text-muted-foreground">Resultaten per pagina</p>
                    </CardContent>
                </Card>
            </div>

            <Card class="border-0 shadow-lg">
                <CardHeader class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <CardTitle class="text-xl">Alle Innovatie Artikelen</CardTitle>
                    <div class="flex flex-wrap gap-2">
                        <Input
                            v-model="q"
                            placeholder="Zoek innovatie..."
                            class="w-full sm:w-[260px]"
                            @keydown.enter.prevent="doSearch"
                        />
                        <Button @click="doSearch" variant="secondary">Zoeken</Button>
                        <Link href="/hoofdbeheerder/innovatie/aanmaken">
                            <Button class="inline-flex items-center gap-2">
                                <Plus class="h-4 w-4" /> Maak artikel
                            </Button>
                        </Link>
                    </div>
                </CardHeader>

                <CardContent class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                        <tr class="border-b text-muted-foreground">
                            <th class="px-4 py-3 text-left font-medium lg:px-6">Titel</th>
                            <th class="px-4 py-3 text-left font-medium lg:px-6">Categorie</th>
                            <th class="px-4 py-3 text-left font-medium lg:px-6">Status</th>
                            <th class="px-4 py-3 text-left font-medium lg:px-6">Media Type</th>
                            <th class="px-4 py-3 text-left font-medium lg:px-6">Download</th>
                            <th class="hidden px-4 py-3 text-left font-medium md:table-cell lg:px-6">Gepubliceerd</th>
                            <th class="hidden px-4 py-3 text-left font-medium md:table-cell lg:px-6">Aangemaakt</th>
                            <th class="px-4 py-3 text-right font-medium lg:px-6">Acties</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr
                            v-for="row in posts.data"
                            :key="row.id"
                            class="border-b transition-colors hover:bg-muted/50 last:border-0"
                        >
                            <td class="px-4 py-4 lg:px-6">
                                <div class="font-medium">{{ row.title }}</div>
                                <div class="text-xs text-muted-foreground">/innovatie/{{ row.slug }}</div>
                            </td>
                            <td class="px-4 py-4 lg:px-6">
                                <div v-if="row.category" class="inline-flex items-center gap-1.5 rounded-full border px-2 py-0.5 text-xs font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 border-transparent bg-secondary text-secondary-foreground hover:bg-secondary/80">
                                    <span :class="['w-2 h-2 rounded-full', row.category.color]"></span>
                                    {{ row.category.name }}
                                </div>
                                <span v-else class="text-muted-foreground text-xs">—</span>
                            </td>
                            <td class="px-4 py-4 lg:px-6">
                                <Badge v-if="row.is_published" variant="default">Gepubliceerd</Badge>
                                <Badge v-else variant="secondary">Concept</Badge>
                            </td>
                            <td class="px-4 py-4 lg:px-6">
                                <template v-if="row.media_type">
                                    <Badge :variant="mediaTypeBadge(row.media_type).variant" class="gap-1">
                                        <component :is="mediaTypeBadge(row.media_type).icon" class="h-3 w-3" />
                                        {{ mediaTypeBadge(row.media_type).text }}
                                    </Badge>
                                </template>
                                <span v-else class="text-muted-foreground">—</span>
                            </td>
                            <td class="px-4 py-4 lg:px-6">
                                <Badge
                                    v-if="row.download_file_path"
                                    variant="default"
                                    class="gap-1 bg-blue-100 text-blue-800 hover:bg-blue-200 dark:bg-blue-900/30 dark:text-blue-400"
                                >
                                    <Download class="h-3 w-3" /> Ja
                                </Badge>
                                <span v-else class="text-muted-foreground">—</span>
                            </td>
                            <td class="hidden px-4 py-4 md:table-cell lg:px-6">
                                {{ formatDatum(row.published_at ?? null) }}
                            </td>
                            <td class="hidden px-4 py-4 md:table-cell lg:px-6">
                                {{ formatDatum(row.created_at) }}
                            </td>
                            <td class="px-4 py-4 lg:px-6">
                                <div class="flex justify-end">
                                    <button
                                        type="button"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-md transition-colors hover:bg-accent"
                                        @click="openRowMenu($event, row)"
                                    >
                                        <MoreVertical class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!posts.data.length">
                            <td colspan="7" class="px-6 py-10 text-center text-muted-foreground">
                                Nog geen innovatie artikelen.
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </CardContent>
            </Card>

            <nav v-if="normLinks.length > 2" class="flex w-full justify-center" role="navigation" aria-label="Paginatie">
                <ul class="inline-flex items-center gap-2">
                    <li v-for="(l, i) in normLinks" :key="i">
                        <Link v-if="l._type === 'prev'" :href="l.url || '#'" :aria-disabled="!l.url">
                            <Button
                                variant="outline"
                                size="sm"
                                class="gap-1"
                                :class="!l.url ? 'pointer-events-none opacity-50' : ''"
                            >
                                <ChevronLeft class="h-4 w-4" />
                                <span class="hidden sm:inline">{{ l._text }}</span>
                            </Button>
                        </Link>

                        <Link
                            v-else-if="l._type === 'page'"
                            :href="l.url || '#'"
                            :aria-current="l.active ? 'page' : undefined"
                        >
                            <Button
                                size="sm"
                                :variant="l.active ? 'default' : 'outline'"
                                class="min-w-9"
                                :class="!l.url && !l.active ? 'pointer-events-none opacity-50' : ''"
                            >
                                {{ l._text }}
                            </Button>
                        </Link>

                        <Link v-else :href="l.url || '#'" :aria-disabled="!l.url">
                            <Button
                                variant="outline"
                                size="sm"
                                class="gap-1"
                                :class="!l.url ? 'pointer-events-none opacity-50' : ''"
                            >
                                <span class="hidden sm:inline">{{ l._text }}</span>
                                <ChevronRight class="h-4 w-4" />
                            </Button>
                        </Link>
                    </li>
                </ul>
            </nav>
        </div>

        <Teleport to="body">
            <div v-if="menu.open">
                <div class="fixed inset-0 z-40" @click="closeMenu"></div>
                <div
                    class="fixed z-50 w-44 rounded-md border bg-popover p-1 shadow-lg"
                    :style="{ top: menu.y + 'px', left: menu.x + 'px' }"
                >
                    <Link
                        :href="menu.publicUrl"
                        target="_blank"
                        rel="noopener"
                        class="flex w-full items-center gap-2 rounded-sm px-2 py-1.5 text-sm transition-colors hover:bg-accent"
                        @click="closeMenu"
                    >
                        <Globe class="h-4 w-4" /> Bekijk pagina
                    </Link>

                    <Link
                        :href="`/hoofdbeheerder/innovatie/${menu.slug}/bewerken`"
                        class="flex w-full items-center gap-2 rounded-sm px-2 py-1.5 text-sm transition-colors hover:bg-accent"
                        @click="closeMenu"
                    >
                        <Pencil class="h-4 w-4" /> Bewerken
                    </Link>

                    <button
                        type="button"
                        class="flex w-full items-center gap-2 rounded-sm px-2 py-1.5 text-sm text-red-600 transition-colors hover:bg-red-50 dark:hover:bg-red-900/20"
                        @click="destroyPost(menu.slug)"
                    >
                        <Trash2 class="h-4 w-4" /> Verwijderen
                    </button>
                </div>
            </div>
        </Teleport>

    </AdminAppLayout>
</template>
