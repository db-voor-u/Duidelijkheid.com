<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
</script>

<template>
    <div class="flex items-center gap-2 text-sidebar-primary-foreground">
        <div
            class="flex aspect-square size-8 items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground"
        >
            <AppLogoIcon class="size-5" />
        </div>
        <div class="grid flex-1 text-left text-sm">
            <span class="truncate font-semibold text-sidebar-foreground">Duidelijkheid.com</span>
        </div>
    </div>
</template>
