<script setup>
import KlinikLayout from '@/Layouts/KlinikLayout.vue'
import Avatar from '@/Components/Clinic/Avatar.vue'
import Badge from '@/Components/Clinic/Badge.vue'
import Btn from '@/Components/Clinic/Btn.vue'
import Icon from '@/Components/Clinic/Icon.vue'

defineOptions({ layout: KlinikLayout })

const days = ['Mon 12', 'Tue 13', 'Wed 14', 'Thu 15', 'Fri 16', 'Sat 17']
const slots = ['08:00','08:30','09:00','09:30','10:00','10:30','11:00','11:30','12:00','14:00','14:30','15:00','15:30','16:00','16:30']
const booked = {
  '08:00':[0,2,4], '08:30':[1], '09:00':[0,2], '09:30':[3,5],
  '10:00':[0,1,2], '10:30':[0], '11:00':[1,4], '11:30':[2,3],
  '12:00':[5], '14:00':[0,3], '14:30':[1,2], '15:00':[4],
  '15:30':[0,5], '16:00':[2], '16:30':[1,3,4],
}

const today = [
  { time:'08:00', name:'Hassan bin Ali',       type:'Follow-up · HTN',      status:'done'      },
  { time:'08:30', name:'Wong Mei Ling',         type:'Routine · Hypothyroid', status:'done'      },
  { time:'09:00', name:'Lim Kok Wing',          type:'New · Chest pain',     status:'in-room'   },
  { time:'09:30', name:'Tan Wei Ming',          type:'Follow-up · Asthma',   status:'in-room'   },
  { time:'10:30', name:'Aminah binti Hassan',   type:'Follow-up · HTN + DM', status:'waiting'   },
  { time:'11:00', name:'Siti Nor Aisyah',       type:'Annual checkup',       status:'confirmed' },
  { time:'11:15', name:'Rajesh Kumar',          type:'Sore throat · 2 days', status:'confirmed' },
  { time:'14:00', name:'Nurul Ain Zainal',      type:'Antenatal week 24',    status:'confirmed' },
]

function isBooked(time, dayIdx) { return (booked[time] || []).includes(dayIdx) }
function statusTone(s) { return s==='done'?'neutral':s==='in-room'?'green':s==='waiting'?'orange':'blue' }
</script>

<template>
  <div class="screen">
    <div class="row">
      <Btn variant="secondary" size="sm">‹</Btn>
      <div style="font:700 14px var(--font-sans); color:var(--fg1)">Week of 12–17 May 2026</div>
      <Btn variant="secondary" size="sm">›</Btn>
      <div class="spacer"></div>
      <Btn variant="ghost">Today</Btn>
      <Btn variant="primary"><Icon name="plus" :size="14" /> New Appointment</Btn>
    </div>

    <div style="display:grid; grid-template-columns:1.4fr 1fr; gap:14px">
      <div class="card">
        <div class="card__header">
          <h3 class="card__title">Weekly slot grid · Dr. Aiman</h3>
          <p class="card__sub">Tap a slot to book</p>
        </div>
        <div class="card__body" style="overflow-x:auto">
          <div style="display:grid; grid-template-columns:70px repeat(6,1fr); gap:4px; min-width:600px">
            <div></div>
            <div v-for="d in days" :key="d" style="font:700 11.5px var(--font-sans); color:var(--fg2); text-align:center; padding:8px 0; background:var(--bg-soft); border-radius:6px">{{ d }}</div>
            <template v-for="t in slots" :key="t">
              <div class="mono" style="font:500 11px var(--font-mono); color:var(--fg3); padding:8px 4px; text-align:right">{{ t }}</div>
              <div
                v-for="i in 6" :key="t + i"
                :style="{
                  padding:'7px 4px', textAlign:'center', borderRadius:'4px', cursor:'pointer',
                  background: isBooked(t, i-1) ? 'var(--brand-green)' : 'var(--bg-soft)',
                  color: isBooked(t, i-1) ? '#fff' : 'var(--fg3)',
                  font: isBooked(t, i-1) ? '700 10.5px var(--font-sans)' : '500 10.5px var(--font-sans)',
                }"
              >{{ isBooked(t, i-1) ? 'Booked' : 'Free' }}</div>
            </template>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card__header">
          <h3 class="card__title">Today · Wed 14 May</h3>
          <p class="card__sub">{{ today.length }} appointments</p>
        </div>
        <div>
          <div v-for="(a, i) in today" :key="i" class="row" style="padding:11px 18px; border-top:1px solid var(--border); gap:10px">
            <div class="mono" style="font:700 12.5px var(--font-mono); color:var(--fg2); width:46px">{{ a.time }}</div>
            <Avatar :name="a.name" size="sm" />
            <div style="flex:1; min-width:0">
              <div style="font:600 12.5px var(--font-sans); color:var(--fg1); overflow:hidden; text-overflow:ellipsis; white-space:nowrap">{{ a.name }}</div>
              <div style="font:400 11px var(--font-sans); color:var(--fg3)">{{ a.type }}</div>
            </div>
            <Badge :tone="statusTone(a.status)">{{ a.status }}</Badge>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
