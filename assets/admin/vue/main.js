import { createApp } from 'vue';
import App from './App.vue';
import './styles.css';

const mountEl = document.getElementById('wpruby-address-checks-admin');

if (mountEl) {
  mountEl.innerHTML = '';
  createApp(App).mount(mountEl);
}
