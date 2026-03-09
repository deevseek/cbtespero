<?php

namespace App\Policies;

use App\Models\Student;
use App\Models\User;

class StudentPolicy
{
    public function before(User $user): bool|null
    {
        return $user->isAdmin() ? true : null;
    }

    public function view(User $user, Student $student): bool
    {
        return $user->student_id === $student->id;
    }
}
