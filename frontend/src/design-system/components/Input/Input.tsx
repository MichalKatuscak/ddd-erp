import styles from './Input.module.css'

interface InputProps {
  id?: string
  name?: string
  type?: 'text' | 'email' | 'password' | 'number'
  value: string
  onChange: (e: React.ChangeEvent<HTMLInputElement>) => void
  placeholder?: string
  disabled?: boolean
  error?: boolean
}

export function Input({ id, name, type = 'text', value, onChange, placeholder, disabled = false, error = false }: InputProps) {
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
    />
  )
}
