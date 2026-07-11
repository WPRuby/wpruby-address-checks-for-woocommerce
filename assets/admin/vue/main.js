import { createApp } from 'vue';
import App from './App.vue';
import './styles.css';

const mountEl = document.getElementById('address-guard-admin');

if (mountEl) {
  mountEl.innerHTML = '';
  createApp(App).mount(mountEl);
}
