<script setup lang="ts">
import AdminUserInfo from '@/components/AdminUserInfo.vue';
import AdminUserMenuContent from '@/components/AdminUserMenuContent.vue';
import Logout from '@/components/auth/Logout.vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { usePage } from '@inertiajs/vue3';
import { ChevronsUpDown } from 'lucide-vue-next';
import { ref } from 'vue';

const page = usePage();
const user = page.props.auth.user;
const { isMobile, state } = useSidebar();
const logoutRef = ref();

const handleLogout = () => {
    logoutRef.value?.openConfirmation();
};
</script>

<template>
    <SidebarMenu>
        <SidebarMenuItem>
            <DropdownMenu v-if="user">
                <DropdownMenuTrigger as-child>
                    <SidebarMenuButton
                        size="lg"
                        class="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground"
                        data-test="sidebar-menu-button"
                    >
                        <AdminUserInfo :user="user" />
                        <ChevronsUpDown class="ml-auto size-4" />
                    </SidebarMenuButton>
                </DropdownMenuTrigger>
                <DropdownMenuContent
                    class="w-(--reka-dropdown-menu-trigger-width) min-w-56 rounded-lg"
                    :side="
                        isMobile
                            ? 'bottom'
                            : state === 'collapsed'
                              ? 'left'
                              : 'bottom'
                    "
                    align="end"
                    :side-offset="4"
                >
                    <AdminUserMenuContent :user="user" @logout="handleLogout" />
                </DropdownMenuContent>
            </DropdownMenu>

            <!-- Headless Logout Component -->
            <Logout ref="logoutRef" class="hidden" />
        </SidebarMenuItem>
    </SidebarMenu>
</template>
