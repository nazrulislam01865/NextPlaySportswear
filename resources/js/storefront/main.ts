import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from './App.vue';
import './styles/index.css';

const element = document.getElementById('nextplay-storefront');

if (element) {
  createApp(App)
    .use(createPinia())
    .mount(element);
}
