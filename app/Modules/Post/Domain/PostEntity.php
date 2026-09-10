<?php
declare(strict_types=1);

namespace App\Modules\Post\Domain;

use App\Modules\Post\Domain\Data\NewPostData;
use App\Modules\Post\Domain\VO\PostAuthor;
use App\Modules\Post\Domain\VO\PostContent;
use App\Modules\Post\Domain\VO\PostFileName;
use App\Modules\Post\Domain\VO\PostTitle;
use App\Modules\Post\Enums\PostMediaCollectionEnum;
use App\Modules\Post\Models\Post;
use App\Modules\Post\VO\PostCover;
use App\Modules\Post\VO\PostFile;
use App\Modules\Post\VO\PostFileIdentifier;
use Illuminate\Database\Eloquent\Collection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class PostEntity
{
    public function __construct(private Post $model)
    {
    }

    public function setTitle(PostTitle $value): void
    {
        $this->model->title = $value->value;
    }

    public function setContent(?PostContent $value): void
    {
        $this->model->content = $value?->value;
    }

    public function setCover(?PostCover $value): void
    {
        if ($value === null) {
            $this->model->clearMediaCollection(PostMediaCollectionEnum::Cover->value);

            return;
        }

        $this->model->addMedia($value)->toMediaCollection(PostMediaCollectionEnum::Cover->value);
    }

    public function setAuthor(PostAuthor $value): void
    {
        $this->model->authorUser()->associate($value->identity->getModel());
    }

    /**
     * Прикрепить файл. Строка media и сам файл появятся после коммита flush() —
     * этим занимается DeferredFileAdder, здесь возвращается та же модель,
     * которую он заполнит.
     */
    public function attachFile(PostFile $file): Media
    {
        return $this->model
            ->addMedia($file)
            ->toMediaCollection(PostMediaCollectionEnum::Files->value);
    }

    /**
     * @return Collection<int, Media>
     */
    public function files(): Collection
    {
        return $this->model->mediaFiles()->get();
    }

    /**
     * Файл поста по идентификатору. Поиск идёт в пределах связи, поэтому чужой
     * файл не найдётся, даже если знать его uuid; удалённые отсеивает
     * SoftDeletes на медиа-модели.
     */
    public function file(PostFileIdentifier $id): Media
    {
        return $this->model->mediaFiles()->where('uuid', $id->value)->firstOrFail();
    }

    /**
     * Переименование меняет отображаемое имя; имя файла на диске остаётся прежним,
     * иначе пришлось бы двигать сам файл ради косметики.
     */
    public function renameFile(PostFileIdentifier $id, PostFileName $name): Media
    {
        $media = $this->file($id);

        $media->name = $name->value;

        return $media;
    }

    public static function makeNew(NewPostData $data): self
    {
        $self = new self(new Post);

        $self->setTitle($data->title);
        $self->setContent($data->content);
        $self->setCover($data->cover);
        $self->setAuthor($data->author);

        return $self;
    }

    public function toModel(): Post
    {
        return $this->model;
    }
}