<?php
declare(strict_types=1);

namespace App\Support\Spatie\Data;

use App\Support\VO\HasValidationRules;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\RuleInferrers\RuleInferrer;
use Spatie\LaravelData\Support\DataProperty;
use Spatie\LaravelData\Support\Validation\PropertyRules;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/**
 * Достаёт правила валидации из типа свойства.
 *
 * Свойство, типизированное доменным значением, само по себе ничего не говорит
 * валидатору: laravel-data умеет выводить правила только для встроенных типов.
 * Этот инферрер закрывает дыру — правила берутся у самого значения, поэтому
 * граница HTTP отвечает 422 там, где иначе конструктор VO бросил бы 500.
 *
 * Правила значения складываются с правилами, объявленными атрибутами на самом
 * свойстве — так же, как в laravel-data складываются выведенные из встроенного
 * типа и атрибутные. Ослабить форму значения атрибутом всё равно нельзя:
 * конструктор VO проверяет её сам, и поле, разрешившее больше, чем значение,
 * получило бы 500 вместо 422.
 */
class ValueRuleInferrer implements RuleInferrer
{
    public function handle(DataProperty $property, PropertyRules $rules, ValidationContext $context): PropertyRules
    {
        /** @var class-string<HasValidationRules>|null $valueClass */
        $valueClass = $property->type->type->findAcceptedTypeForBaseType(HasValidationRules::class);

        if ($valueClass === null) {
            return $rules;
        }

        $valueRules = $valueClass::rules();

        if ($valueRules === []) {
            return $rules;
        }

        return $rules->add(new Rule(...$valueRules));
    }
}
