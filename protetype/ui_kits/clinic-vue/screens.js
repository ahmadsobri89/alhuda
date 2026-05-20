// Klinik Al-Huda — Vue 3 screen components
(function() {
const { Icon, Avatar, Badge, Btn, TriagePill } = window.KlinikComponents;

// ---------- Dashboard ----------
const DashboardScreen = {
  components: { Icon, Avatar, Badge, Btn },
  data: () => ({
    kpis: [
      { l: "Today's Patients", v: '31', s: '12 in queue · 19 done', tone: 'green' },
      { l: 'Avg Wait Time', v: '14 min', s: 'Target ≤ 15 min', tone: 'green' },
      { l: 'Revenue Today', v: 'RM 4,820', s: '+18% vs avg', tone: 'green' },
      { l: 'Critical Alerts', v: '2', s: '1 lab · 1 stock', tone: 'red' },
    ],
    upcoming: [
      { time: '10:30', name: 'Aminah binti Hassan', type: 'Follow-up · HTN + DM', doctor: 'Dr. Aiman' },
      { time: '10:45', name: 'Tan Wei Ming', type: 'New consultation', doctor: 'Dr. Aiman' },
      { time: '11:00', name: 'Siti Nor Aisyah', type: 'Annual checkup', doctor: 'Dr. Aiman' },
      { time: '11:15', name: 'Rajesh Kumar', type: 'Sore throat · 2 days', doctor: 'Dr. Aiman' },
    ],
    alerts: [
      { tone: 'red', title: 'Critical lab result', msg: 'Aminah Hassan · Hb 6.2 g/dL — severe anemia', icon: 'alert' },
      { tone: 'orange', title: 'Low stock', msg: 'Salbutamol Inhaler · 8 units (reorder at 25)', icon: 'pill' },
      { tone: 'yellow', title: 'Insurance pending', msg: '3 GL approvals from AIA Panel', icon: 'invoice' },
    ],
  }),
  template: `
    <div class="screen">
      <div style="display:grid; grid-template-columns: repeat(4,1fr); gap:12px">
        <div v-for="k in kpis" :key="k.l" class="kpi">
          <div class="kpi__label">{{ k.l }}</div>
          <div class="kpi__value" :style="{color: k.tone==='red'?'var(--brand-red)':'var(--fg1)'}">{{ k.v }}</div>
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
            <div v-for="(u,i) in upcoming" :key="i" class="row" style="padding:12px 18px; border-top: 1px solid var(--border)">
              <div class="mono" style="font:700 13px var(--font-mono); color: var(--fg2); width:50px">{{ u.time }}</div>
              <Avatar :name="u.name" />
              <div style="flex:1">
                <div style="font:600 13px var(--font-sans); color: var(--fg1)">{{ u.name }}</div>
                <div style="font:400 12px var(--font-sans); color: var(--fg3)">{{ u.type }}</div>
              </div>
              <Btn variant="primary" size="sm">Start →</Btn>
            </div>
          </div>
        </div>
        <div class="card">
          <div class="card__header">
            <h3 class="card__title">Alerts & inbox</h3>
          </div>
          <div>
            <div v-for="(a,i) in alerts" :key="i" class="row" style="padding:12px 18px; border-top: 1px solid var(--border); align-items: flex-start; gap:10px">
              <div :style="{width:'30px', height:'30px', borderRadius:'8px', background: a.tone==='red'?'#FEE2E2':a.tone==='orange'?'#FFEDD5':'#FEF3C7', color: a.tone==='red'?'#991B1B':a.tone==='orange'?'#9A3412':'#92400E', display:'grid', placeItems:'center'}">
                <Icon :name="a.icon" :size="16" />
              </div>
              <div style="flex:1">
                <div style="font:700 12.5px var(--font-sans); color: var(--fg1)">{{ a.title }}</div>
                <div style="font:400 12px var(--font-sans); color: var(--fg3)">{{ a.msg }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  `
};

// ---------- Queue ----------
const QueueScreen = {
  components: { Icon, Avatar, Badge, Btn, TriagePill },
  data: () => ({
    rows: [
      { no:'A-007', name:'Aminah binti Hassan', ic:'780229-08-5234', age:'47F', complaint:'Sore throat, fever 38.5°C', wait:'8 min', level:3, status:'in-room' },
      { no:'A-008', name:'Lim Kok Wing', ic:'650412-14-8821', age:'58M', complaint:'Chest pain on exertion', wait:'2 min', level:2, status:'urgent' },
      { no:'A-009', name:'Tan Wei Ming', ic:'920815-10-7733', age:'33M', complaint:'High fever, 3 days', wait:'15 min', level:3, status:'waiting' },
      { no:'A-010', name:'Siti Nor Aisyah', ic:'850101-14-5678', age:'40F', complaint:'Annual health screening', wait:'22 min', level:4, status:'waiting' },
      { no:'A-011', name:'Rajesh Kumar', ic:'010322-08-1145', age:'25M', complaint:'Sore throat', wait:'28 min', level:5, status:'waiting' },
      { no:'A-012', name:'Nurul Ain Zainal', ic:'950707-03-9988', age:'30F', complaint:'Antenatal check (week 24)', wait:'35 min', level:4, status:'waiting' },
    ]
  }),
  emits: ['consult'],
  template: `
    <div class="screen">
      <div class="card" style="overflow:hidden">
        <div class="table__head" style="grid-template-columns: 80px 2fr 1.4fr 1.5fr 90px 130px 110px">
          <div>No.</div><div>Patient</div><div>IC / Age</div><div>Complaint</div><div>Wait</div><div>Triage</div><div></div>
        </div>
        <div v-for="r in rows" :key="r.no" class="table__row" :style="{gridTemplateColumns:'80px 2fr 1.4fr 1.5fr 90px 130px 110px', background: r.status==='in-room'?'var(--brand-green-light)':r.status==='urgent'?'#FFF5F5':'transparent'}">
          <div class="mono" style="font:700 13px var(--font-mono); color: var(--fg1)">{{ r.no }}</div>
          <div class="row"><Avatar :name="r.name" :size="'sm'" /><div style="font:600 13px var(--font-sans)">{{ r.name }}</div></div>
          <div><div class="mono" style="font:500 11.5px var(--font-mono); color: var(--fg3)">{{ r.ic }}</div><div style="font:500 11.5px var(--font-sans); color: var(--fg2)">{{ r.age }}</div></div>
          <div style="font:400 12.5px var(--font-sans); color: var(--fg2)">{{ r.complaint }}</div>
          <div class="mono" style="font:600 12.5px var(--font-mono); color: var(--fg2)">{{ r.wait }}</div>
          <div><TriagePill :level="r.level" /></div>
          <div><Btn variant="primary" size="sm" @click="$emit('consult')">Consult →</Btn></div>
        </div>
      </div>
    </div>
  `
};

// ---------- EMR ----------
const EMRScreen = {
  components: { Icon, Avatar, Badge, Btn, TriagePill },
  data: () => ({
    soapTab: 'S',
    soapText: 'Patient presents with sore throat for 3 days. Intermittent fever, max temperature 38.5°C last night. Mild dry cough and pain on swallowing. No vomiting or diarrhea. Appetite reduced. Currently on Amlodipine 5mg OD and Metformin 500mg BD for HTN and T2DM.',
    vitals: [
      { l: 'BP', v: '142/89', u: 'mmHg', abnormal: true },
      { l: 'HR', v: '88', u: 'bpm', abnormal: false },
      { l: 'Temp', v: '37.4', u: '°C', abnormal: false },
      { l: 'SpO₂', v: '98', u: '%', abnormal: false },
      { l: 'Weight', v: '68', u: 'kg', abnormal: false },
    ],
  }),
  template: `
    <div class="screen" style="flex-direction: row; padding: 0">
      <div style="flex: 1; padding: 24px; display: flex; flex-direction: column; gap: 14px; overflow: auto">
        <!-- patient banner -->
        <div class="card" style="padding: 16px 18px; display: flex; align-items: center; gap: 14px">
          <Avatar name="Aminah Hassan" :size="'lg'" />
          <div style="flex:1">
            <div style="display: flex; align-items: center; gap: 10px">
              <div style="font: 700 16px var(--font-sans); color: var(--fg1)">Aminah binti Hassan</div>
              <Badge tone="red">⚠ Penicillin allergy</Badge>
              <Badge tone="orange">HTN · T2DM</Badge>
            </div>
            <div class="mono" style="font: 500 12px var(--font-mono); color: var(--fg3); margin-top: 2px">780229-08-5234 · 47F · O+ · P-2026-00482</div>
          </div>
          <div style="display:flex; gap: 6px">
            <div v-for="v in vitals" :key="v.l" class="vital">
              <div class="vital__label">{{ v.l }}</div>
              <div :class="['vital__val', v.abnormal?'abnormal':'']">{{ v.v }}</div>
              <div class="vital__unit">{{ v.u }}</div>
            </div>
          </div>
        </div>
        <!-- SOAP -->
        <div class="card">
          <div class="card__header">
            <h3 class="card__title">SOAP Notes</h3>
            <p class="card__sub">Auto-saved · 11:02 AM</p>
            <div class="spacer"></div>
            <div class="tabs" style="margin: 0">
              <button v-for="t in [['S','Subjective'],['O','Objective'],['A','Assessment'],['P','Plan']]" :key="t[0]" :class="['tab', soapTab===t[0]?'active':'']" @click="soapTab=t[0]">{{ t[1] }}</button>
            </div>
          </div>
          <div class="card__body">
            <textarea v-model="soapText" style="width:100%; min-height: 140px; border:1px solid var(--border); border-radius:8px; padding:12px; font: 400 13px var(--font-sans); resize: vertical"></textarea>
          </div>
        </div>
        <!-- Diagnosis & Plan -->
        <div class="card">
          <div class="card__header"><h3 class="card__title">Diagnosis · ICD-10</h3></div>
          <div class="card__body" style="display:flex; gap:8px; flex-wrap:wrap">
            <Badge tone="green">J02.9 · Acute pharyngitis</Badge>
            <Badge tone="neutral">I10 · Essential HTN (chronic)</Badge>
            <Badge tone="neutral">E11.9 · Type 2 DM (chronic)</Badge>
            <Btn variant="ghost" size="sm">+ Add</Btn>
          </div>
        </div>
        <div class="card">
          <div class="card__header"><h3 class="card__title">Prescription</h3><div class="spacer"></div><Btn variant="ghost" size="sm">+ Add drug</Btn></div>
          <div>
            <div v-for="(rx,i) in [
              {n:'Amoxicillin 500mg',d:'1 cap TDS × 5 days',w:'⚠ Penicillin allergy on file'},
              {n:'Paracetamol 1g',d:'1 tab QID PRN fever',w:''},
              {n:'Strepsils Lozenges',d:'1 PRN, max 8/day',w:''},
            ]" :key="i" class="row" style="padding: 11px 18px; border-top: 1px solid var(--border)">
              <div style="flex:1">
                <div style="font: 600 13px var(--font-sans)">{{ rx.n }}</div>
                <div class="mono" style="font: 500 11.5px var(--font-mono); color: var(--fg3)">{{ rx.d }}</div>
              </div>
              <Badge v-if="rx.w" tone="red">{{ rx.w }}</Badge>
            </div>
          </div>
        </div>
      </div>
      <!-- AI sidebar -->
      <aside style="width: 320px; flex-shrink: 0; background: #fff; border-left: 1px solid var(--border); padding: 18px; overflow: auto; display: flex; flex-direction: column; gap: 14px">
        <div class="ai-box">
          <Icon name="sparkle" :size="16" />
          <div>
            <div class="ai-box__title">AI Visit Summary</div>
            <div class="ai-box__body" style="margin-top:4px">47y female, hypertensive + T2DM, presents with 3-day sore throat, low-grade fever 38.5°C, no exudate. BP elevated at 142/89 (chronic). Most likely <b>viral pharyngitis</b>, self-limiting.</div>
          </div>
        </div>
        <div>
          <div style="font: 700 12px var(--font-sans); letter-spacing: 0.06em; text-transform: uppercase; color: var(--fg3); margin-bottom: 8px">Differential</div>
          <div style="display:flex; flex-direction: column; gap: 6px">
            <div v-for="d in [{n:'Viral pharyngitis',p:78},{n:'Group A Strep',p:14},{n:'Allergic rhinitis',p:6},{n:'COVID-19',p:2}]" :key="d.n" class="row" style="padding: 6px 0">
              <div style="flex:1; font: 500 12.5px var(--font-sans); color: var(--fg2)">{{ d.n }}</div>
              <div class="mono" style="font: 700 12px var(--font-mono); color: var(--fg2)">{{ d.p }}%</div>
            </div>
          </div>
        </div>
        <div>
          <div style="font: 700 12px var(--font-sans); letter-spacing: 0.06em; text-transform: uppercase; color: var(--fg3); margin-bottom: 8px">Suggested Orders</div>
          <Btn variant="secondary" size="sm" style="width:100%; justify-content: flex-start; margin-bottom: 4px">+ Throat swab + RAT</Btn>
          <Btn variant="secondary" size="sm" style="width:100%; justify-content: flex-start">+ HbA1c (overdue 4mo)</Btn>
        </div>
        <div class="hr"></div>
        <Btn variant="primary" style="width:100%; justify-content:center"><Icon name="check" :size="14" /> Sign & Close Visit</Btn>
      </aside>
    </div>
  `
};

// ---------- Register Patient ----------
const RegisterScreen = {
  components: { Icon, Btn, Badge },
  template: `
    <div class="screen">
      <div class="card">
        <div class="card__header">
          <div><h3 class="card__title">MyKad Smart Intake</h3><p class="card__sub">Tap MyKad reader or scan to auto-fill</p></div>
          <div class="spacer"></div>
          <Badge tone="green">✓ MyKad read · 780229-08-5234</Badge>
        </div>
        <div class="card__body" style="display:grid; grid-template-columns: repeat(4, 1fr); gap: 14px">
          <div class="field" style="grid-column: span 2"><label class="field__label">Full Name *</label><input class="input" value="Aminah binti Hassan" /></div>
          <div class="field"><label class="field__label">IC / MyKad *</label><input class="input mono" value="780229-08-5234" /></div>
          <div class="field"><label class="field__label">Date of Birth</label><input class="input" type="date" value="1978-02-29" /></div>
          <div class="field"><label class="field__label">Gender</label><select class="select"><option>Female</option><option>Male</option></select></div>
          <div class="field"><label class="field__label">Race</label><select class="select"><option>Malay</option><option>Chinese</option><option>Indian</option><option>Other</option></select></div>
          <div class="field"><label class="field__label">Religion</label><select class="select"><option>Islam</option><option>Buddhist</option><option>Christian</option><option>Hindu</option></select></div>
          <div class="field"><label class="field__label">Blood Type</label><select class="select"><option>O+</option><option>A+</option><option>B+</option><option>AB+</option></select></div>
        </div>
      </div>
      <div class="card">
        <div class="card__header"><h3 class="card__title">Contact & Emergency</h3></div>
        <div class="card__body" style="display:grid; grid-template-columns: repeat(4, 1fr); gap: 14px">
          <div class="field" style="grid-column: span 2"><label class="field__label">Address</label><input class="input" value="No. 12, Jalan Hijau 4, Taman Bukit, 43200 Cheras, Selangor" /></div>
          <div class="field"><label class="field__label">Phone</label><input class="input mono" value="+60 12-345 6789" /></div>
          <div class="field"><label class="field__label">Email</label><input class="input" value="aminah.h@gmail.com" /></div>
          <div class="field"><label class="field__label">Next of Kin</label><input class="input" value="Hassan bin Ali (husband)" /></div>
          <div class="field"><label class="field__label">NOK Phone</label><input class="input mono" value="+60 19-876 5432" /></div>
        </div>
      </div>
      <div class="card">
        <div class="card__header"><h3 class="card__title">Medical & Insurance</h3></div>
        <div class="card__body" style="display:grid; grid-template-columns: repeat(4, 1fr); gap: 14px">
          <div class="field" style="grid-column: span 2"><label class="field__label">Known Allergies</label><input class="input" value="Penicillin, peanuts" /><span class="field__hint">Triggers banner on EMR</span></div>
          <div class="field" style="grid-column: span 2"><label class="field__label">Chronic Conditions</label><input class="input" value="Hypertension, Type 2 DM" /></div>
          <div class="field"><label class="field__label">Insurance / Panel</label><select class="select"><option>AIA Panel</option><option>Self-pay</option><option>SOCSO</option><option>Allianz</option></select></div>
          <div class="field"><label class="field__label">Policy No.</label><input class="input mono" placeholder="Optional" /></div>
        </div>
      </div>
      <div class="row" style="justify-content: flex-end; gap: 8px">
        <Btn variant="ghost">Cancel</Btn>
        <Btn variant="secondary">Save Draft</Btn>
        <Btn variant="primary">Register & Add to Queue →</Btn>
      </div>
    </div>
  `
};

// ---------- Pharmacy ----------
const PharmacyScreen = {
  components: { Icon, Avatar, Badge, Btn },
  data: () => ({
    queue: [
      { rx: 'Rx-2026-1142', name: 'Aminah Hassan', items: 3, doctor: 'Dr. Aiman', age: '5 min', status: 'pending' },
      { rx: 'Rx-2026-1141', name: 'Lim Kok Wing', items: 2, doctor: 'Dr. Faridah', age: '8 min', status: 'verifying' },
      { rx: 'Rx-2026-1140', name: 'Tan Wei Ming', items: 4, doctor: 'Dr. Aiman', age: '12 min', status: 'pending' },
      { rx: 'Rx-2026-1139', name: 'Rajesh Kumar', items: 1, doctor: 'Dr. Aiman', age: '15 min', status: 'ready' },
    ]
  }),
  template: `
    <div class="screen">
      <div class="ai-box">
        <Icon name="sparkle" :size="16" />
        <div><div class="ai-box__title">AI Drug Check · No critical interactions</div>
        <div class="ai-box__body">All 4 medications cleared against patient allergies (Sulfa) and current chronic medications.</div></div>
      </div>
      <div class="card" style="overflow:hidden">
        <div class="card__header"><h3 class="card__title">Dispensing Queue</h3><p class="card__sub">4 prescriptions awaiting</p></div>
        <div class="table__head" style="grid-template-columns: 140px 1.6fr 80px 1.2fr 90px 110px 110px">
          <div>Rx No.</div><div>Patient</div><div>Items</div><div>Doctor</div><div>Age</div><div>Status</div><div></div>
        </div>
        <div v-for="r in queue" :key="r.rx" class="table__row" style="grid-template-columns: 140px 1.6fr 80px 1.2fr 90px 110px 110px">
          <div class="mono" style="font:700 12px var(--font-mono); color: var(--fg1)">{{ r.rx }}</div>
          <div class="row"><Avatar :name="r.name" :size="'sm'" /><div style="font:600 13px var(--font-sans)">{{ r.name }}</div></div>
          <div class="mono" style="font:700 13px var(--font-mono); color: var(--fg1)">{{ r.items }}</div>
          <div style="font:500 12.5px var(--font-sans); color: var(--fg2)">{{ r.doctor }}</div>
          <div class="mono" style="font:500 12px var(--font-mono); color: var(--fg3)">{{ r.age }}</div>
          <div><Badge :tone="r.status==='ready'?'green':r.status==='verifying'?'blue':'orange'">{{ r.status }}</Badge></div>
          <div><Btn variant="primary" size="sm">Dispense →</Btn></div>
        </div>
      </div>
    </div>
  `
};

// ---------- Inventory ----------
const InventoryScreen = {
  components: { Icon, Badge, Btn },
  data: () => ({
    items: [
      { name:'Amoxicillin 500mg', form:'Capsule', stock:240, reorder:200, exp:'08/2027', lot:'AMX-2403', price:0.30, st:'ok' },
      { name:'Paracetamol 1g', form:'Tablet', stock:1840, reorder:500, exp:'03/2028', lot:'PCM-2502', price:0.20, st:'ok' },
      { name:'Salbutamol Inhaler 100mcg', form:'MDI', stock:8, reorder:25, exp:'11/2027', lot:'SAL-2511', price:18.00, st:'low' },
      { name:'Metformin 500mg', form:'Tablet', stock:32, reorder:200, exp:'02/2027', lot:'MET-2402', price:0.18, st:'low' },
      { name:'Codeine Phosphate 30mg', form:'Tablet', stock:120, reorder:50, exp:'09/2026', lot:'COD-2509', price:0.85, st:'poison-c' },
      { name:'Diazepam 5mg', form:'Tablet', stock:60, reorder:30, exp:'12/2026', lot:'DIZ-2412', price:0.95, st:'poison-b' },
      { name:'Ciprofloxacin 500mg', form:'Tablet', stock:28, reorder:100, exp:'06/2026', lot:'CIP-2206', price:0.65, st:'expiring' },
    ],
    kpis: [{l:'Total SKUs',v:'284'},{l:'Low Stock',v:'12',c:'orange'},{l:'Expiring 90d',v:'4',c:'red'},{l:'Inventory Value',v:'RM 84,220'}],
  }),
  methods: {
    flag(s) {
      if (s==='ok') return null;
      const map = {low:['orange','Low stock'],expiring:['red','Expiring < 90d'],'poison-b':['red','Akta Racun B'],'poison-c':['red','Akta Racun C']};
      return map[s];
    }
  },
  template: `
    <div class="screen">
      <div style="display:grid; grid-template-columns: repeat(4,1fr); gap:12px">
        <div v-for="k in kpis" :key="k.l" class="kpi">
          <div class="kpi__label">{{ k.l }}</div>
          <div class="kpi__value" :style="{color: k.c==='red'?'var(--brand-red)':k.c==='orange'?'var(--brand-orange)':'var(--fg1)'}">{{ k.v }}</div>
        </div>
      </div>
      <div class="card" style="overflow:hidden">
        <div class="table__head" style="grid-template-columns: 2fr 1fr 90px 90px 110px 110px 90px 1fr">
          <div>Drug</div><div>Form</div><div>Stock</div><div>Reorder</div><div>Expiry</div><div>Lot No.</div><div>Cost</div><div>Flag</div>
        </div>
        <div v-for="it in items" :key="it.lot" class="table__row" style="grid-template-columns: 2fr 1fr 90px 90px 110px 110px 90px 1fr">
          <div style="font:600 13px var(--font-sans); color: var(--fg1)">{{ it.name }}</div>
          <div style="font:500 12.5px var(--font-sans); color: var(--fg2)">{{ it.form }}</div>
          <div class="mono" :style="{font:'700 13px var(--font-mono)', color: it.st==='low'?'var(--brand-orange)':'var(--fg1)'}">{{ it.stock }}</div>
          <div class="mono" style="font:500 12px var(--font-mono); color: var(--fg3)">{{ it.reorder }}</div>
          <div class="mono" :style="{font:'500 12px var(--font-mono)', color: it.st==='expiring'?'var(--brand-red)':'var(--fg2)'}">{{ it.exp }}</div>
          <div class="mono" style="font:500 12px var(--font-mono); color: var(--fg3)">{{ it.lot }}</div>
          <div class="mono" style="font:600 12.5px var(--font-mono); color: var(--fg2)">RM {{ it.price.toFixed(2) }}</div>
          <div><Badge v-if="flag(it.st)" :tone="flag(it.st)[0]">{{ flag(it.st)[1] }}</Badge></div>
        </div>
      </div>
    </div>
  `
};

// ---------- Billing ----------
const BillingScreen = {
  components: { Btn, Badge },
  data: () => ({
    lines: [
      { code: 'CONS-001', desc: 'GP Consultation · Dr. Aiman Rashid', qty: 1, price: 35.00 },
      { code: 'PROC-014', desc: 'Throat swab + rapid antigen', qty: 1, price: 25.00 },
      { code: 'DRUG-AMOX', desc: 'Amoxicillin 500mg × 15 caps', qty: 15, price: 0.30 },
      { code: 'DRUG-PCM', desc: 'Paracetamol 1g × 12 tabs', qty: 12, price: 0.20 },
      { code: 'DRUG-STR', desc: 'Strepsils Lozenges × 24', qty: 24, price: 0.40 },
    ]
  }),
  computed: {
    sub() { return this.lines.reduce((s,l)=>s+l.qty*l.price,0); },
    sst() { return this.sub * 0.08; },
    total() { return this.sub + this.sst; }
  },
  template: `
    <div class="screen" style="display:grid; grid-template-columns: 1fr 320px; gap:14px; padding-bottom: 24px">
      <div class="card">
        <div class="card__header">
          <div><h3 class="card__title">Invoice INV-2026-001847</h3><p class="card__sub">Aminah binti Hassan · 780229-08-5234 · 14 May 2026</p></div>
        </div>
        <div class="table__head" style="grid-template-columns: 110px 1fr 60px 90px 100px">
          <div>Code</div><div>Description</div><div>Qty</div><div>Price</div><div>Total</div>
        </div>
        <div v-for="l in lines" :key="l.code" class="table__row" style="grid-template-columns: 110px 1fr 60px 90px 100px">
          <div class="mono" style="font:600 11.5px var(--font-mono); color: var(--fg3)">{{ l.code }}</div>
          <div style="font:500 12.5px var(--font-sans); color: var(--fg1)">{{ l.desc }}</div>
          <div class="mono" style="font:500 12px var(--font-mono); color: var(--fg2)">{{ l.qty }}</div>
          <div class="mono" style="font:500 12px var(--font-mono); color: var(--fg2)">{{ l.price.toFixed(2) }}</div>
          <div class="mono" style="font:700 13px var(--font-mono); color: var(--fg1)">{{ (l.qty*l.price).toFixed(2) }}</div>
        </div>
      </div>
      <div style="display:flex; flex-direction:column; gap:14px">
        <div class="card">
          <div class="card__body">
            <div class="row"><div style="flex:1; color: var(--fg3); font:500 12px var(--font-sans)">Subtotal</div><div class="mono" style="font:600 13px var(--font-mono)">{{ sub.toFixed(2) }}</div></div>
            <div class="row"><div style="flex:1; color: var(--fg3); font:500 12px var(--font-sans)">SST 8%</div><div class="mono" style="font:600 13px var(--font-mono)">{{ sst.toFixed(2) }}</div></div>
            <div class="hr"></div>
            <div class="row"><div style="flex:1; font:700 14px var(--font-sans); color: var(--fg1)">Total</div><div class="mono" style="font:800 18px var(--font-mono); color: var(--brand-green-dark)">RM {{ total.toFixed(2) }}</div></div>
          </div>
        </div>
        <div class="card">
          <div class="card__header"><h3 class="card__title">Payment</h3></div>
          <div class="card__body" style="display:grid; grid-template-columns: 1fr 1fr; gap: 8px">
            <Btn variant="secondary" size="sm">💵 Cash</Btn>
            <Btn variant="secondary" size="sm">💳 Card</Btn>
            <Btn variant="secondary" size="sm">📱 DuitNow QR</Btn>
            <Btn variant="secondary" size="sm">🛡 Panel GL</Btn>
            <Btn variant="primary" style="grid-column: span 2; justify-content: center">Collect Payment →</Btn>
          </div>
        </div>
      </div>
    </div>
  `
};

// ---------- Reports ----------
const Sparkline = {
  props: ['data', 'color', 'h'],
  computed: {
    points() {
      const data = this.data, h = this.h || 40;
      const max = Math.max(...data), min = Math.min(...data);
      return data.map((v,i) => `${(i/(data.length-1))*120},${h - ((v-min)/(max-min||1))*(h-4) - 2}`).join(' ');
    }
  },
  template: `<svg :viewBox="'0 0 120 ' + (h||40)" style="width:100%; height: 40px"><polyline :points="points" fill="none" :stroke="color||'var(--brand-green)'" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" /></svg>`
};

const ReportsScreen = {
  components: { Btn, Badge, Sparkline },
  data: () => ({
    kpis: [
      {l:'Revenue MTD',v:'RM 142,820',d:'+18%',sp:[12,18,22,16,28,32,28,34,38,42],c:'var(--brand-green)'},
      {l:'Patients Seen',v:'1,847',d:'+9%',sp:[42,48,52,46,58,62,58,64,68,72],c:'#2563EB'},
      {l:'Avg Wait Time',v:'14 min',d:'−3 min',sp:[22,20,18,17,16,15,16,15,14,14],c:'var(--brand-orange)'},
      {l:'Satisfaction',v:'4.7 / 5',d:'+0.2',sp:[4.2,4.3,4.4,4.5,4.4,4.6,4.6,4.7,4.7,4.7],c:'var(--brand-yellow)'},
    ],
    bars: [
      {l:'Cash', v:48200, c:'var(--brand-green)'},
      {l:'Panel Claims', v:42800, c:'#2563EB'},
      {l:'DuitNow / QR', v:24600, c:'var(--brand-yellow)'},
      {l:'Card', v:18420, c:'var(--brand-orange)'},
      {l:'e-Wallet', v:8800, c:'#94A3B8'},
    ],
    icd: [
      {l:'J06.9 · URTI',v:184},
      {l:'I10 · Hypertension',v:142},
      {l:'E11.9 · T2DM',v:118},
      {l:'J02.9 · Pharyngitis',v:96},
      {l:'K30 · Dyspepsia',v:78},
    ]
  }),
  template: `
    <div class="screen">
      <div class="row">
        <select class="select" style="max-width: 200px"><option>This month · May 2026</option><option>Last 30 days</option></select>
        <div class="spacer"></div>
        <Btn variant="secondary">Export PDF</Btn>
        <Btn variant="ghost">Schedule Email</Btn>
      </div>
      <div style="display:grid; grid-template-columns: repeat(4,1fr); gap:12px">
        <div v-for="k in kpis" :key="k.l" class="kpi">
          <div class="kpi__label">{{ k.l }}</div>
          <div class="kpi__value">{{ k.v }}</div>
          <div style="font:600 11.5px var(--font-mono); color: var(--brand-green-dark)">{{ k.d }} vs Apr</div>
          <Sparkline :data="k.sp" :color="k.c" />
        </div>
      </div>
      <div style="display:grid; grid-template-columns: 1.4fr 1fr; gap:14px">
        <div class="card">
          <div class="card__header"><h3 class="card__title">Revenue by Payment Method · This Month</h3></div>
          <div class="card__body">
            <div v-for="b in bars" :key="b.l" class="bar-row">
              <div class="bar-row__label">{{ b.l }}</div>
              <div class="bar-row__track"><div class="bar-row__fill" :style="{width: (b.v/48200*100)+'%', background: b.c}"></div></div>
              <div class="bar-row__val">{{ b.v.toLocaleString() }}</div>
            </div>
          </div>
        </div>
        <div class="card">
          <div class="card__header"><h3 class="card__title">Top 5 Diagnoses (ICD-10)</h3></div>
          <div class="card__body">
            <div v-for="b in icd" :key="b.l" class="bar-row">
              <div class="bar-row__label">{{ b.l }}</div>
              <div class="bar-row__track"><div class="bar-row__fill" :style="{width: (b.v/184*100)+'%'}"></div></div>
              <div class="bar-row__val">{{ b.v }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  `
};

// ---------- Settings ----------
const SettingsScreen = {
  components: { Avatar, Badge, Btn },
  data: () => ({
    tab: 'users',
    tabs: [['users','Users & Roles'],['security','Security'],['audit','Audit Trail']],
    users: [
      { name: 'Dr. Aiman Rashid', role: 'Doctor', email: 'aiman@alhuda.my', mfa: true, st: 'active' },
      { name: 'Nurse Fatimah', role: 'Nurse', email: 'fatimah@alhuda.my', mfa: true, st: 'active' },
      { name: 'Encik Zahid', role: 'Super Admin', email: 'zahid@alhuda.my', mfa: true, st: 'active' },
      { name: 'Pn. Salina', role: 'Receptionist', email: 'salina@alhuda.my', mfa: false, st: 'active' },
      { name: 'Mr. Vinod', role: 'Pharmacist', email: 'vinod@alhuda.my', mfa: true, st: 'inactive' },
    ],
    audit: [
      { ts: '14/05 11:02:14', user: 'Dr. Aiman', act: 'EMR.update', res: 'P-2026-00482 · SOAP note', ip: '10.0.0.45', ok: true },
      { ts: '14/05 10:58:02', user: 'Pn. Salina', act: 'Bill.create', res: 'INV-2026-001847 · RM 135.28', ip: '10.0.0.12', ok: true },
      { ts: '14/05 10:42:11', user: 'Mr. Vinod', act: 'auth.login.fail', res: 'Wrong password (3/5)', ip: '10.0.0.88', ok: false },
      { ts: '14/05 10:18:55', user: 'Dr. Aiman', act: 'Rx.dispense', res: 'Rx-2026-1142 · 4 items', ip: '10.0.0.45', ok: true },
      { ts: '14/05 09:30:01', user: 'Encik Zahid', act: 'Settings.update', res: 'session.timeout 30→15min', ip: '10.0.0.2', ok: true },
    ],
    policies: [
      ['Minimum 12 characters', true],
      ['Require uppercase + number + symbol', true],
      ['Force change every 90 days', true],
      ['Require MFA for all admins', true],
      ['Require MFA for doctors', true],
      ['IP whitelist for super-admin', true],
    ]
  }),
  template: `
    <div class="screen">
      <div class="tabs">
        <button v-for="t in tabs" :key="t[0]" :class="['tab', tab===t[0]?'active':'']" @click="tab=t[0]">{{ t[1] }}</button>
      </div>
      <div v-if="tab==='users'" class="card" style="overflow:hidden">
        <div class="table__head" style="grid-template-columns: 2fr 1fr 1.4fr 100px 100px 100px">
          <div>User</div><div>Role</div><div>Email</div><div>MFA</div><div>Status</div><div></div>
        </div>
        <div v-for="u in users" :key="u.email" class="table__row" style="grid-template-columns: 2fr 1fr 1.4fr 100px 100px 100px">
          <div class="row"><Avatar :name="u.name" /><div style="font:600 13px var(--font-sans)">{{ u.name }}</div></div>
          <div><Badge :tone="u.role.includes('Admin')?'red':u.role.includes('Doctor')?'green':'blue'">{{ u.role }}</Badge></div>
          <div class="mono" style="font:500 12px var(--font-mono); color: var(--fg3)">{{ u.email }}</div>
          <div><Badge :tone="u.mfa?'green':'orange'">{{ u.mfa?'✓ TOTP':'Off' }}</Badge></div>
          <div><Badge :tone="u.st==='active'?'green':'neutral'">{{ u.st }}</Badge></div>
          <div><Btn variant="ghost" size="sm">⋯</Btn></div>
        </div>
      </div>
      <div v-if="tab==='security'" class="card">
        <div class="card__header"><h3 class="card__title">Password & MFA Policy</h3></div>
        <div class="card__body" style="display:flex; flex-direction:column; gap: 14px">
          <div v-for="(p,i) in policies" :key="i" class="row">
            <div class="toggle on"></div>
            <div style="font:500 13px var(--font-sans); color: var(--fg2)">{{ p[0] }}</div>
          </div>
        </div>
      </div>
      <div v-if="tab==='audit'" class="card" style="overflow:hidden">
        <div class="table__head" style="grid-template-columns: 150px 160px 120px 1fr 1fr 100px">
          <div>Timestamp</div><div>User</div><div>Action</div><div>Resource</div><div>IP</div><div>Result</div>
        </div>
        <div v-for="r in audit" :key="r.ts" class="table__row" style="grid-template-columns: 150px 160px 120px 1fr 1fr 100px">
          <div class="mono" style="font:500 11.5px var(--font-mono); color: var(--fg3)">{{ r.ts }}</div>
          <div style="font:600 12.5px var(--font-sans)">{{ r.user }}</div>
          <div class="mono" :style="{font:'600 11.5px var(--font-mono)', color: r.ok?'var(--brand-green-dark)':'var(--brand-red)'}">{{ r.act }}</div>
          <div style="font:400 12px var(--font-sans); color: var(--fg2)">{{ r.res }}</div>
          <div class="mono" style="font:500 11.5px var(--font-mono); color: var(--fg3)">{{ r.ip }}</div>
          <div><Badge :tone="r.ok?'green':'red'">{{ r.ok?'OK':'Failed' }}</Badge></div>
        </div>
      </div>
    </div>
  `
};

// ---------- Patient List ----------
const PatientListScreen = {
  components: { Avatar, Badge, Btn, Icon },
  data: () => ({
    patients: [
      { id:'P-2026-00482', name:'Aminah binti Hassan', ic:'780229-08-5234', age:'47F', last:'Today · 10:42', tags:['HTN','T2DM'], allergy:'Penicillin', visits:12 },
      { id:'P-2026-00481', name:'Lim Kok Wing', ic:'650412-14-8821', age:'58M', last:'Today · 09:30', tags:['CAD','Dyslipidemia'], allergy:'—', visits:24 },
      { id:'P-2026-00480', name:'Tan Wei Ming', ic:'920815-10-7733', age:'33M', last:'Today · 09:05', tags:['Asthma'], allergy:'Aspirin', visits:7 },
      { id:'P-2026-00479', name:'Siti Nor Aisyah', ic:'850101-14-5678', age:'40F', last:'Yesterday', tags:['—'], allergy:'—', visits:3 },
      { id:'P-2026-00478', name:'Rajesh Kumar', ic:'010322-08-1145', age:'25M', last:'Yesterday', tags:['—'], allergy:'Sulfa', visits:2 },
      { id:'P-2026-00477', name:'Nurul Ain Zainal', ic:'950707-03-9988', age:'30F', last:'2 days ago', tags:['Pregnancy W24'], allergy:'—', visits:8 },
      { id:'P-2026-00476', name:'Hassan bin Ali', ic:'750314-08-1122', age:'50M', last:'12 May', tags:['HTN'], allergy:'—', visits:18 },
      { id:'P-2026-00475', name:'Wong Mei Ling', ic:'880905-10-4477', age:'37F', last:'10 May', tags:['Hypothyroid'], allergy:'—', visits:5 },
    ]
  }),
  template: `
    <div class="screen">
      <div class="row">
        <div style="position:relative; flex:1; max-width:360px">
          <input class="input" placeholder="Search by name, IC, or patient ID…" style="padding-left:36px" />
          <div style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--fg3)"><Icon name="search" :size="15" /></div>
        </div>
        <Btn variant="secondary">Filter ⌄</Btn>
        <div class="spacer"></div>
        <Btn variant="ghost">Export CSV</Btn>
        <Btn variant="primary"><Icon name="plus" :size="14" /> New Patient</Btn>
      </div>
      <div class="card" style="overflow:hidden">
        <div class="table__head" style="grid-template-columns: 140px 2fr 1.4fr 80px 1.5fr 110px 90px 100px">
          <div>ID</div><div>Patient</div><div>IC / Age</div><div>Visits</div><div>Tags</div><div>Allergy</div><div>Last Visit</div><div></div>
        </div>
        <div v-for="p in patients" :key="p.id" class="table__row" style="grid-template-columns: 140px 2fr 1.4fr 80px 1.5fr 110px 90px 100px">
          <div class="mono" style="font:600 11.5px var(--font-mono); color: var(--fg3)">{{ p.id }}</div>
          <div class="row"><Avatar :name="p.name" :size="'sm'" /><div style="font:600 13px var(--font-sans)">{{ p.name }}</div></div>
          <div><div class="mono" style="font:500 11.5px var(--font-mono); color: var(--fg3)">{{ p.ic }}</div><div style="font:500 11.5px var(--font-sans); color: var(--fg2)">{{ p.age }}</div></div>
          <div class="mono" style="font:700 13px var(--font-mono); color: var(--fg1)">{{ p.visits }}</div>
          <div style="display:flex; gap:4px; flex-wrap:wrap"><Badge v-for="t in p.tags" :key="t" tone="neutral">{{ t }}</Badge></div>
          <div><Badge v-if="p.allergy !== '—'" tone="red">⚠ {{ p.allergy }}</Badge><span v-else style="font:500 11.5px var(--font-sans); color:var(--fg3)">—</span></div>
          <div style="font:500 11.5px var(--font-sans); color: var(--fg3)">{{ p.last }}</div>
          <div><Btn variant="ghost" size="sm">View →</Btn></div>
        </div>
      </div>
    </div>
  `
};

// ---------- Appointments ----------
const AppointmentsScreen = {
  components: { Avatar, Badge, Btn, Icon },
  data: () => ({
    days: ['Mon 12','Tue 13','Wed 14','Thu 15','Fri 16','Sat 17'],
    slots: ['08:00','08:30','09:00','09:30','10:00','10:30','11:00','11:30','12:00','14:00','14:30','15:00','15:30','16:00','16:30'],
    booked: {
      '08:00': [0,2,4], '08:30': [1], '09:00': [0,2], '09:30': [3,5],
      '10:00': [0,1,2], '10:30': [0], '11:00': [1,4], '11:30': [2,3],
      '12:00': [5], '14:00': [0,3], '14:30': [1,2], '15:00': [4],
      '15:30': [0,5], '16:00': [2], '16:30': [1,3,4],
    },
    today: [
      { time:'08:00', name:'Hassan bin Ali', type:'Follow-up · HTN', doctor:'Dr. Aiman', status:'done' },
      { time:'08:30', name:'Wong Mei Ling', type:'Routine · Hypothyroid', doctor:'Dr. Faridah', status:'done' },
      { time:'09:00', name:'Lim Kok Wing', type:'New · Chest pain', doctor:'Dr. Aiman', status:'in-room' },
      { time:'09:30', name:'Tan Wei Ming', type:'Follow-up · Asthma', doctor:'Dr. Aiman', status:'in-room' },
      { time:'10:30', name:'Aminah binti Hassan', type:'Follow-up · HTN + DM', doctor:'Dr. Aiman', status:'waiting' },
      { time:'11:00', name:'Siti Nor Aisyah', type:'Annual checkup', doctor:'Dr. Aiman', status:'confirmed' },
      { time:'11:15', name:'Rajesh Kumar', type:'Sore throat · 2 days', doctor:'Dr. Aiman', status:'confirmed' },
      { time:'14:00', name:'Nurul Ain Zainal', type:'Antenatal week 24', doctor:'Dr. Faridah', status:'confirmed' },
    ]
  }),
  methods: {
    isBooked(time, dayIdx) { return (this.booked[time] || []).includes(dayIdx); },
    statusTone(s) { return s==='done'?'neutral':s==='in-room'?'green':s==='waiting'?'orange':'blue'; }
  },
  template: `
    <div class="screen">
      <div class="row">
        <Btn variant="secondary" size="sm">‹</Btn>
        <div style="font:700 14px var(--font-sans); color:var(--fg1)">Week of 12–17 May 2026</div>
        <Btn variant="secondary" size="sm">›</Btn>
        <div class="spacer"></div>
        <Btn variant="ghost">Today</Btn>
        <Btn variant="primary"><Icon name="plus" :size="14" /> New Appointment</Btn>
      </div>
      <div style="display:grid; grid-template-columns: 1.4fr 1fr; gap:14px">
        <div class="card">
          <div class="card__header"><h3 class="card__title">Weekly slot grid · Dr. Aiman</h3><p class="card__sub">Tap a slot to book</p></div>
          <div class="card__body" style="overflow-x:auto">
            <div style="display:grid; grid-template-columns: 70px repeat(6, 1fr); gap:4px; min-width: 600px">
              <div></div>
              <div v-for="d in days" :key="d" style="font:700 11.5px var(--font-sans); color:var(--fg2); text-align:center; padding:8px 0; background:var(--bg-soft); border-radius:6px">{{ d }}</div>
              <template v-for="t in slots" :key="t">
                <div class="mono" style="font:500 11px var(--font-mono); color:var(--fg3); padding:8px 4px; text-align:right">{{ t }}</div>
                <div v-for="(_, i) in 6" :key="t+i" :style="{padding:'7px 4px', textAlign:'center', borderRadius:'4px', background: isBooked(t, i)?'var(--brand-green)':'var(--bg-soft)', color: isBooked(t, i)?'#fff':'var(--fg3)', font: isBooked(t, i)?'700 10.5px var(--font-sans)':'500 10.5px var(--font-sans)', cursor:'pointer'}">{{ isBooked(t, i)?'Booked':'Free' }}</div>
              </template>
            </div>
          </div>
        </div>
        <div class="card">
          <div class="card__header"><h3 class="card__title">Today · Wed 14 May</h3><p class="card__sub">{{ today.length }} appointments</p></div>
          <div>
            <div v-for="(a, i) in today" :key="i" class="row" style="padding:11px 18px; border-top:1px solid var(--border); gap:10px">
              <div class="mono" style="font:700 12.5px var(--font-mono); color:var(--fg2); width:46px">{{ a.time }}</div>
              <Avatar :name="a.name" :size="'sm'" />
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
  `
};

// Stub
const StubScreen = { props: ['name'], template: `<div class="screen" style="align-items:center; justify-content:center"><div style="text-align:center"><div style="font:700 18px var(--font-sans); color: var(--fg2)">{{ name }}</div><div style="font:400 13px var(--font-sans); color: var(--fg3); margin-top:6px">This module is in the next phase.</div></div></div>` };

window.KlinikScreens = {
  DashboardScreen, QueueScreen, EMRScreen, RegisterScreen,
  PharmacyScreen, InventoryScreen, BillingScreen, ReportsScreen, SettingsScreen,
  PatientListScreen, AppointmentsScreen, StubScreen
};
})();
