<?php

namespace App\Policies;

use Illuminate\Auth\Access\Response;

use App\Models\Publishing\Songwriter;
use App\Models\User\User;

class SongwriterPolicy
{
    protected $userHasFeature = false;

    public function __construct(User $user) {
        if($user->profile && $user->profile->feature_publishing) {
            dd('sadasd');
            $this->userHasFeature = true;
        }

        dd($this->userHasFeature);
    }

    public function access(User $user, Songwriter $songwriter): bool
    {
        dd($user->profile , $user->profile->feature_publishing, $this->userHasFeature , $user->profile->id , $songwriter->profileid);
        if ($user->profile && $this->userHasFeature && $user->profile->id === $songwriter->profileid) {
            return true;
        }
        
        return false;
    }
}
