import { apiGet, apiPost, apiPut } from './client'

export interface CustomerListItem {
  id: string
  first_name: string
  last_name: string
  email: string
  phone: string
  city: string
}

export interface CustomerDetail {
  id: string
  first_name: string
  last_name: string
  email: string
  phone: string
  street: string
  city: string
  zip: string
  country: string
}

export const crmApi = {
  getCustomers: () =>
    apiGet<CustomerListItem[]>('/api/crm/contacts/customers'),

  getCustomer: (id: string) =>
    apiGet<CustomerDetail>(`/api/crm/contacts/customers/${id}`),

  registerCustomer: (data: {
    firstName: string; lastName: string; email: string
    phone?: string; street?: string; city?: string; zip?: string; country?: string
  }) =>
    apiPost<{ id: string }>('/api/crm/contacts/customers/commands/register-customer', {
      first_name: data.firstName,
      last_name: data.lastName,
      email: data.email,
      phone: data.phone ?? '',
      street: data.street ?? '',
      city: data.city ?? '',
      zip: data.zip ?? '',
      country: data.country ?? '',
    }),

  updateCustomer: (id: string, data: {
    firstName: string; lastName: string; email: string
    phone?: string; street?: string; city?: string; zip?: string; country?: string
  }) =>
    apiPut<void>(`/api/crm/contacts/customers/commands/update-customer/${id}`, {
      first_name: data.firstName,
      last_name: data.lastName,
      email: data.email,
      phone: data.phone ?? '',
      street: data.street ?? '',
      city: data.city ?? '',
      zip: data.zip ?? '',
      country: data.country ?? '',
    }),
}
