<script setup lang="ts">
import { ref, watch } from 'vue'
import { MessageSquare, Send, ThumbsUp, ThumbsDown, CheckCircle2, X, Loader2 } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Textarea } from '@/components/ui/textarea'
import { Input } from '@/components/ui/input'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { useForm } from '@inertiajs/vue3'

const props = defineProps<{
    articleTitle?: string
    pageContext?: string
}>()

const isOpen = ref(false)
const isSubmitted = ref(false)

const form = useForm({
    name: '',
    email: '',
    message: '',
    sentiment: null as 'positive' | 'negative' | null,
    page_context: props.pageContext || props.articleTitle || 'Onbekend'
})

const submitFeedback = () => {
    if (!form.message && !form.sentiment) return
    
    form.page_context = props.pageContext || props.articleTitle || 'Onbekend'

    form.post('/feedback', {
        preserveScroll: true,
        onSuccess: () => {
            isSubmitted.value = true
            form.reset()
            // Auto close after 5 seconds
            setTimeout(() => {
                isOpen.value = false
                // Reset submission state after closing so it's fresh next time
                setTimeout(() => {
                    isSubmitted.value = false
                }, 500)
            }, 5000)
        }
    })
}

const toggleOpen = () => {
    isOpen.value = !isOpen.value
}
</script>

<template>
    <div>
        <!-- Trigger Button (Inline, Centered) -->
        <div class="flex justify-center mt-8 mb-8 no-print">
            <Button
                variant="outline"
                size="lg"
                class="gap-2 shadow-sm hover:shadow-md transition-all duration-300 rounded-full border-primary/20 hover:border-primary/50 bg-background/50 hover:bg-background hover:scale-105"
                @click="isOpen = true"
            >
                <MessageSquare class="h-5 w-5 text-primary transition-transform duration-300" />
                <span class="font-medium">Geef feedback</span>
            </Button>
        </div>

        <!-- Modal Overlay -->
        <Teleport to="body">
            <transition
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div 
                    v-if="isOpen" 
                    class="fixed inset-0 z-[100] flex items-center justify-center bg-black/40 backdrop-blur-sm p-4"
                    @click.self="isOpen = false"
                >
                    <transition
                        appear
                        enter-active-class="transition duration-400 ease-out"
                        enter-from-class="opacity-0 scale-90 translate-y-8"
                        enter-to-class="opacity-100 scale-100 translate-y-0"
                        leave-active-class="transition duration-200 ease-in"
                        leave-from-class="opacity-100 scale-100 translate-y-0"
                        leave-to-class="opacity-0 scale-95 translate-y-4"
                    >
                        <Card v-if="isOpen" class="w-full max-w-[400px] shadow-2xl border-zinc-200 dark:border-zinc-800 relative z-[101] transform-gpu">
                             <!-- Close Button -->
                            <Button 
                                variant="ghost" 
                                size="icon" 
                                class="absolute right-2 top-2 h-6 w-6 rounded-full text-muted-foreground hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors duration-200"
                                @click="isOpen = false"
                            >
                                <X class="h-4 w-4" />
                            </Button>
            
                            <CardHeader v-if="!isSubmitted" class="pb-2 pt-4">
                                <CardTitle class="text-lg flex items-center gap-2">
                                    <MessageSquare class="h-5 w-5 text-primary" />
                                    Feedback
                                </CardTitle>
                                <CardDescription class="text-xs">
                                    Wat vind je van deze pagina?
                                </CardDescription>
                            </CardHeader>
                            <CardContent class="pb-4">
                                <!-- Success State -->
                                <transition
                                    enter-active-class="transition duration-500 ease-out"
                                    enter-from-class="opacity-0 scale-90"
                                    enter-to-class="opacity-100 scale-100"
                                >
                                    <div v-if="isSubmitted" class="flex flex-col items-center justify-center py-6 text-center space-y-3 relative overflow-hidden">
                                        <div class="flex h-14 w-14 items-center justify-center rounded-full bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400 animate-bounce-once">
                                            <CheckCircle2 class="h-7 w-7" />
                                        </div>
                                        <div>
                                            <h3 class="font-semibold text-green-800 dark:text-green-300">Bedankt!</h3>
                                            <p class="text-xs text-green-600/80 dark:text-green-400/80">
                                                Dit venster sluit over 5 seconden.
                                            </p>
                                        </div>
                                        <!-- Progress Bar -->
                                        <div class="absolute bottom-0 left-0 h-1.5 bg-green-500/20 w-full rounded-full overflow-hidden">
                                            <div class="h-full bg-gradient-to-r from-green-400 to-green-600 animate-progress origin-left rounded-full"></div>
                                        </div>
                                    </div>
                                </transition>
            
                                <!-- Form State -->
                                <div v-if="!isSubmitted" class="space-y-4">
                                    <!-- Sentiment -->
                                    <div class="flex gap-2">
                                        <button 
                                            type="button" 
                                            class="flex-1 flex flex-col items-center gap-1 p-3 rounded-lg border transition-all duration-200 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 hover:scale-[1.02] active:scale-[0.98]"
                                            :class="form.sentiment === 'positive' ? 'border-primary bg-primary/5 shadow-sm' : 'border-zinc-200 dark:border-zinc-800'"
                                            @click="form.sentiment = 'positive'"
                                        >
                                            <ThumbsUp 
                                                class="h-5 w-5 transition-all duration-200"
                                                :class="form.sentiment === 'positive' ? 'text-primary scale-110' : 'text-muted-foreground'"
                                            />
                                            <span class="text-[10px] font-medium uppercase tracking-wider text-muted-foreground">Goed</span>
                                        </button>
                                         <button 
                                            type="button" 
                                            class="flex-1 flex flex-col items-center gap-1 p-3 rounded-lg border transition-all duration-200 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 hover:scale-[1.02] active:scale-[0.98]"
                                            :class="form.sentiment === 'negative' ? 'border-primary bg-primary/5 shadow-sm' : 'border-zinc-200 dark:border-zinc-800'"
                                            @click="form.sentiment = 'negative'"
                                        >
                                            <ThumbsDown 
                                                class="h-5 w-5 transition-all duration-200"
                                                :class="form.sentiment === 'negative' ? 'text-primary scale-110' : 'text-muted-foreground'"
                                            />
                                            <span class="text-[10px] font-medium uppercase tracking-wider text-muted-foreground">Kan beter</span>
                                        </button>
                                    </div>
            
                                    <!-- Inputs -->
                                    <div class="space-y-2">
                                        <Input v-model="form.name" placeholder="Naam (optioneel)" class="h-9 text-sm transition-all duration-200 focus:scale-[1.01]" />
                                        <Input v-model="form.email" type="email" placeholder="Email (optioneel)" class="h-9 text-sm transition-all duration-200 focus:scale-[1.01]" />
                                    </div>
            
                                     <Textarea 
                                        v-model="form.message" 
                                        placeholder="Opmerking..." 
                                        class="min-h-[80px] text-sm resize-none transition-all duration-200 focus:scale-[1.005]"
                                    />
            
                                    <Button 
                                        class="w-full h-10 transition-all duration-300 hover:scale-[1.02] active:scale-[0.98]" 
                                        @click="submitFeedback" 
                                        :disabled="form.processing || (!form.sentiment && !form.message)"
                                    >
                                         <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                                         <Send v-else class="mr-2 h-4 w-4" />
                                         {{ form.processing ? 'Versturen...' : 'Versturen' }}
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    </transition>
                </div>
            </transition>
        </Teleport>
    </div>
</template>

<style scoped>
.animate-progress {
    animation: progress 5s linear forwards;
    width: 100%;
}

@keyframes progress {
    from { width: 100%; }
    to { width: 0%; }
}

.animate-bounce-once {
    animation: bounce-once 0.6s ease-out;
}

@keyframes bounce-once {
    0% { transform: scale(0); opacity: 0; }
    50% { transform: scale(1.2); }
    70% { transform: scale(0.9); }
    100% { transform: scale(1); opacity: 1; }
}

/* Smooth duration for different transition speeds */
.duration-400 {
    transition-duration: 400ms;
}
</style>
