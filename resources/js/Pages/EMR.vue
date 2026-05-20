<script setup>
import { ref } from 'vue'
import KlinikLayout from '@/Layouts/KlinikLayout.vue'
import Avatar from '@/Components/Clinic/Avatar.vue'
import Badge from '@/Components/Clinic/Badge.vue'
import Btn from '@/Components/Clinic/Btn.vue'
import Icon from '@/Components/Clinic/Icon.vue'

defineOptions({ layout: KlinikLayout })

const soapTab = ref('S')
const soapText = ref('Patient presents with sore throat for 3 days. Intermittent fever, max temperature 38.5°C last night. Mild dry cough and pain on swallowing. No vomiting or diarrhea. Appetite reduced. Currently on Amlodipine 5mg OD and Metformin 500mg BD for HTN and T2DM.')

const vitals = [
  { l: 'BP',   v: '142/89', u: 'mmHg', abnormal: true  },
  { l: 'HR',   v: '88',     u: 'bpm',  abnormal: false },
  { l: 'Temp', v: '37.4',   u: '°C',   abnormal: false },
  { l: 'SpO₂', v: '98',     u: '%',    abnormal: false },
  { l: 'Weight', v: '68',   u: 'kg',   abnormal: false },
]

const prescriptions = [
  { n: 'Amoxicillin 500mg',   d: '1 cap TDS × 5 days',    w: '⚠ Penicillin allergy on file' },
  { n: 'Paracetamol 1g',      d: '1 tab QID PRN fever',    w: '' },
  { n: 'Strepsils Lozenges',  d: '1 PRN, max 8/day',       w: '' },
]

const differential = [
  { n: 'Viral pharyngitis', p: 78 },
  { n: 'Group A Strep',     p: 14 },
  { n: 'Allergic rhinitis', p: 6  },
  { n: 'COVID-19',          p: 2  },
]
</script>

<template>
  <div class="screen" style="flex-direction:row; padding:0">
    <!-- Left: SOAP + EMR -->
    <div style="flex:1; padding:24px; display:flex; flex-direction:column; gap:14px; overflow:auto">
      <!-- Patient banner -->
      <div class="card" style="padding:16px 18px; display:flex; align-items:center; gap:14px">
        <Avatar name="Aminah Hassan" size="lg" />
        <div style="flex:1">
          <div style="display:flex; align-items:center; gap:10px">
            <div style="font:700 16px var(--font-sans); color:var(--fg1)">Aminah binti Hassan</div>
            <Badge tone="red">⚠ Penicillin allergy</Badge>
            <Badge tone="orange">HTN · T2DM</Badge>
          </div>
          <div class="mono" style="font:500 12px var(--font-mono); color:var(--fg3); margin-top:2px">780229-08-5234 · 47F · O+ · P-2026-00482</div>
        </div>
        <div style="display:flex; gap:6px">
          <div v-for="v in vitals" :key="v.l" class="vital">
            <div class="vital__label">{{ v.l }}</div>
            <div :class="['vital__val', v.abnormal ? 'abnormal' : '']">{{ v.v }}</div>
            <div class="vital__unit">{{ v.u }}</div>
          </div>
        </div>
      </div>

      <!-- SOAP Notes -->
      <div class="card">
        <div class="card__header">
          <h3 class="card__title">SOAP Notes</h3>
          <p class="card__sub">Auto-saved · 11:02 AM</p>
          <div class="spacer"></div>
          <div class="tabs" style="margin:0">
            <button
              v-for="[key, label] in [['S','Subjective'],['O','Objective'],['A','Assessment'],['P','Plan']]"
              :key="key"
              :class="['tab', soapTab === key ? 'active' : '']"
              @click="soapTab = key"
            >{{ label }}</button>
          </div>
        </div>
        <div class="card__body">
          <textarea v-model="soapText" style="width:100%; min-height:140px; border:1px solid var(--border); border-radius:8px; padding:12px; font:400 13px var(--font-sans); resize:vertical"></textarea>
        </div>
      </div>

      <!-- Diagnosis -->
      <div class="card">
        <div class="card__header"><h3 class="card__title">Diagnosis · ICD-10</h3></div>
        <div class="card__body" style="display:flex; gap:8px; flex-wrap:wrap">
          <Badge tone="green">J02.9 · Acute pharyngitis</Badge>
          <Badge tone="neutral">I10 · Essential HTN (chronic)</Badge>
          <Badge tone="neutral">E11.9 · Type 2 DM (chronic)</Badge>
          <Btn variant="ghost" size="sm">+ Add</Btn>
        </div>
      </div>

      <!-- Prescription -->
      <div class="card">
        <div class="card__header">
          <h3 class="card__title">Prescription</h3>
          <div class="spacer"></div>
          <Btn variant="ghost" size="sm">+ Add drug</Btn>
        </div>
        <div>
          <div v-for="(rx, i) in prescriptions" :key="i" class="row" style="padding:11px 18px; border-top:1px solid var(--border)">
            <div style="flex:1">
              <div style="font:600 13px var(--font-sans)">{{ rx.n }}</div>
              <div class="mono" style="font:500 11.5px var(--font-mono); color:var(--fg3)">{{ rx.d }}</div>
            </div>
            <Badge v-if="rx.w" tone="red">{{ rx.w }}</Badge>
          </div>
        </div>
      </div>
    </div>

    <!-- Right: AI sidebar -->
    <aside style="width:320px; flex-shrink:0; background:#fff; border-left:1px solid var(--border); padding:18px; overflow:auto; display:flex; flex-direction:column; gap:14px">
      <div class="ai-box">
        <Icon name="sparkle" :size="16" />
        <div>
          <div class="ai-box__title">AI Visit Summary</div>
          <div class="ai-box__body" style="margin-top:4px">47y female, hypertensive + T2DM, presents with 3-day sore throat, low-grade fever 38.5°C, no exudate. BP elevated at 142/89 (chronic). Most likely <b>viral pharyngitis</b>, self-limiting.</div>
        </div>
      </div>

      <div>
        <div style="font:700 12px var(--font-sans); letter-spacing:0.06em; text-transform:uppercase; color:var(--fg3); margin-bottom:8px">Differential</div>
        <div style="display:flex; flex-direction:column; gap:6px">
          <div v-for="d in differential" :key="d.n" class="row" style="padding:6px 0">
            <div style="flex:1; font:500 12.5px var(--font-sans); color:var(--fg2)">{{ d.n }}</div>
            <div class="mono" style="font:700 12px var(--font-mono); color:var(--fg2)">{{ d.p }}%</div>
          </div>
        </div>
      </div>

      <div>
        <div style="font:700 12px var(--font-sans); letter-spacing:0.06em; text-transform:uppercase; color:var(--fg3); margin-bottom:8px">Suggested Orders</div>
        <Btn variant="secondary" size="sm" style="width:100%; justify-content:flex-start; margin-bottom:4px">+ Throat swab + RAT</Btn>
        <Btn variant="secondary" size="sm" style="width:100%; justify-content:flex-start">+ HbA1c (overdue 4mo)</Btn>
      </div>

      <div class="hr"></div>
      <Btn variant="primary" style="width:100%; justify-content:center">
        <Icon name="check" :size="14" /> Sign &amp; Close Visit
      </Btn>
    </aside>
  </div>
</template>
