<script setup lang="ts">
import { ref } from 'vue'
import { Head, useForm, Link } from '@inertiajs/vue3'
import AdminAppLayout from '@/layouts/AdminAppLayout.vue'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Table, TableHeader, TableRow, TableHead, TableBody, TableCell } from '@/components/ui/table'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog'
import { Pencil, Trash2, Plus, Loader2, ArrowRight } from 'lucide-vue-next'
import { Badge } from '@/components/ui/badge'

type Category = {
    id: number
    name: string
    color: string
    type: 'blog' | 'over_ons' | 'innovatie' | 'neurodiversiteit'
}

const props = defineProps<{
    categories: Category[]
}>()

const form = useForm({
    id: null as number | null,
    name: '',
    color: 'bg-slate-500',
    type: 'innovatie' as Category['type']
})

// End of script logic


const isModalOpen = ref(false)
const isEdit = ref(false)

const colors = [
    { label: 'Rood', class: 'bg-red-500' },
    { label: 'Oranje', class: 'bg-orange-500' },
    { label: 'Amber', class: 'bg-amber-500' },
    { label: 'Geel', class: 'bg-yellow-500' },
    { label: 'Limoen', class: 'bg-lime-500' },
    { label: 'Groen', class: 'bg-green-500' },
    { label: 'Smaragd', class: 'bg-emerald-500' },
    { label: 'Teal', class: 'bg-teal-500' },
    { label: 'Cyaan', class: 'bg-cyan-500' },
    { label: 'Blauw', class: 'bg-blue-500' },
    { label: 'Indigo', class: 'bg-indigo-500' },
    { label: 'Paars', class: 'bg-violet-500' },
    { label: 'Roze', class: 'bg-pink-500' },
    { label: 'Fuchsia', class: 'bg-fuchsia-500' },
    { label: 'Rose', class: 'bg-rose-500' },
    { label: 'Slate', class: 'bg-slate-500' },
]

function openCreate() {
    isEdit.value = false
    form.reset()
    form.id = null
    form.color = 'bg-slate-500'
    form.type = 'innovatie'
    isModalOpen.value = true
}

function openEdit(cat: Category) {
    isEdit.value = true
    form.id = cat.id
    form.name = cat.name
    form.color = cat.color
    form.type = cat.type
    isModalOpen.value = true
}

function submit() {
    if (isEdit.value && form.id) {
        form.put(`/hoofdbeheerder/categorieen/${form.id}`, {
            onSuccess: () => isModalOpen.value = false
        })
    } else {
        form.post('/hoofdbeheerder/categorieen', {
            onSuccess: () => isModalOpen.value = false
        })
    }
}

function deleteCategory(cat: Category) {
    if (confirm(`Zeker weten dat je "${cat.name}" wilt verwijderen?`)) {
        useForm({}).delete(`/hoofdbeheerder/categorieen/${cat.id}`)
    }
}
</script>

<template>
    <Head title="Categorieën Beheer" />
    <AdminAppLayout :breadcrumbs="[
        { title: 'Dashboard', href: '/hoofdbeheerder/dashboard' },
        { title: 'Categorieën', href: '/hoofdbeheerder/categorieen' },
    ]">
        <div class="container mx-auto py-6 max-w-5xl">
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-3xl font-bold tracking-tight">Categorieën</h1>
                <div class="flex items-center gap-3">
                    <Link href="/hoofdbeheerder/blog/aanmaken">
                        <Button variant="outline">
                             <Plus class="mr-2 h-4 w-4" />Naar Nieuw Blog maken
                        </Button>
                    </Link>
                    <Button @click="openCreate">
                        <Plus class="mr-2 h-4 w-4" /> Nieuwe Categorie
                    </Button>
                </div>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Overzicht</CardTitle>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="w-[100px]">Kleur</TableHead>
                                <TableHead>Naam</TableHead>
                                <TableHead>Type</TableHead>
                                <TableHead class="text-right">Acties</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="cat in categories" :key="cat.id">
                                <TableCell>
                                    <Badge :class="['text-white', cat.color]">{{ cat.name }}</Badge>
                                </TableCell>
                                <TableCell class="font-medium">{{ cat.name }}</TableCell>
                                <TableCell>
                                    <Badge variant="outline" class="capitalize">
                                        {{ cat.type === 'over_ons' ? 'Over Ons' : (cat.type === 'innovatie' ? 'Innovatie' : (cat.type === 'neurodiversiteit' ? 'Neurodiversiteit' : 'Blog')) }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="text-right space-x-2">
                                    <Button variant="ghost" size="icon" @click="openEdit(cat)">
                                        <Pencil class="h-4 w-4" />
                                    </Button>
                                    <Button variant="ghost" size="icon" class="text-red-600 hover:text-red-700 hover:bg-red-50" @click="deleteCategory(cat)">
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="categories.length === 0">
                                <TableCell colspan="3" class="text-center py-8 text-muted-foreground">
                                    Geen categorieën gevonden. Maak er eentje aan!
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <Dialog v-model:open="isModalOpen">
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{{ isEdit ? 'Categorie bewerken' : 'Nieuwe Categorie' }}</DialogTitle>
                    </DialogHeader>

                    <form @submit.prevent="submit" class="space-y-4 py-4">
                        <div class="space-y-2">
                            <Label for="name">Naam</Label>
                            <Input id="name" v-model="form.name" placeholder="bv. Technologie" required />
                            <span v-if="form.errors.name" class="text-sm text-red-500">{{ form.errors.name }}</span>
                        </div>

                        <div class="space-y-2">
                            <Label for="type">Type</Label>
                            <div class="grid grid-cols-2 gap-4">
                                <label class="flex items-center gap-2 cursor-pointer border p-3 rounded-md has-[:checked]:bg-zinc-100 dark:has-[:checked]:bg-zinc-800 transition-colors">
                                    <input type="radio" v-model="form.type" value="innovatie" class="accent-black dark:accent-white" />
                                    <span>Innovatie</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer border p-3 rounded-md has-[:checked]:bg-zinc-100 dark:has-[:checked]:bg-zinc-800 transition-colors">
                                    <input type="radio" v-model="form.type" value="neurodiversiteit" class="accent-black dark:accent-white" />
                                    <span>Neurodiversiteit</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer border p-3 rounded-md has-[:checked]:bg-zinc-100 dark:has-[:checked]:bg-zinc-800 transition-colors">
                                    <input type="radio" v-model="form.type" value="over_ons" class="accent-black dark:accent-white" />
                                    <span>Over Ons</span>
                                </label>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label>Kleur Badge</Label>
                            <div class="grid grid-cols-4 gap-2">
                                <button
                                    type="button"
                                    v-for="c in colors"
                                    :key="c.class"
                                    class="h-8 w-full rounded-md border-2 transition-all"
                                    :class="[c.class, form.color === c.class ? 'border-zinc-900 ring-2 ring-zinc-900 ring-offset-2 dark:border-white dark:ring-white' : 'border-transparent']"
                                    :title="c.label"
                                    @click="form.color = c.class"
                                ></button>
                            </div>
                            <span v-if="form.errors.color" class="text-sm text-red-500">{{ form.errors.color }}</span>
                        </div>
                    </form>

                    <DialogFooter>
                        <Button variant="outline" @click="isModalOpen = false">Annuleren</Button>
                        <Button @click="submit" :disabled="form.processing">
                            <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                            {{ isEdit ? 'Opslaan' : 'Aanmaken' }}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    </AdminAppLayout>
</template>
