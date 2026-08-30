<?php
declare(strict_types=1);

namespace App\Support\VO;

/**
 * Значение, которое само знает, какой формы должен быть его сырой вход.
 *
 * Эти правила подставляются в валидацию Data-объекта автоматически
 * ({@see \App\Support\Spatie\Data\ValueRuleInferrer}), поэтому перечислять их
 * в rules() руками не нужно: тип свойства — уже описание правил.
 *
 * Правила описывают только форму значения. Обязательность и nullable — свойство
 * поля, а не значения, их выводит laravel-data из типа (?Type, Optional).
 */
interface HasValidationRules
{
    /**
     * @return list<mixed>
     */
    public static function rules(): array;
}
