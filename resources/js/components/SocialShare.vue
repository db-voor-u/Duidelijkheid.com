<script setup lang="ts">
import { computed } from 'vue'
import { 
    Share2, 
    Link as LinkIcon, 
    Mail, 
    MessageCircle, // WhatsApp-ish
    Linkedin,
    Facebook,
    Twitter 
} from 'lucide-vue-next'
import { 
    DropdownMenu, 
    DropdownMenuTrigger, 
    DropdownMenuContent, 
    DropdownMenuItem, 
    DropdownMenuLabel, 
    DropdownMenuSeparator 
} from '@/components/ui/dropdown-menu'
import { Button } from '@/components/ui/button'
import { toast } from 'vue-sonner'

const props = defineProps<{
    title?: string
    url?: string
}>()

// Use current URL if not provided
const currentUrl = computed(() => {
    if (props.url) return props.url
    if (typeof window !== 'undefined') return window.location.href
    return ''
})

const text = computed(() => props.title || 'Check dit artikel!')

// Share Links
const links = computed(() => [
    {
        label: 'WhatsApp',
        icon: MessageCircle,
        href: `https://wa.me/?text=${encodeURIComponent(text.value + ' ' + currentUrl.value)}`,
        color: 'text-green-500'
    },
    {
        label: 'Facebook',
        icon: Facebook,
        href: `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(currentUrl.value)}`,
        color: 'text-blue-600'
    },
    {
        label: 'LinkedIn',
        icon: Linkedin,
        href: `https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(currentUrl.value)}`,
        color: 'text-blue-700'
    },
    {
        label: 'X / Twitter',
        icon: Twitter,
        href: `https://twitter.com/intent/tweet?text=${encodeURIComponent(text.value)}&url=${encodeURIComponent(currentUrl.value)}`,
        color: 'text-black dark:text-white'
    },
    {
        label: 'E-mail',
        icon: Mail,
        href: `mailto:?subject=${encodeURIComponent(text.value)}&body=${encodeURIComponent(currentUrl.value)}`,
        color: 'text-zinc-500'
    }
])

// Actions
const copyLink = async () => {
    try {
        await navigator.clipboard.writeText(currentUrl.value)
        toast.success('Link gekopieerd!')
    } catch (err) {
        toast.error('Kon link niet kopiëren')
    }
}

const shareNative = async () => {
    if (navigator.share) {
        try {
            await navigator.share({
                title: props.title,
                text: props.title,
                url: currentUrl.value
            })
        } catch (err) {
            // User cancelled or failed
        }
    }
}
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <slot>
                <Button variant="outline" size="sm" class="gap-2">
                    <Share2 class="h-4 w-4" />
                    Delen
                </Button>
            </slot>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-56">
            <DropdownMenuLabel>Deel via</DropdownMenuLabel>
            <DropdownMenuSeparator />
            
            <DropdownMenuItem v-for="l in links" :key="l.label" as-child>
                <a :href="l.href" target="_blank" rel="noopener noreferrer" class="flex items-center gap-2 cursor-pointer">
                    <component :is="l.icon" class="h-4 w-4" :class="l.color" />
                    {{ l.label }}
                </a>
            </DropdownMenuItem>

            <DropdownMenuSeparator />
            
            <DropdownMenuItem @click="copyLink" class="cursor-pointer">
                <LinkIcon class="mr-2 h-4 w-4" />
                Link kopiëren
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
