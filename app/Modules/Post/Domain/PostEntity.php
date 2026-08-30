<?php
declare(strict_types=1);

namespace App\Modules\Post\Domain;

use App\Modules\Post\Domain\Data\NewPostData;
use App\Modules\Post\Domain\VO\PostAuthor;
use App\Modules\Post\Domain\VO\PostContent;
use App\Modules\Post\Domain\VO\PostTitle;
use App\Modules\Post\Models\Post;
use App\Modules\Post\VO\PostCover;

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
        $this->model->setCover($value);
    }

    public function setAuthor(PostAuthor $value): void
    {
        $this->model->authorUser()->associate($value->identity->getModel());
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