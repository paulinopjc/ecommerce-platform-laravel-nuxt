export const USER_ROLE = {
  CUSTOMER:  'customer',
  WAREHOUSE: 'warehouse',
  MANAGER:   'manager',
  ADMIN:     'admin',
} as const

export type UserRole = typeof USER_ROLE[keyof typeof USER_ROLE]

// All roles with management access -- used for isManager guard
export const MANAGER_AND_ABOVE: UserRole[] = [USER_ROLE.ADMIN, USER_ROLE.MANAGER]