<script setup lang="ts">
import { ref, watch } from 'vue' // 'computed' is niet meer nodig voor filteren
import { Head, Link, useForm, router } from '@inertiajs/vue3' // 'router' toegevoegd
import AdminAppLayout from '@/layouts/AdminAppLayout.vue'
import { debounce } from 'lodash' // Zorg dat je lodash hebt, of zie de watcher hieronder zonder debounce

// UI Components imports (deze blijven hetzelfde)
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Separator } from '@/components/ui/separator'
import {
    Table, TableHeader, TableHead, TableRow, TableBody, TableCell
} from '@/components/ui/table'
import {
    DropdownMenu, DropdownMenuTrigger, DropdownMenuContent, DropdownMenuItem
} from '@/components/ui/dropdown-menu'
import {
    Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter
} from '@/components/ui/dialog'
import {
    AlertDialog, AlertDialogContent, AlertDialogHeader, AlertDialogTitle, AlertDialogDescription,
    AlertDialogFooter, AlertDialogCancel
} from '@/components/ui/alert-dialog'
import { Tabs, TabsList, TabsTrigger, TabsContent } from '@/components/ui/tabs'
import { 
    MoreHorizontal, MoreVertical, Archive, Trash2, Mail, CheckCircle2, RotateCcw, 
    XCircle, Undo2, Ban, Inbox, Eye, Reply, Search, ShieldAlert, Loader2,
    Paperclip, ArrowUpRight, ArrowDownLeft
} from 'lucide-vue-next'
import { toast } from 'vue-sonner'

// Types aangepast voor Pagination structuur
type ContactMessage = {
    id: number
    name: string
    email: string
    phone?: string | null
    subject: string
    message: string
    status: 'new'|'read'|'replied'|'closed'|'archived'
    is_spam: boolean
    created_at: string
    duration_ms?: number
    ip?: string
    referer?: string
    has_attachment?: boolean
}

type PaginationLinks = {
    url: string | null
    label: string
    active: boolean
}

type PaginatedMessages = {
    data: ContactMessage[]
    links: PaginationLinks[]
    current_page: number
    last_page: number
    total: number
}

type Stats = {
    total: number
    new: number
    read: number // 'unread' heette 'read' in je controller stats
    spam: number
    archived: number
}

// Props ontvangen nu het paginatie object, plus de huidige filters van de controller
const props = defineProps<{
    messages: PaginatedMessages
    stats?: Stats
    filters?: { search: string; status: string; spam: boolean }
}>()

/* ---------- Breadcrumbs ---------- */
const breadcrumbs = [
    { title: 'Dashboard', href: '/hoofdbeheerder/dashboard' },
    { title: 'Contact', href: '/hoofdbeheerder/contact' },
]

/* ---------- Filters/zoeken (Server Side) ---------- */
// We vullen de refs met de data die terugkomt uit de controller (zodat je zoekterm blijft staan na reload)
const q = ref(props.filters?.search || '')
const status = ref(props.filters?.status || '') // let op: lege string = alle statussen
const onlySpam = ref(props.filters?.spam || false)

// Functie om de backend aan te roepen bij wijzigingen
const updateParams = () => {
    router.get('/hoofdbeheerder/contact', {
        search: q.value,
        status: status.value === 'all' ? null : status.value, // stuur null als 'all' is gekozen
        spam: onlySpam.value ? '1' : '0'
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    })
}

// Watchers: Als je typt of klikt, update de pagina
let timeout: ReturnType<typeof setTimeout>
watch(q, (newVal) => {
    clearTimeout(timeout)
    timeout = setTimeout(() => updateParams(), 500) // Wacht 500ms na typen
})

watch([status, onlySpam], () => {
    updateParams()
})

/* ---------- Helpers ---------- */
const short = (s:string, n=90) => s.length > n ? s.slice(0, n-1) + '…' : s
const dt = (iso?: string|null) => iso ? new Date(iso).toLocaleString('nl-NL', { day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit' }) : '—'
const msToSec = (ms?: number) => typeof ms === 'number' ? Math.round(ms/1000) : null
const statusBadge = (s:ContactMessage['status']) => {
    switch (s) {
        case 'new': return 'default' // Let op: check of 'default' bestaat in je Badge component, anders 'primary'
        case 'read': return 'secondary'
        case 'replied': return 'outline'
        case 'archived': return 'secondary'
        case 'closed': return 'secondary'
        default: return 'outline'
    }
}
const statusLabel = (s:ContactMessage['status']) => {
    switch (s) {
        case 'new': return 'Nieuw'
        case 'read': return 'Gelezen'
        case 'replied': return 'Beantwoord'
        case 'archived': return 'Gearchiveerd'
        case 'closed': return 'Gesloten'
        default: return s
    }
}

/* ---------- Selectie / dialogen ---------- */
const viewOpen = ref(false)
const replyOpen = ref(false)
const deleteOpen = ref(false)
const selected = ref<ContactMessage | null>(null)
const history = ref<any[]>([])
const historyLoading = ref(false)
const replyFileInput = ref<HTMLInputElement | null>(null)

function clearReplyAttachment() {
    replyForm.attachment = null
    if (replyFileInput.value) replyFileInput.value.value = ''
}

// Actie formulieren
const replyForm = useForm({
    to_email: '',
    to_name: '',
    subject: '',
    body: '',
    cc: '',
    bcc: '',
    attachment: null as File | null
})
const deleteForm = useForm({})
const updateStatusForm = useForm({}) // Generieke form voor status updates

// Open View Dialog
async function openView(m: ContactMessage){
    selected.value = m
    history.value = []
    historyLoading.value = true
    viewOpen.value = true

    // Fetch history
    try {
        const response = await fetch(`/hoofdbeheerder/contact/${m.id}/history`)
        if(response.ok) {
            history.value = await response.json()
        }
    } catch(e) {
        console.error('Failed to load history', e)
    } finally {
        historyLoading.value = false
    }
}

function openReply(m:ContactMessage){
    selected.value = m
    replyForm.to_email = m.email
    replyForm.to_name = m.name
    replyForm.subject = `Re: ${m.subject}`
    replyForm.cc = ''
    replyForm.bcc = ''
    replyForm.attachment = null
    replyForm.body = `Beste ${m.name},\n\n\n\nMet vriendelijke groet,\n\nTeam Duidelijkheid`
    replyOpen.value = true
}

function openDelete(m:ContactMessage){ selected.value = m; deleteOpen.value = true }

/* Acties die server aanroepen */
function updateStatus(m:ContactMessage, action: string) {
    router.put(`/hoofdbeheerder/contact/${m.id}/status`, { action }, {
        preserveScroll: true,
        onSuccess: () => toast.success('Status bijgewerkt')
    })
}

function destroy(){
    if(!selected.value) return
    deleteForm.delete(`/hoofdbeheerder/contact/${selected.value.id}`, {
        preserveScroll:true,
        onSuccess:()=>{ deleteOpen.value = false; toast.success('Verwijderd') }
    })
}

const replySuccess = ref(false)

function sendReply(){
    if(!selected.value) return
    replyForm.post(`/hoofdbeheerder/contact/${selected.value.id}/reply`, {
        preserveScroll:true,
        onSuccess:()=>{ 
            replySuccess.value = true
            toast.success('Verzonden') 
            setTimeout(() => {
                replyOpen.value = false
                replySuccess.value = false
                replyForm.reset()
            }, 5000)
        },
        onError: () => {
            toast.error('Verzenden mislukt. Controleer de rood gemarkeerde velden.')
        }
    })
}
</script>

<template>
    <Head title="Contactberichten – Admin" />

    <AdminAppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-8 space-y-8">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="mt-2 text-3xl font-bold tracking-tight">Contactberichten</h1>
                    <p class="text-muted-foreground">Overzicht van ingestuurde formulieren</p>
                </div>
                <div class="flex gap-2">
                    <Link href="/hoofdbeheerder/contact/nieuw">
                        <Button>Nieuw bericht opstellen</Button>
                    </Link>
                </div>
            </div>

            <div v-if="props.stats" class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <Card><CardHeader class="flex items-center justify-between pb-2"><CardTitle class="text-sm">Totaal</CardTitle><Mail class="h-4 w-4 text-muted-foreground" /></CardHeader><CardContent><div class="text-2xl font-bold">{{ props.stats.total }}</div></CardContent></Card>
                <Card><CardHeader class="flex items-center justify-between pb-2"><CardTitle class="text-sm">Nieuw</CardTitle><Mail class="h-4 w-4 text-primary" /></CardHeader><CardContent><div class="text-2xl font-bold">{{ props.stats.new }}</div></CardContent></Card>
                <Card><CardHeader class="flex items-center justify-between pb-2"><CardTitle class="text-sm">Gelezen</CardTitle><MailOpen class="h-4 w-4 text-muted-foreground" /></CardHeader><CardContent><div class="text-2xl font-bold">{{ props.stats.read }}</div></CardContent></Card>
                <Card><CardHeader class="flex items-center justify-between pb-2"><CardTitle class="text-sm">Spam</CardTitle><ShieldAlert class="h-4 w-4 text-red-500" /></CardHeader><CardContent><div class="text-2xl font-bold">{{ props.stats.spam }}</div></CardContent></Card>
            </div>

            <Card>
                <CardHeader>
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <CardTitle>Ontvangen berichten</CardTitle>
                            <CardDescription>Zoek, filter en beheer binnengekomen berichten</CardDescription>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <div class="relative">
                                <Search class="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
                                <Input class="pl-8 w-64" v-model="q" placeholder="Zoek naam, e-mail, onderwerp…" />
                            </div>
                            <select v-model="status" class="h-9 rounded-md border bg-background px-2 text-sm">
                                <option value="">Alle statussen</option>
                                <option value="new">Nieuw</option>
                                <option value="read">Gelezen</option>
                                <option value="replied">Beantwoord</option>
                                <option value="archived">Gearchiveerd</option>
                                <option value="closed">Gesloten</option>
                            </select>
                            <label class="inline-flex items-center gap-2 text-sm cursor-pointer">
                                <input type="checkbox" v-model="onlySpam" class="h-4 w-4 rounded border" />
                                Alleen spam
                            </label>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Afzender</TableHead>
                                <TableHead>Onderwerp</TableHead>
                                <TableHead class="hidden sm:table-cell">Inhoud</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead class="hidden md:table-cell">Ingediend</TableHead>
                                <TableHead class="text-right">Acties</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="m in props.messages.data" :key="m.id" :class="m.status==='new' && !m.is_spam ? 'bg-primary/5' : ''">
                                <TableCell>
                                    <div class="font-medium">{{ m.name }}</div>
                                    <div class="text-xs text-muted-foreground">{{ m.email }}</div>
                                </TableCell>
                                <TableCell class="align-top">
                                    <div class="font-medium line-clamp-1 flex items-center gap-2">
                                        {{ m.subject }}
                                        <Paperclip v-if="m.has_attachment" class="h-3 w-3 text-muted-foreground shrink-0" />
                                    </div>
                                    <div v-if="m.is_spam" class="mt-1"><Badge variant="destructive" class="gap-1"><ShieldAlert class="h-3 w-3" /> Spam</Badge></div>
                                </TableCell>
                                <TableCell class="hidden sm:table-cell align-top text-sm text-muted-foreground">
                                    {{ short(m.message ? m.message.replace(/<[^>]*>/g,'') : '', 120) }}
                                </TableCell>
                                <TableCell class="align-top">
                                    <Badge :variant="statusBadge(m.status)">{{ statusLabel(m.status) }}</Badge>
                                </TableCell>
                                <TableCell class="hidden md:table-cell align-top">{{ dt(m.created_at) }}</TableCell>
                                <TableCell class="text-right align-top">
                                    <div class="flex items-center justify-end gap-1">
                                        <Button 
                                            variant="ghost" 
                                            size="icon" 
                                            class="text-destructive hover:bg-destructive/10"
                                            title="Verwijderen"
                                            @click="openDelete(m)"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </Button>
                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child>
                                                <Button variant="ghost" size="icon" aria-label="Acties">
                                                    <MoreVertical class="h-4 w-4" />
                                                </Button>
                                            </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end">
                                            <DropdownMenuItem @click="openView(m)">
                                                <Eye class="mr-2 h-4 w-4" /> Bekijken
                                            </DropdownMenuItem>
                                            <DropdownMenuItem @click="openReply(m)">
                                                <Reply class="mr-2 h-4 w-4" /> Antwoorden
                                            </DropdownMenuItem>

                                            <DropdownMenuItem v-if="m.status!=='read' && m.status!=='replied'" @click="updateStatus(m, 'read')">
                                                <CheckCircle2 class="mr-2 h-4 w-4" /> Markeer gelezen
                                            </DropdownMenuItem>

                                            <DropdownMenuItem v-if="m.status!=='closed'" @click="updateStatus(m, 'closed')">
                                                <XCircle class="mr-2 h-4 w-4" /> Sluiten
                                            </DropdownMenuItem>

                                            <DropdownMenuItem v-if="m.status==='closed' || m.status==='archived'" @click="updateStatus(m, 'new')">
                                                <Undo2 class="mr-2 h-4 w-4" /> Heropenen
                                            </DropdownMenuItem>

                                            <DropdownMenuItem v-if="m.status!=='archived'" @click="updateStatus(m, 'archive')">
                                                <Archive class="mr-2 h-4 w-4" /> Archiveer
                                            </DropdownMenuItem>

                                            <Separator class="my-1" />
                                            <DropdownMenuItem class="text-red-600" @click="openDelete(m)">
                                                <Trash2 class="mr-2 h-4 w-4" /> Verwijderen
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                    </div>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="props.messages.data.length === 0">
                                <TableCell colspan="6" class="text-center h-24 text-muted-foreground">
                                    Geen berichten gevonden.
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>

                    <div v-if="props.messages.links.length > 3" class="flex items-center justify-center space-x-2 py-4">
                        <template v-for="(link, k) in props.messages.links" :key="k">
                            <Button
                                v-if="link.url"
                                variant="outline"
                                size="sm"
                                :disabled="link.active"
                                :class="{'bg-primary text-primary-foreground': link.active}"
                                @click="router.get(link.url, {}, {preserveState:true, preserveScroll:true})"
                                v-html="link.label"
                            />
                            <span v-else class="text-muted-foreground text-sm px-2" v-html="link.label"></span>
                        </template>
                    </div>

                </CardContent>
            </Card>
        </div>

        <Dialog v-model:open="viewOpen">
            <DialogContent class="sm:max-w-[720px] max-h-[85vh] overflow-y-auto flex flex-col">
                <DialogHeader>
                    <DialogTitle>Details van bericht</DialogTitle>
                    <DialogDescription>
                        Van: <b>{{ selected?.name }}</b> &lt;{{ selected?.email }}&gt;
                    </DialogDescription>
                </DialogHeader>

                <Tabs default-value="details" class="w-full">
                    <TabsList class="grid w-full grid-cols-2">
                        <TabsTrigger value="details">Details</TabsTrigger>
                        <TabsTrigger value="history">Geschiedenis ({{ history.length }})</TabsTrigger>
                    </TabsList>
                    
                    <TabsContent value="details" class="mt-4 space-y-4">
                        <!-- Subject Section -->
                        <div class="space-y-1 border-b pb-4">
                            <h4 class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Onderwerp</h4>
                            <h3 class="text-xl font-bold text-foreground leading-tight">{{ selected?.subject }}</h3>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-1">
                                <h4 class="text-sm font-medium text-muted-foreground">Afzender</h4>
                                <p class="text-sm font-semibold">{{ selected?.name }}</p>
                                <p class="text-sm text-muted-foreground">{{ selected?.email }}</p>
                            </div>
                             <div class="space-y-1">
                                <h4 class="text-sm font-medium text-muted-foreground">Details</h4>
                                <div class="flex flex-col gap-1 text-sm">
                                    <span v-if="selected?.phone" class="flex gap-2"><span class="w-16 text-muted-foreground">Tel:</span> {{ selected.phone }}</span>
                                    <span class="flex gap-2"><span class="w-16 text-muted-foreground">Datum:</span> {{ selected ? dt(selected.created_at) : '' }}</span>
                                    <span class="flex gap-2"><span class="w-16 text-muted-foreground">Status:</span> {{ selected ? statusLabel(selected.status) : '' }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <h4 class="text-sm font-medium text-muted-foreground">Bericht</h4>
                            <div class="rounded-md border p-4 text-sm leading-relaxed bg-muted/20 min-h-[100px] overflow-auto">
                                <div class="prose prose-sm dark:prose-invert max-w-none" v-html="selected?.message"></div>
                            </div>
                        </div>

                        <div v-if="selected?.ip || selected?.referer" class="grid grid-cols-2 gap-4 pt-4 border-t">
                            <div v-if="selected?.ip" class="space-y-0.5">
                                <h4 class="text-xs font-medium text-muted-foreground">IP Adres</h4>
                                <p class="text-xs">{{ selected.ip }}</p>
                            </div>
                            <div v-if="selected?.referer" class="space-y-0.5">
                                <h4 class="text-xs font-medium text-muted-foreground">Bron (Referer)</h4>
                                <p class="text-xs truncate" :title="selected.referer">{{ selected.referer }}</p>
                            </div>
                        </div>
                    </TabsContent>

                    <TabsContent value="history" class="mt-4">
                        <div v-if="historyLoading" class="flex items-center justify-center py-8 text-muted-foreground">
                            <Loader2 class="mr-2 h-4 w-4 animate-spin" /> Laden...
                        </div>
                        <div v-else-if="history.length === 0" class="flex flex-col items-center justify-center py-8 text-muted-foreground">
                            <Inbox class="mb-2 h-8 w-8 opacity-50" />
                            <p>Geen eerdere berichten gevonden.</p>
                        </div>
                        <div v-else class="space-y-4 pr-1">
                            <div v-for="h in history" :key="h.id" class="flex items-start gap-4 rounded-lg border p-4 text-sm hover:bg-muted/50 transition-colors shadow-sm bg-card/50">
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-background border shadow-sm mt-1">
                                    <ArrowUpRight v-if="h.source && h.source.includes('admin')" class="h-4 w-4 text-blue-500" />
                                    <ArrowDownLeft v-else class="h-4 w-4 text-green-500" />
                                </div>
                                <div class="flex-1 min-w-0 grid gap-1.5">
                                    <div class="flex items-center justify-between gap-2 border-b pb-2 mb-1 border-border/50">
                                        <div class="font-bold truncate flex items-center gap-2 text-base">
                                            {{ h.name }}
                                            <Paperclip v-if="h.has_attachment" class="h-3.5 w-3.5 text-muted-foreground shrink-0" />
                                        </div>
                                        <Badge :variant="statusBadge(h.status)" class="text-[10px] h-5 shrink-0">{{ statusLabel(h.status) }}</Badge>
                                    </div>
                                    
                                    <div class="text-foreground font-medium truncate text-sm">{{ h.subject }}</div>
                                    <div class="text-muted-foreground text-xs line-clamp-2 leading-relaxed" v-html="short(h.message ? h.message.replace(/<[^>]*>/g,'') : '', 150)"></div>

                                    <div class="flex items-center text-[10px] text-muted-foreground mt-1">
                                        <span>{{ dt(h.created_at) }}</span>
                                        <span class="mx-1">•</span>
                                        <span>{{ h.is_spam ? 'Spam' : 'Ham' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </TabsContent>
                </Tabs>

                <DialogFooter class="gap-2 mt-4">
                    <Button variant="outline" @click="viewOpen=false">Sluiten</Button>
                    <Button @click="openReply(selected!)">Antwoorden</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="replyOpen">
            <DialogContent class="sm:max-w-[640px]">
                <DialogHeader>
                    <DialogTitle>Antwoord sturen</DialogTitle>
                    <DialogDescription>Je antwoord gaat naar {{ replyForm.to_name }} &lt;{{ replyForm.to_email }}&gt;.</DialogDescription>
                </DialogHeader>

                <!-- Success Message -->
                <div v-if="replySuccess" class="flex flex-col items-center justify-center py-10 space-y-4 text-green-600 animate-in fade-in zoom-in duration-300">
                    <CheckCircle2 class="h-16 w-16" />
                    <h3 class="text-xl font-bold">Verzonden!</h3>
                    <p class="text-sm text-foreground/70">Het venster sluit automatisch...</p>
                </div>

                <!-- Form -->
                <div v-else class="space-y-3">
                    <div v-if="(replyForm.errors as any).message" class="rounded-md bg-destructive/15 p-3 text-sm text-destructive">
                        <p class="font-medium">Er ging iets mis:</p>
                        <p>{{ (replyForm.errors as any).message }}</p>
                    </div>

                    <div>
                        <Input v-model="replyForm.subject" placeholder="Onderwerp" :class="{'border-red-500': replyForm.errors.subject}" />
                        <p v-if="replyForm.errors.subject" class="mt-1 text-xs text-red-500">{{ replyForm.errors.subject }}</p>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <Input v-model="replyForm.cc" placeholder="CC (optioneel)" :class="{'border-red-500': replyForm.errors.cc}" />
                            <p v-if="replyForm.errors.cc" class="mt-1 text-xs text-red-500">{{ replyForm.errors.cc }}</p>
                        </div>
                        <div>
                            <Input v-model="replyForm.bcc" placeholder="BCC (optioneel)" :class="{'border-red-500': replyForm.errors.bcc}" />
                            <p v-if="replyForm.errors.bcc" class="mt-1 text-xs text-red-500">{{ replyForm.errors.bcc }}</p>
                        </div>
                    </div>

                     <div>
                        <Label for="replyAttachment" class="mb-1 text-xs">Bijlage (optioneel)</Label>
                        <div class="flex items-center gap-2">
                            <Input 
                                id="replyAttachment"
                                ref="replyFileInput"
                                type="file" 
                                class="flex-1"
                                @input="replyForm.attachment = $event.target.files[0]"
                            />
                            <Button
                                v-if="replyForm.attachment"
                                type="button"
                                variant="ghost"
                                size="icon"
                                class="text-destructive hover:bg-destructive/10 shrink-0"
                                @click="clearReplyAttachment"
                                title="Bijlage verwijderen"
                            >
                                <Trash2 class="h-4 w-4" />
                            </Button>
                        </div>
                        <p v-if="replyForm.errors.attachment" class="mt-1 text-xs text-red-500">{{ replyForm.errors.attachment }}</p>
                    </div>

                    <div>
                        <textarea 
                            v-model="replyForm.body" 
                            rows="8" 
                            class="w-full rounded-md border bg-background p-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring" 
                            :class="{'border-red-500': replyForm.errors.body}"
                            placeholder="Typ je bericht..." 
                        />
                        <p v-if="replyForm.errors.body" class="mt-1 text-xs text-red-500">{{ replyForm.errors.body }}</p>
                    </div>
                </div>

                <DialogFooter v-if="!replySuccess" class="gap-2">
                    <Button variant="outline" @click="replyOpen=false">Annuleren</Button>
                    <Button :disabled="replyForm.processing || !replyForm.subject || !replyForm.body" @click="sendReply">
                        <Loader2 v-if="replyForm.processing" class="mr-2 h-4 w-4 animate-spin" /> Verzenden
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <AlertDialog v-model:open="deleteOpen">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Weet je het zeker?</AlertDialogTitle>
                    <AlertDialogDescription>
                        Dit verwijdert het bericht van <b>{{ selected?.name }}</b> definitief uit het zicht (soft delete).
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel :disabled="deleteForm.processing">Annuleren</AlertDialogCancel>
                    <Button variant="destructive" :disabled="deleteForm.processing" @click="destroy">
                        <Loader2 v-if="deleteForm.processing" class="mr-2 h-4 w-4 animate-spin" /> Verwijderen
                    </Button>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AdminAppLayout>
</template>
