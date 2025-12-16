<script setup lang="ts">
import { ref, computed, nextTick } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import ContactForm from "@/components/ContactForm.vue";

const page = usePage()
const contactData = computed(() => {
    return (page.props.contactPage as any) ?? {
        title: 'Heb je een vraag?',
        content: 'Neem gerust contact met ons op via het formulier.',
        form_title: 'Contact',
        form_content: 'Vul het formulier in en we nemen zo snel mogelijk contact met je op.',
        button_text: 'Neem contact op',
    }
})

const showForm = ref(false)
const containerRef = ref<HTMLElement | null>(null)

const openForm = () => {
    showForm.value = true
    nextTick(() => {
        containerRef.value?.scrollIntoView({ behavior: 'smooth', block: 'start' })
    })
}

const handleSubmitted = () => {
    showForm.value = false
}
</script>

<template>
    <div ref="containerRef" class="rounded-2xl bg-zinc-50 p-6 sm:p-8 shadow-sm dark:bg-zinc-900/50 scroll-mt-24 -mx-4 sm:mx-0 sm:w-full">
        <div v-if="!showForm" class="text-center">
            <h2 class="text-2xl font-bold tracking-tight sm:text-3xl">{{ contactData.title }}</h2>
            <div class="mt-4 text-muted-foreground prose dark:prose-invert max-w-none mx-auto" v-html="contactData.content"></div>
            
            <div class="mt-8 flex justify-center">
                <Button @click="openForm" size="lg" class="h-12 rounded-full px-8 text-base font-semibold shadow-xl transition-all hover:scale-105 hover:shadow-2xl">
                    {{ contactData.button_text }}
                </Button>
            </div>
        </div>

        <div v-else class="animate-in fade-in zoom-in duration-300 w-full">
            <div class="mb-6">
                    <h2 class="text-3xl font-bold tracking-tight sm:text-4xl text-foreground">{{ contactData.form_title }}</h2>
                    <div class="mt-2 text-muted-foreground prose dark:prose-invert max-w-none" v-html="contactData.form_content"></div>
            </div>
            <ContactForm @submitted="handleSubmitted" />
            <div class="mt-4">
                    <Button variant="ghost" @click="showForm = false" class="text-muted-foreground hover:text-foreground">
                    Annuleren
                    </Button>
            </div>
        </div>
    </div>
</template>
