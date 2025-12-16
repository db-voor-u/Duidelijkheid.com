<script setup lang="ts">
import type { Admin } from '@/types';

interface Props {
    user?: Admin | null;
    showEmail?: boolean;
}

withDefaults(defineProps<Props>(), {
    showEmail: false,
});
</script>

<template>
    <div v-if="user" class="flex items-center gap-3">
        <div
            class="h-8 w-8 rounded-lg bg-primary flex items-center justify-center text-primary-foreground text-sm font-semibold"
        >
            {{ user.name?.charAt(0)?.toUpperCase() || '?' }}
        </div>

        <div class="text-left flex-1">
            <div class="font-medium truncate">{{ user.name || 'Unknown' }}</div>
            <div v-if="showEmail" class="text-xs text-muted-foreground truncate">
                {{ user.email || '' }}
            </div>
        </div>
    </div>
</template>
