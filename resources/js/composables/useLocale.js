import { computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'

export function useLocale() {
  const page = usePage()

  const locale = computed(() => page.props.locale ?? 'ms')
  const translations = computed(() => page.props.translations ?? {})

  function t(key, params = {}) {
    let str = translations.value[key] ?? key
    for (const [k, v] of Object.entries(params)) {
      str = str.replaceAll(`:${k}`, v)
    }
    return str
  }

  function switchLocale() {
    const next = locale.value === 'ms' ? 'en' : 'ms'
    router.post('/locale', { locale: next }, { preserveScroll: true })
  }

  return { t, locale, switchLocale }
}
