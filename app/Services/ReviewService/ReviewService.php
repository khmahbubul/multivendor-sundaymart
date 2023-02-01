<?php

namespace App\Services\ReviewService;

use App\Models\Review;
use App\Services\CoreService;

class ReviewService extends CoreService
{

    protected function getModelClass()
    {
        return Review::class;
    }
}
