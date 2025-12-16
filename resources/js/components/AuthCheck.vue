<script setup lang="ts">
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const page = usePage()

const authInfo = computed(() => ({
    webUser: page.props. auth?. user || null,
    adminUser: page.props.auth?.admin || null,
    isLoggedIn: !!page.props. auth?. admin,
    props: page.props
}))

const checkAuthEndpoint = async () => {
    try {
        const response = await fetch('/hoofdbeheerder/dashboard', {
            credentials: 'same-origin'
        })
        console.log('Dashboard access test:', response.status, response.ok)

        if (! response.ok) {
            console.log('❌ Cannot access dashboard - not properly authenticated')
        } else {
            console.log('✅ Dashboard accessible - admin auth working')
        }
    } catch (error) {
        console.error('Auth check failed:', error)
    }
}
</script>

<template>
    <div class="bg-yellow-50 border border-yellow-200 rounded p-4 mb-4">
        <h3 class="font-bold text-yellow-800 mb-2">🔐 Auth Status Check</h3>

        <div class="text-sm space-y-1">
            <div>
                <strong>Admin User:</strong>
                <span :class="authInfo.adminUser ?  'text-green-600' : 'text-red-600'">
                    {{ authInfo. adminUser?. email || 'NOT LOGGED IN' }}
                </span>
            </div>

            <div>
                <strong>Web User:</strong>
                <span>{{ authInfo.webUser?.email || 'None' }}</span>
            </div>

            <div>
                <strong>Auth Status:</strong>
                <span :class="authInfo. isLoggedIn ?  'text-green-600' : 'text-red-600'">
                    {{ authInfo. isLoggedIn ?  '✅ Authenticated' : '❌ Not Authenticated' }}
                </span>
            </div>
        </div>

        <button @click="checkAuthEndpoint"
                class="mt-2 px-3 py-1 bg-yellow-500 text-white rounded text-sm">
            Test Dashboard Access
        </button>

        <details class="mt-2">
            <summary class="cursor-pointer text-xs text-gray-600">Show Raw Props</summary>
            <pre class="text-xs bg-gray-100 p-2 rounded mt-1 overflow-auto">{{ JSON.stringify(authInfo. props, null, 2) }}</pre>
        </details>
    </div>
</template>
