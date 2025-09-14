export abstract class SpecificException extends Error {
  constructor(message?: string, options?: ErrorOptions) {
    super(message, options)

    this.name = this.constructor.name
  }
}

export const ForbiddenSymbol = Symbol('Forbidden')
export const NotFoundSymbol = Symbol('NotFound')
export const ModelNotFoundSymbol = Symbol('ModelNotFound')
export const ValidationFailedSymbol = Symbol('ValidationFailed')

export const SystemErrorSymbol = Symbol('SystemError')
export const NetworkErrorSymbol = Symbol('NetworkError')

export function isValidationFailed(v: any): v is ValidationFailedInterface {
  return v ? v[ValidationFailedSymbol] === true : false
}

export function isNotFound(v: any): v is NotFoundInterface {
  return v ? v[NotFoundSymbol] === true : false
}
export function isModelNotFound(v: any): v is ModelNotFoundInterface {
  return v ? v[ModelNotFoundSymbol] === true : false
}

export function isSystemError(v: any): v is SystemErrorInterface {
  return v ? v[SystemErrorSymbol] === true : false
}

export function isNetworkError(v: any): v is NetworkErrorInterface {
  return v ? v[NetworkErrorSymbol] === true : false
}

export function isForbidden(v: any): v is ForbiddenInterface {
  return v ? v[ForbiddenSymbol] === true : false
}

interface HasMessage {
  message: string
}

export interface ForbiddenInterface extends HasMessage {
  [ForbiddenSymbol]: boolean
}
export interface NotFoundInterface extends HasMessage {
  [NotFoundSymbol]: boolean
}
export interface ModelNotFoundInterface extends NotFoundInterface {
  [ModelNotFoundSymbol]: boolean
}
export interface ValidationFailedInterface extends HasMessage {
  [ValidationFailedSymbol]: boolean

  errors: Record<string, string[]>
}

export interface SystemErrorInterface extends HasMessage {
  [SystemErrorSymbol]: boolean
}

export interface NetworkErrorInterface extends HasMessage {
  [NetworkErrorSymbol]: boolean
}
