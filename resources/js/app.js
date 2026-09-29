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
        const relativeModulePages = import.meta.glob('../../Modules/*/resources/js/Pages/**/*.vue')

        const parts = name.split('/')
        const moduleName = parts[0]
        const pageSubpath = parts.slice(1).join('/')

        const targetKey1 = `/Modules/${moduleName}/resources/js/Pages/${pageSubpath}.vue`
        if (modulePages[targetKey1]) {
            return modulePages[targetKey1]()
        }

        const targetKey2 = `../../Modules/${moduleName}/resources/js/Pages/${pageSubpath}.vue`
        if (relativeModulePages[targetKey2]) {
            return relativeModulePages[targetKey2]()
        }

        return resolvePageComponent(`./Pages/${name}.vue`, appPages)
    },
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })
        app.use(plugin)
        app.use(pinia)
        setupInertiaStateBridge()
        app.mount(el)
    },
    progress: {
        color: '#6366f1',
    },
})
