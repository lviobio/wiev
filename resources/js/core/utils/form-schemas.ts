import { UploadFileInfo } from 'naive-ui'
import { z, ZodObject, ZodRawShape } from 'zod'

export type RemovableUploadFileInfo = UploadFileInfo | null

/**
 * Создает объект с пустыми значениями на основе Zod схемы
 * Полезно для инициализации форм
 */
export function createEmptyObjectFromSchema<T extends ZodRawShape>(
  schema: ZodObject<T>,
): z.infer<ZodObject<T>> {
  const shape = schema.shape
  const result: any = {}

  for (const key in shape) {
    const field = shape[key] as unknown as z.ZodType<any>

    // Проверяем наличие default значения через ZodDefault.
    // Используем parse с undefined для получения default значения через публичный API
    let hasDefault = false
    let currentType: z.ZodType<any> = field

    while (
      currentType instanceof z.ZodOptional ||
      currentType instanceof z.ZodNullable ||
      currentType instanceof z.ZodDefault
    ) {
      if (currentType instanceof z.ZodDefault) {
        hasDefault = true
        break
      }
      currentType = currentType.unwrap() as z.ZodType<any>
    }

    if (hasDefault) {
      // Используем parse для получения default значения
      result[key] = field.parse(undefined)
      continue
    }

    // Проверяем, является ли поле nullable
    let isNullable = false
    let checkType: z.ZodType<any> = field
    while (checkType instanceof z.ZodOptional || checkType instanceof z.ZodNullable) {
      if (checkType instanceof z.ZodNullable) {
        isNullable = true
      }
      checkType = checkType.unwrap() as z.ZodType<any>
    }

    // Если поле nullable, используем null как дефолт
    if (isNullable) {
      result[key] = null
      continue
    }

    // Получаем базовый тип, "разворачивая" optional/nullable
    let baseType: z.ZodType<any> = field
    while (baseType instanceof z.ZodOptional || baseType instanceof z.ZodNullable) {
      baseType = baseType.unwrap() as z.ZodType<any>
    }

    // Определяем дефолтное значение по типу
    if (baseType instanceof z.ZodString) {
      result[key] = ''
    } else if (baseType instanceof z.ZodNumber) {
      result[key] = 0
    } else if (baseType instanceof z.ZodBoolean) {
      result[key] = false
    } else if (baseType instanceof z.ZodArray) {
      result[key] = []
    } else if (baseType instanceof z.ZodObject) {
      result[key] = createEmptyObjectFromSchema(baseType)
    } else if (baseType instanceof z.ZodDate) {
      result[key] = null
    } else if (field instanceof z.ZodOptional || field instanceof z.ZodNullable) {
      // Для nullable/optional полей ставим undefined
      result[key] = undefined
    } else {
      // Для остальных типов (включая transform/refine) ставим undefined
      result[key] = undefined
    }
  }

  return result as z.infer<ZodObject<T>>
}

// Схема для преобразования URL в UploadFileInfo для формы
const zFormFile = z.string().transform((url): UploadFileInfo | undefined => {
  if (!url) return undefined

  return {
    id: `${url}|${Math.ceil(Math.random() * 1000000)}`,
    name: url.split('/').pop() ?? 'unnamed',
    status: 'finished',
    url,
  }
})

// Проверка содержит ли тип File
type ContainsFile<T> = File extends T ? true : false

// Рекурсивный тип для преобразования File в UploadFileInfo на уровне типов
type TransformSchemaType<T> =
  ContainsFile<T> extends true
    ? // Тип содержит File - преобразуем его
      T extends File
      ? UploadFileInfo | undefined
      : T extends File | null
        ? UploadFileInfo | null | undefined
        : T extends File | undefined
          ? UploadFileInfo | undefined
          : T extends File | null | undefined
            ? UploadFileInfo | null | undefined
            : T // Fallback, если не смогли точно определить
    : // Тип НЕ содержит File - проверяем структуру
      T extends (infer U)[]
      ? TransformSchemaType<U>[]
      : T extends object
        ? { [K in keyof T]: TransformSchemaType<T[K]> }
        : T

/**
 * Преобразует схему API (с File) в схему формы (с UploadFileInfo)
 * Рекурсивно заменяет z.custom<File>() на zFormFile.
 * Сохраняет полную типизацию для z.infer
 */
export function transformSchemaForForm<T extends ZodRawShape>(
  schema: ZodObject<T>,
): ZodObject<{
  [K in keyof T]: z.ZodType<TransformSchemaType<z.infer<T[K]>>>
}> {
  const shape = schema.shape
  const newShape: any = {}

  for (const key in shape) {
    const field = shape[key] as unknown as z.ZodType<any>
    newShape[key] = transformFieldForForm(field)
  }

  return z.object(newShape) as any
}

/**
 * Преобразует отдельное поле схемы
 */
function transformFieldForForm(field: z.ZodType<any>): z.ZodType<any> {
  // Проверяем optional
  if (field instanceof z.ZodOptional) {
    const innerField = transformFieldForForm(field.unwrap() as z.ZodType<any>)
    return innerField.optional()
  }

  // Проверяем nullable
  if (field instanceof z.ZodNullable) {
    const innerField = transformFieldForForm(field.unwrap() as z.ZodType<any>)
    return innerField.nullable()
  }

  // Проверяем массив
  if (field instanceof z.ZodArray) {
    const elementType = (field as any)._def.type as z.ZodType<any>
    const transformedElement = transformFieldForForm(elementType)
    return z.array(transformedElement)
  }

  // Проверяем объект (рекурсивно)
  if (field instanceof z.ZodObject) {
    return transformSchemaForForm(field)
  }

  // Проверяем z.custom<File>() - это будет ZodAny с проверкой на File
  // Простая эвристика: если это не стандартный тип, заменяем на zFormFile
  const isCustomFileType =
    !(field instanceof z.ZodString) &&
    !(field instanceof z.ZodNumber) &&
    !(field instanceof z.ZodBoolean) &&
    !(field instanceof z.ZodDate) &&
    !(field instanceof z.ZodArray) &&
    !(field instanceof z.ZodObject)

  if (isCustomFileType) {
    return zFormFile as z.ZodType<any>
  }

  // Остальные типы возвращаем как есть
  return field
}

// const formSchema = transformSchemaForForm(postFormSchema)
// type PostFormData = z.infer<typeof formSchema>

// Проверка является ли значение UploadFileInfo
function isUploadFileInfo(value: unknown): value is UploadFileInfo {
  return (
    value !== null &&
    typeof value === 'object' &&
    'id' in value &&
    'name' in value &&
    'status' in value
  )
}

// Рекурсивный тип для преобразования UploadFileInfo в File
type TransformUploadFileInfo<T> =
  // Сначала проверяем примитивы null/undefined, чтобы избежать их преобразования
  T extends null
    ? null
    : T extends undefined
      ? undefined
      : // Затем проверяем UploadFileInfo
        T extends UploadFileInfo
        ? File | undefined
        : // Массивы
          T extends (infer U)[]
          ? TransformUploadFileInfo<U>[]
          : // Примитивные типы
            // eslint-disable-next-line @typescript-eslint/no-unsafe-function-type
            T extends string | number | boolean | symbol | bigint | Date | RegExp | Function
            ? T
            : // Объекты (рекурсивно)
              T extends object
              ? { [K in keyof T]: TransformUploadFileInfo<T[K]> }
              : T

// Рекурсивный тип для преобразования File в UploadFileInfo (обратная трансформация)
type TransformFileToUploadFileInfo<T> =
  // Сначала проверяем примитивы null/undefined
  T extends null
    ? null
    : T extends undefined
      ? undefined
      : // Затем проверяем File
        T extends File
        ? UploadFileInfo | undefined
        : // Массивы
          T extends (infer U)[]
          ? TransformFileToUploadFileInfo<U>[]
          : // Примитивные типы
            // eslint-disable-next-line @typescript-eslint/no-unsafe-function-type
            T extends string | number | boolean | symbol | bigint | Date | RegExp | Function
            ? T
            : // Объекты (рекурсивно)
              T extends object
              ? { [K in keyof T]: TransformFileToUploadFileInfo<T[K]> }
              : T

// Проверка является ли значение File
function isFile(value: unknown): value is File {
  return value instanceof File
}

// Рекурсивное преобразование UploadFileInfo в File (полностью типизировано)
export function prepareFormData<T>(data: T): TransformUploadFileInfo<T> {
  // null сохраняем как есть (означает "удалить файл")
  if (data === null) {
    return null as TransformUploadFileInfo<T>
  }

  // undefined сохраняем как есть
  if (data === undefined) {
    return undefined as TransformUploadFileInfo<T>
  }

  // Если это UploadFileInfo - извлекаем File
  if (isUploadFileInfo(data)) {
    return (data.file ?? undefined) as TransformUploadFileInfo<T>
  }

  // Если это массив - обрабатываем каждый элемент
  if (Array.isArray(data)) {
    return data.map((item) => prepareFormData(item)) as TransformUploadFileInfo<T>
  }

  // Если это объект - обрабатываем каждое свойство
  if (typeof data === 'object') {
    const result: any = {}
    for (const key in data) {
      if (Object.prototype.hasOwnProperty.call(data, key)) {
        result[key] = prepareFormData((data as any)[key])
      }
    }
    return result as TransformUploadFileInfo<T>
  }

  // Примитивные типы возвращаем как есть
  return data as TransformUploadFileInfo<T>
}

// Рекурсивное преобразование File в UploadFileInfo (обратная трансформация)
export function transformFileToUploadFileInfoDeep<T>(data: T): TransformFileToUploadFileInfo<T> {
  // null сохраняем как есть
  if (data === null) {
    return null as TransformFileToUploadFileInfo<T>
  }

  // undefined сохраняем как есть
  if (data === undefined) {
    return undefined as TransformFileToUploadFileInfo<T>
  }

  // Если это File - преобразуем в UploadFileInfo
  if (isFile(data)) {
    return {
      id: `file-${Date.now()}-${Math.random()}`,
      name: data.name,
      status: 'finished',
      file: data,
    } as TransformFileToUploadFileInfo<T>
  }

  // Если это массив - обрабатываем каждый элемент
  if (Array.isArray(data)) {
    return data.map((item) =>
      transformFileToUploadFileInfoDeep(item),
    ) as TransformFileToUploadFileInfo<T>
  }

  // Если это объект - обрабатываем каждое свойство
  if (typeof data === 'object') {
    const result: any = {}
    for (const key in data) {
      if (Object.prototype.hasOwnProperty.call(data, key)) {
        result[key] = transformFileToUploadFileInfoDeep((data as any)[key])
      }
    }
    return result as TransformFileToUploadFileInfo<T>
  }

  // Примитивные типы возвращаем как есть
  return data as TransformFileToUploadFileInfo<T>
}
