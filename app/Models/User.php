<?php

namespace App\Models;

use Modules\Access\Models\User as BaseAccessUser;

class User extends BaseAccessUser
{
    // Inherits all tenant user logic, roles, media, and tenant connection
}
