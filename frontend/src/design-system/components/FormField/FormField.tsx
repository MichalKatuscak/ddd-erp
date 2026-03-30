import styles from './FormField.module.css'

interface FormFieldProps {
  label: string
  htmlFor?: string
  error?: string
  hint?: string
  children: React.ReactNode
}

export function FormField({ label, htmlFor, error, hint, children }: FormFieldProps) {
  return (
    <div className={styles.field}>
      <label className={styles.label} htmlFor={htmlFor}>{label}</label>
      {children}
      {error ? <p className={styles.error}>{error}</p> : null}
      {hint && !error ? <p className={styles.hint}>{hint}</p> : null}
    </div>
  )
}
