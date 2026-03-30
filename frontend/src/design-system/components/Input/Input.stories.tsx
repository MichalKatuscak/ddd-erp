import type { Meta, StoryObj } from '@storybook/react'
import { Input } from './Input'

const meta: Meta<typeof Input> = { component: Input, title: 'Design System/Input' }
export default meta
type Story = StoryObj<typeof Input>

export const Default: Story = { args: { value: '', onChange: () => {}, placeholder: 'jan@firma.cz' } }
export const WithValue: Story = { args: { value: 'admin@erp.local', onChange: () => {} } }
export const ErrorState: Story = { args: { value: 'neplatny', onChange: () => {}, error: true } }
export const Disabled: Story = { args: { value: '', onChange: () => {}, disabled: true, placeholder: 'Deaktivováno' } }
export const Password: Story = { args: { value: '', onChange: () => {}, type: 'password', placeholder: '••••••••' } }
