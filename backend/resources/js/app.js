import './bootstrap';
import '../css/app.css';
import '../css/style.css';
import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue, route as ziggyRoute } from '../../vendor/tightenco/ziggy';
import { VueReCaptcha } from 'vue-recaptcha-v3';
import { Ziggy } from './ziggy.js';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';
window.route = (name, params, absolute, config = Ziggy) => ziggyRoute(name, params, absolute, config);

createInertiaApp({
  title: (title) => `${title} - ${appName}`,
  resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
  setup({ el, App, props, plugin }) {
    return createApp({ render: () => h(App, props) })
      .use(plugin)
      .use(ZiggyVue, { ...Ziggy, url: import.meta.env.VITE_APP_URL || 'window.location.origin' })
      .use(VueReCaptcha, {
        siteKey: props.initialPage.props.recaptcha_site_key,
        loaderOptions: { useEnterprise: true },
        container: {
          parameters: {
            badge: 'inline',
          }
        },
        scriptProps: {
          async: false,
          defer: false,
          appendTo: 'head',
          nonce: undefined,
        }
      })
      .mount(el);
  },
  progress: {
    color: '#4B5563',
    showSpinner: true,
  },
});
