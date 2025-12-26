<script setup lang="ts">
import { ref } from 'vue'
import { useForm, Head } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Checkbox } from '@/components/ui/checkbox'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { adminRoutes } from '@/lib/routes'
import { Eye, EyeOff } from 'lucide-vue-next'

const showPassword = ref(false)

const form = useForm({
    email: '',
    password: '',
    remember: false,
})

const submit = () => {
    form.post('/hoofdbeheerder/login', {
        onFinish: () => {
            form.reset('password')
        },
    })
}
</script>

<template>
    <div>
        <Head title="Hoofdbeheerder Login" />

        <div class="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900 px-4">
            <Card class="w-full max-w-md">
                <CardHeader class="space-y-1">
                    <CardTitle class="text-2xl font-bold text-center">Hoofdbeheerder Login</CardTitle>
                    <CardDescription class="text-center">
                        Voer uw gegevens in om toegang te krijgen tot het dashboard
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-4">
                        <div class="space-y-2">
                            <Label for="email">E-mailadres</Label>
                            <Input
                                id="email"
                                v-model="form.email"
                                type="email"
                                placeholder="admin@duidelijkheid.com"
                                required
                                autocomplete="email"
                                :class="{ 'border-red-500': form.errors.email }"
                            />
                            <p v-if="form.errors.email" class="text-sm text-red-500">
                                {{ form.errors.email }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="password">Wachtwoord</Label>
                            <div class="relative">
                                <Input
                                    id="password"
                                    v-model="form.password"
                                    :type="showPassword ? 'text' : 'password'"
                                    required
                                    autocomplete="current-password"
                                    class="pr-10"
                                    :class="{ 'border-red-500': form.errors.password }"
                                />
                                <button
                                    type="button"
                                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                                    @click="showPassword = !showPassword"
                                >
                                    <EyeOff v-if="showPassword" class="h-5 w-5" />
                                    <Eye v-else class="h-5 w-5" />
                                </button>
                            </div>
                            <p v-if="form.errors.password" class="text-sm text-red-500">
                                {{ form.errors.password }}
                            </p>
                        </div>

                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <Checkbox
                                    id="remember"
                                    v-model:checked="form.remember"
                                />
                                <Label for="remember" class="text-sm font-normal cursor-pointer">
                                    Onthoud mij
                                </Label>
                            </div>
                            
                            <a :href="adminRoutes.forgotPassword" class="text-sm text-primary hover:underline">
                                Wachtwoord vergeten?
                            </a>
                        </div>

                        <Button
                            type="submit"
                            class="w-full"
                            :disabled="form.processing"
                        >
                            <span v-if="form.processing">Bezig met inloggen...</span>
                            <span v-else>Inloggen</span>
                        </Button>
                    </form>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
