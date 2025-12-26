<script setup lang="ts">
import { ref } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import AdminAppLayout from '@/layouts/AdminAppLayout.vue'
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog'
import { Separator } from '@/components/ui/separator'
import { routes, adminRouteFns as fns } from '@/lib/routes'
import { Mail, Archive, Undo2, ShieldAlert, MailCheck, Eye, Reply, Trash2, User, Phone, Calendar, Loader2, CheckCircle2 } from 'lucide-vue-next'
import { toast } from 'vue-sonner'

// QuillEditor imports
import RichTextEditor from '@/components/RichTextEditor.vue'

// Toolbar & Options


type Message = {
    id:number; name:string; email:string; phone?:string|null; subject:string; message:string;
    status:string; is_spam:boolean; spam_reason?:string|null;
    read_at?:string|null; replied_at?:string|null; archived_at?:string|null;
    created_at:string; ip?:string|null; user_agent?:string|null; referer?:string|null; utm?:any
}
const props = defineProps<{ message: Message }>()

function patch(action:string){
    router.patch(fns.contactStatus(props.message.id), { action }, { preserveScroll:true })
}

function getStatusVariant(status: string) {
    switch (status) {
        case 'new': return 'default';
        case 'read': return 'secondary';
        case 'replied': return 'secondary'; 
        case 'archived': return 'outline';
        default: return 'outline';
    }
}

const toTitleCase = (name: string) => name?.split(' ').map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase()).join(' ') || ''

/* Reply Logic */
const replyOpen = ref(false)
const replySuccess = ref(false)
const replyFileInput = ref<HTMLInputElement | null>(null)

const replyForm = useForm({
    to_email: '',
    to_name: '',
    subject: '',
    cc: '',
    bcc: '',
    body: '',
    attachment: null as File | null
})

function openReplyDialog() {
    replyForm.to_email = props.message.email
    replyForm.to_name = props.message.name
    replyForm.subject = `Re: ${props.message.subject}`
    replyForm.cc = ''
    replyForm.bcc = ''
    replyForm.attachment = null
    replyForm.body = `Beste ${toTitleCase(props.message.name)},\n\n\n\nMet vriendelijke groet,\n\nTeam Duidelijkheid`
    replyOpen.value = true
}

function clearReplyAttachment() {
    replyForm.attachment = null
    if (replyFileInput.value) replyFileInput.value.value = ''
}

function sendReply(){
    replyForm.post(fns.contactReply(props.message.id), {
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
        onError: (errors: any) => { // Type as any to access custom message key
            const msg = errors.message || 'Verzenden mislukt. Controleer de invoer.'
            toast.error(msg)
        }
    })
}
</script>

<template>
    <Head :title="`Bericht #${message.id}`" />
    <AdminAppLayout :breadcrumbs="[
        { title: 'Contact-berichten', href: routes.admin.contact },
        { title: `Bericht #${message.id}`, href: fns.contactShow(message.id) },
    ]">
        <div class="mx-8 max-w-6xl">
            <!-- Header Section -->
            <div class="mb-6 flex flex-col justify-between gap-4 md:flex-row md:items-start">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">{{ message.subject }}</h1>
                    <div class="mt-2 flex flex-wrap items-center gap-3 text-muted-foreground">
                        <span class="flex items-center gap-1">
                            <User class="h-4 w-4" />
                            <span class="font-medium text-foreground">{{ message.name }}</span>
                        </span>
                        <span>&bull;</span>
                        <a :href="`mailto:${message.email}`" class="flex items-center gap-1 hover:underline">
                            <Mail class="h-4 w-4" />
                            {{ message.email }}
                        </a>
                        <span v-if="message.phone">&bull;</span>
                        <a v-if="message.phone" :href="`tel:${message.phone}`" class="flex items-center gap-1 hover:underline">
                            <Phone class="h-4 w-4" />
                            {{ message.phone }}
                        </a>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <Badge variant="outline" class="flex gap-1">
                        <Calendar class="h-3 w-3" />
                        {{ new Date(message.created_at).toLocaleString('nl-NL') }}
                    </Badge>
                    <Badge :variant="getStatusVariant(message.status)" class="capitalize">
                        {{ message.status }}
                    </Badge>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Main Content (Left) -->
                <div class="lg:col-span-2 space-y-6">
                    <Card class="min-h-[400px]">
                        <CardContent class="pt-6">
                            <h4 class="text-sm font-medium text-muted-foreground">Bericht</h4>
                            <div class="rounded-md border p-4 text-sm leading-relaxed bg-muted/20 min-h-[100px] overflow-auto">
                                <div class="prose prose-sm dark:prose-invert max-w-none" v-html="message.message"></div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Sidebar (Right) -->
                <div class="space-y-6">
                    <!-- Primary Actions -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Acties</CardTitle>
                        </CardHeader>
                        <CardContent class="grid gap-3">
                            <Button size="lg" class="w-full" @click="openReplyDialog">
                                <Reply class="mr-2 h-5 w-5" /> Beantwoorden
                            </Button>
                            
                            <Button v-if="message.status === 'new'" variant="secondary" @click="patch('read')">
                                <Eye class="mr-2 h-4 w-4" /> Markeer als gelezen
                            </Button>

                            <Separator />

                            <div class="grid grid-cols-2 gap-2">
                                <Button variant="outline" @click="patch('archive')" v-if="!message.archived_at">
                                    <Archive class="mr-2 h-4 w-4" /> Archiveer
                                </Button>
                                <Button variant="outline" @click="patch('unarchive')" v-else>
                                    <Undo2 class="mr-2 h-4 w-4" /> Herstel
                                </Button>

                                <Button variant="outline" @click="patch('spam')" v-if="!message.is_spam">
                                    <ShieldAlert class="mr-2 h-4 w-4" /> Spam
                                </Button>
                                <Button variant="outline" @click="patch('ham')" v-else>
                                    <MailCheck class="mr-2 h-4 w-4" /> Geen spam
                                </Button>
                            </div>

                            <Button as-child variant="destructive" class="w-full">
                                <Link :href="fns.contactDestroy(message.id)" method="delete" as="button">
                                    <Trash2 class="mr-2 h-4 w-4" /> Verwijderen
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>

                    <!-- Meta Info -->
                    <Card>
                        <CardHeader class="pb-3">
                             <CardTitle class="text-sm font-medium">Technische Details</CardTitle>
                        </CardHeader>
                        <CardContent class="text-xs text-muted-foreground space-y-2">
                             <div class="grid grid-cols-3 gap-1">
                                <span class="font-semibold">IP Adres:</span>
                                <span class="col-span-2 font-mono">{{ message.ip || '—' }}</span>
                            </div>
                            <div class="grid grid-cols-3 gap-1">
                                <span class="font-semibold">Browser:</span>
                                <span class="col-span-2 break-words">{{ message.user_agent || '—' }}</span>
                            </div>
                            <div class="grid grid-cols-3 gap-1">
                                <span class="font-semibold">Bron:</span>
                                <span class="col-span-2 break-words">{{ message.referer || 'Direct' }}</span>
                            </div>
                            <div v-if="message.spam_reason" class="grid grid-cols-3 gap-1 text-red-500">
                                <span class="font-semibold">Spam Score:</span>
                                <span class="col-span-2">{{ message.spam_reason }}</span>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

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
                                    @input="replyForm.attachment = ($event.target as HTMLInputElement).files?.[0] || null"
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
                            <div class="overflow-hidden rounded-md border focus-within:ring-2 focus-within:ring-ring focus-within:ring-offset-2">
                                <RichTextEditor
                                    v-model="replyForm.body"
                                    placeholder="Typ je bericht..."
                                    height="300px"
                                />
                            </div>
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
        </div>
    </AdminAppLayout>
</template>
