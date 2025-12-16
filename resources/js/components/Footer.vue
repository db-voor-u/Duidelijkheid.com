<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { computed } from 'vue'

const currentYear = computed(() => new Date().getFullYear())
</script>

<template>
    <footer class="border-t bg-background/95 py-6 backdrop-blur supports-[backdrop-filter]:bg-background/60">
        <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-4 px-4 text-sm text-muted-foreground sm:flex-row sm:px-6 lg:px-8">
            <div class="flex items-center gap-4">
                <span class="font-medium">&copy; {{ currentYear }} Duidelijkheid.com</span>
            </div>
            
            <nav class="flex gap-6">
                <Link href="/algemene-voorwaarden" class="transition-colors hover:text-foreground">
                    Algemene Voorwaarden
                </Link>
                <Link href="/privacy" class="transition-colors hover:text-foreground">
                    Privacybeleid
                </Link>
            </nav>
        </div>
    </footer>
</template>
