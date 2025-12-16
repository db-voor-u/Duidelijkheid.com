<script setup lang="ts">
import AdminAppLayout from '@/layouts/app/AdminAppSidebarLayout.vue';
import type { BreadcrumbItemType } from '@/types';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <AdminAppLayout :breadcrumbs="breadcrumbs">
        <slot />
    </AdminAppLayout>
</template>
