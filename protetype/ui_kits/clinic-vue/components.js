// Klinik Al-Huda — Vue 3 components & screens (single-file module)
(function() {

// ---------- Icons ----------
const Icon = {
  props: ['name', 'size'],
  template: `<svg :width="size||16" :height="size||16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><g v-html="path"></g></svg>`,
  computed: {
    path() {
      const p = {
        home: '<path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 22V12h6v10"/>',
        users: '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
        plus: '<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>',
        calendar: '<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>',
        emr: '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8"/>',
        flask: '<path d="M9 2v6L4 20a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2L15 8V2"/><line x1="9" y1="2" x2="15" y2="2"/>',
        pill: '<path d="m10.5 20.5-7-7a4.95 4.95 0 0 1 7-7l7 7a4.95 4.95 0 0 1-7 7Z"/><line x1="8.5" y1="8.5" x2="15.5" y2="15.5"/>',
        invoice: '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h6"/>',
        chart: '<path d="M3 3v18h18"/><path d="m7 14 4-4 4 4 6-6"/>',
        settings: '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>',
        search: '<circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/>',
        bell: '<path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>',
        sparkle: '<path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>',
        check: '<polyline points="20 6 9 17 4 12"/>',
        alert: '<path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
        arrow: '<line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>',
        clock: '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
        queue: '<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>',
      };
      return p[this.name] || '';
    }
  }
};

const Avatar = {
  props: ['name', 'size'],
  computed: {
    initials() { return (this.name||'').split(' ').filter(Boolean).slice(0,2).map(s=>s[0]).join('').toUpperCase(); },
    cls() { return ['avatar', this.size==='lg'?'lg':this.size==='sm'?'sm':''].join(' '); }
  },
  template: `<div :class="cls">{{ initials }}</div>`
};

const Badge = {
  props: ['tone'],
  template: `<span :class="['badge', 'badge--' + (tone||'neutral')]"><slot /></span>`
};

const Btn = {
  props: ['variant', 'size'],
  template: `<button :class="['btn', 'btn--' + (variant||'secondary'), size==='sm'?'btn--sm':'']"><slot /></button>`
};

const TriagePill = {
  props: ['level'],
  computed: {
    label() { return ['','Emergency','Urgent','Semi','Standard','Non-urgent'][this.level]; }
  },
  template: `<span :class="['triage-pill', 'triage-' + level]">L{{ level }} · {{ label }}</span>`
};

// ---------- Sidebar ----------
const Sidebar = {
  components: { Icon, Avatar },
  props: ['active'],
  emits: ['nav'],
  data: () => ({
    items: [
      { id: 'dashboard', icon: 'home', label: 'Dashboard' },
      { id: 'queue', icon: 'queue', label: 'Queue', count: 12 },
      { id: 'register', icon: 'plus', label: 'Register Patient' },
      { id: 'patients', icon: 'users', label: 'Patients' },
      { id: 'appointments', icon: 'calendar', label: 'Appointments' },
      { id: 'emr', icon: 'emr', label: 'EMR / Consultation', badge: '3' },
      { id: 'pharmacy', icon: 'pill', label: 'Pharmacy' },
      { id: 'inventory', icon: 'flask', label: 'Inventory' },
      { id: 'billing', icon: 'invoice', label: 'Billing' },
      { id: 'reports', icon: 'chart', label: 'Reports' },
      { id: 'settings', icon: 'settings', label: 'Settings' },
    ]
  }),
  template: `
    <aside class="sidebar">
      <div class="sidebar__brand">
        <img src="logo.png" alt="" />
        <div class="sidebar__brand-name">Poliklinik<b>Al-Huda</b></div>
      </div>
      <nav class="sidebar__nav">
        <button v-for="it in items" :key="it.id" :class="['sidebar__item', active===it.id?'active':'']" @click="$emit('nav', it.id)">
          <Icon :name="it.icon" :size="17" />
          <span>{{ it.label }}</span>
          <span v-if="it.count" class="count">{{ it.count }}</span>
          <span v-if="it.badge" class="badge-dot">{{ it.badge }}</span>
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
  `
};

const TopBar = {
  components: { Icon },
  props: ['title', 'subtitle'],
  template: `
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
  `
};

window.KlinikComponents = { Icon, Avatar, Badge, Btn, TriagePill, Sidebar, TopBar };
})();
