import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { createPinia } from 'pinia'
import { setupInertiaStateBridge } from '@core/Stores/setupInertiaStateBridge'

const pinia = createPinia()

createInertiaApp({
    resolve: (name) => {
        const appPages = import.meta.glob('./Pages/**/*.vue')
        const modulePages = import.meta.glob('/Modules/*/resources/js/Pages/**/*.vue')

        const parts = name.split('/')
        const moduleName = parts[0]
        const pageSubpath = parts.slice(1).join('/')

        const targetKey = `/Modules/${moduleName}/resources/js/Pages/${pageSubpath}.vue`
        if (modulePages[targetKey]) {
            return modulePages[targetKey]()
        }

        return resolvePageComponent(`./Pages/${name}.vue`, appPages)
    },
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })
        app.use(plugin)
        app.use(pinia)
        setupInertiaStateBridge(props.initialPage?.props)
        app.mount(el)
    },
    progress: {
        // Resolved from the active theme's primary-500 token at runtime.
        color: typeof getComputedStyle !== 'undefined'
            ? getComputedStyle(document.documentElement).getPropertyValue('--color-primary-500').trim() || '#6366f1'
            : '#6366f1',
    },
})
