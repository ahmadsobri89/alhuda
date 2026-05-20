<script setup>
import KlinikLayout from '@/Layouts/KlinikLayout.vue'
import Badge from '@/Components/Clinic/Badge.vue'
import Btn from '@/Components/Clinic/Btn.vue'

defineOptions({ layout: KlinikLayout })
</script>

<template>
  <div class="screen">
    <div class="card">
      <div class="card__header">
        <div>
          <h3 class="card__title">MyKad Smart Intake</h3>
          <p class="card__sub">Tap MyKad reader or scan to auto-fill</p>
        </div>
        <div class="spacer"></div>
        <Badge tone="green">✓ MyKad read · 780229-08-5234</Badge>
      </div>
      <div class="card__body" style="display:grid; grid-template-columns:repeat(4,1fr); gap:14px">
        <div class="field" style="grid-column:span 2">
          <label class="field__label">Full Name *</label>
          <input class="input" value="Aminah binti Hassan" />
        </div>
        <div class="field">
          <label class="field__label">IC / MyKad *</label>
          <input class="input mono" value="780229-08-5234" />
        </div>
        <div class="field">
          <label class="field__label">Date of Birth</label>
          <input class="input" type="date" value="1978-02-29" />
        </div>
        <div class="field">
          <label class="field__label">Gender</label>
          <select class="select"><option>Female</option><option>Male</option></select>
        </div>
        <div class="field">
          <label class="field__label">Race</label>
          <select class="select"><option>Malay</option><option>Chinese</option><option>Indian</option><option>Other</option></select>
        </div>
        <div class="field">
          <label class="field__label">Religion</label>
          <select class="select"><option>Islam</option><option>Buddhist</option><option>Christian</option><option>Hindu</option></select>
        </div>
        <div class="field">
          <label class="field__label">Blood Type</label>
          <select class="select"><option>O+</option><option>A+</option><option>B+</option><option>AB+</option></select>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card__header"><h3 class="card__title">Contact &amp; Emergency</h3></div>
      <div class="card__body" style="display:grid; grid-template-columns:repeat(4,1fr); gap:14px">
        <div class="field" style="grid-column:span 2">
          <label class="field__label">Address</label>
          <input class="input" value="No. 12, Jalan Hijau 4, Taman Bukit, 43200 Cheras, Selangor" />
        </div>
        <div class="field">
          <label class="field__label">Phone</label>
          <input class="input mono" value="+60 12-345 6789" />
        </div>
        <div class="field">
          <label class="field__label">Email</label>
          <input class="input" value="aminah.h@gmail.com" />
        </div>
        <div class="field">
          <label class="field__label">Next of Kin</label>
          <input class="input" value="Hassan bin Ali (husband)" />
        </div>
        <div class="field">
          <label class="field__label">NOK Phone</label>
          <input class="input mono" value="+60 19-876 5432" />
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card__header"><h3 class="card__title">Medical &amp; Insurance</h3></div>
      <div class="card__body" style="display:grid; grid-template-columns:repeat(4,1fr); gap:14px">
        <div class="field" style="grid-column:span 2">
          <label class="field__label">Known Allergies</label>
          <input class="input" value="Penicillin, peanuts" />
          <span class="field__hint">Triggers banner on EMR</span>
        </div>
        <div class="field" style="grid-column:span 2">
          <label class="field__label">Chronic Conditions</label>
          <input class="input" value="Hypertension, Type 2 DM" />
        </div>
        <div class="field">
          <label class="field__label">Insurance / Panel</label>
          <select class="select"><option>AIA Panel</option><option>Self-pay</option><option>SOCSO</option><option>Allianz</option></select>
        </div>
        <div class="field">
          <label class="field__label">Policy No.</label>
          <input class="input mono" placeholder="Optional" />
        </div>
      </div>
    </div>

    <div class="row" style="justify-content:flex-end; gap:8px">
      <Btn variant="ghost">Cancel</Btn>
      <Btn variant="secondary">Save Draft</Btn>
      <Btn variant="primary">Register &amp; Add to Queue →</Btn>
    </div>
  </div>
</template>
