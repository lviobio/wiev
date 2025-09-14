import { AppFormWrapper, AppFormWrapperInst, ValidationMessages } from '@/components/AppFormWrapper'
import { cloneDeep } from 'lodash'
import { FormInst, formProps, NAlert, NCollapseTransition, NForm, NSpin } from 'naive-ui'
import {
  FormItemRule,
  FormRules,
  FormValidateCallback,
  ShouldRuleBeApplied,
} from 'naive-ui/es/form/src/interface'
import { computed, ComputedRef, defineComponent, PropType, ref, SlotsType } from 'vue'

export type AppFormInst = Pick<FormInst, 'restoreValidation'> & {
  validate: (
    callback?: (...args: Parameters<FormValidateCallback>) => Promise<void>,
    shouldRuleBeApplied?: ShouldRuleBeApplied,
  ) => ReturnType<FormValidateCallback>
  failValidation: (message: string, messages: ValidationMessages) => void
}

export const AppForm = defineComponent({
  name: 'AppForm',
  props: {
    ...formProps,
    required: {
      type: Array as PropType<string[]>,
      required: false,
      default: () => [],
    },
    error: {
      type: String,
      required: false,
      default: undefined,
    },
  },
  slots: Object as SlotsType<{
    default: () => Element
  }>,
  setup(props, { expose, slots }) {
    const componentRef = ref<FormInst>()
    const wrapperRef = ref<AppFormWrapperInst>()

    const isValidating = ref(false)
    const isValidationErrored = ref(false)
    const errorMessage = ref<string | undefined>()
    const errorMessages = ref<ValidationMessages>({})

    const exposed: AppFormInst = {
      validate: (callback?, shouldRuleBeApplied?) => {
        isValidating.value = true
        return new Promise((resolve) => {
          const component = componentRef.value

          if (!component) {
            throw new Error('formRef is not defined')
          }

          component.validate(async function (errors, extra) {
            await callback?.(errors, extra)
            resolve(extra)

            isValidating.value = false
          }, shouldRuleBeApplied)
        })
      },
      failValidation: (message: string, messages: ValidationMessages) => {
        wrapperRef.value?.setValidationErrors(messages)
        errorMessage.value = message
        errorMessages.value = messages
        isValidationErrored.value = true
      },
      restoreValidation: () => {
        componentRef.value?.restoreValidation()
        isValidationErrored.value = false
      },
    }

    const customProps = computed(() => {
      const newProps: Record<string, unknown> = {
        ...props,
      }

      const customRules = (cloneDeep(props.rules) || {}) as {
        [path: string]: FormItemRule
      }

      props.required.forEach((path) => {
        if (customRules[path]) {
          customRules[path].required = true
        } else {
          customRules[path] = { required: true }
        }
      })

      newProps.rules = customRules as FormRules

      return newProps
    }) as ComputedRef<typeof props>

    expose(exposed)

    return () => (
      <NForm ref={componentRef} {...customProps.value}>
        <AppFormWrapper ref={wrapperRef}>
          <NCollapseTransition show={isValidationErrored.value || !!customProps.value.error}>
            <NSpin show={isValidating.value}>
              <NAlert title={'Fix errors'} type="error" class="mb-8">
                <ul>
                  {Object.entries(errorMessages.value).map(([key, keyMessages]) =>
                    keyMessages.map((message, i) => <li key={`${key}-${i}`}>{message}</li>),
                  )}

                  <li key={customProps.value.error} class={{ block: customProps.value.error }}>
                    {customProps.value.error}
                  </li>
                </ul>
              </NAlert>
            </NSpin>
          </NCollapseTransition>

          {slots.default()}
        </AppFormWrapper>
      </NForm>
    )
  },
})

export default AppForm
