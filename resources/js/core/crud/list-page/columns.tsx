import AppDateTime from '@/components/AppDateTime.vue'
import { MoreVertical24Regular } from '@vicons/fluent'
import { startCase } from 'lodash'
import { NA, NButton, NDropdown, NFlex, NIcon, NPopconfirm } from 'naive-ui'
import { h } from 'vue'
import type { Router } from 'vue-router'
import type {
  ActionDef,
  ActionGroupDef,
  ActionsColumnCallback,
  ActionsColumnDef,
  Column,
  ColumnHelpers,
  DateColumnOptions,
  DeleteActionDef,
  LinkColumnOptions,
  ListComposables,
  ListPageColumn,
  OpenActionDef,
  RenderedColumn,
} from './types'
import { LIST_PAGE_ACTIONS_SYMBOL } from './types'

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
 * Internal — exposed to list pages via `ColumnHelpers.linkColumn`.
 */
function buildLinkColumn<T extends Record<string, any>>(
  router: Router,
  key: string & keyof T,
  options: LinkColumnOptions<T>,
): RenderedColumn<T> {
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
 * Internal — exposed to list pages via `ColumnHelpers.dateColumn`.
 */
function buildDateColumn<T extends Record<string, any>>(
  key: string & keyof T,
  options?: DateColumnOptions,
): RenderedColumn<T> {
  return {
    title: options?.title ?? startCase(key),
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
): Column<any> & ActionsColumnDef {
  if (typeof optionsOrCallback === 'function') {
    return {
      title: '',
      key: 'actions',
      width: 100,
      [LIST_PAGE_ACTIONS_SYMBOL]: optionsOrCallback,
    }
  }

  return {
    title: optionsOrCallback?.title ?? '',
    key: 'actions',
    width: optionsOrCallback?.width ?? 100,
    [LIST_PAGE_ACTIONS_SYMBOL]: true,
  }
}

/**
 * Create column helpers with the row type `T` pre-bound.
 *
 * Used by `useListPage` to pass typed helpers into the `columns` factory
 * callback, so helper calls need no explicit type arguments and `row`
 * callbacks / keys are checked against `T`. The router is captured here
 * (during setup) because the factory itself runs lazily inside a computed,
 * where `useRouter()` is no longer available.
 */
export function createColumnHelpers<T extends Record<string, any>>(
  composables: ListComposables,
): ColumnHelpers<T> {
  return {
    column: (key, options) => ({ title: startCase(key), ...options, key }),
    linkColumn: (key, options) => buildLinkColumn(composables.router, key, options),
    dateColumn: (key, options) => buildDateColumn<T>(key, options),
    actionsColumn,
  }
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

  /** Resolve the confirmation text of a delete action for the given row. */
  function resolveConfirmText(action: DeleteActionDef<T>, row: T) {
    return typeof action.confirm === 'function'
      ? action.confirm(row)
      : (action.confirm ?? 'Are you sure?')
  }

  /**
   * Run a confirmed delete: invoke the handler, reload the list, and show the
   * success message (if any). Shared by every confirm surface (inline
   * popconfirm and dropdown dialog) so the post-confirm behavior stays in sync.
   */
  function runDelete(action: DeleteActionDef<T>, row: T) {
    return action.handler(row).then(() => {
      load()
      const successText =
        typeof action.success === 'function' ? action.success(row) : action.success
      if (successText) {
        message.success(successText)
      }
    })
  }

  /** Execute a delete action with confirmation via dialog. */
  function deleteWithDialog(action: DeleteActionDef<T>, row: T) {
    dialog.warning({
      title: 'Confirm',
      content: resolveConfirmText(action, row),
      positiveText: 'Yes',
      negativeText: 'Cancel',
      onPositiveClick: () => runDelete(action, row),
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
      return h(
        NPopconfirm,
        {
          onPositiveClick: () => runDelete(action, row),
        },
        {
          default: () => resolveConfirmText(action, row),
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
    // Drop unsupported nested groups first, then index options against this
    // filtered list — `onSelect` must resolve the action from the same array,
    // otherwise a filtered-out group shifts the indices and fires the wrong action.
    const flatActions = group.actions.filter((a) => {
      if ((a as ActionDef<T>).type === 'group') {
        console.warn('[useListPage] Nested actionGroup is not supported — ignoring.')
        return false
      }
      return true
    })

    const dropdownOptions = flatActions.map((action, index) => ({
      label: action.type === 'open' ? (action.label ?? 'Open') : (action.label ?? 'Delete'),
      key: index,
    }))

    return h(
      NDropdown,
      {
        options: dropdownOptions,
        trigger: 'click' as const,
        onSelect: (key: number) => {
          const action = flatActions[key]
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
