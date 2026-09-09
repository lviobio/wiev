<?php
declare(strict_types=1);

namespace App\Core\ModelManager\Guards;

use Illuminate\Database\Eloquent\Model;

/**
 * Проверка класса модели, выполняемая при взятии её под управление.
 *
 * Точка расширения для пакетов, которые пишут в обход менеджера и потому
 * требуют на модели особой обвязки: интеграция объявляет свою проверку, и
 * модель, забывшая её подключить, падает сразу, а не молчаливо теряет данные
 * при откате транзакции.
 *
 * На вход приходит класс, а не экземпляр: пригодность модели — свойство
 * класса, поэтому менеджер выполняет проверки один раз на класс и кеширует
 * результат. Состояние конкретного объекта проверять здесь нельзя.
 *
 * Список проверок задаётся в config/model-manager.php.
 */
interface ManagedModelGuard
{
    /**
     * @param class-string<Model> $modelClass
     *
     * @throws \RuntimeException|\LogicException если модель непригодна
     */
    public function guard(string $modelClass): void;
}
