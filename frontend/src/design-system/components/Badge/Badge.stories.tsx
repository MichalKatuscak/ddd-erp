import type { Meta, StoryObj } from '@storybook/react'
import { Badge } from './Badge'

const meta: Meta<typeof Badge> = { component: Badge, title: 'Design System/Badge' }
export default meta
type Story = StoryObj<typeof Badge>

export const Neutral: Story = { args: { variant: 'neutral', label: 'Neaktivní' } }
export const Success: Story = { args: { variant: 'success', label: 'Aktivní' } }
export const Danger: Story = { args: { variant: 'danger', label: 'Deaktivován' } }
export const Primary: Story = { args: { variant: 'primary', label: 'super-admin' } }
