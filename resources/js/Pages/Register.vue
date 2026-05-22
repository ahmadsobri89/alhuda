<script setup>
import { ref, computed } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import { useLocale } from '@/composables/useLocale'
import KlinikLayout from '@/Layouts/KlinikLayout.vue'
import Badge from '@/Components/Clinic/Badge.vue'
import Btn from '@/Components/Clinic/Btn.vue'

defineOptions({ layout: KlinikLayout })

const props = defineProps({
  lookups: { type: Object, default: () => ({}) },
})

const { t } = useLocale()

// ─── Lookups from DB ───────────────────────────────────────────────────────
const bangsa          = computed(() => props.lookups?.bangsa          ?? [])
const jantina         = computed(() => props.lookups?.jantina         ?? [])
const kumpulanDarah   = computed(() => props.lookups?.kumpulan_darah  ?? [])
const negeri          = computed(() => props.lookups?.negeri          ?? [])
const penyakitKronik  = computed(() => props.lookups?.penyakit_kronik ?? [])

// ─── Form ──────────────────────────────────────────────────────────────────
const form = useForm({
  name:                    '',
  ic_number:               '',
  date_of_birth:           '',
  gender:                  'male',
  race:                    '',
  phone:                   '',
  email:                   '',
  address:                 '',
  postcode:                '',
  city:                    '',
  state:                   '',
  blood_type:              '',
  allergies:               '',
  conditions:              [],
  emergency_contact_name:  '',
  emergency_contact_phone: '',
  status:                  'active',
})

// IC auto-fill DOB + gender
function onIcBlur() {
  const ic = form.ic_number.replace(/\D/g, '')
  if (ic.length < 6) return
  const yy   = parseInt(ic.substring(0, 2))
  const mm   = ic.substring(2, 4)
  const dd   = ic.substring(4, 6)
  const year = yy > new Date().getFullYear() % 100 ? 1900 + yy : 2000 + yy
  if (!form.date_of_birth) form.date_of_birth = `${year}-${mm}-${dd}`
  if (ic.length >= 12) {
    const last = parseInt(ic[11])
    form.gender = last % 2 === 1 ? 'male' : 'female'
  }
}

// Condition chips
const condInput = ref('')

function addCondition(c) {
  const val = c.trim()
  if (val && !form.conditions.includes(val)) form.conditions.push(val)
  condInput.value = ''
}

function removeCondition(i) {
  form.conditions.splice(i, 1)
}

function onCondKey(e) {
  if (e.key === 'Enter' || e.key === ',') {
    e.preventDefault()
    addCondition(condInput.value)
  }
}

// Submit → patients.store (same endpoint as Patients.vue modal)
function submit() {
  form.post(route('patients.store'), {
    onSuccess: () => router.visit(route('patients')),
  })
}
</script>

<template>
  <div class="screen">

    <!-- Header -->
    <div class="reg-header">
      <div>
        <h2 class="reg-title">{{ t('reg_title') }}</h2>
        <p class="reg-sub">{{ t('reg_subtitle') }}</p>
      </div>
    </div>

    <form @submit.prevent="submit">

      <!-- ── Personal Info ───────────────────────────────────────────── -->
      <div class="card" style="margin-bottom:16px">
        <div class="card__header">
          <h3 class="card__title">{{ t('reg_section_personal') }}</h3>
        </div>
        <div class="card__body">
          <div class="reg-grid">

            <div class="field" style="grid-column:1/-1">
              <label class="field__label">{{ t('pat_lbl_name') }} <span class="req">*</span></label>
              <input v-model="form.name" class="input" :placeholder="t('pat_ph_name')" />
              <span v-if="form.errors.name" class="field__error">{{ form.errors.name }}</span>
            </div>

            <div class="field">
              <label class="field__label">{{ t('pat_lbl_ic') }} <span class="req">*</span></label>
              <input v-model="form.ic_number" class="input mono" placeholder="780229-08-5234" @blur="onIcBlur" />
              <span v-if="form.errors.ic_number" class="field__error">{{ form.errors.ic_number }}</span>
            </div>

            <div class="field">
              <label class="field__label">{{ t('pat_lbl_dob') }} <span class="req">*</span></label>
              <input v-model="form.date_of_birth" type="date" class="input" />
              <span v-if="form.errors.date_of_birth" class="field__error">{{ form.errors.date_of_birth }}</span>
            </div>

            <div class="field">
              <label class="field__label">{{ t('pat_lbl_gender') }} <span class="req">*</span></label>
              <select v-model="form.gender" class="select">
                <option v-for="j in jantina" :key="j.code" :value="j.code">{{ j.label_ms }}</option>
                <template v-if="!jantina.length">
                  <option value="male">{{ t('gender_male') }}</option>
                  <option value="female">{{ t('gender_female') }}</option>
                </template>
              </select>
            </div>

            <div class="field">
              <label class="field__label">{{ t('pat_lbl_race') }}</label>
              <select v-model="form.race" class="select">
                <option value="">{{ t('pat_select_race') }}</option>
                <option v-for="b in bangsa" :key="b.code" :value="b.code">{{ b.label_ms }}</option>
              </select>
            </div>

            <div class="field">
              <label class="field__label">{{ t('pat_lbl_blood') }}</label>
              <select v-model="form.blood_type" class="select">
                <option value="">{{ t('pat_select_blood') }}</option>
                <option v-for="bt in kumpulanDarah" :key="bt.code" :value="bt.code">{{ bt.code }}</option>
              </select>
            </div>

          </div>
        </div>
      </div>

      <!-- ── Contact & Address ──────────────────────────────────────── -->
      <div class="card" style="margin-bottom:16px">
        <div class="card__header">
          <h3 class="card__title">{{ t('reg_section_contact') }}</h3>
        </div>
        <div class="card__body">
          <div class="reg-grid">

            <div class="field">
              <label class="field__label">{{ t('pat_lbl_phone') }}</label>
              <input v-model="form.phone" class="input mono" placeholder="012-3456789" />
            </div>

            <div class="field">
              <label class="field__label">{{ t('pat_lbl_email') }}</label>
              <input v-model="form.email" type="email" class="input" placeholder="pesakit@emel.com" />
              <span v-if="form.errors.email" class="field__error">{{ form.errors.email }}</span>
            </div>

            <div class="field" style="grid-column:1/-1">
              <label class="field__label">{{ t('pat_lbl_address') }}</label>
              <input v-model="form.address" class="input" :placeholder="t('pat_ph_address')" />
            </div>

            <div class="field">
              <label class="field__label">{{ t('pat_lbl_postcode') }}</label>
              <input v-model="form.postcode" class="input" placeholder="43000" maxlength="10" />
            </div>

            <div class="field">
              <label class="field__label">{{ t('pat_lbl_city') }}</label>
              <input v-model="form.city" class="input" placeholder="Kajang" />
            </div>

            <div class="field">
              <label class="field__label">{{ t('pat_lbl_state') }}</label>
              <select v-model="form.state" class="select">
                <option value="">{{ t('pat_select_state') }}</option>
                <option v-for="s in negeri" :key="s.code" :value="s.code">{{ s.code }}</option>
              </select>
            </div>

          </div>
        </div>
      </div>

      <!-- ── Medical Info ───────────────────────────────────────────── -->
      <div class="card" style="margin-bottom:16px">
        <div class="card__header">
          <h3 class="card__title">{{ t('reg_section_medical') }}</h3>
        </div>
        <div class="card__body">
          <div class="reg-grid">

            <div class="field" style="grid-column:1/-1">
              <label class="field__label">{{ t('pat_lbl_allergy') }}</label>
              <input v-model="form.allergies" class="input" :placeholder="t('pat_ph_allergy')" />
            </div>

            <div class="field" style="grid-column:1/-1">
              <label class="field__label">{{ t('pat_lbl_conditions') }}</label>
              <div class="tag-input-wrap">
                <span v-for="(c, i) in form.conditions" :key="i" class="cond-tag">
                  {{ c }} <button type="button" @click="removeCondition(i)">×</button>
                </span>
                <input
                  v-model="condInput"
                  class="tag-input"
                  placeholder="Taip &amp; tekan Enter…"
                  @keydown="onCondKey"
                  @blur="condInput && addCondition(condInput)"
                />
              </div>
              <div class="cond-suggestions">
                <button
                  v-for="c in penyakitKronik" :key="c.code"
                  type="button"
                  :class="['cond-chip', form.conditions.includes(c.code) ? 'selected' : '']"
                  @click="form.conditions.includes(c.code) ? removeCondition(form.conditions.indexOf(c.code)) : addCondition(c.code)"
                >{{ c.code }}</button>
              </div>
            </div>

          </div>
        </div>
      </div>

      <!-- ── Emergency Contact ──────────────────────────────────────── -->
      <div class="card" style="margin-bottom:24px">
        <div class="card__header">
          <h3 class="card__title">{{ t('reg_section_emg') }}</h3>
        </div>
        <div class="card__body">
          <div class="reg-grid">

            <div class="field" style="grid-column:1/3">
              <label class="field__label">{{ t('pat_lbl_emg_name') }}</label>
              <input v-model="form.emergency_contact_name" class="input" :placeholder="t('pat_ph_emg_name')" />
            </div>

            <div class="field">
              <label class="field__label">{{ t('pat_lbl_emg_phone') }}</label>
              <input v-model="form.emergency_contact_phone" class="input mono" placeholder="011-1234567" />
            </div>

          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="row" style="justify-content:flex-end;gap:10px">
        <Btn type="button" variant="secondary" @click="router.visit(route('patients'))">
          {{ t('btn_cancel') }}
        </Btn>
        <Btn type="submit" variant="primary" :disabled="form.processing">
          {{ form.processing ? t('reg_registering') : t('reg_register_btn') }}
        </Btn>
      </div>

    </form>
  </div>
</template>

<style scoped>
.reg-header {
  display: flex;
  align-items: flex-start;
  gap: 16px;
  margin-bottom: 20px;
}

.reg-title {
  font: 700 18px var(--font-sans);
  color: var(--fg1);
  margin: 0 0 4px;
}

.reg-sub {
  font: 400 13px var(--font-sans);
  color: var(--fg3);
  margin: 0;
}

.reg-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 14px;
}

.field__error {
  font: 500 11px var(--font-sans);
  color: var(--brand-red);
  margin-top: 2px;
}

/* Tag input */
.tag-input-wrap {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  padding: 6px 10px;
  border: 1px solid var(--border);
  border-radius: 8px;
  min-height: 38px;
  background: #fff;
  cursor: text;
}

.cond-tag {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  background: var(--brand-green-light);
  color: var(--brand-green-dark);
  border: 1px solid var(--brand-green);
  border-radius: 5px;
  font: 600 11px var(--font-mono);
  padding: 2px 7px;
}

.cond-tag button {
  background: none;
  border: none;
  cursor: pointer;
  color: var(--brand-green-dark);
  padding: 0;
  font-size: 13px;
  line-height: 1;
}

.tag-input {
  border: none;
  outline: none;
  font: 500 13px var(--font-sans);
  min-width: 140px;
  flex: 1;
  background: transparent;
}

.cond-suggestions {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-top: 8px;
}

.cond-chip {
  padding: 3px 10px;
  border-radius: 20px;
  border: 1px solid var(--border);
  background: var(--bg-soft);
  font: 500 11.5px var(--font-mono);
  color: var(--fg2);
  cursor: pointer;
  transition: all .12s;
}

.cond-chip:hover {
  border-color: var(--brand-green);
  color: var(--brand-green-dark);
}

.cond-chip.selected {
  background: var(--brand-green-light);
  border-color: var(--brand-green);
  color: var(--brand-green-dark);
  font-weight: 700;
}

.req { color: var(--brand-red); }
</style>
