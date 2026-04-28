<?php

namespace App\Policies;

use App\Models\Label;
use App\Models\User;

class LabelPolicy
{
    public function view(User $user, Label $label): bool
    {
        return $label->user_id === $user->id;
    }

    public function update(User $user, Label $label): bool
    {
        return $label->user_id === $user->id;
    }

    public function delete(User $user, Label $label): bool
    {
        return $label->user_id === $user->id;
    }
}
