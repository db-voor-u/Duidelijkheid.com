<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Button } from '@/components/ui/button'
import { Cookie, ShieldCheck, X } from 'lucide-vue-next'

const isOpen = ref(false)

const checkConsent = () => {
    const consent = localStorage.getItem('cookie_consent')
    if (!consent) {
        // Delay showing to allow page load animation to finish
        setTimeout(() => isOpen.value = true, 1000)
    }
}

const acceptAll = () => {
    localStorage.setItem('cookie_consent', 'all')
    isOpen.value = false
}

const acceptEssential = () => {
    localStorage.setItem('cookie_consent', 'essential')
    isOpen.value = false
}

onMounted(() => {
    checkConsent()
})
</script>

<template>
    <Transition
        enter-active-class="transition duration-700 ease-out"
        enter-from-class="translate-y-full opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition duration-500 ease-in"
        leave-from-class="translate-y-0 opacity-100"
        leave-to-class="translate-y-full opacity-0"
    >
        <div 
            v-if="isOpen" 
            class="fixed bottom-0 left-0 right-0 z-50 flex justify-center p-4 sm:p-6"
        >
            <div class="relative w-full max-w-4xl overflow-hidden rounded-2xl border border-white/10 bg-zinc-950/80 p-6 shadow-2xl backdrop-blur-xl ring-1 ring-black/5 dark:border-zinc-800 dark:bg-zinc-900/80">
                <!-- Decorative Glows -->
                <div class="absolute -left-20 -top-20 h-60 w-60 rounded-full bg-indigo-500/20 blur-3xl" />
                <div class="absolute -right-20 -bottom-20 h-60 w-60 rounded-full bg-purple-500/20 blur-3xl" />

                <div class="relative z-10 flex flex-col items-start gap-6 sm:flex-row sm:items-center">
                    <!-- Icon -->
                    <div class="flex h-12 w-12 flex-none items-center justify-center rounded-full bg-indigo-500/10 ring-1 ring-indigo-500/20">
                        <Cookie class="h-6 w-6 text-indigo-400" />
                    </div>

                    <!-- Text -->
                    <div class="flex-1 space-y-1">
                        <h3 class="text-lg font-semibold text-white">Wij gebruiken cookies 🍪</h3>
                        <p class="text-sm leading-relaxed text-zinc-300">
                            We plaatsen functionele en analytische cookies om deze website te verbeteren. 
                            Door te klikken op 'Alles accepteren' ga je akkoord met het gebruik van alle cookies. 
                            Kies je voor 'Alleen noodzakelijk', dan plaatsen we enkel cookies die nodig zijn voor het functioneren van de site.
                        </p>
                    </div>

                    <!-- Actions -->
                    <div class="flex flex-none flex-col gap-2 sm:flex-row">
                         <Button 
                            variant="outline" 
                            class="border-zinc-700 bg-transparent text-zinc-300 hover:bg-white/5 hover:text-white"
                            @click="acceptEssential"
                        >
                            Alleen noodzakelijk
                        </Button>
                        <Button 
                            class="bg-indigo-600 text-white hover:bg-indigo-700 dark:bg-indigo-600 dark:text-white dark:hover:bg-indigo-700 shadow-lg shadow-indigo-500/20"
                            @click="acceptAll"
                        >
                            Alles accepteren
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </Transition>
</template>
