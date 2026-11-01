<?php

namespace App\Models;

class DatabaseNotification extends \Illuminate\Notifications\DatabaseNotification
{
    use \App\Models\Concerns\HasRelationalFields;
}
