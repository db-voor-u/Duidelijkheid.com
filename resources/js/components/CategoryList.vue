<script setup lang="ts">
import { Link } from '@inertiajs/vue3'

const props = withDefaults(defineProps<{
    categories?: {
        name: string
        slug: string
        color: string
    }[]
    baseUrl?: string
    fragment?: string
    title?: string
}>(), {
    baseUrl: '/blog',
    fragment: '#onderwerpen',
    title: 'Onderwerpen'
})
</script>

<template>
    <div class="mt-10">
        <h3 class="text-sm font-medium uppercase tracking-wider mb-4">
            {{ title }}
        </h3>
        <div v-if="!categories?.length" class="text-xs text-red-500">
            Geen categorieën gevonden (Debug: check controller/db)
        </div>
        <div class="flex flex-wrap gap-3">
            <Link
                v-for="cat in categories"
                :key="cat.slug"
                :href="`${baseUrl}?category=${cat.slug}${fragment}`"
                class="inline-flex items-center gap-2 rounded-full border border-zinc-200 bg-white/80 px-3 py-1 text-sm font-medium text-zinc-800 shadow-sm transition hover:bg-white dark:border-zinc-800 dark:bg-zinc-900/80 dark:text-zinc-200 dark:hover:bg-zinc-900"
            >
                <span :class="['h-2 w-2 rounded-full', cat.color]"></span>
                {{ cat.name }}
            </Link>
        </div>
    </div>
</template>
