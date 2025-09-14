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

export interface DefaultIndexQueryContract<TData>
  extends HasDataContract<TData>,
    HasSignalContract,
    HasPaginationContract {}

export interface DefaultStoreQueryContract<TData>
  extends HasDataContract<TData>,
    HasSignalContract {}

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

function buildAxiosPostConfigFromOptions(options: OptionsContract) {
  const config: AxiosRequestConfig = {}
  const data: any = {}

  handleHasSignalContract(options, config)
  handleHasDataContract(options, data)
  handleHasPaginationContract(options, data)

  return {
    config,
    data,
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
  const { config, data } = buildAxiosPostConfigFromOptions(options)

  return axios.post<T>(url, data, config)
}
