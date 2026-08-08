# HTTP Layer Generator

Генерирует контроллер модуля и его роуты из декларации `app/Modules/<Module>/Http/generator.php`.

Смысл: доменная логика уже живёт в Actions (`XAction` + `XData`), а контроллер — это клей. В `PostController` из 191 строки ~140 занимала OA-разметка, а тела методов сводились к трём формам. Генератор убирает этот клей и выводит OpenAPI из Model / Data / Resource / Query.

Результат — **обычные PHP-файлы, которые коммитятся**. Никакой рантайм-магии: работают IDE, статический анализ и сканер l5-swagger.

```bash
docker compose exec php /app/artisan http:generate Post
```

---

## Быстрый старт

Минимальная декларация — [`app/Modules/Post/Http/generator.php`](../../../Modules/Post/Http/generator.php):

```php
return ControllerDefinition::make(PostController::class)
    ->model(Post::class)
    ->resource(PostResource::class)
    ->endpoints(
        Endpoint::index(PostIndexQuery::class),

        Endpoint::show(ShowPostAction::class)
            ->fill(
                new FillFromAuthenticatedUser('actorUser'),
                new FillFromRouteParameter('id', 'post'),
            ),

        Endpoint::store(CreatePostAction::class)
            ->fill(new FillFromAuthenticatedUser('authorUser')),
    );
```

**Data-класс не указывается** — он берётся рефлексией из типа первого параметра `Action::__invoke()`. Указывать нужно только Action и то, чего в запросе нет: кто заполняет `actorUser`, откуда берётся `id`.

### Несколько контроллеров в одном модуле

`generator.php` может вернуть массив — тогда сгенерируется несколько контроллеров, а их блоки роутов уживутся в одном `routes.php`:

```php
return [
    ControllerDefinition::make(PostController::class)
        ->model(Post::class)
        ->resource(PostResource::class)
        ->endpoints(...),

    ControllerDefinition::make(PostArchiveController::class)
        ->model(Post::class)
        ->resource(PostResource::class)
        ->routePrefix('archived-posts')
        ->tag('archive')
        ->endpoints(...),
];
```

Блоки роутов разделяются по имени контроллера (`@generated-routes:start PostArchiveController`), так что перегенерация одного не задевает другой. Переопределить имя маркера — `->routesMarker('...')`; менять его у существующего блока нельзя, не переименовав маркеры в `routes.php` вручную, иначе старый блок останется сиротой, а новый допишется рядом.

Обратите внимание: два контроллера с одной моделью получат одинаковые route prefix, tag и operationId — второму нужно задать хотя бы `->routePrefix()` и `->tag()`, иначе роуты столкнутся, а operationId в спеке задвоятся.

Команда:

| Флаг | Что делает |
|---|---|
| *(без флагов)* | пишет файлы |
| `--check` | ничего не пишет, exit 1 если что-то устарело (используется в тесте) |
| `--diff` | печатает unified diff без записи |
| `--force` | перезаписать контроллер без `@generated` в шапке |
| `--no-routes` | не трогать `routes.php` в этом прогоне |
| `-v` | показать сводку «эндпоинт → Action» |

Аргумент — имя модуля (`Post`) или имя конкретного контроллера (`PostController`). Без него обрабатываются все найденные `app/**/Http/generator.php`.

---

## Что выводится само

Из `->model(Post::class)`:

| | |
|---|---|
| route prefix / name prefix | `posts` / `posts.` |
| роут-параметр | `post` |
| tag | `posts` |
| OA-путь | `/api/v1/posts`, `/api/v1/posts/{post}` |
| operationId | `getPosts`, `getPost`, `createPost`, `updatePost`, `deletePost` |
| summary | `List posts`, `Show post`, `Create post`, … |
| описания ответов | `Post created`, `Post updated`, `Post not found`, … |

Из рефлексии Action и Data:

| | |
|---|---|
| Data-класс | первый параметр `__invoke()` |
| тип возврата метода и success-ответ | возвращаемый тип `__invoke()`: `Model` → Resource, `void` → 204, коллекция → `PaginatedResourceResponse` |
| `OA\PathParameter` | каждый `{placeholder}` в URI |
| `requestBody` | есть, если у Data остались параметры конструктора вне `->fill()` |
| media type | `multipart/form-data` при наличии `UploadedFile`, иначе `application/json` |
| HTTP-глагол в спеке | тот же, что у роута |
| `424` | если эндпоинт достаёт запись по роут-параметру (есть `FillFromRouteParameter`) |

Тело запроса **никогда не расписывается по свойствам** — это `new OA\Schema(ref: CreatePostData::class)`, а схему собирает сам swagger-php из `#[OA\Property]` на Data. Практическое следствие: добавили поле в Data — контроллер не меняется, `--check` останется зелёным, и это правильно.

Query-параметры `index` читаются из Query-класса: `page`, `per_page`, `sort` (enum из `allowedSorts` и их `-`-вариантов) и по параметру на каждый `allowedFilter`.

Для кастомных эндпоинтов существительное подставляется по-разному в зависимости от имени метода: односложное имя его получает (`restore` → `Restore post` / `restorePost`), многословное уже само называет объект (`removeCover` → `Remove cover` / `removePostCover`). В operationId существительное есть всегда — идентификаторы операций глобальны, и `removeCover` может встретиться в двух модулях.

### Курсорная пагинация

`->cursor()` на listing-эндпоинте переключает и код, и документацию:

```php
Endpoint::index(PostIndexQuery::class)->cursor()
```

Тело становится `$query->cursorPaginate()`, в спеке `page` заменяется на `cursor`, а конверт ответа — на `CursorPagination`. Сортировки, фильтры и `per_page` работают как обычно.

---

## Сложные случаи

Escape hatch'и идут от самого лёгкого к самому тяжёлому. **Берите первый, который решает задачу.**

### 1. Поменять текст

```php
Endpoint::show(ShowPostAction::class)
    ->summary('Public post view')
    ->operationId('viewPublicPost')
    ->description('...')
    ->tags('posts', 'public')
    ->deprecated()
```

Заданное значение отключает вывод только для своего ключа, остальное выводится как обычно.

`->docblock(...)` добавляет над методом обычный PHPDoc — туда стоит писать объяснения «почему так», которым в OpenAPI не место.

#### `_method` spoofing

Глагол в спеке всегда совпадает с роутом: `PUT` документируется как `PUT`. Костыль с `_method=PUT` нужен только формам в браузере, которые PUT отправить не умеют, — нормальный API-клиент шлёт настоящий `PUT`, и документировать эндпоинт как `POST` значило бы врать всем остальным. На рантайм это не влияет: Laravel по-прежнему принимает `POST` с полем `_method`.

Если клиент эндпоинта — именно браузерная форма, опишите это явно:

```php
Endpoint::update(UpdatePostAction::class)->documentAs(HttpMethod::Post)
```

Тогда операция задокументируется как `POST`, в схему тела добавится обязательное поле `_method`, а в `description` попадёт объяснение — но только у этого эндпоинта и только по вашей просьбе.

### 2. Авторизация

По умолчанию `security: [['bearerAuth' => []]]`. На уровне модуля — `->security(...)` / `->public()`, на уровне эндпоинта — то же самое, эндпоинт перебивает модуль.

```php
Endpoint::show(ShowPostAction::class)->public()
```

> Имейте в виду: `security` в спеке и реальный `auth:sanctum` в `routes/api.php` — независимы. Их расхождение ловит тест `tests/Feature/Support/OpenApi/SpecMatchesRoutesTest.php`.

`security` отвечает только за аутентификацию. Права проверяются в Action — там для этого уже лежит `actorUser`, заполняемый `FillFromAuthenticatedUser`:

```php
Gate::forUser($data->actorUser)->authorize('update', $model);
```

Именно `forUser($data->actorUser)`, а не `Gate::authorize()`: Action не должен зависеть от текущего запроса, иначе его нельзя вызвать из очереди или консоли. Пример — [PostPolicy](../../../Modules/Post/Policies/PostPolicy.php), зарегистрирована явным `Gate::policy()` в провайдере модуля (автодискавери Laravel ищет политики в `App\Policies`, а модуль держит свою рядом с моделью).

Генератор про политики не знает, поэтому **403 объявляется руками**:

```php
->addResponses(Gh::response(403, 'Only the author can edit this post'))
```

Порядок важен: `findOrFail` должен идти до `authorize`, иначе по коду ответа можно перебирать существующие id.

### 3. Добавить параметр или ответ

```php
use App\Support\Http\Generator\Php\Gh;

Endpoint::index(PostIndexQuery::class)
    ->addParameters(Gh::queryParameter('with_drafts', 'boolean', 'Include drafts'))
    ->addResponses(Gh::response(422, 'Validation failed'))
```

Добавленное идёт **после** выведенного. `Gh::` строит узлы так, чтобы `use`-блок собрался правильно; для атрибутов без шортката — `Gh::node(SomeAttribute::class, [...])`.

Если выведенный ответ не просто неполон, а неверен — `->responses(...)` заменяет весь список целиком:

```php
->responses(
    Gh::response(204, 'Post cover removed'),
    Gh::response(424, 'Post not found'),
)
```

Нужно в основном для callback-эндпоинтов: генератор не знает, что возвращает замыкание, и по умолчанию пишет `200`.

### 4. Свой глагол и URI

```php
Endpoint::make(
    HttpMethod::Post,
    Endpoint::MODEL_PARAMETER . '/publish',   // → posts/{post}/publish
    controllerMethod: 'publish',
    actionClass: PublishPostAction::class,
)
    ->fill(new FillFromRouteParameter('id', 'post'))
    ->middleware('throttle:publish')
```

`Endpoint::MODEL_PARAMETER` подставляет роут-параметр модуля — не хардкодьте `{post}`, иначе переименование модели не доедет.

Эндпоинты с URI под `{post}` автоматически собираются во вложенную `Route::group(['prefix' => '{post}'])`, как это принято в проекте.

### 5. Метод без Action — сырым кодом

Если операция настолько мелкая, что Action под неё заводить незачем, передайте `callback:` — замыкание целиком станет методом контроллера:

```php
Endpoint::make(
    HttpMethod::Get,
    uri: 'echo',
    controllerMethod: 'echoTest',
    callback: function (Request $request) {
        return $request->input('echo');
    },
)
```

даёт

```php
public function echoTest(Request $request)
{
    return $request->input('echo');
}
```

Сигнатура берётся из замыкания как есть — типы параметров, возвращаемый тип, `Request` инжектится Laravel'ом обычным образом. Стрелочные функции тоже работают: `fn(Request $request): string => Str::upper(...)` превратится в метод с `return`.

**Импорты разрешаются автоматически.** Классы, которые называет замыкание, резолвятся по `use`-блоку самого `generator.php`, и генератор добавляет их в `use` контроллера. Писать FQCN не нужно.

Как это устроено: рефлексия даёт только файл и диапазон строк, поэтому тело достаётся разбором `generator.php` через `nikic/php-parser` (уже есть в зависимостях, ставить `opis/closure` не понадобилось). AST заодно даёт резолв имён — то, чего сериализаторы замыканий не умеют.

Отсюда три ограничения:

- **`use ($var)` запрещён** — захваченную переменную в метод не перенести. Генератор падает с внятной ошибкой; подставьте значение прямо в тело.
- **Тело перепечатывается из AST**, поэтому пустые строки внутри и мелочи форматирования (`(string) $x` вместо `(string)$x`) нормализуются. Комментарии сохраняются.
- **Два замыкания на одних и тех же строках** различить нечем — генератор об этом скажет, разнесите по строкам.

Документация у такого эндпоинта минимальна: вернуть замыкание может что угодно, поэтому выводится только `200 Successful operation`, а `424` не выводится вовсе — обещать «не найдено» там, где ничего не ищется по id, было бы враньём. Уточняйте через `->addResponses()`. `summary` и `operationId` тоже стоит задать явно.

### 6. Своё тело метода при существующем Action

Когда логики больше, чем `return Resource::loaded($action($data))`:

```php
Endpoint::make(HttpMethod::Post, 'import', 'import', actionClass: ImportPostsAction::class)
    ->body(
        <<<'PHP'
        $result = $action($data);

        return response()->json(['imported' => $result->count()], 202);
        PHP,
        JsonResponse::class,   // классы, которые нужно импортировать
    )
```

Сигнатура, атрибуты и роут по-прежнему генерируются — заменяется только тело. Сырая строка здесь допустима: вывод коммитится и проходит ревью как обычный код.

Если из-за своего тела меняется тип возврата — этот случай генератор пока не покрывает, и правильнее взять пункт 5 или 8.

### 7. Полностью своя OA-разметка

```php
Endpoint::destroy(DestroyPostAction::class)
    ->openApi(Gh::operation(OA\Delete::class, [
        'path' => Gh::value('/api/v1/posts/{post}'),
        'operationId' => Gh::value('deletePost'),
        'responses' => Gh::value([]),
    ]))
```

Заменяет весь выведенный атрибут; метод и роут генерируются как обычно. Крайний вариант — `->withoutOpenApi()`: метод будет, документации не будет.

### 8. Не генерировать вовсе

Не описывайте эндпоинт в `generator.php`. Заведите отдельный рукописный контроллер и добавьте роут в `routes.php` **снаружи маркеров** — генератор его не тронет (см. ниже).

Если у модуля рукописных эндпоинтов больше, чем сгенерированных, — `->withoutRoutes()` на декларации, и весь `routes.php` ваш.

---

## `routes.php` — маркеры

Контроллер принадлежит генератору целиком. `routes.php` — **нет**: генератор владеет только участком между маркерами.

```php
use App\Modules\Post\Http\Controllers as C;
use Illuminate\Support\Facades\Route;

// @generated-routes:start PostController
Route::group(['prefix' => 'posts', 'as' => 'posts.', 'controller' => C\PostController::class], function () {
    Route::get('/', 'index')->name('index');
    ...
});
// @generated-routes:end PostController

// это генератор не тронет
Route::get('posts/export', [PostExportController::class, 'export'])->name('posts.export');
```

Правила:

- маркеры есть → переписывается только текст между ними;
- маркеров нет → блок дописывается в конец, ничего не удаляется;
- файла нет → создаётся с нужными `use`;
- отсутствующие `use` дописываются, существующие не дублируются и не переупорядочиваются;
- имя в маркерах — имя контроллера, поэтому несколько сгенерированных блоков спокойно живут в одном файле;
- `->withoutRoutes()` → файл не открывается вообще.

**Подключение модуля в `routes/api.php` остаётся ручным** — генератор туда не лезет.

Поведение маркеров закреплено тестами в `tests/Feature/Support/Http/Generator/RouteMarkersTest.php`.

---

## Фильтры: когда нужен дескриптор

`AllowedFilter::partial()`, `::exact()`, `::trashed()`, `::belongsTo()` генератор распознаёт по классу стратегии — делать ничего не нужно.

А вот всё, что построено через `AllowedFilter::callback()`, по рефлексии неразличимо: колонки спрятаны в замыкании, класс у всех `FiltersCallback`. Такие фильтры описывают себя сами — см. `search()` и `dateRange()` в [`app/Support/Spatie/QueryBuilder/AllowedFilter.php`](../../Spatie/QueryBuilder/AllowedFilter.php):

```php
return self::callback($filterName, $closure)->openApi(
    new FilterParameterDescriptor(
        type: 'integer',
        format: 'int64',
        description: 'Unix timestamp in milliseconds',
        suffixPath: ['from'],      // → filter[created_at][from]
    ),
    // ...второй дескриптор для [to]
);
```

Один фильтр может разворачиваться в несколько параметров — за это отвечает `suffixPath`.

Если фильтр не распознан и дескриптора нет, генератор задокументирует его как `string` **и напечатает предупреждение** с именем фильтра. Предупреждение — не ошибка, генерация не падает; но молча неверная спека хуже, чем шумная.

---

## Eager loading

Догрузка связей объявляется в Resource, а не в контроллере — там можно держать замыкания, которые в генерируемый код не сериализуются:

```php
class PostResource extends JsonResource
{
    public static function eagerLoads(): array
    {
        return [
            'authorUser',
            'media' => fn($q) => $q->whereCollectionName(PostMediaCollectionEnum::Cover->value),
        ];
    }
}
```

Сгенерированный метод вызывает `PostResource::loaded($action($data))`, который применит этот список. Тот же список переиспользует `PostIndexQuery` — раньше он дублировался в двух местах.

---

## Как не сломать

Генерируемые файлы коммитятся, значит могут разойтись с источником. Страховка — тест `GeneratedHttpLayerIsUpToDateTest`, который гоняет `http:generate --check`. Он падает, если кто-то отредактировал сгенерированный контроллер руками или изменил Action / Query без перегенерации. CI уже запускает Pest, отдельной настройки не нужно.

Дополнительно контроллер без `@generated` в шапке не перезаписывается без `--force` — чтобы `http:generate` не снёс рукописный файл.

**Форматирование вывода — не Pint.** В проекте нет `pint.json`, а пресет `laravel` ставит пустую строку после `<?php`, что противоречит принятому здесь `declare(strict_types=1);` на второй строке. Формат держит собственный `Php\Printer` (ширина 140, отступ 4, trailing comma).

---

## Устройство

| Namespace | Ответственность |
|---|---|
| `Generator\` | `ControllerDefinition`, `Endpoint`, `Naming`, `EndpointPlanner`, `DefinitionLocator` |
| `Generator\Introspection\` | чтение Action / Data / фиксеров / Query |
| `Generator\OpenApi\` | сборка OA-атрибута из плана |
| `Generator\Php\` | эмиссия исходника: `Expr`-дерево, `ImportCollector`, `Printer`, `Gh` |
| `Generator\Render\` | `ControllerRenderer`, `RoutesRenderer`, `RoutesWriter` |

Поток: `DefinitionLocator` находит декларации → `EndpointPlanner` резолвит рефлексию один раз в `EndpointPlan` → `OperationFactory` собирает OA → `MethodRenderer` / `ControllerRenderer` печатают файл → `RoutesWriter` вклеивает роуты по маркерам.

Команда — `app/Console/Commands/GenerateHttpLayerCommand.php`.

> В `bootstrap/app.php` есть строка `->withCommands()`. Без неё каталог `app/Console/Commands` не сканируется: `withRouting(commands: ...)` вызывает `withCommands()` с непустым массивом, что глушит автоподхват. Не удаляйте её.
