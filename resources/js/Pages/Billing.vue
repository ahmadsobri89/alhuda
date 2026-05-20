<script setup>
import { computed } from 'vue'
import KlinikLayout from '@/Layouts/KlinikLayout.vue'
import Btn from '@/Components/Clinic/Btn.vue'

defineOptions({ layout: KlinikLayout })

const lines = [
  { code:'CONS-001',  desc:'GP Consultation · Dr. Aiman Rashid',   qty:1,  price:35.00 },
  { code:'PROC-014',  desc:'Throat swab + rapid antigen',           qty:1,  price:25.00 },
  { code:'DRUG-AMOX', desc:'Amoxicillin 500mg × 15 caps',           qty:15, price:0.30  },
  { code:'DRUG-PCM',  desc:'Paracetamol 1g × 12 tabs',             qty:12, price:0.20  },
  { code:'DRUG-STR',  desc:'Strepsils Lozenges × 24',              qty:24, price:0.40  },
]

const sub   = computed(() => lines.reduce((s, l) => s + l.qty * l.price, 0))
const sst   = computed(() => sub.value * 0.08)
const total = computed(() => sub.value + sst.value)
</script>

<template>
  <div class="screen" style="display:grid; grid-template-columns:1fr 320px; gap:14px; padding-bottom:24px">
    <div class="card">
      <div class="card__header">
        <div>
          <h3 class="card__title">Invoice INV-2026-001847</h3>
          <p class="card__sub">Aminah binti Hassan · 780229-08-5234 · 14 May 2026</p>
        </div>
      </div>
      <div class="table__head" style="grid-template-columns:110px 1fr 60px 90px 100px">
        <div>Code</div><div>Description</div><div>Qty</div><div>Price</div><div>Total</div>
      </div>
      <div
        v-for="l in lines" :key="l.code"
        class="table__row"
        style="grid-template-columns:110px 1fr 60px 90px 100px"
      >
        <div class="mono" style="font:600 11.5px var(--font-mono); color:var(--fg3)">{{ l.code }}</div>
        <div style="font:500 12.5px var(--font-sans); color:var(--fg1)">{{ l.desc }}</div>
        <div class="mono" style="font:500 12px var(--font-mono); color:var(--fg2)">{{ l.qty }}</div>
        <div class="mono" style="font:500 12px var(--font-mono); color:var(--fg2)">{{ l.price.toFixed(2) }}</div>
        <div class="mono" style="font:700 13px var(--font-mono); color:var(--fg1)">{{ (l.qty * l.price).toFixed(2) }}</div>
      </div>
    </div>

    <div style="display:flex; flex-direction:column; gap:14px">
      <div class="card">
        <div class="card__body">
          <div class="row"><div style="flex:1; color:var(--fg3); font:500 12px var(--font-sans)">Subtotal</div><div class="mono" style="font:600 13px var(--font-mono)">{{ sub.toFixed(2) }}</div></div>
          <div class="row"><div style="flex:1; color:var(--fg3); font:500 12px var(--font-sans)">SST 8%</div><div class="mono" style="font:600 13px var(--font-mono)">{{ sst.toFixed(2) }}</div></div>
          <div class="hr"></div>
          <div class="row">
            <div style="flex:1; font:700 14px var(--font-sans); color:var(--fg1)">Total</div>
            <div class="mono" style="font:800 18px var(--font-mono); color:var(--brand-green-dark)">RM {{ total.toFixed(2) }}</div>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card__header"><h3 class="card__title">Payment</h3></div>
        <div class="card__body" style="display:grid; grid-template-columns:1fr 1fr; gap:8px">
          <Btn variant="secondary" size="sm">💵 Cash</Btn>
          <Btn variant="secondary" size="sm">💳 Card</Btn>
          <Btn variant="secondary" size="sm">📱 DuitNow QR</Btn>
          <Btn variant="secondary" size="sm">🛡 Panel GL</Btn>
          <Btn variant="primary" style="grid-column:span 2; justify-content:center">Collect Payment →</Btn>
        </div>
      </div>
    </div>
  </div>
</template>
