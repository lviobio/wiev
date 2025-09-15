import { AppFormInst } from '@/components/AppForm'
import { isValidationFailed } from '@/core/errors'
import { FormRules, useMessage } from 'naive-ui'
import { Ref, ref } from 'vue'

interface NaiveForm<T> {
  formModel: Ref<T>
  formRef: Ref<AppFormInst | undefined>
  formRules?: FormRules
  formValidate: (afterClientValidation: () => any | Promise<any>) => Promise<void>
  formLoading: Ref<boolean>
}

export function useNaiveForm<Model extends object>(data: Model, rules?: FormRules): NaiveForm<Model>
export function useNaiveForm<Model extends object>(
  data?: undefined,
  rules?: FormRules,
): NaiveForm<Model | undefined>
export function useNaiveForm<Model>(data?: Model, rules?: FormRules): NaiveForm<Model> {
  const formRef = ref<AppFormInst>()
  const formModel = ref(data) as Ref<Model>
  const formRules = rules
  const message = useMessage()
  const formLoading = ref(false)

  const formValidate: NaiveForm<Model>['formValidate'] = (afterClientValidation) => {
    return new Promise<void>((resolve) => {
      if (formRef.value) {
        formLoading.value = true

        formRef.value.validate(async (errors) => {
          let failed = false
          let failHandled = true
          let failError: any

          if (errors) {
            message.error('Correct form errors')
          } else {
            try {
              await afterClientValidation()
            } catch (err) {
              failed = true
              failError = err
              if (isValidationFailed(err)) {
                formRef.value?.failValidation(err.message, err.errors)
              } else {
                failHandled = false
              }
            }

            if (!failed) {
              formRef.value?.restoreValidation()
            }
          }

          formLoading.value = false
          resolve()

          if (!failHandled && failError) {
            throw failError
          }
        })
      } else {
        console.error('Form not found')
        resolve()
      }
    })
  }

  return {
    formModel,
    formRef,
    formRules,
    formValidate,
    formLoading,
  }
}
