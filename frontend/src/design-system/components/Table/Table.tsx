import styles from './Table.module.css'

export interface Column<T> {
  key: string
  header: string
  render?: (row: T) => React.ReactNode
}

interface TableProps<T> {
  columns: Column<T>[]
  data: T[]
  loading?: boolean
  onRowClick?: (row: T) => void
}

const SKELETON_ROWS = 5

export function Table<T extends Record<string, unknown>>({
  columns,
  data,
  loading = false,
  onRowClick,
}: TableProps<T>) {
  return (
    <div className={styles.wrapper}>
      <table className={styles.table}>
        <thead>
          <tr>
            {columns.map(col => (
              <th key={col.key} className={styles.th}>{col.header}</th>
            ))}
          </tr>
        </thead>
        <tbody>
          {loading
            ? Array.from({ length: SKELETON_ROWS }, (_, i) => (
                <tr key={i}>
                  {columns.map(col => (
                    <td key={col.key} className={styles.td}>
                      <div className={styles.skeleton} />
                    </td>
                  ))}
                </tr>
              ))
            : data.map((row, i) => (
                <tr
                  key={i}
                  className={[styles.row, onRowClick ? styles.clickable : ''].join(' ')}
                  onClick={() => onRowClick?.(row)}
                >
                  {columns.map(col => (
                    <td key={col.key} className={styles.td}>
                      {col.render ? col.render(row) : String(row[col.key] ?? '')}
                    </td>
                  ))}
                </tr>
              ))
          }
        </tbody>
      </table>
    </div>
  )
}
