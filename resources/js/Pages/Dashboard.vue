<script setup>
import KlinikLayout from '@/Layouts/KlinikLayout.vue'
import Avatar from '@/Components/Clinic/Avatar.vue'
import Btn from '@/Components/Clinic/Btn.vue'
import Icon from '@/Components/Clinic/Icon.vue'

defineOptions({ layout: KlinikLayout })

const kpis = [
  { l: "Today's Patients", v: '31',      s: '12 in queue · 19 done', tone: 'green' },
  { l: 'Avg Wait Time',    v: '14 min',  s: 'Target ≤ 15 min',       tone: 'green' },
  { l: 'Revenue Today',   v: 'RM 4,820', s: '+18% vs avg',            tone: 'green' },
  { l: 'Critical Alerts', v: '2',        s: '1 lab · 1 stock',        tone: 'red'   },
]
const upcoming = [
  { time: '10:30', name: 'Aminah binti Hassan', type: 'Follow-up · HTN + DM' },
  { time: '10:45', name: 'Tan Wei Ming',        type: 'New consultation' },
  { time: '11:00', name: 'Siti Nor Aisyah',     type: 'Annual checkup' },
  { time: '11:15', name: 'Rajesh Kumar',         type: 'Sore throat · 2 days' },
]
const alerts = [
  { tone: 'red',    title: 'Critical lab result', msg: 'Aminah Hassan · Hb 6.2 g/dL — severe anemia',  icon: 'alert'   },
  { tone: 'orange', title: 'Low stock',            msg: 'Salbutamol Inhaler · 8 units (reorder at 25)', icon: 'pill'    },
  { tone: 'yellow', title: 'Insurance pending',    msg: '3 GL approvals from AIA Panel',                icon: 'invoice' },
]
</script>

<template>
  <div class="screen">
    <div style="display:grid; grid-template-columns: repeat(4,1fr); gap:12px">
      <div v-for="k in kpis" :key="k.l" class="kpi">
        <div class="kpi__label">{{ k.l }}</div>
        <div class="kpi__value" :style="{ color: k.tone === 'red' ? 'var(--brand-red)' : 'var(--fg1)' }">{{ k.v }}</div>
        <div class="kpi__sub">{{ k.s }}</div>
      </div>
    </div>

    <div style="display:grid; grid-template-columns: 1.4fr 1fr; gap:14px">
      <div class="card">
        <div class="card__header">
          <div>
            <h3 class="card__title">Up next today</h3>
            <p class="card__sub">4 appointments · Dr. Aiman Rashid</p>
          </div>
          <div class="spacer"></div>
          <Btn variant="ghost" size="sm">View all →</Btn>
        </div>
        <div>
          <div v-for="(u, i) in upcoming" :key="i" class="row" style="padding:12px 18px; border-top:1px solid var(--border)">
            <div class="mono" style="font:700 13px var(--font-mono); color:var(--fg2); width:50px">{{ u.time }}</div>
            <Avatar :name="u.name" />
            <div style="flex:1">
              <div style="font:600 13px var(--font-sans); color:var(--fg1)">{{ u.name }}</div>
              <div style="font:400 12px var(--font-sans); color:var(--fg3)">{{ u.type }}</div>
            </div>
            <Btn variant="primary" size="sm">Start →</Btn>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card__header"><h3 class="card__title">Alerts &amp; inbox</h3></div>
        <div>
          <div v-for="(a, i) in alerts" :key="i" class="row" style="padding:12px 18px; border-top:1px solid var(--border); align-items:flex-start; gap:10px">
            <div :style="{
              width:'30px', height:'30px', borderRadius:'8px', display:'grid', placeItems:'center',
              background: a.tone==='red'?'#FEE2E2':a.tone==='orange'?'#FFEDD5':'#FEF3C7',
              color: a.tone==='red'?'#991B1B':a.tone==='orange'?'#9A3412':'#92400E',
            }">
              <Icon :name="a.icon" :size="16" />
            </div>
            <div style="flex:1">
              <div style="font:700 12.5px var(--font-sans); color:var(--fg1)">{{ a.title }}</div>
              <div style="font:400 12px var(--font-sans); color:var(--fg3)">{{ a.msg }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
