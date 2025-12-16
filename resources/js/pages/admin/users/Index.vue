<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { useForm } from '@inertiajs/vue3';
import AdminAppLayout from '@/layouts/AdminAppLayout.vue';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    AlertDialog,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import {
    Users,
    UserCog,
    ShieldCheck,
    CheckCircle2,
    MoreVertical,
    Pencil,
    Trash2,
    Loader2,
} from 'lucide-vue-next';
import { toast } from 'vue-sonner';

interface User {
    id: number;
    name: string;
    email: string;
    role: string;
    type: 'user' | 'admin';
    verified: boolean;
    created_at: string;
}

interface Stats {
    total: number;
    regular_users: number;
    admins: number;
    verified: number;
}

interface Props {
    users: User[];
    stats: Stats;
}

const props = defineProps<Props>();

const breadcrumbs = [
    { title: 'Dashboard', href: '/hoofdbeheerder/dashboard' },
    { title: 'Gebruikers', href: '/hoofdbeheerder/gebruikers' },
];

// State
const editDialogOpen = ref(false);
const deleteDialogOpen = ref(false);
const selectedUser = ref<User | null>(null);
const searchQuery = ref('');

// Edit form
const editForm = useForm({
    name: '',
    email: '',
    password: '',
});

// Delete form
const deleteForm = useForm({});

// Filtered users
const filteredUsers = computed(() => {
    if (!searchQuery.value) return props.users;

    const query = searchQuery.value.toLowerCase();
    return props.users.filter(
        (user) =>
            user.name.toLowerCase().includes(query) ||
            user.email.toLowerCase().includes(query) ||
            user.role.toLowerCase().includes(query),
    );
});

// Open edit dialog
const openEditDialog = (user: User) => {
    selectedUser.value = user;
    editForm.name = user.name;
    editForm.email = user.email;
    editForm.password = '';
    editDialogOpen.value = true;
};

// Open delete dialog
const openDeleteDialog = (user: User) => {
    selectedUser.value = user;
    deleteDialogOpen.value = true;
};

// Submit edit
const submitEdit = () => {
    if (!selectedUser.value) return;

    editForm.put(
        `/hoofdbeheerder/gebruikers/${selectedUser.value.type}/${selectedUser.value.id}`,
        {
            preserveScroll: true,
            onSuccess: () => {
                editDialogOpen.value = false;
                editForm.reset();
                toast.success('Gelukt!', {
                    description: 'Gebruiker is succesvol bijgewerkt.',
                });
            },
            onError: () => {
                toast.error('Fout!', {
                    description: 'Er is iets misgegaan. Probeer het opnieuw.',
                });
            },
        },
    );
};

// Submit delete
const submitDelete = () => {
    if (!selectedUser.value) return;

    deleteForm.delete(
        `/hoofdbeheerder/gebruikers/${selectedUser.value.type}/${selectedUser.value.id}`,
        {
            preserveScroll: true,
            onSuccess: () => {
                deleteDialogOpen.value = false;
                toast.success('Gelukt!', {
                    description: 'Gebruiker is succesvol verwijderd.',
                });
            },
            onError: () => {
                toast.error('Fout!', {
                    description: 'Er is iets misgegaan. Probeer het opnieuw.',
                });
            },
        },
    );
};

// Get role badge variant
const getRoleBadge = (role: string) => {
    return role === 'Beheerder' ? 'default' : 'secondary';
};
</script>

<template>
    <Head title="Gebruikersbeheer - Admin" />

    <AdminAppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-8 space-y-8">
            <!-- Header -->
            <div>
                <h1 class="text-3xl font-bold tracking-tight mt-2">
                    Gebruikersbeheer
                </h1>
                <p class="mt-2 text-muted-foreground">
                    Beheer alle gebruikers en beheerders van het systeem
                </p>
            </div>

            <!-- Stats Cards -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <Card>
                    <CardHeader
                        class="flex flex-row items-center justify-between space-y-0 pb-2"
                    >
                        <CardTitle class="text-sm font-medium"
                            >Totaal Gebruikers</CardTitle
                        >
                        <Users class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.total }}</div>
                        <p class="text-xs text-muted-foreground">
                            Alle accounts
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader
                        class="flex flex-row items-center justify-between space-y-0 pb-2"
                    >
                        <CardTitle class="text-sm font-medium"
                            >Reguliere Gebruikers</CardTitle
                        >
                        <UserCog class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">
                            {{ stats.regular_users }}
                        </div>
                        <p class="text-xs text-muted-foreground">
                            Standaard accounts
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader
                        class="flex flex-row items-center justify-between space-y-0 pb-2"
                    >
                        <CardTitle class="text-sm font-medium"
                            >Beheerders</CardTitle
                        >
                        <ShieldCheck class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.admins }}</div>
                        <p class="text-xs text-muted-foreground">
                            Admin accounts
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader
                        class="flex flex-row items-center justify-between space-y-0 pb-2"
                    >
                        <CardTitle class="text-sm font-medium"
                            >Geverifieerd</CardTitle
                        >
                        <CheckCircle2 class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">
                            {{ stats.verified }}
                        </div>
                        <p class="text-xs text-muted-foreground">
                            Email geverifieerd
                        </p>
                    </CardContent>
                </Card>
            </div>

            <!-- Users Table -->
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle>Alle Gebruikers</CardTitle>
                            <CardDescription>
                                Overzicht van alle gebruikers en beheerders
                            </CardDescription>
                        </div>

                        <div class="w-full max-w-sm">
                            <Input
                                v-model="searchQuery"
                                placeholder="Zoek gebruikers..."
                                class="h-9"
                            />
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Naam</TableHead>
                                <TableHead>Email</TableHead>
                                <TableHead>Rol</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Aangemaakt</TableHead>
                                <TableHead class="text-right">Acties</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="user in filteredUsers"
                                :key="`${user.type}-${user.id}`"
                            >
                                <TableCell class="font-medium">{{
                                    user.name
                                }}</TableCell>
                                <TableCell>{{ user.email }}</TableCell>
                                <TableCell>
                                    <Badge :variant="getRoleBadge(user.role)">
                                        {{ user.role }}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <Badge
                                        v-if="user.verified"
                                        variant="outline"
                                        class="gap-1"
                                    >
                                        <CheckCircle2 class="h-3 w-3" />
                                        Geverifieerd
                                    </Badge>
                                    <Badge v-else variant="secondary">
                                        Niet geverifieerd
                                    </Badge>
                                </TableCell>
                                <TableCell>{{ user.created_at }}</TableCell>
                                <TableCell class="text-right">
                                    <DropdownMenu>
                                        <DropdownMenuTrigger as-child>
                                            <Button variant="ghost" size="icon">
                                                <MoreVertical class="h-4 w-4" />
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end">
                                            <DropdownMenuItem
                                                @click="openEditDialog(user)"
                                            >
                                                <Pencil class="mr-2 h-4 w-4" />
                                                Bewerken
                                            </DropdownMenuItem>
                                            <DropdownMenuItem
                                                @click="openDeleteDialog(user)"
                                                class="text-red-600"
                                            >
                                                <Trash2 class="mr-2 h-4 w-4" />
                                                Verwijderen
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                    <div class="mt-6 flex justify-end">
                        <Link :href="'/hoofdbeheerder/gebruikers/aanmaken'">
                            <Button variant="default">
                                + Maak gebruiker aan
                            </Button>
                        </Link>
                    </div>
                </CardContent>

            </Card>
        </div>

        <!-- Edit Dialog -->
        <Dialog v-model:open="editDialogOpen">
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle>Gebruiker Bewerken</DialogTitle>
                    <DialogDescription>
                        Wijzig de gegevens van de gebruiker. Laat het wachtwoord
                        leeg om het niet te wijzigen.
                    </DialogDescription>
                </DialogHeader>
                <form @submit.prevent="submitEdit" class="space-y-4">
                    <div class="space-y-2">
                        <Label for="name">Naam</Label>
                        <Input
                            id="name"
                            v-model="editForm.name"
                            :disabled="editForm.processing"
                        />
                        <p
                            v-if="editForm.errors.name"
                            class="text-sm text-red-500"
                        >
                            {{ editForm.errors.name }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="email">Email</Label>
                        <Input
                            id="email"
                            v-model="editForm.email"
                            type="email"
                            :disabled="editForm.processing"
                        />
                        <p
                            v-if="editForm.errors.email"
                            class="text-sm text-red-500"
                        >
                            {{ editForm.errors.email }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="password"
                            >Nieuw Wachtwoord (optioneel)</Label
                        >
                        <Input
                            id="password"
                            v-model="editForm.password"
                            type="password"
                            placeholder="Laat leeg om niet te wijzigen"
                            :disabled="editForm.processing"
                        />
                        <p
                            v-if="editForm.errors.password"
                            class="text-sm text-red-500"
                        >
                            {{ editForm.errors.password }}
                        </p>
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            @click="editDialogOpen = false"
                            :disabled="editForm.processing"
                        >
                            Annuleren
                        </Button>
                        <Button type="submit" :disabled="editForm.processing">
                            <Loader2
                                v-if="editForm.processing"
                                class="mr-2 h-4 w-4 animate-spin"
                            />
                            Opslaan
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Delete Dialog -->
        <AlertDialog v-model:open="deleteDialogOpen">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Weet je het zeker?</AlertDialogTitle>
                    <AlertDialogDescription>
                        Deze actie kan niet ongedaan worden gemaakt. Dit zal
                        permanent de gebruiker
                        <strong>{{ selectedUser?.name }}</strong> verwijderen
                        uit het systeem.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel :disabled="deleteForm.processing">
                        Annuleren
                    </AlertDialogCancel>
                    <Button
                        variant="destructive"
                        @click="submitDelete"
                        :disabled="deleteForm.processing"
                    >
                        <Loader2
                            v-if="deleteForm.processing"
                            class="mr-2 h-4 w-4 animate-spin"
                        />
                        Verwijderen
                    </Button>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AdminAppLayout>
</template>
