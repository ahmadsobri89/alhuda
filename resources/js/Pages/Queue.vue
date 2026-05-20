<script setup>
import KlinikLayout from '@/Layouts/KlinikLayout.vue'
import Avatar from '@/Components/Clinic/Avatar.vue'
import Btn from '@/Components/Clinic/Btn.vue'
import TriagePill from '@/Components/Clinic/TriagePill.vue'
import { router } from '@inertiajs/vue3'

defineOptions({ layout: KlinikLayout })

const rows = [
  { no:'A-007', name:'Aminah binti Hassan', ic:'780229-08-5234', age:'47F', complaint:'Sore throat, fever 38.5°C',   wait:'8 min',  level:3, status:'in-room'  },
  { no:'A-008', name:'Lim Kok Wing',        ic:'650412-14-8821', age:'58M', complaint:'Chest pain on exertion',      wait:'2 min',  level:2, status:'urgent'   },
  { no:'A-009', name:'Tan Wei Ming',        ic:'920815-10-7733', age:'33M', complaint:'High fever, 3 days',          wait:'15 min', level:3, status:'waiting'  },
  { no:'A-010', name:'Siti Nor Aisyah',     ic:'850101-14-5678', age:'40F', complaint:'Annual health screening',     wait:'22 min', level:4, status:'waiting'  },
  { no:'A-011', name:'Rajesh Kumar',        ic:'010322-08-1145', age:'25M', complaint:'Sore throat',                 wait:'28 min', level:5, status:'waiting'  },
  { no:'A-012', name:'Nurul Ain Zainal',    ic:'950707-03-9988', age:'30F', complaint:'Antenatal check (week 24)',   wait:'35 min', level:4, status:'waiting'  },
]

function rowBg(status) {
  if (status === 'in-room') return 'var(--brand-green-light)'
  if (status === 'urgent')  return '#FFF5F5'
  return 'transparent'
}
</script>

<template>
  <div class="screen">
    <div class="card" style="overflow:hidden">
      <div class="table__head" style="grid-template-columns: 80px 2fr 1.4fr 1.5fr 90px 130px 110px">
        <div>No.</div><div>Patient</div><div>IC / Age</div><div>Complaint</div><div>Wait</div><div>Triage</div><div></div>
      </div>
      <div
        v-for="r in rows" :key="r.no"
        class="table__row"
        :style="{ gridTemplateColumns: '80px 2fr 1.4fr 1.5fr 90px 130px 110px', background: rowBg(r.status) }"
      >
        <div class="mono" style="font:700 13px var(--font-mono); color:var(--fg1)">{{ r.no }}</div>
        <div class="row">
          <Avatar :name="r.name" size="sm" />
          <div style="font:600 13px var(--font-sans)">{{ r.name }}</div>
        </div>
        <div>
          <div class="mono" style="font:500 11.5px var(--font-mono); color:var(--fg3)">{{ r.ic }}</div>
          <div style="font:500 11.5px var(--font-sans); color:var(--fg2)">{{ r.age }}</div>
        </div>
        <div style="font:400 12.5px var(--font-sans); color:var(--fg2)">{{ r.complaint }}</div>
        <div class="mono" style="font:600 12.5px var(--font-mono); color:var(--fg2)">{{ r.wait }}</div>
        <div><TriagePill :level="r.level" /></div>
        <div><Btn variant="primary" size="sm" @click="router.visit(route('emr'))">Consult →</Btn></div>
      </div>
    </div>
  </div>
</template>
