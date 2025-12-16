<script setup lang="ts">
import { Shield, LogOut } from 'lucide-vue-next';
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

interface Props {
    triggerClass?: string;
    buttonText?: string;
    showConfirmation?: boolean;
    logoutUrl?: string;
}

const props = withDefaults(defineProps<Props>(), {
    triggerClass: 'flex items-center gap-2 px-3 py-1. 5 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 cursor-pointer transition-colors',
    buttonText: 'Uitloggen',
    showConfirmation: true,
    logoutUrl: '/hoofdbeheerder/logout',
});

const showLogoutConfirmation = ref(false);
const isLoggingOut = ref(false);

const openConfirmation = () => {
    if (props.showConfirmation) {
        showLogoutConfirmation.value = true;
    } else {
        performLogout();
    }
};

const handleLogoutClick = () => {
    openConfirmation();
};

const cancelLogout = () => {
    showLogoutConfirmation.value = false;
};

const performLogout = () => {
    if (isLoggingOut.value) return;

    isLoggingOut.value = true;
    showLogoutConfirmation.value = false;

    // Use Inertia router for proper POST request with CSRF
    router.post(props.logoutUrl, {}, {
        onSuccess: () => {
            // Success - Laravel controller handles redirect
        },
        onError: () => {
            // Fallback on error
            window.location.href = '/hoofdbeheerder/login';
        },
        onFinish: () => {
            isLoggingOut.value = false;
        }
    });
};

defineExpose({
    openConfirmation,
});
</script>

<template>
    <div class="relative">
        <!-- Logout Trigger Button -->
        <button
            @click="handleLogoutClick"
            :class="triggerClass"
            :disabled="isLoggingOut || showLogoutConfirmation"
            type="button"
        >
            <LogOut class="h-4 w-4" :class="{ 'animate-spin': isLoggingOut }" />
            <span>{{ isLoggingOut ?  'Uitloggen...' : buttonText }}</span>
        </button>

        <!-- Confirmation Modal (Teleported) -->
        <Teleport to="body">
            <div v-if="showLogoutConfirmation" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                <!-- Backdrop -->
                <div 
                    class="absolute inset-0 bg-black/40 backdrop-blur-[1px]" 
                    @click="cancelLogout"
                ></div>

                <!-- Modal Content -->
                <div class="relative bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-xl p-6 max-w-sm w-full">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0">
                            <div class="p-2 bg-amber-50 dark:bg-amber-900/20 rounded-full">
                                <Shield class="h-6 w-6 text-amber-500" />
                            </div>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">
                                Bevestig uitloggen
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
                                Weet je zeker dat je wilt uitloggen? Je sessie wordt beëindigd.
                            </p>
                            <div class="flex justify-end gap-3">
                                <button
                                    @click="cancelLogout"
                                    type="button"
                                    :disabled="isLoggingOut"
                                    class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors"
                                >
                                    Annuleren
                                </button>
                                <button
                                    @click="performLogout"
                                    type="button"
                                    :disabled="isLoggingOut"
                                    class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 disabled:opacity-50 transition-colors"
                                >
                                    {{ isLoggingOut ? 'Bezig...' : 'Ja, uitloggen' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>
