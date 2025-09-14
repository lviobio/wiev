import axios, { AxiosError } from 'axios'
import {
  ForbiddenInterface,
  ForbiddenSymbol,
  ModelNotFoundInterface,
  ModelNotFoundSymbol,
  NetworkErrorInterface,
  NetworkErrorSymbol,
  NotFoundInterface,
  NotFoundSymbol,
  SpecificException,
  SystemErrorSymbol,
  ValidationFailedInterface,
  ValidationFailedSymbol,
} from './index'

abstract class ClientError extends SpecificException {}

export class RequestConfigurationMismatch extends SpecificException {}

export class HttpException extends SpecificException {
  constructor(message: string, options?: ErrorOptions) {
    super(message, options)
  }
}

export class RequestFailedWithoutResponse
  extends SpecificException
  implements NetworkErrorInterface
{
  [NetworkErrorSymbol] = true

  constructor(
    message: string,
    public code?: string,
    options?: ErrorOptions,
  ) {
    super(message, options)
  }
}

export class ClientErrorException extends ClientError {}

export class ClientErrorUnknown extends ClientError {
  constructor(public result: unknown) {
    super('Unknown client error')
  }
}

export class TimedOutException
  extends RequestFailedWithoutResponse
  implements NetworkErrorInterface
{
  [NetworkErrorSymbol] = true
}

export class UnprocessableEntity extends HttpException {}

export class UnauthorizedHttpException extends HttpException {}
export class ForbiddenHttpException extends HttpException implements ForbiddenInterface {
  [ForbiddenSymbol] = true
}
export class NotFoundHttpException extends HttpException implements NotFoundInterface {
  [NotFoundSymbol] = true
}
export class ModelNotFoundHttpException
  extends NotFoundHttpException
  implements ModelNotFoundInterface
{
  [ModelNotFoundSymbol] = true
}
export class ValidationFailed extends UnprocessableEntity implements ValidationFailedInterface {
  [ValidationFailedSymbol] = true

  constructor(
    message: string,
    public errors: Record<string, string[]> = {},
    options?: ErrorOptions,
  ) {
    super(message, options)
  }
}

export class InternalServerErrorHttpException extends HttpException {
  [SystemErrorSymbol] = true
}

const ERROR_DESCRIPTIONS = {
  [AxiosError.ERR_CANCELED]: 'Request was canceled [ERR_CANCELED]',
  [AxiosError.ERR_NETWORK]: 'Network error [ERR_NETWORK]',
  [AxiosError.ERR_BAD_REQUEST]: 'Bad request [ERR_BAD_REQUEST]',
  [AxiosError.ERR_BAD_RESPONSE]: 'Bad response [ERR_BAD_RESPONSE]',
  [AxiosError.ETIMEDOUT]: 'Request timed out [ETIMEDOUT]',
} as const

export enum ResponseStatus {
  Success = 200,
  Created = 201,
  Accepted = 202,
  NoContent = 204,

  BadRequest = 400,
  Unauthorized = 401,
  Forbidden = 403,
  NotFound = 404,
  ModelNotFound = 424,
  ValidationFailed = 422,

  InternalServerError = 500,
}

export class AxiosUtils {
  static onRejectedToSpecificException(error: AxiosError): never {
    throw AxiosUtils.toSpecificException(error)
  }

  /**
   * @see {https://habr.com/ru/companies/raiffeisenbank/articles/882664/}
   */
  static toSpecificException(error: any): SpecificException {
    if (axios.isAxiosError(error)) {
      if (error.response) {
        // Запрос был сделан, и сервер ответил кодом состояния, который выходит за пределы 2xx.
        // Или был определён validateStatus, который вернул false

        const { response } = error

        // 4xx
        if (response.status === ResponseStatus.Unauthorized) {
          return new UnauthorizedHttpException(response.data.message, { cause: error })
        }
        if (response.status === ResponseStatus.Forbidden) {
          return new ForbiddenHttpException(response.data.message, { cause: error })
        }
        if (response.status === ResponseStatus.NotFound) {
          return new NotFoundHttpException(response.data.message, { cause: error })
        }
        if (response.status === ResponseStatus.ModelNotFound) {
          return new ModelNotFoundHttpException(response.data.message, { cause: error })
        }
        if (response.status === ResponseStatus.ValidationFailed) {
          const data = response.data
          if (
            'message' in data &&
            typeof data.message === 'string' &&
            'errors' in data &&
            typeof data.errors === 'object'
          ) {
            return new ValidationFailed(data.message, data.errors, {
              cause: error,
            })
          }
          return new UnprocessableEntity(response.data.message, { cause: error })
        }

        // 5xx
        if (response.status === ResponseStatus.InternalServerError) {
          return new InternalServerErrorHttpException(response.data.message, { cause: error })
        }

        return new HttpException(response.data.message, { cause: error })
      } else if (error.request) {
        // Запрос был сделан, но ответ не получен
        const code = error.code

        // Дополнительная проверка на offline состояние
        const isOfflineError =
          !navigator.onLine ||
          error.message?.toLowerCase().includes('network') ||
          error.message?.toLowerCase().includes('offline') ||
          error.message?.toLowerCase().includes('connection')

        if (code === undefined) {
          // Если браузер offline или сообщение указывает на сетевую проблему, создаем NetworkError
          if (isOfflineError) {
            return new RequestFailedWithoutResponse(
              error.message || 'Network connection failed - you appear to be offline',
              'ERR_NETWORK',
              { cause: error },
            )
          }
          return new RequestFailedWithoutResponse(error.message, undefined, { cause: error })
        }

        if (code === AxiosError.ETIMEDOUT) {
          return new TimedOutException(error.message, code, { cause: error })
        }

        return new RequestFailedWithoutResponse(
          error.message,
          ERROR_DESCRIPTIONS[code as keyof typeof ERROR_DESCRIPTIONS] || code,
          { cause: error },
        )
      } else {
        // Произошло что-то при настройке запроса, вызвавшее ошибку
        return new RequestConfigurationMismatch(error.message, { cause: error })
      }
    }

    // Сперва проверим не является ли ошибка инстансом системного класса Error
    if (error instanceof Error) {
      return new ClientErrorException(error.message, { cause: error })
    }

    return new ClientErrorUnknown(error)
  }
}
