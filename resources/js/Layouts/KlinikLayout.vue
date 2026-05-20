<script setup>
import { computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import Icon from '@/Components/Clinic/Icon.vue'
import Avatar from '@/Components/Clinic/Avatar.vue'

const page = usePage()
const currentRoute = computed(() => page.props.currentRoute || 'dashboard')

const navItems = [
  { id: 'dashboard',     icon: 'home',     label: 'Dashboard' },
  { id: 'queue',         icon: 'queue',    label: 'Queue',              count: 12 },
  { id: 'register',      icon: 'plus',     label: 'Register Patient', routeName: 'register-patient' },
  { id: 'patients',      icon: 'users',    label: 'Patients' },
  { id: 'appointments',  icon: 'calendar', label: 'Appointments' },
  { id: 'emr',           icon: 'emr',      label: 'EMR / Consultation', badge: '3' },
  { id: 'pharmacy',      icon: 'pill',     label: 'Pharmacy' },
  { id: 'inventory',     icon: 'flask',    label: 'Inventory' },
  { id: 'billing',       icon: 'invoice',  label: 'Billing' },
  { id: 'reports',       icon: 'chart',    label: 'Reports' },
  { id: 'settings',      icon: 'settings', label: 'Settings' },
]

const titles = {
  dashboard:    ['Dashboard',                       'Tuesday, 14 May 2026 · Dr. Aiman Rashid'],
  queue:        ['Patient Queue',                   '12 waiting · 2 urgent · avg wait 14 min'],
  register:     ['Register New Patient',            'MyKad-driven smart intake'],
  emr:          ['Consultation · Aminah binti Hassan', 'Session #2026-00482 · Started 10:42 AM'],
  patients:     ['Registered Patients',             '1,247 active patients'],
  appointments: ['Appointments',                    'Week of 12–17 May 2026'],
  pharmacy:     ['Pharmacy',                        '4 pending prescriptions · AI drug check active'],
  inventory:    ['Drug Inventory',                  '284 SKUs · 12 low-stock · 4 expiring within 90d'],
  billing:      ['Billing & Invoice',               'Invoice INV-2026-001847'],
  reports:      ['Reports & Analytics',             'Executive dashboard · May 2026'],
  settings:     ['Settings & Security',             'RBAC · MFA · Audit · Backup'],
}

const title = computed(() => titles[currentRoute.value]?.[0] || '')
const subtitle = computed(() => titles[currentRoute.value]?.[1] || '')

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
          <input placeholder="Search patient, IC, or EMR code…" />
        </div>
        <button class="topbar__bell"><Icon name="bell" :size="17" /></button>
      </div>

      <!-- Page content -->
      <slot />
    </div>
  </div>
</template>
