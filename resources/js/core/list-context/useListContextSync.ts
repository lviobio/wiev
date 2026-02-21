import type { SortField } from '@/core/sorting/base'
import { Ref, watch } from 'vue'

type WatchHandle = ReturnType<typeof watch>

// ── Types ────────────────────────────────────────────────────────

/**
 * Conventional shape for a list-page context. Individual channels
 * read/write only the keys they care about — features can extend
 * the context freely as long as their own channels handle it.
 */
export interface ListContextConstraint<F> {
  page?: number
  cursor?: string
  per_page?: number
  search?: string
  sort?: SortField[]
  filters: F
}

export interface ContextSyncChannelContext {
  /** The unified context ref. A channel reads/writes only its own keys on it. */
  context: Ref<Record<string, any>>
  /**
   * Run `fn` with every channel's watchers paused — use when writing to
   * context from a local watcher to prevent feedback loops.
   */
  guarded: (fn: () => void) => void
  /**
   * Register a watch handle so the runner can pause it during `guarded`.
   * Every watcher a channel creates should be registered.
   */
  register: (handle: WatchHandle) => void
}

export interface ContextSyncChannel {
  /**
   * Wire up bidirectional sync between a feature's state and the shared
   * context. Called once per channel by `useListContextSync`.
   */
  install(ctx: ContextSyncChannelContext): void
}

// ── Deep assign utility (shared with channels) ───────────────────

/**
 * Recursively assign `source` onto `target` property-by-property.
 * Preserves Vue reactivity on nested objects (e.g. `created_at: { from, to }`)
 * instead of replacing them wholesale.
 *
 * Only iterates own enumerable string keys (skips symbols).
 */
export function deepAssign(target: Record<string, any>, source: Record<string, any>): void {
  for (const key of Object.keys(source)) {
    const srcVal = source[key]
    const tgtVal = target[key]

    if (
      srcVal !== null &&
      typeof srcVal === 'object' &&
      !Array.isArray(srcVal) &&
      tgtVal !== null &&
      typeof tgtVal === 'object' &&
      !Array.isArray(tgtVal)
    ) {
      deepAssign(tgtVal, srcVal)
    } else {
      target[key] = srcVal
    }
  }
}

// ── Composable ───────────────────────────────────────────────────

/**
 * Bidirectional sync between a unified list context and feature-owned
 * state, driven by pluggable channels. Each channel is a self-contained
 * description of what to read/write for one concern (filters, search,
 * pagination, etc.). Features register their own channels — the runner
 * is agnostic to the context shape.
 *
 * @example
 * ```ts
 * useListContextSync(context, [
 *   createFiltersSyncChannel(filters),
 *   createSearchSyncChannel(search),
 * ])
 * ```
 */
export function useListContextSync(
  context: Ref<Record<string, any>>,
  channels: ContextSyncChannel[],
): void {
  const watchers: WatchHandle[] = []

  const guarded = (fn: () => void): void => {
    watchers.forEach((w) => w.pause())
    fn()
    watchers.forEach((w) => w.resume())
  }

  const channelCtx: ContextSyncChannelContext = {
    context,
    guarded,
    register: (handle) => watchers.push(handle),
  }

  for (const channel of channels) {
    channel.install(channelCtx)
  }
}
