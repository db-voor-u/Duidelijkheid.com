<script setup lang="ts">
import { Head, router, Link } from '@inertiajs/vue3'
import AdminAppLayout from "@/layouts/AdminAppLayout.vue"
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table'
import { Button } from '@/components/ui/button'
import { Trash2, CheckCircle, XCircle, Mail, Eye } from 'lucide-vue-next'

// Types
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
    feedbacks: {
        data: Feedback[]
        links: any[]
        meta: any 
    }
}>()

const deleteFeedback = (id: number) => {
    if (confirm('Weet je zeker dat je deze feedback wilt verwijderen?')) {
        router.delete(`/hoofdbeheerder/feedback/${id}`)
    }
}

const toggleRead = (id: number) => {
    router.patch(`/hoofdbeheerder/feedback/${id}/read`)
}

const sendEmail = (email?: string, name?: string) => {
    if (email) {
        const subject = 'Reactie op uw feedback - Duidelijkheid.com'
        const body = `<p>Beste ${name || 'gebruiker'},</p><p>Bedankt voor uw feedback.</p><p><br></p><p>Met vriendelijke groet,</p><p>Team Duidelijkheid.com</p>`
        
        router.get('/hoofdbeheerder/contact/opstellen', {
            email: email,
            name: name,
            subject: subject,
            body: body
        })
    }
}

const fmtDate = (iso: string) => {
    return new Date(iso).toLocaleDateString('nl-NL', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}
</script>

<template>
    <AdminAppLayout>
        <Head title="Feedback Overzicht" />
        
        <div class="space-y-6">
            <h1 class="text-3xl font-bold tracking-tight">Feedback</h1>
            <p class="text-muted-foreground">Bekijk hier wat gebruikers van de website vinden.</p>

            <div class="rounded-md border bg-card">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Datum</TableHead>
                            <TableHead>Naam / Email</TableHead>
                            <TableHead>Gevoel</TableHead>
                            <TableHead>Bericht</TableHead>
                            <TableHead>Pagina</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead class="text-right">Acties</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="item in feedbacks.data" :key="item.id">
                            <TableCell class="whitespace-nowrap">
                                {{ fmtDate(item.created_at) }}
                            </TableCell>
                            <TableCell>
                                <div class="font-medium">{{ item.name || 'Anoniem' }}</div>
                                <div class="text-xs text-muted-foreground">{{ item.email || '-' }}</div>
                            </TableCell>
                            <TableCell>
                                <span v-if="item.sentiment === 'positive'" class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-300">
                                    Positief
                                </span>
                                <span v-else-if="item.sentiment === 'negative'" class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800 dark:bg-red-900 dark:text-red-300">
                                    Negatief
                                </span>
                                <span v-else class="text-xs text-muted-foreground">-</span>
                            </TableCell>
                            <TableCell class="max-w-xs truncate" :title="item.message">
                                {{ item.message || '-' }}
                            </TableCell>
                            <TableCell class="text-sm text-muted-foreground">
                                {{ item.page_context || 'Onbekend' }}
                            </TableCell>
                             <TableCell>
                                <div @click="toggleRead(item.id)" class="cursor-pointer">
                                    <CheckCircle v-if="item.is_read" class="h-5 w-5 text-green-500" />
                                    <div v-else class="h-2.5 w-2.5 rounded-full bg-blue-500"></div>
                                </div>
                            </TableCell>
                            <TableCell class="text-right">
                                <Link :href="`/hoofdbeheerder/feedback/${item.id}`">
                                    <Button variant="ghost" size="icon" class="mr-2 text-muted-foreground hover:bg-muted">
                                        <Eye class="h-4 w-4" />
                                    </Button>
                                </Link>
                                <Button v-if="item.email" variant="ghost" size="icon" @click="sendEmail(item.email, item.name)" class="mr-2 text-primary hover:bg-primary/10">
                                    <Mail class="h-4 w-4" />
                                </Button>
                                <Button variant="ghost" size="icon" @click="deleteFeedback(item.id)" class="text-destructive hover:bg-destructive/10">
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="feedbacks.data.length === 0">
                            <TableCell colspan="7" class="h-24 text-center">
                                Geen feedback gevonden.
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </div>
    </AdminAppLayout>
</template>
