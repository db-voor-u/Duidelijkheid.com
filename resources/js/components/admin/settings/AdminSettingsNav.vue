<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { adminRoutes } from '@/lib/routes';
import { Button } from '@/components/ui/button';

// Helper to check active state
const currentPath = usePage().url;

// Nav Items
const items = [
    { title: 'Profiel', href: adminRoutes.settingsProfile },
    { title: 'Wachtwoord', href: adminRoutes.settingsPassword },
    { title: 'Uiterlijk', href: adminRoutes.settingsAppearance },
];

function isActive(href: string) {
    // Only match path, ignore query params if any
    const path = new URL(href, window.location.origin).pathname;
    return currentPath.startsWith(path);
}
</script>

<template>
    <nav class="flex space-x-2 lg:flex-col lg:space-x-0 lg:space-y-1">
        <Link 
            v-for="item in items" 
            :key="item.href" 
            :href="item.href"
            :class="[
                'justify-start text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 hover:bg-accent hover:text-accent-foreground h-9 px-4 py-2 inline-flex items-center whitespace-nowrap rounded-md',
                isActive(item.href) ? 'bg-muted hover:bg-muted' : 'bg-transparent'
            ]"
        >
            {{ item.title }}
        </Link>
    </nav>
</template>
