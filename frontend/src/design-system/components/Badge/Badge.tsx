import styles from './Badge.module.css'

interface BadgeProps {
  variant?: 'neutral' | 'success' | 'danger' | 'primary'
  label: string
}

export function Badge({ variant = 'neutral', label }: BadgeProps) {
  return <span className={[styles.badge, styles[variant]].join(' ')}>{label}</span>
}
