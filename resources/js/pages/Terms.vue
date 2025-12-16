<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import NavBarPublic from '@/components/NavBarPublic.vue'
import Footer from '@/components/Footer.vue'
import { Separator } from '@/components/ui/separator'
import { Button } from '@/components/ui/button'
import { ArrowLeft } from 'lucide-vue-next'

defineProps<{
    terms?: {
        title?: string
        content?: string
    } | null
}>()

const goBack = () => {
    // Check if this page was opened in a new tab (via window.open)
    if (window.history.length > 2) {
        window.history.back()
    } else {
        // Try to close this tab
        window.close()
        // Fallback if window.close() doesn't work
        setTimeout(() => {
            window.location.href = '/'
        }, 100)
    }
}
</script>

<template>
    <Head :title="terms?.title || 'Algemene Voorwaarden'" />

    <NavBarPublic />

    <div class="mx-auto min-h-screen max-w-4xl px-4 py-10 sm:px-6 lg:px-8">
        <section class="max-w-prose">
            <!-- Terug knop bovenaan -->
            <Button 
                variant="ghost" 
                @click="goBack" 
                class="mb-6 gap-2 text-muted-foreground hover:text-foreground"
            >
                <ArrowLeft class="h-4 w-4" />
                Terug
            </Button>

            <h1 class="mb-6 text-3xl font-extrabold tracking-tight sm:text-4xl text-left">
                {{ terms?.title || 'Algemene Voorwaarden' }}
            </h1>
            
            <div v-if="terms?.content" class="prose prose-zinc leading-relaxed dark:prose-invert" v-html="terms.content"></div>
            <div v-else class="text-muted-foreground italic">Nog geen inhoud geplaatst.</div>

            <Separator class="my-8" />

            <!-- Terug knop onderaan -->
            <Button 
                variant="outline" 
                @click="goBack" 
                class="gap-2"
            >
                <ArrowLeft class="h-4 w-4" />
                Terug naar vorige pagina
            </Button>
        </section>
    </div>
    <Footer />
</template>
