<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import AdminAppLayout from "@/layouts/AdminAppLayout.vue"
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Separator } from '@/components/ui/separator'
import { ArrowLeft, Mail, Trash2, CheckCircle, XCircle, ThumbsUp, ThumbsDown, Globe } from 'lucide-vue-next'

type Feedback = {
    id: number
    name?: string
    email?: string
    message?: string
    sentiment?: 'positive' | 'negative'
    page_context?: string
    is_read: boolean
    created_at: string
}

const props = defineProps<{
    feedback: Feedback
}>()

const fmtDate = (iso: string) => {
    return new Date(iso).toLocaleDateString('nl-NL', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const deleteFeedback = () => {
    if (confirm('Weet je zeker dat je deze feedback wilt verwijderen?')) {
        router.delete(`/hoofdbeheerder/feedback/${props.feedback.id}`, {
            onSuccess: () => {
                router.visit('/hoofdbeheerder/feedback')
            }
        })
    }
}

const toTitleCase = (name: string) => name?.split(' ').map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase()).join(' ') || ''

const sendEmail = () => {
    if (props.feedback.email) {
        const appName = document.title.split(' - ')[1] || 'Duidelijkheid.com'
        const subject = `Reactie op uw feedback - ${appName}`
        const formattedName = props.feedback.name ? toTitleCase(props.feedback.name) : 'gebruiker'
        const body = `<p>Beste ${formattedName},</p><p>Bedankt voor uw feedback.</p><p><br></p><p>Met vriendelijke groet,</p><p>Team ${appName}</p>`
        
        router.get('/hoofdbeheerder/contact/opstellen', {
            email: props.feedback.email,
            name: props.feedback.name,
            subject: subject,
            body: body
        })
    }
}
</script>

<template>
    <AdminAppLayout>
        <Head title="Feedback Details" />
        
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link href="/hoofdbeheerder/feedback">
                        <Button variant="ghost" size="icon">
                            <ArrowLeft class="h-5 w-5" />
                        </Button>
                    </Link>
                    <div>
                        <h1 class="text-3xl font-bold tracking-tight">Feedback Details</h1>
                        <p class="text-muted-foreground">{{ fmtDate(feedback.created_at) }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <Button v-if="feedback.email" variant="outline" @click="sendEmail">
                        <Mail class="mr-2 h-4 w-4" />
                        Reageren
                    </Button>
                    <Button variant="destructive" @click="deleteFeedback">
                        <Trash2 class="mr-2 h-4 w-4" />
                        Verwijderen
                    </Button>
                </div>
            </div>

            <div class="grid gap-6 md:grid-cols-3">
                <!-- Main Content -->
                <Card class="md:col-span-2">
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <CardTitle>Bericht</CardTitle>
                            <Badge v-if="feedback.sentiment === 'positive'" variant="outline" class="border-green-500 text-green-600 dark:text-green-400">
                                <ThumbsUp class="mr-1 h-3 w-3" />
                                Positief
                            </Badge>
                            <Badge v-else-if="feedback.sentiment === 'negative'" variant="outline" class="border-red-500 text-red-600 dark:text-red-400">
                                <ThumbsDown class="mr-1 h-3 w-3" />
                                Negatief
                            </Badge>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="prose prose-sm dark:prose-invert max-w-none">
                            <p class="whitespace-pre-wrap text-foreground">{{ feedback.message || 'Geen bericht.' }}</p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Sidebar Info -->
                <Card>
                    <CardHeader>
                        <CardTitle>Afzender</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Naam</p>
                            <p class="font-medium">{{ feedback.name || 'Anoniem' }}</p>
                        </div>
                        
                        <Separator />
                        
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Email</p>
                            <p v-if="feedback.email" class="font-medium">
                                <a :href="`mailto:${feedback.email}`" class="text-primary hover:underline">
                                    {{ feedback.email }}
                                </a>
                            </p>
                            <p v-else class="text-muted-foreground">Niet opgegeven</p>
                        </div>
                        
                        <Separator />
                        
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Pagina context</p>
                            <div class="flex items-center gap-2 mt-1">
                                <Globe class="h-4 w-4 text-muted-foreground" />
                                <p class="text-sm">{{ feedback.page_context || 'Onbekend' }}</p>
                            </div>
                        </div>
                        
                        <Separator />
                        
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Status</p>
                            <div class="flex items-center gap-2 mt-1">
                                <CheckCircle v-if="feedback.is_read" class="h-4 w-4 text-green-500" />
                                <XCircle v-else class="h-4 w-4 text-amber-500" />
                                <p class="text-sm">{{ feedback.is_read ? 'Gelezen' : 'Ongelezen' }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AdminAppLayout>
</template>
