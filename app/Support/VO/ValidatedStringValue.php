<?php
declare(strict_types=1);

namespace App\Support\VO;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Строковое значение, инвариант которого описан правилами валидации Laravel.
 *
 * Смысл в том, что правила — единственный источник истины: тот же список
 * отдаётся Data-объекту на границе HTTP, поэтому клиент получает 422, а не 500,
 * и границы не разъезжаются по двум местам.
 *
 *   final readonly class PostTitle extends ValidatedStringValue
 *   {
 *       public static function rules(): array
 *       {
 *           return ['string', 'min:3', 'max:255'];
 *       }
 *   }
 *
 * Правила описывают только форму значения. Обязательность и nullable — свойство
 * поля, а не значения, их выводит laravel-data из типа свойства. Перечислять
 * их в rules() Data-объекта не нужно: {@see \App\Support\Spatie\Data\ValueRuleInferrer}
 * подставляет их сам.
 *
 * Для собственной проверки required подставляется всегда: пустая строка — не
 * значение, а его отсутствие, и выражается оно через ?Type. Заодно это обходит
 * особенность валидатора, который для пустой строки пропускает все неявные
 * правила, так что одним 'min:1' её было бы не поймать.
 *
 * Плата за подход: конструктор VO дёргает валидатор из контейнера, то есть
 * значение больше не собрать без загруженного фреймворка.
 */
abstract readonly class ValidatedStringValue implements StringValue, HasValidationRules
{
    public function __construct(
        public string $value,
    )
    {
        $validator = Validator::make(
            ['value' => $this->value],
            ['value' => ['required', ...static::rules()]],
            attributes: ['value' => static::attributeName()],
        );

        if ($validator->fails()) {
            throw InvalidValueException::make(static::class, $validator->errors()->all());
        }
    }

    /**
     * Правила формы значения.
     *
     * @return list<mixed>
     */
    abstract public static function rules(): array;

    /**
     * Имя значения в сообщении об ошибке: PostTitle => "post title".
     */
    protected static function attributeName(): string
    {
        return Str::lower(Str::headline(class_basename(static::class)));
    }
}
