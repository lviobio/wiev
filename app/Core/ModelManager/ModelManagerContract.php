<?php
declare(strict_types=1);

namespace App\Core\ModelManager;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

interface ModelManagerContract
{
    /**
     * Загрузить модель под управление менеджера. Все связи, пришедшие
     * внутри ретривера, считаются загруженными полностью.
     *
     * @template TModel of Model
     *
     * @param class-string<TModel> $modelClass
     * @param Closure(Builder<TModel>): TModel $retriever
     * @return TModel
     */
    public function retrieve(string $modelClass, Closure $retriever): Model;

    /**
     * То же самое, но связи помечаются как частично загруженные:
     * orphan removal по ним работает, sync() — запрещён.
     *
     * @template TModel of Model
     *
     * @param class-string<TModel> $modelClass
     * @param Closure(Builder<TModel>): TModel $retriever
     * @return TModel
     */
    public function retrievePartial(string $modelClass, Closure $retriever): Model;

    /**
     * Взять модель и весь загруженный под ней граф под управление.
     */
    public function persist(Model $model): void;

    /**
     * Под управлением ли конкретный экземпляр модели.
     */
    public function isManaged(Model $model): bool;

    /**
     * Догрузить связи через менеджер: строка — полная загрузка,
     * ключ + Closure — частичная.
     *
     * @param array<int|string, mixed>|string $relations
     */
    public function load(Model $model, array|string $relations): void;

    /**
     * Полная замена содержимого связи. Требует полной загрузки связи —
     * иначе неизвестно, что удалять.
     *
     * @param iterable<Model> $items
     */
    public function sync(Model $model, string $relationName, iterable $items): void;

    /**
     * Запланировать удаление. Выполняется в flush() последним,
     * внутри той же транзакции. Каскадов нет.
     */
    public function remove(Model $model): void;

    /**
     * Снять soft delete. UPDATE уйдёт в flush() вместе с остальным графом.
     */
    public function restore(Model $model): void;

    /**
     * Записать все накопленные изменения одной транзакцией.
     */
    public function flush(): void;

    /**
     * Полный сброс: все выданные ранее модели становятся detached.
     */
    public function clear(): void;
}
