import type { Meta, StoryObj } from '@storybook/react'
import { Table } from './Table'

const meta: Meta<typeof Table> = { component: Table, title: 'Design System/Table' }
export default meta
type Story = StoryObj<typeof Table>

const columns = [
  { key: 'name', header: 'Jméno' },
  { key: 'email', header: 'E-mail' },
  { key: 'city', header: 'Město' },
]

const data = [
  { name: 'Jan Novák', email: 'jan@firma.cz', city: 'Praha' },
  { name: 'Eva Svobodová', email: 'eva@firma.cz', city: 'Brno' },
]

export const Default: Story = { args: { columns, data } }
export const Loading: Story = { args: { columns, data: [], loading: true } }
export const Empty: Story = { args: { columns, data: [] } }
export const Clickable: Story = { args: { columns, data, onRowClick: (row) => alert(JSON.stringify(row)) } }
