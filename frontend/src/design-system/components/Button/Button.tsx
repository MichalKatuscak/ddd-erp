import styles from './Button.module.css'

interface ButtonProps {
  variant?: 'primary' | 'secondary' | 'ghost' | 'danger'
  size?: 'sm' | 'md' | 'lg'
  loading?: boolean
  disabled?: boolean
  type?: 'button' | 'submit' | 'reset'
  onClick?: () => void
  children: React.ReactNode
}

export function Button({
  variant = 'primary',
  size = 'md',
  loading = false,
  disabled = false,
  type = 'button',
  onClick,
  children,
}: ButtonProps) {
  return (
    <button
      type={type}
      className={[
        styles.button,
        styles[variant],
        styles[size],
        (disabled || loading) ? styles.disabled : '',
      ].join(' ')}
      disabled={disabled || loading}
      onClick={onClick}
    >
      {loading ? <span className={styles.spinner} aria-hidden="true" /> : null}
      {children}
    </button>
  )
}
