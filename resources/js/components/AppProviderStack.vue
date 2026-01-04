<script lang="ts">
import { Component, ComponentPropsOptions, ExtractPropTypes, h } from 'vue'

export type StackItem<C extends Component = Component> =
  | C
  | {
      component: C
      props?: C extends new (...args: any) => any
        ? Partial<InstanceType<C>['$props']>
        : C extends { props: ComponentPropsOptions }
          ? Partial<ExtractPropTypes<C['props']>>
          : Record<string, any>
    }

export function defineProviderStack<T extends readonly StackItem[]>(stack: T): T {
  return stack
}

export default defineComponent({
  props: {
    stack: {
      type: Array as PropType<StackItem[]>,
      default: () => [],
    },
  },
  setup(props, { slots }) {
    return () => {
      const defaultSlot = slots.default?.()

      if (!props.stack.length) {
        return defaultSlot
      }

      return props.stack.reduceRight((children, item) => {
        let component: Component
        let componentProps = {}
        if ('component' in item) {
          component = item.component
          componentProps = item.props
        } else {
          component = item
        }

        return h(
          component,
          {
            ...componentProps,
          },
          {
            default: () => children,
          },
        )
      }, defaultSlot as StackItem)
    }
  },
})
</script>
