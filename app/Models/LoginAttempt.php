<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class LoginAttempt extends Model
{
    use HasFactory;

    protected $primaryKey = 'id';

    protected $fillable = [
        'user_id',
        'email',
        'ip_address',
        'user_agent',
        'token_id',
        'successful',
        'attempted_at',
    ];

    protected $casts = [
        'successful' => 'boolean',
        'attempted_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getStatusAttribute()
    {
        return $this->successful ? 'Success' : 'Failed';
    }

    public function getAttemptTypeAttribute()
    {
        return $this->successful ? 'successful' : 'failed';
    }

    public function getBrowserAttribute()
    {
        if (!$this->user_agent) {
            return 'Unknown';
        }
        
        $userAgent = $this->user_agent;
        
        if (preg_match('/Chrome\/[0-9.]+/', $userAgent)) {
            return 'Chrome';
        } elseif (preg_match('/Firefox\/[0-9.]+/', $userAgent)) {
            return 'Firefox';
        } elseif (preg_match('/Safari\/[0-9.]+/', $userAgent)) {
            return 'Safari';
        } elseif (preg_match('/Edge\/[0-9.]+/', $userAgent)) {
            return 'Edge';
        } else {
            return 'Other';
        }
    }

    public function getLocationAttribute()
    {
        if (str_starts_with($this->ip_address, '127.0.0.1') || str_starts_with($this->ip_address, '192.168.')) {
            return 'Local Network';
        }
        return 'External';
    }

    public function scopeSuccessful($query)
    {
        return $query->where('successful', true);
    }

    public function scopeFailed($query)
    {
        return $query->where('successful', false);
    }

    public function scopeRecent($query, $hours = 24)
    {
        return $query->where('attempted_at', '>=', Carbon::now()->subHours($hours));
    }

    public function scopeByIp($query, $ip)
    {
        return $query->where('ip_address', $ip);
    }

    public function scopeByEmail($query, $email)
    {
        return $query->where('email', $email);
    }
}