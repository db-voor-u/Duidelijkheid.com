import { onMounted, onUnmounted, ref, watch } from 'vue'

export function useCodeCopy(containerRef: any) {
    let observer: MutationObserver | null = null

    function processPreTags() {
        if (!containerRef.value) {
            return
        }

        // 1. Find all PRE tags
        const preTags = containerRef.value.querySelectorAll('pre')

        preTags.forEach((pre: HTMLPreElement) => {
            // Check if already processed (check via attribute)
            if (pre.getAttribute('data-has-copy-button') === 'true') return

            // 2. Wrap PRE in a relative container
            // We use a wrapper to ensure the absolute copy button is positioned relative to THIS block
            // independent of the PRE's own overflow/scrolling.
            const wrapper = document.createElement('div')
            // Inline styles for wrapper to ensure relative positioning works
            wrapper.style.position = 'relative'
            wrapper.style.marginBottom = '1.5rem' // Match prose spacing roughly
            wrapper.style.borderRadius = '0.5rem'
            wrapper.style.overflow = 'hidden' // Ensure rounded corners clip content

            // Insert wrapper
            if (pre.parentNode) {
                pre.parentNode.insertBefore(wrapper, pre)
            }
            wrapper.appendChild(pre)

            // Reset PRE margins to avoid double spacing since wrapper handles it
            pre.style.marginTop = '0'
            pre.style.marginBottom = '0'

            // 3. Create the Copy Button
            const button = document.createElement('button')
            button.setAttribute('aria-label', 'Kopieer code')

            // CRITICAL: Use inline styles to bypass any Tailwind purging/scanning issues
            Object.assign(button.style, {
                position: 'absolute',
                top: '0.5rem',
                right: '0.5rem',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                width: '2rem',
                height: '2rem',
                padding: '0.25rem',
                borderRadius: '0.375rem',
                backgroundColor: 'rgba(63, 63, 70, 0.8)', // zinc-700 with opacity
                border: '1px solid rgba(113, 113, 122, 0.5)', // zinc-500
                color: '#fff',
                cursor: 'pointer',
                zIndex: '30', // Less than navbar (z-40)
                transition: 'all 0.2s ease',
                backdropFilter: 'blur(4px)'
            })

            // Hover effects via JS since we are using inline styles
            button.onmouseenter = () => { button.style.backgroundColor = 'rgba(82, 82, 91, 1)' } // zinc-600
            button.onmouseleave = () => { button.style.backgroundColor = 'rgba(63, 63, 70, 0.8)' }

            // Icon SVG
            const copyIcon = `
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="pointer-events: none;">
                    <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                </svg>
            `
            const checkIcon = `
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#34d399" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="pointer-events: none;">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
            `

            button.innerHTML = copyIcon

            // Click Handler
            button.addEventListener('click', async () => {
                const code = pre.innerText
                try {
                    await navigator.clipboard.writeText(code)
                    button.innerHTML = checkIcon
                    button.style.borderColor = '#10b981' // emerald-500

                    setTimeout(() => {
                        button.innerHTML = copyIcon
                        button.style.borderColor = 'rgba(113, 113, 122, 0.5)'
                    }, 2000)
                } catch (err) {
                    console.error('Failed to copy', err)
                }
            })

            wrapper.appendChild(button)

            // Mark as processed
            pre.setAttribute('data-has-copy-button', 'true')
        })
    }

    onMounted(() => {
        // Run immediately
        processPreTags()

        // Set up Observer to watch for changes (e.g. v-html updates)
        if (containerRef.value) {
            observer = new MutationObserver(() => {
                processPreTags()
            })
            observer.observe(containerRef.value, { childList: true, subtree: true })
        }
    })

    onUnmounted(() => {
        if (observer) observer.disconnect()
    })

    return {}
}
