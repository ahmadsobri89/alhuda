<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import Icon from '@/Components/Clinic/Icon.vue'
import { useLocale } from '@/composables/useLocale'

const page = usePage()
const { t, locale } = useLocale()

const user = computed(() => page.props.auth?.user ?? null)
const open = ref(false)
const loading = ref(false)
const items = ref([])
const unreadCount = ref(page.props.notifications?.unreadCount ?? 0)

watch(() => page.props.notifications?.unreadCount, (v) => {
  unreadCount.value = v ?? 0
})

const root = ref(null)

const moduleIcon = { pharmacy: 'pill', billing: 'invoice', finance: 'chart' }
function iconFor(item) {
  return moduleIcon[item.data?.module] || 'bell'
}

function formatTime(iso) {
  return new Date(iso).toLocaleString(locale.value === 'ms' ? 'ms-MY' : 'en-MY', {
    day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit',
  })
}

async function toggle() {
  open.value = !open.value
  if (open.value) await fetchNotifications()
}

async function fetchNotifications() {
  loading.value = true
  try {
    const { data } = await axios.get(route('notifications'))
    items.value = data.notifications
    unreadCount.value = data.unreadCount
  } finally {
    loading.value = false
  }
}

async function openItem(item) {
  if (!item.read_at) {
    item.read_at = new Date().toISOString()
    unreadCount.value = Math.max(0, unreadCount.value - 1)
    await axios.patch(route('notifications.read', item.id))
  }
  open.value = false
  if (item.data?.url) router.visit(item.data.url)
}

async function markAllRead() {
  if (unreadCount.value === 0) return
  items.value.forEach(i => { i.read_at = i.read_at ?? new Date().toISOString() })
  unreadCount.value = 0
  await axios.patch(route('notifications.readAll'))
}

function onNewNotification(payload) {
  items.value.unshift({
    id: payload.id,
    data: { module: payload.module, title: payload.title, message: payload.message, url: payload.url, meta: payload.meta },
    read_at: null,
    created_at: new Date().toISOString(),
  })
  unreadCount.value += 1
}

function onClickOutside(e) {
  if (open.value && root.value && !root.value.contains(e.target)) open.value = false
}

let channel = null

onMounted(() => {
  document.addEventListener('mousedown', onClickOutside)
  if (user.value && window.Echo) {
    channel = window.Echo.private(`App.Models.User.${user.value.id}`)
    channel.notification(onNewNotification)
  }
})

onBeforeUnmount(() => {
  document.removeEventListener('mousedown', onClickOutside)
  if (user.value && window.Echo) window.Echo.leave(`App.Models.User.${user.value.id}`)
})
</script>

<template>
  <div class="notif-wrap" ref="root">
    <button class="topbar__bell" @click="toggle">
      <Icon name="bell" :size="17" />
      <span v-if="unreadCount > 0" class="notif-count">{{ unreadCount > 99 ? '99+' : unreadCount }}</span>
    </button>

    <div v-if="open" class="notif-panel">
      <div class="notif-panel__header">
        <span>{{ t('notif_title') }}</span>
        <button v-if="unreadCount > 0" class="notif-mark-all" @click="markAllRead">
          <Icon name="check" :size="12" />{{ t('notif_mark_all_read') }}
        </button>
      </div>
      <div class="notif-panel__list">
        <div v-if="loading" class="notif-empty">…</div>
        <div v-else-if="items.length === 0" class="notif-empty">{{ t('notif_empty') }}</div>
        <button
          v-for="item in items"
          :key="item.id"
          class="notif-item"
          :class="{ 'notif-item--unread': !item.read_at }"
          @click="openItem(item)"
        >
          <span class="notif-item__icon"><Icon :name="iconFor(item)" :size="15" /></span>
          <span class="notif-item__body">
            <span class="notif-item__title">{{ item.data.title }}</span>
            <span class="notif-item__message">{{ item.data.message }}</span>
            <span class="notif-item__time">{{ formatTime(item.created_at) }}</span>
          </span>
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.notif-wrap { position: relative; }

.notif-count {
  position: absolute; top: -4px; right: -4px;
  min-width: 16px; height: 16px; padding: 0 4px;
  border-radius: 999px; background: var(--brand-red); color: #fff;
  font: 700 10px var(--font-sans); display: grid; place-items: center;
  line-height: 1;
}

.notif-panel {
  position: absolute; top: calc(100% + 8px); right: 0; width: 340px;
  background: #fff; border: 1px solid var(--border); border-radius: 12px;
  box-shadow: var(--shadow-md); z-index: 40; overflow: hidden;
}

.notif-panel__header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 10px 14px; border-bottom: 1px solid var(--border);
  font: 700 13px var(--font-sans); color: var(--fg1);
}

.notif-mark-all {
  display: flex; align-items: center; gap: 4px; border: 0; background: transparent;
  color: var(--brand-green); font: 600 11.5px var(--font-sans); cursor: pointer; padding: 4px 6px;
  border-radius: 6px;
}
.notif-mark-all:hover { background: var(--brand-green-light); }

.notif-panel__list { max-height: 360px; overflow-y: auto; }

.notif-empty {
  padding: 24px 14px; text-align: center; color: var(--fg3);
  font: 500 12.5px var(--font-sans);
}

.notif-item {
  display: flex; gap: 10px; width: 100%; text-align: left; padding: 10px 14px;
  border: 0; border-bottom: 1px solid var(--border); background: #fff; cursor: pointer;
}
.notif-item:last-child { border-bottom: 0; }
.notif-item:hover { background: var(--bg-soft); }
.notif-item--unread { background: var(--brand-green-light); }
.notif-item--unread:hover { background: var(--brand-green-light); filter: brightness(0.98); }

.notif-item__icon {
  flex-shrink: 0; width: 28px; height: 28px; border-radius: 8px;
  background: var(--bg-muted); color: var(--fg2); display: grid; place-items: center;
}

.notif-item__body { display: flex; flex-direction: column; gap: 2px; min-width: 0; }
.notif-item__title { font: 700 12.5px var(--font-sans); color: var(--fg1); }
.notif-item__message { font: 500 12px var(--font-sans); color: var(--fg2); }
.notif-item__time { font: 500 10.5px var(--font-mono); color: var(--fg3); }
</style>
