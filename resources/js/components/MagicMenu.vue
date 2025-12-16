<script setup lang="ts">
import { ref } from 'vue'
import { FileDown, Video, Image as ImageIcon, Link2, FileText, Info } from 'lucide-vue-next'

const props = defineProps<{
    publishedAt?: string | null
    mediaType?: string
    downloadFilePath?: string | null
}>()

const isOpen = ref(false)
let timeout: ReturnType<typeof setTimeout> | null = null

const toggleMenu = () => {
    isOpen.value = !isOpen.value
    
    // Clear any existing timer
    if (timeout) {
        clearTimeout(timeout)
        timeout = null
    }

    // If opened, set a timer to close it after 6s
    if (isOpen.value) {
        timeout = setTimeout(() => {
            isOpen.value = false
            timeout = null
        }, 6000)
    }
}

const fmtDate = (iso?: string | null) => {
    if (!iso) return '—'
    // Ensure ISO string is properly formatted for Date constructor
    const s = iso.includes(' ') ? iso.replace(' ', 'T') : iso
    const d = new Date(s)
    if (Number.isNaN(+d)) return '—'
    return new Intl.DateTimeFormat('nl-NL', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    }).format(d)
}
</script>

<template>
    <div class="menu" :class="{ active: isOpen }">
        <div class="toggle" @click.stop="toggleMenu">
            <Info class="h-5 w-5" />
        </div>

        <li style="--i:0">
            <div class="wrapper">
                <div class="magic-pill">
                    {{ fmtDate(publishedAt) }}
                </div>
            </div>
        </li>

        <li style="--i:1">
            <div class="wrapper">
                <div class="magic-pill">
                    <span v-if="downloadFilePath" class="flex items-center gap-1.5">
                        <FileDown class="h-3.5 w-3.5" /> Download
                    </span>
                    <span v-else-if="mediaType === 'youtube' || mediaType === 'upload'" class="flex items-center gap-1.5">
                        <Video class="h-3.5 w-3.5" /> Video
                    </span>
                    <span v-else-if="mediaType === 'image'" class="flex items-center gap-1.5">
                        <ImageIcon class="h-3.5 w-3.5" /> Afbeelding
                    </span>
                    <span v-else-if="mediaType === 'url'" class="flex items-center gap-1.5">
                        <Link2 class="h-3.5 w-3.5" /> Link
                    </span>
                    <span v-else class="flex items-center gap-1.5">
                        <FileText class="h-3.5 w-3.5" /> Artikel
                    </span>
                </div>
            </div>
        </li>
    </div>
</template>

<style scoped>
.menu {
    position: relative;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.menu .toggle {
    position: relative;
    height: 40px;
    width: 40px;
    background: #fff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #333;
    cursor: pointer;
    transition: 0.5s;
    z-index: 35;
    box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
}

.menu.active .toggle {
    transform: rotate(360deg);
    background: hsl(258, 80%, 58%);
    color: #fff;
}

.menu li {
    position: absolute;
    left: 0;
    top: 0;
    width: 40px;
    height: 40px;
    list-style: none;
    transition: 0.5s;
    z-index: 25;
    pointer-events: none;
    display: flex;
    align-items: center;
    justify-content: center;
    transform-origin: 20px 20px; /* Center of toggle */
    transform: rotate(0deg) translateX(0px);
    opacity: 0;
}

.menu.active li {
    opacity: 1;
    pointer-events: auto;
    /* 
       Item 0: 180deg (Left)
       Item 1: 130deg (Top-Left)
       This avoids clipping at the bottom of the card.
    */
    transform: rotate(calc(180deg - (50deg * var(--i)))) translateX(90px);
}

.menu li .wrapper {
    position: relative;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    transform: rotate(0deg);
    transition: transform 0.5s;
    transform-origin: center center;
}

.menu.active li .wrapper {
    /* Counter-rotate to keep content upright */
    transform: rotate(calc(-1 * (180deg - (50deg * var(--i)))));
}

.magic-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    white-space: nowrap;
    background: #fff;
    padding: 6px 14px;
    border-radius: 9999px; /* Pill */
    box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
    color: #333;
    font-size: 0.75rem;
    font-weight: 500;
    transition: 0.3s;
    border: 1px solid #e5e7eb;
    position: relative;
    z-index: 30;
}

.dark .magic-pill {
    background: #27272a;
    color: #e4e4e7;
    border-color: #3f3f46;
}

.magic-pill:hover {
    color: hsl(258, 80%, 58%);
    border-color: hsl(258, 80%, 58%);
}
</style>
