<?php

namespace App\Repositories;

use App\Models\FactCheck;
use Illuminate\Database\Eloquent\Collection;

class FactCheckRepository extends BaseRepository
{
    public function __construct(FactCheck $model)
    {
        parent::__construct($model);
    }

    public function getHistoryForUser(int $userId): Collection
    {
        return $this->model->where('user_id', $userId)->orderBy('created_at', 'desc')->get();
    }
}
