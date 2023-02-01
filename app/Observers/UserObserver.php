<?php

namespace App\Observers;

use App\Models\User;
use App\Services\ProjectService\ProjectService;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Psr\SimpleCache\InvalidArgumentException;

class UserObserver
{
    /**
     * Handle the Shop "creating" event.
     *
     * @param User $user
     * @return void
     * @throws InvalidArgumentException
     */
    public function creating(User $user)
    {
        $user->uuid = Str::uuid();
        $this->projectStatus();
    }

    /**
     * Handle the User "created" event.
     *
     * @param User $user
     * @return void
     */
    public function created(User $user)
    {
        //
    }

    /**
     * Handle the User "updated" event.
     *
     * @param User $user
     * @return void
     */
    public function updated(User $user)
    {
        //
    }

    /**
     * Handle the User "deleted" event.
     *
     * @param User $user
     * @return void
     */
    public function deleted(User $user)
    {
        //
    }

    /**
     * Handle the User "restored" event.
     *
     * @param User $user
     * @return void
     */
    public function restored(User $user)
    {
        //
    }

    /**
     * Handle the User "force deleted" event.
     *
     * @param User $user
     * @return void
     */
    public function forceDeleted(User $user)
    {
        //
    }

    /**
     * @throws InvalidArgumentException
     * @throws Exception
     */
    private function projectStatus(){
        if (!cache()->has('project.status') || cache('project.status')->active != 1){
            return (new ProjectService())->activationError();
        }
    }
}
