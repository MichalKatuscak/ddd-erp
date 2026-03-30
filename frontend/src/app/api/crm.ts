import { apiGet, apiPost, apiPut } from './client'

export interface CustomerListItem {
  id: string
  email: string
  full_name: string
  registered_at: string
}

export interface CustomerDetail {
  id: string
  email: string
  first_name: string
  last_name: string
  registered_at: string
}

export const crmApi = {
  getCustomers: () =>
    apiGet<CustomerListItem[]>('/api/crm/contacts/customers'),

  getCustomer: (id: string) =>
    apiGet<CustomerDetail>(`/api/crm/contacts/customers/${id}`),

  registerCustomer: (data: { firstName: string; lastName: string; email: string }) =>
    apiPost<{ id: string }>('/api/crm/contacts/customers/commands/register-customer', {
      first_name: data.firstName,
      last_name: data.lastName,
      email: data.email,
    }),

  updateCustomer: (id: string, data: { firstName: string; lastName: string; email: string }) =>
    apiPut<void>(`/api/crm/contacts/customers/commands/update-customer/${id}`, {
      first_name: data.firstName,
      last_name: data.lastName,
      email: data.email,
    }),
}
