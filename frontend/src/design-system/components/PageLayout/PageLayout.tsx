import styles from './PageLayout.module.css'

interface PageLayoutProps {
  sidebar: React.ReactNode
  children: React.ReactNode
}

export function PageLayout({ sidebar, children }: PageLayoutProps) {
  return (
    <div className={styles.layout}>
      <aside className={styles.sidebar}>{sidebar}</aside>
      <main className={styles.main}>{children}</main>
    </div>
  )
}
