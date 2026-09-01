<?php
declare(strict_types=1);

namespace App\Core\ModelManager;

use Closure;
use DB;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\MorphOneOrMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use InvalidArgumentException;
use LogicException;
use Throwable;

/**
 * Unit of Work поверх Eloquent.
 *
 * Что делает, чего не делал предыдущий вариант:
 *
 *  1. Identity map — одна строка БД == один объект в пределах менеджера.
 *  2. Снапшоты связей — при загрузке запоминается список PK. На flush()
 *     считается diff, и из него выводятся осиротевшие записи.
 *  3. Явный флаг partial — вместо попыток угадать по where'ам в билдере.
 *     Полнота коллекции объявляется на входе, а не выводится из SQL.
 *  4. Commit order — BelongsTo сохраняются до модели, HasOneOrMany после,
 *     с проставлением FK и morph type.
 *  5. Разрыв циклов — при циклической зависимости FK остаётся null,
 *     а связь дописывается отдельным UPDATE после всех вставок
 *     (аналог extraUpdates в Doctrine).
 *
 * Ограничения, о которых стоит помнить:
 *  - Составные PK не поддерживаются (как и в самом Eloquent).
 *  - Циклы разрываются только по nullable FK; если FK NOT NULL,
 *     упрётесь в constraint violation — это корректный сигнал о том,
 *     что граф неразрешим.
 *  - После неудачного flush() менеджер очищается (аналог EntityManager::close()),
 *     потому что состояние объектов в памяти рассинхронизировано с откаченной БД.
 */
class ModelManager implements ModelManagerContract
{
    /**
     * Identity map: [class-string][keyHash] => Model.
     *
     * @var array<class-string, array<string, Model>>
     */
    private array $identityMap = [];

    /**
     * Все управляемые объекты. Сильные ссылки — они же защищают от
     * переиспользования spl_object_id после сборки мусора.
     *
     * @var array<int, Model>
     */
    private array $managed = [];

    /**
     * Снапшоты загруженных связей: [oid][relationName] => {keys, partial}.
     *
     * Храним именно массив ключей, а не саму Collection: Collection мутируется
     * на месте, и ссылка на неё сделала бы diff всегда пустым.
     *
     * @var array<int, array<string, array{keys: list<int|string>, partial: bool}>>
     */
    private array $snapshots = [];

    /** @var array<int, true> сохранённые в текущем flush() */
    private array $flushed = [];

    /** @var array<int, true> находящиеся в обработке — детектор циклов */
    private array $visiting = [];

    /**
     * Модели, запланированные к удалению. Выполняются в конце flush(),
     * после всех вставок и апдейтов — как deletes в Doctrine.
     *
     * @var array<int, Model>
     */
    private array $removals = [];

    /**
     * Отложенные ассоциации для разорванных циклов.
     *
     * @var list<array{0: Model, 1: string, 2: Model}>
     */
    private array $extraUpdates = [];

    /** @var list<Closure> */
    private array $afterFlushCallbacks = [];

    private bool $inFlush = false;

    // -----------------------------------------------------------------
    // Публичное API
    // -----------------------------------------------------------------

    /**
     * {@inheritDoc}
     *
     * Все связи, пришедшие внутри ретривера (через with()), считаются
     * загруженными полностью. Если внутри был констрейнт — используйте
     * retrievePartial().
     */
    public function retrieve(string $modelClass, Closure $retriever): Model
    {
        return $this->doRetrieve($modelClass, $retriever, partial: false);
    }

    /**
     * То же самое, но связи помечаются как частично загруженные:
     * orphan removal по ним работает, sync — запрещён.
     */
    public function retrievePartial(string $modelClass, Closure $retriever): Model
    {
        return $this->doRetrieve($modelClass, $retriever, partial: true);
    }

    public function persist(Model $model): void
    {
        $this->manageGraph($model, partial: false);
    }

    /**
     * Под управлением ли конкретный экземпляр. Именно экземпляр, а не строка:
     * другой объект той же строки менеджером не отслеживается, и знать об этом
     * важно тому, кто решает, писать сейчас или отложить до flush().
     */
    public function isManaged(Model $model): bool
    {
        return isset($this->managed[spl_object_id($model)]);
    }

    /**
     * Загрузка связей через менеджер вместо голого $model->load().
     *
     * Правило простое и не требует интроспекции билдера:
     *   строка без замыкания   => полная загрузка
     *   ключ + Closure         => частичная
     *
     *   $manager->load($user, ['profile', 'posts' => fn ($q) => $q->active()]);
     */
    public function load(Model $model, array|string $relations): void
    {
        $relations = is_string($relations) ? [$relations] : $relations;

        $model->load($relations);

        foreach ($relations as $key => $value) {
            $name    = is_int($key) ? $value : $key;
            $partial = !is_int($key) && $value instanceof Closure;

            // для вложенных 'posts.comments' констрейнт относится к листу,
            // промежуточные звенья считаем полными
            $segments = explode('.', $name);
            $cursor   = [$model];

            foreach ($segments as $i => $segment) {
                $isLeaf = $i === array_key_last($segments);
                $next   = [];

                foreach ($cursor as $owner) {
                    if (!$owner instanceof Model || !$owner->relationLoaded($segment)) {
                        continue;
                    }

                    $this->manageGraph($owner, $isLeaf && $partial);
                    $this->snapshotRelation($owner, $segment, $isLeaf && $partial);

                    foreach ($this->toModels($owner->getRelation($segment)) as $child) {
                        $next[] = $child;
                    }
                }

                $cursor = $next;
            }
        }
    }

    /**
     * Полная замена содержимого связи. Разрешена только если связь
     * была загружена целиком — иначе мы не знаем, что удалять.
     */
    public function sync(Model $model, string $relationName, iterable $items): void
    {
        $snapshot = $this->snapshots[spl_object_id($model)][$relationName] ?? null;

        if ($snapshot === null) {
            throw new LogicException(sprintf(
                'Cannot sync relation "%s" of %s: it was never loaded through the manager, '
                . 'so there is no snapshot to diff against.',
                $relationName,
                $model::class,
            ));
        }

        if ($snapshot['partial']) {
            throw new LogicException(sprintf(
                'Cannot sync partially loaded relation "%s" of %s: the loaded set is not '
                . 'authoritative. Reload it without constraints first.',
                $relationName,
                $model::class,
            ));
        }

        $collection = new Collection();

        foreach ($items as $item) {
            $collection->push($this->manageGraph($item, partial: false));
        }

        $model->setRelation($relationName, $collection);
    }

    /**
     * Запланировать удаление. Строка удаляется в flush() последней —
     * после всех вставок и апдейтов, но внутри той же транзакции.
     *
     * Каскадов нет намеренно: связанными записями распоряжается либо БД
     * (ON DELETE), либо обсерверы модели. Менеджер не знает, кто чем владеет.
     *
     * Ещё не существующая в БД модель просто снимается с управления.
     * Удаление приоритетнее persist(): помеченная модель не сохраняется,
     * даже если она грязная.
     */
    public function remove(Model $model): void
    {
        $canonical = $this->canonical($model);
        $oid       = spl_object_id($canonical);

        if (!$canonical->exists) {
            unset($this->managed[$oid], $this->snapshots[$oid]);

            return;
        }

        $this->removals[$oid] = $canonical;
    }

    /**
     * Снятие soft delete. UPDATE уйдёт в flush() вместе с остальным графом.
     *
     * События restoring/restored не стреляют: запись отложена, а событие
     * до записи — ложь. Побочные эффекты вешайте на afterFlush-колбэки.
     */
    public function restore(Model $model): void
    {
        if (!method_exists($model, 'getDeletedAtColumn')) {
            throw new LogicException(sprintf(
                'Cannot restore %s: the model does not use SoftDeletes.',
                $model::class,
            ));
        }

        $canonical = $this->manageGraph($model, partial: false);

        $canonical->setAttribute($model->getDeletedAtColumn(), null);

        unset($this->removals[spl_object_id($canonical)]);
    }

    /**
     * Колбэк, выполняемый после успешного коммита flush().
     *
     * Для эффектов, которые нельзя откатить и потому нельзя пускать внутрь
     * транзакции: запись и удаление файлов, отправка писем, вызовы наружу.
     * Если flush() упадёт, накопленные колбэки будут выброшены вместе с
     * остальным состоянием менеджера.
     */
    public function afterFlush(Closure $callback): void
    {
        $this->afterFlushCallbacks[] = $callback;
    }

    /**
     * @throws Throwable
     */
    public function flush(): void
    {
        if ($this->inFlush) {
            throw new LogicException('Nested flush() is not supported.');
        }

        $this->inFlush      = true;
        $this->flushed      = [];
        $this->visiting     = [];
        $this->extraUpdates = [];

        DB::beginTransaction();

        try {
            // managed пополняется во время обхода (новые модели из связей),
            // поэтому обычный foreach не годится — он итерирует копию.
            $processed = [];

            while (($pending = array_diff_key($this->managed, $processed)) !== []) {
                foreach ($pending as $oid => $model) {
                    $processed[$oid] = true;
                    $this->saveGraph($model);
                }
            }

            $this->applyExtraUpdates();
            $this->applyRemovals();

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            $this->inFlush = false;
            $this->clear();

            throw $e;
        }

        $this->inFlush = false;

        // Снапшоты после успешной записи становятся новым «оригиналом».
        $this->resnapshot();

        $callbacks                 = $this->afterFlushCallbacks;
        $this->afterFlushCallbacks = [];

        foreach ($callbacks as $callback) {
            $callback();
        }
    }

    /**
     * Полный сброс. Все ранее выданные модели становятся detached:
     * менеджер больше не отслеживает их изменения.
     */
    public function clear(): void
    {
        $this->identityMap         = [];
        $this->managed             = [];
        $this->snapshots           = [];
        $this->flushed             = [];
        $this->visiting            = [];
        $this->extraUpdates        = [];
        $this->removals            = [];
        $this->afterFlushCallbacks = [];
    }

    // -----------------------------------------------------------------
    // Регистрация и identity map
    // -----------------------------------------------------------------

    private function doRetrieve(string $modelClass, Closure $retriever, bool $partial): Model
    {
        $model = $retriever($modelClass::query());

        if (!$model instanceof Model) {
            throw new InvalidArgumentException(sprintf(
                'Retriever for %s must return a Model, %s given.',
                $modelClass,
                get_debug_type($model),
            ));
        }

        return $this->manageGraph($model, $partial);
    }

    /**
     * Регистрирует модель и весь загруженный граф под ней.
     * Возвращает канонический экземпляр из identity map.
     *
     * @param array<int, true> $visited
     */
    private function manageGraph(Model $model, bool $partial, array &$visited = []): Model
    {
        $canonical = $this->manage($model, $partial);

        $oid = spl_object_id($canonical);

        if (isset($visited[$oid])) {
            return $canonical;
        }

        $visited[$oid] = true;

        foreach ($canonical->getRelations() as $name => $value) {
            if ($value instanceof Model) {
                $managed = $this->manageGraph($value, $partial, $visited);

                if ($managed !== $value) {
                    $canonical->setRelation($name, $managed);
                }

                continue;
            }

            if (!$value instanceof Collection) {
                continue;
            }

            $items   = $value->all();
            $changed = false;

            foreach ($items as $i => $item) {
                if (!$item instanceof Model) {
                    continue;
                }

                $managed = $this->manageGraph($item, $partial, $visited);

                if ($managed !== $item) {
                    $items[$i] = $managed;
                    $changed   = true;
                }
            }

            if ($changed) {
                $canonical->setRelation($name, new Collection(array_values($items)));
            }
        }

        return $canonical;
    }

    /**
     * Идемпотентная регистрация одной модели.
     */
    private function manage(Model $model, bool $partial): Model
    {
        $oid = spl_object_id($model);

        if (isset($this->managed[$oid])) {
            return $model;
        }

        if ($model->exists && $model->getKey() !== null) {
            $hash     = $this->keyHash($model);
            $existing = $this->identityMap[$model::class][$hash] ?? null;

            if ($existing !== null && $existing !== $model) {
                // Как в Doctrine: результат свежей гидрации отбрасывается,
                // канонический объект в памяти не перезатирается.
                return $existing;
            }

            $this->identityMap[$model::class][$hash] = $model;
        }

        $this->managed[$oid] = $model;

        $this->snapshotAll($model, $partial);

        return $model;
    }

    private function keyHash(Model $model): string
    {
        return (string) $model->getKey();
    }

    /**
     * Канонический экземпляр строки, если она уже известна менеджеру.
     * В отличие от manage() ничего не регистрирует — нужен там, где
     * брать модель под управление незачем (например, перед удалением).
     */
    private function canonical(Model $model): Model
    {
        if (!$model->exists || $model->getKey() === null) {
            return $model;
        }

        return $this->identityMap[$model::class][$this->keyHash($model)] ?? $model;
    }

    // -----------------------------------------------------------------
    // Снапшоты
    // -----------------------------------------------------------------

    private function snapshotAll(Model $model, bool $partial): void
    {
        foreach (array_keys($model->getRelations()) as $name) {
            $this->snapshotRelation($model, (string) $name, $partial);
        }
    }

    private function snapshotRelation(Model $model, string $name, bool $partial): void
    {
        if (!$model->relationLoaded($name)) {
            return;
        }

        $keys = [];

        foreach ($this->toModels($model->getRelation($name)) as $related) {
            if ($related->exists && $related->getKey() !== null) {
                $keys[] = $related->getKey();
            }
        }

        $this->snapshots[spl_object_id($model)][$name] = [
            'keys'    => $keys,
            'partial' => $partial,
        ];
    }

    /**
     * Пересчёт снапшотов после успешного коммита.
     * Флаг partial сохраняется — полнота набора от записи не меняется.
     */
    private function resnapshot(): void
    {
        foreach ($this->managed as $oid => $model) {
            $known = $this->snapshots[$oid] ?? [];

            foreach ($model->getRelations() as $name => $_) {
                $name    = (string) $name;
                $partial = $known[$name]['partial'] ?? false;

                $this->snapshotRelation($model, $name, $partial);
            }
        }
    }

    // -----------------------------------------------------------------
    // Обход графа и запись
    // -----------------------------------------------------------------

    private function saveGraph(Model $model): void
    {
        $oid = spl_object_id($model);

        if (isset($this->flushed[$oid]) || isset($this->removals[$oid])) {
            return;
        }

        if (isset($this->visiting[$oid])) {
            // Цикл. Вызывающая сторона зарегистрирует extraUpdate,
            // FK на этом шаге останется null.
            return;
        }

        $this->visiting[$oid] = true;

        try {
            $this->saveParents($model);
            $this->persistModel($model);
            $this->saveChildren($model);
            $this->syncPivots($model);
            $this->removeOrphans($model);
        } finally {
            unset($this->visiting[$oid]);
        }
    }

    /**
     * BelongsTo / MorphTo — владеющая сторона у нас, родитель нужен раньше.
     */
    private function saveParents(Model $model): void
    {
        foreach ($model->getRelations() as $name => $value) {
            if (!$value instanceof Model) {
                continue;
            }

            $relation = $this->resolveRelation($model, (string) $name);

            if (!$relation instanceof BelongsTo) {
                continue;
            }

            $parentOid = spl_object_id($value);

            if (isset($this->visiting[$parentOid]) && !isset($this->flushed[$parentOid])) {
                // Цикл: откладываем на extraUpdate, FK должен быть nullable.
                $this->extraUpdates[] = [$model, (string) $name, $value];

                continue;
            }

            $this->saveGraph($value);

            $relation->associate($value);
        }
    }

    private function persistModel(Model $model): void
    {
        $model->save();

        $oid = spl_object_id($model);

        $this->flushed[$oid] = true;
        $this->managed[$oid] = $model;

        if ($model->getKey() !== null) {
            $this->identityMap[$model::class][$this->keyHash($model)] = $model;
        }
    }

    /**
     * HasOne / HasMany / MorphOne / MorphMany — FK живёт у ребёнка,
     * поэтому проставляем его после сохранения родителя.
     */
    private function saveChildren(Model $model): void
    {
        foreach ($model->getRelations() as $name => $value) {
            $relation = $this->resolveRelation($model, (string) $name);

            if (!$relation instanceof HasOneOrMany) {
                continue;
            }

            $foreignKey = $relation->getForeignKeyName();
            $localValue = $model->getAttribute($relation->getLocalKeyName());

            foreach ($this->toModels($value) as $child) {
                $child->setAttribute($foreignKey, $localValue);

                if ($relation instanceof MorphOneOrMany) {
                    $child->setAttribute($relation->getMorphType(), $model->getMorphClass());
                }

                $this->saveGraph($child);
            }
        }
    }

    /**
     * BelongsToMany / MorphToMany — работаем с pivot-строками.
     */
    private function syncPivots(Model $model): void
    {
        foreach ($model->getRelations() as $name => $value) {
            $name     = (string) $name;
            $relation = $this->resolveRelation($model, $name);

            if (!$relation instanceof BelongsToMany) {
                continue;
            }

            $current = [];

            foreach ($this->toModels($value) as $related) {
                $this->saveGraph($related);

                if ($related->getKey() !== null) {
                    $current[] = $related->getKey();
                }
            }

            $snapshot = $this->snapshots[spl_object_id($model)][$name] ?? null;
            $known    = $snapshot['keys'] ?? [];

            $attach = array_values(array_diff($current, $known));

            if ($attach !== []) {
                $relation->attach($attach);
            }

            // Отцепляем только если знали полный набор.
            if ($snapshot !== null && !$snapshot['partial']) {
                $detach = array_values(array_diff($known, $current));

                if ($detach !== []) {
                    $relation->detach($detach);
                }
            }
        }
    }

    /**
     * Записи, которые были в снапшоте, но исчезли из коллекции.
     *
     * Работает и для частично загруженных связей: diff считается
     * относительно снапшота, а не относительно содержимого таблицы,
     * поэтому незагруженные строки физически не могут попасть под удаление.
     */
    private function removeOrphans(Model $model): void
    {
        $snapshots = $this->snapshots[spl_object_id($model)] ?? [];

        foreach ($snapshots as $name => $snapshot) {
            $name = (string) $name;

            $strategy = $this->orphanStrategy($model, $name);

            if ($strategy === OrphanRemovalStrategy::Ignore) {
                continue;
            }

            if (!$model->relationLoaded($name)) {
                continue;
            }

            $relation = $this->resolveRelation($model, $name);

            // BelongsToMany уже обработан в syncPivots, BelongsTo сиротами не владеет.
            if (!$relation instanceof HasOneOrMany) {
                continue;
            }

            $current = [];

            foreach ($this->toModels($model->getRelation($name)) as $related) {
                if ($related->getKey() !== null) {
                    $current[] = $related->getKey();
                }
            }

            $removed = array_values(array_diff($snapshot['keys'], $current));

            if ($removed === []) {
                continue;
            }

            $query = $relation->getRelated()->newQuery()->whereKey($removed);

            if ($strategy === OrphanRemovalStrategy::Delete) {
                $query->delete();

                continue;
            }

            $attributes = [$relation->getForeignKeyName() => null];

            if ($relation instanceof MorphOneOrMany) {
                $attributes[$relation->getMorphType()] = null;
            }

            $query->update($attributes);
        }
    }

    /**
     * Дописывание FK, отложенных из-за циклической зависимости.
     */
    private function applyExtraUpdates(): void
    {
        foreach ($this->extraUpdates as [$model, $name, $parent]) {
            $relation = $this->resolveRelation($model, $name);

            if (!$relation instanceof BelongsTo) {
                continue;
            }

            $relation->associate($parent);

            if ($model->isDirty()) {
                $model->save();
            }
        }

        $this->extraUpdates = [];
    }

    /**
     * Удаления идут последними и в обратном порядке регистрации:
     * то, что пометили позже, обычно лежит глубже в графе.
     */
    private function applyRemovals(): void
    {
        foreach (array_reverse($this->removals, preserve_keys: true) as $oid => $model) {
            $model->delete();

            unset($this->managed[$oid], $this->snapshots[$oid], $this->flushed[$oid]);

            if ($model->getKey() !== null) {
                unset($this->identityMap[$model::class][$this->keyHash($model)]);
            }
        }

        $this->removals = [];
    }

    // -----------------------------------------------------------------
    // Вспомогательное
    // -----------------------------------------------------------------

    private function orphanStrategy(Model $model, string $relationName): OrphanRemovalStrategy
    {
        if (!$model instanceof HasOrphanRemoval) {
            return OrphanRemovalStrategy::Ignore;
        }

        return $model->orphanRemovalStrategies()[$relationName] ?? OrphanRemovalStrategy::Ignore;
    }

    private function resolveRelation(Model $model, string $name): ?Relation
    {
        if (!method_exists($model, $name)) {
            return null;
        }

        try {
            $relation = $model->{$name}();
        } catch (Throwable) {
            return null;
        }

        return $relation instanceof Relation ? $relation : null;
    }

    /**
     * @return list<Model>
     */
    private function toModels(mixed $value): array
    {
        if ($value instanceof Model) {
            return [$value];
        }

        if ($value instanceof Collection) {
            return array_values(array_filter(
                $value->all(),
                static fn (mixed $item): bool => $item instanceof Model,
            ));
        }

        return [];
    }
}
