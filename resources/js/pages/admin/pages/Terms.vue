<script setup lang="ts">
import { ref } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import AdminAppLayout from '@/layouts/AdminAppLayout.vue'
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Button } from '@/components/ui/button'
import { Loader2, CheckCircle2, XCircle } from 'lucide-vue-next'
import { routes } from '@/lib/routes'

// Quill Editor
import RichTextEditor from '@/components/RichTextEditor.vue'

const props = defineProps<{
    terms?: {
        title?: string
        content?: string
    } | null
}>()

const form = useForm({
    title: props.terms?.title || 'Algemene Voorwaarden',
    content: props.terms?.content || '',
})

const successBar = ref('')
const errorBar = ref('')

const submit = () => {
    form.put(routes.admin.termsContent, {
        preserveScroll: true,
        onSuccess: () => {
            errorBar.value = ''
            successBar.value = '✅ Opgeslagen!'
            setTimeout(() => (successBar.value = ''), 2200)
        },
        onError: () => {
            errorBar.value = 'Er is iets misgegaan.'
            successBar.value = ''
        }
    })
}


</script>

<template>
    <Head title="Bewerk Algemene Voorwaarden" />

    <AdminAppLayout :breadcrumbs="[
        { title: 'Dashboard', href: routes.admin.dashboard },
        { title: 'Algemene Voorwaarden', href: routes.admin.termsContent },
    ]">
        <div class="mx-auto max-w-7xl space-y-6 p-6">
            <Card>
                <CardHeader>
                    <CardTitle>Algemene Voorwaarden</CardTitle>
                    <CardDescription>Pas hier de inhoud van de algemene voorwaarden pagina aan.</CardDescription>
                </CardHeader>
                <CardContent class="space-y-6">
                    <div v-if="successBar" class="mb-4 flex items-center gap-2 rounded-lg border border-emerald-300 bg-emerald-50 p-2 text-emerald-800">
                        <CheckCircle2 class="h-5 w-5" />
                        <span>{{ successBar }}</span>
                    </div>
                    <div v-if="errorBar" class="mb-4 flex items-center gap-2 rounded-lg border border-red-300 bg-red-50 p-2 text-red-800">
                        <XCircle class="h-5 w-5" />
                        <span>{{ errorBar }}</span>
                    </div>
                    <div class="space-y-2">
                        <Label for="title">Titel</Label>
                        <Input id="title" v-model="form.title" placeholder="Bijv. Algemene Voorwaarden" />
                        <span v-if="form.errors.title" class="text-sm text-destructive">{{ form.errors.title }}</span>
                    </div>

                    <div class="space-y-2">
                        <Label for="content">Inhoud</Label>
                        <div class="overflow-hidden rounded-md border focus-within:ring-2 focus-within:ring-ring focus-within:ring-offset-2">
                            <RichTextEditor
                                v-model="form.content"
                                placeholder="Schrijf hier de algemene voorwaarden..."
                                height="400px"
                            />
                        </div>
                        <span v-if="form.errors.content" class="text-sm text-destructive">{{ form.errors.content }}</span>
                    </div>
                </CardContent>
                <CardFooter class="flex justify-end border-t pt-4">
                    <Button :disabled="form.processing" @click="submit">
                        <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                        Opslaan
                    </Button>
                </CardFooter>
            </Card>
        </div>
    </AdminAppLayout>
</template>
