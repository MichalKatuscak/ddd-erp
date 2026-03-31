import { apiGet, apiPost, apiPut } from './client'

// Auth
export interface LoginResult {
  access_token: string
  refresh_token: string
  expires_in: number
}

export interface CurrentUser {
  id: string
  email: string
  first_name: string
  last_name: string
  permissions: string[]
}

// Users
export interface UserListItem {
  id: string
  email: string
  name: string
  role_ids: string[]
  active: boolean
}

export interface UserDetail {
  id: string
  email: string
  first_name: string
  last_name: string
  role_ids: string[]
  active: boolean
  created_at: string
}

// Roles
export interface RoleListItem {
  id: string
  name: string
  permissions: string[]
}

export interface RoleDetail {
  id: string
  name: string
  permissions: string[]
}

export const identityApi = {
  // Auth
  login: (email: string, password: string) =>
    apiPost<LoginResult>('/api/identity/commands/login', { email, password }),

  logout: (refreshToken: string) =>
    apiPost<void>('/api/identity/commands/logout', { refresh_token: refreshToken }),

  me: () =>
    apiGet<CurrentUser>('/api/identity/me'),

  // Users
  getUsers: () =>
    apiGet<UserListItem[]>('/api/identity/users'),

  getUser: (id: string) =>
    apiGet<UserDetail>(`/api/identity/users/${id}`),

  registerUser: (data: { email: string; password: string; firstName: string; lastName: string }) =>
    apiPost<{ id: string }>('/api/identity/users/commands/register-user', {
      email: data.email,
      password: data.password,
      first_name: data.firstName,
      last_name: data.lastName,
    }),

  updateUser: (id: string, data: { email: string; firstName: string; lastName: string }) =>
    apiPut<void>(`/api/identity/users/commands/update-user/${id}`, {
      email: data.email,
      first_name: data.firstName,
      last_name: data.lastName,
    }),

  deactivateUser: (id: string) =>
    apiPost<void>(`/api/identity/users/commands/deactivate-user/${id}`, {}),

  assignRoles: (userId: string, roleIds: string[]) =>
    apiPost<void>(`/api/identity/users/commands/assign-roles/${userId}`, { role_ids: roleIds }),

  // Roles
  getRoles: () =>
    apiGet<RoleListItem[]>('/api/identity/roles'),

  getRole: (id: string) =>
    apiGet<RoleDetail>(`/api/identity/roles/${id}`),

  createRole: (name: string, permissions: string[]) =>
    apiPost<{ id: string }>('/api/identity/roles/commands/create-role', { name, permissions }),

  updateRolePermissions: (id: string, permissions: string[]) =>
    apiPut<void>(`/api/identity/roles/commands/update-role-permissions/${id}`, { permissions }),
}
