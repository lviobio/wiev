<?php
declare(strict_types=1);

namespace App\Core\ModelManager;

/**
 * Модель объявляет, что делать с осиротевшими записями её связей.
 *
 * Реализуется только теми агрегатами, которые действительно владеют
 * жизненным циклом дочерних записей. Отсутствие интерфейса == Ignore
 * для всех связей.
 *
 * Пример:
 *
 *   public function orphanRemovalStrategies(): array
 *   {
 *       return [
 *           'items'    => OrphanRemovalStrategy::Delete,   // позиции заказа без заказа не живут
 *           'comments' => OrphanRemovalStrategy::Nullify,  // комментарий переживает открепление
 *       ];
 *   }
 */
interface HasOrphanRemoval
{
    /**
     * @return array<string, OrphanRemovalStrategy> имя связи => стратегия
     */
    public function orphanRemovalStrategies(): array;
}
