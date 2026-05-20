<script setup>
import { computed } from 'vue'
import KlinikLayout from '@/Layouts/KlinikLayout.vue'
import Btn from '@/Components/Clinic/Btn.vue'

defineOptions({ layout: KlinikLayout })

const kpis = [
  { l:'Revenue MTD',     v:'RM 142,820', d:'+18%',   sp:[12,18,22,16,28,32,28,34,38,42], c:'var(--brand-green)'  },
  { l:'Patients Seen',   v:'1,847',      d:'+9%',    sp:[42,48,52,46,58,62,58,64,68,72], c:'#2563EB'             },
  { l:'Avg Wait Time',   v:'14 min',     d:'−3 min', sp:[22,20,18,17,16,15,16,15,14,14], c:'var(--brand-orange)' },
  { l:'Satisfaction',    v:'4.7 / 5',    d:'+0.2',   sp:[4.2,4.3,4.4,4.5,4.4,4.6,4.6,4.7,4.7,4.7], c:'var(--brand-yellow)' },
]

const bars = [
  { l:'Cash',           v:48200, c:'var(--brand-green)'  },
  { l:'Panel Claims',   v:42800, c:'#2563EB'             },
  { l:'DuitNow / QR',  v:24600, c:'var(--brand-yellow)' },
  { l:'Card',           v:18420, c:'var(--brand-orange)' },
  { l:'e-Wallet',       v:8800,  c:'#94A3B8'             },
]

const icd = [
  { l:'J06.9 · URTI',          v:184 },
  { l:'I10 · Hypertension',    v:142 },
  { l:'E11.9 · T2DM',          v:118 },
  { l:'J02.9 · Pharyngitis',   v:96  },
  { l:'K30 · Dyspepsia',       v:78  },
]

function sparklinePoints(data, h = 40) {
  const max = Math.max(...data), min = Math.min(...data)
  return data.map((v, i) =>
    `${(i / (data.length - 1)) * 120},${h - ((v - min) / (max - min || 1)) * (h - 4) - 2}`
  ).join(' ')
}
</script>

<template>
  <div class="screen">
    <div class="row">
      <select class="select" style="max-width:200px">
        <option>This month · May 2026</option>
        <option>Last 30 days</option>
      </select>
      <div class="spacer"></div>
      <Btn variant="secondary">Export PDF</Btn>
      <Btn variant="ghost">Schedule Email</Btn>
    </div>

    <div style="display:grid; grid-template-columns:repeat(4,1fr); gap:12px">
      <div v-for="k in kpis" :key="k.l" class="kpi">
        <div class="kpi__label">{{ k.l }}</div>
        <div class="kpi__value">{{ k.v }}</div>
        <div style="font:600 11.5px var(--font-mono); color:var(--brand-green-dark)">{{ k.d }} vs Apr</div>
        <svg viewBox="0 0 120 40" style="width:100%; height:40px">
          <polyline :points="sparklinePoints(k.sp)" fill="none" :stroke="k.c" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
      </div>
    </div>

    <div style="display:grid; grid-template-columns:1.4fr 1fr; gap:14px">
      <div class="card">
        <div class="card__header"><h3 class="card__title">Revenue by Payment Method · This Month</h3></div>
        <div class="card__body">
          <div v-for="b in bars" :key="b.l" class="bar-row">
            <div class="bar-row__label">{{ b.l }}</div>
            <div class="bar-row__track">
              <div class="bar-row__fill" :style="{ width: (b.v / 48200 * 100) + '%', background: b.c }"></div>
            </div>
            <div class="bar-row__val">{{ b.v.toLocaleString() }}</div>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card__header"><h3 class="card__title">Top 5 Diagnoses (ICD-10)</h3></div>
        <div class="card__body">
          <div v-for="b in icd" :key="b.l" class="bar-row">
            <div class="bar-row__label">{{ b.l }}</div>
            <div class="bar-row__track">
              <div class="bar-row__fill" :style="{ width: (b.v / 184 * 100) + '%' }"></div>
            </div>
            <div class="bar-row__val">{{ b.v }}</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
