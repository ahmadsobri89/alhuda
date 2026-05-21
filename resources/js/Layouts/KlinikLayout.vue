<script setup>
import { computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import Icon from '@/Components/Clinic/Icon.vue'
import Avatar from '@/Components/Clinic/Avatar.vue'
import { useLocale } from '@/composables/useLocale'

const page = usePage()
const currentRoute = computed(() => page.props.currentRoute || 'dashboard')
const { t, locale, switchLocale } = useLocale()

const navItems = computed(() => [
  { id: 'dashboard',     icon: 'home',     label: t('nav_dashboard') },
  { id: 'queue',         icon: 'queue',    label: t('nav_queue'),         count: 12 },
  { id: 'register',      icon: 'plus',     label: t('nav_register'),      routeName: 'register-patient' },
  { id: 'patients',      icon: 'users',    label: t('nav_patients') },
  { id: 'appointments',  icon: 'calendar', label: t('nav_appointments') },
  { id: 'emr',           icon: 'emr',      label: t('nav_emr'),           badge: '3' },
  { id: 'pharmacy',      icon: 'pill',     label: t('nav_pharmacy') },
  { id: 'inventory',     icon: 'flask',    label: t('nav_inventory') },
  { id: 'billing',       icon: 'invoice',  label: t('nav_billing') },
  { id: 'reports',       icon: 'chart',    label: t('nav_reports') },
  { id: 'settings',      icon: 'settings', label: t('nav_settings') },
])

const title    = computed(() => t(`page_title_${currentRoute.value}`))
const subtitle = computed(() => page.props.pageSubtitle ?? '')

function navigate(item) {
  router.visit(route(item.routeName || item.id))
}
</script>

<template>
  <div id="app">
    <!-- Sidebar -->
    <aside class="sidebar">
      <div class="sidebar__brand">
        <img src="/logo.png" alt="Al-Huda" />
        <div class="sidebar__brand-name">Poliklinik<b>Al-Huda</b></div>
      </div>
      <nav class="sidebar__nav">
        <button
          v-for="item in navItems"
          :key="item.id"
          :class="['sidebar__item', currentRoute === item.id ? 'active' : '']"
          @click="navigate(item)"
        >
          <Icon :name="item.icon" :size="17" />
          <span>{{ item.label }}</span>
          <span v-if="item.count" class="count">{{ item.count }}</span>
          <span v-if="item.badge" class="badge-dot">{{ item.badge }}</span>
        </button>
      </nav>
      <div class="sidebar__user">
        <Avatar name="Aiman Rashid" />
        <div style="flex:1;min-width:0">
          <div style="font:600 12.5px var(--font-sans); color: var(--fg1)">Dr. Aiman Rashid</div>
          <div style="font:500 11px var(--font-mono); color: var(--fg3)">MMC-87231 · Doctor</div>
        </div>
        <button class="logout-btn" :title="t('layout_logout')" @click="router.post('/logout')">
          <Icon name="logout" :size="16" />
        </button>
      </div>
    </aside>

    <!-- Main -->
    <div class="main">
      <!-- TopBar -->
      <div class="topbar">
        <div class="topbar__title">
          <h1>{{ title }}</h1>
          <p v-if="subtitle">{{ subtitle }}</p>
        </div>
        <div class="topbar__search">
          <Icon name="search" :size="15" />
          <input :placeholder="t('layout_search_placeholder')" />
        </div>
        <button class="lang-btn" @click="switchLocale" :title="locale === 'ms' ? 'Switch to English' : 'Tukar ke Bahasa Malaysia'">
          {{ locale === 'ms' ? 'EN' : 'BM' }}
        </button>
        <button class="topbar__bell"><Icon name="bell" :size="17" /></button>
      </div>

      <!-- Page content -->
      <slot />
    </div>
  </div>
</template>
