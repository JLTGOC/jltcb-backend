<?php

namespace App\Repositories\JobOrder;

use App\Http\Resources\JobOrderResource;
use App\Repositories\BaseRepository;

class ShowJobOrderRepository extends BaseRepository
{
    public function execute($jobOrder){
        return $this->success('Job Order fetched successfully', new JobOrderResource($jobOrder), 200);
    }
}
