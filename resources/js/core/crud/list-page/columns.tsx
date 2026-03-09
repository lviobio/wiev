import AppDateTime from '@/components/AppDateTime.vue'
import { MoreVertical24Regular } from '@vicons/fluent'
import { NA, NButton, NDropdown, NFlex, NIcon, NPopconfirm } from 'naive-ui'
import { h } from 'vue'
import type {
  ActionDef,
  ActionGroupDef,
  ActionsColumnCallback,
  ActionsColumnDef,
  Column,
  DeleteActionDef,
  ListComposables,
  ListPageColumn,
  OpenActionDef,
} from './types'
import { LIST_PAGE_ACTIONS_SYMBOL } from './types'

// ── defineColumns ───────────────────────────────────────────────

/**
 * Narrowed key type: shows autocomplete suggestions from `T`'s keys
 * while still accepting arbitrary strings (e.g. `'actions'`).
 *
 * The `(string & {})` trick prevents TypeScript from collapsing
 * the union into plain `string`, preserving literal suggestions.
 */
type TypedColumnKey<T> = Extract<keyof T, string> | (string & {})

/** A base column definition with narrowed `key` for autocomplete. */
type TypedBaseColumn<T> = Omit<Column<T>, 'key'> & {
  key: TypedColumnKey<T>
}

/** Items accepted by `defineColumns` — plain objects get key autocomplete. */
type DefineColumnsItem<T> = TypedBaseColumn<T> | ListPageColumn<T>

/**
 * Identity helper that provides autocomplete for `key` in plain column objects.
 *
 * @example
 * ```ts
 * const columns = defineColumns<Post>([
 *   linkColumn('id', { to: (row) => ({ ... }), windowed: true }),
 *   { title: 'Title', key: 'title', sorter: true },        // ← autocomplete for key
 *   { title: 'Content', key: 'content', ellipsis: true },   // ← autocomplete for key
 *   dateColumn('created_at', { width: 200 }),
 * ])
 * ```
 */
export function defineColumns<T extends Record<string, any>>(
  columns: DefineColumnsItem<T>[],
): ListPageColumn<T>[] {
  return columns as ListPageColumn<T>[]
}

// ── Action definition helpers ───────────────────────────────────

/**
 * Define an "Open" action button for the actions column.
 *
 * @example
 * ```ts
 * openAction((row) => ({ name: 'posts.show', params: { id: row.id } }))
 * ```
 */
export function openAction<T>(
  to: (row: T) => any,
  options?: {
    label?: string
    windowed?: boolean | ((row: T) => { title: string })
  },
): OpenActionDef<T> {
  return {
    type: 'open',
    to,
    label: options?.label,
    windowed: options?.windowed,
  }
}

/**
 * Define a "Delete" action button with confirmation for the actions column.
 * After successful deletion, the list is automatically reloaded.
 *
 * @example
 * ```ts
 * deleteAction((row) => repository.delete(row.id), {
 *   confirm: 'Delete this post?',
 *   success: (row) => `Post ${row.id} deleted successfully`,
 * })
 * ```
 */
export function deleteAction<T>(
  handler: (row: T) => Promise<unknown>,
  options?: {
    confirm?: string | ((row: T) => string)
    success?: string | ((row: T) => string)
    label?: string
  },
): DeleteActionDef<T> {
  return {
    type: 'delete',
    handler,
    confirm: options?.confirm,
    success: options?.success,
    label: options?.label,
  }
}

/**
 * Group multiple actions into a dropdown menu triggered by a "⋮" button.
 * Useful when a row has many actions and inline buttons take too much space.
 *
 * Nesting is not supported — if a nested `actionGroup` is detected,
 * a console warning is emitted and the nested group is ignored.
 *
 * @example
 * ```ts
 * actions: [
 *   openAction((row) => ({ ... })),       // inline button
 *   actionGroup([                          // dropdown "⋮"
 *     openAction((row) => ({ ... }), { label: 'Preview' }),
 *     deleteAction((row) => repository.delete(row.id)),
 *   ]),
 * ]
 * ```
 */
export function actionGroup<T>(
  actions: (OpenActionDef<T> | DeleteActionDef<T>)[],
  options?: { label?: string },
): ActionGroupDef<T> {
  return {
    type: 'group',
    actions,
    label: options?.label,
  }
}

// ── Column helpers ──────────────────────────────────────────────

/**
 * Create a clickable link column that navigates via router.push.
 * Renders the cell value wrapped in `<NA>` (Naive UI anchor).
 *
 * Must be called during component setup (uses `useRouter()`).
 *
 * @example
 * ```ts
 * linkColumn<Post>('id', {
 *   width: 50,
 *   to: (row) => ({ name: 'posts.show', params: { id: row.id } }),
 *   windowed: (row) => ({ title: `Post #${row.id}` }),
 * })
 * ```
 */
export function linkColumn<T extends Record<string, any>>(
  key: string & keyof T,
  options: {
    width?: number
    title?: string
    sorter?: boolean
    to: (row: T) => any // RouteLocationRaw
    windowed?: boolean | ((row: T) => { title: string })
  },
): Column<Record<string, any>> {
  const router = useRouter()

  return {
    title: options.title ?? key.toUpperCase(),
    key,
    width: options.width,
    sorter: options.sorter,
    render(row) {
      const route = options.to(row as T)

      if (options.windowed) {
        const windowedConfig =
          typeof options.windowed === 'function' ? options.windowed(row as T) : options.windowed
        Object.assign(route, {
          windowed: windowedConfig === true ? {} : windowedConfig,
        })
      }

      return h(
        'span',
        {
          onClick: () => router.push(route),
          style: 'cursor: pointer',
        },
        [h(NA, null, { default: () => String(row[key]) })],
      )
    },
  }
}

/**
 * Create a column that renders a date/time value using AppDateTime.
 *
 * @example
 * ```ts
 * dateColumn<Post>('created_at', { width: 200, sorter: true })
 * ```
 */
export function dateColumn<T extends Record<string, any>>(
  key: string & keyof T,
  options?: {
    width?: number
    title?: string
    sorter?: boolean
  },
): Column<Record<string, any>> {
  return {
    title: options?.title ?? humanizeKey(key),
    key,
    width: options?.width,
    sorter: options?.sorter,
    render(row) {
      return h(AppDateTime, { value: (row as T)[key] })
    },
  }
}

/**
 * Create an actions column placeholder.
 *
 * The actual action definitions come from `useListPage`'s `actions` option.
 * If `actions` is provided to `useListPage` but no `actionsColumn()` is in
 * the columns array, one is appended automatically with default settings.
 *
 * Accepts either static options or a callback for dynamic customization.
 *
 * @example
 * ```ts
 * // No args — auto-appended with defaults (width: 100, title: 'Actions'):
 * actionsColumn()
 *
 * // Static options:
 * actionsColumn({ width: 300, title: 'Operations' })
 *
 * // Callback — receives actions, returns final config:
 * actionsColumn(({ actions }) => ({
 *   actions: actions.filter(a => a.type !== 'delete'),
 *   width: 100,
 * }))
 * ```
 */
export function actionsColumn(
  optionsOrCallback?: { width?: number; title?: string } | ActionsColumnCallback,
): ListPageColumn<any> {
  if (typeof optionsOrCallback === 'function') {
    return {
      title: '',
      key: 'actions',
      width: 100,
      [LIST_PAGE_ACTIONS_SYMBOL]: optionsOrCallback,
    } as ListPageColumn<any>
  }

  return {
    title: optionsOrCallback?.title ?? '',
    key: 'actions',
    width: optionsOrCallback?.width ?? 100,
    [LIST_PAGE_ACTIONS_SYMBOL]: true,
  } as ListPageColumn<any>
}

// ── Internal: process actions column ────────────────────────────

/**
 * Check if a column is an actions column placeholder.
 */
export function isActionsColumn<T>(
  column: ListPageColumn<T>,
): column is Column<T> & ActionsColumnDef {
  return LIST_PAGE_ACTIONS_SYMBOL in column
}

/**
 * Process action definitions into a render function.
 * Called by useListPage to inject `load()` and `message`.
 * Returns a partial column definition with `render` to be merged with the original column.
 */
export function processActionsColumn<T>(
  composables: ListComposables,
  actions: ActionDef<T>[],
  load: () => Promise<void>,
): { render: (row: T) => ReturnType<typeof h> } {
  const { router, message, dialog } = composables

  /** Navigate to a route, optionally in a window. */
  function navigateAction(action: OpenActionDef<T>, row: T) {
    const route = action.to(row)
    if (action.windowed) {
      const windowedConfig =
        typeof action.windowed === 'function' ? action.windowed(row) : action.windowed
      Object.assign(route, {
        windowed: windowedConfig === true ? {} : windowedConfig,
      })
    }
    router.push(route)
  }

  /** Execute a delete action with confirmation via dialog. */
  function deleteWithDialog(action: DeleteActionDef<T>, row: T) {
    const confirmText =
      typeof action.confirm === 'function'
        ? action.confirm(row)
        : (action.confirm ?? 'Are you sure?')

    dialog.warning({
      title: 'Confirm',
      content: confirmText,
      positiveText: 'Yes',
      negativeText: 'Cancel',
      onPositiveClick: () =>
        action.handler(row).then(() => {
          load()
          const successText =
            typeof action.success === 'function' ? action.success(row) : action.success
          if (successText) {
            message.success(successText)
          }
        }),
    })
  }

  /** Render a single inline action as a button (open) or popconfirm+button (delete). */
  function renderInlineAction(action: ActionDef<T>, row: T) {
    if (action.type === 'open') {
      return h(
        NButton,
        {
          size: 'small',
          type: 'info',
          onClick: () => navigateAction(action, row),
        },
        { default: () => action.label ?? 'Open' },
      )
    }

    if (action.type === 'delete') {
      const confirmText =
        typeof action.confirm === 'function'
          ? action.confirm(row)
          : (action.confirm ?? 'Are you sure?')

      return h(
        NPopconfirm,
        {
          onPositiveClick: () =>
            action.handler(row).then(() => {
              load()
              const successText =
                typeof action.success === 'function' ? action.success(row) : action.success
              if (successText) {
                message.success(successText)
              }
            }),
        },
        {
          default: () => confirmText,
          trigger: () =>
            h(
              NButton,
              { size: 'small', type: 'error' },
              { default: () => action.label ?? 'Delete' },
            ),
        },
      )
    }

    return null
  }

  /** Render an action group as a dropdown with a "⋮" trigger button. */
  function renderActionGroup(group: ActionGroupDef<T>, row: T) {
    const dropdownOptions = group.actions
      .filter((a) => {
        if ((a as any).type === 'group') {
          console.warn('[useListPage] Nested actionGroup is not supported — ignoring.')
          return false
        }
        return true
      })
      .map((action, index) => ({
        label: action.type === 'open' ? (action.label ?? 'Open') : (action.label ?? 'Delete'),
        key: index,
      }))

    return h(
      NDropdown,
      {
        options: dropdownOptions,
        trigger: 'click' as const,
        onSelect: (key: number) => {
          const action = group.actions[key]
          if (action.type === 'open') {
            navigateAction(action, row)
          } else if (action.type === 'delete') {
            deleteWithDialog(action, row)
          }
        },
      },
      {
        default: () =>
          h(
            NButton,
            {
              size: 'small',
              quaternary: true,
              title: group.label ?? 'More actions',
            },
            {
              default: () => h(NIcon, null, { default: () => h(MoreVertical24Regular) }),
            },
          ),
      },
    )
  }

  return {
    render(row: T) {
      const elements = actions.map((action) => {
        if (action.type === 'group') {
          return renderActionGroup(action, row)
        }
        return renderInlineAction(action, row)
      })

      return h(NFlex, { align: 'center', justify: 'end' }, { default: () => elements })
    },
  }
}

// ── Utils ───────────────────────────────────────────────────────

function humanizeKey(key: string): string {
  return key.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase())
}
