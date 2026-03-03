<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EncryptedTool extends Model
{
    protected $table = 'encrypted_tools';

    protected $fillable = ['tool_slug', 'encrypted_payload'];
}
