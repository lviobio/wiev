import { FormItemInst } from 'naive-ui'
import { defineComponent, inject, SlotsType } from 'vue'

export type ValidationMessages = Record<string, string[]>
export type AppFormWrapperInst = {
  resetValidationErrors: () => void
  setValidationErrors: (errors: ValidationMessages) => void
}

type FormItemInstExtended = FormItemInst & {
  validationErrored: boolean
  renderExplains: {
    key: string
    render: () => string
  }[]
}

export const AppFormWrapper = defineComponent({
  name: 'AppFormWrapper',
  slots: Object as SlotsType<{
    default: () => Element
  }>,
  setup(props, { expose, slots }) {
    const formItemsInjected = inject('n-form-item-insts', null) as null | {
      formItems: Record<string, FormItemInstExtended[]>
    }

    const exposed: AppFormWrapperInst = {
      resetValidationErrors: () => {
        if (!formItemsInjected) {
          return
        }

        for (const items of Object.values(formItemsInjected.formItems)) {
          items.forEach((item) => {
            item.validationErrored = false
            item.renderExplains = []
          })
        }
      },
      setValidationErrors: (errors: ValidationMessages) => {
        if (!formItemsInjected) {
          return
        }

        exposed.resetValidationErrors()

        for (const [key, messages] of Object.entries(errors)) {
          if (key in formItemsInjected.formItems) {
            const items = formItemsInjected.formItems[key]

            items.forEach((item) => {
              item.validationErrored = true
              item.renderExplains = messages.map((message) => ({
                key: message,
                render: () => message,
              }))
            })
          } else {
            console.warn(
              `Form item with key ${key} not found in form, to display validation errors`,
            )
          }
        }
      },
    }

    expose(exposed)

    return () => <div class="form-wrapper">{slots.default()}</div>
  },
})
