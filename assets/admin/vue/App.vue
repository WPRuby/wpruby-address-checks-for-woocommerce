<template>
  <div class="agl-shell">
    <AppHeader
      :dirty="isDirty"
      :saving="state.savingSettings"
      @save="saveSettings"
    />

    <AppTabs :active-tab="activeTab" @change="setActiveTab" />

    <div class="agl-shell__body">
      <div v-if="state.notice" class="agl-shell__notice">
        <Notice :notice="state.notice" @close="clearNotice" />
      </div>

      <div v-if="!state.ready && !state.loadError" class="agl-skeleton">
        {{ loadingLabel }}
      </div>

      <div v-else-if="state.loadError" class="agl-view">
        <div class="wpruby-ag-notice wpruby-ag-notice--error">
          <span>{{ state.loadError }}</span>
        </div>
        <button type="button" class="agl-button" @click="loadSettings">
          {{ retryLabel }}
        </button>
      </div>

      <component v-else :is="currentView" />
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import AppHeader from './components/AppHeader.vue';
import AppTabs from './components/AppTabs.vue';
import Notice from './components/Notice.vue';
import General from './views/General.vue';
import Checks from './views/Checks.vue';
import Messages from './views/Messages.vue';
import Upgrade from './views/Upgrade.vue';
import {
  state,
  isDirty,
  loadSettings,
  saveSettings,
  clearNotice,
} from './store.js';
import { __ } from './api/client.js';

const tabs = [
  { id: 'general', view: General },
  { id: 'checks', view: Checks },
  { id: 'messages', view: Messages },
  { id: 'upgrade', view: Upgrade },
];
const tabIds = tabs.map((tab) => tab.id);
const activeTab = ref(tabFromHash());

const loadingLabel = __('Loading…');
const retryLabel = __('Try again');

const currentView = computed(() => {
  const tab = tabs.find((t) => t.id === activeTab.value);
  return tab ? tab.view : General;
});

function beforeUnload(e) {
  if (isDirty.value) {
    e.preventDefault();
    e.returnValue = '';
  }
}

function tabFromHash() {
  const hash = window.location.hash.replace(/^#/, '');
  const id = hash.split('?')[0];
  return tabIds.includes(id) ? id : 'general';
}

function setActiveTab(id) {
  activeTab.value = id;
  window.location.hash = id;
}

function onHashChange() {
  activeTab.value = tabFromHash();
}

onMounted(() => {
  loadSettings();
  window.addEventListener('beforeunload', beforeUnload);
  window.addEventListener('hashchange', onHashChange);
});

onUnmounted(() => {
  window.removeEventListener('beforeunload', beforeUnload);
  window.removeEventListener('hashchange', onHashChange);
});
</script>
