export interface ElementConfig {
  visible: boolean
  x: number       // % of label width (0–100)
  y: number       // % of label height (0–100)
  w: number       // % of label width (5–100)
  h: number       // % of label height (0 = auto/content)
  shape?: 'circle' | 'rect'
  columnSpan: 1 | 2 | 3
  fontSize: number  // px, 4–100
  fontWeight: 'font-normal' | 'font-medium' | 'font-semibold' | 'font-bold'
  textColor: string        // hex
  backgroundColor: string  // 'transparent' or hex
  border: boolean
  borderWidth: '1' | '2' | '4' | '8'
  borderStyle: 'solid' | 'dashed' | 'dotted'
  borderColor: string
  cornerRadius: 'rounded-none' | 'rounded-md' | 'rounded-xl' | 'rounded-full'
  padding: 'p-0' | 'p-1' | 'p-2' | 'p-3'
}

export interface LabelConfig {
  backgroundColor: string
  labelWidth: number
  labelHeight: number
  globalFontSize: 'text-xs' | 'text-sm' | 'text-base'
  globalCorners: 'rounded-none' | 'rounded-md' | 'rounded-xl'
  columnGap: 'gap-1' | 'gap-2' | 'gap-3'
  padding: 'p-2' | 'p-3' | 'p-4'
  showDivider: boolean
  freeTexts: Record<string, string>   // keyed by element id
  elementOrder: string[]
  elements: Record<string, ElementConfig>
}

export interface LabelData {
  logo: string                // TODO: actual field
  medicine_name: string       // TODO: actual field
  medicine_usage: string      // TODO: actual field
  dosage: string              // TODO: actual field
  frequency: string           // TODO: actual field
  food_timing: string         // TODO: actual field
  patient_name: string        // TODO: actual field
  dispense_date: string       // TODO: actual field
  notes: string               // TODO: actual field
  address_line1: string       // TODO: actual field
  address_line2: string       // TODO: actual field
  city: string                // TODO: actual field
  postcode: string            // TODO: actual field
  state: string               // TODO: actual field
  phone: string               // TODO: actual field
  email: string               // TODO: actual field
}
