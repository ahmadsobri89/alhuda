export const mmToPx = (mm: number): number => mm * 3.7795

export const isMalaysianMobile = (phone: string): boolean => {
  const cleaned = phone.replace(/[\s\-\(\)]/g, '')
  return /^(011|012|013|014|016|017|018|019|601[1-9])/.test(cleaned)
}

export const formatDateMalay = (dateStr: string): string => {
  const months = ['Jan', 'Feb', 'Mac', 'Apr', 'Mei', 'Jun',
                  'Jul', 'Ogos', 'Sep', 'Okt', 'Nov', 'Dis']
  const d = new Date(dateStr)
  return `${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()}`
}

export const foodTimingLabel = (val: string): string => {
  const map: Record<string, string> = {
    before: 'Sebelum Makan',
    after: 'Selepas Makan',
    with: 'Bersama Makan',
    as_needed: 'Bila Perlu',
  }
  return map[val] ?? val
}
