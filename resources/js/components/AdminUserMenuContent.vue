<script setup lang="ts">
import AdminUserInfo from '@/components/AdminUserInfo.vue';
import {
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuSub,
    DropdownMenuSubTrigger,
    DropdownMenuSubContent,
    DropdownMenuPortal,
    DropdownMenuCheckboxItem,
} from '@/components/ui/dropdown-menu';
import type { Admin } from '@/types';
import { Link } from '@inertiajs/vue3';
import { Settings, LogOut, Moon, Sun, Monitor } from 'lucide-vue-next';
import { useAppearance } from '@/composables/useAppearance';
import { adminRoutes } from '@/lib/routes';

interface Props {
    user: Admin;
}

defineProps<Props>();
const emit = defineEmits(['logout']);

const { appearance, updateAppearance } = useAppearance();
</script>

<template>
    <div class="p-1">
        <DropdownMenuLabel class="p-0 font-normal">
            <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
                <AdminUserInfo :user="user" :show-email="true" />
            </div>
        </DropdownMenuLabel>

        <DropdownMenuSeparator />

        <DropdownMenuItem 
            @select.prevent="emit('logout')"
            class="flex items-center w-full px-2 py-1.5 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 cursor-pointer focus:bg-red-50 dark:focus:bg-red-900/20 focus:text-red-600"
        >
            <LogOut class="mr-2 h-4 w-4" />
            Uitloggen
        </DropdownMenuItem>
    </div>
</template>
