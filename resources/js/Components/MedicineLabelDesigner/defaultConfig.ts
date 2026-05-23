import type { LabelConfig, ElementConfig } from './types'

const el = (
  overrides: Partial<ElementConfig> & { x: number; y: number; w: number }
): ElementConfig => ({
  visible:         true,
  h:               0,
  columnSpan:      1,
  fontSize:        12,
  fontWeight:      'font-normal',
  textColor:       '#000000',
  backgroundColor: 'transparent',
  border:          false,
  borderWidth:     '1',
  borderStyle:     'solid',
  borderColor:     '#000000',
  cornerRadius:    'rounded-none',
  padding:         'p-1',
  ...overrides,
})

export const defaultConfig: LabelConfig = {
  backgroundColor: 'transparent',
  labelWidth:      148,
  labelHeight:     100,
  globalFontSize:  'text-xs',
  globalCorners:   'rounded-none',
  columnGap:       'gap-2',
  padding:         'p-2',
  showDivider:     false,
  freeTexts: {
    free_text:  '',
    text_nama:  'Nama:',
    text_tarikh: 'Tarikh:',
    text_ubat:  'Nama Ubat / Kegunaan:',
    text_footer: 'UBAT TERKAWAL / CONTROLLED MEDICINE',
  },
  elementOrder: [
    'logo', 'address', 'phone', 'email',
    'text_nama', 'patient',
    'text_tarikh', 'date',
    'text_ubat', 'medicine',
    'dosage', 'frequency', 'food_timing',
    'notes',
    'text_footer',
    'free_text',
  ],
  elements: {
    // ── Clinic header ──────────────────────────────────────────────────────
    logo:        el({ x: 1,  y: 1,  w: 21, visible: true,  padding: 'p-1' }),
    address:     el({ x: 23, y: 1,  w: 53, visible: true,  fontSize: 11 }),
    phone:       el({ x: 23, y: 14, w: 30, visible: true,  fontSize: 11 }),
    email:       el({ x: 55, y: 14, w: 43, visible: true,  fontSize: 11 }),

    // ── Patient row ────────────────────────────────────────────────────────
    text_nama:   el({ x: 1,  y: 28, w: 8,  fontSize: 12, fontWeight: 'font-normal' }),
    patient:     el({ x: 10, y: 28, w: 40, fontSize: 12, border: true, borderWidth: '1', borderStyle: 'dashed' }),

    text_tarikh: el({ x: 52, y: 28, w: 10, fontSize: 12, fontWeight: 'font-normal' }),
    date:        el({ x: 63, y: 28, w: 35, fontSize: 12, border: true, borderWidth: '1', borderStyle: 'dashed' }),

    // ── Medicine ───────────────────────────────────────────────────────────
    text_ubat:   el({ x: 1,  y: 38, w: 30, fontSize: 12, fontWeight: 'font-medium' }),
    medicine:    el({ x: 1,  y: 44, w: 96, fontSize: 13, fontWeight: 'font-semibold', border: true, borderWidth: '1' }),

    // ── Dosage row ─────────────────────────────────────────────────────────
    dosage:      el({ x: 1,  y: 57, w: 25, fontSize: 12, border: true, borderWidth: '1' }),
    frequency:   el({ x: 28, y: 57, w: 35, fontSize: 12, border: true, borderWidth: '1' }),
    food_timing: el({ x: 65, y: 57, w: 33, fontSize: 12, border: true, borderWidth: '1' }),

    // ── Notes ──────────────────────────────────────────────────────────────
    notes:       el({ x: 1,  y: 70, w: 96, visible: true, fontSize: 11, border: true, borderWidth: '1', borderStyle: 'dashed' }),

    // ── Footer ─────────────────────────────────────────────────────────────
    text_footer: el({
      x: 1, y: 88, w: 96,
      fontSize: 13, fontWeight: 'font-bold',
      textColor: '#ffffff', backgroundColor: '#000000',
      padding: 'p-2',
    }),

    // ── Hidden ─────────────────────────────────────────────────────────────
    free_text:   el({ x: 1, y: 80, w: 50, visible: false }),
  },
}
