import { apiGet, apiPost, apiPut } from './client'

// ---- Shared ----

export type WorkerRole = 'designer' | 'frontend' | 'backend' | 'pm' | 'qa' | 'devops'

export const WORKER_ROLE_LABELS: Record<WorkerRole, string> = {
  designer: 'Designér',
  frontend: 'Frontend',
  backend: 'Backend',
  pm: 'PM',
  qa: 'QA',
  devops: 'DevOps',
}

// ---- Orders ----

export interface OrderListItem {
  id: string
  name: string
  client_name: string
  status: string
  phase_count: number
}

export interface AssignmentDTO {
  user_id: string
  allocation_percent: number
}

export interface PhaseDetail {
  id: string
  name: string
  required_role: WorkerRole
  required_skills: string[]
  headcount: number
  duration_days: number
  depends_on: string[]
  start_date: string | null
  end_date: string | null
  assignments: AssignmentDTO[]
}

export interface OrderDetail {
  id: string
  name: string
  client_name: string
  planned_start_date: string
  status: string
  phases: PhaseDetail[]
}

// ---- Workers ----

export interface WorkerListItem {
  id: string
  name: string
  primary_role: WorkerRole
  skills: string[]
  current_allocation_percent: number
}

export interface WorkerAllocation {
  order_id: string
  order_name: string
  phase_id: string
  phase_name: string
  allocation_percent: number
  start_date: string
  end_date: string
}

export interface WorkerDetail {
  id: string
  name: string
  primary_role: WorkerRole
  skills: string[]
  allocations: WorkerAllocation[]
}

// ---- Suggestions ----

export interface CandidateDTO {
  id: string
  name: string
  primary_role: WorkerRole
  skills: string[]
  available_percent: number
}

// ---- API object ----

export const planningApi = {
  // Orders
  getOrders: () =>
    apiGet<OrderListItem[]>('/api/planning/orders'),

  getOrder: (id: string) =>
    apiGet<OrderDetail>(`/api/planning/orders/${id}`),

  createOrder: (data: { name: string; clientName: string; plannedStartDate: string }) =>
    apiPost<{ id: string }>('/api/planning/orders', {
      name: data.name,
      client_name: data.clientName,
      planned_start_date: data.plannedStartDate,
    }),

  addPhase: (orderId: string, data: {
    name: string
    requiredRole: WorkerRole
    requiredSkills: string[]
    headcount: number
    durationDays: number
    dependsOn: string[]
  }) =>
    apiPost<{ id: string }>(`/api/planning/orders/${orderId}/phases`, {
      name: data.name,
      required_role: data.requiredRole,
      required_skills: data.requiredSkills,
      headcount: data.headcount,
      duration_days: data.durationDays,
      depends_on: data.dependsOn,
    }),

  scheduleOrder: (orderId: string) =>
    apiPost<void>(`/api/planning/orders/${orderId}/commands/schedule`, {}),

  advanceStatus: (orderId: string) =>
    apiPost<void>(`/api/planning/orders/${orderId}/commands/advance-status`, {}),

  getSuggestions: (orderId: string, phaseId: string) =>
    apiGet<CandidateDTO[]>(`/api/planning/orders/${orderId}/phases/${phaseId}/suggestions`),

  assignWorker: (orderId: string, phaseId: string, data: { userId: string; allocationPercent: number }) =>
    apiPost<void>(`/api/planning/orders/${orderId}/phases/${phaseId}/assignments`, {
      worker_id: data.userId,
      allocation_percent: data.allocationPercent,
    }),

  // Workers
  getWorkers: () =>
    apiGet<WorkerListItem[]>('/api/planning/workers'),

  getWorker: (id: string) =>
    apiGet<WorkerDetail>(`/api/planning/workers/${id}`),

  registerWorker: (data: { userId: string; primaryRole: WorkerRole; skills: string[] }) =>
    apiPost<void>('/api/planning/workers', {
      user_id: data.userId,
      primary_role: data.primaryRole,
      skills: data.skills,
    }),

  updateWorkerSkills: (id: string, data: { primaryRole: WorkerRole; skills: string[] }) =>
    apiPut<void>(`/api/planning/workers/${id}/skills`, {
      primary_role: data.primaryRole,
      skills: data.skills,
    }),
}
