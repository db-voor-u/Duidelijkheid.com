<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { computed, ref, onMounted, onBeforeUnmount } from 'vue'
import AdminAppLayout from '@/layouts/AdminAppLayout.vue'
import {
    Card, CardContent, CardDescription, CardHeader, CardTitle
} from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import {
    Users, FolderKanban, CheckSquare, Activity, TrendingUp,
    HardDrive, Cpu, Clock, Zap, Shield, FileText, Plus, Moon, Sun,
    Inbox, Mail,  Server, Rocket, BrainCircuit, Lightbulb, ArrowRight,
} from 'lucide-vue-next'
import { routes } from '@/lib/routes'


// -------- DARK MODE --------
import { useDark, useToggle } from '@vueuse/core'

/* ================== Types ================== */
interface DiskUsage { total:string; used:string; free:string; percentage:number }
interface MemUsage  { current:string; limit:string; percentage:number }
interface Stats {
    total_users: number
    active_projects: number   // = aantal blogs
    over_ons_count: number    // = aantal over ons blogs
    innovatie_count: number   // = aantal innovatie blogs
    pending_tasks: number     // bijv. aantal open contact-berichten
    system_status: 'online'|'offline'
    disk_usage?: DiskUsage | null
    memory_usage?: MemUsage | null
}
interface ActivityItem { id:number; user:string; action:string; description:string; created_at:string }

/* Inbox preview (optioneel) */
interface ContactPreviewItem {
    id:number; name:string; email:string; subject:string; status:'new'|'read'|'replied'|'closed'|'archived'; created_at:string
}
interface ContactSummary {
    total:number; unread:number; new_today:number; latest: ContactPreviewItem[]
}

/* Versies / queue (optioneel) */
interface Versions { php?:string; laravel?:string; node?:string; db_label?:string; db_version?:string; os?:string; server?:string }
interface QueueMetrics { pending?:number; failed?:number }

interface Props {
    admin: { id:number; name:string; email:string; created_at:string; last_login?:string }
    stats: Stats
    recentActivity?: ActivityItem[]
    contactSummary?: ContactSummary        // ← optioneel
    versions?: Versions                    // ← optioneel
    queue?: QueueMetrics                   // ← optioneel
}
const props = defineProps<Props>()

/* -------- DARK MODE REF -------- */
const isDark = useDark({
    selector: 'html',
    attribute: 'class',
    valueDark: 'dark',
    valueLight: '',
    storageKey: 'theme',
})
const toggleDark = useToggle(isDark)

/* -------- Helpers -------- */
function getStatusColor(status: string): string {
    return status === 'online' ? 'bg-green-500' : 'bg-red-500'
}
function getStatusPulse(status: string): string {
    return status === 'online' ? 'animate-pulse' : ''
}
function getProgressColor(p: number): string {
    return p < 50 ? 'bg-green-500' : p < 80 ? 'bg-yellow-500' : 'bg-red-500'
}
function fmtAgo(iso:string){
    const d = new Date(iso)
    const diff = (Date.now()-d.getTime())/1000
    if (diff < 60) return `${Math.floor(diff)}s`
    if (diff < 3600) return `${Math.floor(diff/60)}m`
    if (diff < 86400) return `${Math.floor(diff/3600)}u`
    return `${Math.floor(diff/86400)}d`
}

/* -------- Veilige fallbacks voor optionele props -------- */
const contact = computed<ContactSummary>(() => props.contactSummary ?? {
    total: 0, unread: 0, new_today: 0, latest: []
})
const versions = computed<Versions>(() => props.versions ?? {})
const queue = computed<QueueMetrics>(() => props.queue ?? {})

/* -------- Tijd (veilig i.v.m. SSR/HMR) -------- */
const currentTime = ref<string>('—')
let timer: number | undefined
onMounted(() => {
    const update = () => {
        currentTime.value = new Date().toLocaleString('nl-NL', {
            weekday: 'long', year: 'numeric', month: 'long', day: 'numeric',
            hour: '2-digit', minute: '2-digit'
        })
    }
    update()
    timer = window.setInterval(update, 60_000) as unknown as number;
})
onBeforeUnmount(() => { if (timer) window.clearInterval(timer) })

/* -------- Breadcrumbs -------- */
const breadcrumbs = [{ title: 'Dashboard', href: routes.admin.dashboard }]
</script>

<template>
    <Head title="Admin Dashboard - Duidelijkheid.com" />

    <AdminAppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-8 space-y-8">

            <!-- ========== HERO ========== -->
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-blue-600 via-purple-600 to-pink-600 p-8 text-white shadow-2xl">
                <div class="absolute inset-0 bg-black/20"></div>
                <div class="relative z-10">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="mb-2 text-4xl font-bold">Welkom terug, {{ admin.name }}! 👋</h1>
                            <p class="mb-1 text-lg text-blue-100">Hoofdbeheerder van Duidelijkheid.com</p>
                            <p class="flex items-center gap-2 text-sm text-blue-200">
                                <Clock class="h-4 w-4" /> {{ currentTime }}
                            </p>
                        </div>
                        <div class="hidden items-center gap-3 md:flex">
                            <div class="text-right">
                                <p class="text-sm text-blue-200">Laatste login</p>
                                <p class="font-semibold">{{ admin.last_login || 'Eerste keer' }}</p>
                            </div>
                            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-white/20 backdrop-blur">
                                <Shield class="h-6 w-6" />
                            </div>
                            <!-- DARK MODE -->
                            <button
                                aria-label="Donker/Licht modus wisselen"
                                :aria-pressed="isDark"
                                class="ml-4 flex h-12 w-12 items-center justify-center rounded-full bg-white/20 backdrop-blur transition hover:bg-white/30"
                                @click="toggleDark()"
                            >
                                <Sun v-if="isDark" class="h-6 w-6 text-yellow-300" />
                                <Moon v-else class="h-6 w-6 text-blue-200" />
                            </button>
                        </div>
                    </div>


                    <!-- Quick actions removed from header as requested -->
                </div>
                <div class="pointer-events-none absolute -top-10 -right-10 h-40 w-40 rounded-full bg-white/10 blur-3xl"></div>
                <div class="pointer-events-none absolute -bottom-10 -left-10 h-40 w-40 rounded-full bg-white/10 blur-3xl"></div>
            </div>

            <!-- ========== QUICK ACTIONS (TILES) ========== -->
            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                <!-- Nieuw Blog -->
                <Link :href="routes.admin.blogCreate" 
                      class="group relative overflow-hidden rounded-xl border bg-card p-6 shadow-sm transition-all hover:shadow-md hover:border-blue-500/50">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 group-hover:bg-blue-600 group-hover:text-white transition-colors duration-300">
                            <Plus class="h-6 w-6" />
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold group-hover:text-blue-600 transition-colors">Nieuw Blogbericht</h3>
                            <p class="text-sm text-muted-foreground">Schrijf een nieuw artikel voor de blog.</p>
                        </div>
                    </div>
                </Link>

                <!-- Nieuw Innovatie Item -->
                <Link :href="routes.admin.overonsBlogCreate + '?section=innovatie'" 
                      class="group relative overflow-hidden rounded-xl border bg-card p-6 shadow-sm transition-all hover:shadow-md hover:border-amber-500/50">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400 group-hover:bg-amber-600 group-hover:text-white transition-colors duration-300">
                             <div class="relative">
                                <Lightbulb class="h-6 w-6" />
                                <Plus class="absolute -top-1 -right-1 h-3 w-3 bg-white dark:bg-zinc-950 rounded-full" />
                            </div>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold group-hover:text-amber-600 transition-colors">Nieuw Innovatie Item</h3>
                            <p class="text-sm text-muted-foreground">Voeg een nieuw item toe aan de Innovatie pagina.</p>
                        </div>
                    </div>
                </Link>

                <!-- Nieuw Over Ons Item -->
                <Link :href="routes.admin.overonsBlogCreate + '?section=over_ons'" 
                      class="group relative overflow-hidden rounded-xl border bg-card p-6 shadow-sm transition-all hover:shadow-md hover:border-teal-500/50">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-teal-100 dark:bg-teal-900/20 text-teal-600 dark:text-teal-400 group-hover:bg-teal-600 group-hover:text-white transition-colors duration-300">
                            <div class="relative">
                                <Users class="h-6 w-6" />
                                <Plus class="absolute -top-1 -right-1 h-3 w-3 bg-white dark:bg-zinc-950 rounded-full" />
                            </div>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold group-hover:text-teal-600 transition-colors">Nieuw Over Ons Item</h3>
                            <p class="text-sm text-muted-foreground">Tijdlijn, teamlid of informatie over ons.</p>
                        </div>
                    </div>
                </Link>
            </div>

            <!-- ========== KPI CARDS ========== -->
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                <!-- Gebruikers -->
                <div class="group relative overflow-hidden rounded-xl border bg-card text-card-foreground shadow transition-all hover:shadow-lg">
                    <Link :href="routes.admin.users" class="absolute inset-0 z-10" aria-label="Ga naar Gebruikers" />
                    <div class="p-6">
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                                <Users class="h-6 w-6" />
                            </div>
                            <div>
                                <h3 class="font-semibold tracking-tight">Gebruikers</h3>
                                <p class="text-sm text-muted-foreground">Beheer accounts</p>
                            </div>
                        </div>
                        <div class="mt-4 flex items-center justify-between">
                            <div class="text-2xl font-bold">{{ stats.total_users }}</div>
                            <div class="flex items-center text-xs text-muted-foreground group-hover:text-primary">
                                Bekijken <ArrowRight class="ml-1 h-3 w-3 transition-transform group-hover:translate-x-0.5" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Blogs -->
                <div class="group relative overflow-hidden rounded-xl border bg-card text-card-foreground shadow transition-all hover:shadow-lg">
                    <Link :href="routes.admin.blog" class="absolute inset-0 z-10" aria-label="Ga naar Blog-overzicht" />
                    <div class="p-6">
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400">
                                <FolderKanban class="h-6 w-6" />
                            </div>
                            <div>
                                <h3 class="font-semibold tracking-tight">Standaard Blogs</h3>
                                <p class="text-sm text-muted-foreground">Nieuws & Updates</p>
                            </div>
                        </div>
                        <div class="mt-4 flex items-center justify-between">
                            <div class="text-2xl font-bold">{{ stats.active_projects }}</div>
                            <div class="flex items-center text-xs text-muted-foreground group-hover:text-primary">
                                Bekijken <ArrowRight class="ml-1 h-3 w-3 transition-transform group-hover:translate-x-0.5" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Innovatie -->
                <div class="group relative overflow-hidden rounded-xl border bg-card text-card-foreground shadow transition-all hover:shadow-lg">
                    <Link href="/hoofdbeheerder/innovatie" class="absolute inset-0 z-10" aria-label="Ga naar Innovatie Blogs" />
                    <div class="p-6">
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400">
                                <Lightbulb class="h-6 w-6" />
                            </div>
                            <div>
                                <h3 class="font-semibold tracking-tight">Innovatie</h3>
                                <p class="text-sm text-muted-foreground">Beheer innovatie artikelen</p>
                            </div>
                        </div>
                        <div class="mt-4 flex items-center justify-between">
                            <div class="text-2xl font-bold">{{ stats.innovatie_count }}</div>
                            <div class="flex items-center text-xs text-muted-foreground group-hover:text-primary">
                                Bekijken <ArrowRight class="ml-1 h-3 w-3 transition-transform group-hover:translate-x-0.5" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Over Ons Blogs -->
                <div class="group relative overflow-hidden rounded-xl border bg-card text-card-foreground shadow transition-all hover:shadow-lg">
                    <Link :href="routes.admin.overonsBlog" class="absolute inset-0 z-10" aria-label="Ga naar OverOns Blogs" />
                    <div class="p-6">
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-teal-100 text-teal-600 dark:bg-teal-900/30 dark:text-teal-400">
                                <Users class="h-6 w-6" />
                            </div>
                            <div>
                                <h3 class="font-semibold tracking-tight">Over Ons Blogs</h3>
                                <p class="text-sm text-muted-foreground">Beheer over ons verhalen</p>
                            </div>
                        </div>
                        <div class="mt-4 flex items-center justify-between">
                            <div class="text-2xl font-bold">{{ stats.over_ons_count }}</div>
                            <div class="flex items-center text-xs text-muted-foreground group-hover:text-primary">
                                Bekijken <ArrowRight class="ml-1 h-3 w-3 transition-transform group-hover:translate-x-0.5" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Openstaande Taken -->
                <div class="group relative overflow-hidden rounded-xl border bg-card text-card-foreground shadow transition-all hover:shadow-lg">
                    <Link :href="routes.admin.contact" class="absolute inset-0 z-10" aria-label="Ga naar Contact-inbox" />
                    <div class="p-6">
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-orange-100 text-orange-600 dark:bg-orange-900/30 dark:text-orange-400">
                                <CheckSquare class="h-6 w-6" />
                            </div>
                            <div>
                                <h3 class="font-semibold tracking-tight">Openstaande Taken</h3>
                                <p class="text-sm text-muted-foreground">Inbox & Berichten</p>
                            </div>
                        </div>
                        <div class="mt-4 flex items-center justify-between">
                            <div class="text-2xl font-bold">{{ stats.pending_tasks }}</div>
                            <div class="flex items-center text-xs text-muted-foreground group-hover:text-primary">
                                Beheren <ArrowRight class="ml-1 h-3 w-3 transition-transform group-hover:translate-x-0.5" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Status -->
                <div class="group relative overflow-hidden rounded-xl border bg-card text-card-foreground shadow transition-all hover:shadow-lg">
                    <div class="p-6">
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400">
                                <Activity class="h-6 w-6" />
                            </div>
                            <div>
                                <h3 class="font-semibold tracking-tight">Systeem Status</h3>
                                <p class="text-sm text-muted-foreground">Server gezondheid</p>
                            </div>
                        </div>
                        <div class="mt-4 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div class="h-3 w-3 rounded-full" :class="[getStatusColor(stats.system_status), getStatusPulse(stats.system_status)]"></div>
                                <span class="text-xl font-bold capitalize">{{ stats.system_status }}</span>
                            </div>
                            <div class="flex items-center text-xs text-muted-foreground">
                                <Zap class="mr-1 h-3 w-3" /> Operationeel
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- GRID: INBOX + VERSIES/RESOURCE TILES -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

                <!-- CONTACT INBOX PREVIEW -->
                <Card class="transition-shadow hover:shadow-lg">
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <div>
                                <CardTitle class="flex items-center gap-2">
                                    <Inbox class="h-5 w-5 text-primary" /> Contact-inbox
                                </CardTitle>
                                <CardDescription class="mt-1">
                                    {{ contact.unread }} ongelezen • {{ contact.new_today }} nieuw vandaag
                                </CardDescription>
                            </div>
                            <Link :href="routes.admin.contact" class="rounded-md bg-primary px-3 py-1.5 text-sm font-medium text-primary-foreground hover:opacity-90">
                                Inbox openen
                            </Link>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div v-if="contact.latest.length" class="space-y-2">
                            <div v-for="m in contact.latest" :key="m.id"
                                 class="flex items-center gap-3 rounded-lg border p-3 transition-colors hover:bg-accent/50">
                                <div class="flex h-9 w-9 items-center justify-center rounded-full bg-primary/10">
                                    <Mail class="h-4 w-4 text-primary" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium">{{ m.subject }}</p>
                                    <p class="truncate text-xs text-muted-foreground">{{ m.name }} • {{ m.email }}</p>
                                </div>
                                <Badge :variant="m.status === 'new' ? 'default' : (m.status === 'replied' ? 'secondary' : 'outline')" class="shrink-0 text-xs capitalize">
                                    {{ m. status }}
                                </Badge>
                                <span class="w-10 shrink-0 text-right text-xs text-muted-foreground">{{ fmtAgo(m. created_at) }}</span>
                            </div>
                        </div>
                        <div v-else class="flex flex-col items-center justify-center py-8 text-center">
                            <Inbox class="mb-2 h-12 w-12 text-muted-foreground/50" />
                            <p class="text-sm text-muted-foreground">Nog geen berichten ontvangen</p>
                        </div>
                    </CardContent>
                </Card>

                <!-- VERSIES & QUEUE -->
                <Card class="transition-shadow hover:shadow-lg">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Server class="h-5 w-5 text-primary" /> Versies & Wachtrij
                        </CardTitle>
                        <CardDescription>Omgevingsinfo (optioneel)</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                            <div class="rounded-lg border bg-card p-3">
                                <p class="text-xs font-medium text-muted-foreground">PHP</p>
                                <p class="mt-1 text-lg font-bold">{{ versions.php || '—' }}</p>
                            </div>
                            <div class="rounded-lg border bg-card p-3">
                                <p class="text-xs font-medium text-muted-foreground">Laravel</p>
                                <p class="mt-1 text-lg font-bold">{{ versions. laravel || '—' }}</p>
                            </div>
                            <div class="rounded-lg border bg-card p-3">
                                <p class="text-xs font-medium text-muted-foreground">Node</p>
                                <p class="mt-1 text-lg font-bold">{{ versions.node || '—' }}</p>
                            </div>

                            <!-- Dynamic Database Version -->
                            <div class="rounded-lg border bg-card p-3">
                                <p class="text-xs font-medium text-muted-foreground">{{ versions.db_label || 'Database' }}</p>
                                <p class="mt-1 text-lg font-bold">{{ versions.db_version || '—' }}</p>
                            </div>

                            <!-- OS -->
                            <div v-if="versions.os" class="rounded-lg border bg-card p-3">
                                <p class="text-xs font-medium text-muted-foreground">OS</p>
                                <p class="mt-1 text-xs font-bold leading-tight" :title="versions.os">{{ versions.os.split(' ')[0] }}</p>
                            </div>

                            <!-- Server -->
                            <div v-if="versions.server" class="rounded-lg border bg-card p-3">
                                <p class="text-xs font-medium text-muted-foreground">Web Server</p>
                                <p class="mt-1 text-xs font-bold leading-tight" :title="versions.server">{{ versions.server.split('/')[0] }}</p>
                            </div>

                            <div v-if="queue.pending !== undefined && queue.pending !== null" class="rounded-lg border bg-card p-3">
                                <p class="text-xs font-medium text-muted-foreground">Wachtrij</p>
                                <p class="mt-1 text-lg font-bold">{{ queue.pending }}</p>
                            </div>
                            <div v-if="queue.failed !== undefined && queue.failed !== null" class="rounded-lg border bg-card p-3">
                                <p class="text-xs font-medium text-muted-foreground">Mislukt</p>
                                <p class="mt-1 text-lg font-bold" :class="(queue.failed ?? 0) > 0 ? 'text-red-600' : ''">{{ queue.failed }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- SYSTEM RESOURCES -->
            <div v-if="stats.disk_usage || stats.memory_usage" class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <!-- Disk -->
                <Card v-if="stats.disk_usage" class="transition-shadow hover:shadow-lg">
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <div>
                                <CardTitle class="flex items-center gap-2">
                                    <HardDrive class="h-5 w-5 text-blue-600" /> Schijfgebruik
                                </CardTitle>
                                <CardDescription>Server opslag status</CardDescription>
                            </div>
                            <Badge :variant="stats.disk_usage.percentage > 80 ? 'destructive' : 'outline'" class="text-xs">
                                {{ stats.disk_usage.percentage }}%
                            </Badge>
                        </div>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div>
                            <div class="mb-2 flex justify-between text-sm font-medium">
                                <span>{{ stats.disk_usage.used }}</span>
                                <span class="text-muted-foreground">{{ stats.disk_usage. total }}</span>
                            </div>
                            <div class="h-3 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                                <div class="h-3 rounded-full transition-all duration-500"
                                     :class="getProgressColor(stats.disk_usage.percentage)"
                                     :style="{ width: stats.disk_usage.percentage + '%' }"></div>
                            </div>
                        </div>
                        <div class="flex justify-between text-xs text-muted-foreground">
                            <span>Gebruikt</span>
                            <span>Beschikbaar: {{ stats.disk_usage.free }}</span>
                        </div>
                    </CardContent>
                </Card>

                <!-- Memory -->
                <Card v-if="stats.memory_usage" class="transition-shadow hover:shadow-lg">
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <div>
                                <CardTitle class="flex items-center gap-2">
                                    <Cpu class="h-5 w-5 text-purple-600" /> Geheugengebruik
                                </CardTitle>
                                <CardDescription>PHP geheugen status</CardDescription>
                            </div>
                            <Badge :variant="stats.memory_usage.percentage > 80 ? 'destructive' : 'outline'" class="text-xs">
                                {{ stats.memory_usage.percentage }}%
                            </Badge>
                        </div>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div>
                            <div class="mb-2 flex justify-between text-sm font-medium">
                                <span>{{ stats.memory_usage.current }}</span>
                                <span class="text-muted-foreground">{{ stats.memory_usage.limit }}</span>
                            </div>
                            <div class="h-3 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                                <div class="h-3 rounded-full transition-all duration-500"
                                     :class="getProgressColor(stats.memory_usage.percentage)"
                                     :style="{ width: stats.memory_usage.percentage + '%' }"></div>
                            </div>
                        </div>
                        <div class="flex justify-between text-xs text-muted-foreground">
                            <span>In gebruik</span>
                            <span>Limiet</span>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- RECENTE ACTIVITEIT -->
            <Card v-if="recentActivity && recentActivity.length" class="transition-shadow hover:shadow-lg">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Activity class="h-5 w-5 text-orange-600" /> Recente Activiteit
                    </CardTitle>
                    <CardDescription>Laatste systeemactiviteiten</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-3">
                        <div v-for="a in recentActivity" :key="a.id"
                             class="group flex items-start gap-4 rounded-lg border p-4 transition-colors hover:bg-accent/50">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary/10 transition-colors group-hover:bg-primary/20">
                                <FileText class="h-5 w-5 text-primary" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="mb-1 flex flex-wrap items-center gap-2">
                                    <p class="text-sm font-semibold">{{ a.user }}</p>
                                    <Badge variant="secondary" class="text-xs">{{ a.action }}</Badge>
                                </div>
                                <p class="text-sm text-muted-foreground">{{ a.description }}</p>
                                <p class="mt-1 flex items-center gap-1 text-xs text-muted-foreground">
                                    <Clock class="h-3 w-3" /> {{ a.created_at }}
                                </p>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AdminAppLayout>
</template>
