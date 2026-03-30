import type { ChangeEvent } from 'react'
import styles from './Input.module.css'

interface InputProps {
  id?: string
  name?: string
  type?: 'text' | 'email' | 'password' | 'number'
  value: string
  onChange: (e: ChangeEvent<HTMLInputElement>) => void
  placeholder?: string
  disabled?: boolean
  error?: boolean
  'aria-describedby'?: string
}

export function Input({ id, name, type = 'text', value, onChange, placeholder, disabled = false, error = false, 'aria-describedby': ariaDescribedby }: InputProps) {
  return (
    <input
      id={id}
      name={name}
      type={type}
      value={value}
      onChange={onChange}
      placeholder={placeholder}
      disabled={disabled}
      className={[styles.input, error ? styles.error : ''].join(' ')}
      aria-invalid={error ? 'true' : undefined}
      aria-describedby={ariaDescribedby}
    />
  )
}
