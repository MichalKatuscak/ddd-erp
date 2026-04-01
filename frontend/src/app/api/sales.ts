import { apiGet, apiPost, apiPut } from './client'

export type SalesRole = 'designer' | 'frontend' | 'backend' | 'pm' | 'qa' | 'devops'

export const SALES_ROLE_LABELS: Record<SalesRole, string> = {
  designer: 'Designér',
  frontend: 'Frontend',
  backend: 'Backend',
  pm: 'PM',
  qa: 'QA',
  devops: 'DevOps',
}

export type InquiryStatus = 'new' | 'in_progress' | 'quoted' | 'won' | 'lost' | 'cancelled'

export const INQUIRY_STATUS_LABELS: Record<InquiryStatus, string> = {
  new: 'Nová',
  in_progress: 'Zpracovává se',
  quoted: 'Nabídnuto',
  won: 'Vyhráno',
  lost: 'Prohráno',
  cancelled: 'Zrušeno',
}

export type QuoteStatus = 'draft' | 'sent' | 'accepted' | 'rejected'

export const QUOTE_STATUS_LABELS: Record<QuoteStatus, string> = {
  draft: 'Rozpracována',
  sent: 'Odeslaná',
  accepted: 'Přijatá',
  rejected: 'Odmítnuta',
}

export interface RequiredRoleDTO {
  role: SalesRole
  skills: string[]
}

export interface AttachmentDTO {
  id: string
  path: string
  mimeType: string
  originalName: string
}

export interface InquiryListItem {
  id: string
  customer_name: string
  description: string
  status: InquiryStatus
  requested_deadline: string | null
  created_at: string
}

export interface InquiryDetail {
  id: string
  customer_id: string | null
  customer_name: string
  contact_email: string
  description: string
  requested_deadline: string | null
  required_roles: RequiredRoleDTO[]
  attachments: AttachmentDTO[]
  status: InquiryStatus
  created_at: string
}

export interface QuotePhaseDetail {
  id: string
  name: string
  required_role: SalesRole
  duration_days: number
  daily_rate_amount: number
  daily_rate_currency: string
  subtotal_amount: number
  subtotal_currency: string
}

export interface QuoteDetail {
  id: string
  inquiry_id: string
  valid_until: string
  status: QuoteStatus
  pdf_path: string | null
  notes: string
  phases: QuotePhaseDetail[]
  total_price_amount: number
  total_price_currency: string
}

export const salesApi = {
  getInquiries: (status?: string) =>
    apiGet<InquiryListItem[]>(`/api/sales/inquiries${status ? `?status=${status}` : ''}`),

  getInquiry: (id: string) =>
    apiGet<InquiryDetail>(`/api/sales/inquiries/${id}`),

  createInquiry: (data: {
    customer_name: string
    contact_email: string
    description: string
    customer_id?: string
    requested_deadline?: string
    required_roles: RequiredRoleDTO[]
  }) => apiPost<{ id: string }>('/api/sales/inquiries', data),

  updateInquiry: (id: string, data: {
    customer_name: string
    contact_email: string
    description: string
    customer_id?: string
    requested_deadline?: string
    required_roles: RequiredRoleDTO[]
  }) => apiPut<void>(`/api/sales/inquiries/${id}`, data),

  advanceInquiryStatus: (id: string, targetStatus?: string) =>
    apiPost<void>(`/api/sales/inquiries/${id}/commands/advance-status`, { target_status: targetStatus ?? null }),

  uploadAttachment: async (inquiryId: string, file: File): Promise<{ path: string }> => {
    const { useAuthStore } = await import('../auth/authStore')
    const store = useAuthStore.getState()
    const formData = new FormData()
    formData.append('file', file)
    const res = await fetch(`/api/sales/inquiries/${inquiryId}/attachments`, {
      method: 'POST',
      headers: store.accessToken ? { Authorization: `Bearer ${store.accessToken}` } : {},
      body: formData,
    })
    if (!res.ok) throw new Error(`HTTP ${res.status}`)
    return res.json()
  },

  getAttachmentUrl: (path: string) => `/api/sales/attachments/${path}`,

  getQuote: (inquiryId: string, quoteId: string) =>
    apiGet<QuoteDetail>(`/api/sales/inquiries/${inquiryId}/quotes/${quoteId}`),

  createQuote: (inquiryId: string, data: { valid_until: string; notes: string }) =>
    apiPost<{ id: string }>(`/api/sales/inquiries/${inquiryId}/quotes`, data),

  addQuotePhase: (inquiryId: string, quoteId: string, data: {
    name: string; required_role: SalesRole; duration_days: number
    daily_rate_amount: number; daily_rate_currency: string
  }) => apiPost<{ id: string }>(`/api/sales/inquiries/${inquiryId}/quotes/${quoteId}/phases`, data),

  updateQuotePhase: (inquiryId: string, quoteId: string, phaseId: string, data: {
    name: string; required_role: SalesRole; duration_days: number
    daily_rate_amount: number; daily_rate_currency: string
  }) => apiPut<void>(`/api/sales/inquiries/${inquiryId}/quotes/${quoteId}/phases/${phaseId}`, data),

  sendQuote: (inquiryId: string, quoteId: string) =>
    apiPost<void>(`/api/sales/inquiries/${inquiryId}/quotes/${quoteId}/commands/send`, {}),

  acceptQuote: (inquiryId: string, quoteId: string) =>
    apiPost<void>(`/api/sales/inquiries/${inquiryId}/quotes/${quoteId}/commands/accept`, {}),

  rejectQuote: (inquiryId: string, quoteId: string) =>
    apiPost<void>(`/api/sales/inquiries/${inquiryId}/quotes/${quoteId}/commands/reject`, {}),

  exportQuotePdf: (inquiryId: string, quoteId: string) =>
    apiPost<void>(`/api/sales/inquiries/${inquiryId}/quotes/${quoteId}/commands/export-pdf`, {}),

  getQuotePdfUrl: (inquiryId: string, quoteId: string) =>
    `/api/sales/inquiries/${inquiryId}/quotes/${quoteId}/pdf`,
}
