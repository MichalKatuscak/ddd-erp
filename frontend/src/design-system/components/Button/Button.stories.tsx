import type { Meta, StoryObj } from '@storybook/react'
import { Button } from './Button'

const meta: Meta<typeof Button> = {
  component: Button,
  title: 'Design System/Button',
}
export default meta
type Story = StoryObj<typeof Button>

export const Primary: Story = { args: { variant: 'primary', children: 'Přidat zákazníka' } }
export const Secondary: Story = { args: { variant: 'secondary', children: 'Zrušit' } }
export const Ghost: Story = { args: { variant: 'ghost', children: 'Více' } }
export const Danger: Story = { args: { variant: 'danger', children: 'Deaktivovat' } }
export const Loading: Story = { args: { variant: 'primary', children: 'Ukládám...', loading: true } }
export const Disabled: Story = { args: { variant: 'primary', children: 'Přidat', disabled: true } }
export const Small: Story = { args: { variant: 'secondary', size: 'sm', children: 'Malé' } }
export const Large: Story = { args: { variant: 'primary', size: 'lg', children: 'Velké' } }
