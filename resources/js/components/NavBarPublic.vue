<script setup lang="ts">
import { computed, ref } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'

// shadcn-vue ui
import {
    NavigationMenu,
    NavigationMenuList,
    NavigationMenuItem,
    NavigationMenuTrigger,
    NavigationMenuContent,
    NavigationMenuLink,
} from '@/components/ui/navigation-menu'
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip'
import { Button } from '@/components/ui/button'
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetTrigger, SheetDescription } from '@/components/ui/sheet'
import { Separator } from '@/components/ui/separator'
import { Menu, Moon, Sun } from 'lucide-vue-next'

// Dark mode (vueuse)
import { useDark, useToggle } from '@vueuse/core'

import AppLogo from '@/components/AppLogo.vue'
import CookieConsent from '@/components/CookieConsent.vue'

type NavLink = { label: string; href: string; icon?: any; tooltip?: string }
type NavGroup = { label: string; items: NavLink[] }
type NavCta = { label: string; href: string|null }

const page = usePage()

const defaults = {
    links: [
        { label: 'Welkom', href: '/', tooltip: 'Ga naar de startpagina' },
        { label: 'Blog', href: '/blog', tooltip: 'Bekijk onze artikelen' },

    ] as NavLink[],
    groups: [
        {
            label: 'Meer',
            items: [
                { label: 'Over ons', href: '/over-ons' },
                { label: 'Innovatie', href: '/innovatie' },
                { label: 'Algemene Voorwaarden', href: '/algemene-voorwaarden' },
            ],
        },
    ] as NavGroup[],
    cta: null as NavCta | null,
}

const nav = computed(() => {
    const p = (page.props.value as any)?.navPublic ?? {}
    return {
        links: (p.links ?? defaults.links) as NavLink[],
        groups: (p.groups ?? defaults.groups) as NavGroup[],
        cta: (p.cta ?? defaults.cta) as NavCta | null,
    }
})

const isActive = (href: string) => {
    try {
        const current = new URL(location.href)
        return current.pathname === href || (href !== '/' && current.pathname.startsWith(href))
    } catch {
        return false
    }
}

const open = ref(false)

// -------- Dark mode --------
const isDark = useDark({
    selector: 'html',
    attribute: 'class',
    valueDark: 'dark',
    valueLight: '',
    storageKey: 'theme',
})
const toggleDark = useToggle(isDark)
</script>

<template>
    <header class="sticky top-0 z-40 w-full border-b bg-background/80 backdrop-blur">
        <div class="mx-auto flex h-16 w-full max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">

            <!-- Logo -->
            <div class="flex items-center gap-3">
                <AppLogo class="h-8 w-auto" />
            </div>

            <!-- Desktop nav -->
            <nav class="hidden md:block">
                <NavigationMenu>
                    <NavigationMenuList class="items-center gap-1">
                        <!-- Links -->
                        <NavigationMenuItem v-for="l in nav.links" :key="`link-${l.href}`">
                            <TooltipProvider>
                                <Tooltip>
                                    <TooltipTrigger as-child>
                                        <Link :href="l.href" as="button">
                                            <NavigationMenuLink
                                                class="rounded-md px-3 py-2 text-sm font-medium transition hover:bg-muted flex items-center gap-2"
                                                :class="isActive(l.href) ? 'bg-primary' : ''"
                                            >
                                                <!-- Only render icon if it exists -->
                                                <component :is="l.icon" v-if="l.icon" class="h-4 w-4" />
                                                <span>{{ l.label }}</span>
                                            </NavigationMenuLink>
                                        </Link>
                                    </TooltipTrigger>
                                    <TooltipContent side="bottom" v-if="l.tooltip">
                                        <p>{{ l.tooltip }}</p>
                                    </TooltipContent>
                                </Tooltip>
                            </TooltipProvider>
                        </NavigationMenuItem>

                        <!-- Groups (Meer) -->
                        <NavigationMenuItem v-for="g in nav.groups" :key="`grp-${g.label}`">
                            <NavigationMenuTrigger class="text-sm font-medium">{{ g.label }}</NavigationMenuTrigger>
                            <NavigationMenuContent>
                                <div class="flex flex-col w-full sm:w-[200px] gap-1 p-2">
                                    <Link
                                        v-for="it in g.items"
                                        :key="`gi-${it.href}`"
                                        :href="it.href"
                                        class="rounded-md p-2 text-sm hover:bg-muted"
                                        :class="isActive(it.href) ? 'bg-primary' : ''"
                                    >
                                        {{ it.label }}
                                    </Link>
                                </div>
                            </NavigationMenuContent>
                        </NavigationMenuItem>


                        <!-- Desktop dark mode toggle -->
                        <NavigationMenuItem>
                            <Button
                                variant="ghost"
                                size="icon"
                                class="ml-1"
                                :aria-pressed="isDark"
                                :aria-label="isDark ? 'Schakel licht modus in' : 'Schakel donkere modus in'"
                                @click="toggleDark()"
                            >
                                <Sun v-if="isDark" class="h-5 w-5" />
                                <Moon v-else class="h-5 w-5" />
                            </Button>
                        </NavigationMenuItem>
                    </NavigationMenuList>
                </NavigationMenu>
            </nav>

            <!-- Mobile nav -->
            <div class="md:hidden">
                <Sheet v-model:open="open">
                    <SheetTrigger as-child>
                        <Button variant="ghost" size="icon" aria-label="Menu">
                            <Menu class="h-5 w-5" />
                        </Button>
                    </SheetTrigger>
                    <SheetContent side="right" class="w-[300px]">
                        <SheetHeader class="mb-2">
                            <SheetTitle class="flex items-center gap-2">
                                <AppLogo class="h-6 w-auto" />
                            </SheetTitle>
                            <SheetDescription class="sr-only">Navigatiemenu</SheetDescription>
                        </SheetHeader>

                        <!-- Links -->
                        <div class="space-y-1">
                            <Link
                                v-for="l in nav.links"
                                :key="`m-${l.href}`"
                                :href="l.href"
                                class="block rounded-md px-3 py-2 text-sm font-medium hover:bg-muted"
                                :class="isActive(l.href) ? 'bg-muted' : ''"
                                @click="open = false"
                            >
                                {{ l.label }}
                            </Link>
                        </div>

                        <!-- Groepen -->
                        <div v-for="g in nav.groups" :key="`mg-${g.label}`" class="mt-4">
                            <p class="px-3 pb-2 text-xs font-semibold uppercase text-muted-foreground">{{ g.label }}</p>
                            <div class="space-y-1">
                                <Link
                                    v-for="it in g.items"
                                    :key="`mgi-${it.href}`"
                                    :href="it.href"
                                    class="block rounded-md px-3 py-2 text-sm hover:bg-muted"
                                    :class="isActive(it.href) ? 'bg-muted' : ''"
                                    @click="open = false"
                                >
                                    {{ it.label }}
                                </Link>
                            </div>
                        </div>

                        <Separator class="my-4" />

                        <!-- CTA -->
                        <div v-if="nav.cta?.href" class="px-3 mb-3">
                            <Link :href="nav.cta.href" @click="open = false">
                                <Button class="w-full">{{ nav.cta.label }}</Button>
                            </Link>
                        </div>

                        <!-- Dark mode in sidebar -->
                        <!-- Dark mode in sidebar -->
                        <div class="mt-2 flex items-center justify-between rounded-md border px-3 py-2">
                            <div class="flex items-center gap-2">
                                <span class="text-sm">Scherm weergave</span>
                            </div>
                            <!-- BUTTON ipv Switch -->
                            <Button
                                variant="ghost"
                                size="icon"
                                :aria-pressed="isDark"
                                :aria-label="isDark ? 'Schakel licht modus in' : 'Schakel donkere modus in'"
                                @click="toggleDark()"
                            >
                                <Sun v-if="isDark" class="h-5 w-5" />
                                <Moon v-else class="h-5 w-5" />
                            </Button>
                        </div>
                    </SheetContent>
                </Sheet>
            </div>
        </div>
    </header>
    <CookieConsent />
</template>
