<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import AdminNavUser from '@/components/AdminNavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { adminRoutes } from "@/lib/routes";
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { LayoutGrid,Home,FolderKanban,Mail,Users, Tags, FileText, Settings, MessageSquare } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: adminRoutes.dashboard,
        icon: LayoutGrid,
    },
    {
        title: 'Feedback',
        href: '/hoofdbeheerder/feedback',
        icon: MessageSquare,
    },
    {
        title: 'Pagina\'s',
        href: '#',
        icon: FileText,
        items: [
            {
                title: 'Welkom hero inhoud',
                href: adminRoutes.welcomeContent,
                icon: Home,
            },
            {
                title: "Blog hero inhoud",
                href: adminRoutes.blogContent,
                icon: FolderKanban,
            },
            {
                title: "Over Ons hero inhoud",
                href: adminRoutes.overOnsContent,
                icon: Users,
            },
            {
                title: "Innovatie hero inhoud",
                href: adminRoutes.innovatieContent,
                icon: FileText,
            },

            {
                title: "Algemene Voorwaarden",
                href: '/hoofdbeheerder/pages/terms',
                icon: FileText,
            },
            {
                title: "Privacybeleid",
                href: adminRoutes.privacyContent,
                icon: FileText,
            },
        ]
    },
    {
        title: "Contact kaart",
        href: adminRoutes.contactPage,
        icon: Mail,
    },
    {
        title: "Categorieën",
        href: '/hoofdbeheerder/categorieen',
        icon: Tags,
    },
    {
        title: 'Instellingen',
        href: adminRoutes.settings,
        icon: Settings,
    },
];

const footerNavItems: NavItem[] = [];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <div class="px-2 py-2">
                        <AppLogo />
                    </div>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <AdminNavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
