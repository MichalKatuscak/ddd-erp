import type { Meta, StoryObj } from '@storybook/react'
import { PageLayout } from './PageLayout'

const meta: Meta<typeof PageLayout> = { component: PageLayout, title: 'Design System/PageLayout' }
export default meta
type Story = StoryObj<typeof PageLayout>

export const Default: Story = {
  args: {
    sidebar: (
      <nav>
        <strong>ERP</strong>
        <ul style={{ listStyle: 'none', marginTop: 24 }}>
          <li>Zákazníci</li>
          <li>Uživatelé</li>
          <li>Role</li>
        </ul>
      </nav>
    ),
    children: <div><h1>Obsah stránky</h1><p>Hlavní obsah zde.</p></div>,
  },
}
