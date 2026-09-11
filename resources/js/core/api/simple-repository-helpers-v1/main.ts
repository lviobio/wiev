import { PaginationComposable } from '@/core/pagination/base'
import { SortingComposable } from '@/core/sorting/base'
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

export interface HasSortingContract {
  sorting?: SortingComposable
}

export interface DefaultListQueryContract<TData>
  extends HasDataContract<TData>, HasSignalContract, HasPaginationContract, HasSortingContract {}

export interface DefaultCreateQueryContract<TData>
  extends HasDataContract<TData>, HasSignalContract {}

export interface DefaultUpdateQueryContract<TData>
  extends HasDataContract<TData>, HasSignalContract {}

export interface DefaultCreateQueryResultContract<TData> extends HasDataContract<TData> {}
export interface DefaultFindQueryResultContract<TData> extends HasDataContract<TData> {}
export interface DefaultUpdateQueryResultContract<TData> extends HasDataContract<TData> {}

export type OptionsContract =
  | HasSignalContract
  | HasDataContract<unknown>
  | HasPaginationContract
  | HasSortingContract

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
    Object.assign(data, options.pagination.params.value)
  }
}

function handleHasSortingContract(options: OptionsContract, data: any) {
  if ('sorting' in options && options.sorting) {
    Object.assign(data, options.sorting.params.value)
  }
}

function buildAxiosGetConfigFromOptions(options: OptionsContract, addDataToParams = false) {
  const config: AxiosRequestConfig = {}
  const data: any = {}

  handleHasSignalContract(options, config)
  handleHasDataContract(options, data)
  handleHasPaginationContract(options, data)
  handleHasSortingContract(options, data)

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

function buildAxiosJsonConfigFromOptions(options: OptionsContract) {
  const config: AxiosRequestConfig = {}
  const data: any = {}

  handleHasSignalContract(options, config)
  handleHasDataContract(options, data)
  handleHasPaginationContract(options, data)

  return { config, data }
}

/**
 * Same shape as {@see sendAxiosPostRequest}, minus the FormData/File handling - for a
 * body that carries no File, so it can go as plain JSON instead of multipart.
 */
export function sendAxiosPostRequestJson<T>(
  axios: AxiosInstance,
  url: string,
  options: OptionsContract,
) {
  const { config, data } = buildAxiosJsonConfigFromOptions(options)

  return axios.post<T>(url, data, config)
}

/**
 * Unlike {@see sendAxiosPutRequest}, this sends a real PUT rather than a POST with
 * `_method` spoofing - that trick exists only because browsers can't submit multipart
 * forms with a PUT verb, which doesn't apply to a JSON request.
 */
export function sendAxiosPutRequestJson<T>(
  axios: AxiosInstance,
  url: string,
  options: OptionsContract,
) {
  const { config, data } = buildAxiosJsonConfigFromOptions(options)

  return axios.put<T>(url, data, config)
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

    if (contextProperty === null) {
      formData.append(formKey, '')
    } else if (typeof contextProperty === 'object' && !(contextProperty instanceof File)) {
      buildFormData<any>(contextProperty, formData, formKey)
    } else {
      formData.append(formKey, contextProperty as string | File)
    }
  }
  return formData
}
