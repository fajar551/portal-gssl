<?php

namespace App\Models;

use Database;
use Illuminate\Database\Eloquent\Model;

class Ticketnote extends Model
{
	protected $table = 'ticketnotes';
	public $timestamps = false;

	protected $fillable = [
        'ticketid',
        'admin',
        'message',
        'attachments',
        'date',
        // Add any other fields that should be mass assignable
    ];

	public function __construct(array $attributes = [])
	{
		$this->table = Database::prefix() . $this->table;
		parent::__construct($attributes);
	}
}
