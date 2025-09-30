import { PaginationComposable } from '@/core/pagination/base'
import { AxiosInstance, AxiosRequestConfig } from 'axios'

export interface HasSignalContract {
  signal?: AbortSignal
}

export interface HasDataContract<T> {
  data: T
}

export interface HasPaginationContract {
  pagination?: PaginationComposable
}

export interface DefaultListQueryContract<TData>
  extends HasDataContract<TData>,
    HasSignalContract,
    HasPaginationContract {}

export interface DefaultCreateQueryContract<TData>
  extends HasDataContract<TData>,
    HasSignalContract {}

export interface DefaultUpdateQueryContract<TData>
  extends HasDataContract<TData>,
    HasSignalContract {}

export interface DefaultCreateQueryResultContract<TData> extends HasDataContract<TData> {}
export interface DefaultFindQueryResultContract<TData> extends HasDataContract<TData> {}
export interface DefaultUpdateQueryResultContract<TData> extends HasDataContract<TData> {}

export type OptionsContract = HasSignalContract | HasDataContract<unknown> | HasPaginationContract

function handleHasSignalContract(options: OptionsContract, config: AxiosRequestConfig) {
  if ('signal' in options) {
    config.signal = options.signal
  }
}

function handleHasDataContract(options: OptionsContract, data: any) {
  if ('data' in options) {
    Object.assign(data, options.data)
  }
}

function handleHasPaginationContract(options: OptionsContract, data: any) {
  if ('pagination' in options && options.pagination) {
    Object.assign(data, options.pagination.toQueryParams())
  }
}

function buildAxiosGetConfigFromOptions(options: OptionsContract, addDataToParams = false) {
  const config: AxiosRequestConfig = {}
  const data: any = {}

  handleHasSignalContract(options, config)
  handleHasDataContract(options, data)
  handleHasPaginationContract(options, data)

  if (addDataToParams) {
    config.params = data
  }

  return {
    config,
    data,
  }
}

function buildAxiosFormDataConfigFromOptions(options: OptionsContract, method: string) {
  const config: AxiosRequestConfig = {}
  const data: any = {}

  handleHasSignalContract(options, config)
  handleHasDataContract(options, data)
  handleHasPaginationContract(options, data)

  const formData = buildFormData(data, new FormData())

  if (method !== 'POST') {
    formData.append('_method', method)
  }

  return {
    config,
    data: formData,
  }
}

export function sendAxiosGetRequest<T>(
  axios: AxiosInstance,
  url: string,
  options: OptionsContract,
) {
  const { config } = buildAxiosGetConfigFromOptions(options, true)

  return axios.get<T>(url, config)
}

export function sendAxiosPostRequest<T>(
  axios: AxiosInstance,
  url: string,
  options: OptionsContract,
) {
  const { config, data } = buildAxiosFormDataConfigFromOptions(options, 'POST')

  return axios.post<T>(url, data, config)
}

export function sendAxiosPutRequest<T>(
  axios: AxiosInstance,
  url: string,
  options: OptionsContract,
) {
  const { config, data } = buildAxiosFormDataConfigFromOptions(options, 'PUT')

  return axios.post<T>(url, data, config)
}

function buildFormData<T extends object>(
  source: T,
  formData?: FormData,
  namespace?: string,
): FormData {
  formData = formData || new FormData()
  for (const property in source) {
    const isPropertyExist = property in source

    if (!isPropertyExist) {
      continue
    }

    const contextProperty = source[property]

    if (contextProperty === undefined) {
      continue
    }

    const formKey = namespace ? `${namespace}[${property}]` : property

    if (typeof contextProperty === 'object' && !(contextProperty instanceof File)) {
      buildFormData<any>(contextProperty, formData, formKey)
    } else {
      formData.append(formKey, contextProperty as string | File)
    }
  }
  return formData
}
