# MedicineLabelDesigner — Nota Integrasi

## Status Semasa
Panel ini **standalone** — tidak bergantung pada mana-mana modul lain.
Sesuai untuk demo / proposal.

---

## Cara Kerja Sekarang

Data contoh dimasukkan secara manual dalam `Settings.vue`:

```js
// resources/js/Pages/Settings.vue (~baris 25)
const labelPreviewData = computed(() => ({
  logo:           props.clinic.logo_url ?? '',
  medicine_name:  'Amoxicillin 500mg',
  medicine_usage: 'Rawatan Jangkitan Bakteria',
  dosage:         '1 kapsul',
  frequency:      '3 kali sehari',
  food_timing:    'after',
  patient_name:   'Ahmad bin Abu Bakar',
  dispense_date:  new Date().toISOString().slice(0, 10),
  // ... maklumat klinik dari props.clinic
}))
```

---

## Bila Nak Integrate dengan Modul Farmasi

Hanya **satu tempat** yang perlu diubah — `labelPreviewData` dalam `Settings.vue`.

Ganti data contoh dengan data prescription sebenar dari Inertia props:

```js
// Contoh — terima rx sebagai prop dari controller
const props = defineProps({
  // ... props sedia ada
  rx: { type: Object, default: null },
})

const labelPreviewData = computed(() => ({
  logo:           props.clinic.logo_url        ?? '',
  medicine_name:  props.rx?.medicine?.name     ?? '',
  medicine_usage: props.rx?.medicine?.usage    ?? '',
  dosage:         props.rx?.dosage             ?? '',
  frequency:      props.rx?.frequency          ?? '',
  food_timing:    props.rx?.food_timing        ?? '',
  patient_name:   props.rx?.patient?.name      ?? '',
  dispense_date:  props.rx?.dispensed_at       ?? '',
  notes:          props.rx?.notes              ?? '',
  address_line1:  props.clinic.address         ?? '',
  address_line2:  '',
  city:           props.clinic.city            ?? '',
  postcode:       props.clinic.postcode        ?? '',
  state:          props.clinic.state           ?? '',
  phone:          props.clinic.phone           ?? '',
  email:          props.clinic.email           ?? '',
}))
```

> Komponen `MedicineLabelDesigner.vue` **tidak perlu diubah langsung**.

---

## Struktur Fail

```
resources/js/Components/MedicineLabelDesigner/
  MedicineLabelDesigner.vue   ← komponen utama (terima data + config via props)
  SettingsPanel.vue           ← panel kiri — tetapan
  ElementList.vue             ← senarai elemen, drag & drop (Shopify Sortable)
  ElementAccordion.vue        ← tetapan per-elemen (saiz, warna, sempadan...)
  GlobalSettings.vue          ← tetapan global label
  LabelPreview.vue            ← canvas preview — boleh drag & resize elemen
  types.ts                    ← LabelConfig, ElementConfig, LabelData
  defaultConfig.ts            ← konfigurasi lalai semua elemen
  utils/
    labelUtils.ts             ← mmToPx, formatDateMalay, isMalaysianMobile...
  elements/
    LogoElement.vue
    MedicineElement.vue
    DosageElement.vue
    FrequencyElement.vue
    FoodTimingElement.vue
    PatientElement.vue
    DateElement.vue
    NotesElement.vue
    AddressElement.vue
    PhoneElement.vue
    EmailElement.vue
    FreeTextElement.vue
```

---

## Field Mapping (TODO bila integrate)

| Field dalam `LabelData`  | Source dari DB              |
|--------------------------|-----------------------------|
| `logo`                   | `clinics.logo_url`          |
| `medicine_name`          | `medicines.name`            |
| `medicine_usage`         | `medicines.usage`           |
| `dosage`                 | `prescriptions.dosage`      |
| `frequency`              | `prescriptions.frequency`   |
| `food_timing`            | `prescriptions.food_timing` |
| `patient_name`           | `patients.name`             |
| `dispense_date`          | `prescriptions.dispensed_at`|
| `notes`                  | `prescriptions.notes`       |
| `address_line1`          | `clinics.address`           |
| `city`                   | `clinics.city`              |
| `postcode`               | `clinics.postcode`          |
| `state`                  | `clinics.state`             |
| `phone`                  | `clinics.phone`             |
| `email`                  | `clinics.email`             |

---

## API Template (Save/Load)

Config label disimpan/dimuatkan melalui:

```
GET  /api/label-templates/{clinic_id}   ← load semasa mount
POST /api/label-templates               ← save bila tekan butang Simpan
     body: { clinic_id, config: LabelConfig }
```

> Endpoint ini **belum dibuat** di backend. Perlu buat route + controller + migration bila ready.
