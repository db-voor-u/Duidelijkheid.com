<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import AdminAppLayout from '@/layouts/AdminAppLayout.vue'
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card'
import { Textarea } from '@/components/ui/textarea'
import { Button } from '@/components/ui/button'
import { routes, adminRouteFns as fns } from '@/lib/routes'

const props = defineProps<{ message: { id:number; subject:string; name:string; email:string } }>()

const form = useForm({ reply: '' })
function submit(){
    form.post(fns.contactReply(props.message.id), { preserveScroll:true })
}
</script>

<template>
    <Head :title="`Beantwoord: ${props.message.subject}`" />
    <AdminAppLayout :breadcrumbs="[
    { title:'Contact-berichten', href: routes.admin.contact },
    { title:`Bericht #${props.message.id}`, href: fns.contactShow(props.message.id) },
    { title:'Beantwoorden', href: fns.contactReply(props.message.id) },
  ]">
        <div class="mx-8">
            <Card>
                <CardHeader>
                    <CardTitle>Beantwoorden</CardTitle>
                    <CardDescription>Aan {{ props.message.name }} &lt;{{ props.message.email }}&gt;</CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <Textarea v-model="form.reply" rows="12" placeholder="Typ je antwoord…" />
                    <div class="flex gap-2">
                        <Button :disabled="form.processing || !form.reply" @click="submit">Versturen</Button>
                        <Link :href="fns.contactShow(props.message.id)" class="btn btn-muted">Annuleren</Link>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AdminAppLayout>
</template>
