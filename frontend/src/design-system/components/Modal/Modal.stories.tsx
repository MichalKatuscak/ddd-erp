import { useState } from 'react'
import type { Meta, StoryObj } from '@storybook/react'
import { Modal } from './Modal'
import { Button } from '../Button/Button'

const meta: Meta<typeof Modal> = { component: Modal, title: 'Design System/Modal' }
export default meta
type Story = StoryObj<typeof Modal>

export const Default: Story = {
  render: () => {
    const [open, setOpen] = useState(false)
    return (
      <>
        <Button onClick={() => setOpen(true)}>Otevřít modal</Button>
        <Modal open={open} onClose={() => setOpen(false)} title="Přidat zákazníka">
          <p>Obsah modalu zde.</p>
        </Modal>
      </>
    )
  },
}
