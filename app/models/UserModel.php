<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class UserModel extends Model
{
    protected $table = 'users';
    protected $primary_key = 'id';
    protected $fillable = ['username', 'email', 'password', 'role', 'is_active'];
    protected $guarded = ['id'];
    protected $timestamps = false;
}
