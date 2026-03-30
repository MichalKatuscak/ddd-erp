import type { Meta, StoryObj } from '@storybook/react'
import { FormField } from './FormField'
import { Input } from '../Input/Input'

const meta: Meta<typeof FormField> = { component: FormField, title: 'Design System/FormField' }
export default meta
type Story = StoryObj<typeof FormField>

export const Default: Story = {
  args: { label: 'E-mail', htmlFor: 'email' },
  render: (args) => <FormField {...args}><Input id="email" value="" onChange={() => {}} placeholder="jan@firma.cz" /></FormField>,
}
export const WithError: Story = {
  args: { label: 'E-mail', error: 'Nesprávné přihlašovací údaje' },
  render: (args) => <FormField {...args}><Input value="spatny@email" onChange={() => {}} error /></FormField>,
}
export const WithHint: Story = {
  args: { label: 'Heslo', hint: 'Minimálně 8 znaků' },
  render: (args) => <FormField {...args}><Input type="password" value="" onChange={() => {}} /></FormField>,
}
